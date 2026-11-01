<?php defined('EBAZAR') or exit;
$err = fn(string $champ) => isset($erreurs[$champ]) ? '<p class="erreur-champ">' . icone('alerte') . e($erreurs[$champ]) . '</p>' : '';
$inv = fn(string $champ) => isset($erreurs[$champ]) ? ' invalide' : ''; ?>
<div class="titre-page">
    <span class="titre-page-ic"><?= icone('plus') ?></span>
    <div><h1>Déposer une annonce</h1><p>Votre annonce sera visible immédiatement par tous les visiteurs.</p></div>
</div>

<?php if ($erreurs): ?>
    <div class="toast toast-erreur"><?= icone('alerte') ?><div class="toast-texte">Le formulaire contient <?= e(pluriel(count($erreurs), 'erreur')) ?>, merci de les corriger.</div></div><br>
<?php endif; ?>

<form method="post" action="<?= e(url('annonce', 'deposer')) ?>" enctype="multipart/form-data" class="formulaire-depot" novalidate data-formulaire-depot>
    <div>
        <?= csrf_field() ?>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= Photo::TAILLE_MAX ?>">

        <section class="carte bloc-form">
            <div class="carte-tete"><h2><span class="num-etape">1</span> L’essentiel</h2></div>
            <div class="carte-corps">
                <div class="champ-groupe">
                    <label class="etiquette" for="categorie_id">Catégorie</label>
                    <select class="champ<?= $inv('categorie_id') ?>" id="categorie_id" name="categorie_id" required>
                        <option value="">Choisissez une catégorie…</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (int) $saisie['categorie_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $err('categorie_id') ?>
                </div>
                <div class="champ-groupe">
                    <label class="etiquette" for="titre">Titre de l’annonce <small>5 à 30 caractères</small></label>
                    <input class="champ<?= $inv('titre') ?>" id="titre" name="titre" type="text" minlength="5" maxlength="30" required
                           placeholder="ex. Vélo de course Décathlon" value="<?= e($saisie['titre']) ?>" data-compteur>
                    <?= $err('titre') ?>
                </div>
                <div class="champ-groupe">
                    <label class="etiquette" for="description">Description <small>5 à 200 caractères</small></label>
                    <textarea class="champ<?= $inv('description') ?>" id="description" name="description" rows="5" minlength="5" maxlength="200" required
                              placeholder="État, dimensions, raison de la vente…" data-compteur><?= e($saisie['description']) ?></textarea>
                    <?= $err('description') ?>
                </div>
            </div>
        </section>

        <section class="carte bloc-form">
            <div class="carte-tete"><h2><span class="num-etape">2</span> Prix et livraison</h2></div>
            <div class="carte-corps">
                <div class="champ-groupe">
                    <label class="etiquette" for="prix">Prix <small>0 pour un don</small></label>
                    <div class="champ-prefixe">
                        <input class="champ<?= $inv('prix') ?>" id="prix" name="prix" type="text" inputmode="decimal" pattern="\d{1,8}([.,]\d{1,2})?"
                               placeholder="0,00" required value="<?= e((string) $saisie['prix']) ?>">
                        <span>€</span>
                    </div>
                    <?= $err('prix') ?>
                </div>
                <div class="etiquette">Modes de livraison acceptés</div>
                <div class="choix-grille">
                    <label class="choix">
                        <input type="checkbox" name="livraison_postale" value="1" <?= $saisie['livraison_postale'] ? 'checked' : '' ?>>
                        <span class="choix-carte"><span class="choix-ic"><?= icone('camion') ?></span><span><strong>Envoi postal</strong><small>Expédition par colis</small></span></span>
                    </label>
                    <label class="choix">
                        <input type="checkbox" name="livraison_main_propre" value="1" <?= $saisie['livraison_main_propre'] ? 'checked' : '' ?>>
                        <span class="choix-carte"><span class="choix-ic"><?= icone('main') ?></span><span><strong>Main propre</strong><small>Rencontre avec l’acheteur</small></span></span>
                    </label>
                </div>
                <?= $err('livraison') ?>
            </div>
        </section>

        <section class="carte bloc-form">
            <div class="carte-tete"><h2><span class="num-etape">3</span> Photos <small class="aide">facultatif</small></h2></div>
            <div class="carte-corps">
                <label class="zone-photos" data-zone-photos>
                    <input id="photos" name="photos[]" type="file" accept="image/jpeg,.jpg,.jpeg" multiple data-max="<?= Photo::MAX_PHOTOS ?>" data-taille="<?= Photo::TAILLE_MAX ?>">
                    <span class="zone-photos-ic"><?= icone('image') ?></span>
                    <strong>Cliquez ou glissez vos photos ici</strong>
                    <p>JPEG uniquement · 200 Kio maximum par photo · 5 photos maximum</p>
                </label>
                <div class="apercus" data-apercus></div>
                <p class="aide" data-photos-info>La première photo servira de vignette dans les listes.</p>
                <?= $err('photos') ?>
            </div>
        </section>

        <button type="submit" class="btn btn-degrade btn-lg"><?= icone('check') ?> Publier mon annonce</button>
    </div>

    <aside class="carte carte-corps conseils">
        <h3>Conseils pour bien vendre</h3>
        <ul>
            <li><?= icone('image') ?><span>Des photos nettes et lumineuses multiplient les chances de vente.</span></li>
            <li><?= icone('crayon') ?><span>Un titre précis : marque, modèle, taille.</span></li>
            <li><?= icone('euro') ?><span>Fixez un prix juste en comparant avec des annonces similaires.</span></li>
            <li><?= icone('bouclier') ?><span>Vos photos sont nettoyées : leurs métadonnées (position GPS, appareil) sont supprimées.</span></li>
        </ul>
    </aside>
</form>
