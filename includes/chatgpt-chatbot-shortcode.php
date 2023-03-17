<?php
/**
 * ChatGPT Chatbot for WordPress - Shortcode Registration
 *
 * This file contains the code for registering the shortcode used
 * to display the ChatGPT Chatbot on the website.
 *
 * @package chatgpt-chatbot
 */

function chatgpt_chatbot_shortcode() {
    ob_start();
    ?>
    <div id="chatgpt-chatbot">
        <div id="chatgpt-chatbot-header">
            <div id="chatgptTitle" class="title">ChatGPT Chatbot</div>
        </div>
        <div id="chatgpt-chatbot-conversation"></div>
        <div id="chatgpt-chatbot-input">
            <input type="text" id="chatgpt-chatbot-message" placeholder="Type your message...">
            <button id="chatgpt-chatbot-submit">Send</button>
        </div>
    </div>
    <!-- <button id="chatgpt-open-btn">+</button> -->
    <button id="chatgpt-open-btn">
    <i class="dashicons dashicons-format-chat"></i>
    </button>
    <?php
    return ob_get_clean();
}
add_shortcode('chatgpt_chatbot', 'chatgpt_chatbot_shortcode');
