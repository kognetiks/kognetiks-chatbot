jQuery(document).ready(function ($) {
    
    function chatbot_chatgpt_localize() {
    
        // DIAG - Diagnostics - Ver 2.4.5
        // console.log('Chatbot: NOTICE: chatbot-chatgpt-local.js - Before localStorage.set Item loop');

        // Resolve LocalStorage - Ver 2.4.5
        const includeKeys = [
            'chatbot_chatgpt_last_reset',
            'chatbot_chatgpt_message_count',
            'chatbot_chatgpt_display_message_count',
            'chatbot_chatgpt_message_limit_setting',
            'chatbot_chatgpt_message_limit_period_setting',
            'chatbot_chatgpt_start_status',
            'chatbot_chatgpt_start_status_new_visitor',
            'chatbot_chatgpt_opened',
            'chatbot_chatgpt_last_reset',
            'chatbot_chatgpt_appearance_open_icon',
            'chatbot_chatgpt_appearance_collapse_icon',
            'chatbot_chatgpt_appearance_erase_icon',
            'chatbot_chatgpt_appearance_mic_enabled_icon',
            'chatbot_chatgpt_appearance_mic_disabled_icon',
        ];
        
        if (typeof kchat_settings === "object") {
            Object.keys(kchat_settings).forEach(function(key) {
                if (includeKeys.includes(key)) {
                    localStorage.setItem(key, kchat_settings[key]);
                    // DIAG - Diagnostics - Ver 2.4.5
                    // console.log("Chatbot: NOTICE: chatbot-shortcode.php - Key: " + key + " Value: " + kchat_settings[key]);
                }
            });
        }

        // DIAG - Diagnostics - Ver 2.4.5
        // console.log('Chatbot: NOTICE: chatbot-chatgpt-local.js - After localStorage.set Item loop');

    }

    // Function to check if the chatbot shortcode is present on the page
    function isChatbotShortcodePresent() {
        // console.log('Chatbot: NOTICE: chatbot-chatgpt-local.js - isChatbotShortcodePresent: ' + document.querySelector('.chatbot-chatgpt') !== null);
        return document.querySelector('.chatbot-chatgpt') !== null;
    }

    // Only call the function if the chatbot shortcode is present
    if (isChatbotShortcodePresent()) {
        // console.log('Chatbot: NOTICE: chatbot-chatgpt-local.js - isChatbotShortcodePresent: ' + isChatbotShortcodePresent());
        chatbot_chatgpt_localize();
    }

});
