jQuery(document).ready(function ($) {
 
    var messageInput = $('#chatbot-chatgpt-message');
    var conversation = $('#chatbot-chatgpt-conversation');
    var submitButton = $('#chatbot-chatgpt-submit');
    var chatGptChatBot = $('#chatbot-chatgpt');
    var chatGptOpenButton = $('#chatgpt-open-btn');
    chatGptOpenButton.hide();

    var chatbotContainer = $('<div></div>').addClass('chatbot-container');
    var chatbotCollapseBtn = $('<button></button>').addClass('chatbot-collapse-btn').addClass('dashicons dashicons-format-chat'); // Add a collapse button
    var chatbotCollapsed = $('<div></div>').addClass('chatbot-collapsed'); // Add a collapsed chatbot icon dashicons-format-chat f125

    // Append the collapse button and collapsed chatbot icon to the chatbot container
    chatbotContainer.append(chatbotCollapseBtn);
    chatbotContainer.append(chatbotCollapsed);

    // Add initial greeting to the chatbot
    conversation.append(chatbotContainer);

     function initializeChatbot() {
        var isFirstTime = !localStorage.getItem('chatgptChatbotOpened');
        var initialGreeting;
        if (isFirstTime) {
            initialGreeting = 'Hello! How can I help you today?';
            appendMessage(initialGreeting, 'bot', 'initial-greeting');
            localStorage.setItem('chatgptChatbotOpened', 'true');
        } else {
            initialGreeting = 'Hello again! How can I help you?';
            appendMessage(initialGreeting, 'bot', 'initial-greeting');
            localStorage.setItem('chatgptChatbotOpened', 'true');        
         }
 }

    // Call the initializeChatbot() function after appending the chatbot to the page
    initializeChatbot();

    // Add the toggleChatbot() function
    function toggleChatbot() {
        if (chatGptChatBot.is(':visible')) {
            chatGptChatBot.hide();
            chatGptOpenButton.show();
        } else {
            chatGptChatBot.show();
            chatGptOpenButton.hide();
        }
    }
    
    // Attach the click event listeners for the collapse button and collapsed chatbot icon
    chatbotCollapseBtn.on('click', toggleChatbot);
    chatbotCollapsed.on('click', toggleChatbot);
    chatGptOpenButton.on('click', toggleChatbot);

    function appendMessage(message, sender, cssClass) {
    var messageElement = $('<div></div>').addClass('chat-message');
    var textElement = $('<span></span>').text(message);

    // Add initial greetings if first time
    if (cssClass) {
        textElement.addClass(cssClass);
    }

    if (sender === 'user') {
        messageElement.addClass('user-message');
        textElement.addClass('user-text');
    } else if (sender === 'bot') {
        messageElement.addClass('bot-message');
        textElement.addClass('bot-text');
    } else {
        messageElement.addClass('error-message');
        textElement.addClass('error-text');
    }

    messageElement.append(textElement);
    conversation.append(messageElement);

    // Add space between user input and bot response
    if (sender === 'user' || sender === 'bot') {
        var spaceElement = $('<div></div>').addClass('message-space');
        conversation.append(spaceElement);
    }

    conversation.scrollTop(conversation[0].scrollHeight);

}

function showTypingIndicator() {
    var typingIndicator = $('<div></div>').addClass('typing-indicator');
    var dot1 = $('<span>.</span>').addClass('typing-dot');
    var dot2 = $('<span>.</span>').addClass('typing-dot');
    var dot3 = $('<span>.</span>').addClass('typing-dot');
    
    typingIndicator.append(dot1, dot2, dot3);
    conversation.append(typingIndicator);
    conversation.scrollTop(conversation[0].scrollHeight);
}

function removeTypingIndicator() {
    $('.typing-indicator').remove();
}

    submitButton.on('click', function () {
        var message = messageInput.val().trim();
      
        if (!message) {
            return;
        }
            
        messageInput.val('');
        appendMessage(message, 'user');

        $.ajax({
            url: chatbot_chatgpt_params.ajax_url,
            method: 'POST',
            data: {
                action: 'chatbot_chatgpt_send_message',
                message: message,
            },
            beforeSend: function () {
                showTypingIndicator();
                submitButton.prop('disabled', true);
            },
            success: function (response) {
                removeTypingIndicator();
                if (response.success) {
                    appendMessage(response.data, 'bot');
                } else {
                    appendMessage('Error: ' + response.data, 'error');
                }
            },
            error: function () {
                removeTypingIndicator();
                appendMessage('Error: Unable to send message', 'error');
            },
            complete: function () {
                removeTypingIndicator();
                submitButton.prop('disabled', false);
            },
        });
    });

    messageInput.on('keydown', function (e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            submitButton.click();
        }
    });
});
