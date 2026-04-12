# Gemini Project Configuration & Context

**Untitled CMS** is an AI-native Content Management System built on Laravel 13 with a MongoDB backend and a React + Inertia.js administration layer.

## LLM Wiki & Knowledge Base Management
All enduring project documentation, architectural patterns, and module guidelines are housed in the `wiki/` directory. This serves as the local persistent memory across operational runs.

### CRITICAL AGENT INSTRUCTIONS: Proactive Updates
You **MUST proactively maintain and update the `wiki/`** immediately whenever you encounter or orchestrate new features, routing patterns, schemas, dependencies, or significant design decisions. Do not let system knowledge decay in transient chat histories.

### Automatic Retrieval Protocol
To reference this knowledge efficiently in future runs and avoid redundant analysis:
1. Use file-reading tools to check `wiki/index.md` first. This provides your initial map of the workspace and links to all subsequent documentation.
2. Read `wiki/SCHEMA.md` to fully understand formatting rules, update conventions, and query mechanics.
3. Access detailed breakdowns within `wiki/architecture/`, `wiki/database/`, `wiki/frontend/`, and `wiki/modules/`.
4. Update the `wiki/log.md` with a timestamped note after making documented adjustments.
