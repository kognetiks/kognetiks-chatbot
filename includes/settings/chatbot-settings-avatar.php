<?php
/**
 * Kognetiks Chatbot - Settings - Avatar Page
 *
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the select avatar of choice and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

function chatbot_chatgpt_avatar_overview_section_callback($args) {

    ?>
    <div>
        <p>Choose an avatar that best represents you and your brand or link to your own avatar by adding a Custom Avatar URL (recommended 60x60px).</p>
        <p>It's ok you don't want an Avatar.  Just select the 'None' option among the Avatar Icon Options below.</p>
        <p>Be sure to remove the Custom Avatar URL if you want to select 'None' or one from the set below.</p>
        <p>You can change your avatar at any time.</p>
        <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
        <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use Avatars and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=avatars&file=avatars.md">here</a>.</b></p>
    </div>
    <?php

}

// Custom Avatar Icon - Ver 1.5.0
function chatbot_chatgpt_avatar_section_callback($args) {

}
 
// Custom Avatar Icon - Ver 1.5.0
function chatbot_chatgpt_custom_avatar_callback($args) {

    // Check for a custom avatar icon
    $custom_avatar_icon = esc_attr(get_option('chatbot_chatgpt_custom_avatar_icon_setting', ''));

    ?>
        <input type="text" id="chatbot_chatgpt_custom_avatar_icon_setting" name="chatbot_chatgpt_custom_avatar_icon_setting" value="<?php echo esc_attr( $custom_avatar_icon ); ?>" class="regular-text">
    <?php

}

// Avatar Icon Set - Ver 1.6.7
function chatbot_chatgpt_avatar_icon_set_callback($args) {

    // Get the avatar set option. If it's not set or is NULL, default to custom value.
    $avatar_icon_set = esc_attr(get_option('chatbot_chatgpt_avatar_icon_set', 'Original'));

    ?>
        <select id="chatbot_chatgpt_avatar_icon_set" name="chatbot_chatgpt_avatar_icon_set">
            <?php
            $options = array("Original", "Chinese New Year", "Christmas", "Fall", "Halloween", "Spring", "Summer", "Thanksgiving", "Winter");
            foreach ($options as $option) {
                $selected = ($avatar_icon_set == $option) ? 'selected' : '';
                echo "<option value=\"$option\" $selected>$option</option>";
            }
            ?>
        </select>
    <?php

}

// Avatar Icon - Ver 1.5.0
function chatbot_chatgpt_avatar_greeting_callback($args) {

    // Get the avatar greeting option. If it's not set, is NULL, or is an empty string, default to custom value.
    $avatar_greeting = esc_attr(get_option('chatbot_chatgpt_avatar_greeting_setting', 'Howdy!!! Great to see you today! How can I help you?'));

    // If avatar greeting is still an empty string, assign it the default value
    if (empty($avatar_greeting)) {
        $avatar_greeting = 'Howdy!!! Great to see you today! How can I help you?';
    }

    ?>
        <input type="text" id="chatbot_chatgpt_avatar_greeting_setting" name="chatbot_chatgpt_avatar_greeting_setting" value="<?php echo esc_attr($avatar_greeting); ?>" class="regular-text">
    <?php

}


// Avatar Icon settings section callback - Ver 1.5.0
function chatbot_chatgpt_avatar_icon_callback($args) {

    // Try to get the current setting for the avatar icon.
    $selectedIcon = esc_attr(get_option('chatbot_chatgpt_avatar_icon_setting', 'icon-001.png'));
    $customAvatarIcon = esc_attr(get_option('chatbot_chatgpt_custom_avatar_icon_setting'));

    // DIAG - Diagnostics - Ver 2.0.3

    if ($customAvatarIcon !== '') {
        $selectedIcon = 'custom-000.png';
        update_option('chatbot_chatgpt_avatar_icon_setting', $selectedIcon);
    }

    // DIAG - Diagnostics - Ver 2.0.3

    ?>
        <p>Select your icon by clicking on an image to select it.  Don't forget to click 'Save Settings'.</p>
        <input type="hidden" id="chatbot_chatgpt_avatar_icon_setting" name="chatbot_chatgpt_avatar_icon_setting" value="<?php echo esc_attr( $selectedIcon ); ?>">
        <table>
            <?php
                $iconSets = [
                    "Icon" => 30,
                    "Chinese" => 10,
                    "Christmas" => 10,
                    "Fall" => 10,
                    "Halloween" => 10,
                    "Spring" => 10,
                    "Summer" => 10,
                    "Thanksgiving" => 10,
                    "Winter" => 10,
                    "Custom" => 1
                ];
                $cols = 10;

                foreach ($iconSets as $setName => $iconCount) {
                    $rows = ceil($iconCount / $cols);
                    $iconIndex = 0;

                    for ($i = 0; $i < $rows; $i++) {
                        echo '<tr>';
                        for ($j = 0; $j < $cols; $j++) {
                            if ($iconIndex < $iconCount) {
                                $iconName = sprintf(strtolower(str_replace(' ', '-', $setName)) . "-%03d.png", $iconIndex);
                                
                                // DIAG - Diagnostics - Ver 2.0.3

                                $selected = ($iconName === $selectedIcon) ? 'class="selected-icon"' : '';
                                echo '<td style="padding: 15px;">';
                                echo '<img src="' . plugins_url('assets/icons/'.$iconName, dirname(__FILE__, 2)) . '" id="'. $iconName .'" onclick="selectIcon(\''.$iconName.'\')" '.$selected.' style="width:60px;height:60px;cursor:pointer;"/>';
                                echo '</td>';
                                $iconIndex++;
                            }
                        }
                        echo '</tr>';
                    }
                }
            ?>
        </table>
    <?php

}

// Register Avatar settings - Ver 2.0.7
function chatbot_chatgpt_avatar_settings_init() {

    // Avatar settings tab - Ver 1.5.0
    register_setting('chatbot_chatgpt_avatar', 'chatbot_chatgpt_avatar_icon_setting');
    register_setting('chatbot_chatgpt_avatar', 'chatbot_chatgpt_avatar_icon_url_setting');
    register_setting('chatbot_chatgpt_avatar', 'chatbot_chatgpt_custom_avatar_icon_setting');
    register_setting('chatbot_chatgpt_avatar', 'chatbot_chatgpt_avatar_greeting_setting');
    register_setting('chatbot_chatgpt_avatar', 'chatbot_chatgpt_avatar_icon_set');

    // Register Avatar Overview
    add_settings_section(
        'chatbot_chatgpt_avatar_overview_section', 
        'Avatar Settings Overview', 
        'chatbot_chatgpt_avatar_overview_section_callback', 
        'chatbot_chatgpt_avatar_overview'
    );

    // Register Avatar Setting
    add_settings_section(
        'chatbot_chatgpt_avatar_section', 
        'Avatar Settings', 
        'chatbot_chatgpt_avatar_section_callback', 
        'chatbot_chatgpt_avatar'
    );

    // Avatar Greeting
    add_settings_field(
        'chatbot_chatgpt_avatar_greeting_setting',
        'Avatar Greeting',
        'chatbot_chatgpt_avatar_greeting_callback',
        'chatbot_chatgpt_avatar',
        'chatbot_chatgpt_avatar_section'
    );

    // Custom Avatar URL
    add_settings_field(
        'chatbot_chatgpt_custom_avatar_icon_setting',
        'Custom Avatar URL (60x60px)',
        'chatbot_chatgpt_custom_avatar_callback',
        'chatbot_chatgpt_avatar',
        'chatbot_chatgpt_avatar_section'
    );

    // Avatar Icon Set
    // add_settings_field(
    //     'chatbot_chatgpt_avatar_icon_set',
    //     'Avatar Icon Set',
    //     'chatbot_chatgpt_avatar_icon_set_callback',
    //     'chatbot_chatgpt_avatar',
    //     'chatbot_chatgpt_avatar_section'
    // );
    
    // Avatar Icon Selection - None, Custom, or one from the various sets
    add_settings_field(
        'chatbot_chatgpt_avatar_icon_setting',
        'Avatar Icon Options',
        'chatbot_chatgpt_avatar_icon_callback',
        'chatbot_chatgpt_avatar',
        'chatbot_chatgpt_avatar_section'
    );    

}
add_action('admin_init', 'chatbot_chatgpt_avatar_settings_init');
