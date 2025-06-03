# Relay Guide for Wave CRM  
*Model-Context-Protocol (MCP) client for external tool use*  

_Last updated: 2025-06-03_

---

## 1. What is Relay?

Relay is a Laravel package that **bridges Prism and external “tool servers”** that speak the Model Context Protocol (MCP).  
With Relay you can:

* Launch or connect to MCP servers (locally or remotely)  
* Discover their tool definitions (functions the LLM can call)  
* Expose those tools to Prism agents with a single line of code  
* Handle execution, streaming results, time-outs, and caching

---

## 2. Installation _(already done)_

```bash
composer require prism-php/relay
```

Wave ships with `prism-php/relay:^1.0` in `composer.json`.  
The default config was copied to **config/relay.php**.

---

## 3. Configuration

### 3.1 `config/relay.php`

```php
return [
    'servers' => [

        // 1) Local Puppeteer (STDIO)
        'puppeteer' => [
            'transport' => \Prism\Relay\Enums\Transport::Stdio,
            'command'   => [
                'npx', '-y', '@modelcontextprotocol/server-puppeteer',
                '--options', ['debug' => env('MCP_PUPPETEER_DEBUG', false)],
            ],
            'timeout' => env('RELAY_PUPPETEER_SERVER_TIMEOUT', 60),
            'env'     => [],      // e.g. NODE_ENV, custom ports…
        ],

        // 2) File-System tools (local script, STDIO)
        'filesystem' => [
            'transport' => \Prism\Relay\Enums\Transport::Stdio,
            'command'   => ['php', 'artisan', 'mcp:filesystem'], // custom Laravel command
            'timeout'   => 30,
        ],

        // 3) Remote GitHub MCP server (HTTP)
        'github' => [
            'transport' => \Prism\Relay\Enums\Transport::Http,
            'url'       => env('RELAY_GITHUB_SERVER_URL', 'http://localhost:8001/api'),
            'timeout'   => env('RELAY_GITHUB_SERVER_TIMEOUT', 30),
            'headers'   => [
                'Authorization' => 'Bearer '.env('RELAY_GITHUB_SERVER_API_KEY'),
            ],
        ],
    ],

    // Minutes to cache tool definitions (0 = disable)
    'cache_duration' => env('RELAY_TOOLS_CACHE_DURATION', 60),
];
```

### 3.2 Environment Variables

See [`docs/env-vars.md`](./env-vars.md) for the full reference  
(`RELAY_*`, `MCP_*`, etc.).

---

## 4. Transport Types

| Transport | Use-case | How it works |
|-----------|---------|--------------|
| **STDIO** | Local processes (Node, PHP, Python…) | Relay spawns a child process, communicates via stdin/stdout JSON streams. Ideal for Puppeteer or any CLI tool. |
| **HTTP**  | Remote or containerised servers | Relay issues HTTP POST requests to `/tools/discover` & `/tools/call`. Works over LAN, Docker network, or internet. |

**Choosing:**  
* Use **STDIO** when you want zero infrastructure – Relay launches the process on demand.  
* Use **HTTP** when the tool server is long-running, resource-heavy, or shared between apps.

---

## 5. MCP Server Examples

### 5.1 Puppeteer (headless browser)

* **Transport:** STDIO  
* **Command:** Provided in config above  
* **Tools available:** `navigate`, `screenshot`, `click`, `type`, `extractText`, …  

Start manually (optional – Relay will auto-spawn):

```bash
npx -y @modelcontextprotocol/server-puppeteer
```

### 5.2 File-System Tools (custom)

A simple Laravel command could expose:

* `read_file(path)`
* `write_file(path, contents)`
* `list_dir(path)`

Register it under `filesystem` server with STDIO transport.

### 5.3 Custom HTTP Server (e.g. GitHub insights)

Implement two endpoints:

```
POST /tools/discover   # returns JSON schema of tools
POST /tools/call       # body: { tool, args }  -> returns result
```

Protect with an API key header and point Relay’s `url` to it.

---

## 6. Tool Discovery & Usage

```php
use Prism\Relay\Facades\Relay;

// 1) Fetch definitions
$tools = Relay::tools('puppeteer');      // ToolCollection
$names = $tools->keys();                 // ['navigate', 'screenshot', …]

// 2) Pass to Prism
Prism::text()
    ->using(Provider::Anthropic, 'claude-3-7-sonnet-latest')
    ->withPrompt('Visit laravel.com and screenshot the hero.')
    ->withTools([...$tools])
    ->asText();
```

Relay caches the discovery payload for `cache_duration` minutes.

---

## 7. Integration Patterns in Wave

### 7.1 AI Assistant CLI

```php
// app/Console/Commands/AiAssistant.php
$response = Prism::text()
    ->withPrompt($prompt)
    ->withTools([...Relay::tools('puppeteer')])
    ->asText();
```

### 7.2 Livewire Component Example

```php
$this->screenshot = Prism::text()
    ->using(Provider::OpenAI, 'gpt-4o-mini')
    ->withPrompt("Take a screenshot of {$this->url}")
    ->withTools([...Relay::tools('puppeteer')])
    ->asText()
    ->text;
```

### 7.3 Queued Job

```php
Relay::tools('filesystem'); // warm cache in queue worker boot

dispatch(function () {
    Prism::text()
        ->withPrompt('Append release notes to CHANGELOG.md')
        ->withTools([...Relay::tools('filesystem')])
        ->asText();
});
```

---

## 8. Error Handling

| Exception | Likely Cause | Remedy |
|-----------|--------------|--------|
| `ServerConfigurationException` | Missing server key in `config/relay.php`. | Add/rename server. |
| `TransportException` | Process failed / HTTP 500 | Check logs, increase timeout, test endpoint manually. |
| `ToolDefinitionException` | Server returned invalid schema | Update server, clear cache. |
| `ToolCallException` | Runtime error inside tool | Inspect `error` field in response payload. |

---

## 9. Troubleshooting Checklist

1. **Process not starting (STDIO)** – run command manually, ensure executable is in `$PATH`.  
2. **`Tools cache stale`** – `php artisan cache:clear` or set `cache_duration=0` while iterating.  
3. **High memory** – long-running STDIO servers stay alive per request cycle; consider switching to HTTP.  
4. **Unicode / binary data** – tools should base64-encode binary blobs (e.g. images) before returning.  

---

## 10. Best Practices

* **Separate concerns** – keep heavy scraping in dedicated HTTP servers; use STDIO for quick utilities.  
* **Limit timeouts** – never leave default `timeout` high in production (protect queue workers).  
* **Validate inputs** – MCP servers should validate arguments and return helpful errors.  
* **Version tools** – include a `version` key in discovery payload; rotate cache when bumped.  
* **Monitor** – log `TransportException` to understand failures early.

---

## 11. Upgrading Relay

```bash
composer update prism-php/relay -W
php artisan optimize
```

Check release notes: <https://github.com/prism-php/relay/releases>

---

## 12. Further Reading

* Prism Guide – [`docs/prism.md`](./prism.md)  
* Full env list – [`docs/env-vars.md`](./env-vars.md)  
* Model Context Protocol – <https://modelcontextprotocol.org>  

---

**Enjoy adding powerful external tools to your Wave CRM AI workflows!**
