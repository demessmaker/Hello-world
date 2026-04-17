import logging
from typing import Iterable

import httpx

from .base import JobPosting, Source

log = logging.getLogger(__name__)

BASE_URL = "https://api.ashbyhq.com/posting-api/job-board/{org}"


class AshbySource(Source):
    name = "ashby"

    def fetch(self) -> Iterable[JobPosting]:
        orgs = self.config.get("orgs", [])
        location_filters = [s.lower() for s in self.config.get("location_filters", [])]
        for org in orgs:
            yield from self._fetch_org(org, location_filters)

    def _fetch_org(self, org: str, location_filters):
        try:
            with httpx.Client(timeout=30) as client:
                resp = client.get(BASE_URL.format(org=org))
                resp.raise_for_status()
                data = resp.json()
        except Exception as exc:
            log.warning("ashby %s failed: %s", org, exc)
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
