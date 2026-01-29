<?php

namespace AlwaysOpen\AuditLog\Tests\Fakes\Models\AuditLogs;

use AlwaysOpen\AuditLog\Models\BaseModel;
use AlwaysOpen\AuditLog\Tests\Fakes\Models\Post;

/**
 * This class is used for testing the custom audit log namespace config.
 */
class PostAuditLog extends BaseModel
{
    public $timestamps = false;

    public $table = 'posts_auditlog';

    protected $guarded = [];

    protected string $auditLoggableModel = Post::class;
}
