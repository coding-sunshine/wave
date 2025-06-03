# Fusion CRM V4 - Automation & Workflows Architecture

This document outlines the architecture for the Automation and Workflows system in Fusion CRM V4, providing a comprehensive framework for automating business processes and task management.

## Overview

Fusion CRM V4 implements a powerful workflow automation system with these key capabilities:

1. **Event-Driven Automation**: Trigger-based workflows from system events
2. **Task Automation**: Intelligent task creation, assignment, and follow-up
3. **Conditional Logic**: If-this-then-that workflow rules
4. **Multi-step Sequences**: Complex multi-stage workflows
5. **Integration Triggers**: Automation from external system events

## Core Components

### 1. Workflow Engine

The central service for defining and executing automated workflows:

```php
namespace App\Services\Workflow;

use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowStep;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Log;

class WorkflowEngine
{
    protected $tenantManager;
    protected $actionRegistry;
    
    public function __construct(TenantManager $tenantManager, WorkflowActionRegistry $actionRegistry)
    {
        $this->tenantManager = $tenantManager;
        $this->actionRegistry = $actionRegistry;
    }
    
    /**
     * Create a new workflow
     */
    public function createWorkflow(array $data): Workflow
    {
        $tenant = $this->tenantManager->getTenant();
        
        $workflow = new Workflow();
        $workflow->tenant_id = $tenant->id;
        $workflow->name = $data['name'];
        $workflow->description = $data['description'] ?? null;
        $workflow->trigger_type = $data['trigger_type'];
        $workflow->trigger_config = $data['trigger_config'] ?? [];
        $workflow->is_active = $data['is_active'] ?? false;
        $workflow->created_by = auth()->id();
        $workflow->save();
        
        // Create workflow steps
        if (!empty($data['steps'])) {
            foreach ($data['steps'] as $order => $stepData) {
                $this->createWorkflowStep($workflow, $stepData, $order + 1);
            }
        }
        
        return $workflow;
    }
    
    /**
     * Create a workflow step
     */
    public function createWorkflowStep(Workflow $workflow, array $data, int $order): WorkflowStep
    {
        // Validate action type
        if (!$this->actionRegistry->hasAction($data['action_type'])) {
            throw new \Exception("Invalid action type: {$data['action_type']}");
        }
        
        $step = new WorkflowStep();
        $step->workflow_id = $workflow->id;
        $step->name = $data['name'] ?? "Step {$order}";
        $step->action_type = $data['action_type'];
        $step->action_config = $data['action_config'] ?? [];
        $step->condition_type = $data['condition_type'] ?? 'always';
        $step->condition_config = $data['condition_config'] ?? [];
        $step->order = $order;
        $step->save();
        
        return $step;
    }
    
    /**
     * Update a workflow
     */
    public function updateWorkflow(Workflow $workflow, array $data): Workflow
    {
        // Update basic workflow data
        if (isset($data['name'])) {
            $workflow->name = $data['name'];
        }
        
        if (isset($data['description'])) {
            $workflow->description = $data['description'];
        }
        
        if (isset($data['is_active'])) {
            $workflow->is_active = $data['is_active'];
        }
        
        if (isset($data['trigger_type'])) {
            $workflow->trigger_type = $data['trigger_type'];
        }
        
        if (isset($data['trigger_config'])) {
            $workflow->trigger_config = $data['trigger_config'];
        }
        
        $workflow->save();
        
        // Update steps if provided
        if (isset($data['steps'])) {
            // Delete existing steps
            $workflow->steps()->delete();
            
            // Create new steps
            foreach ($data['steps'] as $order => $stepData) {
                $this->createWorkflowStep($workflow, $stepData, $order + 1);
            }
        }
        
        return $workflow;
    }
    
    /**
     * Delete a workflow
     */
    public function deleteWorkflow(Workflow $workflow): bool
    {
        // Delete all steps
        $workflow->steps()->delete();
        
        // Delete all executions
        $workflow->executions()->delete();
        
        // Delete workflow
        return $workflow->delete();
    }
    
    /**
     * Execute a workflow for a trigger event
     */
    public function executeTrigger(string $triggerType, array $triggerData): array
    {
        $tenant = $this->tenantManager->getTenant();
        
        // Find matching workflows
        $workflows = Workflow::where('tenant_id', $tenant->id)
            ->where('trigger_type', $triggerType)
            ->where('is_active', true)
            ->get();
        
        $results = [];
        
        foreach ($workflows as $workflow) {
            try {
                // Check if workflow trigger conditions match
                if (!$this->matchesTriggerConditions($workflow, $triggerData)) {
                    continue;
                }
                
                // Start workflow execution
                $execution = $this->startExecution($workflow, $triggerData);
                $results[] = $execution;
                
                // Process workflow steps
                $this->processWorkflowSteps($execution);
            } catch (\Exception $e) {
                Log::error('Workflow execution failed', [
                    'workflow_id' => $workflow->id,
                    'trigger_type' => $triggerType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * Check if trigger data matches workflow conditions
     */
    protected function matchesTriggerConditions(Workflow $workflow, array $triggerData): bool
    {
        $config = $workflow->trigger_config;
        
        // If no conditions, always match
        if (empty($config['conditions'])) {
            return true;
        }
        
        foreach ($config['conditions'] as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;
            
            if ($field === null) {
                continue;
            }
            
            // Get actual value from trigger data
            $actualValue = data_get($triggerData, $field);
            
            // Compare based on operator
            $matches = match ($operator) {
                'equals' => $actualValue == $value,
                'not_equals' => $actualValue != $value,
                'contains' => is_string($actualValue) && str_contains($actualValue, $value),
                'starts_with' => is_string($actualValue) && str_starts_with($actualValue, $value),
                'ends_with' => is_string($actualValue) && str_ends_with($actualValue, $value),
                'greater_than' => $actualValue > $value,
                'less_than' => $actualValue < $value,
                'in' => in_array($actualValue, (array) $value),
                'not_in' => !in_array($actualValue, (array) $value),
                'exists' => isset($triggerData[$field]),
                'not_exists' => !isset($triggerData[$field]),
                default => false,
            };
            
            // For AND logic, return false immediately if any condition fails
            if (!$matches && ($config['logic'] ?? 'and') === 'and') {
                return false;
            }
            
            // For OR logic, return true immediately if any condition passes
            if ($matches && ($config['logic'] ?? 'and') === 'or') {
                return true;
            }
        }
        
        // If we got here with AND logic, all conditions passed
        // If we got here with OR logic, no conditions passed
        return ($config['logic'] ?? 'and') === 'and';
    }
    
    /**
     * Start workflow execution
     */
    protected function startExecution(Workflow $workflow, array $triggerData): WorkflowExecution
    {
        $execution = new WorkflowExecution();
        $execution->workflow_id = $workflow->id;
        $execution->trigger_data = $triggerData;
        $execution->status = 'running';
        $execution->result_data = [];
        $execution->started_at = now();
        $execution->save();
        
        return $execution;
    }
    
    /**
     * Process workflow steps
     */
    protected function processWorkflowSteps(WorkflowExecution $execution): void
    {
        $workflow = $execution->workflow;
        $steps = $workflow->steps()->orderBy('order')->get();
        $context = [
            'trigger' => $execution->trigger_data,
            'results' => [],
        ];
        
        $allStepsSuccessful = true;
        
        foreach ($steps as $step) {
            try {
                // Check if step condition is met
                if (!$this->evaluateStepCondition($step, $context)) {
                    // Log skipped step
                    $execution->addLog($step->id, 'skipped', 'Step condition not met');
                    continue;
                }
                
                // Log step start
                $execution->addLog($step->id, 'running', 'Executing step');
                
                // Execute step action
                $actionHandler = $this->actionRegistry->getAction($step->action_type);
                $result = $actionHandler->execute($step->action_config, $context);
                
                // Store result
                $context['results'][$step->id] = $result;
                
                // Log step success
                $execution->addLog($step->id, 'completed', 'Step completed successfully', $result);
            } catch (\Exception $e) {
                // Log step failure
                $execution->addLog($step->id, 'failed', $e->getMessage());
                $allStepsSuccessful = false;
                
                // Break execution if step is configured to break on failure
                if ($step->break_on_failure) {
                    break;
                }
            }
        }
        
        // Update execution status
        $execution->status = $allStepsSuccessful ? 'completed' : 'failed';
        $execution->result_data = $context['results'];
        $execution->completed_at = now();
        $execution->save();
    }
    
    /**
     * Evaluate step condition
     */
    protected function evaluateStepCondition(WorkflowStep $step, array $context): bool
    {
        $conditionType = $step->condition_type;
        $config = $step->condition_config;
        
        return match ($conditionType) {
            'always' => true,
            'expression' => $this->evaluateExpression($config['expression'] ?? '', $context),
            'previous_step_success' => isset($context['results'][$step->order - 1]) && 
                !isset($context['results'][$step->order - 1]['error']),
            'data_condition' => $this->evaluateDataCondition($config, $context),
            default => false,
        };
    }
    
    /**
     * Evaluate expression
     */
    protected function evaluateExpression(string $expression, array $context): bool
    {
        // Simple expression evaluator (in real implementation, use a proper expression parser)
        try {
            // Replace context variables in expression
            $parsedExpression = $this->parseContextVariables($expression, $context);
            
            // SAFETY WARNING: eval is used here for simplicity but should be replaced with a secure expression parser
            $result = eval("return {$parsedExpression};");
            return (bool) $result;
        } catch (\Exception $e) {
            Log::error('Expression evaluation failed', [
                'expression' => $expression,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Evaluate data condition
     */
    protected function evaluateDataCondition(array $config, array $context): bool
    {
        $field = $config['field'] ?? '';
        $operator = $config['operator'] ?? 'equals';
        $value = $config['value'] ?? null;
        
        // Get actual value from context
        $actualValue = $this->getValueFromContext($field, $context);
        
        // Compare based on operator
        return match ($operator) {
            'equals' => $actualValue == $value,
            'not_equals' => $actualValue != $value,
            'contains' => is_string($actualValue) && str_contains($actualValue, $value),
            'starts_with' => is_string($actualValue) && str_starts_with($actualValue, $value),
            'ends_with' => is_string($actualValue) && str_ends_with($actualValue, $value),
            'greater_than' => $actualValue > $value,
            'less_than' => $actualValue < $value,
            'in' => in_array($actualValue, (array) $value),
            'not_in' => !in_array($actualValue, (array) $value),
            'exists' => $actualValue !== null,
            'not_exists' => $actualValue === null,
            default => false,
        };
    }
    
    /**
     * Parse context variables in a string
     */
    protected function parseContextVariables(string $string, array $context): string
    {
        // Replace {{variable}} with actual values
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($context) {
            $path = trim($matches[1]);
            return $this->getValueFromContext($path, $context);
        }, $string);
    }
    
    /**
     * Get value from context using dot notation
     */
    protected function getValueFromContext(string $path, array $context)
    {
        return data_get($context, $path);
    }
    
    /**
     * Get all workflows for current tenant
     */
    public function getAllWorkflows(): array
    {
        $tenant = $this->tenantManager->getTenant();
        
        return Workflow::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get()
            ->toArray();
    }
    
    /**
     * Get workflow executions
     */
    public function getWorkflowExecutions(int $workflowId, int $limit = 100): array
    {
        return WorkflowExecution::where('workflow_id', $workflowId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
```

