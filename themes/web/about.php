<?php
$this->layout("_theme");
?>

<?php $this->start("specific-script"); ?>
<link rel="stylesheet" href="<?= url("views/assets/public/styles/about.css"); ?>">
<?php $this->end(); ?>

<section class="sobre-nos">
    <h1>Sobre Nós</h1>

    <p class="paragraph">
        A <strong>Joille</strong> é um sistema de gerenciamento desenvolvido para empresas de pequeno porte
        dos ramos de joalheria e bijuteria.
    </p>

    <p class="paragraph">
        O projeto surgiu da necessidade de um sistema único, capaz de integrar múltiplas funções essenciais
        em uma única plataforma, trazendo organização, agilidade e controle ao negócio.
    </p>

    <img src="<?= url("views/assets/public/images/sobreimagemjoia.jpg"); ?>"
         alt="Peças de joalheria produzidas com apoio do sistema Joille">

    <ul>
        <li><strong>Missão:</strong> Fornecer soluções de alta qualidade.</li>
        <li><strong>Visão:</strong> Ser referência no setor.</li>
        <li><strong>Valores:</strong> Inovação, ética e excelência.</li>
    </ul>
</section>
