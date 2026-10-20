<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Schéma FAQPage (JSON-LD) — recommandation Google : le schéma ne doit
 * correspondre qu'à un contenu réellement visible sur la page.
 *
 * Sur un article/une page/un produit, l'affichage dépend du shortcode
 * [saito_faq] posé à la main dans le contenu : le schéma ne sort dans le
 * <head> que si ce shortcode y est réellement présent.
 *
 * Sur une archive de catégorie couverte, l'affichage est automatique mais
 * dépend du thème (voir saito_faq_term_archive_html(), frontend.php) : le
 * schéma attend la fin de page (wp_footer) et ne sort que si la FAQ a bien
 * été affichée. Un thème classique n'affiche l'archive qu'après le <head>,
 * on ne peut donc pas le savoir plus tôt.
 */
add_action( 'wp_head', 'saito_faq_output_schema' );
function saito_faq_output_schema() {
    if ( ! is_singular( saito_faq_post_types() ) ) {
        return;
    }
    global $post;
    if ( ! $post || ! has_shortcode( $post->post_content, 'saito_faq' ) ) {
        return;
    }
    saito_faq_print_schema( saito_faq_get_current_context_items() );
}

add_action( 'wp_footer', 'saito_faq_output_term_schema' );
function saito_faq_output_term_schema() {
    if ( ! is_tax( saito_faq_taxonomies() ) || ! saito_faq_term_archive_rendered() ) {
        return;
    }
    saito_faq_print_schema( saito_faq_get_current_context_items() );
}

function saito_faq_print_schema( array $items ) {
    if ( empty( $items ) ) {
        return;
    }

    $entities = array();
    foreach ( $items as $item ) {
        $entities[] = array(
            '@type'          => 'Question',
            'name'           => wp_strip_all_tags( $item['question'] ),
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => wp_strip_all_tags( $item['answer'] ),
            ),
        );
    }

    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $entities,
    );

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode() suffit à échapper un bloc JSON-LD, esc_html casserait le JSON.
}
