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

    public function login ()
    {
        echo $this->view->render("login",[]);
    }

    public function products ()
    {
        echo $this->view->render("products",[]);
    }

    public function editUser ()
    {
        echo $this->view->render("editUser",[]);
    }

    public function editServices ()
    {
        echo $this->view->render("editServices",[]);
    }

    public function editCategory ()
    {
        echo $this->view->render("editCategory",[]);
    }

    public function editFaqs ()
    {
        echo $this->view->render("editFaqs",[]);
    }

    /** Mensagens recebidas pelo formulário de contato da área pública. */
    public function contacts ()
    {
        echo $this->view->render("contacts",[]);
    }

}
