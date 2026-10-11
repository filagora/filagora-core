<?php

declare(strict_types=1);

use ForumKit\Core\Contracts\DoctorCheck;
use ForumKit\Core\Doctor\CheckResult;
use ForumKit\Core\Doctor\Doctor;
use ForumKit\Core\Modules\Module;
use ForumKit\Core\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->loadLaravelMigrations());

it('passes on a healthy install', function () {
    $this->artisan('forumkit:doctor')
        ->expectsOutputToContain('All checks passed.')
        ->assertExitCode(0);
});

it('fails when the core tables are missing', function () {
    Schema::drop('forumkit_system_settings');

    $this->artisan('forumkit:doctor')
        ->expectsOutputToContain('forumkit_system_settings')
        ->assertExitCode(1);
});

it('fails when the user model cannot be resolved', function () {
    config(['forumkit.user_model' => 'App\\Missing\\User']);

    $this->artisan('forumkit:doctor')->assertExitCode(1);
});

it('fails when a registered module needs one that is not installed', function () {
    app(ModuleRegistry::class)->register(new Module('forums', 'Forums', 'zcwilt/forumkit-forums', requires: ['entitlements']));

    $this->artisan('forumkit:doctor')
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
    $this->artisan('forumkit:install', ['--force' => true])
        ->expectsOutputToContain('All checks passed.')
        ->assertExitCode(0);
});
