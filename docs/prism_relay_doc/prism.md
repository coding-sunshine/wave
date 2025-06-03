# Prism Guide for Wave CRM

*A complete reference for integrating Large-Language-Model (LLM) features in the Wave SaaS starter kit (Laravel 12, PHP 8.3).*

---

## 1. What is Prism?

[Prism](https://prismphp.com) is a Laravel package that wraps multiple AI providers behind one expressive API.  
It lets you:

* switch models/providers with a single line,
* generate text or images,
* call **external tools** (via Relay),
* validate structured output,
* stub calls in automated tests.

Wave builds on Prism for its **AI Assistant CLI**, future Livewire components, and background jobs.

---

## 2. Installation (already done)

```bash
composer require prism-php/prism
```

Wave ships with `prism-php/prism:^0.68.1` in `composer.json`.  
A default config file was copied to **config/prism.php**.

---

## 3. Configuration

### 3.1 `config/prism.php`

Key sections:

| Key | Purpose |
|-----|---------|
| `prism_server.enabled` | Toggle HTTP routes should you expose Prism endpoints. |
| `providers.*` | Per-provider base URL & API key placeholders. |

Feel free to remove providers you will never use.

### 3.2 Environment variables

Add secrets to `.env` (full list in [`docs/env-vars.md`](./env-vars.md)):

```
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=claude-key-...
PRISM_SERVER_ENABLED=false
```

After editing run:

```bash
php artisan optimize:clear
```

---

## 4. Provider Setup Examples

### 4.1 OpenAI

No extra steps beyond setting `OPENAI_API_KEY`.  
Change base URL if you proxy through Azure-OpenAI:

```dotenv
OPENAI_URL=https://{resource}.openai.azure.com/openai/deployments/{deployment}/extensions/chat/completions?api-version=2024-02-15-preview
```

### 4.2 Anthropic

```dotenv
ANTHROPIC_API_KEY=claude-key-...
# Optional
ANTHROPIC_DEFAULT_THINKING_BUDGET=2048
ANTHROPIC_BETA=tool-use
```

---

## 5. Basic Usage

### 5.1 Simple text generation

```php
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;

$response = Prism::text()
    ->using(Provider::OpenAI, 'gpt-4o-mini')
    ->withPrompt('List three KPIs for a SaaS company.')
    ->asText();

echo $response->text;
```

### 5.2 System prompt

```php
Prism::text()
    ->using(Provider::Anthropic, 'claude-3-haiku-latest')
    ->withSystemPrompt('You are a helpful Wave CRM assistant.')
    ->withPrompt('Explain “MRR churn” in 2 sentences.')
    ->asText();
```

### 5.3 Streaming (to the console)

```php
Prism::text()
    ->using(Provider::OpenAI, 'gpt-4o-mini')
    ->withPrompt('Write a 200-word product update.')
    ->stream(function (string $delta) {
        echo $delta;
    });
```

---

## 6. Advanced Features

### 6.1 Tool Use via Relay

```php
use Prism\Relay\Facades\Relay;

$response = Prism::text()
    ->using(Provider::Anthropic, 'claude-3-7-sonnet-latest')
    ->withPrompt('Screenshot laravel.com hero section and describe it.')
    ->withTools([...Relay::tools('puppeteer')])   // STDIO Puppeteer server
    ->asText();
```

### 6.2 Multi-step Agents

```php
Prism::text()
    ->usingTopP(1)
    ->withMaxSteps(5)     // let the model reason & call tools up to 5 times
    ->withPrompt('Research Laravel Breeze and summarise pros/cons.')
    ->withTools([...Relay::tools('puppeteer')])
    ->asText();
```

### 6.3 Structured Output

```php
$response = Prism::text()
    ->expectJson([
        'metric'   => 'string',
        'value'    => 'float',
        'comment'  => 'string',
    ])
    ->withPrompt('Return today’s demo conversion rate as JSON.')
    ->asJson();

$value = $response->value; // strongly typed
```

---

## 7. Wave Integration Patterns

### 7.1 AI Assistant Console (`php artisan ai:assistant`)

The assistant uses the helper below (excerpt):

```php
$agent = Prism::text()
    ->using($providerEnum, $model)
    ->withPrompt($prompt)
    ->withSystemPrompt($systemPrompt)
    ->withTools($tools)
    ->withMaxTokens(4096);
```

Browse the full code in `app/Console/Commands/AiAssistant.php` for patterns such as:

* dynamic provider / model selection,
* Relay tool mergers,
* saving outputs to `storage/app/ai_assistant`.

### 7.2 Livewire Components

```php
public function generateSummary()
{
    $this->summary = Prism::text()
        ->using(Provider::OpenAI, 'gpt-4o-mini')
        ->withPrompt("Summarise deal #{$this->dealId} in 50 words.")
        ->asText()
        ->text;
}
```

### 7.3 Queued Jobs

```php
class GenerateInsights implements ShouldQueue
{
    public function handle()
    {
        $insights = Prism::text()
            ->using(Provider::Anthropic, 'claude-3-opus-latest')
            ->withPrompt('Analyse quarterly revenue data...')
            ->asText()
            ->text;

        // persist to DB...
    }
}
```

---

## 8. Testing with Prism Fakes (Pest)

```php
use Prism\Prism\Facades\Prism as PrismFacade;

PrismFacade::fake([
    'gpt-4o-mini' => 'Hello, world!',
]);

it('generates greeting', function () {
    $result = app(SomeService::class)->generateGreeting();
    expect($result)->toBe('Hello, world!');
});
```

Assertions:

```php
PrismFacade::assertSent();               // at least one call
PrismFacade::assertSentCount(2);
PrismFacade::assertNothingSent();
```

---

## 9. Performance & Limits

| Area | Tip |
|------|-----|
| **Token limits** | Use `withMaxTokens()` and `withMaxSteps()` to guard cost. |
| **Concurrency**  | Run heavy jobs in queues; Prism is HTTP-bound and non-blocking. |
| **Caching**      | Responses can be cached at application level if immutable. |
| **Retries**      | Wrap calls in Laravel’s `retry()` helper for transient network issues. |

---

## 10. Troubleshooting

| Symptom | Possible Cause | Fix |
|---------|----------------|-----|
| `PrismException: Provider not configured` | Missing API key | Add key to `.env`, clear config cache. |
| `TransportException` when using tools | MCP server not reachable | Start server or check `config/relay.php` URL/command. |
| Large responses truncated | `withMaxTokens()` too low | Increase token limit or use streaming. |

---

## 11. Upgrading Prism

```bash
composer update prism-php/prism -W
php artisan optimize
```

Check the [release notes](https://github.com/prism-php/prism/releases) for breaking changes.

---

## 12. Further Reading

* Official docs – <https://prismphp.com>
* Relay guide – [`docs/relay.md`](./relay.md)
* Model Context Protocol – <https://modelcontextprotocol.org>

---

**Happy building intelligent CRM features with Prism!**  
