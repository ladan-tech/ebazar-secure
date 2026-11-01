<?php
defined('EBAZAR') or exit;

class Annonce extends Model
{
    public const PAR_PAGE = 10;

    /** Colonnes communes aux listes : annonce + vignette (première photo). */
    private const SELECT_LISTE = "
        SELECT a.*, c.nom AS categorie_nom,
               (SELECT p.fichier FROM photo p WHERE p.annonce_id = a.id ORDER BY p.ordre LIMIT 1) AS vignette
        FROM annonce a
        JOIN categorie c ON c.id = a.categorie_id";

    public function trouver(int $id): ?array
    {
        $a = $this->requete(
            "SELECT a.*, c.nom AS categorie_nom, u.email AS vendeur_email, u.date_inscription AS vendeur_depuis,
                    (SELECT COUNT(*) FROM annonce x WHERE x.vendeur_id = a.vendeur_id AND x.statut = 'en_vente') AS vendeur_nb_annonces,
                    (SELECT p.fichier FROM photo p WHERE p.annonce_id = a.id ORDER BY p.ordre LIMIT 1) AS vignette
             FROM annonce a
             JOIN categorie c ON c.id = a.categorie_id
             JOIN utilisateur u ON u.id = a.vendeur_id
             WHERE a.id = ?",
            [$id]
        )->fetch();
        return $a ?: null;
    }

    /** Les n dernières annonces mises en vente (page d'accueil). */
    public function dernieres(int $n = 4): array
    {
        return $this->requete(
            self::SELECT_LISTE . " WHERE a.statut = 'en_vente' ORDER BY a.date_depot DESC, a.id DESC LIMIT ?",
            [$n]
        )->fetchAll();
    }

    public function compterEnVenteParCategorie(int $categorieId): int
    {
        return (int) $this->requete(
            "SELECT COUNT(*) FROM annonce WHERE categorie_id = ? AND statut = 'en_vente'",
            [$categorieId]
        )->fetchColumn();
    }

    /** Page $page (1..n) des biens en vente d'une catégorie. */
    public function enVenteParCategorie(int $categorieId, int $page): array
    {
        return $this->requete(
            self::SELECT_LISTE . " WHERE a.categorie_id = ? AND a.statut = 'en_vente'
              ORDER BY a.date_depot DESC, a.id DESC LIMIT ? OFFSET ?",
            [$categorieId, self::PAR_PAGE, ($page - 1) * self::PAR_PAGE]
        )->fetchAll();
    }

    /** Biens d'un vendeur selon le statut ('en_vente' ou 'vendue'). */
    public function duVendeur(int $vendeurId, string $statut): array
    {
        return $this->requete(
            self::SELECT_LISTE . ' WHERE a.vendeur_id = ? AND a.statut = ? ORDER BY a.date_depot DESC',
            [$vendeurId, $statut]
        )->fetchAll();
    }

    /** Biens d'un vendeur vendus, avec l'email de l'acheteur. */
    public function vendusParVendeur(int $vendeurId): array
    {
        return $this->requete(
            "SELECT a.*, c.nom AS categorie_nom, ach.email AS acheteur_email,
                    (SELECT p.fichier FROM photo p WHERE p.annonce_id = a.id ORDER BY p.ordre LIMIT 1) AS vignette
             FROM annonce a
             JOIN categorie c ON c.id = a.categorie_id
             LEFT JOIN utilisateur ach ON ach.id = a.acheteur_id
             WHERE a.vendeur_id = ? AND a.statut = 'vendue'
             ORDER BY a.date_vente DESC",
            [$vendeurId]
        )->fetchAll();
    }

    public function acheteesPar(int $acheteurId): array
    {
        return $this->requete(
            "SELECT a.*, c.nom AS categorie_nom, v.email AS vendeur_email,
                    (SELECT p.fichier FROM photo p WHERE p.annonce_id = a.id ORDER BY p.ordre LIMIT 1) AS vignette
             FROM annonce a
             JOIN categorie c ON c.id = a.categorie_id
             JOIN utilisateur v ON v.id = a.vendeur_id
             WHERE a.acheteur_id = ? AND a.statut = 'vendue'
             ORDER BY a.date_vente DESC",
            [$acheteurId]
        )->fetchAll();
    }

