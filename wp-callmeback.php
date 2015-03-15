<?php
error_reporting(0);
ob_start();
/**
 * Plugin Name: Call me back widget
 * Plugin URI: http://pigeonhut.com
 * Description: Request call me back widget by PigeonHUT
 * Version: 1.20
 * Author: Jody Nesbitt (WebPlugins)
 * Author URI: http://webplugins.co.uk
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
if (!class_exists('Wpg_Callback_List_Table')) {
    require_once( plugin_dir_path(__FILE__) . 'class/class-wpg-callback-list-table.php' );
}
if (!class_exists('Wpg_Besttime_List_Table')) {
    require_once( plugin_dir_path(__FILE__) . 'class/class-wpg-besttime-list-table.php' );
}
if (!class_exists('Wpg_DropdownOptions_List_Table')) {
    require_once( plugin_dir_path(__FILE__) . 'class/class-wpg-dropdownoptions-list-table.php' );
}
if (!class_exists('ReCaptcha')) {
    require_once( plugin_dir_path(__FILE__) . 'class/recaptchalib.php' );
}
if (!class_exists('NMRichReviewsAdminHelper')) {
    require_once(plugin_dir_path(__FILE__) . 'class/admin-view-helper-functions.php');
}
// Register API keys at https://www.google.com/recaptcha/admin
$get_option_details = unserialize(get_option('rcb_settings_options'));
$siteKey = $get_option_details['site_key'];
$secret = $get_option_details['secret'];
// reCAPTCHA supported 40+ languages listed here: https://developers.google.com/recaptcha/docs/language
$lang = "en";

// The response from reCAPTCHA
$resp = null;
// The error code from reCAPTCHA, if any
$error = null;

$reCaptcha = new ReCaptcha($secret);

function initialize_table() {
    global $wpdb;
    $sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "request_a_call_back" . "` (
            `id` bigint(20) unsigned NOT NULL auto_increment,
            `name` varchar(255) default NULL,
            `number` varchar(255) default NULL,
            `postcode` bigint(20) default NULL,
            `email` varchar(255) default NULL,
            `besttime` varchar(255) DEFAULT NULL, 
            `options` varchar(255) DEFAULT NULL, 
            `message` varchar(255) DEFAULT NULL, 
            `dateCreated` timestamp NOT NULL,
            PRIMARY KEY (`id`))ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";
    $wpdb->query($sql);
    $sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "call_back_best_time" . "` (
            `id` bigint(20) unsigned NOT NULL auto_increment,
            `best_time` varchar(255) default NULL,                     
            `dateCreated` timestamp NOT NULL,
            PRIMARY KEY (`id`))ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";
    $wpdb->query($sql);
    $sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "drop_down_options" . "` (
            `id` bigint(20) unsigned NOT NULL auto_increment,
            `option` varchar(255) default NULL,                     
            `dateCreated` timestamp NOT NULL,
            PRIMARY KEY (`id`))ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";
    $wpdb->query($sql);
    add_menu_page(__('Call Back', 'rcb'), __('Call Back', 'rcb'), 'manage_options', 'request-call-back', 'callRequestCallBack', '');
    add_submenu_page('request-call-back', __('Settings and options', 'rcb'), __('Settings and options', 'rcb'), 'manage_options', 'settings-options', 'callSettings');
    add_submenu_page('request-call-back', __('Dropdown Options', 'rcb'), __('Dropdown Options', 'rcb'), 'manage_options', 'list-best-time', 'listBestTime');
    add_submenu_page('', __('Add Dropdown Options', 'rcb'), __('Add Dropdown Options', 'rcb'), 'manage_options', 'rcb-best-time', 'rcbBestTime');
    add_submenu_page('', __('Delete Dropdown Options', 'rcb'), __('Delete Dropdown Options', 'rcb'), 'manage_options', 'delete-best-time', 'rcbDeleteBestTime');

    add_submenu_page('request-call-back', __('Dropdown Options2', 'rcb'), __('Dropdown Options2', 'rcb'), 'manage_options', 'list-options-two', 'listOptionsTwo');
    add_submenu_page('', __('Add Dropdown Options2', 'rcb'), __('Add Dropdown Options2', 'rcb'), 'manage_options', 'rcb-options-two', 'rcbOptionsTwo');
    add_submenu_page('', __('Delete Dropdown Options2', 'rcb'), __('Delete Dropdown Options2', 'rcb'), 'manage_options', 'delete-options-two', 'rcbDeleteOptionsTwo');
}

function rcbTabs() {
    $pluginDirectory = trailingslashit(plugins_url(basename(dirname(__FILE__))));
    wp_register_style('wp-callmeback-css', $pluginDirectory . 'css/wp-callmeback.css');
    wp_enqueue_style('wp-callmeback-css');
    $my_plugin_tabs = array(
        'request-call-back' => 'Contact Requests',
        'settings-options' => 'Settings'
    );
    echo admin_tabs($my_plugin_tabs);
}

function wpgcallmeback_style() {
    // Register the style like this for a plugin:
    wp_register_style('wpgcallmeback-style', plugins_url('/style.css', __FILE__), array(), '20120208', 'all');
    // or
    // Register the style like this for a theme:
    wp_register_style('wpgcallmeback-style', get_template_directory_uri() . '/style.css', array(), '20120208', 'all');

    // For either a plugin or a theme, you can then enqueue the style:
    wp_enqueue_style('wpgcallmeback-style');
}

add_action('admin_menu', 'initialize_table');
add_action('wp_enqueue_scripts', 'wpgcallmeback_style');
add_action('admin_enqueue_scripts', 'adminCallBackScripts');
add_action('admin_post_submit-callback-settings-form', 'saveCallbackSettings');
add_action('admin_post_submit-besttime-form', 'saveBestTime');
add_action('admin_post_submit-dropdownoptions-form', 'saveDropdownOptions');
add_action('admin_post_submit-recaptcha-form', 'saveRecaptchaForm');

function adminCallBackScripts() {
    wp_register_style('wpgcallmeback-style', plugins_url('css/colpick.css', __FILE__), array(), '20120208', 'all');
    wp_enqueue_script('wpgcallmeback-style', plugins_url('js/colpick.js', __FILE__), array(), '1.0.0', true);
    wp_enqueue_style('wpgcallmeback-style');
}

function listOptionsTwo() {
    session_start();
    global $wpdb;
    ?>
    <style>        
        ﻿.alert-box {
            color:#555;
            border-radius:10px;
            font-family:Tahoma,Geneva,Arial,sans-serif;font-size:11px;
            padding:10px 36px;
            margin:10px;
        }
        .alert-box span {
            font-weight:bold;
            text-transform:uppercase;
        }
        .errormes {
            background:#ffecec no-repeat 10px 50%;
            border:1px solid #f5aca6;
            padding: 10px;
        }
        .success {
            background:#e9ffd9 no-repeat 10px 50%;
            border:1px solid #a6ca8a;
            padding: 10px;
        }
        .warning {
            background:#fff8c4 no-repeat 10px 50%;
            border:1px solid #f2c779;
            padding: 10px;
        }
        .notice {
            background:#e3f7fc  no-repeat 10px 50%;
            border:1px solid #8ed9f6;
            padding: 10px;
        }
    </style>
    <div class="wrap">                               
        <h2><?php _e('Dropdown Options 2', 'wpre'); ?> <a class="add-new-h2" href="<?php echo admin_url() ?>admin.php?page=rcb-options-two">Add New</a></h2> 
        <?php _statusMessage('Dropdown Options2'); ?>
        <div id="poststuff" class="metabox-holder ppw-settings">

            <div class="inside">  
                <form id="options" name="options" method="post" action="">
                    <input type="hidden" name="action" value="delete"/>
                    <?php
                    print_r($_REQUEST);
                    if ($_REQUEST['action'] == 'delete') {
                        $del = $_REQUEST['options'];
                        if ($del != '') {
                            $idsToDelete = implode($del, ',');
                            global $wpdb;
                            $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "drop_down_options WHERE id IN ($idsToDelete)"));
                            if ($wpdb->rows_affected > 0) {
                                $_SESSION['area_status'] = 'deletesuccess';
                                wp_redirect(admin_url('admin.php?page=settings-options&paged="' . $_GET['paged'] . '"'));
                                exit;
                            }
                        } else {
                            $_SESSION['area_status'] = 'deletefailed';
                            if ($_GET['paged'] != '') {
                                wp_redirect(admin_url('admin.php?page=settings-options&paged="' . $_GET['paged'] . '"'));
                            } else {
                                wp_redirect(admin_url('admin.php?page=settings-options'));
                            }
                        }
                    }

                    $myListTable = new Wpg_DropdownOptions_List_Table();
                    $myListTable->prepare_items();
                    $myListTable->display();
                    ?>
                </form>      
            </div>

        </div>
    </div><?php
}

function rcbOptionsTwo() {

    session_start();
    global $wpdb;
    if ($_REQUEST['action'] == 'edit' && $_REQUEST['options'] != '') {
        $getBestTime = '';
        $getDetails = $wpdb->get_row('SELECT * FROM  ' . $wpdb->prefix . 'drop_down_options WHERE id=' . $_REQUEST['options']);
        if ($getDetails != NULL) {
            $getId = $getDetails->id;
            $getBestTime = $getDetails->option;
        }
    }
    ?>
    <style>        
        ﻿.alert-box {
            color:#555;
            border-radius:10px;
            font-family:Tahoma,Geneva,Arial,sans-serif;font-size:11px;
            padding:10px 36px;
            margin:10px;
        }
        .alert-box span {
            font-weight:bold;
            text-transform:uppercase;
        }
        .errormes {
            background:#ffecec no-repeat 10px 50%;
            border:1px solid #f5aca6;
            padding: 10px;
        }
        .success {
            background:#e9ffd9 no-repeat 10px 50%;
            border:1px solid #a6ca8a;
            padding: 10px;
        }
        .warning {
            background:#fff8c4 no-repeat 10px 50%;
            border:1px solid #f2c779;
            padding: 10px;
        }
        .notice {
            background:#e3f7fc  no-repeat 10px 50%;
            border:1px solid #8ed9f6;
            padding: 10px;
        }
    </style>
    <div class="wrap">  
        <h1> <?php echo _e('Add Dropdown options 2', 'cqp'); ?></h1>       
        <div id="poststuff" class="metabox-holder ppw-settings">
            <div class="postbox" id="ppw_global_postbox">                 
                <div class="inside">                               
                    <div>
                        <form id="callback_settings" method="post" action="<?php echo get_admin_url() ?>admin-post.php" onsubmit="return validate();">  
                            <fieldset>
                                <input type='hidden' name='action' value='submit-dropdownoptions-form' />
                                <input type='hidden' name='id' value='<?php echo $getId ?>' />
                                <input type='hidden' name='paged' value='<?php echo $_GET['paged']; ?>' />
                                <table width="600px" cellpadding="0" cellspacing="0" class="form-table">
                                    <tr>
                                        <td>Options</td>
                                        <td><input type="text" id="options" name="options" value="<?php echo $getBestTime; ?>"></input></td>
                                    </tr>                                        
                                    <tr>                                
                                        <td colspan="2"><input class="button-primary" type="submit" id="submit_form_settings" name="submit_form_settings"></input></td>
                                    </tr>
                                </table>
                            </fieldset>
                        </form>
                    </div>                         
                </div>
            </div>           
        </div>
    </div>
    <script>
        jQuery(document).ready(function () {
            jQuery('#submit_form_settings').click(function () {
                if (jQuery('#options').val() == '') {
                    alert('Please enter Dropdown options');
                    return false;
                } else {
                    return true;
                }
            });
        });
    </script>
    <?php
}

function rcbDeleteOptionsTwo() {
    session_start();
    global $wpdb;
    $wpdb->delete($wpdb->prefix . "drop_down_options", array('id' => $_GET['options']));
    if ($wpdb->rows_affected > 0) {
        $_SESSION['area_status'] = 'deletesuccess';
    } else {
        $_SESSION['area_status'] = 'deletefailed';
    }
    if ($_GET['paged'] != '') {
        wp_redirect(admin_url('admin.php?page=settings-options&paged="' . $_GET['paged'] . '"'));
        exit;
    }
    wp_redirect(admin_url('admin.php?page=settings-options'));
}

function saveDropdownOptions() {
    session_start();
    global $wpdb;
    if (isset($_POST['submit_form_settings'])) {
        $insertArray['option'] = $_POST['options'];
        if ($_POST['id'] != '') {
            $wpdb->update($wpdb->prefix . "drop_down_options", $insertArray, array('id' => $_POST['id']), array('%s', '%s'), array('%d'));
            $_SESSION['area_status'] = 'updated';
        } else {
            $wpdb->insert($wpdb->prefix . "drop_down_options", $insertArray, array('%s', '%s'));
            if ($wpdb->insert_id > 0) {
                $_SESSION['area_status'] = 'success';
            } else {
                $_SESSION['area_status'] = 'failed';
            }
        }
        if ($_POST['paged'] != '') {
            wp_redirect(admin_url('admin.php?page=settings-options&paged="' . $_POST['paged'] . '"'));
            exit;
        }
        wp_redirect(admin_url('admin.php?page=settings-options'));
    }
}

function rcbDeleteBestTime() {
    session_start();
    global $wpdb;
    $wpdb->delete($wpdb->prefix . "call_back_best_time", array('id' => $_GET['best_time']));
    if ($wpdb->rows_affected > 0) {
        $_SESSION['area_status'] = 'deletesuccess';
    } else {
        $_SESSION['area_status'] = 'deletefailed';
    }
    if ($_GET['paged'] != '') {
        wp_redirect(admin_url('admin.php?page=settings-options&paged="' . $_GET['paged'] . '"'));
        exit;
    }
    wp_redirect(admin_url('admin.php?page=settings-options'));
}

function listBestTime() {
    session_start();
    global $wpdb;
    ?>
    <style>        
        ﻿.alert-box {
            color:#555;
            border-radius:10px;
            font-family:Tahoma,Geneva,Arial,sans-serif;font-size:11px;
            padding:10px 36px;
            margin:10px;
        }
        .alert-box span {
            font-weight:bold;
            text-transform:uppercase;
        }
        .errormes {
            background:#ffecec no-repeat 10px 50%;
            border:1px solid #f5aca6;
            padding: 10px;
        }
        .success {
            background:#e9ffd9 no-repeat 10px 50%;
            border:1px solid #a6ca8a;
            padding: 10px;
        }
        .warning {
            background:#fff8c4 no-repeat 10px 50%;
            border:1px solid #f2c779;
            padding: 10px;
        }
        .notice {
            background:#e3f7fc  no-repeat 10px 50%;
            border:1px solid #8ed9f6;
            padding: 10px;
        }
    </style>
    <div class="wrap">                               
        <h2><?php _e('Best Time', 'wpre'); ?> <a class="add-new-h2" href="<?php echo admin_url() ?>admin.php?page=rcb-best-time">Add New</a></h2> 
        <?php _statusMessage('Best Time'); ?>        
            <div class="inside">  
                <form id="best_time" name="best_time" method="post" action="">
                    <input type="hidden" name="action" value="delete"/>
                    <?php
                    if ($_REQUEST['action'] == 'delete') {
                        $del = $_REQUEST['best_time'];
                        if ($del != '') {
                            $idsToDelete = implode($del, ',');
                            global $wpdb;
                            $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "call_back_best_time WHERE id IN ($idsToDelete)"));
                            if ($wpdb->rows_affected > 0) {
                                $_SESSION['area_status'] = 'deletesuccess';
                                wp_redirect(admin_url('admin.php?page=settings-options&paged="' . $_GET['paged'] . '"'));
                                exit;
                            }
                        } else {
                            $_SESSION['area_status'] = 'deletefailed';
                            if ($_GET['paged'] != '') {
                                wp_redirect(admin_url('admin.php?page=settings-options&paged="' . $_GET['paged'] . '"'));
                            } else {
                                wp_redirect(admin_url('admin.php?page=settings-options'));
                            }
                        }
                    }

                    $myListTable = new Wpg_Besttime_List_Table();
                    $myListTable->prepare_items();
                    $myListTable->display();
                    ?>
                </form>      
            </div>                 
    </div><?php
}

function rcbBestTime() {
    session_start();
    global $wpdb;
    if ($_REQUEST['action'] == 'edit' && $_REQUEST['best_time'] != '') {
        $getBestTime = '';
        $getDetails = $wpdb->get_row('SELECT * FROM  ' . $wpdb->prefix . 'call_back_best_time WHERE id=' . $_REQUEST['best_time']);
        if ($getDetails != NULL) {
            $getId = $getDetails->id;
            $getBestTime = $getDetails->best_time;
        }
    }
    ?>
    <style>        
        ﻿.alert-box {
            color:#555;
            border-radius:10px;
            font-family:Tahoma,Geneva,Arial,sans-serif;font-size:11px;
            padding:10px 36px;
            margin:10px;
        }
        .alert-box span {
            font-weight:bold;
            text-transform:uppercase;
        }
        .errormes {
            background:#ffecec no-repeat 10px 50%;
            border:1px solid #f5aca6;
            padding: 10px;
        }
        .success {
            background:#e9ffd9 no-repeat 10px 50%;
            border:1px solid #a6ca8a;
            padding: 10px;
        }
        .warning {
            background:#fff8c4 no-repeat 10px 50%;
            border:1px solid #f2c779;
            padding: 10px;
        }
        .notice {
            background:#e3f7fc  no-repeat 10px 50%;
            border:1px solid #8ed9f6;
            padding: 10px;
        }
    </style>
    <div class="wrap">  
        <h1> <?php echo _e('Add Best Time', 'cqp'); ?></h1>       
        <div id="poststuff" class="metabox-holder ppw-settings">
            <div class="postbox" id="ppw_global_postbox">                 
                <div class="inside">                               
                    <div>
                        <form id="callback_settings" method="post" action="<?php echo get_admin_url() ?>admin-post.php" onsubmit="return validate();">  
                            <fieldset>
                                <input type='hidden' name='action' value='submit-besttime-form' />
                                <input type='hidden' name='id' value='<?php echo $getId ?>' />
                                <input type='hidden' name='paged' value='<?php echo $_GET['paged']; ?>' />
                                <table width="600px" cellpadding="0" cellspacing="0" class="form-table">
                                    <tr>
                                        <td>Best time </td>
                                        <td><input type="text" id="best_time" name="best_time" value="<?php echo $getBestTime; ?>"></input></td>
                                    </tr>                                        
                                    <tr>                                
                                        <td colspan="2"><input class="button-primary" type="submit" id="submit_form_settings" name="submit_form_settings"></input></td>
                                    </tr>
                                </table>
                            </fieldset>
                        </form>
                    </div>                         
                </div>
            </div>           
        </div>
    </div>
    <script>
        jQuery(document).ready(function () {
            jQuery('#submit_form_settings').click(function () {
                if (jQuery('#best_time').val() == '') {
                    alert('Please enter best time');
                    return false;
                } else {
                    return true;
                }
            });
        });
    </script>
    <?php
}

function saveBestTime() {
    session_start();
    global $wpdb;
    if (isset($_POST['submit_form_settings'])) {
        $insertArray['best_time'] = $_POST['best_time'];
        if ($_POST['id'] != '') {
            $wpdb->update($wpdb->prefix . "call_back_best_time", $insertArray, array('id' => $_POST['id']), array('%s', '%s'), array('%d'));
            $_SESSION['area_status'] = 'updated';
        } else {
            $wpdb->insert($wpdb->prefix . "call_back_best_time", $insertArray, array('%s', '%s'));
            if ($wpdb->insert_id > 0) {
                $_SESSION['area_status'] = 'success';
            } else {
                $_SESSION['area_status'] = 'failed';
            }
        }
        if ($_POST['paged'] != '') {
            wp_redirect(admin_url('admin.php?page=settings-options&paged="' . $_POST['paged'] . '"'));
            exit;
        }
        wp_redirect(admin_url('admin.php?page=settings-options'));
    }
}

/**
 * Add function to widgets_init that'll load our widget.
 * @since 0.1
 */
