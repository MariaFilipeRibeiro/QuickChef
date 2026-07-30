DROP DATABASE `quickchef`;
CREATE DATABASE IF NOT EXISTS `quickchef`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `quickchef`;

CREATE TABLE IF NOT EXISTS `utilizadores` (
    `id`              INT          NOT NULL AUTO_INCREMENT,
    `nome`            VARCHAR(100) NOT NULL,
    `email`           VARCHAR(150) NOT NULL,
    `password_hash`   VARCHAR(255) NOT NULL,
    `role`            ENUM('user','admin') NOT NULL DEFAULT 'user',
    `meta_calorica`   INT          NULL     DEFAULT 700,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `perfil_utilizador` (
    `user_id`          INT     NOT NULL,
    `sem_gluten`       BOOLEAN NOT NULL DEFAULT FALSE,
    `sem_lactose`      BOOLEAN NOT NULL DEFAULT FALSE,
    `vegetariano`      BOOLEAN NOT NULL DEFAULT FALSE,
    `vegan`            BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_perfil_user` FOREIGN KEY (`user_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `refeicoes` (
    `id`              INT            NOT NULL AUTO_INCREMENT,
    `nome`            VARCHAR(200)   NOT NULL,
    `tipo`            ENUM('sopa','prato_principal','sobremesa') NOT NULL,
    `descricao`       TEXT           NULL,
    `ingredientes`    TEXT           NULL,
    `calorias`        DECIMAL(7,2)   NOT NULL DEFAULT 0.00,
    `proteinas`       DECIMAL(7,2)   NOT NULL DEFAULT 0.00,
    `hidratos`        DECIMAL(7,2)   NOT NULL DEFAULT 0.00,
    `lipidos`         DECIMAL(7,2)   NOT NULL DEFAULT 0.00,
    `fibra`           DECIMAL(7,2)   NOT NULL DEFAULT 0.00,
    `sem_gluten`      BOOLEAN        NOT NULL DEFAULT FALSE,
    `sem_lactose`     BOOLEAN        NOT NULL DEFAULT FALSE,
    `vegetariano`     BOOLEAN        NOT NULL DEFAULT FALSE,
    `vegan`           BOOLEAN        NOT NULL DEFAULT FALSE,
    `rating_media`    DECIMAL(3,2)   NULL,
    `rating_total`    INT            NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `ementa_diaria` (
    `id`           INT     NOT NULL AUTO_INCREMENT,
    `refeicao_id`  INT     NOT NULL,
    `dia_semana`   TINYINT NOT NULL,
    `data`         DATE    NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ementa_dia_refeicao` (`data`, `refeicao_id`),
    CONSTRAINT `fk_ementa_refeicao` FOREIGN KEY (`refeicao_id`) REFERENCES `refeicoes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_dia_semana` CHECK (`dia_semana` BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `marcacoes` (
    `id`           INT       NOT NULL AUTO_INCREMENT,
    `user_id`      INT       NOT NULL,
    `data`         DATE      NOT NULL,
    `sopa_id`      INT       NULL,
    `prato_id`     INT       NULL,
    `sobremesa_id` INT       NULL,
    `total_kcal`   DECIMAL(7,2) NOT NULL DEFAULT 0.00,
    `criado_em`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em`TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_marcacao_user_data` (`user_id`, `data`),
    CONSTRAINT `fk_marc_user`      FOREIGN KEY (`user_id`)      REFERENCES `utilizadores` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_marc_sopa`      FOREIGN KEY (`sopa_id`)      REFERENCES `refeicoes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_marc_prato`     FOREIGN KEY (`prato_id`)     REFERENCES `refeicoes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_marc_sobremesa` FOREIGN KEY (`sobremesa_id`) REFERENCES `refeicoes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `avaliacoes` (
    `id`           INT       NOT NULL AUTO_INCREMENT,
    `refeicao_id`  INT       NOT NULL,
    `user_id`      INT       NOT NULL,
    `nota`         TINYINT   NOT NULL                        COMMENT '1= Mau  2= Ok  3= Bom',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_avaliacao_user_refeicao` (`user_id`, `refeicao_id`) COMMENT 'Cada utilizador só avalia uma vez por refeição',
    CONSTRAINT `fk_aval_refeicao` FOREIGN KEY (`refeicao_id`) REFERENCES `refeicoes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_aval_user`     FOREIGN KEY (`user_id`)     REFERENCES `utilizadores` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_nota`         CHECK (`nota` BETWEEN 1 AND 3)
) ENGINE=InnoDB;

DELIMITER $$

CREATE TRIGGER `trg_aval_insert` AFTER INSERT ON `avaliacoes` FOR EACH ROW
BEGIN
    UPDATE `refeicoes` SET
        `rating_media` = (SELECT AVG(nota)   FROM `avaliacoes` WHERE refeicao_id = NEW.refeicao_id),
        `rating_total` = (SELECT COUNT(*)    FROM `avaliacoes` WHERE refeicao_id = NEW.refeicao_id)
    WHERE `id` = NEW.refeicao_id;
END$$

CREATE TRIGGER `trg_aval_update` AFTER UPDATE ON `avaliacoes` FOR EACH ROW
BEGIN
    UPDATE `refeicoes` SET
        `rating_media` = (SELECT AVG(nota)   FROM `avaliacoes` WHERE refeicao_id = NEW.refeicao_id),
        `rating_total` = (SELECT COUNT(*)    FROM `avaliacoes` WHERE refeicao_id = NEW.refeicao_id)
    WHERE `id` = NEW.refeicao_id;
END$$

CREATE TRIGGER `trg_aval_delete` AFTER DELETE ON `avaliacoes` FOR EACH ROW
BEGIN
    UPDATE `refeicoes` SET
        `rating_media` = (SELECT AVG(nota)   FROM `avaliacoes` WHERE refeicao_id = OLD.refeicao_id),
        `rating_total` = (SELECT COUNT(*)    FROM `avaliacoes` WHERE refeicao_id = OLD.refeicao_id)
    WHERE `id` = OLD.refeicao_id;
END$$

DELIMITER ;

-- Vista para o admin: refeições ordenadas por popularidade
CREATE OR REPLACE VIEW `v_popularidade_refeicoes` AS
SELECT
    r.id,
    r.nome,
    r.tipo,
    r.rating_media,
    r.rating_total,
    SUM(CASE WHEN a.nota = 3 THEN 1 ELSE 0 END) AS total_bom,
    SUM(CASE WHEN a.nota = 2 THEN 1 ELSE 0 END) AS total_ok,
    SUM(CASE WHEN a.nota = 1 THEN 1 ELSE 0 END) AS total_mau
FROM `refeicoes` r
LEFT JOIN `avaliacoes` a ON a.refeicao_id = r.id
GROUP BY r.id, r.nome, r.tipo, r.rating_media, r.rating_total
ORDER BY r.rating_media DESC, r.rating_total DESC;

-- Vista para o dashboard: ementa da semana atual
CREATE OR REPLACE VIEW `v_ementa_semana_atual` AS
SELECT
    e.data,
    e.dia_semana,
    r.id          AS refeicao_id,
    r.nome,
    r.tipo,
    r.descricao,
    r.calorias,
    r.proteinas,
    r.hidratos,
    r.lipidos,
    r.fibra,
    r.sem_gluten,
    r.sem_lactose,
    r.vegetariano,
    r.vegan,
    r.rating_media,
    r.rating_total
FROM `ementa_diaria` e
JOIN `refeicoes` r ON r.id = e.refeicao_id
WHERE e.data BETWEEN DATE(NOW() - INTERVAL WEEKDAY(NOW()) DAY)
                 AND DATE(NOW() - INTERVAL WEEKDAY(NOW()) DAY + INTERVAL 4 DAY)
ORDER BY e.data, r.tipo;

-- Refeições da cantina
INSERT INTO `refeicoes` (`id`, `nome`, `tipo`, `descricao`, `ingredientes`, `calorias`, `proteinas`, `hidratos`, `lipidos`, `fibra`, `sem_gluten`, `sem_lactose`, `vegetariano`, `vegan`) VALUES
-- Sopas
(1,  'Sopa de Legumes',          'sopa',           'Sopa simples de legumes da época.',                    'Cenoura, cebola, batata, azeite',                   85.00,  2.00, 15.00,  2.00, 3.00, TRUE,  TRUE,  TRUE,  TRUE),
(2,  'Caldo Verde',              'sopa',           'Sopa tradicional portuguesa com couve e chouriço.',    'Batata, couve galega, chouriço, azeite',            120.00,  4.00, 16.00,  4.50, 2.50, TRUE,  TRUE,  FALSE, FALSE),
(3,  'Sopa de Tomate',           'sopa',           'Sopa de tomate fresco com um fio de azeite.',          'Tomate, cebola, alho, azeite',                       70.00,  1.50, 12.00,  2.00, 2.00, TRUE,  TRUE,  TRUE,  TRUE),
-- Pratos principais
(4,  'Frango com Arroz',         'prato_principal','Peito de frango grelhado com arroz basmati.',          'Peito de frango, arroz basmati, azeite, alho',      465.00, 49.00, 28.00,  6.00, 0.50, TRUE,  TRUE,  FALSE, FALSE),
(5,  'Bacalhau com Grão',        'prato_principal','Bacalhau cozido tradicional com grão de bico.',        'Bacalhau, grão de bico, cebola, ovo, azeite',       410.00, 49.00, 40.00,  5.00, 7.60, TRUE,  TRUE,  FALSE, FALSE),
(6,  'Bolonhesa com Esparguete', 'prato_principal','Carne picada estufada com massa esparguete.',          'Carne de vaca picada, esparguete, cebola, tomate',  550.00, 45.00, 35.00, 18.00, 1.80, FALSE, TRUE,  FALSE, FALSE),
(7,  'Salmão no Forno',          'prato_principal','Lombo de salmão assado com legumes.',                  'Lombo de salmão, brócolos, azeite, limão',          410.00, 34.00, 10.00, 21.00, 2.60, TRUE,  TRUE,  FALSE, FALSE),
(8,  'Frango com Natas',         'prato_principal','Frango estufado com natas e cogumelos.',               'Frango, natas, cogumelos, cebola',                  540.00, 48.00,  4.00, 35.00, 0.80, TRUE,  FALSE, FALSE, FALSE),
(9,  'Arroz de Atum',            'prato_principal','Atum de lata com arroz basmati e cebola.',             'Atum (lata), arroz basmati, cebola, azeite',        270.00, 34.00, 28.00,  1.50, 0.40, TRUE,  TRUE,  FALSE, FALSE),
(10, 'Bacalhau no Forno',        'prato_principal','Bacalhau desfiado no forno com espinafres.',           'Bacalhau, espinafres, azeite, alho',                150.00, 30.00,  4.00,  2.00, 2.20, TRUE,  TRUE,  FALSE, FALSE),
-- Vegetarianos / Vegan
(11, 'Tofu com Brócolos',        'prato_principal',    'Tofu salteado com brócolos frescos e azeite.',         'Tofu, brócolos, azeite, alho',                      155.00, 15.00, 10.00,  8.00, 2.60, TRUE,  TRUE,  TRUE,  TRUE),
(12, 'Estufado de Lentilhas',    'prato_principal',    'Lentilhas castanhas estufadas com quinoa.',            'Lentilhas, quinoa, cebola, azeite',                 212.00, 13.00, 37.00,  2.00, 7.90, TRUE,  TRUE,  TRUE,  TRUE),
(13, 'Caril de Grão de Bico',    'prato_principal',          'Grão de bico com molho de caril suave.',               'Grão de bico, tomate, cebola, caril, azeite',       350.00, 18.00, 58.00,  6.00, 7.60, TRUE,  TRUE,  TRUE,  TRUE),
(14, 'Omelete de Espinafres',    'prato_principal',    'Omelete de ovos frescos com espinafres e queijo.',     'Ovos, espinafres, queijo mozzarela',                420.00, 33.00,  5.00, 30.00, 2.20, TRUE,  FALSE, TRUE,  FALSE),
(15, 'Massa de Milho com Tofu',  'prato_principal',          'Massa sem glúten com tofu e molho de tomate.',         'Massa de milho, tofu, tomate, alho, azeite',        426.00, 15.00, 77.00,  6.30, 2.00, FALSE, TRUE,  TRUE,  TRUE),
-- Sobremesas
(16, 'Fruta da Época',           'sobremesa',      'Fruta fresca da época.',                               'Fruta variada',                                      60.00,  0.50, 14.00,  0.20, 2.00, TRUE,  TRUE,  TRUE,  TRUE),
(17, 'Iogurte Natural',          'sobremesa',      'Iogurte natural sem açúcar.',                          'Leite, fermentos lácticos',                          59.00,  3.50,  3.60,  3.30, 0.00, TRUE,  FALSE, TRUE,  FALSE),
-- Pratos principais com mais de 700kcal
(18, 'Lasanha de Carne',      'prato_principal', 'Lasanha tradicional com carne picada e bechamel.',   'Massa, carne picada, bechamel, queijo, tomate',        850.00, 42.00, 65.00, 38.00, 2.00, FALSE, FALSE, FALSE, FALSE),
(19, 'Feijoada à Portuguesa', 'prato_principal', 'Feijoada rica com enchidos e carne de porco.',       'Feijão, chouriço, farinheira, carne de porco, arroz',  920.00, 55.00, 72.00, 35.00, 8.00, TRUE,  FALSE, FALSE, FALSE),
(20, 'Empadão de Carne',      'prato_principal', 'Empadão de carne picada com puré de batata.',        'Carne picada, batata, manteiga, leite, cebola',        780.00, 38.00, 68.00, 32.00, 3.00, TRUE,  FALSE, FALSE, FALSE),
(21, 'Arroz de Cabidela',     'prato_principal', 'Arroz de frango com sangue e vinagre.',              'Frango, arroz, sangue, cebola, vinagre',               760.00, 48.00, 62.00, 28.00, 1.50, TRUE,  TRUE,  FALSE, FALSE),
(22, 'Francesinha',           'prato_principal', 'Francesinha com molho especial e batata frita.',     'Pão, bife, fiambre, chouriço, queijo, molho, batata', 1100.00, 58.00, 82.00, 52.00, 3.00, FALSE, FALSE, FALSE, FALSE),
-- Pratos principais entre 550kcal e 690kcal
(23, 'Bife com Batatas Fritas',    'prato_principal', 'Bife de vaca grelhado com batatas fritas.',          'Bife de vaca, batata, azeite, alho',                  650.00, 52.00, 48.00, 28.00, 3.00, TRUE,  TRUE,  FALSE, FALSE),
(24, 'Bacalhau à Brás',            'prato_principal', 'Bacalhau desfiado com ovos, batata palha e azeitonas.','Bacalhau, ovos, batata palha, azeitonas, cebola',    620.00, 45.00, 38.00, 30.00, 1.50, TRUE,  TRUE,  FALSE, FALSE),
(25, 'Perna de Frango Assada',     'prato_principal', 'Perna de frango assada com arroz de legumes.',       'Perna de frango, arroz, cenoura, cebola, azeite',     580.00, 54.00, 42.00, 22.00, 2.00, TRUE,  TRUE,  FALSE, FALSE),
(26, 'Massa com Atum e Tomate',    'prato_principal', 'Massa esparguete com atum e molho de tomate caseiro.','Esparguete, atum, tomate, alho, azeite',             560.00, 38.00, 72.00, 12.00, 3.50, FALSE, TRUE,  FALSE, FALSE),
(27, 'Risotto de Cogumelos',       'prato_principal',     'Risotto cremoso de cogumelos variados.',              'Arroz arbóreo, cogumelos, cebola, vinho branco, queijo', 670.00, 18.00, 82.00, 22.00, 2.50, TRUE,  FALSE, TRUE,  FALSE);

INSERT INTO `ementa_diaria` (`refeicao_id`, `dia_semana`, `data`) VALUES
-- SEMANA: 22 a 26 de Junho 2026
-- Segunda
(1,  1, '2026-06-22'), (2,  1, '2026-06-22'), (7,  1, '2026-06-22'), (19, 1, '2026-06-22'), (23, 1, '2026-06-22'), (27, 1, '2026-06-22'),
-- Terça
(2,  2, '2026-06-23'), (3,  2, '2026-06-23'), (10, 2, '2026-06-23'), (17, 2, '2026-06-23'), (24, 2, '2026-06-23'), (26, 2, '2026-06-23'),
-- Quarta
(1,  3, '2026-06-24'), (3,  3, '2026-06-24'), (9,  3, '2026-06-24'), (16, 3, '2026-06-24'), (22, 3, '2026-06-24'), (27, 3, '2026-06-24'),
-- Quinta
(1,  4, '2026-06-25'), (2,  4, '2026-06-25'), (11, 4, '2026-06-25'), (18, 4, '2026-06-25'), (21, 4, '2026-06-25'), (26, 4, '2026-06-25'),
-- Sexta
(2,  5, '2026-06-26'), (3,  5, '2026-06-26'), (13, 5, '2026-06-26'), (20, 5, '2026-06-26'), (25, 5, '2026-06-26'), (27, 5, '2026-06-26'),

-- SEMANA: 29 de Junho a 3 de Julho 2026
-- Segunda
(1,  1, '2026-06-29'), (3,  1, '2026-06-29'), (13, 1, '2026-06-29'), (19, 1, '2026-06-29'), (23, 1, '2026-06-29'), (27, 1, '2026-06-29'),
-- Terça
(1,  2, '2026-06-30'), (2,  2, '2026-06-30'), (10, 2, '2026-06-30'), (17, 2, '2026-06-30'), (24, 2, '2026-06-30'), (26, 2, '2026-06-30'),
-- Quarta
(2,  3, '2026-07-01'), (3,  3, '2026-07-01'), (9,  3, '2026-07-01'), (16, 3, '2026-07-01'), (22, 3, '2026-07-01'), (27, 3, '2026-07-01'),
-- Quinta
(1,  4, '2026-07-02'), (3,  4, '2026-07-02'), (11, 4, '2026-07-02'), (18, 4, '2026-07-02'), (21, 4, '2026-07-02'), (26, 4, '2026-07-02'),
-- Sexta
(2,  5, '2026-07-03'), (3,  5, '2026-07-03'), (13, 5, '2026-07-03'), (20, 5, '2026-07-03'), (25, 5, '2026-07-03'), (27, 5, '2026-07-03'),

-- SEMANA: 6 a 10 de Julho 2026
-- Segunda
(1,  1, '2026-07-06'), (2,  1, '2026-07-06'), (4,  1, '2026-07-06'), (16, 1, '2026-07-06'), (21, 1, '2026-07-06'), (26, 1, '2026-07-06'),
-- Terça
(2,  2, '2026-07-07'), (3,  2, '2026-07-07'), (5,  2, '2026-07-07'), (20, 2, '2026-07-07'), (22, 2, '2026-07-07'), (27, 2, '2026-07-07'),
-- Quarta
(1,  3, '2026-07-08'), (3,  3, '2026-07-08'), (7,  3, '2026-07-08'), (17, 3, '2026-07-08'), (24, 3, '2026-07-08'), (26, 3, '2026-07-08'),
-- Quinta
(1,  4, '2026-07-09'), (2,  4, '2026-07-09'), (12, 4, '2026-07-09'), (19, 4, '2026-07-09'), (23, 4, '2026-07-09'), (27, 4, '2026-07-09'),
-- Sexta
(2,  5, '2026-07-10'), (3,  5, '2026-07-10'), (13, 5, '2026-07-10'), (18, 5, '2026-07-10'), (25, 5, '2026-07-10'), (26, 5, '2026-07-10');