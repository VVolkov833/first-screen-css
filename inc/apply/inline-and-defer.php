<?php

// apply new rules to the posts

namespace FCP\FirstScreenCSS;
defined( 'ABSPATH' ) || exit;


$inline_defer = function() use ($csss) {

    $all_styles = get_all_styles();
    $all_scripts = get_all_scripts();

    // inline
    list( $styles, $scripts ) = get_names_to_inline( $csss );
    inline_style( in_array( '*', $styles ) ? $all_styles : $styles );
    inline_script( in_array( '*', $scripts ) ? $all_scripts : $scripts );

    // defer
    list( $styles, $scripts ) = get_names_to_defer( $csss );
    defer_style( in_array( '*', $styles ) ? $all_styles : $styles );
    defer_script( in_array( '*', $scripts ) ? $all_scripts : $scripts ); // ++hoverintent-js is not effected - improve to fetch src directly from the tag
};

add_action( 'wp_enqueue_scripts', $inline_defer, 100000 );
add_action( 'wp_footer', $inline_defer, 1 );
add_action( 'wp_footer', $inline_defer, 11 );


function get_names_to_inline($ids) {
    return get_names_to_($ids, 'inline');
}
function get_names_to_defer($ids) {
    return get_names_to_($ids, 'defer');
}


function inline_style($name, $priority = 10) {
    static $store = []; // ++ make global to not interfere with inline, defer, deregister

    $name = array_diff( (array) $name, $store );
    if ( empty( $name ) ) { return; }

    $store = array_merge( $store, $name );

    $styles_data = get_all_styles(true);

    add_filter( 'style_loader_tag', function ($tag, $handle) use ($name, $styles_data) {
        if ( is_string( $name ) && $handle !== $name || is_array( $name ) && !in_array( $handle, $name ) ) { return $tag; }
        // try to get the src directly from the tag
        $path = $styles_data[ $handle ]['get'] !== null ? $styles_data[ $handle ]['get'] : get_href_from_link_tag( $tag );
        if ( empty( $path ) ) { return $tag; }
        $content = file_get_contents( $path );
        if ( empty( trim( $content ) ) ) { return $tag; }
        list( $errors, $sanitized ) = sanitize_css( $content );
        if ( !empty( $errors ) ) { return $tag; }
        $sanitized = FCPFSC_DEV ? css_minify( $sanitized ) : $sanitized;
        return '<style id="'.$handle.'-css">'.$sanitized.'</style>';
    }, $priority, 2 );
}

function inline_script($name, $priority = 10) {
    static $store = [];

    $name = array_diff( (array) $name, $store );
    if ( empty( $name ) ) { return; }

    $store = array_merge( $store, $name );

    $scripts_data = get_all_scripts(true);

    add_filter( 'script_loader_tag', function ($tag, $handle) use ($name, $scripts_data) {
        if ( is_string( $name ) && $handle !== $name || is_array( $name ) && !in_array( $handle, $name ) ) { return $tag; }
        // try to get the src directly from the tag
        $path = $scripts_data[ $handle ]['get'] !== null ? $scripts_data[ $handle ]['get'] : get_src_from_script_tag( $tag );
        if ( empty( $path ) ) { return $tag; }
        $content = file_get_contents( $path );
        if ( empty( trim( $content ) ) ) { return $tag; }
        return '<script id="'.$handle.'-js">'.$content.'</script>';
    }, $priority, 2 );
}

function defer_style($name, $priority = 10) {
    static $store = [];

    $name = array_diff( (array) $name, $store );
    if ( empty( $name ) ) { return; }

    $store = array_merge( $store, $name );

    add_filter( 'style_loader_tag', function ($tag, $handle) use ($name) {
        if ( is_string( $name ) && $handle !== $name || is_array( $name ) && !in_array( $handle, $name ) ) { return $tag; }
        return
            str_replace( [ 'rel="stylesheet"', "rel='stylesheet'" ], [
                'rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"',
                "rel='preload' as='style' onload='this.onload=null;this.rel=\"stylesheet\"'"
            ], $tag ).
            '<noscript>'.str_replace(
                [ ' id="'.$handle.'-css"', " id='".$handle."-css'" ], // remove doubling id
                [ '', '' ],
                substr( $tag, 0, -1 )
            ).'</noscript>' . "\n"
        ;
    }, $priority, 2 );
}

function defer_script($name, $priority = 10) {
    static $store = [];

    $name = array_diff( (array) $name, $store );
    if ( empty( $name ) ) { return; }

    $store = array_merge( $store, $name );

    add_filter('script_loader_tag', function ($tag, $handle) use ($name) {
        if ( is_string( $name ) && $handle !== $name || is_array( $name ) && !in_array( $handle, $name ) ) { return $tag; }
        return str_replace( [' defer', ' src'], [' ', ' defer src'], $tag );
    }, $priority, 2 );

}

function get_src_from_script_tag($tag) {
    if ( !preg_match_all( "/<script[^>]*src=['\"](http[^'\"]+)['\"][^>]*>/i", $tag, $matches ) ) { return null; }
    return isset( $matches[1] ) && isset( $matches[1][0] ) ? $matches[1][0] : null;
};
function get_href_from_link_tag($tag) {
    if ( !preg_match_all( "/<link[^>]*href=['\"](http[^'\"]+)['\"][^>]*>/i", $tag, $matches ) ) { return null; }
    return isset( $matches[1] ) && isset( $matches[1][0] ) ? $matches[1][0] : null;
};