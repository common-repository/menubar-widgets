<?php
/*
	Plugin Name: Menubar Widgets
	Plugin URI: https://bitbucket.org/khosroblog/menubar-widgets
	Description: A standard wordpress plugin that helps you add multiple widgets to navigation menu item.
	Version: 0.1.0
	Author: Hadi Khosrojerdi
	Author URI: http://khosroblog.com
	License: GNU General Public License v2 or later 
*/
?>
<?php 
	class Menubar_Widgets_Plugin {
		
		public function __construct(){
			$this->init();
		}
		
		
		public function init(){
		
			# Constants
			add_action("plugins_loaded", array($this, "define_constants"));
			
			# Classes
			add_action("plugins_loaded", array($this, "load_inc"));
			
			# Localization
			add_action("plugins_loaded", array($this, "localization"));
			
			# Register Menubar
			add_action( 'widgets_init', array($this, "register_menubar"));
			
			# Menubar Widgets Walker
			add_action("wp_edit_nav_menu_walker", array($this, "register_menubar_widgets_walker"));
			add_action("walker_nav_menu_start_el", "walker_menubar_start_el", 1, 4);
			
			# Menubar Widgets Actions
			add_action("wp_update_nav_menu_item", array($this, "menubar_widgets_actions"), 1, 3);
			add_action("widgets.php", array($this, "delete_widgets_from_widgets_area") );
			
			# Menubar Widgets Hooks
			add_action("mbw_register_error_rows", array($this,"register_error_rows"), 1, 2);
			
			# Menubar Widgets Styles & Scripts
			add_action("nav_menu_css_class", array($this, "menubar_item_css_classes"), 1, 2);
			add_action("admin_enqueue_scripts", array($this, "register_admin_styles_and_scripts"), 100, 1);
		}
		
		
		public function define_constants(){
			defined("MBW_URL") 	? null : define("MBW_URL", plugin_dir_url( __FILE__ ) );
			defined("MBW_DIR") 	? null : define("MBW_DIR", plugin_dir_path( __FILE__ ) );
			defined("MBW_INC") 	? null : define("MBW_INC", MBW_DIR . trailingslashit("inc") );
			defined("MBW_LANG") ? null : define("MBW_LANG", MBW_DIR . trailingslashit("languages") );
			defined("MBW_CSS") 	? null : define("MBW_CSS", MBW_URL . trailingslashit("css") );
			defined("MBW_JS") 	? null : define("MBW_JS", MBW_URL . trailingslashit("js") );
			defined("MBW_VER") 	? null : define("MBW_VER", "0.1.0" );
		}
		
		
		public function load_inc(){
			if( !class_exists("Walker_Menubar_Widgets") ){
				require_once( MBW_INC . "Walker_Menubar_Widgets.class.php");
			}
			require_once( MBW_INC . "walker_menubar_start_el.php");
		}
		
		
		public function localization(){
			load_plugin_textdomain('menubar-widgets', false, plugin_basename( MBW_LANG ) );
		}
		
		
		public function register_menubar_widgets_walker(){
			return "Walker_Menubar_Widgets";
		}
		
		
		public function register_menubar(){
			register_sidebar( array(
				'name' 			=> esc_html__( 'Menubar Area.', 'menubar-widgets' ),
				'id' 			=> 'menubar_widgets',
				"before_widget" => '<li id="%1$s" class="menubar-widget %2$s">',
				"after_widget" 	=> '</li>',
				'description' 	=> esc_html__( 'Widgets in this area will be shown on the menu bar.', 'menubar-widgets' ),
				'before_title' 	=> '<span class="menubar-widget-title" >',
				'after_title' 	=> '</span>'
			) );
		}
		
		public function menubar_widgets_actions( $menu_id, $item_id, $args ){
			global $sidebars_widgets;
			
			$mb_widgets = isset( $_POST["menubar_widgets"] )? $_POST["menubar_widgets"] : array() ;
			$actions = isset( $_POST["menubar_widgets_action"] )? $_POST["menubar_widgets_action"] : false ;
			
			if( !$actions || !isset( $actions[$menu_id] ) || !isset( $actions[$menu_id][$item_id] ) ){
				return;
			}
			
			if( !isset( $mb_widgets[$menu_id] ) || !isset( $mb_widgets[$menu_id][$item_id]) ){
				return;
			}

			$received_widgets = array_values( $mb_widgets[$menu_id][$item_id] );
			$db_widgets = ( $dbw=get_post_meta($item_id, "_menubar_widgets", true) )? $dbw : array() ;
			$action = $actions[$menu_id][$item_id];
			
			switch( $action ){
				
				case "activate-selected": 
				case "update-selected": 
					$all_widgets = $received_widgets;
					if( $db_widgets ){
						foreach( $received_widgets as $wid ){
							$index = array_search($wid, $db_widgets, true);
							if( false !== $index ){
								unset( $db_widgets[$index] );
							}
						}
						$all_widgets = array_unique( array_merge( $received_widgets, $db_widgets ) );
					}
					update_post_meta( $item_id, '_menubar_widgets', $all_widgets );
					
					do_action("menubar_widgets_{$action}", $received_widgets, $menu_id, $item_id, $args );
					break;
					
				case "deactivate-selected": 
					foreach( $received_widgets as $wid ){
						$index = array_search($wid, $db_widgets, true);
						if( false !== $index ){
							unset( $db_widgets[$index] );
							update_post_meta( $item_id, '_menubar_widgets', $db_widgets );
						} 
					}
					
					do_action("menubar_widgets_{$action}", $received_widgets, $menu_id, $item_id, $args );
					break;
					
				case "delete-selected": 
					foreach( $received_widgets as $wid ){
						// Delete sidebar widgets
						if( isset( $sidebars_widgets['menubar_widgets'] ) ){
							$menubar_widget_index = array_search($wid, $sidebars_widgets['menubar_widgets'], true);
							if( false !== $menubar_widget_index ){
								unset( $sidebars_widgets['menubar_widgets'][$menubar_widget_index] );
								wp_set_sidebars_widgets( $sidebars_widgets );
								
							}
						}
						// Delete inactive widgets
						if( isset( $sidebars_widgets['wp_inactive_widgets'] ) ){
							$inactive_widget_index = array_search($wid, $sidebars_widgets['wp_inactive_widgets'], true);
							if( false !== $inactive_widget_index){
								unset( $sidebars_widgets['wp_inactive_widgets'][$inactive_widget_index] );
								wp_set_sidebars_widgets( $sidebars_widgets );
							}
						}
						// Delete widget meta posts
						$index = array_search($wid, $db_widgets, true);
						if( false !== $index ){
							unset( $db_widgets[$index] );
							update_post_meta( $item_id, '_menubar_widgets', $db_widgets );
						} 
						
					}
					
					do_action("menubar_widgets_{$action}", $received_widgets, $menu_id, $item_id, $args );
					break;
				
			}
		
			
		}
		
		public function delete_widgets_from_widgets_area(){
		
			$widget_id = $_POST['widget-id'];
			$delete_widget = isset($_POST['delete_widget']) && $_POST['delete_widget'];
			
			if( $delete_widget ):
				$_posts = get_posts(array(
					'post_status'	=> 'publish',
					'post_type'		=>	'nav_menu_item',
					'meta_key' 		=> '_menubar_widgets',
				));
			   
				$mb_widgets = array();
				foreach( (array) $_posts as $p ){
					$mb_widgets = get_post_meta( $p->ID, "_menubar_widgets", true );
					$index = array_search( $widget_id, $mb_widgets , true);
					if( false !== $index ){
						unset( $mb_widgets[$index] );
						update_post_meta($p->ID, "_menubar_widgets", $mb_widgets );
					}
				}
			
			endif;
		}
		
		
		public function register_error_rows( $empty, $widget_id){
			global $sidebars_widgets;
			
			$message = esc_html__('The widget %1$s not exists in the %2$s Menubar area %3$s, you can to delete it.', 'menubar-widgets');
			if( in_array( $widget_id, $sidebars_widgets["wp_inactive_widgets"], true ) ){
				$message = esc_html__('The widget %1$s not exists in the %2$s Menubar area %3$s, you can to add it of %2$s inactive widgets %3$s to %2$s Menubar area %3$s.', 'menubar-widgets');
			}
			$error_msg = sprintf( $message, "<strong>{$widget_id}</strong>", "<strong>", "</strong>");
			
			$error_temp  = '<tr class="menubar-widget-error plugin-update-tr '.$widget_id.'">';
			$error_temp .= '<td class="menubar-widget-error-msg plugin-update colspanchange" colspan="3">';
			$error_temp .= '<div class="menubar-widget-error-msg-wrap" >';
			$error_temp .= '<div style="color:#D54E21" class="dashicons dashicons-post-trash"></div>';
			$error_temp .= $error_msg;
			$error_temp .= '</div>';
			$error_temp .= '</td>';
			$error_temp .= '</tr>';
			
			return $error_temp;
		}
		
	
		public function menubar_item_css_classes( $classes, $item ){
		
			$_classes = array_map(function( $css ){
				$is_math = preg_match("|(menu-item[a-z-_0-9]*)+|i", $css, $mathes );
				if( $is_math ){
					return $mathes[0];
				}
			}, (array) $item->classes );
			
			return array_unique( $_classes );
		}
		
		
		public function register_admin_styles_and_scripts(){
			global $hook_suffix;
			
			if( trim( $hook_suffix, " " ) != "nav-menus.php" ){ return; }
			
			# Menubar Widgets Admin CSS
			wp_register_style( "menubar-widgets-admin", MBW_CSS . "menubar-widgets-admin.css", array(), MBW_VER );
			wp_enqueue_style("menubar-widgets-admin");
			
			# Menubar Widgets Admin JS
			wp_register_script( "menubar-widgets-admin", MBW_JS . "menubar-widgets-admin.js", array(), MBW_VER, true );
			wp_enqueue_script("menubar-widgets-admin");
		}
		
		
	}

	$Menubar_Widgets_Plugin = new Menubar_Widgets_Plugin();

?>