<?php defined('EBAZAR') or exit;
$gratuit = (float) $annonce['prix'] == 0; ?>
<nav class="fil" aria-label="Fil d’Ariane">
    <a href="<?= e(url()) ?>">Accueil</a><?= icone('droite') ?>
    <a href="<?= e(url('categorie', 'voir', ['id' => $annonce['categorie_id']])) ?>"><?= e($annonce['categorie_nom']) ?></a><?= icone('droite') ?>
    <span><?= e($annonce['titre']) ?></span>
</nav>

<div class="fiche">
    <div>
        <div class="carte galerie" data-galerie>
            <div class="galerie-principale">
                <?php if ($photos): ?>
                    <img data-galerie-image src="<?= e(photo_url($photos[0]['fichier'])) ?>" alt="Photo 1 de « <?= e($annonce['titre']) ?> »">
                    <?php if (count($photos) > 1): ?>
                        <button type="button" class="galerie-nav prec" data-galerie-prec aria-label="Photo précédente"><?= icone('gauche') ?></button>
                        <button type="button" class="galerie-nav suiv" data-galerie-suiv aria-label="Photo suivante"><?= icone('droite') ?></button>
                        <span class="galerie-compteur" data-galerie-compteur>1 / <?= count($photos) ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="sans-photo"><?= icone('image') ?></div>
                <?php endif; ?>
            </div>
            <?php if (count($photos) > 1): ?>
                <div class="miniatures">
                    <?php foreach ($photos as $i => $ph): ?>
                        <button type="button" class="miniature<?= $i === 0 ? ' active' : '' ?>" data-src="<?= e(photo_url($ph['fichier'])) ?>" aria-label="Voir la photo <?= $i + 1 ?>">
                            <img src="<?= e(photo_url($ph['fichier'])) ?>" alt="">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="carte" id="description">
            <div class="carte-tete"><h2><?= icone('info') ?> Description</h2></div>
            <div class="carte-corps"><p class="description-texte"><?= e($annonce['description']) ?></p></div>
        </div>
    </div>

    <aside class="fiche-panneau">
        <div class="carte carte-corps">
            <div class="fiche-puces">
                <span class="puce puce-vert"><?= icone('etiquette') ?><?= e($annonce['categorie_nom']) ?></span>
                <?php if ($gratuit): ?><span class="puce puce-bleu">Don</span><?php endif; ?>
            </div>
            <h1><?= e($annonce['titre']) ?></h1>
            <div class="fiche-prix<?= $gratuit ? ' prix-gratuit' : '' ?>"><?= e(prix($annonce['prix'])) ?></div>

            <ul class="liste-details">
                <?php if ($annonce['livraison_postale']): ?><li><span><?= icone('camion') ?> Envoi postal</span><span>Accepté</span></li><?php endif; ?>
                <?php if ($annonce['livraison_main_propre']): ?><li><span><?= icone('main') ?> Remise en main propre</span><span>Acceptée</span></li><?php endif; ?>
                <li><span><?= icone('horloge') ?> Publiée</span><span><?= e(date_fr($annonce['date_depot'])) ?></span></li>
                <li><span><?= icone('image') ?> Photos</span><span><?= count($photos) ?></span></li>
            </ul>

            <?php if ($annonce['statut'] !== 'en_vente'): ?>
                <div class="statut-vendu"><?= icone('check') ?> Ce bien a été vendu le <?= e(date_fr($annonce['date_vente'])) ?>.</div>
            <?php elseif ($estVendeur): ?>
                <div class="note-secu"><?= icone('info') ?><div>C’est votre annonce. Elle est visible par tous les visiteurs.</div></div><br>
                <form method="post" action="<?= e(url('annonce', 'supprimer', ['id' => $annonce['id']])) ?>" data-confirmer="Supprimer définitivement cette annonce ?">
                    <?= csrf_field() ?>
                    <button class="btn btn-danger btn-bloc"><?= icone('poubelle') ?> Supprimer mon annonce</button>
                </form>
            <?php elseif (Session::estAdmin()): ?>
                <form method="post" action="<?= e(url('admin', 'supprimerAnnonce', ['id' => $annonce['id']])) ?>" data-confirmer="Supprimer cette annonce (modération) ?">
                    <?= csrf_field() ?>
                    <button class="btn btn-danger-plein btn-bloc"><?= icone('poubelle') ?> Supprimer (modération)</button>
                </form>
            <?php else: ?>
                <a class="btn btn-degrade btn-lg btn-bloc" href="<?= e(url('achat', 'acheter', ['id' => $annonce['id']])) ?>"><?= icone('panier') ?> Acheter ce bien</a>
                <?php if (!Session::estConnecte()): ?>
                    <p class="aide">Vous serez invité à vous connecter puis ramené ici automatiquement.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="carte carte-corps">
            <div class="vendeur">
                <span class="avatar avatar-lg"><?= e(initiale($annonce['vendeur_email'])) ?></span>
                <div>
                    <strong><?= e(masquer_email($annonce['vendeur_email'])) ?></strong>
                    <small>Membre depuis <?= e(date_fr($annonce['vendeur_depuis'])) ?> · <?= e(pluriel((int) $annonce['vendeur_nb_annonces'], 'annonce')) ?> en ligne</small>
                </div>
            </div>
        </div>

        <div class="note-secu"><?= icone('bouclier') ?>
            <div><strong>Conseil de sécurité :</strong> ne communiquez jamais vos coordonnées bancaires ni de code reçu par SMS. Les coordonnées du vendeur ne sont visibles qu’après l’achat.</div>
        </div>
    </aside>
</div>
