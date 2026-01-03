<?php
if(!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$attempts_table = $wpdb->prefix . 'cbt_attempts';

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
$filter_class = isset($_GET['filter_class']) ? sanitize_text_field($_GET['filter_class']) : '';

//Get attempts for the selected exam
$attempts = [];
$classes = [];
if($exam_id) {
    // Get all classes for this exam
    $classes = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT student_class FROM $attempts_table WHERE exam_id = %d ORDER BY student_class", $exam_id
    ));

    // Build query
    $sql = "SELECT * FROM $attempts_table WHERE exam_id = %d";
    $params = [$exam_id];

    if ($filter_class) {
        $sql .= ' AND student_class = %s';
        $params[] = $filter_class;
    }

    $sql .= ' ORDER BY student_class, student_name';

    $attempts = $wpdb->get_results($wpdb->prepare($sql, $params));
}

//Calculate statistics
$stats = [
    'total' => count($attempts),
    'submitted' => 0,
    'avg_score' => 0,
    'highest' => 0,
    'lowest' => 100,
];

if(!empty($attempts)) {
    $total_score = 0;
    $scored_count = 0;

    foreach ($attempts as $attempt) {
        if ($attempt->is_submitted) {
            $stats['submitted']++;
        } 

        if($attempt->score !== null) {
            $total_score += $attempt->score;
            $scored_count++;

            if($attempt->score > $stats['highest']) {
                $stats['highest'] = $attempt->score;
            }

            if($attempt->score < $stats['lowest']) {
                $stats['lowest'] = $attempt->score;
            }
        }
    }
    
    if ($scored_count > 0) {
        $stats['avg_score'] = round($total_score / $scored_count, 2);
    }
    
    if ($stats['lowest'] == 100 && $scored_count == 0) {
        $stats['lowest'] = 0;
    }
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Hasil Ujian</h1>
    <hr class="wp-header-end">

    <!-- EXAM SELECTIOR -->
     <form method="get" style="margin: 20px 0; display: flex; gap: 10px; align-items: center;">
        <input type="hidden" name="post_type" value="cbt_exam">
        <input type="hidden" name="page" value="cbt-results">

        <label for="exam_id"><strong>Pilih Ujian:</strong></label>
        <select name="exam_id" id="exam_id" onchange="this.form.submit()">
            <option value="">-- Pilih Ujian --</option>
            <?php
            foreach ($exams as $exam): ?>
            <option value="<?php echo $exam->ID; ?>" <?php selected($exam_id, $exam->ID); ?>>
                <?php echo esc_html($exam->post_title); ?>
            </option>
            <?php endforeach; ?>
        </select>

        <?php if($exam_id && !empty($classes)): ?>
            <label for="filter_class"><strong>Pilih Kelas:</strong></label>
            <select name="filter_class" id="filter_class" onchange="this.form.submit()">
                <option value="">-- Semua Kelas --</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?php echo esc_attr($class); ?>" <?php selected($filter_class, $class); ?>>
                        <?php echo esc_html($class); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
     </form>

    <?php if ($exam_id): ?>
        <div style="display: flex; gap: 20px; margin-bottom:20px;">
            <div style="background: #fff; padding: 15px 25px; border-left: 4px solid #0073aa;" >
                <strong> Total Perserta </strong> <br>
                <span style="font-size: 24px;" ><?php echo $stats['total']; ?></span>
            </div>

            <div style="background: #fff; padding: 15px 25px; border-left: 4px solid #46b450;" >
                <strong> Peserta Mengumpulkan </strong> <br>
                <span style="font-size: 24px;" ><?php echo $stats['submitted']; ?></span>
            </div>
            
            <div style="background: #fff; padding: 15px 25px; border-left: 4px solid #f0ad4e;" >
                <strong> Rata-rata Nilai </strong> <br>
                <span style="font-size: 24px;" ><?php echo $stats['avg_score']; ?></span>
            </div>

            <div style="background: #fff; padding: 15px 25px; border-left: 4px solid #5cb85c;" >
                <strong> Nilai Tertinggi </strong> <br>
                <span style="font-size: 24px;" ><?php echo $stats['highest']; ?></span>
            </div>

            <div style="background: #fff; padding: 15px 25px; border-left: 4px solid #d9534f;">
                <strong> Nilai Terendah </strong> <br>
                <span style="font-size: 24px;" ><?php echo $stats['lowest']; ?></span>
            </div>
    </div>

  <!-- Export Button -->
     <?php if(!empty($attempts)): ?>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-bottom: 20px;">
            <input type="hidden" name="action" value="cbt_export_results">
            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
            <?php wp_nonce_field('cbt_export_results'); ?>
            <button type="submit" class="button button-primary">
                <span class="dashicons dashicons-download" style="vertical-align: middle;" ></span>
                Ekspor Hasil ke (CSV)
            </button>
        </form>
     <?php endif; ?>


     <!-- Results Table -->
      <?php if(empty($attempts)): ?>
                <p>Belum Ada Perserta Yang Mengikuti Ujian Ini.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px">No</th>
                            <th style="width: 120px" >NISN</th>
                            <th> Nama Siswa </th>
                            <th style="width: 100px" >Kelas</th>
                            <th style="width: 80px" >Nilai</th>
                            <th style="width: 100px">Benar/Total</th>
                            <th style="width: 150px">Waktu Mulai</th>
                            <th style="width: 150px">Waktu Selesai</th>
                            <th style="width: 100px">Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($attempts as $index => $attempt): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo esc_html($attempt->student_nisn); ?></td>
                            <td><?php echo esc_html($attempt->student_name); ?></td>
                            <td><?php echo esc_html($attempt->student_class); ?></td>
                            <td>
                                <?php if ($attempt->score !== null): ?>
                                    <strong><?php echo number_format($attempt->score, 2); ?></strong>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td> <?php echo $attempt->correct_answers;?> / <?php echo $attempt->total_questions; ?></td>
                            <td> <?php echo date('d/m/Y H:i:s', strtotime($attempt->start_time)); ?></td>
                                <td>
                                    <?php if ($attempt->submitted_at): ?>
                                        <?php echo date('d/m/Y H:i:s', strtotime($attempt->submitted_at)); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($attempt->is_submitted): ?>
                                        <span style="color: #46b450;">✓ Selesai</span>
                                    <?php else: ?>
                                        <span style="color: #dc3232;">⏳ Berlangsung</span>
                                    <?php endif; ?>
                                </td>
                        </tr>
                            <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php else: ?>
                    <p>Silakan pilih ujian terlebih dahulu untuk melihat hasil.</p>
        <?php endif; ?>
    </div>
   