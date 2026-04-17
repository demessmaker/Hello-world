import logging
import os
import smtplib
from email.message import EmailMessage

log = logging.getLogger(__name__)


def send(cfg: dict, subject: str, html: str, text: str) -> bool:
    password = os.environ.get(cfg.get("smtp_password_env", "SMTP_PASSWORD"))
    if not password:
        log.error("SMTP password env var %s not set", cfg.get("smtp_password_env"))
        return False

    msg = EmailMessage()
    msg["Subject"] = subject
    msg["From"] = cfg["from_address"]
    msg["To"] = cfg["to_address"]
    msg.set_content(text)
    msg.add_alternative(html, subtype="html")

    try:
        with smtplib.SMTP(cfg["smtp_host"], cfg["smtp_port"]) as s:
            s.starttls()
            s.login(cfg["smtp_user"], password)
            s.send_message(msg)
        log.info("email sent to %s", cfg["to_address"])
        return True
    except Exception as exc:
        log.error("failed to send email: %s", exc)
        return False
