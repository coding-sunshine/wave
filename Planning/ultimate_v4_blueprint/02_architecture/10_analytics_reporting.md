# Fusion CRM V4 - Analytics & Reporting Architecture

This document outlines the architecture for the Analytics & Reporting system in Fusion CRM V4, focusing on providing actionable insights and data-driven decision making.

## Overview

Fusion CRM V4 implements a comprehensive analytics system that provides:

1. **AI Analytics Layer**: Natural language query interface for data insights
2. **Role-Based Dashboards**: Customized KPI views based on user roles
3. **Conversion Funnel Visualization**: Track leads through the entire sales cycle
4. **AI Deal Forecasting**: Predictive analytics for sales pipeline
5. **Custom Reports**: User-defined reports with flexible filtering

## Core Components

### 1. Analytics Data Service

The central service for collecting, processing, and retrieving analytics data:

```php
namespace App\Services\Analytics;

use App\Models\Deal;
use App\Models\Contact;
use App\Models\User;
use App\Models\Lot;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsDataService
{
    protected $tenantManager;
    
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Get dashboard summary data
     */
    public function getDashboardSummary(array $filters = []): array
    {
        $tenant = $this->tenantManager->getTenant();
        $cacheKey = "dashboard_summary_{$tenant->id}_" . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($filters) {
            $dateRange = $filters['date_range'] ?? 'month';
            $startDate = $this->getStartDateForRange($dateRange);
            
            return [
                'leads' => $this->getLeadCount($startDate),
                'deals' => $this->getDealCount($startDate),
                'revenue' => $this->getRevenue($startDate),
                'conversion_rate' => $this->getConversionRate($startDate),
                'average_deal_size' => $this->getAverageDealSize($startDate),
                'popular_properties' => $this->getPopularProperties($startDate),
                'top_agents' => $this->getTopAgents($startDate),
            ];
        });
    }
    
    /**
     * Get lead count
     */
    protected function getLeadCount(\DateTime $startDate): int
    {
        return Contact::where('created_at', '>=', $startDate)->count();
    }
    
    /**
     * Get deal count
     */
    protected function getDealCount(\DateTime $startDate): int
    {
        return Deal::where('created_at', '>=', $startDate)->count();
    }
    
    /**
     * Get revenue
     */
    protected function getRevenue(\DateTime $startDate): float
    {
        return Deal::where('status', 'won')
            ->where('actual_close_date', '>=', $startDate)
            ->sum('value');
    }
    
    /**
     * Get conversion rate
     */
    protected function getConversionRate(\DateTime $startDate): float
    {
        $leads = $this->getLeadCount($startDate);
        
        if ($leads === 0) {
            return 0;
        }
        
        $dealsWon = Deal::where('status', 'won')
            ->where('actual_close_date', '>=', $startDate)
            ->count();
        
        return ($dealsWon / $leads) * 100;
    }
    
    /**
     * Get average deal size
     */
    protected function getAverageDealSize(\DateTime $startDate): float
    {
        $dealsWon = Deal::where('status', 'won')
            ->where('actual_close_date', '>=', $startDate)
            ->get();
        
        if ($dealsWon->isEmpty()) {
            return 0;
        }
        
        return $dealsWon->avg('value');
    }
    
    /**
     * Get popular properties
     */
    protected function getPopularProperties(\DateTime $startDate): Collection
    {
        return Lot::select('lots.*', DB::raw('COUNT(deals.id) as deal_count'))
            ->leftJoin('deals', 'lots.id', '=', 'deals.lot_id')
            ->where('deals.created_at', '>=', $startDate)
            ->groupBy('lots.id')
            ->orderBy('deal_count', 'desc')
            ->limit(5)
            ->get();
    }
    
    /**
     * Get top agents
     */
    protected function getTopAgents(\DateTime $startDate): Collection
    {
        return User::select('users.*', DB::raw('COUNT(deals.id) as deal_count'), DB::raw('SUM(deals.value) as total_value'))
            ->leftJoin('deals', 'users.id', '=', 'deals.assigned_to')
            ->where('deals.status', 'won')
            ->where('deals.actual_close_date', '>=', $startDate)
            ->groupBy('users.id')
            ->orderBy('total_value', 'desc')
            ->limit(5)
            ->get();
    }
    
    /**
     * Get start date for range
     */
    protected function getStartDateForRange(string $range): \DateTime
    {
        return match($range) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->subDays(30),
        };
    }
    
    /**
     * Get sales funnel data
     */
    public function getSalesFunnelData(array $filters = []): array
    {
        $tenant = $this->tenantManager->getTenant();
        $cacheKey = "sales_funnel_{$tenant->id}_" . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($filters) {
            $dateRange = $filters['date_range'] ?? 'month';
            $startDate = $this->getStartDateForRange($dateRange);
            
            $stages = DB::table('pipeline_stages')
                ->where('tenant_id', $this->tenantManager->getTenant()->id)
                ->orderBy('position')
                ->get()
                ->keyBy('id');
            
            $deals = Deal::where('created_at', '>=', $startDate)
                ->get()
                ->groupBy('stage_id');
            
            $result = [];
            
            foreach ($stages as $stageId => $stage) {
                $stageDeals = $deals->get($stageId, collect());
                
                $result[] = [
                    'stage' => $stage->name,
                    'count' => $stageDeals->count(),
                    'value' => $stageDeals->sum('value'),
                    'conversion_rate' => $this->calculateStageConversionRate($stageId, $startDate),
                ];
            }
            
            return $result;
        });
    }
    
    /**
     * Calculate stage conversion rate
     */
    protected function calculateStageConversionRate(int $stageId, \DateTime $startDate): float
    {
        $stage = DB::table('pipeline_stages')->find($stageId);
        
        if (!$stage) {
            return 0;
        }
        
        $allDealsInStage = Deal::where('stage_id', $stageId)
            ->where('created_at', '>=', $startDate)
            ->count();
        
        if ($allDealsInStage === 0) {
            return 0;
        }
        
        $movedToNextStage = Deal::whereIn('id', function ($query) use ($stageId, $startDate) {
            $query->select('deal_id')
                ->from('deal_activities')
                ->where('activity_type', 'stage_changed')
                ->where('created_at', '>=', $startDate)
                ->whereRaw("JSON_EXTRACT(metadata, '$.old_stage_id') = ?", [$stageId]);
        })->count();
        
        return ($movedToNextStage / $allDealsInStage) * 100;
    }
    
    /**
     * Generate forecast for deals
     */
    public function generateDealForecast(array $filters = []): array
    {
        $tenant = $this->tenantManager->getTenant();
        $cacheKey = "deal_forecast_{$tenant->id}_" . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, now()->addHours(3), function () use ($filters) {
            $dateRange = $filters['date_range'] ?? 'month';
            $startDate = $this->getStartDateForRange($dateRange);
            
            $openDeals = Deal::where('status', 'open')
                ->where('expected_close_date', '>=', $startDate)
                ->get();
            
            $forecastData = [
                'total_pipeline_value' => $openDeals->sum('value'),
                'expected_to_close' => $this->calculateExpectedToClose($openDeals),
                'best_case' => $this->calculateBestCase($openDeals),
                'worst_case' => $this->calculateWorstCase($openDeals),
                'monthly_forecast' => $this->generateMonthlyForecast($openDeals),
            ];
            
            return $forecastData;
        });
    }
    
    /**
     * Calculate deals expected to close
     */
    protected function calculateExpectedToClose(Collection $deals): float
    {
        return $deals->sum(function ($deal) {
            $stage = DB::table('pipeline_stages')->find($deal->stage_id);
            $probability = $stage ? ($stage->probability / 100) : 0.5;
            
            return $deal->value * $probability;
        });
    }
    
    /**
     * Calculate best case scenario
     */
    protected function calculateBestCase(Collection $deals): float
    {
        return $deals->sum(function ($deal) {
            $stage = DB::table('pipeline_stages')->find($deal->stage_id);
            $probability = $stage ? (($stage->probability + 20) / 100) : 0.7;
            $probability = min($probability, 1.0);
            
            return $deal->value * $probability;
        });
    }
    
    /**
     * Calculate worst case scenario
     */
    protected function calculateWorstCase(Collection $deals): float
    {
        return $deals->sum(function ($deal) {
            $stage = DB::table('pipeline_stages')->find($deal->stage_id);
            $probability = $stage ? (($stage->probability - 20) / 100) : 0.3;
            $probability = max($probability, 0.1);
            
            return $deal->value * $probability;
        });
    }
    
    /**
     * Generate monthly forecast
     */
    protected function generateMonthlyForecast(Collection $deals): array
    {
        $forecast = [];
        
        // Group deals by expected close month
        $dealsByMonth = $deals->groupBy(function ($deal) {
            return date('Y-m', strtotime($deal->expected_close_date));
        });
        
        // Generate forecast for the next 6 months
        $currentMonth = now()->format('Y-m');
        
        for ($i = 0; $i < 6; $i++) {
            $month = date('Y-m', strtotime("+{$i} months"));
            $monthDeals = $dealsByMonth->get($month, collect());
            
            $forecast[$month] = [
                'month' => date('M Y', strtotime($month)),
                'count' => $monthDeals->count(),
                'expected_value' => $this->calculateExpectedToClose($monthDeals),
                'best_case' => $this->calculateBestCase($monthDeals),
                'worst_case' => $this->calculateWorstCase($monthDeals),
            ];
        }
        
        return $forecast;
    }
}
```

