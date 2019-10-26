<?php
/*
Plugin Name: Comment Limiter
Description: A simple plugin that limit the maximum and minimum of characters allowed in a post comment
Version:     1.2
Author:      Anass Rahou
Author URI:  https://wpbody.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: comment-limiter
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CL_VERSION', '2' );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-comment-limiter-i18n.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-comment-limiter-config.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-comment-limiter-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-comment-limiter-deactivator.php';


Comment_Limiter_i18n::factory();
Comment_Limiter_Config::factory();
Comment_Limiter_Settings::factory();
Comment_Limiter_Deactivator::factory();


/**
 * Add settings link to plugin actions
 *
 * @param  array  $plugin_actions
 * @param  string $plugin_file
 * @since  1.0
 * @return array
 */
function cl_filter_plugin_action_links( $plugin_actions, $plugin_file ) {
    
    $new_actions = array();
    
    if ( basename( dirname( __FILE__ ) ) . '/comment-limiter.php' === $plugin_file ) {
        $new_actions['cl_settings'] = sprintf( __( '<a href="%s">Settings</a>', 'comment-limiter' ), esc_url( admin_url( 'options-general.php?page=comment-limiter' ) ) );
    }
    
    return array_merge( $new_actions, $plugin_actions );
}
add_filter( 'plugin_action_links', 'cl_filter_plugin_action_links', 10, 2 );