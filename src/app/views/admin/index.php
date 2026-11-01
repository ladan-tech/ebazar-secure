<?php defined('EBAZAR') or exit;
$u = Session::utilisateur();
$nav = [
    'apercu'     => ['Vue d’ensemble', 'grille', null],
    'annonces'   => ['Annonces', 'paquet', count($annonces)],
    'membres'    => ['Membres', 'users', count($utilisateurs)],
    'categories' => ['Catégories', 'dossier', count($categories)],
    'journal'    => ['Journal de sécurité', 'bouclier', null],
];
$lienOnglet = fn(string $o, array $p = []) => url('admin', 'index', ['onglet' => $o] + $p);
$ligneJournal = function (array $ev): void {
    [$lib, $ton] = Journal::LIBELLES[$ev['evenement']] ?? [$ev['evenement'], 'neutre']; ?>
    <tr>
        <td><span class="puce puce-<?= e($ton) ?>"><?= e($lib) ?></span></td>
        <td><?= e($ev['email'] ?? '—') ?></td>
        <td><span class="ip"><?= e($ev['ip']) ?></span></td>
        <td class="discret"><?= e($ev['details'] ?? '') ?></td>
        <td class="discret"><?= e(date_fr($ev['date_evt'], true)) ?></td>
    </tr>
<?php }; ?>