add_action('widgets_init', 'wpg_callmeback');

/**
 * Register our widget.
 * 'wpgcallmeback_Widget' is the widget class used below.
 *
 * @since 0.1
 */
function wpg_callmeback() {
    register_widget('wpgcallmeback_Widget');
}

function callRequestCallBack() {
    rcbTabs();
    ?>
    <script>
        jQuery(document).ready(function () {
            jQuery("body").addClass("wps-admin-page");
            // binds form submission and fields to the validation engine          
            jQuery(".wps-postbox-container .handlediv, .wps-postbox-container .hndle").on("click", function (n) {
                return n.preventDefault(), jQuery(this).parent().toggleClass("closed");
            });
        });
    </script>  
    <div class="wrap">
        <div id="poststuff" class="metabox-holder ppw-settings">
            <div class="left-side">
                <?php
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('Contact Request');
                $myListTable = new Wpg_Callback_List_Table();
                $myListTable->prepare_items();
                $myListTable->display();
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();

                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('About');
                callback_about_us();
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                ?>
            </div>
        </div>
        <?php displayRightContactRequest(); ?>
    </div>
    <?php
}

function callSettings() {
    rcbTabs();
    session_start();
    global $wpdb;
    $picker1 = '';
    $picker2 = '';
    $picker3 = '';
    $picker4 = '';
    $call_back_admin_email = '';
    $get_option_details = unserialize(get_option('rcb_settings_options'));
    if (isset($get_option_details['picker1']) && $get_option_details['picker1'] != '')
        $picker1 = $get_option_details['picker1'];
    if (isset($get_option_details['picker2']) && $get_option_details['picker2'] != '')
        $picker2 = $get_option_details['picker2'];
    if (isset($get_option_details['picker3']) && $get_option_details['picker3'] != '')
        $picker3 = $get_option_details['picker3'];
    if (isset($get_option_details['picker4']) && $get_option_details['picker4'] != '')
        $picker4 = $get_option_details['picker4'];
    if (isset($get_option_details['call_back_admin_email']) && $get_option_details['call_back_admin_email'] != '')
        $call_back_admin_email = $get_option_details['call_back_admin_email'];
    if (isset($get_option_details['site_key']) && $get_option_details['site_key'] != '')
        $site_key = $get_option_details['site_key'];
    if (isset($get_option_details['secret']) && $get_option_details['secret'] != '')
        $secret = $get_option_details['secret'];
    if (isset($get_option_details['auto-responder']) && $get_option_details['auto-responder'] != '')
        $getResponder = $get_option_details['auto-responder'];
    if (isset($get_option_details['subject']) && $get_option_details['subject'] != '')
        $subject = $get_option_details['subject'];
    if (isset($get_option_details['dropdown-two']) && $get_option_details['dropdown-two'] != '')
        $dropdown = $get_option_details['dropdown-two'];
    ?>
    <script>
        jQuery(document).ready(function () {
            jQuery("body").addClass("wps-admin-page");
            // binds form submission and fields to the validation engine          
            jQuery(".wps-postbox-container .handlediv, .wps-postbox-container .hndle").on("click", function (n) {
                return n.preventDefault(), jQuery(this).parent().toggleClass("closed");
            });
        });
    </script>
    <div class="wrap">  
    <!--        <h1> <?php echo _e('Settings and Options', 'cqp'); ?></h1>-->
        <?php _statusMessage('Settings and Options'); ?>
        <div id="poststuff" class="metabox-holder ppw-settings">
            <div class="left-side">                 
                <?php
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('Settings');
                ?>
                <div>
                    <form id="callback_settings" method="post" action="<?php echo get_admin_url() ?>admin-post.php" onsubmit="return validate();">  
                        <fieldset>
                            <input type='hidden' name='action' value='submit-callback-settings-form' />
                            <table width="600px" cellpadding="0" cellspacing="0" class="form-table">
                                <tr>
                                    <td>Top background color : </td>
                                    <td><input readonly type="text" id="picker1" name="picker1" style="border-color:<?php echo $picker1; ?>" value="<?php echo $picker1; ?>"></input></td>
                                </tr>
                                <tr>
                                    <td>Bottom background color : </td>
                                    <td><input readonly type="text" id="picker2" name="picker2" style="border-color:<?php echo $picker2; ?>" value="<?php echo $picker2; ?>"></input></td>
                                </tr>
                                <tr>
                                    <td>Callback button background color : </td>
                                    <td><input readonly type="text" id="picker3" name="picker3" style="border-color:<?php echo $picker3; ?>" value="<?php echo $picker3; ?>"></input></td>
                                </tr>
                                <tr>
                                    <td>Help button background color : </td>
                                    <td><input readonly type="text" id="picker4" name="picker4" style="border-color:<?php echo $picker4; ?>" value="<?php echo $picker4; ?>"></input></td>
                                </tr>                                
                                <tr>
                                    <td>Mail subject</td>
                                    <td><input type="text" id="subject" name="subject" size="40" value="<?php echo $subject; ?>"></input></td>
                                </tr>
                                <tr>
                                    <td>Auto-responder to user</td>
                                    <td><textarea id="auto-responder" name="auto-responder" cols="90" rows="15"><?php echo $getResponder; ?></textarea></td>
                                </tr> 
                                <tr>
                                    <td>Show dropdown option two</td>
                                    <?php
                                    if ($dropdown == 1) {
                                        $checked = 'checked="checked"';
                                    } else {
                                        $checked = '';
                                    }
                                    ?>
                                    <td><input <?php echo $checked; ?> type="checkbox" id="dropdown-two" name="dropdown-two" size="40" value="1"></input></td>
                                </tr> 
                                <tr>                                
                                    <td colspan="2"><input class="button-primary" type="submit" id="submit_form_settings" name="submit_form_settings"></input></td>
                                </tr>
                            </table>
                        </fieldset>
                    </form>
                </div>  
                <?php
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();

//                NMRichReviewsAdminHelper::render_container_open('content-container');
//                NMRichReviewsAdminHelper::render_postbox_open('Best Time');
//                listBestTime();
//                NMRichReviewsAdminHelper::render_postbox_close();
//                NMRichReviewsAdminHelper::render_container_close();

                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('Dropdown Options 2');
                listOptionsTwo();
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();

                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('About');
                callback_about_us();
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                ?>
            </div>      
        </div>
        <?php displayRightContactRequest(); ?>
    </div>
    <script>
        function validate() {
            var picker1 = jQuery('#picker1').val();
            var picker2 = jQuery('#picker2').val();
            var picker3 = jQuery('#picker3').val();
            var picker4 = jQuery('#picker4').val();
            var call_back_admin_email = jQuery('#call_back_admin_email').val();
            if (picker1 == '' || picker2 == '' || picker3 == '' || picker4 == '' || call_back_admin_email == '') {
                alert('Please fill all the required fields');
                return false;
            }
            return true;
        }
        jQuery(document).ready(function () {
            jQuery('#picker1,#picker2,#picker3,#picker4').colpick({
                layout: 'hex',
                submit: 0,
                color: '3289c7',
                colorScheme: 'dark',
                onChange: function (hsb, hex, rgb, el, bySetColor) {
                    jQuery(el).css('border-color', '#' + hex);
                    // Fill the text box just if the color was set using the picker, and not the colpickSetColor function.
                    if (!bySetColor)
                        jQuery(el).val('#' + hex);
                }
            }).keyup(function () {
                jQuery(this).colpickSetColor(this.value);
            });
        });
    </script>
    <?php
}

