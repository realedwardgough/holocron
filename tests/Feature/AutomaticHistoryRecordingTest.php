<?php

declare(strict_types=1);

namespace Egough\Holocron\Tests\Feature;

use Egough\Holocron\Tests\Fixtures\Models\TestOrder;
use Egough\Holocron\Tests\Fixtures\Models\TestUserWithHistory;
use Egough\Holocron\Tests\TestCase;

class AutomaticHistoryRecordingTest extends TestCase
{
    public function test_it_records_tracked_model_changes_on_update(): void
    {
        $order = TestOrder::create([
            'status' => 'pending',
            'title' => 'Draft Invoice',
        ]);

        $order->update([
            'status' => 'paid',
            'title' => 'Paid Invoice',
        ]);

        $entries = $order->history()->orderBy('id')->get();

        $this->assertCount(2, $entries);
        $this->assertSame('created', $entries[0]->event);
        $this->assertSame('updated', $entries[1]->event);
        $this->assertSame('pending', $entries[1]->changes['status']['old']);
        $this->assertSame('paid', $entries[1]->changes['status']['new']);
        $this->assertSame('Draft Invoice', $entries[1]->changes['title']['old']);
        $this->assertSame('Paid Invoice', $entries[1]->changes['title']['new']);
        $this->assertSame('Test Order updated', $entries[1]->message);
    }

    public function test_it_skips_empty_update_history_entries(): void
    {
        $order = TestOrder::create([
            'status' => 'pending',
            'title' => 'Draft Invoice',
        ]);

        $order->touch();

        $this->assertCount(1, $order->history()->get());
        $this->assertSame('created', $order->history()->first()->event);
    }

    public function test_it_records_deleted_and_restored_events(): void
    {
        $order = TestOrder::create([
            'status' => 'pending',
            'title' => 'Draft Invoice',
        ]);

        $order->delete();
        $order->restore();

        $entries = $order->history()->orderBy('id')->get();

        $this->assertCount(3, $entries);
        $this->assertSame('created', $entries[0]->event);
        $this->assertSame('deleted', $entries[1]->event);
        $this->assertSame('restored', $entries[2]->event);
        $this->assertSame('Test Order deleted', $entries[1]->message);
        $this->assertSame('Test Order restored', $entries[2]->message);
        $this->assertSame([], $entries[1]->changes ?? []);
        $this->assertSame([], $entries[2]->changes ?? []);
    }

    public function test_it_boots_cleanly_for_models_without_soft_deletes(): void
    {
        $user = TestUserWithHistory::create([
            'name' => 'Initial Name',
        ]);

        $user->update([
            'name' => 'Updated Name',
        ]);

        $entries = $user->history()->orderBy('id')->get();

        $this->assertCount(2, $entries);
        $this->assertSame('created', $entries[0]->event);
        $this->assertSame('updated', $entries[1]->event);
        $this->assertSame('Initial Name', $entries[1]->changes['name']['old']);
        $this->assertSame('Updated Name', $entries[1]->changes['name']['new']);
    }
}
