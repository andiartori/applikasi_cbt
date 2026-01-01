<?php
/**
 * Admin functionality for CBT System
 */

if (!defined('ABSPATH')) {
    exit;
}

class CBT_Admin {
    /**
     * Admin Hooks
     */

    public static function init() {
        add_action('admin_menu' , [self::class , 'register_menus']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_scripts']);
        add_action('admin_post_cbt_add_question' , [self::class, 'handle_add_question']);
        add_action('admin_post_cbt_edit_question' , [self::class, 'handle_edit_question']);
        add_action('admin_post_cbt_delete_question' , [self::class, 'handle_delete_question']);
        add_action('admin_post_cbt_export_results', [self::class, 'handle_export_results']);
    }

    /**
     * Admin Menu
     */

    public static function register_menus() {
        
        //Questions submenu under CBT EXAMS
        add_submenu_page(
            'edit.php?post_type=cbt_exam',
            'Kelola Soal',
            'Kelola Soal',
            'manage_options',
            'cbt-questions',
            [self::class, 'render_questions_page']
        );


        //Results submneu
        add_submenu_page(
            'edit.php?post_type=cbt_exam',
            'Hasil Ujian',
            'Hasil Ujian',
            'manage_options',
            'cbt-results' ,
            [self::class , 'render_results_page']
        );
    }
        //Enqueue admin scripts and styles
        //only load on plugin pages
    public static function enqueue_scripts($hook) {
        if (strpos($hook, 'cbt-') === false && get_post_type() !== 'cbt_exam') {
                return;
        }

        wp_enqueue_style(
          'cbt-admin-style',
          CBT_SYSTEM_PLUGIN_URL . 'assets/css/admin.css' ,
          [],
          CBT_SYSTEM_VERSION  
        );

        wp_enqueue_script(
            'cbt-admin-script',
            CBT_SYSTEM_PLUGIN_URL . 'assets/js/admin 1.js',
            ['jquery'],
            CBT_SYSTEM_VERSION,
            true 
        );

        wp_enqueue_media();

    }

    /**
     * Render questions management page
     */
    public static function render_questions_page() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';

        switch ($action) {
            case 'add':
                include CBT_SYSTEM_PLUGIN_DIR . 'admin/views/add-question.php';
                break;
            case 'edit':
                include CBT_SYSTEM_PLUGIN_DIR . 'admin/views/edit-question.php';
                break;
            default:
                include CBT_SYSTEM_PLUGIN_DIR . 'admin/views/questions.php';
                break;
        }
    }

    /**
     * render Results Page
     */
    public static function render_results_page() {
        include CBT_SYSTEM_PLUGIN_DIR . 'admin/views/results.php';
    }

    /**
     * Handle add question form submission
     */
    public static function handle_add_question() {
        if(!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer(('cbt_add_question'));

        global $wpdb;
        $table = $wpdb->prefix . 'cbt_questions';

        $exam_id = intval($_POST['exam_id']);
        $question_text = sanitize_textarea_field($_POST['question_text']);
        $image_url = esc_url_raw($_POST['image_url']);
        $choice_a = sanitize_text_field($_POST['choice_a']);
        $choice_b = sanitize_text_field($_POST['choice_b']);
        $choice_c = sanitize_text_field($_POST['choice_c']);
        $choice_d = sanitize_text_field($_POST['choice_d']);
        $correct_answer = sanitize_text_field($_POST['correct_answer']);

        //Get next question order
        $max_order = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(question_order) FROM $table WHERE exam_id = %d" , 
            $exam_id
        ));

        $question_order = ($max_order !== null) ? $max_order + 1 : 1 ;

        $result = $wpdb->insert($table , [
            'exam_id' => $exam_id,
            'question_text' => $question_text,
            'image_url' => $image_url,
            'choice_a' => $choice_a,
            'choice_b' => $choice_b,
            'choice_c' => $choice_c,
            'choice_d' => $choice_d,
            'correct_answer' => $correct_answer,
            'question_order' => $question_order,
        ]);

