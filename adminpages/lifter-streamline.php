<?php
    if( !function_exists("current_user_can") || (!current_user_can("manage_options"))) {
        die(__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
    }   
?>
<div class="llms-setup-wrapper">

<h1 id="llms-logo">
    <a href="https://lifterlms.com/blog/pmpro-partnership/" target="_blank">
        <img src="<?php echo esc_url( PMPRO_URL .  '/images/lifter-streamline.png' ); ?>" alt="PMProLifterLMS">
    </a>
</h1>

<ul class="llms-setup-progress">
        <li class="current">
            <a href="<?php echo esc_url( admin_url() . "/admin.php?page=pmpro-lifter-streamline" ) ?>">
                <?php esc_html_e( 'Welcome!', 'paid-memberships-pro' );?>
            </a>
        </li>
        <li>
            <a href="<?php echo esc_url( admin_url() . "/admin.php?page=pmpro-lifter-streamline-step-pages" ) ?>">
                <?php esc_html_e( 'Page Setup', 'paid-memberships-pro' );?>
            </a>
        </li>
        <li>
            <a href="<?php echo esc_url( admin_url() . "?page=llms-setup&amp;step=payments" ) ?>">
                <?php esc_html_e( 'Payments', 'paid-memberships-pro' );?>
            </a>
        </li>
        <li>
            <a href="<?php echo esc_url( admin_url() . "?page=llms-setup&amp;step=coupon" ) ?>">
                <?php esc_html_e( 'Coupon', 'paid-memberships-pro' );?>
            </a>
        </li>
        <li>
            <a href="<?php echo esc_url( admin_url() . "?page=llms-setup&amp;step=finish" ) ?>">
                <?php esc_html_e( 'Finish!', 'paid-memberships-pro' );?>
            </a>
        </li>
</ul>

<div class="llms-setup-content">
    <form action="" method="POST">

        
<h1><?php esc_html_e( 'Welcome to LifterLMS!', 'paid-memberships-pro') ?> </h1>
<p><?php esc_html_e( 'Thanks for choosing LifterLMS to power your online courses! This short setup wizard will guide you through the basic settings and configure LifterLMS so you can get started creating courses faster!', 'paid-memberships-pro' )  ?></p>
<p><?php esc_html_e( 'It will only take a few minutes and it is completely optional. If you don\'t have the time now, come back later.', 'paid-memberships-pro' )  ?></p>
<p><?php esc_html_e( 'Since you already have Paid Memberships Pro installed, you can enable a "streamlined" version of LifterLMS that will let PMPro handle all checkouts, memberships, restrictions, and user fields.', 'paid-memberships-pro' ) ?> </p>

<label for="lifter-streamline">
    <input type="checkbox" name="lifter-streamline" id="lifter-streamline" checked="checked">
    <?php esc_html_e( 'Enable streamlined version of LifterLMS', 'paid-memberships-pro' ) ?>
</label>

        <p class="llms-setup-actions">
            <a href="<?php echo esc_url( admin_url() ); ?>" class="llms-button-secondary large"><?php esc_html_e( 'Skip setup' ) ?></a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=llms-setup&step=pages' ) ); ?>" class="llms-button-primary large"><?php esc_html_e( 'Get Started Now' ) ?></a>
        </p>

    </form>
</div>

</div>

<script>
jQuery(document).ready(function($) {
    $('#lifter-streamline').on('change', function() {
    $.ajax({
      method: "POST",
      data:{
        action:'toggle_streamline',
        status: $(this).is(':checked')
        },
        url: location.origin +"/wp-admin/admin-ajax.php",
        success:function(result){
            console.log(result);
        },
        error:function(error){
            console.log(error);
        }
    });
  });
});
</script>

