document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contactForm');
    const mensagemStatus = document.getElementById('mensagemStatus');
    const submitButton = form.querySelector('input[type="submit"]');

    if (!form) {
        console.error('Formulário não encontrado!');
        return;
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Feedback visual
        submitButton.value = 'Enviando...';
        submitButton.disabled = true;
        if (mensagemStatus) {
            mensagemStatus.style.display = 'none';
        }

        try {
            const formData = new FormData(form);
            
            // Remove o botcheck se marcado (proteção anti-spam)
            if (formData.get('botcheck')) {
                throw new Error('Spam detectado');
            }
            formData.delete('botcheck');
            
            const object = Object.fromEntries(formData);
            const json = JSON.stringify(object);

            console.log('Enviando dados:', object);

            const response = await fetch('https://api.web3forms.com/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: json
            });

            const result = await response.json();
            console.log('Resposta da API:', result);

            if (response.ok && result.success) {
                if (mensagemStatus) {
                    mensagemStatus.className = 'message success';
                    mensagemStatus.style.display = 'block';
                    mensagemStatus.textContent = '✓ Mensagem enviada com sucesso! Obrigado pelo contato.';
                }
                form.reset();
                
                // Scroll suave até a mensagem
                mensagemStatus?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                throw new Error(result.message || 'Erro ao enviar');
            }
        } catch (error) {
            console.error('Erro ao enviar:', error);
            if (mensagemStatus) {
                mensagemStatus.className = 'message error';
                mensagemStatus.style.display = 'block';
                mensagemStatus.textContent = '✗ Erro ao enviar mensagem. Por favor, tente novamente.';
            }
        } finally {
            submitButton.value = 'Enviar Mensagem';
            submitButton.disabled = false;
            
            // Remove a mensagem após 5 segundos
            if (mensagemStatus) {
                setTimeout(() => {
                    mensagemStatus.style.display = 'none';
                }, 5000);
            }
        }
    });
});