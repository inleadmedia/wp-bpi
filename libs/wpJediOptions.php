<?php
/*~ wpJediOptions.php
.---------------------------------------------------------------------------.
|  Software: wpJediOptions - PHP class for wordpress to create options page |
|   Version: 1.0                                                            |
|   Contact: via github.com support pages                                   |
|      Info: https://github.com/lukelojedi/wp-jedi-options                  |
|   Support: https://github.com/lukelojedi/wp-jedi-options                  |
| ------------------------------------------------------------------------- |
|     Admin: Luca Nardi (project admininistrator)                           |
|   Authors: Luca Nardi                                                     |
|   Founder: Luca Nardi                                                     |
| Copyright (c) 20014, Luca Nardi. All Rights Reserved.                     |
| ------------------------------------------------------------------------- |
|   License: Distributed under the MIT License                              |
|   https://github.com/lukelojedi/wp-jedi-options/blob/master/LICENSE       |
| This program is distributed in the hope that it will be useful - WITHOUT  |
| ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or     |
| FITNESS FOR A PARTICULAR PURPOSE.                                         |
'---------------------------------------------------------------------------'
*/

/**
 * wpJediOptions - HP class for wordpress to create options page
 * NOTE: Requires Wordpress 3.5 or later
 * @package wpJediOptions
 * @author Luca Nardi
 * @copyright 2014 Luca Nardi
 * @version 1.0
 * @license https://github.com/lukelojedi/wp-jedi-options/blob/master/LICENSE MIT License
 */
class wpJediOptions
{
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;
	private $options_tree;
	private $page_options_title;
	private $page_options_note;
	private $page_menu_title;
	private $options_name;
	private $page_slug;
	private $option_group;

	private $is_sub_page = false;
	private $parent_page_slug = false;

