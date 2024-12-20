jQuery(document).ready(function($) {
  var mediaUploader;


  $('#product_image_button').click(function(e) {
      e.preventDefault();

      if (mediaUploader) {
          mediaUploader.open();
          return;
      }

      mediaUploader = wp.media.frames.file_frame = wp.media({
          title: '',
          button: {
              text: "Use this image"
          },
          multiple: false,  
          library: {
              type: 'image'  
          }
      });

 
      mediaUploader.on('select', function() {
          var attachment = mediaUploader.state().get('selection').first().toJSON();
          $('#product_image').val(attachment.url); 
      });

      mediaUploader.open(); 
  });
});
