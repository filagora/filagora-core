<?php

declare(strict_types=1);

use Filagora\Core\Contracts\DoctorCheck;
use Filagora\Core\Doctor\CheckResult;
use Filagora\Core\Doctor\Doctor;
use Filagora\Core\Modules\Module;
use Filagora\Core\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->loadLaravelMigrations());

it('passes on a healthy install', function () {
    $this->artisan('filagora:doctor')
        ->expectsOutputToContain('All checks passed.')
        ->assertExitCode(0);
});

it('fails when the core tables are missing', function () {
    Schema::drop('filagora_system_settings');

    $this->artisan('filagora:doctor')
        ->expectsOutputToContain('filagora_system_settings')
        ->assertExitCode(1);
});

it('fails when the user model cannot be resolved', function () {
    config(['filagora.user_model' => 'App\\Missing\\User']);

    $this->artisan('filagora:doctor')->assertExitCode(1);
});

it('fails when a registered module needs one that is not installed', function () {
    app(ModuleRegistry::class)->register(new Module('forums', 'Forums', 'filagora/filagora-forums', requires: ['entitlements']));

    $this->artisan('filagora:doctor')
        ->expectsOutputToContain('forums needs entitlements')
        ->assertExitCode(1);
});

it('runs checks registered by other packages and survives one that throws', function () {
    $doctor = app(Doctor::class);
    $doctor->register(new class implements DoctorCheck
    {
        public function run(): CheckResult
        {
            return CheckResult::warn('Extra', 'just a warning');
        }
    });
    $doctor->register(new class implements DoctorCheck
    {
        public function run(): CheckResult
        {
            throw new RuntimeException('boom');
        }
    });

    $results = $doctor->run();

    expect(collect($results)->pluck('message')->all())->toContain('just a warning')
        ->and(collect($results)->last()->message)->toContain('boom')
        ->and(Doctor::passed($results))->toBeFalse();
});

it('installs: migrates and then runs the doctor', function () {
    $this->artisan('filagora:install', ['--force' => true])
        ->expectsOutputToContain('All checks passed.')
        ->assertExitCode(0);
});
