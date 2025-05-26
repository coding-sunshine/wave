# Filament PHP Integration with Spatie Packages - Part 3

## Laravel-Model-States Integration

### Creating Custom State Field Components

Implement a State Select component for Filament:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\State;

class ModelStateSelect extends Select
{
    protected ?string $stateMachine = null;
    protected ?string $stateAttribute = 'state';
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->afterStateHydrated(function (Select $component, ?Model $record, $state) {
            if (!$record) return;
            
            $currentState = $record->{$this->getStateAttribute()};
            
            if ($currentState instanceof State) {
                $component->state(get_class($currentState));
            }
        });
        
        $this->getOptionsUsing(function (ModelStateSelect $component, ?Model $record) {
            if (!$record) return [];
            
            $currentState = $record->{$this->getStateAttribute()};
            
            if (!$currentState instanceof State) return [];
            
            $stateClass = get_class($currentState);
            $possibleTransitions = [];
            
            // Add current state as an option
            $possibleTransitions[get_class($currentState)] = $currentState->getLabel() ?? class_basename($stateClass);
            
            // Get possible transitions
            foreach ($stateClass::getAvailableTransitions($currentState) as $transitionState) {
                $transitionStateClass = get_class($transitionState);
                $possibleTransitions[$transitionStateClass] = $transitionState->getLabel() ?? class_basename($transitionStateClass);
            }
            
            return $possibleTransitions;
        });
        
        $this->afterStateUpdated(function (Select $component, ?Model $record, $state) {
            if (!$record || !$state) return;
            
            // Don't do anything if the state hasn't changed
            $currentState = $record->{$this->getStateAttribute()};
            if ($currentState instanceof State && get_class($currentState) === $state) return;
            
            // Store the new state class to be used during save
            $record->setStateClass($this->getStateAttribute(), $state);
        });
        
        $this->dehydrated(true);
    }
    
    public function stateMachine(string $stateMachine): static
    {
        $this->stateMachine = $stateMachine;
        
        return $this;
    }
    
    public function stateAttribute(string $attribute): static
    {
        $this->stateAttribute = $attribute;
        
        return $this;
    }
    
    public function getStateMachine(): ?string
    {
        return $this->stateMachine;
    }
    
    public function getStateAttribute(): string
    {
        return $this->stateAttribute;
    }
}
```

### Using the State Select Component

```php
use App\Filament\Forms\Components\ModelStateSelect;

// In a Filament resource's form() method
public static function form(Form $form): Form
{
    return $form
        ->schema([
            // Other fields
            ModelStateSelect::make('state')
                ->stateAttribute('state')
                ->label('Deal Status'),
        ]);
}
```

### State Transition Listener

Create a listener to handle state transitions in a more controlled manner:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources\DealResource\Actions;

use App\Models\Deal;
use App\States\Deal\NewToQualifiedTransition;
use App\States\Deal\QualifiedToProposalTransition;
use App\States\Deal\ProposalToWonTransition;
use App\States\Deal\ProposalToLostTransition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class TransitionStateBulkAction extends BulkAction
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->label('Change Status');
        $this->icon('heroicon-o-arrow-path');
        $this->form(fn (Form $form): Form => $form
            ->schema([
                Forms\Components\Select::make('transition')
                    ->label('New Status')
                    ->options([
                        'qualify' => 'Qualify',
                        'proposal' => 'Send Proposal',
                        'win' => 'Won',
                        'lose' => 'Lost',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Transition Notes')
                    ->rows(3),
            ])
        );
        
        $this->action(function (Collection $records, array $data): void {
            $transitioned = 0;
            $failed = 0;
            
            foreach ($records as $record) {
                if (!$record instanceof Deal) continue;
                
                try {
                    $currentState = $record->state;
                    $stateClass = get_class($currentState);
                    
                    switch ($data['transition']) {
                        case 'qualify':
                            if ($record->state->canTransitionTo(Qualified::class)) {
                                $record->state->transitionTo(Qualified::class, [
                                    'notes' => $data['notes'] ?? null,
                                ]);
                                $transitioned++;
                            } else {
                                $failed++;
                            }
                            break;
                        case 'proposal':
                            if ($record->state->canTransitionTo(Proposal::class)) {
                                $record->state->transitionTo(Proposal::class, [
                                    'notes' => $data['notes'] ?? null,
                                ]);
                                $transitioned++;
                            } else {
                                $failed++;
                            }
                            break;
                        case 'win':
                            if ($record->state->canTransitionTo(Won::class)) {
                                $record->state->transitionTo(Won::class, [
                                    'notes' => $data['notes'] ?? null,
                                ]);
                                $transitioned++;
                            } else {
                                $failed++;
                            }
                            break;
                        case 'lose':
                            if ($record->state->canTransitionTo(Lost::class)) {
                                $record->state->transitionTo(Lost::class, [
                                    'notes' => $data['notes'] ?? null,
                                ]);
                                $transitioned++;
                            } else {
                                $failed++;
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    $failed++;
                }
            }
            
            Notification::make()
                ->title("Status changes completed")
                ->body("{$transitioned} deals updated successfully. {$failed} deals could not be updated.")
                ->success()
                ->send();
        });
    }
}
```

