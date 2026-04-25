<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Http\Middleware\CheckPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Define a test route for middleware verification
        Route::get('/test-permission/{permission}', function () {
            return response()->json(['message' => 'Access Granted']);
        })->middleware(['auth:sanctum', CheckPermission::class . ':test-action']);
    }

    /** @test */
    public function a_user_has_permissions_via_role()
    {
        $permission = Permission::create(['name' => 'view-test', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'web']);
        $role->syncPermissions(['view-test']);

        $user = User::factory()->create(['role' => 'test-role', 'active' => true]);

        $this->assertTrue($user->hasPermission('view-test'));
        $this->assertFalse($user->hasPermission('edit-test'));
    }

    /** @test */
    public function super_admin_bypasses_all_permissions()
    {
        $adminRole = config('permissions.super_admin_role', 'admin');
        
        // Create the role but DON'T assign any permissions to it
        Role::create(['name' => $adminRole, 'guard_name' => 'web']);
        
        $user = User::factory()->create(['role' => $adminRole, 'active' => true]);

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->hasPermission('any-random-permission'));
    }

    /** @test */
    public function permissions_are_cached_and_invalidated_on_role_change()
    {
        $permission = Permission::create(['name' => 'view-test', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'web']);
        $user = User::factory()->create(['role' => 'test-role', 'active' => true]);

        // First check: should be false
        $this->assertFalse($user->hasPermission('view-test'));

        // Verify cache exists
        $this->assertTrue(Cache::has("user_perms_{$user->id}"));
        $this->assertEquals([], Cache::get("user_perms_{$user->id}"));

        // Update role permissions
        $role->syncPermissions(['view-test']);

        // Cache should be cleared by Role model's saved/sync logic
        $this->assertFalse(Cache::has("user_perms_{$user->id}"));

        // Second check: should be true and re-cached
        $this->assertTrue($user->hasPermission('view-test'));
        $this->assertContains('view-test', Cache::get("user_perms_{$user->id}"));
    }

    /** @test */
    public function middleware_blocks_unauthorized_users()
    {
        // Permission auto-registration test: the middleware will create 'test-action' if missing
        $role = Role::create(['name' => 'limited-role', 'guard_name' => 'web']);
        $user = User::factory()->create(['role' => 'limited-role', 'active' => true]);

        Sanctum::actingAs($user);

        $this->getJson('/test-permission/test-action')
            ->assertStatus(403)
            ->assertJsonFragment(['message' => 'No tiene el permiso requerido: Test Action']);

        // Verify auto-registration happened
        $this->assertDatabaseHas('permissions', ['name' => 'test-action']);
    }

    /** @test */
    public function middleware_allows_authorized_users()
    {
        Permission::create(['name' => 'test-action', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'staff', 'guard_name' => 'web']);
        $role->syncPermissions(['test-action']);
        
        $user = User::factory()->create(['role' => 'staff', 'active' => true]);

        Sanctum::actingAs($user);

        $this->getJson('/test-permission/test-action')
            ->assertStatus(200)
            ->assertJson(['message' => 'Access Granted']);
    }

    /** @test */
    public function user_is_cleared_from_cache_when_role_is_deleted()
    {
        $role = Role::create(['name' => 'temporary', 'guard_name' => 'web']);
        $user = User::factory()->create(['role' => 'temporary', 'active' => true]);
        
        $user->getPermissions(); // Trigger caching
        $this->assertTrue(Cache::has("user_perms_{$user->id}"));

        $role->delete();

        $this->assertFalse(Cache::has("user_perms_{$user->id}"));
    }
}
