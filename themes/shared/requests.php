<section class="request-page" data-requests-area="<?= $admin ? 'admin' : 'user'; ?>">
    <h1><?= $admin ? 'Gerenciar solicitações' : 'Minhas solicitações'; ?></h1>
    <p><?= $admin ? 'Acompanhe os pedidos e informe o andamento do atendimento.' : 'Solicite um serviço e acompanhe o atendimento. Você pode editar ou cancelar enquanto estiver pendente.'; ?></p>
    <?php if (!$admin): ?>
    <form id="request-form" class="request-form">
        <h2 id="request-form-title">Nova solicitação</h2>
        <label for="request-service">Serviço</label>
        <select id="request-service" name="services_id" required><option value="">Carregando serviços...</option></select>
        <label for="request-quantity">Quantidade de peças</label>
        <input id="request-quantity" name="quantity" type="number" min="1" max="100" value="1" required>
        <label for="request-description">Descreva o atendimento</label>
        <textarea id="request-description" name="description" minlength="10" maxlength="2000" rows="3" required></textarea>
        <div class="request-actions"><button type="submit">Enviar solicitação</button><button id="request-reset" type="button" hidden>Cancelar edição</button></div>
    </form>
    <?php endif; ?>
    <div class="request-actions"><h2><?= $admin ? 'Atendimentos' : 'Histórico'; ?></h2><button id="request-reload" type="button">Atualizar lista</button></div>
    <div id="request-list" aria-live="polite"></div>
</section>
