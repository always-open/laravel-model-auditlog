<?php

namespace AlwaysOpen\AuditLog\Tests\Fakes\Models;

use Illuminate\Database\Eloquent\Model;
use AlwaysOpen\AuditLog\Traits\AuditLoggable;

class IgnoredFieldsPost extends Model
{
    use AuditLoggable;

    protected $guarded = [];

    protected $table = 'posts';

    public function getAuditLogIgnoredFields(): array
    {
        return ['posted_at'];
    }
}
