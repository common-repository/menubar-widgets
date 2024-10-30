<?php 
	function walker_menubar_start_el( $item_output, $item, $depth, $args ){
		global $wp_registered_widgets, $wp_registered_sidebars;
		
		$icon_classes = array_map(function( $css ){
			$is_math = preg_match("|(menu-item[a-z-_0-9]*)+|i", $css, $mathes );
			if( !$is_math ){
				
				return $css;
			}
		}, (array) $item->classes );
		$icon_classes = implode(" ", array_unique( $icon_classes ) );
		$menu_icon = "<i class='{$icon_classes}'></i>";
		
		
		# Template %%menu_icon%% for add '<i class="custom-css-classes" ></i>' to navigation menu .
		# eg: wp_nav_menu(array('link_before' => '%%menu_icon%%')); ===output==> <li><a href="url"><i class="fa fa-facebook-square"></i>Facebook</a></li>
		# or: wp_nav_menu(array('link_after' => '%%menu_icon%%')); ===output==> <li><a href="url">Facebook<i class="fa fa-facebook-square"></i></a></li>
		# You can to add your custom classes by 'Css Classes' field in Edit Menus section.
		
		$icon_depth = apply_filters("mbw_menu_item_icon_depth", 1, $depth);
		if( strpos($item_output, "%%menu_icon%%") ){
			// At default for all menu items
			if( $icon_depth ){
				$item_output = str_replace("%%menu_icon%%", $menu_icon, $item_output);
			}else{
				$item_output = str_replace("%%menu_icon%%", "", $item_output);
			}
		}else{
			# Default example: 
			# <li><a href="url">Facebook</a>
			# <i class="fa fa-facebook-square"></i></li>
			if( $icon_depth ){
				$item_output .= $menu_icon;
			}
		}
		
		# Template %%menu_desc%% for add '<p class='menu-item-description' >description</p>' to navmenu
		# Just like %%menu_icon%% template.
		
		$menu_desc = "<span class='menu-item-description' >" . trim($item->description , " ") . "</span>" ;
		$desc_depth = apply_filters("mbw_menu_item_desc_depth", 1, $depth);
		if( strpos($item_output, "%%menu_desc%%") ){
			// At default for all menu items
			if( $desc_depth ){
				$item_output = str_replace("%%menu_desc%%", $menu_desc, $item_output);
			}else{
				$item_output = str_replace("%%menu_desc%%", "", $item_output);
			}
		}else{
			# Default example: description with icon-font: 
			# <li><a href="url">Facebook</a>
			# <i class="fa fa-facebook-square"></i>
			# <p class='menu-item-description' >description</p></li>
			if( $desc_depth ){
				$item_output .= $menu_desc;
			}
		}
		
		$db_widgets = ( $w=get_post_meta( $item->ID, "_menubar_widgets", true ) )? $w : array() ;
		if( $db_widgets ): 
		$menubar_widgets_output = "";
		
		foreach( $db_widgets as $widget_id ){
			if( !isset( $wp_registered_widgets[$widget_id] ) ){ break; }
			$widget = $wp_registered_widgets[$widget_id] ;
			
			ob_start();
			$menubar = $wp_registered_sidebars["menubar_widgets"]; 
			$original_params = array_merge(
				array( array_merge( $menubar, array('widget_id' => $widget_id, 'widget_name' => $widget['name']) ) ),
				(array) $widget['params']
			);
			
			$params = $original_params;
			$classname = str_replace("_", "-", $widget["classname"] );
			$wid = $widget['params'][0]['number']; 
			
			$params[0]['before_widget'] = sprintf($params[0]['before_widget'], "menubar_widget_{$wid}", "{$classname} {$widget_id}" );
			$params = apply_filters("dynamic_menubar_widgets_params", $params, $original_params, $item, $widget );
			do_action("dynamic_menubar_widgets", $widget, $original_params, $item);
			
			if ( is_callable( $widget['callback'] ) ) {
				call_user_func_array( $widget['callback'], $params );
			}
			
			$menubar_widgets_output .= ob_get_contents();
			ob_end_clean();
		}
		
		if( $menubar_widgets_output ){ 
			/*** Change the <ul/><li/> tags to the <div/> tags, useful for different navigation menus ****
			For example : 
			1. wp_nav_menu(array('widgets_wrap'=>'<div id="your-menubar-widgets %1$s" class="sub-menu your-menubar-class %2$s" >%3$s</div>'));
			2. add_action('dynamic_menubar_widgets_params', function( $params, $o_params, $i, $widget ){
				if( $widget['callback'][0]['id_base'] != 'your-widget-id' ){ // or calendar, archives, ...
					return $params;
				}
				$params[0]['before_widget']	= '<div id="%1$s" class="your-menubar-widget %2$s">';
				$params[0]['after_widget']	= '</div>';	
				return $params;
			}, 1, 4);
			***/
			$widgets_wrap = isset( $args->widgets_wrap )? $args->widgets_wrap : '<ul id="%1$s" class="sub-menu menubar-widgets %2$s" >%3$s</ul>';
			$item_output .= sprintf( $widgets_wrap, "wp-menubar-widgets-{$item->ID}", "menubar-item-{$item->ID} menubar-item-{$classname}", $menubar_widgets_output );
		}
		endif; # if( $db_widgets ): 
		
		return $item_output;
	}

?>