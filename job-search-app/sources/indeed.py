import logging
import time
from typing import Iterable
from urllib.parse import quote_plus

import httpx

from .base import JobPosting, Source

log = logging.getLogger(__name__)

SEARCH_URL = "https://ca.indeed.com/jobs"


class IndeedSource(Source):
    """Indeed scraping. Note: Indeed's official Publisher API is deprecated.
    This implementation uses the public HTML search page as a fallback. It will
    often be rate-limited or blocked in production — replace with an Indeed MCP
    connector or third-party aggregator (Adzuna, SerpAPI) when available."""

    name = "indeed"

    def fetch(self) -> Iterable[JobPosting]:
        queries = self.config.get("queries", [])
        country = self.config.get("country_code", "CA")
        for q in queries:
            yield from self._search(q, country)
            time.sleep(2)

    def _search(self, query: str, country: str) -> Iterable[JobPosting]:
        params = {"q": query, "l": "Canada" if country == "CA" else ""}
        try:
            with httpx.Client(
                timeout=30, headers={"User-Agent": _UA}, follow_redirects=True
            ) as client:
                resp = client.get(SEARCH_URL, params=params)
                resp.raise_for_status()
                html = resp.text
        except Exception as exc:
            log.warning("indeed query %r failed: %s", query, exc)
            return

        yield from _parse_indeed_html(html, query)


_UA = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36"


def _parse_indeed_html(html: str, query: str) -> Iterable[JobPosting]:
    from bs4 import BeautifulSoup

    soup = BeautifulSoup(html, "lxml")
    for card in soup.select("div.job_seen_beacon"):
        title_el = card.select_one("h2.jobTitle span")
        company_el = card.select_one("[data-testid='company-name']")
        location_el = card.select_one("[data-testid='text-location']")
        link_el = card.select_one("a.jcs-JobTitle")
        comp_el = card.select_one(".metadata.salary-snippet-container")
        summary_el = card.select_one(".job-snippet")
        job_id = link_el.get("data-jk", "") if link_el else ""
        href = link_el.get("href", "") if link_el else ""
        if href and href.startswith("/"):
            href = f"https://ca.indeed.com{href}"
        yield JobPosting(
            source="indeed",
            source_job_id=job_id,
            company=(company_el.get_text(strip=True) if company_el else "") or "",
            title=(title_el.get_text(strip=True) if title_el else "") or "",
            location=(location_el.get_text(strip=True) if location_el else "") or "",
            compensation=(comp_el.get_text(strip=True) if comp_el else "") or "",
            url=href,
            description=(summary_el.get_text(" ", strip=True) if summary_el else "") or "",
            query=query,
        )
