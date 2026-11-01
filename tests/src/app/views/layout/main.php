<?php defined('EBAZAR') or exit;
$u = Session::utilisateur();
$installe = Database::estConfiguree();
$iconesFlash = ['succes' => 'check', 'erreur' => 'alerte', 'info' => 'info', 'alerte' => 'alerte']; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="same-origin">
    <title><?= e($titre) ?> · e-bazar</title>
    <link rel="icon" href="<?= e(asset('public/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('public/css/style.css')) ?>">
</head>
<body>
<a class="sr" href="#contenu">Aller au contenu</a>
<header class="entete">
    <div class="conteneur entete-inner">
        <a class="logo" href="<?= e(url()) ?>" aria-label="e-bazar, accueil">
            <span class="logo-marque"><?= icone('logo') ?></span><span class="logo-texte">e-bazar</span>
        </a>

        <?php if ($installe): ?>
        <form class="recherche-entete" action="<?= e(base_path() . 'index.php') ?>" method="get" role="search">
            <input type="hidden" name="c" value="annonce"><input type="hidden" name="a" value="rechercher">
            <?= icone('recherche') ?>
            <input type="search" name="q" placeholder="Rechercher un objet…" aria-label="Rechercher" maxlength="60"
                   value="<?= e($_GET['c'] ?? '') === 'annonce' ? e((string) ($_GET['q'] ?? '')) : '' ?>">
        </form>
        <?php endif; ?>

        <nav class="nav" aria-label="Navigation principale">
            <?php if (Session::estMembre()): ?>
                <a class="btn btn-primaire" href="<?= e(url('annonce', 'deposer')) ?>"><?= icone('plus') ?><span class="txt">Déposer une annonce</span></a>
            <?php endif; ?>

            <?php if ($u): ?>
                <details class="menu-u">
                    <summary aria-label="Menu du compte">
                        <span class="avatar<?= Session::estAdmin() ? ' avatar-admin' : '' ?>"><?= e(initiale($u['email'])) ?></span>
                        <span class="menu-u-email"><?= e($u['email']) ?></span>
                    </summary>
                    <div class="menu-u-panneau">
                        <div class="menu-u-tete">
                            <strong><?= e($u['email']) ?></strong>
                            <small><?= Session::estAdmin() ? 'Administrateur' : 'Membre' ?></small>
                        </div>
                        <?php if (Session::estAdmin()): ?>
                            <a href="<?= e(url('admin')) ?>"><?= icone('grille') ?> Tableau de bord</a>
                            <a href="<?= e(url('admin', 'index', ['onglet' => 'journal'])) ?>"><?= icone('bouclier') ?> Journal de sécurité</a>
                        <?php else: ?>
                            <a href="<?= e(url('compte')) ?>"><?= icone('grille') ?> Tableau de bord</a>
                            <a href="<?= e(url('compte', 'index', ['onglet' => 'ventes'])) ?>"><?= icone('etiquette') ?> Mes ventes</a>
                            <a href="<?= e(url('compte', 'index', ['onglet' => 'achats'])) ?>"><?= icone('panier') ?> Mes achats</a>
                            <a href="<?= e(url('compte', 'index', ['onglet' => 'securite'])) ?>"><?= icone('bouclier') ?> Sécurité du compte</a>
                        <?php endif; ?>
                        <div class="sep"></div>
                        <form method="post" action="<?= e(url('auth', 'deconnexion')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="rouge"><?= icone('sortie') ?> Se déconnecter</button>
                        </form>
                    </div>
                </details>
            <?php elseif ($installe): ?>
                <a class="nav-lien" href="<?= e(url('auth', 'connexion')) ?>"><?= icone('entree') ?><span class="txt">Connexion</span></a>
                <a class="btn btn-primaire" href="<?= e(url('auth', 'inscription')) ?>">S’inscrire</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="page" id="contenu">
    <div class="conteneur">
        <?php if ($flash = Session::lireFlash()): ?>
            <div class="toasts" role="status" aria-live="polite">
                <?php foreach ($flash as $f): ?>
                    <div class="toast toast-<?= e($f['type']) ?>">
                        <?= icone($iconesFlash[$f['type']] ?? 'info') ?>
                        <div class="toast-texte"><?= e($f['message']) ?></div>
                        <button type="button" class="toast-fermer" aria-label="Fermer"><?= icone('x') ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?= $contenu ?>
    </div>
</main>

<footer class="pied">
    <div class="conteneur">
        <div class="pied-grille">
            <div>
                <a class="logo" href="<?= e(url()) ?>"><span class="logo-marque"><?= icone('logo') ?></span><span class="logo-texte">e-bazar</span></a>
                <p>La plateforme de petites annonces entre particuliers, pensée pour la sécurité de ses membres.</p>
            </div>
            <div>
                <h4>Plateforme</h4>
                <ul>
                    <li><a href="<?= e(url()) ?>">Accueil</a></li>
                    <li><a href="<?= e(url('annonce', 'rechercher')) ?>">Rechercher</a></li>
                    <li><a href="<?= e(url('annonce', 'deposer')) ?>">Déposer une annonce</a></li>
                </ul>
            </div>
            <div>
                <h4>Mon compte</h4>
                <ul>
                    <?php if ($u): ?>
                        <li><a href="<?= e(Session::estAdmin() ? url('admin') : url('compte')) ?>">Tableau de bord</a></li>
                    <?php else: ?>
                        <li><a href="<?= e(url('auth', 'connexion')) ?>">Connexion</a></li>
                        <li><a href="<?= e(url('auth', 'inscription')) ?>">Créer un compte</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div>
                <h4>Sécurité</h4>
                <ul>
                    <li>Mots de passe hachés (Argon2id)</li>
                    <li>Protection anti-force brute</li>
                    <li>Photos nettoyées de leurs métadonnées</li>
                </ul>
            </div>
        </div>
        <div class="pied-bas">
            <span>© <?= date('Y') ?> e-bazar — Mini-projet LW1, M1 Informatique</span>
            <span><?= icone('cadenas') ?> Session protégée · déconnexion automatique après <?= Session::minutesRestantes() ?> min d’inactivité</span>
        </div>
    </div>
</footer>
<script src="<?= e(asset('public/js/app.js')) ?>"></script>
</body>
</html>
