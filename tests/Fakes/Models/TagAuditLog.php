<?php

namespace AlwaysOpen\AuditLog\Tests\Fakes\Models;

use AlwaysOpen\AuditLog\Models\BaseModel;

class TagAuditLog extends BaseModel
{
    public $timestamps = false;

    public $table = 'tags_auditlog';

    protected $guarded = [];
}
