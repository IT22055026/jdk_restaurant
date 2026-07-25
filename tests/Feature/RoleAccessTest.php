<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Role;
use App\Models\User;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_cashier_can_open_pos_billing()
    {
        $cashier = $this->userWithRole('Cashier');

        $this->actingAs($cashier)->get(route('pos.index'))->assertOk();
    }

    public function test_cashier_can_open_shifts_and_till_management()
    {
        $cashier = $this->userWithRole('Cashier');

        $this->actingAs($cashier)->get(route('shifts.index'))->assertOk();
    }

    public function test_cashier_is_blocked_from_every_other_module()
    {
        $cashier = $this->userWithRole('Cashier');

        $this->actingAs($cashier)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('customers.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('inventory.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('offers.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('sales-report.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('settings.index'))->assertForbidden();
    }

    public function test_admin_has_access_to_every_module()
    {
        $admin = $this->userWithRole('Admin');

        $this->actingAs($admin)->get(route('pos.index'))->assertOk();
        $this->actingAs($admin)->get(route('reports.index'))->assertOk();
        $this->actingAs($admin)->get(route('customers.index'))->assertOk();
        $this->actingAs($admin)->get(route('inventory.index'))->assertOk();
        $this->actingAs($admin)->get(route('shifts.index'))->assertOk();
        $this->actingAs($admin)->get(route('offers.index'))->assertOk();
        $this->actingAs($admin)->get(route('sales-report.index'))->assertOk();
        $this->actingAs($admin)->get(route('settings.index'))->assertOk();
    }

    public function test_manager_has_access_to_everything_except_pos()
    {
        $manager = $this->userWithRole('Manager');

        $this->actingAs($manager)->get(route('reports.index'))->assertOk();
        $this->actingAs($manager)->get(route('inventory.index'))->assertOk();
        $this->actingAs($manager)->get(route('pos.index'))->assertForbidden();
    }

    public function test_login_redirects_cashier_straight_to_pos()
    {
        $cashier = $this->userWithRole('Cashier');

        $response = $this->post(route('login'), [
            'email' => $cashier->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('pos.index'));
    }

    public function test_login_redirects_admin_to_their_first_module()
    {
        $admin = $this->userWithRole('Admin');

        $response = $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        // Admin's first module by sort_order is Reports (sort_order 0), shown as "Dashboard".
        $response->assertRedirect(route('reports.index'));
    }

    public function test_login_ignores_a_stale_intended_url_the_role_cannot_access()
    {
        // Simulates hitting a forbidden page (e.g. an old bookmark to Reports)
        // while logged out, which Laravel stores as the "intended" URL — that
        // must not override the role's own deterministic landing page.
        $cashier = $this->userWithRole('Cashier');
        $this->withSession(['url.intended' => route('reports.index')]);

        $response = $this->post(route('login'), [
            'email' => $cashier->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('pos.index'));
    }
}
