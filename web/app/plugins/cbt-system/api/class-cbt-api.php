<?php
/**
 * REST API ROUTER - Routes registration only
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include Controllers
require_once plugin_dir_path(__FILE__) . 'endpoints/class-exam-controller.php';
require_once plugin_dir_path(__FILE__) . 'endpoints/class-attempt-controller.php';
require_once plugin_dir_path(__FILE__) . 'endpoints/class-answer-controller.php';

class CBT_API {
    /**
     * API namespace
     */
    const NAMESPACE = 'cbt/v1';

    /**
     * Initialize API
     */
    public static function init(){
        add_action('rest_api_init', [self::class, 'register_routes']);
    }
}