<?php
//only admins can get this
if (!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_pagesettings"))) {
    die(__("You do not have permissions to perform this action.", "pmpro"));
}

global $wpdb, $msg, $msgt;

//get/set settings
global $pmpro_pages;

/**
 * Adds additional page settings for use with add-on plugins, etc.
 *
 * @param array $pages {
 *     Formatted as array($name => $label)
 *
 *     @type string $name Page name. (Letters, numbers, and underscores only.)
 *     @type string $label Settings label.
 * }
 * @since 1.8.5
 */
$extra_pages = apply_filters('pmpro_extra_page_settings', array());

if (!empty($_REQUEST['savesettings'])) {
    //page ids
    pmpro_setOption("account_page_id");
    pmpro_setOption("billing_page_id");
    pmpro_setOption("cancel_page_id");
    pmpro_setOption("checkout_page_id");
    pmpro_setOption("confirmation_page_id");
    pmpro_setOption("invoice_page_id");
    pmpro_setOption("levels_page_id");

    //update the pages array
    $pmpro_pages["account"] = pmpro_getOption("account_page_id");
    $pmpro_pages["billing"] = pmpro_getOption("billing_page_id");
    $pmpro_pages["cancel"] = pmpro_getOption("cancel_page_id");
    $pmpro_pages["checkout"] = pmpro_getOption("checkout_page_id");
    $pmpro_pages["confirmation"] = pmpro_getOption("confirmation_page_id");
    $pmpro_pages["invoice"] = pmpro_getOption("invoice_page_id");
    $pmpro_pages["levels"] = pmpro_getOption("levels_page_id");

    //save additional pages
    if (!empty($extra_pages)) {
        foreach ($extra_pages as $name => $label) {
            pmpro_setOption($name . '_page_id');
            $pmpro_pages[$name] = pmpro_getOption($name . '_page_id');
        }
    }

    //assume success
    $msg = true;
    $msgt = __("Your page settings have been updated.", "pmpro");
}

//are we generating pages?
if (!empty($_REQUEST['createpages'])) {

    $pages = array();

    if(empty($_REQUEST['page_name'])) {
        //default pages
        $pages['account'] = __('Membership Account', 'pmpro');
        $pages['billing'] = __('Membership Billing', 'pmpro');
        $pages['cancel'] = __('Membership Cancel', 'pmpro');
        $pages['checkout'] = __('Membership Checkout', 'pmpro');
        $pages['confirmation'] = __('Membership Confirmation', 'pmpro');
        $pages['invoice'] = __('Membership Invoice', 'pmpro');
        $pages['levels'] = __('Membership Levels', 'pmpro');

    } else {
        //generate extra pages one at a time
        $pmpro_page_name = $_REQUEST['page_name'];
        $pmpro_page_id = $pmpro_pages[$pmpro_page_name];
        $pages[$pmpro_page_name] = $extra_pages[$pmpro_page_name];
    }

    $pages_created = pmpro_generatePages($pages);

    if (!empty($pages_created)) {
        $msg = true;
        $msgt = __("The following pages have been created for you", "pmpro") . ": " . implode(", ", $pages_created) . ".";
    }
}

