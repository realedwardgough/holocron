<?php

declare(strict_types=1);

namespace Egough\Holocron\Tests\Feature;

use Egough\Holocron\Facades\Holocron;
use Egough\Holocron\Tests\Fixtures\Models\TestOrder;
use Egough\Holocron\Tests\Fixtures\Models\TestUser;
use Egough\Holocron\Tests\TestCase;

class ManualHistoryRecordingTest extends TestCase
{
    public function test_it_records_a_manual_history_entry(): void
    {
        $order = TestOrder::create([
            'status' => 'pending',
            'title' => 'Invoice #1001',
        ]);

        $user = TestUser::create([
            'name' => 'Admin User',
        ]);

        $entry = Holocron::record('status_changed')
            ->on($order)
            ->by($user)
            ->category('orders')
            ->withMeta([
                'from' => 'pending',
                'to' => 'paid',
            ])
            ->withChanges([
                'status' => [
                    'old' => 'pending',
                    'new' => 'paid',
                ],
            ])
            ->message('Order status changed from pending to paid')
            ->save();

        $this->assertSame('status_changed', $entry->event);
        $this->assertSame('orders', $entry->category);
        $this->assertSame('Order status changed from pending to paid', $entry->message);
        $this->assertSame('pending', $entry->changes['status']['old']);
        $this->assertSame('paid', $entry->changes['status']['new']);
        $this->assertSame('paid', $entry->meta['to']);
        $this->assertTrue($entry->actor->is($user));
        $this->assertTrue($entry->subject->is($order));
    }
}
