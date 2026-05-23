# Vault

> Media manager: upload pipeline, configuration, and storage.

Last updated: 2026-04-05

## Overview

`VaultService` runs uploads through a Laravel pipeline of pipe classes in
`app/Vault/Pipes/`. Each pipe receives a typed `VaultPipelinePayload` DTO,
does one thing, and passes it to the next stage. If any pipe rejects the file,
the upload fails with an appropriate error.

## Pipeline stages (in order)

| # | Pipe class | What it does |
|---|-----------|--------------|
| 1 | `DetectDoubleExtension` | Rejects files like `image.php.jpg` — disguised executables |
| 2 | `ValidateMimeType` | Checks MIME type against allowlist in `config/vault.php` |
| * | `SandboxedScan` | Optional ClamAV antivirus daemon scanning (TCP stream INSTREAM mode; dynamically injected at pos 2 when enabled) |
| 3 | `SanitizeImage` | Strips EXIF/metadata from images via Intervention Image |
| 4 | `ModerationCheck` | Optional AI content moderation check |
| 5 | `GenerateUuid` | Assigns a UUID filename to prevent path traversal / collisions |
| 6 | `StoreMetadata` | Persists file record to MongoDB `vault_files` collection |

*Note: `SandboxedScan` connects to a clamd daemon via TCP. By default, it fails open, but can be configured to fail closed using the `CLAMAV_FAIL_CLOSED` setting, blocking uploads when the scanner is offline.*

## Configuration (`config/vault.php`)

- **Allowed MIME types** — explicit allowlist
- **Max size** — 50 MB
- **ClamAV** — optional antivirus scan (`CLAMAV_ENABLED=false` by default)
- **Image washing** — `image_washing = true` by default (Intervention Image sanitization)

## DTO

`app/Vault/DTOs/VaultPipelinePayload.php` carries state through the pipeline.
Inspect this class to see what data is available at each stage.

## Adding a new pipe

1. Create a class in `app/Vault/Pipes/` implementing the pipe interface.
2. Add it to the pipeline sequence in `VaultService`.
3. It receives and must pass along `VaultPipelinePayload`.

## Gotchas

- `SanitizeImage` uses Intervention Image — if the image library is not installed,
  this stage will fail. Check `image_washing` config if you're seeing unexpected errors.
- `ModerationCheck` is an AI call — it adds latency and can fail if AI is
  misconfigured. Investigate whether it short-circuits gracefully on failure.
- ClamAV is off by default. Do not assume it runs in production unless explicitly enabled.

## See also

- [[modules/services]] — VaultService overview
- [[database/collections]] — vault_files and vault_folders collections
