"""Entry point for the daily job search pipeline."""

import argparse
import json
import logging
import logging.handlers
import sys
from datetime import date, datetime
from pathlib import Path

import yaml

HERE = Path(__file__).parent
sys.path.insert(0, str(HERE))

from database import (
    Pipeline,
    SearchRun,
    SeenPosting,
    get_session,
    init_db,
)
from dashboard import generate as generate_dashboard
from emailer import compose, send
from processing import mark_closed_postings, normalize, score_posting, upsert_posting
from sources import (
    AdzunaSource,
    AmazonSource,
    AshbySource,
    DiceSource,
    GreenhouseSource,
    IndeedSource,
    MicrosoftSource,
)

log = logging.getLogger("jobsearcher")


def load_config(path: Path) -> dict:
    with open(path) as f:
        return yaml.safe_load(f)


def setup_logging(cfg: dict):
    level = getattr(logging, cfg.get("level", "INFO").upper(), logging.INFO)
    handlers = [logging.StreamHandler()]
    log_file = cfg.get("file")
    if log_file:
        p = Path(log_file)
        p.parent.mkdir(parents=True, exist_ok=True)
        handlers.append(
            logging.handlers.RotatingFileHandler(
                p,
                maxBytes=cfg.get("max_size_mb", 10) * 1024 * 1024,
                backupCount=cfg.get("backup_count", 5),
            )
        )
    logging.basicConfig(
        level=level,
        handlers=handlers,
        format="%(asctime)s %(levelname)s %(name)s: %(message)s",
    )


def build_sources(cfg: dict):
    srcs = cfg.get("sources", {})
    location_filters = cfg.get("filters", {}).get("locations", [])
    built = []

    if srcs.get("greenhouse", {}).get("enabled"):
        gh_cfg = dict(srcs["greenhouse"])
        gh_cfg["location_filters"] = location_filters
        built.append(GreenhouseSource(gh_cfg))
    if srcs.get("ashby", {}).get("enabled"):
        a_cfg = dict(srcs["ashby"])
        a_cfg["location_filters"] = location_filters
        built.append(AshbySource(a_cfg))
    if srcs.get("adzuna", {}).get("enabled"):
        built.append(AdzunaSource(srcs["adzuna"]))
    if srcs.get("indeed", {}).get("enabled"):
        built.append(IndeedSource(srcs["indeed"]))
    if srcs.get("dice", {}).get("enabled"):
        built.append(DiceSource(srcs["dice"]))
    if srcs.get("microsoft", {}).get("enabled"):
        built.append(MicrosoftSource(srcs["microsoft"]))
    if srcs.get("amazon", {}).get("enabled"):
        built.append(AmazonSource(srcs["amazon"]))
    return built


def run_pipeline(cfg: dict, dry_run: bool = False):
    engine = init_db(cfg["database"]["path"])
    session = get_session(engine)

    sources = build_sources(cfg)
    profile = cfg.get("profile", {})
    min_score = cfg["scoring"]["min_score_for_email"]
    top_threshold = cfg["scoring"]["top_match_threshold"]

    new_for_email = []
    scanned = 0
    source_names = []

    for src in sources:
        source_names.append(src.name)
        count = 0
        new_count = 0
        try:
            for posting in src.fetch():
                posting = normalize(posting)
                if not posting.title or not posting.company:
                    continue
                scanned += 1
                count += 1
                row, is_new = upsert_posting(session, posting)
                breakdown = score_posting(posting, profile)
                row.score = breakdown.total

                if is_new and breakdown.total >= min_score:
                    new_count += 1
                    row.emailed_date = date.today()
                    new_for_email.append(
                        {
                            "id": row.id,
                            "title": row.title,
                            "company": row.company,
                            "location": row.location,
                            "compensation": row.compensation,
                            "url": row.url,
                            "score": breakdown.total,
                            "snippet": (posting.description or "")[:240],
                            "why": _why(breakdown, profile, posting),
                        }
                    )
            session.add(
                SearchRun(
                    source=src.name,
                    query=",".join(src.config.get("queries", [])) or src.name,
                    results_count=count,
                    new_count=new_count,
                )
            )
        except Exception as exc:
            log.exception("source %s failed", src.name)
            session.add(
                SearchRun(
                    source=src.name,
                    query=src.name,
                    results_count=count,
                    new_count=new_count,
                    error=str(exc),
                )
            )

    closed = mark_closed_postings(session, threshold_days=3)
    active = (
        session.query(SeenPosting)
        .filter(SeenPosting.status == "active", SeenPosting.score >= min_score)
        .order_by(SeenPosting.score.desc())
        .limit(25)
        .all()
    )

    session.commit()

    new_for_email.sort(key=lambda p: p["score"], reverse=True)
    context = {
        "run_date": date.today().isoformat(),
        "new_postings": new_for_email,
        "pipeline_active": [
            {"company": r.company, "title": r.title, "location": r.location} for r in active
        ],
        "pipeline_closed": [{"company": r.company, "title": r.title} for r in closed],
        "min_score": min_score,
        "top_match_threshold": top_threshold,
        "stats": {
            "scanned": scanned,
            "new_matches": len(new_for_email),
            "sources": source_names,
        },
    }

    subject, html, text = compose(context)

    dashboard_path = Path(cfg.get("dashboard", {}).get("output_path", "docs/index.html"))
    try:
        generate_dashboard(cfg, dashboard_path)
        log.info("dashboard written to %s", dashboard_path)
    except Exception:
        log.exception("failed to generate dashboard")

    if dry_run:
        print(f"--- SUBJECT ---\n{subject}\n\n--- TEXT ---\n{text}")
        return

    send(cfg["email"], subject, html, text)


