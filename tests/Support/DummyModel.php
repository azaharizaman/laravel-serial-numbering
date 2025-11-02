<?php

namespace AzahariZaman\ControlledNumber\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class DummyModel extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    public function getTable()
    {
        return 'dummy_models';
    }
}
