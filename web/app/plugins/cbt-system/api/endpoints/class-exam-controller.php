<?php
/**
 * Exam Controller - Handles exam-related API endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class CBT_Exam_Controller {

    /**
     * GET /exams - List available exams for a class
     */
    public static function get_exams($request) {
        $class = $request->get_param('class');
        $now = current_time('mysql');

        // Get published exams
        $exams = get_posts([
            'post_type' => 'cbt_exam',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        $available_exams = [];

        foreach ($exams as $exam) {
            $exam_class = get_field('exam_class', $exam->ID);
            $start_time = get_field('exam_start_time', $exam->ID);
            $end_time = get_field('exam_end_time', $exam->ID);
            $is_visible = get_field('exam_visible', $exam->ID);
            $duration = get_field('exam_duration', $exam->ID);
            $description = get_field('exam_description', $exam->ID);

            // Check if exam matches class
            if ($exam_class !== $class) {
                continue;
            }

            // Check if exam is visible
            if (!$is_visible) {
                continue;
            }

            // Check if current time is within exam window
            if ($now < $start_time || $now > $end_time) {
                continue;
            }

            $available_exams[] = [
                'id' => $exam->ID,
                'title' => $exam->post_title,
                'description' => $description,
                'duration_minutes' => intval($duration),
                'start_time' => $start_time,
                'end_time' => $end_time,
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $available_exams,
        ]);
    }

    /**
     * GET /exam/{id} - Get exam details with questions
     */
    public static function get_exam($request) {
        global $wpdb;
        
        $exam_id = $request->get_param('id');
        $questions_table = $wpdb->prefix . 'cbt_questions';

        // Get exam post
        $exam = get_post($exam_id);

        if (!$exam || $exam->post_type !== 'cbt_exam') {
            return new WP_Error('not_found', 'Ujian tidak ditemukan', ['status' => 404]);
        }

        // Get ACF fields
        $description = get_field('exam_description', $exam_id);
        $duration = get_field('exam_duration', $exam_id);
        $start_time = get_field('exam_start_time', $exam_id);
        $end_time = get_field('exam_end_time', $exam_id);

        // Get questions
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, question_text, image_url, choice_a, choice_b, choice_c, choice_d, question_order 
             FROM $questions_table 
             WHERE exam_id = %d 
             ORDER BY question_order ASC",
            $exam_id
        ));

        // Format questions for response (without correct answers!)
        $formatted_questions = [];
        foreach ($questions as $q) {
            $formatted_questions[] = [
                'id' => intval($q->id),
                'question_text' => $q->question_text,
                'image_url' => $q->image_url,
                'choices' => [
                    'a' => $q->choice_a,
                    'b' => $q->choice_b,
                    'c' => $q->choice_c,
                    'd' => $q->choice_d,
                ],
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'data' => [
                'id' => $exam->ID,
                'title' => $exam->post_title,
                'description' => $description,
                'duration_minutes' => intval($duration),
                'start_time' => $start_time,
                'end_time' => $end_time,
                'total_questions' => count($formatted_questions),
                'questions' => $formatted_questions,
            ],
        ]);
    }
}