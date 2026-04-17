"""Static HTML dashboard generator.

Reads from the seen_postings table and emits a single-page dashboard with
embedded JSON. Client-side JS handles filtering, sorting, and drilldown.
"""

import json
from datetime import datetime
from pathlib import Path

from jinja2 import Environment, FileSystemLoader, select_autoescape

from database import SeenPosting, get_session, init_db

TEMPLATE_DIR = Path(__file__).parent / "templates"

_env = Environment(
    loader=FileSystemLoader(str(TEMPLATE_DIR)),
    autoescape=select_autoescape(["html", "xml"]),
)


def generate(cfg: dict, output_path: Path, min_score: int | None = None) -> Path:
    """Generate dashboard HTML. Returns the path written."""
    if min_score is None:
        min_score = cfg["scoring"]["min_score_for_email"]
    top_threshold = cfg["scoring"]["top_match_threshold"]

    engine = init_db(cfg["database"]["path"])
    session = get_session(engine)

    rows = (
        session.query(SeenPosting)
        .filter(SeenPosting.status.in_(["active", "applied"]))
        .order_by(SeenPosting.score.desc().nullslast())
        .all()
    )

    postings = [
        {
            "id": r.id,
            "score": r.score or 0,
            "company": r.company or "",
            "title": r.title or "",
            "location": r.location or "",
            "compensation": r.compensation or "",
            "url": r.url or "",
            "source": r.source or "",
            "status": r.status or "",
            "first_seen": r.first_seen_date.isoformat() if r.first_seen_date else "",
            "last_seen": r.last_seen_date.isoformat() if r.last_seen_date else "",
            "description": (r.description or "")[:4000],
        }
        for r in rows
    ]

    # summary breakdowns
    companies = _bucket(postings, "company")
    sources = _bucket(postings, "source")
    locations = _bucket_location(postings)
    relevant = [p for p in postings if p["score"] >= min_score]
    top = [p for p in postings if p["score"] >= top_threshold]

    context = {
        "generated_at": datetime.utcnow().isoformat(timespec="seconds") + "Z",
        "min_score": min_score,
        "top_threshold": top_threshold,
        "postings_json": json.dumps(postings),
        "stats": {
            "total": len(postings),
            "relevant": len(relevant),
            "top": len(top),
            "companies": len(companies),
            "sources": len(sources),
        },
        "companies": sorted(companies.items(), key=lambda x: -x[1])[:15],
        "sources": sorted(sources.items(), key=lambda x: -x[1]),
        "locations": sorted(locations.items(), key=lambda x: -x[1])[:10],
    }

    html = _env.get_template("index.html").render(**context)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(html, encoding="utf-8")
    return output_path


def _bucket(postings, key):
    out = {}
    for p in postings:
        if p["score"] < 40:
            continue
        k = p[key] or "(unknown)"
        out[k] = out.get(k, 0) + 1
    return out


def _bucket_location(postings):
    out = {}
    for p in postings:
        if p["score"] < 40:
            continue
        loc = (p["location"] or "").lower()
        bucket = "Other"
        if "montr" in loc or "québec" in loc or "quebec" in loc or ", qc" in loc:
            bucket = "Montreal / QC"
        elif "toronto" in loc or "ottawa" in loc or ", on" in loc:
            bucket = "Ontario"
        elif "vancouver" in loc or ", bc" in loc:
            bucket = "BC"
        elif "canada" in loc:
            bucket = "Canada (other)"
        elif "remote" in loc and ("us" in loc or "united states" in loc or any(s in loc for s in ["- california","- texas","- new york","- washington","- oregon","- colorado","- illinois","- georgia","- ohio","- arizona","- massachusetts"])):
            bucket = "US Remote"
        elif "remote" in loc:
            bucket = "Remote (other)"
        else:
            bucket = "International"
        out[bucket] = out.get(bucket, 0) + 1
    return out
