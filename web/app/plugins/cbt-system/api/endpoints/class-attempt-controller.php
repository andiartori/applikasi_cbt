<?php
/**
 * Attempt Controller - Handles attempt-related API endpoints
 */

if(!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


class CBT_Attempt_Controller {
    /**
     * POST / STAART - Start an exam attempt 
     */

    public static function start_exam($request) {

        global $wpdb;

        $exam_id = $request->get_param('exam_id');
        $student_name = $request->get_param('student_name');
        $student_class = $request->get_param('student_class');
        $student_nisn = $request->get_param('student_nisn');

        $attempts_table = $wpdb->prefix . 'cbt_attempts';
        $questions_table = $wpdb->prefix . 'cbt_questions';

        //Validate exam exists
        $exam = get_post($exam_id);
        if(!$exam || $exam->post_type !== 'cbt_exam') {
            return new WP_Error('invalid_exam', 'Ujian Tidak Ditemukan', array('status' => 404));
        }

        //Check if student already attempt for this exam
        $existing_attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $attempts_table WHERE exam_id = %d AND student_nisn = %s",
            $exam_id,
            $student_nisn
        ));

        if ($existing_attempt) {
            if (!$existing_attempt->is_submitted) {
                return rest_ensure_response([
                    'success' => true,
                    'message' => 'Melanjutkan ujian sebelumnya',
                    'data' => [
                        'attempt_id' => intval($existing_attempt->id),
                        'start_time' => $existing_attempt->start_time,
                        'is_resuming' => true,  
                    ],
                ]);
        } else {
            return new WP_Error('already_submitted' , 'Anda sudah menyelesaikan ujian ini');
        }
    }

    // Get total questions
        $total_questions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $questions_table WHERE exam_id = %d",
            $exam_id
        ));

    //Create new attempt
    $now = current_time('mysql');

    $result = $wpdb->insert($attempts_table ,[
        'exam_id' => $exam_id,
        'student_name' => $student_name,
        'student_class' => $student_class,
        'student_nisn' => $student_nisn,
        'start_time' => $now,
        'total_questions' => $total_questions,
        'is_submitted' => 0,
    ]);

    if (!$result) {
        return new WP_Error('db_insert_error', 'Gagal memulai ujian. Silakan coba lagi.', array('status' => 500));
    }

    $attempt_id = $wpdb->insert_id;

    return rest_ensure_response([
        'success' => true,
        'message' => 'Ujian dimulai',
        'data' => [
            'attempt_id' => intval($attempt_id),
            'start_time' => $now,
            'is_resuming' => false,  
        ],
    ]);
}

/**
 * Post /submit - Submit exam and calculate score
 */

public static function submit_exam($request) {
    global $wpdb;

    $attempt_id = $request->get_param('attempt_id');

    $attempts_table = $wpdb->prefix . 'cbt_attempts';
    $answers_table = $wpdb->prefix . 'cbt_answers';
    $questions_table = $wpdb->prefix . 'cbt_questions';

    $attempt = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $attempts_table WHERE id = %d",
        $attempt_id
    ));

    if (!$attempt) {
        return new WP_Error('invalid_attempt', 'Attempt tidak ditemukan', array('status' => 404));
    }

    if ($attempt->is_submitted) {
        return new WP_Error('already_submitted', 'Ujian sudah disubmit', array('status' => 400));
    }

    //Get All Questions for this exam
    $questions = $wpdb->get_results($wpdb->prepare(
        "SELECT id, correct_choice FROM $questions_table WHERE exam_id = %d",
        $attempt->exam_id
    ));

    //Get all answer for this attempt
    $answers = $wpdb->get_results($wpdb->prepare(
        "SELECT question_id, student_answer FROM $answers_table WHERE attempt_id = %d",
        $attempt_id
    ));

    // Create Answer Lookup
    $answer_lookup = [];
    foreach ($answers as $answer) {
        $answer_lookup[$answer->question_id] = $answer->student_answer;
    }




}
}



