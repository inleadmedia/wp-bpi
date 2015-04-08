jQuery(function ($) {
	var $pushToBpiButton = $('#push-to-bpi');
	$pushToBpiButton.click(function () {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: 'post_id=' + $pushToBpiButton.data('post-id') + '&action=check_push_to_bpi',
			success: function (data) {
				if (!data.state) {
					$popupFunction(data.message, 'Error :(');
					return;
				}
				var $preparingPopup;
				$preparingPopup = $popupFunction(data.html, 'Preparing to syndicate', {
					"Push": function () {
						var $options = {
							'post_id': $pushToBpiButton.data('post-id'),
							'action' : 'push_to_bpi',
							'images': $('#images', $preparingPopup).is(':checked') ? 1 : 0,
							'anonymous': $('#anonymous', $preparingPopup).is(':checked') ? 1 : 0,
							'references': $('#references', $preparingPopup).is(':checked') ? 1 : 0,
							'editable': $('#editable', $preparingPopup).is(':checked') ? 1 : 0,
							'category' : $('#category', $preparingPopup).val(),
							'audience' : $('#audience', $preparingPopup).val()
						};
						$preparingPopup.dialog('close');
						/**
						 * @todo: add some params check
						 */
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							dataType: 'json',
							data: $options,
							success: function (data) {
								if (!data.state) {
									$popupFunction(data.message, 'Error :(');
									return;
								}
								$pushToBpiButton.parents('.inside').html(data.text);
							}
						});
					},
					"Cancel": function () {
						$(this).dialog('close');
					}
				});


				/**/
			}
		});
	});

	$('.pull_from_bpi').click(function () {
		var $bpiNodePullButton = $(this);
		$.ajax({
			url: ajaxurl,
			type: 'GET',
			dataType: 'json',
			data: 'bpi-node-id=' + $bpiNodePullButton.data('node-id') + '&action=check_pull_from_bpi',
			success: function (data) {
				if (!data.state) {
					$popupFunction(data.message, 'Error :(');
					return;
				}
				var $preparingPopup;
				$preparingPopup = $popupFunction(data.html, 'Preparing to syndicate', {
					"Syndicate": function () {
						var $images = [];
						var imageList = $('.selected-images:checked', $preparingPopup);
						if (imageList.length) {
							imageList.each(function () {
								$images.push($(this).val());
							});
						}
						$preparingPopup.dialog('close');
						/**
						 * @todo: add some params check
						 */
						$.ajax({
							url: ajaxurl,
							type: 'GET',
							dataType: 'json',
							data: {
								'bpi-node-id': $bpiNodePullButton.data('node-id'),
								'action': 'pull_from_bpi',
								'images': $images
							},
							success: function (data) {
								if (!data.state) {
									$popupFunction(data.message, 'Error :(');
									return;
								}
								$('.' + data.column_name, '#' + data.id).html(data.field);
								$popupFunction(data.text, 'Success!');
							}
						});
					},
					"Cancel": function () {
						$(this).dialog('close');
					}
				});
			}
		});
	});

	$popupFunction = function ($content, $title, $buttons) {
		var $info = $($content);
		$info.attr('title', $title);
		if (!$buttons) {
			$buttons = {
				'Ok': function () {
					$info.dialog('close');
				}
			}
		}
		$info.dialog({
			'dialogClass': 'wp-dialog',
			'modal': true,
			'autoOpen': false,
			'closeOnEscape': true,
			'buttons': $buttons
		});
		$info.dialog('open');
		return $info;
	}

});
