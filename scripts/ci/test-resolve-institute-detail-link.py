#!/usr/bin/env python3
import importlib.util
import unittest
from pathlib import Path

SCRIPT = Path(__file__).with_name("resolve-institute-detail-link.py")
SPEC = importlib.util.spec_from_file_location("resolve_institute_detail_link", SCRIPT)
MODULE = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(MODULE)


class ResolveInstituteDetailLinkTests(unittest.TestCase):
    def test_populated_listing_resolves_real_detail_link(self):
        state, path = MODULE.resolve_listing(
            '<main><a href="/institutes/university-of-lahore">University of Lahore</a></main>'
        )
        self.assertEqual(("FOUND", "/institutes/university-of-lahore"), (state, path))

    def test_empty_healthy_listing_is_not_a_broken_detail_route(self):
        state, path = MODULE.resolve_listing(
            '<main><p>No institutions to display</p></main>'
        )
        self.assertEqual(("EMPTY_DATA", ""), (state, path))

    def test_populated_or_unknown_page_without_detail_link_is_not_silently_accepted(self):
        state, path = MODULE.resolve_listing(
            '<main><p>Showing 12 of 12 institutions</p><a href="/institutes/search">Search</a></main>'
        )
        self.assertEqual(("MISSING_DETAIL_LINK", ""), (state, path))

    def test_external_urls_and_utility_routes_are_not_detail_links(self):
        state, path = MODULE.resolve_listing(
            '<a href="https://example.com/institutes/other">Other</a>'
            '<a href="/institutes/login">Login</a>'
        )
        self.assertEqual(("MISSING_DETAIL_LINK", ""), (state, path))


if __name__ == "__main__":
    unittest.main()
