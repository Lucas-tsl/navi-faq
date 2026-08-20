<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// -------------------- Articles / pages --------------------
// Les produits n'utilisent pas ce metabox générique : voir plus bas,
// navi_faq_add_product_tab(), qui les intègre plutôt dans l'onglet
// "Données produit" natif de WooCommerce (même patron que l'onglet
// "Stories (Navi)" du plugin compagnon Saito Navi).

add_action( 'add_meta_boxes', 'navi_faq_register_meta_box' );
function navi_faq_register_meta_box() {
    foreach ( navi_faq_post_types() as $post_type ) {
        if ( 'product' === $post_type ) {
            continue;
        }
        add_meta_box( 'navi_faq_box', __( 'FAQ (Navi)', 'navi-faq' ), 'navi_faq_render_meta_box', $post_type, 'normal', 'default' );
    }
}

function navi_faq_render_meta_box( $post ) {
    wp_nonce_field( 'navi_faq_save_' . $post->ID, 'navi_faq_nonce' );
    navi_faq_render_editor_ui( navi_faq_get_for_post( $post->ID ), 'navi_faq_post' );
}

// -------------------- Produits (onglet "Données produit") --------------------

add_filter( 'woocommerce_product_data_tabs', 'navi_faq_add_product_tab' );
function navi_faq_add_product_tab( $tabs ) {
    if ( ! in_array( 'product', navi_faq_post_types(), true ) ) {
        return $tabs;
    }
    $tabs['navi_faq'] = array(
        'label'    => __( 'FAQ (Navi)', 'navi-faq' ),
        'target'   => 'navi_faq_product_data',
        'class'    => array(),
        'priority' => 65,
    );
    return $tabs;
}

add_action( 'woocommerce_product_data_panels', 'navi_faq_render_product_data_panel' );
function navi_faq_render_product_data_panel() {
    if ( ! in_array( 'product', navi_faq_post_types(), true ) ) {
        return;
    }
    global $post;
    if ( ! $post ) {
        return;
    }
    ?>
    <div id="navi_faq_product_data" class="panel woocommerce_options_panel hidden">
        <div class="options_group" style="padding: 12px 20px;">
            <?php
            wp_nonce_field( 'navi_faq_save_' . $post->ID, 'navi_faq_nonce' );
            navi_faq_render_editor_ui( navi_faq_get_for_post( $post->ID ), 'navi_faq_post' );
            ?>
        </div>
    </div>
    <?php
}