def show_status(cfg: dict):
    engine = init_db(cfg["database"]["path"])
    session = get_session(engine)
    active = (
        session.query(SeenPosting)
        .filter(SeenPosting.status == "active")
        .order_by(SeenPosting.score.desc().nullslast())
        .limit(50)
        .all()
    )
    rows = [
        {
            "id": r.id,
            "score": r.score,
            "company": r.company,
            "title": r.title,
            "location": r.location,
            "url": r.url,
        }
        for r in active
    ]
    print(json.dumps(rows, indent=2, default=str))


def mark_applied(cfg: dict, posting_id: int):
    engine = init_db(cfg["database"]["path"])
    session = get_session(engine)
    row = session.query(SeenPosting).filter(SeenPosting.id == posting_id).first()
    if not row:
        print(f"posting {posting_id} not found")
        return
    row.status = "applied"
    entry = Pipeline(posting_id=row.id, status="applied", updated_at=datetime.utcnow())
    session.add(entry)
    session.commit()
    print(f"marked {posting_id} as applied: {row.company} — {row.title}")


def _why(breakdown, profile, posting) -> str:
    parts = []
    if breakdown.title >= 20:
        parts.append("target seniority title")
    if breakdown.company >= 15:
        parts.append("priority target company")
    if breakdown.skill >= 15:
        parts.append("strong primary-skill overlap")
    if breakdown.location >= 13:
        parts.append("preferred location")
    if breakdown.compensation >= 8:
        parts.append("comp in target range")
    return "; ".join(parts) or "meets minimum criteria"


def main():
    parser = argparse.ArgumentParser(description="Daily job search pipeline")
    parser.add_argument("--config", default=str(HERE / "config.yaml"))
    parser.add_argument("--init", action="store_true", help="Initialize the database")
    parser.add_argument("--run-now", action="store_true", help="Run the pipeline and email")
    parser.add_argument("--dry-run", action="store_true", help="Run but print email instead of sending")
    parser.add_argument("--status", action="store_true", help="Show current pipeline")
    parser.add_argument("--applied", type=int, metavar="ID", help="Mark posting ID as applied")
    parser.add_argument("--dashboard", action="store_true", help="Regenerate the static HTML dashboard")
    args = parser.parse_args()

    cfg = load_config(Path(args.config))
    setup_logging(cfg.get("logging", {}))

    if args.init:
        init_db(cfg["database"]["path"])
        print(f"initialized db at {cfg['database']['path']}")
        return
    if args.status:
        show_status(cfg)
        return
    if args.applied:
        mark_applied(cfg, args.applied)
        return
    if args.dashboard:
        out = Path(cfg.get("dashboard", {}).get("output_path", "docs/index.html"))
        generate_dashboard(cfg, out)
        print(f"dashboard written to {out}")
        return
    if args.run_now or args.dry_run:
        run_pipeline(cfg, dry_run=args.dry_run)
        return

    parser.print_help()


if __name__ == "__main__":
    main()
