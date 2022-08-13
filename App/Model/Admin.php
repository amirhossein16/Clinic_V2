<?php

namespace App\Model;

use Core\Model;

class Admin extends Model
{
    use traits\UsersTraits;
    protected array $convert = [];

    public function getTable()
    {
        return 'admin';
    }
}
