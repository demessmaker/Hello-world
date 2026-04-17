import logging
from typing import Iterable

from ._http import get_json
from .base import JobPosting, Source

log = logging.getLogger(__name__)

SEARCH_URL = "https://www.amazon.jobs/en/search.json"


class AmazonSource(Source):
    """Amazon jobs public JSON search."""

    name = "amazon"

    def fetch(self) -> Iterable[JobPosting]:
        queries = self.config.get(
            "queries",
            ["solutions architect AI", "principal data architect", "GenAI architect"],
        )
        for q in queries:
            yield from self._search(q)

    def _search(self, query: str) -> Iterable[JobPosting]:
        params = {
            "normalized_country_code[]": "CAN",
            "radius": "24km",
            "facets[]": ["normalized_country_code", "normalized_state_name", "job_category"],
            "offset": 0,
            "result_limit": 25,
            "sort": "recent",
            "base_query": query,
        }
        data = get_json(SEARCH_URL, params=params)
        if not data:
            return

        for job in data.get("jobs", []):
            yield JobPosting(
                source="amazon",
                source_job_id=str(job.get("id_icims") or job.get("id", "")),
                company="Amazon",
                title=job.get("title", "") or "",
                location=job.get("normalized_location", "") or job.get("location", ""),
                url=("https://www.amazon.jobs" + job["job_path"]) if job.get("job_path") else "",
                description=job.get("description_short", "") or "",
                query=query,
            )
