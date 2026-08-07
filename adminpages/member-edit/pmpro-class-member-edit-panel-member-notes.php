<?php

/**
 * Member Notes panel.
 *
 * Notes are stored as a single string in the `user_notes` user meta — the same
 * key the old textarea used, and the same shape order notes use:
 *
 *     May 1, 2026 8:00 am by David: First note body
 *
 *     April 30, 2026 7:55 am by System: Another note body
 *
 * Notes are separated by a blank line. Each note's header is
 * `<date> by <author>: <body>`. The string is parsed for display and rebuilt
 * on add/delete. Pre-existing free-form `user_notes` content that doesn't
 * match the header pattern still displays as one or more "legacy" entries and
 * can be trashed like any other note.
 */
class PMPro_Member_Edit_Panel_Member_Notes extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$this->slug = 'member-notes';
		$this->title = __( 'Member Notes', 'paid-memberships-pro' );

		// The panel handles its own action buttons. No top-level submit.
		$this->submit_text = '';

		// Show success/error messages from prior actions on this panel.
		if ( ! empty( $_REQUEST['user_id'] ) && ! empty( $_REQUEST['member_notes_action'] ) ) {
			$action = sanitize_key( $_REQUEST['member_notes_action'] );
			if ( $action === 'added' ) {
				pmpro_setMessage( __( 'Member note added.', 'paid-memberships-pro' ), 'pmpro_success' );
			} elseif ( $action === 'deleted' ) {
				pmpro_setMessage( __( 'Member note deleted.', 'paid-memberships-pro' ), 'pmpro_success' );
			}
		}
	}

	/**
	 * Parse the user's `user_notes` string into structured records.
	 *
	 * Each record: { index, date, author, note, legacy }. Records are returned
	 * in stored order (oldest first); `index` is the position in the stored
	 * string and is what `delete_note()` uses.
	 *
	 * @param int $user_id
	 * @return array
	 */
	protected static function parse_notes( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return array();
		}

		$string = (string) get_user_meta( $user_id, 'user_notes', true );
		if ( '' === trim( $string ) ) {
			return array();
		}

		$chunks = preg_split( "/\n\s*\n/", $string );
		$notes  = array();
		$index  = 0;
		foreach ( $chunks as $chunk ) {
			$chunk = trim( $chunk );
			if ( '' === $chunk ) {
				continue;
			}

			if ( preg_match( '/^(.+?) by (.+?): (.*)$/s', $chunk, $matches ) ) {
				$notes[] = array(
					'index'  => $index,
					'date'   => $matches[1],
					'author' => $matches[2],
					'note'   => $matches[3],
					'legacy' => false,
				);
			} else {
				$notes[] = array(
					'index'  => $index,
					'date'   => '',
					'author' => '',
					'note'   => $chunk,
					'legacy' => true,
				);
			}
			$index++;
		}

		return $notes;
	}

	/**
	 * Append a new note to the user's `user_notes` string.
	 *
	 * @param int    $user_id
	 * @param string $note
	 * @return bool
	 */
	protected static function add_note( $user_id, $note ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}

		// Sanitize and collapse internal blank lines so the `\n\n` separator stays unambiguous.
		$note = trim( wp_kses_post( (string) $note ) );
		$note = preg_replace( "/\n\s*\n+/", "\n", $note );
		if ( '' === $note ) {
			return false;
		}

		$current_user = wp_get_current_user();
		$author_name  = $current_user && $current_user->ID ? $current_user->display_name : __( 'System', 'paid-memberships-pro' );
		$author_name  = trim( str_replace( array( ' by ', ': ' ), ' ', $author_name ) );
		if ( '' === $author_name ) {
			$author_name = __( 'Unknown', 'paid-memberships-pro' );
		}

		$date_display = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
		$entry        = sprintf( '%1$s by %2$s: %3$s', $date_display, $author_name, $note );

		$existing = (string) get_user_meta( $user_id, 'user_notes', true );
		$new      = '' === trim( $existing ) ? $entry : ( rtrim( $existing ) . "\n\n" . $entry );

		update_user_meta( $user_id, 'user_notes', $new );
		return true;
	}

	/**
	 * Remove the note at `$index` (its position in the stored string).
	 *
	 * @param int $user_id
	 * @param int $index
	 * @return bool
	 */
	protected static function delete_note( $user_id, $index ) {
		$user_id = (int) $user_id;
		$index   = (int) $index;
		if ( $user_id <= 0 || $index < 0 ) {
			return false;
		}

		$notes = self::parse_notes( $user_id );
		if ( ! isset( $notes[ $index ] ) ) {
			return false;
		}

		$kept_strings = array();
		foreach ( $notes as $n ) {
			if ( $n['index'] === $index ) {
				continue;
			}
			if ( ! empty( $n['legacy'] ) ) {
				$kept_strings[] = $n['note'];
			} else {
				$kept_strings[] = sprintf( '%1$s by %2$s: %3$s', $n['date'], $n['author'], $n['note'] );
			}
		}
		update_user_meta( $user_id, 'user_notes', implode( "\n\n", $kept_strings ) );
		return true;
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
		$user = self::get_user();
		if ( empty( $user->ID ) ) {
			return;
		}

		// Reverse so newest is shown first while keeping each note's `index`
		// aligned with its position in the stored string for delete.
		$notes = array_reverse( self::parse_notes( $user->ID ) );
		?>
		<p class="description">
			<?php esc_html_e( 'Member notes are private and only visible to other users with membership management capabilities.', 'paid-memberships-pro' ); ?>
		</p>

		<?php if ( empty( $notes ) ) { ?>
			<p id="pmpro_member_notes_empty"><?php esc_html_e( 'No notes for this member.', 'paid-memberships-pro' ); ?></p>
		<?php } else { ?>
			<ul class="pmpro_member_notes_list">
				<?php foreach ( $notes as $note ) {
					$is_legacy = ! empty( $note['legacy'] );
					?>
					<li class="pmpro_member_note">
						<div class="pmpro_member_note-meta">
							<span class="pmpro_member_note-meta-text">
								<?php
								if ( $is_legacy ) {
									esc_html_e( 'Legacy note', 'paid-memberships-pro' );
								} else {
									echo esc_html(
										sprintf(
											/* translators: 1: date, 2: author name */
											__( '%1$s by %2$s', 'paid-memberships-pro' ),
											$note['date'],
											$note['author']
										)
									);
								}
								?>
							</span>
							<button
								type="submit"
								name="pmpro_delete_member_note"
								value="<?php echo esc_attr( (int) $note['index'] ); ?>"
								class="button-link pmpro_member_note-delete pmpro-has-icon pmpro-has-icon-trash"
								title="<?php esc_attr_e( 'Delete this note', 'paid-memberships-pro' ); ?>"
								onclick="return confirm(<?php echo wp_json_encode( __( 'Are you sure you want to delete this note? This cannot be undone.', 'paid-memberships-pro' ) ); ?>);"
							><?php esc_html_e( 'Trash', 'paid-memberships-pro' ); ?></button>
						</div>
						<div class="pmpro_member_note-body"><?php echo wp_kses_post( nl2br( (string) $note['note'] ) ); ?></div>
					</li>
				<?php } ?>
			</ul>
		<?php } ?>

		<button id="pmpro_add_member_note" class="button button-secondary pmpro-has-icon pmpro-has-icon-plus" type="button">
			<?php esc_html_e( 'Add Member Note', 'paid-memberships-pro' ); ?>
		</button>
		<div class="pmpro_add_member_note_form" style="display:none;">
			<hr />
			<p><strong><label for="pmpro_member_note_text"><?php esc_html_e( 'Member Note', 'paid-memberships-pro' ); ?></label></strong></p>
			<p><textarea id="pmpro_member_note_text" name="pmpro_member_note_text" rows="4" cols="60"></textarea></p>
			<button type="submit" name="pmpro_add_member_note_submit" value="1" class="button button-primary"><?php esc_html_e( 'Save Note', 'paid-memberships-pro' ); ?></button>
			<button id="pmpro_cancel_add_member_note" class="button button-cancel" type="button"><?php esc_html_e( 'Cancel', 'paid-memberships-pro' ); ?></button>
		</div>

		<style>
			.pmpro_member_notes_list { list-style: none; margin: 0 0 1em 0; padding: 0; }
			.pmpro_member_note { background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 3px; padding: 10px 12px; margin-bottom: 8px; }
			.pmpro_member_note-meta { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #50575e; margin-bottom: 6px; }
			.pmpro_member_note-body { white-space: pre-wrap; }
			.pmpro_member_note-delete { color: #b32d2e; text-decoration: none; }
			.pmpro_member_note-delete:hover { color: #d63638; }
		</style>
		<script>
			jQuery(document).ready(function($) {
				$('#pmpro_add_member_note').on('click', function() {
					$('.pmpro_add_member_note_form').show();
					$('#pmpro_member_note_text').focus();
					$('#pmpro_add_member_note').hide();
				});
				$('#pmpro_cancel_add_member_note').on('click', function() {
					$('.pmpro_add_member_note_form').hide();
					$('#pmpro_member_note_text').val('');
					$('#pmpro_add_member_note').show();
				});
			});
		</script>
		<?php
	}

	/**
	 * Save panel actions: add a new note or delete an existing note.
	 */
	public function save() {
		if ( ! current_user_can( pmpro_get_edit_member_capability() ) ) {
			return;
		}

		$user = self::get_user();
		if ( empty( $user->ID ) ) {
			return;
		}

		// Delete a note. The hidden value is the note's index in the stored string.
		if ( isset( $_POST['pmpro_delete_member_note'] ) && '' !== $_POST['pmpro_delete_member_note'] ) {
			self::delete_note( $user->ID, (int) $_POST['pmpro_delete_member_note'] );

			wp_safe_redirect( add_query_arg( array(
				'page'                    => 'pmpro-member',
				'user_id'                 => $user->ID,
				'pmpro_member_edit_panel' => $this->slug,
				'member_notes_action'     => 'deleted',
			), admin_url( 'admin.php' ) ) );
			exit;
		}

		// Add a note.
		if ( ! empty( $_POST['pmpro_add_member_note_submit'] ) ) {
			$note_text = isset( $_POST['pmpro_member_note_text'] ) ? wp_unslash( $_POST['pmpro_member_note_text'] ) : '';
			self::add_note( $user->ID, $note_text );

			wp_safe_redirect( add_query_arg( array(
				'page'                    => 'pmpro-member',
				'user_id'                 => $user->ID,
				'pmpro_member_edit_panel' => $this->slug,
				'member_notes_action'     => 'added',
			), admin_url( 'admin.php' ) ) );
			exit;
		}
	}
}