function saveCallbackSettings() {
    session_start();
    global $wpdb;
    if (isset($_POST['submit_form_settings'])) {
        if (isset($_POST['picker1']))
            $insertArray['picker1'] = $_POST['picker1'];
        if (isset($_POST['picker2']))
            $insertArray['picker2'] = $_POST['picker2'];
        if (isset($_POST['picker3']))
            $insertArray['picker3'] = $_POST['picker3'];
        if (isset($_POST['picker4']))
            $insertArray['picker4'] = $_POST['picker4'];
        if (isset($_POST['call_back_admin_email']))
            $insertArray['call_back_admin_email'] = $_POST['call_back_admin_email'];
        if (isset($_POST['site_key']))
            $insertArray['site_key'] = $_POST['site_key'];
        if (isset($_POST['secret']))
            $insertArray['secret'] = $_POST['secret'];
        if (isset($_POST['auto-responder']))
            $insertArray['auto-responder'] = $_POST['auto-responder'];
        if (isset($_POST['subject']))
            $insertArray['subject'] = $_POST['subject'];
        if (isset($_POST['dropdown-two']))
            $insertArray['dropdown-two'] = $_POST['dropdown-two'];

        $serialize_array = serialize($insertArray);
        update_option('rcb_settings_options', $serialize_array);
        $_SESSION['area_status'] = 'updated';
        wp_redirect(admin_url('admin.php?page=settings-options'));
    }
    wp_redirect(admin_url('admin.php?page=settings-options'));
}

