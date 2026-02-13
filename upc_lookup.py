#!/usr/bin/env python3
"""
UPC Lookup Utility

Receives a list of UPC codes and returns JSON containing product information.
Uses the UPC Item DB API (https://www.upcitemdb.com) for lookups.

Usage:
    # As a CLI tool:
    python upc_lookup.py 012345678905 4006381333931
    python upc_lookup.py --pretty 012345678905

    # As a module:
    from upc_lookup import lookup_upcs
    results = lookup_upcs(["012345678905", "4006381333931"])
"""

import argparse
import json
import re
import urllib.error
import urllib.request


def normalize_upc(upc: str) -> str:
    """Remove hyphens, spaces, and leading zeros beyond 14 digits from a UPC string."""
    return re.sub(r"[-\s]", "", upc.strip())


def validate_upc(upc: str) -> bool:
    """
    Validate that a string is a properly formatted UPC/EAN/GTIN barcode.

    Supports UPC-A (12 digits), EAN-13 (13 digits), and GTIN-14 (14 digits).
    Validates the check digit using the standard modulo-10 algorithm.
    """
    upc = normalize_upc(upc)

    if not upc.isdigit() or len(upc) not in (12, 13, 14):
        return False

    return _validate_check_digit(upc)


def _validate_check_digit(upc: str) -> bool:
    """Validate a UPC/EAN/GTIN check digit using the modulo-10 algorithm."""
    digits = [int(d) for d in upc]
    check = digits[-1]
    payload = digits[:-1]

    # Weight pattern depends on length: from the right, weights alternate
    # 3, 1, 3, 1... starting with the digit next to the check digit.
    # From the left, weight is 3 when i and payload length have different parity.
    total = sum(d * (3 if i % 2 != len(payload) % 2 else 1) for i, d in enumerate(payload))
    expected = (10 - (total % 10)) % 10

    return check == expected


def lookup_upc(upc: str) -> dict:
    """
    Look up a single UPC using the UPC Item DB API.

    Args:
        upc: A UPC-A (12), EAN-13 (13), or GTIN-14 (14) digit string.

    Returns:
        A dict with product information or an error entry.
    """
    upc = normalize_upc(upc)

    if not validate_upc(upc):
        return {"upc": upc, "error": "Invalid UPC format"}

    url = f"https://api.upcitemdb.com/prod/trial/lookup?upc={upc}"

    try:
        req = urllib.request.Request(url, headers={
            "User-Agent": "upc-lookup-utility/1.0",
            "Accept": "application/json",
        })
        with urllib.request.urlopen(req, timeout=10) as response:
            data = json.loads(response.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        if e.code == 429:
            return {"upc": upc, "error": "Rate limit exceeded. Try again later."}
        return {"upc": upc, "error": f"HTTP error: {e.code}"}
    except urllib.error.URLError as e:
        return {"upc": upc, "error": f"Network error: {e.reason}"}
    except Exception as e:
        return {"upc": upc, "error": str(e)}

    if data.get("code") != "OK" or not data.get("items"):
        return {"upc": upc, "error": "Product not found"}

    item = data["items"][0]

    return {
        "upc": upc,
        "title": item.get("title"),
        "brand": item.get("brand"),
        "category": item.get("category"),
        "description": item.get("description"),
        "weight": item.get("weight"),
        "dimension": item.get("dimension"),
        "lowest_recorded_price": item.get("lowest_recorded_price"),
        "highest_recorded_price": item.get("highest_recorded_price"),
        "images": item.get("images", []),
        "offers": [
            {
                "merchant": o.get("merchant"),
                "title": o.get("title"),
                "price": o.get("price"),
                "currency": o.get("currency"),
                "link": o.get("link"),
            }
            for o in item.get("offers", [])
        ],
    }


def lookup_upcs(upcs: list[str]) -> list[dict]:
    """
    Look up multiple UPC codes and return a list of product info dicts.

    Args:
        upcs: A list of UPC/EAN/GTIN barcode strings.

    Returns:
        A list of dicts, each containing product information or an error.
    """
    return [lookup_upc(upc) for upc in upcs]


def main():
    parser = argparse.ArgumentParser(
        description="Look up product information by UPC codes."
    )
    parser.add_argument(
        "upcs",
        nargs="+",
        help="One or more UPC-A (12), EAN-13 (13), or GTIN-14 (14) digit codes.",
    )
    parser.add_argument(
        "--pretty",
        action="store_true",
        default=False,
        help="Pretty-print the JSON output.",
    )
    args = parser.parse_args()

    results = lookup_upcs(args.upcs)

    indent = 2 if args.pretty else None
    print(json.dumps(results, indent=indent, ensure_ascii=False))


if __name__ == "__main__":
    main()
