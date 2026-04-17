from datetime import date
from pathlib import Path

from jinja2 import Environment, FileSystemLoader, select_autoescape

TEMPLATE_DIR = Path(__file__).parent / "templates"

_env = Environment(
    loader=FileSystemLoader(str(TEMPLATE_DIR)),
    autoescape=select_autoescape(["html", "xml"]),
)


def compose(context: dict) -> tuple[str, str, str]:
    """Return (subject, html_body, text_body)."""
    today = date.today().isoformat()
    new_count = len(context.get("new_postings", []))
    subject = f"Job Search Update — {today} — {new_count} New Postings"
    html = _env.get_template("daily_report.html").render(**context)
    text = _env.get_template("daily_report.txt").render(**context)
    return subject, html, text
