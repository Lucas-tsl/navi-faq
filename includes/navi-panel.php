<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Panneau "Navi" — metabox autonome sur la fiche produit, hors "Données
 * produit" (WooCommerce). Espace partagé pensé pour accueillir plusieurs
 * fonctionnalités de la famille Navi sous forme d'onglets internes (FAQ ici,
 * et potentiellement Stories du plugin compagnon Saito Navi à l'avenir),
 * plutôt que chaque fonctionnalité ajoutant sa propre entrée plate dans
 * "Données produit".
 *
 * Registre ouvert via le filtre 'navi_product_panel_tabs' : n'importe quel
 * plugin peut y ajouter un onglet (voir navi_panel_get_tabs()) sans créer
 * de dépendance au chargement — si navi-faq est absent, ce filtre n'existe
 * simplement pas et l'appel n'a aucun effet pour l'appelant.
 */

add_action( 'add_meta_boxes', 'navi_panel_register_meta_box' );
function navi_panel_register_meta_box() {
    if ( ! in_array( 'product', navi_faq_post_types(), true ) ) {
        return;
    }
    add_meta_box( 'navi_panel_box', __( 'Navi', 'navi-faq' ), 'navi_panel_render', 'product', 'normal', 'default' );
}

/**
 * Onglets enregistrés dans le panneau — chaque entrée :
 * ['label' => string, 'callback' => callable( WP_Post $post )]. FAQ
 * s'enregistre elle-même juste en dessous.
 */
function navi_panel_get_tabs() {
    $tabs = apply_filters( 'navi_product_panel_tabs', array() );
    return is_array( $tabs ) ? $tabs : array();
}

add_filter( 'navi_product_panel_tabs', 'navi_faq_register_panel_tab' );
function navi_faq_register_panel_tab( $tabs ) {
    $tabs['faq'] = array(
        'label'    => __( 'FAQ', 'navi-faq' ),
        'callback' => 'navi_faq_render_panel_tab',
    );
    return $tabs;
}

function navi_faq_render_panel_tab( $post ) {
    wp_nonce_field( 'navi_faq_save_' . $post->ID, 'navi_faq_nonce' );
    navi_faq_render_editor_ui( navi_faq_get_for_post( $post->ID ), 'navi_faq_post', 'post', $post->ID );
}

/**
 * Un seul onglet enregistré (FAQ seule, cas par défaut sans autre
 * fonctionnalité Navi active) : son contenu s'affiche directement, sans le
 * "meuble" d'onglets — même logique que le front, qui n'affiche des
 * onglets que si 2 thèmes ou plus sont réellement utilisés (voir
 * navi_faq_render_items_html(), frontend.php).
 */
function navi_panel_render( $post ) {
    $tabs = navi_panel_get_tabs();
    if ( empty( $tabs ) ) {
        return;
    }

    if ( 1 === count( $tabs ) ) {
        $only = reset( $tabs );
        call_user_func( $only['callback'], $post );
        return;
    }
    ?>
    <div class="navi-panel-tabs">
        <div class="navi-panel-tabs-nav" role="tablist" aria-label="<?php esc_attr_e( 'Fonctionnalités Navi', 'navi-faq' ); ?>">
            <?php
            $first = true;
            foreach ( $tabs as $id => $tab ) :
                ?>
                <button type="button" class="navi-panel-tab-btn<?php echo $first ? ' active' : ''; ?>" role="tab" aria-selected="<?php echo $first ? 'true' : 'false'; ?>" aria-controls="navi-panel-tab-<?php echo esc_attr( $id ); ?>" id="navi-panel-tabbtn-<?php echo esc_attr( $id ); ?>">
                    <?php echo esc_html( $tab['label'] ); ?>
                </button>
                <?php
                $first = false;
            endforeach;
            ?>
        </div>
        <div class="navi-panel-tabs-panels">
            <?php
            $first = true;
            foreach ( $tabs as $id => $tab ) :
                ?>
                <div class="navi-panel-tab-panel<?php echo $first ? ' active' : ''; ?>" id="navi-panel-tab-<?php echo esc_attr( $id ); ?>" role="tabpanel" aria-labelledby="navi-panel-tabbtn-<?php echo esc_attr( $id ); ?>" <?php echo $first ? '' : 'hidden'; ?>>
                    <?php call_user_func( $tab['callback'], $post ); ?>
                </div>
                <?php
                $first = false;
            endforeach;
            ?>
        </div>
    </div>
    <?php
}
