import sys
import tempfile
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent.parent))

from database import get_session, init_db
from processing.deduplicator import upsert_posting
from sources.base import JobPosting


def test_upsert_new_then_existing():
    with tempfile.TemporaryDirectory() as tmp:
        engine = init_db(f"{tmp}/test.db")
        session = get_session(engine)

        p = JobPosting(
            source="greenhouse:caylent",
            source_job_id="abc",
            company="Caylent",
            title="Principal Data Architect",
            location="Remote, Canada",
        )
        row1, is_new1 = upsert_posting(session, p)
        assert is_new1 is True
        assert row1.id is not None

        row2, is_new2 = upsert_posting(session, p)
        assert is_new2 is False
        assert row2.id == row1.id


def test_dedup_by_company_title_location():
    with tempfile.TemporaryDirectory() as tmp:
        engine = init_db(f"{tmp}/test.db")
        session = get_session(engine)

        p1 = JobPosting(
            source="indeed",
            source_job_id="ind-1",
            company="Caylent",
            title="Principal Data Architect",
            location="Remote, Canada",
        )
        p2 = JobPosting(
            source="greenhouse:caylent",
            source_job_id="gh-1",
            company="Caylent",
            title="Principal Data Architect",
            location="Remote, Canada",
        )
        _, is_new1 = upsert_posting(session, p1)
        _, is_new2 = upsert_posting(session, p2)
        assert is_new1 is True
        assert is_new2 is False
