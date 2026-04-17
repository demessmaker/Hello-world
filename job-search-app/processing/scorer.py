"""Relevance scoring engine (see spec §5.2)."""

import re
from dataclasses import dataclass


@dataclass
class ScoreBreakdown:
    title: int
    company: int
    skill: int
    location: int
    compensation: int
    seniority: int

    @property
    def total(self) -> int:
        return (
            self.title
            + self.company
            + self.skill
            + self.location
            + self.compensation
            + self.seniority
        )


def score_posting(posting, profile: dict) -> ScoreBreakdown:
    title = (posting.title or "").lower()
    company = (posting.company or "").lower()
    loc = (posting.location or "").lower()
    desc = (posting.description or "").lower()
    comp = (posting.compensation or "").lower()

    excluded = [t.lower() for t in profile.get("excluded_titles", [])]
    if any(x in title for x in excluded):
        return ScoreBreakdown(0, 0, 0, 0, 0, 0)

    return ScoreBreakdown(
        title=_title_score(title),
        company=_company_score(company, profile),
        skill=_skill_score(desc + " " + title, profile),
        location=_location_score(loc),
        compensation=_compensation_score(comp),
        seniority=_seniority_score(desc),
    )


def _title_score(title: str) -> int:
    high = ["principal", "director", "head of", "vp", "vice president", "lead architect"]
    mid_high = ["senior manager", "practice director", "practice lead"]
    mid = ["senior architect", "senior solutions architect", "staff"]
    low = ["architect", "manager"]
    if any(k in title for k in high):
        return 25
    if any(k in title for k in mid_high):
        return 20
    if any(k in title for k in mid):
        return 15
    if any(k in title for k in low):
        return 10
    return 0


def _company_score(company: str, profile: dict) -> int:
    def hit(tier):
        return any(c.lower() in company for c in profile.get(tier, []))

    if hit("tier_1_companies"):
        return 20
    if hit("tier_2_companies"):
        return 18
    if hit("tier_3_companies"):
        return 15
    consulting = ["deloitte", "accenture", "kpmg", "pwc", "ernst", "ibm", "capgemini"]
    if any(c in company for c in consulting):
        return 8
    staffing = ["staffing", "recruit", "placement", "talent"]
    if any(c in company for c in staffing):
        return 3
    if not company:
        return 5
    return 5


def _skill_score(text: str, profile: dict) -> int:
    hits = 0
    for skill in profile.get("primary_skills", []):
        if re.search(rf"\b{re.escape(skill.lower())}\b", text):
            hits += 1
    if hits >= 5:
        return 20
    if hits >= 3:
        return 15
    if hits >= 1:
        return 10
    return 0


def _location_score(loc: str) -> int:
    if not loc:
        return 5
    if "montreal" in loc or "montréal" in loc:
        return 15
    if "remote" in loc and ("canada" in loc or "qc" in loc or "on" in loc):
        return 14
    if "laval" in loc:
        return 13
    if "toronto" in loc:
        return 10
    if "ottawa" in loc:
        return 9
    if "canada" in loc or " qc" in loc or ", qc" in loc or " on" in loc:
        return 7
    if "remote" in loc and ("us" in loc or "united states" in loc or "usa" in loc):
        return 5
    if "remote" in loc:
        return 8
    return 0


_SALARY_RE = re.compile(r"\$?\s*([0-9]{2,3})(?:[,.]?([0-9]{3}))?")


def _compensation_score(comp: str) -> int:
    if not comp:
        return 5
    nums = []
    for m in _SALARY_RE.finditer(comp):
        whole = m.group(1) + (m.group(2) or "")
        try:
            n = int(whole)
            if n > 1000:
                nums.append(n)
        except ValueError:
            continue
    if not nums:
        return 5
    top = max(nums)
    is_usd = "usd" in comp or "$ us" in comp
    cad_eq = top if not is_usd else int(top * 1.35)
    if cad_eq >= 200_000:
        return 10
    if cad_eq >= 170_000:
        return 8
    if cad_eq >= 140_000:
        return 4
    return 0


_YEARS_RE = re.compile(r"(\d{1,2})\s*\+?\s*(?:to|-)?\s*(\d{1,2})?\s*\+?\s*years?")


def _seniority_score(text: str) -> int:
    best = 0
    for m in _YEARS_RE.finditer(text):
        low = int(m.group(1))
        high = int(m.group(2)) if m.group(2) else low
        yrs = max(low, high)
        if yrs >= 10:
            best = max(best, 10)
        elif yrs >= 7:
            best = max(best, 8)
        elif yrs >= 5:
            best = max(best, 5)
    return best
