jQuery(document).ready(function ($) {
    
    function chatbot_chatgpt_localize() {
        // Access the variables passed from PHP using the chatbotSettings object - Ver 1.4.1
        var chatgptName = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_bot_name) ? chatbotSettings.chatgpt_bot_name : 'Chatbot ChatGPT';
        var chatgptInitialGreeting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.initial_greeting) ? chatbotSettings.initial_greeting : 'Hello! How can I help you today?';
        var chatgptSubsequentGreeting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_subsequent_greeting) ? chatbotSettings.chatgpt_subsequent_greeting : 'Hello again! How can I help you?';
        var chatgptStartStatus = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatGPTChatBotStatus) ? chatbotSettings.chatGPTChatBotStatus : 'closed';
        var chatgptDisclaimerSetting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_disclaimer_setting) ? chatbotSettings.chatgpt_disclaimer_setting : 'Yes';
        var chatgptMaxTokensSetting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_max_tokens_setting) ? chatbotSettings.chatgpt_max_tokens_setting : '150';
        var chatgptWidthSetting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_width_setting) ? chatbotSettings.chatgpt_width_setting : 'Narrow';

        // Get the input elements
        var chatgptNameInput = document.getElementById('chatgpt_bot_name');
        var chatgptInitialGreetingInput = document.getElementById('chatgpt_initial_greeting');
        var chatgptSubsequentGreetingInput = document.getElementById('chatgpt_subsequent_greeting');
        var chatgptStartStatusInput = document.getElementById('chatGPTChatBotStatus');
        var chatgptDisclaimerSettingInput = document.getElementById('chatgpt_disclaimer_setting');
        var chatgptMaxTokensSettingInput = document.getElementById('chatgpt_max_tokens_setting');
        var chatgptWidthSettingInput = document.getElementById('chatgpt_width_setting');

        if(chatgptNameInput) {
            chatgptNameInput.addEventListener('change', function() {
                localStorage.setItem('chatgpt_bot_name', this.value);
            });
        }

        if(chatgptInitialGreetingInput) {
            chatgptInitialGreetingInput.addEventListener('change', function() {
                localStorage.setItem('chatgpt_initial_greeting', this.value);
            });
        }

        if(chatgptSubsequentGreetingInput) {
            chatgptSubsequentGreetingInput.addEventListener('change', function() {
                localStorage.setItem('chatgpt_subsequent_greeting', this.value);
            });
        }
        
        if(chatgptStartStatusInput) {
            chatgptStartStatusInput.addEventListener('change', function() {
                localStorage.setItem('chatGPTChatBotStatus', this.options[this.selectedIndex].value);
            });
        }

        if(chatgptDisclaimerSettingInput) {
            chatgptDisclaimerSettingInput.addEventListener('change', function() {
                localStorage.setItem('chatgpt_disclaimer_setting', this.options[this.selectedIndex].value);
            });
        }

        if(chatgptMaxTokensSettingInput) {
            chatgptMaxTokensSettingInput.addEventListener('change', function() {
                localStorage.setItem('chatgpt_max_tokens_setting', this.options[this.selectedIndex].value);
            });
        }

        if(chatgptWidthSettingInput) {
            chatgptWidthSettingInput.addEventListener('change', function() {
                localStorage.setItem('chatgpt_width_setting', this.options[this.selectedIndex].value);
            });
        }

        // Update the localStorage values when the form is submitted - Ver 1.4.1
        // chatgpt-settings-form vs. your-form-id
        var chatgptSettingsForm = document.getElementById('chatgpt-settings-form');

        if (chatgptSettingsForm) {
            chatgptSettingsForm.addEventListener('submit', function(event) {

                event.preventDefault(); // Prevent form submission

                const chatgptNameInput = document.getElementById('chatgpt_bot_name');
                const chatgptInitialGreetingInput = document.getElementById('chatgpt_initial_greeting');
                const chatgptSubsequentGreetingInput = document.getElementById('chatgpt_subsequent_greeting');
                const chatgptStartStatusInput = document.getElementById('chatGPTChatBotStatus');
                const chatgptDisclaimerSettingInput = document.getElementById('chatgpt_disclaimer_setting');
                const chatgptMaxTokensSettingInput = document.getElementById('chatgpt_max_tokens_setting');
                const chatgptWidthSettingInput = document.getElementById('chatgpt_width_setting');

                if(chatgptNameInput) {
                    localStorage.setItem('chatgpt_bot_name', chatgptNameInput.value);
                }

                if(chatgptInitialGreetingInput) {
                    localStorage.setItem('chatgpt_initial_greeting', chatgptInitialGreetingInput.value);
                }

                if(chatgptSubsequentGreetingInput) {
                    localStorage.setItem('chatgpt_subsequent_greeting', chatgptSubsequentGreetingInput.value);
                }

                if(chatgptStartStatusInput) {
                    localStorage.setItem('chatGPTChatBotStatus', chatgptStartStatusInput.value);
                }

                if(chatgptDisclaimerSettingInput) {
                    localStorage.setItem('chatgpt_disclaimer_setting', chatgptDisclaimerSettingInput.value);
                }

                if(chatgptMaxTokensSettingInput) {
                    localStorage.setItem('chatgpt_max_tokens_setting', chatgptMaxTokensSettingInput.value);
                }

                if(chatgptWidthSettingInput) {
                    localStorage.setItem('chatgpt_width_setting', chatgptWidthSettingInput.value)
                }

            });
        }
    }

    chatbot_chatgpt_localize();

});
