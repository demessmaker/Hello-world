import logging
from typing import Iterable

from ._http import get_json
from .base import JobPosting, Source

log = logging.getLogger(__name__)

BASE_URL = "https://api.ashbyhq.com/posting-api/job-board/{org}"


class AshbySource(Source):
    """Fetches from Ashby's public job board API.

    Note: Ashby slugs are case-sensitive. If a lowercase slug 404s we retry
    with the title-cased variant."""

    name = "ashby"

    def fetch(self) -> Iterable[JobPosting]:
        orgs = self.config.get("orgs", [])
        location_filters = [s.lower() for s in self.config.get("location_filters", [])]
        for org in orgs:
            yield from self._fetch_org(org, location_filters)

    def _fetch_org(self, org: str, location_filters):
        data = get_json(BASE_URL.format(org=org), max_retries=1)
        if not data and org.lower() == org:
            data = get_json(BASE_URL.format(org=org.title()), max_retries=1)
        if not data:
            return

        for job in data.get("jobs", []):
            loc = job.get("locationName") or ""
            if location_filters and not any(f in loc.lower() for f in location_filters):
                continue
            yield JobPosting(
                source=f"ashby:{org}",
                source_job_id=job.get("id", ""),
                company=org.title(),
                title=job.get("title", ""),
                location=loc,
                url=job.get("jobUrl", ""),
                description=job.get("descriptionPlain", "") or "",
                query=f"ashby:{org}",
                raw={"department": job.get("departmentName")},
            )
