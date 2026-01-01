<?php
if(!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$attemps_table = $wpdb->prefix . 'cbt_attemps';

//Get all exams
$exams = get_posts([
    'post_type' => 'cbt_exam',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
]);

//Get selected exam
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

//Filter by class
$class_id = isset($_GET['filter_class']) ? intval($_GET['filter_class']) : 0;

//Get attempts for the selected exam
$attempts = [];
$classes = [];
if($exam_id) {
    // Get all classes for this exam
    $classes = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT student_class FROM $attemps_table WHERE exam_id = $d ORDER BY student_class", $exam_id
    ));

    // Build query
    $sql = 'SELECT * FROM $attemps_table WHERE exam_id = %d';
    $params = [$exam_id];

    if ($filter_class) {
        $sql .= ' AND student_class = %s';
        $params[] = $filter_class;
    }

    $sql .= ' ORDER BY student_class, student_name';

    $attempts = $wpdb->get_results($wpdb->prepare($sql, $params));
}

//Calculate statistics
   