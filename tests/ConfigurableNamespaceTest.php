<?php

namespace AlwaysOpen\AuditLog\Tests;

use AlwaysOpen\AuditLog\Tests\Fakes\Models\Post;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ConfigurableNamespaceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_uses_configured_namespace_for_audit_log_model_name()
    {
        config(['model-auditlog.model_namespace' => 'App\\Models\\AuditLogs']);

        $post = new Post();
        $this->assertEquals('App\\Models\\AuditLogs\\PostAuditLog', $post->getAuditLogModelName());
    }

    /** @test */
    public function it_uses_default_namespace_when_config_is_null()
    {
        config(['model-auditlog.model_namespace' => null]);

        $post = new Post();
        // Post is in AlwaysOpen\AuditLog\Tests\Fakes\Models namespace
        $this->assertEquals('AlwaysOpen\\AuditLog\\Tests\\Fakes\\Models\\PostAuditLog', $post->getAuditLogModelName());
    }
}
