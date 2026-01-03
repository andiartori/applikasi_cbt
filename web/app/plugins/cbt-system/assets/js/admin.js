jQuery(document).ready(function($) {
    
    // Image upload functionality
    var mediaUploader;
    
    $('#upload_image_button').on('click', function(e) {
        e.preventDefault();
        
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        mediaUploader = wp.media({
            title: 'Pilih Gambar',
            button: {
                text: 'Gunakan Gambar'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#image_url').val(attachment.url);
            $('#image_preview').html('<img src="' + attachment.url + '" style="max-width: 300px;">');
        });
        
        mediaUploader.open();
    });
    
    // Clear image preview if URL is cleared
    $('#image_url').on('change', function() {
        if ($(this).val() === '') {
            $('#image_preview').html('');
        }
    });
    
});