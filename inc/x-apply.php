<?php
// THE FILE WAS SPLIT INTO FILES IN FALDER inc/apply
// apply new rules to the posts

namespace FCP\FirstScreenCSS;
defined( 'ABSPATH' ) || exit;


// print the styles
add_action( 'wp_enqueue_scripts', function() {

    // collects css-s to print
    $csss = [];

    // get the post type
    $qo = get_queried_object();
    $post_type = '';
    if ( is_object( $qo ) ) {
        if ( get_class( $qo ) === 'WP_Post_Type' ) {
            $post_type = $qo->name;
        }
        if ( get_class( $qo ) === 'WP_Post' ) {
            $post_type = $qo->post_type;
        }
    }

    if ( is_singular( $post_type ) ) {
        // get css by post id 
        if ( $css_id = get_post_meta( $qo->ID, FCPFSC_PREF.'id' )[0] ?? null ) {
            $csss[] = $css_id;
        }

        // get css by post-type
        //if ( (int) get_option('page_on_front') !== (int) $qo->ID ) { // exclude the front-page, as they stand out, mostly
        $csss = array_merge( $csss, get_css_ids( FCPFSC_PREF.'post-types', $post_type ) );
        //}
    }
    if ( is_home() || is_archive() && ( !$post_type || $post_type === 'page' ) ) {
        // get css for blog
        $csss = array_merge( $csss, get_css_ids( FCPFSC_PREF.'post-archives', 'blog' ) );
    }
    if ( is_post_type_archive( $post_type ) ) {
        // get css for custom post type archive
        $csss = array_merge( $csss, get_css_ids( FCPFSC_PREF.'post-archives', $post_type ) );
    }

    if ( empty( $csss ) ) { return; }

    // filter by post_status, post_type, development-mode
    $csss = filter_csss( $csss );

    // filter by exclude
    if ( $css_exclude = get_post_meta( $qo->ID, FCPFSC_PREF.'id-exclude' )[0] ?? null ) {
        $csss = array_values( array_diff( $csss, [ $css_exclude ] ) );
    }

    if ( empty( $csss ) ) { return; }

    //-------------------------------------------------------------------------------------
    // print styles
    wp_register_style( FCPFSC_FRONT_NAME, false );
    wp_enqueue_style( FCPFSC_FRONT_NAME );
    wp_add_inline_style( FCPFSC_FRONT_NAME, get_csss_contents( $csss ) );


    //-------------------------------------------------------------------------------------
    // enqueue the rest-screen styles
    add_action( 'wp_enqueue_scripts', function() use ( $csss ) {

        foreach ( $csss as $id ) {
            $file = '/style-'.$id.'.css';
            if ( !is_file( FCPFSC_REST_DIR.$file ) ) { continue; }
            wp_enqueue_style(
                FCPFSC_FRONT_NAME.'-css-rest-'.$id,
                FCPFSC_REST_URL.$file,
                [],
                filemtime( FCPFSC_REST_DIR.$file ),
                'all'
            );
        }

        // defer loading the rest-screen styles
        $defer_csss = get_rest_to_defer( $csss );
        $defer_names = array_map( function( $id ) {
            return FCPFSC_FRONT_NAME.'-css-rest-'.$id;
        }, $defer_csss ?? [] );
        defer_style( $defer_names );

    }, 10 );

    //-------------------------------------------------------------------------------------

    // inline styles and scripts
    $inline = function() use ( $csss ) {

        list( $styles, $scripts ) = get_names_to_inline( $csss );

        if ( in_array( '*', $styles ) ) { $styles = get_all_styles(); }
        inline_style( $styles );

        if ( in_array( '*', $scripts ) ) { $scripts = get_all_scripts(); }
        inline_script( $scripts );

    };
    add_action( 'wp_enqueue_scripts', $inline, 100000 );
    add_action( 'wp_footer', $inline, 1 );
    add_action( 'wp_footer', $inline, 11 );

    // defer styles and scripts
    $defer = function() use ( $csss ) {

        list( $styles, $scripts ) = get_names_to_defer( $csss );

        if ( in_array( '*', $styles ) ) { $styles = get_all_styles(); }
        defer_style( $styles );

        if ( in_array( '*', $scripts ) ) { $scripts = get_all_scripts(); }
        defer_script( $scripts );

    };
    add_action( 'wp_enqueue_scripts', $defer, 100000 );
    add_action( 'wp_footer', $defer, 1 );
    add_action( 'wp_footer', $defer, 11 );


    // deregister styles and scripts
    $deregister = function() use ( $csss ) {

        $deregister_styles = function($list = []) {
            if ( empty( $list ) ) { return; }
            if ( in_array( '*', $list ) ) { $list = get_all_styles(); }
            foreach ( $list as $v ) { wp_deregister_style( $v ); }
        };

        $deregister_scripts = function($list = []) {
            if ( empty( $list ) ) { return; }
            if ( in_array( '*', $list ) ) { $list = get_all_scripts(); }
            foreach ( $list as $v ) { wp_deregister_script( $v ); }
        };

        list( $styles, $scripts ) = get_names_to_deregister( $csss );

        $deregister_styles( $styles );
        $deregister_scripts( $scripts );
    };
    add_action( 'wp_enqueue_scripts', $deregister, 100000 );
    add_action( 'wp_footer', $deregister, 1 );
    add_action( 'wp_footer', $deregister, 11 );

}, 7 );


