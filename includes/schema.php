<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Schéma FAQPage (JSON-LD) — recommandation Google : le schéma ne doit
 * correspondre qu'à un contenu réellement visible sur la page. Sur une page
 * d'archive de catégorie couverte, l'affichage est automatique (voir
 * saito_faq_render_on_term_archive(), frontend.php) donc le schéma l'est
 * aussi. Sur un article/une page/un produit, l'affichage dépend du
 * shortcode [saito_faq] posé à la main dans le contenu : le schéma ne sort
 * donc que si ce shortcode y est réellement présent.
 */
add_action( 'wp_head', 'saito_faq_output_schema' );
function saito_faq_output_schema() {
    if ( is_tax( saito_faq_taxonomies() ) ) {
        $items = saito_faq_get_current_context_items();
    } elseif ( is_singular( navi_faq_post_types() ) ) {
        global $post;
        $items = ( $post && has_shortcode( $post->post_content, 'saito_faq' ) )
            ? saito_faq_get_current_context_items()
            : array();
    } else {
        $items = array();
    }

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