### 2. Report Service

Managing report generation and execution:

```php
namespace App\Services\Analytics;

use App\Models\Report;
use App\Models\ReportExecution;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportService
{
    protected $tenantManager;
    protected $analyticsDataService;
    
    public function __construct(
        TenantManager $tenantManager,
        AnalyticsDataService $analyticsDataService
    ) {
        $this->tenantManager = $tenantManager;
        $this->analyticsDataService = $analyticsDataService;
    }
    
    /**
     * Create a new report
     */
    public function createReport(array $data): Report
    {
        $tenant = $this->tenantManager->getTenant();
        
        $report = new Report();
        $report->tenant_id = $tenant->id;
        $report->name = $data['name'];
        $report->description = $data['description'] ?? null;
        $report->report_type = $data['report_type'];
        $report->configuration = $data['configuration'] ?? [];
        $report->schedule = $data['schedule'] ?? null;
        $report->created_by = auth()->id();
        $report->save();
        
        return $report;
    }
    
    /**
     * Execute a report
     */
    public function executeReport(Report $report, array $parameters = []): ReportExecution
    {
        try {
            $execution = new ReportExecution();
            $execution->report_id = $report->id;
            $execution->user_id = auth()->id();
            $execution->parameters = $parameters;
            $execution->status = 'processing';
            $execution->save();
            
            // Execute the report based on type
            $method = 'execute' . ucfirst($report->report_type) . 'Report';
            
            if (method_exists($this, $method)) {
                $results = $this->$method($report, $parameters);
                
                $execution->results = $results;
                $execution->status = 'completed';
                $execution->completed_at = now();
                $execution->save();
            } else {
                throw new \Exception("Unsupported report type: {$report->report_type}");
            }
            
            return $execution;
        } catch (\Exception $e) {
            Log::error('Report execution failed', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'parameters' => $parameters
            ]);
            
            if (isset($execution)) {
                $execution->status = 'failed';
                $execution->error = $e->getMessage();
                $execution->save();
            }
            
            throw $e;
        }
    }
    
    /**
     * Execute sales report
     */
    protected function executeSalesReport(Report $report, array $parameters): array
    {
        $config = $report->configuration;
        $dateRange = $parameters['date_range'] ?? $config['default_date_range'] ?? 'month';
        $groupBy = $parameters['group_by'] ?? $config['group_by'] ?? 'day';
        $startDate = $this->analyticsDataService->getStartDateForRange($dateRange);
        
        $query = DB::table('deals')
            ->where('tenant_id', $this->tenantManager->getTenant()->id)
            ->where('status', 'won')
            ->where('actual_close_date', '>=', $startDate);
        
        // Apply additional filters
        if (isset($parameters['agent_id'])) {
            $query->where('assigned_to', $parameters['agent_id']);
        }
        
        if (isset($parameters['pipeline_id'])) {
            $query->where('pipeline_id', $parameters['pipeline_id']);
        }
        
        // Group by time period
        $groupByFormat = match($groupBy) {
            'day' => 'Y-m-d',
            'week' => 'Y-W',
            'month' => 'Y-m',
            'quarter' => 'Y-\QQ',
            'year' => 'Y',
            default => 'Y-m-d',
        };
        
        $results = $query->select(
                DB::raw("DATE_FORMAT(actual_close_date, '{$groupByFormat}') as period"),
                DB::raw('COUNT(*) as deal_count'),
                DB::raw('SUM(value) as total_value'),
                DB::raw('AVG(value) as average_value')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
        
        return [
            'title' => $report->name,
            'description' => $report->description,
            'parameters' => $parameters,
            'results' => $results,
            'summary' => [
                'total_deals' => array_sum(array_column($results, 'deal_count')),
                'total_value' => array_sum(array_column($results, 'total_value')),
                'average_deal_value' => array_sum(array_column($results, 'total_value')) / array_sum(array_column($results, 'deal_count')),
            ]
        ];
    }
    
    /**
     * Execute leads report
     */
    protected function executeLeadsReport(Report $report, array $parameters): array
    {
        $config = $report->configuration;
        $dateRange = $parameters['date_range'] ?? $config['default_date_range'] ?? 'month';
        $groupBy = $parameters['group_by'] ?? $config['group_by'] ?? 'day';
        $startDate = $this->analyticsDataService->getStartDateForRange($dateRange);
        
        $query = DB::table('contacts')
            ->where('tenant_id', $this->tenantManager->getTenant()->id)
            ->where('created_at', '>=', $startDate);
        
        // Apply additional filters
        if (isset($parameters['source'])) {
            $query->where('source', $parameters['source']);
        }
        
        if (isset($parameters['status'])) {
            $query->where('status', $parameters['status']);
        }
        
        // Group by time period
        $groupByFormat = match($groupBy) {
            'day' => 'Y-m-d',
            'week' => 'Y-W',
            'month' => 'Y-m',
            'quarter' => 'Y-\QQ',
            'year' => 'Y',
            default => 'Y-m-d',
        };
        
        $results = $query->select(
                DB::raw("DATE_FORMAT(created_at, '{$groupByFormat}') as period"),
                DB::raw('COUNT(*) as lead_count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
        
        // Get conversion rates
        $conversionRates = [];
        
        foreach ($results as $row) {
            $period = $row->period;
            $periodFormat = match($groupBy) {
                'day' => 'Y-m-d',
                'week' => 'Y-W',
                'month' => 'Y-m',
                'quarter' => 'Y-\QQ',
                'year' => 'Y',
                default => 'Y-m-d',
            };
            
            $periodStartDate = \DateTime::createFromFormat($periodFormat, $period);
            
            if (!$periodStartDate) {
                continue;
            }
            
            $periodEndDate = match($groupBy) {
                'day' => (clone $periodStartDate)->modify('+1 day'),
                'week' => (clone $periodStartDate)->modify('+1 week'),
                'month' => (clone $periodStartDate)->modify('+1 month'),
                'quarter' => (clone $periodStartDate)->modify('+3 months'),
                'year' => (clone $periodStartDate)->modify('+1 year'),
                default => (clone $periodStartDate)->modify('+1 day'),
            };
            
            $leadsConverted = DB::table('deals')
                ->where('tenant_id', $this->tenantManager->getTenant()->id)
                ->whereBetween('created_at', [$periodStartDate, $periodEndDate])
                ->count();
            
            $conversionRates[$period] = $row->lead_count > 0 ? ($leadsConverted / $row->lead_count) * 100 : 0;
        }
        
        return [
            'title' => $report->name,
            'description' => $report->description,
            'parameters' => $parameters,
            'results' => $results,
            'conversion_rates' => $conversionRates,
            'summary' => [
                'total_leads' => array_sum(array_column($results, 'lead_count')),
                'average_conversion_rate' => !empty($conversionRates) ? array_sum($conversionRates) / count($conversionRates) : 0,
            ]
        ];
    }
    
    /**
     * Get all reports
     */
    public function getAllReports(): Collection
    {
        $tenant = $this->tenantManager->getTenant();
        
        return Report::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();
    }
    
    /**
     * Get report executions
     */
    public function getReportExecutions(Report $report, int $limit = 10): Collection
    {
        return $report->executions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
```

