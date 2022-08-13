<?php

namespace App\Controller;

use Core\Controller;
use Core\Response;
use Core\Validation;

class AuthController extends Controller
{
    private string $table;
    private $model;

    public function showRegister()
    {
        $this->show('authentication/register');
    }
    public function showLogin()
    {
        $this->show('authentication/login');
    }

    public function register()
    {
        $model = "App\Model\\" . ucfirst($_POST['role']);
        $this->model = new $model;

        $validation = new Validation();
        $validation->setData($_POST);

        $rules = $this->model->getRules();
        $validation->setRules($rules);
        $errors = $validation->validate();

        $username = $_POST['username'];
        $password = $_POST['password'];

        $exists = $this->usernameExists($username);

        //validation
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addMessage('error', $error);
            }
            $this->show('authentication/register');
            exit;
        }

        // check exists
        if ($exists) {
            $this->addMessage('error', 'username already exists!');
            $this->show('authentication/register');
            exit;
        }

        //add user
        if (!$this->addUser($username, $password)) {
            $this->addMessage('error', 'Something went wrong? Please try again!');
            $this->show('authentication/register');
        }

        $newUser = $this->userData($username);
        $this->setSession($newUser->{ "$_POST[role]" . '_id'}, $newUser->username, $newUser->password, $_POST['role']);
        (new Response)->setCookie(['id' => $newUser->{ "$_POST[role]" . '_id'}, 'username' => $newUser->username]);
        $this->addMessage('success', 'registered successfully!');
        header('Location:/dashboard');
    }

    public function login()
    {
        if (!isset($_POST['role'])) {
            $this->addMessage('error', 'Please choose your role!');
            $this->show('authentication/login');
            exit;
        }

        //validation
        $validation = new Validation();
        $validation->setData($_POST);
        $validation->setRules([
            'username' => 'required',
            'password' => 'required',
        ]);
        $errors = $validation->validate();
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addMessage('error', $error);
            }
            $this->show('authentication/register');
            exit;
        }

        $model = "App\Model\\" . ucfirst($_POST['role']);
        $this->model = new $model;

        $username = $_POST['username'];
        $password = md5($_POST['password']);

        $exists = $this->usernameExists($username);

        //check exists
        if (!$exists) {
            $this->addMessage('error', 'username does not exist!');
            $this->show('authentication/login');
            exit;
        }
        $newUser = $this->userData($username);

        // check password
        if ($password !== $newUser->password) {
            $this->addMessage('error', 'password is incorrect!');
            $this->show('authentication/login');
            exit;
        }

        $this->setSession($newUser->{ "$_POST[role]" . '_id'}, $newUser->username, $newUser->password, $_POST['role']);
        (new Response)->setCookie(['id' => $newUser->{ "$_POST[role]" . '_id'}, 'username' => $newUser->username]);
        $this->addMessage('success', 'login successfully!');
        header('Location:/dashboard');


    }

    public function usernameExists(string $username)
    {
        return $this->model->exists('username', "$username");
    }

    public function addUser(string $username, string $password): bool
    {
        if ($this->model->insert(['username' => $username, 'password' => md5($password)])) {
            return true;
        }
        return false;
    }


    public function setSession($id, $username, $password, $role)
    {
        $_SESSION['id'] = $id;
        $_SESSION['username'] = $username;
        $_SESSION['password'] = $password;
        $_SESSION['role'] = $role;
    }

    public function userData(string $username)
    {
        return (new $this->model)->get('`username`', $username);
    }

    public function logout()
    {
        $this->unsetSession();
        (new Response)->unsetCookie('userdata');

        header('location: /login');
    }

    public function unsetSession()
    {
        unset($_SESSION['id']);
        unset($_SESSION['username']);
        unset($_SESSION['password']);
        unset($_SESSION['role']);
    }
}
