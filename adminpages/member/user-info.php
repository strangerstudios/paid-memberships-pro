<div class="pmpro_section">
<div id="pmpro-user-info-panel" role="tabpanel" tabindex="0" aria-labelledby="tab-1">
    <h2>
        <?php esc_html_e( 'User Info', 'paid-memberships-pro' ); ?>
        <a href="<?php echo esc_url( add_query_arg( array( 'user_id' => intval( $user_id ) ), admin_url( 'user-edit.php' ) ) ); ?>" target="_blank" class="page-title-action pmpro-has-icon pmpro-has-icon-admin-users"><?php esc_html_e( 'Edit User', 'paid-memberships-pro' ); ?></a>
    </h2>
    <table class="form-table">
        <tr>
            <th><label for="user_login"><?php esc_html_e( 'Username (required)', 'paid-memberships-pro' ); ?></label></th>
            <td><input type="text" name="user_login" id="user_login" autocapitalize="none" autocorrect="off" autocomplete="off" required <?php if ( ! empty( $_REQUEST['user_id'] ) ) { ?>readonly="true"<?php } ?> value="<?php echo esc_attr( $user_login ) ?>"></td>
        </tr>
        <tr>
            <th><label for="email"><?php esc_html_e( 'Email (required)', 'paid-memberships-pro' ); ?></label></th>
            <td><input type="email" name="email" id="email" autocomplete="new-password" spellcheck="false" required value="<?php echo esc_attr( $user_email ) ?>"></td>
        </tr>
        <tr>
            <th><label for="first_name"><?php esc_html_e( 'First Name', 'paid-memberships-pro' ); ?></label></th>
            <td><input type="text" name="first_name" id="first_name" autocomplete="off" value="<?php echo $first_name ?>"></td>
        </tr>
        <tr>
            <th><label for="last_name"><?php esc_html_e( 'Last Name', 'paid-memberships-pro' ); ?></label></th>
            <td><input type="text" name="last_name" id="last_name" autocomplete="off" value="<?php echo $last_name ?>"></td>
        </tr>						
        <?php
        // Only show for new users.
        if ( empty( $_REQUEST['user_id'] ) ) {
        ?>
        <tr>
            <th><label for="password"><?php esc_html_e( 'Password', 'paid-memberships-pro' ); ?></label></th>
            <td>
                <input type="password" name="password" id="password" autocomplete="off" required value="">
                <button class="toggle-pass-visibility" aria-controls="password" aria-expanded="false"><span class="dashicons dashicons-visibility toggle-pass-visibility"></span></button>
            </td>
        </tr>
        <tr>
            <th><label for="send_password">Send User Notification</label></th>
            <td><input type="checkbox" name="send_password" id="send_password">
            <label for="send_password">Send the new user an email about their account.</label>
            </td>
        </tr>
        <?php
        }
        ?>
        <?php if ( ! IS_PROFILE_PAGE && current_user_can( 'promote_user', $user->ID ) ) { ?>
        <tr>
            <th><label for="role"><?php esc_html_e( 'Role', 'paid-memberships-pro' ); ?></label></th>
            <td>
                <select name="role" id="role" class="<?php echo pmpro_getClassForField( 'role' ); ?>">
                    <?php wp_dropdown_roles( $role ); ?>
                </select>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>
