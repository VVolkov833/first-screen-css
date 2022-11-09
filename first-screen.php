<?php
/*
Plugin Name: FCP First Screen CSS
Description: Insert inline CSS to the head of the website, so the first screen renders with no jumps, which might improve the CLS web vital. Or for any other reason.
Version: 1.1.0
Requires at least: 5.8
Tested up to: 6.0
Requires PHP: 8.0.0
Author: Firmcatalyst, Vadim Volkov
Author URI: https://firmcatalyst.com
License: GPL v3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace FCP\FirstScreenCSS;

defined( 'ABSPATH' ) || exit;

define( 'FCPFSC_SLUG', 'fcpfsc' );
define( 'FCPFSC_PREF', FCPFSC_SLUG.'-' );


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
        $css_id = get_post_meta( $qo->ID, FCPFSC_PREF.'id' )[0];
        if ( isset( $css_id ) ) {
            $csss[] = $css_id;
        }
        unset( $css_id );
        // get css by post-type
        if ( (int) get_option('page_on_front') !== (int) $qo->ID ) { // exclude the front-page, as they are mostly stand out
            $csss = array_merge( $csss, get_css_ids( FCPFSC_PREF.'post-types', $post_type ) );
        }
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

    wp_register_style( 'first-screen', false );
    wp_enqueue_style( 'first-screen' );
    wp_add_inline_style( 'first-screen', get_css_contents_filtered( $csss ) );

}, 7 );

// admin post type for css-s
add_action( 'init', function() {
    $shorter = [
        'name' => 'First Screen CSS',
        'plural' => 'First Screen CSS',
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
        'supports'            => ['title', 'editor'],
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
        'capabilities'        => [
            'edit_post'          => 'switch_themes',
            'read_post'          => 'switch_themes',
            'delete_post'        => 'switch_themes',
            'edit_posts'         => 'switch_themes',
            'edit_others_posts'  => 'switch_themes',
            'delete_posts'       => 'switch_themes',
            'publish_posts'      => 'switch_themes',
            'read_private_posts' => 'switch_themes'
        ]
    ];
    register_post_type( FCPFSC_SLUG, $args );
});

// admin meta boxes
add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'first-screen-css',
        'Bulk apply the First Screen CSS',
        'FCP\FirstScreenCSS\fcpfsc_meta_box',
        FCPFSC_SLUG,
        'normal',
        'high'
    );

    list( 'public' => $public_post_types ) = get_all_post_types();
    add_meta_box(
        'first-screen-css',
        'Select First Screen CSS',
        'FCP\FirstScreenCSS\anypost_meta_box',
        array_keys( $public_post_types ),
        'side',
        'low'
    );
});

// style meta boxes
add_action( 'admin_footer', function() {

    $screen = get_current_screen();
    if ( !isset( $screen ) || !is_object( $screen ) || !in_array( $screen->base, [ 'post' ] ) ) { return; }

    ?>
    <style type="text/css">
    #first-screen-css select {
        width:100%;
        box-sizing:border-box;
    }
    #first-screen-css fieldset label {
        display:inline-block;
        min-width:90px;
        margin-right:16px;
        white-space:nowrap;
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

// codemirror editor instead of tinymce
add_filter( 'wp_editor_settings', function($settings, $editor_id) {

    if ( $editor_id !== 'content' ) { return $settings; }
    
    $screen = get_current_screen();
    if ( !isset( $screen ) || !is_object( $screen ) || $screen->post_type !== FCPFSC_SLUG ) { return $settings; }

    $settings['tinymce']   = false;
    $settings['quicktags'] = false;
    $settings['media_buttons'] = false;

    return $settings;
}, 10, 2 );

add_action( 'admin_enqueue_scripts', function( $hook ) {

    if ( !in_array( $hook, ['post.php', 'post-new.php'] ) ) { return; }

    $screen = get_current_screen();
    if ( !isset( $screen ) || !is_object( $screen ) || $screen->post_type !== FCPFSC_SLUG ) { return; }

    $cm_settings['codeEditor'] = wp_enqueue_code_editor( ['type' => 'text/css'] );
    wp_localize_script( 'jquery', 'cm_settings', $cm_settings );
    wp_enqueue_script( 'wp-theme-plugin-editor' );
    wp_add_inline_script( 'wp-theme-plugin-editor', 'jQuery(document).ready(function($){const $ed=$(\'#content\');$ed.attr(\'placeholder\',\'/* enter your css here */\n* {\n    border: 1px dotted red;\n    box-sizing: border-box;\n}\');wp.codeEditor.initialize($ed,cm_settings);});' );
    wp_enqueue_style( 'wp-codemirror' );
});

