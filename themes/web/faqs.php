<?php
$this->layout("_theme");
?>

<?php $this->start("specific-script"); ?>
<link rel="stylesheet" href="<?= url("views/assets/public/styles/faqs.css"); ?>">
<script type="module" src="<?= url("views/assets/public/scripts/faqs.js"); ?>"></script>
<?php $this->end(); ?>

<section class="faqs-section" aria-labelledby="faqs-heading">
    <div class="container">
        <header class="faqs-header">
            <h1 id="faqs-heading">Perguntas <mark>frequentes</mark></h1>
            <p>Tire suas dúvidas antes de contratar um de nossos serviços.</p>
        </header>

        <!-- Estados de carregamento / vazio / erro ficam FORA do <dl>, que só
             aceita itens de definição como filhos -->
        <div id="faqs-state" aria-live="polite"></div>

        <!-- As perguntas são carregadas da API pelo faqs.js -->
        <dl id="list-faqs" class="accordion"></dl>
    </div>
</section>
