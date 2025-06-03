# Troubleshooting Prism & Relay  
Wave CRM · Laravel 12 · PHP 8.3

A pragmatic guide to diagnose and fix the most common issues when using **Prism** (LLM integration) and **Relay** (MCP tool bridge) in Wave.

---

## 1. Error Cheat-Sheet

| Symptom / Exception | Likely Cause | Quick Fix |
|--------------------|--------------|-----------|
| `PrismException: Provider not configured` | Missing / typo in API key or provider array removed from `config/prism.php`. | • Add key to `.env`.<br>• Run `php artisan config:clear`. |
| `PrismException: Model is not supported` | Wrong model name for provider. | Use one from docs; verify via provider dashboard. |
| `TransportException: Process timed out` | STDIO server (e.g. Puppeteer) failed to start within `timeout`. | • Increase `timeout` in `config/relay.php`.<br>• Test command manually. |
| `ToolDefinitionException` | MCP server returned invalid discovery schema (missing `name`, `parameters`, etc.). | • Upgrade server.<br>• Clear cache `php artisan cache:clear`. |
| `ToolCallException: 500` | Runtime error inside tool execution. | Inspect `error` field in Relay log; run tool endpoint directly. |
| Empty / truncated response | Token limit hit or safety filters. | • Raise `withMaxTokens()`.<br>• Use streaming. |
| High latency (>20 s) | Network issues, cold-start, large prompts. | • Enable caching.<br>• Reduce prompt size.<br>• Pin region-local endpoints. |
| `cURL error 60: SSL certificate problem` | PHP-cURL cannot validate remote cert. | Update CA bundle or set correct `OPENAI_URL` / endpoint URL. |

---

## 2. Debugging Techniques

### 2.1 Enable Verbose Output

```bash
# Show Relay & Prism debug traces
php artisan ai:assistant --debug
```

### 2.2 Tinker Test

```php
// Quick provider ping
Prism\Prism\Prism::text()
    ->using(Prism\Prism\Enums\Provider::OpenAI, 'gpt-4o-mini')
    ->withPrompt('ping')
    ->asText();
```

### 2.3 Validate MCP Server

```bash
# STDIO: run command directly
npx -y @modelcontextprotocol/server-puppeteer --help

# HTTP: check discovery endpoint
curl -s http://localhost:8001/api/tools/discover | jq .
```

### 2.4 Log Inspections

* Laravel log: `storage/logs/laravel.log` – Prism & Relay exceptions.
* Browserless processes: STDIO servers write to console; redirect to file if needed.
* Queue workers: run with `--tries=1 -vvv` for verbose job output.

---

## 3. Performance Optimisation

| Area | Tip |
|------|-----|
| **Prompt Size** | Strip unnecessary context, ask for concise formats (tables). |
| **Token Budget** | Set `withMaxTokens()` and `withMaxSteps()`; use smaller models (`haiku`, `gpt-4o-mini`) for drafts. |
| **Tool Cache** | Tune `RELAY_TOOLS_CACHE_DURATION` – 0 in dev, 60+ in prod. |
| **STDIO Re-use** | For heavy Puppeteer workloads deploy as **HTTP** service to avoid spawn cost. |
| **Parallelism** | Dispatch long calls to queues; set queue workers `--max-jobs` to recycle memory. |
| **Batching** | Combine small similar requests into one prompt. |

---

## 4. Security Considerations

| Concern | Mitigation |
|---------|------------|
| Leaking secrets via prompts | Never interpolate raw secrets; use abstractions or backend calls. |
| Arbitrary file / browser access | Whitelist tool servers; restrict `filesystem` commands to safe dirs. |
| Over-usage / cost spikes | Set hard `withMaxTokens()` and provider quotas. |
| Inbound HTTP MCP servers | Protect with API keys (`Authorization: Bearer …`) & TLS. |
| Prompt injections | Sanitize user-supplied content; add system prompts that instruct the model to ignore malicious instructions. |

---

## 5. Deployment-Specific Issues

### 5.1 Docker Alpine & OpenSSL

`cURL error 60` often occurs on Alpine images missing CA certificates:

```Dockerfile
RUN apk add --no-cache ca-certificates
```

### 5.2 Octane / RoadRunner

* Disable STDIO servers in workers (`transport => Http`) **or** set `timeout` to 0 so Relay keeps a persistent process per worker.

### 5.3 Serverless (Vercel, Lambda)

* Warm-up functions to avoid cold start on model calls.  
* Use HTTP MCP servers; STDIO child processes may exceed ephemeral FS limits.

---

## 6. Best-Practice Workflow

1. **Local dev** – Set `cache_duration=0`, run tools via STDIO.  
2. **Staging** – Switch heavy tools to HTTP, enable cache 5 min.  
3. **Production** –  
   * PHP 8.3, Opcache.  
   * `cache_duration=60` or more.  
   * Queue workers for long calls.  
   * Monitor provider usage / billing dashboards.

---

## 7. Emergency Checklist

- [ ] Confirm `.env` keys loaded (`php artisan tinker -- env('OPENAI_API_KEY')`).  
- [ ] `php artisan config:clear && php artisan optimize`.  
- [ ] Ping provider with minimal prompt.  
- [ ] `Relay::tools('puppeteer')` returns collection without exception.  
- [ ] Inspect logs for stack trace.  
- [ ] Increase `timeout` temporarily.  
- [ ] Disable tools to isolate Prism vs Relay problem.  

If all fails, enable `--debug`, capture full trace, and open an issue with stack-trace plus provider response body (strip secrets).  