### 2. Action Registry

Registry for all available workflow actions:

```php
namespace App\Services\Workflow;

class WorkflowActionRegistry
{
    protected $actions = [];
    
    /**
     * Register an action handler
     */
    public function registerAction(string $type, WorkflowActionInterface $handler): void
    {
        $this->actions[$type] = $handler;
    }
    
    /**
     * Check if an action is registered
     */
    public function hasAction(string $type): bool
    {
        return isset($this->actions[$type]);
    }
    
    /**
     * Get an action handler
     */
    public function getAction(string $type): WorkflowActionInterface
    {
        if (!$this->hasAction($type)) {
            throw new \Exception("Action type not registered: {$type}");
        }
        
        return $this->actions[$type];
    }
    
    /**
     * Get all registered actions
     */
    public function getAllActions(): array
    {
        $result = [];
        
        foreach ($this->actions as $type => $handler) {
            $result[$type] = [
                'type' => $type,
                'name' => $handler->getName(),
                'description' => $handler->getDescription(),
                'config_schema' => $handler->getConfigSchema(),
            ];
        }
        
        return $result;
    }
}
```

### 3. Action Interface

Common interface for all workflow actions:

```php
namespace App\Services\Workflow;

interface WorkflowActionInterface
{
    /**
     * Get action name
     */
    public function getName(): string;
    
    /**
     * Get action description
     */
    public function getDescription(): string;
    
    /**
     * Get config schema
     */
    public function getConfigSchema(): array;
    
    /**
     * Execute the action
     */
    public function execute(array $config, array $context): array;
}
```

