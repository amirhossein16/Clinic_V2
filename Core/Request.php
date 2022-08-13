<?php

namespace Core;

class Request
{
    /**
     * Request path
     * 
     * @return string
     */
    public function requestHandler(): string
    {
        $request = $_SERVER['REQUEST_URI'];

        return parse_url($request, PHP_URL_PATH);
    }

    
    /**
     * Find request method
     * 
     * @return string
     */
    public function method(): string
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);

        if ($method == 'get') {
            return $method;
        }
        
        if (isset($_POST['_method']) && $_POST['_method'] == 'put') {
            return 'put';
        } elseif (isset($_POST['_method']) && $_POST['_method'] == 'delete') {
            return 'delete';
        } else {
            return 'post';
        }
    }
}
