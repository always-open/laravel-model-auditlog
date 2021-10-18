<?php

namespace AlwaysOpen\AuditLog\Observers;

use Illuminate\Database\Eloquent\Model;
use AlwaysOpen\AuditLog\EventType;

class AuditLogObserver
{
    public function created(Model $model): void
    {
        $this->getAuditLogModel($model)
            ->recordChanges(EventType::CREATED, $model);
    }

    public function updated(Model $model): void
    {
        $this->getAuditLogModel($model)
            ->recordChanges(EventType::UPDATED, $model);
    }

    public function deleted(Model $model): void
    {
        $event = EventType::DELETED;
        /*
         * If a model is hard deleting, either via a force delete or that model does not implement
         * the SoftDeletes trait we should tag it as such so logging doesn't occur down the pipe.
         */
        if ((! method_exists($model, 'isForceDeleting') || $model->isForceDeleting())) {
            $event = EventType::FORCE_DELETED;
        }

        $this->getAuditLogModel($model)
            ->recordChanges($event, $model);
    }

    public function restored(Model $model): void
    {
        $this->getAuditLogModel($model)
            ->recordChanges(EventType::RESTORED, $model);
    }

    public function pivotDetached(Model $model, string $relationName, array $pivotIds)
    {
        $this->getAuditLogModel($model)
            ->recordPivotChanges(EventType::PIVOT_DELETED, $model, $relationName, $pivotIds);
    }

    protected function getAuditLogModel(Model $model)
    {
        return $model->getAuditLogModelInstance();
    }
}
