<?php

namespace App\Controller;
use Core\Controller;
use App\Controller\traits\ControllerTraits;
use App\Model\DoctorPatient;
use Core\Validation;
use App\Model\Doctor;
use App\Model\Admin;
use App\Model\DoctorSection;
use App\Model\Section;

class DashboardController extends Controller
{
    use ControllerTraits;

    public function main()
    {
        $this->auth();
        $this->layout = 'dashboard';

        $userdata = $this->{ $_SESSION['role'] . 'Data'}();



        $role = $_SESSION['role'] ?? 'admin';
        $this->show('dashboard/' . $role . 'Main', compact('userdata'));
    }

    public function patientData()
    {
        $date = date("Y-m-d");


        $comingAppointments = (new DoctorPatient)->findAppointments(
        ['doctors_patients.*', 'doctors.firstName as doctorFirstName', 'doctors.lastName as doctorLastName', 'doctors.education as doctorEducation'],
        [
            ['doctors', 'doctors_patients.doctor_id', 'doctors.doctor_id'],
        ],
        ['patient_id', $_SESSION['id']],
        [
            ['date', $date, '>']
        ],
        [
            ['date', 'ASC']
        ]
        );

        $appointments = (new DoctorPatient)->findAppointments(
        ['doctors_patients.*'],
        [
            ['doctors', 'doctors_patients.doctor_id', 'doctors.doctor_id'],
        ],
        ['patient_id', $_SESSION['id']]
        );

        $appointmentsFullCount = count($comingAppointments);
        $reserveAppointments = count($appointments);

        return [
            'appointmentsFullCount' => $appointmentsFullCount,
            'reserveAppointments' => $reserveAppointments,
            'appointments' => $comingAppointments
        ];
    }

    public function doctorData()
    {
        $is_confirm = (new Doctor)->get('doctor_id', $_SESSION['id'])->confirm;

        if (is_null($is_confirm)) {
            $this->addMessage('info', 'Please wait for confirm from admins');
            return [];
        }

        $date = date('Y-m-d');
        $nextWeek = date('Y-m-d', strtotime($date . ' + 7 days'));

        $weekAppointments = (new DoctorPatient)->findAppointments(
        ['doctors_patients.*', 'patients.firstName as patientFirstName', 'patients.lastName as patientLastName', 'doctors.firstName as doctorFirstName', 'doctors.lastName as doctorLastName', 'doctors.education as doctorEducation'],
        [
            ['doctors', 'doctors_patients.doctor_id', 'doctors.doctor_id'],
            ['patients', 'doctors_patients.patient_id', 'patients.patient_id'],

        ],
        ['doctors_patients.doctor_id', $_SESSION['id']],
            null, null,
        ['col' => 'date', 'from' => $date, 'to' => $nextWeek]
        );

        $countAllAppointments = count((new DoctorPatient)->findAppointments(
        ['doctors_patients.*'],
        [
            ['doctors', 'doctors_patients.doctor_id', 'doctors.doctor_id'],
            ['patients', 'doctors_patients.patient_id', 'patients.patient_id'],
        ],
        ['doctors_patients.doctor_id', $_SESSION['id']],
        [['date', $date, '>']]
        ));

        $countWeekAppointments = count($weekAppointments);

        return [
            'weekAppointments' => $weekAppointments,
            'countWeekAppointments' => $countWeekAppointments,
            'countAllAppointments' => $countAllAppointments
        ];
    }