### 3. AI Query Service

Natural language processing for analytics queries:

```php
namespace App\Services\Analytics;

use App\Services\AI\AIService;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIQueryService
{
    protected $aiService;
    protected $tenantManager;
    
    public function __construct(AIService $aiService, TenantManager $tenantManager)
    {
        $this->aiService = $aiService;
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Process natural language query
     */
    public function processQuery(string $query): array
    {
        try {
            // Generate SQL query using AI
            $sqlQuery = $this->generateSqlQuery($query);
            
            // Execute the query
            $results = $this->executeGeneratedQuery($sqlQuery);
            
            // Generate natural language explanation
            $explanation = $this->generateExplanation($query, $results);
            
            return [
                'query' => $query,
                'sql' => $sqlQuery,
                'results' => $results,
                'explanation' => $explanation,
                'status' => 'success'
            ];
        } catch (\Exception $e) {
            Log::error('AI query processing failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return [
                'query' => $query,
                'error' => $e->getMessage(),
                'status' => 'error'
            ];
        }
    }
    
    /**
     * Generate SQL query from natural language
     */
    protected function generateSqlQuery(string $query): string
    {
        $tenant = $this->tenantManager->getTenant();
        
        $systemPrompt = "You are a SQL query generator for a real estate CRM. Generate a safe SQL query based on the user's natural language question. The database has the following tables:
            
1. contacts: id, tenant_id, first_name, last_name, email, phone, status, source, created_at
2. deals: id, tenant_id, title, value, status, stage_id, contact_id, lot_id, assigned_to, created_at, updated_at, actual_close_date
3. lots: id, tenant_id, title, property_type, status, price, bedrooms, bathrooms, car_spaces, land_size, suburb, state, postcode
4. users: id, tenant_id, name, email, role
5. pipeline_stages: id, tenant_id, pipeline_id, name, position, probability

Rules:
1. All queries must include 'WHERE tenant_id = {$tenant->id}' to ensure data isolation
2. Use appropriate JOINs when needed
3. Do not use DELETE, UPDATE, or INSERT operations
4. Aim for efficiency and readability
5. Only include SELECT operations
6. Limit results to 100 rows max
7. Provide the SQL query only, with no explanations";

        $userPrompt = "Generate a SQL query for this question: {$query}";
        
        $sqlQuery = $this->aiService->generateText([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.2,
            'max_tokens' => 500,
        ]);
        
        // Clean up the query (remove markdown code blocks if present)
        $sqlQuery = preg_replace('/```sql|```/i', '', $sqlQuery);
        
        return trim($sqlQuery);
    }
    
    /**
     * Execute generated SQL query
     */
    protected function executeGeneratedQuery(string $sqlQuery): array
    {
        // Verify the query is safe (prevent any non-SELECT operations)
        $this->validateQuery($sqlQuery);
        
        // Execute the query
        $results = DB::select($sqlQuery);
        
        return json_decode(json_encode($results), true);
    }
    
    /**
     * Validate that a query is safe to execute
     */
    protected function validateQuery(string $sqlQuery): void
    {
        // Check that the query only contains SELECT statements
        if (!preg_match('/^\s*SELECT\s+/i', $sqlQuery)) {
            throw new \Exception('Only SELECT operations are allowed');
        }
        
        // Check for disallowed operations
        $disallowedOperations = ['DELETE', 'DROP', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 'TRUNCATE', 'GRANT'];
        
        foreach ($disallowedOperations as $operation) {
            if (preg_match('/\b' . $operation . '\b/i', $sqlQuery)) {
                throw new \Exception("Operation not allowed: {$operation}");
            }
        }
        
        // Ensure tenant_id is included in the query
        $tenant = $this->tenantManager->getTenant();
        
        if (!preg_match('/tenant_id\s*=\s*' . $tenant->id . '/i', $sqlQuery)) {
            throw new \Exception('Query must include tenant isolation');
        }
    }
    
    /**
     * Generate natural language explanation of results
     */
    protected function generateExplanation(string $query, array $results): string
    {
        $systemPrompt = "You are an analytics assistant for a real estate CRM. Your task is to provide a brief, clear explanation of the data returned from a database query. Focus on key insights and patterns. Keep your explanation concise and data-focused.";
        
        $userPrompt = "Original question: {$query}\n\nQuery results: " . json_encode($results) . "\n\nProvide a brief explanation of these results, highlighting key insights.";
        
        return $this->aiService->generateText([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.5,
            'max_tokens' => 300,
        ]);
    }
}
```

