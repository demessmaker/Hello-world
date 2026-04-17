from datetime import date, datetime

from sqlalchemy import (
    Column,
    Date,
    DateTime,
    ForeignKey,
    Integer,
    String,
    Text,
    create_engine,
)
from sqlalchemy.orm import declarative_base, relationship, sessionmaker

Base = declarative_base()


class SeenPosting(Base):
    __tablename__ = "seen_postings"

    id = Column(Integer, primary_key=True, autoincrement=True)
    source = Column(String, nullable=False, index=True)
    source_job_id = Column(String, index=True)
    company = Column(String, nullable=False, index=True)
    title = Column(String, nullable=False)
    location = Column(String)
    compensation = Column(String)
    url = Column(String)
    description = Column(Text)
    score = Column(Integer)
    first_seen_date = Column(Date, nullable=False, default=date.today)
    last_seen_date = Column(Date, nullable=False, default=date.today)
    status = Column(String, default="active")
    emailed_date = Column(Date)
    notes = Column(Text)

    pipeline_entries = relationship("Pipeline", back_populates="posting")


class SearchRun(Base):
    __tablename__ = "search_runs"

    id = Column(Integer, primary_key=True, autoincrement=True)
    run_date = Column(DateTime, nullable=False, default=datetime.utcnow)
    source = Column(String, nullable=False)
    query = Column(String, nullable=False)
    results_count = Column(Integer, default=0)
    new_count = Column(Integer, default=0)
    error = Column(Text)


class Pipeline(Base):
    __tablename__ = "pipeline"

    id = Column(Integer, primary_key=True, autoincrement=True)
    posting_id = Column(Integer, ForeignKey("seen_postings.id"), nullable=False)
    status = Column(String, default="new")
    deadline = Column(Date)
    priority = Column(String)
    notes = Column(Text)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    posting = relationship("SeenPosting", back_populates="pipeline_entries")


def init_db(db_path: str):
    engine = create_engine(f"sqlite:///{db_path}", future=True)
    Base.metadata.create_all(engine)
    return engine


def get_session(engine):
    return sessionmaker(bind=engine, future=True)()
