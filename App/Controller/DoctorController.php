<?php

namespace App\Controller;

use App\Controller\traits\ControllerTraits;
use App\Model\Doctor;
use App\Model\DoctorPatient;
use Core\Controller;
use Core\Validation;

class DoctorController extends Controller
{

    use ControllerTraits;
    public function doctors()
    {
        $sections = $this->findSections();
        $itemPerPage = 20;
        $currentPage = $_GET['page'] ?? 1;
        $filter = $_GET['filter'] ?? null;
        $where = [
            ['confirm', 'NULL', 'IS NOT'],
            ['confirm', 'denied', '!=', 'AND']
        ];
        $sort = [];

        if (isset($filter['section']) && !empty($filter['section'])) {
            $where[] = ['sections.name', $filter['section'], '='];
        }
        if (isset($filter['education']) && !empty($filter['education'])) {
            $where[] = ['doctors.education', $filter['education'], '='];
        }
        if (isset($filter['sortBy'])) {
            $sortBy = explode(':', $filter['sortBy']);

            $sort[] = [$sortBy[0], $sortBy[1]];
        }

        if (isset($_GET['search'])) {
            $where[] = ["`firstName`", "%$_GET[search]%", 'LIKE', 'AND'];
            $where[] = ["`lastName`", "%$_GET[search]%", 'LIKE', 'OR'];
        }

        $doctors = (new Doctor)->select(
        ['doctors.doctor_id', 'doctors.firstName', 'doctors.lastName', 'doctors.education', 'sections.name'],
            $where,
            $sort,
        [
            ['doctors_sections', 'doctors_sections.doctor_id', 'doctors.doctor_id', 'LEFT'],
            ['sections', 'doctors_sections.section_id', 'sections.section_id', 'LEFT'],
        ]
        );

        $fellowships = (new Doctor)->select(['*'], [['confirm', 'NULL', 'IS NOT'], ['confirm', 'denied', '!=', 'AND']], null, null, null, 'education');

        $doctorsCount = count($doctors);

        $pagination = $this->pagination((array)$doctors, $itemPerPage);

        $doctors = array_slice((array)$doctors, $itemPerPage * ($currentPage - 1), $itemPerPage);

        $this->show('doctors', compact('doctors', 'filter', 'pagination', 'doctorsCount', 'fellowships', 'sections'));
    }

    public function profile()
    {
        $sections = $this->findSections();
        $doctor_id = array_keys($_GET)[0];

        $doctor = (new Doctor)->get('`doctor_id`', (string)$doctor_id);


        $similar = (new Doctor)->select(
        ['*'],
        [
            ['`education`', $doctor->education, '=']
        ],
            null
        );
        $similar = array_slice($similar, 0, 6);

        // remove selected profile from similar doctors
        $similar = array_filter((array)$similar, fn($case) => $case->doctor_id != $doctor->doctor_id);

        $this->show('profile', compact('doctor', 'similar', 'sections'));
    }

    public function getVisitTime()
    {
        $doctor_id = array_keys($_GET)[0];
        $doctorModel = (new Doctor);
        $appointment = (new DoctorPatient);
        $timePerPatient = 10;


        $doctor = $doctorModel->get('`doctor_id`', (string)$doctor_id);

        // validate input parameters for give visit time
        $validation = new Validation();
        $validation->setData($_POST);
        $rules = $appointment->getRules();
        $validation->setRules($rules);
        $errors = $validation->validate();
        if (!empty($errors)) {
            foreach ($errors as $error) {
                foreach ($error as $item) {
                    $this->addMessage('error', $item);
                }
            }
            $this->profile();
            exit;
        }


        // validate date for visit time
        $reqDay = strtolower(date('D', strtotime($_POST['date'])));
        if (!property_exists($doctor, 'visit_time') || empty($doctor->visit_time->$reqDay->{ $_POST['time']})) {
            $this->addMessage('error', 'please select a valid visit time');

            $this->profile();
            exit;
        }


        // check for duplicate requests
        $exists = $appointment->existsAppointment($doctor->doctor_id, $_SESSION['id'], $_POST['time'], $_POST['date']);
        if (!empty($exists)) {
            $this->addMessage('warning', 'your have appointment on this time');

            $this->profile();
            exit;
        }


        // find turn for appointment
        $times = $appointment->betweenDate('date', $_POST['date'], $_POST['date'], 'doctor_id', $doctor->doctor_id);
        $appointNumber = count(array_filter($times, fn($time) => $time->time == $_POST['time'])) + 1;
        $doctorStart = explode('-', $doctor->visit_time->$reqDay->{ $_POST['time']})[0];
        $doctorEnd = explode('-', $doctor->visit_time->$reqDay->{ $_POST['time']})[1];
        $startTime = date("H:i", strtotime($doctorStart) + 60 * $timePerPatient * $appointNumber);
        if (strtotime($startTime) > strtotime($doctorEnd)) {
            $this->addMessage('warning', 'not available in this time period');

            $this->profile();
            exit;
        }


        $appointmentDetail = [
            'doctor_id' => $doctor->doctor_id,
            'patient_id' => $_SESSION['id'],
            'date' => $_POST['date'],
            'time' => $startTime,
            'appointment_number' => $appointNumber
        ];

        if ($appointment->insert($appointmentDetail)) {
            $this->addMessage('success', 'your appointment has been add successfully.');
        }

        $this->profile();
    }
}