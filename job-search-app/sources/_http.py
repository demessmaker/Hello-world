"""HTTP helper with exponential backoff retry."""

import logging
import time

import httpx

log = logging.getLogger(__name__)

DEFAULT_UA = (
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) "
    "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36"
)

RETRY_STATUSES = {429, 500, 502, 503, 504}


def get_json(url: str, params: dict | None = None, *, max_retries: int = 3,
             timeout: float = 30.0, headers: dict | None = None):
    """GET url, return parsed JSON. Retries on 5xx/429 and network errors.

    Returns None on final failure; callers should check for None."""
    hdr = {"User-Agent": DEFAULT_UA, "Accept": "application/json"}
    if headers:
        hdr.update(headers)

    delay = 2.0
    for attempt in range(max_retries + 1):
        try:
            with httpx.Client(timeout=timeout, headers=hdr) as c:
                resp = c.get(url, params=params)
            if resp.status_code in RETRY_STATUSES and attempt < max_retries:
                log.warning("GET %s -> %d, retry %d/%d in %.1fs",
                            url, resp.status_code, attempt + 1, max_retries, delay)
                time.sleep(delay)
                delay *= 2
                continue
            resp.raise_for_status()
            return resp.json()
        except (httpx.RequestError, httpx.HTTPStatusError) as exc:
            if attempt < max_retries:
                log.warning("GET %s error %s, retry %d/%d in %.1fs",
                            url, exc, attempt + 1, max_retries, delay)
                time.sleep(delay)
                delay *= 2
                continue
            log.warning("GET %s failed after %d retries: %s", url, max_retries, exc)
            return None
