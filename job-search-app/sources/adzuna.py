"""Adzuna public API source.

Adzuna aggregates postings from thousands of sites (including Indeed, LinkedIn,
Workopolis, etc) and offers a free developer tier at
https://developer.adzuna.com/  (app_id + app_key required).

Set env vars ADZUNA_APP_ID and ADZUNA_APP_KEY, or override the env var names
in config under sources.adzuna.{app_id_env, app_key_env}."""

import logging
import os
from typing import Iterable

from ._http import get_json
from .base import JobPosting, Source

log = logging.getLogger(__name__)

BASE_URL = "https://api.adzuna.com/v1/api/jobs/{country}/search/1"


class AdzunaSource(Source):
    name = "adzuna"

    def fetch(self) -> Iterable[JobPosting]:
        app_id = os.environ.get(self.config.get("app_id_env", "ADZUNA_APP_ID"))
        app_key = os.environ.get(self.config.get("app_key_env", "ADZUNA_APP_KEY"))
        if not app_id or not app_key:
            log.warning("adzuna disabled: ADZUNA_APP_ID/KEY env vars not set")
            return

        country = self.config.get("country", "ca")
        queries = self.config.get("queries", [])
        locations = self.config.get("locations", [""])
        per_page = self.config.get("results_per_page", 20)
        max_days = self.config.get("max_days_old", 7)

        for q in queries:
            for loc in locations:
                yield from self._search(
                    country, q, loc, per_page, max_days, app_id, app_key
                )

    def _search(self, country, query, location, per_page, max_days, app_id, app_key):
        params = {
            "app_id": app_id,
            "app_key": app_key,
            "what": query,
            "results_per_page": per_page,
            "max_days_old": max_days,
            "content-type": "application/json",
        }
        if location:
            params["where"] = location
        data = get_json(BASE_URL.format(country=country), params=params)
        if not data:
            return
        for job in data.get("results", []):
            salary = ""
            smin, smax = job.get("salary_min"), job.get("salary_max")
            if smin and smax:
                salary = f"{int(smin):,} - {int(smax):,} {job.get('salary_is_predicted','') and '(est.)' or ''}"
            elif smin:
                salary = f"{int(smin):,}+"
            company = (job.get("company") or {}).get("display_name", "") or ""
            loc_name = (job.get("location") or {}).get("display_name", "") or ""
            yield JobPosting(
                source="adzuna",
                source_job_id=str(job.get("id", "")),
                company=company,
                title=job.get("title", "") or "",
                location=loc_name,
                compensation=salary,
                url=job.get("redirect_url", "") or "",
                description=job.get("description", "") or "",
                query=query,
            )
