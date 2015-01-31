<?php
error_reporting(0);

/**
 * Plugin Name: Call me back widget
 * Plugin URI: http://pigeonhut.com
 * Description: Request call me back widget by PigeonHUT
 * Version: 1.11
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
if (!class_exists('ReCaptcha')) {
    require_once( plugin_dir_path(__FILE__) . 'class/recaptchalib.php' );
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
            `dateCreated` timestamp NOT NULL,
            PRIMARY KEY (`id`))ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";
    $wpdb->query($sql);
    add_menu_page(__('Request a call back', 'rcb'), __('Request a call back', 'rcb'), 'manage_options', 'request-call-back', 'callRequestCallBack', '');
    add_submenu_page('request-call-back', __('Settings and options', 'rcb'), __('Settings and options', 'rcb'), 'manage_options', 'settings-options', 'callSettings');
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

function adminCallBackScripts() {
    wp_register_style('wpgcallmeback-style', plugins_url('css/colpick.css', __FILE__), array(), '20120208', 'all');
    wp_enqueue_script('wpgcallmeback-style', plugins_url('js/colpick.js', __FILE__), array(), '1.0.0', true);
    wp_enqueue_style('wpgcallmeback-style');
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
    $myListTable = new Wpg_Callback_List_Table();
    $myListTable->prepare_items();
    $myListTable->display();
}

function callSettings() {
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
        <h1> <?php echo _e('Settings and Options', 'cqp'); ?></h2>
            <?php _statusMessage('Settings and Options'); ?>
            <div id="poststuff" class="metabox-holder ppw-settings">
                <div class="postbox" id="ppw_global_postbox">                 
                    <div class="inside">                               
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
                                            <td>Admin email</td>
                                            <td><input type="text" id="call_back_admin_email" name="call_back_admin_email" value="<?php echo $call_back_admin_email; ?>"></input></td>
                                        </tr>
                                        <tr>
                                            <td>Recaptcha site key</td>
                                            <td><input type="text" id="site_key" name="site_key" size="40" value="<?php echo $site_key; ?>"></input></td>
                                        </tr>
                                        <tr>
                                            <td>Recaptcha secret</td>
                                            <td><input type="text" id="secret" name="secret" size="40" value="<?php echo $secret; ?>"></input></td>
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
                if ($resp != null && $resp->success) {
                    $admin_email = $get_option_details['call_back_admin_email'];
                    $rname = $_POST['rname'];
                    $rnumber = $_POST['rnumber'];
                    $rtime = $_POST['rtime'];
                    $remail = $_POST['remail'];
                    $insertArray['name'] = $_POST['rname'];
                    $insertArray['number'] = $_POST['rnumber'];
                    $insertArray['email'] = $_POST['remail'];
                    $insertArray['besttime'] = $_POST['rtime'];
                    $insertArray['postcode'] = $_POST['postcode'];
                    $wpdb->insert($wpdb->prefix . "request_a_call_back", $insertArray, array('%s', '%s'));
                    $mailbody = "< Request call me back > form data
	Name: $rname
	Number: $rnumber
	Best time to call: $rtime
	Email: $remail";
                    $headers = 'From: ' . $admin_email . "\r\n" .
                            'Reply-To: ' . $admin_email . "\r\n" .
                            'X-Mailer: PHP/' . phpversion();
                    if (mail($admin_email, 'Call me back query from website', $mailbody, $headers)) {
                        echo "Thanks for your request. We will call you during your requested timeslot!";
                    } else {
                        echo('Sorry there wa error processing your request. please try again');
                    }
                } else {
                    echo 'Please enter correct captcha code';
                }
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
                        <div class="wpgform"><form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post" enctype="application/x-www-form-urlencoded" name="callbackwidget">
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
                                    <option selected="selected">Select best time to call</option>
                                    <option value="Morning">Morning</option>
                                    <option value="Afternoon">Afternoon</option>
                                    <option value="Evening">Evening</option>
                                </select>
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
?>
