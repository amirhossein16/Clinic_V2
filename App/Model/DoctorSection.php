<?php

namespace App\Model;

use Core\Model;

class DoctorSection extends Model
{
    protected array $convert = [];
    
    public function getTable()
    {
        return 'doctors_sections';
    }
    
}