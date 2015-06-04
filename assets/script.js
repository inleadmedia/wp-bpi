jQuery(function ($) {
	var $pushToAeButton = $('#push-to-ae');
	$pushToAeButton.click(function () {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: 'post_id=' + $pushToAeButton.data('post-id') + '&action=check_push_to_ae',
			success: function (data) {
				if (!data.state) {
					$popupFunction(data.message, 'Error :(');
					return;
				}
				var $preparingPopup;
				$preparingPopup = $popupFunction(data.html, 'Preparing to syndicate', {
					"Push": function () {
						var $options = {
							'post_id': $pushToAeButton.data('post-id'),
							'action' : 'push_to_ae',
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
								$pushToAeButton.parents('.inside').html(data.text);
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

	$('.pull_from_ae').click(function () {
		var $aeNodePullButton = $(this);
		$.ajax({
			url: ajaxurl,
			type: 'GET',
			dataType: 'json',
			data: 'ae-node-id=' + $aeNodePullButton.data('node-id') + '&action=check_pull_from_ae',
			success: function (data) {
				if (!data.state) {
					$popupFunction(data.message, 'Error :(');
					return;
				}
				var $preparingPopup;
				$preparingPopup = $popupFunction(data.html, 'Preparing to syndicate', {
					"Preview": function()
					{
						$preparingPopup.html('<h2>'+data.title+'</h2>'+data.body+'<p align="center"><button type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only ui-state-hover ui-state-active" role="button" id="close_preview"><span class="ui-button-text">Close</span></button>');
						$preparingPopup.find('#close_preview').click(function(){
							$preparingPopup.html(data.html)
						});
					},
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
								'ae-node-id': $aeNodePullButton.data('node-id'),
								'action': 'pull_from_ae',
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
				}, 700);
			}
		});
	});

	$popupFunction = function ($content, $title, $buttons, $minWidth) {
		$minWidth = $minWidth == null ? 200 : $minWidth;
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
			'minWidth': $minWidth,
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
