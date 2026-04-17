import logging
from typing import Iterable

from ._http import get_json
from .base import JobPosting, Source

log = logging.getLogger(__name__)

SEARCH_URL = "https://gcsservices.careers.microsoft.com/search/api/v1/search"


class MicrosoftSource(Source):
    """Microsoft Careers public search API."""

    name = "microsoft"

    def fetch(self) -> Iterable[JobPosting]:
        queries = self.config.get(
            "queries",
            ["Cloud Solution Architect", "Data & AI", "Principal Architect"],
        )
        for q in queries:
            yield from self._search(q)

    def _search(self, query: str) -> Iterable[JobPosting]:
        params = {
            "q": query,
            "lc": "Canada",
            "l": "en_us",
            "pg": 1,
            "pgSz": 20,
            "o": "Relevance",
            "flt": "true",
        }
        data = get_json(SEARCH_URL, params=params, max_retries=3)
        if not data:
            return

        jobs = (data.get("operationResult") or {}).get("result", {}).get("jobs", [])
        for job in jobs:
            job_id = str(job.get("jobId", ""))
            props = job.get("properties") or {}
            locations = props.get("locations") or []
            location = ", ".join(
                p.get("name", "") if isinstance(p, dict) else str(p) for p in locations
            ) or props.get("primaryLocation", "")
            yield JobPosting(
                source="microsoft",
                source_job_id=job_id,
                company="Microsoft",
                title=job.get("title", "") or "",
                location=location,
                url=f"https://jobs.careers.microsoft.com/global/en/job/{job_id}" if job_id else "",
                description=props.get("description", "") or "",
                query=query,
            )
