<?php

declare(strict_types=1);

namespace Egough\Holocron\Tests\Fixtures\Models;

use Egough\Holocron\Concerns\HasHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestOrder extends Model
{
    use HasHistory;
    use SoftDeletes;

    protected $table = 'orders';

    protected $guarded = [];

    protected array $holocronTrack = [
        'status',
        'title',
    ];
}
