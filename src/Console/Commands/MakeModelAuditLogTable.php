<?php

namespace AlwaysOpen\AuditLog\Console\Commands;

use AlwaysOpen\AuditLog\Traits\AuditLoggable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeModelAuditLogTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:model-auditlog
                            {existing-model-class : Define which model this auditlog should extend.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes a new audit log migration and model to your application.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $class = $this->argument('existing-model-class');

        if (! class_exists($class)) {
            $this->error("Class $class not found");

            return self::FAILURE;
        }

        $subjectModel = new $class();

        if (! in_array(AuditLoggable::class, class_uses_recursive($subjectModel), true)) {
            $this->error("Class $class does not use the AuditLoggable trait");

            return self::FAILURE;
        }

        $auditLogModelClass = $subjectModel->getAuditLogModelName();
        if (class_exists($auditLogModelClass)) {
            $this->warn("An audit log model already exists for this model: $auditLogModelClass");
            if (! $this->confirm('Do you want to regenerate a new model and migration for ' . $class . '?', false)) {
                return self::SUCCESS;
            }
        }

        $this->line("Generating audit log model and table migration for: $class");

        $config = config('model-auditlog');

        $this->line("Audit Table will be {$this->generateAuditTableName($subjectModel)}");
        $this->createMigration($subjectModel, $config);

        $this->line("Audit Model will be {$this->generateAuditModelName($subjectModel)}");
        $this->createModel($subjectModel, $config);

        return self::SUCCESS;
    }

    /**
     * @param Model $subjectModel
     *
     * @return string
     */
    public function generateAuditTableName(Model $subjectModel): string
    {
        return $subjectModel->getAuditLogTableName();
    }

    /**
     * @param Model $subjectModel
     *
     * @return string
     */
    public function generateAuditModelName(Model $subjectModel): string
    {
        return Str::afterLast($subjectModel->getAuditLogModelName(), '\\');
    }

    /**
     * @param Model $subjectModel
     *
     * @return string
     */
    public function getModelNamespace(Model $subjectModel): string
    {
        return $subjectModel->getAuditLogModelNamespace();
    }

    /**
     * @param Model $subjectModel
     * @param array $config
     */
    public function createModel(Model $subjectModel, array $config): void
    {
        $modelName = $this->generateAuditModelName($subjectModel);

        $stub = $this->getStubWithReplacements($config['model_stub'], [
            '{TABLE_NAME}' => $this->generateAuditTableName($subjectModel),
            '{CLASS_NAME}' => $modelName,
            '{NAMESPACE}'  => $this->getModelNamespace($subjectModel),
        ]);

        $filename = $config['model_path'] . DIRECTORY_SEPARATOR . $modelName . '.php';

        $directory = dirname($filename);
        if (
            !File::isDirectory($directory) &&
            !File::makeDirectory($directory, 0755, true)
        ) {
            $this->error("Directory $directory could not be created");

            return;
        }

        if (File::put($filename, $stub)) {
            $this->info("Model successfully created at: $filename");
        }
    }

    /**
     * @param Model $subjectModel
     * @param array $config
     */
    public function createMigration(Model $subjectModel, array $config): void
    {
        $tableName = $this->generateAuditTableName($subjectModel);
        $fileSlug = "create_{$tableName}_table";

        $stub = $this->getStubWithReplacements($config['migration_stub'], [
            '{TABLE_NAME}'          => $tableName,
            '{CLASS_NAME}'          => $this->generateMigrationClassname($fileSlug),
            '{PROCESS_IDS_SETUP}'   => $this->generateMigrationProcessStamps($config),
            '{FOREIGN_KEY_SUBJECT}' => $this->generateMigrationSubjectForeignKeys($subjectModel, $config),
            '{FOREIGN_KEY_USER}'    => $this->generateMigrationUserForeignKeys($config),
            '{PRECISION}'           => $this->generatePrecisionValue($config),
        ]);

        $filename = $config['migration_path'] . DIRECTORY_SEPARATOR . $this->generateMigrationFilename($fileSlug);

        $directory = dirname($filename);
        if (
            !File::isDirectory($directory) &&
            !File::makeDirectory($directory, 0755, true)
        ) {
            $this->error("Directory $directory could not be created");

            return;
        }

        if (File::put($filename, $stub)) {
            $this->info("Migration successfully created at: $filename");
        }
    }

    /**
     * @param string $fileSlug
     *
     * @return string
     */
    public function generateMigrationFilename(string $fileSlug): string
    {
        return Str::snake(Str::lower(date('Y_m_d_His') . ' ' . $fileSlug . '.php'));
    }

    /**
     * @param string $fileSlug
     *
     * @return string
     */
    public function generateMigrationClassname(string $fileSlug): string
    {
        return Str::studly($fileSlug);
    }

    /**
     * @param string $file
     * @param array  $replacements
     *
     * @return string
     */
    public function getStubWithReplacements(string $file, array $replacements): string
    {
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            File::get(realpath($file))
        );
    }

    /**
     * @param Model $subjectModel
     * @param array $config
     *
     * @return string
     */
    public function generateMigrationSubjectForeignKeys(Model $subjectModel, array $config): string
    {
        if (Arr::get($config, 'enable_subject_foreign_keys') === true) {
            return '$table->foreign(\'subject_id\')
                ->references(\'' . $subjectModel->getKeyName() . '\')
                ->on(\'' . $subjectModel->getTable() . '\');';
        }

        return '';
    }

    /**
     * @param array $config
     *
     * @return string
     */
    public function generateMigrationUserForeignKeys(array $config): string
    {
        $userModel = new $config['user_model']();
        if (Arr::get($config, 'enable_user_foreign_keys') === true && ! empty($userModel)) {
            $userTable = $userModel->getTable();
            $userPrimary = $userModel->getKeyName();

            return '$table->foreign(\'user_id\')
                ->references(\'' . $userPrimary . '\')
                ->on(\'' . $userTable . '\');';
        }

        return '';
    }

    /**
     * @param array $config
     *
     * @return string
     */
    public function generateMigrationProcessStamps(array $config): string
    {
        if (Arr::get($config, 'enable_process_stamps') === true) {
            return '$table->processIds();';
        }

        return '';
    }

    public function generatePrecisionValue(array $config): string
    {
        return (string) (Arr::get($config, 'log_timestamp_precision', 0) ?? 0);
    }
}
