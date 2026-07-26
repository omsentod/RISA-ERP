<?php

namespace Tests\Feature\Domain\Stock\Actions;

use App\Domain\Stock\Actions\StartOutboundSession;
use App\Domain\Stock\Models\OutboundTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StartOutboundSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_creates_draft_transaction_with_generated_doc_no(): void
    {
        Carbon::setTestNow('2026-07-26 10:00:00');

        $tx = app(StartOutboundSession::class)->handle();

        $this->assertInstanceOf(OutboundTransaction::class, $tx);
        $this->assertTrue($tx->exists);
        $this->assertSame('SJ-20260726-001', $tx->doc_no);
        $this->assertSame(OutboundTransaction::STATUS_DRAFT, $tx->status);
        $this->assertSame(0, $tx->total_qty);
        $this->assertSame('2026-07-26', $tx->doc_date->toDateString());
        $this->assertNotNull($tx->started_at);
    }

    public function test_uses_authenticated_user_as_creator_when_no_user_id_passed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tx = app(StartOutboundSession::class)->handle();

        $this->assertSame($user->id, $tx->created_by);
    }

    public function test_prefers_passed_user_id_over_authenticated_user(): void
    {
        $auth = User::factory()->create();
        $explicit = User::factory()->create();
        $this->actingAs($auth);

        $tx = app(StartOutboundSession::class)->handle($explicit->id);

        $this->assertSame($explicit->id, $tx->created_by);
    }

    public function test_returns_null_creator_when_no_auth_and_no_user_id(): void
    {
        $tx = app(StartOutboundSession::class)->handle();

        $this->assertNull($tx->created_by);
    }
}