<div class="tdb">
    <aside class="carte tdb-lateral">
        <div class="tdb-profil">
            <span class="avatar avatar-lg avatar-admin"><?= icone('bouclier') ?></span>
            <div><strong><?= e($u['email']) ?></strong><small>Administrateur de la plateforme</small></div>
        </div>
        <ul class="tdb-nav">
            <?php foreach ($nav as $cle => [$libelle, $ic, $nb]): ?>
                <li><a href="<?= e($lienOnglet($cle)) ?>" class="<?= $onglet === $cle ? 'actif' : '' ?>">
                    <?= icone($ic) ?> <?= e($libelle) ?>
                    <?php if ($nb !== null): ?><span class="compteur-pastille"><?= $nb ?></span><?php endif; ?>
                </a></li>
            <?php endforeach; ?>
        </ul>
        <div class="tdb-session"><?= icone('cadenas') ?><span>Compte administrateur unique. Il ne peut ni vendre ni acheter.</span></div>
    </aside>

    <div>
    <?php if ($onglet === 'apercu'): ?>
        <div class="tdb-entete"><div><h1>Administration</h1><p>Supervision de la plateforme et de sa sécurité.</p></div></div>
        <div class="stats">
            <div class="carte stat"><div class="stat-tete">Membres <span class="stat-ic vert"><?= icone('users') ?></span></div><div class="stat-valeur"><?= count($utilisateurs) ?></div></div>
            <div class="carte stat"><div class="stat-tete">Annonces en ligne <span class="stat-ic bleu"><?= icone('paquet') ?></span></div><div class="stat-valeur"><?= (int) $statsAnnonces['en_vente'] ?></div></div>
            <div class="carte stat"><div class="stat-tete">Ventes en cours <span class="stat-ic ardoise"><?= icone('etiquette') ?></span></div><div class="stat-valeur"><?= (int) $statsAnnonces['vendues'] ?></div><div class="stat-sous">Volume <strong><?= e(montant($statsAnnonces['volume'])) ?></strong></div></div>
        </div>
        <div class="stats">
            <div class="carte stat"><div class="stat-tete">Connexions (24 h) <span class="stat-ic vert"><?= icone('entree') ?></span></div><div class="stat-valeur"><?= (int) ($statsSecu['connexions_24h'] ?? 0) ?></div></div>
            <div class="carte stat"><div class="stat-tete">Échecs de connexion (24 h) <span class="stat-ic ambre"><?= icone('alerte') ?></span></div><div class="stat-valeur"><?= (int) ($statsSecu['echecs_24h'] ?? 0) ?></div><div class="stat-sous">depuis <strong><?= e(pluriel((int) ($statsSecu['ips_suspectes'] ?? 0), 'adresse IP', 'adresses IP')) ?></strong></div></div>
            <div class="carte stat"><div class="stat-tete">Blocages anti force brute <span class="stat-ic rouge"><?= icone('bouclier') ?></span></div><div class="stat-valeur"><?= (int) ($statsSecu['blocages_24h'] ?? 0) ?></div><div class="stat-sous">sur les dernières 24 h</div></div>
        </div>
        <div class="carte">
            <div class="carte-tete"><h3><?= icone('alerte') ?> Derniers événements de sécurité</h3><a href="<?= e($lienOnglet('journal', ['filtre' => 'securite'])) ?>">Journal complet</a></div>
            <div class="tableau-conteneur"><table class="tableau">
                <thead><tr><th>Événement</th><th>Compte visé</th><th>IP</th><th>Détails</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach ($evenements as $ev) { $ligneJournal($ev); } ?>
                    <?php if (!$evenements): ?><tr><td colspan="5" class="discret">Aucun incident de sécurité. 🎉</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>

    <?php elseif ($onglet === 'annonces'): ?>
        <div class="tdb-entete"><div><h1>Annonces</h1><p>Modération : supprimez les annonces qui contreviennent aux règles.</p></div></div>
        <div class="carte"><div class="tableau-conteneur"><table class="tableau">
            <thead><tr><th>Annonce</th><th>Catégorie</th><th>Vendeur</th><th>Statut</th><th class="num">Prix</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($annonces as $a): ?>
                <tr>
                    <td><a class="objet" href="<?= e(url('annonce', 'voir', ['id' => $a['id']])) ?>"><strong><?= e($a['titre']) ?></strong></a><span class="discret"><?= e(date_fr($a['date_depot'])) ?></span></td>
                    <td><span class="puce"><?= e($a['categorie_nom']) ?></span></td>
                    <td><?= e($a['vendeur_email']) ?></td>
                    <td><?= $a['statut'] === 'en_vente' ? '<span class="puce puce-vert">En vente</span>' : '<span class="puce puce-ambre">Vendue</span>' ?></td>
                    <td class="num"><?= e(prix($a['prix'])) ?></td>
                    <td class="actions">
                        <form method="post" action="<?= e(url('admin', 'supprimerAnnonce', ['id' => $a['id']])) ?>" data-confirmer="Supprimer l’annonce « <?= e($a['titre']) ?> » ?">
                            <?= csrf_field() ?><button class="btn btn-danger btn-sm"><?= icone('poubelle') ?> Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$annonces): ?><tr><td colspan="6" class="discret">Aucune annonce.</td></tr><?php endif; ?>
            </tbody>
        </table></div></div>

    <?php elseif ($onglet === 'membres'): ?>
        <div class="tdb-entete"><div><h1>Membres</h1><p>Supprimer un membre supprime aussi toutes ses annonces et ferme ses sessions.</p></div></div>
        <div class="carte"><div class="tableau-conteneur"><table class="tableau">
            <thead><tr><th>Membre</th><th>Inscription</th><th>Dernière connexion</th><th class="num">Annonces</th><th class="num">Achats</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($utilisateurs as $m): ?>
                <tr>
                    <td><div class="objet"><span class="avatar"><?= e(initiale($m['email'])) ?></span><strong><?= e($m['email']) ?></strong></div></td>
                    <td class="discret"><?= e(date_fr($m['date_inscription'])) ?></td>
                    <td class="discret"><?= e(il_y_a($m['derniere_connexion'])) ?></td>
                    <td class="num"><?= (int) $m['nb_annonces'] ?></td>
                    <td class="num"><?= (int) $m['nb_achats'] ?></td>
                    <td class="actions">
                        <form method="post" action="<?= e(url('admin', 'supprimerUtilisateur', ['id' => $m['id']])) ?>" data-confirmer="Supprimer <?= e($m['email']) ?> et toutes ses annonces ? Action irréversible.">
                            <?= csrf_field() ?><button class="btn btn-danger btn-sm"><?= icone('poubelle') ?> Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$utilisateurs): ?><tr><td colspan="6" class="discret">Aucun membre inscrit.</td></tr><?php endif; ?>
            </tbody>
        </table></div></div>

    <?php elseif ($onglet === 'categories'): ?>
        <div class="tdb-entete"><div><h1>Catégories</h1><p>Renommer une catégorie conserve les biens qui y sont en vente.</p></div></div>
        <div class="carte">
            <div class="carte-tete"><h3><?= icone('plus') ?> Nouvelle catégorie</h3></div>
            <form class="carte-corps form-ligne" method="post" action="<?= e(url('admin', 'ajouterCategorie')) ?>">
                <?= csrf_field() ?>
                <input class="champ" name="nom" type="text" placeholder="ex. Jardin, Électroménager…" minlength="2" maxlength="50" required>
                <button class="btn btn-primaire"><?= icone('plus') ?> Ajouter</button>
            </form>
        </div>
        <br>
        <div class="carte"><div class="tableau-conteneur"><table class="tableau">
            <thead><tr><th>Catégorie</th><th class="num">Biens en vente</th><th>Renommer</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><a class="objet" href="<?= e(url('categorie', 'voir', ['id' => $c['id']])) ?>"><span class="stat-ic vert"><?= icone_categorie($c['nom']) ?></span><strong><?= e($c['nom']) ?></strong></a></td>
                    <td class="num"><?= (int) $c['nb_biens'] ?></td>
                    <td>
                        <form class="form-ligne" method="post" action="<?= e(url('admin', 'renommerCategorie', ['id' => $c['id']])) ?>">
                            <?= csrf_field() ?>
                            <input class="champ" name="nom" type="text" value="<?= e($c['nom']) ?>" minlength="2" maxlength="50" required aria-label="Nouveau nom">
                            <button class="btn btn-secondaire btn-sm"><?= icone('crayon') ?> Renommer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div></div>

    <?php else: /* journal */ ?>
        <div class="tdb-entete">
            <div><h1>Journal de sécurité</h1><p>Traçabilité des connexions, blocages et actions d’administration (200 derniers événements).</p></div>
            <div class="filtres">
                <a class="filtre<?= $filtre === null ? ' actif' : '' ?>" href="<?= e($lienOnglet('journal')) ?>">Tout</a>
                <a class="filtre<?= $filtre === 'securite' ? ' actif' : '' ?>" href="<?= e($lienOnglet('journal', ['filtre' => 'securite'])) ?>">Incidents</a>
                <a class="filtre<?= $filtre === 'admin' ? ' actif' : '' ?>" href="<?= e($lienOnglet('journal', ['filtre' => 'admin'])) ?>">Actions admin</a>
            </div>
        </div>
        <div class="carte"><div class="tableau-conteneur"><table class="tableau">
            <thead><tr><th>Événement</th><th>Compte</th><th>IP</th><th>Détails</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($evenements as $ev) { $ligneJournal($ev); } ?>
                <?php if (!$evenements): ?><tr><td colspan="5" class="discret">Aucun événement.</td></tr><?php endif; ?>
            </tbody>
        </table></div></div>
    <?php endif; ?>
    </div>
</div>
