<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Couche données : deux propriétaires possibles pour une liste de FAQ — un
 * post (article/page/produit) ou un terme de taxonomie (catégorie) — mais
 * une seule structure de tableau (['question' => ..., 'answer' => ...,
 * 'group' => ...]), partagée par l'admin, l'affichage front et le schéma.
 * 'group' (thème, ex. "Livraison") est optionnel : une chaîne vide range la
 * question dans l'accordéon simple plutôt que dans un onglet nommé — voir
 * navi_faq_render_items_html() (frontend.php).
 */

function navi_faq_get_for_post( $post_id ) {
    $items = get_post_meta( $post_id, NAVI_FAQ_META_KEY, true );
    return is_array( $items ) ? $items : array();
}

function navi_faq_save_for_post( $post_id, array $items ) {
    if ( empty( $items ) ) {
        delete_post_meta( $post_id, NAVI_FAQ_META_KEY );
    } else {
        update_post_meta( $post_id, NAVI_FAQ_META_KEY, $items );
    }
}

function navi_faq_get_for_term( $term_id ) {
    $items = get_term_meta( $term_id, NAVI_FAQ_META_KEY, true );
    return is_array( $items ) ? $items : array();
}

function navi_faq_save_for_term( $term_id, array $items ) {
    if ( empty( $items ) ) {
        delete_term_meta( $term_id, NAVI_FAQ_META_KEY );
    } else {
        update_term_meta( $term_id, NAVI_FAQ_META_KEY, $items );
    }
}

/**
 * Normalise les deux tableaux parallèles envoyés par le formulaire
 * (navi_faq_*_question[]/navi_faq_*_answer[]) en une liste propre — une
 * ligne sans question ET réponse (ex. bouton "Ajouter" cliqué puis laissé
 * de côté) est simplement écartée plutôt que sauvegardée vide.
 */
function navi_faq_sanitize_items( array $questions, array $answers, array $groups = array() ) {
    $items = array();
    foreach ( $questions as $index => $question ) {
        $question = sanitize_text_field( $question );
        $answer   = isset( $answers[ $index ] ) ? wp_kses_post( $answers[ $index ] ) : '';
        if ( '' === $question || '' === $answer ) {
            continue;
        }
        $items[] = array(
            'question' => $question,
            'answer'   => $answer,
            'group'    => isset( $groups[ $index ] ) ? sanitize_text_field( $groups[ $index ] ) : '',
        );
    }
    return $items;
}
