<?php

// admin side

namespace FCP\FirstScreenCSS;
defined( 'ABSPATH' ) || exit;

$meta_close_by_default = [
    //FCPFSC_FRONT_NAME.'-css-rest', // ++ it breaks the codemirror loading if closed by default
    FCPFSC_FRONT_NAME.'-css-inline',
    FCPFSC_FRONT_NAME.'-css-defer',
    FCPFSC_FRONT_NAME.'-css-deregister',
    FCPFSC_FRONT_NAME.'-css-hints',
];

// admin controls
add_action( 'add_meta_boxes', function() {
    if ( !current_user_can( 'administrator' ) ) { return; }

    add_meta_box(
        FCPFSC_FRONT_NAME.'-css-bulk',
        'Bulk apply',
        'FCP\FirstScreenCSS\css_type_meta_bulk_apply',
        FCPFSC_SLUG,
        'normal',
        'high'
    );

    add_meta_box(
        FCPFSC_FRONT_NAME.'-css-rest',
        'Non-first-screen CSS',
        'FCP\FirstScreenCSS\css_type_meta_rest_css',
        FCPFSC_SLUG,
        'normal',
        'low'
    );

    add_meta_box(
        FCPFSC_FRONT_NAME.'-css-inline',
        '<span>Specify Styles and Scripts for <font color=#2271b1>Inline</font> Inclusion</span>',
        'FCP\FirstScreenCSS\css_type_meta_inline',
        FCPFSC_SLUG,
        'normal',
        'low'
    );

    add_meta_box(
        FCPFSC_FRONT_NAME.'-css-defer',
        '<span>Specify Styles and Scripts to <font color=#2271b1>Defer</font> loading</span>',
        'FCP\FirstScreenCSS\css_type_meta_defer',
        FCPFSC_SLUG,
        'normal',
        'low'
    );

    add_meta_box(
        FCPFSC_FRONT_NAME.'-css-deregister',
        '<span>Specify Styles and Scripts to <font color=#2271b1>Deregister</font></span>',
        'FCP\FirstScreenCSS\css_type_meta_deregister',
        FCPFSC_SLUG,
        'normal',
        'low'
    );

    add_meta_box(
        FCPFSC_FRONT_NAME.'-css-hints',
        'Instruction & Tools',
        'FCP\FirstScreenCSS\css_type_meta_hints',
        FCPFSC_SLUG,
        'normal',
        'low'
    );


    list( 'public' => $public_post_types ) = get_all_post_types();
    add_meta_box(
        FCPFSC_FRONT_NAME.'-css',
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

    ?><p><strong>Apply to the post types:</strong></p><?php

    checkboxes( (object) [
        'name' => 'post-types',
        'options' => $public_post_types,
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'post-types' )[0] ?? '',
    ]);

    ?><p><strong>Apply to the Archives:</strong></p><?php

    checkboxes( (object) [
        'name' => 'post-archives',
        'options' => $archives_post_types,
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'post-archives' )[0] ?? '',
    ]);

    ?>
    <p>To apply this CSS Setting to a specific post, navigate to the desired post editor and choose this Setting from the dropdown menu located in the right sidebar.</p>
    <?php

    checkboxes( (object) [
        'name' => 'development-mode',
        'options' => ['on' => 'Development mode (the Setting is visible only to administrators)'],
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'development-mode' )[0] ?? '',
    ]);

    ?>
    <input type="hidden" name="<?php echo esc_attr( FCPFSC_PREF ) ?>nonce" value="<?= esc_attr( wp_create_nonce( FCPFSC_PREF.'nonce' ) ) ?>">
    <?php
}

function css_type_meta_inline() {
    global $post;

    ?><p><strong>List the names of STYLES to inline.</strong> Separate names by commas. To inline all styles set *</p><?php

    input( (object) [
        'name' => 'inline-style-names',
        'placeholder' => 'my-theme-style, some-plugin-style',
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'inline-style-names' )[0] ?? '',
    ]);


    ?><p><strong>List the names of SCRIPTS to inline.</strong> Separate names by commas. To inline all scripts set *</p><?php

    input( (object) [
        'name' => 'inline-script-names',
        'placeholder' => 'my-theme-script, some-plugin-script',
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'inline-script-names' )[0] ?? '',
    ]);

}

function css_type_meta_defer() {
    global $post;

    ?><p><strong>List the names of STYLES to defer.</strong> Separate names by commas. To defer all styles set *</p><?php

    input( (object) [
        'name' => 'defer-style-names',
        'placeholder' => 'my-theme-style, some-plugin-style',
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'defer-style-names' )[0] ?? '',
    ]);


    ?><p><strong>List the names of SCRIPTS to defer.</strong> Separate names by commas. To defer all scripts set *</p><?php

    input( (object) [
        'name' => 'defer-script-names',
        'placeholder' => 'my-theme-script, some-plugin-script',
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'defer-script-names' )[0] ?? '',
    ]);

}

function css_type_meta_deregister() {
    global $post;

    ?><p><strong>List the names of STYLES to deregister.</strong> Separate names by commas. To deregister all styles set *</p><?php

    input( (object) [
        'name' => 'deregister-style-names',
        'placeholder' => 'my-theme-style, some-plugin-style',
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'deregister-style-names' )[0] ?? '',
    ]);


    ?><p><strong>List the names of SCRIPTS to deregister.</strong> Separate names by commas. To deregister all scripts set *</p><?php

    input( (object) [
        'name' => 'deregister-script-names',
        'placeholder' => 'my-theme-script, some-plugin-script',
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'deregister-script-names' )[0] ?? '',
    ]);

}

function css_type_meta_hints() {
    ?>

    <p>You can grab the first screen css of a page with the script: <a href="https://github.com/VVolkov833/first-screen-css-grabber" target="_blank" rel="noopener">github.com/VVolkov833/first-screen-css-grabber</a></p>

    <?php
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
        'options' => ['on' => 'Defer the non-first-screen CSS to prevent render-blinking'],
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