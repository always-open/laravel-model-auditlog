<?php

namespace AlwaysOpen\AuditLog\Traits;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait AuditLoggablePivot
{
    use AuditLoggable;
    use Compoships;

    /**
     * @note Returns audit_loggable_keys set on AuditLogModelInstance.
     */
    public function getAuditLogForeignKeyColumns(): array
    {
        return $this->audit_loggable_keys;
    }

    /**
     * @note Get the audit logs for this model.
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
