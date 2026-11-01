<?php
defined('EBAZAR') or exit;

/* ---------- Solutions de secours si l'extension mbstring est absente ---------- */
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $texte): int
    {
        return (int) preg_match_all('/./us', $texte);
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr(string $texte, int $debut, ?int $longueur = null): string
    {
        preg_match_all('/./us', $texte, $m);
        return implode('', array_slice($m[0], $debut, $longueur));
    }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $texte): string
    {
        return strtolower($texte);
    }
}

/* ---------- En-têtes HTTP de sécurité ---------- */
function envoyer_entetes_securite(): void
{
    // CSP stricte : uniquement nos propres scripts/styles, aucun script en ligne
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data: blob:; "
         . "font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
    header('X-Content-Type-Options: nosniff');          // pas de « devinette » du type MIME
    header('X-Frame-Options: DENY');                    // anti-clickjacking (anciens navigateurs)
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    if (Session::estHttps()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/** Échappement HTML (protection XSS). */
function e(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Chemin de base de l'application (fonctionne aussi dans un sous-dossier). */
function base_path(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return rtrim($dir, '/') . '/';
}

/** Construit l'URL d'une action : url('annonce', 'voir', ['id' => 3]). */
function url(string $c = 'home', string $a = 'index', array $params = []): string
{
    if ($c === 'home' && $a === 'index' && !$params) {
        return base_path();
    }
    return base_path() . 'index.php?' . http_build_query(array_merge(['c' => $c, 'a' => $a], $params));
}

/** URL d'un fichier statique (css, js, images, photos). */
function asset(string $chemin): string
{
    $url = base_path() . ltrim($chemin, '/');
    $fichier = ROOT . '/' . ltrim($chemin, '/');
    // Paramètre de version pour invalider le cache quand le fichier change
    return str_starts_with($chemin, 'public/') && is_file($fichier) ? $url . '?v=' . filemtime($fichier) : $url;
}

function photo_url(string $fichier): string
{
    return base_path() . 'uploads/' . rawurlencode(basename($fichier));
}

/** Formatage d'un prix en euros. */
function prix(float|string $montant): string
{
    $m = (float) $montant;
    return $m == 0.0 ? 'Gratuit' : number_format($m, 2, ',', ' ') . ' €';
}

function montant(float|string $m): string
{
    return number_format((float) $m, 2, ',', ' ') . ' €';
}

/** Libellé lisible des modes de livraison d'une annonce. */
function modes_livraison(array $annonce): array
{
    $modes = [];
    if (!empty($annonce['livraison_postale'])) {
        $modes[] = 'Envoi postal';
    }
    if (!empty($annonce['livraison_main_propre'])) {
        $modes[] = 'Main propre';
    }
    return $modes;
}

const MODES_LIVRAISON = [
    'postale'     => 'Envoi postal',
    'main_propre' => 'Remise en main propre',
];

/** Masque une adresse e-mail pour l'affichage public : j***n@g***.com */
function masquer_email(string $email): string
{
    [$local, $domaine] = array_pad(explode('@', $email, 2), 2, '');
    $l = mb_strlen($local);
    $local = $l <= 2 ? mb_substr($local, 0, 1) . '*' : mb_substr($local, 0, 1) . str_repeat('•', min(5, $l - 2)) . mb_substr($local, -1);
    $points = strrpos($domaine, '.');
    $ext = $points !== false ? substr($domaine, $points) : '';
    return $local . '@' . mb_substr($domaine, 0, 1) . '•••' . $ext;
}

function initiale(string $email): string
{
    return mb_strtoupper(mb_substr($email, 0, 1));
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $t): string
    {
        return strtoupper($t);
    }
}

function date_fr(?string $date, bool $heure = false): string
{
    if (!$date) {
        return '—';
    }
    $mois = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $t = strtotime($date);
    $s = date('j', $t) . ' ' . $mois[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
    return $heure ? $s . ' à ' . date('H:i', $t) : $s;
}

function il_y_a(?string $date): string
{
    if (!$date) {
        return 'jamais';
    }
    $d = time() - strtotime($date);
    return match (true) {
        $d < 60      => 'à l’instant',
        $d < 3600    => 'il y a ' . intdiv($d, 60) . ' min',
        $d < 86400   => 'il y a ' . intdiv($d, 3600) . ' h',
        $d < 2592000 => 'il y a ' . intdiv($d, 86400) . ' j',
        default      => 'le ' . date_fr($date),
    };
}

function pluriel(int $n, string $singulier, ?string $pluriel = null): string
{
    return $n . ' ' . ($n > 1 ? ($pluriel ?? $singulier . 's') : $singulier);
}

/** Champ caché contenant le jeton CSRF. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(Session::jetonCsrf()) . '">';
}

/* ---------- Icônes SVG en ligne (aucune ressource externe, compatible CSP) ---------- */
function icone(string $nom, string $classe = 'ic'): string
{
    static $chemins = [
        'logo'     => '<path d="M6 7h12l-1 12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 7Z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/><path d="M9.5 13.5l1.8 1.8 3.2-3.6"/>',
        'accueil'  => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'recherche'=> '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M18 14a6 6 0 0 1 3.5 6"/>',
        'sortie'   => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l-5-5 5-5"/><path d="M5 12h11"/>',
        'entree'   => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/>',
        'bouclier' => '<path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6l8-3Z"/><path d="m9 12 2 2 4-4"/>',
        'cadenas'  => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'cle'      => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9"/><path d="m17 6 3 3"/><path d="m15 8 2 2"/>',
        'paquet'   => '<path d="M21 8 12 3 3 8v8l9 5 9-5V8Z"/><path d="m3 8 9 5 9-5"/><path d="M12 13v8"/>',
        'etiquette'=> '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9Z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'panier'   => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 11.4a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.6L21 7H6"/>',
        'camion'   => '<path d="M2 6h11v10H2z"/><path d="M13 9h4l4 4v3h-8"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'main'     => '<path d="M7 11V6a1.5 1.5 0 0 1 3 0v4"/><path d="M10 10V4.5a1.5 1.5 0 0 1 3 0V10"/><path d="M13 10V6a1.5 1.5 0 0 1 3 0v5"/><path d="M16 11V9a1.5 1.5 0 0 1 3 0v4a8 8 0 0 1-8 8h-1a6 6 0 0 1-5-2.7L3 15.5a1.6 1.6 0 0 1 2.6-1.9L7 15"/>',
        'check'    => '<path d="m5 12 5 5 9-10"/>',
        'x'        => '<path d="M6 6l12 12M18 6 6 18"/>',
        'poubelle' => '<path d="M4 7h16"/><path d="M10 11v6M14 11v6"/><path d="M6 7l1 13h10l1-13"/><path d="M9 7V4h6v3"/>',
        'grille'   => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'graph'    => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>',
        'horloge'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'alerte'   => '<path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17.5v.5"/>',
        'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.5"/>',
        'oeil'     => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'image'    => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/>',
        'euro'     => '<path d="M17 6.5A7 7 0 1 0 17 17.5"/><path d="M4 10h9M4 14h9"/>',
        'reglages' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
        'dossier'  => '<path d="M3 6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6Z"/>',
        'crayon'   => '<path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>',
        'fleche'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'gauche'   => '<path d="m15 18-6-6 6-6"/>',
        'droite'   => '<path d="m9 18 6-6-6-6"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'historique'=> '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/>',
        'portable' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/>',
        'canape'   => '<path d="M4 11V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3"/><path d="M2 13a2 2 0 0 1 4 0v2h12v-2a2 2 0 0 1 4 0v5H2v-5Z"/><path d="M5 18v2M19 18v2"/>',
        'livre'    => '<path d="M4 4.5A1.5 1.5 0 0 1 5.5 3H20v15H5.5A1.5 1.5 0 0 0 4 19.5v-15Z"/><path d="M4 19.5A1.5 1.5 0 0 0 5.5 21H20"/>',
        'tshirt'   => '<path d="M8 3 3 6l2 4 2-1v12h10V9l2 1 2-4-5-3a4 4 0 0 1-8 0Z"/>',
        'velo'     => '<circle cx="6" cy="16" r="3.5"/><circle cx="18" cy="16" r="3.5"/><path d="M6 16l4-8h5l3 8"/><path d="M10 8 12 16h-6"/><path d="M14 5h2"/>',
        'voiture'  => '<path d="M5 16H3v-4l2-5h14l2 5v4h-2"/><path d="M3 12h18"/><circle cx="7.5" cy="16.5" r="2"/><circle cx="16.5" cy="16.5" r="2"/><path d="M9.5 17h5"/>',
        'feuille'  => '<path d="M5 19c0-9 6-14 15-15-1 9-6 15-15 15Z"/><path d="M5 19 13 11"/>',
        'eclair'   => '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>',
        'smile'    => '<circle cx="12" cy="12" r="9"/><path d="M8.5 14a4 4 0 0 0 7 0"/><path d="M9 9.5h.01M15 9.5h.01"/>',
    ];
    $d = $chemins[$nom] ?? $chemins['etiquette'];
    return '<svg class="' . e($classe) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

/** Icône déduite du nom de catégorie. */
function icone_categorie(string $nom): string
{
    $n = mb_strtolower($nom);
    $table = [
        'inform' => 'portable', 'high' => 'portable', 'tél' => 'portable', 'tel' => 'portable', 'électro' => 'eclair',
        'maison' => 'canape', 'meuble' => 'canape', 'déco' => 'canape',
        'livre' => 'livre', 'bouquin' => 'livre', 'bd' => 'livre',
        'vêt' => 'tshirt', 'vet' => 'tshirt', 'mode' => 'tshirt',
        'loisir' => 'velo', 'sport' => 'velo', 'jeu' => 'smile',
        'véhic' => 'voiture', 'vehic' => 'voiture', 'auto' => 'voiture', 'moto' => 'voiture',
        'jardin' => 'feuille', 'bricol' => 'reglages',
    ];
    foreach ($table as $cle => $ic) {
        if (str_contains($n, $cle)) {
            return icone($ic);
        }
    }
    return icone('etiquette');
}