### 4. Task Action Handler

Example of a task creation action:

```php
namespace App\Services\Workflow\Actions;

use App\Models\Task;
use App\Services\Task\TaskService;
use App\Services\Workflow\WorkflowActionInterface;

class CreateTaskAction implements WorkflowActionInterface
{
    protected $taskService;
    
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }
    
    /**
     * Get action name
     */
    public function getName(): string
    {
        return 'Create Task';
    }
    
    /**
     * Get action description
     */
    public function getDescription(): string
    {
        return 'Creates a new task with the specified details';
    }
    
    /**
     * Get config schema
     */
    public function getConfigSchema(): array
    {
        return [
            'title' => [
                'type' => 'string',
                'label' => 'Task Title',
                'required' => true,
            ],
            'description' => [
                'type' => 'text',
                'label' => 'Description',
                'required' => false,
            ],
            'due_date' => [
                'type' => 'date',
                'label' => 'Due Date',
                'required' => false,
                'supports_variables' => true,
            ],
            'assigned_to' => [
                'type' => 'user',
                'label' => 'Assign To',
                'required' => false,
                'supports_variables' => true,
            ],
            'priority' => [
                'type' => 'select',
                'label' => 'Priority',
                'options' => [
                    'low' => 'Low',
                    'normal' => 'Normal',
                    'high' => 'High',
                    'urgent' => 'Urgent',
                ],
                'default' => 'normal',
                'required' => false,
            ],
            'related_type' => [
                'type' => 'string',
                'label' => 'Related Type',
                'required' => false,
                'supports_variables' => true,
            ],
            'related_id' => [
                'type' => 'number',
                'label' => 'Related ID',
                'required' => false,
                'supports_variables' => true,
            ],
        ];
    }
    
    /**
     * Execute the action
     */
    public function execute(array $config, array $context): array
    {
        // Parse dynamic fields
        $title = $this->parseVariable($config['title'] ?? '', $context);
        $description = $this->parseVariable($config['description'] ?? '', $context);
        $dueDate = $this->parseVariable($config['due_date'] ?? null, $context);
        $assignedTo = $this->parseVariable($config['assigned_to'] ?? null, $context);
        $relatedType = $this->parseVariable($config['related_type'] ?? null, $context);
        $relatedId = $this->parseVariable($config['related_id'] ?? null, $context);
        
        // Create task data
        $taskData = [
            'title' => $title,
            'description' => $description,
            'priority' => $config['priority'] ?? 'normal',
            'status' => 'pending',
        ];
        
        // Add optional fields if present
        if ($dueDate) {
            $taskData['due_date'] = $dueDate;
        }
        
        if ($assignedTo) {
            $taskData['assigned_to'] = $assignedTo;
        }
        
        if ($relatedType && $relatedId) {
            $taskData['related_type'] = $relatedType;
            $taskData['related_id'] = $relatedId;
        }
        
        // Create task
        $task = $this->taskService->createTask($taskData);
        
        return [
            'success' => true,
            'task_id' => $task->id,
            'message' => "Task '{$title}' created successfully",
        ];
    }
    
    /**
     * Parse variable from context
     */
    protected function parseVariable(string $value, array $context)
    {
        if (empty($value)) {
            return $value;
        }
        
        // Replace {{variable}} with actual values
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($context) {
            $path = trim($matches[1]);
            return data_get($context, $path, '');
        }, $value);
    }
}
```

