<?php

namespace AlwaysOpen\AuditLog\Traits;

use AlwaysOpen\AuditLog\Observers\AuditLogObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    public function getAuditLogModelName(?string $className = null): string
    {
        $namespace = config('model-auditlog.model_namespace');
        $targetClass = $className ?: get_class($this);
        $modelName = class_basename($targetClass) . config('model-auditlog.model_suffix');

        if ($namespace) {
            $fullClassName = rtrim($namespace, '\\') . '\\' . $modelName;
        } else {
            $fullClassName = (new ReflectionClass($targetClass))->getNamespaceName() . '\\' . $modelName;
        }

        if (class_exists($fullClassName)) {
            return $fullClassName;
        }

        $parent = get_parent_class($targetClass);
        if ($parent && is_subclass_of($parent, Model::class)) {
            return $this->getAuditLogModelName($parent);
        }

        return $fullClassName;
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
        $modelClass = $this->getAuditLogModelName();
        if (class_exists($modelClass)) {
            return (new $modelClass())->getTable();
        }

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
