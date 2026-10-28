#!/usr/bin/env python3
"""Resolve whether the live Institutes listing contains a detail link or is empty."""

from html.parser import HTMLParser
from pathlib import Path
import re
import sys
from urllib.parse import urlparse


EXCLUDED_SLUGS = {"search", "login", "setup", "automation", "sitemap", "robots"}


class ListingParser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.hrefs = []
        self.text_parts = []

    def handle_starttag(self, tag, attrs):
        if tag.lower() == "a":
            self.hrefs.extend(value for key, value in attrs if key.lower() == "href" and value)

    def handle_data(self, data):
        self.text_parts.append(data)


def resolve_listing(html):
    parser = ListingParser()
    parser.feed(html)
    for href in parser.hrefs:
        parsed = urlparse(href.strip())
        if parsed.scheme and parsed.netloc.lower() not in {"estudent.pk", "www.estudent.pk"}:
            continue
        path = parsed.path or href.strip()
        match = re.fullmatch(r"/institutes/([a-z0-9-]+)/?", path, flags=re.I)
        if match and match.group(1).lower() not in EXCLUDED_SLUGS:
            return ("FOUND", f"/institutes/{match.group(1)}")

    visible_text = " ".join(" ".join(parser.text_parts).split()).lower()
    empty_markers = (
        "no institutions to display",
        "no approved institutions found",
        "no institutions found",
        "showing 0 of 0 institutions",
    )
    if any(marker in visible_text for marker in empty_markers):
        return ("EMPTY_DATA", "")
    return ("MISSING_DETAIL_LINK", "")


def main():
    if len(sys.argv) != 2:
        print("Usage: resolve-institute-detail-link.py <html-file>", file=sys.stderr)
        return 2
    try:
        html = Path(sys.argv[1]).read_text(encoding="utf-8", errors="replace")
    except OSError:
        print("MISSING_DETAIL_LINK")
        return 0
    state, detail_path = resolve_listing(html)
    print(state)
    if detail_path:
        print(detail_path)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
