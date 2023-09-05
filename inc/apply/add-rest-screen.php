<?php

// apply new rules to the posts

namespace FCP\FirstScreenCSS;
defined( 'ABSPATH' ) || exit;


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
    $which_rest_to_defer = function( $ids ) {
        global $wpdb;
        $defer_ids = $wpdb->get_col( $wpdb->remove_placeholder_escape( $wpdb->prepare('
            SELECT `post_id`
            FROM `'.$wpdb->postmeta.'`
            WHERE `meta_key` = %s AND `meta_value` = %s AND `post_id` IN ( '.implode( ',', array_fill( 0, count( $ids ), '%s' ), ).' )
        ', array_merge( [ FCPFSC_PREF.'rest-css-defer', serialize(['on']) ], $ids ) ) ) );
        return $defer_ids;
    };
    
    $defer_csss = $which_rest_to_defer( $csss );
    $defer_names = array_map( function( $id ) {
        return FCPFSC_FRONT_NAME.'-css-rest-'.$id;
    }, $defer_csss ?? [] );
    defer_style( $defer_names );

}, 10 );