### 4. Role-Based Dashboard Manager

Customizing dashboards based on user roles:

```php
namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\DashboardConfig;
use App\Services\Access\PermissionService;
use App\Services\Analytics\AnalyticsDataService;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Collection;

class DashboardManager
{
    protected $permissionService;
    protected $analyticsDataService;
    protected $tenantManager;
    
    public function __construct(
        PermissionService $permissionService,
        AnalyticsDataService $analyticsDataService,
        TenantManager $tenantManager
    ) {
        $this->permissionService = $permissionService;
        $this->analyticsDataService = $analyticsDataService;
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Get dashboard configuration for user
     */
    public function getDashboardConfig(User $user): array
    {
        // Check for user-specific config
        $userConfig = DashboardConfig::where('user_id', $user->id)->first();
        
        if ($userConfig) {
            return $this->applyPermissionsToConfig($userConfig->configuration, $user);
        }
        
        // Get role-based config
        $roleIds = $user->roles()->pluck('roles.id')->toArray();
        
        if (!empty($roleIds)) {
            $roleConfig = DashboardConfig::whereIn('role_id', $roleIds)
                ->orderBy('updated_at', 'desc')
                ->first();
            
            if ($roleConfig) {
                return $this->applyPermissionsToConfig($roleConfig->configuration, $user);
            }
        }
        
        // Fall back to default config based on user's permissions
        return $this->getDefaultConfig($user);
    }
    
    /**
     * Apply permission filters to dashboard configuration
     */
    protected function applyPermissionsToConfig(array $config, User $user): array
    {
        $filteredWidgets = [];
        
        foreach ($config['widgets'] ?? [] as $widget) {
            // Skip widget if user doesn't have required permission
            if (isset($widget['permission']) && !$this->permissionService->hasPermission($user, $widget['permission'])) {
                continue;
            }
            
            $filteredWidgets[] = $widget;
        }
        
        $config['widgets'] = $filteredWidgets;
        
        return $config;
    }
    
    /**
     * Get default dashboard configuration
     */
    protected function getDefaultConfig(User $user): array
    {
        // Define available widgets
        $availableWidgets = [
            [
                'id' => 'leads_summary',
                'type' => 'summary',
                'title' => 'Leads Summary',
                'permission' => 'contacts.view',
                'dataSource' => 'leads_summary',
                'position' => ['row' => 0, 'col' => 0, 'sizeX' => 1, 'sizeY' => 1]
            ],
            [
                'id' => 'deals_summary',
                'type' => 'summary',
                'title' => 'Deals Summary',
                'permission' => 'deals.view',
                'dataSource' => 'deals_summary',
                'position' => ['row' => 0, 'col' => 1, 'sizeX' => 1, 'sizeY' => 1]
            ],
            [
                'id' => 'revenue_summary',
                'type' => 'summary',
                'title' => 'Revenue',
                'permission' => 'deals.view',
                'dataSource' => 'revenue_summary',
                'position' => ['row' => 0, 'col' => 2, 'sizeX' => 1, 'sizeY' => 1]
            ],
            [
                'id' => 'conversion_rate',
                'type' => 'percentage',
                'title' => 'Conversion Rate',
                'permission' => 'deals.view',
                'dataSource' => 'conversion_rate',
                'position' => ['row' => 0, 'col' => 3, 'sizeX' => 1, 'sizeY' => 1]
            ],
            [
                'id' => 'sales_funnel',
                'type' => 'funnel',
                'title' => 'Sales Funnel',
                'permission' => 'deals.view',
                'dataSource' => 'sales_funnel',
                'position' => ['row' => 1, 'col' => 0, 'sizeX' => 2, 'sizeY' => 2]
            ],
            [
                'id' => 'deals_by_stage',
                'type' => 'bar',
                'title' => 'Deals by Stage',
                'permission' => 'deals.view',
                'dataSource' => 'deals_by_stage',
                'position' => ['row' => 1, 'col' => 2, 'sizeX' => 2, 'sizeY' => 1]
            ],
            [
                'id' => 'recent_deals',
                'type' => 'list',
                'title' => 'Recent Deals',
                'permission' => 'deals.view',
                'dataSource' => 'recent_deals',
                'position' => ['row' => 2, 'col' => 2, 'sizeX' => 2, 'sizeY' => 1]
            ],
            [
                'id' => 'top_agents',
                'type' => 'list',
                'title' => 'Top Performing Agents',
                'permission' => 'users.view',
                'dataSource' => 'top_agents',
                'position' => ['row' => 3, 'col' => 0, 'sizeX' => 2, 'sizeY' => 1]
            ],
            [
                'id' => 'popular_properties',
                'type' => 'list',
                'title' => 'Popular Properties',
                'permission' => 'properties.view',
                'dataSource' => 'popular_properties',
                'position' => ['row' => 3, 'col' => 2, 'sizeX' => 2, 'sizeY' => 1]
            ],
            [
                'id' => 'deal_forecast',
                'type' => 'forecast',
                'title' => 'Deal Forecast',
                'permission' => 'reports.view',
                'dataSource' => 'deal_forecast',
                'position' => ['row' => 4, 'col' => 0, 'sizeX' => 4, 'sizeY' => 1]
            ],
        ];
        
        // Filter widgets based on user's permissions
        $widgets = array_filter($availableWidgets, function ($widget) use ($user) {
            return !isset($widget['permission']) || $this->permissionService->hasPermission($user, $widget['permission']);
        });
        
        return [
            'layout' => 'grid',
            'widgets' => array_values($widgets)
        ];
    }
    
    /**
     * Save user dashboard configuration
     */
    public function saveDashboardConfig(User $user, array $config): DashboardConfig
    {
        $dashboardConfig = DashboardConfig::updateOrCreate(
            ['user_id' => $user->id],
            [
                'tenant_id' => $this->tenantManager->getTenant()->id,
                'configuration' => $config
            ]
        );
        
        return $dashboardConfig;
    }
    
    /**
     * Reset user dashboard to default
     */
    public function resetDashboardConfig(User $user): void
    {
        DashboardConfig::where('user_id', $user->id)->delete();
    }
    
    /**
     * Get dashboard data
     */
    public function getDashboardData(User $user, array $parameters = []): array
    {
        $config = $this->getDashboardConfig($user);
        $data = [];
        
        // Get data for each widget
        foreach ($config['widgets'] as $widget) {
            $dataSourceMethod = 'get' . ucfirst($widget['dataSource']) . 'Data';
            
            if (method_exists($this, $dataSourceMethod)) {
                $data[$widget['id']] = $this->$dataSourceMethod($parameters);
            } else {
                $data[$widget['id']] = ['error' => 'Data source not found'];
            }
        }
        
        return [
            'config' => $config,
            'data' => $data
        ];
    }
    
    /**
     * Get leads summary data
     */
    protected function getLeadsSummaryData(array $parameters = []): array
    {
        $dateRange = $parameters['date_range'] ?? 'month';
        $startDate = $this->analyticsDataService->getStartDateForRange($dateRange);
        
        $leadCount = $this->analyticsDataService->getLeadCount($startDate);
        $previousStartDate = clone $startDate;
        
        switch ($dateRange) {
            case 'today':
                $previousStartDate->modify('-1 day');
                break;
            case 'week':
                $previousStartDate->modify('-1 week');
                break;
            case 'month':
                $previousStartDate->modify('-1 month');
                break;
            case 'quarter':
                $previousStartDate->modify('-3 months');
                break;
            case 'year':
                $previousStartDate->modify('-1 year');
                break;
            default:
                $previousStartDate->modify('-30 days');
                break;
        }
        
        $previousLeadCount = $this->analyticsDataService->getLeadCount($previousStartDate);
        $percentChange = $previousLeadCount > 0 ? (($leadCount - $previousLeadCount) / $previousLeadCount) * 100 : 0;
        
        return [
            'count' => $leadCount,
            'previous' => $previousLeadCount,
            'percent_change' => round($percentChange, 1),
            'trend' => $percentChange >= 0 ? 'up' : 'down'
        ];
    }
    
    // Additional data source methods would be implemented here
}
```

