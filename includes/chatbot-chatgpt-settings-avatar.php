<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Avatar Page
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

// Custom Avatar Icon - Ver 1.5.0
function chatbot_chatgpt_avatar_section_callback($args) {
    echo '<p>Configure your avatar settings below:</p>';
}
 
// Custom Avatar Icon - Ver 1.5.0
function chatbot_chatgpt_custom_avatar_callback($args) {
    // Get the avatar option. If it's not set or is NULL, default to custom value.
    $custom_avatar_icon = "";
    $custom_avatar_icon = esc_attr(get_option('chatgpt_custom_avatar_icon_setting', 'icon-001.png'));
    ?>
    <input type="text" id="chatgpt_custom_avatar_icon_setting" name="chatgpt_custom_avatar_icon_setting" value="<?php echo esc_attr( $custom_avatar_icon ); ?>" class="regular-text">
    <?php
}

// Avatar Icon - Ver 1.5.0
function chatbot_chatgpt_avatar_greeting_callback($args) {
    // Get the avatar greeting option. If it's not set, is NULL, or is an empty string, default to custom value.
    $avatar_greeting = esc_attr(get_option('chatgpt_avatar_greeting_setting', 'Howdy!!! Great to see you today! How can I help you?'));

    // If avatar greeting is still an empty string, assign it the default value
    if (empty($avatar_greeting)) {
        $avatar_greeting = 'Howdy!!! Great to see you today! How can I help you?';
    }
    ?>
    <input type="text" id="chatgpt_avatar_greeting_setting" name="chatgpt_avatar_greeting_setting" value="<?php echo esc_attr($avatar_greeting); ?>" class="regular-text">
    <?php
}


// Avatar Icon settings section callback - Ver 1.5.0
function chatbot_chatgpt_avatar_icon_callback($args) {
    // Get the avatar option. If it's not set or is NULL, default to the first icon.
    $selectedIcon = esc_attr(get_option('chatgpt_avatar_icon_setting', 'icon-001.png'));
    if ($selectedIcon === '') {
        $selectedIcon = 'icon-001.png';
    }
    ?>
    <p>Select your icon by clicking on an image to select it.  Don't forget to click 'Save Settings'.</p>
    <input type="hidden" id="chatgpt_avatar_icon_setting" name="chatgpt_avatar_icon_setting" value="<?php echo esc_attr( $selectedIcon ); ?>">
    <table>
        <?php
            $iconCount = 29;  // Update this number as you add more icons
            $cols = 10;
            $rows = 5;
            $iconIndex = 0;

            // $selectedIcon = esc_attr(get_option('chatgpt_avatar_icon_setting', 'icon-001.png'));
            
            for($i = 0; $i < $rows; $i++) {
                echo '<tr>';
                for($j = 0; $j < $cols; $j++) {
                    if ($iconIndex <= $iconCount) {
                        $iconName = sprintf("icon-%03d.png", $iconIndex);
                        $selected = ""; 
                        $selected = ($iconName === $selectedIcon) ? 'class="selected-icon"' : '';
                        echo '<td  style="padding: 15px;">';
                        // change the id attribute of the image tag to replace '-' with '_'
                        echo '<img src="' . plugins_url('../assets/icons/'.$iconName, __FILE__) . '" id="'. $iconName .'" onclick="selectIcon(\''.$iconName.'\')" '.$selected.' style="width:100px;height:100px;cursor:pointer;"/>';
                        echo '</td>';
                        $iconIndex++;
                    }
                }
                echo '</tr>';
            }
        ?>
    </table>
    <?php
}
