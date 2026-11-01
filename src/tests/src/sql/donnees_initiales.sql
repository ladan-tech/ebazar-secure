-- e-bazar — Données initiales (installation MANUELLE uniquement).
-- Compte administrateur unique : admin@ebazar.local / Vert&Bleu-2026!
-- (haché en bcrypt pour compatibilité ; converti automatiquement en Argon2id à la 1re connexion si disponible)
SET NAMES utf8mb4;
INSERT INTO utilisateur (email, mot_de_passe, role) VALUES
  ('admin@ebazar.local', '$2y$10$Y79FvO6gXbmZ2ebScJR.4uNpbPuZgGx0AtJ/T2oHKExwbG6dzlDNy', 'admin');
INSERT INTO categorie (nom) VALUES
  ('Informatique'), ('Maison'), ('Livres'), ('Vêtements'), ('Loisirs'), ('Véhicules');
