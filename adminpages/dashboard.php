<?php
	/*
		Much of this code is borroed from yst_plugin_tools.php in the Yoast WordPress SEO plugin. Thanks, Yoast!		
	*/
	
	global $pmpro_feed;
	$pmpro_feed = "http://feeds.feedburner.com/PaidMembershipsPro";
	
	function pmpro_postbox($id, $title, $content) 
	{
	?>
		<div id="<?php echo $id; ?>" class="postbox">
			<div class="handlediv" title="Click to toggle"><br /></div>
			<h3 class="hndle"><span><?php echo $title; ?></span></h3>
			<div class="inside">
				<?php echo $content; ?>
			</div>
		</div>
	<?php
	}	
	
	function pmpro_fetch_rss_items( $num ) 
	{
		global $pmpro_feed;
		
		include_once(ABSPATH . WPINC . '/feed.php');
		$rss = fetch_feed( $pmpro_feed );
		
		// Bail if feed doesn't work
		if ( is_wp_error($rss) )
			return false;
		
		$rss_items = $rss->get_items( 0, $rss->get_item_quantity( $num ) );
		
		// If the feed was erroneously 
		if ( !$rss_items ) {
			$md5 = md5( $pmpro_feed );
			delete_transient( 'feed_' . $md5 );
			delete_transient( 'feed_mod_' . $md5 );
			$rss = fetch_feed( $pmpro_feed );
			$rss_items = $rss->get_items( 0, $rss->get_item_quantity( $num ) );
		}
		
		return $rss_items;
	}
	
	/**
	 * Box with latest news from PaidMembershipsPro.com for sidebar
	 */
	function pmpro_news() 
	{
		$rss_items = pmpro_fetch_rss_items( 5 );
		
		$content = '<ul>';
		if ( !$rss_items ) {
			$content .= '<li class="pmpro_news">no news items, feed might be broken...</li>';
		} else {
			foreach ( $rss_items as $item ) {
				$content .= '<li class="pmpro_news">';
				$content .= '<a class="rsswidget" href="'.esc_url( $item->get_permalink(), $protocolls=null, 'display' ).'">'. esc_html( $item->get_title() ) .'</a> ';
				$content .= '</li>';
			}
		}								
		$content .= '</ul>';
		$pmpro_postbox('pmprolatest', 'Recent Updates from PaidMembershipsPro.com', $content);
	}

	/**
	 * Widget with latest news from PaidMembershipsPro.com for dashbaord
	 */
	function pmpro_db_widget() 
	{
		global $pmpro_feed;
		
		$options = get_option('pmpro_pmprodbwidget');
		
		$network = '';
		if ( function_exists('is_network_admin') && is_network_admin() )
			$network = '_network';

		if (isset($_POST['pmpro_removedbwidget'])) {
			$options['removedbwidget'.$network] = true;
			update_option('pmpro_pmprodbwidget',$options);
		}			
		if ( isset($options['removedbwidget'.$network]) && $options['removedbwidget'.$network] ) {
			echo "If you reload, this widget will be gone and never appear again, unless you decide to delete the database option 'pmpro_pmprodbwidget'.";
			return;
		}

		$rss_items = pmpro_fetch_rss_items( 3 );			
		
		echo "<ul>";
		
		if ( !$rss_items ) {
			echo '<li class="pmpro_news">no news items, feed might be broken...</li>';
		} else {
			foreach ( $rss_items as $item ) {
				echo '<li class="pmpro_news">';
				echo '<a class="rsswidget" href="'.esc_url( $item->get_permalink(), $protocolls=null, 'display' ).'">'. esc_html( $item->get_title() ) .'</a>';
				echo ' <span class="rss-date">'. $item->get_date(get_option('date_format')) .'</span>';
				echo '<div class="rssSummary">'. esc_html( pmpro_text_limit( strip_tags( $item->get_description() ), 150 ) ).'</div>';
				echo '</li>';
			}
		}						

		echo '</ul>';
		echo '<br class="clear"/><div style="margin-top:10px;border-top: 1px solid #ddd; padding-top: 10px; text-align:center;">';
		echo '<a href="'.$pmpro_feed.'"><img src="'.get_bloginfo('wpurl').'/wp-includes/images/rss.png" alt=""/> Subscribe with RSS</a>';
		echo ' &nbsp; &nbsp; &nbsp; ';
		echo '<a href="http://www.paidmembershipspro.com/"><img src="'.get_bloginfo('wpurl').'/wp-includes/images/wpmini-blue.png" alt=""/> View Online</a>';
		echo '<form class="alignright" method="post"><input type="hidden" name="pmpro_removedbwidget" value="true"/><input title="Remove this widget from all users dashboards" class="button" type="submit" value="X"/></form>';
		echo '</div>';
	}

	function pmpro_widget_setup() 
	{
		$network = '';
		if ( function_exists('is_network_admin') && is_network_admin() )
			$network = '_network';

		$options = get_option('pmpro_pmprodbwidget');
		if ( !isset($options['removedbwidget'.$network]) || !$options['removedbwidget'.$network] )
			wp_add_dashboard_widget( 'pmpro_db_widget' , 'The Latest From PaidMembershipsPro.com' , 'pmpro_db_widget');
	}
	
	add_action( 'wp_dashboard_setup', 'pmpro_widget_setup');
?>
