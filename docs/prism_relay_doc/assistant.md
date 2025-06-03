# AI Assistant CLI (`php artisan ai:assistant`)
_Wave CRM — Laravel 12 · PHP 8.3 · Prism + Relay_

An interactive, Swiss-army-knife console tool that unlocks AI super-powers directly from your terminal.

---

## 1. Overview

| Feature | Description |
|---------|-------------|
| **Interactive menu** | Pick actions without memorising flags. |
| **Research mode** | Browse the web via Puppeteer MCP tools (screenshots, scraping). |
| **Analyze mode** | Explore CRM sample data (customers, deals, marketing, support). |
| **Automate mode** | Generate emails, social posts, follow-up sequences, report templates. |
| **Chat mode** | Free-form conversation with your configured LLM. |
| **Provider / model switch** | Change LLM provider & model on the fly (`--provider`, `--model`). |
| **Save outputs** | Persist Markdown reports to `storage/app/ai_assistant/`. |
| **Debug trace** | Print stack traces and Relay/Prism errors with `--debug`. |

The command is implemented in  
`app/Console/Commands/AiAssistant.php`.

---

## 2. Command-line Options

| Flag | Default | Values | Description |
|------|---------|--------|-------------|
| `--mode` | `interactive` | `interactive`, `research`, `analyze`, `automate`, `chat` | Choose workflow directly, bypassing the menu. |
| `--model` | `claude-3-7-sonnet-latest` | Provider-specific model name | Override default model. |
| `--provider` | `anthropic` | `anthropic`, `openai` | Select LLM provider. |
| `--save` | _off_ | – | Auto-save response to file (skips confirmation prompt). |
| `--debug` | _off_ | – | Show Exception traces and verbose Relay logs. |
| Standard Laravel flags | – | `-n`, `-q`, `-v`, `--env`… | Behave as usual. |

---

## 3. Modes & Workflows

### 3.1 Interactive (`--mode=interactive`)

A menu-driven UX:

1. Pick one of _Research_, _Analyze_, _Automate_, _Chat_, _Exit_.  
2. The assistant routes to the chosen sub-workflow.  
3. Safe fallback to main menu on validation errors or `Ctrl-C`.

### 3.2 Research (`--mode=research`)

Purpose | Tools
:--|:--
Web investigation, screenshots, extraction | `puppeteer` MCP server (STDIO)

Prompts are sent with a **system prompt** instructing the LLM how to operate the browser tools.

Flow
1. User supplies a **textarea** prompt—e.g. *“Compare pricing pages of Mailchimp and SendGrid, capture hero screenshots.”*
2. Prism agent loads **Puppeteer** tool definitions via Relay.
3. Agent may call `navigate`, `screenshot`, `extractText`, etc.
4. Result returns as Markdown + base64 screenshots (or remote URLs if server handles uploads).
5. Optional save to `storage/app/ai_assistant/research_YYYY-mm-dd_HH-MM-SS.md`.

### 3.3 Analyze (`--mode=analyze`)

Dataset | Source method
:--|:--
Customers, Deals, Marketing, Support | `getSampleData($type)` seeded arrays

Steps
1. Select dataset (or _Custom Query_).  
2. Enter analysis question (*“Segment customers by plan and average revenue.”*).  
3. Assistant injects dataset JSON inside a fenced code-block and asks LLM for insights.  
4. Outputs tables, bullet points, recommendations.  

### 3.4 Automate (`--mode=automate`)

Sub-tasks:  
* **email** – subject lines + 3-email sequence  
* **social** – multi-platform post ideas  
* **followup** – SDR follow-up scripts  
* **report** – template skeletons  
* **custom** – free-form automation

Wizard prompts gather parameters (tone, audience, count…).  
Results saved to `automation_<task>_<timestamp>.md` when confirmed.

### 3.5 Chat (`--mode=chat`)

Plain conversation with the selected model—no external tools.  
Useful for quick Q&A, brainstorming, or testing provider connectivity.

---

## 4. Provider & Model Matrix

Provider | Supported Models in Assistant
---------|--------------------------------
`anthropic` | `claude-3-7-sonnet-latest`, `claude-3-5-sonnet-latest`, `claude-3-opus-latest`, `claude-3-haiku-latest`
`openai` | `gpt-4o`, `gpt-4o-mini`, `gpt-4-turbo`, `gpt-3.5-turbo`

_Model validation_ — if you pass an unsupported name, the first model of that provider is chosen.

---

## 5. Practical Examples

### 5.1 Quick Chat

```bash
php artisan ai:assistant --mode=chat \
  --provider=openai --model=gpt-4o-mini
```

### 5.2 Non-interactive Web Research

```bash
php artisan ai:assistant --mode=research \
  --provider=anthropic \
  --model=claude-3-haiku-latest \
  --save \
  -- <<<'PROMPT'
Browse laravel.com, take a screenshot of the Downloads section,
and list the server requirements.
PROMPT
```

### 5.3 Weekly Report Template Generation

```bash
php artisan ai:assistant --mode=automate
# choose "report" -> "weekly" -> "executive summary"
```

---

## 6. File Output & Storage

Saved files live under:

```
storage/app/ai_assistant/
├── analysis_customers_2025-06-03_10-45-22.md
├── research_2025-06-03_11-02-05.md
└── automation_email_2025-06-03_11-15-41.md
```

You can publish them to S3, attach to emails, or render in Filament.

---

## 7. Extending the Assistant

1. **Add new mode**  
   *Create a `runXyzMode()` method, register in menu and `--mode` switch.*

2. **Inject custom data**  
   Replace `getSampleData()` with DB queries or Livewire props.

3. **Expose more tools**  
   Add new MCP server in `config/relay.php`, then merge:
   ```php
   $tools = [...Relay::tools('github')];
   ```

4. **Queue long operations**  
   Dispatch the `createAgent()` call to a queued job and stream updates with Pail or WebSockets.

---

## 8. Troubleshooting

| Issue | Fix |
|-------|-----|
| Command hangs in research mode | Ensure Puppeteer server can launch (Node installed) or pre-start with `npx @modelcontextprotocol/server-puppeteer`. |
| `PrismException` about provider | Verify API key present and `.env` loaded (`php artisan config:clear`). |
| Output not saved | Check write permissions on `storage/app/ai_assistant`. |
| Huge responses cost tokens | Use `--model=claude-3-haiku-latest` (cheap) or refine prompt to limit scope. |

---

## 9. Related Docs

* [Environment Variables](./env-vars.md)  
* [Prism Guide](./prism.md)  
* [Relay Guide](./relay.md)

---

**Happy commanding!** The AI Assistant is your gateway to powerful, repeatable AI workflows inside Wave CRM.
