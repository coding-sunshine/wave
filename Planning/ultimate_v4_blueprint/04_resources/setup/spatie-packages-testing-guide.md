# Testing Guide for Spatie Packages in Fusion CRM V4

This guide provides comprehensive testing approaches for all Spatie packages used in Fusion CRM V4, with a focus on ensuring proper integration with DevDojo Wave's team-based architecture.

## Table of Contents

1. [Testing Setup](#testing-setup)
2. [Testing Laravel-Permission](#testing-laravel-permission)
3. [Testing Laravel-MediaLibrary](#testing-laravel-medialibrary)
4. [Testing Laravel-Tags](#testing-laravel-tags)
5. [Testing Laravel-Comments](#testing-laravel-comments)
6. [Testing Laravel-Model-States](#testing-laravel-model-states)
7. [Testing Laravel-Data](#testing-laravel-data)
8. [Testing Laravel-Dashboard](#testing-laravel-dashboard)
9. [Testing Team Isolation](#testing-team-isolation)

## Testing Setup

### Prerequisites

- PEST PHP for running tests
- Laravel's built-in testing utilities
- Wave's team-based tenant architecture

### Base Test Case with Team Context

```php
<?php

namespace Tests;

use App\Models\Team;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;
    
    protected Team $team1;
    protected Team $team2;
    protected User $userInTeam1;
    protected User $userInTeam2;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create teams
        $this->team1 = Team::factory()->create(['name' => 'Team 1']);
        $this->team2 = Team::factory()->create(['name' => 'Team 2']);
        
        // Create users in different teams
        $this->userInTeam1 = User::factory()->create(['team_id' => $this->team1->id]);
        $this->userInTeam2 = User::factory()->create(['team_id' => $this->team2->id]);
        
        // Seed permissions (if needed for your tests)
        $this->seed(PermissionSeeder::class);
    }
    
    /**
     * Act as a user from a specific team.
     */
    protected function actingAsTeamMember(Team $team = null): User
    {
        $team = $team ?? $this->team1;
        $user = User::factory()->create(['team_id' => $team->id]);
        $this->actingAs($user);
        return $user;
    }
}
```

## Testing Laravel-Permission

### Test User Permissions and Roles

```php
<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use function Pest\Laravel\actingAs;

test('users can be assigned team-specific roles', function () {
    // Create teams
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    
    // Create users in different teams
    $user1 = User::factory()->create(['team_id' => $team1->id]);
    $user2 = User::factory()->create(['team_id' => $team2->id]);
    
    // Create roles for specific teams
    $role1 = Role::create([
        'name' => 'editor',
        'team_id' => $team1->id,
        'guard_name' => 'web'
    ]);
    $role2 = Role::create([
        'name' => 'editor',
        'team_id' => $team2->id,
        'guard_name' => 'web'
    ]);
    
    // Assign roles
    $user1->assignRole($role1);
    $user2->assignRole($role2);
    
    // Test role assignment within team context
    actingAs($user1);
    expect($user1->hasRole('editor'))->toBeTrue();
    
    // User has the same-named role, but in a different team
    actingAs($user2);
    expect($user2->hasRole('editor'))->toBeTrue();
    
    // Roles should be team-specific and not bleed across teams
    $permission = Permission::create([
        'name' => 'edit articles',
        'team_id' => $team1->id,
        'guard_name' => 'web'
    ]);
    
    $role1->givePermissionTo($permission);
    
    actingAs($user1);
    expect($user1->can('edit articles'))->toBeTrue();
    
    actingAs($user2);
    expect($user2->can('edit articles'))->toBeFalse();
});
```

## Testing Laravel-MediaLibrary

### Test Media Uploads with Team Isolation

```php
<?php

use App\Models\User;
use App\Models\Team;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\actingAs;

test('uploaded media are isolated by team', function () {
    Storage::fake('public');
    
    // Create teams and users
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    
    $user1 = User::factory()->create(['team_id' => $team1->id]);
    $user2 = User::factory()->create(['team_id' => $team2->id]);
    
    // User 1 uploads a file in team 1 context
    actingAs($user1);
    
    $file = UploadedFile::fake()->image('team1-photo.jpg');
    
    $user1->addMedia($file)
        ->withCustomProperties(['team_id' => $user1->team_id])
        ->toMediaCollection('avatars');
    
    // User 2 uploads a file in team 2 context
    actingAs($user2);
    
    $file = UploadedFile::fake()->image('team2-photo.jpg');
    
    $user2->addMedia($file)
        ->withCustomProperties(['team_id' => $user2->team_id])
        ->toMediaCollection('avatars');
    
    // Verify team1 can only see its media
    actingAs($user1);
    $team1Media = $user1->getMedia('avatars');
    expect($team1Media)->toHaveCount(1);
    expect($team1Media[0]->file_name)->toBe('team1-photo.jpg');
    
    // Verify team2 can only see its media
    actingAs($user2);
    $team2Media = $user2->getMedia('avatars');
    expect($team2Media)->toHaveCount(1);
    expect($team2Media[0]->file_name)->toBe('team2-photo.jpg');
    
    // Verify storage paths are team-specific
    $mediaModel1 = $team1Media[0];
    $mediaModel2 = $team2Media[0];
    
    // Check that the files exist in team-specific paths
    $teamProperty1 = $mediaModel1->getCustomProperty('team_id');
    $teamProperty2 = $mediaModel2->getCustomProperty('team_id');
    
    expect($teamProperty1)->toBe($team1->id);
    expect($teamProperty2)->toBe($team2->id);
});
```

## Testing Laravel-Tags

### Test Team-Specific Tags

```php
<?php

use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use function Pest\Laravel\actingAs;

test('tags are isolated by team', function () {
    // Create teams
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    
    // Create users in different teams
    $user1 = User::factory()->create(['team_id' => $team1->id]);
    $user2 = User::factory()->create(['team_id' => $team2->id]);
    
    // Create tags for each team
    actingAs($user1);
    Tag::findOrCreate('marketing', 'categories', $team1->id);
    Tag::findOrCreate('sales', 'categories', $team1->id);
    
    actingAs($user2);
    Tag::findOrCreate('marketing', 'categories', $team2->id);
    Tag::findOrCreate('development', 'categories', $team2->id);
    
    // Test tag isolation by team
    actingAs($user1);
    $team1Tags = Tag::all();
    expect($team1Tags)->toHaveCount(2);
    expect($team1Tags->pluck('name')->toArray())->toContain('marketing');
    expect($team1Tags->pluck('name')->toArray())->toContain('sales');
    expect($team1Tags->pluck('name')->toArray())->not->toContain('development');
    
    actingAs($user2);
    $team2Tags = Tag::all();
    expect($team2Tags)->toHaveCount(2);
    expect($team2Tags->pluck('name')->toArray())->toContain('marketing');
    expect($team2Tags->pluck('name')->toArray())->toContain('development');
    expect($team2Tags->pluck('name')->toArray())->not->toContain('sales');
});
```

## Testing Laravel-Comments

### Test Team-Specific Comments

```php
<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\Team;
use App\Models\User;
use function Pest\Laravel\actingAs;

test('comments are isolated by team', function () {
    // Create teams
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    
    // Create users in different teams
    $user1 = User::factory()->create(['team_id' => $team1->id]);
    $user2 = User::factory()->create(['team_id' => $team2->id]);
    
    // Create posts for each team
    actingAs($user1);
    $post1 = Post::factory()->create(['team_id' => $team1->id, 'user_id' => $user1->id]);
    
    actingAs($user2);
    $post2 = Post::factory()->create(['team_id' => $team2->id, 'user_id' => $user2->id]);
    
    // Add comments from each team
    actingAs($user1);
    $post1->comment('This is a team 1 comment');
    
    actingAs($user2);
    $post2->comment('This is a team 2 comment');
    
    // Test comment isolation by team
    actingAs($user1);
    expect(Comment::count())->toBe(1);
    $comment = Comment::first();
    expect($comment->text)->toBe('This is a team 1 comment');
    
    actingAs($user2);
    // Using a new query to clear any cached results
    expect(Comment::count())->toBe(1);
    $comment = Comment::first();
    expect($comment->text)->toBe('This is a team 2 comment');
});
```

## Testing Laravel-Model-States

### Test Deal State Transitions with Team Context

```php
<?php

use App\Models\Deal;
use App\Models\Team;
use App\Models\User;
use App\States\Deal\DealState;
use App\States\Deal\New;
use App\States\Deal\Qualified;
use function Pest\Laravel\actingAs;

test('deal state transitions respect team boundaries', function () {
    // Create teams
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    
    // Create users in different teams
    $user1 = User::factory()->create(['team_id' => $team1->id]);
    $user2 = User::factory()->create(['team_id' => $team2->id]);
    
    // Create a deal for team 1
    actingAs($user1);
    $deal = Deal::factory()->create([
        'team_id' => $team1->id,
        'status' => New::class,
    ]);
    
    // Team 1 user can transition the deal
    expect($deal->status)->toBeInstanceOf(New::class);
    $deal->status->transitionTo(Qualified::class);
    $deal->refresh();
    expect($deal->status)->toBeInstanceOf(Qualified::class);
    
    // Create a deal for team 2
    actingAs($user2);
    $deal2 = Deal::factory()->create([
        'team_id' => $team2->id,
        'status' => New::class,
    ]);
    
    // Team 2 user should not be able to transition team 1's deal
    try {
        $deal->status->transitionTo(New::class);
        $this->fail('Team 2 user should not be able to transition team 1 deal');
    } catch (\Exception $e) {
        expect($e->getMessage())->toContain('not authorized');
    }
    
    // But they can transition their own team's deal
    $deal2->status->transitionTo(Qualified::class);
    $deal2->refresh();
    expect($deal2->status)->toBeInstanceOf(Qualified::class);
});
```

## Testing Laravel-Data

### Test Data Classes with Team Context

```php
<?php

use App\Data\DealData;
use App\Models\Team;
use App\Models\User;
use function Pest\Laravel\actingAs;

test('data classes enforce team boundaries', function () {
    // Create teams
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    
    // Create users in different teams
    $user1 = User::factory()->create(['team_id' => $team1->id]);
    $user2 = User::factory()->create(['team_id' => $team2->id]);
    
    // Team 1 context
    actingAs($user1);
    
    // Creating data for own team works
    $dealData = new DealData(
        id: null,
        team_id: $team1->id,
        title: 'Team 1 Deal'
    );
    
    expect($dealData->team_id)->toBe($team1->id);
    
    // Creating data for another team throws exception
    try {
        $dealData = new DealData(
            id: null,
            team_id: $team2->id,
            title: 'Team 2 Deal'
        );
        $this->fail('Should not be able to create data for another team');
    } catch (\Exception $e) {
        expect($e->getMessage())->toContain('team mismatch');
    }
    
    // Automatic team assignment works
    $dealData = new DealData(
        id: null,
        team_id: null, // Will be auto-filled 
        title: 'Auto Team Deal'
    );
    
    expect($dealData->team_id)->toBe($team1->id);
});
```

## Testing Laravel-Dashboard

### Test Dashboard Tiles with Team Context

```php
<?php

use App\Dashboard\SalesOverviewTile;
use App\Models\Team;
use App\Models\User;
use function Pest\Laravel\actingAs;

test('dashboard tiles respect team boundaries', function () {
    // Create teams
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    
    // Create users in different teams
    $user1 = User::factory()->create(['team_id' => $team1->id]);
    $user2 = User::factory()->create(['team_id' => $team2->id]);
    
    // Set data for team 1's tile
    actingAs($user1);
    $tile1 = new SalesOverviewTile('sales');
    $tile1->put(['total' => 1000, 'count' => 10]);
    
    // Set data for team 2's tile
    actingAs($user2);
    $tile2 = new SalesOverviewTile('sales');
    $tile2->put(['total' => 2000, 'count' => 20]);
    
    // Team 1 should see its own data
    actingAs($user1);
    $tile1 = new SalesOverviewTile('sales');
    $data1 = $tile1->getData();
    expect($data1)->toBe(['total' => 1000, 'count' => 10]);
    
    // Team 2 should see its own data
    actingAs($user2);
    $tile2 = new SalesOverviewTile('sales');
    $data2 = $tile2->getData();
    expect($data2)->toBe(['total' => 2000, 'count' => 20]);
});
```

## Testing Team Isolation

### Cross-Team Access Tests

```php
<?php

use App\Models\Deal;
use App\Models\Team;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('users cannot access data from another team', function () {
    // Create teams
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    
    // Create users in different teams
    $user1 = User::factory()->create(['team_id' => $team1->id]);
    $user2 = User::factory()->create(['team_id' => $team2->id]);
    
    // Create data in team 1
    $deal1 = Deal::factory()->create([
        'team_id' => $team1->id,
        'title' => 'Team 1 Deal'
    ]);
    
    // Create data in team 2
    $deal2 = Deal::factory()->create([
        'team_id' => $team2->id,
        'title' => 'Team 2 Deal'
    ]);
    
    // Team 1 user can only see team 1 data
    actingAs($user1);
    $response = get(route('deals.index'));
    $response->assertOk();
    $response->assertSee('Team 1 Deal');
    $response->assertDontSee('Team 2 Deal');
    
    // Team 2 user can only see team 2 data
    actingAs($user2);
    $response = get(route('deals.index'));
    $response->assertOk();
    $response->assertSee('Team 2 Deal');
    $response->assertDontSee('Team 1 Deal');
    
    // Direct access to a deal from another team should be forbidden
    actingAs($user1);
    $response = get(route('deals.show', $deal2->id));
    $response->assertForbidden();
    
    actingAs($user2);
    $response = get(route('deals.show', $deal1->id));
    $response->assertForbidden();
});
```

### Helper Functions for Team Context Testing

```php
<?php

namespace Tests\Helpers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;

class TeamTestHelpers
{
    /**
     * Create a team with optional users.
     */
    public static function createTeam(TestCase $test, int $userCount = 1): array
    {
        $team = Team::factory()->create();
        
        $users = [];
        for ($i = 0; $i < $userCount; $i++) {
            $users[] = User::factory()->create(['team_id' => $team->id]);
        }
        
        return [
            'team' => $team,
            'users' => $users,
        ];
    }
    
    /**
     * Run a test for each team context and verify data isolation.
     */
    public static function assertTeamIsolation(TestCase $test, callable $setupCallback, callable $assertCallback): void
    {
        // Create two teams with users
        $team1Data = self::createTeam($test);
        $team2Data = self::createTeam($test);
        
        // Setup data for both teams
        $team1Setup = $setupCallback($team1Data['team'], $team1Data['users'][0]);
        $team2Setup = $setupCallback($team2Data['team'], $team2Data['users'][0]);
        
        // Assert isolation for team 1
        $test->actingAs($team1Data['users'][0]);
        $assertCallback($team1Data['team'], $team1Data['users'][0], $team1Setup, $team2Setup);
        
        // Assert isolation for team 2
        $test->actingAs($team2Data['users'][0]);
        $assertCallback($team2Data['team'], $team2Data['users'][0], $team2Setup, $team1Setup);
    }
}
```

## Best Practices for Testing with Wave's Team System

1. **Always Set Team Context**: Begin tests by establishing a team context using `actingAs($user)` where the user belongs to a specific team.

2. **Create Multiple Teams**: Test with multiple teams to verify isolation between them.

3. **Test Cross-Team Access**: Explicitly test that users from one team cannot access resources from another team.

4. **Use Team-Aware Factories**: Create model factories that automatically associate models with the current user's team.

5. **Check Global Scopes**: Verify that global scopes are correctly filtering queries by team.

6. **Test Direct and Indirect Access**: Test both the UI and direct API access to ensure team isolation at all levels.

7. **Reset Authentication Between Tests**: Clear authentication between tests to avoid leaking team context:

```php
$this->app->make('auth')->forgetGuards();
```

8. **Test Team-Specific Configurations**: Verify that configurations like permissions, roles, and dashboard settings are team-specific.

By following these testing patterns, you can ensure that all Spatie packages maintain proper team isolation in your Wave application.
