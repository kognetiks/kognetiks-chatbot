jQuery(document).ready(function ($) {
    
    function chatbot_chatgpt_localize() {

        // console.log('ENTERING chatbot_chatgpt_localize');

        // Access the variables passed from PHP using the chatbotSettings object - Ver 1.4.1
        var chatgptName = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_bot_name) ? chatbotSettings.chatgpt_bot_name : 'Chatbot ChatGPT';
        var chatgptInitialGreeting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.initial_greeting) ? chatbotSettings.initial_greeting : 'Hello! How can I help you today?';
        var chatgptSubsequentGreeting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_subsequent_greeting) ? chatbotSettings.chatgpt_subsequent_greeting : 'Hello again! How can I help you?';
        var chatgptStartStatus = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgptStartStatus) ? chatbotSettings.chatgptStartStatus : 'closed';
        var chatgptStartStatusNewVisitor = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgptStartStatusNewVisitor) ? chatbotSettings.chatgptStartStatusNewVisitor : 'closed';
        var chatgptDisclaimerSetting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_disclaimer_setting) ? chatbotSettings.chatgpt_disclaimer_setting : 'Yes';
        var chatgptMaxTokensSetting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_max_tokens_setting) ? chatbotSettings.chatgpt_max_tokens_setting : '150';
        var chatgptWidthSetting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_width_setting) ? chatbotSettings.chatgpt_width_setting : 'Narrow';
        var chatgptDiagnosticsSetting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_diagnotics) ? chatbotSettings.chatgpt_diagnotics : 'Off';
        // Avatar Setting - Ver 1.5.0
        var chatgptAvatarIconSetting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_avatar_icon_setting) ? chatbotSettings.chatgpt_avatar_icon_setting : 'icon-001.png';
        var chatgptAvatarIconURLSetting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_avatar_icon_url_setting) ? chatbotSettings.chatgpt_avatar_icon_url_setting : 'icon-001.png';
        var chatgptCustomAvatarIconSetting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_custom_avatar_icon_setting) ? chatbotSettings.chatgpt_custom_avatar_icon_setting : 'icon-001.png';
        var chatgptAvatarGreetingSetting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_avatar_greeting_setting) ? chatbotSettings.chatgpt_avatar_greeting_setting : 'Great to see you today! How can I help you?';

        let chatbotSettings = " . json_encode($chatbot_settings) . ";
    
        Object.keys(chatbotSettings).forEach((key) => {
            if(!localStorage.getItem(key)) {
                // DIAG - Log the key and value
                // console.log('Setting ' + key + ' in localStorage');
                localStorage.setItem(key, chatbotSettings[key]);
            } else {
                // DIAG - Log the key and value
                // console.log(key + ' is already set in localStorage');
            }
        });

        // Get the input elements
        var chatgptNameInput = document.getElementById('chatgpt_bot_name');
        var chatgptInitialGreetingInput = document.getElementById('chatgpt_initial_greeting');
        var chatgptSubsequentGreetingInput = document.getElementById('chatgpt_subsequent_greeting');
        var chatgptStartStatusInput = document.getElementById('chatgptStartStatus');
        var chatgptStartStatusNewVisitorInput = document.getElementById('chatgptStartStatusNewVisitor');
        var chatgptDisclaimerSettingInput = document.getElementById('chatgpt_disclaimer_setting');
        var chatgptMaxTokensSettingInput = document.getElementById('chatgpt_max_tokens_setting');
        var chatgptWidthSettingInput = document.getElementById('chatgpt_width_setting');
        var chatgptDiagnosticsSettingInput = document.getElementById('chatgpt_diagnostics_setting');
        // Avatar Setting - Ver 1.5.0
        var chatgptAvatarIconSettingInput = document.getElementById('chatgpt_avatar_icon_setting');
        var chatgptCustomAvatarIconSettingInput = document.getElementById('chatgpt_custom_avatar_icon_setting');
        var chatgptAvatarGreetingSettingInput = document.getElementById('chatgpt_avatar_greeting_setting');

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
                localStorage.setItem('chatgptStartStatus', this.options[this.selectedIndex].value);
            });
        }

        if(chatgptStartStatusNewVisitorInput) {
            chatgptStartStatusNewVisitorInput.addEventListener('change', function() {
                localStorage.setItem('chatgptStartStatusNewVisitor', this.options[this.selectedIndex].value);
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

        if(chatgptDiagnosticsSettingInput) {
            chatgptDiagnosticsSettingInput.addEventListener('change', function() {
                localStorage.setItem('chatgpt_diagnostics', this.options[this.selectedIndex].value);
            });
        }

        // Avatar Settings - Ver 1.5.0
        if(document.getElementById('chatgpt_avatar_icon_setting')) {
            document.getElementById('chatgpt_avatar_icon_setting').addEventListener('change', function() {
                localStorage.setItem('chatgpt_avatar_icon_setting', this.value);
            });
        }

        if(document.getElementById('chatgpt_custom_avatar_icon_setting')) {
            document.getElementById('chatgpt_custom_avatar_icon_setting').addEventListener('change', function() {
                localStorage.setItem('chatgpt_custom_avatar_icon_setting', this.value);
            });
        }
        
        if(document.getElementById('chatgpt_avatar_greeting_setting')) {
            document.getElementById('chatgpt_avatar_greeting_setting').addEventListener('change', function() {
                localStorage.setItem('chatgpt_avatar_greeting_setting', this.value);
            });
        }        

        // Update the localStorage values when the form is submitted - Ver 1.4.1
        // chatgpt-settings-form vs. your-form-id
        var chatgptSettingsForm = document.getElementById('chatgpt-settings-form');

        if (chatgptSettingsForm) {
            chatgptSettingsForm.addEventListener('submit', function(event) {

                event.preventDefault(); // Prevent form submission

                // Changed const to var - Ver 1.5.0
                var chatgptNameInput = document.getElementById('chatgpt_bot_name');
                var chatgptInitialGreetingInput = document.getElementById('chatgpt_initial_greeting');
                var chatgptSubsequentGreetingInput = document.getElementById('chatgpt_subsequent_greeting');
                var chatgptStartStatusInput = document.getElementById('chatgptStartStatus');
                var chatgptStartStatusNewVisitorInput = document.getElementById('chatgptStartStatusNewVisitor');
                var chatgptDisclaimerSettingInput = document.getElementById('chatgpt_disclaimer_setting');
                var chatgptMaxTokensSettingInput = document.getElementById('chatgpt_max_tokens_setting');
                var chatgptWidthSettingInput = document.getElementById('chatgpt_width_setting');
                var chatgptDiagnosticsSettingInput = document.getElementById('chatgpt_diagnostics_setting');
                // Avatar Settings - Ver 1.5.0
                var chatgptAvatarIconSettingInput = document.getElementById('chatgpt_avatar_icon_setting');
                var chatgptCustomAvatarIconSettingInput = document.getElementById('chatgpt_custom_avatar_icon_setting');
                var chatgptAvatarGreetingSettingInput = document.getElementById('chatgpt_avatar_greeting_setting');

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
                    localStorage.setItem('chatgptStartStatus', chatgptStartStatusInput.value);
                }

                if(chatgptStartStatusNewVisitorInput) {
                    localStorage.setItem('chatgptStartStatusNewVisitor', chatgptStartStatusNewVisitorInput.value);
                }

                if(chatgptDisclaimerSettingInput) {
                    localStorage.setItem('chatgpt_disclaimer_setting', chatgptDisclaimerSettingInput.value);
                }

                if(chatgptMaxTokensSettingInput) {
                    localStorage.setItem('chatgpt_max_tokens_setting', chatgptMaxTokensSettingInput.value);
                }

                if(chatgptWidthSettingInput) {
                    localStorage.setItem('chatgpt_width_setting', chatgptWidthSettingInput.value);
                }

                if(chatgptDiagnosticsSettingInput) {
                    localStorage.setItem('chatgpt_diagnostics', chatgptDiagnosticsSettingInput.value)
                }

                // Avatar Settings - Ver 1.5.0
                if(chatgptAvatarIconSettingInput) {
                    localStorage.setItem('chatgpt_avatar_icon_setting', chatgptAvatarIconSettingInput.value);
                }

                // Avatar Settings - Ver 1.5.0
                if(chatgptCustomAvatarIconSettingInput) {
                    localStorage.setItem('chatgpt_custom_avatar_icon_setting', chatgptCustomAvatarIconSettingInput.value);
                }
                
                // Avatar Settings - Ver 1.5.0
                if(chatgptAvatarGreetingSettingInput) {
                    localStorage.setItem('chatgpt_avatar_greeting_setting', chatgptAvatarGreetingSettingInput.value);
                }

            });
        }

        // DIAG - Log exiting the function
        // console.log('EXITING chatbot_chatgpt_localize');
        
    }

    chatbot_chatgpt_localize();

});
