<?php

namespace App\Model;

use Core\Model;

class DoctorPatient extends Model
{
    protected array $convert = [];
    public function getTable()
    {
        return 'doctors_patients';
    }

    public function getRules(): array
    {
        return [
            'patient_id' => ['required'],
            'date' => ['required'],
            'day' => ['required'],
            'time' => ['required'],
            'role' => ['patient']
        ];
    }
}