	/**
	 * Start up
	 */
	public function __construct($data)
	{
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		add_action( 'admin_enqueue_scripts', Array( $this, 'enqueue_color_picker' ) );

		$this->options_tree = $data['options'];
		$this->page_options_note = $data['page_note'];
		$this->page_options_title = $data['page_title'];
		$this->page_menu_title = $data['page_menu_title'];
		$this->page_slug = $data['page_slug'];
		$this->options_name = $data['options_name'];
		$this->option_group = $data['options_group'];

		$this->is_sub_page = !empty($data['parent_page_slug']);
		$this->parent_page_slug = empty($data['parent_page_slug']) ? '' : $data['parent_page_slug'];
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page()
	{
		if ($this->is_sub_page)
		{
			// This page will be under "Settings"
			add_submenu_page(
				$this->parent_page_slug,
				$this->page_options_title,
				$this->page_menu_title,
				'manage_options',
				$this->page_slug,
				array($this, 'create_admin_page')
			);
		}
		else
		{
			// This page will be under "Settings"
			add_menu_page(
				$this->page_options_title,
				$this->page_menu_title,
				'manage_options',
				$this->page_slug,
				array($this, 'create_admin_page')
			);
		}


	}

	/**
	 * Options page callback
	 */
	public function create_admin_page()
	{
		// Set class property
		$this->options = get_option( $this->options_name );

		add_thickbox();
		wp_enqueue_media();
		echo $this->genereteCSS();
		echo $this->generateJS();
		?>
		<div class="wrap no_move">
			<h2><?php echo $this->page_options_title; ?></h2>
			<?php if ($_GET['settings-updated']) { ?>
				<?php if ($_GET['settings-updated'] == 'true') { ?>
					<div id="setting-error-settings_updated" class="updated settings-error">
						<p><strong><?php _('Changes Saved', 'wp-ae-plugin') ?></strong></p>
					</div>
				<?php } else { ?>
					<div id="setting-error-settings_updated" class="error settings-error">
						<p><strong><?php _('Error while saving', 'wp-ae-plugin') ?></strong></p>
					</div>
				<?php } ?>
			<?php } ?>
			<div class="metabox-holder has-right-sidebar" id="poststuff">
				<form method="post" action="options.php">
					<div class="inner-sidebar" id="side-info-column">

						<!-- Update -->
						<div class="postbox" style="position:fixed;">
							<h3 class="hndle"><span>Actions</span></h3>
							<div class="inside">
								<?php submit_button(); ?>
							</div>
						</div>

						<div id="side-sortables" class="meta-box-sortables"></div>
					</div>
					<div id="post-body">
						<div id="post-body-content">
							<div id="normal-sortables" class="meta-box-sortables">
								<div class="postbox default">

									<div class="inside">
										<?php settings_fields( $this->option_group ); ?>
										<?php do_settings_sections( $this->page_menu_title ); ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>



	<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init()
	{
		register_setting(
			$this->option_group, // Option group
			$this->options_name, // Option name
			array( $this, 'sanitize' ) // Sanitize
		);

		add_settings_section(
			'jedi_section', // ID
			$this->page_options_title, // Title
			array( $this, 'print_section_info' ), // Callback
			$this->page_menu_title // Page
		);

		$ex_options = Array();



		foreach($this->options_tree as $option=>$data) {
			$data['language'] = empty($data['language']) ? '' : $data['language'];
			$ex_options[$data['name'].$data['language']] = '';

			$method = $data['type'].'_callback';

			add_settings_field(
				$data['name'], // ID
				$data['label'], // Title
				array( $this, $method ), // Callback
				$this->page_menu_title, // Page
				'jedi_section', // Section
				$data // data passed
			);



		}

		$this->options = get_option( $this->options_name );



		$this->options = is_array($this->options) ? $this->options : array();
		foreach($this->options as $key=>$opt) {

			if (!array_key_exists($key, $ex_options)) {

				add_settings_field(
					$key, // ID
					'', // Title
					array( $this, 'hidden_callback' ), // Callback
					$this->page_menu_title, // Page
					'jedi_section', // Section
					Array($key=>$opt) // data passed
				);
			}

		}



	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize( $input )
	{

		/* TODO - OPTIONALS */
		$new_input = array();

		return $input;
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info()
	{
		print '<div class="intro">'.$this->page_options_note.'</div>';
	}

	/**
	 * Get the settings option array and print one of its values (TEXT)
	 */
	public function text_callback($data)
	{

		printf(
			'<p>%s</p><input class="text-inpt-1" type="text" name="'.$this->options_name.'['.$data['name'].$data['language'].']" value="%s" />',
			isset( $data["description"] ) ? $data["description"] : '',
			isset( $this->options[$data['name'].$data['language']] ) ? $this->options[$data['name'].$data['language']] : ''
		);
	}

	public function hidden_callback($data)
	{
		$key = array_keys($data);

		?>
		<input type="hidden" name="<?php echo $this->options_name.'['.$key[0].']'; ?>" value="<?php echo $this->options[$key[0]]; ?>" />

	<?php
	}

	/**
	 * Get the settings option array and print one of its values (IMAGES)
	 */
	public function image_callback($data)
	{
		?>
		<div class="box-1">
			<input class="img_url" type="hidden" name="<?php echo $this->options_name.'['.$data['name'].$data['language'].']'; ?>" value="<?php echo esc_url($this->options[$data['name'].$data['language']]); ?>" />
			<input class="img_id" type="hidden" name="<?php echo $this->options_name.'['.$data['name'].$data['language'].'_id]'; ?>" value="<?php echo $this->options[$data['name'].$data['language'].'_id']; ?>" />
			<div class="img_preview" style="min-height: 100px;">
				<p><?php echo $data['description']; ?></p>
				<img class="img_preview_small" src="<?php echo ($this->options[$data['name'].$data['language']] != "") ? esc_url($this->options[$data['name'].$data['language']]) : bloginfo('template_url').'/assets/images/cms/no-image.png'; ?>" />
				<div class="edit-btn">
					<a href="javascript:void(0);" class="upload_image_button"><?php echo ($this->options[$data['name'].$data['language']] != "") ? 'Edit' : 'Upload'; ?></a> <?php if ($this->options[$data['name'].$data['language']] != "") { ?>- <a href="javascript:void(0);" class="remove_image_button">Rimuovi immagine</a><?php } ?>
				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * Get the settings option array and print one of its values (IMAGES)
	 */
	public function select_callback($data)
	{
		?>

		<p>
			<?php echo $data['description']; ?>
		</p>
		<select name="<?php echo $this->options_name.'['.$data['name'].$data['language'].']'; ?>">
			<?php foreach ($data['options'] as $key=>$opt) { ?>
				<option value="<?php echo $key; ?>" <?php echo ($key == $this->options[$data['name'].$data['language']]) ? "selected" : ""; ?>><?php echo $opt; ?></option>
			<?php } ?>
		</select>
	<?php
	}

	/**
	 * Get the settings option array and print one of its values (RICHTEXT)
	 */
	public function richtext_callback($data)
	{
		?>

		<p>
			<?php echo $data['description']; ?>
		</p>

		<?php
		$id = $data['name'];
		$content = $this->options[$data['name'].$data['language']];
		$ed_args = array(
			"textarea_rows" => 5,
			"editor_class" => "jedi_richtext",
			"media_buttons" => false,
			"textarea_name" => $this->options_name.'['.$data['name'].$data['language'].']');

		wp_editor($content, $id, $ed_args);


		?>

	<?php
	}

	/**
	 * Get the settings option array and print one of its values (CHECKBOX)
	 */
	public function checkbox_callback($data)
	{
		?>

		<p>
			<?php echo $data['description']; ?>
		</p>

		<input type="checkbox" value="on" <?php if ($this->options[$data['name'].$data['language']] == 'on') { ?>checked=""<?php } ?> name="<?php echo $this->options_name.'['.$data['name'].$data['language'].']'; ?>"> <?php echo $data['label']; ?>

	<?php
	}





	/**
	 * Get the settings option array and print one of its values (MAP)
	 */
	public function map_callback($data)
	{



		?>

        <p>
            <?php echo $data['description']; ?>
        </p>
        </p>


        <input type="hidden" id="jedi_location_<?php echo $data['name'].$data['language']; ?>" name="<?php echo $this->options_name.'['.$data['name'].$data['language'].']'; ?>" value="<?php echo $this->options[$data['name'].$data['language']]; ?>" />
        <div style="width: 100%;height:300px;position: relative;">
                <?php
		$coords = $this->options[$data['name'].$data['language']];


		if ($coords == "") {
			/* Coordinate di default di meetodo */
			$lat = '45.490580';
			$lng = '12.247377';
		} else {

			$coords = str_replace("(","",$coords);
			$coords = str_replace(")","",$coords);
			$coords = str_replace(" ","",$coords);


			$coords = explode(",",$coords);

			$lat = $coords[0];
			$lng = $coords[1];
		}

		?>
                <style type="text/css">
                        #addmarker-map-<?php echo $data['name'].$data['language']; ?> {
                                width: 100%;
                                height: 100%;
                                float: left;
                                display: inline-block;
                        }

                        #panel-<?php echo $data['name'].$data['language']; ?> {
                            position: absolute;
                            top: 5px;
                            left: 50%;
                            margin-left: -180px;
                            z-index: 5;
                            background-color: #fff;
                            padding: 5px;
                            border: 1px solid #999;
                        }
                </style>

                <script type="text/javascript">

                function codeAddress_<?php echo $data['name'].$data['language']; ?>() {
                    var address = document.getElementById('address-<?php echo $data['name'].$data['language']; ?>').value;
                    geocoder_<?php echo $data['name'].$data['language']; ?>.geocode( { 'address': address}, function(results, status) {
                      if (status == google.maps.GeocoderStatus.OK) {

                        map_<?php echo $data['name'].$data['language']; ?>.setCenter(results[0].geometry.location);
                        marker_<?php echo $data['name'].$data['language']; ?>.setPosition(results[0].geometry.location);
                        var posToSave = marker_<?php echo $data['name'].$data['language']; ?>.getPosition();
                        jQuery('#jedi_location_<?php echo $data['name'].$data['language']; ?>').val(posToSave);
                      } else {
                        alert('Geocode was not successful for the following reason: ' + status);
                      }
                    });
                  }

                var geocoder_<?php echo $data['name'].$data['language']; ?>;
                var map_<?php echo $data['name'].$data['language']; ?>;
                var marker_<?php echo $data['name'].$data['language']; ?>;
                jQuery(document).ready(function(){


                        geocoder_<?php echo $data['name'].$data['language']; ?> = new google.maps.Geocoder();

                        // fornisce latitudine e longitudine
                        var latlng = new google.maps.LatLng(<?php echo $lat; ?>, <?php echo $lng; ?>);

                        // imposta le opzioni di visualizzazione
                        var options = { zoom: 12,
                        center: latlng,
                        mapTypeId: google.maps.MapTypeId.ROADMAP
                        };

                        // crea l'oggetto mappa
                        map_<?php echo $data['name'].$data['language']; ?> = new google.maps.Map(document.getElementById('addmarker-map-<?php echo $data['name'].$data['language']; ?>'), options);


                        marker_<?php echo $data['name'].$data['language']; ?> = new google.maps.Marker({ position: latlng,
                        map: map_<?php echo $data['name'].$data['language']; ?>,
                        draggable: true,
                        title: 'Marker dell\'oggetto' });


                        google.maps.event.addListener(marker_<?php echo $data['name'].$data['language']; ?>, 'dragend', function() {
                                var posToSave = marker_<?php echo $data['name'].$data['language']; ?>.getPosition();


                                if (jQuery('#jedi_location_<?php echo $data['name'].$data['language']; ?>').length > 0)
                                        jQuery('#jedi_location_<?php echo $data['name'].$data['language']; ?>').val(posToSave);

                        });

                })

                </script>


        <div id="addmarker-map-<?php echo $data['name'].$data['language']; ?>"></div>
        <div id="panel-<?php echo $data['name'].$data['language']; ?>">
            <input id="address-<?php echo $data['name'].$data['language']; ?>" type="textbox" value="Venezia, Italia">
            <input type="button" value="Geocode" onclick="codeAddress_<?php echo $data['name'].$data['language']; ?>()">
        </div>
        </div>

    <?php
	}

	/**
	 * Get the settings option array and print one of its values (IMAGES)
	 */
	public function colorpicker_callback($data)
	{
		?>
		<p>
			<?php echo $data["description"]; ?>
		</p>
		<input type="text" value="<?php echo $this->options[$data['name'].$data['language']]; ?>" name="<?php echo $this->options_name.'['.$data['name'].$data['language'].']'; ?>" class="my-color-field" />
	<?php
	}


	private function genereteCSS() {
		ob_start(); ?>
		<style type="text/css">

			.form-table {
				margin: 0 12px;
			}

			.intro {
				display: block;
				margin: 20px 0;
				padding: 0 12px;
			}

			.field {
				padding: 15px 10px;
				border-top: #e8e8e8 solid 1px;
			}

			.text-inpt-1 {

				width: 50%;

			}

			.img_preview_small {
				max-width:200px;
				display:inline-block;
				vertical-align: middle;
			}

			.edit-btn {
				display:inline-block;
				vertical-align: middle;
				margin-left: 5px;
			}


			.field p.label {
				font-size: 12px;
				line-height: 1.5em;
				margin: 0 0 1em;
				padding: 0;
				color: #666666;
				text-shadow: 0 1px 0 #FFFFFF;
			}

			.field p.label label {
				color: #333333;
				font-size: 13px;
				line-height: 1.5em;
				font-weight: bold;
				padding: 0;
				margin: 0 0 3px;
				display: block;
				vertical-align: text-bottom;
			}

		</style>
		<?php
		$out = ob_get_contents();
		ob_end_clean();

		return $out;

	}


	private function generateJS() {

		ob_start(); ?>
		<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
		<script type="text/javascript">
			jQuery(document).ready(function($) {

				var file_frame;

				jQuery('.upload_image_button').live('click', function( event ){

					target_1 = $(this).parent().parent().siblings('.img_url');
					target_2 = $(this).parent().parent().siblings('.img_id');
					target_3 = $(this).parent().siblings('.img_preview_small');

					event.preventDefault();

					// If the media frame already exists, reopen it.
					if ( file_frame ) {
						file_frame.open();
						return;
					}

					// Create the media frame.
					file_frame = wp.media.frames.file_frame = wp.media({
						title: jQuery( this ).data( 'uploader_title' ),
						button: {
							text: jQuery( this ).data( 'uploader_button_text' ),
						},
						multiple: false  // Set to true to allow multiple files to be selected
					});

					// When an image is selected, run a callback.
					file_frame.on( 'select', function() {
						// We set multiple to false so only get one image from the uploader
						attachment = file_frame.state().get('selection').first().toJSON();

						// Do something with attachment.id and/or attachment.url here
						target_1.val(attachment.url);
						target_2.val(attachment.id);
						target_3.attr('src',attachment.url);

					});

					// Finally, open the modal
					file_frame.open();
				});


				jQuery('.remove_image_button').live('click', function( event ){

					target_1 = $(this).parent().parent().siblings('.img_url');
					target_2 = $(this).parent().parent().siblings('.img_id');
					target_3 = $(this).parent().siblings('.img_preview_small');

					target_1.val('');
					target_2.val('');
					target_3.attr('src','<?php bloginfo('template_url'); echo '/assets/images/cms/no-image.png'; ?>');


				});



				jQuery(document).ready(function($){
					$('.my-color-field').wpColorPicker();
				});




			});
		</script>
		<?php
		$out = ob_get_contents();
		ob_end_clean();

		return $out;


	}

	public function enqueue_color_picker( $hook_suffix ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		//wp_enqueue_script( 'my-script-handle', plugins_url('my-script.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
	}

	public static function get_option($options_name,$option) {

		$options = get_option($options_name);

		return $options[$option];

	}

}
