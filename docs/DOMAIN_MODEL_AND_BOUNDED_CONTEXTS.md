# Domain Model and Bounded Contexts

## Core canonical entities
- **Institution:** canonical education provider or school-level entity; carries name, official name, short name, aliases, slug, type and status.
- **Campus / sub-institution:** a physical or operational unit associated with an institution; must support parent/child relationships where the real-world structure requires them.
- **Geography:** structured province/territory, district, city/locality and relevant address information.
- **Program:** academic or educational program offered by an institution.
- **Program offering:** a program's availability at a particular institution/campus and applicable delivery details.
- **Admission intake:** a time-bounded admission cycle linked to a valid offering.
- **Source, snapshot and evidence:** provenance records identifying where a fact came from, when it was retrieved and what it supports.
- **Import batch / row:** staged data and its validation, identity, conflict and review state.
- **Promotion decision / conflict:** explicit decisions and unresolved identity or data conflicts.

## Planned entities / capabilities
- **Institution type and education level taxonomy:** support lower-level schools, colleges, vocational/technical institutes, universities and other approved categories without forcing irrelevant fields onto every type.
- **Location point:** latitude/longitude, coordinate provenance, accuracy, verification status and geocoding provider metadata; addresses and coordinates must be correctable.
- **Rating submission:** reviewer/contributor type, scope (institution, campus/sub-institution or program), score, experience period, submission date, moderation/verification state and anti-abuse signals.
- **Written review/comment:** target entity, author or privacy-preserving identity, content, created/edited dates, moderation status, report flags and response/history as policy permits.
- **External rating observation:** provider/source, target match, original score, scale, review count if available, observation date, source URL and normalization metadata.
- **Composite rating snapshot:** methodology version, component values, sample-size/confidence indicators, freshness and calculation timestamp.
- **AI research task and extraction proposal:** query/scope, source URLs, snapshots, extracted claims, confidence, matched entity candidates, conflicts and review outcome.
- **Audit event:** actor, action, target, time, reason and before/after reference where appropriate.

## Relationship invariants
- A public campus must belong to a valid canonical institution and have valid geography according to approved rules.
- A public program must reference a valid institution; an offering must reference a valid program and campus/institution context.
- An intake must reference a valid offering.
- Alias and normalized-name collisions must be reported for review; string similarity alone is not proof of identity.
- Rating/comment scope must point to the exact institution, campus or program being reviewed.
- External rating data must retain provenance and scale; never silently mix unlike scoring scales.
- AI proposals must retain the source and evidence that support each proposed fact.

## Future compatibility
Design identifiers and relations so ratings, location search, AI discovery and education levels can be added without changing the meaning of existing canonical IDs or making provider-specific IDs the primary identity.