// save meta data
add_action( 'save_post', function( $postID ) {

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( !wp_verify_nonce( $_POST[ FCPFSC_PREF.'nounce-name' ], FCPFSC_PREF.'nounce-action' ) ) { return; }
    if ( !current_user_can( 'edit_post', $postID ) ) { return; }

    $post = get_post( $postID );
    if ( $post->post_type === 'revision' ) { return; }


    if ( $post->post_type === FCPFSC_SLUG ) {
        $fields = [ 'post-types', 'post-archives' ];
    } else {
        $fields = [ 'id' ];
    }

    foreach ( $fields as $f ) {
        $f = FCPFSC_PREF . $f;
        if ( empty( $_POST[ $f ] ) || empty( $new_value = sanitize_meta( $_POST[ $f ], $f ) ) ) {
            delete_post_meta( $postID, $f );
            continue;
        }
        update_post_meta( $postID, $f, $new_value );
    }
});

// filter css
add_filter( 'wp_insert_post_data', function($data) {

    if ( $data['post_type'] !== FCPFSC_SLUG ) { return $data; }

    // empty is not an error
    if ( trim( $data['post_content'] ) === '' ) {
        $data['post_content_filtered'] = '';
        return $data;
    }

    // process errors
    $errors = [];

    $filtered = wp_unslash( $data['post_content'] );

    // track tags
    // try to escape svgs with url-encoding
    if ( str_contains( $filtered, '<' ) && preg_match( '/<\/?\w+/', $filtered ) ) {
        // the idea is taken from https://github.com/yoksel/url-encoder/
        $svg_sanitized = preg_replace_callback( '/url\(\s*(["\']*)\s*data:\s*image\/svg\+xml(.*)\\1\s*\)/', function($m) {
            return 'url('.$m[1].'data:image/svg+xml'
                .preg_replace_callback( '/[\r\n%#\(\)<>\?\[\]\\\\^\`\{\}\|]+/', function($m) {
                    return urlencode( $m[0] );
                }, urldecode( $m[2] ) )
                .$m[1].')';
        }, $filtered );

        if ( $svg_sanitized === null ) {
            $errors['tags'] = 'SVG tags must be escaped by urlencode.';
        } else {
            $filtered = $svg_sanitized;
        }
    }
    // if tags still exist, forbid that
    // the idea is taken from WP_Customize_Custom_CSS_Setting::validate as well as the translation
    if ( str_contains( $filtered, '<' ) && preg_match( '/<\/?\w+/', $filtered ) ) {
        $errors['tags'] = 'HTML ' . __( 'Markup is not allowed in CSS.' );
    }

    // ++maybe add parser sometime later
    // ++safecss_filter_attr($css)??

    // correct
    if ( empty( $errors ) ) {
        $data['post_content_filtered'] = css_minify( wp_slash( $filtered ) ); // return slashes, and they will be stripped again right after
        return $data;
    }

    // wrong
    $data['post_content_filtered'] = '';

    update_option( FCPFSC_PREF.'_post_errors', $errors );

    add_filter( 'redirect_post_location', function($location) {
        $location = remove_query_arg( 'message', $location );
        $location = add_query_arg( 'css_content', 'wrong', $location );
        return $location;
    });

    return $data;

}, 10 );

// message on errors in css
add_action( 'admin_notices', function () {

    $screen = get_current_screen();
    if ( !isset( $screen ) || !is_object( $screen ) || $screen->post_type !== FCPFSC_SLUG || $screen->base !== 'post' || $screen->action === 'add' ) { return; }

    $errors = [];

    if ( isset( $_GET['css_content'] ) && $_GET['css_content'] === 'wrong' ) {
        $errors = get_option( FCPFSC_PREF.'_post_errors' );
        delete_option( FCPFSC_PREF.'_post_errors' );
    }

    if ( empty( $errors ) ) {
        global $post;
        if ( empty( $post->post_content_filtered ) && !empty( trim( $post->post_content ) ) ) {
            $errors[] = 'The CSS is incorrect. Please check it\'s syntax.';
        }
    }

    if ( empty( $errors ) ) { return; }
    $errors[] = 'The CSS content can not be applied to selected posts due to errors. Press Publish or Update to see the errors.';
    ?>

    <div class="notice notice-error"><ul>
    <?php array_walk( $errors, function($a) { ?>
        <li><?php echo wp_kses( $a, 'strip' ) ?></li>
    <?php }) ?>
    </ul></div>

    <?php
});


// functions -------------------------------------

function sanitize_meta( $value, $field ) {

    $field = ( strpos( $field, FCPFSC_PREF ) === 0 ) ? substr( $field, strlen( FCPFSC_PREF ) ) : $field;

    switch( $field ) {
        case( 'post-types' ):
            return array_intersect( $value, array_keys( get_all_post_types()['public'] ) );
        break;
        case( 'post-archives' ):
            return array_intersect( $value, array_keys( get_all_post_types()['archive'] ) );
        break;
        case( 'id' ):
            if ( !is_numeric( $value ) ) { return ''; }
            if ( !( $post = get_post( $value ) ) || $post->post_type !== FCPFSC_SLUG ) { return ''; }
            return $value;
        break;
    }

    return '';
}


