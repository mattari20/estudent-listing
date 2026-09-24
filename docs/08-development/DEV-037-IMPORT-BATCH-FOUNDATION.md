# DEV-037 — Import Batch Foundation

Status: Implemented

Import batches provide a durable operational boundary for bulk data ingestion. Each batch records entity type, source, status and row counts. A batch is not itself a canonical data change.
