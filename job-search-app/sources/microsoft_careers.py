import logging
from typing import Iterable

import httpx

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
        try:
            with httpx.Client(timeout=30, headers={"User-Agent": _UA}) as client:
                resp = client.get(SEARCH_URL, params=params)
                resp.raise_for_status()
                data = resp.json()
        except Exception as exc:
            log.warning("microsoft query %r failed: %s", query, exc)
            return

        jobs = (data.get("operationResult") or {}).get("result", {}).get("jobs", [])
        for job in jobs:
            job_id = str(job.get("jobId", ""))
            yield JobPosting(
                source="microsoft",
                source_job_id=job_id,
                company="Microsoft",
                title=job.get("title", "") or "",
                location=", ".join(
                    p.get("name", "")
                    for p in (job.get("properties") or {}).get("locations", []) or []
                ) or (job.get("properties") or {}).get("primaryLocation", ""),
                url=f"https://jobs.careers.microsoft.com/global/en/job/{job_id}" if job_id else "",
                description=(job.get("properties") or {}).get("description", "") or "",
                query=query,
            )


_UA = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/124.0 Safari/537.36"
