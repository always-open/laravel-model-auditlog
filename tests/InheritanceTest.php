<?php

namespace AlwaysOpen\AuditLog\Tests;

use AlwaysOpen\AuditLog\Tests\Fakes\Models\CustomPost;
use AlwaysOpen\AuditLog\Tests\Fakes\Models\SubClassOfPosts;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;

class InheritanceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_uses_child_audit_log_model_when_child_has_its_own_table()
    {
        $customPost = new CustomPost();

        $this->assertEquals(
            'AlwaysOpen\\AuditLog\\Tests\\Fakes\\Models\\CustomPostAuditLog',
            $customPost->getAuditLogModelName()
        );
    }

    /** @test */
    public function it_uses_parent_audit_log_model_when_child_uses_parent_table()
    {
        $extendedPost = new SubClassOfPosts();

        $this->assertEquals(
            'AlwaysOpen\\AuditLog\\Tests\\Fakes\\Models\\PostAuditLog',
            $extendedPost->getAuditLogModelName()
        );
    }

    /** @test */
    public function it_uses_child_audit_log_table_when_child_has_its_own_table()
    {
        $customPost = new CustomPost();

        $this->assertEquals(
            'custom_posts_auditlog',
            $customPost->getAuditLogTableName()
        );
    }

    /** @test */
    public function it_calculates_child_audit_log_table_when_child_uses_parent_table()
    {
        $extendedPost = new SubClassOfPosts();

        $this->assertEquals(
            'posts_auditlog',
            $extendedPost->getAuditLogTableName()
        );
    }
}
