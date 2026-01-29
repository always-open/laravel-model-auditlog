<?php

namespace AlwaysOpen\AuditLog\Tests;

use AlwaysOpen\AuditLog\Tests\Fakes\Models\Post;
use AlwaysOpen\AuditLog\Tests\Fakes\Models\PostAuditLog;
use AlwaysOpen\AuditLog\Tests\Fakes\Models\AuditLogs\PostAuditLog AS CustomNamespacePostAuditLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BaseModelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_correct_subject_model_classname_with_default_namespace()
    {
        $auditLog = new PostAuditLog();

        $this->assertEquals(Post::class, $auditLog->getSubjectModelClassname());
    }

    /** @test */
    public function it_gets_correct_subject_model_classname_with_custom_namespace()
    {
        config(['model-auditlog.model_namespace' => 'AlwaysOpen\Tests\Fakes\Models\AuditLogs']);

        $auditLog = new CustomNamespacePostAuditLog();

        $this->assertEquals(Post::class, $auditLog->getSubjectModelClassname());
    }

    /** @test */
    public function it_gets_correct_subject_model_classname_with_custom_suffix()
    {
        config(['model-auditlog.model_suffix' => 'AuditLog']);
        $auditLog = new CustomNamespacePostAuditLog();
        $this->assertEquals(Post::class, $auditLog->getSubjectModelClassname());

        config(['model-auditlog.model_suffix' => 'Log']);
        // If suffix is Log, then AlwaysOpen\AuditLog\Tests\Fakes\Models\PostAuditLog
        // should become AlwaysOpen\AuditLog\Tests\Fakes\Models\PostAudit
        $this->assertEquals('AlwaysOpen\AuditLog\Tests\Fakes\Models\PostAudit', $auditLog->getSubjectModelClassname());
    }

    /** @test */
    public function it_can_get_subject_model_instance_with_default_namespace()
    {
        $auditLog = new PostAuditLog();
        $subject = $auditLog->getSubjectModelClassInstance();

        $this->assertInstanceOf(Post::class, $subject);
    }

    /** @test */
    public function it_can_get_subject_model_instance_with_custom_namespace()
    {
        config(['model-auditlog.model_namespace' => 'AlwaysOpen\Tests\Fakes\Models\AuditLogs']);

        $auditLog = new CustomNamespacePostAuditLog();
        $subject = $auditLog->getSubjectModelClassInstance();

        $this->assertInstanceOf(Post::class, $subject);
    }
}
