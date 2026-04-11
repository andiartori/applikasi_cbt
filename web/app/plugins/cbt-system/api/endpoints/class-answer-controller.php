<?php
/**
 * Answer Controller for CBT API
 */

if(!defined('ABSPATH')) {
    exit;
}

class CBT_Answer_Controller {

    /**
     * POST /answer - Save an answer (autosave)
     */
    public static function save_answer($request) {
        global $wpdb;   

        // --- STEP 1: EXTRACT AND SANITIZE INPUT incluing attempt_id, question_id, and answer ---


        $attempt_id = intval($request->getparam('attempt_id'));
        $question_id = intval($request->getparam('question_id'));
        $answer = strtolower($request->getparam('answer'));

        // --- STEP 2: Define Table Name and Prepare Data for Insertion table is attemps_table and answers_table ---
        $attempts_table = $wpdb->prefix . 'cbt_attempts';
        $answers_table = $wpdb->prefix . 'cbt_answers';

        // --- STEP 3: VALIDATE THE ANSWER VALUE ---
        if (!in_array($answer, ['a' , 'b', 'c', 'd'])) {
            return new WP_Error (
                'invalid_answer' ,
                'Jawaban harus berupa salah satu dari A, B, C, atau D' ,
                ['status' => 400]);     
        }

        // --- STEP 4: CHECK IF THE ATTEMPT EXISTS AND IS ACTIVE ---
        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $attempts_table WHERE id = %d",
            $attempt_id
        ));

        if (!$attempt) {
            return new WP_Error (
                'attempt_not_found' ,
                'Percobaan tidak ditemukan' ,
                ['status' => 404]); 
        }

        // --- STEP 5: CHECK IF THE EXAM IS ALREADY SUBMITTED ---
        if ($attempt->is_submitted) {
            return new WP_Error(
                'already_submitted',
                'Ujian sudah dikumpulkan',
                ['status' => 400]   // HTTP 400 = Bad Request
            );
        }

        // --- STEP 6: CHECK IF AN ANSWER ALREADY EXISTS FOR THIS QUESTION ---
        $existing_answer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $answers_table WHERE attempt_id = %d AND question_id = %d",
            // We search by BOTH attempt_id AND question_id together,
            // because the same question_id can appear in different attempts.
            $attempt_id,
            $question_id
        ));
        // Returns the existing answer row, or NULL if this question hasn't been answered yet.

        // Get the current timestamp in MySQL format (e.g. "2025-04-11 10:30:00")
        // current_time('mysql') uses WordPress's timezone setting, not server time.
        $now = current_time('mysql');

        // --- STEP 7: INSERT OR UPDATE ---
        if($existing_answer)
        {
            $wpdb->update(
                $answers_table,
                [
                    'student_answer' => $answer,
                    'answered_at' => $now
                ],
                [
                    'attempt_id' => $attempt_id,
                    'question_id' => $question_id,
                ]
            );
        } else {
            $wpdb->insert(
                $answers_table,
                [
                    'attempt_id' => $attempt_id,
                    'question_id' => $question_id,
                    'student_answer' => $answer,
                    'answered_at' => $now
                ]
            );
        }

    // --- STEP 8: RETURN SUCCESS RESPONSE ---

        return rest_ensure_response([
            'success' => true,
            'message' => 'Jawaban tersimpan',
        ]);
    }


    

    









}