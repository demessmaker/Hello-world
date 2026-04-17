# Daily Job Search Automation

An automated pipeline that runs daily job searches across multiple sources,
filters and scores results against a candidate profile, deduplicates against
previously seen postings, and delivers a formatted email summary.

Target: Senior Data Architect / Principal-level AI & Cloud professional,
Montreal, QC.

## Features

- Multi-source fetch: Greenhouse (Caylent, Databricks, Anthropic), Adzuna
  (aggregator across thousands of boards), Microsoft Careers, Amazon Jobs.
  Ashby, Indeed-scrape and Dice are included but disabled by default.
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

Export secrets:

```bash
export SMTP_PASSWORD="your-app-password"
export ADZUNA_APP_ID="xxxxxxxx"      # free at developer.adzuna.com
export ADZUNA_APP_KEY="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
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
   - `ADZUNA_APP_ID`, `ADZUNA_APP_KEY` = free tier at developer.adzuna.com.
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

- **Adzuna** is the primary aggregator (free 250 calls/month). Fills the gap
  left by Indeed's closed Publisher API.
- **Indeed** HTML scraper is included but disabled by default — frequently
  returns 403. Swap in an MCP connector or SerpAPI if you need it.
- **Dice** public endpoint is undocumented and disabled by default.
- **Google / Snowflake / Apple / Meta** career sites render client-side; add
  Playwright scrapers in `sources/` when needed.
- All HTTP sources share `sources/_http.py` which retries 429/5xx and network
  errors with exponential backoff.

## Architecture

```
main.py ──► sources/* ──► processing/{normalizer,scorer,deduplicator}
                    │
                    └──► database/models.py (SQLite)
                    │
                    └──► emailer/composer.py (Jinja2) ──► emailer/sender.py (SMTP)
```

See the spec document for full design details.
