from datetime import date, timedelta

from database.models import SeenPosting


def mark_closed_postings(session, threshold_days: int = 3):
    """Mark active postings as closed if not seen for threshold_days."""
    cutoff = date.today() - timedelta(days=threshold_days)
    rows = (
        session.query(SeenPosting)
        .filter(SeenPosting.status == "active", SeenPosting.last_seen_date < cutoff)
        .all()
    )
    for row in rows:
        row.status = "closed"
    return rows
