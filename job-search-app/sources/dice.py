import logging
import time
from typing import Iterable

import httpx

from .base import JobPosting, Source

log = logging.getLogger(__name__)

SEARCH_URL = "https://service-api.dice.com/jobs/v1/search/jobs"


class DiceSource(Source):
    """Dice public search endpoint. Subject to change without notice."""

    name = "dice"

    def fetch(self) -> Iterable[JobPosting]:
        queries = self.config.get("queries", [])
        default_wp = self.config.get("default_workplace", ["Remote"])
        for q in queries:
            keyword = q if isinstance(q, str) else q.get("keyword", "")
            location = "" if isinstance(q, str) else q.get("location", "")
            workplace = default_wp if isinstance(q, str) else q.get("workplace_types", default_wp)
            yield from self._search(keyword, location, workplace)
            time.sleep(1)

    def _search(self, keyword: str, location: str, workplace_types) -> Iterable[JobPosting]:
        params = {
            "q": keyword,
            "countryCode2": "US",
            "workplaceTypeCodes": ",".join(workplace_types) if workplace_types else "",
            "postedDate": "THREE",
            "pageSize": 30,
            "page": 1,
        }
        if location:
            params["locationPrecision"] = location
        try:
            with httpx.Client(timeout=30, headers={"User-Agent": _UA}) as client:
                resp = client.get(SEARCH_URL, params=params)
                resp.raise_for_status()
                data = resp.json()
        except Exception as exc:
            log.warning("dice query %r failed: %s", keyword, exc)
            return

        for item in data.get("data", []):
            yield JobPosting(
                source="dice",
                source_job_id=str(item.get("id", "")),
                company=item.get("companyName", "") or "",
                title=item.get("title", "") or "",
                location=item.get("jobLocation", {}).get("displayName", "")
                if isinstance(item.get("jobLocation"), dict)
                else (item.get("jobLocation") or ""),
                compensation=item.get("salary", "") or "",
                url=item.get("detailsPageUrl", "") or "",
                description=item.get("summary", "") or "",
                query=keyword,
            )


_UA = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36"
