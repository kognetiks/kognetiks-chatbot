jQuery(document).ready(function ($) {

    // DIAG - Diagnostics = Ver 1.4.2
    // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
    //     console.log('Chatbot ChatGPT: NOTICE: Entering chatbot-chatgpt.js');
    // }

    var chatGptChatBot = $('#chatbot-chatgpt').hide();

    messageInput = $('#chatbot-chatgpt-message');
    
    var conversation = $('#chatbot-chatgpt-conversation');

    submitButton = $('#chatbot-chatgpt-submit');
    uploadfileButton = $('#chatbot-chatgpt-upload-file');
    chatGptOpenButton = $('#chatgpt-open-btn');

    chatbotChatgptBotName = localStorage.getItem('chatbot_chatgpt_bot_name') || 'Chatbot ChatGPT';
    chatbotChatgptBotPrompt = localStorage.getItem('chatbot_chatgpt_bot_prompt') || 'Enter your question ...';

    // Determine the shortcode styling where default is 'floating' or 'embedded' - Ver 1.7.1
    chatbot_chatgpt_display_style = localStorage.getItem('chatbot_chatgpt_display_style') || 'floating';
    chatbot_chatgpt_assistant_alias = localStorage.getItem('chatbot_chatgpt_assistant_alias') || 'original';

    initialGreeting = localStorage.getItem('chatbot_chatgpt_initial_greeting') || 'Hello! How can I help you today?';
    subsequentGreeting = localStorage.getItem('chatbot_chatgpt_subsequent_greeting') || 'Hello again! How can I help you?';

    chatbotChatgptDisplaySytle = localStorage.getItem('chatbot_chatgpt_display_style') || 'floating';
    chatbotChatgptAssistantAlias = localStorage.getItem('chatbot_chatgpt_assistant_alias') || 'original';

    chatbot_chatgpt_start_status = localStorage.getItem('chatbot_chatgpt_start_status') || 'closed';
    chatbot_chatgpt_start_status_new_visitor = localStorage.getItem('chatbot_chatgpt_start_status_new_visitor') || 'closed';

    chatbot_chatgpt_disclaimer_setting = localStorage.getItem('chatbot_chatgpt_disclaimer_setting') || 'Yes';
    chatbot_chatgpt_width_setting = localStorage.getItem('chatbot_chatgpt_width_setting') || 'Narrow';

    pluginUrl = plugin_vars.pluginUrl;

    // Get an open icon for the chatbot - Ver 1.8.6
    chatbotopenicon = pluginUrl + '/assets/icons/' + 'chat_FILL0_wght400_GRAD0_opsz24.png';
    chatbotopenicon = $('<img>')
    .attr('id', 'chatbot-open-icon')
    .attr('class', 'chatbot-open-icon')
    .attr('src', chatbotopenicon);

    // Get a collapse icon for the chatbot - Ver 1.8.6
    chatbotcollapseicon = pluginUrl + '/assets/icons/' + 'close_FILL0_wght400_GRAD0_opsz24.png';
    chatbotcollapseicon = $('<img>')
    .attr('id', 'chatbot-collapse-icon')
    .attr('class', 'chatbot-collapse-icon')
    .attr('src', chatbotcollapseicon);

    // Get am erase icon for the chatbot - Ver 1.8.6
    chatboteraseicon = pluginUrl + '/assets/icons/' + 'delete_FILL0_wght400_GRAD0_opsz24.png';
    chatboteraseicon = $('<img>')
    .attr('id', 'chatbot-erase-icon')
    .attr('class', 'chatbot-erase-icon')
    .attr('src', chatboteraseicon);

    // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
        // console.log('Chatbot ChatGPT: NOTICE: chatbot_chatgpt_display_style: ' + chatbot_chatgpt_display_style);
        // console.log('Chatbot ChatGPT: NOTICE: chatbot_chatgpt_assistant_alias: ' + chatbot_chatgpt_assistant_alias);
    // }

    // Determine the shortcode styling where default is 'floating' or 'embedded' - Ver 1.7.1
    // var site-header = document.querySelector("#site-header");
    // var site-footer = document.querySelector("#site-footer");

    // if(header && footer) {
    //     var headerBottom = site-header.getBoundingClientRect().bottom;
    //     var footerTop = site-footer.getBoundingClientRect().top;

    //     var visible-distance = footerTop - headerBottom;
    //     console.log('Chatbot ChatGPT: NOTICE: Distance:  + distance + 'px');
    // }
    
    if (chatbot_chatgpt_display_style === 'embedded') {
        // Apply configurations for embedded style
        $('#chatbot-chatgpt').addClass('embedded-style').removeClass('floating-style');
        // Other configurations specific to embedded style
        chatbot_chatgpt_start_status = 'open'; // Force the chatbot to open if embedded
        chatbot_chatgpt_start_status_new_visitor = 'open'; // Force the chatbot to open if embedded
        localStorage.setItem('chatbot_chatgpt_start_status', chatbot_chatgpt_start_status);
        localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', chatbot_chatgpt_start_status_new_visitor);
        chatGptChatBot.addClass('embedded-style').removeClass('floating-style');
    } else {
        // Apply configurations for floating style
        $('#chatbot-chatgpt').addClass('floating-style').removeClass('embedded-style');
        // Other configurations specific to floating style
        if (chatbot_chatgpt_width_setting === 'Wide') {
            chatGptChatBot.addClass('wide');
        } else {
            // chatGptChatBot.removeClass('wide').css('display', 'none');
            chatGptChatBot.removeClass('wide');
        }
    }
    
    // Overrides for mobile devices - Ver 1.8.1
    if (isMobile()) {
        // chatbot_chatgpt_start_status = 'closed';
        // chatbot_chatgpt_start_status_new_visitor = 'closed';
        localStorage.setItem('chatbot_chatgpt_start_status', chatbot_chatgpt_start_status);
        localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', chatbot_chatgpt_start_status_new_visitor);

        // Determine the viewport width and height
        let viewportWidth = window.innerWidth;
        let viewportHeight = window.innerHeight;
        // console.log('Viewport Width:', viewportWidth, 'Viewport Height:', viewportHeight);
        // Determine the orientation
        const orientation = screen.orientation || screen.mozOrientation || screen.msOrientation;
        if (orientation.type === 'landscape-primary') {
        // console.log('Orientation: Landscape');
        } else if (orientation.type === 'portrait-primary') {
        // console.log('Orientation: Portrait');
        } else {
        // console.log('Orientation:', orientation.type);
        }

        updateChatbotStyles();
    
        // Listen for orientation changes
        window.addEventListener('orientationchange', updateChatbotStyles);
    
        // Listen for resize events
        window.addEventListener('resize', updateChatbotStyles);

        // TODO - IF MOBILE REMOVE ICON AND SHOW  DASHICON AND DETERMINE WIDTH AND ORIENTATION (PORTRAIT OR LANDSCAPE)
        
    }

    // Removed css from here into the .css file - Refactored for Ver 1.7.3
    // Initially hide the chatbot
    if (chatbot_chatgpt_start_status === 'closed') {
        chatGptChatBot.hide();
        chatGptOpenButton.show();
    } else {
        if (chatbot_chatgpt_display_style === 'floating') {
            if (chatbot_chatgpt_width_setting === 'Wide') {
                $('#chatbot-chatgpt').removeClass('chatbot-narrow chatbot-full').addClass('chatbot-wide');
            } else {
                $('#chatbot-chatgpt').removeClass('chatbot-wide chatbot-full').addClass('chatbot-narrow');
            }

            // Overrides for mobile devices - Ver 1.8.1
            if (isMobile()) {
                // Initial update
                updateChatbotStyles();
            
                // Listen for orientation changes
                window.addEventListener('orientationchange', updateChatbotStyles);
            
                // Listen for resize events
                window.addEventListener('resize', updateChatbotStyles);
            }

            chatGptChatBot.show();
            chatGptOpenButton.hide();
        } else {
            $('#chatbot-chatgpt').removeClass('chatbot-wide chatbot-narrow').addClass('chatbot-full');
        }
    }

    chatbotContainer = $('<div></div>').addClass('chatbot-container');

    // Changed this out for an image - Ver 1.8.6
    // chatbotCollapseBtn = $('<button></button>').addClass('chatbot-collapse-btn').addClass('dashicons dashicons-format-chat'); // Add a collapse button
    chatbotCollapseBtn = $('<button></button>').addClass('chatbot-collapse-btn').append(chatbotcollapseicon); // Add a collapse button

    chatbotCollapsed = $('<div></div>').addClass('chatbot-collapsed'); // Add a collapsed chatbot icon dashicons-format-chat f125

    // Avatar and Custom Message - Ver 1.5.0
    selectedAvatar = encodeURIComponent(localStorage.getItem('chatbot_chatgpt_avatar_icon_setting'));
    if (isValidAvatarSetting(selectedAvatar)) {
        // Is valid avatar setting
        // DIAG - Diagnostics - Ver 1.8.1
        // console.log('Chatbot ChatGPT: NOTICE: selectedAvatar: ' + selectedAvatar);
    } else {
        // Is not valid avatar setting
        // DIAG - Diagnostics - Ver 1.8.1
        // console.error('Chatbot ChatGPT: ERROR: selectedAvatar: ' + selectedAvatar);
        selectedAvatar = 'icon-000.png';
    }

    // Overrides for mobile devices - Ver 1.8.1
    if (isMobile()) {
        // Set selectedAvatar to 'icon-000.png' for mobile devices
        selectedAvatar = 'icon-000.png';
    }



    if (selectedAvatar && selectedAvatar !== 'icon-000.png') {
        // Construct the path to the avatar
        avatarPath = pluginUrl + '/assets/icons/' + selectedAvatar;
        
        // IDEA - Add option to suppress avatar greeting in setting options page
        // IDEA - If blank greeting, don't show the bubble
        // IDEA - Add option to suppress avatar greeting if clicked on

        // Updated to address cross-site scripting - Ver 1.8.1
        // If an avatar is selected, and it's not 'icon-000.png', use the avatar
        avatarImg = $('<img>')
            .attr('id', 'chatbot_chatgpt_avatar_icon_setting')
            .attr('class', 'chatbot-avatar')
            .attr('src', avatarPath);

        // Get the stored greeting message. If it's not set, default to a custom value.
        avatarGreeting = localStorage.getItem('chatbot_chatgpt_avatar_greeting_setting') || 'Howdy!!! Great to see you today! How can I help you?';

        // Create a bubble with the greeting message
        // Using .text() for safety, as it automatically escapes HTML
        bubble = $('<div>').text(avatarGreeting).addClass('chatbot-bubble');

        // Append the avatar and the bubble to the button and apply the class for the avatar icon
        chatGptOpenButton.empty().append(avatarImg, bubble).addClass('avatar-icon');
    } else {
        // If no avatar is selected or the selected avatar is 'icon-000.png', use the dashicon
        // Remove the avatar-icon class (if it was previously added) and add the dashicon class
        // chatGptOpenButton.empty().removeClass('avatar-icon').addClass('dashicons dashicons-format-chat dashicon');
        // chatGptOpenButton.empty().removeClass('avatar-icon').addClass('dashicons chatbot-open-icon chatbotopenicon'); // Add a open button
        chatGptOpenButton.empty().removeClass('avatar-icon').addClass('chatbot-open-icon').append(chatbotopenicon); // Add a open button
    }
    
    // Append the collapse button and collapsed chatbot icon to the chatbot container
    $('#chatbot-chatgpt-header').append(chatbotCollapseBtn);
    chatbotContainer.append(chatbotCollapsed);

    // Add initial greeting to the chatbot
    conversation.append(chatbotContainer);

    function initializeChatbot() {

        isFirstTime = !localStorage.getItem('chatbot_chatgpt_opened') || false;

        // Remove any legacy conversations that might be store in local storage for increased privacy - Ver 1.4.2
        localStorage.removeItem('chatgpt_conversation');

        if (isFirstTime) {
            // DIAG - Logging for Diagnostics
            // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
            //     console.log('Chatbot ChatGPT: NOTICE: initializeChatbot at isFirstTime');
            // }
            initialGreeting = localStorage.getItem('chatbot_chatgpt_initial_greeting') || 'Hello! How can I help you today?';

            // Don't append the greeting if it's already in the conversation
            if (conversation.text().includes(initialGreeting)) {
                return;
            }

            lastMessage = conversation.children().last().text();

            // Don't append the subsequent greeting if it's already in the conversation - Ver 1.5.0
            if (lastMessage === subsequentGreeting) {
                return;
            }

            appendMessage(initialGreeting, 'bot', 'initial-greeting');
            localStorage.setItem('chatbot_chatgpt_opened', 'true');
            // Save the conversation after the initial greeting is appended - Ver 1.2.0
            sessionStorage.setItem('chatgpt_conversation', conversation.html());           

        } else {
            // DIAG - Logging for Diagnostics - Ver 1.4.2
            // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
            //     console.log('Chatbot ChatGPT: NOTICE: initializeChatbot at else');
            // }
            initialGreeting = localStorage.getItem('chatbot_chatgpt_subsequent_greeting') || 'Hello again! How can I help you?';

            // Don't append the greeting if it's already in the conversation
            if (conversation.text().includes(initialGreeting)) {
                return;
            }

            appendMessage(initialGreeting, 'bot', 'initial-greeting');
            localStorage.setItem('chatbot_chatgpt_opened', 'true');

        }

        return;

    }

    if (chatbot_chatgpt_display_style === 'floating') {

        // Add chatbot header, body, and other elements
        chatbotHeader = $('<div></div>').addClass('chatbot-header');
        chatGptChatBot.append(chatbotHeader);

        // Add the chatbot button to the header
        $('#chatbot-chatgpt-header').append(chatbotCollapseBtn);
        chatbotHeader.append(chatbotCollapsed);

        // Attach the click event listeners for the collapse button and collapsed chatbot icon
        chatbotCollapseBtn.on('click', toggleChatbot);
        chatbotCollapsed.on('click', toggleChatbot);
        chatGptOpenButton.on('click', toggleChatbot);

    } else {

        // Embedded style - Do not add the collapse button and collapsed chatbot icon
        chatbotHeader = $('<div></div>');

    }

    function appendMessage(message, sender, cssClass) {

        messageElement = $('<div></div>').addClass('chat-message');
        // Use HTML for the response so that links are clickable - Ver 1.6.3
        // textElement = $('<span></span>').html(message);
        // Fix for XSS vulnerability - Ver 1.8.1
        var sanitizedMessage = DOMPurify.sanitize(message);
        textElement = $('<span></span>').html(sanitizedMessage);

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
            spaceElement = $('<div></div>').addClass('message-space');
            conversation.append(spaceElement);
        }

        // Ver 1.2.4
        conversation[0].scrollTop = conversation[0].scrollHeight;
        // Scroll to bottom if embedded - Ver 1.7.1
        // window.scrollTo(0, document.body.scrollHeight);

        // Save the conversation locally between bot sessions - Ver 1.2.0
        sessionStorage.setItem('chatgpt_conversation', conversation.html());

    }

    function showTypingIndicator() {
        typingIndicator = $('<div></div>').addClass('typing-indicator');
        dot1 = $('<span>.</span>').addClass('typing-dot');
        dot2 = $('<span>.</span>').addClass('typing-dot');
        dot3 = $('<span>.</span>').addClass('typing-dot');
        
        typingIndicator.append(dot1, dot2, dot3);
        conversation.append(typingIndicator);
        conversation.scrollTop(conversation[0].scrollHeight);
    }

    function removeTypingIndicator() {
        $('.typing-indicator').remove();
    }

    submitButton.on('click', function () {
        message = messageInput.val().trim();

        if (!message) {
            return;
        }
            
        messageInput.val('');
        appendMessage(message, 'user');

        var user_id = php_vars.user_id;
        var page_id = php_vars.page_id;

        $.ajax({
            url: chatbot_chatgpt_params.ajax_url,
            method: 'POST',
            data: {
                action: 'chatbot_chatgpt_send_message',
                message: message,
                user_id: user_id, // pass the user ID here
                page_id: page_id, // pass the page ID here
            },
            beforeSend: function () {
                showTypingIndicator();
                submitButton.prop('disabled', true);
            },
            success: function (response) {
                removeTypingIndicator();
                // console.log('Chatbot ChatGPT: SUCCESS: ' + JSON.stringify(response));
                if (response.success) {
                    botResponse = response.data;
                    // Revision to how disclaimers are handled - Ver 1.5.0
                    if (localStorage.getItem('chatbot_chatgpt_disclaimer_setting') === 'No') {
                        const prefixes = [
                            "As an AI, ",
                            "As an AI language model, ",
                            "I am an AI language model and ",
                            "As an artificial intelligence, ",
                            "As an AI developed by OpenAI, ",
                            "As an artificial intelligence developed by OpenAI, "
                        ];
                        for (let prefix of prefixes) {
                            if (botResponse.startsWith(prefix)) {
                                botResponse = botResponse.slice(prefix.length);
                                break;
                            }
                        }
                    }
                    // IDEA Check for a URL
                    if (botResponse.includes('[URL: ')) {
                        // DIAG - Diagnostics - Ver 1.6.3
                        // console.error('Chatbot ChatGPT: ERROR: URL found in bot response');
                        link = '';
                        urlRegex = /\[URL: (.*?)\]/g;
                        match = botResponse.match(urlRegex);
                        if (match && match.length > 0) {
                            link = match[0].replace(/\[URL: /, '').replace(/\]/g, '');
                            // DIAG - Diagnostics - Ver 1.6.3
                            // console.log('Chatbot ChatGPT: NOTICE: link: ' + link);
                        }

                        linkElement = document.createElement('a');
                        linkElement.href = link;
                        linkElement.textContent = 'here';
                        text = botResponse.replace(urlRegex, '');
                        textElement = document.createElement('span');
                        textElement.textContent = text;
                        botResponse = document.createElement('div');
                        botResponse.appendChild(textElement);
                        botResponse.appendChild(linkElement);
                        botResponse.innerHTML += '.';
                        botResponse = botResponse.outerHTML;
                    }

                    // Check for double asterisks suggesting a "bold" response
                    // Check for linefeeds suggesting paragraphs response
                    botResponse = botResponse.replace(/\n/g, "<br>");
                    botResponse = botResponse.replace(/\*\*(.*?)\*\*/g, "<b>$1</b>");

                    // Return the response
                    appendMessage(botResponse, 'bot');
                } else {
                    appendMessage('Error: ' + response.data, 'error');
                }
            },
            error: function (response) {
                removeTypingIndicator();
                // DIAG - Log the error - Ver 1.6.7
                // console.log('Chatbot ChatGPT: ERROR: ' + JSON.stringify(response));
                // console.error('Chatbot ChatGPT: ERROR: Unable to send message');
                appendMessage('Oops! Something went wrong on our end. Please try again later.', 'error');
            },
            complete: function () {
                removeTypingIndicator();
                submitButton.prop('disabled', false);
            },
        });
    });
    
    // Add the keydown event listener to the message input - Ver 1.7.6
    messageInput.on('keydown', function (e) {
        if (e.keyCode === 13  && !e.shiftKey) {
            e.preventDefault();
            submitButton.trigger('click');
        }
    });

    // Add the keydown event listener to the upload file button - Ver 1.7.6
    $('#chatbot-chatgpt-upload-file').on('keydown', function(e) {
        if (e.keyCode === 13  && !e.shiftKey) {
            e.preventDefault();
            // console.log('Chatbot ChatGPT: NOTICE: Enter key pressed on upload file button');
            $response = chatbot_chatgpt_upload_file_to_assistant();
            $('#chatbot-chatgpt-upload-file-input').click();
        }
    });

    // Add the change event listener to the file input field
    $('#chatbot-chatgpt-upload-file-input').on('change', function(e) {
        // console.log('Chatbot ChatGPT: NOTICE: File selected');

        showTypingIndicator();

        var fileField = e.target;

        // Check if a file is selected
        if (!fileField.files.length) {
            // console.log('Chatbot ChatGPT: WARNING: No file selected');
            return;
        }

        var formData = new FormData();
        formData.append('file', fileField.files[0]);
        // console.log('Chatbot ChatGPT: NOTICE: File selected ', fileField.files[0]);
        formData.append('action', 'chatbot_chatgpt_upload_file_to_assistant');

        $.ajax({
            url: chatbot_chatgpt_params.ajax_url,
            method: 'POST',
            data: formData,
            processData: false,  // tell jQuery not to process the data
            contentType: false,  // tell jQuery not to set contentType
            success: function(response) {
                // console.log('Chatbot ChatGPT: NOTICE: Response from server', response);
                $('#chatbot-chatgpt-upload-file-input').val('');
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // console.error('AJAX error:', textStatus, errorThrown);
            }
        });

        removeTypingIndicator();

        appendMessage('File uploaded.', 'bot');

    });

    // Add the keydown event listener to the erase conversation button
    $('#chatbot-chatgpt-erase-conversation').on('keydown', function(e) {
        if (e.keyCode === 13  && !e.shiftKey) {
            e.preventDefault();
            console.log('Chatbot ChatGPT: NOTICE: Enter key pressed on erase conversation button');
            $response = chatbot_chatgpt_erase_conversation();
            $('#chatbot-chatgpt-erase-conversation').click();
        }
    });

    // Add the change event listener to the file input field
    $('#chatbot-chatgpt-erase-conversation').on('change', function(e) {
        
        console.log('Chatbot ChatGPT: NOTICE: Erase conversation selected');

        showTypingIndicator();

        var fileField = e.target;

        var formData = new FormData();
        formData.append('action', 'chatbot_chatgpt_erase_conversation');

        $.ajax({
            url: chatbot_chatgpt_params.ajax_url,
            method: 'POST',
            data: {
                action: 'chatbot_chatgpt_erase_conversation'
            },
            success: function(response) {
                console.log('Chatbot ChatGPT: NOTICE: Response from server', response);
                // Clear the conversation display area
                $('#chatbot-chatgpt-erase-conversation').val('');
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
            }
        });

        removeTypingIndicator();

        appendMessage('Conversation cleared.', 'bot');

    });
    
    // Moved the css to the .css file - Refactored for Ver 1.7.3
    // Add the toggleChatbot() function - Ver 1.1.0
    function toggleChatbot() {
    if (chatGptChatBot.is(':visible')) {
        chatGptChatBot.hide();
        chatGptOpenButton.show();
        localStorage.setItem('chatbot_chatgpt_start_status', 'closed');
    } else {
        if (chatbot_chatgpt_display_style === 'floating') {
            if (chatbot_chatgpt_width_setting === 'Wide') {
                $('#chatbot-chatgpt').removeClass('chatbot-narrow chatbot-full').addClass('chatbot-wide');
            } else {
                $('#chatbot-chatgpt').removeClass('chatbot-wide chatbot-full').addClass('chatbot-narrow');
            }
            chatGptChatBot.show();
            chatGptOpenButton.hide();
        } else {
            $('#chatbot-chatgpt').removeClass('chatbot-wide chatbot-narrow').addClass('chatbot-full');
        }
        chatGptChatBot.show();
        chatGptOpenButton.hide();
        localStorage.setItem('chatbot_chatgpt_start_status', 'open');
        loadConversation();
        scrollToBottom();
    }
    }

    // Add this function to maintain the chatbot status across page refreshes and sessions - Ver 1.1.0 and updated for Ver 1.4.1
    function loadChatbotStatus() {
        chatbot_chatgpt_start_status = localStorage.getItem('chatbot_chatgpt_start_status');
        chatbot_chatgpt_start_status_new_visitor = localStorage.getItem('chatbot_chatgpt_start_status_new_visitor');

        // Nuclear option to clear session conversation - Ver 1.5.0
        // Do not use unless absolutely needed
        // DIAG - Diagnostics - Ver 1.5.0
        // nuclearOption = 'Off';
        // if (nuclearOption === 'On') {
        //     console.log('Chatbot ChatGPT: NOTICE: ***** NUCLEAR OPTION IS ON ***** ');
        //     sessionStorage.removeItem('chatgpt_conversation');
        //     // Removed in Ver 1.6.1
        //     sessionStorage.removeItem('chatgpt_last_response');
        // }

        // DIAG - Diagnostics - Ver 1.5.0
        // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
        //     console.log('Chatbot ChatGPT: NOTICE: loadChatbotStatus - BEFORE DECISION');
        // }

        // Decide what to do for a new visitor - Ver 1.5.0
        if (chatbotSettings.chatbot_chatgpt_start_status_new_visitor === 'open') {
            if (chatbot_chatgpt_start_status_new_visitor === null) {
                // Override initial status
                chatbot_chatgpt_start_status = 'open';
                chatbot_chatgpt_start_status_new_visitor = 'closed';
                localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', 'closed');
            } else {
                // Override initial status
                chatbot_chatgpt_start_status_new_visitor = 'closed';
                localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', 'closed');
            }
        }

        // DIAG - Diagnostics - Ver 1.5.0
        // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
        //     console.log('Chatbot ChatGPT: NOTICE: loadChatbotStatus - AFTER DECISION');
        // }
        
        // If the chatbot status is not set in local storage, use chatbot_chatgpt_start_status - Ver 1.5.1
        if (chatbot_chatgpt_start_status === 'closed') {
            chatGptChatBot.hide();
            chatGptOpenButton.show();
        } else {
            chatGptChatBot.show();
            chatGptOpenButton.hide();
            // Load the conversation if the chatbot is open on page load
            loadConversation();
            scrollToBottom();
        }

    }

    // Add this function to scroll to the bottom of the conversation - Ver 1.2.1
    function scrollToBottom() {
        setTimeout(() => {
            // DIAG - Diagnostics - Ver 1.5.0
            // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
            //     console.log('Chatbot ChatGPT: NOTICE: scrollToBottom");
            // }
            conversation.scrollTop(conversation[0].scrollHeight);
        }, 100);  // delay of 100 milliseconds  

    }
   
    // Load conversation from local storage if available - Ver 1.2.0
    function loadConversation() {
        storedConversation = sessionStorage.getItem('chatgpt_conversation');
        localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', 'closed');
  
        // DIAG - Diagnostics - Ver 1.5.0
        // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
        //     console.log('Chatbot ChatGPT: NOTICE: loadConversation');
        // }

        if (storedConversation) {
            // DIAG - Diagnostics - Ver 1.5.0
            // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
            //     console.log('Chatbot ChatGPT: NOTICE: loadConversation - IN THE IF STATEMENT');
            // }

            // Check if current conversation is different from stored conversation
            // if (conversation.html() !== storedConversation) {
            //     conversation.html(storedConversation);  // Set the conversation HTML to stored conversation
            // }
            // Fix for XSS vulnerability - Ver 1.8.1
            if (conversation.html() !== storedConversation) {
                var sanitizedConversation = DOMPurify.sanitize(storedConversation);
                conversation.html(sanitizedConversation);  // Set the conversation HTML to sanitized stored conversation
            }          

            // Use setTimeout to ensure scrollToBottom is called after the conversation is rendered
            setTimeout(scrollToBottom, 0);
        } else {
            // DIAG - Diagnostics - Ver 1.5.0
            // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
            //     console.log('Chatbot ChatGPT: NOTICE: loadConversation - IN THE ELSE STATEMENT');
            // }
            initializeChatbot();
        }

    }

    // Validation function - Ver 1.8.1
    function isValidAvatarSetting(setting) {
        const allowedAvatars = ['icon-001.png', 'icon-002.png', 'icon-003.png', 'icon-004.png', 'icon-005.png', 'icon-006.png', 'icon-007.png', 'icon-008.png', 'icon-009.png', 'icon-010.png',
                                'icon-011.png', 'icon-012.png', 'icon-013.png', 'icon-014.png', 'icon-015.png', 'icon-016.png', 'icon-017.png', 'icon-018.png', 'icon-019.png', 'icon-020.png',
                                'icon-021.png', 'icon-022.png', 'icon-023.png', 'icon-024.png', 'icon-025.png', 'icon-026.png', 'icon-027.png', 'icon-028.png', 'icon-029.png',
                                'icon-001.png', 'icon-002.png', 'icon-003.png', 'icon-004.png', 'icon-005.png', 'icon-006.png', 'icon-007.png', 'icon-008.png', 'icon-009.png', 'icon-010.png',
                                'chinese-001.png', 'chinese-002.png', 'chinese-003.png', 'chinese-004.png', 'chinese-005.png', 'chinese-006.png', 'chinese-007.png', 'chinese-008.png', 'chinese-009.png',
                                'christmas-001.png', 'christmas-002.png', 'christmas-003.png', 'christmas-004.png', 'christmas-005.png', 'christmas-006.png', 'christmas-007.png', 'christmas-008.png', 'christmas-009.png',
                                'fall-001.png', 'fall-002.png', 'fall-003.png', 'fall-004.png', 'fall-005.png', 'fall-006.png', 'fall-007.png', 'fall-008.png', 'fall-009.png',
                                'halloween-001.png', 'halloween-002.png', 'halloween-003.png', 'halloween-004.png', 'halloween-005.png', 'halloween-006.png', 'halloween-007.png', 'halloween-008.png', 'halloween-009.png',
                                'spring-001.png', 'spring-002.png', 'spring-003.png', 'spring-004.png', 'spring-005.png', 'spring-006.png', 'spring-007.png', 'spring-008.png', 'spring-009.png',
                                'summer-001.png', 'summer-002.png', 'summer-003.png', 'summer-004.png', 'summer-005.png', 'summer-006.png', 'summer-007.png', 'summer-008.png', 'summer-009.png',
                                'thanksgiving-001.png', 'thanksgiving-002.png', 'thanksgiving-003.png', 'thanksgiving-004.png', 'thanksgiving-005.png', 'thanksgiving-006.png', 'thanksgiving-007.png', 'thanksgiving-008.png', 'thanksgiving-009.png',
                                'winter-001.png', 'winter-002.png', 'winter-003.png', 'winter-004.png', 'winter-005.png', 'winter-006.png', 'winter-007.png', 'winter-008.png', 'winter-009.png',
            ];
        return allowedAvatars.includes(setting);
    }

    // Detect mobile device - Ver 1.8.1
    function isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || (window.innerWidth <= 800);
    }

    function updateChatbotStyles() {

        // console.log('Chatbot ChatGPT: NOTICE: updateChatbotStyles');

        const chatbotElement = document.getElementById('chatbot-chatgpt');
        if (!chatbotElement) return;
    
        // Calculate the viewport dimensions
        viewportWidth = window.innerWidth;
        viewportHeight = window.innerHeight;

        // console.log('Chatbot ChatGPT: NOTICE: Viewport Width:', viewportWidth, 'Viewport Height:', viewportHeight);
    
        // Adjust styles based on orientation
        const orientation = (screen.orientation && screen.orientation.type.includes('portrait')) ? 'portrait' : 'landscape';
    
        // Remove classes that are not needed
        chatbotElement.classList.remove('wide', 'chatbot-wide');

        // Apply styles and classes based on the orientation
        if (orientation === 'portrait') {
            // console.log('Chatbot ChatGPT: NOTICE: Mobile device in portrait mode');
            chatbotElement.classList.add('mobile-portrait');
            chatbotElement.style.setProperty('width', `${viewportWidth * 0.8}px`, 'important');
            chatbotElement.style.setProperty('height', `${viewportHeight * 0.7}px`, 'important');
        } else {
            // console.log('Chatbot ChatGPT: NOTICE: Mobile device in landscape mode');
            chatbotElement.classList.add('mobile-landscape');
            chatbotElement.style.setProperty('width', `${viewportWidth * 0.7}px`, 'important');
            chatbotElement.style.setProperty('height', `${viewportHeight * 0.8}px`, 'important');
        }

    }

    // Call the loadChatbotStatus function here - Ver 1.1.0
    loadChatbotStatus(); 

    // Load the conversation when the chatbot is shown on page load - Ver 1.2.0
    // Let the conversation stay persistent in session storage for increased privacy - Ver 1.4.2
    // loadConversation();

});