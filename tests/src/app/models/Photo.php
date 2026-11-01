<?php
defined('EBAZAR') or exit;

/**
 * Photos d'une annonce. Les fichiers sont stockés dans uploads/ sous un nom
 * aléatoire ; seule la référence est gardée en base.
 *
 * Sécurité des envois :
 *  - type vérifié sur le CONTENU (finfo + getimagesize), jamais sur l'extension ou le type annoncé
 *  - taille ≤ 200 Kio et dimensions ≤ 6000 px (protection « bombe de décompression »)
 *  - image RÉ-ENCODÉE avec GD : supprime les métadonnées EXIF (position GPS du vendeur !)
 *    et neutralise les fichiers polyglottes (JPEG contenant du code)
 *  - nom aléatoire de 128 bits, extension .jpg forcée, exécution de scripts interdite dans uploads/
 */
class Photo extends Model
{
    public const MAX_PHOTOS  = 5;
    public const TAILLE_MAX  = 200 * 1024; // 200 Kio
    public const DIMENSION_MAX = 6000;

    public function deLAnnonce(int $annonceId): array
    {
        return $this->requete('SELECT * FROM photo WHERE annonce_id = ? ORDER BY ordre', [$annonceId])->fetchAll();
    }

    public function fichiersDe(int $annonceId): array
    {
        return array_column($this->deLAnnonce($annonceId), 'fichier');
    }

    public function ajouter(int $annonceId, string $fichier, int $ordre): void
    {
        $this->requete('INSERT INTO photo (annonce_id, fichier, ordre) VALUES (?, ?, ?)', [$annonceId, $fichier, $ordre]);
    }

    public static function effacerFichiers(array $fichiers): void
    {
        foreach ($fichiers as $f) {
            $chemin = UPLOAD_DIR . '/' . basename((string) $f);
            if (is_file($chemin)) {
                @unlink($chemin);
            }
        }
    }

    /**
     * Valide les fichiers envoyés via <input type="file" name="photos[]" multiple>.
     * Renvoie [liste de chemins temporaires valides, liste d'erreurs].
     */
    public static function validerEnvoi(?array $fichiers): array
    {
        $valides = [];
        $erreurs = [];
        if (!$fichiers || !isset($fichiers['name']) || !is_array($fichiers['name'])) {
            return [$valides, $erreurs];
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $nbEnvoyes = 0;
        foreach (array_keys($fichiers['name']) as $i) {
            $err = (int) $fichiers['error'][$i];
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $nbEnvoyes++;
            $nom = (string) $fichiers['name'][$i];
            if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE || $fichiers['size'][$i] > self::TAILLE_MAX) {
                $erreurs[] = "« $nom » dépasse la taille maximale de 200 Kio.";
                continue;
            }
            if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($fichiers['tmp_name'][$i])) {
                $erreurs[] = "« $nom » n'a pas pu être reçu.";
                continue;
            }
            $tmp = $fichiers['tmp_name'][$i];
            $infos = @getimagesize($tmp);
            if ($finfo->file($tmp) !== 'image/jpeg' || !$infos || $infos[2] !== IMAGETYPE_JPEG) {
                $erreurs[] = "« $nom » n'est pas une image JPEG.";
                continue;
            }
            if ($infos[0] > self::DIMENSION_MAX || $infos[1] > self::DIMENSION_MAX) {
                $erreurs[] = "« $nom » est trop grande (6000 × 6000 pixels maximum).";
                continue;
            }
            $valides[] = $tmp;
        }
        if ($nbEnvoyes > self::MAX_PHOTOS) {
            $erreurs[] = 'Vous pouvez déposer au maximum ' . self::MAX_PHOTOS . ' photos.';
        }
        return [$valides, $erreurs];
    }

    /** Enregistre un fichier temporaire validé dans uploads/ ; renvoie le nom généré. */
    public static function enregistrerFichier(string $tmp): string
    {
        $nom = bin2hex(random_bytes(16)) . '.jpg';
        $dest = UPLOAD_DIR . '/' . $nom;

        if (function_exists('imagecreatefromjpeg')) {
            // Ré-encodage : seules les données de pixels sont conservées
            $img = @imagecreatefromjpeg($tmp);
            if ($img === false) {
                throw new RuntimeException('Une des photos est corrompue.');
            }
            $ok = imagejpeg($img, $dest, 85);
            imagedestroy($img);
            if (!$ok) {
                throw new RuntimeException("Impossible d'enregistrer la photo (droits du dossier uploads/ ?).");
            }
        } elseif (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException("Impossible d'enregistrer la photo (droits du dossier uploads/ ?).");
        }
        @chmod($dest, 0644);
        return $nom;
    }
}
