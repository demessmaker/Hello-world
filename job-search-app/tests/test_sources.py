import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent.parent))

from sources.adzuna import AdzunaSource
from sources.ashby import AshbySource


def test_adzuna_disabled_without_credentials(monkeypatch):
    monkeypatch.delenv("ADZUNA_APP_ID", raising=False)
    monkeypatch.delenv("ADZUNA_APP_KEY", raising=False)
    src = AdzunaSource({"country": "ca", "queries": ["test"], "locations": [""]})
    assert list(src.fetch()) == []


def test_ashby_no_orgs_returns_empty():
    src = AshbySource({"orgs": []})
    assert list(src.fetch()) == []
