<?php
/**
 * Plugin Name: Saito FAQ
 * Description: Questions/réponses sur les articles, pages, produits et catégories de produits (WooCommerce), avec schéma FAQPage (JSON-LD). Plugin compagnon de Saito Navi.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Troteseil Lucas
 * Author URI: https://github.com/Lucas-tsl
 * Text Domain: saito-faq
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SAITO_FAQ_VERSION', '0.1.0' );
define( 'SAITO_FAQ_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAITO_FAQ_URL', plugin_dir_url( __FILE__ ) );
define( 'SAITO_FAQ_META_KEY', '_saito_faq_items' );

/**
 * Types de contenu et taxonomies couverts, filtrables : un thème ou un autre
 * plugin peut étendre (ou restreindre) la liste sans toucher à ce fichier.
 * 'product_cat' par défaut plutôt que 'category' : ce plugin naît d'un
 * besoin WooCommerce précis (FAQ par catégorie de produit), pas d'un besoin
 * générique de blog.
 */
function navi_faq_post_types() {
    return apply_filters( 'navi_faq_post_types', array( 'post', 'page', 'product' ) );
}

function saito_faq_taxonomies() {
    return apply_filters( 'saito_faq_taxonomies', array( 'product_cat' ) );
}

// Une seule feuille de style (admin ET front) : les rangées Q/R de l'admin
// et les blocs accordéon du front partagent des noms de classes préfixés
// saito-faq-, autant les regrouper plutôt que dupliquer le fichier.
function saito_faq_enqueue_shared_style() {
    wp_enqueue_style( 'saito-faq', SAITO_FAQ_URL . 'assets/css/saito-faq.css', array(), SAITO_FAQ_VERSION );
}

require_once SAITO_FAQ_DIR . 'includes/data.php';
require_once SAITO_FAQ_DIR . 'includes/admin.php';
require_once SAITO_FAQ_DIR . 'includes/saito-panel.php';
require_once SAITO_FAQ_DIR . 'includes/frontend.php';
require_once SAITO_FAQ_DIR . 'includes/schema.php';
