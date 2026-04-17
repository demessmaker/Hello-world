import re

from sources.base import JobPosting


def normalize(p: JobPosting) -> JobPosting:
    p.company = _clean(p.company)
    p.title = _clean(p.title)
    p.location = _clean(p.location)
    p.compensation = _clean(p.compensation)
    p.description = _clean(p.description)
    return p


_WS = re.compile(r"\s+")


def _clean(s: str) -> str:
    if not s:
        return ""
    return _WS.sub(" ", s).strip()
