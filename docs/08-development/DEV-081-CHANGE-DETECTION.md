# DEV-081 — Change Detection
Document ID: DEV-081
Status: Implemented

Detected differences create durable ChangeEvent records with subject, materiality, actor, status, summary and detection time.

Change detection identifies possible change; it does not silently publish incoming values. High-impact attributes require governed review before canonical modification.