# Fusion CRM V4 - Xero Integration Architecture

This document outlines the architecture and implementation details for integrating Xero accounting services with Fusion CRM V4, enabling financial management for tenants.

## Overview

The Xero integration in Fusion CRM V4 enables comprehensive financial management capabilities:

1. **Multi-Tenant OAuth2 Integration**: Each tenant connects to their own Xero organization
2. **Contact Synchronization**: Bidirectional sync between CRM contacts and Xero contacts
3. **Invoice Management**: Creation, tracking, and reconciliation of invoices
4. **Commission Tracking**: Calculation and management of agent commissions
5. **Financial Reporting**: Real-time financial dashboards and reports
6. **Payment Tracking**: Monitor payment status across the sales pipeline

## Architectural Components

### 1. OAuth2 Authentication Layer

The Xero integration uses OAuth2 for secure, token-based authentication:

```php
namespace App\Services\Integrations\Xero;

use App\Models\Tenant;
use App\Models\XeroIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class XeroAuthService
{
    /**
     * OAuth2 endpoints
     */
    protected $authorizeUrl = 'https://login.xero.com/identity/connect/authorize';
    protected $tokenUrl = 'https://identity.xero.com/connect/token';
    
    /**
     * Client credentials from config
     */
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    
    /**
     * Required scopes for the application
     */
    protected $scopes = [
        'accounting.transactions',
        'accounting.contacts',
        'accounting.settings',
        'offline_access'
    ];
    
    public function __construct()
    {
        $this->clientId = config('services.xero.client_id');
        $this->clientSecret = config('services.xero.client_secret');
        $this->redirectUri = config('services.xero.redirect_uri');
    }
    
    /**
     * Generate the authorization URL for a tenant
     */
    public function getAuthorizationUrl(Tenant $tenant): string
    {
        $state = encrypt(['tenant_id' => $tenant->id, 'time' => time()]);
        
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $this->scopes),
            'state' => $state
        ];
        
        return $this->authorizeUrl . '?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for tokens
     */
    public function exchangeCodeForTokens(string $code): array
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ]);
        
        if (!$response->successful()) {
            Log::error('Xero token exchange failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            throw new \Exception('Failed to exchange code for tokens');
        }
        
        return $response->json();
    }
    
    /**
     * Refresh access token
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ]);
        
        if (!$response->successful()) {
            Log::error('Xero token refresh failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            throw new \Exception('Failed to refresh token');
        }
        
        return $response->json();
    }
    
    /**
     * Store Xero credentials for a tenant
     */
    public function storeCredentials(Tenant $tenant, array $tokens): XeroIntegration
    {
        $integration = XeroIntegration::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'token_type' => $tokens['token_type'],
                'expires_at' => now()->addSeconds($tokens['expires_in']),
                'xero_tenant_id' => $tokens['id_token'] ?? null,
                'scope' => $tokens['scope'] ?? implode(' ', $this->scopes),
                'connected_at' => now(),
            ]
        );
        
        return $integration;
    }
    
    /**
     * Get a valid access token for API requests
     */
    public function getValidAccessToken(Tenant $tenant): string
    {
        $integration = XeroIntegration::where('tenant_id', $tenant->id)->firstOrFail();
        
        // Check if token needs refresh
        if ($integration->expires_at <= now()->addMinutes(5)) {
            $tokens = $this->refreshToken($integration->refresh_token);
            
            $integration->update([
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_at' => now()->addSeconds($tokens['expires_in']),
            ]);
        }
        
        return $integration->access_token;
    }
}
```

### 2. Xero API Service

Core service for interacting with Xero APIs:

```php
namespace App\Services\Integrations\Xero;

use App\Models\Tenant;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class XeroApiService
{
    protected $baseUrl = 'https://api.xero.com/api.xro/2.0';
    protected $authService;
    protected $tenantManager;
    
    public function __construct(XeroAuthService $authService, TenantManager $tenantManager)
    {
        $this->authService = $authService;
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Get a configured HTTP client with proper authentication
     */
    protected function getHttpClient(Tenant $tenant = null)
    {
        $tenant = $tenant ?? $this->tenantManager->getTenant();
        $accessToken = $this->authService->getValidAccessToken($tenant);
        
        return Http::withToken($accessToken)
            ->withHeaders([
                'Accept' => 'application/json',
                'Xero-Tenant-Id' => $tenant->xeroIntegration->xero_tenant_id
            ]);
    }
    
    /**
     * Get all contacts from Xero
     */
    public function getContacts(Tenant $tenant = null)
    {
        $response = $this->getHttpClient($tenant)->get("{$this->baseUrl}/Contacts");
        
        if (!$response->successful()) {
            throw new \Exception('Failed to fetch contacts from Xero: ' . $response->body());
        }
        
        return $response->json()['Contacts'];
    }
    
    /**
     * Create or update a contact in Xero
     */
    public function saveContact(array $contactData, Tenant $tenant = null)
    {
        $response = $this->getHttpClient($tenant)->post("{$this->baseUrl}/Contacts", [
            'Contacts' => [$contactData]
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to save contact in Xero: ' . $response->body());
        }
        
        return $response->json()['Contacts'][0];
    }
    
    /**
     * Create invoice in Xero
     */
    public function createInvoice(array $invoiceData, Tenant $tenant = null)
    {
        $response = $this->getHttpClient($tenant)->post("{$this->baseUrl}/Invoices", [
            'Invoices' => [$invoiceData]
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to create invoice in Xero: ' . $response->body());
        }
        
        return $response->json()['Invoices'][0];
    }
    
    /**
     * Get invoice by ID
     */
    public function getInvoice(string $invoiceId, Tenant $tenant = null)
    {
        $response = $this->getHttpClient($tenant)->get("{$this->baseUrl}/Invoices/{$invoiceId}");
        
        if (!$response->successful()) {
            throw new \Exception('Failed to fetch invoice from Xero: ' . $response->body());
        }
        
        return $response->json()['Invoices'][0];
    }
    
    /**
     * Check invoice status
     */
    public function getInvoiceStatus(string $invoiceId, Tenant $tenant = null)
    {
        $invoice = $this->getInvoice($invoiceId, $tenant);
        return $invoice['Status'];
    }
    
    /**
     * Get chart of accounts
     */
    public function getAccounts(Tenant $tenant = null)
    {
        $response = $this->getHttpClient($tenant)->get("{$this->baseUrl}/Accounts");
        
        if (!$response->successful()) {
            throw new \Exception('Failed to fetch accounts from Xero: ' . $response->body());
        }
        
        return $response->json()['Accounts'];
    }
}
```

### 3. Synchronization Services

#### Contact Synchronization:

```php
namespace App\Services\Integrations\Xero;

use App\Models\Contact;
use App\Models\Tenant;
use App\Models\XeroContact;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Log;

class ContactSyncService
{
    protected $xeroApiService;
    protected $tenantManager;
    
    public function __construct(XeroApiService $xeroApiService, TenantManager $tenantManager)
    {
        $this->xeroApiService = $xeroApiService;
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Sync a CRM contact to Xero
     */
    public function syncContactToXero(Contact $contact)
    {
        try {
            $tenant = $this->tenantManager->getTenant();
            
            // Format for Xero
            $xeroContactData = [
                'Name' => $contact->first_name . ' ' . $contact->last_name,
                'FirstName' => $contact->first_name,
                'LastName' => $contact->last_name,
                'EmailAddress' => $contact->email,
                'Phones' => [
                    [
                        'PhoneType' => 'DEFAULT',
                        'PhoneNumber' => $contact->phone
                    ]
                ],
                'Addresses' => [
                    [
                        'AddressType' => 'STREET',
                        'AddressLine1' => $contact->address,
                        'City' => $contact->suburb,
                        'Region' => $contact->state,
                        'PostalCode' => $contact->postcode,
                        'Country' => $contact->country
                    ]
                ]
            ];
            
            // Get existing mapping if any
            $xeroContact = XeroContact::where('contact_id', $contact->id)->first();
            
            if ($xeroContact && $xeroContact->xero_contact_id) {
                $xeroContactData['ContactID'] = $xeroContact->xero_contact_id;
            }
            
            // Save to Xero
            $result = $this->xeroApiService->saveContact($xeroContactData, $tenant);
            
            // Store mapping
            XeroContact::updateOrCreate(
                ['contact_id' => $contact->id],
                [
                    'tenant_id' => $tenant->id,
                    'xero_contact_id' => $result['ContactID'],
                    'last_synced_at' => now()
                ]
            );
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to sync contact to Xero', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Sync contacts from Xero to CRM
     */
    public function syncContactsFromXero()
    {
        try {
            $tenant = $this->tenantManager->getTenant();
            $xeroContacts = $this->xeroApiService->getContacts($tenant);
            
            $count = 0;
            
            foreach ($xeroContacts as $xeroContactData) {
                // Skip if already mapped
                $existing = XeroContact::where('xero_contact_id', $xeroContactData['ContactID'])->first();
                if ($existing) {
                    continue;
                }
                
                // Extract name
                $firstName = $xeroContactData['FirstName'] ?? '';
                $lastName = $xeroContactData['LastName'] ?? '';
                
                if (empty($firstName) && empty($lastName) && !empty($xeroContactData['Name'])) {
                    $nameParts = explode(' ', $xeroContactData['Name']);
                    $firstName = $nameParts[0] ?? '';
                    $lastName = count($nameParts) > 1 ? end($nameParts) : '';
                }
                
                // Extract address
                $address = null;
                $suburb = null;
                $state = null;
                $postcode = null;
                $country = null;
                
                if (!empty($xeroContactData['Addresses'])) {
                    $primaryAddress = $xeroContactData['Addresses'][0];
                    $address = $primaryAddress['AddressLine1'] ?? null;
                    $suburb = $primaryAddress['City'] ?? null;
                    $state = $primaryAddress['Region'] ?? null;
                    $postcode = $primaryAddress['PostalCode'] ?? null;
                    $country = $primaryAddress['Country'] ?? null;
                }
                
                // Extract phone
                $phone = null;
                if (!empty($xeroContactData['Phones'])) {
                    $phone = $xeroContactData['Phones'][0]['PhoneNumber'] ?? null;
                }
                
                // Create contact in CRM
                $contact = Contact::create([
                    'tenant_id' => $tenant->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $xeroContactData['EmailAddress'] ?? null,
                    'phone' => $phone,
                    'address' => $address,
                    'suburb' => $suburb,
                    'state' => $state,
                    'postcode' => $postcode,
                    'country' => $country,
                    'source' => 'xero',
                ]);
                
                // Create mapping
                XeroContact::create([
                    'tenant_id' => $tenant->id,
                    'contact_id' => $contact->id,
                    'xero_contact_id' => $xeroContactData['ContactID'],
                    'last_synced_at' => now()
                ]);
                
                $count++;
            }
            
            return $count;
        } catch (\Exception $e) {
            Log::error('Failed to sync contacts from Xero', [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
```

#### Invoice Synchronization:

```php
namespace App\Services\Integrations\Xero;

use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\XeroContact;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Log;

class InvoiceSyncService
{
    protected $xeroApiService;
    protected $tenantManager;
    protected $contactSyncService;
    
    public function __construct(
        XeroApiService $xeroApiService, 
        TenantManager $tenantManager,
        ContactSyncService $contactSyncService
    ) {
        $this->xeroApiService = $xeroApiService;
        $this->tenantManager = $tenantManager;
        $this->contactSyncService = $contactSyncService;
    }
    
    /**
     * Create invoice in Xero for a deal
     */
    public function createInvoiceForDeal(Deal $deal)
    {
        try {
            $tenant = $this->tenantManager->getTenant();
            
            // Ensure contact exists in Xero
            $contact = $deal->contact;
            $xeroContact = XeroContact::where('contact_id', $contact->id)->first();
            
            if (!$xeroContact) {
                $this->contactSyncService->syncContactToXero($contact);
                $xeroContact = XeroContact::where('contact_id', $contact->id)->first();
            }
            
            // Format line items
            $lineItems = [];
            $lineItems[] = [
                'Description' => "Property: {$deal->lot->street_number} {$deal->lot->street_name}",
                'Quantity' => 1,
                'UnitAmount' => $deal->value,
                'AccountCode' => config('xero.default_sales_account'),
                'TaxType' => 'OUTPUT'
            ];
            
            // Format invoice
            $invoiceData = [
                'Type' => 'ACCREC',
                'Contact' => [
                    'ContactID' => $xeroContact->xero_contact_id
                ],
                'Date' => now()->format('Y-m-d'),
                'DueDate' => now()->addDays(30)->format('Y-m-d'),
                'LineItems' => $lineItems,
                'Reference' => "Deal #{$deal->id}",
                'Status' => 'AUTHORISED'
            ];
            
            // Create in Xero
            $result = $this->xeroApiService->createInvoice($invoiceData, $tenant);
            
            // Store in our system
            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'deal_id' => $deal->id,
                'contact_id' => $contact->id,
                'invoice_number' => $result['InvoiceNumber'],
                'xero_invoice_id' => $result['InvoiceID'],
                'xero_contact_id' => $xeroContact->xero_contact_id,
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'amount' => $deal->value,
                'tax_amount' => $deal->value * 0.1, // Assuming 10% tax
                'status' => 'SENT',
                'currency' => 'AUD',
                'reference' => "Deal #{$deal->id}",
                'description' => "Property: {$deal->lot->street_number} {$deal->lot->street_name}",
            ]);
            
            // Update deal status
            $deal->update([
                'invoice_id' => $invoice->id,
                'invoice_status' => 'SENT'
            ]);
            
            return $invoice;
        } catch (\Exception $e) {
            Log::error('Failed to create invoice in Xero', [
                'deal_id' => $deal->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Update invoice status from Xero
     */
    public function updateInvoiceStatus(Invoice $invoice)
    {
        try {
            $tenant = $this->tenantManager->getTenant();
            
            $status = $this->xeroApiService->getInvoiceStatus($invoice->xero_invoice_id, $tenant);
            
            $invoice->update([
                'status' => $status,
                'last_synced_at' => now()
            ]);
            
            // If deal exists, update its status
            if ($invoice->deal) {
                $invoice->deal->update([
                    'invoice_status' => $status
                ]);
                
                // If paid, trigger commission calculation
                if ($status === 'PAID') {
                    event(new InvoicePaid($invoice));
                }
            }
            
            return $status;
        } catch (\Exception $e) {
            Log::error('Failed to update invoice status from Xero', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
```

### 4. Commission Management

Commission calculation and tracking:

```php
namespace App\Services\Commissions;

use App\Models\Commission;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Log;

class CommissionService
{
    protected $tenantManager;
    
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Calculate and create commission for a deal
     */
    public function calculateCommission(Deal $deal)
    {
        try {
            $tenant = $this->tenantManager->getTenant();
            
            // Get assigned user
            $user = User::find($deal->assigned_to);
            if (!$user) {
                throw new \Exception('No user assigned to deal');
            }
            
            // Get commission rate for user
            $rate = $user->commission_rate ?? config('commissions.default_rate', 0.05);
            
            // Calculate commission amount
            $amount = $deal->value * $rate;
            
            // Create commission record
            $commission = Commission::create([
                'tenant_id' => $tenant->id,
                'deal_id' => $deal->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'rate' => $rate,
                'status' => 'PENDING',
            ]);
            
            return $commission;
        } catch (\Exception $e) {
            Log::error('Failed to calculate commission', [
                'deal_id' => $deal->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Process commission when invoice is paid
     */
    public function processCommissionPayment(Invoice $invoice)
    {
        try {
            if (!$invoice->deal) {
                throw new \Exception('Invoice not linked to a deal');
            }
            
            // Find or create commission
            $commission = Commission::where('deal_id', $invoice->deal->id)->first();
            if (!$commission) {
                $commission = $this->calculateCommission($invoice->deal);
            }
            
            // Update commission status
            $commission->update([
                'status' => 'APPROVED',
                'invoice_id' => $invoice->id,
            ]);
            
            // Create Xero invoice for commission payment if configured
            if (config('commissions.create_invoices', true)) {
                $this->createCommissionInvoice($commission);
            }
            
            return $commission;
        } catch (\Exception $e) {
            Log::error('Failed to process commission payment', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Create commission payment invoice
     */
    protected function createCommissionInvoice(Commission $commission)
    {
        // Implementation depends on your accounting workflow
        // This could create a bill in Xero for the commission payment
    }
}
```

### 5. Financial Dashboard Data Services

Services to provide financial dashboard data:

```php
namespace App\Services\Dashboards;

use App\Models\Commission;
use App\Models\Deal;
use App\Models\Invoice;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\DB;

class FinancialDashboardService
{
    protected $tenantManager;
    
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Get summary of financial data
     */
    public function getFinancialSummary()
    {
        $tenant = $this->tenantManager->getTenant();
        $tenantId = $tenant->id;
        
        // Total revenue (from paid invoices)
        $totalRevenue = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'PAID')
            ->sum('amount');
        
        // Outstanding invoices
        $outstandingInvoices = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['AUTHORISED', 'SENT'])
            ->sum('amount');
        
        // Total commissions
        $totalCommissions = Commission::where('tenant_id', $tenantId)
            ->where('status', 'APPROVED')
            ->sum('amount');
        
        // Pending commissions
        $pendingCommissions = Commission::where('tenant_id', $tenantId)
            ->where('status', 'PENDING')
            ->sum('amount');
        
        // Deal pipeline value
        $pipelineValue = Deal::where('tenant_id', $tenantId)
            ->whereNull('actual_close_date')
            ->sum('value');
        
        return [
            'total_revenue' => $totalRevenue,
            'outstanding_invoices' => $outstandingInvoices,
            'total_commissions' => $totalCommissions,
            'pending_commissions' => $pendingCommissions,
            'pipeline_value' => $pipelineValue,
        ];
    }
    
    /**
     * Get monthly revenue data
     */
    public function getMonthlyRevenue($months = 12)
    {
        $tenant = $this->tenantManager->getTenant();
        $tenantId = $tenant->id;
        
        $data = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'PAID')
            ->where('invoice_date', '>=', now()->subMonths($months))
            ->select(
                DB::raw('YEAR(invoice_date) as year'),
                DB::raw('MONTH(invoice_date) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        
        return $data;
    }
    
    /**
     * Get agent commission data
     */
    public function getAgentCommissions()
    {
        $tenant = $this->tenantManager->getTenant();
        $tenantId = $tenant->id;
        
        $data = Commission::where('tenant_id', $tenantId)
            ->where('status', 'APPROVED')
            ->with('user:id,name')
            ->select('user_id', DB::raw('SUM(amount) as total'))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->get();
        
        return $data;
    }
    
    /**
     * Get invoice aging report
     */
    public function getInvoiceAging()
    {
        $tenant = $this->tenantManager->getTenant();
        $tenantId = $tenant->id;
        
        $today = now();
        
        $current = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['AUTHORISED', 'SENT'])
            ->where('due_date', '>=', $today)
            ->sum('amount');
            
        $days30 = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['AUTHORISED', 'SENT'])
            ->where('due_date', '<', $today)
            ->where('due_date', '>=', $today->copy()->subDays(30))
            ->sum('amount');
            
        $days60 = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['AUTHORISED', 'SENT'])
            ->where('due_date', '<', $today->copy()->subDays(30))
            ->where('due_date', '>=', $today->copy()->subDays(60))
            ->sum('amount');
            
        $days90 = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['AUTHORISED', 'SENT'])
            ->where('due_date', '<', $today->copy()->subDays(60))
            ->where('due_date', '>=', $today->copy()->subDays(90))
            ->sum('amount');
            
        $days90Plus = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['AUTHORISED', 'SENT'])
            ->where('due_date', '<', $today->copy()->subDays(90))
            ->sum('amount');
        
        return [
            'current' => $current,
            '30_days' => $days30,
            '60_days' => $days60,
            '90_days' => $days90,
            '90_plus_days' => $days90Plus,
        ];
    }
}
```

## Database Schema

The Xero integration relies on these database tables:

```sql
CREATE TABLE xero_integrations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    access_token TEXT NOT NULL,
    refresh_token TEXT NOT NULL,
    token_type VARCHAR(50) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    xero_tenant_id VARCHAR(255) NOT NULL,
    scope TEXT NULL,
    connected_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE xero_contacts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED NOT NULL,
    xero_contact_id VARCHAR(255) NOT NULL,
    last_synced_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
);

CREATE TABLE invoices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    deal_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    invoice_number VARCHAR(100) NOT NULL,
    xero_invoice_id VARCHAR(255) NULL,
    xero_contact_id VARCHAR(255) NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    tax_amount DECIMAL(12,2) NOT NULL,
    status VARCHAR(50) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    reference VARCHAR(255) NULL,
    description TEXT NULL,
    last_synced_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE SET NULL,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL
);

CREATE TABLE invoice_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    invoice_id BIGINT UNSIGNED NOT NULL,
    description TEXT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    tax_rate DECIMAL(5,2) NOT NULL,
    tax_amount DECIMAL(12,2) NOT NULL,
    xero_line_item_id VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

CREATE TABLE commissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    deal_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    invoice_id BIGINT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL,
    rate DECIMAL(5,2) NULL,
    status VARCHAR(50) NOT NULL,
    payment_date DATE NULL,
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
);
```