## Analytics Database Schema

```sql
-- Dashboard configurations
CREATE TABLE dashboard_configs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    role_id BIGINT UNSIGNED NULL,
    configuration JSON NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- Custom reports
CREATE TABLE reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    report_type VARCHAR(50) NOT NULL,
    configuration JSON NOT NULL,
    schedule JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Report executions
CREATE TABLE report_executions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    parameters JSON NULL,
    results JSON NULL,
    status VARCHAR(20) NOT NULL,
    error TEXT NULL,
    created_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Analytics queries
CREATE TABLE analytics_queries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    query_text TEXT NOT NULL,
    sql_query TEXT NULL,
    results JSON NULL,
    status VARCHAR(20) NOT NULL,
    execution_time INT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Analytics data snapshots
CREATE TABLE analytics_snapshots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    snapshot_date DATE NOT NULL,
    snapshot_type VARCHAR(50) NOT NULL,
    data JSON NOT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE INDEX tenant_snapshot (tenant_id, snapshot_date, snapshot_type)
);
```

## Implementation Strategy

### Phase 1: Core Analytics Framework

1. **Basic Dashboard**
   - Implement key metrics calculation
   - Create dashboard structure and widgets
   - Role-based dashboard configurations

2. **Data Aggregation**
   - Periodic data snapshots
   - Caching strategies
   - Performance optimization

