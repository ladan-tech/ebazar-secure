<?php defined('EBAZAR') or exit;
$err = fn(string $champ) => isset($erreurs[$champ]) ? '<p class="erreur-champ">' . icone('alerte') . e($erreurs[$champ]) . '</p>' : '';
$inv = fn(string $champ) => isset($erreurs[$champ]) ? ' invalide' : ''; ?>
<a class="auth-retour" href="<?= e(url()) ?>"><?= icone('gauche') ?> Retour à l’accueil</a>
<h1>Créer un compte</h1>
<p class="sous">Gratuit, et prêt en moins d’une minute.</p>

<form method="post" action="<?= e(url('auth', 'inscription')) ?>" novalidate>
    <?= csrf_field() ?>
    <!-- Pot de miel anti-robots : invisible pour les humains -->
    <div class="cache-robot" aria-hidden="true">
        <label for="site_web">Ne pas remplir</label>
        <input id="site_web" name="site_web" type="text" tabindex="-1" autocomplete="off">
    </div>

    <div class="champ-groupe">
        <label class="etiquette" for="email">Adresse e-mail <small>servira d’identifiant</small></label>
        <input class="champ<?= $inv('email') ?>" id="email" name="email" type="email" required autofocus autocomplete="email" maxlength="190"
               placeholder="vous@exemple.fr" value="<?= e($email) ?>">
        <?= $err('email') ?>
    </div>
    <div class="champ-groupe">
        <label class="etiquette" for="mot_de_passe">Mot de passe</label>
        <div class="mdp-boite">
            <input class="champ<?= $inv('mot_de_passe') ?>" id="mot_de_passe" name="mot_de_passe" type="password" required minlength="12" maxlength="128"
                   autocomplete="new-password" data-force data-email-source="email">
            <button type="button" class="mdp-oeil" data-oeil aria-label="Afficher le mot de passe"><?= icone('oeil') ?></button>
        </div>
        <?php require APP . '/views/auth/_force.php'; ?>
        <?= $err('mot_de_passe') ?>
    </div>
    <div class="champ-groupe">
        <label class="etiquette" for="confirmation">Confirmer le mot de passe</label>
        <input class="champ<?= $inv('confirmation') ?>" id="confirmation" name="confirmation" type="password" required autocomplete="new-password" data-confirme="mot_de_passe">
        <?= $err('confirmation') ?>
    </div>
    <div class="champ-groupe">
        <label class="case"><input type="checkbox" name="cgu" value="1" required>
            <span>J’accepte les conditions d’utilisation et la politique de confidentialité d’e-bazar.</span></label>
        <?= $err('cgu') ?>
    </div>
    <button type="submit" class="btn btn-degrade btn-lg btn-bloc">Créer mon compte</button>
</form>
<p class="auth-lien">Déjà inscrit ? <a href="<?= e(url('auth', 'connexion')) ?>">Se connecter</a></p>
