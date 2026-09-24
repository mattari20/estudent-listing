# DEV-068 — Parent Dependency Validation

Status: Implemented

Enrichment rows cannot be promoted unless their governed canonical parent type is correct. Institution → Program/Campus → Program Offering → Admission Intake → Eligibility/Merit/Selection dependencies are enforced before promotion. Financial and accreditation scope is validated separately.
