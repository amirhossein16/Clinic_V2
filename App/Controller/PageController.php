<?php

namespace App\Controller;

use Core\Controller;

class PageController extends Controller
{

    public function home()
    {
        $sections = $this->findSections();
        $this->show('home', compact('sections'));
    }

    public function section()
    {
        $sections = $this->findSections();
        $this->show('section', compact('sections'));
    }
}
