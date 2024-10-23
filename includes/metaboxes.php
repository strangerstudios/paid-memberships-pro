<?php
/**
 * Require Membership Meta Box
 */
function pmpro_page_meta() {
	global $post, $wpdb;
	$membership_levels = pmpro_getAllLevels( true, true );
	$membership_levels = pmpro_sort_levels_by_order( $membership_levels );
	$page_levels = $wpdb->get_col( "SELECT membership_id FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = '" . intval( $post->ID ) . "'" );

	// Build the selectors for the #memberships list based on level count.
	$pmpro_memberships_checklist_classes = array( 'list:category', 'categorychecklist', 'form-no-clear');
	if ( count( $membership_levels ) > 9 ) {
		$pmpro_memberships_checklist_classes[] = "pmpro_scrollable";
	}
	$pmpro_memberships_checklist_classes = implode( ' ', array_unique( $pmpro_memberships_checklist_classes ) );

	if ( count( $membership_levels ) > 1 ) { ?>
		<p><?php esc_html_e( 'Select:', 'paid-memberships-pro' ); ?> <a id="pmpro-memberships-checklist-select-all" href="javascript:void(0);"><?php esc_html_e( 'All', 'paid-memberships-pro' ); ?></a> | <a id="pmpro-memberships-checklist-select-none" href="javascript:void(0);"><?php esc_html_e( 'None', 'paid-memberships-pro' ); ?></a></p>
		<script type="text/javascript">
			jQuery('#pmpro-memberships-checklist-select-all').on('click',function(){
				jQuery('#pmpro-memberships-checklist input').prop('checked', true);
			});
			jQuery('#pmpro-memberships-checklist-select-none').on('click',function(){
				jQuery('#pmpro-memberships-checklist input').prop('checked', false);
			});
		</script>
	<?php } ?>
    <ul id="pmpro-memberships-checklist" class="<?php echo esc_attr( $pmpro_memberships_checklist_classes ); ?>">
    <input type="hidden" name="pmpro_noncename" id="pmpro_noncename" value="<?php echo esc_attr( wp_create_nonce( plugin_basename(__FILE__) ) )?>" />
	<?php
		$in_member_cat = false;
		foreach( $membership_levels as $level ) {
		?>
    	<li id="membership-level-<?php echo esc_attr( $level->id ); ?>">
        	<label class="selectit">
            	<input id="in-membership-level-<?php echo esc_attr( $level->id ); ?>" type="checkbox" <?php if(in_array($level->id, $page_levels)) { ?>checked="checked"<?php } ?> name="page_levels[]" value="<?php echo esc_attr( $level->id ) ;?>" />
				<?php
					echo esc_html( $level->name );
					//Check which categories are protected for this level
					$protectedcategories = $wpdb->get_col( "SELECT category_id FROM $wpdb->pmpro_memberships_categories WHERE membership_id = '" . intval( $level->id ) . "'");
					//See if this post is in any of the level's protected categories
					if( in_category( $protectedcategories, $post->id ) ) {
						$in_member_cat = true;
						echo ' *';
					}
				?>
            </label>
        </li>
    	<?php
		}
    ?>
    </ul>
	<?php
		if( 'post' == get_post_type( $post ) && $in_member_cat ) { ?>
		<p class="pmpro_meta_notice">* <?php esc_html_e("This post is already protected for this level because it is within a category that requires membership.", 'paid-memberships-pro' );?></p>
	<?php
		}
	?>
	<?php
		do_action( 'pmpro_after_require_membership_metabox', $post );
	?>
<?php
}

/**
 * Saves meta options when a page is saved.
 */
function pmpro_page_save( $post_id ) {
	global $wpdb;

	if( empty( $post_id ) ) {
		return false;
	}

	// Post is saving somehow with our meta box not shown.
	if ( ! isset( $_POST['pmpro_noncename'] ) ) {
		return $post_id;
	}

	// Verify the nonce.
	if ( ! wp_verify_nonce( sanitize_key( $_POST['pmpro_noncename'] ), plugin_basename( __FILE__ ) ) ) {
		return $post_id;
	}

	// Don't try to update meta fields on AUTOSAVE.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// Check permissions.
	if( ! empty( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
	}

	// OK, we're authenticated. We need to find and save the data.
	if( ! empty( $_POST['page_levels'] ) ) {
		$mydata = array_map( 'intval', $_POST['page_levels'] );
	} else {
		$mydata = NULL;
	}

	// Remove all memberships for this page.
	$wpdb->query( "DELETE FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = '" . intval( $post_id ) . "'" );

	// Add new memberships for this page.
	if( is_array( $mydata ) ) {
		foreach( $mydata as $level ) {
			$wpdb->query( "INSERT INTO {$wpdb->pmpro_memberships_pages} (membership_id, page_id) VALUES('" . intval( $level ) . "', '" . intval( $post_id ) . "')" );
		}
	}

	return $mydata;
}
add_action( 'save_post', 'pmpro_page_save' );

/**
 * Wrapper to add meta boxes for classic editor.
 */
function pmpro_page_meta_wrapper() {
    // If the block editor is being used, skip adding the meta boxes.
	$current_screen = get_current_screen();
	if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
		return;
	}
	
	// Add meta box for each restrictable post type.
	$restrictable_post_types = apply_filters( 'pmpro_restrictable_post_types', array( 'page', 'post' ) );
	foreach( $restrictable_post_types as $post_type ) {
		add_meta_box( 'pmpro_page_meta', __( 'Require Membership', 'paid-memberships-pro' ), 'pmpro_page_meta', $post_type, 'side', 'high' );
	}
}
add_action( 'add_meta_boxes', 'pmpro_page_meta_wrapper' );
