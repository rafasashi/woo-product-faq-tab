;(function($){

	$(document).ready(function(){
		
		if( $(".wfaq-add-input-group").length ){
			
			var tinymceFaqSettings = {
				selector: '.wfaq-rich-text',
				height: 150,
				theme: 'modern',
				menubar: '',
				statusbar: false,
				branding: false,
				plugins: 'link image media wordpress wpeditimage lists',
				toolbar1: 'formatselect | bold italic strikethrough forecolor backcolor | link media | alignleft aligncenter alignright alignjustify  | numlist bullist outdent indent  | removeformat',
				image_advtab: false,
				media_advtab: false,
				image_dimensions: false,
				media_dimensions: false
			};			
			
			if( $('.wfaq-rich-text').length && typeof tinymce != 'undefined' ){
				
				tinymce.init(tinymceFaqSettings);
			}
			
			if( $( ".sortable .ui-sortable" ).length ){
				
				$( ".sortable .ui-sortable" ).sortable({
					
					placeholder	: "ui-state-highlight",
					items		: "li:not(.ui-state-disabled)",
					start: function (e, ui) {
						
						if( $('.wfaq-rich-text').length && typeof tinymce != 'undefined' ){
							
							$(ui.item).find('.wfaq-rich-text').each(function () {
								tinymce.execCommand('mceRemoveEditor', false, $(this).attr('id'));
							});
						}
					},
					stop: function (e, ui) {
						
						if( $('.wfaq-rich-text').length && typeof tinymce != 'undefined' ){
							
							$(ui.item).find('.wfaq-rich-text').each(function () {
								tinymce.execCommand('mceAddEditor', true, $(this).attr('id'));
							});
						}
					}
				});
				
				$( ".sortable .ui-sortable li" ).disableSelection();
			}
			
			//input group add row

			$(".wfaq-add-input-group").on('click', function(e){
				
				e.preventDefault();

				//var clone = $(".input-group-row").eq(0).clone().removeClass('ui-state-disabled');
				
				var target = "." + $(this).data("target");
				
				var clone = $(target).eq(0).clone().removeClass('ui-state-disabled');
				
				clone.css('display','inline-block');

				clone.find('textarea').addClass('wfaq-rich-text').uniqueId();
				
				//clone.append('<a class="remove-input-group" href="#">remove</a>');

				$('<a class="remove-input-group" href="#">remove</a>').insertAfter(clone.find('input'));

				$(this).next(".input-group").append(clone);
				
				if( $('.wfaq-rich-text').length && typeof tinymce != 'undefined' ){
					
					$('<div class="wp-media-buttons" style="display: contents;"><button type="button" class="button insert-media add_media" data-editor="' + clone.find('textarea').attr('id') + '"><span class="wp-media-buttons-icon"></span> Add Media</button></div>').insertBefore(clone.find('textarea'));
					
					tinymce.init(tinymceFaqSettings);
				}
				
			});
			
			$(".input-group").on('click', ".remove-input-group", function(e){

				e.preventDefault();
				$(this).closest('.input-group-row').remove();
			});
		}
	});
		
})(jQuery);