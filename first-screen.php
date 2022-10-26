<?php
/*
Plugin Name: FCP First Screen CSS
Description: Insert inline CSS to the head of the website, so the first screen renders with no jumps, which improves the CLS web vital. Or for any other reason.
Version: 1.0.0
Requires at least: 4.7
Requires PHP: 7.0.0
Author: Firmcatalyst, Vadim Volkov
Author URI: https://firmcatalyst.com
License: GPL v3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace FCP\FirstScreenCSS;

defined( 'ABSPATH' ) || exit;

define( 'FCPFSC', [
    'dev'            => false,
    'prefix'         => 'fcpfsc' . '-',
]);

if( !function_exists( 'get_plugin_data' ) ) { require_once( ABSPATH . 'wp-admin/includes/plugin.php' ); }

define( 'FCPFSC_VER', get_plugin_data( __FILE__ )[ 'Version' ] . ( FCPFSC['dev'] ? time() : '' ) );


// print the styles
add_action( 'wp_head', function() { // include the first-screen styles, instead of enqueuing

    // collect csss to print on the post
    $csss = [];

    // get post type
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
        $css_id = get_post_meta( $qo->ID, FCPFSC['prefix'].'id' )[0];
        if ( isset( $css_id ) ) {
            $csss[] = $css_id;
        }
        unset( $css_id );
        // get css by post-type
        $csss = array_merge( $csss, get_css_ids( FCPFSC['prefix'].'post-types', $post_type ) );
    }
    if ( is_home() || is_archive() && ( !$post_type || $post_type === 'page' ) ) {
        // get css for blog
        $csss = array_merge( $csss, get_css_ids( FCPFSC['prefix'].'post-archives', 'blog' ) );
    }
    if ( is_post_type_archive( $post_type ) ) {
        // get css for custom post type archive
        $csss = array_merge( $csss, get_css_ids( FCPFSC['prefix'].'post-archives', $post_type ) );
    }


    ob_start();

    ?><style id='first-screen-inline-css' type='text/css'><?php
    echo esc_html( css_minify( get_css_contents( $csss ) ) );
    ?></style><?php

    $content = ob_get_contents();
    ob_end_clean();

    if ( FCPFSC['dev'] ) {  echo $content; return; }
    echo $content;
   
}, 7 );

// admin post type for css-s
add_action( 'init', function() {
    $shorter = [
        'name' => 'FirstScreen CSS',
        'plural' => 'FirstScreen CSS',
        'public' => false,
    ];
    $labels = [
        'name'                => $shorter['plural'],
        'singular_name'       => $shorter['name'],
        'menu_name'           => $shorter['plural'],
        'all_items'           => 'View All ' . $shorter['plural'],
        'archives'            => 'All ' . $shorter['plural'],
        'view_item'           => 'View ' . $shorter['name'],
        'add_new'             => 'Add New',
        'add_new_item'        => 'Add New ' . $shorter['name'],
        'edit_item'           => 'Edit ' . $shorter['name'],
        'update_item'         => 'Update ' . $shorter['name'],
        'search_items'        => 'Search ' . $shorter['name'],
        'not_found'           => $shorter['name'] . ' Not Found',
        'not_found_in_trash'  => $shorter['name'] . ' Not found in Trash',
    ];
    $args = [
        'label'               => $shorter['name'],
        'description'         => 'CSS to print before everything',
        'labels'              => $labels,
        'supports'            => ['title'],
        'hierarchical'        => false,
        'public'              => $shorter['public'],
        'show_in_rest'        => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => false,
        'show_in_admin_bar'   => true,
        'menu_position'       => 29,
        'menu_icon'           => 'dashicons-money-alt',
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => !$shorter['public'],
        'publicly_queryable'  => $shorter['public'],
        'capability_type'     => 'page',
        'rewrite'             => [ 'slug' => 'fcpfsc' ],
    ];
    register_post_type( 'fcpfsc', $args );
});

// admin meta boxes
add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'first-screen-css',
        'First screen CSS',
        'FCP\FirstScreenCSS\fcpfsc_meta_box',
        ['fcpfsc'],
        'normal',
        'high'
    );

    list( 'public' => $public_post_types ) = get_all_post_types();
    add_meta_box(
        'first-screen-css',
        'First screen CSS',
        'FCP\FirstScreenCSS\anypost_meta_box',
        array_keys( $public_post_types ),
        'side',
        'low'
    );
});

function fcpfsc_meta_box() {
    global $post;

    // get post types to print options
    list( 'public' => $public_post_types, 'archieve' => $archives_post_types ) = get_all_post_types();

    textarea( (object) [
        'name' => 'css',
        'placeholder' => '/* enter your css here */
