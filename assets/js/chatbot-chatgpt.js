jQuery(document).ready(function ($) {

    // DIAG - Diagnostics = Ver 1.4.2
    // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
    //     console.log('Chatbot: NOTICE: Entering chatbot-chatgpt.js');
    // }

    let chatGptChatBot = $('#chatbot-chatgpt').hide();

    messageInput = $('#chatbot-chatgpt-message');
    
    let conversation = $('#chatbot-chatgpt-conversation');

    submitButton = $('#chatbot-chatgpt-submit');
    uploadfileButton = $('#chatbot-chatgpt-upload-file');
    chatGptOpenButton = $('#chatgpt-open-btn');

    chatbotChatgptBotName = localStorage.getItem('chatbot_chatgpt_bot_name') || 'Kognetiks Chatbot';
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

    // Add variables for the timeout setting - Ver 1.8.8
    timeout_setting = localStorage.getItem('chatbot_chatgpt_timeout_setting') || 240;
    // console.log('Chatbot: NOTICE: timeout_setting: ' + timeout_setting);
    // Convert the timeout setting to a number
    timeout_setting = parseInt(timeout_setting);
    // Convert the timeout setting to milliseconds
    timeout_setting = timeout_setting * 1000;

    plugins_url = plugin_vars.plugins_url;

    // Get an open icon for the chatbot - Ver 1.8.6
    chatbotopenicon = plugins_url + '/assets/icons/' + 'chat_FILL0_wght400_GRAD0_opsz24.png';
    chatbotopenicon = $('<img>')
    .attr('id', 'chatbot-open-icon')
    .attr('class', 'chatbot-open-icon')
    .attr('src', chatbotopenicon);

    // Get a collapse icon for the chatbot - Ver 1.8.6
    chatbotcollapseicon = plugins_url + '/assets/icons/' + 'close_FILL0_wght400_GRAD0_opsz24.png';
    chatbotcollapseicon = $('<img>')
    .attr('id', 'chatbot-collapse-icon')
    .attr('class', 'chatbot-collapse-icon')
    .attr('src', chatbotcollapseicon);

    // Get am erase icon for the chatbot - Ver 1.8.6
    chatboteraseicon = plugins_url + '/assets/icons/' + 'delete_FILL0_wght400_GRAD0_opsz24.png';
    chatboteraseicon = $('<img>')
    .attr('id', 'chatbot-erase-icon')
    .attr('class', 'chatbot-erase-icon')
    .attr('src', chatboteraseicon);

    // console.log('Chatbot: NOTICE: chatbot_chatgpt_start_status: ' + chatbot_chatgpt_start_status);
    // console.log('Chatbot: NOTICE: chatbot_chatgpt_start_status_new_visitor: ' + chatbot_chatgpt_start_status_new_visitor);
    // console.log('Chatbot: NOTICE: chatbot_chatgpt_display_style: ' + chatbot_chatgpt_display_style);
    // console.log('Chatbot: NOTICE: chatbot_chatgpt_width_setting: ' + chatbot_chatgpt_width_setting);

    // Determine the shortcode styling where default is 'floating' or 'embedded' - Ver 1.7.1
    // var site-header = document.querySelector("#site-header");
    // var site-footer = document.querySelector("#site-footer");

    // if(header && footer) {
    //     var headerBottom = site-header.getBoundingClientRect().bottom;
    //     var footerTop = site-footer.getBoundingClientRect().top;

    //     var visible-distance = footerTop - headerBottom;
    //     console.log('Chatbot: NOTICE: Distance:  + distance + 'px');
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

        // console.log('Chatbot: NOTICE: chatbot_chatgpt_start_status: ' + chatbot_chatgpt_start_status);
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_start_status_new_visitor: ' + chatbot_chatgpt_start_status_new_visitor);
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_display_style: ' + chatbot_chatgpt_display_style);
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_width_setting: ' + chatbot_chatgpt_width_setting);

        if ( chatbot_chatgpt_display_style === 'embedded') {
            // Apply configurations for embedded style
            chatbot_chatgpt_start_status = 'open'; // Force the chatbot to open if embedded
            chatbot_chatgpt_start_status_new_visitor = 'open'; // Force the chatbot to open if embedded
        }

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

    // console.log('Chatbot: NOTICE: chatbot_chatgpt_start_status: ' + chatbot_chatgpt_start_status);
    // console.log('Chatbot: NOTICE: chatbot_chatgpt_start_status_new_visitor: ' + chatbot_chatgpt_start_status_new_visitor);
    // console.log('Chatbot: NOTICE: chatbot_chatgpt_display_style: ' + chatbot_chatgpt_display_style);
    // console.log('Chatbot: NOTICE: chatbot_chatgpt_width_setting: ' + chatbot_chatgpt_width_setting);

    if ( chatbot_chatgpt_display_style === 'embedded') {
        // Apply configurations for embedded style
        chatbot_chatgpt_start_status = 'open'; // Force the chatbot to open if embedded
        chatbot_chatgpt_start_status_new_visitor = 'open'; // Force the chatbot to open if embedded
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

    // Avatar and Custom Message - Ver 1.5.0 - Upgraded - Ver 2.0.3 - 2024 05 28
    let selectedAvatar = encodeURIComponent(localStorage.getItem('chatbot_chatgpt_avatar_icon_setting') || '');
    let customAvatar = localStorage.getItem('chatbot_chatgpt_custom_avatar_icon_setting') || '';

    // Overrides for mobile devices - Ver 1.8.1
    // if (isMobile()) {
    //     // Set selectedAvatar to 'icon-000.png' for mobile devices, i.e., none
    //     selectedAvatar = 'icon-000.png';
    // }

    // Select the avatar based on the setting - Ver 1.5.0
    if (customAvatar !== '') {
        avatarPath = customAvatar; // Use the custom URL
    } else if (selectedAvatar && selectedAvatar !== 'icon-000.png') {
        // Valid avatar setting
        if (isValidAvatarSetting(selectedAvatar)) {
            avatarPath = plugins_url + '/assets/icons/' + selectedAvatar;
        } else {
            // Invalid avatar setting
            console.error('Chatbot: ERROR: selectedAvatar: ' + selectedAvatar);
            avatarPath = plugins_url + '/assets/icons/icon-000.png';
        }
    } else {
        avatarPath = plugins_url + '/assets/icons/icon-000.png'; // Default avatar
    }

    // IDEA - Add option to suppress avatar greeting in setting options page
    // IDEA - If blank greeting, don't show the bubble
    // IDEA - Add option to suppress avatar greeting if clicked on

    // Updated to address cross-site scripting - Ver 1.8.1
    // If an avatar is selected, and it's not 'icon-000.png', use the avatar
    if (avatarPath !== plugins_url + '/assets/icons/icon-000.png') {
        avatarImg = $('<img>')
            .attr('id', 'chatbot_chatgpt_avatar_icon_setting')
            .attr('class', 'chatbot-avatar')
            .attr('src', avatarPath);

        // Get the stored greeting message. If it's not set, default to a custom value.
        let avatarGreeting = localStorage.getItem('chatbot_chatgpt_avatar_greeting_setting') || 'Howdy!!! Great to see you today! How can I help you?';

        // Create a bubble with the greeting message
        // Using .text() for safety, as it automatically escapes HTML
        let bubble = $('<div>').text(avatarGreeting).addClass('chatbot-bubble');

        // Don't add greeting bubble if mobile - Ver 2.0.3
        if (isMobile()) {
            // Remove the bubble if it was previously added
            bubble.remove();
            // Append the avatar and the bubble to the button and apply the class for the avatar icon
            chatGptOpenButton.empty().append(avatarImg).addClass('avatar-icon');
            // console.log('Chatbot: NOTICE: Mobile device detected. Avatar greeting suppressed.');
        } else {
            // Append the avatar and the bubble to the button and apply the class for the avatar icon
            chatGptOpenButton.empty().append(avatarImg, bubble).addClass('avatar-icon');
            //console.log('Chatbot: NOTICE: Avatar greeting displayed.');
        }

    } else {
        // If no avatar is selected or the selected avatar is 'icon-000.png', use the dashicon
        // Remove the avatar-icon class (if it was previously added) and add the dashicon class
        chatGptOpenButton.empty().removeClass('avatar-icon').addClass('chatbot-open-icon').append(chatbotopenicon); // Add an open button
    }
    
    // Append the collapse button and collapsed chatbot icon to the chatbot container
    $('#chatbot-chatgpt-header').append(chatbotCollapseBtn);
    chatbotContainer.append(chatbotCollapsed);

    // Add initial greeting to the chatbot
    conversation.append(chatbotContainer);

    function initializeChatbot() {

        isFirstTime = !localStorage.getItem('chatbot_chatgpt_opened') || false;

        // Remove any legacy conversations that might be store in local storage for increased privacy - Ver 1.4.2
        localStorage.removeItem('chatbot_chatgpt_conversation');

        // console.log('Chatbot: NOTICE: isFirstTime: ' + isFirstTime);

        if (isFirstTime) {
            // DIAG - Logging for Diagnostics
            // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
            //     console.log('Chatbot: NOTICE: initializeChatbot at isFirstTime');
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
            sessionStorage.setItem('chatbot_chatgpt_conversation', conversation.html());           

        } else {
            // DIAG - Logging for Diagnostics - Ver 1.4.2
            // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
            //     console.log('Chatbot: NOTICE: initializeChatbot at else');
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

        // Convert HTML entities back to their original form
        let decodedMessage = $('<textarea/>').html(message).text();

        // Check if the message contains an audio tag
        if (decodedMessage.includes('<audio')) {
            // Add the autoplay attribute to the audio tag
            decodedMessage = decodedMessage.replace('<audio', '<audio autoplay');
        }

        // Parse the HTML string
        let parsedHtml = $.parseHTML(decodedMessage);

        // Create a new span element
        let textElement = $('<span></span>');

        // Iterate over the parsed elements
        $.each(parsedHtml, function(i, el) {
            // Append each element to the textElement as HTML
            textElement.append(el);
        });

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
        if (conversation && conversation[0]) {
            conversation[0].scrollTop = conversation[0].scrollHeight;
        }
        // Scroll to bottom if embedded - Ver 1.7.1
        // window.scrollTo(0, document.body.scrollHeight);

        // Save the conversation locally between bot sessions - Ver 1.2.0
        // if message starts with "Conversation Clearer" then clear the conversation - Ver 1.9.3
        if (message.startsWith('Conversation cleared')) {
            // Clear the conversation from sessionStorage
            // console.log('Chatbot: NOTICE: Clearing the conversation');
            sessionStorage.removeItem('chatbot_chatgpt_conversation');
        } else {
            // console.log('Chatbot: NOTICE: Saving the conversation');
            sessionStorage.setItem('chatbot_chatgpt_conversation', conversation.html());
        }

    }

    function showTypingIndicator() {
        typingIndicator = $('<div></div>').addClass('typing-indicator');
        dot1 = $('<span>.</span>').addClass('typing-dot');
        dot2 = $('<span>.</span>').addClass('typing-dot');
        dot3 = $('<span>.</span>').addClass('typing-dot');
        
        typingIndicator.append(dot1, dot2, dot3);
        conversation.append(typingIndicator);
        if (conversation && conversation[0]) {
            conversation.scrollTop(conversation[0].scrollHeight);
        }
    }

    function removeTypingIndicator() {
        $('.typing-indicator').remove();
    }

    // markdownToHtml - Ver 1.9.2
    function markdownToHtml(markdown) {

        // console.log("Original Markdown:", markdown);
    
        // Step 1: Extract predefined HTML tags
        const predefinedHtmlRegex = /<.*?>/g;
        let predefinedHtml = [];
        markdown = markdown.replace(predefinedHtmlRegex, (match) => {
            predefinedHtml.push(match);
            return `{{HTML_TAG_${predefinedHtml.length - 1}}}`;
        });
        // console.log("After Extracting HTML Tags:", markdown);
    
        // Step 2: Escape HTML outside of code blocks
        markdown = markdown.split(/(```[\s\S]+?```)/g).map((chunk, index) => {
            return index % 2 === 0 ? chunk.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;") : chunk;
        }).join('');
        // console.log("After HTML Escape:", markdown);
    
        // Step 3: Process images first
        markdown = markdown.replace(/\!\[(.*?)\]\((.*?)\)/g, `<img alt="$1" src="$2">`);
        // console.log("After Image Replacement:", markdown);
    
        // Step 4: Process links before any other inline elements
        markdown = markdown.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank">$1</a>');
        // console.log("After Link Replacement:", markdown);
    
        // Step 5: Headers
        markdown = markdown.replace(/^#### (.*)$/gim, '<h4>$1</h4>')
                           .replace(/^### (.*)$/gim, '<h3>$1</h3>')
                           .replace(/^## (.*)$/gim, '<h2>$1</h2>')
                           .replace(/^# (.*)$/gim, '<h1>$1</h1>');
        // console.log("After Headers Replacement:", markdown);
    
        // Step 6: Bold, Italic, Strikethrough
        markdown = markdown.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                           .replace(/\*(.*?)\*/g, '<em>$1</em>')
                           .replace(/\~\~(.*?)\~\~/g, '<del>$1</del>');
        // console.log("After Text Formatting Replacement:", markdown);
    
        // Step 7: Multi-line code blocks
        markdown = markdown.replace(/```([\s\S]*?)```/gm, '<pre><code>$1</code></pre>');
        // console.log("After Code Block Replacement:", markdown);
    
        // Step 8: Inline code - after handling multi-line to prevent conflicts
        markdown = markdown.replace(/`([^`]+)`/g, '<code>$1</code>');
        // console.log("After Inline Code Replacement:", markdown);
    
        // Step 9: Lists - Needs refining for nested lists
        markdown = markdown.replace(/^\*\s(.+)$/gim, '<li>$1</li>')
                           .replace(/<\/li><li>/g, '</li>\n<li>')
                           .replace(/<li>(.*?)<\/li>/gs, '<ul>$&</ul>')
                           .replace(/<ul>\s*<li>/g, '<ul>\n<li>')
                           .replace(/<\/li>\s*<\/ul>/g, '</li>\n</ul>');
        // console.log("After Lists Replacement:", markdown);
    
        // Step 10: Improved blockquote handling
        markdown = markdown.replace(/^(>+\s?)(.*)$/gm, (match, p1, p2) => {
            return `<blockquote>${p2}</blockquote>`;
        });
        // console.log("After Blockquote Replacement:", markdown);
    
        // Step 11: Convert line breaks to <br>, except for code blocks and blockquotes
        markdown = markdown.split(/(<pre><code>[\s\S]*?<\/code><\/pre>|<blockquote>[\s\S]*?<\/blockquote>)/g).map((chunk, index) => {
            // Only convert newlines to <br> outside of code blocks and blockquotes
            return index % 2 === 0 ? chunk.replace(/\n/g, '<br>') : chunk;
        }).join('');
        // console.log("After Line Breaks Replacement:", markdown);
    
        // Step 12: Reinsert predefined HTML tags
        markdown = markdown.replace(/{{HTML_TAG_(\d+)}}/g, (match, index) => {
            return predefinedHtml[parseInt(index)];
        });
        // console.log("After Reinserting HTML Tags:", markdown);
    
        return markdown.trim();

    }
    
    // Submit the message when the submit button is clicked
    submitButton.on('click', function () {

        // Sanitize the input - Ver 2.0.0
        message = sanitizeInput(messageInput.val().trim());
        // console.log('Chatbot: NOTICE: Message: ' + message);

        if (!message) {
            return;
        }

        // Reset message count daily
        let lastReset = localStorage.getItem('chatbot_chatgpt_last_reset');
        let today = new Date().toDateString();

        if (lastReset !== today) {
            localStorage.setItem('chatbot_chatgpt_message_count', 0); // Reset the counter
            localStorage.setItem('chatbot_chatgpt_last_reset', today); // Update last reset date
        }

        // Add +1 to the message count - Ver 1.9.6
        let messageCount = localStorage.getItem('chatbot_chatgpt_message_count') || 0;
        messageCount++;
        localStorage.setItem('chatbot_chatgpt_message_count', messageCount);
      
        // If messageCount is greater than  messageLimit then don't send the message - Ver 1.9.6
        let messageLimit = localStorage.getItem('chatbot_chatgpt_message_limit_setting') || 999999;
        if (messageCount > messageLimit) {
            appendMessage('Oops! You have reached the message limit. Please try again later.', 'error');
            return;
        }

        input_type = 'user';

        // Check to see if the message starts with [Chatbot] - Ver 1.9.5
        if (message.startsWith('[Chatbot]')) {
            // console.log('Chatbot: NOTICE: Message starts with [Chatbot]');
            input_type = 'chatbot';
        }
          
        messageInput.val('');
        if (input_type === 'user') {
            appendMessage(message, 'user');
        } else {
            // DO NOTHING
        }
        // appendMessage(message, 'user');

        let user_id = php_vars.user_id;
        let page_id = php_vars.page_id;

        $.ajax({
            url: chatbot_chatgpt_params.ajax_url,
            method: 'POST',
            timeout: timeout_setting, // Example: 10,000ms = 10 seconds
            data: {
                action: 'chatbot_chatgpt_send_message',
                message: message,
                user_id: user_id, // pass the user ID here
                page_id: page_id, // pass the page ID here
            },
            headers: {  // Adding headers to prevent caching
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },        
            beforeSend: function () {
                showTypingIndicator();
                submitButton.prop('disabled', true);
            },
            success: function (response) {
                // console.log('Chatbot: SUCCESS: ' + JSON.stringify(response));
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
                        if (typeof botResponse === 'string' && botResponse.startsWith(prefix)) {
                            botResponse = botResponse.slice(prefix.length);
                            break;
                        }
                    }
                }
                // markdownToHtml - Ver 1.9.2
                botResponse = markdownToHtml(botResponse);
            },
            error: function (jqXHR, status, error) {
                if(status === "timeout") {
                    appendMessage('Error: ' + error, 'error');
                    appendMessage('Oops! This request timed out. Please try again.', 'error');
                    botResponse = '';
                } else {
                    // DIAG - Log the error - Ver 1.6.7
                    // console.log('Chatbot ChatGPT: ERROR: ' + JSON.stringify(response));
                    // appendMessage('Error: ' + errorThrown, 'error');
                    appendMessage('Error: ' + error, 'error')
                    appendMessage('Oops! Something went wrong on our end. Please try again later.', 'error');
                    botResponse = '';
                }
            },
            complete: function () {
                removeTypingIndicator();
                if (botResponse) {
                    appendMessage(botResponse, 'bot');
                }
                scrollToLastBotResponse();
                submitButton.prop('disabled', false);
            },
            cache: false, // This ensures jQuery does not cache the result
        });
    });

    // Input mitigation - Ver 2.0.0
    function sanitizeInput(input) {
        // Replace script end tags
        input = input.replace(/<\/script\s*>/gi, "");
    
        // Replace <script with &lt;script
        input = input.replace(/<script/gi, "&lt;script");
    
        // Escape quotes and other special characters
        input = input.replace(/"/g, "&quot;");
        input = input.replace(/'/g, "&#x27;");
        input = input.replace(/</g, "&lt;");
        input = input.replace(/>/g, "&gt;");
        input = input.replace(/`/g, "&#x60;");
    
        return input;
    }
    
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
            // console.log('Chatbot: NOTICE: Enter key pressed on upload file button');
            let $response = chatbot_chatgpt_upload_files();
            $('#chatbot-chatgpt-upload-file-input').click();
            let button = $(this);  // Store a reference to the button
            setTimeout(function() {
                button.blur();  // Remove focus from the button
            }, 0);
        }
    });

    // Add the keydown event listener to the upload mp3 button - Ver 2.0.1
    $('#chatbot-chatgpt-upload-mp3').on('keydown', function(e) {
        if (e.keyCode === 13  && !e.shiftKey) {
            e.preventDefault();
            // console.log('Chatbot: NOTICE: Enter key pressed on upload mp3 button');
            let $response = chatbot_chatgpt_upload_mp3();
            $('#chatbot-chatgpt-upload-mp3-input').click();
            let button = $(this);  // Store a reference to the button
            setTimeout(function() {
                button.blur();  // Remove focus from the button
            }, 0);
        }
    });

    // Add the click event listener to the download transcript button - Ver 1.9.9
    $('#chatbot-chatgpt-download-transcript-btn').on('click', function(e) {
        e.preventDefault();  // Prevent the default action of the button (if needed)
        // console.log('Button clicked: Downloading transcript');  // Optional: Log to console
    
        let conversationContent = $('#chatbot-chatgpt-conversation').html();  // Get the HTML content
        let button = $(this);  // Store a reference to the button
    
        $.ajax({
            url: chatbot_chatgpt_params.ajax_url,  // URL to WordPress AJAX handler
            method: 'POST',
            data: {
                action: 'chatbot_chatgpt_download_transcript',
                user_id: php_vars.user_id,
                page_id: php_vars.page_id,
                conversation_content: conversationContent  // Send the conversation content
            },
            beforeSend: function () {
                // Show typing indicator and disable submit button
                // Replace these functions with your own
                showTypingIndicator();
                $('#submit-button').prop('disabled', true);
            },
            success: function(response) {
                if (response.success && response.data) {
                    let link = document.createElement('a');
                    link.href = response.data;
                    link.download = ''; // Optionally set the filename
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    // console.error('Error: Download URL not provided or error in response.');
                    // console.error(response.data || 'No additional error data.');
                    appendMessage('Oops! There was a problem downloading the transcript. Please try again later.', 'error');
                }
            },
            error: function(jqXHR, status, error) {
                // Handle AJAX errors
                appendMessage('Error: ' + error, 'error');
                appendMessage('Oops! There was a problem downloading the transcript. Please try again late.', 'error');
            },
            complete: function () {
                // Remove typing indicator and enable submit button
                // Replace these functions with your own
                removeTypingIndicator();
                $('#submit-button').prop('disabled', false);
                button.blur();  // Remove focus from the button
            },
        });
    });

    // Read Out Loud - Ver 1.9.5
    $('#chatbot-chatgpt-text-to-speech-btn').on('click', function(e) {

        // console.log('Chatbot: NOTICE: Text-to-Speech button clicked');

        // Read out loud the last bot response
        let lastMessage = $('#chatbot-chatgpt-conversation .bot-message:last .bot-text').text();
        let button = $(this);  // Store a reference to the button

        // console.log('Chatbot: NOTICE: lastMessage: ' + lastMessage);

        // Check if the bot response is empty
        if (!lastMessage) {
            appendMessage('Oops! There is no response to read out loud.', 'error');
            return;
        }

        // Call function "chatbot_chatgpt_call_tts_api" to convert the text to speech
        $.ajax({
            url: chatbot_chatgpt_params.ajax_url,
            method: 'POST',
            timeout: timeout_setting, // Example timeout_setting value: 10000 for 10 seconds
            data: {
                action: 'chatbot_chatgpt_read_aloud',
                message: lastMessage,
            },
            beforeSend: function () {
                showTypingIndicator();
                submitButton.prop('disabled', true);
            },
            success: function(response) {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }
                response.data = markdownToHtml(response.data);
                // appendMessage('Text-to-Speech: ' + response.data, 'bot');
                appendMessage(response.data, 'bot');
            },
            error: function(jqXHR, status, error) {
                if(status === "timeout") {
                    appendMessage('Error: ' + error, 'error');
                    appendMessage('Oops! This request timed out. Please try again.', 'error');
                } else {
                    // DIAG - Log the error - Ver 1.6.7
                    // console.log('Chatbot: ERROR: ' + JSON.stringify(response));
                    appendMessage('Error: ' + error, 'error');
                    appendMessage('Oops! Failed to convert text to speech. Please try again.', 'error');
                }
            },
            complete: function () {
                removeTypingIndicator();
                submitButton.prop('disabled', false);
                button.blur();  // Remove focus from the button
            },
        });
    });

    // List of allowed MIME types - Ver 2.0.1
    var allowedFileTypes = [
        'text/csv',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/gif',
        'image/jpeg',
        'audio/mpeg',
        'video/mp4',
        'video/mpeg',
        'audio/m4a',
        'application/pdf',
        'image/png',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/rtf',
        'image/svg+xml',
        'text/plain',
        'audio/wav',
        'video/webm',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/xml',
        'application/json',
        'text/markdown',
        'application/zip'
    ];
    // List of allowed extensions - Ver 2.0.1
    var allowedExtensions = [
        '.csv', '.doc', '.docx', '.gif', '.jpeg', '.jpg', '.mp3', '.mp4', 
        '.mpeg', '.m4a', '.pdf', '.png', '.ppt', '.pptx', '.rtf', '.svg', 
        '.txt', '.wav', '.webm', '.xls', '.xlsx', '.xml', '.json', '.md', '.zip'
    ];

    // Function to get file extension - Ver 2.0.1
    function getFileExtension(filename) {
        var index = filename.lastIndexOf('.');
        return index < 0 ? '' : filename.substr(index);
    }

    $('#chatbot-chatgpt-upload-file-input').on('change', function(e) {

        // console.log('Chatbot: NOTICE: File selected');
        
        let fileField = e.target;
    
        // Check if any files are selected
        if (!fileField.files.length) {
            // console.log('Chatbot: WARNING: No file selected');
            return;
        }
    
        let formData = new FormData();
        // Append each file to the formData object - Updated - Ver 2.0.1
        var hasDisallowedFile = false;
        for (var i = 0; i < fileField.files.length; i++) {
            var file = fileField.files[i];
            var ext = getFileExtension(file.name).toLowerCase();
            var fileType = file.type;
            // Validate the file type and extension
            if (allowedFileTypes.includes(fileType) && allowedExtensions.includes(ext)) {
                formData.append('file[]', file);
            } else {
                // console.log('Disallowed file type or extension: ' + file.name);
                hasDisallowedFile = true;
                appendMessage('Oops! Unsupported file type. Please try again.', 'error');
                break;
            }
        }

        if (hasDisallowedFile) {
            return;
        }
        // console.log('Chatbot: NOTICE: Files selected ', fileField.files);
        formData.append('action', 'chatbot_chatgpt_upload_files');
    
        $.ajax({
            url: chatbot_chatgpt_params.ajax_url,
            method: 'POST',
            timeout: timeout_setting, // Example timeout_setting value: 10000 for 10 seconds
            data: formData,
            processData: false,  // Tell jQuery not to process the data
            contentType: false,  // Tell jQuery not to set contentType
            beforeSend: function () {
                showTypingIndicator();
                submitButton.prop('disabled', true);
            },
            success: function(response) {
                // console.log('Chatbot: NOTICE: Response from server', response);
                $('#chatbot-chatgpt-upload-file-input').val(''); // Clear the file input after successful upload
                appendMessage('File(s) successfully uploaded.', 'bot');
            },
            error: function(jqXHR, status, error) {
                if(status === "timeout") {
                    appendMessage('Error: ' + error, 'error');
                    appendMessage('Oops! This request timed out. Please try again.', 'error');
                } else {
                    // DIAG - Log the error - Ver 1.6.7
                    // console.log('Chatbot: ERROR: ' + JSON.stringify(response));
                    appendMessage('Error: ' + error, 'error');
                    appendMessage('Oops! Failed to upload file. Please try again.', 'error');
                }
            },
            complete: function () {
                removeTypingIndicator();
                submitButton.prop('disabled', false);
            },
        });
    });

    $('#chatbot-chatgpt-upload-mp3-input').on('change', function(e) {

        // console.log('Chatbot: NOTICE: MP3 selected');
        
        let fileField = e.target;
    
        // Check if any files are selected
        if (!fileField.files.length) {
            // console.log('Chatbot: WARNING: No file selected');
            return;
        }
    
        let formData = new FormData();
        // Append each file to the formData object - Updated - Ver 2.0.1
        var hasDisallowedFile = false;
        for (var i = 0; i < fileField.files.length; i++) {
            var file = fileField.files[i];
            var ext = getFileExtension(file.name).toLowerCase();
            var fileType = file.type;
            // Validate the file type and extension
            if (allowedFileTypes.includes(fileType) && allowedExtensions.includes(ext)) {
                formData.append('file[]', file);
            } else {
                // console.log('Disallowed file type or extension: ' + file.name);
                hasDisallowedFile = true;
                appendMessage('Oops! Unsupported file type. Please try again.', 'error');
                break;
            }
        }

        if (hasDisallowedFile) {
            return;

        }
        // console.log('Chatbot: NOTICE: Files selected ', fileField.files);
        formData.append('action', 'chatbot_chatgpt_upload_mp3');
    
        $.ajax({
            url: chatbot_chatgpt_params.ajax_url,
            method: 'POST',
            timeout: timeout_setting, // Example timeout_setting value: 10000 for 10 seconds
            data: formData,
            processData: false,  // Tell jQuery not to process the data
            contentType: false,  // Tell jQuery not to set contentType
            beforeSend: function () {
                showTypingIndicator();
                submitButton.prop('disabled', true);
            },
            success: function(response) {
                // console.log('Chatbot: NOTICE: Response from server', response);
                $('#chatbot-chatgpt-upload-mp3-input').val(''); // Clear the file input after successful upload
                appendMessage('File(s) successfully uploaded.', 'bot');
            },
            error: function(jqXHR, status, error) {
                if(status === "timeout") {
                    appendMessage('Error: ' + error, 'error');
                    appendMessage('Oops! This request timed out. Please try again.', 'error');
                } else {
                    // DIAG - Log the error - Ver 1.6.7
                    // console.log('Chatbot: ERROR: ' + JSON.stringify(response));
                    appendMessage('Error: ' + error, 'error');
                    appendMessage('Oops! Failed to upload file. Please try again.', 'error');
                }
            },
            complete: function () {
                removeTypingIndicator();
                submitButton.prop('disabled', false);
            },
        });
    });

    // Add the click event listener to the clear button - Ver 1.8.6
    $('#chatbot-chatgpt-erase-btn').on('click', function() {

        // console.log('Chatbot: NOTICE: Erase conversation selected');

        let user_id = php_vars.user_id;
        let page_id = php_vars.page_id;
        let session_id = php_vars.session_id;
        let assistant_id = php_vars.assistant_id;
        let thread_id = php_vars.thread_id;

        // DIAG - Diagnostics - Ver 1.9.1
        // console.log('Chatbot: NOTICE: assistant_id: ' + assistant_id);
    
        $.ajax({
            url: chatbot_chatgpt_params.ajax_url,
            method: 'POST',
            timeout: timeout_setting, // Example: 10,000ms = 10 seconds
            data: {
                action: 'chatbot_chatgpt_erase_conversation', // The action to be handled on the server-side
                user_id: user_id, // pass the user ID here
                page_id: page_id, // pass the page ID here
                session_id: session_id, // pass the session
                thread_id: thread_id, // pass the thread ID
                assistant_id: assistant_id, // pass the assistant ID
            },
            beforeSend: function () {
                showTypingIndicator();
                submitButton.prop('disabled', true);
            },
            success: function(response) {
                // sessionStorage.setItem('chatbot_chatgpt_conversation', ''); // Clear the conversation from sessionStorage
                sessionStorage.removeItem('chatbot_chatgpt_conversation'); // Clear the last response from sessionStorage
                // DIAG - Log the response
                // console.log('Success:', response.data);
                appendMessage( response.data, 'bot');
                // Force a page reload
                // location.reload();
            },
            error: function(jqXHR, status, error) {
                if(status === "timeout") {
                    appendMessage('Error: ' + error, 'error');
                    appendMessage('Oops! This request timed out. Please try again.', 'error');
                } else {
                    // DIAG - Log the error - Ver 1.6.7
                    // console.log('Chatbot: ERROR: ' + JSON.stringify(response));
                    appendMessage('Error: ' + error, 'error');
                    appendMessage('Oops! Unable to clear conversation. Please try again.', 'error');
                }
            },
            complete: function () {
                removeTypingIndicator();
                submitButton.prop('disabled', false);
            },
        });
       
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
            // Removed in Ver 1.9.3
            loadConversation();
            scrollToBottom();
        }
    }

    // Add this function to maintain the chatbot status across page refreshes and sessions - Ver 1.1.0 and updated for Ver 1.4.1
    function loadChatbotStatus() {
        chatbot_chatgpt_start_status = localStorage.getItem('chatbot_chatgpt_start_status');
        chatbot_chatgpt_start_status_new_visitor = localStorage.getItem('chatbot_chatgpt_start_status_new_visitor');

        // console.log('Chatbot: NOTICE: chatbot_chatgpt_start_status: ' + chatbot_chatgpt_start_status);
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_start_status_new_visitor: ' + chatbot_chatgpt_start_status_new_visitor);
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_display_style: ' + chatbot_chatgpt_display_style);
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_width_setting: ' + chatbot_chatgpt_width_setting);
    
        // FIXME - THIS SHOULD FIX IOS CHROME ISSUE - Ver 1.8.6
        if ( chatbot_chatgpt_display_style === 'embedded') {
            // Apply configurations for embedded style
            chatbot_chatgpt_start_status = 'open'; // Force the chatbot to open if embedded
            chatbot_chatgpt_start_status_new_visitor = 'open'; // Force the chatbot to open if embedded
        } else {
            chatbot_chatgpt_start_status = 'closed';
            chatbot_chatgpt_start_status_new_visitor = 'closed';
        }

        // Nuclear option to clear session conversation - Ver 1.5.0
        // Do not use unless absolutely needed
        // DIAG - Diagnostics - Ver 1.5.0
        // nuclearOption = 'Off';
        // if (nuclearOption === 'On') {
        //     console.log('Chatbot: NOTICE: ***** NUCLEAR OPTION IS ON ***** ');
        //     sessionStorage.removeItem('chatbot_chatgpt_conversation');
        //     // Removed in Ver 1.6.1
        //     sessionStorage.removeItem('chatgpt_last_response');
        // }

        // DIAG - Diagnostics - Ver 1.5.0
        // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
        //     console.log('Chatbot: NOTICE: loadChatbotStatus - BEFORE DECISION');
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
        //     console.log('Chatbot: NOTICE: loadChatbotStatus - AFTER DECISION');
        // }
        
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_start_status: ' + chatbot_chatgpt_start_status);
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_start_status_new_visitor: ' + chatbot_chatgpt_start_status_new_visitor);
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_display_style: ' + chatbot_chatgpt_display_style);
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_width_setting: ' + chatbot_chatgpt_width_setting);
        
        // If the chatbot status is not set in local storage, use chatbot_chatgpt_start_status - Ver 1.5.1
        if (chatbot_chatgpt_start_status === 'closed') {
            chatGptChatBot.hide();
            chatGptOpenButton.show();
        } else {
            chatGptChatBot.show();
            chatGptOpenButton.hide();
            // Load the conversation if the chatbot is open on page load
            // Removed in Ver 1.9.3
            loadConversation();
            scrollToBottom();
        }

    }

    // Add this function to scroll to the bottom of the conversation - Ver 1.2.1
    function scrollToBottom() {
        // setTimeout(() => {
        //     // DIAG - Diagnostics - Ver 1.5.0
        //     // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
        //     //     console.log('Chatbot: NOTICE: scrollToBottom");
        //     // }
        //     if (conversation && conversation.length > 0) {
        //         conversation.scrollTop(conversation[0].scrollHeight);
        //     }
        // }, 100);  // delay of 100 milliseconds  

    }

    // Add this function to scroll to the top of the last chatbot response - Ver 2.0.3
    function scrollToLastBotResponse() {
        setTimeout(() => {
            // DIAG - Diagnostics - Ver 1.5.0
            // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
                 console.log('Chatbot: NOTICE: scrollToLastBotResponse');
            // }

            const botTexts = document.querySelectorAll('.bot-text');
            if (botTexts && botTexts.length > 0) {
                const lastBotText = botTexts[botTexts.length - 1];
                const conversation = document.querySelector('#conversation');
                if (conversation) {
                    let topPosition = lastBotText.offsetTop;
                    let parent = lastBotText.offsetParent;
                    while(parent && parent !== conversation) {
                        topPosition += parent.offsetTop;
                        parent = parent.offsetParent;
                    }
                    topPosition -= conversation.clientHeight;
                    conversation.scrollTo({
                        top: topPosition > 0 ? topPosition : 0,
                        behavior: 'smooth'
                    });
                }
            }
        }, 100);  // delay of 100 milliseconds
    }
   
    // Load conversation from local storage if available - Ver 1.2.0
    function loadConversation() {

        // Removed in Ver 1.9.3
        // storedConversation = sessionStorage.getItem('chatbot_chatgpt_conversation');
        // Reset the conversation - Added in Ver 1.9.3
        storedConversation = '';
        localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', 'closed');

        // FIXME - IS THIS USED ANYWHERE ??? - Ver 1.8.9
        if (storedConversation) {
            // DIAG - Diagnostics - Ver 1.5.0
            // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
            //     console.log('Chatbot: NOTICE: loadConversation - IN THE IF STATEMENT');
            // }

            // Check if current conversation is different from stored conversation
            // FIXME - ADDED THIS BACK IN VER 1.9.1 - 2024 03 04
            // if (conversation.html() !== storedConversation) {
            //     conversation.html(storedConversation);  // Set the conversation HTML to stored conversation
            // }
            // Fix for XSS vulnerability - Ver 1.8.1
            // FIXME - REMOVED THIS MAY BE BREAKING VER 1.9.1 - 2024 03 04
            if (conversation.html() !== storedConversation) {
                let sanitizedConversation = DOMPurify.sanitize(storedConversation);
                conversation.html(sanitizedConversation);  // Set the conversation HTML to sanitized stored conversation
            }          

            // Use setTimeout to ensure scrollToBottom is called after the conversation is rendered
            setTimeout(scrollToBottom, 0);
        } else {
            // DIAG - Diagnostics - Ver 1.5.0
            // if (chatbotSettings.chatbot_chatgpt_diagnostics === 'On') {
            //     console.log('Chatbot: NOTICE: loadConversation - IN THE ELSE STATEMENT');
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

        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Windows Phone|Silk|Kindle|Symbian/i.test(navigator.userAgent) || (window.innerWidth <= 800);

    }

    function updateChatbotStyles() {

        // console.log('Chatbot: NOTICE: updateChatbotStyles');

        // Just return if it's mobile and embedded
        if (isMobile() && chatbot_chatgpt_display_style === 'embedded') {
            return;
        }

        const chatbotElement = document.getElementById('chatbot-chatgpt');
        if (!chatbotElement) return;
    
        // Calculate the viewport dimensions
        viewportWidth = window.innerWidth;
        viewportHeight = window.innerHeight;

        // console.log('Chatbot: NOTICE: Viewport Width:', viewportWidth, 'Viewport Height:', viewportHeight);
    
        // Adjust styles based on orientation
        const orientation = (screen.orientation && screen.orientation.type.includes('portrait')) ? 'portrait' : 'landscape';
    
        // Remove classes that are not needed
        chatbotElement.classList.remove('wide', 'chatbot-wide');

        // Apply styles and classes based on the orientation
        if (orientation === 'portrait') {
            // console.log('Chatbot: NOTICE: Mobile device in portrait mode');
            chatbotElement.classList.add('mobile-portrait');
            chatbotElement.style.setProperty('width', `${viewportWidth * 0.8}px`, 'important');
            chatbotElement.style.setProperty('height', `${viewportHeight * 0.7}px`, 'important');
        } else {
            // console.log('Chatbot: NOTICE: Mobile device in landscape mode');
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