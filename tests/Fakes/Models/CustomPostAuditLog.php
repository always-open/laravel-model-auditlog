<?php

namespace AlwaysOpen\AuditLog\Tests\Fakes\Models;

use AlwaysOpen\AuditLog\Models\BaseModel;

class CustomPostAuditLog extends BaseModel
{
    public $timestamps = false;

    protected $guarded = [];
}