* {
    border: 1px dotted red;
    box-sizing: border-box;
}',
        'value' => get_post_meta( $post->ID, FCPFSC['prefix'].'css' )[0],
    ]);

    ?><p><strong>Apply to the following post types</strong></p><?php

    checkboxes( (object) [
        'name' => 'post-types',
        'options' => $public_post_types,
        'value' => get_post_meta( $post->ID, FCPFSC['prefix'].'post-types' )[0],
    ]);

    ?><p><strong>Apply to the Archive pages of the following post types</strong></p><?php

    checkboxes( (object) [
        'name' => 'post-archives',
        'options' => $archives_post_types,
        'value' => get_post_meta( $post->ID, FCPFSC['prefix'].'post-archives' )[0],
    ]);

    ?>
    <p>Every public post type now has a special select box in the right sidebar to pick from the list of the first-screen-css posts, like this one.</p>
    <p>CSS will be minified before printing.</p>
    <p>You can grab the first screen css with a script: <a href="https://github.com/VVolkov833/first-screen-css-grabber" target="_blank" rel="noopener">github.com/VVolkov833/first-screen-css-grabber</a></p>
    <?php

    wp_nonce_field( FCPFSC['prefix'].'nounce-action', FCPFSC['prefix'].'nounce-name' );
}

function anypost_meta_box() {
    global $post;

    // get css post types
    $css_posts0 = get_posts([
        'post_type' => 'fcpfsc',
        'orderby' => 'post_title',
        'order'   => 'ASC',
        'post_status' => ['any', 'active'],
        'posts_per_page' => -1,
    ]);
    $css_posts = [];
    foreach( $css_posts0 as $v ){
        $css_posts[ $v->ID ] = $v->post_title ? $v->post_title : __( '(no title)' );
    }

    select( (object) [
        'name' => 'id',
        'placeholder' => 'Select the FirstScreen css',
        'options' => $css_posts,
        'value' => get_post_meta( $post->ID, FCPFSC['prefix'].'id' )[0],
    ]);

    wp_nonce_field( FCPFSC['prefix'].'nounce-action', FCPFSC['prefix'].'nounce-name' );
}

// style meta boxes
add_action( 'admin_footer', function() {
    global $post;
    if( $post->post_type !== 'fcpfsc' ) { return; }

    ?>
<style type="text/css">
#first-screen-css textarea {
    width:100%;
    height:60vh;
}
#first-screen-css select {
    width:100%;
    box-sizing:border-box;
}
#first-screen-css p {
    margin:30px 0 10px;
}
#first-screen-css p + p {
    margin-top:10px;
}
</style>
    <?php
});

// save meta data
add_action( 'save_post', function( $postID ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( !wp_verify_nonce( $_POST[ FCPFSC['prefix'].'nounce-name' ], FCPFSC['prefix'].'nounce-action' ) ) { return; }
    if ( !current_user_can( 'edit_post', $postID ) ) { return; }

    $post = get_post( $postID );
    if ( $post->post_type === 'revision' ) { return; }

    if ( $post->post_type === 'fcpfsc' ) {
        $fields = [ 'css', 'post-types', 'post-archives' ];
    } else {
        $fields = [ 'id' ];
    }

    foreach ( $fields as $f ) {
        $f = FCPFSC['prefix'] . $f;
        if ( empty( $_POST[ $f ] ) ) {
            delete_post_meta( $postID, $f );
            continue;
        }
        update_post_meta( $postID, $f, $_POST[ $f ] );
    }
});


// functions -------------------------------------


function textarea($a) {
    ?>
<textarea
    name="<?php echo FCPFSC['prefix'] . $a->name ?>"
    id="<?php echo FCPFSC['prefix'] . $a->name ?>"
    rows="<?php echo isset( $a->rows ) ? $a->rows : '10' ?>" cols="<?php echo isset( $a->cols ) ? $a->cols : '50' ?>"
    placeholder="<?php echo isset( $a->placeholder ) ? $a->placeholder : '' ?>"
    class="<?php echo isset( $a->className ) ? $a->className : '' ?>"
><?php
    echo esc_textarea( isset( $a->value ) ? $a->value : '' )
?></textarea>
    <?php
}

