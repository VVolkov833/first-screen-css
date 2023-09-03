<?php

// admin side

namespace FCP\FirstScreenCSS;
defined( 'ABSPATH' ) || exit;


// save meta data
add_action( 'save_post', function( $postID ) {

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( empty( $_POST[ FCPFSC_PREF.'nonce' ] ) || !wp_verify_nonce( $_POST[ FCPFSC_PREF.'nonce' ], FCPFSC_PREF.'nonce' ) ) { return; }
    if ( !current_user_can( 'administrator' ) ) { return; }

    $post = get_post( $postID );
    if ( $post->post_type === 'revision' ) { return; } // update_post_meta fixes the id to the parent, but id can be used before


    if ( $post->post_type === FCPFSC_SLUG ) {
        $fields = [ 'post-types', 'post-archives', 'development-mode', 'deregister-style-names', 'deregister-script-names', 'rest-css', 'rest-css-defer' ];
    } else {
        $fields = [ 'id', 'id-exclude' ];
    }

    // exception for the rest-css ++ improve when having different function for processing values
    $file = wp_upload_dir()['basedir'] . '/' . basename( __DIR__ ) . '/style-'.$postID.'.css';
    @unlink( $file );

    foreach ( $fields as $f ) {
        $f = FCPFSC_PREF . $f;
        if ( empty( $_POST[ $f ] ) || empty( $new_value = sanitize_meta( $_POST[ $f ], $f, $postID ) ) ) {
            delete_post_meta( $postID, $f );
            continue;
        }
        update_post_meta( $postID, $f, $new_value );
    }
});

// filter css
add_filter( 'wp_insert_post_data', function($data, $postarr) {
    // ++ if can admin?
    if ( $data['post_type'] !== FCPFSC_SLUG ) { return $data; }
    clear_errors( $postarr['ID'] );

    // empty is not an error
    if ( trim( $data['post_content'] ) === '' ) {
        $data['post_content_filtered'] = '';
        return $data;
    }

    $errors = [];

    list( $errors, $filtered ) = sanitize_css( wp_unslash( $data['post_content'] ) );

    // right
    if ( empty( $errors ) ) {
        $data['post_content_filtered'] = wp_slash( css_minify( $filtered ) ); // slashes are stripped again right after
        return $data;
    }

    // wrong
    $data['post_content_filtered'] = '';
    save_errors( $errors, $postarr['ID'], '#postdivrich' ); // ++set to draft on any error?
    return $data;

}, 10, 2 );

// message on errors in css
add_action( 'admin_notices', function () {

    $screen = get_current_screen();
    if ( !isset( $screen ) || !is_object( $screen ) || $screen->post_type !== FCPFSC_SLUG || $screen->base !== 'post' ) { return; }

    global $post;
    if ( empty( $errors = get_post_meta( $post->ID, FCPFSC_PREF.'_post_errors' )[0] ?? '' ) ) { return; }

    array_unshift( $errors['errors'], '<strong>This CSS-post can not be published due to the following errors:</strong>' );
    ?>

    <div class="notice notice-error"><ul>
    <?php array_walk( $errors['errors'], function($a) {
        ?>
        <li><?php echo wp_kses( $a, ['strong' => [], 'em' => []] ) ?></li>
    <?php }) ?>
    </ul></div>

    <style type="text/css"><?php echo implode( ', ', $errors['selectors']) ?>{box-shadow:-3px 0px 0px 0px #d63638}</style>

    <?php
});

function save_errors($errors, $postID, $selector = '') {
    static $errors_list = [ 'errors' => [], 'selectors' => [] ];    

    $errors = (array) $errors;

    $errors_list['errors'] = array_merge( $errors_list['errors'], $errors );
    $errors_list['selectors'][] = $selector; // errors override by associative key, numeric add, but selectors only add for now
    update_post_meta( $postID, FCPFSC_PREF.'_post_errors', $errors_list );
}
function clear_errors($postID) {
    delete_post_meta( $postID, FCPFSC_PREF.'_post_errors' );
}

function sanitize_meta( $value, $field, $postID ) {

    $field = ( strpos( $field, FCPFSC_PREF ) === 0 ) ? substr( $field, strlen( FCPFSC_PREF ) ) : $field;

    $onoff = function($value) {
        return $value[0] === 'on' ? ['on'] : [];
    };

    switch( $field ) {
        case ( 'post-types' ):
            return array_intersect( $value, array_keys( get_all_post_types()['public'] ) );
        break;
        case ( 'post-archives' ):
            return array_intersect( $value, array_keys( get_all_post_types()['archive'] ) );
        break;
        case ( 'development-mode' ):
            return $onoff( $value );
        break;
        case ( 'deregister-style-names' ):
            return $value; // ++preg_replace not letters ,space-_, lowercase?, 
        break;
        case ( 'deregister-script-names' ):
            return $value; // ++preg_replace not letters ,space-_, lowercase?, 
        break;
        case ( 'rest-css' ):

            list( $errors, $filtered ) = sanitize_css( wp_unslash( $value ) ); //++ move it all to a separate filter / actions, organize better with errors!!
            $file = wp_upload_dir()['basedir'] . '/' . basename( __DIR__ ) . '/style-'.$postID.'.css';

            // correct
            if ( empty( $errors ) ) {
                file_put_contents( $file, css_minify( $filtered ) ); //++ add the permission error
                return $value;
            }
            // wrong
            unlink( $file );
            save_errors( $errors, $postID, '#first-screen-css-rest > .inside' );
            return $value;
        break;
        case ( 'rest-css-defer' ):
            return $onoff( $value );
        break;
        case ( 'id' ):
            if ( !is_numeric( $value ) ) { return ''; } // ++to a function
            if ( !( $post = get_post( $value ) ) || $post->post_type !== FCPFSC_SLUG ) { return ''; }
            return $value;
        break;
        case ( 'id-exclude' ):
            if ( !is_numeric( $value ) ) { return ''; }
            if ( !( $post = get_post( $value ) ) || $post->post_type !== FCPFSC_SLUG ) { return ''; }
            return $value;
        break;
    }

    return '';
}

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