## Laravel-Data Integration

### Creating Form Components for Data Objects

Create a base trait for handling Data objects in Filament:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Forms\Concerns;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

trait HandlesDataObjects
{
    protected function fillFormWithData(object $form, ?Model $record, ?Data $data): void
    {
        if (!$data) {
            return;
        }
        
        // Get all the data properties and set them in the form
        foreach ($data->toArray() as $field => $value) {
            if (property_exists($form, 'data') && is_array($form->data)) {
                data_set($form->data, $field, $value);
            }
        }
    }
    
    protected function saveDataToModel(Model $record, Data $data): void
    {
        // Save the data object to the model
        foreach ($data->toArray() as $field => $value) {
            if ($field === 'team_id') {
                // Skip team_id as it's handled by the BelongsToTeam trait
                continue;
            }
            
            if ($record->isFillable($field)) {
                $record->{$field} = $value;
            }
        }
        
        $record->save();
    }
    
    protected function getDataFromForm(object $form, ?Model $record, string $dataClass): Data
    {
        // Create a new Data object from the form
        $dataArray = [];
        
        if (property_exists($form, 'data') && is_array($form->data)) {
            $dataArray = $form->data;
        }
        
        // Ensure team_id is set
        if (auth()->check() && !isset($dataArray['team_id'])) {
            $dataArray['team_id'] = auth()->user()->team_id;
        }
        
        // If we have a record ID, include it
        if ($record && $record->exists && !isset($dataArray['id'])) {
            $dataArray['id'] = $record->id;
        }
        
        return $dataClass::from($dataArray);
    }
}
```

### Using Data Objects in Filament

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadResource\Pages;

use App\Data\LeadData;
use App\Filament\Forms\Concerns\HandlesDataObjects;
use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    use HandlesDataObjects;
    
    protected static string $resource = LeadResource::class;
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert model to data object
        $lead = $this->getRecord();
        
        if ($lead instanceof Lead) {
            $leadData = LeadData::from($lead);
            $this->fillFormWithData($this, $lead, $leadData);
        }
        
        return $data;
    }
    
    protected function handleRecordUpdate(Lead $record, array $data): Lead
    {
        // Convert form data to data object
        $leadData = $this->getDataFromForm($this, $record, LeadData::class);
        
        // Save to model
        $this->saveDataToModel($record, $leadData);
        
        return $record->fresh();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
```

## Laravel-Dashboard Integration

### Create Custom Filament Dashboard Tiles

Create a base Filament Dashboard Tile:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

abstract class DashboardTileWidget extends Widget
{
    protected static ?int $sort = 2;
    
    protected static string $view = 'filament.widgets.dashboard-tile-widget';
    
    protected int $refreshIntervalInSeconds = 60;
    protected ?string $cachePrefix = null;
    protected ?array $cachedData = null;
    
    abstract protected function generateData(): array;
    abstract public function getTitle(): string;
    
    public function getCacheKey(): string
    {
        $prefix = $this->cachePrefix ?? static::class;
        
        // Add team_id for isolation
        $teamId = auth()->check() ? auth()->user()->team_id : 'global';
        
        return "team_{$teamId}_{$prefix}";
    }
    
    public function getData(): array
    {
        if ($this->cachedData !== null) {
            return $this->cachedData;
        }
        
        $cacheKey = $this->getCacheKey();
        
        if (Cache::has($cacheKey)) {
            $this->cachedData = Cache::get($cacheKey);
            return $this->cachedData;
        }
        
        $data = $this->generateData();
        
        Cache::put($cacheKey, $data, $this->refreshIntervalInSeconds);
        $this->cachedData = $data;
        
        return $data;
    }
    
    public function refreshData(): void
    {
        $data = $this->generateData();
        
        Cache::put($this->getCacheKey(), $data, $this->refreshIntervalInSeconds);
        $this->cachedData = $data;
    }
    
