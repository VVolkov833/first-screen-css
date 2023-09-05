<?php

// apply the first-screen css

namespace FCP\FirstScreenCSS;
defined( 'ABSPATH' ) || exit;


wp_register_style( FCPFSC_FRONT_NAME, false );
wp_enqueue_style( FCPFSC_FRONT_NAME );
wp_add_inline_style( FCPFSC_FRONT_NAME, get_csss_contents( $csss ) );


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