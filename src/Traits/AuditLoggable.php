<?php

namespace AlwaysOpen\AuditLog\Traits;

use AlwaysOpen\AuditLog\EventType;
use AlwaysOpen\AuditLog\Observers\AuditLogObserver;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use ReflectionClass;

trait AuditLoggable
{
    public const AUDIT_EVENT_CREATED = 'created';
    public const AUDIT_EVENT_UPDATED = 'updated';
    public const AUDIT_EVENT_DELETED = 'deleted';
    public const AUDIT_EVENT_RESTORED = 'restored';

    /**
     * Boots the trait and sets the observer.
     */
    public static function bootAuditLoggable(): void
    {
        static::observe(AuditLogObserver::class);
    }

    /**
     * @throws \ReflectionException
     */
    public function getAuditLogModelName(): string
    {
        $modelSuffix = config('model-auditlog.model_suffix');

        $tableName = Str::afterLast($this->getTable(), '.');
        $modelName = Str::studly(Str::singular($tableName)) . $modelSuffix;

        return $this->getAuditLogModelNamespace() . '\\' . $modelName;
    }

    public function getAuditLogModelNamespace(): string
    {
        $namespace = config('model-auditlog.model_namespace');

        if (!empty($namespace)) {
            return rtrim($namespace, '\\');
        }

        return (new ReflectionClass($this))->getNamespaceName();
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
     *
     * @throws \ReflectionException
     */
    public function auditLogs(): ?HasMany
    {
        return $this->hasMany($this->getAuditLogModelName(), 'subject_id');
    }

    /**
     * Get the value a field held on the given date.
     *
     * Uses the newest row at or before the date. Failing that, the earliest later
     * row's old value, when that row recorded one. Returns $whenUnknown when the
     * value can't be determined (no rows, or the model didn't exist yet).
     */
    public function fieldAsOf($field, \DateTime $date, mixed $whenUnknown = null) : mixed
    {
        $before = $this->auditLogs()
            ->where('field_name', '=', $field)
            ->where('occurred_at', '<=', $date)
            ->orderBy('occurred_at', 'desc')
            ->orderBy($this->getAuditLogTableName() . '.id')
            ->first();

        if ($before) {
            return $before->field_value_new;
        }

        $after = $this->auditLogs()
            ->where('field_name', '=', $field)
            ->where('occurred_at', '>', $date)
            ->orderBy('occurred_at')
            ->orderBy($this->getAuditLogTableName() . '.id')
            ->first();

        // Only updates record the prior value, and a soft delete implies deleted_at was null.
        // Created rows mean the model didn't exist; restored rows are written after the
        // original is synced, so their old value is never recorded.
        if ($after && in_array((int) $after->event_type, [EventType::UPDATED, EventType::DELETED], true)) {
            return $after->field_value_old;
        }

        return $whenUnknown;
    }

    public function asOf(\DateTime $date) : self
    {
        $fields = $this->auditLogs()
            ->select('field_name')
            ->distinct()
            ->get();

        $subject = $this->replicate();
        $unknown = new \stdClass();

        $fields->each(function ($row) use ($date, &$subject, $unknown) {
            $value = $this->fieldAsOf($row->field_name, $date, $unknown);

            if ($value !== $unknown) {
                $subject->{$row->field_name} = $value;
            }
        });

        return $subject;
    }

    /**
     * Overridable but allows all events by default
     *
     * @return array
     */
    public function allowedAuditActions() : array
    {
        return [
            self::AUDIT_EVENT_CREATED,
            self::AUDIT_EVENT_UPDATED,
            self::AUDIT_EVENT_DELETED,
            self::AUDIT_EVENT_RESTORED,
        ];
    }

    public function auditEventAllowed(string $event) : bool
    {
        return in_array(
            $event,
            array_intersect($this->allowedAuditActions(), config('model-auditlog.allowed_audit_actions', []))
        );
    }
}
