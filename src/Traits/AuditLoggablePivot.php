<?php

namespace AlwaysOpen\AuditLog\Traits;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait AuditLoggablePivot
{
    use AuditLoggable;
    use Compoships;

    /**
     * Returns audit_loggable_keys set on AuditLogModelInstance.
     *
     * @return array
     */
    public function getAuditLogForeignKeyColumns(): array
    {
        return $this->audit_loggable_keys;
    }

    /**
     * Get the audit logs for this model.
     *
     * @return HasMany|null
     */
    public function auditLogs(): ?HasMany
    {
        return $this->hasMany(
            $this->getAuditLogModelName(),
            $this->getAuditLogForeignKeyColumnKeys(),
            $this->getAuditLogForeignKeyColumnValues()
        );
    }
}