### Phase 2: Reporting Engine

3. **Custom Report Builder**
   - Report configuration UI
   - Report execution engine
   - Scheduling capabilities

4. **Export Functionality**
   - PDF, Excel, and CSV exports
   - Branded report templates
   - Sharing options

### Phase 3: Advanced Analytics

5. **AI Analytics Layer**
   - Natural language query processing
   - Query optimization and security
   - Results visualization

6. **Predictive Analytics**
   - Deal forecasting algorithms
   - AI-powered insights
   - Trend detection

## Security Considerations

1. **Data Access Control**
   - Role-based access to reports and dashboards
   - Tenant data isolation
   - Permission-based filtering of results

2. **Query Validation**
   - SQL injection prevention
   - AI-generated query validation
   - Rate limiting for complex queries

3. **Data Privacy**
   - PII handling in reports
   - Configurable data masking
   - Audit logging of all analytics access

## Conclusion

The Analytics & Reporting system in Fusion CRM V4 provides:

1. **Actionable Insights**: Real-time metrics to drive decision making
2. **Role-Based Views**: Customized analytics tailored to different roles
3. **AI-Powered Analysis**: Natural language queries and predictive analytics
4. **Flexible Reporting**: Custom report building for specific business needs
5. **Performance Optimization**: Efficient data processing for large datasets

This architecture supports advanced analytics capabilities while maintaining performance, security, and usability across the entire CRM platform. 