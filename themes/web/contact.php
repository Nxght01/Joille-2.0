<?php
$this->layout("_theme");
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contato</title>
    <link rel="stylesheet" href="assets/css/web/contato.css">
    <script src="assets/js/web/contact.js"></script>
</head>

<body>
    <div class="container">
        <h2>Entre em Contato</h2>
        <div id="mensagemStatus" class="message"></div>

        <!-- HTML do Formulário -->
<form id="contactForm" method="POST">
    <input type="hidden" name="access_key" value="69166dc8-d080-4056-b10a-38509353387f">
    <input type="hidden" name="subject" value="Nova mensagem do site">
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
        <label for="message">Mensagem:</label>
        <textarea id="message" name="message" required></textarea>
    </div>
    
    <div class="form-group">
        <input type="submit" value="Enviar Mensagem">
    </div>
</form>

        <h3>Endereço: R. Campos Sáles, 512 - São José, Guaporé - RS, 99200-000</h3>
        <h3>Email: joilleifsul@gmail.com</h3>
        
        <div class="iframe">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3494.966320551029!2d-51.8930819!3d-28.839862699999998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x951da4f851c11a47%3A0x4c3a4f3f4960356e!2sCadena%20Artefatos%20de%20Metais!5e0!3m2!1spt-BR!2sbr!4v1766008272945!5m2!1spt-BR!2sbr" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>
</body>

</html>