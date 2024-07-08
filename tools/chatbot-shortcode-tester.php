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

function chatbot_shortcode_tester() {

    ?>
    <h2>Test 1: Calling shortcode without any parameters</h2>
    <div>
        <?php echo do_shortcode('[chatbot_chatgpt_short_code_tester]'); ?>
    </div>

    <h2>Test 2: Calling shortcode with one parameter</h2>
    <div>
        <?php echo do_shortcode('[chatbot_chatgpt_short_code_tester param1="cat"]'); ?>
    </div>

    <h2>Test 3: Calling shortcode with three parameters</h2>
    <div>
        <?php echo do_shortcode('[chatbot_chatgpt_short_code_tester param1="dog" param2="horse" param3="elephant"]'); ?>
    </div>
    <h3>Results:<h3>
    <div>
        <p>If Test 1 shows "No parameters passed for param1, 2 and 3", then shortcoded are working correctly.</p>
        <p>If Test 2 shows "cat and no parameters passed for param2 and 3", then shortcodes are working correctly.</p>
        <p>If Test 3 shows "dog, horse and elephant", then the shortcode are working correctly.</p>
    <?php

}
