# Daily Job Search Automation

An automated pipeline that runs daily job searches across multiple sources,
filters and scores results against a candidate profile, deduplicates against
previously seen postings, and delivers a formatted email summary.

Target: Senior Data Architect / Principal-level AI & Cloud professional,
Montreal, QC.

## Features

- Multi-source fetch: Greenhouse (Caylent, Databricks), Ashby (Anthropic),
  Microsoft Careers, Amazon Jobs, Indeed (scraped), Dice.
- Persistent SQLite store of seen postings, search runs, and an application
  pipeline.
- Relevance scoring engine (title, company tier, skill overlap, location,
  compensation, seniority).
- Deduplication against prior runs (by platform job ID + normalized
  company/title/location).
- Closed-posting detection (3-day absence).
- HTML + plain-text email via SMTP.

## Install

```bash
cd job-search-app
pip install -r requirements.txt
python main.py --init
```

## Configure

Edit `config.yaml`:

- `candidate.email` → where the report is sent.
- `email.smtp_*` → SMTP settings. Password is read from the env var named by
  `smtp_password_env` (default `SMTP_PASSWORD`).
- `sources.*.enabled` → toggle each source.
- `profile.*` → skills, target titles, tier 1/2/3 companies.

Export the SMTP password:

```bash
export SMTP_PASSWORD="your-app-password"
```

## Run

```bash
# Dry run — prints the email to stdout, sends nothing
python main.py --dry-run

# Real run — fetches, scores, emails
python main.py --run-now

# View active pipeline
python main.py --status

# Mark a posting as applied
python main.py --applied 42
```

## Schedule

### Option A — GitHub Actions (recommended, free)

Workflow: `.github/workflows/daily-job-search.yml`. Runs 11:00 UTC (07:00 EDT)
Mon–Fri and also on-demand via **Run workflow** in the Actions tab.

Setup:

1. Push this repo to GitHub.
2. Settings → Secrets and variables → Actions → **New repository secret**:
   - `SMTP_PASSWORD` = your SMTP app password.
3. Edit `job-search-app/config.yaml` and commit:
   - `email.smtp_host`, `smtp_port`, `smtp_user`, `from_address`, `to_address`.
   - `candidate.email`.
4. The SQLite DB is persisted between runs via `actions/cache` and also
   uploaded as an artifact (30-day retention) so nothing is lost if the cache
   is evicted.

Trigger a one-off run from the Actions tab; use the `dry_run` input to test
without sending email.

### Option B — Local cron

```
0 7 * * 1-5 cd /path/to/job-search-app && /usr/bin/python3 main.py --run-now >> logs/cron.log 2>&1
```

## Testing

```bash
pip install pytest
pytest tests/
```

## Notes on sources

- **Indeed** no longer offers a public Publisher API. The included scraper
  works against the HTML search page but is fragile and may be rate-limited.
  Swap in an Indeed MCP connector, Adzuna, or SerpAPI for production.
- **Dice** public endpoint is undocumented and may change without notice.
- **Google / Snowflake / Apple / Meta** career sites render client-side; add
  Playwright scrapers in `sources/` when needed.

## Architecture

```
main.py ──► sources/* ──► processing/{normalizer,scorer,deduplicator}
                    │
                    └──► database/models.py (SQLite)
                    │
                    └──► emailer/composer.py (Jinja2) ──► emailer/sender.py (SMTP)
```

See the spec document for full design details.
