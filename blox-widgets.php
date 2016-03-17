<?php
/**
 * Plugin Name: Blox - Widgets Addon
 * Plugin URI:  https://www.bloxwp.com
 * Description: Enables the Widgets Addon for Blox
 * Author:      Nick Diego
 * Author URI:  http://www.outermostdesign.com
 * Version:     1.0.0
 * Text Domain: blox-widgets
 * Domain Path: languages
 *
 * Blox is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Blox is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Blox. If not, see <http://www.gnu.org/licenses/>.
 */


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


add_action( 'plugins_loaded', 'blox_load_widgets_addon' );
/**
 * Load the class. Must be called after all plugins are loaded
 *
 * @since 1.0.0
 */
function blox_load_widgets_addon() {

	// If Blox is not active or if the addon class already exists, bail...
	if ( ! class_exists( 'Blox_Main' ) || class_exists( 'Blox_Widgets_Main' ) ) {
		return;
	}
	
	
	/**
	 * Main plugin class.
	 *
	 * @since 1.0.0
	 *
	 * @package Blox
	 * @author  Nick Diego
	 */
	class Blox_Widgets_Main {

		/**
		 * Holds the class object.
		 *
		 * @since 1.0.0
		 *
		 * @var object
		 */
		public static $instance;

		/**
		 * Plugin version, used for cache-busting of style and script file references.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version = '1.0.0';

		/**
		 * The name of the plugin.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $plugin_name = 'Blox - Widgets Addon';
		
		/**
		 * Unique plugin slug identifier.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $plugin_slug = 'blox-widgets';

		/**
		 * Plugin file.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $file = __FILE__;

		/**
		 * Primary class constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			// Load the plugin textdomain.
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			
			// Add additional links to the plugin's row on the admin plugin page
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
			
			// Initialize addon's license settings field
			add_action( 'init', array( $this, 'license_init' ) );
			
			// Register the Blox widget area
			add_action( 'init', array( $this, 'register_blox_widget_area' ) );
			
			// Add the Widgets content type, format settings, and save...
			add_filter( 'blox_content_type', array( $this, 'add_widgets_content' ), 20 );
			add_action( 'blox_get_content_widgets', array( $this, 'get_widgets_content' ), 10, 4 );
			add_filter( 'blox_save_content_widgets', array( $this, 'save_widgets_content' ), 10, 3 );
			
			// Print widget content on the frontend
			add_action( 'blox_print_content_widgets', array( $this, 'print_widgets_content' ), 10, 4 );
	
			// Let Blox know the addon is active
			add_filter( 'blox_get_active_addons', array( $this, 'notify_of_active_addon' ), 10 );
		}


		/**
		 * Loads the plugin textdomain for translation.
		 *
		 * @since 1.0.0
		 */
		public function load_textdomain() {
			load_plugin_textdomain( $this->plugin_slug, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}


		/**
		 * Adds additional links to the plugin row meta links
		 *
		 * @since 1.0.0
		 *
		 * @param array $links   Already defined meta links
		 * @param string $file   Plugin file path and name being processed
		 * @return array $links  The new array of meta links
		 */
		function plugin_row_meta( $links, $file ) {

			// If we are not on the correct plugin, abort
			if ( $file != 'blox-widgets/blox-widgets.php' ) {
				return $links;
			}

			$docs_link = esc_url( add_query_arg( array(
					'utm_source'   => 'admin-plugins-page',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'BloxPluginsPage',
					'utm_content'  => 'plugin-page-link'
				), 'https://www.bloxwp.com/documentation/widgets' )
			);

			$new_links = array(
				'<a href="' . $docs_link . '">' . esc_html__( 'Documentation', 'blox-widgets' ) . '</a>',
			);

			$links = array_merge( $links, $new_links );

			return $links;
		}
	
		
		/**
		 * Load license settings
		 *
		 * @since 1.0.0
		 */
		public function license_init() {
			
			// Setup the license
			if ( class_exists( 'Blox_License' ) ) {
				$blox_widgets_addon_license = new Blox_License( __FILE__, 'Widgets Addon', '1.0.0', 'Nicholas Diego', 'blox_widgets_addon_license_key', 'https://www.bloxwp.com', 'addons' );
			}
			
		}
		
		
		/**
		 * Register the Blox widget area
		 *
		 * @since 1.0.0
		 */
		public function register_blox_widget_area() {
		
			// Only run if Genesis is active...
			if ( function_exists( 'genesis_pre' ) ) {
			
				// Use builtin Genesis function to register widget area
				genesis_register_widget_area(
					array(
						'id'            => 'blox-widgets',
						'name'          => __( 'Blox Widgets', 'blox-widgets' ),
						'description'   => __( 'Place all widgets you would like to make available to Blox here. When building a widget content block, you will be able to toggle which widgets you would like to use. However, the display order of the widgets is determined here.', 'blox-widgets' )
					)
				);
			}
		}


		/* Enabled the "custom" content (i.e. WP Editor) option in the plugin
		 *
		 * @since 1.0.0
		 *
		 * @param array $content_types  An array of the content types available
		 */
		public function add_widgets_content( $content_types ) {
			$content_types['widgets'] = __( 'Widgets', 'blox-widgets' );
			return $content_types;
		}


		/* Prints all of the editor ralated settings fields
		 *
		 * @since 1.0.0
		 *
		 * @param int $id             The block id
		 * @param string $name_prefix The prefix for saving each setting
		 * @param string $get_prefix  The prefix for retrieving each setting
		 * @param bool $global        The block state
		 */
		public function get_widgets_content( $id, $name_prefix, $get_prefix, $global ) {
	
			global $wp_registered_widgets;
			?>

			<!-- Wordpress Editor Settings -->
			<table class="form-table blox-content-widgets blox-hidden">
				<tbody>
					<tr class="blox-content-title"><th scope="row"><?php _e( 'Widgets Settings', 'blox-widgets' ); ?></th><td><hr></td></tr>
					<tr>
						<th scope="row"><?php _e( 'Available Widgets', 'blox-widgets' ); ?></th>
						<td>
							<?php 
					
							$sidebar_id       = 'blox-widgets';
							$sidebars_widgets = wp_get_sidebars_widgets();
					
							if ( ! empty ( $sidebars_widgets[$sidebar_id] ) ) {
					
							?>
							<div class="blox-checkbox-container">
								<ul>
								<?php 
					
									foreach ( (array) $sidebars_widgets[$sidebar_id] as $widget_id ) {
								
										// Make sure our widget is in the registered widgets array
										if ( ! isset( $wp_registered_widgets[$widget_id] ) ) continue;
										?>
										<li>
											<label>
								
											<input type="checkbox" name="<?php echo $name_prefix; ?>[widgets][selection][]" value="<?php echo $widget_id; ?>" <?php echo ! empty( $get_prefix['widgets']['selection'] ) && in_array( $widget_id, $get_prefix['widgets']['selection'] ) ? 'checked="checked"' : ''; ?> /> <?php echo $wp_registered_widgets[$widget_id]['name']; ?>
											<?php							
											if ( isset( $wp_registered_widgets[$widget_id]['params'][0]['number'] ) ) {
						
												// Retrieve optional set title if the widget has one (code thanks to qurl: Dynamic Widgets)
												$number      = $wp_registered_widgets[$widget_id]['params'][0]['number'];
												$option_name = $wp_registered_widgets[$widget_id]['callback'][0]->option_name;
												$option      = get_option( $option_name );
						
												// if a title was found, print it
												if ( ! empty( $option[$number]['title'] ) ) {
													echo ': <span class="in-widget-title">' . $option[$number]['title'] . '</span>';
												}
											}
											?>
											</label>
										</li>
									<?php } ?>
								</ul>
							</div>
							<div class="blox-checkbox-select-tools">
								<a class="blox-checkbox-select-all" href="#"><?php _e( 'Select All', 'blox-widgets' ); ?></a> <a class="blox-checkbox-select-none" href="#"><?php _e( 'Unselect All', 'blox-widgets' ); ?></a>
							</div>
							<div class="blox-description" style="margin-top:15px">
								<?php echo sprintf( __( 'To add more widgets, head on over to the %1$sWidgets%5$s page and add a few widgets to the %2$sBlox Widgets%3$s widget area. The order in which selected widget are shown on the frontend is managed on the Widgets page. For more information, check out the %4$sWidgets Documentation%5$s', 'blox-widgets' ), '<a href="' . admin_url( 'widgets.php' ) . '">', '<strong>','</strong>', '<a href="https://www.bloxwp.com/documentation/widgets" target="_blank">', '</a>' );?>
							</div>
							<?php } else { 
								echo '<div class="blox-description">' . sprintf( __( 'It doesn\'t look like you have added any widgets yet. Head on over to the %1$sWidgets%5$s page and add a few widgets to the %2$sBlox Widgets%3$s widget area. They will then show up here and you can choose the ones you want to use. For more information, check out the %4$sWidgets Documentation%5$s', 'blox-widgets' ), '<a href="' . admin_url( 'widgets.php' ) . '">', '<strong>','</strong>', '<a href="https://www.bloxwp.com/documentation/widgets" target="_blank">', '</a>' ) . '</div>'; 
							} ?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
		}


		/* Saves all of the editor ralated settings
		 *
		 * @since 1.0.0
		 *
		 * @param string $name_prefix The prefix for saving each setting (this brings ...['editor'] with it)
		 * @param int $id             The block id
		 * @param bool $global        The block state
		 */
		public function save_widgets_content( $name_prefix, $id, $global ) {

			$settings = array();

			$settings['selection'] = isset( $name_prefix['selection'] ) ? array_map( 'esc_attr', $name_prefix['selection'] ) : '';

			return $settings;
		}


		/* Prints the editor content to the frontend
		 *
		 * @since 1.0.0
		 *
		 * @param array $content_data Array of all content data
		 * @param int $id             The block id
		 * @param array $block        NEED DESCRIPTION
		 * @param string $global      The block state
		 */
		public function print_widgets_content( $content_data, $block_id, $block, $global ) {
	
			$this->block_id_master = $block_id;
			
			// Check to see if the Blox Widgets area has widgets. If not, do nothing.
			if ( ! is_active_sidebar( 'blox-widgets' ) ) {
				return;
			}
	
			// Empty array of additional CSS classes
			$classes = array();
	
			// Empty array of blox widget area args
			$args = array();
	
			$defaults = apply_filters( 'blox_widget_area_defaults', array(
				'before'              => genesis_html5() ? '<aside class="blox-widgets widget-area ' . implode( ' ', apply_filters( 'blox_content_widgets_classes', $classes ) ) . '">' . genesis_sidebar_title( 'blox-widgets' ) : '<div class="widget-area">',
				'after'               => genesis_html5() ? '</aside>' : '</div>',
				'before_sidebar_hook' => 'blox_before_widget_area',
				'after_sidebar_hook'  => 'blox_after_widget_area',
			), 'blox-widgets', $args );
	
			// Merge our defaults and any "custom" args
			$args = wp_parse_args( $args, $defaults );
	
			// Opening widget area markup
			echo $args['before'];
	
			// Before widget area hook
			if ( $args['before_sidebar_hook'] ) {
				do_action( $args['before_sidebar_hook'] );
			}
	
			if ( ! empty( $content_data['widgets']['selection'] ) ) {

				// We need to outout buffer the widget contents
				ob_start();
				call_user_func( array( $this, 'blox_display_widgets' ), 'blox-widgets', $content_data, $block_id, $block, $global );
				$all_widgets = ob_get_clean();
		
				echo ( $all_widgets );

			} else {
				_e( 'You forgot to select some widgets to display!', 'blox-widgets' );
			}
	
			// After widget area hook
			if ( $args['after_sidebar_hook'] ) {
				do_action( $args['after_sidebar_hook'] );
			}
	
			// Closing widget area markup
			echo $args['after'];

		}



		public function blox_display_widgets( $index, $content_data, $block_id, $block, $global ) {
	
			global $wp_registered_sidebars, $wp_registered_widgets;
	
			$widget_prefix    = 'blox_' . $block_id . '_';
			$sidebar 		  = $wp_registered_sidebars[$index];
			$sidebars_widgets = wp_get_sidebars_widgets();
	
			// Bail early if "blox-widgets" does not exist or if we have no widgets in the widget area
			if ( empty( $sidebar ) || empty( $sidebars_widgets[ $index ] ) || ! is_array( $sidebars_widgets[ $index ] ) ) {
				return;
			}

			// Loop through all the widgets in the Blox Widgets sidebar and determine whether to show or not
			foreach ( (array) $sidebars_widgets[$index] as $id ) {
		
				// If the widget is not in the registered widgets array, bail...
				if ( !isset( $wp_registered_widgets[$id] ) ) continue;
		
				// If the widget is not in our "selected" widgets array, bail...
				if ( ! in_array( $id, $content_data['widgets']['selection'] ) ) continue;
		
				// Build our array of widget parameters 
				$params = array_merge(
					array( array_merge( $sidebar, array( 'widget_id' => $id, 'widget_name' => $wp_registered_widgets[$id]['name'] ) ) ),
					(array) $wp_registered_widgets[$id]['params']
				);

				// Substitute HTML id (with "blox_[id]_" prefix) and class attributes into before_widget
				$classname_ = '';
				foreach ( (array) $wp_registered_widgets[$id]['classname'] as $cn ) {
					if ( is_string( $cn ) ) {
						$classname_ .= '_' . $cn;
					} else if ( is_object( $cn ) ) {
						$classname_ .= '_' . get_class( $cn );
					}
				}
				$classname_ = ltrim( $classname_, '_' );
				$params[0]['before_widget'] = sprintf( $params[0]['before_widget'], $widget_prefix . $id, $classname_ );


				/**
				 * Filter the parameters passed to a widget's display callback.
				 *
				 * @since 1.0.0
				 *
				 * @param array $params {
				 *     @type array $args  {
				 *         @type string $name          Name of the sidebar the widget is assigned to.
				 *         @type string $id            ID of the sidebar the widget is assigned to.
				 *         @type string $description   The sidebar description.
				 *         @type string $class         CSS class applied to the sidebar container.
				 *         @type string $before_widget HTML markup to prepend to each widget in the sidebar.
				 *         @type string $after_widget  HTML markup to append to each widget in the sidebar.
				 *         @type string $before_title  HTML markup to prepend to the widget title when displayed.
				 *         @type string $after_title   HTML markup to append to the widget title when displayed.
				 *         @type string $widget_id     ID of the widget.
				 *         @type string $widget_name   Name of the widget.
				 *     }
				 *     @type array $widget_args {
				 *         An array of multi-widget arguments.
				 *
				 *         @type int $number Number increment used for multiples of the same widget.
				 *     }
				 * }
				 */
				$params = apply_filters( 'blox_widget_area_params', $params );

				// Make sure the widget callback function exists, then call it
				if ( is_callable( $wp_registered_widgets[$id]['callback'] ) ) {
					call_user_func_array( $wp_registered_widgets[$id]['callback'], $params );
				}
			}
		}

	
	
		/**
		 * Let Blox know this extension has been activated.
		 *
		 * @since 1.0.0
		 */
		public function notify_of_active_addon( $addons ) {

			$addons['widgets_addon'] = __( 'Widgets Addon', 'blox-widgets' );
			return $addons;
		}


		/**
		 * Returns the singleton instance of the class.
		 *
		 * @since 1.0.0
		 *
		 * @return object The Blox_Widgets_Main object.
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Blox_Widgets_Main ) ) {
				self::$instance = new Blox_Widgets_Main();
			}

			return self::$instance;
		}
	}

	// Load the main plugin class.
	$blox_widgets_main = Blox_Widgets_Main::get_instance();
}