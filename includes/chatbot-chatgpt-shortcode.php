<?php
/**
 * Chatbot ChatGPT for WordPress - Shortcode Registration
 *
 * This file contains the code for registering the shortcode used
 * to display the Chatbot ChatGPT on the website.
 *
 * @package chatbot-chatgpt
 */

function chatbot_chatgpt_shortcode() {
    // Retrieve the bot name - Ver 1.1.0
    // Add styling to the bot to ensure that it is not shown before it is needed Ver 1.2.0
    $bot_name = esc_attr(get_option('chatgpt_bot_name', 'Hi there!'));
    ob_start();
    $icons_url =  plugins_url('/icons', __FILE__);
    ?>
    <div id="chatbot-chatgpt" style="display: none;" class="rounded-15">
        <div id="chatbot-chatgpt-header" class="header-back header-rounded flex flex-row justify-between" >
            <div id="chatgptTitle" class="title"><span><?php echo $bot_name; ?></span><img src="<?php echo $icons_url . '/wave.png';?>" alt="Hi" class="wave-size" ></div>
            <div class="right-header flex items-center ">
                <div class="action-btn" id="action-option">
                  <i class="fa fa-ellipsis-v"></i>
                  <span class="tooltip-text">Open options</span>
                </div>
                <div style="position: absolute; background: white;top: 60px; right: 40px;  border-radius: 5px; padding: 5px; z-index: 11; box-shadow: rgba(0, 18, 46, 0.16) 0px 8px 36px 0px; display:none" id="item-group">
                    <div style="display:flex;  flex-direction: column;" >
                        <div class="item-style"> <i class="fa fa-bell-slash"></i> Turn off notifications </div>
                        <div class="item-style"> <i class="fa fa-star" style="color: #ffe500;"></i> Rate this conversation </div>
                    </div>
                </div>
                <div id="collapse_action" class="action-btn">
                  <i class="fa fa-chevron-down"></i> 
                </div>
            </div>
        </div>
        <div class="state-display"> 
         <img src="<?php echo $icons_url . '/state-back.png';?>" class="state-back" alt="">
          <div class="state-text" style=""><div class="state-badge"></div> <span>We are online</span></div>
        </div>
        <div id="chatbot-chatgpt-conversation"></div>
        <div id="chatbot-chatgpt-input">
            <textarea id="chatbot-chatgpt-message" rows="1" placeholder="<?php echo esc_attr( 'Enter your message...' ); ?>" maxlength="2000"></textarea>
            <div id="chatbot-chatgpt-submit">
               <i class="fa fa-paper-plane" style="color:white; font-size:20px"></i>
            </div>
        </div>
        
        <div class="flex justify-end">
            <div class="flex logo">
                <span class="com-log"> POWERED BY</span>
                <div class="logo"> </div>
            </div> 
        </div>
    </div>
    <button id="chatgpt-open-btn" style="display: none;">
        <i class="dashicons dashicons-format-chat"></i>
    </button>
    <?php
    return ob_get_clean();
}
add_shortcode('chatbot_chatgpt', 'chatbot_chatgpt_shortcode');
