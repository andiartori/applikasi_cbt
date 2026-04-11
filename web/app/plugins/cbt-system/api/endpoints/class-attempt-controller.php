<?php
/**
 * Attempt Controller - Handles exam attempt API endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class CBT_Attempt_Controller {

    /**
     * Generate a secure random token
     */
    private static function generate_token() {
        return bin2hex(random_bytes(32)); // 64 character hex string
    }

    /**
     * Validate token from request header
     */
    public static function validate_token($request, $attempt_id) {
        global $wpdb;
        $attempts_table = $wpdb->prefix . 'cbt_attempts';

        // Get token from header
        $provided_token = $request->get_header('X-CBT-Token');

        if (!$provided_token) {
            return new WP_Error('missing_token', 'Token tidak ditemukan', ['status' => 401]);
        }

        // Get attempt from database
        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $attempts_table WHERE id = %d",
            $attempt_id
        ));

        if (!$attempt) {
            return new WP_Error('not_found', 'Attempt tidak ditemukan', ['status' => 404]);
        }

        // Validate token matches
        if ($attempt->token !== $provided_token) {
            return new WP_Error('invalid_token', 'Token tidak valid', ['status' => 401]);
        }

        return $attempt; // Return attempt object if valid
    }

    /**
     * POST /start - Start an exam attempt
     */
    public static function start_exam($request) {
        global $wpdb;
        
        $exam_id = intval($request->get_param('exam_id'));
        $student_name = $request->get_param('student_name');
        $student_class = $request->get_param('student_class');
        $student_nisn = $request->get_param('student_nisn');
        
        $attempts_table = $wpdb->prefix . 'cbt_attempts';
        $questions_table = $wpdb->prefix . 'cbt_questions';

        // Validate NISN format (10 digits)
        if (!preg_match('/^\d{10}$/', $student_nisn)) {
            return new WP_Error('invalid_nisn', 'NISN harus 10 digit angka', ['status' => 400]);
        }

        // Validate exam exists
        $exam = get_post($exam_id);
        if (!$exam || $exam->post_type !== 'cbt_exam') {
            return new WP_Error('not_found', 'Ujian tidak ditemukan', ['status' => 404]);
        }

        // Validate exam is for this class
        $exam_class = get_field('exam_class', $exam_id);
        if ($exam_class !== $student_class) {
            return new WP_Error('wrong_class', 'Ujian ini bukan untuk kelas Anda', ['status' => 403]);
        }

        // Validate exam is visible
        $is_visible = get_field('exam_visible', $exam_id);
        if (!$is_visible) {
            return new WP_Error('not_available', 'Ujian tidak tersedia', ['status' => 403]);
        }

        // Validate exam time window
        $now = current_time('mysql');
        $start_time = get_field('exam_start_time', $exam_id);
        $end_time = get_field('exam_end_time', $exam_id);

        if ($now < $start_time) {
            return new WP_Error('not_started', 'Ujian belum dimulai', ['status' => 403]);
        }

        if ($now > $end_time) {
            return new WP_Error('ended', 'Ujian sudah berakhir', ['status' => 403]);
        }

        // Check if student already has an attempt for this exam
        $existing_attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $attempts_table WHERE exam_id = %d AND student_nisn = %s",
            $exam_id,
            $student_nisn
        ));

        if ($existing_attempt) {
            // Return existing attempt if not submitted
            if (!$existing_attempt->is_submitted) {
                return rest_ensure_response([
                    'success' => true,
                    'message' => 'Melanjutkan ujian sebelumnya',
                    'data' => [
                        'attempt_id' => intval($existing_attempt->id),
                        'token' => $existing_attempt->token,
                        'start_time' => $existing_attempt->start_time,
                        'is_resuming' => true,
                    ],
                ]);
            } else {
                return new WP_Error('already_submitted', 'Anda sudah mengumpulkan ujian ini', ['status' => 400]);
            }
        }

        // Get total questions
        $total_questions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $questions_table WHERE exam_id = %d",
            $exam_id
        ));

        // Generate token
        $token = self::generate_token();

        // Create new attempt
        $result = $wpdb->insert($attempts_table, [
            'exam_id' => $exam_id,
            'student_name' => $student_name,
            'student_class' => $student_class,
            'student_nisn' => $student_nisn,
            'token' => $token,
            'start_time' => $now,
            'total_questions' => $total_questions,
            'is_submitted' => 0,
        ]);

        if (!$result) {
            return new WP_Error('db_error', 'Gagal memulai ujian', ['status' => 500]);
        }

        $attempt_id = $wpdb->insert_id;

        return rest_ensure_response([
            'success' => true,
            'message' => 'Ujian dimulai',
            'data' => [
                'attempt_id' => $attempt_id,
                'token' => $token,
                'start_time' => $now,
                'is_resuming' => false,
            ],
        ]);
    }

    /**
     * POST /submit - Submit exam and calculate score
     */
    public static function submit_exam($request) {
        global $wpdb;
        
        $attempt_id = intval($request->get_param('attempt_id'));
        
        // Validate token
        $attempt = self::validate_token($request, $attempt_id);
        if (is_wp_error($attempt)) {
            return $attempt;
        }

        $attempts_table = $wpdb->prefix . 'cbt_attempts';
        $answers_table = $wpdb->prefix . 'cbt_answers';
        $questions_table = $wpdb->prefix . 'cbt_questions';

        if ($attempt->is_submitted) {
            return new WP_Error('already_submitted', 'Ujian sudah dikumpulkan', ['status' => 400]);
        }

        // Get all questions for this exam
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, correct_answer FROM $questions_table WHERE exam_id = %d",
            $attempt->exam_id
        ));

        // Get all answers for this attempt
        $answers = $wpdb->get_results($wpdb->prepare(
            "SELECT question_id, student_answer FROM $answers_table WHERE attempt_id = %d",
            $attempt_id
        ));

        // Create answer lookup
        $answer_lookup = [];
        foreach ($answers as $a) {
            $answer_lookup[$a->question_id] = $a->student_answer;
        }

        // Calculate score
        $correct_count = 0;
        $total_questions = count($questions);

        foreach ($questions as $q) {
            $student_answer = isset($answer_lookup[$q->id]) ? $answer_lookup[$q->id] : null;
            
            if ($student_answer === $q->correct_answer) {
                $correct_count++;
                
                // Update is_correct in answers table
                $wpdb->update(
                    $answers_table,
                    ['is_correct' => 1],
                    [
                        'attempt_id' => $attempt_id,
                        'question_id' => $q->id,
                    ]
                );
            } else {
                // Mark as incorrect
                $wpdb->update(
                    $answers_table,
                    ['is_correct' => 0],
                    [
                        'attempt_id' => $attempt_id,
                        'question_id' => $q->id,
                    ]
                );
            }
        }

        // Calculate percentage score
        $score = $total_questions > 0 ? ($correct_count / $total_questions) * 100 : 0;
        $now = current_time('mysql');

        // Update attempt
        $wpdb->update(
            $attempts_table,
            [
                'score' => $score,
                'correct_answers' => $correct_count,
                'is_submitted' => 1,
                'submitted_at' => $now,
                'end_time' => $now,
            ],
            ['id' => $attempt_id]
        );

        return rest_ensure_response([
            'success' => true,
            'message' => 'Ujian berhasil dikumpulkan',
            'data' => [
                'score' => round($score, 2),
                'correct_answers' => $correct_count,
                'total_questions' => $total_questions,
                'submitted_at' => $now,
            ],
        ]);
    }

    /**
     * GET /attempt/{id} - Get attempt status
     */
    public static function get_attempt($request) {
        global $wpdb;
        
        $attempt_id = $request->get_param('id');

        // Validate token
        $attempt = self::validate_token($request, $attempt_id);
        if (is_wp_error($attempt)) {
            return $attempt;
        }
        
        $answers_table = $wpdb->prefix . 'cbt_answers';

        // Get saved answers
        $answers = $wpdb->get_results($wpdb->prepare(
            "SELECT question_id, student_answer FROM $answers_table WHERE attempt_id = %d",
            $attempt_id
        ));

        $saved_answers = [];
        foreach ($answers as $a) {
            $saved_answers[$a->question_id] = $a->student_answer;
        }

        return rest_ensure_response([
            'success' => true,
            'data' => [
                'id' => intval($attempt->id),
                'exam_id' => intval($attempt->exam_id),
                'student_name' => $attempt->student_name,
                'student_class' => $attempt->student_class,
                'student_nisn' => $attempt->student_nisn,
                'start_time' => $attempt->start_time,
                'end_time' => $attempt->end_time,
                'score' => $attempt->score ? floatval($attempt->score) : null,
                'correct_answers' => intval($attempt->correct_answers),
                'total_questions' => intval($attempt->total_questions),
                'is_submitted' => (bool) $attempt->is_submitted,
                'submitted_at' => $attempt->submitted_at,
                'saved_answers' => $saved_answers,
            ],
        ]);
    }
}