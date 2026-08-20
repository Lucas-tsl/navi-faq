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

/**
 * Thèmes déjà utilisés quelque part sur le site (tous posts + tous termes
 * couverts confondus), proposés en auto-complétion sur le champ "Thème" de
 * l'admin (voir navi_faq_render_row_markup(), admin.php) : sans ça, une
 * simple variation de casse ou d'espace ("Livraison" vs "livraison ")
 * fragmenterait silencieusement un regroupement voulu en deux onglets
 * distincts en front. Exécuté uniquement au chargement d'un écran d'admin
 * (voir navi_faq_enqueue_admin_assets()) : sans cache pour l'instant, à
 * revoir (transient) si le catalogue grossit significativement.
 */
function navi_faq_get_known_themes() {
    $themes = array();

    foreach ( navi_faq_post_types() as $post_type ) {
        $query = new WP_Query( array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'meta_key'       => NAVI_FAQ_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- admin uniquement, voir docblock ci-dessus.
            'post_status'    => 'any',
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );
        foreach ( $query->posts as $post_id ) {
            foreach ( navi_faq_get_for_post( $post_id ) as $item ) {
                if ( ! empty( $item['group'] ) ) {
                    $themes[ trim( $item['group'] ) ] = true;
                }
            }
        }
    }

    foreach ( navi_faq_taxonomies() as $taxonomy ) {
        $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids' ) );
        if ( is_wp_error( $terms ) ) {
            continue;
        }
        foreach ( $terms as $term_id ) {
            foreach ( navi_faq_get_for_term( $term_id ) as $item ) {
                if ( ! empty( $item['group'] ) ) {
                    $themes[ trim( $item['group'] ) ] = true;
                }
            }
        }
    }

    return array_keys( $themes );
}

/**
 * Clé compacte identifiant un propriétaire de FAQ, utilisée côté client
 * (menu "Dupliquer vers…", voir navi_faq_render_duplicate_ui() et
 * navi_faq_ajax_duplicate(), admin.php) plutôt que deux champs séparés.
 */
function navi_faq_entity_key( $type, $id ) {
    return $type . ':' . (int) $id;
}

/**
 * Inverse de navi_faq_entity_key() — array( '', 0 ) si la clé est malformée
 * (ne doit normalement jamais arriver hors requête forgée à la main).
 */
function navi_faq_parse_entity_key( $key ) {
    if ( ! is_string( $key ) || ! preg_match( '/^(post|term):(\d+)$/', $key, $matches ) ) {
        return array( '', 0 );
    }
    return array( $matches[1], (int) $matches[2] );
}

/**
 * Cibles possibles pour dupliquer un jeu de FAQ — tous les posts des types
 * couverts et tous les termes des taxonomies couvertes, à l'exclusion de
 * l'entité actuellement éditée (source). Alimente le menu déroulant
 * "Dupliquer vers…" (voir navi_faq_render_duplicate_ui(), admin.php) ; même
 * limite de volumétrie que navi_faq_get_known_themes() (pas de cache pour
 * l'instant, admin uniquement).
 */
function navi_faq_get_duplicate_targets( $exclude_type, $exclude_id ) {
    $targets = array();

    foreach ( navi_faq_post_types() as $post_type ) {
        $post_type_object = get_post_type_object( $post_type );
        $type_label        = $post_type_object ? $post_type_object->labels->singular_name : $post_type;

        $query = new WP_Query( array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ) );
        foreach ( $query->posts as $post ) {
            if ( 'post' === $exclude_type && (int) $exclude_id === $post->ID ) {
                continue;
            }
            $title = get_the_title( $post );
            if ( '' === $title ) {
                continue;
            }
            $targets[] = array(
                'value' => navi_faq_entity_key( 'post', $post->ID ),
                'label' => $type_label . ' : ' . $title,
            );
        }
    }

    foreach ( navi_faq_taxonomies() as $taxonomy ) {
        $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
        if ( is_wp_error( $terms ) ) {
            continue;
        }
        $taxonomy_object = get_taxonomy( $taxonomy );
        $type_label       = $taxonomy_object ? $taxonomy_object->labels->singular_name : $taxonomy;

        foreach ( $terms as $term ) {
            if ( 'term' === $exclude_type && (int) $exclude_id === $term->term_id ) {
                continue;
            }
            $targets[] = array(
                'value' => navi_faq_entity_key( 'term', $term->term_id ),
                'label' => $type_label . ' : ' . $term->name,
            );
        }
    }

    return $targets;
}

/**
 * Peut l'utilisateur courant modifier les FAQ de cette entité ? Vérifié à
 * la fois pour la source et la destination avant une duplication (voir
 * navi_faq_ajax_duplicate(), admin.php) — sans ça, un utilisateur limité à
 * un produit donné pourrait copier son contenu vers un article qu'il n'a
 * pas le droit de modifier, ou l'inverse.
 */
function navi_faq_current_user_can_edit_entity( $type, $id ) {
    if ( 'post' === $type ) {
        return current_user_can( 'edit_post', $id );
    }
    if ( 'term' === $type ) {
        return current_user_can( 'manage_categories' );
    }
    return false;
}
