<?php

namespace AlwaysOpen\AuditLog\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use AlwaysOpen\AuditLog\Observers\AuditLogObserver;

trait AuditLoggable
{
    /**
     * Boots the trait and sets the observer.
     */
    public static function bootAuditLoggable(): void
    {
        static::observe(AuditLogObserver::class);
    }

    /**
     * @return string
     */
    public function getAuditLogModelName(): string
    {
        return get_class($this) . config('model-auditlog.model_suffix');
    }

    /**
     * Gets an instance of the audit log for this model.
     *
     * @return mixed
     */
    public function getAuditLogModelInstance()
    {
        $class = $this->getAuditLogModelName();

        return new $class();
    }

    /**
     * @return string
     */
    public function getAuditLogTableName(): string
    {
        return $this->getTable() . config('model-auditlog.table_suffix');
    }

    /**
     * Get fields that should be ignored from the auditlog for this model.
     *
     * @return array
     */
    public function getAuditLogIgnoredFields(): array
    {
        return [];
    }

    /**
     * Get fields that should be used as keys on the auditlog for this model.
     *
     * @return array
     */
    public function getAuditLogForeignKeyColumns(): array
    {
        return ['subject_id' => $this->getKeyName()];
    }

    /**
     * Get the columns used in the foreign key on the audit log table.
     *
     * @return array
     */
    public function getAuditLogForeignKeyColumnKeys(): array
    {
        return array_keys($this->getAuditLogForeignKeyColumns());
    }

    /**
     * Get the columns used in the unique index on the model table.
     *
     * @return array
     */
    public function getAuditLogForeignKeyColumnValues(): array
    {
        return array_values($this->getAuditLogForeignKeyColumns());
    }

    /**
     * Get the audit logs for this model.
     *
     * @return HasMany|null
     */
    public function auditLogs(): ?HasMany
    {
        return $this->hasMany($this->getAuditLogModelName(), 'subject_id');
    }

    public function fieldAsOf($field, \DateTime $date) : mixed
    {
        return $this->auditLogs()
                ->where('field_name', '=', $field)
                ->where('occurred_at', '<=', $date)
                ->orderBy('occurred_at', 'desc')
                ->orderBy($this->getAuditLogTableName() . '.id')
                ->first()
                ->field_value_new ?? null;
    }

    public function asOf(\DateTime $date) : self
    {
        $fields = $this->auditLogs()
            ->where('occurred_at', '<=', $date)
            ->select('field_name')
            ->distinct()
            ->get();

        $subject = $this->replicate();

        $fields->each(function ($row) use ($date, &$subject) {
            $subject->{$row->field_name} = $this->fieldAsOf($row->field_name, $date);
        });

        return $subject;
    }
}
