<?php $this->layout('_theme', ['activePage' => 'profile', 'pageTitle' => 'Meu perfil | Joille']); ?>
<?php $this->start('specific-script'); ?>
<script type="module" src="<?= url('views/assets/app/scripts/profile.js'); ?>?v=<?= filemtime(__DIR__ . '/../../views/assets/app/scripts/profile.js'); ?>"></script>
<link rel="stylesheet" href="<?= url('views/assets/_common/styles/requests.css'); ?>">
<?php $this->end(); ?>
<section class="profile-page request-page" data-profile-scope="<?= $profileScope ?? 'user'; ?>">
    <header class="app-page-header">
        <h1>Meu perfil</h1>
        <p>Mantenha seus dados de contato e sua foto atualizados.</p>
    </header>
    <div class="profile-layout">
        <form id="profile" class="request-form profile-details">
            <div class="form-heading"><h2>Dados pessoais</h2><p>Essas informações ajudam no seu atendimento.</p></div>
            <div class="profile-fields">
                <div class="form-group field-full">
                    <label for="name">Nome completo</label>
                    <input type="text" id="name" name="name" autocomplete="name" required>
                </div>
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Telefone</label>
                    <input type="tel" id="phone" name="phone" maxlength="30" autocomplete="tel">
                </div>
                <div class="form-group field-full">
                    <label for="address">Endereço</label>
                    <input type="text" id="address" name="address" maxlength="255" autocomplete="street-address">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit">Salvar alterações</button>
                <a href="<?= url(($profileScope ?? 'user') === 'admin' ? 'admin/redefinir-senha' : 'app/redefinir-senha'); ?>">Alterar minha senha</a>
            </div>
        </form>
        <form id="form-photo" class="request-form profile-photo">
            <div class="form-heading"><h2>Foto de perfil</h2><p>Como você aparece na sua conta.</p></div>
            <img class="profile-avatar" data-profile-photo src="<?= url('views/assets/public/images/avatar-default.svg'); ?>" alt="Foto atual do seu perfil" width="112" height="112">
            <div class="form-group">
                <label for="photo">Escolha uma nova foto</label>
                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png" aria-describedby="photo-help">
                <small id="photo-help">JPG ou PNG, até 4 MB.</small>
            </div>
            <div id="photoPreview" aria-live="polite"></div>
            <button type="submit">Salvar foto</button>
            <p id="photo-status" class="form-success" role="status" hidden></p>
        </form>
    </div>
</section>
