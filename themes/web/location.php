<?php
$this->layout("_theme");
?>

<?php $this->start("specific-script"); ?>
<link rel="stylesheet" href="<?= url("views/assets/public/styles/location.css"); ?>">
<?php $this->end(); ?>

<section class="location-section" aria-labelledby="location-heading">
    <div class="container">
        <header class="location-header">
            <h1 id="location-heading">Onde <mark>nos encontrar</mark></h1>
            <p>Reuniões de diagnóstico com hora marcada. Visitas técnicas e implantação presencial são combinadas conforme o projeto.</p>
        </header>

        <div class="location-grid">
            <address class="location-card">
                <h2>Endereço</h2>
                <p>
                    R. Campos Sáles, 512 — São José<br>
                    Guaporé/RS, 99200-000
                </p>

                <h2>Contato</h2>
                <p>
                    Telefone: <a href="tel:+555130000000"><?= CONF_COMPANY_PHONE; ?></a><br>
                    E-mail: <a href="mailto:joilleifsul@gmail.com">joilleifsul@gmail.com</a>
                </p>

                <h2>Horários</h2>
                <p>
                    Segunda a sexta: 9h às 18h<br>
                    Sábado: 9h às 12h<br>
                    Domingo e feriados: fechado
                </p>

                <a class="location-cta" href="<?= url("contato"); ?>">Enviar uma mensagem</a>
            </address>

            <div class="location-map">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3494.966320551029!2d-51.8930819!3d-28.839862699999998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x951da4f851c11a47%3A0x4c3a4f3f4960356e!2sCadena%20Artefatos%20de%20Metais!5e0!3m2!1spt-BR!2sbr!4v1766008272945!5m2!1spt-BR!2sbr"
                    style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Mapa com a localização da Joille"></iframe>
            </div>
        </div>
    </div>
</section>
