<?php
/*
Plugin Name: Simple Flickr Display
Plugin URI: http://davidlaietta.com/plugins/
Description: Installs a widget that you can place onto any sidebar and allows you to pull recent photos from Flickr. Light on settings/options, you can enter your username and the number of photos that you would like displayed.
Version: 1.0
Author: David Laietta
Author URI: http://davidlaietta.com/
Author Email: plugins@davidlaietta.com
Network: false
License: GPL
License URI: http://www.gnu.org/licenses/gpl.html

Copyright 2013 (plugins@davidlaietta.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class OBM_Simple_Flickr_Display extends WP_Widget {

	/*--------------------------------------------------*/
	/* Constructor
	/*--------------------------------------------------*/
	
	/**
	 * Specifies the classname and description, instantiates the widget, 
	 * loads localization files, and includes necessary stylesheets and JavaScript.
	 */
	public function __construct() {
			
		// Hooks fired when the Widget is activated and deactivated
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		
		parent::__construct(
			'simple-flickr-display',
			'Simple Flickr Display',
			array(
				'classname'		=>	'simple-flickr-display-class',
				'description'	=>	'Display Recent Photos from your Flickr'
			)
		);
	
		// Register site styles and scripts
		if ( is_active_widget( false, false, 'simple-flickr-display', true ) ) {
			wp_register_style( 'simple-flickr-display-widget-styles', plugins_url( 'simple-flickr-display/css/simple-flickr-display.css' ) );
		}
		
	} // end constructor

	/*--------------------------------------------------*/
	/* Widget API Functions
	/*--------------------------------------------------*/
	
	/**
	 * Outputs the content of the widget.
	 *
	 * @param	array	args		The array of form elements
	 * @param	array	instance	The current instance of the widget
	 */
	public function widget( $args, $instance ) {
	
		extract( $args, EXTR_SKIP );
		
		$title = apply_filters('widget_title', $instance['title']);
		$screen_name = $instance['screen_name'];
		$number = $instance['number'];
		
		echo $before_widget;
        
		// Frontend View of Flickr Display

		wp_enqueue_style( 'simple-flickr-display-widget-styles' );
		
		if($title) {
			echo $before_title.$title.$after_title;
		}
		
		if($screen_name && $number) {
			// Plugin Developer API Key
			$api_key = '692b5728131fcd77dfdec0b6f6bc0cad';
			
			// Retrieve User
			$person = wp_remote_get('http://api.flickr.com/services/rest/?method=flickr.people.findByUsername&api_key='.$api_key.'&username='.$screen_name.'&format=json');
			$person = trim($person['body'], 'jsonFlickrApi()');
			$person = json_decode($person);
		
			if($person->user->id) {
				// Retrieve Photo URL
				$photos_url = wp_remote_get('http://api.flickr.com/services/rest/?method=flickr.urls.getUserPhotos&api_key='.$api_key.'&user_id='.$person->user->id.'&format=json');
				$photos_url = trim($photos_url['body'], 'jsonFlickrApi()');
				$photos_url = json_decode($photos_url);
				
				// Retrieve Photos
				$photos = wp_remote_get('http://api.flickr.com/services/rest/?method=flickr.people.getPublicPhotos&api_key='.$api_key.'&user_id='.$person->user->id.'&per_page='.$number.'&format=json');
				$photos = trim($photos['body'], 'jsonFlickrApi()');
				$photos = json_decode($photos);
				
				// Create unordered list of selected photos
				echo '<ul class="flickr-photos">';
					foreach($photos->photos->photo as $photo): $photo = (array) $photo;
						$url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . '_s' . ".jpg";
						echo '<li class="flickr-photo">';
							echo '<a href="' . $photos_url->user->url . $photo['id'] . '" target="_blank">';
								echo '<img src="' . $url . '" alt="' . $photo['title'] . '" />';
							echo '</a>';
						echo '</li>';
					endforeach;
				echo '</ul>';
		
			} else { // If username does not exist
				echo '<p class="flickr-error">Invalid Flickr Username</p>';
			}
		}
		
		echo $after_widget;
		
	} // end widget
	
	/**
	 * Processes the widget's options to be saved.
	 *
	 * @param	array	new_instance	The previous instance of values before the update.
	 * @param	array	old_instance	The new instance of values to be generated via the update.
	 */
	public function update( $new_instance, $old_instance ) {
	
		$instance = $old_instance;
		
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['screen_name'] = $new_instance['screen_name'];
		$instance['number'] = $new_instance['number'];
    
		return $instance;
		
	} // end widget
	
	/**
	 * Generates the administration form for the widget.
	 *
	 * @param	array	instance	The array of keys and values for the widget.
	 */
	public function form( $instance ) {
	
		$defaults = array(
			'title' => 'Photos from Flickr',
			'screen_name' => '',
			'number' => 6
		);
		$instance = wp_parse_args((array) $instance, $defaults);
			
		// Display the admin form
		?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title</label>
            <input class="widefat" style="width: 216px;" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" />
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('screen_name'); ?>">Flickr Username</label>
            <input class="widefat" style="width: 216px;" id="<?php echo $this->get_field_id('screen_name'); ?>" name="<?php echo $this->get_field_name('screen_name'); ?>" value="<?php echo $instance['screen_name']; ?>" />
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('number'); ?>">Number of Photos to Display</label>
            <input class="widefat" style="width: 30px;" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo $instance['number']; ?>" />
        </p>
        <?php
		
	} // end form
	
} // end class

add_action( 'widgets_init', create_function( '', 'register_widget("OBM_Simple_Flickr_Display");' ) );