-- =========================================================
-- e-bazar — Schéma de la base de données (MySQL / MariaDB)
-- =========================================================
-- Exécuté automatiquement par l'interface d'installation.
-- Utilisable aussi manuellement :
--   mysql -u <user> -p <base> < sql/schema.sql
--   mysql -u <user> -p <base> < sql/donnees_initiales.sql

SET NAMES utf8mb4;

DROP TABLE IF EXISTS journal;
DROP TABLE IF EXISTS photo;
DROP TABLE IF EXISTS annonce;
DROP TABLE IF EXISTS categorie;
DROP TABLE IF EXISTS utilisateur;

CREATE TABLE utilisateur (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email            VARCHAR(190) NOT NULL,
    mot_de_passe     VARCHAR(255) NOT NULL,          -- hachage password_hash()
    role             ENUM('membre','admin') NOT NULL DEFAULT 'membre',
    version_session  INT UNSIGNED NOT NULL DEFAULT 1,   -- incrémenté pour révoquer toutes les sessions
    date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_utilisateur_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categorie (
    id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categorie_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE annonce (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendeur_id            INT UNSIGNED NOT NULL,
    categorie_id          INT UNSIGNED NOT NULL,
    titre                 VARCHAR(30)  NOT NULL,
    description           VARCHAR(200) NOT NULL,
    prix                  DECIMAL(10,2) NOT NULL DEFAULT 0,
    livraison_postale     TINYINT(1) NOT NULL DEFAULT 0,
    livraison_main_propre TINYINT(1) NOT NULL DEFAULT 0,
    statut                ENUM('en_vente','vendue') NOT NULL DEFAULT 'en_vente',
    acheteur_id           INT UNSIGNED NULL,
    mode_livraison        ENUM('postale','main_propre') NULL,
    date_depot            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_vente            DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_annonce_cat_statut (categorie_id, statut, date_depot),
    KEY idx_annonce_vendeur (vendeur_id),
    KEY idx_annonce_acheteur (acheteur_id),
    CONSTRAINT fk_annonce_vendeur   FOREIGN KEY (vendeur_id)   REFERENCES utilisateur(id) ON DELETE CASCADE,
    CONSTRAINT fk_annonce_acheteur  FOREIGN KEY (acheteur_id)  REFERENCES utilisateur(id) ON DELETE SET NULL,
    CONSTRAINT fk_annonce_categorie FOREIGN KEY (categorie_id) REFERENCES categorie(id)   ON DELETE RESTRICT,
    CONSTRAINT ck_annonce_prix      CHECK (prix >= 0),
    CONSTRAINT ck_annonce_livraison CHECK (livraison_postale = 1 OR livraison_main_propre = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE photo (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    annonce_id INT UNSIGNED NOT NULL,
    fichier    VARCHAR(64) NOT NULL,                 -- nom aléatoire, ex. 9f86d0...e1.jpg
    ordre      TINYINT UNSIGNED NOT NULL,           -- 0 = vignette
    PRIMARY KEY (id),
    KEY idx_photo_annonce (annonce_id, ordre),
    CONSTRAINT fk_photo_annonce FOREIGN KEY (annonce_id) REFERENCES annonce(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Journal de sécurité : tentatives de connexion (limitation anti-force brute)
-- et événements d'audit (inscriptions, changements de mot de passe, actions admin…)
CREATE TABLE journal (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    utilisateur_id INT UNSIGNED NULL,
    email          VARCHAR(190) NULL,
    ip             VARCHAR(45)  NOT NULL,
    evenement      VARCHAR(40)  NOT NULL,
    details        VARCHAR(255) NULL,
    date_evt       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_journal_tentatives (evenement, email, ip, date_evt),
    KEY idx_journal_ip (ip, evenement, date_evt),
    KEY idx_journal_utilisateur (utilisateur_id, date_evt),
    CONSTRAINT fk_journal_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
