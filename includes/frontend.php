<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [saito_faq] : FAQ du contexte courant — article/page/produit affiché, ou
 * page d'archive d'une taxonomie couverte. Contrairement à un shortcode qui
 * ne lirait qu'un post_id fixe, celui-ci s'adapte au contexte de la requête
 * pour fonctionner tel quel sur une page d'archive de catégorie (où il n'y a
 * pas de $post unique).
 */
add_shortcode( 'saito_faq', 'saito_faq_shortcode' );
function saito_faq_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'title' => '' ), $atts, 'saito_faq' );
    $items = saito_faq_get_current_context_items();

    if ( empty( $items ) ) {
        return '';
    }

    return saito_faq_render_items_html( $items, $atts['title'] );
}

/**
 * [saito_faq_all] : toutes les FAQ du site (articles/pages/produits +
 * catégories couvertes), groupées par titre — pour une page "Questions
 * fréquentes" centralisée.
 */
add_shortcode( 'saito_faq_all', 'saito_faq_all_shortcode' );
function saito_faq_all_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'title' => __( 'Toutes les questions fréquentes', 'saito-faq' ) ), $atts, 'saito_faq_all' );

    $out = '';
    if ( '' !== $atts['title'] ) {
        $out .= '<h2 class="saito-faq-all-title">' . esc_html( $atts['title'] ) . '</h2>';
    }

    foreach ( navi_faq_post_types() as $post_type ) {
        $query = new WP_Query( array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'meta_key'       => SAITO_FAQ_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- catalogue de test, volumétrie non représentative d'un site en production.
            'post_status'    => 'publish',
            'no_found_rows'  => true,
        ) );
        foreach ( $query->posts as $post ) {
            $items = saito_faq_get_for_post( $post->ID );
            if ( ! empty( $items ) ) {
                $out .= saito_faq_render_items_html( $items, get_the_title( $post ) );
            }
        }
    }

    foreach ( saito_faq_taxonomies() as $taxonomy ) {
        $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
        if ( is_wp_error( $terms ) ) {
            continue;
        }
        foreach ( $terms as $term ) {
            $items = saito_faq_get_for_term( $term->term_id );
            if ( ! empty( $items ) ) {
                $out .= saito_faq_render_items_html( $items, $term->name );
            }
        }
    }

    return $out;
}

/**
 * FAQ associées au contexte de requête courant, indépendamment du fait
 * qu'elles soient déjà en train d'être rendues — réutilisé tel quel par
 * includes/schema.php pour que le JSON-LD corresponde exactement à ce que
 * le shortcode afficherait.
 */
function saito_faq_get_current_context_items() {
    if ( is_tax( saito_faq_taxonomies() ) ) {
        $term = get_queried_object();
        return ( $term && ! is_wp_error( $term ) ) ? saito_faq_get_for_term( $term->term_id ) : array();
    }

    if ( is_singular( navi_faq_post_types() ) ) {
        $post_id = get_the_ID();
        return $post_id ? saito_faq_get_for_post( $post_id ) : array();
    }

    return array();
}

/**
 * Bloc FAQ complet (titre + accordéon simple, ou accordéon groupé par
 * onglets si les questions ont plusieurs thèmes distincts renseignés dans
 * l'admin — voir saito_faq_render_row_markup(), admin.php).
 */
function saito_faq_render_items_html( array $items, $title = '' ) {
    $groups       = saito_faq_group_items_by_theme( $items );
    $named_themes = array_filter( array_keys( $groups ), 'strlen' );

    $out = '<div class="saito-faq-block">';
    if ( '' !== $title ) {
        $out .= '<h3 class="saito-faq-block-title">' . esc_html( $title ) . '</h3>';
    }

    // Moins de 2 thèmes nommés : un accordéon simple suffit, les onglets
    // n'apporteraient rien (voire nuiraient à la lisibilité pour 1 seule
    // catégorie).
    $out .= ( count( $named_themes ) < 2 )
        ? saito_faq_render_accordion_html( $items )
        : saito_faq_render_tabbed_html( $groups );

    $out .= '</div>';
    return $out;
}

/**
 * Regroupe les FAQ par thème en conservant l'ordre de première apparition
 * (important pour que l'onglet actif par défaut soit celui de la première
 * question saisie, comme dans l'admin) — un thème vide ('') regroupe les
 * questions non classées, placé en dernier onglet par
 * saito_faq_render_tabbed_html() plutôt qu'en premier.
 */
function saito_faq_group_items_by_theme( array $items ) {
    $groups = array();
    foreach ( $items as $item ) {
        $theme = isset( $item['group'] ) ? trim( (string) $item['group'] ) : '';
        if ( ! isset( $groups[ $theme ] ) ) {
            $groups[ $theme ] = array();
        }
        $groups[ $theme ][] = $item;
    }
    return $groups;
}

function saito_faq_render_accordion_html( array $items ) {
    $out = '<div class="saito-faq-accordion">';
    foreach ( $items as $item ) {
        $out .= saito_faq_render_item_html( $item );
    }
    $out .= '</div>';
    return $out;
}

