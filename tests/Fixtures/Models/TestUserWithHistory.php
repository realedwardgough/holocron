<?php

declare(strict_types=1);

namespace Egough\Holocron\Tests\Fixtures\Models;

use Egough\Holocron\Concerns\HasHistory;
use Illuminate\Database\Eloquent\Model;

class TestUserWithHistory extends Model
{
    use HasHistory;

    protected $table = 'users';

    protected $guarded = [];

    protected array $holocronTrack = [
        'name',
    ];
}
