<?php

namespace Source\App;

use League\Plates\Engine;

class Admin
{
    private $view;

    public function __construct()
    {
        $this->view = new Engine(__DIR__ . "/../../themes/adm","php");
    }

    public function home ()
    {
        
        echo $this->view->render("home",[]);
    }

    public function products () {
        echo $this->view->render("products",[]);
    }
    public function editUser () {
        echo $this->view->render("editUser",[]);
    }
    public function editServices () {
        echo $this->view->render("editServices",[]);
    }
    public function editCategory () {
        echo $this->view->render("editCategory",[]);
    }

}