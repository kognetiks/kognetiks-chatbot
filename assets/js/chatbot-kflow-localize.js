jQuery(document).ready(function ($) {
    var kflow_data = kflow_data || {};

    try {
        localStorage.setItem('chatbot_kflow_enabled', kflow_data.kflow_enabled);
        localStorage.setItem('chatbot_kflow_sequence', kflow_data.kflow_sequence);
        localStorage.setItem('chatbot_kflow_prompts', kflow_data.kflow_prompts);
        localStorage.setItem('chatbot_kflow_steps', kflow_data.kflow_steps);
        localStorage.setItem('chatbot_kflow_template', kflow_data.kflow_template);
        // All items are set
        } catch (error) {
            console.error('An error occurred:', error);
        }
});