import logging
from typing import Iterable

from ._http import get_json
from .base import JobPosting, Source

log = logging.getLogger(__name__)

BASE_URL = "https://boards-api.greenhouse.io/v1/boards/{board}/jobs"


class GreenhouseSource(Source):
    name = "greenhouse"

    def fetch(self) -> Iterable[JobPosting]:
        boards = self.config.get("boards", [])
        location_filters = [s.lower() for s in self.config.get("location_filters", [])]
        for board in boards:
            yield from self._fetch_board(board, location_filters)

    def _fetch_board(self, board: str, location_filters):
        data = get_json(BASE_URL.format(board=board), params={"content": "true"})
        if not data:
            return
        for job in data.get("jobs", []):
            loc = (job.get("location") or {}).get("name", "") or ""
            if location_filters and not any(f in loc.lower() for f in location_filters):
                continue
            yield JobPosting(
                source=f"greenhouse:{board}",
                source_job_id=str(job.get("id")),
                company=(job.get("company_name") or board.title()),
                title=job.get("title", ""),
                location=loc,
                url=job.get("absolute_url", ""),
                description=_strip_html(job.get("content", "")),
                query=f"greenhouse:{board}",
                raw={"greenhouse_id": job.get("id")},
            )


def _strip_html(s: str) -> str:
    if not s:
        return ""
    import html
    from bs4 import BeautifulSoup

    return BeautifulSoup(html.unescape(s), "lxml").get_text(" ", strip=True)
