<?php
if (!defined('ABSPATH')) exit;

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if (!$exam_id) {
    wp_die('Exam ID is required');
}

$exam = get_post($exam_id);
if (!$exam || $exam->post_type !== 'cbt_exam') {
    wp_die('Invalid exam');
}
?>

<div class="wrap">
    <h1>Tambah Soal - <?php echo esc_html($exam->post_title); ?></h1>
    
    <a href="<?php echo admin_url('edit.php?post_type=cbt_exam&page=cbt-questions&exam_id=' . $exam_id); ?>" class="page-title-action">← Kembali ke Daftar Soal</a>
    
    <hr class="wp-header-end">

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="max-width: 800px; margin-top: 20px;">
        <input type="hidden" name="action" value="cbt_add_question">
        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
        <?php wp_nonce_field('cbt_add_question'); ?>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="question_text">Pertanyaan <span class="required">*</span></label></th>
                <td>
                    <textarea name="question_text" id="question_text" rows="4" class="large-text" required></textarea>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="image_url">Gambar (opsional)</label></th>
                <td>
                    <input type="url" name="image_url" id="image_url" class="regular-text" placeholder="https://...">
                    <button type="button" class="button" id="upload_image_button">Pilih Gambar</button>
                    <p class="description">Upload atau pilih gambar dari media library</p>
                    <div id="image_preview" style="margin-top: 10px;"></div>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="choice_a">Pilihan A <span class="required">*</span></label></th>
                <td>
                    <input type="text" name="choice_a" id="choice_a" class="large-text" required>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="choice_b">Pilihan B <span class="required">*</span></label></th>
                <td>
                    <input type="text" name="choice_b" id="choice_b" class="large-text" required>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="choice_c">Pilihan C <span class="required">*</span></label></th>
                <td>
                    <input type="text" name="choice_c" id="choice_c" class="large-text" required>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="choice_d">Pilihan D <span class="required">*</span></label></th>
                <td>
                    <input type="text" name="choice_d" id="choice_d" class="large-text" required>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="correct_answer">Jawaban Benar <span class="required">*</span></label></th>
                <td>
                    <select name="correct_answer" id="correct_answer" required>
                        <option value="">-- Pilih Jawaban --</option>
                        <option value="a">A</option>
                        <option value="b">B</option>
                        <option value="c">C</option>
                        <option value="d">D</option>
                    </select>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary">Simpan Soal</button>
            <a href="<?php echo admin_url('edit.php?post_type=cbt_exam&page=cbt-questions&exam_id=' . $exam_id); ?>" class="button">Batal</a>
        </p>
    </form>
</div>

<style>
.required { color: #dc3232; }
</style>