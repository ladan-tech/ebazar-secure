#!/usr/bin/env bash
# =============================================================================
#  e-bazar — Suite de tests de sécurité automatisés (boîte noire, via HTTP)
# =============================================================================
#  Chaque test simule une attaque réelle et vérifie que l'application y résiste.
#  Le script RÉINSTALLE l'application sur une base vide : ne jamais le lancer
#  contre une base de production.
#
#  Usage :
#     BASE_URL=http://localhost:8000 APP_DIR=./src \
#     DB_HOST=127.0.0.1 DB_NAME=ebazar_test DB_USER=ebazar DB_PASS=secret \
#     ./tests/security/run.sh
# =============================================================================
set -uo pipefail

BASE_URL="${BASE_URL:-http://localhost:8000}"
APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/../../src" && pwd)}"
DB_HOST="${DB_HOST:-127.0.0.1}"; DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-ebazar_test}"; DB_USER="${DB_USER:-ebazar}"; DB_PASS="${DB_PASS:-secret}"
ADMIN_EMAIL="admin@ebazar.test"; ADMIN_PASS='Adm!nistrat0r-Test'

TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
PASS=0; FAIL=0; SECTION=""
VERT=$'\e[32m'; ROUGE=$'\e[31m'; GRIS=$'\e[90m'; GRAS=$'\e[1m'; RAZ=$'\e[0m'
[ -t 1 ] || { VERT=""; ROUGE=""; GRIS=""; GRAS=""; RAZ=""; }

section() { SECTION="$1"; printf "\n${GRAS}%s${RAZ}\n" "$1"; }
ok()   { PASS=$((PASS + 1)); printf "  ${VERT}✔ PASS${RAZ}  %s\n" "$1"; }
ko()   { FAIL=$((FAIL + 1)); printf "  ${ROUGE}✘ FAIL${RAZ}  %s ${GRIS}(%s)${RAZ}\n" "$1" "${2:-}"; }
check() { if eval "$2"; then ok "$1"; else ko "$1" "${3:-}"; fi; }

