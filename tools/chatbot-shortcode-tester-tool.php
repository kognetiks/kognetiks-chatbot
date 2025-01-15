<?php
/**
 * Kognetiks Chatbot - Chatbot Shortcode Tester - Ver 2.0.6
 *
 * Accepts up to three parameters and will display them.
 *
 * @param array  $atts    Shortcode attributes. Default empty.
 * @param string $content Shortcode content. Default null.
 * @param string $tag     Shortcode tag (name). Default empty.
 * @return string Shortcode output.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

function chatbot_chatgpt_short_code_tester_shortcode_tester($atts = [], $content = null, $tag = '') {

    // normalize attribute keys, lowercase
    $atts = array_change_key_case((array)$atts, CASE_LOWER);

    // override default attributes with user attributes
    $chatbot_chatgpt_short_code_tester_atts = shortcode_atts(
        array(
            'param1' => 'No parameter passed for param1',
            'param2' => 'No parameter passed for param2',
            'param3' => 'No parameter passed for param3',
        ), $atts, $tag
    );

    // start box
    $output_results = '<div class="chatbot_chatgpt_short_code_tester-box">';

    // display parameters
    $output_results .= '<p>Param1: ' . esc_html($chatbot_chatgpt_short_code_tester_atts['param1']) . '</p>';
    $output_results .= '<p>Param2: ' . esc_html($chatbot_chatgpt_short_code_tester_atts['param2']) . '</p>';
    $output_results .= '<p>Param3: ' . esc_html($chatbot_chatgpt_short_code_tester_atts['param3']) . '</p>';

    // enclosing tags
    if (!is_null($content)) {
        // Secure output by executing the_content filter hook on $content
        $output_results .= apply_filters('the_content', $content);
    }

    // end box
    $output_results .= '</div>';

    // return output
    return $output_results;

}

/**
 * Central location to create all shortcodes.
 */
function chatbot_chatgpt_short_code_tester_shortcodes_init() {
    add_shortcode('chatbot_chatgpt_short_code_tester', 'chatbot_chatgpt_short_code_tester_shortcode_tester');
}
add_action('init', 'chatbot_chatgpt_short_code_tester_shortcodes_init');

