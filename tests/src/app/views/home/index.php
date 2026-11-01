<?php defined('EBAZAR') or exit; ?>
<section class="heros">
    <span class="heros-sur"><?= icone('bouclier') ?> Plateforme sécurisée entre particuliers</span>
    <h1>Donnez une seconde vie à vos objets.</h1>
    <p class="chapo">Vendez ce dont vous ne vous servez plus, trouvez la perle rare près de chez vous. Simple, rapide et protégé.</p>
    <form class="heros-recherche" action="<?= e(base_path() . 'index.php') ?>" method="get" role="search">
        <input type="hidden" name="c" value="annonce"><input type="hidden" name="a" value="rechercher">
        <label class="champ-icone"><?= icone('recherche') ?>
            <input type="search" name="q" placeholder="Que recherchez-vous ? (vélo, livre, console…)" maxlength="60" aria-label="Rechercher">
        </label>
        <button class="btn btn-primaire btn-lg">Rechercher</button>
    </form>
    <div class="heros-chiffres">
        <div><strong><?= (int) $stats['en_vente'] ?></strong><span>annonces en ligne</span></div>
        <div><strong><?= count($categories) ?></strong><span>catégories</span></div>
        <div><strong><?= (int) $nbMembres ?></strong><span>membres inscrits</span></div>
        <div><strong><?= (int) $stats['vendues'] ?></strong><span>ventes en cours de livraison</span></div>
    </div>
</section>

<section class="section">
    <div class="section-tete">
        <div><div class="sur-titre">Explorer</div><h2>Parcourir les catégories</h2></div>
    </div>
    <?php if (!$categories): ?>
        <div class="carte vide"><p>Aucune catégorie pour le moment.</p></div>
    <?php else: ?>
        <ul class="grille-cat">
            <?php foreach ($categories as $c): ?>
                <li>
                    <a class="tuile-cat" href="<?= e(url('categorie', 'voir', ['id' => $c['id']])) ?>">
                        <span class="tuile-cat-ic"><?= icone_categorie($c['nom']) ?></span>
                        <span><strong><?= e($c['nom']) ?></strong><small><?= e(pluriel((int) $c['nb_biens'], 'bien')) ?> en vente</small></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="section">
    <div class="section-tete">
        <div><div class="sur-titre">Nouveautés</div><h2>Dernières annonces</h2><p>Les quatre derniers biens mis en vente sur e-bazar.</p></div>
        <a class="btn btn-secondaire" href="<?= e(url('annonce', 'rechercher')) ?>">Tout voir <?= icone('fleche') ?></a>
    </div>
    <?php if (!$dernieres): ?>
        <div class="carte vide">
            <div class="vide-ic"><?= icone('paquet') ?></div>
            <h3>Aucun bien en vente pour l’instant</h3>
            <p>Soyez le premier à déposer une annonce !</p>
            <a class="btn btn-primaire" href="<?= e(url('annonce', 'deposer')) ?>"><?= icone('plus') ?> Déposer une annonce</a>
        </div>
    <?php else: ?>
        <div class="liste-annonces">
            <?php foreach ($dernieres as $a) { require APP . '/views/annonce/_ligne.php'; } ?>
        </div>
    <?php endif; ?>
</section>

<section class="section">
    <div class="section-tete"><div><div class="sur-titre">Comment ça marche</div><h2>Vendre ou acheter en trois étapes</h2></div></div>
    <div class="etapes">
        <div class="carte etape"><div class="etape-num"><?= icone('user') ?></div><h3>1. Créez votre compte</h3><p>Une adresse e-mail et un mot de passe robuste suffisent. Votre compte est protégé contre les intrusions.</p></div>
        <div class="carte etape"><div class="etape-num"><?= icone('image') ?></div><h3>2. Publiez votre annonce</h3><p>Titre, description, prix, modes de livraison et jusqu’à 5 photos. En ligne immédiatement.</p></div>
        <div class="carte etape"><div class="etape-num"><?= icone('camion') ?></div><h3>3. Vendez et livrez</h3><p>Suivez vos ventes et vos achats depuis votre tableau de bord, jusqu’à la confirmation de réception.</p></div>
    </div>
</section>

<section class="section bandeau-secu">
    <div>
        <div class="sur-titre">Sécurité</div>
        <h2>Votre sécurité n’est pas une option.</h2>
        <p>e-bazar applique les recommandations de l’OWASP et de l’ANSSI pour protéger vos données et votre compte.</p>
    </div>
    <ul>
        <li><?= icone('cadenas') ?><div><strong>Mots de passe robustes et hachés</strong><span>12 caractères minimum, hachage Argon2id.</span></div></li>
        <li><?= icone('bouclier') ?><div><strong>Anti force brute</strong><span>Blocage temporaire après 5 essais infructueux.</span></div></li>
        <li><?= icone('horloge') ?><div><strong>Sessions maîtrisées</strong><span>Expiration, empreinte navigateur, révocation à distance.</span></div></li>
        <li><?= icone('image') ?><div><strong>Vie privée</strong><span>Photos ré-encodées : la position GPS est supprimée.</span></div></li>
    </ul>
</section>

<?php if (!Session::estConnecte()): ?>
<section class="appel">
    <div><h2>Vous avez un objet à vendre ?</h2><p>Créez votre compte gratuitement et publiez votre première annonce en moins de 2 minutes.</p></div>
    <a class="btn btn-degrade btn-lg" href="<?= e(url('auth', 'inscription')) ?>">Commencer maintenant <?= icone('fleche') ?></a>
</section>
<?php endif; ?>
