<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$questions_table = $wpdb->prefix . 'cbt_questions';

// Search filter
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get all Exams
$exam_args = [
    'post_type' => 'cbt_exam',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
];

if ($search) {
    $exam_args['s'] = $search;
}

$exams = get_posts($exam_args);

// Get selected exam 
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// Get questions for the selected exam
$questions = [];
$selected_exam = null;

if ($exam_id) {
    $selected_exam = get_post($exam_id);
    $questions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $questions_table WHERE exam_id = %d ORDER BY question_order ASC", $exam_id
    ));
}

// Messages
$message = isset($_GET['message']) ? $_GET['message'] : '';

// Get question count for each exam
function get_question_count($exam_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'cbt_questions';
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE exam_id = %d", $exam_id
    ));
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Kelola Soal</h1>
    
    <?php if ($exam_id && $selected_exam): ?>
        <a href="<?php echo admin_url('edit.php?post_type=cbt_exam&page=cbt-questions'); ?>" class="page-title-action">← Kembali ke Daftar Ujian</a>
        <a href="<?php echo admin_url('edit.php?post_type=cbt_exam&page=cbt-questions&action=add&exam_id=' . $exam_id); ?>" class="page-title-action">Tambah Soal</a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php if ($message === 'added'): ?>
        <div class="notice notice-success is-dismissible"><p>Soal berhasil ditambahkan!</p></div>
    <?php elseif ($message === 'updated'): ?>
        <div class="notice notice-success is-dismissible"><p>Soal berhasil diperbarui!</p></div>
    <?php elseif ($message === 'deleted'): ?>
        <div class="notice notice-success is-dismissible"><p>Soal berhasil dihapus!</p></div>
    <?php elseif ($message === 'error'): ?>
        <div class="notice notice-error is-dismissible"><p>Terjadi kesalahan. Silakan coba lagi.</p></div>
    <?php endif; ?>

    <?php if (!$exam_id): ?>
        <!-- EXAM LIST VIEW -->
        
        <!-- Search Form -->
        <form method="get" style="margin: 20px 0;">
            <input type="hidden" name="post_type" value="cbt_exam">
            <input type="hidden" name="page" value="cbt-questions">
            
            <p class="search-box">
                <label class="screen-reader-text" for="exam-search-input">Cari Ujian:</label>
                <input type="search" id="exam-search-input" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Cari ujian...">
                <input type="submit" id="search-submit" class="button" value="Cari">
                <?php if ($search): ?>
                    <a href="<?php echo admin_url('edit.php?post_type=cbt_exam&page=cbt-questions'); ?>" class="button">Reset</a>
                <?php endif; ?>
            </p>
        </form>

        <?php if (empty($exams)): ?>
            <p>
                <?php if ($search): ?>
                    Tidak ada ujian yang ditemukan untuk "<?php echo esc_html($search); ?>".
                <?php else: ?>
                    Belum ada ujian. <a href="<?php echo admin_url('post-new.php?post_type=cbt_exam'); ?>">Buat ujian baru</a>.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;">No.</th>
                        <th>Nama Ujian</th>
                        <th style="width: 120px;">Kelas</th>
                        <th style="width: 100px;">Jumlah Soal</th>
                        <th style="width: 150px;">Tanggal Mulai</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exams as $index => $exam): 
                        $question_count = get_question_count($exam->ID);
                        $exam_class = get_field('exam_class', $exam->ID);
                        $start_time = get_field('exam_start_time', $exam->ID);
                        $is_visible = get_field('exam_visible', $exam->ID);
                    ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <strong>
                                    <a href="<?php echo admin_url('edit.php?post_type=cbt_exam&page=cbt-questions&exam_id=' . $exam->ID); ?>">
                                        <?php echo esc_html($exam->post_title); ?>
                                    </a>
                                </strong>
                            </td>
                            <td><?php echo esc_html($exam_class ?: '-'); ?></td>
                            <td>
                                <span class="<?php echo $question_count > 0 ? 'dashicons dashicons-yes' : 'dashicons dashicons-warning'; ?>" style="color: <?php echo $question_count > 0 ? '#46b450' : '#dc3232'; ?>;"></span>
                                <?php echo $question_count; ?> soal
                            </td>
                            <td>
                                <?php 
                                if ($start_time) {
                                    echo date('d/m/Y H:i', strtotime($start_time));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($is_visible): ?>
                                    <span style="color: #46b450;">● Aktif</span>
                                <?php else: ?>
                                    <span style="color: #999;">● Draft</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('edit.php?post_type=cbt_exam&page=cbt-questions&exam_id=' . $exam->ID); ?>" class="button button-small button-primary">Kelola Soal</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p style="margin-top: 15px;">
                <strong>Total Ujian:</strong> <?php echo count($exams); ?>
            </p>
        <?php endif; ?>

    <?php else: ?>
        <!-- QUESTIONS VIEW FOR SELECTED EXAM -->
        
        <h2 style="margin-top: 20px;">
            Soal untuk: <?php echo esc_html($selected_exam->post_title); ?>
        </h2>

        <?php if (empty($questions)): ?>
            <p>Belum ada soal untuk ujian ini. <a href="<?php echo admin_url('edit.php?post_type=cbt_exam&page=cbt-questions&action=add&exam_id=' . $exam_id); ?>">Tambah soal pertama</a>.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;">No.</th>
                        <th>Soal</th>
                        <th style="width: 80px;">Gambar</th>
                        <th style="width: 100px;">Jawaban</th>
                        <th style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $index => $question): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo esc_html(wp_trim_words($question->question_text, 20)); ?></td>
                            <td>
                                <?php if ($question->image_url): ?>
                                    <img src="<?php echo esc_url($question->image_url); ?>" style="max-width: 60px; max-height: 40px;">
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo esc_html(strtoupper($question->correct_answer)); ?></strong></td>
                            <td>
                                <a href="<?php echo admin_url('edit.php?post_type=cbt_exam&page=cbt-questions&action=edit&question_id=' . $question->id . '&exam_id=' . $exam_id); ?>" class="button button-small">Edit</a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=cbt_delete_question&question_id=' . $question->id . '&exam_id=' . $exam_id), 'delete_question_' . $question->id); ?>" class="button button-small button-link-delete" onclick="return confirm('Yakin ingin menghapus soal ini?');">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p style="margin-top: 15px;">
                <strong>Total Soal:</strong> <?php echo count($questions); ?>
            </p>
        <?php endif; ?>
        
    <?php endif; ?>
</div>