function checkboxes($a) {
    ?>
<fieldset
    id="<?php echo FCPFSC['prefix'] . $a->name ?>"
    class="<?php echo isset( $a->className ) ? $a->className : '' ?>"><?php

    foreach ( (array) $a->options as $k => $v ) {
        $checked = is_array( $a->value ) && in_array( $k, $a->value );
    ?><label>
        <input type="checkbox"
            name="<?php echo FCPFSC['prefix'] . $a->name ?>[]"
            value="<?php echo esc_attr( $k ) ?>"
            <?php echo $checked ? 'checked' : '' ?>
        >
        <span><?php echo $v ?></span>
    </label><?php } ?>
</fieldset>
    <?php
}

function select($a) {
    ?>
    <select
        name="<?php echo FCPFSC['prefix'] . $a->name ?>"
        id="<?php echo FCPFSC['prefix'] . $a->name ?>"
        class="<?php echo isset( $a->className ) ? $a->className : '' ?>"><?php

        if ( isset( $a->placeholder ) ) { ?>
            <option value=""><?php echo esc_html( $a->placeholder ) ?></option>
        <?php } ?>

        <?php foreach ( $a->options as $k => $v ) { ?>
            <option
                value="<?php echo esc_attr( $k ) ?>"
                <?php echo isset( $a->value ) && $a->value == $k ? 'selected' : '' ?>
            ><?php echo esc_html( $v ) ?></option>
        <?php } ?>
    </select>
    <?php
}

function input($a) {
    ?>
    <input type="text"
        name="<?php echo FCPFSC['prefix'] . $a->name ?>"
        id="<?php echo FCPFSC['prefix'] . $a->name ?>"
        placeholder="<?php echo isset( $a->placeholder ) ? $a->placeholder  : '' ?>"
        value="<?php echo isset( $a->value ) ? esc_attr( $a->value ) : '' ?>"
        class="<?php echo isset( $a->className ) ? $a->className : '' ?>"
    />
    <?php
}

function get_css_ids( $key, $type = 'post' ) {

    global $wpdb;

    $metas = $wpdb->get_col( $wpdb->remove_placeholder_escape( $wpdb->prepare('

        SELECT `post_id`
        FROM `'.$wpdb->postmeta.'`
        WHERE `meta_key` = %s AND `meta_value` LIKE %s

    ', $key, $wpdb->add_placeholder_escape( '%"'.$type.'"%' ) ) ) );

    return $metas;
}

function get_css_contents( $ids ) {

    if ( empty( $ids ) ) { return; }

    global $wpdb;

    $metas = $wpdb->get_col( $wpdb->prepare('

        SELECT `meta_value`
        FROM `'.$wpdb->postmeta.'`
        WHERE `meta_key` = %s AND `post_id` IN ( '.join( ',', array_fill( 0, count( $ids ), '%s' ), ).' )

    ', array_merge( [ FCPFSC['prefix'].'css'], $ids ) ) );

    return implode( '', $metas );
}

function get_all_post_types() {
    static $public = [], $archieve = [];

    if ( !empty( $public ) ) { return [ 'all' => $all, 'public' => $public, 'archieve' => $archieve ]; }

    $all = get_post_types( [], 'objects' );
    $public = [];
    $archieve = [];
    $archieve[ 'blog' ] = 'Blog';
    foreach ( $all as $type ) {
        $type->name = isset( $type->rewrite->slug ) ? $type->rewrite->slug : $type->name;
        if ( $type->public ) {
            $public[ $type->name ] = $type->label;
        }
        if ( $type->has_archive ) {
            $archieve[ $type->name ] = $type->label;
        }
    }

    return [ 'all' => $all, 'public' => $public, 'archieve' => $archieve ];

}

function css_minify($css) {
    $css = preg_replace( '/\/\*(?:.*?)*\*\//', '', $css ); // remove comments
    $css = preg_replace( '/\s+/', ' ', $css ); // one-line & only single speces
    $css = preg_replace( '/ ?([\{\};:\>\~\+]) ?/', '$1', $css ); // remove spaces
    $css = preg_replace( '/\+(\d)/', ' + $1', $css ); // restore spaces in functions
    $css = preg_replace( '/(?:[^\}]*)\{\}/', '', $css ); // remove empty properties
    $css = str_replace( [';}', '( ', ' )'], ['}', '(', ')'], $css ); // remove last ; and spaces
    // ++should also remove 0 from 0.5, but not from svg-s?
    return trim( $css );
};

//++ add the syntax-highlighter for css for the next version