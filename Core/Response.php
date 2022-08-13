<?php

namespace Core;

class Response
{

    public function setStatus(int $code)
    {
        http_response_code($code);
    }

    public function setCookie(array $data)
    {
        setCookie('userdata', serialize($data), time() + 60 * 60 * 24);
    }

    public function unsetCookie(string $name)
    {
        unset($_COOKIE[$name]);
        setcookie($name, null, -1, '/');
    }
}
