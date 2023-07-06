<?php

defined( 'ABSPATH' ) || exit;
?>
<div class="llms-setup-wrapper">

    <h1 id="llms-logo">
        <a href="https://lifterlms.com/blog/pmpro-partnership/" target="_blank">
            <img src="<?php echo esc_url( PMPRO_URL .  '/images/lifter-streamline.png' ); ?>" alt="PMProLifterLMS">
        </a>
    </h1>

    <ul class="llms-setup-progress">
            <li >
                <a href="http://wp-core.dvl.to/wp-admin/?page=llms-setup&amp;step=intro">
                    <?php esc_html_e( 'Welcome!', 'paid-memberships-pro' );?>
            </li>
            <li class="current">
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

        <h1><?php _e( 'Page Setup', 'lifterlms' ); ?></h1>

        <p><?php _e( 'LifterLMS has a few essential pages. The following will be created automatically if they don\'t already exist.', 'lifterlms' ); ?>

        <table>
            <tr>
                <td><a href="https://lifterlms.com/docs/course-catalog/?utm_source=LifterLMS%20Plugin&utm_campaign=Plugin%20to%20Sale&utm_medium=Wizard&utm_content=LifterLMS%20Course%20Catalog" target="_blank"><?php _e( 'Course Catalog', 'lifterlms' ); ?></a></td>
                <td><p><?php _e( 'This page is where your visitors will find a list of all your available courses.', 'lifterlms' ); ?></p></td>
            </tr>
            <tr>
                <td><a href="https://lifterlms.com/docs/student-dashboard/?utm_source=LifterLMS%20Plugin&utm_campaign=Plugin%20to%20Sale&utm_medium=Wizard&utm_content=LifterLMS%20Student%20Dashboard" target="_blank"><?php _e( 'Student Dashboard', 'lifterlms' ); ?></a></td>
                <td><p><?php _e( 'Page where students can view and manage their current enrollments, earned certificates and achievements, account information, and purchase history.', 'lifterlms' ); ?></p></td>
            </tr>
        </table>

        <p><?php printf( __( 'After setup, you can manage these pages from the admin dashboard on the %1$sPages screen%2$s and you can control which pages display on your menu(s) via %3$sAppearance > Menus%4$s.', 'lifterlms' ), '<a href="' . esc_url( admin_url( 'edit.php?post_type=page' ) ) . '" target="_blank">', '</a>', '<a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '" target="_blank">', '</a>' ); ?></p>
        <p class="llms-setup-actions">
					
        <a class="llms-exit-setup" data-confirm="The site setup is incomplete! Are you sure you wish to exit?" href="http://wp-core.dvl.to/wp-admin/admin.php?page=llms-settings">Exit Setup</a>
        <a href="?page=llms-setup&step=payments" class="llms-button-secondary large">Skip this step</a>
        <a class="llms-button-primary large" href="?page=llms-setup&step=payments" id="llms-setup-submit">Save & Continue</a>
        </p>

    </div>
</div>