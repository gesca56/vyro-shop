<?php
/** Articles de démonstration du Journal VYRO */
function seed_posts(PDO $pdo): int
{
    $posts = [
        ['New Drop SS — Define your style', 'Drop', 'hoodie', 'Noir', 1,
            'Le nouveau drop VYRO est là : heavy hoodies, cargo et sneakers affûtées. Découvrez les pièces phares.',
            "Le drop que tout Abidjan attendait est enfin disponible.\n\n## Des matières lourdes\n\nNos hoodies passent à **400 g/m²** : molleton brossé, capuche doublée et coutures renforcées. Une pièce pensée pour durer.\n\n## Les pièces phares\n\n- Le **VYRO Heavy Hoodie**, en noir, gris chiné et bleu nuit\n- Le **Cargo Street Pants**, coupe relaxed et 6 poches\n- La **VYRO Street Runner 01**, notre nouvelle sneaker\n\nLes quantités sont limitées : certaines tailles partent en quelques heures.\n\n[Voir le New Drop](shop.php?collection=new-drop)"],
        ['Comment porter l’oversized sans paraître négligé', 'Guide', 'tee', 'Blanc', 0,
            'Tee oversized, cargo, sneakers : nos règles simples pour un look ample mais maîtrisé.',
            "L'oversized est au cœur du streetwear, mais il demande un peu de méthode.\n\n## 1. Jouez sur les proportions\n\nUn haut ample appelle un bas plus structuré. Associez un **tee oversized** à un cargo coupe relaxed plutôt qu'à un jogging très large.\n\n## 2. Choisissez la bonne taille\n\nNos coupes oversized taillent déjà large : prenez **votre taille habituelle**. Une taille au-dessus pour un effet très ample.\n\n## 3. Soignez les chaussures\n\nUne sneaker propre et massive équilibre la silhouette.\n\n- Tee : VYRO Oversized Tee\n- Bas : Cargo Street Pants\n- Chaussures : VYRO Court Low\n\n[Shopper le look](shop.php?cat=tshirts)"],
        ['Lookbook : Street Collection à Treichville', 'Lookbook', 'pants', 'Kaki', 0,
            'On a shooté la Street Collection dans les rues de Treichville. Retour en images et en ambiance.',
            "Pour la Street Collection, on voulait un décor vrai : les rues de **Treichville**, leurs couleurs et leur énergie.\n\n## L'équipe\n\nTrois modèles, un photographe, une journée entière à arpenter le quartier.\n\n## Les looks\n\n- Coach Jacket kaki + Street Signature Tee\n- Cargo noir + Crossbody Bag\n- High Classic beige, portées avec un jogger\n\nMerci à tous ceux qui nous ont accueillis. **Taguez-nous avec #VYROSTYLE** pour apparaître dans le prochain lookbook.\n\n[Voir la Street Collection](shop.php?collection=street-collection)"],
        ['Entretenir ses sneakers blanches : le guide', 'Guide', 'sneaker', 'Blanc', 0,
            'Nettoyage, rangement, erreurs à éviter : gardez vos Court Low éclatantes plus longtemps.',
            "Des sneakers blanches propres, c'est la base d'un bon look.\n\n## Après chaque sortie\n\nUn coup de brosse souple suffit pour enlever la poussière. Ne laissez pas les taches sécher.\n\n## Le nettoyage en profondeur\n\n- Retirez les lacets et lavez-les à part\n- Utilisez de l'eau tiède et un savon doux\n- **Jamais de machine à laver** : la colle et le cuir n'aiment pas ça\n- Séchez à l'air libre, à l'ombre\n\n## Le rangement\n\nGardez-les dans leur boîte, avec du papier à l'intérieur pour garder la forme.\n\n[Découvrir les sneakers](shop.php?cat=sneakers)"],
    ];
    $st = $pdo->prepare('INSERT INTO posts (title, slug, category, excerpt, content, visual, visual_color, is_featured, status, published_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
    foreach ($posts as $i => $p) {
        $slug = trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', strtr($p[0], ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'à' => 'a', 'â' => 'a', 'î' => 'i', 'ô' => 'o', 'û' => 'u', 'ç' => 'c', 'É' => 'E', '’' => '-']))), '-');
        $st->execute([$p[0], $slug, $p[1], $p[5], $p[6], $p[2], $p[3], $p[4], 'published', date('Y-m-d H:i:s', strtotime('-' . ($i * 6 + 1) . ' days'))]);
    }
    return count($posts);
}
