<?php

// list of functions

namespace FCP\FirstScreenCSS;
defined( 'ABSPATH' ) || exit;


// disable timymce from the editor
add_filter( 'wp_editor_settings', function($settings, $editor_id) {

    if ( $editor_id !== 'content' ) { return $settings; }
    
    $screen = get_current_screen();
    if ( !isset( $screen ) || !is_object( $screen ) || $screen->post_type !== FCPFSC_SLUG ) { return $settings; }

    $settings['tinymce']   = false;
    $settings['quicktags'] = false;
    $settings['media_buttons'] = false;

    return $settings;

}, 10, 2 );


// apply codemirror to textareas
add_action( 'admin_enqueue_scripts', function( $hook ) {

    if ( !in_array( $hook, ['post.php', 'post-new.php'] ) ) { return; }

    $screen = get_current_screen();
    if ( !isset( $screen ) || !is_object( $screen ) || $screen->post_type !== FCPFSC_SLUG ) { return; }

    // remove wp-native codemirror
    wp_deregister_script( 'code-editor' );
    wp_deregister_style( 'wp-codemirror' );

    global $wp_scripts; // remove cm_settings // priority 11 is for this part mostly - default is fine for the rest
    $jquery_extra_core_data = $wp_scripts->get_data( 'jquery-core', 'data' );
    $jquery_extra_core_data = preg_replace( '/var cm_settings(?:.*?);/', '', $jquery_extra_core_data );
    $wp_scripts->add_data( 'jquery-core', 'data', $jquery_extra_core_data );
    //echo '***'; print_r( $wp_scripts ); exit;

    $cm = 'codemirror';

    // codemirror core
    wp_enqueue_script( $cm, FCPFSC_URL . 'assets/codemirror/codemirror.js', ['jquery'], FCPFSC_CM_VER );
    wp_enqueue_style( $cm, FCPFSC_URL . 'assets/codemirror/codemirror.css', [], FCPFSC_CM_VER );

    // codemirror addons
    wp_enqueue_script( $cm.'-mode-css', FCPFSC_URL . 'assets/codemirror/mode/css/css.js', [$cm], FCPFSC_CM_VER );
    wp_enqueue_script( $cm.'-addon-active-line', FCPFSC_URL . 'assets/codemirror/addon/selection/active-line.js', [$cm], FCPFSC_CM_VER );
    wp_enqueue_script( $cm.'-addon-placeholder', FCPFSC_URL . 'assets/codemirror/addon/display/placeholder.js', [$cm], FCPFSC_CM_VER );
    wp_enqueue_script( $cm.'-formatting', FCPFSC_URL . 'assets/codemirror/util/formatting.js', [$cm], '2.38+' );

    // comfortable search
    wp_enqueue_style( $cm.'-addon-dialog', FCPFSC_URL . 'assets/codemirror/addon/dialog/dialog.css', [], FCPFSC_CM_VER );
    wp_enqueue_style( $cm.'-addon-matchsonscrollbar', FCPFSC_URL . 'assets/codemirror/addon/search/matchesonscrollbar.css', [], FCPFSC_CM_VER );

    wp_enqueue_script( $cm.'-addon-dialog', FCPFSC_URL . 'assets/codemirror/addon/dialog/dialog.js', [$cm], FCPFSC_CM_VER );
    wp_enqueue_script( $cm.'-addon-searchcursor', FCPFSC_URL . 'assets/codemirror/addon/search/searchcursor.js', [$cm], FCPFSC_CM_VER );
    wp_enqueue_script( $cm.'-addon-search', FCPFSC_URL . 'assets/codemirror/addon/search/search.js', [$cm], FCPFSC_CM_VER );
    wp_enqueue_script( $cm.'-addon-annotatescrollbar', FCPFSC_URL . 'assets/codemirror/addon/scroll/annotatescrollbar.js', [$cm], FCPFSC_CM_VER );
    wp_enqueue_script( $cm.'-addon-matchesonscrollbar', FCPFSC_URL . 'assets/codemirror/addon/search/matchesonscrollbar.js', [$cm], FCPFSC_CM_VER );
    wp_enqueue_script( $cm.'-addon-jump-to-line', FCPFSC_URL . 'assets/codemirror/addon/search/jump-to-line.js', [$cm], FCPFSC_CM_VER );


    // codemirror init
    wp_enqueue_script( $cm.'-init', FCPFSC_URL . '/assets/codemirror/init.js', [$cm], FCPFSC_VER  );

    // overall styling
    wp_enqueue_style( $cm.'-style', FCPFSC_URL . 'assets/style.css', [$cm], FCPFSC_VER );

}, 11 );