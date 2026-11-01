<?php defined('EBAZAR') or exit;
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
<div class="auth">
    <aside class="auth-visuel">
        <a class="logo" href="<?= e(url()) ?>"><span class="logo-marque"><?= icone('logo') ?></span><span class="logo-texte">e-bazar</span></a>
        <div class="auth-accroche">
            <h2>Achetez et vendez en toute confiance.</h2>
            <p>Des milliers d’objets cherchent une seconde vie. Votre compte est protégé à chaque étape.</p>
            <ul class="auth-points">
                <li><?= icone('cadenas') ?><div><strong>Mot de passe chiffré</strong><small>Haché avec Argon2id, jamais stocké en clair</small></div></li>
                <li><?= icone('bouclier') ?><div><strong>Protection anti-intrusion</strong><small>Blocage automatique après plusieurs échecs</small></div></li>
                <li><?= icone('horloge') ?><div><strong>Session sécurisée</strong><small>Expiration automatique et révocation à distance</small></div></li>
            </ul>
        </div>
        <div class="auth-pied">© <?= date('Y') ?> e-bazar</div>
    </aside>
    <main class="auth-form" id="contenu">
        <div class="auth-boite<?= ($large ?? false) ? ' large' : '' ?>">
            <?php if ($flash = Session::lireFlash()): ?>
                <div class="toasts" role="status" aria-live="polite">
                    <?php foreach ($flash as $f): ?>
                        <div class="toast toast-<?= e($f['type']) ?>">
                            <?= icone($iconesFlash[$f['type']] ?? 'info') ?>
                            <div class="toast-texte"><?= e($f['message']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?= $contenu ?>
        </div>
    </main>
</div>
<script src="<?= e(asset('public/js/app.js')) ?>"></script>
</body>
</html>
