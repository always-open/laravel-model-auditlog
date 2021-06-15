<?php

namespace AlwaysOpen\AuditLog\Tests\Fakes\Models;

use Illuminate\Database\Eloquent\Model;
use AlwaysOpen\AuditLog\Traits\AuditLoggable;

class NonSoftDeletePost extends Model
{
    use AuditLoggable;

    protected $guarded = [];

    protected $table = 'posts';
}
