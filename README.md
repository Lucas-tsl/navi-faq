# Navi FAQ

Plugin WordPress/WooCommerce : questions/réponses sur les articles, pages,
**produits et catégories de produits**, avec schéma `FAQPage` (JSON-LD) et
regroupement optionnel des questions par thème (affichage en onglets).

Développé comme plugin compagnon de [Saito Navi](https://github.com/Lucas-tsl/navi-wordpress).

📖 **[Documentation complète (captures d'écran + explications)](https://lucas-tsl.github.io/navi-faq/)**
([version Notion](https://rectangular-tiara-ce3.notion.site/Navi-FAQ-Documentation-3c61e90fa69a81df9caad08c42242bf7))

## Pourquoi ce plugin

Les plugins FAQ existants (ex. FAQ Magic) gèrent les articles, pages et
produits, mais pas les catégories de produits WooCommerce — pas de moyen
natif d'ajouter une FAQ à une page d'archive de catégorie. Navi FAQ comble
ce manque, avec une structure de données propre et un regroupement des
questions par thème (ex. "Livraison", "Le produit") pour les FAQ plus
étoffées.

## Fonctionnalités

- **Couverture** : articles, pages, produits, catégories de produits
  (`product_cat`) — extensible via les filtres `navi_faq_post_types` et
  `navi_faq_taxonomies`.
- **Admin** : un encart FAQ sur les fiches article/page/produit, un champ
  identique sur l'écran d'édition de catégorie — même interface partagée,
  ajout/suppression de questions en JS, sans rechargement de page.
- **Thème par question (optionnel)** : donner le même thème à plusieurs
  questions les regroupe automatiquement sous un même onglet en front. Une
  seule catégorie de thème (ou aucune) : accordéon simple, pas d'onglets
  inutiles.
- **Affichage front** :
  - `[navi_faq]` — FAQ du contexte courant (article/page/produit affiché,
    ou page d'archive d'une taxonomie couverte).
  - `[navi_faq_all]` — toutes les FAQ du site, groupées par titre, pour une
    page "Questions fréquentes" centralisée.
  - Affichage automatique en haut des pages d'archive de catégorie (pas de
    zone de contenu où poser un shortcode à la main sur ce type de page).
- **Schéma `FAQPage` (JSON-LD)** généré côté serveur, uniquement là où le
  contenu est réellement visible (recommandation Google) : toujours sur une
  catégorie couverte, seulement si `[navi_faq]` est posé dans le contenu
  sur un article/une page/un produit.
- **Accessibilité** : navigation clavier flèches gauche/droite/Home/End
  entre les onglets de thème (pattern [ARIA Tabs](https://www.w3.org/WAI/ARIA/apg/patterns/tabs/)).

## Installation

1. Copier le dossier `navi-faq/` dans `wp-content/plugins/`.
2. Activer le plugin depuis le menu **Extensions**.

## Structure des données

Chaque FAQ (post ou terme de taxonomie) est stockée dans une seule
meta (`_navi_faq_items`), tableau de :

```php
array(
    'question' => 'Livrez-vous à l\'international ?',
    'answer'   => '<p>Oui, ...</p>', // HTML limité (wp_kses_post)
    'group'    => 'Commandes & Livraison', // optionnel, '' = pas de thème
)
```

## Statut

Version 0.1.0 — base fonctionnelle testée en environnement de
développement local. Pas encore soumis à WordPress.org.