function _statusMessage($string) {
    if ($_SESSION['area_status'] == 'success') {
        unset($_SESSION['area_status']);
        ?>
        <div class="alert-box success"><span>Success : </span>New <?php echo $string; ?> has been added successfully</div>
        <?php
    } else if ($_SESSION['area_status'] == 'failed') {
        unset($_SESSION['area_status']);
        ?>
        <div class="alert-box errormes"><span>Error : </span>Problem in creating new <?php echo $string; ?>.</div>
        <?php
    } else if ($_SESSION['area_status'] == 'updated') {
        unset($_SESSION['area_status']);
        ?>
        <div class="alert-box success"><span>Success : </span><?php echo $string; ?> has been updated successfully.</div>
        <?php
    } else if ($_SESSION['area_status'] == 'deletesuccess') {
        unset($_SESSION['area_status']);
        ?>
        <div class="alert-box success"><span>Success : </span><?php echo $string; ?> has been deleted successfully.</div>
        <?php
    } else if ($_SESSION['area_status'] == 'deletefailed') {
        unset($_SESSION['area_status']);
        ?>
        <div class="alert-box errormes"><span>Error : </span>Problem in deleting <?php echo $string; ?>.</div>
        <?php
    } else if ($_SESSION['area_status'] == 'invalid_file') {
        unset($_SESSION['area_status']);
        ?>
        <div class="alert-box errormes"><span>Error : </span><?php echo $string; ?> should be a PHP file.</div>
        <?php
    }
}

