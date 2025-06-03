# Environment Variables Reference  
_Prism & Relay (Wave CRM – Laravel 12, PHP 8.3)_

This document lists **every supported environment variable** for the AI stack:

* Core Prism settings  
* Provider-specific API keys & endpoints  
* Core Relay settings  
* MCP server-specific variables (examples)

For each variable you’ll find the **default**, a **description**, and **where to obtain** the value.

---

## 1. Core Prism

| Variable | Default | Description |
|----------|---------|-------------|
| `PRISM_SERVER_ENABLED` | `true` | Toggle the built-in Prism Server routes. Disable (`false`) if you expose no public endpoints. |

---

## 2. Provider Keys & Endpoints

> All API keys are **secret** values – store them in your CI/CD secret manager or `.env` (never commit).

### 2.1 OpenAI

| Variable | Default | Purpose / Where to obtain |
|----------|---------|---------------------------|
| `OPENAI_API_KEY` | _none_ | **Required.** Create at <https://platform.openai.com/api-keys>. |
| `OPENAI_URL` | `https://api.openai.com/v1` | Override if using Azure-OpenAI or proxy. |
| `OPENAI_ORGANIZATION` | _none_ | Your Organization ID (required only when you belong to multiple orgs). |
| `OPENAI_PROJECT` | _none_ | Enterprise “project” token (optional). |

### 2.2 Anthropic

| Variable | Default | Purpose / Where to obtain |
|----------|---------|---------------------------|
| `ANTHROPIC_API_KEY` | _none_ | **Required.** Generate at <https://console.anthropic.com/settings/keys>. |
| `ANTHROPIC_API_VERSION` | `2023-06-01` | API version string; rarely changed. |
| `ANTHROPIC_DEFAULT_THINKING_BUDGET` | `1024` | Max tokens for Claude “thought” steps. |
| `ANTHROPIC_BETA` | _none_ | Comma-separated list of beta features (e.g. `tool-use`). |

### 2.3 Ollama (Local)

| Variable | Default | Purpose |
|----------|---------|---------|
| `OLLAMA_URL` | `http://localhost:11434` | Ollama server base URL. |

### 2.4 Mistral AI

| Variable | Default | Purpose |
|----------|---------|---------|
| `MISTRAL_API_KEY` | _none_ | Obtain from <https://console.mistral.ai/>. |
| `MISTRAL_URL` | `https://api.mistral.ai/v1` | Override for proxy / EU region. |

### 2.5 Groq

| Variable | Default | Purpose |
|----------|---------|---------|
| `GROQ_API_KEY` | _none_ | Create at <https://console.groq.com/keys>. |
| `GROQ_URL` | `https://api.groq.com/openai/v1` | Endpoint base URL. |

### 2.6 xAI

| Variable | Default | Purpose |
|----------|---------|---------|
| `XAI_API_KEY` | _none_ | Apply at <https://x.ai/>. |
| `XAI_URL` | `https://api.x.ai/v1` | Endpoint base URL. |

### 2.7 Google Gemini

| Variable | Default | Purpose |
|----------|---------|---------|
| `GEMINI_API_KEY` | _none_ | Obtain from Google AI Studio (<https://aistudio.google.com/>). |
| `GEMINI_URL` | `https://generativelanguage.googleapis.com/v1beta/models` | Base URL for Gemini models. |

### 2.8 DeepSeek

| Variable | Default | Purpose |
|----------|---------|---------|
| `DEEPSEEK_API_KEY` | _none_ | Key from <https://platform.deepseek.com/>. |
| `DEEPSEEK_URL` | `https://api.deepseek.com/v1` | Endpoint. |

### 2.9 VoyageAI

| Variable | Default | Purpose |
|----------|---------|---------|
| `VOYAGEAI_API_KEY` | _none_ | Create at <https://console.voyageai.com/api-keys>. |
| `VOYAGEAI_URL` | `https://api.voyageai.com/v1` | Endpoint. |

---

## 3. Core Relay

| Variable | Default | Description |
|----------|---------|-------------|
| `RELAY_TOOLS_CACHE_DURATION` | `60` | Minutes to cache tool definitions (`0` = no cache). |

---

## 4. MCP Server Examples

Relay lets you declare any number of **servers** in `config/relay.php`. Some may need extra environment vars.

### 4.1 Puppeteer – STDIO (Local Browsing)

| Variable | Default | Description |
|----------|---------|-------------|
| `MCP_PUPPETEER_DEBUG` | `false` | When `true`, launches Puppeteer server with verbose logging. |
| `RELAY_PUPPETEER_SERVER_TIMEOUT` | `60` | Seconds before Relay aborts if the server fails to respond. |

> Relay spawns the Puppeteer server automatically via the `command` array – no separate process required.

### 4.2 GitHub MCP Server – HTTP (Remote Tools)  

_Example if you build / deploy a custom GitHub data MCP server._

| Variable | Default | Description |
|----------|---------|-------------|
| `RELAY_GITHUB_SERVER_URL` | `http://localhost:8001/api` | Base URL of the MCP HTTP server. |
| `RELAY_GITHUB_SERVER_API_KEY` | _none_ | API token expected by your server. |
| `RELAY_GITHUB_SERVER_TIMEOUT` | `30` | HTTP timeout (seconds). |

---

## 5. Tips for Managing Secrets

1. **Local development** – store in `.env` (never commit).  
2. **CI / Production** – inject via secret manager (GitHub Actions, Forge, Envoy, etc.).  
3. Use Laravel’s `config:cache` after updating `.env` in production.  
4. Rotate keys periodically; both Prism & Relay read from config on every call.

---

## 6. Quick Checklist

- [ ] Required provider keys set (`OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, …).  
- [ ] `PRISM_SERVER_ENABLED` aligned with your routing strategy.  
- [ ] MCP servers defined in `config/relay.php` **and** reachable.  
- [ ] `RELAY_TOOLS_CACHE_DURATION` tuned for development vs production.  

Once the above are in place, you are ready to run:

```bash
php artisan ai:assistant --mode=research
```

Enjoy building with **Prism** & **Relay**!  