function get_css_ids( $key, $type = 'post' ) {

    global $wpdb;

    $ids = $wpdb->get_col( $wpdb->remove_placeholder_escape( $wpdb->prepare('

        SELECT `post_id`
        FROM `'.$wpdb->postmeta.'`
        WHERE `meta_key` = %s AND `meta_value` LIKE %s

    ', $key, $wpdb->add_placeholder_escape( '%"'.$type.'"%' ) ) ) );

    return $ids;
}

function filter_csss( $ids ) {

    if ( empty( $ids ) ) { return []; }

    global $wpdb;

    // filter by post_status & post_type
    $filtered_ids = $wpdb->get_col( $wpdb->prepare('

        SELECT `ID`
        FROM `'.$wpdb->posts.'`
        WHERE `post_status` = %s AND `post_type` = %s AND `ID` IN ( '.implode( ',', array_fill( 0, count( $ids ), '%s' ), ).' )

    ', array_merge( [ 'publish', FCPFSC_SLUG ], $ids ) ) );

    // filter by development mode
    if ( current_user_can( 'administrator' ) ) { return $filtered_ids; }

    $dev_mode = $wpdb->get_col( $wpdb->remove_placeholder_escape( $wpdb->prepare('

        SELECT `post_id`
        FROM `'.$wpdb->postmeta.'`
        WHERE `meta_key` = %s AND `meta_value` = %s AND `post_id` IN ( '.implode( ',', array_fill( 0, count( $ids ), '%s' ), ).' )

    ', array_merge( [ FCPFSC_PREF.'development-mode', serialize(['on']) ], $ids ) ) ) );


    return array_values( array_diff( $filtered_ids, $dev_mode ) );
}

function get_csss_contents( $ids ) { // ++add proper ordering

    if ( empty( $ids ) ) { return; }

    global $wpdb;

    $contents = $wpdb->get_col( $wpdb->prepare('

        SELECT `post_content_filtered`
        FROM `'.$wpdb->posts.'`
        WHERE `ID` IN ( '.implode( ',', array_fill( 0, count( $ids ), '%s' ), ).' )

    ', $ids ) );

    return implode( '', $contents );
}


function get_rest_to_defer( $ids ) {

    global $wpdb;

    $defer_ids = $wpdb->get_col( $wpdb->remove_placeholder_escape( $wpdb->prepare('

        SELECT `post_id`
        FROM `'.$wpdb->postmeta.'`
        WHERE `meta_key` = %s AND `meta_value` = %s AND `post_id` IN ( '.implode( ',', array_fill( 0, count( $ids ), '%s' ), ).' )

    ', array_merge( [ FCPFSC_PREF.'rest-css-defer', serialize(['on']) ], $ids ) ) ) );

    return $defer_ids;
}


function inline_style($name, $priority = 10) {
    static $store = [];

    $name = array_diff( (array) $name, $store );
    if ( empty( $name ) ) { return; }

    $store = array_merge( $store, $name );

    $styles_data = get_all_styles(true);

    add_filter( 'style_loader_tag', function ($tag, $handle) use ($name, $styles_data) {
        if ( is_string( $name ) && $handle !== $name || is_array( $name ) && !in_array( $handle, $name ) || $styles_data[ $handle ]['get'] === null ) { return $tag; }
        $content = file_get_contents( $styles_data[ $handle ]['get'] );
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
        if ( is_string( $name ) && $handle !== $name || is_array( $name ) && !in_array( $handle, $name ) || $scripts_data[ $handle ]['get'] === null ) { return $tag; }
        $content = file_get_contents( $scripts_data[ $handle ]['get'] );
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


function get_names_to_inline($ids) {
    return get_names_to_($ids, 'inline');
}
function get_names_to_defer($ids) {
    return get_names_to_($ids, 'defer');
}
function get_names_to_deregister($ids) {
    return get_names_to_($ids, 'deregister');
}

function get_names_to_($ids, $label) {
    static $store = [];

    if ( empty( $ids ) ) { return []; }
    if ( isset( $store[$label] ) ) { return $store[$label]; }

    global $wpdb;

    $wpdb->query( $wpdb->remove_placeholder_escape( $wpdb->prepare('

        SELECT
            IF ( STRCMP( `meta_key`, %s ) = 0, `meta_value`, "" ) AS "styles",
            IF ( STRCMP( `meta_key`, %s ) = 0, `meta_value`, "" ) AS "scripts"
        FROM `'.$wpdb->postmeta.'`
        WHERE ( `meta_key` = %s OR `meta_key` = %s ) AND `post_id` IN ( '.implode( ',', array_fill( 0, count( $ids ), '%s' ), ).' )

    ', array_merge(
        [ FCPFSC_PREF.$label.'-style-names', FCPFSC_PREF.$label.'-script-names', FCPFSC_PREF.$label.'-style-names', FCPFSC_PREF.$label.'-script-names' ],
        $ids
    ) ) ) );

    $clear = function($a) { return array_values( array_unique( array_filter( array_map( 'trim', explode( ',', implode( ', ', $a ) ) ) ) ) ); };
    
    $styles = $clear( $wpdb->get_col( null, 0 ) );
    $scripts = $clear( $wpdb->get_col( null, 1 ) );

    $store[$label] = [$styles, $scripts];
    return $store[$label];
}

function get_all_styles($data = false) {
    return get_all_ss_('style', $data);
}
function get_all_scripts($data = false) {
    return get_all_ss_('script', $data);
}
function get_all_ss_($ss, $data = false) {
    $global = 'wp_'.$ss.'s';
    global $$global;
    if ( empty( $$global ) ) { return; }

    if ( $data ) {
        return array_reduce( (array) $$global->registered ?? [], function( $result, $el ) {
            if ( empty($el->src) && ( !isset($el->extra) || empty($el->extra['path']) ) ) { return $result; }
            $result[ $el->handle ] = [
                'handle' => $el->handle,
                'src' => $el->src,
                'path' => $el->extra['path'],
                'get' => !empty($el->extra['path']) ? $el->extra['path'] : ( strpos( $el->src, 'http' ) === 0 ? $el->src : null ),
            ];
            return $result;
        }, [] );
    }

    return array_filter( array_map( function( $el ) {
        $name = $el->handle;
        if ( strpos( $name, FCPFSC_FRONT_NAME ) === 0 ) { return null; } // exclude those generated by the plugin
        return $name ?? null;
    }, (array) $$global->registered ?? [] ) );

}