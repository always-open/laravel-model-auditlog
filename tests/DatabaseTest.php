<?php

namespace AlwaysOpen\AuditLog\Tests;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class DatabaseTest extends TestCase
{
    #[Test]
    public function posts_table_exists()
    {
        $this->assertTrue(Schema::hasTable('posts'));
    }

    #[Test]
    public function posts_auditlog_table_exists()
    {
        $this->assertTrue(Schema::hasTable('posts_auditlog'));
    }
}
