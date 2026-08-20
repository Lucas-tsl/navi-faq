<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Une seule meta (_navi_faq_items, voir NAVI_FAQ_META_KEY dans navi-faq.php),
// répartie sur potentiellement des centaines de posts/termes : pas
// d'équivalent WP_Query/API pour une suppression en masse par meta_key,
// d'où la requête directe — désinstallation, hors requête HTTP normale.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_navi_faq_items' ) );
$wpdb->delete( $wpdb->termmeta, array( 'meta_key' => '_navi_faq_items' ) );
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
