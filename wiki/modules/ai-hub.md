# AI Hub

> Multi-provider AI configuration, usage tracking, and integration patterns.

Last updated: 2026-07-12

## Overview

AI providers are configured at runtime through the admin AI Hub UI. Configuration
is stored in the `ai_hubs` MongoDB collection — **do not hardcode API keys in
config files or .env**. The config is patched dynamically by `AiService`.

## Supported providers

Capabilities depend on hub configuration and model choice. Typical matrix:

| Provider | Text (laravel/ai) | Vision alt-text | Image generation |
|----------|-------------------|-----------------|------------------|
| OpenAI | Yes | Yes | Yes (e.g. DALL·E) |
| Gemini | Yes | Yes | Yes (generateContent / Imagen) |
| OpenRouter | Yes | Yes | Yes (image-capable models) |
| Stability AI | Via hub if configured | No | Yes (SDXL) |
| Others in AI Hub UI | Via `config/ai.providers` | Varies | Varies |

Canonical provider keys for the SDK are `array_keys(config('ai.providers'))`.
Hub `name` must match a supported key (case-insensitive).

## Rate limits (enforced at route level)

- Text generation (SEO, tags, generate): **30 requests/minute**
- Image generation: **10 requests/minute**
- Chat + actions + context: **60 requests/minute**
- Chat resilience: 120s-oriented timeout path; **1 retry** with **2s backoff** on HTTP 429 / rate-limit errors

## Usage tracking

Monthly usage is tracked per hub in `ai_hubs.monthly_usage` (increments inside `AiService`).

## Chat sessions

Conversation history is stored in `chat_sessions`. Each session belongs to a user.
Session data (including past messages and AI proposals) is formatted and passed as
conversation history in subsequent prompts.

## Outbound HTTP

- **Untrusted URLs** (e.g. download image from AI-returned URL): `SafeHttpClient`
- **Provider API calls** in `AiService` vision/image methods: `AiHttpClient`

See [modules/services#Outbound HTTP policy (two tiers)](services.md#outbound-http-policy-two-tiers).

## AiContextService

Aggregates project context (pages, settings, etc.) for use in AI prompts.
Results are cached to avoid redundant DB queries within a session.

## Gotchas

- If AI calls fail, first check credentials in the AI Hub admin UI, not `.env`.
- Encrypted API keys fail to decrypt after `APP_KEY` rotation — re-enter the key in UI.
- Image generation requires an active hub whose provider supports images (OpenAI, Gemini, Stability, OpenRouter); text can still work on other hubs.
- Do not route user-supplied URLs through `AiHttpClient` — use `SafeHttpClient`.

## See also

- [modules/services](services.md) — AiService, AiHttpClient, SafeHttpClient
- [architecture/request-flow](../architecture/request-flow.md) — rate limiting configuration