function recaptchaHtml() {
    session_start();
    global $wpdb;
    $call_back_admin_email = '';
    $site_key = '';
    $secret = '';
    $get_option_details = unserialize(get_option('rcb_recaptcha_options'));
    if (isset($get_option_details['call_back_admin_email']) && $get_option_details['call_back_admin_email'] != '')
        $call_back_admin_email = $get_option_details['call_back_admin_email'];
    if (isset($get_option_details['site_key']) && $get_option_details['site_key'] != '')
        $site_key = $get_option_details['site_key'];
    if (isset($get_option_details['secret']) && $get_option_details['secret'] != '')
        $secret = $get_option_details['secret'];
    ?>   
    <?php _statusMessage('Recaptcha settings'); ?>                            
    <div>
        <form id="callback_settings" method="post" action="<?php echo get_admin_url() ?>admin-post.php" onsubmit="return validate();">  
            <fieldset>
                <input type='hidden' name='action' value='submit-recaptcha-form' />
                <table width="100%" cellpadding="0" cellspacing="0" class="form-table">                                    
                    <tr>
    <!--                        <td>Admin email</td>-->
                        <td>Admin email : <br/><input type="text" id="call_back_admin_email" name="call_back_admin_email" value="<?php echo $call_back_admin_email; ?>"></input></td>
                    </tr>
                    <tr>
    <!--                        <td>Recaptcha site key</td>-->
                        <td>Recaptcha site key: <br/><input type="text" id="site_key" name="site_key" size="40" value="<?php echo $site_key; ?>"></input></td>
                    </tr>
                    <tr>
    <!--                        <td>Recaptcha secret</td>-->
                        <td>Recaptcha secret: <br/> <input type="text" id="secret" name="secret" size="40" value="<?php echo $secret; ?>"></input></td>
                    </tr>                                                                 
                    <tr>                                
                        <td ><input class="button-primary" type="submit" id="submit_form_settings" name="submit_form_settings"></input></td>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>                                         
    <?php
}

