(function ($) {
	function getEditorContent() {
		var content = '';
		try {
			if (window.tinymce && tinymce.activeEditor) {
				content = tinymce.activeEditor.getContent({ format: 'text' }) || '';
			} else {
				content = $('#content').val() || '';
			}
		} catch (e) {
			content = '';
		}
		return content;
	}

	$(document).on('click', '#aicd-generate-summary', function () {
		var $btn = $(this);
		var postId = $('#aicd-post-id').val();
		var nonce = $('#aicd-nonce').val();
		var $field = $('#aicd-summary-field');
		$btn.prop('disabled', true).text('Generating…');
		$.ajax({
			url: (window.AICD && AICD.ajaxUrl) || ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'ai_community_discussions_generate_summary',
				postId: postId,
				nonce: nonce,
				content: getEditorContent()
			}
		})
		.done(function (resp) {
			if (resp && resp.success && resp.data && resp.data.summary) {
				$field.val(resp.data.summary);
			} else if (resp && resp.data && resp.data.message) {
				alert(resp.data.message);
			} else {
				alert('Failed to generate summary.');
			}
		})
		.fail(function (e) {
			alert(e.responseJSON);
		})
		.always(function () {
			$btn.prop('disabled', false).text('Generate Summary');
		});
	});
})(jQuery);