    public function adminData()
    {
        $is_confirm = (new Admin)->get('admin_id', $_SESSION['id'])->confirm;

        if (is_null($is_confirm)) {
            $this->addMessage('info', 'Please wait for confirm from admins');
            return [];
        }

        $doctor = new Doctor();
        $doctorsCount = $doctor->select(
        ['count(*) AS count'],
        [
            ['confirm', 'NULL', 'IS NOT']
        ],
        )[0];

        $sections = new DoctorSection;
        $sections = $sections->select(
        ['sections.*', 'COUNT(*) As sectionDoctorsCount', 'doctors.doctor_id'],
            null,
            null,
        [
            ['sections', '`sections`.`section_id`', '`doctors_sections`.`section_id`', 'RIGHT'],
            ['doctors', '`doctors`.`doctor_id`', '`doctors_sections`.`doctor_id`', 'LEFT'],
        ],
            null,
            'sections.name'
        );

        $sectionsCount = count($sections);

        $appointments = new DoctorPatient;
        $appointmentsCount = $appointments->select(['count(*) AS count'])[0];

        array_map(fn(&$section) => is_null($section->doctor_id) ? $section->sectionDoctorsCount = 0 : null, $sections);

        return [
            'doctorsCount' => $doctorsCount,
            'sections' => $sections,
            'sectionsCount' => $sectionsCount,
            'appointmentsCount' => $appointmentsCount,
        ];

    }

    public function appointment()
    {
        $this->auth();

        $this->layout = 'dashboard';
        $role = $_SESSION['role'] ?? 'admin';

        $itemPerPage = 30;
        $currentPage = $_GET['page'] ?? 1;
        $filter = [];
        $timePerPatient = 10;

        $searchFirstName = isset($_GET['search']) ? ["`firstName`", "%$_GET[search]%", 'LIKE'] : [null, null, null];
        $searchLastName = isset($_GET['search']) ? ["`lastName`", "%$_GET[search]%", 'LIKE'] : [null, null, null];

        $alphabetSort = isset($_GET['alphabet']) ? ["`doctorLastName`", $_GET['alphabet']] : [null, null];
        $timeSort = isset($_GET['time']) ? ["`date`", $_GET['time']] : [null, null];

        $appointments = (new DoctorPatient)->findAppointments(
        ['doctors_patients.*', 'doctors.firstName as doctorFirstName', 'doctors.lastName as doctorLastName', 'doctors.education as doctorEducation'],
        [
            ['doctors', 'doctors_patients.doctor_id', 'doctors.doctor_id'],
        ],
        ['patient_id', $_SESSION['id']],
        [$searchFirstName, $searchLastName],
        [$alphabetSort, $timeSort]
        );

        $appointmentsCount = count($appointments);

        $pagination = $this->pagination((array)$appointmentsCount, $itemPerPage);

        $appointments = array_slice((array)$appointments, $itemPerPage * ($currentPage - 1), $itemPerPage);


        $this->show('dashboard/appointment', compact('appointments', 'appointmentsCount', 'pagination'));
    }

    public function showEditProfile()
    {
        $this->auth();
        $this->layout = 'dashboard';

        $model = "App\Model\\" . ucfirst($_SESSION['role']);
        $this->model = new $model;

        //fetch user data from db
        $userData = $this->model->get($_SESSION['role'] . '_id', $_SESSION['id']);

        $is_confirm = $userData->confirm;

        if (is_null($is_confirm)) {
            $this->main();
        }


        $this->show('dashboard/editProfile', compact('userData'));
    }
    public function showRequests()
    {
        $this->auth();
        $itemPerPage = 10;
        $currentPage = $_GET['page'] ?? 1;

        $unCheckDoctors = (new Doctor)->select(['doctor_id', 'username'], [['confirm', 'NULL', 'IS']]);
        $unCheckAdmins = (new Admin)->select(['admin_id', 'username'], [['confirm', 'NULL', 'IS']]);

        array_map(fn(&$unCheckDoctors) => $unCheckDoctors->role = 'doctor', $unCheckDoctors);
        array_map(fn(&$unCheckAdmins) => $unCheckAdmins->role = 'admin', $unCheckAdmins);
        $unChecks = array_merge($unCheckDoctors, $unCheckAdmins);

        $pagination = $this->pagination((array)$unCheckDoctors, $itemPerPage);
        $unChecks = array_slice((array)$unChecks, $itemPerPage * ($currentPage - 1), $itemPerPage);


        $this->layout = 'dashboard';
        $this->show('dashboard/requests', compact('unChecks', 'pagination'));
    }

