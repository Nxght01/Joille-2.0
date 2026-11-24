-- Valor total preliminar do projeto. NULL significa ainda não estimado.
ALTER TABLE service_requests
    ADD COLUMN preliminary_value DECIMAL(12,2) NULL DEFAULT NULL
    COMMENT 'Estimativa total do projeto definida pelo administrador';