/**
 * <details>/<summary> plutôt qu'un bouton + JS pour le pli/dépli : widget de
 * divulgation natif du navigateur (état ouvert/fermé, focus, annonce aux
 * lecteurs d'écran déjà gérés nativement), qui fonctionne même si le JS ne
 * charge pas — amélioration par rapport à un accordéon piloté uniquement en
 * JS.
 *
 * id="faq-N" (compteur global à la requête, voir saito_faq_next_anchor_id())
 * donne à chaque question une ancre partageable (#faq-3) — un lien vers une
 * réponse précise plutôt que vers la page entière. Repérée par
 * assets/js/saito-faq-front.js au chargement pour déplier automatiquement la
 * bonne question (et activer son onglet si elle est dans un panneau caché).
 */
function saito_faq_render_item_html( array $item ) {
    $out  = '<details class="saito-faq-item" id="' . esc_attr( saito_faq_next_anchor_id() ) . '">';
    $out .= '<summary class="saito-faq-question">' . esc_html( $item['question'] ) . '</summary>';
    $out .= '<div class="saito-faq-answer">' . wp_kses_post( wpautop( $item['answer'] ) ) . '</div>';
    $out .= '</details>';
    return $out;
}

function saito_faq_next_anchor_id() {
    static $counter = 0;
    $counter++;
    return 'faq-' . $counter;
}

/**
 * Mise en page à onglets (menu latéral + panneau) — structure ARIA Tabs
 * (role="tablist"/"tab"/"tabpanel", aria-selected, tabindex en "roving
 * tabindex") pour que la navigation clavier flèches gauche/droite fonctionne
 * (voir assets/js/saito-faq-front.js), ce que l'intégration précédente
 * n'avait pas.
 */
function saito_faq_render_tabbed_html( array $groups ) {
    static $instance = 0;
    $instance++;
    $uid = 'saito-faq-' . $instance;

    // Thème vide (questions non classées) en dernier onglet plutôt qu'en
    // premier : un thème nommé est plus utile en position par défaut.
    $labels = array_keys( $groups );
    usort( $labels, function ( $a, $b ) {
        if ( '' === $a ) {
            return 1;
        }
        if ( '' === $b ) {
            return -1;
        }
        return 0;
    } );

    $out  = '<div class="saito-faq-tabs">';
    $out .= '<div class="saito-faq-tabs-nav" role="tablist" aria-label="' . esc_attr__( 'Catégories de questions', 'saito-faq' ) . '">';
    foreach ( $labels as $index => $label ) {
        $tab_id   = $uid . '-tab-' . $index;
        $panel_id = $uid . '-panel-' . $index;
        $display  = ( '' !== $label ) ? $label : __( 'Autres questions', 'saito-faq' );
        $active   = ( 0 === $index );
        $out     .= '<button type="button" class="saito-faq-tab-btn' . ( $active ? ' active' : '' ) . '"'
            . ' id="' . esc_attr( $tab_id ) . '" role="tab"'
            . ' aria-selected="' . ( $active ? 'true' : 'false' ) . '"'
            . ' aria-controls="' . esc_attr( $panel_id ) . '"'
            . ' tabindex="' . ( $active ? '0' : '-1' ) . '">'
            . esc_html( $display ) . '</button>';
    }
    $out .= '</div>';

    $out .= '<div class="saito-faq-tabs-panels">';
    foreach ( $labels as $index => $label ) {
        $tab_id   = $uid . '-tab-' . $index;
        $panel_id = $uid . '-panel-' . $index;
        $active   = ( 0 === $index );
        $out     .= '<div class="saito-faq-tab-panel' . ( $active ? ' active' : '' ) . '" id="' . esc_attr( $panel_id ) . '"'
            . ' role="tabpanel" aria-labelledby="' . esc_attr( $tab_id ) . '"' . ( ! $active ? ' hidden' : '' ) . '>';
        $out     .= saito_faq_render_accordion_html( $groups[ $label ] );
        $out     .= '</div>';
    }
    $out .= '</div>';
    $out .= '</div>';

    return $out;
}

/**
 * Affichage automatique en haut d'une page d'archive de catégorie de
 * produit : contrairement à un article/une page/un produit, une page
 * d'archive n'a pas de zone de contenu où coller [saito_faq] à la main.
 */
add_action( 'woocommerce_archive_description', 'saito_faq_render_on_term_archive', 20 );
function saito_faq_render_on_term_archive() {
    if ( ! is_tax( saito_faq_taxonomies() ) ) {
        return;
    }
    $items = saito_faq_get_current_context_items();
    if ( empty( $items ) ) {
        return;
    }
    echo saito_faq_render_items_html( $items, __( 'Questions fréquentes', 'saito-faq' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- déjà échappé dans saito_faq_render_items_html().
}

add_action( 'wp_enqueue_scripts', 'saito_faq_enqueue_front_assets' );
function saito_faq_enqueue_front_assets() {
    saito_faq_enqueue_shared_style();
    wp_enqueue_script( 'saito-faq-front', SAITO_FAQ_URL . 'assets/js/saito-faq-front.js', array(), SAITO_FAQ_VERSION, true );
}
