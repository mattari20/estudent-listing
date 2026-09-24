# DEV-062 — Import Schema Versioning

Status: Implemented

Import schemas are explicit versioned profiles. Each import batch records the selected profile/version. Header validation is profile-driven, allowing future schema versions without silently changing historical import contracts.
