<?php
/**
 * Exam Controller - Handles exam-related API endpoints
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class CBT_Exam_Controller {
    /**
     * GET / exams - List available exams for a class
     * Get Specific published exams
     * Check if exam matches class
     * check if exam is visible
     * Check if current time is within exam window time
     */

    public static function getExams($request) {
        $class = $request->get_param('class');
        $now = current_time('mysql');

        //Get published exams
        $exams = get_posts([
            'post_type' => 'cbt_exam',
            'post_per_page' => -1,
            'post_status' => 'publish',
        ]);

        $available_exams = [];

        foreach ($exams as $exam) {
            $exam_class = get_field('exam_class', $exam->ID);
            $start_time =  get_field('exam_start_time', $exam->ID);
            $end_time = get_field('exam_end_time', $exam->ID);
            $is_visible = get_field('exam_is_visible', $exam->ID);
            $duration = get_field('exam_duration', $exam->ID);
            $description = get_field('exam_description', $exam->ID);

            if ($class === $exam_class && $is_visible && $now >= $start_time && $now <= $end_time) {
                $available_exams[] = [
                    'id' => $exam->ID,
                    'title' => $exam->post_title,
                    'description' => $description,
                    'duration' => $duration,
                    'start_time' => $start_time,
                    'end_time' => $end_time
                ];
            }

        if($exam_class !== $class){
            continue;
        }

        if(!$is_visible){
            continue;
        }

        if($now < $start_time || $now > $end_time){
            continue;       
        }

        $available_exams[] = [
            'id' => $exam->ID,
            'title' => $exam->post_title,
            'description' => $description,
            'duration_minutes' => intval($duration),
            'start_time' => $start_time,
            'end_time' => $end_time
        ];
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $available_exams
        ]);
    }

    /**
     * GET / exams / {id} - Get details of a specific exam
     * Get Exam Post per specific ID
     * Get Necessary ACF Field
     * Get Questions
     * Formatiing Answers for response
     */

    public static function getExam($request) {
        global $wpdb;

        $exam_id = $request->get_param('id');
        $questions_table = $wpdb->prefix . 'cbt_questions';

        //get exam post
        $exam = get_post($exam_id);

        if (!$exam || $exam->post_type !== 'cbt_exam') {
                return new WP_Error('exam_not_found', ' Ujian tidak ditemukan');
        }
        
        //get ACF fields
        $description = get_field('exam_description', $exam_id);
        $duration = get_field('exam_duration', $exam_id);
        $start_time = get_field('exam_start_time', $exam_id);
        $end_time = get_field('exam_end_time', $exam_id);

        //get questions from custom table
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, question_text, image_url, choice_a, choice_b, choice_c, choice_d, question_order 
             FROM $questions_table 
             WHERE exam_id = %d 
             ORDER BY question_order ASC",
            $exam_id
        ));

        //get formatted questions
        $formatted_questions = [];
        foreach ($questions as $question) {
            $formatted_questions[] = [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'image_url' => $question->image_url,
                'choices' => [
                    'a' => $question->choice_a,
                    'b' => $question->choice_b,
                    'c' => $question->choice_c,
                    'd' => $question->choice_d,
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