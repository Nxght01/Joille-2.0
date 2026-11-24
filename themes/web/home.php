<?php $this->layout('_theme'); ?>
<?php $this->start('specific-script'); ?>
<link rel="stylesheet" href="<?= url('views/assets/public/styles/agency.css'); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/public/styles/agency.css'); ?>">
<?php $this->end(); ?>
<div class="agency-page">
    <div class="agency-hero">
        <div>
            <h1>Conecte os sistemas, as áreas e as máquinas da sua empresa.</h1>
            <p>A Joille cria soluções de tecnologia para empresas do ramo joalheiro: do projeto da rede física ao desenvolvimento de sistemas que integram sua operação.</p>
            <div class="agency-actions">
                <a class="agency-primary" href="<?= url('services'); ?>">Conhecer as soluções</a>
                <a href="<?= url('contato'); ?>">Conversar sobre meu projeto</a>
            </div>
        </div>
        <img src="<?= url('views/assets/public/images/agency-network.svg'); ?>" alt="Joille conecta produção, estoque, máquinas e gestão por sistemas e redes integrados.">
    </div>
    <div class="agency-offers">
        <article><h2>Redes e infraestrutura</h2><p>Planejamento e implantação da conectividade que liga setores, equipamentos e unidades da sua empresa.</p></article>
        <article><h2>Sistemas de gestão</h2><p>Aplicações web desenvolvidas conforme seus processos de produção, estoque, comercial e administração.</p></article>
        <article><h2>Integração e evolução</h2><p>Conexão entre sistemas e máquinas, com análise de compatibilidade e suporte definido para cada projeto.</p></article>
    </div>
    <div class="agency-process">
        <h2>Do diagnóstico à entrega</h2>
        <ol>
            <li><strong>Conte sua necessidade.</strong> Escolha uma solução e envie um briefing sobre sua empresa.</li>
            <li><strong>Definimos o projeto juntos.</strong> Após a análise técnica, alinhamos escopo, orçamento e prazo.</li>
            <li><strong>Acompanhe a execução.</strong> Consulte as etapas e os retornos da equipe na sua área do cliente.</li>
        </ol>
        <p>Todos os serviços são sob orçamento. Enviar um briefing não confirma a contratação.</p>
        <div class="agency-actions"><a class="agency-primary" href="<?= url('app/solicitacoes'); ?>">Solicitar projeto</a><a href="<?= url('cadastro'); ?>">Criar conta</a></div>
    </div>
</div>
