<?php

// collect the fcpfsc type ids to apply to the post

namespace FCP\FirstScreenCSS;
defined( 'ABSPATH' ) || exit;


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
        $csss = array_merge( $csss, get_fcpfsc_ids( FCPFSC_PREF.'post-types', $post_type ) );
        //}
    }

    // get css by archive
    if ( is_home() || is_archive() && ( !$post_type || $post_type === 'page' ) ) {
        // get css for blog
        $csss = array_merge( $csss, get_fcpfsc_ids( FCPFSC_PREF.'post-archives', 'blog' ) );
    }
    if ( is_post_type_archive( $post_type ) ) {
        // get css for custom post type archive
        $csss = array_merge( $csss, get_fcpfsc_ids( FCPFSC_PREF.'post-archives', $post_type ) );
    }

    // get css by template
    if ( isset($qo->ID) && $template = get_page_template_slug( $qo->ID ) ) { // not default '' and not not-applied false
        $csss = array_merge( $csss, get_fcpfsc_ids( FCPFSC_PREF.'post-templates', $template ) );
    }
    


    if ( empty( $csss ) ) { return; }

    // filter by css-post_status, post_type, development-mode
    $csss = filter_csss( $csss );

    // filter by exclude
    if ( isset($qo->ID) && $css_exclude = get_post_meta( $qo->ID, FCPFSC_PREF.'id-exclude' )[0] ?? null ) {
        $csss = array_values( array_diff( $csss, [ $css_exclude ] ) );
    }

    if ( empty( $csss ) ) { return; }

    // apply the rules defined in the fcpfsc
    require FCPFSC_DIR . 'inc/apply/functions.php';
    require FCPFSC_DIR . 'inc/apply/add-first-screen.php';
    require FCPFSC_DIR . 'inc/apply/add-rest-screen.php';
    require FCPFSC_DIR . 'inc/apply/inline-and-defer.php';
    require FCPFSC_DIR . 'inc/apply/deregister.php';

}, 7 );


function get_fcpfsc_ids( $key, $type = 'post' ) {

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