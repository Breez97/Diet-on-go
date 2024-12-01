jQuery(document).ready(function($) {
    var mediaUploader;
    $('#upload_image_button').on('click', function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media({
            title: 'Выберите изображение',
            button: {
                text: 'Выбрать изображение'
            },
            multiple: false
        });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#recipe_image').val(attachment.url);
            $('#image_preview')
                .html('<img src="' + attachment.url + '" width="100" alt="Recipe Image" />')
                .show();
        });
        mediaUploader.open();
    });
});