add_action( 'save_post', 'navi_faq_save_post_meta' );
function navi_faq_save_post_meta( $post_id ) {
    if ( ! in_array( get_post_type( $post_id ), navi_faq_post_types(), true ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- le nonce est vérifié juste en dessous.
    if ( ! isset( $_POST['navi_faq_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['navi_faq_nonce'] ) ), 'navi_faq_save_' . $post_id ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $questions = isset( $_POST['navi_faq_post_question'] ) ? (array) wp_unslash( $_POST['navi_faq_post_question'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitisé dans navi_faq_sanitize_items().
    $answers   = isset( $_POST['navi_faq_post_answer'] ) ? (array) wp_unslash( $_POST['navi_faq_post_answer'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitisé dans navi_faq_sanitize_items().
    $groups    = isset( $_POST['navi_faq_post_group'] ) ? (array) wp_unslash( $_POST['navi_faq_post_group'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitisé dans navi_faq_sanitize_items().

    navi_faq_save_for_post( $post_id, navi_faq_sanitize_items( $questions, $answers, $groups ) );
}

// -------------------- Catégories (et autres taxonomies couvertes) --------------------

add_action( 'init', 'navi_faq_register_term_hooks' );
function navi_faq_register_term_hooks() {
    foreach ( navi_faq_taxonomies() as $taxonomy ) {
        add_action( "{$taxonomy}_edit_form_fields", 'navi_faq_render_term_field' );
        add_action( "edited_{$taxonomy}", 'navi_faq_save_term_meta' );
        add_action( "create_{$taxonomy}", 'navi_faq_save_term_meta' );
    }
}

function navi_faq_render_term_field( $term ) {
    ?>
    <tr class="form-field">
        <th scope="row"><label><?php esc_html_e( 'FAQ (Navi)', 'navi-faq' ); ?></label></th>
        <td>
            <?php
            wp_nonce_field( 'navi_faq_save_term_' . $term->term_id, 'navi_faq_term_nonce' );
            navi_faq_render_editor_ui( navi_faq_get_for_term( $term->term_id ), 'navi_faq_term' );
            ?>
            <p class="description"><?php esc_html_e( 'Affichées automatiquement en haut de la page de cette catégorie sur le site.', 'navi-faq' ); ?></p>
        </td>
    </tr>
    <?php
}

function navi_faq_save_term_meta( $term_id ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- le nonce est vérifié juste en dessous.
    if ( ! isset( $_POST['navi_faq_term_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['navi_faq_term_nonce'] ) ), 'navi_faq_save_term_' . $term_id ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_categories' ) ) {
        return;
    }

    $questions = isset( $_POST['navi_faq_term_question'] ) ? (array) wp_unslash( $_POST['navi_faq_term_question'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitisé dans navi_faq_sanitize_items().
    $answers   = isset( $_POST['navi_faq_term_answer'] ) ? (array) wp_unslash( $_POST['navi_faq_term_answer'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitisé dans navi_faq_sanitize_items().
    $groups    = isset( $_POST['navi_faq_term_group'] ) ? (array) wp_unslash( $_POST['navi_faq_term_group'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitisé dans navi_faq_sanitize_items().

    navi_faq_save_for_term( $term_id, navi_faq_sanitize_items( $questions, $answers, $groups ) );
}

// -------------------- UI partagée (article/page/produit ET catégorie) --------------------
// Un seul balisage pour les deux contextes (data-prefix distingue les noms
// de champs à la soumission) : évite de dupliquer le HTML des rangées et le
// JS d'ajout/suppression entre le metabox post et le formulaire de terme.

function navi_faq_render_editor_ui( array $items, $field_prefix ) {
    $known_themes = navi_faq_get_known_themes();
    ?>
    <div class="navi-faq-editor" data-prefix="<?php echo esc_attr( $field_prefix ); ?>" data-empty-label="<?php esc_attr_e( 'Aucune question pour l’instant.', 'navi-faq' ); ?>">
        <?php if ( $known_themes ) : ?>
            <datalist id="navi-faq-themes-datalist">
                <?php foreach ( $known_themes as $theme ) : ?>
                    <option value="<?php echo esc_attr( $theme ); ?>"></option>
                <?php endforeach; ?>
            </datalist>
        <?php endif; ?>

        <div class="navi-faq-rows">
            <?php if ( empty( $items ) ) : ?>
                <p class="navi-faq-empty"><?php esc_html_e( 'Aucune question pour l’instant.', 'navi-faq' ); ?></p>
            <?php endif; ?>
            <?php foreach ( $items as $index => $item ) : ?>
                <?php navi_faq_render_row_markup( $field_prefix, $index + 1, $item['question'], $item['answer'], isset( $item['group'] ) ? $item['group'] : '' ); ?>
            <?php endforeach; ?>
        </div>
        <p><button type="button" class="button navi-faq-add-row">+ <?php esc_html_e( 'Ajouter une question', 'navi-faq' ); ?></button></p>
        <p class="description"><?php esc_html_e( 'Donnez le même thème à plusieurs questions pour les regrouper sous un même onglet en front (ex. "Livraison" sur 3 questions). Laissez vide pour un simple accordéon sans onglets.', 'navi-faq' ); ?></p>
    </div>
    <?php
}

function navi_faq_render_row_markup( $field_prefix, $number, $question = '', $answer = '', $group = '' ) {
    ?>
    <div class="navi-faq-row">
        <div class="navi-faq-row-header">
            <span class="navi-faq-row-title">
                <?php
                /* translators: %d: numéro de la question dans la liste */
                echo esc_html( sprintf( __( 'Question #%d', 'navi-faq' ), $number ) );
                ?>
            </span>
            <button type="button" class="navi-faq-remove-row" aria-label="<?php esc_attr_e( 'Supprimer cette question', 'navi-faq' ); ?>">&times;</button>
        </div>
        <div class="navi-faq-row-body">
            <p class="navi-faq-field navi-faq-field-group">
                <label><?php esc_html_e( 'Thème (optionnel)', 'navi-faq' ); ?></label>
                <input type="text" class="widefat" list="navi-faq-themes-datalist" name="<?php echo esc_attr( $field_prefix ); ?>_group[]" value="<?php echo esc_attr( $group ); ?>" placeholder="<?php esc_attr_e( 'ex. Livraison, Nos parfums…', 'navi-faq' ); ?>" />
            </p>
            <p class="navi-faq-field">
                <label><?php esc_html_e( 'Question', 'navi-faq' ); ?></label>
                <input type="text" class="widefat" name="<?php echo esc_attr( $field_prefix ); ?>_question[]" value="<?php echo esc_attr( $question ); ?>" />
            </p>
            <p class="navi-faq-field">
                <label><?php esc_html_e( 'Réponse', 'navi-faq' ); ?></label>
                <textarea class="widefat" rows="3" name="<?php echo esc_attr( $field_prefix ); ?>_answer[]"><?php echo esc_textarea( $answer ); ?></textarea>
            </p>
        </div>
    </div>
    <?php
}

add_action( 'admin_enqueue_scripts', 'navi_faq_enqueue_admin_assets' );
function navi_faq_enqueue_admin_assets( $hook_suffix ) {
    if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php', 'term.php', 'edit-tags.php' ), true ) ) {
        return;
    }
    navi_faq_enqueue_shared_style();
    wp_enqueue_script( 'navi-faq-admin', NAVI_FAQ_URL . 'assets/js/navi-faq-admin.js', array(), NAVI_FAQ_VERSION, true );
    wp_localize_script( 'navi-faq-admin', 'naviFaqAdminI18n', array(
        'group'            => __( 'Thème (optionnel)', 'navi-faq' ),
        'groupPlaceholder' => __( 'ex. Livraison, Nos parfums…', 'navi-faq' ),
        'question'         => __( 'Question', 'navi-faq' ),
        'answer'           => __( 'Réponse', 'navi-faq' ),
        'remove'           => __( 'Supprimer cette question', 'navi-faq' ),
        /* translators: %d sera remplacé par le numéro de la question (JS, voir assets/js/navi-faq-admin.js). */
        'questionNumber'   => __( 'Question #%d', 'navi-faq' ),
    ) );
}
