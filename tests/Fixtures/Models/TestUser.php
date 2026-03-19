<?php

declare(strict_types=1);

namespace Egough\Holocron\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}
