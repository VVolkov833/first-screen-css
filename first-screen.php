<?php
/*
Plugin Name: First Screen CSS & Settings
Description: This is a professional tool to manipulate enqueued styles and scripts on your website and add custom CSS to first screen and not first screen optionally. Use it to improve your Core Web Vitals score or just add custom styling.
Version: 1.6.4
Requires at least: 5.8
Tested up to: 6.3
Requires PHP: 7.4
Author: Vadim Volkov, Firmcatalyst
Author URI: https://firmcatalyst.com
License: GPL v3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace FCP\FirstScreenCSS;
defined( 'ABSPATH' ) || exit;

define( 'FCPFSC_DEV', false ); // for own assets and minifying inlined styles
define( 'FCPFSC_VER', get_file_data( __FILE__, [ 'ver' => 'Version' ] )[ 'ver' ] . ( FCPFSC_DEV ? time() : '' ) ); // only for own plugin's assets

define( 'FCPFSC_SLUG', 'fcpfsc' );
define( 'FCPFSC_PREF', FCPFSC_SLUG.'-' );
define( 'FCPFSC_FRONT_NAME', 'first-screen' );

define( 'FCPFSC_URL', plugin_dir_url( __FILE__ ) );
define( 'FCPFSC_DIR', plugin_dir_path( __FILE__ ) );
define( 'FCPFSC_BSN', plugin_basename(__FILE__) );

define( 'FCPFSC_REST_URL', wp_upload_dir()['baseurl'] . '/' . basename( FCPFSC_DIR ) );
define( 'FCPFSC_REST_DIR', wp_upload_dir()['basedir'] . '/' . basename( FCPFSC_DIR ) );

define( 'FCPFSC_CM_VER', '5.65.13' ); // codemirror version


require FCPFSC_DIR . 'inc/functions.php';
require FCPFSC_DIR . 'inc/apply/main.php';
require FCPFSC_DIR . 'inc/admin/main.php';


// install / uninstall the plugin
register_activation_hook( __FILE__, function() use ($meta_close_by_default) {
     // store the non-first-screen css (rest-css)
    wp_mkdir_p( FCPFSC_REST_DIR );

    // close secondary meta boxes by default
    $admins = get_users(['role' => 'administrator']);
    foreach ($admins as $admin) {
        // set the default state for the specified metaboxes
        update_user_meta($admin->ID, 'closedpostboxes_'.FCPFSC_SLUG, $meta_close_by_default);
    }

    //
    register_uninstall_hook( __FILE__, 'FCP\FirstScreenCSS\delete_the_plugin' );
} );

function delete_the_plugin() {
    return true;
    // delete the rest-storage // deprecated as it doesn't restore after re-install the plugin
    /*
    $dir = FCPFSC_REST_DIR;
    array_map( 'unlink', glob( FCPFSC_REST_DIR . '/*' ) );
    rmdir( FCPFSC_REST_DIR );
    //*/
    // ++ add the setting to delete all the plugin's leftovers
}

// ++Ctrl + H
// ++highlight the developers mode as it can be the source of rage
// ++add those metas to the table too
// ++first field placeholder make more obvious
// ++switch selects to checkboxes or multiples
// ++maybe limit the id-exclude to the fitting post types
// ++don't show rest meta box if the storing dir is absent or is not writable or/and the permission error
// ++get the list of css to unload with jQuery.html() && regexp, or ?query in url to print loaded scripts
// ++dont minify if local dev mode is on + add the comment for the content source