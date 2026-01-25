<?php

namespace AlwaysOpen\AuditLog\Tests;

use AlwaysOpen\AuditLog\Tests\Fakes\Models\NonAuditLoggable;
use AlwaysOpen\AuditLog\Tests\Fakes\Models\Post;
use AlwaysOpen\AuditLog\Tests\Fakes\Models\ExtendedPost;
use AlwaysOpen\AuditLog\Tests\Fakes\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MakeModelAuditLogTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        config([
            'model-auditlog.user_model' => User::class,
        ]);
    }

    /** @test */
    public function it_fails_if_model_class_does_not_exist()
    {
        $this->artisan('make:model-auditlog', ['existing-model-class' => 'NonExistentModel'])
            ->assertExitCode(1)
            ->expectsOutputToContain('Class NonExistentModel could not be found');
    }

    /** @test */
    public function it_fails_if_model_does_not_use_auditloggable_trait()
    {
        $this->artisan('make:model-auditlog', ['existing-model-class' => NonAuditLoggable::class])
            ->assertExitCode(1)
            ->expectsOutputToContain('does not use the AuditLoggable trait');
    }

    /** @test */
    public function it_generates_model_and_migration_for_new_model()
    {
        $modelPath = Storage::disk('local')->path('Models');
        $migrationPath = Storage::disk('local')->path('migrations');
        config([
            'model-auditlog.model_path' => $modelPath,
            'model-auditlog.migration_path' => $migrationPath,
        ]);

        $model = User::class;

        $this->artisan('make:model-auditlog', ['existing-model-class' => $model])
            ->assertExitCode(0)
            ->expectsOutputToContain("Generating audit log model and table migration for: $model")
            ->expectsOutputToContain("Model successfully created at: $modelPath/UserAuditLog.php");

        $this->assertTrue(File::exists($modelPath . '/UserAuditLog.php'));

        $migrationFiles = File::files($migrationPath);
        $this->assertCount(1, $migrationFiles);
        $this->assertStringContainsString('create_users_auditlog_table.php', $migrationFiles[0]->getFilename());
    }

    /** @test */
    public function it_warns_and_asks_confirmation_if_audit_log_exists()
    {
        $modelPath = Storage::disk('local')->path('Models');
        $migrationPath = Storage::disk('local')->path('migrations');

        config([
            'model-auditlog.model_path' => $modelPath,
            'model-auditlog.migration_path' => $migrationPath,
        ]);

        // PostAuditLog exists
        $class = Post::class;
        $existingAuditModel = 'AlwaysOpen\AuditLog\Tests\Fakes\Models\PostAuditLog';

        $this->artisan('make:model-auditlog', ['existing-model-class' => $class])
            ->expectsOutput("An audit log model already exists for this model or its parent: $existingAuditModel")
            ->expectsConfirmation('Do you want to generate a new one specifically for ' . $class . '?', 'no')
            ->assertExitCode(0);

        // Should not have created files
        $this->assertFalse(File::exists($modelPath . '/PostAuditLog.php'));
    }

    /** @test */
    public function it_proceeds_with_generation_if_user_confirms_existing_audit_log()
    {
        $modelPath = Storage::disk('local')->path('Models');
        $migrationPath = Storage::disk('local')->path('migrations');

        config([
            'model-auditlog.model_path' => $modelPath,
            'model-auditlog.migration_path' => $migrationPath,
        ]);

        $class = Post::class;

        $this->artisan('make:model-auditlog', ['existing-model-class' => $class])
            ->expectsConfirmation('Do you want to generate a new one specifically for ' . $class . '?', 'yes')
            ->expectsOutput("Model successfully created at: $modelPath/PostAuditLog.php")
            ->assertExitCode(0);

        $this->assertTrue(File::exists($modelPath . '/PostAuditLog.php'));
    }

    /** @test */
    public function it_handles_inheritance_fallback_warning()
    {
        $modelPath = Storage::disk('local')->path('Models');
        $migrationPath = Storage::disk('local')->path('migrations');

        config([
            'model-auditlog.model_path' => $modelPath,
            'model-auditlog.migration_path' => $migrationPath,
        ]);

        // ExtendedPost inherits Post's audit log: PostAuditLog
        $class = ExtendedPost::class;
        $existingAuditModel = 'AlwaysOpen\AuditLog\Tests\Fakes\Models\PostAuditLog';

        $this->artisan('make:model-auditlog', ['existing-model-class' => $class])
            ->expectsOutput("An audit log model already exists for this model or its parent: $existingAuditModel")
            ->expectsConfirmation('Do you want to generate a new one specifically for ' . $class . '?', 'no')
            ->assertExitCode(0);
    }
}
