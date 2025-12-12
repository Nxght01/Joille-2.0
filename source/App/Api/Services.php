<?php

namespace Source\App\Api;
use Source\Core\TokenJWT;
use Source\Models\Service;


// public function index($data): void
// {
//     // Render the services view
//     echo $this->view->render("services", [
//         "title" => "Nossos Serviços"
//     ]);
// }

class Services extends Api
{
    public function __construct()
    {
        parent::__construct();
        // quando todas as rotas da classe são autenticadas, o método $this->auth() pode ser evocado aqui
        // $this->auth();
    }

   
  
    public function insertServices(array $data)
    {
       // $this->auth();


        if (in_array("", $data)) {
            $this->back([
                "type" => "error",
               
            ]);
            return;
        }

        $service = new Service(
            null,
            $data["name"],
            $data["description"],   
            $data["value"], // o valor e a descrição estavam trocados na ordem, o certo é a descrição sobre o valor, e não o contrário.
            $data["categories_id"]
        );

        $insertService = $service->insert();

        if (!$insertService) {
            $this->back([
                "type" => "error",
               
            ]);
            return;
        }

        $this->back([
            "type" => "success",
            "message" => "Serviço cadastrado com sucesso!"
        ]);
    }
    
    public function delete(array $data)
    {
        //$this->auth();
    
        $service = new Service();
        $success = $service->delete($data["id"]);
        
        if(!$success){
            $this->back([
                "type" => "error",
                "message" => $service->getMessage()
            ]);
            return;
        }
    
        $this->back([
            "type" => "success",
            "message" => "Serviço Excluido com sucesso!"
        ]);
    }

    public function listService(array $data)
    {
        
        $service = new Service();
        $listservices = $service->listService($data);
        $this->back($listservices);
    }
    
    public function listById(array $data)
{
    $service = new Service();
    $service = $service->getServiceById($data["id"]);
    $this->back($service);
}
public function updateService(array $data)
{
    //$this->auth();
    $service = new Service(
        $data["id"],
        $data["name"],
        $data["description"],
        $data["value"],
        $data["categories_id"]
    );
    
    $result = $service->updateService(); // Changed variable name to avoid confusion
    if (!$result) {
        $this->back([
            "type" => "error",
            "message" => $service->getMessage() // Changed to use the Service object's message
        ]);
        return;
    }

    $this->back([
        "type" => "success",
        "message" => "Serviço Atualizado com sucesso!"
    ]);
}

}

