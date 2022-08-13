<?php

namespace App\Model;

use Core\Model;

class Doctor extends Model
{
    protected array $convert = [
        'visit_time' => 'array',
        'social' => 'array'
    ];

    use traits\UsersTraits;

    public function getTable()
    {
        return 'doctors';
    }
}
