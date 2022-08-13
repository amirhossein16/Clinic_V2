<?php

define('root_dir', '/Public');
define('root_url', (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . root_dir);
define('storage_url', root_url . 'storage/');