    public function mount(): void
    {
        $this->getData();
    }
    
    public function getRefreshIntervalInSeconds(): int
    {
        return $this->refreshIntervalInSeconds;
    }
}
```

Create the view for the dashboard tile:

```blade
{{-- resources/views/filament/widgets/dashboard-tile-widget.blade.php --}}
@php
    $heading = $this->getTitle();
    $data = $this->getData();
@endphp

<x-filament::section>
    <x-slot name="heading">
        {{ $heading }}
    </x-slot>
    
    <div class="flex flex-col space-y-4">
        @foreach ($data as $key => $value)
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $key)) }}</span>
                
                @if (is_numeric($value))
                    <span class="text-lg font-bold">{{ number_format($value) }}</span>
                @else
                    <span class="text-lg">{{ $value }}</span>
                @endif
            </div>
            
            @if (!$loop->last)
                <hr class="border-gray-200 dark:border-gray-700">
            @endif
        @endforeach
    </div>
    
    <x-slot name="footer">
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Last updated: {{ now()->format('M j, Y H:i') }}
            <button wire:click="refreshData" class="text-primary-600 hover:text-primary-500 ml-2">
                Refresh
            </button>
        </div>
    </x-slot>
</x-filament::section>
```

### Create a Sample Task Statistics Tile

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Task;

class TaskStatisticsWidget extends DashboardTileWidget
{
    protected int $refreshIntervalInSeconds = 300;
    protected ?string $cachePrefix = 'task_statistics';
    protected static ?int $sort = 3;

    public function getTitle(): string
    {
        return 'Task Statistics';
    }
    
    protected function generateData(): array
    {
        $query = Task::query()
            ->where('team_id', auth()->user()->team_id);
        
        $tasksByStatus = $query->clone()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        $statuses = [
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'on_hold' => 'On Hold',
        ];
        
        $formattedData = [];
        
        foreach ($statuses as $key => $label) {
            $formattedData[$label] = $tasksByStatus[$key] ?? 0;
        }
        
        // Add total
        $formattedData['Total Tasks'] = array_sum($tasksByStatus);
        
        // Add tasks due soon
        $formattedData['Due Today'] = $query->clone()
            ->whereDate('due_date', today())
            ->count();
        
        $formattedData['Due This Week'] = $query->clone()
            ->whereBetween('due_date', [today(), today()->endOfWeek()])
            ->count();
        
        return $formattedData;
    }
}
```

## Best Practices

### Team Isolation in Filament

1. **Global Scope for Queries**: Ensure all query builders respect team boundaries:

```php
// In all Resource classes
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->where('team_id', auth()->user()->team_id);
}
```

2. **Automatic Team ID Assignment**: Set default values in Filament forms:

```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            // Other fields
            Hidden::make('team_id')
                ->default(fn () => auth()->user()->team_id),
        ]);
}
```

3. **Middleware Protection**: Apply team-aware middleware to all Filament routes:

```php
// In your panel configuration
->authMiddleware([
    Authenticate::class,
    EnsureTeamAccessMiddleware::class,
])
```

4. **Resource Policy Checks**: Implement strict team-checking policies:

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Property;
use Illuminate\Auth\Access\HandlesAuthorization;

class PropertyPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Property $property): bool
    {
        // Check for same team
        return $user->team_id === $property->team_id;
    }
    
    // Similar checks for update, delete, etc.
}
```

5. **Relation Managers**: Ensure relation managers also apply team scopes:

```php
class DealsRelationManager extends RelationManager
{
    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->where('team_id', auth()->user()->team_id);
    }
}
```

### Performance Considerations

1. **Eager Loading**: Always use eager loading for relation display to reduce N+1 issues:

```php
public static function getRelations(): array
{
    return [
        RelationManagers\DealsRelationManager::make()
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['state', 'client', 'assignedTo'])),
    ];
}
```

2. **Cache Dashboard Tiles**: Use the caching approach demonstrated with dashboard tiles.

3. **Paginate and Filter Large Lists**: Avoid loading all records at once:

```php
public static function table(Table $table): Table
{
    return $table
        ->defaultPaginationPageOption(25)
        ->defaultSort('created_at', 'desc')
        // Rest of configuration
}
```

This guide provides a comprehensive approach to integrating Filament PHP with Spatie packages while maintaining proper team isolation in a single-database multi-tenancy architecture. Each component is designed to respect team boundaries and follow PSR-12 coding standards.
