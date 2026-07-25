<?php

namespace Tests;

use Database\Seeders\ModuleSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Roles/modules need to exist for every test that hits a route gated by
     * EnsureModuleAccess. Seeding via the RefreshDatabase trait's own
     * afterRefreshingDatabase() hook doesn't work here: that trait is used
     * directly in each test class, so its empty stub always wins over this
     * class's override (traits take precedence over inherited parent
     * methods) — seeding after parent::setUp() instead sidesteps that.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class))) {
            $this->seed([RoleSeeder::class, ModuleSeeder::class]);
        }
    }
}
