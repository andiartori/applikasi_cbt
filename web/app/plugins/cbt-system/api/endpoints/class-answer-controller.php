<?php
/**
 * Answer Controller - Handles answer-related API endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class CBT_Answer_Controller {

    /**
     * POST /answer - Save an answer (autosave)
     */
    public static function save_answer($request) {
        global $wpdb;
        
        $attempt_id = intval($request->get_param('attempt_id'));
        $question_id = intval($request->get_param('question_id'));
        $answer = strtolower($request->get_param('answer'));
        
        $attempts_table = $wpdb->prefix . 'cbt_attempts';
        $answers_table = $wpdb->prefix . 'cbt_answers';

        // Validate token using Attempt Controller
        $attempt = CBT_Attempt_Controller::validate_token($request, $attempt_id);
        if (is_wp_error($attempt)) {
            return $attempt;
        }

        // Validate answer is a, b, c, or d
        if (!in_array($answer, ['a', 'b', 'c', 'd'])) {
            return new WP_Error('invalid_answer', 'Jawaban harus a, b, c, atau d', ['status' => 400]);
        }

        if ($attempt->is_submitted) {
            return new WP_Error('already_submitted', 'Ujian sudah dikumpulkan', ['status' => 400]);
        }

        // Check if answer already exists
        $existing_answer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $answers_table WHERE attempt_id = %d AND question_id = %d",
            $attempt_id,
            $question_id
        ));

        $now = current_time('mysql');

        if ($existing_answer) {
            // Update existing answer
            $wpdb->update(
                $answers_table,
                [
                    'student_answer' => $answer,
                    'answered_at' => $now,
                ],
                [
                    'attempt_id' => $attempt_id,
                    'question_id' => $question_id,
                ]
            );
        } else {
            // Insert new answer
            $wpdb->insert($answers_table, [
                'attempt_id' => $attempt_id,
                'question_id' => $question_id,
                'student_answer' => $answer,
                'answered_at' => $now,
            ]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Jawaban tersimpan',
        ]);
    }
}