function saveRecaptchaForm() {
    session_start();
    global $wpdb;
    if (isset($_POST['submit_form_settings'])) {

        if (isset($_POST['call_back_admin_email']))
            $insertArray['call_back_admin_email'] = $_POST['call_back_admin_email'];
        if (isset($_POST['site_key']))
            $insertArray['site_key'] = $_POST['site_key'];
        if (isset($_POST['secret']))
            $insertArray['secret'] = $_POST['secret'];

        $serialize_array = serialize($insertArray);
        update_option('rcb_recaptcha_options', $serialize_array);
        $_SESSION['area_status'] = 'updated';
        wp_redirect(admin_url('admin.php?page=request-call-back'));
    }
    wp_redirect(admin_url('admin.php?page=request-call-back'));
}

/**
 * Example Widget class.
 * This class handles everything that needs to be handled with the widget:
 * the settings, form, display, and update.  Nice!
 *
 * @since 0.1
 */
class wpgcallmeback_Widget extends WP_Widget {

    /**
     * Widget setup.
     */
    function wpgcallmeback_Widget() {
        /* Widget settings. */
        $widget_ops = array('classname' => 'wpgcallmeback', 'description' => __('A request call me back widget by webplugins.co.uk', 'wpgcallmeback'));
        /* Widget control settings. */
        $control_ops = array('width' => 300, 'height' => 350, 'id_base' => 'wpgcallmeback-widget');
        /* Create the widget. */
        $this->WP_Widget('wpgcallmeback-widget', __('Reqest call me back', 'wpgcallmeback'), $widget_ops, $control_ops);
    }