    public function requests()
    {
        $this->auth();
        unset($_POST['_method']);

        $model = "App\\Model\\" . ucfirst($_POST['role']);
        $this->model = new $model;

        if ($_POST['confirm'] == 'accept' && !$this->model->updateRow($_POST['ID'], $_POST["role"], ['confirm' => $_SESSION['id']])) {
            $this->addMessage('error', 'something went wrong');
        }
        elseif ($_POST['confirm'] == 'denied' && $this->model->updateRow($_POST['ID'], $_POST['role'], ['confirm' => 'denied'])) {
            $this->addMessage('warning', "$_POST[role]'s request is denied");
        }
        else {
            $this->addMessage('success', "$_POST[role]'s request is accepted");
        }

        $this->showRequests();
    }

    public function checkAccess(string $role, string $id, string $password)
    {
        $model = "App\\Model\\" . ucfirst($role);
        $this->model = new $model;

        $userdata = $this->model->get($role . '_id', $id);

        return $password === $userdata->password ? true : false;
    }

    /**
     * Authentication
     *
     * @return void
     */
    public function auth()
    {
        if (!isset($_SESSION['role']) || !isset($_SESSION['id']) || !isset($_SESSION['password'])) {
            $this->addMessage('error', 'Access denied please first login or register');
            header('Location: /');
            exit;
        }

        if (!$this->checkAccess($_SESSION['role'], $_SESSION['id'], $_SESSION['password'])) {
            $this->addMessage('error', 'Access denied please first login or register');
            header('Location: /');
            exit;
        }
    }

    public function editProfile()
    {
        $this->auth();
        $this->layout = 'dashboard';
        $model = "App\Model\\" . ucfirst($_SESSION['role']);
        $this->model = new $model;

        //fetch user data from db
        $userData = $this->model->get($_SESSION['role'] . '_id', $_SESSION['id']);

        $validation = (new Validation);
        $validateRequired = [];

        foreach ($_POST as $key => $value) {
            if ($key == '_method' || $key == 'field') {
                continue;
            }
            $validateRequired[$key] = $value;
        }

        if (isset($_FILES['file-upload'])) {
            $validateRequired['file'] = $_FILES['file-upload'];
        }

        //validation inputs
        $validation->setData($validateRequired);
        $rules = $this->model->{ $_POST['field'] . 'EditRules'}();
        $validation->setRules($rules);
        $errors = $validation->validate();

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addMessage('error', $error);
            }

