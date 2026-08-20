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
    navi_faq_render_editor_ui( navi_faq_get_for_post( $post->ID ), 'navi_faq_post', 'post', $post->ID );
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
            navi_faq_render_editor_ui( navi_faq_get_for_post( $post->ID ), 'navi_faq_post', 'post', $post->ID );
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
            navi_faq_render_editor_ui( navi_faq_get_for_term( $term->term_id ), 'navi_faq_term', 'term', $term->term_id );
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

function navi_faq_render_editor_ui( array $items, $field_prefix, $source_type = '', $source_id = 0 ) {
    $known_themes = navi_faq_get_known_themes();
    ?>
    <div class="navi-faq-editor" data-prefix="<?php echo esc_attr( $field_prefix ); ?>" data-empty-label="<?php esc_attr_e( 'Aucune question pour l’instant.', 'navi-faq' ); ?>" data-next-number="<?php echo (int) ( count( $items ) + 1 ); ?>">
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
        <?php
        // Zone d'annonce dédiée (WCAG 4.1.3, statut) plutôt qu'un aria-live
        // posé directement sur .navi-faq-rows : sur cette dernière, un
        // lecteur d'écran annoncerait tout le contenu de chaque nouvelle
        // ligne (libellés, éditeur TinyMCE...) à chaque ajout, bien trop
        // verbeux — voir "Question ajoutée"/"Question supprimée" dans
        // assets/js/navi-faq-admin.js. .screen-reader-text : classe
        // utilitaire fournie par WordPress lui-même en admin, pas besoin de
        // la redéfinir.
        ?>
        <div class="navi-faq-status screen-reader-text" aria-live="polite" aria-atomic="true"></div>
        <?php if ( $source_id ) : ?>
            <?php navi_faq_render_duplicate_ui( $source_type, $source_id ); ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * "Dupliquer vers…" : copie le jeu de FAQ ENREGISTRÉ (pas le formulaire en
 * cours d'édition, pour éviter de devoir synchroniser le contenu TinyMCE
 * pas encore soumis) d'une entité vers une autre — utile pour des produits
 * très proches (variantes) qui partagent les mêmes questions. Traité en
 * AJAX (navi_faq_ajax_duplicate()) plutôt qu'à la sauvegarde du formulaire :
 * la cible n'a aucun rapport avec l'entité en cours d'édition.
 */
function navi_faq_render_duplicate_ui( $source_type, $source_id ) {
    $targets = navi_faq_get_duplicate_targets( $source_type, $source_id );
    if ( empty( $targets ) ) {
        return;
    }
    ?>
    <div class="navi-faq-duplicate">
        <h4><?php esc_html_e( 'Dupliquer ces FAQ vers…', 'navi-faq' ); ?></h4>
        <p class="description"><?php esc_html_e( 'Remplace les FAQ existantes de la destination par celles actuellement enregistrées ici — pensez à sauvegarder vos modifications avant de dupliquer.', 'navi-faq' ); ?></p>
        <p>
            <label class="screen-reader-text" for="<?php echo esc_attr( $source_type . '_' . $source_id ); ?>_duplicate_target"><?php esc_html_e( 'Dupliquer vers', 'navi-faq' ); ?></label>
            <select class="navi-faq-duplicate-target" id="<?php echo esc_attr( $source_type . '_' . $source_id ); ?>_duplicate_target">
                <option value=""><?php esc_html_e( '— Choisir une destination —', 'navi-faq' ); ?></option>
                <?php foreach ( $targets as $target ) : ?>
                    <option value="<?php echo esc_attr( $target['value'] ); ?>"><?php echo esc_html( $target['label'] ); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="button navi-faq-duplicate-btn" data-source="<?php echo esc_attr( navi_faq_entity_key( $source_type, $source_id ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'navi_faq_duplicate' ) ); ?>"><?php esc_html_e( 'Dupliquer', 'navi-faq' ); ?></button>
        </p>
        <p class="navi-faq-duplicate-status" role="status"></p>
    </div>
    <?php
}

add_action( 'wp_ajax_navi_faq_duplicate', 'navi_faq_ajax_duplicate' );
function navi_faq_ajax_duplicate() {
    check_ajax_referer( 'navi_faq_duplicate', 'nonce' );

    $source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
    $target = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '';

    list( $source_type, $source_id ) = navi_faq_parse_entity_key( $source );
    list( $target_type, $target_id ) = navi_faq_parse_entity_key( $target );

    if ( ! $source_type || ! $target_type ) {
        wp_send_json_error( array( 'message' => __( 'Destination invalide.', 'navi-faq' ) ) );
    }

    if ( ! navi_faq_current_user_can_edit_entity( $source_type, $source_id )
        || ! navi_faq_current_user_can_edit_entity( $target_type, $target_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Vous n’avez pas les droits nécessaires sur la source ou la destination.', 'navi-faq' ) ) );
    }

    $items = ( 'post' === $source_type ) ? navi_faq_get_for_post( $source_id ) : navi_faq_get_for_term( $source_id );

    if ( 'post' === $target_type ) {
        navi_faq_save_for_post( $target_id, $items );
    } else {
        navi_faq_save_for_term( $target_id, $items );
    }

    wp_send_json_success( array(
        /* translators: %d: nombre de questions dupliquées. */
        'message' => sprintf( _n( '%d question dupliquée.', '%d questions dupliquées.', count( $items ), 'navi-faq' ), count( $items ) ),
    ) );
}

/**
 * Réglages de l'éditeur visuel de la réponse — utilisés à la fois ici (rendu
 * PHP des lignes déjà enregistrées, via wp_editor()) et en JS pour les
 * lignes ajoutées dynamiquement (wp.editor.initialize(), voir
 * navi_faq_enqueue_admin_assets() plus bas et assets/js/navi-faq-admin.js) :
 * les deux DOIVENT rester synchronisés pour un rendu cohérent qu'une ligne
 * vienne du serveur ou du clic sur "Ajouter une question". Barre d'outils
 * volontairement réduite (gras/italique/listes/lien) : une réponse de FAQ
 * n'a pas besoin de la mise en forme complète d'un article.
 */
function navi_faq_editor_tinymce_settings() {
    return array(
        'toolbar1'                     => 'bold italic bullist numlist link unlink',
        'toolbar2'                     => '',
        'menubar'                      => false,
        'statusbar'                    => false,
        'paste_remove_styles'          => true,
        'paste_strip_class_attributes' => 'all',
        'valid_elements'               => 'p,br,strong/b,em/i,ul,ol,li,a[href|target|rel]',
    );
}

function navi_faq_render_row_markup( $field_prefix, $number, $question = '', $answer = '', $group = '' ) {
    $editor_id   = 'navi_faq_answer_' . $number;
    $group_id    = $field_prefix . '_group_' . $number;
    $question_id = $field_prefix . '_question_' . $number;
    $title_id    = $field_prefix . '_row_title_' . $number;
    ?>
    <div class="navi-faq-row" role="group" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
        <div class="navi-faq-row-header">
            <span class="navi-faq-row-title" id="<?php echo esc_attr( $title_id ); ?>">
                <?php
                /* translators: %d: numéro de la question dans la liste */
                echo esc_html( sprintf( __( 'Question #%d', 'navi-faq' ), $number ) );
                ?>
            </span>
            <button type="button" class="navi-faq-remove-row" aria-label="<?php esc_attr_e( 'Supprimer cette question', 'navi-faq' ); ?>">&times;</button>
        </div>
        <div class="navi-faq-row-body">
            <p class="navi-faq-field navi-faq-field-group">
                <label for="<?php echo esc_attr( $group_id ); ?>"><?php esc_html_e( 'Thème', 'navi-faq' ); ?> <span class="navi-faq-field-optional"><?php esc_html_e( '(optionnel)', 'navi-faq' ); ?></span></label>
                <input type="text" class="widefat" id="<?php echo esc_attr( $group_id ); ?>" list="navi-faq-themes-datalist" name="<?php echo esc_attr( $field_prefix ); ?>_group[]" value="<?php echo esc_attr( $group ); ?>" placeholder="<?php esc_attr_e( 'ex. Livraison, Nos parfums…', 'navi-faq' ); ?>" />
                <p class="description"><?php esc_html_e( 'Donnez le même thème à plusieurs questions pour les regrouper sous un même onglet en front. Laissez vide pour un simple accordéon sans onglets.', 'navi-faq' ); ?></p>
            </p>
            <p class="navi-faq-field">
                <label for="<?php echo esc_attr( $question_id ); ?>"><?php esc_html_e( 'Question', 'navi-faq' ); ?></label>
                <input type="text" class="widefat" id="<?php echo esc_attr( $question_id ); ?>" name="<?php echo esc_attr( $field_prefix ); ?>_question[]" value="<?php echo esc_attr( $question ); ?>" placeholder="<?php esc_attr_e( 'ex. Livrez-vous à l’international ?', 'navi-faq' ); ?>" />
                <p class="description"><?php esc_html_e( 'Telle qu’un client pourrait la poser — affichée en titre cliquable.', 'navi-faq' ); ?></p>
            </p>
            <div class="navi-faq-field navi-faq-field-answer">
                <label for="<?php echo esc_attr( $editor_id ); ?>"><?php esc_html_e( 'Réponse', 'navi-faq' ); ?></label>
                <p class="description"><?php esc_html_e( 'Affichée sous la question une fois dépliée. Le bouton lien de la barre d’outils permet de rechercher directement une page ou un produit du site, ou de coller une URL externe.', 'navi-faq' ); ?></p>
                <?php
                wp_editor(
                    $answer,
                    $editor_id,
                    array(
                        'textarea_name' => $field_prefix . '_answer[]',
                        'textarea_rows' => 5,
                        'media_buttons' => false,
                        'teeny'         => false,
                        'quicktags'     => false,
                        'tinymce'       => navi_faq_editor_tinymce_settings(),
                    )
                );
                ?>
                <p class="navi-faq-char-count" data-editor="<?php echo esc_attr( $editor_id ); ?>"></p>
            </div>
        </div>
    </div>
    <?php
}

add_action( 'admin_enqueue_scripts', 'navi_faq_enqueue_admin_assets' );
function navi_faq_enqueue_admin_assets( $hook_suffix ) {
    if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php', 'term.php', 'edit-tags.php' ), true ) ) {
        return;
    }

    // Garantit que wp.editor.initialize()/remove() sont disponibles en JS
    // même si aucune ligne n'est encore affichée au chargement (produit/
    // catégorie sans FAQ pour l'instant) : sans ligne existante, aucun
    // wp_editor() PHP n'est appelé plus bas, qui aurait sinon chargé ces
    // scripts lui-même — voir "Ajouter une question" dans
    // assets/js/navi-faq-admin.js.
    wp_enqueue_editor();

    navi_faq_enqueue_shared_style();
    wp_enqueue_script( 'navi-faq-admin', NAVI_FAQ_URL . 'assets/js/navi-faq-admin.js', array( 'editor' ), NAVI_FAQ_VERSION, true );
    wp_localize_script( 'navi-faq-admin', 'naviFaqAdminI18n', array(
        'group'            => __( 'Thème (optionnel)', 'navi-faq' ),
        'groupPlaceholder' => __( 'ex. Livraison, Nos parfums…', 'navi-faq' ),
        'question'         => __( 'Question', 'navi-faq' ),
        'answer'           => __( 'Réponse', 'navi-faq' ),
        'remove'           => __( 'Supprimer cette question', 'navi-faq' ),
        /* translators: %d sera remplacé par le numéro de la question (JS, voir assets/js/navi-faq-admin.js). */
        'questionNumber'   => __( 'Question #%d', 'navi-faq' ),
        // Annoncées via la zone de statut (WCAG 4.1.3), voir
        // navi_faq_render_editor_ui() (.navi-faq-status) ci-dessus.
        'rowAdded'         => __( 'Question ajoutée.', 'navi-faq' ),
        'rowRemoved'       => __( 'Question supprimée.', 'navi-faq' ),
        'confirmRemove'    => __( 'Supprimer cette question ? Cette action ne peut pas être annulée.', 'navi-faq' ),
        'questionPlaceholder' => __( 'ex. Livrez-vous à l’international ?', 'navi-faq' ),
        /* translators: %d sera remplacé par le nombre de caractères (texte brut) de la réponse. */
        'charCount'        => __( '%d caractères', 'navi-faq' ),
        /* translators: %d sera remplacé par le nombre de caractères (texte brut) de la réponse. */
        'charCountLong'    => __( '%d caractères — plutôt long pour un extrait Google (environ 300 recommandés).', 'navi-faq' ),
        'duplicateChooseTarget' => __( 'Choisissez d’abord une destination.', 'navi-faq' ),
        'duplicateConfirm'      => __( 'Remplacer les FAQ de la destination par celles-ci ?', 'navi-faq' ),
        'duplicateInProgress'   => __( 'Duplication en cours…', 'navi-faq' ),
        'duplicateError'        => __( 'Une erreur est survenue, réessayez.', 'navi-faq' ),
    ) );
    wp_localize_script( 'navi-faq-admin', 'naviFaqEditorSettings', array(
        // Doit rester en phase avec navi_faq_editor_tinymce_settings() —
        // même barre d'outils, que la ligne vienne du serveur (wp_editor())
        // ou d'un clic sur "Ajouter une question" (wp.editor.initialize()).
        'tinymce'      => navi_faq_editor_tinymce_settings(),
        'quicktags'    => false,
        'mediaButtons' => false,
    ) );
}
