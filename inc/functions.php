<?php

// list of functions

namespace FCP\FirstScreenCSS;
defined( 'ABSPATH' ) || exit;



function sanitize_css($css) {

    $errors = [];

    // try to escape tags inside svg with url-encoding
    if ( strpos( $css, '<' ) !== false && preg_match( '/<\/?\w+/', $css ) ) {
        // the idea is taken from https://github.com/yoksel/url-encoder/
        $svg_sanitized = preg_replace_callback( '/url\(\s*(["\']*)\s*data:\s*image\/svg\+xml(.*)\\1\s*\)/', function($m) {
            return 'url('.$m[1].'data:image/svg+xml'
                .preg_replace_callback( '/[\r\n%#\(\)<>\?\[\]\\\\^\`\{\}\|]+/', function($m) {
                    return urlencode( $m[0] );
                }, urldecode( $m[2] ) )
                .$m[1].')';
        }, $css );

        if ( $svg_sanitized !== null ) {
            $css = $svg_sanitized;
        }
    }
    // if tags still exist, forbid that
    // the idea is taken from WP_Customize_Custom_CSS_Setting::validate as well as the translation
    if ( strpos( $css, '<' ) !== false && preg_match( '/<\/?\w+/', $css ) ) {
        $errors['tags'] = 'HTML ' . __( 'Markup is not allowed in CSS.' );
    }

    // ++strip <?php, <!--??
    // ++maybe add parser sometime later
    // ++safecss_filter_attr($css)??

    return [$errors, $css];
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

function get_css_ids( $key, $type = 'post' ) {

    global $wpdb;

    $ids = $wpdb->get_col( $wpdb->remove_placeholder_escape( $wpdb->prepare('

        SELECT `post_id`
        FROM `'.$wpdb->postmeta.'`
        WHERE `meta_key` = %s AND `meta_value` LIKE %s

    ', $key, $wpdb->add_placeholder_escape( '%"'.$type.'"%' ) ) ) );

    return $ids;
}

function get_to_defer( $ids ) {

    global $wpdb;

    $defer_ids = $wpdb->get_col( $wpdb->remove_placeholder_escape( $wpdb->prepare('

        SELECT `post_id`
        FROM `'.$wpdb->postmeta.'`
        WHERE `meta_key` = %s AND `meta_value` = %s AND `post_id` IN ( '.implode( ',', array_fill( 0, count( $ids ), '%s' ), ).' )

    ', array_merge( [ FCPFSC_PREF.'rest-css-defer', serialize(['on']) ], $ids ) ) ) );


    return $defer_ids;
}

function get_css_contents_filtered( $ids ) { // ++add proper ordering

    if ( empty( $ids ) ) { return; }

    global $wpdb;

    $metas = $wpdb->get_col( $wpdb->prepare('

        SELECT `post_content_filtered`
        FROM `'.$wpdb->posts.'`
        WHERE `ID` IN ( '.implode( ',', array_fill( 0, count( $ids ), '%s' ), ).' )

    ', $ids ) );

    return implode( '', $metas );
}

function get_all_to_deregister( $ids ) {

    if ( empty( $ids ) ) { return []; }

    global $wpdb;

    $wpdb->query( $wpdb->remove_placeholder_escape( $wpdb->prepare('

        SELECT
            IF ( STRCMP( `meta_key`, %s ) = 0, `meta_value`, "" ) AS "styles",
            IF ( STRCMP( `meta_key`, %s ) = 0, `meta_value`, "" ) AS "scripts"
        FROM `'.$wpdb->postmeta.'`
        WHERE ( `meta_key` = %s OR `meta_key` = %s ) AND `post_id` IN ( '.implode( ',', array_fill( 0, count( $ids ), '%s' ), ).' )

    ', array_merge(
        [ FCPFSC_PREF.'deregister-style-names', FCPFSC_PREF.'deregister-script-names', FCPFSC_PREF.'deregister-style-names', FCPFSC_PREF.'deregister-script-names' ],
        $ids
    ) ) ) );

    $clear = function($a) { return array_values( array_unique( array_filter( array_map( 'trim', explode( ',', implode( ', ', $a ) ) ) ) ) ); };
    
    $styles = $clear( $wpdb->get_col( null, 0 ) );
    $scripts = $clear( $wpdb->get_col( null, 1 ) );

    return [ $styles, $scripts ];
}

function get_all_post_types() {
    static $all = [], $public = [], $archive = [];

    if ( !empty( $public ) ) { return [ 'all' => $all, 'public' => $public, 'archive' => $archive ]; }

    $all = get_post_types( [], 'objects' );
    $public = [];
    $archive = [];
    $archive[ 'blog' ] = 'Blog';
    usort( $all, function($a,$b) { return strcasecmp( $a->label, $b->label ); });
    foreach ( $all as $type ) {
        $type->name = isset( $type->rewrite->slug ) ? $type->rewrite->slug : $type->name;
        if ( $type->has_archive ) {
            $archive[ $type->name ] = $type->label;
        }
        if ( $type->public ) {
            //if ( $type->name === 'page' ) { $type->label .= ' (except Front Page)'; }
            $public[ $type->name ] = $type->label;
        }
    }

    return [ 'all' => $all, 'public' => $public, 'archive' => $archive ];

}

function css_minify($css) {
    $preg_replace = function($regexp, $replace, $string) { // avoid null result so that css still works even though not fully minified
        return preg_replace( $regexp, $replace, $string ) ?: $string . '/* --- failed '.$regexp.', '.$replace.' */';
    };
    $css = $preg_replace( '/\s+/', ' ', $css ); // one-line & only single speces
    $css = $preg_replace( '/ ?\/\*(?:.*?)\*\/ ?/', '', $css ); // remove comments
    $css = $preg_replace( '/ ?([\{\};:\>\~\+]) ?/', '$1', $css ); // remove spaces
    $css = $preg_replace( '/\+(\d)/', ' + $1', $css ); // restore spaces in functions
    $css = $preg_replace( '/(?:[^\}]*)\{\}/', '', $css ); // remove empty properties
    $css = str_replace( [';}', '( ', ' )'], ['}', '(', ')'], $css ); // remove last ; and spaces
    // ++ should also remove 0 from 0.5, but not from svg-s?
    // ++ try replacing ', ' with ','
    // ++ remove space between %3E %3C and before %3E and /%3E
    return trim( $css );
};