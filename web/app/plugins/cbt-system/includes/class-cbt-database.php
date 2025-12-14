<?php
/**
 * Database table creation and management
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class CBT_Database
{

    public static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        //Table names with prefix
        $exams_table = $wpdb->prefix . 'cbt_exams';
        $questions_table = $wpdb->prefix . 'cbt_questions';
        $attempts_table = $wpdb->prefix . 'cbt_attempts';
        $answers_table = $wpdb->prefix . 'cbt_answers';

        //SQL for cbt_exams table
        $sql_exams = "CREATE TABLE $exams_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            class VARCHAR(100) NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            duration_minutes INT(11) NOT NULL DEFAULT 120,
            is_visible TINYINT(1) NOT NULL DEFAULT 0,
            shuffle_questions TINYINT(1) NOT NULL DEFAULT 0,
            shuffle_choices TINYINT(1) NOT NULL DEFAULT 0,
            created_by BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY class_idx (class),
            KEY visibility_idx (is_visible),
            KEY time_idx (start_time, end_time)
        ) $charset_collate;";

        // SQL for cbt_questions table
        $sql_questions = "CREATE TABLE $questions_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            exam_id BIGINT(20) UNSIGNED NOT NULL,
            question_text TEXT NOT NULL,
            image_url VARCHAR(500),
            choice_a VARCHAR(500) NOT NULL,
            choice_b VARCHAR(500) NOT NULL,
            choice_c VARCHAR(500) NOT NULL,
            choice_d VARCHAR(500) NOT NULL,
            correct_answer CHAR(1) NOT NULL DEFAULT 0,
            question_order INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exam_idx (exam_id),
            KEY order_idx (question_order)
        ) $charset_collate;";

        //SQL for cbt_attempts table
        $sql_attempts = "CREATE TABLE $attempts_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        student_id BIGINT(20) UNSIGNED NOT NULL,
        exam_id BIGINT(20) UNSIGNED NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME,
        score DECIMAL(5,2) DEFAULT NULL,
        total_questions INT(11) NOT NULL DEFAULT 0,
        correct_answers INT(11) NOT NULL DEFAULT 0,
        is_submitted TINYINT(1) NOT NULL DEFAULT 0,
        submitted_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY student_idx (student_id),
        KEY exam_idx (exam_id),
        KEY submission_idx (is_submitted),
        UNIQUE KEY student_exam_unique (student_id, exam_id)
        ) $charset_collate;";

        //SQL for cbt_answers table
        $sql_answers = "CREATE TABLE $answers_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        attempt_id BIGINT(20) UNSIGNED NOT NULL,
        question_id BIGINT(20) UNSIGNED NOT NULL,
        student_answer CHAR(1) NOT NULL,
        is_correct TINYINT(1) NOT NULL DEFAULT 0,
        answered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY attempt_idx (attempt_id),
        KEY question_idx (question_id),
        UNIQUE KEY attempt_question_unique (attempt_id , question_id)
        ) $charset_collate;";

        //Include Wordpress upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        //Execute table creation
        dbDelta($sql_exams);
        dbDelta($sql_questions);
        dbDelta($sql_attempts);
        dbDelta($sql_answers);

        //Store database version
        update_option('cbt_system_db_version', "1.0");
    }

    /**
     * Drop all custom tables (use with caution)
     */
    public static function drop_tables()
    {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'cbt_exams',
            $wpdb->prefix . 'cbt_questions',
            $wpdb->prefix . 'cbt_attempts',
            $wpdb->prefix . 'cbt_answers',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('cbt_system_db_version');
    }


}