function checkboxes($a) {
    ?>
<fieldset
    id="<?php echo esc_attr( FCPFSC_PREF . $a->name ) ?>"
    class="<?php echo isset( $a->className ) ? esc_attr( $a->className ) : '' ?>"><?php

    foreach ( (array) $a->options as $k => $v ) {
        $checked = is_array( $a->value ) && in_array( $k, $a->value );
    ?><label>
        <input type="checkbox"
            name="<?php echo esc_attr( FCPFSC_PREF . $a->name ) ?>[]"
            value="<?php echo esc_attr( $k ) ?>"
            <?php echo $checked ? 'checked' : '' ?>
        >
        <span><?php echo esc_html( $v ) ?></span>
    </label><?php } ?>
</fieldset>
    <?php
}

function select($a) {
    ?>
    <select
        name="<?php echo esc_attr( FCPFSC_PREF . $a->name ) ?>"
        id="<?php echo esc_attr( FCPFSC_PREF . $a->name ) ?>"
        class="<?php echo isset( $a->className ) ? esc_attr( $a->className ) : '' ?>"><?php

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


function get_css_ids( $key, $type = 'post' ) {

    global $wpdb;

    $metas = $wpdb->get_col( $wpdb->remove_placeholder_escape( $wpdb->prepare('

        SELECT `post_id`
        FROM `'.$wpdb->postmeta.'`
        WHERE `meta_key` = %s AND `meta_value` LIKE %s

    ', $key, $wpdb->add_placeholder_escape( '%"'.$type.'"%' ) ) ) );

    return $metas;
}

function get_css_contents_filtered( $ids ) { // ++add proper ordering

    if ( empty( $ids ) ) { return; }

    global $wpdb;

    $metas = $wpdb->get_col( $wpdb->prepare('

        SELECT `post_content_filtered`
        FROM `'.$wpdb->posts.'`
        WHERE `post_status` = %s AND `post_type` = %s AND `ID` IN ( '.implode( ',', array_fill( 0, count( $ids ), '%s' ), ).' )

    ', array_merge( [ 'publish', FCPFSC_SLUG ], $ids ) ) );

    return implode( '', $metas );
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
            if ( $type->name === 'page' ) { $type->label .= ' (except Front Page)'; }
            $public[ $type->name ] = $type->label;
        }
    }

    return [ 'all' => $all, 'public' => $public, 'archive' => $archive ];

}

function css_minify($css) {
    $css = preg_replace( '/\/\*(?:.*?)*\*\//', '', $css ); // remove comments
    $css = preg_replace( '/\s+/', ' ', $css ); // one-line & only single speces
    $css = preg_replace( '/ ?([\{\};:\>\~\+]) ?/', '$1', $css ); // remove spaces
    $css = preg_replace( '/\+(\d)/', ' + $1', $css ); // restore spaces in functions
    $css = preg_replace( '/(?:[^\}]*)\{\}/', '', $css ); // remove empty properties
    $css = str_replace( [';}', '( ', ' )'], ['}', '(', ')'], $css ); // remove last ; and spaces
    // ++ should also remove 0 from 0.5, but not from svg-s?
    // ++ try replacing ', ' with ','
    // ++ remove space between %3E %3C and before %3E and /%3E
    return trim( $css );
};

// meta boxes
function fcpfsc_meta_box() {
    global $post;

    // get post types to print options
    list( 'public' => $public_post_types, 'archive' => $archives_post_types ) = get_all_post_types();

    ?><p><strong>Apply to the following post types</strong></p><?php

    checkboxes( (object) [
        'name' => 'post-types',
        'options' => $public_post_types,
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'post-types' )[0],
    ]);

    ?><p><strong>Apply to the Archive pages of the following post types</strong></p><?php

    checkboxes( (object) [
        'name' => 'post-archives',
        'options' => $archives_post_types,
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'post-archives' )[0],
    ]);

    ?>
    <p>You can apply this styling to a separate post. Every public post type now has a special select box in the right sidebar to pick from the list of the first-screen-css posts, like this one.</p>
    <p>CSS will be minified before printing.</p>
    <p>You can grab the first screen css of a page with the script: <a href="https://github.com/VVolkov833/first-screen-css-grabber" target="_blank" rel="noopener">github.com/VVolkov833/first-screen-css-grabber</a></p>
    <?php

    wp_nonce_field( FCPFSC_PREF.'nounce-action', FCPFSC_PREF.'nounce-name' );
}

function anypost_meta_box() {
    global $post;

    // get css post types
    $css_posts0 = get_posts([
        'post_type' => FCPFSC_SLUG,
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
        'placeholder' => '------',
        'options' => $css_posts,
        'value' => get_post_meta( $post->ID, FCPFSC_PREF.'id' )[0],
    ]);

    wp_nonce_field( FCPFSC_PREF.'nounce-action', FCPFSC_PREF.'nounce-name' );
}