## UI Integration Components

### 1. Xero Connection Component

```php
namespace App\Http\Livewire\Settings;

use App\Services\Integrations\Xero\XeroAuthService;
use App\Services\Tenancy\TenantManager;
use Livewire\Component;

class XeroIntegration extends Component
{
    public $connected = false;
    public $connectionUrl;
    public $lastConnected;
    
    protected $listeners = ['xeroConnectionUpdated' => '$refresh'];
    
    public function mount()
    {
        $this->refreshConnectionStatus();
    }
    
    public function refreshConnectionStatus()
    {
        $tenant = app(TenantManager::class)->getTenant();
        $integration = $tenant->xeroIntegration;
        
        $this->connected = $integration && $integration->xero_tenant_id;
        $this->lastConnected = $integration ? $integration->connected_at : null;
        
        if (!$this->connected) {
            $authService = app(XeroAuthService::class);
            $this->connectionUrl = $authService->getAuthorizationUrl($tenant);
        }
    }
    
    public function disconnect()
    {
        $tenant = app(TenantManager::class)->getTenant();
        $tenant->xeroIntegration()->delete();
        
        $this->connected = false;
        $this->refreshConnectionStatus();
        
        $this->emit('xeroDisconnected');
        session()->flash('success', 'Xero integration disconnected successfully.');
    }
    
    public function render()
    {
        return view('livewire.settings.xero-integration');
    }
}
```

### 2. Financial Dashboard Component

```php
namespace App\Http\Livewire\Dashboards;

use App\Services\Dashboards\FinancialDashboardService;
use Livewire\Component;

class FinancialDashboard extends Component
{
    public $summary;
    public $monthlyRevenue;
    public $agentCommissions;
    public $invoiceAging;
    
    protected $listeners = ['refreshFinancialData' => 'loadData'];
    
    public function mount()
    {
        $this->loadData();
    }
    
    public function loadData()
    {
        $service = app(FinancialDashboardService::class);
        
        $this->summary = $service->getFinancialSummary();
        $this->monthlyRevenue = $service->getMonthlyRevenue();
        $this->agentCommissions = $service->getAgentCommissions();
        $this->invoiceAging = $service->getInvoiceAging();
    }
    
    public function render()
    {
        return view('livewire.dashboards.financial-dashboard');
    }
}
```

## Implementation Strategy

### Phase 1: Core Integration

1. **Xero Authentication Implementation**
   - OAuth2 setup and token management
   - Connect/disconnect functionality
   - Tenant isolation for Xero connections

2. **Contact Synchronization**
   - Basic contact mapping
   - Create/update contacts in Xero
   - Sync contacts from Xero to CRM

### Phase 2: Financial Management

3. **Invoice Management**
   - Create invoices for deals
   - Track invoice status
   - Invoice history and details view

4. **Commission Tracking**
   - Calculate commissions based on deals/invoices
   - Commission approval workflow
   - Commission payment tracking

### Phase 3: Dashboard & Reporting

5. **Financial Dashboards**
   - Revenue overview
   - Invoice aging reports
   - Agent commission summaries
   - Pipeline value projections

6. **Advanced Analytics**
   - Cash flow forecasting
   - Commission projections
   - Revenue trend analysis

## Security Considerations

1. **OAuth Token Security**
   - Encrypt tokens in database
   - Implement proper token refresh processes
   - Set up monitoring for token failures

2. **Tenant Isolation**
   - Ensure Xero data is properly isolated by tenant
   - Prevent cross-tenant access to financial data
   - Implement role-based access to financial information

3. **Audit Logging**
   - Log all financial operations
   - Track changes to commission structures
   - Monitor invoice status changes

## Conclusion

The Xero integration in Fusion CRM V4 provides a robust financial management solution that:

1. Maintains proper tenant isolation in a multi-tenant environment
2. Connects each tenant to their own Xero organization 
3. Manages contacts, invoices, and payments efficiently
4. Supports commission calculations and payments
5. Provides comprehensive financial dashboards and reporting

This integration enables real-time financial management, streamlined commission processing, and enhanced financial visibility for all users of the system. 