import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent.parent))

from processing.scorer import score_posting
from sources.base import JobPosting


PROFILE = {
    "primary_skills": ["AWS Bedrock", "GenAI", "RAG", "LangChain", "Anthropic Claude", "AWS S3"],
    "excluded_titles": ["Junior", "Intern", "Analyst"],
    "tier_1_companies": ["Google", "Microsoft", "Amazon", "Apple", "Meta"],
    "tier_2_companies": ["Caylent", "Anthropic", "Databricks", "Snowflake"],
    "tier_3_companies": ["Softchoice", "Deloitte"],
}


def test_principal_caylent_remote_canada_scores_high():
    p = JobPosting(
        source="greenhouse:caylent",
        source_job_id="1",
        company="Caylent",
        title="Principal Data Architect",
        location="Remote, Canada",
        description="AWS Bedrock, GenAI, RAG, LangChain, Anthropic Claude, AWS S3. 12+ years required.",
        compensation="",
    )
    score = score_posting(p, PROFILE).total
    assert score >= 70, f"expected top match, got {score}"


def test_excluded_title_scores_zero():
    p = JobPosting(
        source="indeed",
        source_job_id="2",
        company="Acme",
        title="Junior Data Analyst",
        location="Montreal",
        description="AWS Bedrock GenAI RAG LangChain",
    )
    assert score_posting(p, PROFILE).total == 0


def test_toronto_director_mid_high():
    p = JobPosting(
        source="indeed",
        source_job_id="3",
        company="Equinix",
        title="Director, AI Solutions",
        location="Toronto, ON",
        description="GenAI, RAG, LangChain, 10+ years",
        compensation="CAD 180,000 - 220,000",
    )
    assert score_posting(p, PROFILE).total >= 55


def test_us_remote_scores_lower_than_canada_remote():
    base = dict(
        source="indeed",
        company="Acme",
        title="Principal Architect",
        description="AWS Bedrock GenAI 10+ years",
    )
    us = JobPosting(source_job_id="4", location="Remote, US", **base)
    ca = JobPosting(source_job_id="5", location="Remote, Canada", **base)
    assert score_posting(ca, PROFILE).total > score_posting(us, PROFILE).total
