<?php

/**
 * Plugin Name: CBT System
 * Description: A plugin to manage computer-based testing systems for WORDPRESS.
 * Version: 1.0.0
 * Author: Rizky
 * Text Domain: cbt-system
 */

//Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define( 'CBT_SYSTEM_VERSION', '1.0.0' );
define( 'CBT_SYSTEM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CBT_SYSTEM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

//Include Required Files
require_once CBT_SYSTEM_PLUGIN_DIR . 'includes/class-cbt-database.php';
require_once CBT_SYSTEM_PLUGIN_DIR . 'includes/class-cbt-roles.php';
require_once CBT_SYSTEM_PLUGIN_DIR . 'includes/class-cbt-activator.php';
require_once CBT_SYSTEM_PLUGIN_DIR . 'includes/class-cbt-acf-fields.php';

// Set ACF JSON save/load point for this plugin
add_filter('acf/settings/save_json', fn() => CBT_SYSTEM_PLUGIN_DIR . 'acf-json');

add_filter('acf/settings/load_json', function ($paths) {
    // Remove original path
    unset($paths[0]);
    // Add plugin path
    $paths[] = CBT_SYSTEM_PLUGIN_DIR . 'acf-json';
    return $paths;
});

//Activation hook
register_activation_hook( __FILE__, array( 'CBT_Activator', 'activate' ) );

//Deactivation hook 
register_deactivation_hook( __FILE__, array( 'CBT_Activator', 'deactivate' ) );

//Initialize the plugin
add_action('init', function() {
    
    register_post_type('cbt_exam' , [
        'labels' => [
            'name'  => 'Exams',
            'singular_name' => 'Exam',
            'add_new'   => 'Add New Exam',
            'add_new_item' => 'Add New Exam',   
            'edit_item'     =>'Edit Exam',
            'new_item'      => 'New Exam',
            'view_item'     => 'View Exam',
            'search_items' => 'Search Exams',
            'not_found' => 'No exams found',
            'not_found_in_trash'    => 'No exams found in trash',
            'menu_name' => 'CBT Exams',
        ],
        'public'    =>false,
        'publicly_queryable' => false,
        'show_ui'   => true,
        'show_in_menu'  => true,
        'query_var' => true,
        'rewrite'=> ['slug' => 'exam'],
        'capability_type' => 'post',
        'has_archive' => false,
        'hierarchical' =>false,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-clipboard',
        'supports'      => ['title'],
    ]);
});