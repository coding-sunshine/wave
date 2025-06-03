# Code Examples — Prism + Relay in Wave CRM

A curated collection of **copy-paste ready** snippets that show how to integrate AI throughout the Wave SaaS stack.

Sections  
1. Livewire component – inline AI suggestions  
2. Console command – bulk lead enrichment via web research  
3. Queued job – nightly “churn risk” report  
4. Service class – smart email sequence generator  
5. Testing patterns – faking Prism & Relay in Pest

---

## 1. Livewire Component – “Deal Insights”

**Goal:** On a deal view page, let reps click “Generate Insights” and receive instant talking points powered by Claude and recent web research.

```php
// app/Livewire/DealInsights.php
namespace App\Livewire;

use Livewire\Component;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Relay\Facades\Relay;

class DealInsights extends Component
{
    public $deal;          // injected from parent component / route
    public $insights = '';
    public $loading  = false;

    public function render()
    {
        return view('livewire.deal-insights');
    }

    public function generate()
    {
        $this->loading = true;

        $prompt = <<<PROMPT
Research {$this->deal->company} and provide three talking points for the next call.
Focus on recent news, product releases, and industry trends.
PROMPT;

        $response = Prism::text()
            ->using(Provider::Anthropic, 'claude-3-7-sonnet-latest')
            ->withSystemPrompt('You are a B2B sales assistant.')
            ->withPrompt($prompt)
            ->withTools([...Relay::tools('puppeteer')])   // browse web
            ->withMaxSteps(8)   // allow multi-tool reasoning
            ->asText();

        $this->insights = $response->text;
        $this->loading  = false;
    }
}
```

Blade snippet:

```blade
<div>
  @if ($loading)
      <x-filament::spinner />
  @elseif ($insights)
      <x-markdown :content="$insights" />
  @else
      <x-filament::button wire:click="generate">
          Generate Insights
      </x-filament::button>
  @endif
</div>
```

---

## 2. Console Command – Bulk Lead Enrichment

**Use-case:** Marketing wants LinkedIn headlines for all *Qualified* leads. We’ll loop through leads and call an LLM with Puppeteer tools.

```php
// app/Console/Commands/LeadEnrich.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Relay\Facades\Relay;

class LeadEnrich extends Command
{
    protected $signature = 'leads:enrich {limit=50}';
    protected $description = 'Fetch LinkedIn taglines for qualified leads';

    public function handle()
    {
        $leads = Lead::qualified()->limit($this->argument('limit'))->get();

        $bar = $this->output->createProgressBar($leads->count());

        foreach ($leads as $lead) {
            $prompt = "Find the LinkedIn profile for {$lead->company} {$lead->contact_name} " .
                      "and return their headline in 20 words or less.";

            $response = Prism::text()
                ->using(Provider::OpenAI, 'gpt-4o-mini')
                ->withSystemPrompt('You are a B2B data enrichment bot.')
                ->withPrompt($prompt)
                ->withTools([...Relay::tools('puppeteer')])
                ->withMaxTokens(100)
                ->asText();

            $lead->update(['headline' => $response->text]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Enrichment complete!');
    }
}
```

Run:

```bash
php artisan leads:enrich 100
```

---

## 3. Queued Job – Nightly Churn-Risk Report

```php
// app/Jobs/GenerateChurnRisk.php
namespace App\Jobs;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;

class GenerateChurnRisk implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle()
    {
        $data = Customer::with('subscriptions')
            ->active()
            ->take(500)               // cap payload
            ->get()
            ->toArray();

        $prompt = <<<PROMPT
Here is JSON data for 500 customers with plan, MRR, last_login_at, NPS score
and support ticket counts. Identify the top 10 at risk of churn and explain why.
PROMPT;

        $json = json_encode($data, JSON_PRETTY_PRINT);

        $report = Prism::text()
            ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
            ->withSystemPrompt('You are a SaaS retention analyst.')
            ->withPrompt("```json\n{$json}\n```\n\n{$prompt}")
            ->expectMarkdown()   // return nicely formatted report
            ->withMaxTokens(1500)
            ->asText()
            ->text;

        Mail::raw($report, fn ($m) => $m
            ->to('success@wavecrm.test')
            ->subject('Nightly Churn-Risk Report'));
    }
}
```

Schedule in `App\Console\Kernel`:

```php
$schedule->job(new GenerateChurnRisk)->dailyAt('02:00');
```

---

## 4. Service Class – Smart Email Sequence

```php
// app/Services/EmailSequenceBuilder.php
namespace App\Services;

use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;

class EmailSequenceBuilder
{
    public function campaign(string $audience, string $goal, string $product): string
    {
        $prompt = <<<PROMPT
Write a 3-step email sequence targeting {$audience} promoting {$product}.
Goal: {$goal}. Include subject lines and CTA suggestions.
PROMPT;

        return Prism::text()
            ->using(Provider::OpenAI, 'gpt-4-turbo')
            ->withSystemPrompt('You are a SaaS copywriter.')
            ->withPrompt($prompt)
            ->asText()
            ->text;
    }
}
```

Controller usage:

```php
$sequence = app(EmailSequenceBuilder::class)
    ->campaign('marketing managers', 'feature adoption', 'Dashboards 2.0');
```

---

## 5. Testing Patterns (Pest)

### 5.1 Faking Prism

```php
use Prism\Prism\Facades\Prism as PrismFacade;

it('returns canned insights', function () {
    PrismFacade::fake([
        'claude-3-7-sonnet-latest' => "Insight 1\nInsight 2\nInsight 3"
    ]);

    $livewire = Livewire::test('deal-insights', ['deal' => Deal::factory()->create()]);
    $livewire->call('generate')
             ->assertSee('Insight 1');

    PrismFacade::assertSent();             // one call registered
});
```

### 5.2 Faking Relay Tools

Relay exposes a fake helper so your tests run without launching Puppeteer.

```php
use Prism\Relay\Facades\Relay;

beforeEach(function () {
    Relay::fake('puppeteer', [
        'screenshot' => fn ($args) => 'data:image/png;base64,FAKE',
        'navigate'   => fn () => null,
    ]);
});

it('generates screenshot summary', function () {
    $job = new GenerateScreenshotSummary('https://laravel.com');
    $content = $job->handle();
    expect($content)->toContain('FAKE');
});
```

_If a test tries an **undefined** fake tool, Relay raises `ToolCallException` – ensuring your mocks are up-to-date._  

---

## 6. Cheat-Sheet

| Task | Snippet |
|------|---------|
| Discover tools | `Relay::tools('puppeteer')->keys()` |
| Combine tool servers | `array_merge(Relay::tools('fs')->toArray(), Relay::tools('git')->toArray())` |
| Stream tokens | `->stream(fn ($delta) => echo $delta)` |
| Force JSON output | `->expectJson([...schema...])->asJson()` |
| Max thinking steps | `->withMaxSteps(10)` |

---

Use these examples as templates and adapt them to your Wave CRM workflows.  
Pull requests welcome if you craft new patterns! 🚀
