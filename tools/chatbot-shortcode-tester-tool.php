<?php
/**
 * Kognetiks Chatbot for WordPress - Chatbot Shortcode Tester - Ver 2.0.6
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
    die;
}

function wporg_shortcode_tester($atts = [], $content = null, $tag = '') {

    // normalize attribute keys, lowercase
    $atts = array_change_key_case((array)$atts, CASE_LOWER);

    // override default attributes with user attributes
    $wporg_atts = shortcode_atts(
        array(
            'param1' => 'No parameter passed for param1',
            'param2' => 'No parameter passed for param2',
            'param3' => 'No parameter passed for param3',
        ), $atts, $tag
    );

    // start box
    $o = '<div class="wporg-box">';

    // display parameters
    $o .= '<p>Param1: ' . esc_html($wporg_atts['param1']) . '</p>';
    $o .= '<p>Param2: ' . esc_html($wporg_atts['param2']) . '</p>';
    $o .= '<p>Param3: ' . esc_html($wporg_atts['param3']) . '</p>';

    // enclosing tags
    if (!is_null($content)) {
        // Secure output by executing the_content filter hook on $content
        $o .= apply_filters('the_content', $content);
    }

    // end box
    $o .= '</div>';

    // return output
    return $o;
}

/**
 * Central location to create all shortcodes.
 */
function wporg_shortcodes_init() {
    add_shortcode('wporg', 'wporg_shortcode_tester');
}
add_action('init', 'wporg_shortcodes_init');