require_once(dirname(__FILE__) . "/admin_header.php");
?>


    <form action="" method="post" enctype="multipart/form-data">
        <h2><?php _e('Pages', 'pmpro'); ?></h2>
        <?php
        global $pmpro_pages_ready;
        if ($pmpro_pages_ready) {
            ?>
            <p><?php _e('Manage the WordPress pages assigned to each required Paid Memberships Pro page.', 'pmpro'); ?></p>
            <?php
        } else {
            ?>
            <p><?php _e('Assign the WordPress pages for each required Paid Memberships Pro page or', 'pmpro'); ?> <a
                    href="?page=pmpro-pagesettings&createpages=1"><?php _e('click here to let us generate them for you', 'pmpro'); ?></a>.
            </p>
            <?php
        }
        ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row" valign="top">
                    <label for="account_page_id"><?php _e('Account Page', 'pmpro'); ?>:</label>
                </th>
                <td>
                    <?php
                    wp_dropdown_pages(array("name" => "account_page_id", "show_option_none" => "-- " . __('Choose One', 'pmpro') . " --", "selected" => $pmpro_pages['account']));
                    ?>
                    <?php if (!empty($pmpro_pages['account'])) { ?>
                        <a target="_blank" href="post.php?post=<?php echo $pmpro_pages['account']; ?>&action=edit"
                           class="button button-secondary pmpro_page_edit"><?php _e('edit page', 'pmpro'); ?></a>
                        &nbsp;
                        <a target="_blank" href="<?php echo get_permalink($pmpro_pages['account']); ?>"
                           class="button button-secondary pmpro_page_view"><?php _e('view page', 'pmpro'); ?></a>
                    <?php } ?>
                    <br/>
                    <small class="pmpro_lite"><?php _e('Include the shortcode', 'pmpro'); ?> [pmpro_account].</small>
                </td>
            <tr>
                <th scope="row" valign="top">
                    <label for="billing_page_id"><?php _e('Billing Information Page', 'pmpro'); ?>:</label>
                </th>
                <td>
                    <?php
                    wp_dropdown_pages(array("name" => "billing_page_id", "show_option_none" => "-- " . __('Choose One', 'pmpro') . " --", "selected" => $pmpro_pages['billing']));
                    ?>
                    <?php if (!empty($pmpro_pages['billing'])) { ?>
                        <a target="_blank" href="post.php?post=<?php echo $pmpro_pages['billing'] ?>&action=edit"
                           class="button button-secondary pmpro_page_edit"><?php _e('edit page', 'pmpro'); ?></a>
                        &nbsp;
                        <a target="_blank" href="<?php echo get_permalink($pmpro_pages['billing']); ?>"
                           class="button button-secondary pmpro_page_view"><?php _e('view page', 'pmpro'); ?></a>
                    <?php } ?>
                    <br/>
                    <small class="pmpro_lite"><?php _e('Include the shortcode', 'pmpro'); ?> [pmpro_billing].</small>
                </td>
            <tr>
                <th scope="row" valign="top">
                    <label for="cancel_page_id"><?php _e('Cancel Page', 'pmpro'); ?>:</label>
                </th>
                <td>
                    <?php
                    wp_dropdown_pages(array("name" => "cancel_page_id", "show_option_none" => "-- " . __('Choose One', 'pmpro') . " --", "selected" => $pmpro_pages['cancel']));
                    ?>
                    <?php if (!empty($pmpro_pages['cancel'])) { ?>
                        <a target="_blank" href="post.php?post=<?php echo $pmpro_pages['cancel'] ?>&action=edit"
                           class="button button-secondary pmpro_page_edit"><?php _e('edit page', 'pmpro'); ?></a>
                        &nbsp;
                        <a target="_blank" href="<?php echo get_permalink($pmpro_pages['cancel']); ?>"
                           class="button button-secondary pmpro_page_view"><?php _e('view page', 'pmpro'); ?></a>
                    <?php } ?>
                    <br/>
                    <small class="pmpro_lite"><?php _e('Include the shortcode', 'pmpro'); ?> [pmpro_cancel].</small>
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top">
                    <label for="checkout_page_id"><?php _e('Checkout Page', 'pmpro'); ?>:</label>
                </th>
                <td>
                    <?php
                    wp_dropdown_pages(array("name" => "checkout_page_id", "show_option_none" => "-- " . __('Choose One', 'pmpro') . " --", "selected" => $pmpro_pages['checkout']));
                    ?>
                    <?php if (!empty($pmpro_pages['checkout'])) { ?>
                        <a target="_blank" href="post.php?post=<?php echo $pmpro_pages['checkout'] ?>&action=edit"
                           class="button button-secondary pmpro_page_edit"><?php _e('edit page', 'pmpro'); ?></a>
                        &nbsp;
                        <a target="_blank" href="<?php echo get_permalink($pmpro_pages['checkout']); ?>"
                           class="button button-secondary pmpro_page_view"><?php _e('view page', 'pmpro'); ?></a>
                    <?php } ?>
                    <br/>
                    <small class="pmpro_lite"><?php _e('Include the shortcode', 'pmpro'); ?> [pmpro_checkout].</small>
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top">
                    <label for="confirmation_page_id"><?php _e('Confirmation Page', 'pmpro'); ?>:</label>
                </th>
                <td>
                    <?php
                    wp_dropdown_pages(array("name" => "confirmation_page_id", "show_option_none" => "-- " . __('Choose One', 'pmpro') . " --", "selected" => $pmpro_pages['confirmation']));
                    ?>
                    <?php if (!empty($pmpro_pages['confirmation'])) { ?>
                        <a target="_blank" href="post.php?post=<?php echo $pmpro_pages['confirmation'] ?>&action=edit"
                           class="button button-secondary pmpro_page_edit"><?php _e('edit page', 'pmpro'); ?></a>
                        &nbsp;
                        <a target="_blank" href="<?php echo get_permalink($pmpro_pages['confirmation']); ?>"
                           class="button button-secondary pmpro_page_view"><?php _e('view page', 'pmpro'); ?></a>
                    <?php } ?>
                    <br/>
                    <small class="pmpro_lite"><?php _e('Include the shortcode', 'pmpro'); ?>[pmpro_confirmation].
                    </small>
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top">
                    <label for="invoice_page_id"><?php _e('Invoice Page', 'pmpro'); ?>:</label>
                </th>
                <td>
                    <?php
                    wp_dropdown_pages(array("name" => "invoice_page_id", "show_option_none" => "-- " . __('Choose One', 'pmpro') . " --", "selected" => $pmpro_pages['invoice']));
                    ?>
                    <?php if (!empty($pmpro_pages['invoice'])) { ?>
                        <a target="_blank" href="post.php?post=<?php echo $pmpro_pages['invoice'] ?>&action=edit"
                           class="button button-secondary pmpro_page_edit"><?php _e('edit page', 'pmpro'); ?></a>
                        &nbsp;
                        <a target="_blank" href="<?php echo get_permalink($pmpro_pages['invoice']); ?>"
                           class="button button-secondary pmpro_page_view"><?php _e('view page', 'pmpro'); ?></a>
                    <?php } ?>
                    <br/>
                    <small class="pmpro_lite"><?php _e('Include the shortcode', 'pmpro'); ?> [pmpro_invoice].</small>
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top">
                    <label for="levels_page_id"><?php _e('Levels Page', 'pmpro'); ?>:</label>
                </th>
                <td>
                    <?php
                    wp_dropdown_pages(array("name" => "levels_page_id", "show_option_none" => "-- " . __('Choose One', 'pmpro') . " --", "selected" => $pmpro_pages['levels']));
                    ?>
                    <?php if (!empty($pmpro_pages['levels'])) { ?>
                        <a target="_blank" href="post.php?post=<?php echo $pmpro_pages['levels'] ?>&action=edit"
                           class="button button-secondary pmpro_page_edit"><?php _e('edit page', 'pmpro'); ?></a>
                        &nbsp;
                        <a target="_blank" href="<?php echo get_permalink($pmpro_pages['levels']); ?>"
                           class="button button-secondary pmpro_page_view"><?php _e('view page', 'pmpro'); ?></a>
                    <?php } ?>
                    <br/>
                    <small class="pmpro_lite"><?php _e('Include the shortcode', 'pmpro'); ?> [pmpro_levels].</small>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
        if (!empty($extra_pages)) { ?>
            <h2><?php _e('Additional Page Settings', 'pmpro'); ?></h2>
            <table class="form-table">
                <tbody>
                <?php foreach ($extra_pages as $name => $page) { ?>
                    <?php
						if(is_array($page)) {
							$label = $page['title'];
							if(!empty($page['hint']))
								$hint = $page['hint'];
							else
								$hint = '';
						} else {
							$label = $page;
							$hint = '';
						}
					?>
					<tr>
                        <th scope="row" valign="top">
                            <label for="<?php echo $name; ?>_page_id"><?php echo $label; ?></label>
                        </th>
                        <td>
                            <?php wp_dropdown_pages(array(
                                "name" => $name . '_page_id',
                                "show_option_none" => "-- " . __('Choose One', 'pmpro') . " --",
                                "selected" => $pmpro_pages[$name],
                            ));
                            if(!empty($pmpro_pages[$name])) {
                                ?>
                                <a target="_blank" href="post.php?post=<?php echo $pmpro_pages[$name] ?>&action=edit"
                                   class="button button-secondary pmpro_page_edit"><?php _e('edit page', 'pmpro'); ?></a>
                                &nbsp;
                                <a target="_blank" href="<?php echo get_permalink($pmpro_pages[$name]); ?>"
                                   class="button button-secondary pmpro_page_view"><?php _e('view page', 'pmpro'); ?></a>
                            <?php } else { ?>
                                &nbsp;
                                <a href="?page=pmpro-pagesettings&createpages=1&page_name=<?php echo $name; ?>"><?php _e('Generate Page', 'pmpro'); ?></a>
                            <?php } ?>
							<?php if(!empty($hint)) { ?>
								<br/>
								<small class="pmpro_lite"><?php echo $hint;?></small>
							<?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>
        <p class="submit">
            <input name="savesettings" type="submit" class="button button-primary"
                   value="<?php _e('Save Settings', 'pmpro'); ?>"/>
        </p>
    </form>

<?php
require_once(dirname(__FILE__) . "/admin_footer.php");
?>