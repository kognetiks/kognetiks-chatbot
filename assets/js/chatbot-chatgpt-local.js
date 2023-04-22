jQuery(document).ready(function ($) {
    
    function chatbot_chatgpt_localize() {
        // Access the variables passed from PHP using the chatbotSettings object - Ver 1.4.1
        var chatgptName = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_bot_name) ? chatbotSettings.chatgpt_bot_name : 'Chatbot ChatGPT';
        var chatgptInitialGreeting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.initial_greeting) ? chatbotSettings.initial_greeting : 'Hello! How can I help you today?';
        var chatgptSubsequentGreeting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_subsequent_greeting) ? chatbotSettings.chatgpt_subsequent_greeting : 'Hello again! How can I help you?';
        var chatgptStartStatus = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatGPTChatBotStatus) ? chatbotSettings.chatGPTChatBotStatus : 'closed';
        var chatgptDisclaimerSetting = (typeof chatbotSettings !== 'undefined' && chatbotSettings.chatgpt_disclaimer_setting) ? chatbotSettings.chatgpt_disclaimer_setting : 'Yes';

        // Set the variables in localStorage - Ver 1.4.1
        localStorage.setItem('chatgpt_bot_name', chatgptName);
        localStorage.setItem('chatgpt_initial_greeting', chatgptInitialGreeting);
        localStorage.setItem('chatgpt_subsequent_greeting', chatgptSubsequentGreeting);
        localStorage.setItem('chatGPTChatBotStatus', chatgptStartStatus);
        localStorage.setItem('chatgpt_disclaimer_setting', chatgptDisclaimerSetting);

        // Update the localStorage values when the form is submitted - Ver 1.4.1
        var chatgptSettingsForm = document.getElementById('your-form-id');
        if (chatgptSettingsForm) {
            chatgptSettingsForm.addEventListener('submit', function() {
                const chatgptNameInput = document.getElementById('chatgpt_bot_name');
                const chatgptInitialGreetingInput = document.getElementById('chatgpt_initial_greeting');
                const chatgptSubsequentGreetingInput = document.getElementById('chatgpt_subsequent_greeting');
                const chatgptStartStatusInput = document.getElementById('chatGPTChatBotStatus');
                const chatgptDisclaimerSettingInput = document.getElementById('chatgpt_disclaimer_setting');

                localStorage.setItem('chatgpt_bot_name', chatgptNameInput.value);
                localStorage.setItem('chatgpt_initial_greeting', chatgptInitialGreetingInput.value);
                localStorage.setItem('chatgpt_subsequent_greeting', chatgptSubsequentGreetingInput.value);
                localStorage.setItem('chatGPTChatBotStatus', chatgptStartStatusInput.value);
                localStorage.setItem('chatgpt_disclaimer_setting', chatgptDisclaimerSettingInput.value);
            });
        }
    }

    chatbot_chatgpt_localize();
});
