jQuery(document).ready(function ($) {
    
    function chatbot_chatgpt_localize() {
   
        // DIAG - Diagnostics - Ver 1.8.5
        // console.log('Entering chatbot_chatgpt_localize');

        // Access the variables passed from PHP using the chatbotSettings object - Ver 1.4.1
        var chatbotChatgptBotNameInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_bot_name) ? chatbotSettings.chatbot_chatgpt_bot_name : 'Chatbot ChatGPT';
        var chatbotChatgptBotPromptInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_bot_prompt) ? chatbotSettings.chatbot_chatgpt_bot_prompt : 'Enter your question ...';

        var chatgptInitialGreetingInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.initial_greeting) ? chatbotSettings.initial_greeting : 'Hello! How can I help you today?';
        var chatgptSubsequentGreetingInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_subsequent_greeting) ? chatbotSettings.chatbot_chatgpt_subsequent_greeting : 'Hello again! How can I help you?';

        var chatbotChatgptDisplayStyleInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_display_style) ? chatbotSettings.chatbot_chatgpt_display_style : 'floating';
        var chatbotChatgptAssistantAliasInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_assistant_alias) ? chatbotSettings.chatbot_chatgpt_assistant_alias : 'primary';

        var chatgptStartStatusInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbotStartStatus) ? chatbotSettings.chatbotStartStatus : 'closed';
        var chatbotChatgptStartStatusNewVisitorInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_start_status_new_visitor) ? chatbotSettings.chatbot_chatgpt_start_status_new_visitor : 'closed';

        var chatgptDisclaimerSettingInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_disclaimer_setting) ? chatbotSettings.chatbot_chatgpt_disclaimer_setting : 'Yes';
        var chatgptWidthSettingInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_width_setting) ? chatbotSettings.chatbot_chatgpt_width_setting : 'Narrow';
        var chatgptDiagnosticsSettingInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_diagnotics) ? chatbotSettings.chatbot_chatgpt_diagnostics : 'Off';

        // Avatar Setting - Ver 1.5.0
        var chatgptAvatarIconSettingInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_avatar_icon_setting) ? chatbotSettings.chatbot_chatgpt_avatar_icon_setting : 'icon-001.png';
        var chatgptAvatarIconURLSettingInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_avatar_icon_url_setting) ? chatbotSettings.chatbot_chatgpt_avatar_icon_url_setting : '';
        var chatgptCustomAvatarIconSettingInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_custom_avatar_icon_setting) ? chatbotSettings.chatbot_chatgpt_custom_avatar_icon_setting : '';
        var chatgptAvatarGreetingSettingInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_avatar_greeting_setting) ? chatbotSettings.chatbot_chatgpt_avatar_greeting_setting : 'Great to see you today! How can I help you?';

        var chatgptEnableCustomButtonsInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_enable_custom_buttons) ? chatbotSettings.chatbot_chatgpt_enable_custom_buttons : 'Off';
        var chatgptCustomButtonName1Input = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_custom_button_name_1) ? chatbotSettings.chatbot_chatgpt_custom_button_name_1 : '';
        var chatgptCustomButtonURL1Input = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_custom_button_url_1) ? chatbotSettings.chatbot_chatgpt_custom_button_url_1 : '';
        var chatgptCustomButtonName2Input = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_custom_button_name_2) ? chatbotSettings.chatbot_chatgpt_custom_button_name_2 : '';
        var chatgptCustomButtonURL2Input = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_custom_button_url_2) ? chatbotSettings.chatbot_chatgpt_custom_button_url_2 : '';

        // Allow file uploads - Ver 1.7.6
        var chatgptAllowFileUploadsInput = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatbot_chatgpt_allow_file_uploads) ? chatbotSettings.chatbot_chatgpt_allow_file_uploads : 'No';
    
        // DIAG - Diagnostics - Ver 1.8.5
        console.log('Before localStorage.set Item loop');

        Object.keys(chatbotSettings).forEach((key) => {

            localStorage.setItem(key, chatbotSettings[key]);
            
            // DIAG - Diagnostics - Ver 1.8.5
            console.log('Setting ' + key + ' Value ' + chatbotSettings[key] + ' in localStorage');

        });

        // DIAG - Diagnostics - Ver 1.8.5
        console.log('After localStorage.set Item loop');

        // Get the input elements
        var chatbotChatgptBotNameInput = document.getElementById('chatbot_chatgpt_bot_name');
        var chatbotChatgptBotPromptInput = document.getElementById('chatbot_chatgpt_bot_prompt');

        var chatgptInitialGreetingInput = document.getElementById('chatbot_chatgpt_initial_greeting');
        var chatgptSubsequentGreetingInput = document.getElementById('chatbot_chatgpt_subsequent_greeting');

        var chatbotChatgptDisplayStyleInput = document.getElementById('chatbot_chatgpt_display_style');
        var chatbotChatgptAssistantAliasInput = document.getElementById('chatbot_chatgpt_assistant_alias');

        var chatgptStartStatusInput = document.getElementById('chatbot_chatgpt_start_status');
        var chatbotChatgptStartStatusNewVisitorInput = document.getElementById('chatbot_chatgpt_start_status_new_visitor');

        var chatgptDisclaimerSettingInput = document.getElementById('chatbot_chatgpt_disclaimer_setting');
        var chatgptWidthSettingInput = document.getElementById('chatbot_chatgpt_width_setting');
        var chatgptDiagnosticsSettingInput = document.getElementById('chatbot_chatgpt_diagnostics');

        // Avatar Setting - Ver 1.5.0
        var chatgptAvatarIconSettingInput = document.getElementById('chatbot_chatgpt_avatar_icon_setting');
        var chatgptAvatarIconURLSettingInput = document.getElementById('chatbot_chatgpt_avatar_icon_url_setting');
        var chatgptCustomAvatarIconSettingInput = document.getElementById('chatbot_chatgpt_custom_avatar_icon_setting');
        var chatgptAvatarGreetingSettingInput = document.getElementById('chatbot_chatgpt_avatar_greeting_setting');

        var chatgptEnableCustomButtonsInput = document.getElementById('chatbot_chatgpt_enable_custom_buttons');
        var chatgptCustomButtonName1Input = document.getElementById('chatbot_chatgpt_custom_button_name_1');
        var chatgptCustomButtonURL1Input = document.getElementById('chatbot_chatgpt_custom_button_url_1');
        var chatgptCustomButtonName2Input = document.getElementById('chatbot_chatgpt_custom_button_name_2');
        var chatgptCustomButtonURL2Input = document.getElementById('chatbot_chatgpt_custom_button_url_2');

        // Allow file uploads - Ver 1.7.6
        var chatgptAllowFileUploadsInput = document.getElementById('chatbot_chatgpt_allow_file_uploads');

        if(chatbotChatgptBotNameInput) {
            chatbotChatgptBotNameInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_bot_name', this.value);
            });
        }

        if(chatbotChatgptBotPromptInput) {
            chatbotChatgptBotPromptInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_bot_prompt', this.value);
            });
        }

        if(chatgptInitialGreetingInput) {
            chatgptInitialGreetingInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_initial_greeting', this.value);
            });
        }

        if(chatgptSubsequentGreetingInput) {
            chatgptSubsequentGreetingInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_subsequent_greeting', this.value);
            });
        }
        
        if(chatgptStartStatusInput) {
            chatgptStartStatusInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_start_status', this.options[this.selectedIndex].value);
            });
        }

        if(chatbotChatgptStartStatusNewVisitorInput) {
            chatbotChatgptStartStatusNewVisitorInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', this.options[this.selectedIndex].value);
            });
        }

        if(chatgptDisclaimerSettingInput) {
            chatgptDisclaimerSettingInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_disclaimer_setting', this.options[this.selectedIndex].value);
            });
        }

        if(chatgptWidthSettingInput) {
            chatgptWidthSettingInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_width_setting', this.options[this.selectedIndex].value);
            });
        }

        if(chatgptDiagnosticsSettingInput) {
            chatgptDiagnosticsSettingInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_diagnostics', this.options[this.selectedIndex].value);
            });
        }

        if(chatgptEnableCustomButtonsInput) {
            chatgptEnableCustomButtonsInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_enable_custom_buttons', this.options[this.selectedIndex].value);
            });
        }
        
        if(chatgptCustomButtonName1Input) {
            chatgptCustomButtonName1Input.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_custom_button_name_1', this.value);
            });
        }

        if(chatgptCustomButtonURL1Input) {
            chatgptCustomButtonURL1Input.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_custom_button_url_1', this.value);
            });
        }

        if(chatgptCustomButtonName2Input) {
            chatgptCustomButtonName2Input.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_custom_button_name_2', this.value);
            });
        }

        if(chatgptCustomButtonURL2Input) {
            chatgptCustomButtonURL2Input.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_custom_button_url_2', this.value);
            });
        }
        
        // Avatar Settings - Ver 1.5.0
        if(chatgptAvatarIconSettingInput) {
            chatgptAvatarIconSettingInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_avatar_icon_setting', this.value);
            });
        }

        if(chatgptCustomAvatarIconSettingInput) {
            chatgptCustomAvatarIconSettingInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_custom_avatar_icon_setting', this.value);
            });
        }

        if(chatgptAvatarIconURLSettingInput) {
            chatgptAvatarIconURLSettingInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_avatar_icon_url_setting', this.value);
            });
        }
        
        if(chatgptAvatarGreetingSettingInput) {
            chatgptAvatarGreetingSettingInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_avatar_greeting_setting', this.value);
            });
        }

        if(chatgptEnableCustomButtonsInput) {
            chatgptEnableCustomButtonsInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_enable_custom_buttons', this.value);
            });
        }

        if(chatgptCustomButtonName1Input) {
            chatgptCustomButtonName1Input.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_custom_button_name_1', this.value);
            });
        }

        if(chatgptCustomButtonURL1Input) {
            chatgptCustomButtonURL1Input.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_custom_button_url_1', this.value);
            });
        }

        if(chatgptCustomButtonName2Input) {
            chatgptCustomButtonName2Input.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_custom_button_name_2', this.value);
            });
        }

        if(chatgptCustomButtonURL2Input) {
            chatgptCustomButtonURL2Input.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_custom_button_url_2', this.value);
            });
        }

        // Allow file uploads - Ver 1.7.6
        if(chatgptAllowFileUploadsInput) {
            chatgptAllowFileUploadsInput.addEventListener('change', function() {
                localStorage.setItem('chatbot_chatgpt_allow_file_uploads', this.value);
            });
        }

        // Update the localStorage values when the form is submitted - Ver 1.4.1
        // chatgpt-settings-form vs. your-form-id
        var chatgptSettingsForm = document.getElementById('chatgpt-settings-form');

        if (chatgptSettingsForm) {
            chatgptSettingsForm.addEventListener('submit', function(event) {

                event.preventDefault(); // Prevent form submission

                // Changed const to var - Ver 1.5.0
                var chatbotChatgptBotNameInput = document.getElementById('chatbot_chatgpt_bot_name');
                var chatbotChatgptBotPromptInput = document.getElementById('chatbot_chatgpt_bot_prompt');

                var chatgptInitialGreetingInput = document.getElementById('chatbot_chatgpt_initial_greeting');
                var chatgptSubsequentGreetingInput = document.getElementById('chatbot_chatgpt_subsequent_greeting');

                var chatgptStartStatusInput = document.getElementById('chatbot_chatgpt_start_status');
                var chatbotChatgptStartStatusNewVisitorInput = document.getElementById('chatbot_chatgpt_start_status_new_visitor');

                var chatbotChatgptDisplayStyleInput = document.getElementById('chatbot_chatgpt_display_style');
                var chatbotChatgptAssistantAliasInput = document.getElementById('chatbot_chatgpt_assistant_alias');

                var chatgptDisclaimerSettingInput = document.getElementById('chatbot_chatgpt_disclaimer_setting');
                var chatgptWidthSettingInput = document.getElementById('chatbot_chatgpt_width_setting');
                var chatgptDiagnosticsSettingInput = document.getElementById('chatbot_chatgpt_diagnostics');

                // Avatar Settings - Ver 1.5.0
                var chatgptAvatarIconSettingInput = document.getElementById('chatbot_chatgpt_avatar_icon_setting');
                var chatgptCustomAvatarIconSettingInput = document.getElementById('chatbot_chatgpt_custom_avatar_icon_setting');
                var chatgptAvatarIconURLSettingInput = document.getElementById('chatbot_chatgpt_avatar_icon_url_setting');
                var chatgptAvatarGreetingSettingInput = document.getElementById('chatbot_chatgpt_avatar_greeting_setting');

                // Custom Buttons - Ver 1.6.5
                var chatgptEnableCustomButtonsInput = document.getElementById('chatbot_chatgpt_enable_custom_buttons');
                var chatgptCustomButtonName1Input = document.getElementById('chatbot_chatgpt_custom_button_name_1');
                var chatgptCustomButtonURL1Input = document.getElementById('chatbot_chatgpt_custom_button_url_1');
                var chatgptCustomButtonName2Input = document.getElementById('chatbot_chatgpt_custom_button_name_2');
                var chatgptCustomButtonURL2Input = document.getElementById('chatbot_chatgpt_custom_button_url_2');

                // Allow file uploads - Ver 1.7.6
                var chatgptAllowFileUploadsInput = document.getElementById('chatbot_chatgpt_allow_file_uploads');

                if(chatbotChatgptBotNameInput) {
                    localStorage.setItem('chatbot_chatgpt_bot_name', chatbotChatgptBotNameInput.value);
                }

                if(chatbotChatgptBotPromptInput) {
                    localStorage.setItem('chatbot_chatgpt_bot_prompt', chatbotChatgptBotPromptInput.value);
                }

                if(chatgptInitialGreetingInput) {
                    localStorage.setItem('chatbot_chatgpt_initial_greeting', chatgptInitialGreetingInput.value);
                }

                if(chatgptSubsequentGreetingInput) {
                    localStorage.setItem('chatbot_chatgpt_subsequent_greeting', chatgptSubsequentGreetingInput.value);
                }

                if(chatgptStartStatusInput) {
                    localStorage.setItem('chatbot_chatgpt_start_status', chatgptStartStatusInput.value);
                }

                if(chatbotChatgptStartStatusNewVisitorInput) {
                    localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', chatbotChatgptStartStatusNewVisitorInput.value);
                }

                if(chatgptDisclaimerSettingInput) {
                    localStorage.setItem('chatbot_chatgpt_disclaimer_setting', chatgptDisclaimerSettingInput.value);
                }

                if(chatgptWidthSettingInput) {
                    localStorage.setItem('chatbot_chatgpt_width_setting', chatgptWidthSettingInput.value);
                }

                if(chatgptDiagnosticsSettingInput) {
                    localStorage.setItem('chatbot_chatgpt_diagnostics', chatgptDiagnosticsSettingInput.value);
                }
                
                // Avatar Settings - Ver 1.5.0
                if(chatgptAvatarIconSettingInput) {
                    localStorage.setItem('chatbot_chatgpt_avatar_icon_setting', chatgptAvatarIconSettingInput.value);
                }

                if(chatgptAvatarIconURLSettingInput) {
                    localStorage.setItem('chatbot_chatgpt_avatar_icon_url_setting', chatgptAvatarIconURLSettingInput.value);
                }

                if(chatgptCustomAvatarIconSettingInput) {
                    localStorage.setItem('chatbot_chatgpt_custom_avatar_icon_setting', chatgptCustomAvatarIconSettingInput.value);
                }
                
                if(chatgptAvatarGreetingSettingInput) {
                    localStorage.setItem('chatbot_chatgpt_avatar_greeting_setting', chatgptAvatarGreetingSettingInput.value);
                }

                if(chatgptEnableCustomButtonsInput) {
                    localStorage.setItem('chatbot_chatgpt_enable_custom_buttons', chatgptEnableCustomButtonsInput.value);
                }

                if(chatgptCustomButtonName1Input) {
                    localStorage.setItem('chatbot_chatgpt_custom_button_name_1', chatgptCustomButtonName1Input.value);
                }

                if(chatgptCustomButtonURL1Input) {
                    localStorage.setItem('chatbot_chatgpt_custom_button_url_1', chatgptCustomButtonURL1Input.value);
                }

                if(chatgptCustomButtonName2Input) {
                    localStorage.setItem('chatbot_chatgpt_custom_button_name_2', chatgptCustomButtonName2Input.value);
                }

                if(chatgptCustomButtonURL2Input) {
                    localStorage.setItem('chatbot_chatgpt_custom_button_url_2', chatgptCustomButtonURL2Input.value);
                }

                // Allow file uploads - Ver 1.7.6
                if(chatgptAllowFileUploadsInput) {
                    localStorage.setItem('chatbot_chatgpt_allow_file_uploads', chatgptAllowFileUploadsInput.value);
                }

            });
        }

        // DIAG - Diagnostics - Ver 1.8.5
        // console.log('Exiting chatbot_chatgpt_localize');
        
    }

    // DIAG - Diagnostics - Ver 1.8.5
    // console.log(chatbotSettings);

    // Localize the chatbot settings
    chatbot_chatgpt_localize();

    // DIAG - Diagnostics - Ver 1.8.5
    // console.log(chatbotSettings);

});

