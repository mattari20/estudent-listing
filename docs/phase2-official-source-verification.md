# Official-source verification for the 36 Step 3 programme gaps

## Command

`php artisan institutes:verify-offering-gap-sources --json`

The command reads the committed evidence register and safely fetches each unique non-empty source URL once using the existing SSRF-aware `SafeSourceHttpRetriever`. It reports HTTP/retrieval status, content type, SHA-256 of the fetched response body, page title, and whether the normalized programme or campus name appears in the returned text.

It does not connect to or write to the canonical database. It has caps of 100 register rows, 100 unique URLs, and 5 MiB per response body. The command does not save response bodies; only concise metadata and per-row results are returned.

## Interpreting results

- `reachable_text`: successful HTTP response with an HTML/plain-text body that can be searched.
- `http_error`: server returned a non-success HTTP status.
- `retrieval_failed`: the safe retriever blocked the URL or a network/request error occurred; `error_code` is a class/status marker, not a raw exception message.
- `body_too_large`: response exceeded the 5 MiB processing limit.
- `non_text_content`: successful response with a content type that this text-only checker cannot inspect.
- `missing_url`: the register has no URL for that source role.

Phrase matches are only triage signals. A match does not prove the page is official, the programme is currently offered, or that delivery occurs at the named campus. `source_ownership_verified` is always false. Review the institution's official domain, source date, programme details, and campus context before making any canonical repair.

The report includes each URL's SHA-256 and retrieval timestamp so reviewers can tie findings to the exact retrieved response without retaining page content. No AI API is called and no database mutation occurs.
