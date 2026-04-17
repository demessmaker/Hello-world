from abc import ABC, abstractmethod
from dataclasses import dataclass, field
from typing import Iterable


@dataclass
class JobPosting:
    source: str
    source_job_id: str
    company: str
    title: str
    location: str = ""
    compensation: str = ""
    url: str = ""
    description: str = ""
    query: str = ""
    raw: dict = field(default_factory=dict)


class Source(ABC):
    name: str = "base"

    def __init__(self, config: dict):
        self.config = config

    @abstractmethod
    def fetch(self) -> Iterable[JobPosting]:
        """Yield all postings this source can produce for the configured queries."""
        raise NotImplementedError
