jQuery(document).ready(function ($) {
    
    function chatbot_chatgpt_localize() {
        // Access the variables passed from PHP using the chatbotSettings object
        var chatgptName = chatbotSettings.chatgpt_bot_name || 'Chatbot ChatGPT';
        var chatgptInitialGreeting = chatbotSettings.initial_greeting || 'Hello! How can I help you today?';
        var chatgptSubsequentGreeting = chatbotSettings.subsequent_greeting || 'Hello again! How can I help you?';
        var chatgptStartStatus = chatbotSettings.start_status || 'closed';
        var chatgptDisclaimerSetting = chatbotSettings.disclaimer_setting || '1';

        // Set the variables in localStorage
        localStorage.setItem('chatgpt_bot_name', chatgptName);
        localStorage.setItem('chatgpt_initial_greeting', chatgptInitialGreeting);
        localStorage.setItem('chatgpt_subsequent_greeting', chatgptSubsequentGreeting);
        localStorage.setItem('chatGPTChatBotStatus', chatgptStartStatus);
        localStorage.setItem('chatgpt_disclaimer_setting', chatgptDisclaimerSetting);

        // Update the localStorage values when the form is submitted
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

    chatbot_chatgpt_localize(); // Call the function inside the jQuery ready function
}); // Add the missing closing parenthesis for the jQuery ready function