            $this->show('dashboard/editProfile', compact('userData'));
            exit;
        }

        if ($_POST['field'] == 'account') {
            unset($validateRequired['confirmPassword']);
            $validateRequired['password'] = md5($validateRequired['password']);
        }

        if ($_POST['field'] == 'education') {
            $validateRequired['visit_time'] = json_encode($_POST['visit_time']);
        }

        if ($_POST['field'] == 'personal' && !empty($_POST['social'])) {
            $validateRequired['social'] = json_encode($_POST['social']);
            $avatarAddress = $this->uploadFile('avatars', $_SESSION['id']);

            if ($avatarAddress) {
                unset($validateRequired['file']);
                $validateRequired['avatar'] = $avatarAddress;
            }
        }

        if (!$this->model->updateRow($userData->{ $_SESSION['role'] . '_id'}, $_SESSION['role'], $validateRequired)) {
            $this->addMessage('error', 'something went wrong! Please try again.');

            $this->show('dashboard/editProfile', compact('userData'));
            exit;
        }

        $this->addMessage('success', 'data successfully updated');
        $this->showEditProfile();

    }

    public function addSection()
    {
        $this->auth();
        $section = new Section;

        if (empty($_POST['sectionName'])) {
            $this->addMessage('error', 'Please enter a section Name');
            $this->main();
            exit;
        }

        $exists = $section->exists('name', $_POST['sectionName']);
        if ($exists) {
            $this->addMessage('error', 'Name already exists');
            $this->main();
            exit;
        }

        if (!$section->insert(['name' => $_POST['sectionName']])) {
            $this->addMessage('error', 'something went wrong');
        }

        $this->addMessage('success', 'section add successfully');
        $this->main();
    }

    public function deleteSection()
    {
        $this->auth();
        $section = new Section;
        $doctorsSections = new DoctorSection;

        if (empty($_POST['sectionName'])) {
            $this->addMessage('error', 'Please enter a section Name');
            $this->main();
            exit;
        }

        $exists = $section->get('name', $_POST['sectionName']);
        if (empty($exists)) {
            $this->addMessage('error', 'Please enter correct section Name');
            $this->main();
            exit;
        }

        if (!$doctorsSections->delete([['section_id', $exists->section_id, '=']])) {
            $this->addMessage('error', 'something went wrong');
        }
        else {
            if (!$section->delete([['section_id', $exists->section_id, '=']])) {
                $this->addMessage('error', 'something went wrong');
            }
            else {
                $this->addMessage('success', 'section deleted successfully');
            }
        }

        $this->main();
    }

    public function editSection()
    {
        $this->auth();
        $section = new Section;

        if (empty($_POST['sectionName'])) {
            $this->addMessage('error', 'Please enter a section Name');
            $this->main();
            exit;
        }

        $exists = $section->exists('name', $_POST['sectionName']);
        if ($exists) {
            $this->addMessage('error', 'name already exists');
            $this->main();
            exit;
        }

        if (!$section->updateRow($_POST['sectionID'], 'section', ['name' => $_POST['sectionName']])) {
            $this->addMessage('error', 'something went wrong');
        }
        else {
            $this->addMessage('success', 'section name edited successfully');
        }


        $this->main();
    }

    public function sectionDetails()
    {
        $this->auth();
        $this->layout = 'dashboard';

        $doctorSection = new DoctorSection;
        $section = $doctorSection->select(
        ['sections.*', 'doctors.doctor_id', 'doctors.firstName', 'doctors.lastName', 'doctors.education'],
        [
            ['sections.section_id', array_keys($_GET)[0], '=']
        ],
            null,
        [
            ['sections', '`sections`.`section_id`', '`doctors_sections`.`section_id`', 'INNER'],
            ['doctors', '`doctors`.`doctor_id`', '`doctors_sections`.`doctor_id`', 'INNER'],
        ]
        );

        $available = $doctorSection->select(
        ['doctors.doctor_id', 'doctors.firstName', 'doctors.lastName', 'doctors.education'],
        [
            ['doctors_sections.doctor_id', 'NULL', 'IS']
        ],
            null,
        [
            ['doctors', '`doctors`.`doctor_id`', '`doctors_sections`.`doctor_id`', 'RIGHT'],
        ]
        );

        $this->show('dashboard/sectionDetails', compact('section', 'available'));
    }

    public function deleteDoctorFromSection()
    {
        $this->auth();
        $doctorsSections = new DoctorSection;

        if (!$doctorsSections->delete(
        [
        ['section_id', array_keys($_GET)[0], '='],
        ['doctor_id', $_POST['doctorID'], '=']
        ])) {
            $this->addMessage('error', 'something went wrong');
        }
        else {
            $this->addMessage('success', 'doctor removed from section successfully');
        }

        $this->sectionDetails();
    }

    public function addDoctorToSection()
    {
        $this->auth();
        $doctorsSections = new DoctorSection;

        if (!$doctorsSections->insert(
        [
        'section_id' => array_keys($_GET)[0],
        'doctor_id' => $_POST['doctorID']
        ]
        )) {
            $this->addMessage('error', 'something went wrong');
        }
        else {
            $this->addMessage('success', 'doctor removed from section successfully');
        }

        $this->sectionDetails();
    }
}