<?php
/**
 * Enqueue frontend JavaScript and CSS
 */
function pmpro_enqueue_scripts() {
    global $pmpro_pages;
    
    // Frontend styles.
    $frontend_css_rtl = false;
    if(file_exists(get_stylesheet_directory() . "/paid-memberships-pro/css/frontend.css")) {
        $frontend_css = get_stylesheet_directory_uri() . "/paid-memberships-pro/css/frontend.css";
        if( is_rtl() && file_exists(get_stylesheet_directory() . "/paid-memberships-pro/css/frontend-rtl.css") ) {
            $frontend_css_rtl = get_stylesheet_directory_uri() . "/paid-memberships-pro/css/frontend-rtl.css";
        }
    } elseif(file_exists(get_template_directory() . "/paid-memberships-pro/frontend.css")) {
        $frontend_css = get_template_directory_uri() . "/paid-memberships-pro/frontend.css";
        if( is_rtl() && file_exists(get_template_directory() . "/paid-memberships-pro/css/frontend-rtl.css") ) {
            $frontend_css_rtl = get_template_directory_uri() . "/paid-memberships-pro/css/frontend-rtl.css";
        }
    } else {
        $frontend_css = plugins_url('css/frontend.css',dirname(__FILE__) );
        if( is_rtl() ) {
            $frontend_css_rtl = plugins_url('css/frontend-rtl.css',dirname(__FILE__) );
        }
    }
    wp_enqueue_style('pmpro_frontend', $frontend_css, array(), PMPRO_VERSION, "screen");
    if( $frontend_css_rtl ) {
        wp_enqueue_style('pmpro_frontend_rtl', $frontend_css_rtl, array(), PMPRO_VERSION, "screen");
    }

    // Print styles.
    if(file_exists(get_stylesheet_directory() . "/paid-memberships-pro/css/print.css"))
        $print_css = get_stylesheet_directory_uri() . "/paid-memberships-pro/css/print.css";
    elseif(file_exists(get_template_directory() . "/paid-memberships-pro/print.css"))
        $print_css = get_template_directory_uri() . "/paid-memberships-pro/print.css";
    else
        $print_css = plugins_url('css/print.css',dirname(__FILE__) );
    wp_enqueue_style('pmpro_print', $print_css, array(), PMPRO_VERSION, "print");
    
    // Checkout page JS
    if ( pmpro_is_checkout() ) {
        wp_register_script( 'pmpro_checkout',
                            plugins_url( 'js/pmpro-checkout.js', dirname(__FILE__) ),
                            array( 'jquery' ),
                            PMPRO_VERSION );

        wp_localize_script( 'pmpro_checkout', 'pmpro', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'ajax_timeout' => apply_filters( 'pmpro_ajax_timeout', 5000, 'applydiscountcode' ),
            'show_discount_code' => pmpro_show_discount_code(),
			'discount_code_passed_in' => !empty( $_REQUEST['discount_code'] ),
        ));
        wp_enqueue_script( 'pmpro_checkout' );
    }
    
    // Change Password page JS 
	$is_change_pass_page = ! empty( $pmpro_pages['member_profile_edit'] )
							&& is_page( $pmpro_pages['member_profile_edit'] )
							&& ! empty( $_REQUEST['view'] )
							&& $_REQUEST['view'] === 'change-password';
	$is_reset_pass_page = ! empty( $pmpro_pages['login'] )
							&& is_page( $pmpro_pages['login'] )
							&& ! empty( $_REQUEST['action'] )
							&& $_REQUEST['action'] === 'rp';
		
	if ( $is_change_pass_page || $is_reset_pass_page ) {
        wp_register_script( 'pmpro_login',
                            plugins_url( 'js/pmpro-login.js', dirname(__FILE__) ),
                            array( 'jquery', 'password-strength-meter' ),
                            PMPRO_VERSION );

        /**
         * Filter to allow weak passwords on the 
         * change password and reset password forms.
         * At this time, this only disables the JS check on the frontend.
         * There is no backend check for weak passwords on those forms.
         * 
         * @since 2.3.3
         *
         * @param bool $allow_weak_passwords    Whether to allow weak passwords.
         */
        $allow_weak_passwords = apply_filters( 'pmpro_allow_weak_passwords', false );

        wp_localize_script( 'pmpro_login', 'pmpro', array(
            'pmpro_login_page' => 'changepassword',
			'strength_indicator_text' => __( 'Strength Indicator', 'paid-memberships-pro' ),
            'allow_weak_passwords' => $allow_weak_passwords ) );
        wp_enqueue_script( 'pmpro_login' );	
    }
}
add_action( 'wp_enqueue_scripts', 'pmpro_enqueue_scripts' );

/**
 * Enqueue admin JavaScript and CSS
 */
function pmpro_admin_enqueue_scripts() {
    // Admin JS
    wp_register_script( 'pmpro_admin',
                        plugins_url( 'js/pmpro-admin.js', dirname(__FILE__) ),
                        array( 'jquery', 'jquery-ui-sortable' ),
                        PMPRO_VERSION );
    $all_levels = pmpro_getAllLevels( true, true );
    $all_level_values_and_labels = array();
    foreach( $all_levels as $level ) {
        $all_level_values_and_labels[] = array( 'value' => $level->id, 'label' => $level->name );
    }
    wp_localize_script( 'pmpro_admin', 'pmpro', array(
        'all_levels' => $all_levels,
        'all_level_values_and_labels' => $all_level_values_and_labels
    ));
    wp_enqueue_script( 'pmpro_admin' );

    // Admin CSS
    $admin_css_rtl = false;
    if(file_exists(get_stylesheet_directory() . "/paid-memberships-pro/css/admin.css")) {
        $admin_css = get_stylesheet_directory_uri() . "/paid-memberships-pro/css/admin.css";
        if( is_rtl() && file_exists(get_stylesheet_directory() . "/paid-memberships-pro/css/admin-rtl.css") ) {
            $admin_css_rtl = get_stylesheet_directory_uri() . "/paid-memberships-pro/css/admin-rtl.css";
        }
    } elseif(file_exists(get_template_directory() . "/paid-memberships-pro/admin.css")) {
        $admin_css = get_template_directory_uri() . "/paid-memberships-pro/admin.css";
        if( is_rtl() && file_exists(get_template_directory() . "/paid-memberships-pro/css/admin-rtl.css") ) {
            $admin_css_rtl = get_template_directory_uri() . "/paid-memberships-pro/css/admin-rtl.css";
        }
    } else {
        $admin_css = plugins_url('css/admin.css',dirname(__FILE__) );
        if( is_rtl() ) {
            $admin_css_rtl = plugins_url('css/admin-rtl.css',dirname(__FILE__) );
        }
    }
    wp_enqueue_style('pmpro_admin', $admin_css, array(), PMPRO_VERSION, "screen");
    if( $admin_css_rtl ) {
        wp_enqueue_style('pmpro_admin_rtl', $admin_css_rtl, array(), PMPRO_VERSION, "screen");
    }
}
add_action( 'admin_enqueue_scripts', 'pmpro_admin_enqueue_scripts' );