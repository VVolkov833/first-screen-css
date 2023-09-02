<?php
/*
Plugin Name: FCP First Screen CSS
Description: Insert inline CSS to the head of the website, so the first screen renders with no jumps, which might improve the CLS web vital. Or for any other reason.
Version: 1.6
Requires at least: 5.8
Tested up to: 6.3
Requires PHP: 7.4
Author: Firmcatalyst, Vadim Volkov
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
define( 'FCPFSC_FRONT_PREF', 'first-screen' );

define( 'FCPFSC_URL', plugin_dir_url( __FILE__ ) );
define( 'FCPFSC_DIR', plugin_dir_path( __FILE__ ) );

define( 'FCPFSC_CM_VER', '5.65.13' ); // codemirror version


require FCPFSC_DIR . 'inc/functions.php';
require FCPFSC_DIR . 'inc/apply.php';
require FCPFSC_DIR . 'inc/admin/main.php';


register_activation_hook( __FILE__, function() {
    wp_mkdir_p( wp_upload_dir()['basedir'] . '/' . basename( __DIR__ ) );
    register_uninstall_hook( __FILE__, 'FCP\FirstScreenCSS\delete_the_plugin' );
} );

function delete_the_plugin() {
    $dir = wp_upload_dir()['basedir'] . '/' . basename( __DIR__ );
    array_map( 'unlink', glob( $dir . '/*' ) );
    rmdir( $dir );
}


// ++refactor - split in files
// ++add the option to switch to inline
// ++add the option to defer loading
// ++add the @bigger height@ button and save new height in local storage
// ++switch selects to checkboxes or multiples
// ++maybe limit the id-exclude to the fitting post types
// ++don't show rest meta box if the storing dir is absent or is not writable or/and the permission error
// ++get the list of css to unload with jQuery.html() && regexp, or ?query in url to print loaded scripts
// ++list of styles to defer like with deregister?