    /**
     * How to display the widget on the screen.
     */
    function widget($args, $instance) {
        global $wpdb;
        $get_option_details = unserialize(get_option('rcb_settings_options'));
        extract($args);
        /* Our variables from the widget settings. */
        $title = apply_filters('widget_title', $instance['wpgtitle']);
        $wpgslogan = $instance['wpgslogan'];
        $wpginfo = $instance['wpginfo'];
        $wpgcallbut = $instance['wpgcallbut'];
        $wpgcallus = $instance['wpgcallus'];
        $wpgphonenum = $instance['wpgphonenum'];
        $wpglinesinfo = $instance['wpglinesinfo'];
        $show_form = isset($instance['show_form']) ? $instance['show_form'] : false;

        /* Before widget (defined by themes). */
        echo $before_widget;

        /* call back from start */
        if ($show_form)
            if (isset($_POST['submit'])) {
                //echo '<pre>'; print_r($_POST); exit;
                $siteKey = $get_option_details['site_key'];
                $secret = $get_option_details['secret'];
// reCAPTCHA supported 40+ languages listed here: https://developers.google.com/recaptcha/docs/language
                $lang = "en";
// The response from reCAPTCHA
                $resp = null;
// The error code from reCAPTCHA, if any
                $error = null;

                $reCaptcha = new ReCaptcha($secret);
                if ($_POST["g-recaptcha-response"]) {
                    $resp = $reCaptcha->verifyResponse(
                            $_SERVER["REMOTE_ADDR"], $_POST["g-recaptcha-response"]
                    );
                }
                //if ($resp != null && $resp->success) {
                $admin_email = $get_option_details['call_back_admin_email'];
                if ($get_option_details['subject'] != '' && isset($get_option_details['subject'])) {
                    $setSubject = $get_option_details['subject'];
                } else {
                    $setSubject = 'Call me back query from website';
                }
                $rname = $_POST['rname'];
                $rnumber = $_POST['rnumber'];
                $rtime = $_POST['rtime'];
                $remail = $_POST['remail'];
                $roption = $_POST['optiontwo'];
                $rmessage = $_POST['message'];
                $insertArray['name'] = $_POST['rname'];
                $insertArray['number'] = $_POST['rnumber'];
                $insertArray['email'] = $_POST['remail'];
                $insertArray['besttime'] = $_POST['rtime'];
                $insertArray['postcode'] = $_POST['postcode'];
                if ($get_option_details['dropdown-two'] == 1) {
                    $insertArray['options'] = $_POST['optiontwo'];
                    $insertArray['message'] = $_POST['message'];
                }
                $wpdb->insert($wpdb->prefix . "request_a_call_back", $insertArray, array('%s', '%s'));
                $mailbody .= "Hello Admin, </br></br>
                    Please see below for the enquiry received from one of our customer with details.</br>
                    Name: $rname </br>
                    Number: $rnumber</br>
                    Best time to call: $rtime</br>
                    Email: $remail</br>";
                if ($get_option_details['dropdown-two'] == 1) {
                    $mailbody .="Option: $roption</br> Message: $rmessage</br>";
                }
                $mailbody .="</br> Regards,<br/>" . get_bloginfo();
                $headers = 'From: ' . $admin_email . "\r\n" .
                        'Reply-To: ' . $admin_email . "\r\n" .
                        'X-Mailer: PHP/' . phpversion();
                $headers .= 'MIME-Version: 1.0' . "\n";
                $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                if (mail($admin_email, $setSubject, $mailbody, $headers)) {
                    
                } else {
                    
                }
                $nl2br = nl2br($get_option_details['auto-responder']);
                $customer_mail_body = str_replace('{name}', $rname, $nl2br);
                $customer_mail_body = str_replace('{besttime}', $rtime, $customer_mail_body);
                $customer_mail_body = str_replace('{siteadmin}', get_bloginfo(), $customer_mail_body);
                if (mail($_POST['remail'], 'Thank you for contacting us!', $customer_mail_body, $headers)) {
                    echo "Thanks for your request. We will call you during your requested timeslot!";
                } else {
                    echo('Sorry there wa error processing your request. please try again');
                }
//                } else {
//                    echo 'Please enter correct captcha code';
//                }
            } else {
                ?>
                <style>
                    .g-recaptcha div div {width:100% !important}
                    .g-recaptcha div div iframe{width:100% !important}
                    .g-recaptcha div div iframe html body .rc-anchor .rc-anchor-content{width:47px !important;}
                </style>
                <div class="wpgcallbackform">

                    <script type="text/javascript"
                            src="https://www.google.com/recaptcha/api.js?hl=<?php echo $lang; ?>">
                    </script>
                    <div class="wpgctop" style="background-color: <?php echo $get_option_details['picker1']; ?>;">
                        <div class="wpgtitle"><?php echo $title; ?></div>
                        <div class="wpgslogan"><?php echo $wpgslogan; ?></div>
                        <div class="wpginfo"><?php echo $wpginfo; ?></div>
                        <div class="wpgform"><form action="#" method="post" enctype="application/x-www-form-urlencoded" name="callbackwidget">
                                <input name="rname" type="text" value="Name"  onclick="this.value = '';" onblur="if (this.value == '') {
                                            this.value = 'Name'
                                        }" size="17" />
                                <input name="rnumber" type="text" value="Number"  onclick="this.value = '';"  onblur="if (this.value == '') {
                                            this.value = 'Number'
                                        }"  size="17" />
                                <input name="remail" type="text" value="Email"  onclick="this.value = '';"  onblur="if (this.value == '') {
                                            this.value = 'Email'
                                        }"  size="17" />
                                <input name="postcode" type="text" value="Postcode"  onclick="this.value = '';"  onblur="if (this.value == '') {
                                            this.value = 'Postcode'
                                        }"  size="17" />
                                <select class="wpgselect" name="rtime" size="1">                                    
                                    <?php
                                    global $wpdb;
                                    $getAllBestTimes = $wpdb->get_results('SELECT best_time FROM  ' . $wpdb->prefix . 'call_back_best_time');
                                    foreach ($getAllBestTimes as $getAllBestTime) {
                                        echo '<option value=' . $getAllBestTime->best_time . '>' . $getAllBestTime->best_time . '</option>';
                                    }
                                    ?>                                                                       
                                </select>
                                <?php if ($get_option_details['dropdown-two'] == 1) { ?>
                                    <select class="wpgselect" name="optiontwo" size="1">                                    
                                        <?php
                                        global $wpdb;
                                        $getAllBestTimes = $wpdb->get_results('SELECT * FROM  ' . $wpdb->prefix . 'drop_down_options');
                                        foreach ($getAllBestTimes as $getAllBestTime) {
                                            echo '<option value=' . $getAllBestTime->option . '>' . $getAllBestTime->option . '</option>';
                                        }
                                        ?>                                                                       
                                    </select>
                                    <input name="message" type="text" value="Message"  onclick="this.value = '';"  onblur="if (this.value == '') {
                                                this.value = 'Message'
                                            }"  size="17" />
                                       <?php } ?>
                                <div class="g-recaptcha" style="width:100%; display: block;" data-theme="light" data-type="image" data-sitekey="<?php echo $get_option_details['site_key']; ?>"></div>                                                                                
                                <input name="submit" type="submit" style="background-color: <?php echo $get_option_details['picker3']; ?>" class="callmeback" value="Call me back" />
                            </form></div>
                    </div>
                    <div class="wpgcbottom" style="background-color: <?php echo $get_option_details['picker2']; ?>">
                        <div class="wpgcallbut" style="background-color: <?php echo $get_option_details['picker4']; ?>;"><?php echo $wpgcallbut; ?></div>
                        <div class="wpgcallus"><?php echo $wpgcallus; ?></div>
                        <div class="wpgphonenum"><?php echo $wpgphonenum; ?></div>
                        <div class="wpglinesinfo"><?php echo $wpglinesinfo; ?></div>
                    </div>
                </div>
                <?php
            }
        /* After widget (defined by themes). */
        echo $after_widget;
    }

    /**
     * Update the widget settings.
     */
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        /* Strip tags for title and name to remove HTML (important for text inputs). */
        $instance['wpgtitle'] = strip_tags($new_instance['wpgtitle']);
        $instance['wpgslogan'] = strip_tags($new_instance['wpgslogan']);
        $instance['wpginfo'] = strip_tags($new_instance['wpginfo']);
        $instance['wpgcallbut'] = strip_tags($new_instance['wpgcallbut']);
        $instance['wpgcallus'] = strip_tags($new_instance['wpgcallus']);
        $instance['wpgphonenum'] = strip_tags($new_instance['wpgphonenum']);
        $instance['wpglinesinfo'] = strip_tags($new_instance['wpglinesinfo']);
        /* No need to strip tags for show_form. */
        $instance['show_form'] = $new_instance['show_form'];
        return $instance;
    }

    /**
     * Displays the widget settings controls on the widget panel.
     * Make use of the get_field_id() and get_field_name() function
     * when creating your form elements. This handles the confusing stuff.
     */
    function form($instance) {
        /* Set up some default widget settings. */
        $defaults = array('wpgtitle' => 'REQUEST A CALL BACK', 'wpgslogan' => 'Free no obligation call', 'wpginfo' => 'A Short Message goes here.', 'wpgcallbut' => 'Here to help you', 'wpgcallus' => 'Call Us', 'wpgphonenum' => 'your number', 'wpglinesinfo' => 'Open 8am – 8pm Monday to Friday Saturday and Sunday 9-4pm', 'show_form' => true);
        $instance = wp_parse_args((array) $instance, $defaults);
        ?>
        <!-- Widget Title: Text Input -->
        <p>
            <label for="<?php echo $this->get_field_id('wpgtitle'); ?>"><?php _e('Title:', 'wpgtitle'); ?></label>
            <input id="<?php echo $this->get_field_id('wpgtitle'); ?>" name="<?php echo $this->get_field_name('wpgtitle'); ?>" value="<?php echo $instance['wpgtitle']; ?>" style="width:100%;" />
        </p>
        <p>

            <label for="<?php echo $this->get_field_id('wpgslogan'); ?>"><?php _e('Slogan:', 'wpgcallmeback'); ?></label>
            <input id="<?php echo $this->get_field_id('wpgslogan'); ?>" name="<?php echo $this->get_field_name('wpgslogan'); ?>" value="<?php echo $instance['wpgslogan']; ?>" style="width:100%;" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('wpginfo'); ?>"><?php _e('Information:', 'wpginfo'); ?></label>
            <input id="<?php echo $this->get_field_id('wpginfo'); ?>" name="<?php echo $this->get_field_name('wpginfo'); ?>" value="<?php echo $instance['wpginfo']; ?>" style="width:100%;" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('wpgcallbut'); ?>"><?php _e('Call button:', 'wpgcallbut'); ?></label>
            <input id="<?php echo $this->get_field_id('wpgcallbut'); ?>" name="<?php echo $this->get_field_name('wpgcallbut'); ?>" value="<?php echo $instance['wpgcallbut']; ?>" style="width:100%;" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('wpgcallus'); ?>"><?php _e('Call Us Title:', 'wpgcallus'); ?></label>
            <input id="<?php echo $this->get_field_id('wpgcallus'); ?>" name="<?php echo $this->get_field_name('wpgcallus'); ?>" value="<?php echo $instance['wpgcallus']; ?>" style="width:100%;" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('wpgphonenum'); ?>"><?php _e('Phone Number:', 'wpgphonenum'); ?></label>
            <input id="<?php echo $this->get_field_id('wpgphonenum'); ?>" name="<?php echo $this->get_field_name('wpgphonenum'); ?>" value="<?php echo $instance['wpgphonenum']; ?>" style="width:100%;" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('wpglinesinfo'); ?>"><?php _e('Phone lines info:', 'wpglinesinfo'); ?></label>
            <input id="<?php echo $this->get_field_id('wpglinesinfo'); ?>" name="<?php echo $this->get_field_name('wpglinesinfo'); ?>" value="<?php echo $instance['wpglinesinfo']; ?>" style="width:100%;" />
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($instance['show_form'], on); ?> id="<?php echo $this->get_field_id('show_form'); ?>" name="<?php echo $this->get_field_name('show_form'); ?>" /> 
            <label for="<?php echo $this->get_field_id('show_form'); ?>"><?php _e('Display form publicly?', 'example'); ?></label>
        </p>
        <?php
    }

}

