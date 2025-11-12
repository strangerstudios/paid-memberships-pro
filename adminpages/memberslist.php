<?php

global $user_list_table;
// Query, filter, and sort the data.
$user_list_table = new PMPro_Members_List_Table();
$user_list_table->prepare_items();

require_once dirname( __DIR__ ) . '/adminpages/admin_header.php';

// Build CSV export link.
// We now use the REST API for exports. Gather current filters to pass along when starting an export.
$members_export_filters = array();
if ( isset( $_REQUEST['s'] ) ) {
    $members_export_filters['s'] = esc_attr( trim( sanitize_text_field( $_REQUEST['s'] ) ) );
}
if ( isset( $_REQUEST['l'] ) ) {
    $members_export_filters['l'] = trim( sanitize_text_field( $_REQUEST['l'] ) );
}

// Render the List Table.
?>
	<hr class="wp-header-end">
	<form id="member-list-form" method="get">		
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Members List', 'paid-memberships-pro' ); ?></h1>
		<?php if ( current_user_can( 'edit_users' ) ) { ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-member'), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action pmpro-has-icon pmpro-has-icon-plus"><?php esc_html_e( 'Add New Member', 'paid-memberships-pro' ); ?></a>
		<?php } ?>
		<?php if ( current_user_can( 'pmpro_memberslistcsv' ) ) { ?>
			<button type="button" id="pmpro-members-export-button" class="page-title-action pmpro-has-icon pmpro-has-icon-download" aria-live="polite" data-status="idle" data-export-id="" data-type="members">
				<?php esc_html_e( 'Export to CSV', 'paid-memberships-pro' ); ?>
			</button>
		<?php } ?>
		<?php do_action( 'pmpro_memberslist_before_table' ); ?>	
		<input type="hidden" name="page" value="pmpro-memberslist" />
		<?php
			$user_list_table->search_box( __( 'Search Members', 'paid-memberships-pro' ), 'paid-memberships-pro' );
			$user_list_table->display();
		?>
	</form>
<?php
	// Inline script for handling members export via REST.
	if ( current_user_can( 'pmpro_memberslistcsv' ) ) :
		$export_filters_json = wp_json_encode( $members_export_filters );
?>
	<script type="text/javascript">
	(function(){
		const btn = document.getElementById('pmpro-members-export-button');
		if(!btn){return;}
		const filters = <?php echo $export_filters_json ? $export_filters_json : '{}'; ?>;
		let polling = false;
		let pollInterval = null;
		function setButton(text,status){
			btn.textContent = text;
			btn.dataset.status = status;
		}
		function startExport(){
			if ( btn.dataset.status !== 'idle' ) { return; }
			setButton('<?php echo esc_js( __( 'Preparing…', 'paid-memberships-pro' ) ); ?>','preparing');
			const payload = Object.assign({type:'members'}, filters);
			fetch('<?php echo esc_url_raw( rest_url( 'pmpro/v1/exports/start' ) ); ?>',{
				method:'POST',
				headers:{'Content-Type':'application/json','X-WP-Nonce':'<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'},
				body: JSON.stringify(payload)
			}).then(r=>r.json()).then(data=>{
				if(data.error){
					setButton('<?php echo esc_js( __( 'Error Starting Export', 'paid-memberships-pro' ) ); ?>','error');
					console.error('Export start error', data.error);
					return;
				}
				btn.dataset.exportId = data.export_id;
				if(data.status==='complete' && data.download_url){
					setButton('<?php echo esc_js( __( 'Download', 'paid-memberships-pro' ) ); ?>','complete');
					btn.onclick = ()=>{ window.location = data.download_url; };
				} else {
					setButton('<?php echo esc_js( __( 'Building CSV 0%…', 'paid-memberships-pro' ) ); ?>','running');
					beginPolling();
				}
			}).catch(err=>{
				setButton('<?php echo esc_js( __( 'Error', 'paid-memberships-pro' ) ); ?>','error');
				console.error(err);
			});
		}
		function pollStatus(){
			const exportId = btn.dataset.exportId;
			if(!exportId){ stopPolling(); return; }
			const url = new URL('<?php echo esc_url_raw( rest_url( 'pmpro/v1/exports/status' ) ); ?>');
			url.searchParams.set('type','members');
			url.searchParams.set('export_id', exportId);
			fetch(url.toString(),{headers:{'X-WP-Nonce':'<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'}})
				.then(r=>r.json())
				.then(data=>{
					if(data.error){
						setButton('<?php echo esc_js( __( 'Error', 'paid-memberships-pro' ) ); ?>','error');
						stopPolling();
						return;
					}
					if(data.status==='complete' && data.download_url){
						setButton('<?php echo esc_js( __( 'Download', 'paid-memberships-pro' ) ); ?>','complete');
						btn.onclick = ()=>{ window.location = data.download_url; };
						stopPolling();
						return;
					}
					if(data.status==='error'){
						setButton('<?php echo esc_js( __( 'Error', 'paid-memberships-pro' ) ); ?>','error');
						stopPolling();
						return;
					}
					const pct = data.percent || 0;
					setButton('<?php echo esc_js( __( 'Building CSV', 'paid-memberships-pro' ) ); ?> ' + pct + '%…','running');
				})
				.catch(err=>{
					console.error(err);
				});
		}
		function beginPolling(){
			if(polling){return;}
			polling = true;
			pollInterval = setInterval(pollStatus, 3000);
		}
		function stopPolling(){
			polling = false;
			if(pollInterval){ clearInterval(pollInterval); pollInterval=null; }
		}
		// Attempt to resume existing export for this user/type on load.
		function resumeIfActive(){
			const url = new URL('<?php echo esc_url_raw( rest_url( 'pmpro/v1/exports/status' ) ); ?>');
			url.searchParams.set('type','members');
			fetch(url.toString(),{headers:{'X-WP-Nonce':'<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'}})
				.then(r=>r.json())
				.then(data=>{
					if(data && !data.error && data.export_id){
						btn.dataset.exportId = data.export_id;
						if(data.status==='complete' && data.download_url){
							setButton('<?php echo esc_js( __( 'Download', 'paid-memberships-pro' ) ); ?>','complete');
							btn.onclick = ()=>{ window.location = data.download_url; };
						} else if(data.status==='running' || data.status==='queued'){
							setButton('<?php echo esc_js( __( 'Building CSV', 'paid-memberships-pro' ) ); ?> ' + (data.percent||0) + '%…','running');
							beginPolling();
						}
					}
				}).catch(()=>{});
		}
		btn.addEventListener('click', function(){
			if(btn.dataset.status==='complete'){ return; }
			if(btn.dataset.status==='running'){ return; }
			startExport();
		});
		resumeIfActive();
	})();
	</script>
<?php endif; ?>
<?php
	require_once dirname( __DIR__ ) . '/adminpages/admin_footer.php';
?>
