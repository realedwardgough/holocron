<?php

declare(strict_types=1);

namespace Egough\Holocron\Tests\Feature;

use Egough\Holocron\Facades\Holocron;
use Egough\Holocron\Models\HolocronEntry;
use Egough\Holocron\Tests\Fixtures\Models\TestOrder;
use Egough\Holocron\Tests\Fixtures\Models\TestUser;
use Egough\Holocron\Tests\TestCase;

class HolocronEntryScopesTest extends TestCase
{
    public function test_it_filters_entries_by_subject_actor_event_and_category(): void
    {
        $order = TestOrder::create([
            'status' => 'pending',
            'title' => 'Invoice',
        ]);

        $otherOrder = TestOrder::create([
            'status' => 'draft',
            'title' => 'Other Invoice',
        ]);

        $user = TestUser::create(['name' => 'Admin User']);
        $otherUser = TestUser::create(['name' => 'Support User']);

        Holocron::record('invoice_sent')
            ->on($order)
            ->by($user)
            ->category('communication')
            ->message('Invoice sent to customer')
            ->save();

        Holocron::record('manual_note_added')
            ->on($otherOrder)
            ->by($otherUser)
            ->category('notes')
            ->message('Manual note added')
            ->save();

        $entries = HolocronEntry::query()
            ->forSubject($order)
            ->causedBy($user)
            ->event('invoice_sent')
            ->category('communication')
            ->get();

        $this->assertCount(1, $entries);
        $this->assertSame('Invoice sent to customer', $entries->first()->message);
    }
}
