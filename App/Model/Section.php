<?php

namespace App\Model;

use Core\Model;

class Section extends Model
{
    protected array $convert = [];
    public function getTable(): string
    {
        return 'sections';
    }
}
