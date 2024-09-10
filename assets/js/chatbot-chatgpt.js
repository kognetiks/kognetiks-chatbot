jQuery(document).ready(function ($) {

    if (typeof kchat_settings === 'undefined') {
        // console.error('Chatbot: NOTICE: kchat_settings is not defined.');
        return;
    } else {
        // console.log('Chatbot: NOTICE: kchat_settings:', kchat_settings);
    }

    // Only call the function if the chatbot shortcode is present
    if (isChatbotShortcodePresent()) {
        // console.log('Chatbot: NOTICE: Chatbot shortcode not found.');
        return;
    } else {
        // console.log('Chatbot: NOTICE: Chatbot shortcode found.');
    }

    // Function to check if the chatbot shortcode is present on the page
    function isChatbotShortcodePresent() {
        return document.querySelector('.chatbot-chatgpt') !== null;
    }
   
    // DIAG - Diagnostic - Ver 2.1.1.1
    // const sortedKeys = Object.keys(kchat_settings).sort();
    // for (const key of sortedKeys) {
    //     console.log('Chatbot: NOTICE: kchat_settings: ' + key + ': ' + kchat_settings[key]);
    // }

    let chatbot_chatgpt_Elements = $('#chatbot-chatgpt').hide();
    let messageInput = $('#chatbot-chatgpt-message');
    let conversation = $('#chatbot-chatgpt-conversation');
    let submitButton = $('#chatbot-chatgpt-submit');
    let chatGptOpenButton = $('#chatgpt-open-btn');

    // console.log('Chatbot: NOTICE: chatbot_chatgpt_display_style: ' + kchat_settings.chatbot_chatgpt_display_style);

    let chatbot_chatgpt_display_style = kchat_settings.chatbot_chatgpt_display_style || 'floating';
    let chatbot_chatgpt_width_setting = kchat_settings.chatbot_chatgpt_width_setting || 'Narrow';

    let initialGreeting = kchat_settings.chatbot_chatgpt_initial_greeting || 'Hello! How can I help you today?';
    let subsequentGreeting = kchat_settings.chatbot_chatgpt_subsequent_greeting || 'Hello again! How can I help you?';

    let chatbot_chatgpt_start_status = kchat_settings.chatbot_chatgpt_start_status || 'closed';
    let chatbot_chatgpt_start_status_new_visitor = kchat_settings.chatbot_chatgpt_start_status_new_visitor || 'closed';

    // Convert the timeout setting to milliseconds
    let timeout_setting = (parseInt(kchat_settings.chatbot_chatgpt_timeout_setting) || 240) * 1000;

    plugins_url = kchat_settings['plugins_url'];

    // Get an open icon for the chatbot - Ver 1.8.6
    chatbotopenicon = plugins_url + 'assets/icons/' + 'chat_FILL0_wght400_GRAD0_opsz24.png';
    chatbotopenicon = $('<img>')
    .attr('id', 'chatbot-open-icon')
    .attr('class', 'chatbot-open-icon')
    .attr('src', chatbotopenicon);

    // Get a collapse icon for the chatbot - Ver 1.8.6
    chatbotcollapseicon = plugins_url + 'assets/icons/' + 'close_FILL0_wght400_GRAD0_opsz24.png';
    chatbotcollapseicon = $('<img>')
    .attr('id', 'chatbot-collapse-icon')
    .attr('class', 'chatbot-collapse-icon')
    .attr('src', chatbotcollapseicon);

    // Get an erase icon for the chatbot - Ver 1.8.6
    chatboteraseicon = plugins_url + 'assets/icons/' + 'delete_FILL0_wght400_GRAD0_opsz24.png';
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
        // console.log('Chatbot: NOTICE: Embedded style detected.');
        // Apply configurations for embedded style
        $('#chatbot-chatgpt').addClass('chatbot-embedded-style').removeClass('chatbot-floating-style');
        // Other configurations specific to embedded style
        chatbot_chatgpt_start_status = 'open'; // Force the chatbot to open if embedded
        chatbot_chatgpt_start_status_new_visitor = 'open'; // Force the chatbot to open if embedded
        localStorage.setItem('chatbot_chatgpt_start_status', chatbot_chatgpt_start_status);
        localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', chatbot_chatgpt_start_status_new_visitor);
        chatbot_chatgpt_Elements.addClass('chatbot-embedded-style').removeClass('chatbot-floating-style');
    } else {
        // console.log('Chatbot: NOTICE: Floating style detected.');
        // Apply configurations for floating style
        $('#chatbot-chatgpt').addClass('chatbot-floating-style').removeClass('chatbot-embedded-style');
        // Other configurations specific to floating style
        if (chatbot_chatgpt_width_setting === 'Wide') {
            chatbot_chatgpt_Elements.addClass('wide');
        } else {
            // chatbot_chatgpt_Elements.removeClass('wide').css('display', 'none');
            chatbot_chatgpt_Elements.removeClass('wide');
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
        // console.log('Chatbot: NOTICE: Orientation: Landscape');
        } else if (orientation.type === 'portrait-primary') {
        // console.log('Chatbot: NOTICE: Orientation: Portrait');
        } else {
        // console.log('Chatbot: NOTICE: Orientation:', orientation.type);
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

    // Initially hide the chatbot
    if (chatbot_chatgpt_start_status === 'closed') {
        chatbot_chatgpt_Elements.hide();
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

            chatbot_chatgpt_Elements.show();
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
    let selectedAvatar = kchat_settings.chatbot_chatgpt_avatar_icon_setting || '';
    let customAvatar = kchat_settings.chatbot_chatgpt_custom_avatar_icon_setting || '';

    customAvatar = DOMPurify.sanitize(customAvatar); // Sanitize the custom avatar URL
    // customAvatar = document.createTextNode(customAvatar); // Create a text node from the custom avatar URL

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
            avatarPath = plugins_url + 'assets/icons/' + selectedAvatar;
        } else {
            // Invalid avatar setting
            // console.error('Chatbot: ERROR: selectedAvatar: ' + selectedAvatar);
            avatarPath = plugins_url + 'assets/icons/icon-000.png';
        }
    } else {
        avatarPath = plugins_url + 'assets/icons/icon-000.png'; // Default avatar
    }

    // IDEA - Add option to suppress avatar greeting in setting options page
    // IDEA - If blank greeting, don't show the bubble
    // IDEA - Add option to suppress avatar greeting if clicked on

    // Updated to address cross-site scripting - Ver 1.8.1
    // If an avatar is selected, and it's not 'icon-000.png', use the avatar
    if (avatarPath !== plugins_url + 'assets/icons/icon-000.png') {
        avatarImg = $('<img>')
            .attr('id', 'chatbot_chatgpt_avatar_icon_setting')
            .attr('class', 'chatbot-avatar')
            .attr('src', avatarPath);

        // Get the stored greeting message. If it's not set, default to a custom value.
        let avatarGreeting = kchat_settings['chatbot_chatgpt_avatar_greeting_setting'] || 'Howdy!!! Great to see you today! How can I help you?';

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
            // console.log('Chatbot: NOTICE: Avatar greeting displayed.');
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

        let user_id = kchat_settings.user_id;
        let page_id = kchat_settings.page_id;
        let session_id = kchat_settings.session_id;
        let assistant_id = kchat_settings.assistant_id;
        let thread_id = kchat_settings.thread_id;
        let chatbot_chatgpt_force_page_reload = kchat_settings.chatbot_chatgpt_force_page_reload || 'No';

        isFirstTime = !localStorage.getItem('chatbot_chatgpt_opened') || false;

        // Remove any legacy conversations that might be store in local storage for increased privacy - Ver 1.4.2
        localStorage.removeItem('chatbot_chatgpt_conversation');

        // console.log('Chatbot: NOTICE: isFirstTime: ' + isFirstTime);

        if (isFirstTime) {

            // Explicitly check for null to determine if a value exists for the key
            let storedGreeting = kchat_settings['chatbot_chatgpt_initial_greeting'];
            // initialGreeting = storedGreeting !== null ? storedGreeting : 'Hello! How can I help you today?';
            if (storedGreeting != null) {
                initialGreeting = storedGreeting;
                // console.log('Chatbot: NOTICE: chatbot-chatgpt.js - Greeting: was not null: ' + storedGreeting);
                // console.log('Chatbot: NOTICE: chatbot-chatgpt.js - Greeting: ' + initialGreeting);
            } else {
                initialGreeting = 'Hello again! How can I help you?';
                // console.log('Chatbot: NOTICE: chatbot-chatgpt.js - Greeting: ' + initialGreeting);
            }

            if (conversation.text().includes(initialGreeting)) {
                return;
            }

            lastMessage = conversation.children().last().text();

            if (lastMessage === subsequentGreeting) {
                return;
            }

            appendMessage(initialGreeting, 'bot', 'initial-greeting');
            localStorage.setItem('chatbot_chatgpt_opened', 'true');
            sessionStorage.setItem('chatbot_chatgpt_conversation' + '_' + assistant_id, conversation.html());         

        } else {

            let storedGreeting = kchat_settings['chatbot_chatgpt_subsequent_greeting'];
            // initialGreeting = storedGreeting !== null ? storedGreeting : 'Hello again! How can I help you?';
            if (storedGreeting != null) {
                initialGreeting = storedGreeting;
                // console.log('Chatbot: NOTICE: chatbot-chatgpt.js - Greeting: was not null: ' + storedGreeting);
                // console.log('Chatbot: NOTICE: chatbot-chatgpt.js - Greeting: ' + initialGreeting);
            } else {
                initialGreeting = 'Hello again! How can I help you?';
                // console.log('Chatbot: NOTICE: chatbot-chatgpt.js - Greeting: ' + initialGreeting);
            }

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
        chatbot_chatgpt_Elements.append(chatbotHeader);

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

        let user_id = kchat_settings.user_id;
        let page_id = kchat_settings.page_id;
        let session_id = kchat_settings.session_id;
        let assistant_id = kchat_settings.assistant_id;
        let thread_id = kchat_settings.thread_id;
        let chatbot_chatgpt_force_page_reload = kchat_settings.chatbot_chatgpt_force_page_reload || 'No';

        // Check if the message starts with "Error" or "Oops" - Ver 2.0.3
        const defaultCustomErrorMessage = 'Your custom error message goes here.';
        let customErrorMessage = kchat_settings['chatbot_chatgpt_custom_error_message'] || 'Your custom error message goes here.';
    
        if (typeof message !== 'undefined' && message !== null) {
            if (message.startsWith('Error')) {
                logErrorToServer(message);  // Log the error to the server
        
                if (customErrorMessage && customErrorMessage !== defaultCustomErrorMessage) {
                    message = customErrorMessage;  // Replace the message with the value from local storage
                }
            } else if (message.startsWith('Oops')) {
                if (customErrorMessage && customErrorMessage !== defaultCustomErrorMessage) {
                    logErrorToServer(message);  // Log the error to the server
                    return;  // Return to prevent further processing of the error message
                }
            }
        } else {
            // console.error('Chatbot: ERROR: Received undefined or null message:', message);
            return;  // Optionally, return early if the message is undefined or null
        }

        messageElement = $('<div></div>').addClass('chat-message');

        // Convert HTML entities back to their original form
        let decodedMessage = $('<textarea/>').html(DOMPurify.sanitize(message)).text();

        // Check if the message contains an audio tag
        if (decodedMessage.includes('<audio')) {
            // Add the autoplay attribute to the audio tag
            decodedMessage = decodedMessage.replace('<audio', '<audio autoplay');
        }

        // Parse the HTML string
        let parsedHtml = $.parseHTML(decodedMessage);

        // Assuming parsedHtml is the variable containing the parsed HTML elements
        $(parsedHtml).find('a').addBack('a').attr('target', '_blank');

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
            textElement.addClass('chatbot-user-text');
        // } else if (sender === 'bot') {
        //     messageElement.addClass('bot-message');
        //     textElement.addClass('chatbot-bot-text');
        // } else {
        //     messageElement.addClass('error-message');
        //     textElement.addClass('error-text');
        } else {
            messageElement.addClass('bot-message');
            textElement.addClass('chatbot-bot-text');
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
        // if message starts with "Conversation cleared" then clear the conversation - Ver 1.9.3
        if (message.startsWith('Conversation cleared')) {
            // Clear the conversation from sessionStorage
            // console.log('Chatbot: NOTICE: Clearing the conversation');
            sessionStorage.removeItem('chatbot_chatgpt_conversation' + '_' + assistant_id);
        } else {
            // console.log('Chatbot: NOTICE: Saving the conversation');
            sessionStorage.setItem('chatbot_chatgpt_conversation' + '_' + assistant_id, conversation.html());
        }

        // MathJax rendering - Ver 2.1.2 - 2024 08 29
        MathJax.typesetPromise([messageElement[0]])
        .then(() => {
            // console.log("MathJax rendering complete");
        })
        .catch((err) => console.log("MathJax rendering failed: ", err));

    }

    function showTypingIndicator() {
        typingIndicator = $('<div></div>').addClass('chatbot-typing-indicator');
        dot1 = $('<span>.</span>').addClass('chatbot-typing-dot');
        dot2 = $('<span>.</span>').addClass('chatbot-typing-dot');
        dot3 = $('<span>.</span>').addClass('chatbot-typing-dot');
        
        typingIndicator.append(dot1, dot2, dot3);
        conversation.append(typingIndicator);
        if (conversation && conversation[0]) {
            conversation.scrollTop(conversation[0].scrollHeight);
        }
    }

    function removeTypingIndicator() {
        $('.chatbot-typing-indicator').remove();
    }

    // markdownToHtml - Ver 1.9.2 - Updated in Ver 2.1.4
    function markdownToHtml(markdown) {
        // Step 1: Process links before any other inline elements
        markdown = markdown.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank">$1</a>');
        
        // Step 2: Extract predefined HTML tags
        const predefinedHtmlRegex = /<.*?>/g;
        let predefinedHtml = [];
        markdown = markdown.replace(predefinedHtmlRegex, (match) => {
            predefinedHtml.push(match);
            return `{{HTML_TAG_${predefinedHtml.length - 1}}}`;
        });
    
        // Step 3: Escape HTML outside of code blocks
        markdown = markdown.split(/(```[\s\S]+?```)/g).map((chunk, index) => {
            return index % 2 === 0 ? chunk.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;") : chunk;
        }).join('');
    
        // Step 4: Process images
        markdown = markdown.replace(/\!\[(.*?)\]\((.*?)\)/g, `<img alt="$1" src="$2">`);
    
        // Step 5: Headers
        markdown = markdown.replace(/^#### (.*)$/gim, '<h4>$1</h4>')
                           .replace(/^### (.*)$/gim, '<h3>$1</h3>')
                           .replace(/^## (.*)$/gim, '<h2>$1</h2>')
                           .replace(/^# (.*)$/gim, '<h1>$1</h1>');
    
        // Step 6: Process tables as a block and wrap the entire table in <pre>
        markdown = markdown.replace(/(\|.+?\|)\n(\|[-:|]+\|)\n([\s\S]+?)\n(?=\n|$)/gm, (match, header, separator, body) => {
            const headersArray = header.split('|').map(h => h.trim()).filter(h => h !== '');
            const rowsArray = body.trim().split('\n').map(row => row.split('|').map(cell => cell.trim()).filter(cell => cell !== ''));
    
            // Ensure all rows have the correct number of columns
            rowsArray.forEach(row => {
                while (row.length < headersArray.length) {
                    row.push(''); // Add empty cells to match header length
                }
                if (row.length > headersArray.length) {
                    row.splice(headersArray.length); // Remove any extra cells
                }
            });
    
            // Calculate column widths
            const colWidths = headersArray.map((header, i) => {
                return Math.max(
                    header.length,
                    ...rowsArray.map(row => (row[i] || '').length)
                );
            });
    
            // Build the table with consistent column widths
            const headerRow = '| ' + headersArray.map((header, i) => header.padEnd(colWidths[i])).join(' | ') + ' |';
            const separatorRow = '|-' + colWidths.map(width => '-'.repeat(width)).join('-|-') + '-|';
            const rows = rowsArray.map(row => {
                return '| ' + row.map((cell, i) => (cell || '').padEnd(colWidths[i])).join(' | ') + ' |';
            }).join('\n');
    
            // Wrap the entire table in <pre> and return it
            return `<pre style="font-family: monospace;">${headerRow}\n${separatorRow}\n${rows}</pre>`;
        });
    
        // Step 7: Bold, Italic, Strikethrough
        markdown = markdown.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                           .replace(/\*(.*?)\*/g, '<em>$1</em>')
                           .replace(/\~\~(.*?)\~\~/g, '<del>$1</del>');
    
        // Step 8: Multi-line code blocks
        markdown = markdown.replace(/```([\s\S]*?)```/gm, '<pre><code>$1</code></pre>');
    
        // Step 9: Inline code - after handling multi-line to prevent conflicts
        markdown = markdown.replace(/`([^`]+)`/g, '<code>$1</code>');
    
        // Step 10: Lists - Needs refining for nested lists
        markdown = markdown.replace(/^\*\s(.+)$/gim, '<li>$1</li>')
                           .replace(/<\/li><li>/g, '</li>\n<li>')
                           .replace(/<li>(.*?)<\/li>/gs, '<ul>$&</ul>')
                           .replace(/<ul>\s*<li>/g, '<ul>\n<li>')
                           .replace(/<\/li>\s*<\/ul>/g, '</li>\n</ul>');
    
        // Step 11: Improved blockquote handling
        markdown = markdown.replace(/^(>+\s?)(.*)$/gm, (match, p1, p2) => {
            return `<blockquote>${p2}</blockquote>`;
        });
    
        // Step 12: Convert line breaks to <br>, except for code blocks and blockquotes
        // markdown = markdown.split(/(<pre><code>[\s\S]*?<\/code><\/pre>|<blockquote>[\s\S]*?<\/blockquote>|<\/?h[1-6]>)/g).map((chunk, index) => {
        //     // Only convert newlines to <br> outside of code blocks, blockquotes, and handle header tags
        //     if (index % 2 === 0) {
        //         // Convert \n to <br>, then remove any double <br> tags
        //         return chunk.replace(/\n/g, '<br>').replace(/(<br>\s*){2,}/g, '<br>');
        //     } else {
        //         return chunk;
        //     }
        // }).join('');
    
        // REVERTED TO VER 2.1.3 - 2024 09 02
        // FIXME - LOOK AT USING <p> TAGS INSTEAD OF <BR> TAGS
        markdown = markdown.split(/(<pre><code>[\s\S]*?<\/code><\/pre>|<blockquote>[\s\S]*?<\/blockquote>)/g).map((chunk, index) => {
            // Only convert newlines to <br> outside of code blocks and blockquotes
            return index % 2 === 0 ? chunk.replace(/\n/g, '<br>') : chunk;
        }).join('');
    
        // Step 13: Reinsert predefined HTML tags
        markdown = markdown.replace(/{{HTML_TAG_(\d+)}}/g, (match, index) => {
            return predefinedHtml[parseInt(index)];
        });
    
        return `<div>${markdown.trim()}</div>`;
    }

    // Submit the message when the submit button is clicked
    submitButton.on('click', function () {

        // Sanitize the input - Ver 2.0.0
        message = sanitizeInput(messageInput.val().trim());
        // console.log('Chatbot: NOTICE: submitButton.on Message: ' + message);

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

        let user_id = kchat_settings.user_id;
        let page_id = kchat_settings.page_id;
        let session_id = kchat_settings.session_id;
        let assistant_id = kchat_settings.assistant_id;
        let thread_id = kchat_settings.thread_id;
        let chatbot_chatgpt_force_page_reload = kchat_settings['chatbot_chatgpt_force_page_reload'] || 'No';

        // console.log('Chatbot: NOTICE: user_id: ' + user_id);
        // console.log('Chatbot: NOTICE: page_id: ' + page_id);
        // console.log('Chatbot: NOTICE: message: ' + message);

        $.ajax({
            url: kchat_settings.ajax_url,
            method: 'POST',
            timeout: timeout_setting, // Example: 10,000ms = 10 seconds
            data: {
                action: 'chatbot_chatgpt_send_message',
                message: message,
                user_id: user_id, // pass the user ID here
                page_id: page_id, // pass the page ID here
                session_id: session_id, // pass the session ID here
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
                if (kchat_settings.chatbot_chatgpt_disclaimer_setting === 'No') {
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
                // console.log('Chatbot: NOTICE: botResponse: ' + botResponse);
                botResponse = markdownToHtml(botResponse);
            },
            error: function (jqXHR, status, error) {
                if(status === "timeout") {
                    // appendMessage('Error: ' + error, 'error');
                    // console.log('Chatbot: ERROR: ' + error);
                    appendMessage('Oops! This request timed out. Please try again.', 'error');
                    botResponse = '';
                } else {
                    // appendMessage('Error: ' + error, 'error')
                    // console.log('Chatbot: ERROR: ' + error);
                    appendMessage('Oops! Something went wrong on our end. Please try again later.', 'error');
                    botResponse = '';
                }
            },
            complete: function () {
                removeTypingIndicator();
                if (botResponse) {
                    appendMessage(botResponse, 'bot');
                    // FIXME - Add custom JS to the bot's response - Ver 2.0.9
                    // Append custom JS to the bot's response - Ver 2.0.9
                    if (typeof appendCustomJsToBotResponse === 'function') {
                        let customMessage = '';
                        customMessage = appendCustomJsToBotResponse(botResponse);
                        // Check if customMessage is not null, undefined, or an empty string
                        if (customMessage) {
                            appendMessage(customMessage, 'bot');
                        }
                    };
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
            $('#chatbot-chatgpt-upload-file-input').trigger('click');
            let button = $(this);  // Store a reference to the button
            setTimeout(function() {
                button.trigger('blur');  // Remove focus from the button
            }, 0);
        }
    });

    // Add the keydown event listener to the upload mp3 button - Ver 2.0.1
    $('#chatbot-chatgpt-upload-mp3').on('keydown', function(e) {
        if (e.keyCode === 13  && !e.shiftKey) {
            e.preventDefault();
            // console.log('Chatbot: NOTICE: Enter key pressed on upload mp3 button');
            let $response = chatbot_chatgpt_upload_mp3();
            $('#chatbot-chatgpt-upload-mp3-input').trigger('click');
            let button = $(this);  // Store a reference to the button
            setTimeout(function() {
                button.trigger('blur');  // Remove focus from the button
            }, 0);
        }
    });

    // Add the click event listener to the download transcript button - Ver 1.9.9
    $('#chatbot-chatgpt-download-transcript-btn').on('click', function(e) {
        e.preventDefault();  // Prevent the default action of the button (if needed)
        // console.log('Chatbot: NOTICE: Button clicked: Downloading transcript');  // Optional: Log to console
    
        let conversationContent = $('#chatbot-chatgpt-conversation').html();  // Get the HTML content
        let button = $(this);  // Store a reference to the button
    
        $.ajax({
            url: kchat_settings.ajax_url,
            method: 'POST',
            data: {
                action: 'chatbot_chatgpt_download_transcript',
                user_id: kchat_settings.user_id,
                page_id: kchat_settings.page_id,
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
                    // let link = document.createElement('a');
                    // link.href = response.data;
                    // link.download = ''; // Optionally set the filename
                    // document.body.appendChild(link);
                    // // Refactored to use MouseEvent - Ver 2.0.5 - 2024 07 06
                    // // link.click();
                    // link.dispatchEvent(new MouseEvent('click')); // Use MouseEvent to simulate click
                    // document.body.removeChild(link);

                    let link = document.createElement('a');
                    link.href = sanitizeUrl(response.data); // Sanitize the URL
                    link.download = ''; // Optionally set the filename
                    document.body.appendChild(link);
                    // Refactored to use MouseEvent - Ver 2.0.5 - 2024 07 06
                    // link.click();
                    link.dispatchEvent(new MouseEvent('click')); // Use MouseEvent to simulate click
                    document.body.removeChild(link);

                } else {
                    // console.error('Chatbot: ERROR: Download URL not provided or error in response.');
                    // console.error(response.data || 'No additional error data.');
                    appendMessage('Oops! There was a problem downloading the transcript. Please try again later.', 'error');
                }
            },
            error: function(jqXHR, status, error) {
                // Handle AJAX errors
                appendMessage('Error: ' + error, 'error');
                appendMessage('Oops! There was a problem downloading the transcript. Please try again later.', 'error');
            },
            complete: function () {
                // Remove typing indicator and enable submit button
                // Replace these functions with your own
                removeTypingIndicator();
                $('#submit-button').prop('disabled', false);
                button.trigger('blur');  // Remove focus from the button
            },
        });
    });

    function sanitizeUrl(url) {
        // A simple implementation could check for a valid protocol (http or https)
        // This is a basic example and might need to be expanded based on your requirements
        const urlPattern = /^https?:\/\/[^ "]+$/;
        return urlPattern.test(url) ? url : 'about:blank'; // Use 'about:blank' if the URL is invalid
    }

    // Read Out Loud - Ver 1.9.5
    $('#chatbot-chatgpt-text-to-speech-btn').on('click', function(e) {

        // console.log('Chatbot: NOTICE: Text-to-Speech button clicked');

        // Read out loud the last bot response
        let lastMessage = $('#chatbot-chatgpt-conversation .bot-message:last .chatbot-bot-text').text();
        let button = $(this);  // Store a reference to the button

        // console.log('Chatbot: NOTICE: lastMessage: ' + lastMessage);

        // Check if the bot response is empty
        if (!lastMessage) {
            appendMessage('Oops! There is no response to read out loud.', 'error');
            return;
        }

        // Call function "chatbot_chatgpt_call_tts_api" to convert the text to speech
        $.ajax({
            url: kchat_settings.ajax_url,
            method: 'POST',
            timeout: timeout_setting, // Example timeout_setting value: 10000 for 10 seconds
            data: {
                action: 'chatbot_chatgpt_read_aloud',
                message: lastMessage,
                voice: kchat_settings.chatbot_chatgpt_voice_option || 'alloy',
                user_id: kchat_settings.user_id,
                page_id: kchat_settings.page_id,
                session_id: kchat_settings.session_id,
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
                button.trigger('blur');  // Remove focus from the button
            },
        });
    });

    // Get a microphone icon for the chatbot
    const micIcon = $('<img>')
        .attr('id', 'chatbot-mic-icon')
        .attr('class', 'chatbot-mic-icon')
        .attr('src', plugins_url + 'assets/icons/' + 'mic_24dp_000000_FILL0_wght400_GRAD0_opsz24.png');

    // Get a microphone slash icon for the chatbot
    const micSlashIcon = $('<img>')
        .attr('id', 'chatbot-mic-slash-icon')
        .attr('class', 'chatbot-mic-icon')
        .attr('src', plugins_url + 'assets/icons/' + 'mic_off_24dp_000000_FILL0_wght400_GRAD0_opsz24.png');

    // Add the initial icon (microphone on) to the button
    $('#chatbot-chatgpt-speech-recognition-btn').html(micIcon);

    // Flag to track the recognition state
    let isRecognizing = false;  // Track if recognition is active
    let isManuallyStopping = false;  // Track if recognition is being manually stopped
    let recognition;  // Declare recognition globally

    // Add the initial icon (microphone on) to the button
    $('#chatbot-chatgpt-speech-recognition-btn').on('click', function (e) {

        console.log('Running version 2.0.0 of the speech recognition feature.');

        e.preventDefault();  // Prevent default action if necessary

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (SpeechRecognition) {

            // Initialize recognition only if it's not already active
            if (!isRecognizing) {

                // Clear any existing recognition instance
                if (recognition) {
                    recognition.removeEventListener('result', handleRecognitionResult);  // Clear event listener
                    recognition.abort();  // Stop any existing instance
                }

                // Create a new SpeechRecognition instance
                recognition = new SpeechRecognition();
                recognition.lang = 'en-US';
                recognition.interimResults = false;
                recognition.maxAlternatives = 1;
                recognition.continuous = false;  // Disable continuous listening

                console.log('Starting speech recognition...');

                recognition.start();
                isRecognizing = true;  // Update flag

                // Switch to the "microphone off" icon
                $('#chatbot-chatgpt-speech-recognition-btn').html(micSlashIcon);

                // Attach the result event handler
                recognition.addEventListener('result', handleRecognitionResult);

                // Handle errors
                recognition.addEventListener('error', (event) => {
                    console.error('Speech Recognition Error:', event.error);

                    if (event.error === 'no-speech') {
                        console.log('No speech detected, stopping recognition...');
                        recognition.stop();  // Stop recognition when no speech is detected
                    } else {
                        alert("Speech recognition error: " + event.error);
                    }
                    resetRecognition();  // Stop recognition on error
                });

                // Handle recognition end
                recognition.addEventListener('end', () => {
                    console.log('Speech recognition ended.');
                    isRecognizing = false;

                    // Restart the recognition only if it wasn't manually stopped
                    if (!isManuallyStopping) {
                        setTimeout(() => {
                            if (!isRecognizing) {  // Check to prevent multiple starts
                                console.log('Restarting speech recognition...');
                                recognition.start();  // Restart recognition if it was not manually stopped
                            }
                        }, 1000);  // Adjust delay as needed
                    } else {
                        console.log('Manual stop detected, not restarting recognition.');
                        isManuallyStopping = false;  // Reset the manual stop flag
                    }
                });

            } else {
                // If recognition is already active, stop it when the button is clicked again
                isManuallyStopping = true;  // Indicate that we're stopping manually
                recognition.stop();  // Stop the recognition
                resetRecognition();  // Reset the recognition state and icon
                console.log('Speech recognition stopped manually.');
            }

        } else {
            alert('Speech Recognition API not supported in this browser.');
        }

    });

    // Separate function to handle recognition results
    function handleRecognitionResult(event) {
        const transcript = event.results[0][0].transcript;
        console.log('Speech recognized:', transcript);
        $('#chatbot-chatgpt-message').val(transcript);
        sendToChatbot(transcript);  // Send the recognized speech to the chatbot

        // After sending transcript to chatbot, reset the recognition state and icon
        console.log('Resetting recognition state and icon...');
        resetRecognition();

        // Manually stop recognition and restart after a slight delay
        setTimeout(() => {
            if (!isManuallyStopping) {  // Prevent restarts during manual stops
                recognition.stop();  // Ensure the recognition is stopped
            }
        }, 500);
    }

    // Function to reset recognition state and icon
    function resetRecognition() {
        isRecognizing = false;
        // Switch back to the "microphone on" icon
        $('#chatbot-chatgpt-speech-recognition-btn').html(micIcon);
    }

    // Function to send recognized speech text to chatbot input - V2.1.5.1
    function sendToChatbot(message) {

        // console.log("Sending message to chatbot:", message);
    
        // Update the input field with the recognized speech
        $('#chatbot-chatgpt-message').val(message);
    
        // Ensure that the value is updated before trying to submit
        let updatedMessage = $('#chatbot-chatgpt-message').val().trim();
    
        // console.log("Updated message in input field:", updatedMessage);
    
        if (updatedMessage) {

            // Trigger the submit button's click event programmatically
            $('#chatbot-chatgpt-submit').trigger('click');

        } else {

            // console.error("Message is empty, cannot submit.");

        }
    }

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
                // console.log('Chatbot: NOTICE: Disallowed file type or extension: ' + file.name);
                hasDisallowedFile = true;
                appendMessage('Oops! Unsupported file type. Please try again.', 'error');
                break;
            }
        }

        if (hasDisallowedFile) {
            return;
        }
        // console.log('Chatbot: NOTICE: Files selected ' + fileField.files);
        formData.append('action', 'chatbot_chatgpt_upload_files');
    
        $.ajax({
            url: kchat_settings.ajax_url,
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
                // console.error('Chatbot: NOTICE: Response from server', response);
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
            // console.warn('Chatbot: WARNING: No file selected');
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
                // console.log('Chatbot: NOTICE: Disallowed file type or extension: ' + file.name);
                hasDisallowedFile = true;
                appendMessage('Oops! Unsupported file type. Please try again.', 'error');
                break;
            }
        }

        if (hasDisallowedFile) {
            return;

        }
        // console.log('Chatbot: NOTICE: Files selected ' + fileField.files);
        formData.append('action', 'chatbot_chatgpt_upload_mp3');
    
        $.ajax({
            url: kchat_settings.ajax_url,
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
                    // console.error('Chatbot: ERROR: ' + JSON.stringify(response));
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

        let user_id = kchat_settings.user_id;
        let page_id = kchat_settings.page_id;
        let session_id = kchat_settings.session_id;
        let assistant_id = kchat_settings.assistant_id;
        let thread_id = kchat_settings.thread_id;
        let chatbot_chatgpt_force_page_reload = kchat_settings['chatbot_chatgpt_force_page_reload'] || 'No';

        // DIAG - Diagnostics - Ver 1.9.1
        // console.log('Chatbot: NOTICE: assistant_id: ' + assistant_id);
    
        $.ajax({
            url: kchat_settings.ajax_url,
            method: 'POST',
            timeout: timeout_setting, // Example: 10,000ms = 10 seconds
            data: {
                action: 'chatbot_chatgpt_erase_conversation', // The action to be handled on the server-side
                user_id: user_id, // pass the user ID here
                page_id: page_id, // pass the page ID here
                session_id: session_id, // pass the session
                thread_id: thread_id, // pass the thread ID
                assistant_id: assistant_id, // pass the assistant ID
                chatbot_chatgpt_force_page_reload: chatbot_chatgpt_force_page_reload, // pass the force page reload setting
            },
            beforeSend: function () {
                showTypingIndicator();
                submitButton.prop('disabled', true);
            },
            success: function(response) {
                sessionStorage.removeItem('chatbot_chatgpt_conversation' + '_' + assistant_id); // Clear the last response from sessionStorage
                // DIAG - Log the response
                // console.log('Chatbot: NOTICE: Removing conversation from sessionStorage');
                // console.log('Chatbot: SUCCESS:', response.data);
                appendMessage( response.data, 'bot');
                // Check localStorage setting and force a page reload if equal to 'Yes' - Ver 2.0.4
                if (kchat_settings.chatbot_chatgpt_force_page_reload === 'Yes') {
                    location.reload(); // Force a page reload after clearing the conversation
                }
            },
            error: function(jqXHR, status, error) {
                if(status === "timeout") {
                    appendMessage('Error: ' + error, 'error');
                    appendMessage('Oops! This request timed out. Please try again.', 'error');
                } else {
                    // DIAG - Log the error - Ver 1.6.7
                    // console.error('Chatbot: ERROR: ' + JSON.stringify(response));
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
    
    // Toggle the chatbot visibility
    function toggleChatbot() {
        if (chatbot_chatgpt_Elements.is(':visible')) {
            chatbot_chatgpt_Elements.hide();
            chatGptOpenButton.show();
            localStorage.setItem('chatbot_chatgpt_start_status', 'closed');
        } else {
            if (chatbot_chatgpt_display_style === 'floating') {
                if (chatbot_chatgpt_width_setting === 'Wide') {
                    $('#chatbot-chatgpt').removeClass('chatbot-narrow chatbot-full').addClass('chatbot-wide');
                } else {
                    $('#chatbot-chatgpt').removeClass('chatbot-wide chatbot-full').addClass('chatbot-narrow');
                }
                chatbot_chatgpt_Elements.show();
                chatGptOpenButton.hide();
            } else {
                $('#chatbot-chatgpt').removeClass('chatbot-wide chatbot-narrow').addClass('chatbot-full');
            }
            chatbot_chatgpt_Elements.show();
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
            // chatbot_chatgpt_start_status = 'closed';
            // chatbot_chatgpt_start_status_new_visitor = 'closed';
        }

        // Nuclear option to clear session conversation - Ver 1.5.0
        // Do not use unless absolutely needed
        // DIAG - Diagnostics - Ver 1.5.0
        // nuclearOption = 'Off';
        // if (nuclearOption === 'On') {
        //     console.log('Chatbot: NOTICE: ***** NUCLEAR OPTION IS ON ***** ');
        //     sessionStorage.removeItem('chatbot_chatgpt_conversation' + '_' + assistant_id);
        //     // Removed in Ver 1.6.1
        //     sessionStorage.removeItem('chatgpt_last_response');
        // }

        // DIAG - Diagnostics - Ver 1.5.0
        // if (kchat_settings.chatbot_chatgpt_diagnostics === 'On') {
        //     console.log('Chatbot: NOTICE: loadChatbotStatus - BEFORE DECISION');
        // }

        // Decide what to do for a new visitor - Ver 1.5.0
        // if (kchat_settings.chatbot_chatgpt_start_status_new_visitor === 'open') {
        //     if (chatbot_chatgpt_start_status_new_visitor === null) {
        //         // Override initial status
        //         chatbot_chatgpt_start_status = 'open';
        //         chatbot_chatgpt_start_status_new_visitor = 'closed';
        //         localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', 'closed');
        //     } else {
        //         // Override initial status
        //         chatbot_chatgpt_start_status_new_visitor = 'closed';
        //         localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', 'closed');
        //     }
        // }

        // REPLACED ABOVE IN Ver 2.0.9
        // if (chatbot_chatgpt_start_status_new_visitor != 'open' && chatbot_chatgpt_start_status_new_visitor != 'closed') {
        //     if (chatbot_chatgpt_start_status_new_visitor === null) {
        //         // Override initial status
        //         chatbot_chatgpt_start_status = 'open';
        //         chatbot_chatgpt_start_status_new_visitor = 'closed';
        //         localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', 'closed');
        //     } else {
        //         // Override initial status
        //         chatbot_chatgpt_start_status_new_visitor = 'closed';
        //         localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', 'closed');
        //     }
        // }

        // Just check for null value - Ver 2.1.3 - 2024 08 30
        if (chatbot_chatgpt_start_status_new_visitor === null) {
            chatbot_chatgpt_start_status = 'closed';
            localStorage.setItem('chatbot_chatgpt_start_status', 'closed');
            chatbot_chatgpt_start_status_new_visitor = 'closed';
            localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', 'closed');
        }

        // DIAG - Diagnostics - Ver 1.5.0
        // if (kchat_settings.chatbot_chatgpt_diagnostics === 'On') {
        //     console.log('Chatbot: NOTICE: loadChatbotStatus - AFTER DECISION');
        // }
        
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_start_status: ' + chatbot_chatgpt_start_status);
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_start_status_new_visitor: ' + chatbot_chatgpt_start_status_new_visitor);
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_display_style: ' + chatbot_chatgpt_display_style);
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_width_setting: ' + chatbot_chatgpt_width_setting);
        
        // If the chatbot status is not set in local storage, use chatbot_chatgpt_start_status - Ver 1.5.1
        if (chatbot_chatgpt_start_status === 'closed') {
            chatbot_chatgpt_Elements.hide();
            chatGptOpenButton.show();
        } else {
            chatbot_chatgpt_Elements.show();
            chatGptOpenButton.hide();
            // Load the conversation if the chatbot is open on page load
            // Removed in Ver 1.9.3
            loadConversation();
            scrollToBottom();
        }

    }

    // Add this function to scroll to the bottom of the conversation - Ver 1.2.1 - Revised in Ver 2.0.7
    function scrollToBottom() {

        // setTimeout(() => {
        //     // DIAG - Diagnostics - Ver 1.5.0
        //     // if (kchat_settings.chatbot_chatgpt_diagnostics === 'On') {
        //     //     console.log('Chatbot: NOTICE: scrollToBottom");
        //     // }
        //     if (conversation && conversation.length > 0) {
        //         conversation.scrollTop(conversation[0].scrollHeight);
        //     }
        // }, 100);  // delay of 100 milliseconds
        //

        // Call the function to scroll to the bottom of the conversation - Ver 2.0.7
        scrollToLastBotResponse();

    }

    // Add this function to scroll to the top of the last chatbot response - Ver 2.0.3
    function scrollToLastBotResponse() {

        setTimeout(() => {
            
            // DIAG - Diagnostics - Ver 2.0.3
            // if (kchat_settings.chatbot_chatgpt_diagnostics === 'On') {
            //    console.log('Chatbot: NOTICE: scrollToLastBotResponse');
            // }
    
            const botTexts = document.querySelectorAll('.chatbot-bot-text');
            const conversation = document.querySelector('#chatbot-chatgpt-conversation');
    
            // DIAG - Diagnostics - Ver 2.0.3
            // if (kchat_settings.chatbot_chatgpt_diagnostics === 'On') {
            //     console.log('Chatbot: NOTICE: Bot Texts:', botTexts);
            //     console.log('Chatbot: NOTICE: Conversation:', conversation);
            // }
    
            if (botTexts && botTexts.length > 0 && conversation) {
                const lastBotText = botTexts[botTexts.length - 1];
                const topPosition = lastBotText.offsetTop - conversation.offsetTop;

                // DIAG - Diagnostics - Ver 2.0.3
                // if (kchat_settings.chatbot_chatgpt_diagnostics === 'On') {
                //     console.log('Chatbot: NOTICE: Last Bot Text:', lastBotText);
                //     console.log('Chatbot: NOTICE: Last Bot Text OffsetTop:', lastBotText.offsetTop);
                //     console.log('Chatbot: NOTICE: Conversation OffsetTop:', conversation.offsetTop);
                //     console.log('Chatbot: NOTICE: Top Position:', topPosition);
                // }
    
                // Scroll to the top of the last bot message
                conversation.scrollTop = topPosition;

            } else {

                // DIAG - Diagnostics - Ver 2.0.3
                // if (kchat_settings.chatbot_chatgpt_diagnostics === 'On') {
                // console.log('Chatbot: NOTICE: No bot texts found or conversation container is missing.');
                // }

            }

        }, 200); // Adjust the delay as needed
    }    
   
    // Load conversation from local storage if available - Ver 1.2.0 - Revised in Ver 2.0.7
    function loadConversation() {

        let user_id = kchat_settings.user_id;
        let page_id = kchat_settings.page_id;
        let session_id = kchat_settings.session_id;
        let assistant_id = kchat_settings.assistant_id;
        let thread_id = kchat_settings.thread_id;
        let chatbot_chatgpt_force_page_reload = kchat_settings.chatbot_chatgpt_force_page_reload || 'No';

        // Removed in Ver 1.9.3
        // storedConversation = sessionStorage.getItem('chatbot_chatgpt_conversation' + '_' + assistant_id);
        // Reset the conversation - Added in Ver 1.9.3
        let storedConversation = '';
        let sanitizedConversation = '';
        localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', 'closed');

        // If conversation_continuation is enabled, load the conversation from local storage - Ver 2.0.7
        if (kchat_settings.chatbot_chatgpt_conversation_continuation === 'On') {
            storedConversation = sessionStorage.getItem('chatbot_chatgpt_conversation' + '_' + assistant_id);
            
            // Check if storedConversation is not null before trying to replace
            if (storedConversation) {
                // remove autoplay attribute from the audio elements - Ver 2.0.7
                // console.log('Chatbot: NOTICE: loadConversation - storedConversation: ' + storedConversation);
                storedConversation = storedConversation.replace(/autoplay/g, '');
                // console.log('Chatbot: NOTICE: loadConversation - storedConversation: ' + storedConversation);
                // console.warn('Chatbot: WARNING: Conversation found in session storage.');
            } else {
                // console.warn('Chatbot: WARNING: No conversation found in session storage.');
            }
        }

        if (storedConversation) {
 
            // console.log('Chatbot: NOTICE: loadConversation - IN THE IF STATEMENT');
 
            // Check if current conversation is different from stored conversation
            // if (conversation.html() !== storedConversation) {
            //     conversation.html(storedConversation);  // Set the conversation HTML to stored conversation
            // }
            // console.log ('Chatbot: NOTICE: storedConversation: ' + storedConversation);
            if (conversation.html() !== storedConversation) {
                let sanitizedConversation = DOMPurify.sanitize(storedConversation);
                conversation.html(sanitizedConversation);  // Set the conversation HTML to sanitized stored conversation
            }
            // console.log ('Chatbot: NOTICE: sanitizedConversation: ' + sanitizedConversation);

            // Use setTimeout to ensure scrollToBottom is called after the conversation is rendered
            setTimeout(scrollToBottom, 0);
            removeTypingIndicator();

            // Re-render MathJax for any formulas in the stored conversation - Ver 2.1.2 - 2024 08 29
            MathJax.typesetPromise([conversation[0]])
            .then(() => {
                // console.log("MathJax re-rendering complete for stored conversation");
            })
            .catch((err) => console.error("Chatbot: ERROR: MathJax re-rendering failed: ", err));

        } else {

            // console.log('Chatbot: NOTICE: loadConversation - IN THE ELSE STATEMENT');
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
        // console.log('Chatbot: NOTICE: chatbot_chatgpt_display_style: ' + chatbot_chatgpt_display_style);

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

// Log error to the error log - Ver 2.0.3
function logErrorToServer(error) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', kchat_settings.ajax_url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send('action=log_chatbot_error&error_message=' + encodeURIComponent(error));
}
