<?php
$this->layout("_theme");
?>

<?php $this->start("specific-script"); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Sedan:ital@0;1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url("views/assets/public/styles/home.css"); ?>">
<?php $this->end(); ?>

<!-- `.container` é `display: flex`. Título e texto vão juntos em um wrapper,
     senão viram dois flex items separados e ficam lado a lado, cada um em uma
     coluna estreita. -->
<section class="container">
    <div class="hero-texto">
        <h1>Bem-vindo à Joille!</h1>

        <p class="texto">
            Com nossos serviços você coloca sua empresa em um futuro mais conectado!
        </p>
    </div>

    <img src="<?= url("views/assets/public/images/joiashome.jpg"); ?>" class="image"
         alt="Peças de joalheria em destaque">
</section>

<!-- `container-atuacao` é o cartão que o home.css já estilizava; o HTML usava
     `atuacao`, uma classe que não existia em nenhuma folha -->
<section class="container-atuacao">
    <h3>Área de Atuação</h3>
    <p>
        Nosso sistema foca em EPPs (empresas de pequeno porte) do ramo de joias e semijoias,
        oferecendo um serviço que facilita a organização e a administração da grande carga de
        pedidos feitos por empresas maiores — para que a empresa possa focar em desenvolver
        seus projetos, com a certeza de ter em mãos um serviço que atende às suas necessidades
        de gerenciamento interno a qualquer momento e em qualquer lugar.
    </p>

    <div class="home-cta">
        <a class="home-cta-primary" href="<?= url("services"); ?>">Ver serviços</a>
        <a class="home-cta-secondary" href="<?= url("cadastro"); ?>">Criar conta</a>
    </div>
</section>