## Database Schema

```sql
-- Workflows
CREATE TABLE workflows (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    trigger_type VARCHAR(100) NOT NULL,
    trigger_config JSON NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT FALSE,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Workflow steps
CREATE TABLE workflow_steps (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    workflow_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    action_config JSON NOT NULL,
    condition_type VARCHAR(50) NOT NULL DEFAULT 'always',
    condition_config JSON NULL,
    order INT NOT NULL,
    break_on_failure BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE
);

-- Workflow executions
CREATE TABLE workflow_executions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    workflow_id BIGINT UNSIGNED NOT NULL,
    trigger_data JSON NOT NULL,
    status VARCHAR(50) NOT NULL,
    result_data JSON NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE
);

-- Workflow execution logs
CREATE TABLE workflow_execution_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    execution_id BIGINT UNSIGNED NOT NULL,
    step_id BIGINT UNSIGNED NULL,
    status VARCHAR(50) NOT NULL,
    message TEXT NULL,
    data JSON NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (execution_id) REFERENCES workflow_executions(id) ON DELETE CASCADE,
    FOREIGN KEY (step_id) REFERENCES workflow_steps(id) ON DELETE SET NULL
);
```

## Available Workflow Triggers

The system supports various trigger types:

1. **System Events**
   - `contact.created` - New contact created
   - `contact.updated` - Contact information updated
   - `deal.created` - New deal created
   - `deal.stage_changed` - Deal moved to new stage
   - `task.created` - New task created
   - `task.completed` - Task marked as complete
   - `property.created` - New property listed
   - `property.status_changed` - Property status changed

