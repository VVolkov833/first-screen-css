<?php
/*
Plugin Name: First Screen CSS & Settings
Description: Insert the inline CSS into your website's head tag. You can choose to inline, defer, or de-register existing styles and scripts. Additionally, you can add custom non-inline styles. Apply everything individually, by post-type, or for archives.
Version: 1.6
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

define( 'FCPFSC_DEV', true );
define( 'FCPFSC_VER', get_file_data( __FILE__, [ 'ver' => 'Version' ] )[ 'ver' ] . ( FCPFSC_DEV ? time() : '' ) );

define( 'FCPFSC_SLUG', 'fcpfsc' );
define( 'FCPFSC_PREF', FCPFSC_SLUG.'-' );
define( 'FCPFSC_FRONT_NAME', 'first-screen' );

define( 'FCPFSC_URL', plugin_dir_url( __FILE__ ) );
define( 'FCPFSC_DIR', plugin_dir_path( __FILE__ ) );

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


// ++add the option to switch to inline
// ++add the option to defer loading
// ++autopick the names by url or the instruction how to
// ++add the @bigger height@ button and save new height in local storage or user settings?
// ++switch selects to checkboxes or multiples
// ++maybe limit the id-exclude to the fitting post types
// ++don't show rest meta box if the storing dir is absent or is not writable or/and the permission error
// ++get the list of css to unload with jQuery.html() && regexp, or ?query in url to print loaded scripts
// ++list of styles to defer like with deregister?