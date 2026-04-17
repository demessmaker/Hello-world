from datetime import date

from sqlalchemy import func

from database.models import SeenPosting
from sources.base import JobPosting


def upsert_posting(session, posting: JobPosting) -> tuple[SeenPosting, bool]:
    """Insert new posting or update last_seen_date. Returns (row, is_new)."""
    existing = _find_existing(session, posting)
    if existing:
        existing.last_seen_date = date.today()
        if not existing.description and posting.description:
            existing.description = posting.description
        return existing, False

    row = SeenPosting(
        source=posting.source,
        source_job_id=posting.source_job_id or None,
        company=posting.company,
        title=posting.title,
        location=posting.location,
        compensation=posting.compensation,
        url=posting.url,
        description=posting.description,
        first_seen_date=date.today(),
        last_seen_date=date.today(),
        status="active",
    )
    session.add(row)
    session.flush()
    return row, True


def _find_existing(session, posting: JobPosting):
    if posting.source_job_id:
        hit = (
            session.query(SeenPosting)
            .filter(
                SeenPosting.source == posting.source,
                SeenPosting.source_job_id == posting.source_job_id,
            )
            .first()
        )
        if hit:
            return hit

    return (
        session.query(SeenPosting)
        .filter(
            func.lower(SeenPosting.company) == (posting.company or "").lower(),
            func.lower(SeenPosting.title) == (posting.title or "").lower(),
            func.lower(SeenPosting.location) == (posting.location or "").lower(),
        )
        .first()
    )