2. **Scheduled Triggers**
   - `schedule.daily` - Run once per day
   - `schedule.weekly` - Run once per week
   - `schedule.monthly` - Run once per month
   - `schedule.custom` - Custom CRON pattern

3. **External Triggers**
   - `api.webhook` - External API webhook received
   - `form.submitted` - Website form submitted
   - `integration.event` - Event from integration (Zapier, etc.)

## Available Workflow Actions

The system provides various action types:

1. **Task Management**
   - `task.create` - Create a new task
   - `task.update` - Update an existing task
   - `task.complete` - Mark a task as complete

2. **Communication**
   - `email.send` - Send an email
   - `sms.send` - Send an SMS message
   - `notification.send` - Send an in-app notification

3. **CRM Operations**
   - `contact.create` - Create a new contact
   - `contact.update` - Update a contact
   - `deal.create` - Create a new deal
   - `deal.update` - Update a deal
   - `deal.change_stage` - Move a deal to a new stage

4. **Data Operations**
   - `data.transform` - Transform data using a template
   - `data.validate` - Validate data against rules
   - `data.store` - Store data in temporary storage

5. **Integrations**
   - `integration.execute` - Execute an integration action
   - `xero.create_invoice` - Create an invoice in Xero
   - `api.request` - Make an external API request

## Implementation Strategy

### Phase 1: Core Workflow Engine

1. **Workflow Framework**
   - Basic workflow model and execution
   - Simple trigger types (system events)
   - Core action types

2. **Task Automation**
   - Task creation actions
   - Follow-up scheduling
   - Due date management

### Phase 2: Advanced Automation

3. **Communication Workflows**
   - Email and SMS actions
   - Template support
   - Scheduling logic

4. **Conditional Logic**
   - Advanced condition evaluation
   - Branching workflows
   - Error handling

### Phase 3: Integration & Extension

5. **Integration Actions**
   - External system actions
   - Webhook triggers
   - API connectors

6. **AI-Enhanced Workflows**
   - AI-suggested workflows
   - Smart condition evaluation
   - Predictive task scheduling

## Security Considerations

1. **Data Access Control**
   - Workflows can only access data within tenant boundaries
   - Permission checks on action execution
   - Sensitive data handling

2. **Execution Limits**
   - Rate limiting for workflow execution
   - Maximum step count
   - Execution timeouts

3. **Validation & Sanitization**
   - Input validation for all actions
   - Output sanitization
   - Expression sandboxing

## Conclusion

The Automation & Workflows architecture in Fusion CRM V4 provides:

1. **Efficiency**: Automate repetitive tasks and follow-ups
2. **Consistency**: Ensure business processes are followed
3. **Scalability**: Handle complex workflows with minimal overhead
4. **Flexibility**: Customizable actions for specific business needs
5. **Intelligence**: AI-enhanced automation for smarter processes

This system enables real estate professionals to automate their business processes, increase productivity, and ensure consistent client communication and follow-up. 