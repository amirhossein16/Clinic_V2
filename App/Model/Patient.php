<?php

namespace App\Model;

use Core\Model;

class Patient extends Model
{
    protected array $convert = [
        'visit_time' => 'array'
    ];

    use traits\UsersTraits;
    
    public function getTable()
    {
        return 'patients';
    }

}
