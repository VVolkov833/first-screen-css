<?php

// admin side

namespace FCP\FirstScreenCSS;
defined( 'ABSPATH' ) || exit;

// admin controls
add_action( 'add_meta_boxes', function() {
    if ( !current_user_can( 'administrator' ) ) { return; }

    add_meta_box(
        FCPFSC_FRONT_PREF.'-css-bulk',
        'Bulk apply',
        'FCP\FirstScreenCSS\css_type_meta_bulk_apply',
        FCPFSC_SLUG,
        'normal',
        'high'
    );
    add_meta_box(
        FCPFSC_FRONT_PREF.'-css-disable',
        'Deregister styles and scripts',
        'FCP\FirstScreenCSS\css_type_meta_disable_styles',
        FCPFSC_SLUG,
        'normal',
        'low'
    );
    add_meta_box(
        FCPFSC_FRONT_PREF.'-css-rest',
        'The rest of CSS, which is a not-first-screen',
        'FCP\FirstScreenCSS\css_type_meta_rest_css',
        FCPFSC_SLUG,
        'normal',
        'low'
    );

    list( 'public' => $public_post_types ) = get_all_post_types();
    add_meta_box(
        FCPFSC_FRONT_PREF.'-css',
        'Select First Screen CSS',
        'FCP\FirstScreenCSS\applied_type_meta_main',
        array_keys( $public_post_types ),
        'side',
        'low'
    );
});


// meta boxes
function css_type_meta_bulk_apply() {
    global $post;

    // get post types to print options
    list( 'public' => $public_post_types, 'archive' => $archives_post_types ) = get_all_post_types();

    ?><p><strong>Apply to the following post types</strong></p><?php

    checkboxes( (object) [
        'name' => 'post-types',
        'options' => $public_post_types,
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'post-types' )[0] ?? '',
    ]);

    ?><p><strong>Apply to the Archive pages of the following post types</strong></p><?php

    checkboxes( (object) [
        'name' => 'post-archives',
        'options' => $archives_post_types,
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'post-archives' )[0] ?? '',
    ]);

    ?>
    <p>You can apply this styling to a separate post. Every public post type has a special select box in the right sidebar to pick this or any other first-screen-css.</p>
    <p>You can grab the first screen css of a page with the script: <a href="https://github.com/VVolkov833/first-screen-css-grabber" target="_blank" rel="noopener">github.com/VVolkov833/first-screen-css-grabber</a></p>
    <?php

    checkboxes( (object) [
        'name' => 'development-mode',
        'options' => ['on' => 'Development mode (apply only if the post is visited by administrator)'],
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'development-mode' )[0] ?? '',
    ]);

    ?>
    <input type="hidden" name="<?php echo esc_attr( FCPFSC_PREF ) ?>nonce" value="<?= esc_attr( wp_create_nonce( FCPFSC_PREF.'nonce' ) ) ?>">
    <?php
}

function css_type_meta_disable_styles() {
    global $post;

    ?><p><strong>List the names of STYLES to deregister</strong></p><?php

    input( (object) [
        'name' => 'deregister-style-names',
        'placeholder' => 'my-theme-style, some-plugin-style',
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'deregister-style-names' )[0] ?? '',
    ]);
    ?>Separate names by comma. To deregister all styles set *<?php

    ?><p><strong>List the names of SCRIPTS to deregister</strong></p><?php

    input( (object) [
        'name' => 'deregister-script-names',
        'placeholder' => 'my-theme-script, some-plugin-script',
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'deregister-script-names' )[0] ?? '',
    ]);
    ?>Separate names by comma. To deregister all scripts set *<?php

}

function css_type_meta_rest_css() {
    global $post;

    textarea( (object) [
        'name' => 'rest-css',
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'rest-css' )[0] ?? '',
        'style' => 'height:300px',
    ]);

    checkboxes( (object) [
        'name' => 'rest-css-defer',
        'options' => ['on' => 'Defer the not-first-screen CSS (avoid render-blicking)'],
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'rest-css-defer' )[0] ?? '',
    ]);
}

function applied_type_meta_main() {
    global $post;

    // get css post types
    $css_posts0 = get_posts([
        'post_type' => FCPFSC_SLUG,
        'orderby' => 'post_title',
        'order'   => 'ASC',
        'post_status' => ['any', 'active'],
        'posts_per_page' => -1,
    ]);
    $css_posts = [];
    foreach( $css_posts0 as $v ){
        $css_posts[ $v->ID ] = $v->post_title ? $v->post_title : __( '(no title)' );
    }

    select( (object) [
        'name' => 'id',
        'placeholder' => '------',
        'options' => $css_posts,
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'id' )[0] ?? '',
    ]);

    ?><p>&nbsp;</p><p><strong>Exclude CSS</strong></p><?php
    select( (object) [
        'name' => 'id-exclude',
        'placeholder' => '------',
        'options' => $css_posts,
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'id-exclude' )[0] ?? '',
    ]);

    ?>
    <input type="hidden" name="<?php echo esc_attr( FCPFSC_PREF ) ?>nonce" value="<?= esc_attr( wp_create_nonce( FCPFSC_PREF.'nonce' ) ) ?>">
    <?php
}