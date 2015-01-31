<?php
error_reporting(0);

/**
 * Plugin Name: Wp Social
 * Plugin URI: http://www.web9.co.uk/
 * Description: Use structured data markup embedded in your public website to specify your preferred social profiles. You can specify these types of social profiles: Facebook, Twitter, Google+, Instagram, YouTube, LinkedIn and Myspace.
 * Version: 1.0
 * Author: Jody Nesbitt (WebPlugins)
 * Author URI: http://webplugins.co.uk
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
add_action('admin_menu', 'admin_init');
add_action('admin_post_submit-wnp-settings', 'saveSettings');

function admin_init() {
    add_menu_page(__('Wp Social', 'wnp'), __('Social profile', 'wnp'), 'manage_options', 'wps-social-profile', 'callWebNicePlc', '');
}

function load_custom_wp_admin_style() {
    wp_register_style('custom_wp_admin_css',plugins_url('/css/validationEngine.jquery.css', __FILE__), false, '1.0.0');
    wp_enqueue_style('custom_wp_admin_css');
    wp_enqueue_script('locationjs', plugins_url('/js/jquery-1.8.2.min.js', __FILE__),array(),'',1);
    wp_enqueue_script('locationjs1', plugins_url('/js/jquery.validationEngine-en.js', __FILE__),array(),'',1);
    wp_enqueue_script('locationjs2', plugins_url('/js/jquery.validationEngine.js', __FILE__),array(),'',1);
}

add_action('admin_enqueue_scripts', 'load_custom_wp_admin_style');

function callWebNicePlc() {   
    $get_option_details = unserialize(get_option('wnp_social_settings'));
    ?>
    <script>
        jQuery(document).ready(function () {
            // binds form submission and fields to the validation engine
            jQuery("#settingsID").validationEngine();
        });
    </script>
    <div class="wrap">        
        <h2><?php _e('Wp Social profile settings', 'wnp'); ?></h2> 
        <div id="poststuff" class="metabox-holder ppw-settings">
            <div class="postbox" id="ppw_global_postbox">               
                <div class="inside">                               
                    <form id="settingsID" method="post" action="<?php echo get_admin_url() ?>admin-post.php">  
                        <fieldset>                            
                            <input type='hidden' name='action' value='submit-wnp-settings' />
                            <input type='hidden' name='id' value='<?php echo $getId ?>' />
                            <input type='hidden' name='paged' value='<?php echo $_GET['paged']; ?>' />
                            <div>
                                <table cellpadding="0" cellspacing="0" border="0" width="600" class="form-table">
                                    <tr height="50">
                                        <td width="150">Type : </td>
                                        <td>    
                                            <input type="text" class="validate[required] text-input" id="type" name="type" value="<?php echo $get_option_details['type']; ?>" />
                                        </td>
                                    </tr>
                                    <tr height="50">
                                        <td>Name : </td>
                                        <td><input type="text" class="validate[required] text-input" id="name" name="name" value="<?php echo $get_option_details['name']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Url : </td>
                                        <td><input type="text" class="validate[required] text-input" id="url" name="url" value="<?php echo $get_option_details['url']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Logo Url : </td>
                                        <td><input type="text" class="validate[required] text-input" id="logo-url" name="logo-url" value="<?php echo $get_option_details['logo-url']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Facebook : </td>
                                        <td><input type="text" class="validate[required] text-input" id="facebook" name="facebook" value="<?php echo $get_option_details['facebook']; ?>" /> </td>
                                    </tr>
                                    <tr height="50">
                                        <td>Twitter : </td>
                                        <td><input type="text" id="twitter" name="twitter" value="<?php echo $get_option_details['twitter']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Google+ : </td>
                                        <td><input type="text" class="text-input" id="googleplus" name="googleplus" value="<?php echo $get_option_details['googleplus']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Instagram : </td>
                                        <td><input type="text" id="instagram" name="instagram" value="<?php echo $get_option_details['instagram']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>YouTube : </td>
                                        <td><input type="text" id="youtube" name="youtube" value="<?php echo $get_option_details['youtube']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>LinkedIn : </td>
                                        <td><input type="text" id="linkedin" name="linkedin" value="<?php echo $get_option_details['linkedin']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Myspace : </td>
                                        <td><input type="text" id="myspace" name="myspace" value="<?php echo $get_option_details['myspace']; ?>" /></td>
                                    </tr>

                                </table>
                            </div>                         
                            <input class="button-primary" type="submit" value="Submit" name="submit" />    
                        </fieldset>
                    </form>
                </div>
            </div>           
        </div>
    </div>
    <?php
}

function saveSettings() {
    session_start();
    global $wpdb;
    if (isset($_POST['submit'])) {
        $insertArray = array();
        if ($_POST['type'] != '')
            $insertArray['type'] = $_POST['type'];
        if ($_POST['name'] != '')
            $insertArray['name'] = $_POST['name'];
        if ($_POST['url'] != '')
            $insertArray['url'] = $_POST['url'];
        if ($_POST['logo-url'] != '')
            $insertArray['logo-url'] = $_POST['logo-url'];
        if ($_POST['facebook'] != '')
            $insertArray['facebook'] = $_POST['facebook'];
        if ($_POST['twitter'] != '')
            $insertArray['twitter'] = $_POST['twitter'];
        if ($_POST['googleplus'] != '')
            $insertArray['googleplus'] = $_POST['googleplus'];
        if ($_POST['instagram'] != '')
            $insertArray['instagram'] = $_POST['instagram'];
        if ($_POST['youtube'] != '')
            $insertArray['youtube'] = $_POST['youtube'];
        if ($_POST['linkedin'] != '')
            $insertArray['linkedin'] = $_POST['linkedin'];
        if ($_POST['myspace'] != '')
            $insertArray['myspace'] = $_POST['myspace'];
        if (!empty($insertArray)) {
            $serialize_array = serialize($insertArray);
            update_option('wnp_social_settings', $serialize_array);
            $_SESSION['area_status'] = 'updated';
        }
        wp_redirect(admin_url('admin.php?page=web-nine-plc'));
    }
}

add_action('wp_footer', 'buffer_end');

function buffer_end() {
    $get_option_details = unserialize(get_option('wnp_social_settings'));
    echo '<script type="application/ld+json">
{ "@context" : "http://schema.org",
  "@type" : "' . $get_option_details['type'] . '",
  "name" : "' . $get_option_details['name'] . '",
  "url" : "' . $get_option_details['url'] . '",
  "logo": "' . $get_option_details['logo-url'] . '",
  "sameAs" : [ "' . $get_option_details['facebook'] . '","' . $get_option_details['twitter'] . '","' . $get_option_details['googleplus'] . '","' . $get_option_details['instagram'] . '","' . $get_option_details['youtube'] . '","' . $get_option_details['linkedin'] . '","' . $get_option_details['myspace'] . '"] 
}
</script>
';
}
