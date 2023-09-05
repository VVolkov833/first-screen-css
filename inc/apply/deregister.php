<?php

// deregister

namespace FCP\FirstScreenCSS;
defined( 'ABSPATH' ) || exit;


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


function get_names_to_deregister($ids) {
    return get_names_to_($ids, 'deregister');
}