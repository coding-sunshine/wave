# Fusion CRM V4 - Core CRM Features Architecture

This document outlines the architecture for the core CRM features in Fusion CRM V4, focusing on the essential components that drive the customer relationship management functionality.

## Overview

Fusion CRM V4 implements these core CRM capabilities:

1. **Client Profiles & Management**: Comprehensive contact profiles with interaction history
2. **Sales Pipeline & Deals**: Advanced deal tracking from lead to settlement
3. **Task & Follow-Up System**: Intelligent task scheduling and reminders
4. **Document Management**: Secure storage and management of client documents
5. **Reservation System**: Streamlined property reservation process

## Client Profiles Architecture

### Data Model

```sql
CREATE TABLE contacts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    alternate_phone VARCHAR(50) NULL,
    address TEXT NULL,
    suburb VARCHAR(100) NULL,
    state VARCHAR(50) NULL,
    postcode VARCHAR(20) NULL,
    country VARCHAR(100) NULL DEFAULT 'Australia',
    date_of_birth DATE NULL,
    occupation VARCHAR(255) NULL,
    income_range VARCHAR(50) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    source VARCHAR(100) NULL,
    assigned_to BIGINT UNSIGNED NULL,
    lead_score INT NULL,
    tags JSON NULL,
    custom_fields JSON NULL,
    notes TEXT NULL,
    last_interaction_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE interactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    interaction_type VARCHAR(50) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### Contact Repository

```php
namespace App\Repositories;