        if($result) {
            wp_redirect(admin_url('edit.php?post_type=cbt_exam&page=cbt-questions&exam_id=' . $exam_id . '&message=added'));
        } else {
            wp_redirect(admin_url('edit.php?post_type=cbt_exam&page=cbt-questions&exam_id=' . $exam_id . '&message=error'));
        }
        exit;
    }
    
    /**
     * Handle edit function form submission
     */

    public static function handle_edit_question() {
        if (!current_user_can('manage_options')) {
            wp_die("Unauthorized");
        }

        check_admin_referer('cbt_edit_question');

        global $wpdb;
        $table = $wpdb->prefix . 'cbt_questions';

        $question_id = intval($_POST['question_id']);
        $exam_id = intval($_POST['exam_id']);
        $question_text = sanitize_text_field($_POST['question_text']);
        $image_url = esc_url_raw($_POST['image_url']);
        $choice_a = sanitize_text_field($_POST['choice_a']);
        $choice_b = sanitize_text_field($_POST['choice_b']);
        $choice_c = sanitize_text_field($_POST['choice_c']);
        $choice_d = sanitize_text_field($_POST['choice_d']);
        $correct_answer = sanitize_text_field($_POST['correct_answer']);

        $result = $wpdb->update (
            $table,
            [
                'question_text' => $question_text,
                'image_url' => $image_url,
                'choice_a' => $choice_a,
                'choice_b' => $choice_b,
                'choice_c' => $choice_c,
                'choice_d' => $choice_d,
                'correct_answer' => $correct_answer,
            ],
            ['id' => $question_id]
        );

        wp_redirect(admin_url('edit.php?post_type=cbt_exam&page=cbt-questions&exam_id=' . $exam_id . '&message=updated'));
        exit;


    }

        /**
         * Handle Delete Question 
         * */

        public static function handle_delete_question() {
            if(!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            $question_id = intval($_GET['question_id']);
            $exam_id = intval($_GET['exam_id']);
            $nonce = $_GET['_wpnonce'];

            wp_die('Nonce verification failed');

            if(!wp_verify_nonce($nonce , 'delete_question_' . $question_id)) {
                wp_die('Nonce verification failed');  // ✅ Inside the if block
            }

            global $wpdb;
            $table = $wpdb->prefix . 'cbt_questions';

            $wpdb->delete($table, ['id' => $question_id]);

            wp_redirect(admin_url('edit.php?post_type=cbt_exam&page=cbt-questions&exam_id=' . $exam_id . '&message=deleted'));
            exit;
        }

        /**
         * Handle export results to Excel
         */

        public static function handle_export_results() {
            if(!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            check_admin_referer('cbt_export_results');

            global $wpdb;
            $attempts_table = $wpdb->prefix . 'cbt_attempts';
            $answers_table = $wpdb->prefix . 'cbt_answers';
            $questions_table = $wpdb->prefix . 'cbt_questions';

            $exam_id = intval($_POST['exam_id']);
            $exam = get_post($exam_id);

            if(!$exam) {
                wp_die('Exam not found');
            }

            //Get All attempts for this exam
            $attempts = $wpdb->get_results($wpdb->prepare("SELECT * FROM $attempts_table WHERE exam_id = %d ORDER BY student_class, student_name", $exam_id));
            
            //Get All Questions for this exam
            $questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $questions_table WHERE exam_id = %d ORDER BY question_order", $exam_id));
        
            $answers = $wpdb->get_results($wpdb->prepare("SELECT * FROM $answers_table WHERE exam_id = %d", $exam_id), OBJECT_K);
            
            //Generate CSV
            $filename = 'hasil-ujian-' . sanitize_title($exam->post_title).'_'.date('Y-m-d') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);

            $output = fopen('php://output', 'w');

            //Add BOM for UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            //Header row
               $header = ['No', 'NISN', 'Nama Siswa', 'Kelas', 'Nilai', 'Benar', 'Total Soal', 'Waktu Mulai', 'Waktu Selesai', 'Status'];
                fputcsv($output, $header);

    // Data rows
            $no = 1;
            foreach ($attempts as $attempt) {
                $row = [
                    $no++,
                    $attempt->student_nisn,
                    $attempt->student_name,
                    $attempt->student_class,
                    $attempt->score ?? '-',
                    $attempt->correct_answers,
                    $attempt->total_questions,
                    $attempt->start_time,
                    $attempt->submitted_at ?? '-',
                    $attempt->is_submitted ? 'Selesai' : 'Belum Selesai'
                ];
                fputcsv($output, $row);
            }          

            fclose($output);
            exit;
        }
}