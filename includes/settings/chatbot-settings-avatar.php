<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Avatar Page
 *
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the select avatar of choice and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Custom Avatar Icon - Ver 1.5.0
function chatbot_chatgpt_avatar_section_callback($args): void {

    echo '<div>
        <p>Choose an avatar that best represents you and your brand or link to your own avatar by adding a Custom Avatar URL (recommended 60x60px).</p>
        <p>It\'s ok you don\'t want an Avatar.  Just select the \'None\' option among the Avatar Icon Options below.</p>
        <p>Besure to remove the Custom Avatar URL if you want to select \'None\' or one from the set below.</p>
        <p>You can change your avatar at any time.</p>
        <p><b><i>Don\'t forget to click \'Save Settings\' to save your changes.</i><b></p>
    </div>';
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
    if (esc_attr(get_option('chatbot_chatgpt_custom_avatar_icon_setting')) !== '') {
        $selectedIcon = 'custom-000.png';
    } else {
        $selectedIcon = esc_attr(get_option('chatbot_chatgpt_avatar_icon_setting', 'icon-001.png'));
    }

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
                $selectedIcon = esc_attr(get_option('chatbot_chatgpt_avatar_icon_setting', 'icon-001.png'));
                foreach ($iconSets as $setName => $iconCount) {
                    $rows = ceil($iconCount / $cols);
                    $iconIndex = 0;

                    for ($i = 0; $i < $rows; $i++) {
                        echo '<tr>';
                        for ($j = 0; $j < $cols; $j++) {
                            if ($iconIndex < $iconCount) {
                                $iconName = sprintf(strtolower(str_replace(' ', '-', $setName)) . "-%03d.png", $iconIndex);
                                $selected = ($iconName === $selectedIcon) ? 'class="selected-icon"' : '';
                                echo '<td style="padding: 15px;">';
                                echo '<img src="' . plugins_url('../../assets/icons/'.$iconName, __FILE__) . '" id="'. $iconName .'" onclick="selectIcon(\''.$iconName.'\')" '.$selected.' style="width:60px;height:60px;cursor:pointer;"/>';
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

