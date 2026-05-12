<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_accounts_cannot_login_to_admin_side(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $customer = User::create([
            'fname' => 'Customer',
            'lname' => 'User',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
        ]);

        Role::create([
            'name' => 'customer',
            'guard_name' => 'sanctum',
        ]);

        $customer->assignRole('customer');

        $this->postJson('http://localhost/api/login', [
            'email' => 'customer@example.com',
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $customer->id,
            'name' => 'cms-admin',
        ]);
    }

    public function test_customer_accounts_can_login_to_customer_side(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $customer = User::create([
            'fname' => 'Customer',
            'lname' => 'User',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
        ]);

        Role::create([
            'name' => 'customer',
            'guard_name' => 'sanctum',
        ]);

        $customer->assignRole('customer');

        $this->postJson('http://localhost/api/customer-login', [
            'email' => 'customer@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email']]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $customer->id,
            'name' => 'cms-customer',
        ]);
    }

    public function test_non_customer_accounts_cannot_login_to_customer_side(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = User::create([
            'fname' => 'Admin',
            'lname' => 'User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->postJson('http://localhost/api/customer-login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $admin->id,
            'name' => 'cms-customer',
        ]);
    }
}