use App\Models\Contact;
use App\Services\Tenancy\TenantManager;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ContactRepository
{
    protected $tenantManager;
    
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Get all contacts with pagination
     */
    public function getAllPaginated(int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return Contact::with($with)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
    
    /**
     * Search contacts
     */
    public function search(string $term, int $perPage = 20): LengthAwarePaginator
    {
        return Contact::where(function($query) use ($term) {
            $query->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        })
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
    }
    
    /**
     * Find by email
     */
    public function findByEmail(string $email): ?Contact
    {
        return Contact::where('email', $email)->first();
    }
    
    /**
     * Create a new contact
     */
    public function create(array $data): Contact
    {
        $contact = new Contact();
        $contact->fill($data);
        $contact->save();
        
        return $contact;
    }
    
    /**
     * Update a contact
     */
    public function update(Contact $contact, array $data): Contact
    {
        $contact->fill($data);
        $contact->save();
        
        return $contact;
    }
    
    /**
     * Delete a contact
     */
    public function delete(Contact $contact): bool
    {
        return $contact->delete();
    }
    
    /**
     * Record an interaction for a contact
     */
    public function recordInteraction(Contact $contact, string $type, ?string $notes = null): Contact
    {
        $contact->interactions()->create([
            'tenant_id' => $this->tenantManager->getTenant()->id,
            'user_id' => auth()->id(),
            'interaction_type' => $type,
            'notes' => $notes,
        ]);
        
        $contact->last_interaction_at = now();
        $contact->save();
        
        return $contact;
    }
}
```

## Sales Pipeline Architecture

### Data Model

```sql
CREATE TABLE pipelines (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    is_default BOOLEAN NOT NULL DEFAULT false,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE pipeline_stages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    pipeline_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    position INT NOT NULL,
    color VARCHAR(20) NULL,
    probability INT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (pipeline_id) REFERENCES pipelines(id) ON DELETE CASCADE
);

CREATE TABLE deals (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    pipeline_id BIGINT UNSIGNED NOT NULL,
    stage_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    value DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'AUD',
    assigned_to BIGINT UNSIGNED NULL,
    expected_close_date DATE NULL,
    actual_close_date DATE NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'open',
    invoice_id BIGINT UNSIGNED NULL,
    invoice_status VARCHAR(50) NULL,
    notes TEXT NULL,
    custom_fields JSON NULL,
    tags JSON NULL,
    last_activity_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (pipeline_id) REFERENCES pipelines(id) ON DELETE CASCADE,
    FOREIGN KEY (stage_id) REFERENCES pipeline_stages(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (lot_id) REFERENCES lots(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
);

CREATE TABLE deal_activities (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    deal_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### Deal Service

```php
namespace App\Services\Deal;

use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Repositories\DealRepository;
use App\Events\DealStageChanged;
use App\Events\DealCreated;
use App\Events\DealClosed;

class DealService
{
    protected $dealRepository;
    
    public function __construct(DealRepository $dealRepository)
    {
        $this->dealRepository = $dealRepository;
    }
    
    /**
     * Create a new deal
     */
    public function createDeal(array $data): Deal
    {
        $deal = $this->dealRepository->create($data);
        
        // Record activity
        $this->dealRepository->recordActivity($deal, 'created', 'Deal created');
        
        // Trigger event
        event(new DealCreated($deal));
        
        return $deal;
    }
    
    /**
     * Update deal
     */
    public function updateDeal(Deal $deal, array $data): Deal
    {
        $originalStageId = $deal->stage_id;
        
        $deal = $this->dealRepository->update($deal, $data);
        
        // Check if stage changed
        if (isset($data['stage_id']) && $originalStageId != $data['stage_id']) {
            $newStage = PipelineStage::find($data['stage_id']);
            
            // Record activity
            $this->dealRepository->recordActivity(
                $deal, 
                'stage_changed', 
                "Deal moved to {$newStage->name} stage"
            );
            
            // Trigger event
            event(new DealStageChanged($deal, $originalStageId, $data['stage_id']));
        }
        
        return $deal;
    }
    
    /**
     * Change deal stage
     */
    public function changeDealStage(Deal $deal, PipelineStage $stage): Deal
    {
        $originalStageId = $deal->stage_id;
        
        $deal->stage_id = $stage->id;
        $deal->save();
        
        // Record activity
        $this->dealRepository->recordActivity(
            $deal, 
            'stage_changed', 
            "Deal moved to {$stage->name} stage"
        );
        
        // Trigger event
        event(new DealStageChanged($deal, $originalStageId, $stage->id));
        
        return $deal;
    }
    
    /**
     * Close deal (won)
     */
    public function closeDealWon(Deal $deal, array $data = []): Deal
    {
        $deal->status = 'won';
        $deal->actual_close_date = $data['close_date'] ?? now();
        
        if (isset($data['notes'])) {
            $deal->notes = $data['notes'];
        }
        
        $deal->save();
        
        // Record activity
        $this->dealRepository->recordActivity($deal, 'closed_won', 'Deal closed (won)');
        
        // Trigger event
        event(new DealClosed($deal, 'won'));
        
        return $deal;
    }
    
    /**
     * Close deal (lost)
     */
    public function closeDealLost(Deal $deal, array $data = []): Deal
    {
        $deal->status = 'lost';
        $deal->actual_close_date = $data['close_date'] ?? now();
        
        if (isset($data['notes'])) {
            $deal->notes = $data['notes'];
        }
        
        $deal->save();
        
        // Record activity
        $this->dealRepository->recordActivity(
            $deal, 
            'closed_lost', 
            'Deal closed (lost)' . (isset($data['reason']) ? ": {$data['reason']}" : '')
        );
        
        // Trigger event
        event(new DealClosed($deal, 'lost'));
        
        return $deal;
    }
    
    /**
     * Reopen deal
     */
    public function reopenDeal(Deal $deal): Deal
    {
        $deal->status = 'open';
        $deal->actual_close_date = null;
        $deal->save();
        
        // Record activity
        $this->dealRepository->recordActivity($deal, 'reopened', 'Deal reopened');
        
        return $deal;
    }
}
```

## Task Management Architecture

### Data Model

```sql
CREATE TABLE tasks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    priority VARCHAR(20) NOT NULL DEFAULT 'medium',
    due_date TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    user_id BIGINT UNSIGNED NULL,
    assigned_to BIGINT UNSIGNED NULL,
    related_type VARCHAR(100) NULL,
    related_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE task_reminders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    task_id BIGINT UNSIGNED NOT NULL,
    remind_at TIMESTAMP NOT NULL,
    sent_at TIMESTAMP NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);
```

### Task Service

```php
namespace App\Services\Task;

use App\Models\Task;
use App\Repositories\TaskRepository;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use Carbon\Carbon;

class TaskService
{
    protected $taskRepository;
    
    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }
    
    /**
     * Create a new task
     */
    public function createTask(array $data): Task
    {
        $task = $this->taskRepository->create($data);
        
        // Create reminder if due date is set
        if (isset($data['due_date']) && !empty($data['due_date'])) {
            $dueDate = Carbon::parse($data['due_date']);
            
            // Create reminder 24 hours before due date
            $this->createReminder($task, $dueDate->copy()->subDay());
            
            // Create reminder 1 hour before due date
            $this->createReminder($task, $dueDate->copy()->subHour());
        }
        
        // Trigger event if assigned to someone
        if (isset($data['assigned_to']) && !empty($data['assigned_to'])) {
            event(new TaskAssigned($task));
        }
        
        return $task;
    }
    
    /**
     * Update task
     */
    public function updateTask(Task $task, array $data): Task
    {
        $oldAssignedTo = $task->assigned_to;
        
        $task = $this->taskRepository->update($task, $data);
        
        // Handle due date change
        if (isset($data['due_date']) && $task->getOriginal('due_date') != $data['due_date']) {
            // Delete existing reminders
            $task->reminders()->delete();
            
            if (!empty($data['due_date'])) {
                $dueDate = Carbon::parse($data['due_date']);
                
                // Create new reminders
                $this->createReminder($task, $dueDate->copy()->subDay());
                $this->createReminder($task, $dueDate->copy()->subHour());
            }
        }
        
        // Trigger event if assigned to someone new
        if (isset($data['assigned_to']) && $oldAssignedTo != $data['assigned_to']) {
            event(new TaskAssigned($task));
        }
        
        return $task;
    }
    
    /**
     * Complete task
     */
    public function completeTask(Task $task, ?string $notes = null): Task
    {
        $task->status = 'completed';
        $task->completed_at = now();
        
        if ($notes) {
            $task->description = ($task->description ? $task->description . "\n\n" : '') . 
                "Completion notes: {$notes}";
        }
        
        $task->save();
        
        // Delete any pending reminders
        $task->reminders()->where('status', 'pending')->delete();
        
        // Trigger event
        event(new TaskCompleted($task));
        
        return $task;
    }
    
    /**
     * Create a reminder for a task
     */
    protected function createReminder(Task $task, Carbon $remindAt): void
    {
        $task->reminders()->create([
            'remind_at' => $remindAt,
            'status' => 'pending'
        ]);
    }
    
    /**
     * Get overdue tasks
     */
    public function getOverdueTasks(?int $userId = null): array
    {
        $query = Task::where('status', '!=', 'completed')
            ->where('due_date', '<', now());
        
        if ($userId) {
            $query->where('assigned_to', $userId);
        }
        
        return $query->get()->toArray();
    }
    
    /**
     * Get upcoming tasks
     */
    public function getUpcomingTasks(?int $userId = null, int $days = 7): array
    {
        $query = Task::where('status', '!=', 'completed')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays($days));
        
        if ($userId) {
            $query->where('assigned_to', $userId);
        }
        
        return $query->get()->toArray();
    }
}
```

## Document Management

### Data Model

```sql
CREATE TABLE documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size BIGINT NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    category VARCHAR(100) NULL,
    description TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    uploaded_by BIGINT UNSIGNED NULL,
    related_type VARCHAR(100) NULL,
    related_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE document_shares (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    shared_by BIGINT UNSIGNED NOT NULL,
    shared_with BIGINT UNSIGNED NULL,
    share_token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NULL,
    downloads INT NOT NULL DEFAULT 0,
    last_downloaded_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_with) REFERENCES users(id) ON DELETE SET NULL
);
```

### Document Service

```php
namespace App\Services\Document;

use App\Models\Document;
use App\Repositories\DocumentRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DocumentService
{
    protected $documentRepository;
    
    public function __construct(DocumentRepository $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }
    
    /**
     * Store a document
     */
    public function storeDocument(UploadedFile $file, array $data): Document
    {
        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        
        // Determine storage path
        $category = $data['category'] ?? 'general';
        $path = 'documents/' . $category;
        
        // Store the file
        $filePath = $file->storeAs($path, $filename, 'tenant');
        
        // Create document record
        $documentData = [
            'title' => $data['title'] ?? $file->getClientOriginalName(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'file_type' => $file->getMimeType(),
            'category' => $category,
            'description' => $data['description'] ?? null,
            'uploaded_by' => auth()->id(),
            'related_type' => $data['related_type'] ?? null,
            'related_id' => $data['related_id'] ?? null,
        ];
        
        return $this->documentRepository->create($documentData);
    }
    
    /**
     * Create a share link for a document
     */
    public function createShareLink(Document $document, ?int $sharedWithId = null, ?int $expiresInDays = 7): string
    {
        $share = $document->shares()->create([
            'shared_by' => auth()->id(),
            'shared_with' => $sharedWithId,
            'share_token' => Str::random(64),
            'expires_at' => $expiresInDays ? Carbon::now()->addDays($expiresInDays) : null,
        ]);
        
        return route('documents.shared', ['token' => $share->share_token]);
    }
    
    /**
     * Get document by share token
     */
    public function getDocumentByShareToken(string $token): ?Document
    {
        $share = \App\Models\DocumentShare::where('share_token', $token)
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
        
        if (!$share) {
            return null;
        }
        
        // Record download
        $share->downloads++;
        $share->last_downloaded_at = now();
        $share->save();
        
        return $share->document;
    }
    
    /**
     * Delete document
     */
    public function deleteDocument(Document $document): bool
    {
        // Delete physical file
        \Storage::disk('tenant')->delete($document->file_path);
        
        // Delete document record
        return $this->documentRepository->delete($document);
    }
}
```

## Reservation System Architecture

### Data Model

```sql
CREATE TABLE reservations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED NOT NULL,
    deal_id BIGINT UNSIGNED NULL,
    agent_id BIGINT UNSIGNED NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    reservation_date TIMESTAMP NOT NULL,
    expiry_date TIMESTAMP NOT NULL,
    deposit_amount DECIMAL(12,2) NULL,
    deposit_paid_at TIMESTAMP NULL,
    notes TEXT NULL,
    custom_fields JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (lot_id) REFERENCES lots(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE SET NULL,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE reservation_documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    reservation_id BIGINT UNSIGNED NOT NULL,
    document_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
);
```

### Reservation Service

```php
namespace App\Services\Reservation;

use App\Models\Reservation;
use App\Models\Lot;
use App\Models\Contact;
use App\Repositories\ReservationRepository;
use App\Repositories\LotRepository;
use App\Events\ReservationCreated;
use App\Events\ReservationConfirmed;
use App\Events\ReservationCancelled;
use Carbon\Carbon;

class ReservationService
{
    protected $reservationRepository;
    protected $lotRepository;
    
    public function __construct(
        ReservationRepository $reservationRepository,
        LotRepository $lotRepository
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->lotRepository = $lotRepository;
    }
    
    /**
     * Create a reservation
     */
    public function createReservation(Lot $lot, Contact $contact, array $data): Reservation
    {
        // Check if lot is available
        if ($lot->status !== 'available') {
            throw new \Exception("Property is not available for reservation");
        }
        
        // Create reservation
        $reservationData = [
            'lot_id' => $lot->id,
            'contact_id' => $contact->id,
            'deal_id' => $data['deal_id'] ?? null,
            'agent_id' => $data['agent_id'] ?? auth()->id(),
            'status' => 'pending',
            'reservation_date' => now(),
            'expiry_date' => isset($data['expiry_date']) ? 
                Carbon::parse($data['expiry_date']) : 
                now()->addDays(7),
            'deposit_amount' => $data['deposit_amount'] ?? null,
            'notes' => $data['notes'] ?? null,
            'custom_fields' => $data['custom_fields'] ?? null,
        ];
        
        $reservation = $this->reservationRepository->create($reservationData);
        
        // Update lot status
        $this->lotRepository->update($lot, ['status' => 'reserved']);
        
        // Trigger event
        event(new ReservationCreated($reservation));
        
        return $reservation;
    }
    
    /**
     * Confirm reservation
     */
    public function confirmReservation(Reservation $reservation, array $data = []): Reservation
    {
        // Update reservation
        $updateData = [
            'status' => 'confirmed',
        ];
        
        if (isset($data['deposit_paid_at'])) {
            $updateData['deposit_paid_at'] = Carbon::parse($data['deposit_paid_at']);
        } else if (isset($data['deposit_paid']) && $data['deposit_paid']) {
            $updateData['deposit_paid_at'] = now();
        }
        
        if (isset($data['notes'])) {
            $updateData['notes'] = $reservation->notes . "\n\n" . $data['notes'];
        }
        
        $reservation = $this->reservationRepository->update($reservation, $updateData);
        
        // Trigger event
        event(new ReservationConfirmed($reservation));
        
        return $reservation;
    }
    
    /**
     * Cancel reservation
     */
    public function cancelReservation(Reservation $reservation, array $data = []): Reservation
    {
        // Update reservation
        $updateData = [
            'status' => 'cancelled',
        ];
        
        if (isset($data['notes'])) {
            $updateData['notes'] = $reservation->notes . "\n\n" . "Cancellation: " . $data['notes'];
        }
        
        $reservation = $this->reservationRepository->update($reservation, $updateData);
        
        // Update lot status back to available
        $this->lotRepository->update($reservation->lot, ['status' => 'available']);
        
        // Trigger event
        event(new ReservationCancelled($reservation));
        
        return $reservation;
    }
    
    /**
     * Check for expired reservations
     */
    public function processExpiredReservations(): int
    {
        $expiredReservations = Reservation::where('status', 'pending')
            ->where('expiry_date', '<', now())
            ->get();
        
        foreach ($expiredReservations as $reservation) {
            $this->cancelReservation($reservation, [
                'notes' => 'Automatically cancelled due to expiration'
            ]);
        }
        
        return $expiredReservations->count();
    }
    
    /**
     * Attach document to reservation
     */
    public function attachDocument(Reservation $reservation, int $documentId, string $documentType): void
    {
        $reservation->documents()->create([
            'document_id' => $documentId,
            'document_type' => $documentType
        ]);
    }
}
```

## Event-Driven Architecture

The Core CRM features utilize an event-driven architecture to ensure loose coupling between components and to enable extensibility:

```php
namespace App\Events;

use App\Models\Deal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class DealStageChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deal;
    public $oldStageId;
    public $newStageId;

    public function __construct(Deal $deal, int $oldStageId, int $newStageId)
    {
        $this->deal = $deal;
        $this->oldStageId = $oldStageId;
        $this->newStageId = $newStageId;
    }
}
```

Event listeners handle triggered events to perform side effects:

```php
namespace App\Listeners;

use App\Events\DealStageChanged;
use App\Services\Task\TaskService;

class CreateDealStageTasks
{
    protected $taskService;
    
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }
    
    public function handle(DealStageChanged $event)
    {
        $deal = $event->deal;
        $stage = $deal->stage;
        
        // Check if the stage has any predefined tasks
        $stageTasks = $stage->tasks ?? [];
        
        foreach ($stageTasks as $stageTask) {
            $this->taskService->createTask([
                'title' => $stageTask['title'],
                'description' => $stageTask['description'] ?? null,
                'due_date' => now()->addDays($stageTask['due_days'] ?? 3),
                'priority' => $stageTask['priority'] ?? 'medium',
                'assigned_to' => $deal->assigned_to,
                'related_type' => 'deal',
                'related_id' => $deal->id
            ]);
        }
    }
}
```

## API Integration

The Core CRM components expose RESTful APIs for seamless integration:

```php
// routes/api.php
Route::middleware(['auth:api', 'tenant'])->prefix('api/v1')->group(function () {
    // Contacts
    Route::apiResource('contacts', 'Api\ContactController');
    Route::post('contacts/{contact}/interactions', 'Api\ContactController@recordInteraction');
    
    // Deals
    Route::apiResource('deals', 'Api\DealController');
    Route::post('deals/{deal}/stage', 'Api\DealController@changeStage');
    Route::post('deals/{deal}/close-won', 'Api\DealController@closeWon');
    Route::post('deals/{deal}/close-lost', 'Api\DealController@closeLost');
    
    // Tasks
    Route::apiResource('tasks', 'Api\TaskController');
    Route::post('tasks/{task}/complete', 'Api\TaskController@complete');
    
    // Documents
    Route::apiResource('documents', 'Api\DocumentController');
    Route::post('documents/{document}/share', 'Api\DocumentController@share');
    
    // Reservations
    Route::apiResource('reservations', 'Api\ReservationController');
    Route::post('reservations/{reservation}/confirm', 'Api\ReservationController@confirm');
    Route::post('reservations/{reservation}/cancel', 'Api\ReservationController@cancel');
});
```

## Implementation Strategy

### Phase 1: Core Contact & Deal Management

1. **Contact Management**
   - Contact CRUD functionality
   - Interaction tracking
   - Contact tagging and categorization

2. **Deal Pipeline**
   - Pipeline and stage management
   - Deal creation and tracking
   - Stage transitions

### Phase 2: Task & Document Management

3. **Task System**
   - Task assignment
   - Due date handling
   - Reminders and notifications

4. **Document Management**
   - Document upload and storage
   - Document categorization
   - Sharing and permissions

### Phase 3: Reservations & Automation

5. **Reservation System**
   - Property reservation process
   - Deposit tracking
   - Reservation lifecycles

6. **Workflow Automation**
   - Event-based task creation
   - Automated notifications
   - Process automation

## Conclusion

The Core CRM Features Architecture in Fusion CRM V4 provides:

1. **Comprehensive Client Management**: Full contact lifecycle from lead to client
2. **Flexible Sales Pipeline**: Customizable stages and deal tracking
3. **Efficient Task Management**: Structured follow-up and activity tracking
4. **Secure Document Management**: Centralized document storage and sharing
5. **Streamlined Reservations**: Simplified property reservation process

This architecture ensures that Fusion CRM V4 delivers a robust, scalable foundation for real estate professionals to manage their client relationships and sales processes effectively. 