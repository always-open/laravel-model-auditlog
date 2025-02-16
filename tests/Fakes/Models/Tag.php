<?php

namespace AlwaysOpen\AuditLog\Tests\Fakes\Models;

use Fico7489\Laravel\Pivot\Traits\PivotEventTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use AlwaysOpen\AuditLog\Traits\AuditLoggable;

class Tag extends Model
{
    use AuditLoggable;
    use SoftDeletes;
    use PivotEventTrait;

    protected $guarded = [];

    public function posts()
    {
        return $this->belongsToMany(Post::class)
            ->using(PostTag::class);
    }
}
