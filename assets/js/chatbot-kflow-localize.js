jQuery(document).ready(function ($) {
    var kflow_data = kflow_data || {};

    Promise.all([
        localStorage.setItem('chatbot_kflow_enabled', kflow_data.kflow_enabled),
        localStorage.setItem('chatbot_kflow_sequence', kflow_data.kflow_sequence),
        localStorage.setItem('chatbot_kflow_prompts', kflow_data.kflow_prompts),
        localStorage.setItem('chatbot_kflow_steps', kflow_data.kflow_steps),
        localStorage.setItem('chatbot_kflow_template', kflow_data.kflow_template)
    ]).then(function() {
        // All items are set
    });
});