# Wave CRM — AI Documentation Hub

Welcome to the **AI docs** for the Wave SaaS starter kit.  
This section groups everything you need to build, test, and deploy AI-powered features using **Prism** (LLM integration) and **Relay** (Model Context Protocol tools).

---

## 📑 Index

| Topic | Summary |
|-------|---------|
| [Prism](./prism.md) | Install, configure, and call LLM providers (OpenAI, Anthropic, etc.). |
| [Relay](./relay.md) | Connect to MCP servers (e.g. Puppeteer) & expose external tools. |
| [Environment Variables](./env-vars.md) | Complete reference of all `PRISM_*`, `RELAY_*`, and provider keys. |
| [AI Assistant CLI](./assistant.md) | Usage guide for `php artisan ai:assistant` interactive command. |
| [Code Examples](./examples.md) | Common snippets for text generation, web research, data analysis. |
| [Troubleshooting](./troubleshooting.md) | Fixing transport errors, tool caching, and common pitfalls. |

---

## 🚀 Quick Start

1. **Add API keys**

   ```dotenv
   OPENAI_API_KEY=sk-...
   ANTHROPIC_API_KEY=claude-key-...
   ```

2. **Start (or let Relay auto-spawn) an MCP server**

   ```bash
   npx -y @modelcontextprotocol/server-puppeteer
   ```

3. **Run the assistant**

   ```bash
   php artisan ai:assistant --mode=research
   ```

4. **Explore tools**

   ```php
   Prism\Relay\Facades\Relay::tools('puppeteer')->keys();
   ```

---

## 📦 What’s Included

- **Prism (v0.68.1)** – unified API for multiple LLM providers.
- **Relay (v1.0.1)** – bridges Prism with external tool servers.
- **`config/prism.php` & `config/relay.php`** – default configs.
- **AI Assistant console command** with modes: *interactive*, *research*, *analyze*, *automate*, *chat*.
- **Storage scaffold** (`storage/app/ai_assistant/`) for saving research & reports.
- **Pest test helpers** (`Prism::fake()`) ready for mocking LLM calls.

---

## 🗺️ Next Steps

1. Read the [Prism guide](./prism.md) to learn provider-specific settings.  
2. Configure your first tool server in [Relay guide](./relay.md).  
3. Check real-world snippets in [Code Examples](./examples.md).  
4. Ensure your CI uses **PHP 8.3** and set secrets for provider keys.  

Happy building! If you spot anything missing in these docs, please open an issue or PR.  
