<?php

namespace AlwaysOpen\AuditLog\Tests\Fakes\Models;

use AlwaysOpen\AuditLog\Models\BaseModel;

class IgnoredFieldsPostAuditLog extends BaseModel
{
    public $timestamps = false;

    public $table = 'posts_auditlog';

    protected $guarded = [];
}
