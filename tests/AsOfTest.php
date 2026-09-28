<?php

namespace AlwaysOpen\AuditLog\Tests;

use AlwaysOpen\AuditLog\EventType;
use AlwaysOpen\AuditLog\Tests\Fakes\Models\Post;
use AlwaysOpen\AuditLog\Tests\Fakes\Models\PostAuditLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;

class AsOfTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A post whose audit history starts after it was created, as when the
     * audit table is added to an existing model.
     */
    protected function postWithoutHistory(): Post
    {
        $post = Post::create([
            'title'     => 'current',
            'posted_at' => '2019-04-05 12:00:00',
        ]);

        $post->auditLogs()->delete();

        return $post;
    }

    protected function log(Post $post, int $eventType, string $field, $old, $new, string $occurredAt): PostAuditLog
    {
        return PostAuditLog::create([
            'subject_id'      => $post->getKey(),
            'event_type'      => $eventType,
            'field_name'      => $field,
            'field_value_old' => $old,
            'field_value_new' => $new,
            'occurred_at'     => $occurredAt,
        ]);
    }

    /** @test */
    public function date_before_first_update_returns_its_old_value()
    {
        $post = $this->postWithoutHistory();
        $this->log($post, EventType::UPDATED, 'title', 'original', 'second', '2020-01-10 00:00:00');
        $this->log($post, EventType::UPDATED, 'title', 'second', 'current', '2020-01-20 00:00:00');

        $this->assertEquals('original', $post->fieldAsOf('title', Carbon::parse('2020-01-01')));
    }

    /** @test */
    public function date_before_created_row_returns_unknown()
    {
        $post = $this->postWithoutHistory();
        $this->log($post, EventType::CREATED, 'title', null, 'current', '2020-01-10 00:00:00');

        $date = Carbon::parse('2020-01-01');
        $this->assertNull($post->fieldAsOf('title', $date));
        $this->assertEquals('fallback', $post->fieldAsOf('title', $date, 'fallback'));
    }

    /** @test */
    public function update_with_null_old_value_returns_null_not_unknown()
    {
        $post = $this->postWithoutHistory();
        $this->log($post, EventType::UPDATED, 'title', null, 'current', '2020-01-10 00:00:00');

        $this->assertNull($post->fieldAsOf('title', Carbon::parse('2020-01-01'), 'fallback'));
    }

    /** @test */
    public function date_before_restore_uses_paired_update_row()
    {
        $post = $this->postWithoutHistory();
        $post->delete();
        $deletedAt = $post->getRawOriginal('deleted_at');
        $post->auditLogs()->delete();
        $post->restore();

        // restore() saves the model (an UPDATED row with the real old value) before firing restored
        $this->assertEquals($deletedAt, $post->fieldAsOf('deleted_at', now()->subDay(), 'fallback'));
    }

    /** @test */
    public function date_before_restore_without_update_row_returns_unknown()
    {
        $post = $this->postWithoutHistory();
        $post->delete();
        $post->auditLogs()->delete();
        $post->restore();
        $post->auditLogs()->where('event_type', EventType::UPDATED)->delete();

        $restored = $post->auditLogs()->where('field_name', 'deleted_at')->sole();
        $this->assertEquals(EventType::RESTORED, $restored->event_type);
        $this->assertNull($restored->field_value_old);

        $this->assertEquals('fallback', $post->fieldAsOf('deleted_at', now()->subDay(), 'fallback'));
    }

    /** @test */
    public function date_before_soft_delete_returns_null()
    {
        $post = $this->postWithoutHistory();
        $post->delete();

        $this->assertNull($post->fieldAsOf('deleted_at', now()->subDay(), 'fallback'));
    }

    /** @test */
    public function date_between_rows_returns_earlier_rows_new_value()
    {
        $post = $this->postWithoutHistory();
        $this->log($post, EventType::UPDATED, 'title', 'original', 'second', '2020-01-10 00:00:00');
        $this->log($post, EventType::UPDATED, 'title', 'second', 'current', '2020-01-20 00:00:00');

        $this->assertEquals('second', $post->fieldAsOf('title', Carbon::parse('2020-01-15'), 'fallback'));
    }

    /** @test */
    public function row_with_null_new_value_at_or_before_date_returns_null()
    {
        $post = $this->postWithoutHistory();
        $this->log($post, EventType::UPDATED, 'title', 'original', null, '2020-01-10 00:00:00');
        $this->log($post, EventType::UPDATED, 'title', null, 'current', '2020-01-20 00:00:00');

        $this->assertNull($post->fieldAsOf('title', Carbon::parse('2020-01-15'), 'fallback'));
    }

    /** @test */
    public function no_rows_returns_unknown()
    {
        $post = $this->postWithoutHistory();

        $date = Carbon::parse('2020-01-01');
        $this->assertNull($post->fieldAsOf('title', $date));
        $this->assertEquals('fallback', $post->fieldAsOf('title', $date, 'fallback'));
    }

    /** @test */
    public function ties_on_occurred_at_break_by_id()
    {
        $post = $this->postWithoutHistory();
        $this->log($post, EventType::UPDATED, 'title', 'first-old', 'first-new', '2020-01-10 00:00:00');
        $this->log($post, EventType::UPDATED, 'title', 'second-old', 'second-new', '2020-01-10 00:00:00');

        $this->assertEquals('first-new', $post->fieldAsOf('title', Carbon::parse('2020-01-10')));
        $this->assertEquals('first-old', $post->fieldAsOf('title', Carbon::parse('2020-01-09')));
    }

    /** @test */
    public function asOf_matches_fieldAsOf_for_fields_with_only_later_rows()
    {
        $post = $this->postWithoutHistory();
        $post->update(['title' => 'updated']);
        $post->auditLogs()->update(['occurred_at' => '2020-01-10 00:00:00']);
        $this->log($post, EventType::UPDATED, 'posted_at', '2019-04-05 12:00:00', '2019-05-01 00:00:00', '2020-01-05 00:00:00');

        $date = Carbon::parse('2020-01-07');
        $asOf = $post->fresh()->asOf($date);

        $this->assertEquals('current', $asOf->title);
        $this->assertEquals($post->fieldAsOf('title', $date), $asOf->title);
        $this->assertEquals('2019-05-01 00:00:00', $asOf->posted_at);
    }

    /** @test */
    public function asOf_keeps_current_value_for_unknown_fields()
    {
        $post = $this->postWithoutHistory();
        $this->log($post, EventType::CREATED, 'title', null, 'current', '2020-01-10 00:00:00');

        $this->assertEquals('current', $post->asOf(Carbon::parse('2020-01-01'))->title);
    }
}