    /** Toutes les annonces (vue administrateur). */
    public function toutes(): array
    {
        return $this->requete(
            "SELECT a.id, a.titre, a.prix, a.statut, a.date_depot, c.nom AS categorie_nom, u.email AS vendeur_email
             FROM annonce a
             JOIN categorie c ON c.id = a.categorie_id
             JOIN utilisateur u ON u.id = a.vendeur_id
             ORDER BY a.date_depot DESC"
        )->fetchAll();
    }

    public function idsDuVendeur(int $vendeurId): array
    {
        return array_map('intval', $this->requete('SELECT id FROM annonce WHERE vendeur_id = ?', [$vendeurId])->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Règles métier de validation d'une annonce.
     * Renvoie [données normalisées, erreurs indexées par champ].
     */
    public function valider(array $saisie, array $categoriesValides): array
    {
        $d = [
            'categorie_id'          => (int) ($saisie['categorie_id'] ?? 0),
            'titre'                 => trim((string) ($saisie['titre'] ?? '')),
            'description'           => trim(str_replace("\r\n", "\n", (string) ($saisie['description'] ?? ''))),
            'prix'                  => trim(str_replace([' ', '€'], '', (string) ($saisie['prix'] ?? ''))),
            'livraison_postale'     => !empty($saisie['livraison_postale']),
            'livraison_main_propre' => !empty($saisie['livraison_main_propre']),
        ];
        $err = [];

        if (!in_array($d['categorie_id'], $categoriesValides, true)) {
            $err['categorie_id'] = 'Veuillez choisir une catégorie.';
        }
        $l = mb_strlen($d['titre']);
        if ($l < 5 || $l > 30) {
            $err['titre'] = 'Le titre doit comporter entre 5 et 30 caractères.';
        } elseif (preg_match('/[\r\n]/', $d['titre'])) {
            $err['titre'] = 'Le titre doit tenir sur une seule ligne.';
        }
        $l = mb_strlen($d['description']);
        if ($l < 5 || $l > 200) {
            $err['description'] = 'La description doit comporter entre 5 et 200 caractères.';
        }
        if (!preg_match('/^\d{1,8}([.,]\d{1,2})?$/', $d['prix'])) {
            $err['prix'] = 'Prix invalide : nombre positif avec au plus 2 décimales (0 pour un don).';
        } else {
            $d['prix'] = number_format((float) str_replace(',', '.', $d['prix']), 2, '.', '');
        }
        if (!$d['livraison_postale'] && !$d['livraison_main_propre']) {
            $err['livraison'] = 'Choisissez au moins un mode de livraison.';
        }
        return [$d, $err];
    }

    /** Crée l'annonce et ses photos dans une transaction ; renvoie l'id. */
    public function creer(int $vendeurId, array $d, array $photosTmp): int
    {
        $fichiersEcrits = [];
        $this->db->beginTransaction();
        try {
            $this->requete(
                'INSERT INTO annonce (vendeur_id, categorie_id, titre, description, prix, livraison_postale, livraison_main_propre)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$vendeurId, $d['categorie_id'], $d['titre'], $d['description'], $d['prix'],
                 (int) $d['livraison_postale'], (int) $d['livraison_main_propre']]
            );
            $id = (int) $this->db->lastInsertId();
            $photos = new Photo($this->db);
            foreach (array_values($photosTmp) as $ordre => $tmp) {
                $nom = Photo::enregistrerFichier($tmp);
                $fichiersEcrits[] = $nom;
                $photos->ajouter($id, $nom, $ordre);
            }
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            $this->db->rollBack();
            foreach ($fichiersEcrits as $f) {
                @unlink(UPLOAD_DIR . '/' . $f);
            }
            throw $e;
        }
    }

    /**
     * Achat : passage atomique en_vente -> vendue.
     * La condition statut='en_vente' dans le WHERE empêche deux achats concurrents.
     */
    public function acheter(int $id, int $acheteurId, string $mode): bool
    {
        $colonne = $mode === 'postale' ? 'livraison_postale' : 'livraison_main_propre';
        $n = $this->requete(
            "UPDATE annonce
             SET statut = 'vendue', acheteur_id = ?, mode_livraison = ?, date_vente = NOW()
             WHERE id = ? AND statut = 'en_vente' AND vendeur_id <> ? AND $colonne = 1",
            [$acheteurId, $mode, $id, $acheteurId]
        )->rowCount();
        return $n === 1;
    }

    /** Suppression complète (ligne + photos sur le disque). */
    public function supprimer(int $id): void
    {
        $fichiers = (new Photo($this->db))->fichiersDe($id);
        $this->requete('DELETE FROM annonce WHERE id = ?', [$id]); // photos : ON DELETE CASCADE
        Photo::effacerFichiers($fichiers);
    }

    /* ---------- Recherche ---------- */

    /** Échappe les jokers de LIKE pour que « % » ou « _ » saisis soient littéraux. */
    private static function motifLike(string $q): string
    {
        return '%' . addcslashes($q, '%_\\') . '%';
    }

    public function compterRecherche(string $q): int
    {
        $m = self::motifLike($q);
        return (int) $this->requete(
            "SELECT COUNT(*) FROM annonce a WHERE a.statut = 'en_vente' AND (a.titre LIKE ? OR a.description LIKE ?)",
            [$m, $m]
        )->fetchColumn();
    }

    public function rechercher(string $q, int $page): array
    {
        $m = self::motifLike($q);
        return $this->requete(
            self::SELECT_LISTE . " WHERE a.statut = 'en_vente' AND (a.titre LIKE ? OR a.description LIKE ?)
              ORDER BY a.date_depot DESC, a.id DESC LIMIT ? OFFSET ?",
            [$m, $m, self::PAR_PAGE, ($page - 1) * self::PAR_PAGE]
        )->fetchAll();
    }

    /* ---------- Statistiques ---------- */

    /** Indicateurs du tableau de bord d'un membre. */
    public function statistiquesMembre(int $id): array
    {
        return $this->requete(
            "SELECT
               COALESCE(SUM(vendeur_id = :v1 AND statut = 'en_vente'), 0)                       AS en_vente,
               COALESCE(SUM(CASE WHEN vendeur_id = :v3 AND statut = 'en_vente' THEN prix ELSE 0 END), 0) AS valeur_en_vente,
               COALESCE(SUM(vendeur_id = :v4 AND statut = 'vendue'), 0)                         AS vendus,
               COALESCE(SUM(CASE WHEN vendeur_id = :v5 AND statut = 'vendue' THEN prix ELSE 0 END), 0) AS revenus,
               COALESCE(SUM(acheteur_id = :a1 AND statut = 'vendue'), 0)                        AS achats,
               COALESCE(SUM(CASE WHEN acheteur_id = :a2 AND statut = 'vendue' THEN prix ELSE 0 END), 0) AS depenses
             FROM annonce
             WHERE vendeur_id = :v6 OR acheteur_id = :a3",
            ['v1' => $id, 'v3' => $id, 'v4' => $id, 'v5' => $id, 'v6' => $id, 'a1' => $id, 'a2' => $id, 'a3' => $id]
        )->fetch();
    }

    /** Indicateurs globaux (accueil et administration). */
    public function statistiquesGlobales(): array
    {
        return $this->requete(
            "SELECT COALESCE(SUM(statut = 'en_vente'), 0) AS en_vente,
                    COALESCE(SUM(statut = 'vendue'), 0)   AS vendues,
                    COALESCE(SUM(CASE WHEN statut = 'vendue' THEN prix ELSE 0 END), 0) AS volume
             FROM annonce"
        )->fetch();
    }
}
