<?php
/*
 * Plugin Name: 	  Letter Avatars
 * Plugin URI:  	  https://wordpress.org/plugins/letter-avatars/
 * Description: 	  Letter Avatars enable you to use Letters from commenters names instead of generic avatars.
 * Version: 		  3.5
 * Requires at least: 5.3
 * Requires PHP:      7.2
 * Author: 	 	      Sibin Grasic
 * Author URI:        https://sgi.io
 * Text Domain:       letter-avatars
 */

namespace SGI\LtrAv;

use \SGI\LtrAv\Core\Bootstrap as Letter_Avatars;

// Prevent direct access
!defined('WPINC') && die;

!defined(__NAMESPACE__ . '\FILE')     && define(__NAMESPACE__ . '\FILE', __FILE__);                   // Define Main plugin file
!defined(__NAMESPACE__ . '\BASENAME') && define(__NAMESPACE__ . '\BASENAME', plugin_basename(FILE));  //Define Basename
!defined(__NAMESPACE__ . '\PATH')     && define(__NAMESPACE__ . '\PATH', plugin_dir_path( FILE ));    //Define internal path
!defined(__NAMESPACE__ . '\VERSION')  && define (__NAMESPACE__ . '\VERSION', '3.5');                // Define internal version
!defined(__NAMESPACE__ . '\DOMAIN')   && define (__NAMESPACE__ . '\DOMAIN', 'letter-avatars');        // Define Text domain

// Bootstrap the plugin
require (PATH . '/vendor/autoload.php');

// Run the plugin
function runLetterAvatars()
{

    global $wp_version;

    if (version_compare( PHP_VERSION, '7.2.0', '<' ))
        throw new \Exception(__('Letter Avatars plugin requires PHP 7.2 or greater.', DOMAIN));

    if (version_compare($wp_version, '5.1', '<'))
        throw new \Exception(__('Letter Avatars plugin requires WordPress 5.1.0.', DOMAIN));

    LtrAv();

}

// And awaaaaay we goooo
try {

    runLetterAvatars();

} catch (\Exception $e) {


    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    deactivate_plugins( __FILE__ );
    wp_die($e->getMessage());

}