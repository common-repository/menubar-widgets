<?php 

	class Walker_Menubar_Widgets extends Walker_Nav_Menu{
		
		function start_lvl( &$output, $depth = 0, $args = array() ) {}
		
		function end_lvl( &$output, $depth = 0, $args = array() ) {}

		function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
			global $wp_registered_widgets, $sidebars_widgets, $_wp_nav_menu_max_depth;
			
			$_wp_nav_menu_max_depth = $depth > $_wp_nav_menu_max_depth ? $depth : $_wp_nav_menu_max_depth;

			ob_start();
			$item_id = esc_attr( $item->ID );
			$term = wp_get_post_terms( $item_id, "nav_menu" );
			$menu_id = ( isset( $term[0] ) )? $term[0]->term_id : 0;
			$removed_args = array('action','customlink-tab','edit-menu-item','menu-item','page-tab','_wpnonce');

			$original_title = '';
			if ( 'taxonomy' == $item->type ) {
				$original_title = get_term_field( 'name', $item->object_id, $item->object, 'raw' );
				if ( is_wp_error( $original_title ) ){
					$original_title = false;
				}
			} elseif ( 'post_type' == $item->type ) {
				$original_object = get_post( $item->object_id );
				$original_title = get_the_title( $original_object->ID );
			}

			$classes = array(
				'menu-item menu-item-depth-' . $depth,
				'menu-item-' . esc_attr( $item->object ),
				'menu-item-edit-' . ( ( isset( $_GET['edit-menu-item'] ) && $item_id == $_GET['edit-menu-item'] ) ? 'active' : 'inactive'),
			);

			$title = $item->title;

			if ( ! empty( $item->_invalid ) ) {
				$classes[] = 'menu-item-invalid';
				/* translators: %s: title of menu item which is invalid */
				$title = sprintf( esc_html__( '%s (Invalid)', 'default'), $item->title );
			} elseif ( isset( $item->post_status ) && 'draft' == $item->post_status ) {
				$classes[] = 'pending';
				/* translators: %s: title of menu item in draft status */
				$title = sprintf( esc_html__('%s (Pending)', 'default'), $item->title );
			}

			$title = ( ! isset( $item->label ) || '' == $item->label ) ? $title : $item->label;

			$submenu_text = '';
			if ( 0 == $depth ){
				$submenu_text = 'style="display: none;"';
			}
			?>
			<li id="menu-item-<?php echo $item_id; ?>" class="<?php echo implode(' ', $classes ); ?>">
				<dl class="menu-item-bar">
					<dt class="menu-item-handle">
						<span class="item-title"><span class="menu-item-title"><?php echo esc_html( $title ); ?></span> <span class="is-submenu" <?php echo $submenu_text; ?>><?php esc_html_e( 'sub item', 'default'); ?></span></span>
						<span class="item-controls">
							<span class="item-type"><?php echo esc_html( $item->type_label ); ?></span>
							<span class="item-order hide-if-js">
								<?php 
									$move_url = wp_nonce_url( add_query_arg( 
														array( 'action' => '__replace__', 'menu-item' => $item_id,),
														remove_query_arg($removed_args, admin_url( 'nav-menus.php' ) )
												   ),'move-menu_item');
												   
									$move_format = '<a href="%1$s" class="item-move-%2$s"><abbr title="%3$s">%4$s</abbr></a>';
									
									echo sprintf( $move_format, str_replace( "__replace__", "move-up-menu-item", $move_url ), "up", esc_html__('Move up', 'default'), "&#8593;" ) . " | ";
									echo sprintf( $move_format, str_replace( "__replace__", "move-down-menu-item", $move_url ), "down", esc_html__('Move down', 'default'), "&#8595;" );
								?>
							</span>
							<a class="item-edit" id="edit-<?php echo $item_id; ?>" title="<?php esc_attr_e('Edit Menu Item', 'default'); ?>" href="<?php
								echo ( isset( $_GET['edit-menu-item'] ) && $item_id == $_GET['edit-menu-item'] ) ? admin_url( 'nav-menus.php' ) : add_query_arg( 'edit-menu-item', $item_id, remove_query_arg( $removed_args, admin_url( 'nav-menus.php#menu-item-settings-' . $item_id ) ) );
							?>"><?php esc_html_e( 'Edit Menu Item', 'default'); ?></a>
						</span>
					</dt>
				</dl>

				<div class="menu-item-settings" id="menu-item-settings-<?php echo $item_id; ?>">
					<?php if( 'custom' == $item->type ) : ?>
						<p class="field-url description description-wide">
							<label for="edit-menu-item-url-<?php echo $item_id; ?>">
								<?php esc_html_e( 'URL', 'default'); ?><br />
								<input type="text" id="edit-menu-item-url-<?php echo $item_id; ?>" class="widefat code edit-menu-item-url" name="menu-item-url[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->url ); ?>" />
							</label>
						</p>
					<?php endif; ?>
					<p class="description description-thin">
						<label for="edit-menu-item-title-<?php echo $item_id; ?>">
							<?php esc_html_e( 'Navigation Label', 'default'); ?><br />
							<input type="text" id="edit-menu-item-title-<?php echo $item_id; ?>" class="widefat edit-menu-item-title" name="menu-item-title[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->title ); ?>" />
						</label>
					</p>
					<p class="description description-thin">
						<label for="edit-menu-item-attr-title-<?php echo $item_id; ?>">
							<?php esc_html_e( 'Title Attribute', 'default'); ?><br />
							<input type="text" id="edit-menu-item-attr-title-<?php echo $item_id; ?>" class="widefat edit-menu-item-attr-title" name="menu-item-attr-title[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->post_excerpt ); ?>" />
						</label>
					</p>
					<p class=" field-link-target description">
						<label for="edit-menu-item-target-<?php echo $item_id; ?>">
							<input type="checkbox" id="edit-menu-item-target-<?php echo $item_id; ?>" value="_blank" name="menu-item-target[<?php echo $item_id; ?>]"<?php checked( $item->target, '_blank' ); ?> />
							<?php esc_html_e( 'Open link in a new window/tab', 'default'); ?>
						</label>
					</p>
					<p class=" field-css-classes description description-wide">
						<label for="edit-menu-item-classes-<?php echo $item_id; ?>">
							<?php esc_html_e( 'CSS Classes (optional)', 'default'); ?><br />
							<input type="text" id="edit-menu-item-classes-<?php echo $item_id; ?>" class="widefat code edit-menu-item-classes" name="menu-item-classes[<?php echo $item_id; ?>]" value="<?php echo esc_attr( implode(' ', $item->classes ) ); ?>" />
							<span class="description">
								<?php 
									$css_desc = esc_html__('The css classes that starts to menu-item will appended in li tag (eg: %1$s), and the rest like iconfont classes will appended in "<i></i>" tag. (eg: %2$s) ', 'menubar-widgets'); 
									$li_tag = esc_html('<li class="menu-item-my-class" ></li>');
									$i_tag = esc_html('<li><i class="fa fa-facebook-square" ></i></li>');
									
									echo sprintf( $css_desc, "<code>{$li_tag}</code>", "<code>{$i_tag}</code>");
								?>
							</span>
						</label>
					</p>
					<p class=" field-xfn description description-wide">
						<label for="edit-menu-item-xfn-<?php echo $item_id; ?>">
							<?php esc_html_e( 'Link Relationship (XFN)' , 'default'); ?><br />
							<input type="text" id="edit-menu-item-xfn-<?php echo $item_id; ?>" class="widefat code edit-menu-item-xfn" name="menu-item-xfn[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->xfn ); ?>" />
						</label>
					</p>
					<p class=" field-description description description-wide">
						<label for="edit-menu-item-description-<?php echo $item_id; ?>">
							<?php esc_html_e( 'Description' , 'default'); ?><br />
							<textarea id="edit-menu-item-description-<?php echo $item_id; ?>" class="widefat edit-menu-item-description" rows="3" cols="20" name="menu-item-description[<?php echo $item_id; ?>]"><?php echo esc_html( $item->description ); // textarea_escaped ?></textarea>
							<span class="description"><?php esc_html_e('The description will be displayed in the menu if the current theme supports it.', 'default'); ?></span>
						</label>
					</p>
					
					
		<div class="menu-settings field-description description description-wide">
			<h3 class="title menubar-widgets-title" ><?php esc_html_e( 'Menubar Widgets' , 'menubar-widgets'); ?></h3>
	
<?php 
	if( isset( $sidebars_widgets["menubar_widgets"] ) ): 

		$menubar_widgets = $sidebars_widgets["menubar_widgets"];
		$db_widgets = ( $dbw = get_post_meta( $item_id, "_menubar_widgets", true ) )? $dbw : array();
		krsort( $db_widgets );
		$widgets =  array_unique( array_merge( $menubar_widgets , $db_widgets ) );
		
?>
		<?php if( !$widgets ){ ?>
		<p class="field-description description description-wide">
			<span class="description">
				<?php 
				$msg = esc_html__('There isn\'t  no widget to show, for add widgets please refer to %1$s the widgets section. %2$s', 'menubar-widgets');
				$widget_manager_url = admin_url("widgets.php");
				echo sprintf( $msg, "<a href='{$widget_manager_url}' >", "</a>");
				?>
			</span>
		</p>
		<?php }else{ ?>
		
		<span class="description"><?php esc_html_e( 'The list of active widgets in Menubar area.' , 'menubar-widgets'); ?></span>
		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<select name="menubar_widgets_action[<?php echo $menu_id;?>][<?php echo $item_id;?>]">
					<option value="activate-selected"><?php esc_html_e("Activate", 'default');?></option>
					<option value="deactivate-selected"><?php esc_html_e("Deactivate", 'default');?></option>
					<option value="update-selected"><?php esc_html_e("Update", 'default');?></option>
					<option value="delete-selected"><?php esc_html_e("Delete", 'default');?></option>
				</select>
				<input name="save_menu" id="save_menu_header" class="button button-primary menu-save" value="<?php esc_attr_e("Save Menu", 'default');?>" type="submit">
			</div>
		</div>
		
		<table  class="wp-menubar-widgets  wp-list-table widefat plugins">
			
			<?php 
				foreach( $db_widgets as $_dbw ){
					$in_arr = array_search( $_dbw, $widgets, true );
					if( false !== $in_arr ){
						unset( $widgets[$in_arr] );
						array_unshift( $widgets, $_dbw );
					}
				} 
				
				$widget_thead_rows = '
					<%1$s>
						<tr>
							<th scope="col" id="cb" class="manage-column column-cb check-column" >
								<label class="screen-reader-text" for="cb-select-all-1">%2$s</label>
								<input type="checkbox" id="cb-select-all-1">
							</th>
							<th scope="col" id="name" class="manage-column column-name" >%3$s</th>
							<th scope="col" id="description" class="manage-column column-description" >%4$s</th>
						</tr>
					</%1$s>
				';
				
				if( $widgets ){
					echo sprintf( $widget_thead_rows, "thead", esc_html__("Select All", 'default'), esc_html__("Widgets", 'default'), esc_html__("Description", 'default') );
					echo sprintf( $widget_thead_rows, "tfoot", esc_html__("Select All", 'default'), esc_html__("Widgets", 'default'), esc_html__("Description", 'default') );
				}
			?>
			
			<tbody id="menubar-widgets-list" >
			
			<?php
			
			foreach( (array) $widgets as $widget_id ){
			
				$widget_row = '
					<tr id="%1$s" class="menubar-widget-item %2$s" >
						<th scope="row" class="check-column menubar-item menubar-item-%3$d">
							<label class="screen-reader-text" for="%3$d-%1$s"> %4$s </label>
							<input  type="checkbox" name="menubar_widgets[%5$d][%3$d][%1$s]" value="%1$s" id="%3$d-%1$s">
						</th>
						<td class="plugin-title column-title">
							<strong>%4$s</strong>
							<div class="row-actions" >%1$s</div>
						</td>
						<td class="column-description desc">
							<div class="description">
								<p>%6$s</p>
							</div>
						</td>
						%7$s
					</tr>
					%8$s
				';
				
				$widget = isset( $wp_registered_widgets[$widget_id] )? $wp_registered_widgets[$widget_id] : array();
				$w_number = isset( $widget['params'][0]["number"] )? $widget['params'][0]["number"] : null ;
				
				$widget_id_without_int = preg_replace("|[0-9]+|i", "",  $widget_id );
				$widget_item = "widget_" . rtrim($widget_id_without_int,"-");
				
				$o = get_option( $widget_item , array() );
				$w_options = ( isset( $o[$w_number] ) )? $o[$w_number] : array(); 
				
				$w_title = ucfirst(str_replace(array("_", "-"), " ", $widget_id_without_int));
				$w_desc = isset( $widget["description"] )? $widget["description"] : esc_html__("Without description.", 'menubar-widgets');
				
				if( isset( $w_options["title"] ) && trim( $w_options["title"], " ") ){
					$w_title = $w_options["title"];
					
				}elseif( isset( $widget['name'] ) && $widget['name'] ){
					$w_title = $widget['name'];
				}
				
				
				if( !in_array( $widget_id, $menubar_widgets, true ) || !isset( $wp_registered_widgets[$widget_id] ) ){
					
					echo sprintf( $widget_row,
								  esc_attr( $widget_id ),
								  "widget-not-exists active update",
								  esc_attr( $item_id ),
								  esc_html($w_title),
								  $menu_id,
								  esc_html($w_desc),
								  apply_filters("mbw_register_error_columns", "", $widget_id, $item),
								  apply_filters("mbw_register_error_rows", "", $widget_id, $item)
						); 
					
					continue;
				}
				
				$active_class = "";
				if( in_array( $widget_id, $db_widgets , true) ){
					$active_class = "active";
				}
				
				echo sprintf( $widget_row, 
								esc_attr($widget_id),
								$active_class,
								esc_attr($item_id),
								esc_html($w_title),
								$menu_id,
								esc_html($w_desc),
								apply_filters("mbw_register_columns", "", $widget, $item),
								apply_filters("mbw_register_rows", "", $widget, $item)
					); 
			
			} // end foreach( $widgets as $widget_id ) ?>
			</tbody>
		</table> 
		
	<?php } // end if( !$widgets ) ?>
<?php endif; // end if( isset( $sidebars_widgets['menubar_widgets] ) ) ?>

</div>

<br />
<br />
<br />

<p class="menu-item-actions field-move hide-if-no-js description description-wide">
	<label>
		<span><?php esc_html_e( 'Move', 'default'); ?></span>
		<a href="#" class="menus-move-up"><?php esc_html_e( 'Up one', 'default'); ?></a>
		<a href="#" class="menus-move-down"><?php esc_html_e( 'Down one', 'default'); ?></a>
		<a href="#" class="menus-move-left"></a>
		<a href="#" class="menus-move-right"></a>
		<a href="#" class="menus-move-top"><?php esc_html_e( 'To the top', 'default'); ?></a>
	</label>
</p>

<div class="menu-item-actions description-wide submitbox">
	<?php if( 'custom' != $item->type && $original_title !== false ) : ?>
		<p class="link-to-original">
			<?php printf( esc_html__('Original: %s', 'default'), '<a href="' . esc_attr( $item->url ) . '">' . esc_html( $original_title ) . '</a>' ); ?>
		</p>
	<?php endif; ?>
	<a class="item-delete submitdelete deletion" id="delete-<?php echo $item_id; ?>" href="<?php
	echo wp_nonce_url( add_query_arg(
			array(
				'action' => 'delete-menu-item',
				'menu-item' => $item_id,
			),
			admin_url( 'nav-menus.php' )
		),'delete-menu_item_' . $item_id ); ?>"><?php esc_html_e( 'Remove', 'default'); ?></a> <span class="meta-sep hide-if-no-js"> | </span> <a class="item-cancel submitcancel hide-if-no-js" id="cancel-<?php echo $item_id; ?>" href="<?php echo esc_url( add_query_arg( array( 'edit-menu-item' => $item_id, 'cancel' => time() ), admin_url( 'nav-menus.php' ) ) );
		?>#menu-item-settings-<?php echo $item_id; ?>"><?php esc_html_e('Cancel', 'default'); ?></a>
</div>

	<input class="menu-item-data-db-id" type="hidden" name="menu-item-db-id[<?php echo $item_id; ?>]" value="<?php echo $item_id; ?>" />
	<input class="menu-item-data-object-id" type="hidden" name="menu-item-object-id[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->object_id ); ?>" />
	<input class="menu-item-data-object" type="hidden" name="menu-item-object[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->object ); ?>" />
	<input class="menu-item-data-parent-id" type="hidden" name="menu-item-parent-id[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->menu_item_parent ); ?>" />
	<input class="menu-item-data-position" type="hidden" name="menu-item-position[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->menu_order ); ?>" />
	<input class="menu-item-data-type" type="hidden" name="menu-item-type[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->type ); ?>" />

</div><!-- .menu-item-settings-->
<ul class="menu-item-transport"></ul>
		
<?php
			
			$output .= ob_get_contents();
			ob_end_clean();
		}
	
	}
	
?>