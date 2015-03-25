/**
 * Created by keriat on 3/25/15.
 */
jQuery(function($){
	var $pushToBpiButton = $('#push-to-bpi');
	var pushToBpi = function () {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: 'post_id=' + $pushToBpiButton.data('post-id') + '&action=push_to_bpi',
			success: function (data) {
				if (!data.state) {
					alert(data.message);
					return;
				}
				$pushToBpiButton.parent().html(' '+data.text);
			}
		});
	};
	$pushToBpiButton.click(function(){
		pushToBpi();
	})
});