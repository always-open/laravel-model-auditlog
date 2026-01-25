<?php

namespace AlwaysOpen\AuditLog\Tests\Fakes\Models;

use AlwaysOpen\AuditLog\Models\BaseModel;

class CustomPostAuditLog extends BaseModel
{
    public $timestamps = false;

    public $table = 'custom_posts_auditlog';

    protected $guarded = [];
}
