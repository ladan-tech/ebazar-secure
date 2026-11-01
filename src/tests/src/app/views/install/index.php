<?php defined('EBAZAR') or exit; $large = true; ?>
<h1>Installation d’e-bazar</h1>
<p class="sous">Renseignez l’accès à la base MySQL/MariaDB <strong>déjà existante</strong>. Les tables seront créées automatiquement.</p>

<?php foreach ($erreurs as $er): ?>
    <div class="toast toast-erreur"><?= icone('alerte') ?><div class="toast-texte"><?= e($er) ?></div></div><br>
<?php endforeach; ?>

<?php if ($configManuelle !== null): ?>
    <div class="toast toast-info"><?= icone('info') ?><div class="toast-texte">
        La base est initialisée, mais le serveur ne peut pas écrire <code>config/config.php</code>.
        Créez ce fichier avec le contenu ci-dessous, puis <a href="<?= e(url('auth', 'connexion')) ?>">connectez-vous</a>.
    </div></div>
    <pre class="code"><?= e($configManuelle) ?></pre>
<?php else: ?>
<form method="post" action="<?= e(base_path() . 'index.php') ?>">
    <?= csrf_field() ?>
    <fieldset class="groupe-champs">
        <legend><?= icone('dossier') ?> Base de données</legend>
        <div class="grille-champs">
            <div class="champ-groupe"><label class="etiquette" for="db_host">Serveur</label>
                <input class="champ" id="db_host" name="db_host" required value="<?= e($p['db_host']) ?>"></div>
            <div class="champ-groupe"><label class="etiquette" for="db_port">Port</label>
                <input class="champ" id="db_port" name="db_port" required value="<?= e($p['db_port']) ?>" inputmode="numeric"></div>
        </div>
        <div class="champ-groupe"><label class="etiquette" for="db_name">Nom de la base</label>
            <input class="champ" id="db_name" name="db_name" required value="<?= e($p['db_name']) ?>" placeholder="ebazar"></div>
        <div class="grille-champs">
            <div class="champ-groupe"><label class="etiquette" for="db_user">Utilisateur</label>
                <input class="champ" id="db_user" name="db_user" required value="<?= e($p['db_user']) ?>" autocomplete="off"></div>
            <div class="champ-groupe"><label class="etiquette" for="db_pass">Mot de passe</label>
                <input class="champ" id="db_pass" name="db_pass" type="password" autocomplete="new-password"></div>
        </div>
    </fieldset>
    <fieldset class="groupe-champs">
        <legend><?= icone('bouclier') ?> Compte administrateur (unique)</legend>
        <div class="champ-groupe"><label class="etiquette" for="admin_email">E-mail</label>
            <input class="champ" id="admin_email" name="admin_email" type="email" required value="<?= e($p['admin_email']) ?>"></div>
        <div class="champ-groupe"><label class="etiquette" for="admin_pass">Mot de passe</label>
            <div class="mdp-boite"><input class="champ" id="admin_pass" name="admin_pass" type="password" minlength="12" required autocomplete="new-password" data-force data-email-source="admin_email">
                <button type="button" class="mdp-oeil" data-oeil aria-label="Afficher le mot de passe"><?= icone('oeil') ?></button></div>
            <?php require APP . '/views/auth/_force.php'; ?>
        </div>
    </fieldset>
    <p class="aide"><?= icone('alerte') ?> Les tables <code>utilisateur</code>, <code>categorie</code>, <code>annonce</code>, <code>photo</code> et <code>journal</code> existantes seront recréées.</p>
    <button type="submit" class="btn btn-degrade btn-lg btn-bloc">Installer e-bazar</button>
</form>
<?php endif; ?>
