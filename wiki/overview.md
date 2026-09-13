# Overview

> AI-native CMS built on Laravel 13 + MongoDB with a React/Inertia admin SPA.

Last updated: 2026-07-12

## What it is

Untitled CMS is a content management system where AI is a first-class feature, not
a bolt-on. It serves public pages as HTML by default and as Markdown+YAML frontmatter
when requested with `Accept: text/markdown` — designed so AI crawlers and agents can
consume content directly without parsing HTML.

## Who it's for

Admins manage content through a React SPA. End users consume public pages. AI agents
consume the markdown-flavoured endpoints.

## Core capabilities

- Page management with rich content editing (TinyMCE)
- Media management (Vault) with security pipeline
- AI content generation and chat (multi-provider, runtime AI Hub)
- Role-based permissions with fine-grained policies
- Banners and menus with drag-and-drop ordering
- Database-driven redirects
- Custom maintenance mode with admin bypass
- Activity logging
- Analytics dashboard (Recharts)
- Email logs, suppression, and multi-provider webhooks

## Key numbers

Source of truth is always the code; these numbers are snapshots:

- **32** permissions in `resource.action` format (`Role::availablePermissions()`)
- **10** Policy classes under `app/Policies/`
- **6** Vault upload pipeline stages (+ optional ClamAV `SandboxedScan`)
- Multi-provider AI Hub (OpenAI, Gemini, OpenRouter, Stability, plus others configured in AI Hub UI / `config/ai.php`)
- Rate limits: 30/min text generation, 10/min image generation, 60/min chat/actions

## See also

- [architecture/stack](architecture/stack.md) — stack and request flow
- [modules/services](modules/services.md) — service layer overview
- [modules/permissions](modules/permissions.md) — how access control works
- [modules/ai-hub](modules/ai-hub.md) — AI provider configuration
- [architecture/mongodb](architecture/mongodb.md) — why MongoDB, test gap, re-evaluate criteria
