jQuery(document).ready(function ($) {
 
    var messageInput = $('#chatbot-chatgpt-message');
    var conversation = $('#chatbot-chatgpt-conversation');
    var submitButton = $('#chatbot-chatgpt-submit');
    var chatGptChatBot = $('#chatbot-chatgpt');
    var chatGptOpenButton = $('#chatgpt-open-btn');
    // Use 'open' for an open chatbot or 'closed' for a closed chatbot - Ver 1.1.0
    var chatgpt_start_status = 'closed';
    
    // Initially hide the chatbot - Ver 1.1.0
    chatGptChatBot.hide();
    chatGptOpenButton.show();

    var chatbotContainer = $('<div></div>').addClass('chatbot-container');
    var chatbotCollapseBtn = $('<button></button>').addClass('chatbot-collapse-btn').addClass('dashicons dashicons-format-chat'); // Add a collapse button
    var chatbotCollapsed = $('<div></div>').addClass('chatbot-collapsed'); // Add a collapsed chatbot icon dashicons-format-chat f125

    // Support variable greetings based on setting - Ver 1.1.0
    var initialGreeting = localStorage.getItem('chatgpt_initial_greeting') || 'Hello! How can I help you today?';
    localStorage.setItem('chatgpt_initial_greeting', initialGreeting);
    var subsequentGreeting = localStorage.getItem('chatgpt_subsequent_greeting') || 'Hello again! How can I help you?';
    localStorage.setItem('chatgpt_subsequent_greeting', subsequentGreeting);
        
    // Append the collapse button and collapsed chatbot icon to the chatbot container
    chatbotContainer.append(chatbotCollapseBtn);
    chatbotContainer.append(chatbotCollapsed);

    // Add initial greeting to the chatbot
    conversation.append(chatbotContainer);

    function initializeChatbot() {
        var isFirstTime = !localStorage.getItem('chatgptChatbotOpened');
        var initialGreeting;
    
        if (isFirstTime) {
            initialGreeting = localStorage.getItem('chatgpt_initial_greeting') || 'Hello! How can I help you today?';
            appendMessage(initialGreeting, 'bot', 'initial-greeting');
            localStorage.setItem('chatgptChatbotOpened', 'true');
            // Save the conversation after the initial greeting is appended - Ver 1.2.0
            localStorage.setItem('chatgpt_conversation', conversation.html());
        } else {
            initialGreeting = localStorage.getItem('chatgpt_subsequent_greeting') || 'Hello again! How can I help you?';
            appendMessage(initialGreeting, 'bot', 'initial-greeting');
            localStorage.setItem('chatgptChatbotOpened', 'true');
            // Load the conversation after the subsequent greeting is appended - Ver 1.2.0
            // loadConversation();
        }
    }

    // Call the initializeChatbot() function after appending the chatbot to the page
    // Remove the call to inialize the bot - Ver 1.2.1
    // initializeChatbot();

    // Add chatbot header, body, and other elements - Ver 1.1.0
    var chatbotHeader = $('<div></div>').addClass('chatbot-header');
    chatGptChatBot.append(chatbotHeader);
    // Fix for Ver 1.2.0
    // chatbotContainer.append(chatbotCollapseBtn);
    // chatbotContainer.append(chatbotCollapsed);
    chatbotHeader.append(chatbotCollapseBtn);
    chatbotHeader.append(chatbotCollapsed);

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

    // Save the conversation locally between bot sessions - Ver 1.2.0
    localStorage.setItem('chatgpt_conversation', conversation.html());

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

    // Add the toggleChatbot() function - Ver 1.1.0
    function toggleChatbot() {
        if (chatGptChatBot.is(':visible')) {
            chatGptChatBot.hide();
            chatGptOpenButton.show();
            localStorage.setItem('chatGPTChatBotStatus', 'closed');
            // Clear the conversation when the chatbot is closed - Ver 1.2.0
            localStorage.removeItem('chatgpt_conversation');
        } else {
            chatGptChatBot.show();
            chatGptOpenButton.hide();
            localStorage.setItem('chatGPTChatBotStatus', 'open');
        }
    }

    // Add this function to maintain the chatbot status across page refreshes and sessions - Ver 1.1.0
    function loadChatbotStatus() {
        const chatGPTChatBotStatus = localStorage.getItem('chatGPTChatBotStatus');
       
        // Add test to see if bot should start opened or closed - Ver 1.1.0
        if (chatGPTChatBotStatus === null) {
            if (chatgpt_start_status === 'closed') {
                chatGptChatBot.hide();
                chatGptOpenButton.show();
            } else {
                chatGptChatBot.show();
                chatGptOpenButton.hide();
                // Load the conversation when the chatbot is shown on page load - Ver 1.2.0
                loadConversation();
                scrollToBottom(); // Call the scrollToBottom function here - Ver 1.2.1
            }
        } else if (chatGPTChatBotStatus === 'closed') {
            if (chatGptChatBot.is(':visible')) {
                chatGptChatBot.hide();
                chatGptOpenButton.show();
            }
        } else if (chatGPTChatBotStatus === 'open') {
            if (chatGptChatBot.is(':hidden')) {
                chatGptChatBot.show();
                chatGptOpenButton.hide();
                // loadConversation(); // Call loadConvesration function here - Ver 1.2.1
                scrollToBottom(); // Call the scrollToBottom function here - Ver 1.2.1    
            }
        }
      
    }

    // Add this function to scroll to the bottom of the conversation - Ver 1.2.1
    function scrollToBottom() {
        conversation.scrollTop(conversation[0].scrollHeight);
    }
   
    // Load conversation from local storage if available - Ver 1.2.0
    function loadConversation() {
        var storedConversation = localStorage.getItem('chatgpt_conversation');
        if (storedConversation) {
            conversation.append(storedConversation);
        } else {
            initializeChatbot();
        }
    }

    // Load the conversation when the chatbot is shown on page load - Ver 1.2.0
    loadConversation();
    
    // Call the loadChatbotStatus function here - Ver 1.1.0
    loadChatbotStatus(); 

});
