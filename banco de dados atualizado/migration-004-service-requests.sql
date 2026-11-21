-- Execute uma vez, depois das migrations 001 a 003. Preserva os dados existentes.
ALTER TABLE users ADD COLUMN address VARCHAR(255) DEFAULT NULL,
                  ADD COLUMN phone VARCHAR(30) DEFAULT NULL;

CREATE TABLE service_requests (
    id INT NOT NULL AUTO_INCREMENT,
    users_id INT NOT NULL,
    services_id INT NOT NULL,
    description TEXT NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL,
    status ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    admin_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_requests_user (users_id, created_at),
    CONSTRAINT fk_requests_user FOREIGN KEY (users_id) REFERENCES users(id),
    CONSTRAINT fk_requests_service FOREIGN KEY (services_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COMMENT='Solicitacoes dos clientes; preco capturado do catalogo no momento da criacao';