# Client HTTP : un « navigateur » = un fichier de cookies
req()  { local jar="$TMP/$1.jar"; shift; curl -s -c "$jar" -b "$jar" -A "${UA:-Mozilla/5.0 (SecurityTest)}" "$@"; }
code() { local jar="$TMP/$1.jar"; shift; curl -s -o /dev/null -w '%{http_code}' -c "$jar" -b "$jar" -A "${UA:-Mozilla/5.0 (SecurityTest)}" "$@"; }
loc()  { local jar="$TMP/$1.jar"; shift; curl -s -o /dev/null -w '%{redirect_url}' -c "$jar" -b "$jar" -A "${UA:-Mozilla/5.0 (SecurityTest)}" "$@"; }
csrf() { req "$1" "$BASE_URL/index.php?${2:-c=auth&a=connexion}" | grep -o 'name="csrf" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
inscrire() { local t; t=$(csrf "$1" "c=auth&a=inscription"); loc "$1" --data-urlencode "email=$2" --data-urlencode "mot_de_passe=$3" --data-urlencode "confirmation=$3" -d "cgu=1&csrf=$t" "$BASE_URL/index.php?c=auth&a=inscription"; }
connecter() { local t; t=$(csrf "$1"); loc "$1" --data-urlencode "email=$2" --data-urlencode "mot_de_passe=$3" -d "csrf=$t" "$BASE_URL/index.php?c=auth&a=connexion"; }
deposer() { # navigateur titre [args curl supplémentaires…]
    local jar=$1 titre=$2; shift 2; local t; t=$(csrf "$jar" "c=annonce&a=deposer")
    loc "$jar" -F "csrf=$t" -F categorie_id=1 -F "titre=$titre" -F "description=Objet de test pour la suite de sécurité" -F prix=10 -F livraison_postale=1 "$@" "$BASE_URL/index.php?c=annonce&a=deposer"; }
id_annonce() { sed -n 's/.*[?&]id=\([0-9]*\).*/\1/p' <<< "$1"; }

printf "${GRAS}e-bazar — tests de sécurité${RAZ}  →  %s\n" "$BASE_URL"

# ----------------------------------------------------------------------------
section "0. Préparation : installation sur une base vierge"
rm -f "$APP_DIR/config/config.php" "$APP_DIR"/uploads/*.jpg 2>/dev/null
php -r '
  $mk = function ($f, $r, $g, $b) { $i = imagecreatetruecolor(400, 300); imagefill($i, 0, 0, imagecolorallocate($i, $r, $g, $b)); ob_start(); imagejpeg($i, null, 80); return ob_get_clean(); };
  file_put_contents($argv[1] . "/ok.jpg", $mk(0, 16, 185, 129));
  $j = $mk(0, 37, 99, 235); $p = "Exif\0\0GPS-SECRET-48.8566N-2.3522E";
  file_put_contents($argv[1] . "/exif.jpg", substr($j, 0, 2) . "\xFF\xE1" . pack("n", strlen($p) + 2) . $p . substr($j, 2));
  file_put_contents($argv[1] . "/shell.jpg", "<?php system(\$_GET[\"cmd\"]); ?>");
  file_put_contents($argv[1] . "/polyglotte.jpg", $mk(0, 200, 50, 50) . "<?php system(\$_GET[\"cmd\"]); ?>");
  file_put_contents($argv[1] . "/gros.jpg", $mk(0, 1, 2, 3) . random_bytes(260000));
' "$TMP"
T=$(csrf install "")
R=$(loc install -d "csrf=$T&db_host=$DB_HOST&db_port=$DB_PORT&db_name=$DB_NAME&db_user=$DB_USER" --data-urlencode "db_pass=$DB_PASS" \
    --data-urlencode "admin_email=$ADMIN_EMAIL" --data-urlencode "admin_pass=$ADMIN_PASS" "$BASE_URL/index.php")
check "Installation automatique de l'application" '[[ "$R" == *"c=auth"* ]]' "redirection : $R"
check "L'assistant d'installation est verrouillé après usage" '[ "$(code anon "$BASE_URL/index.php?c=install")" = 403 ]'

A_MAIL="alice@ebazar.test"; A_PASS='Al1ce#Vend-2026'
B_MAIL="bob@ebazar.test";   B_PASS='B0b!Achete-2026'
C_MAIL="carol@ebazar.test"; C_PASS='Car0l$Cible-2026'

# ----------------------------------------------------------------------------
section "1. En-têtes HTTP de sécurité (A05 — Security Misconfiguration)"
curl -s -D "$TMP/h" -o /dev/null "$BASE_URL/"
H=$(tr -d '\r' < "$TMP/h")
check "Content-Security-Policy stricte (script-src 'self', sans unsafe-inline)" 'grep -qi "^content-security-policy:.*script-src .self." <<< "$H" && ! grep -qi "unsafe-inline" <<< "$H"'
check "Protection anti-clickjacking (frame-ancestors 'none' + X-Frame-Options)" 'grep -qi "frame-ancestors .none." <<< "$H" && grep -qi "^x-frame-options: *deny" <<< "$H"'
check "X-Content-Type-Options: nosniff" 'grep -qi "^x-content-type-options: *nosniff" <<< "$H"'
check "Referrer-Policy et Permissions-Policy présents" 'grep -qi "^referrer-policy:" <<< "$H" && grep -qi "^permissions-policy:" <<< "$H"'
check "Aucune fuite de version (X-Powered-By absent)" '! grep -qi "^x-powered-by:" <<< "$H"'
check "Cookie de session HttpOnly + SameSite" 'grep -i "^set-cookie: *EBAZARSESSID" <<< "$H" | grep -qi "httponly" && grep -i "^set-cookie: *EBAZARSESSID" <<< "$H" | grep -qi "samesite=lax"'

# ----------------------------------------------------------------------------
section "2. Exposition de fichiers et traversée de répertoire (A01 / A05)"
for f in "config/config.php" "config/config.php.dist" "sql/schema.sql" "app/core/App.php" ".htaccess" "uploads/.htaccess"; do
    c=$(code anon "$BASE_URL/$f")
    check "Accès direct interdit : /$f" '[ "$c" = 403 ] || [ "$c" = 404 ]' "HTTP $c"
done
B=$(req anon "$BASE_URL/index.php?c=../../../../etc/passwd&a=index")
check "Traversée via le routeur (?c=../../etc/passwd) refusée" '! grep -q "root:x:" <<< "$B" && [ "$(code anon "$BASE_URL/index.php?c=../../../../etc/passwd")" = 404 ]'
check "Appel d'une méthode interne (?a=__construct) refusé" '[ "$(code anon "$BASE_URL/index.php?c=admin&a=__construct")" = 404 ]'

# ----------------------------------------------------------------------------
section "3. Authentification et mots de passe (A07 — Identification & Authentication Failures)"
T=$(csrf alice "c=auth&a=inscription")
B=$(req alice -d "csrf=$T&email=$A_MAIL&mot_de_passe=password123&confirmation=password123&cgu=1" "$BASE_URL/index.php?c=auth&a=inscription")
check "Mot de passe faible refusé (« password123 »)" 'grep -q "Le mot de passe doit contenir" <<< "$B"'
B=$(req alice --data-urlencode "email=$A_MAIL" --data-urlencode "mot_de_passe=Alice#2026-alice" --data-urlencode "confirmation=Alice#2026-alice" -d "csrf=$T&cgu=1" "$BASE_URL/index.php?c=auth&a=inscription")
check "Mot de passe contenant l'identifiant refusé" 'grep -q "ne pas contenir votre adresse e-mail" <<< "$B"'
R=$(inscrire alice "$A_MAIL" "$A_PASS");  check "Inscription avec un mot de passe robuste" '[[ "$R" == *"c=compte"* ]]' "$R"
R=$(inscrire bob "$B_MAIL" "$B_PASS");    check "Inscription d'un second membre" '[[ "$R" == *"c=compte"* ]]' "$R"
R=$(inscrire carol "$C_MAIL" "$C_PASS");  check "Inscription d'un troisième membre" '[[ "$R" == *"c=compte"* ]]' "$R"
check "Mots de passe stockés hachés (aucun mot de passe en clair dans la réponse)" '! req alice "$BASE_URL/index.php?c=compte&a=index&onglet=securite" | grep -qF "$A_PASS"'
T=$(csrf robot "c=auth&a=inscription")
R=$(loc robot -d "csrf=$T&site_web=http://spam.example&email=robot@ebazar.test&mot_de_passe=R0bot!Spam-2026&confirmation=R0bot!Spam-2026&cgu=1" "$BASE_URL/index.php?c=auth&a=inscription")
check "Robot piégé par le pot de miel (aucun compte créé)" '[ "$(connecter robot2 robot@ebazar.test "R0bot!Spam-2026")" = "" ]'

# Énumération de comptes
T=$(csrf enum)
M1=$(req enum -d "csrf=$T&email=inconnu@ebazar.test&mot_de_passe=Faux-Mdp-123!" "$BASE_URL/index.php?c=auth&a=connexion" | grep -o 'toast-texte">[^<.]*' | head -1)
M2=$(req enum --data-urlencode "email=$C_MAIL" -d "csrf=$T&mot_de_passe=Faux-Mdp-123!" "$BASE_URL/index.php?c=auth&a=connexion" | grep -o 'toast-texte">[^<.]*' | head -1)
check "Anti-énumération : même message pour un compte inconnu et un mauvais mot de passe" '[ -n "$M1" ] && [ "$M1" = "$M2" ]' "« $M1 » / « $M2 »"

# Force brute
T=$(csrf brute)
for i in 1 2 3 4 5; do req brute --data-urlencode "email=$C_MAIL" -d "csrf=$T&mot_de_passe=essai$i" "$BASE_URL/index.php?c=auth&a=connexion" > /dev/null; done
B=$(req brute --data-urlencode "email=$C_MAIL" --data-urlencode "mot_de_passe=$C_PASS" -d "csrf=$T" "$BASE_URL/index.php?c=auth&a=connexion")
check "Force brute : après 5 échecs, même le BON mot de passe est refusé (verrouillage)" 'grep -q "Trop de tentatives" <<< "$B"'
check "Le verrouillage est lié à l'IP : la victime reste connectée ailleurs" '[ "$(code carol "$BASE_URL/index.php?c=compte")" = 200 ]'

# ----------------------------------------------------------------------------
section "4. Gestion des sessions (A07)"
curl -s -D "$TMP/fix" -o /dev/null -b "EBAZARSESSID=sessionimposeeparlattaquant" "$BASE_URL/"
check "Fixation de session : un identifiant imposé est rejeté (strict mode)" '! grep -qi "EBAZARSESSID=sessionimposeeparlattaquant" "$TMP/fix" && grep -qi "set-cookie: *EBAZARSESSID=" "$TMP/fix"'
cp "$TMP/bob.jar" "$TMP/vol.jar"
R=$(UA="Attaquant/1.0 (autre machine)" loc vol "$BASE_URL/index.php?c=compte")
check "Détournement : cookie volé rejoué depuis un autre navigateur → session détruite" '[[ "$R" == *"c=auth"* ]]' "$R"
connecter bob "$B_MAIL" "$B_PASS" > /dev/null
connecter bob2 "$B_MAIL" "$B_PASS" > /dev/null
check "Deux sessions ouvertes pour le même membre" '[ "$(code bob2 "$BASE_URL/index.php?c=compte")" = 200 ]'
T=$(csrf bob "c=compte&a=index&onglet=securite")
code bob -d "csrf=$T" "$BASE_URL/index.php?c=compte&a=revoquerSessions" > /dev/null
check "Révocation : « déconnecter les autres appareils » ferme l'autre session" '[ "$(code bob2 "$BASE_URL/index.php?c=compte")" = 303 ]'
check "… et conserve la session courante" '[ "$(code bob "$BASE_URL/index.php?c=compte")" = 200 ]'
T=$(csrf bob "c=compte")
code bob -d "csrf=$T" "$BASE_URL/index.php?c=auth&a=deconnexion" > /dev/null
check "Déconnexion : la session est détruite côté serveur" '[ "$(code bob "$BASE_URL/index.php?c=compte")" = 303 ]'
connecter bob "$B_MAIL" "$B_PASS" > /dev/null
check "Pages privées non mises en cache (Cache-Control: no-store)" 'curl -s -D - -o /dev/null -b "$TMP/bob.jar" -A "Mozilla/5.0 (SecurityTest)" "$BASE_URL/index.php?c=compte" | grep -qi "cache-control:.*no-store"'

# ----------------------------------------------------------------------------
section "5. Falsification de requête inter-sites — CSRF (A01)"
R=$(loc alice -d "csrf=" "$BASE_URL/index.php?c=auth&a=deconnexion")
check "Déconnexion forcée sans jeton CSRF refusée" '[ "$(code alice "$BASE_URL/index.php?c=compte")" = 200 ]'
R=$(loc alice -d "csrf=0000000000000000000000000000000000000000000000000000000000000000" "$BASE_URL/index.php?c=compte&a=revoquerSessions")
check "Jeton CSRF falsifié refusé" '[[ "$R" != *"onglet=securite"* ]]' "$R"
check "Action d'état en GET refusée (405)" '[ "$(code alice "$BASE_URL/index.php?c=annonce&a=supprimer&id=1")" = 405 ]'

# ----------------------------------------------------------------------------
section "6. Injections (A03 — Injection)"
T=$(csrf sqli)
B=$(req sqli --data-urlencode "email=$A_MAIL' OR '1'='1' -- " --data-urlencode "mot_de_passe=' OR '1'='1" -d "csrf=$T" "$BASE_URL/index.php?c=auth&a=connexion")
check "Injection SQL dans le formulaire de connexion : contournement impossible" '[ "$(code sqli "$BASE_URL/index.php?c=compte")" = 303 ]'
c=$(code anon "$BASE_URL/index.php?c=annonce&a=rechercher&q=%27%20UNION%20SELECT%20email,mot_de_passe%20FROM%20utilisateur--%20")
B=$(req anon "$BASE_URL/index.php?c=annonce&a=rechercher&q=%27%20UNION%20SELECT%20email,mot_de_passe%20FROM%20utilisateur--%20")
check "Injection SQL UNION dans la recherche : aucune donnée exfiltrée, pas d'erreur" '[ "$c" = 200 ] && ! grep -q "argon2id\|\$2y\$" <<< "$B"'
check "Injection SQL dans un identifiant numérique (id=1 OR 1=1) rejetée" '[ "$(code anon "$BASE_URL/index.php?c=annonce&a=voir&id=1%20OR%201=1")" = 404 ]'
check "Jokers LIKE neutralisés (recherche « %% » ne liste pas tout)" '! req anon "$BASE_URL/index.php?c=annonce&a=rechercher&q=%25%25" | grep -q "class=\"ligne\""'

XSS='<script>alert(document.cookie)</script>'
R=$(deposer alice "Lampe <img src=x onerror=1>" --form-string "description=$XSS")
ID_XSS=$(id_annonce "$R")
B=$(req anon "$BASE_URL/index.php?c=annonce&a=voir&id=$ID_XSS")
check "XSS stockée : le script injecté est échappé à l'affichage" '[ -n "$ID_XSS" ] && grep -qF "&lt;script&gt;" <<< "$B" && ! grep -qF "$XSS" <<< "$B" && ! grep -q "<img src=x" <<< "$B"'
B=$(req anon "$BASE_URL/index.php?c=annonce&a=rechercher&q=%3Cscript%3Ealert(1)%3C%2Fscript%3E")
check "XSS réfléchie dans la recherche : échappée" '! grep -qF "<script>alert(1)</script>" <<< "$B"'

# ----------------------------------------------------------------------------
section "7. Contrôle d'accès et IDOR (A01 — Broken Access Control)"
R=$(deposer alice "Vélo de test IDOR" -F "photos[]=@$TMP/ok.jpg")
ID_A=$(id_annonce "$R")
check "Anonyme → tableau de bord : redirigé vers la connexion" '[[ "$(loc anon "$BASE_URL/index.php?c=compte")" == *"c=auth"* ]]'
check "Membre → administration : 403" '[ "$(code bob "$BASE_URL/index.php?c=admin")" = 403 ]'
T=$(csrf bob "c=compte")
check "IDOR : un membre ne peut pas supprimer l'annonce d'un autre (403)" '[ "$(code bob -d "csrf=$T" "$BASE_URL/index.php?c=annonce&a=supprimer&id=$ID_A")" = 403 ]'
check "Élévation : un membre ne peut pas utiliser une action admin (403)" '[ "$(code bob -d "csrf=$T" "$BASE_URL/index.php?c=admin&a=supprimerAnnonce&id=$ID_A")" = 403 ]'
T=$(csrf alice "c=compte")
check "Le vendeur ne peut pas acheter son propre bien" '[[ "$(loc alice -d "csrf=$T&mode_livraison=postale" "$BASE_URL/index.php?c=achat&a=acheter&id=$ID_A")" == *"a=voir"* ]]'
T=$(csrf bob "c=achat&a=acheter&id=$ID_A")
R=$(loc bob -d "csrf=$T&mode_livraison=main_propre" "$BASE_URL/index.php?c=achat&a=acheter&id=$ID_A")
check "Falsification de paramètre : mode de livraison non proposé par le vendeur refusé" '[[ "$R" != *"c=compte"* ]]' "$R"
R=$(loc bob -d "csrf=$T&mode_livraison=postale" "$BASE_URL/index.php?c=achat&a=acheter&id=$ID_A")
check "Achat légitime accepté" '[[ "$R" == *"c=compte"* ]]' "$R"
T=$(csrf carol "c=compte")
check "IDOR : un tiers ne peut pas confirmer la réception d'un achat qui n'est pas le sien" '[ "$(code carol -d "csrf=$T" "$BASE_URL/index.php?c=achat&a=confirmerReception&id=$ID_A")" = 403 ]'
check "Un bien vendu n'est plus visible par les tiers" '[ "$(code carol "$BASE_URL/index.php?c=annonce&a=voir&id=$ID_A")" = 404 ]'
check "Confidentialité : l'e-mail du vendeur est masqué sur la page publique" '! req anon "$BASE_URL/index.php?c=annonce&a=voir&id=$ID_XSS" | grep -qF "$A_MAIL"'

# ----------------------------------------------------------------------------
section "8. Logique métier : situation de compétition (achat simultané)"
R=$(deposer alice "Objet très convoité")
ID_R=$(id_annonce "$R")
TB=$(csrf bob "c=achat&a=acheter&id=$ID_R"); TC=$(csrf carol "c=achat&a=acheter&id=$ID_R")
for i in 1 2 3 4 5; do
    loc bob   -d "csrf=$TB&mode_livraison=postale" "$BASE_URL/index.php?c=achat&a=acheter&id=$ID_R" > "$TMP/race_b$i" &
    loc carol -d "csrf=$TC&mode_livraison=postale" "$BASE_URL/index.php?c=achat&a=acheter&id=$ID_R" > "$TMP/race_c$i" &
done; wait
GAGNANTS=$(cat "$TMP"/race_* | grep -c "c=compte" || true)
check "10 achats simultanés du même bien → exactement 1 réussite" '[ "$GAGNANTS" = 1 ]' "$GAGNANTS réussite(s)"

# ----------------------------------------------------------------------------
section "9. Téléversement de fichiers malveillants (A04 / A08)"
B=$(T=$(csrf alice "c=annonce&a=deposer"); req alice -F "csrf=$T" -F categorie_id=1 -F "titre=Test webshell" -F "description=Tentative de webshell" -F prix=1 -F livraison_postale=1 -F "photos[]=@$TMP/shell.jpg;type=image/jpeg" "$BASE_URL/index.php?c=annonce&a=deposer")
check "Webshell PHP déguisé en .jpg (type MIME falsifié) refusé" 'grep -q "pas une image JPEG" <<< "$B"'
B=$(T=$(csrf alice "c=annonce&a=deposer"); req alice -F "csrf=$T" -F categorie_id=1 -F "titre=Test trop gros" -F "description=Fichier trop volumineux" -F prix=1 -F livraison_postale=1 -F "photos[]=@$TMP/gros.jpg" "$BASE_URL/index.php?c=annonce&a=deposer")
check "Fichier de plus de 200 Kio refusé" 'grep -q "taille maximale" <<< "$B"'
R=$(deposer alice "Test polyglotte" -F "photos[]=@$TMP/polyglotte.jpg" -F "photos[]=@$TMP/exif.jpg")
ID_P=$(id_annonce "$R")
mapfile -t PHOTOS < <(req anon "$BASE_URL/index.php?c=annonce&a=voir&id=$ID_P" | grep -o 'uploads/[a-f0-9]\{32\}\.jpg' | sort -u)
check "Photos stockées sous un nom aléatoire de 128 bits" '[ "${#PHOTOS[@]}" -ge 2 ]' "${#PHOTOS[@]} photo(s)"
CONTENU=""; for p in "${PHOTOS[@]}"; do CONTENU+=$(curl -s "$BASE_URL/$p" | tr -d '\0' | strings); done
check "JPEG polyglotte : le code PHP caché est éliminé par le ré-encodage" '! grep -q "<?php" <<< "$CONTENU"'
check "Vie privée : les métadonnées EXIF (position GPS) sont supprimées" '! grep -q "GPS-SECRET" <<< "$CONTENU"'
check "Le dossier uploads/ ne sert que des JPEG au nom attendu" '[ "$(code anon "$BASE_URL/uploads/test.php")" = 403 ] || [ "$(code anon "$BASE_URL/uploads/test.php")" = 404 ]'

# ----------------------------------------------------------------------------
section "10. Journalisation et supervision (A09 — Security Logging & Monitoring Failures)"
connecter admin "$ADMIN_EMAIL" "$ADMIN_PASS" > /dev/null
J=$(req admin "$BASE_URL/index.php?c=admin&a=index&onglet=journal")
check "Les échecs de connexion sont journalisés" 'grep -q "Échec de connexion" <<< "$J"'
check "Les blocages anti force brute sont journalisés" 'grep -q "Connexion bloquée" <<< "$J"'
check "Les sessions suspectes (cookie volé) sont journalisées" 'grep -q "Session suspecte" <<< "$J"'
B=$(req anon "$BASE_URL/index.php?c=annonce&a=voir&id=999999")
check "Erreurs : aucune trace technique divulguée (pas de chemin, de pile ni de SQL)" '! grep -qiE "stack trace|PDOException|/home/|/var/www|SQLSTATE" <<< "$B"'

# ----------------------------------------------------------------------------
printf "\n${GRAS}Résultat : ${VERT}%d réussi(s)${RAZ}${GRAS}, ${ROUGE}%d échec(s)${RAZ}\n" "$PASS" "$FAIL"
[ "$FAIL" -eq 0 ]
