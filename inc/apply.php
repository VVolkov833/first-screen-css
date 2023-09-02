<?php

// apply new rules to the posts

namespace FCP\FirstScreenCSS;
defined( 'ABSPATH' ) || exit;


// print the styles
add_action( 'wp_enqueue_scripts', function() {

    // collect css-s to print on the post
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

    // print styles
    wp_register_style( FCPFSC_FRONT_PREF, false );
    wp_enqueue_style( FCPFSC_FRONT_PREF );
    wp_add_inline_style( FCPFSC_FRONT_PREF, get_css_contents_filtered( $csss ) );


    // deregister existing styles
    $deregister = function() use ( $csss ) {
        list( $styles, $scripts ) = get_all_to_deregister( $csss );

        $deregister = function($list, $ss) {
            if ( empty( $list ) ) { return; }

            if ( in_array( '*', $list ) ) { // get all registered
                $global = 'wp_'.$ss.'s';
                global $$global;

                $list = array_map( function( $el ) {
                    $name = $el->handle;
                    if ( strpos( $name, FCPFSC_FRONT_PREF ) === 0 ) { return ''; }
                    return $name ?? '';
                }, (array) $$global->registered ?? [] );
            }

            $func = 'wp_deregister_'.$ss;
            foreach ( $list as $v ) {
                $func( $v );
            }
        };

        $deregister( $styles, 'style' );
        $deregister( $scripts, 'script' );
    };
    add_action( 'wp_enqueue_scripts', $deregister, 100000 );
    add_action( 'wp_footer', $deregister, 1 );
    add_action( 'wp_footer', $deregister, 11 );


    // enqueue the rest-screen styles
    add_action( 'wp_enqueue_scripts', function() use ( $csss ) {
        foreach ( $csss as $id ) {
            $path = '/' . basename( __DIR__ ) . '/style-'.$id.'.css';
            if ( !is_file( wp_upload_dir()['basedir'] . $path ) ) { continue; }
            wp_enqueue_style(
                FCPFSC_FRONT_PREF.'-css-rest-' . $id,
                wp_upload_dir()['baseurl'] . $path,
                [],
                filemtime( wp_upload_dir()['basedir'] . $path ),
                'all'
            );
        }

        // defer loading
        $defer_csss = get_to_defer( $csss );
        add_filter( 'style_loader_tag', function ($tag, $handle) use ($defer_csss) {
            if ( strpos( $handle, FCPFSC_FRONT_PREF.'-css-rest-' ) === false || !in_array( str_replace( FCPFSC_FRONT_PREF.'-css-rest-', '', $handle ), $defer_csss ) ) {
                return $tag;
            }
            return
                str_replace( [ 'rel="stylesheet"', "rel='stylesheet'" ], [
                    'rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"',
                    "rel='preload' as='style' onload='this.onload=null;this.rel=\"stylesheet\"'"
                ], $tag ).
                '<noscript>'.str_replace( // remove doubling id
                    [ ' id="'.$handle.'-css"', " id='".$handle."-css'" ],
                    [ '', '' ],
                    substr( $tag, 0, -1 )
                ).'</noscript>' . "\n"
            ;
        }, 10, 2);
    }, 10 );

}, 7 );