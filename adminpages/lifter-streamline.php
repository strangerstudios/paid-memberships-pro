<?php
    if( !function_exists("current_user_can") || (!current_user_can("manage_options"))) {
        die(__("You do not have permissions to perform this action.", 'paid-memberships-pro' ));
    }   
?>

<style>
#llms-setup-wizard {
    background-color: #f0f0f1;
    height: 100%;
    left: 0;
    overflow: scroll;
    position: fixed;
    top: 0;
    width: 100%;
}
.llms-setup-wrapper {
    margin: 30px auto;
    max-width: 640px;
}
#llms-logo {
    text-align: center;
}
#llms-logo a {
    display: inline-block;
}
#llms-logo a {
    display: inline-block;
}

.llms-setup-progress {
    display: flex;
    margin: 20px 0;
}
.llms-setup-progress li {
    border-bottom: 4px solid #2295ff;
    display: inline-block;
    font-size: 14px;
    padding-bottom: 10px;
    position: relative;
    text-align: center;
    -webkit-box-flex: 1;
    -ms-flex: 1;
    flex: 1;
}
.llms-setup-progress li.current:after {
    background: #fff;
}

.llms-setup-progress li.current {
    font-weight: 700;
}
.llms-setup-progress li a {
    color: #2295ff;
    text-decoration: none;
}


.llms-setup-progress li:after {
    background: #2295ff;
    bottom: 0;
    content: "";
    border: 4px solid #2295ff;
    border-radius: 100%;
    height: 4px;
    position: absolute;
    left: 50%;
    margin-left: -6px;
    margin-bottom: -8px;
    width: 4px;
}
.llms-setup-progress li.current~li {
    border-bottom-color: #ccc;
}
.llms-setup-progress li.current~li:after {
    background: #ccc;
    border-color: #ccc;
}
.llms-setup-content {
    background-color: #fff;
    -webkit-box-shadow: 0 1px 3px rgba(0,0,0,.13);
    box-shadow: 0 1px 3px rgba(0,0,0,.13);
    padding: 30px;
}
.llms-setup-content h1 {
    color: #3c434a;
}

.llms-setup-content p, .llms-setup-content li {
    color: #3c434a;
    font-size: 16px;
    line-height: 1.5;
    margin: 1em 0;
}
.llms-setup-content .llms-setup-actions {
    margin-top: 40px;
    text-align: right;
}
.llms-button-action, .llms-button-danger, .llms-button-primary, .llms-button-secondary {
    border: none;
    border-radius: 8px;
    color: #fefefe;
    cursor: pointer;
    font-weight: 700;
    text-decoration: none;
    text-shadow: none;
    margin: 0;
    max-width: 100%;
    position: relative;
    -webkit-transition: all .5s ease;
    transition: all .5s ease;
    background: #e1e1e1;
    color: #414141;
    font-size: 18px;
    line-height: 1.2;
    padding: 16px 32px;
}

.llms-button-action.large, .llms-button-danger.large, .llms-button-primary.large, .llms-button-secondary.large {
    font-size: 18px;
    line-height: 1.2;
    padding: 16px 32px;
}
#llms-logo a img {
    max-width: 300px;
}
input[type="checkbox"] {
transform: scale(1.5);
}
label {
    font-size: 16px;
    font-weight: 700;
}
</style>
<div class="llms-setup-wrapper">

<h1 id="llms-logo">
    <a href="https://lifterlms.com/blog/pmpro-partnership/" target="_blank">
        <img src="<?php echo esc_url( PMPRO_URL .  '/images/lifter-streamline.png' ); ?>" alt="PMProLifterLMS">
    </a>
</h1>

<ul class="llms-setup-progress">
        <li class="current">
            <a href="http://wp-core.dvl.to/wp-admin/?page=llms-setup&amp;step=intro">
                <?php esc_html_e( 'Welcome!', 'paid-memberships-pro' );?>
        </li>
        <li>
            <a href="http://wp-core.dvl.to/wp-admin/?page=llms-setup&amp;step=pages">
                <?php esc_html_e( 'Page Setup', 'paid-memberships-pro' );?></a>
        </li>
        <li>
            <a href="http://wp-core.dvl.to/wp-admin/?page=llms-setup&amp;step=payments">
                <?php esc_html_e( 'Payments', 'paid-memberships-pro' );?></a>
        </li>
        <li>
            <a href="http://wp-core.dvl.to/wp-admin/?page=llms-setup&amp;step=coupon">
                <?php esc_html_e( 'Coupon', 'paid-memberships-pro' );?></a>
        </li>
        <li>
            <a href="http://wp-core.dvl.to/wp-admin/?page=llms-setup&amp;step=finish">
                <?php esc_html_e( 'Finish!', 'paid-memberships-pro' );?></a>
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
            <a href="http://wp-core.dvl.to/wp-admin/" class="llms-button-secondary large"><?php esc_html_e( 'Skip setup' ) ?></a>
            <a href="http://wp-core.dvl.to/wp-admin/?page=llms-setup&amp;step=pages" class="llms-button-primary large"><?php esc_html_e( 'Get Started Now' ) ?></a>
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

