<?php defined('EBAZAR') or exit; ?>
<a class="auth-retour" href="<?= e(url()) ?>"><?= icone('gauche') ?> Retour à l’accueil</a>
<h1>Bon retour parmi nous</h1>
<p class="sous">Connectez-vous pour acheter, vendre et suivre vos transactions.</p>

<?php if ($erreur): ?>
    <div class="toast toast-erreur"><?= icone('alerte') ?><div class="toast-texte"><?= e($erreur) ?></div></div><br>
<?php endif; ?>

<form method="post" action="<?= e(url('auth', 'connexion')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="champ-groupe">
        <label class="etiquette" for="email">Adresse e-mail</label>
        <input class="champ" id="email" name="email" type="email" required autofocus autocomplete="username" maxlength="190"
               placeholder="vous@exemple.fr" value="<?= e($email) ?>">
    </div>
    <div class="champ-groupe">
        <label class="etiquette" for="mot_de_passe">Mot de passe</label>
        <div class="mdp-boite">
            <input class="champ" id="mot_de_passe" name="mot_de_passe" type="password" required autocomplete="current-password" maxlength="128" placeholder="••••••••••••">
            <button type="button" class="mdp-oeil" data-oeil aria-label="Afficher le mot de passe"><?= icone('oeil') ?></button>
        </div>
    </div>
    <button type="submit" class="btn btn-degrade btn-lg btn-bloc"><?= icone('entree') ?> Se connecter</button>
</form>

<div class="separateur">Nouveau sur e-bazar ?</div>
<a class="btn btn-secondaire btn-lg btn-bloc" href="<?= e(url('auth', 'inscription')) ?>">Créer un compte gratuitement</a>
<p class="aide auth-lien"><?= icone('cadenas') ?> Après 5 échecs, la connexion est bloquée 15 minutes.</p>
