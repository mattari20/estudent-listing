# Institutes Directory Requirements

## Product goal
Help students find, understand, compare and choose suitable education institutions through accurate, structured, current and trustworthy information. The directory should expand from lower-level education through colleges, technical/vocational institutions and universities, while displaying fields appropriate to each type.

## Public discovery
- Search by institution name, alternate/short name, campus, program and location.
- Filter by education level/type and supported geography such as province/territory, district and city.
- Show useful institution cards and canonical detail pages, with working pagination and no dead links.
- Support campuses/sub-institutions and their relationship to parent institutions.
- Include a location-based discovery system so visitors can find institutions on a map, near a chosen location, or near their current location when they grant browser permission.
- Support geocoding and map display through a replaceable provider integration. Handle denied permission, missing coordinates, inaccurate geocoding and provider failures gracefully.
- Provide clear, structured information students commonly seek, where verified and applicable: official name, institution type/level, address and contacts, campuses, programs, admission requirements/deadlines, fees, scholarships, facilities, delivery modes, eligibility, accreditation/recognition, official website and source/freshness notes.

## Institution, campus and program feedback
- Allow visitors to submit ratings for institutions and their campuses/sub-institutions; program-level feedback may be added where useful.
- Allow visitors to write comments/reviews at the end of institution detail pages to share their experience and discuss the institution.
- Keep ratings and written reviews distinct, and clearly label the target being reviewed.
- Design moderation, reporting, spam/abuse controls, privacy and rate limits before public launch.
- Do not claim a reviewer is verified unless the verification method has actually been completed.
- Display sample size, recency and relevant context; do not imply that a small number of submissions is definitive.
- Plan institution responses and comment correction/moderation workflows where appropriate.

## Future universal rating
Create a transparent composite rating system that may combine:
1. visitor ratings and appropriately moderated review signals;
2. external/international ratings or rankings when reliable data is available;
3. data freshness, sample size, confidence and source quality as appropriate.

Do not fix arbitrary weights now. The methodology must document eligible sources, scale normalization, coverage differences, recency, confidence, anti-manipulation, missing-data handling and versioning. Show component sources and explain the result. Never present an unavailable external rating as zero, or compare unlike scales without qualification. Comments may inform carefully designed qualitative signals, but raw sentiment must not be treated as objective fact or used without abuse and bias controls.

## Future AI research agent
- Discover institution websites and approved trustworthy sources.
- Collect and snapshot source evidence.
- Extract proposed facts about institutions, campuses, programs, admissions, fees, scholarships, accreditation/recognition and rankings.
- Match candidates to canonical entities and flag duplicates, changes, uncertainty and conflicts.
- Submit evidence-backed proposals to an approval queue with source URL, retrieval time and confidence.
- Never silently publish, overwrite canonical facts or fabricate evidence.
- Provide a review trail and safe retry/failure reporting.

## Administrative and trust requirements
- Role-based access for the planned small team (two administrators and two users).
- Import validation, review queues, conflict review, explicit approval and publication.
- Read-only diagnostics and automated reports.
- Auditability for important changes and moderation actions.
- Privacy-conscious handling of account and user-generated data.

## SEO and quality
- Use stable canonical URLs and accurate metadata.
- Keep sitemap and robots rules aligned with useful public pages.
- Avoid indexing thin, duplicate, incomplete or unapproved pages.
- Ensure filters do not create uncontrolled indexable URL combinations.
- Verify public content and deployed behaviour with automated smoke tests.

## Acceptance rule
A requirement is not complete until implementation, automated tests, security/privacy review where relevant, documentation and live verification (when applicable) are recorded in the status register.
