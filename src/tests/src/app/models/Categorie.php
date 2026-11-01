<?php
defined('EBAZAR') or exit;

class Categorie extends Model
{
    /** Toutes les catégories avec le nombre de biens EN VENTE de chacune. */
    public function toutesAvecCompte(): array
    {
        return $this->requete(
            "SELECT c.id, c.nom, COUNT(a.id) AS nb_biens
             FROM categorie c
             LEFT JOIN annonce a ON a.categorie_id = c.id AND a.statut = 'en_vente'
             GROUP BY c.id, c.nom
             ORDER BY c.nom"
        )->fetchAll();
    }

    public function toutes(): array
    {
        return $this->requete('SELECT id, nom FROM categorie ORDER BY nom')->fetchAll();
    }

    public function trouver(int $id): ?array
    {
        return $this->requete('SELECT * FROM categorie WHERE id = ?', [$id])->fetch() ?: null;
    }

    public function existeNom(string $nom, ?int $saufId = null): bool
    {
        return (bool) $this->requete(
            'SELECT 1 FROM categorie WHERE nom = ? AND id <> ?',
            [$nom, $saufId ?? 0]
        )->fetchColumn();
    }

    public function creer(string $nom): int
    {
        $this->requete('INSERT INTO categorie (nom) VALUES (?)', [$nom]);
        return (int) $this->db->lastInsertId();
    }

    public function renommer(int $id, string $nom): void
    {
        $this->requete('UPDATE categorie SET nom = ? WHERE id = ?', [$nom, $id]);
    }

    /** Règle de validation d'un nom de catégorie ; renvoie un message d'erreur ou null. */
    public function validerNom(string $nom, ?int $saufId = null): ?string
    {
        $l = mb_strlen($nom);
        if ($l < 2 || $l > 50) {
            return 'Le nom de catégorie doit comporter entre 2 et 50 caractères.';
        }
        if ($this->existeNom($nom, $saufId)) {
            return 'Cette catégorie existe déjà.';
        }
        return null;
    }
}
