<?php

namespace AlwaysOpen\AuditLog\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use AlwaysOpen\AuditLog\Observers\AuditLogObserver;

trait AuditLoggable
{
    public static function bootAuditLoggable(): void
    {
        static::observe(AuditLogObserver::class);
    }

    public function getAuditLogModelName(): string
    {
        return get_class($this) . config('model-auditlog.model_suffix');
    }

    /**
     * Gets an instance of the audit log for this model.
     *
     * @return mixed
     */
    public function getAuditLogModelInstance() : mixed
    {
        $class = $this->getAuditLogModelName();

        return new $class();
    }

    public function getAuditLogTableName(): string
    {
        return $this->getTable() . config('model-auditlog.table_suffix');
    }

    /**
     * @note Get fields that should be ignored from the auditlog for this model.
     */
    public function getAuditLogIgnoredFields(): array
    {
        return [];
    }

    /**
     * @note Get fields that should be used as keys on the auditlog for this model.
     */
    public function getAuditLogForeignKeyColumns(): array
    {
        return ['subject_id' => $this->getKeyName()];
    }

    /**
     * @note Get the columns used in the foreign key on the audit log table.
     */
    public function getAuditLogForeignKeyColumnKeys(): array
    {
        return array_keys($this->getAuditLogForeignKeyColumns());
    }

    /**
     * @note Get the columns used in the unique index on the model table.
     */
    public function getAuditLogForeignKeyColumnValues(): array
    {
        return array_values($this->getAuditLogForeignKeyColumns());
    }

    /**
     * @note Get the audit logs for this model.
     */
    public function auditLogs(): ?HasMany
    {
        return $this->hasMany($this->getAuditLogModelName(), 'subject_id');
    }
}
