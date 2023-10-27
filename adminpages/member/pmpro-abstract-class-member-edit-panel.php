<?php
abstract class PMPro_Member_Edit_Panel {
	abstract public function get_title( $user_id );
	abstract public function display( $user_id );

	public function get_title_link( $user_id ) {}
	public function get_submit_text( $user_id ) {}
	public function save( $user_id ) {}
}
