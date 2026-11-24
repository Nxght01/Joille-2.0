<?php $this->layout('_theme'); ?>
<?php $this->start('specific-script'); ?>
<link rel="stylesheet" href="<?= url('views/assets/public/styles/agency.css'); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/public/styles/agency.css'); ?>">
<?php $this->end(); ?>
<div class="agency-page">
    <div class="agency-hero">
        <div><h1>Tecnologia para a operação joalheira.</h1>
            <p>A Joille é uma agência de prestação de serviços de tecnologia voltada a empresas do ramo joalheiro. Projetamos e implantamos redes físicas e digitais, criamos sistemas e integramos as informações que circulam entre setores e máquinas.</p>
            <p>Cada empresa tem uma operação diferente. Por isso, começamos pelo diagnóstico: processos, equipamentos, sistemas existentes e objetivos orientam a proposta.</p>
        </div>
        <img src="<?= url('views/assets/public/images/agency-network.svg'); ?>" alt="Integração de produção, estoque, máquinas e gestão.">
    </div>
    <div class="agency-offers">
        <article><h2>Visão integrada</h2><p>Conectar produção, estoque e gestão para que a informação acompanhe o trabalho da empresa.</p></article>
        <article><h2>Implantação planejada</h2><p>Definir requisitos, compatibilidade e etapas antes de intervir na infraestrutura e nos equipamentos.</p></article>
        <article><h2>Evolução contínua</h2><p>Planejar manutenção e melhorias conforme as necessidades do negócio e as condições acordadas.</p></article>
    </div>
    <p>O portal Joille reúne o catálogo e o acompanhamento dos projetos. Os módulos de gestão, integrações e serviços de infraestrutura são definidos e entregues conforme o escopo contratado.</p>
    <div class="agency-actions"><a class="agency-primary" href="<?= url('services'); ?>">Explorar soluções</a><a href="<?= url('contato'); ?>">Falar com a equipe</a></div>
</div>
