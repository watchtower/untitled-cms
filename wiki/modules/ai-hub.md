# AI Hub

> Multi-provider AI configuration, usage tracking, and integration patterns.

Last updated: 2026-04-05

## Overview

AI providers are configured at runtime through the admin AI Hub UI. Configuration
is stored in the `ai_hubs` MongoDB collection — **do not hardcode API keys in
config files or .env**. The config is patched dynamically by `AiService`.

## Supported providers

| Provider | Capabilities |
|---------|-------------|
| OpenAI | Text generation, chat |
| Gemini | Text generation |
| Stability AI | Image generation |

## Rate limits (enforced at route level)

- Text generation: **30 requests/minute**
- Image generation: **10 requests/minute**

## Usage tracking

Monthly usage is tracked per provider in the `ai_hubs` collection. This feeds
the dashboard analytics. If usage numbers look wrong, check `AiService` for
where tracking increments are written.

## Chat sessions

Conversation history is stored in `chat_sessions`. Each session belongs to a user.
Session data is not currently used as context in subsequent prompts — investigate
`AiContextService` if you need to change this.

## AiContextService

Aggregates project context (pages, settings, etc.) for use in AI prompts.
Results are cached to avoid redundant DB queries within a session. See [[modules/services]].

## Gotchas

- If AI calls are failing, the first thing to check is whether provider credentials
  are set in the AI Hub admin UI, not in `.env`.
- `SafeHttpClient` must be used for all outbound requests to AI APIs —
  direct use of `Http` facade bypasses SSRF protection. See [[modules/services]].
- Image generation uses Stability AI specifically. If it's misconfigured,
  text generation (OpenAI/Gemini) will still work.

## See also

- [[modules/services]] — AiService, AiContextService, SafeHttpClient
- [[architecture/request-flow]] — rate limiting configuration
