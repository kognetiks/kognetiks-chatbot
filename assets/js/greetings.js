jQuery(document).ready(function ($) {

    // DIAG - Diagnostics - Ver 2.0.5
    // console.log('Chatbot: NOTICE: greetings.js - ENTERING');
    // console.log('Chatbot: NOTICE: greetings.js - chatbot_chatgpt_initial_greeting: ' + localStorage.getItem('chatbot_chatgpt_initial_greeting'));
    // console.log('Chatbot: NOTICE: greetings.js - chatbot_chatgpt_subsequent_greeting: ' + localStorage.getItem('chatbot_chatgpt_subsequent_greeting'));
    
    if (typeof greetings_data !== 'undefined') {
        localStorage.setItem('chatbot_chatgpt_initial_greeting', greetings_data.initial_greeting);
        localStorage.setItem('chatbot_chatgpt_subsequent_greeting', greetings_data.subsequent_greeting);
    } else {
        // console.error('Chatbot: ERROR: greetings_data is not defined.');
    }

    // DIAG - Diagnostics - Ver 2.0.5
    // console.log('Chatbot: NOTICE: greetings.js - EXITING');
    // console.log('Chatbot: NOTICE: greetings.js - chatbot_chatgpt_initial_greeting: ' + localStorage.getItem('chatbot_chatgpt_initial_greeting'));
    // console.log('Chatbot: NOTICE: greetings.js - chatbot_chatgpt_subsequent_greeting: ' + localStorage.getItem('chatbot_chatgpt_subsequent_greeting'));
    
});