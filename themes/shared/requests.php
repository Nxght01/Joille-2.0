<section class="request-page" data-requests-area="<?= $admin ? 'admin' : 'user'; ?>">
    <h1><?= $admin ? 'Projetos solicitados' : 'Meus projetos'; ?></h1>
    <p><?= $admin ? 'Analise o briefing da empresa, alinhe o orçamento e acompanhe a execução e a entrega.' : 'Conte o que sua empresa precisa integrar. Nossa equipe analisa o escopo e entra em contato para definir orçamento e prazo. Você pode editar ou cancelar enquanto o projeto estiver em análise.'; ?></p>
    <?php if (!$admin): ?>
    <form id="request-form" class="request-form">
        <h2 id="request-form-title">Solicitar projeto</h2>
        <div class="request-field">
            <label for="request-service">Solução desejada</label>
            <select id="request-service" name="services_id" required><option value="">Carregando serviços...</option></select>
        </div>
        <div class="request-field">
            <label for="request-quantity">Unidades da empresa</label>
            <input id="request-quantity" name="quantity" type="number" min="1" max="100" value="1" aria-describedby="units-help" required>
            <small id="units-help">Número de locais ou filiais envolvidos.</small>
        </div>
        <div class="request-field field-full">
            <label for="request-description">Briefing do projeto</label>
            <textarea id="request-description" name="description" minlength="10" maxlength="2000" rows="5" aria-describedby="brief-help" placeholder="Empresa e cidade; áreas a integrar; máquinas e sistemas atuais; objetivo e prazo desejado." required></textarea>
            <small id="brief-help">Ex.: integrar produção, estoque e três máquinas de uma fábrica de joias. Não inclua senhas ou credenciais de acesso.</small>
        </div>
        <p class="field-full">Enviar o briefing não confirma a contratação. Escopo, preço e prazo serão definidos com sua empresa após a análise.</p>
        <div class="request-actions"><button type="submit">Enviar briefing</button><button id="request-reset" type="button" hidden>Cancelar edição</button></div>
    </form>
    <?php endif; ?>
    <div class="request-actions"><h2><?= $admin ? 'Acompanhamento dos projetos' : 'Projetos enviados'; ?></h2><button id="request-reload" type="button">Atualizar lista</button></div>
    <div id="request-list" aria-live="polite"></div>
</section>
