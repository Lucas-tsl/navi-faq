<?php
/**
 * Bootstrap PHPUnit — délibérément SANS la suite de tests WordPress
 * (wp-phpunit), qui exige une base de données MySQL et un checkout complet
 * de WordPress (voir la même approche dans le plugin compagnon Saito Navi,
 * tests/bootstrap.php). Les fonctions couvertes ici
 * (navi_faq_sanitize_items(), navi_faq_group_items_by_theme()) sont pures
 * ou quasi pures : seuls quelques bouchons minimalistes des fonctions
 * WordPress qu'elles appellent suffisent à les charger et à les exécuter
 * isolément.
 */

define( 'ABSPATH', __DIR__ . '/../' );

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) {
        return trim( wp_strip_all_tags( (string) $str ) );
    }
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
    function wp_strip_all_tags( $str ) {
        return trim( strip_tags( (string) $str ) );
    }
}

if ( ! function_exists( 'wp_kses_post' ) ) {
    // Bouchon volontairement permissif (pas de vraie liste blanche de
    // balises) : les tests de navi_faq_sanitize_items() vérifient que
    // l'entrée est bien passée à cette fonction, pas le détail de sa
    // politique de filtrage HTML (déjà couverte par WordPress lui-même).
    function wp_kses_post( $str ) {
        return trim( (string) $str );
    }
}

if ( ! function_exists( 'add_shortcode' ) ) {
    function add_shortcode( $tag, $callback ) {
        return true;
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
        return true;
    }
}

require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/frontend.php';
