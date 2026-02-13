#!/usr/bin/env python3
"""
ISBN Lookup Utility

Receives a list of ISBN numbers and returns JSON containing book information.
Uses the Open Library API (https://openlibrary.org) for lookups.

Usage:
    # As a CLI tool:
    python isbn_lookup.py 9780134685991 9780201633610 9780596007126

    # As a module:
    from isbn_lookup import lookup_isbns
    results = lookup_isbns(["9780134685991", "9780201633610"])
"""

import argparse
import json
import re
import sys
import urllib.error
import urllib.request


def normalize_isbn(isbn: str) -> str:
    """Remove hyphens and spaces from an ISBN string."""
    return re.sub(r"[-\s]", "", isbn.strip())


def validate_isbn(isbn: str) -> bool:
    """Validate that a string is a properly formatted ISBN-10 or ISBN-13."""
    isbn = normalize_isbn(isbn)

    if len(isbn) == 10:
        return _validate_isbn10(isbn)
    elif len(isbn) == 13:
        return _validate_isbn13(isbn)
    return False


def _validate_isbn10(isbn: str) -> bool:
    """Validate an ISBN-10 check digit."""
    if not re.match(r"^\d{9}[\dXx]$", isbn):
        return False
    total = sum((10 - i) * (10 if c in "Xx" else int(c)) for i, c in enumerate(isbn))
    return total % 11 == 0


def _validate_isbn13(isbn: str) -> bool:
    """Validate an ISBN-13 check digit."""
    if not isbn.isdigit():
        return False
    total = sum(int(c) * (1 if i % 2 == 0 else 3) for i, c in enumerate(isbn))
    return total % 10 == 0


def lookup_isbn(isbn: str) -> dict:
    """
    Look up a single ISBN using the Open Library API.

    Args:
        isbn: An ISBN-10 or ISBN-13 string.

    Returns:
        A dict with book information or an error entry.
    """
    isbn = normalize_isbn(isbn)

    if not validate_isbn(isbn):
        return {"isbn": isbn, "error": "Invalid ISBN format"}

    url = f"https://openlibrary.org/api/books?bibkeys=ISBN:{isbn}&format=json&jscmd=data"

    try:
        req = urllib.request.Request(url, headers={"User-Agent": "isbn-lookup-utility/1.0"})
        with urllib.request.urlopen(req, timeout=10) as response:
            data = json.loads(response.read().decode("utf-8"))
    except urllib.error.URLError as e:
        return {"isbn": isbn, "error": f"Network error: {e.reason}"}
    except Exception as e:
        return {"isbn": isbn, "error": str(e)}

    key = f"ISBN:{isbn}"
    if key not in data:
        return {"isbn": isbn, "error": "Book not found"}

    book = data[key]

    return {
        "isbn": isbn,
        "title": book.get("title"),
        "authors": [a.get("name") for a in book.get("authors", [])],
        "publishers": [p.get("name") for p in book.get("publishers", [])],
        "publish_date": book.get("publish_date"),
        "number_of_pages": book.get("number_of_pages"),
        "subjects": [s.get("name") for s in book.get("subjects", [])],
        "cover": book.get("cover", {}).get("medium"),
        "url": book.get("url"),
    }


def lookup_isbns(isbns: list[str]) -> list[dict]:
    """
    Look up multiple ISBNs and return a list of book info dicts.

    Args:
        isbns: A list of ISBN strings (ISBN-10 or ISBN-13).

    Returns:
        A list of dicts, each containing book information or an error.
    """
    return [lookup_isbn(isbn) for isbn in isbns]


def main():
    parser = argparse.ArgumentParser(
        description="Look up book information by ISBN numbers."
    )
    parser.add_argument(
        "isbns",
        nargs="+",
        help="One or more ISBN-10 or ISBN-13 numbers to look up.",
    )
    parser.add_argument(
        "--pretty",
        action="store_true",
        default=False,
        help="Pretty-print the JSON output.",
    )
    args = parser.parse_args()

    results = lookup_isbns(args.isbns)

    indent = 2 if args.pretty else None
    print(json.dumps(results, indent=indent, ensure_ascii=False))


if __name__ == "__main__":
    main()
