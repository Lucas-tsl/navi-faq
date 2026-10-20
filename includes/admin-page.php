<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Saito > FAQ : vue d'ensemble des contenus qui ont une FAQ, et rappel des
 * shortcodes. Les questions elles-mêmes se saisissent sur chaque contenu
 * (metabox, ou panneau produit partagé — voir admin.php et saito-panel.php).
 */
add_action( 'admin_menu', 'saito_faq_add_admin_page' );
function saito_faq_add_admin_page() {
    add_submenu_page( saito_hub_menu_slug(), __( 'FAQ', 'saito-faq' ), __( 'FAQ', 'saito-faq' ), 'edit_posts', 'saito-faq', 'saito_faq_render_admin_page' );
}

/**
 * Contenus et termes couverts qui ont au moins une question.
 *
 * @return array[] Lignes ['label', 'type', 'count', 'edit', 'view'].
 */
function saito_faq_admin_rows() {
    $rows = array();
    foreach ( saito_faq_post_types() as $post_type ) {
        $object = get_post_type_object( $post_type );
        if ( ! $object ) {
            continue;
        }
        $query = new WP_Query( array(
            'post_type'      => $post_type,
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'posts_per_page' => 200,
            'meta_key'       => SAITO_FAQ_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- écran d'admin, 200 contenus au plus.
            'no_found_rows'  => true,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );
        foreach ( $query->posts as $post ) {
            $count = count( saito_faq_get_for_post( $post->ID ) );
            if ( $count ) {
                $rows[] = array(
                    'label' => get_the_title( $post ),
                    'type'  => $object->labels->singular_name,
                    'count' => $count,
                    'edit'  => get_edit_post_link( $post->ID, 'raw' ),
                    'view'  => get_permalink( $post ),
                );
            }
        }
    }
    foreach ( saito_faq_taxonomies() as $taxonomy ) {
        $object = get_taxonomy( $taxonomy );
        $terms  = $object ? get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'meta_key' => SAITO_FAQ_META_KEY ) ) : array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- écran d'admin.
        if ( is_wp_error( $terms ) ) {
            continue;
        }
        foreach ( $terms as $term ) {
            $count = count( saito_faq_get_for_term( $term->term_id ) );
            if ( $count ) {
                $link   = get_term_link( $term );
                $rows[] = array(
                    'label' => $term->name,
                    'type'  => $object->labels->singular_name,
                    'count' => $count,
                    'edit'  => get_edit_term_link( $term->term_id, $taxonomy ),
                    'view'  => is_wp_error( $link ) ? '' : $link,
                );
            }
        }
    }
    return $rows;
}

function saito_faq_render_admin_page() {
    $rows = saito_faq_admin_rows();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'FAQ', 'saito-faq' ); ?></h1>
        <p><?php esc_html_e( 'Les questions se saisissent directement sur chaque article, page, produit ou catégorie de produits, dans la boîte « FAQ » de l\'écran de modification.', 'saito-faq' ); ?></p>

        <h2><?php esc_html_e( 'Contenus avec une FAQ', 'saito-faq' ); ?></h2>
        <?php if ( empty( $rows ) ) : ?>
            <p><?php esc_html_e( 'Aucune FAQ pour l\'instant.', 'saito-faq' ); ?></p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Contenu', 'saito-faq' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'saito-faq' ); ?></th>
                        <th><?php esc_html_e( 'Questions', 'saito-faq' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'saito-faq' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rows as $row ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $row['label'] ); ?></strong></td>
                            <td><?php echo esc_html( $row['type'] ); ?></td>
                            <td><?php echo (int) $row['count']; ?></td>
                            <td>
                                <?php if ( $row['edit'] ) : ?>
                                    <a href="<?php echo esc_url( $row['edit'] ); ?>"><?php esc_html_e( 'Modifier', 'saito-faq' ); ?></a>
                                <?php endif; ?>
                                <?php if ( $row['view'] ) : ?>
                                    | <a href="<?php echo esc_url( $row['view'] ); ?>"><?php esc_html_e( 'Voir', 'saito-faq' ); ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2><?php esc_html_e( 'Shortcodes', 'saito-faq' ); ?></h2>
        <table class="widefat striped">
            <tbody>
                <tr>
                    <td><code>[saito_faq]</code></td>
                    <td><?php esc_html_e( 'FAQ du contenu affiché, à placer dans un article, une page ou une fiche produit. Option : title="Questions fréquentes".', 'saito-faq' ); ?></td>
                </tr>
                <tr>
                    <td><code>[saito_faq_all]</code></td>
                    <td><?php esc_html_e( 'Toutes les FAQ du site, groupées par contenu, pour une page « Questions fréquentes » centrale.', 'saito-faq' ); ?></td>
                </tr>
            </tbody>
        </table>
        <p class="description"><?php esc_html_e( 'Sur les catégories de produits, la FAQ s\'affiche automatiquement sous la description de la catégorie.', 'saito-faq' ); ?></p>
    </div>
    <?php
}
