<?php
    echo $this->layout("_theme");
?>

<?php
    $this->start("specific-script");
?>
<script type="module" src="<?= url("assets/js/web/about.js"); ?>" async></script>
<?php
    $this->end();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nós</title>

    <link rel="stylesheet" href="<?= url("assets/css/web/about.css"); ?>">
</head>

<body>

    <div class="sobre-nos">
        <h1>Sobre Nós</h1>

        <p class="paragraph">
            A <strong>Joille</strong> é um sistema de gerenciamento desenvolvido para empresas de pequeno porte
            dos ramos de joalheria e bijuteria.
        </p>

        <p class="paragraph">
            O projeto surgiu da necessidade de um sistema único, capaz de integrar múltiplas funções essenciais
            em uma única plataforma, trazendo organização, agilidade e controle ao negócio.
        </p>

        <img src="<?= url("assets/images/sobreimagemjoia.jpg"); ?>" alt="Imagem da empresa">

        <ul>
            <li><strong>Missão:</strong> Fornecer soluções de alta qualidade.</li>
            <li><strong>Visão:</strong> Ser referência no setor.</li>
            <li><strong>Valores:</strong> Inovação, ética e excelência.</li>
        </ul>
    </div>

</body>
</html>