function displayRightContactRequest() {
    ?>
    <div class="right-side">
        <?php
        NMRichReviewsAdminHelper::render_container_open('content-container-right');
        NMRichReviewsAdminHelper::render_postbox_open('Information');
        callback_contact_request_info();
        NMRichReviewsAdminHelper::render_postbox_close();
        NMRichReviewsAdminHelper::render_container_close();
        NMRichReviewsAdminHelper::render_container_open('content-container-right');
//        NMRichReviewsAdminHelper::render_postbox_open('What we Do');
//        //render_rr_what_we_do();
//        NMRichReviewsAdminHelper::render_postbox_close();
//        NMRichReviewsAdminHelper::render_container_close();
        ?>
    </div>
    <?php
}

function callback_about_us() {
    $output = '<p><strong>WP Social SEO</strong> gives you the ability to quick add your Social Profiles in a compliant way so that it shows up in a google search.</p>
               <p>Specify your social profiles to Google <a href="https://developers.google.com/webmasters/structured-data/customize/social-profiles" target="_blank">https://developers.google.com/webmasters/structured-data/customize/social-profiles</a></p>
               <p>Use mark-up on your official website to add your social profile information to the Google Knowledge panel in some searches. Knowledge panels can prominently display your social profile information.</p>
               <p>Our other free plugins can be found at <a href="https://profiles.wordpress.org/pigeonhut/" target="_blank">https://profiles.wordpress.org/pigeonhut/</a> </p>
               <p>To see more about us as a company, visit <a href="http://www.web9.co.uk" target="_blank">http://www.web9.co.uk</a></p>
               <p>Proudly made in Belfast, Northern Ireland.</p>';
    echo $output;
}

function callback_contact_request_info() {
    session_start();
    global $wpdb;
    $call_back_admin_email = '';
    $site_key = '';
    $secret = '';
    $get_option_details = unserialize(get_option('rcb_recaptcha_options'));
    if (isset($get_option_details['call_back_admin_email']) && $get_option_details['call_back_admin_email'] != '')
        $call_back_admin_email = $get_option_details['call_back_admin_email'];
    if (isset($get_option_details['site_key']) && $get_option_details['site_key'] != '')
        $site_key = $get_option_details['site_key'];
    if (isset($get_option_details['secret']) && $get_option_details['secret'] != '')
        $secret = $get_option_details['secret'];
    _statusMessage('Recaptcha settings');
    $output = '<div style="background: none repeat scroll 0 0 #99ff99;display:block;padding: 10px;">Don\'t yet have a Google Recaptcha account ? <a href="http://www.google.com/recaptcha/intro/index.html" target="_blank">Signup Free</a></div></br>';
    $output1 = '<div class="info_class"><div>
        <form id="callback_settings" method="post" action="' . get_admin_url() . 'admin-post.php" onsubmit="return validate();">  
            <fieldset>
                <input type=\'hidden\' name=\'action\' value=\'submit-recaptcha-form\' />
                <table width="100%" cellpadding="0" cellspacing="0" class="form-table">                                    
                    <tr>
<!--                        <td>Admin email</td>-->
                        <td>Admin email : <br/><input type="text" id="call_back_admin_email" name="call_back_admin_email" value="' . $call_back_admin_email . '"></input></td>
                    </tr>
                    <tr>
<!--                        <td>Recaptcha site key</td>-->
                        <td>Recaptcha site key: <br/><input type="text" id="site_key" name="site_key" size="40" value="' . $site_key . '"></input></td>
                    </tr>
                    <tr>
<!--                        <td>Recaptcha secret</td>-->
                        <td>Recaptcha secret: <br/> <input type="text" id="secret" name="secret" size="40" value="' . $secret . '"></input></td>
                    </tr>                                                                 
                    <tr>                                
                        <td ><input class="button-primary" type="submit" id="submit_form_settings" name="submit_form_settings"></input></td>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>             </div></br>';
    //$output2 ='<div class="info_class">See this link to view <a href="https://developers.google.com/structured-data/customize/social-profiles" target="_blank">Googles Description</a></div>';
    echo $output . $output1;
}
?>