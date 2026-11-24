<?php
$this->layout("_theme");
?>

<?php $this->start("specific-script"); ?>
<link rel="stylesheet" href="<?= url("views/assets/public/styles/contato.css"); ?>">
<script type="module" src="<?= url("views/assets/public/scripts/contact.js"); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/public/scripts/contact.js'); ?>"></script>
<?php $this->end(); ?>

<div class="container">
    <h2>Entre em Contato</h2>
    <p class="contact-intro">
        Conte quais áreas, máquinas ou sistemas sua empresa deseja integrar. Nossa equipe responderá no e-mail informado.
        Você também receberá uma cópia da sua mensagem.
    </p>

    <?php if (CONF_MAIL_MODE === "capture"): ?>
        <p class="dev-notice">
            <strong>Modo de teste.</strong> A mensagem é gravada no banco e os e-mails
            ficam na <a href="<?= url(CONF_MAIL_CAPTURE_DIR . "/"); ?>" target="_blank"
            rel="noopener">caixa de entrada local</a>, sem sair para a internet.
        </p>
    <?php endif; ?>

    <div id="mensagemStatus" class="message"></div>

    <form id="contactForm" method="POST">
        <!-- Campo honeypot: invisível para pessoas, atrativo para robôs -->
        <input type="checkbox" name="botcheck" class="hidden" style="display: none;" tabindex="-1">

        <div class="form-group">
            <label for="name">Nome:</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="subject">Assunto:</label>
            <input type="text" id="subject" name="subject" placeholder="Ex.: Integração de produção e estoque">
        </div>

        <div class="form-group">
            <label for="message">Mensagem:</label>
            <textarea id="message" name="message" rows="6" required></textarea>
        </div>

        <div class="form-group">
            <input type="submit" value="Enviar Mensagem">
        </div>
    </form>

    <section class="contact-info">
        <h3>Endereço: R. Campos Sáles, 512 - São José, Guaporé - RS, 99200-000</h3>
        <h3>Telefone: <?= CONF_COMPANY_PHONE; ?></h3>
        <h3>Email: joilleifsul@gmail.com</h3>
    </section>

    <div class="iframe">
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3494.966320551029!2d-51.8930819!3d-28.839862699999998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x951da4f851c11a47%3A0x4c3a4f3f4960356e!2sCadena%20Artefatos%20de%20Metais!5e0!3m2!1spt-BR!2sbr!4v1766008272945!5m2!1spt-BR!2sbr"
            width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"
            referrerpolicy="no-referrer-when-downgrade" title="Localização da Joille no mapa"></iframe>
    </div>
</div>
