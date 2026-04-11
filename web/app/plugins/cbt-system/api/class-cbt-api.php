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
    const API_NAMESPACE = 'cbt/v1';

    /**
     * Initialize API
     */
    public static function init(){
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    /**
     * Register all REST routes
     */

    public static function register_routes() {
        
    // == EXAM ROUTES == 
    // GET / exams?class=XII-IPA-1

        register_rest_route(self::API_NAMESPACE, '/exams', [
            'methods'  => 'GET',
            'callback' => ['CBT_Exam_Controller', 'get_exams'],
            'permission_callback' => '__return_true',
            'args' => [
                'class' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

    // GET /exam/{id}

        register_rest_route(self::API_NAMESPACE, '/exam/(?P<id>\d+)' , [
            'methods' => 'GET',
            'callback' => ['CBT_Exam_Controller' , 'get_exam'],
            'permission_callback' => '__return_true' ,
            'args' => [
                'id' => [
                    'required' => true ,
                    'type' => 'integer' ,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
            ],
        ]);

    // == ATTEMP ROUTES ==

    // POST /start
    register_rest_route(self::API_NAMESPACE , '/start' , [
        'methods' => 'POST',
        'callback' => ['CBT_Attempt_Controller', 'start_exam'],
        'permission_callback' => '__return_true' ,
        'args' => [
            'exam_id' => [
                'required' => true,
                'type' => 'integer',
            ],
            'student_name' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'student_class' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'student_nisn' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ],
        ],
    ]);

    // POST /submit
    register_rest_route(self::API_NAMESPACE , '/submit' , [
        'methods' => 'post',
        'callback' => ['CBT_Attempt_Controller' , 'submit_exam'],
        'permission_callback' => '__return_true',
        'args' => [
            'attempt_id' => [
                'required' => true,
                'type' => 'integer',
            ],
        ], 
    ]);
    

    // GET /attempt/{id}
        register_rest_route(self::API_NAMESPACE, '/attempt/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => ['CBT_Attempt_Controller', 'get_attempt'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);
    


        register_rest_route(self::API_NAMESPACE, '/answer', [
            'methods'  => 'POST',
            'callback' => ['CBT_Answer_Controller', 'save_answer'],
            'permission_callback' => '__return_true',
            'args' => [
                'attempt_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
                'question_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
                'answer' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }
}