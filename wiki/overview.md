# Overview

> AI-native CMS built on Laravel 12 + MongoDB with a React/Inertia admin SPA.

Last updated: 2026-04-05

## What it is

Untitled CMS is a content management system where AI is a first-class feature, not
a bolt-on. It serves public pages as HTML by default and as Markdown+YAML frontmatter
when requested with `Accept: text/markdown` — designed so AI crawlers and agents can
consume content directly without parsing HTML.

## Who it's for

Admins manage content through a React SPA. End users consume public pages. AI agents
consume the markdown-flavoured endpoints.

## Core capabilities

- Page management with rich content editing
- Media management (Vault) with security pipeline
- AI content generation and chat (multi-provider)
- Role-based permissions with fine-grained policies
- Banners and menus with drag-and-drop ordering
- Database-driven redirects
- Custom maintenance mode with admin bypass
- Activity logging
- Analytics dashboard (Recharts)

## Key numbers

- ~20 permissions in `resource.action` format
- 8 Policy classes
- 6 Vault upload pipeline stages
- 3 AI providers (OpenAI, Gemini, Stability AI)
- Rate limits: 30/min text generation, 10/min image generation

## See also

- [[architecture]] — stack and request flow
- [[services]] — service layer overview
- [[permissions]] — how access control works
- [[ai-hub]] — AI provider configuration
