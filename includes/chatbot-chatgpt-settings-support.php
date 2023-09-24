<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Support Page
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

// Support settings section callback - Ver 1.3.0
function chatbot_chatgpt_support_section_callback($args) {
    ?>
    <div>
	<h3>Description</h3>
    <p>Chatbot ChatGPT for WordPress is a plugin that allows you to effortlessly integrate OpenAI&#8217;s ChatGPT API into your website, providing a powerful, AI-driven chatbot for enhanced user experience and personalized support.</p>
    <p>ChatGPT is a conversational AI platform that uses natural language processing and machine learning algorithms to interact with users in a human-like manner. It is designed to answer questions, provide suggestions, and engage in conversations with users. ChatGPT is important because it can provide assistance and support to people who need it, especially in situations where human support is not available or is limited. It can also be used to automate customer service, reduce response times, and improve customer satisfaction. Moreover, ChatGPT can be used in various fields such as healthcare, education, finance, and many more.</p>
    <p>Chatbot ChatGPT leverages the OpenAI platform using the gpt-3.5-turbo and gpt-4 model brings it to life within your WordPress Website.</p>
    <p><b>Important Note:</b> This plugin requires an API key from OpenAI to function correctly. You can obtain an API key by signing up at <a href="https://platform.openai.com/account/api-keys" rel="nofollow ugc" target="_blank">https://platform.openai.com/account/api-keys</a>.<p>
    <h3>Official Sites:</h3>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;"> 
    <li><a href="https://kognetiks.com/wordpress-plugins/chatbot-chatgpt/" rel="nofollow ugc" target="_blank">Kognetiks.com</a></li>
    <li><a href="https://github.com/kognetiks/chatbot-chatgpt" target="_blank">https://github.com/kognetiks/chatbot-chatgpt</a></li>
    <li><a href="https://wordpress.org/plugins/chatbot-chatgpt/" target="_blank">https://wordpress.org/plugins/chatbot-chatgpt/</a></li>
    </ul>
    <h3>Features</h3>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
    <li>Easy setup and integration with OpenAI&#8217;s ChatGPT API</li>
    <li>Support for gpt-3.5-turbo</li>
    <li>Support for gpt-4 (Learn how to access the gpt-4 API at <a href="https://help.openai.com/en/articles/7102672-how-can-i-access-gpt-4" rel="nofollow ugc" target="_blank">https://help.openai.com/en/articles/7102672-how-can-i-access-gpt-4</a></li>
    <li>Floating chatbot interface with customizable appearance</li>
    <li>User-friendly settings page for managing API key and other parameters</li>
    <li>Collapsible chatbot interface when not in use</li>
    <li>Initial greeting message for first-time users</li>
    <li>Shortcode to embed the chatbot on any page or post</li>
    <li>Setting to determine if chatbot should start opened or closed</li>
    <li>Chatbot maintains state when navigating between pages</li>
    <li>Chatbot name and initial and subsequent greetings are configurable</li>
    </ul>
    <h3>Getting Started</h3>
    <ol>
    <li>Obtain your API key by signing up at <a href="https://platform.openai.com/account/api-keys" rel="nofollow ugc" target="_blank">https://platform.openai.com/account/api-keys</a>.</li>
    <li>Install and activate the Chatbot ChatGPT plugin.</li>
    <li>Navigate to the settings page (Settings &gt; API/Model) and enter your API key.</li>
    <li>Customize the chatbot appearance and other parameters as needed.</li>
    <li>Add the chatbot to any page or post using the provided shortcode: [chatbot_chatgpt]</li>
    </ol>
    <p>Now your website visitors can enjoy a seamless and personalized chat experience powered by OpenAI&#8217;s ChatGPT API.</p>
    <h2>Installation</h2>
	<ol>
    <li>Upload the &#8216;chatbot-chatgpt&#8217; folder to the &#8216;/wp-content/plugins/&#8217; directory.</li>
    <li>Activate the plugin through the &#8216;Plugins&#8217; menu in WordPress.</li>
    <li>Go to the &#8216;Settings &gt; Chatbot ChatGPT&#8217; page and enter your OpenAI API key.</li>
    <li>Customize the chatbot appearance and other parameters as needed.</li>
    <li>Add the chatbot to any page or post using the provided shortcode: [chatbot_chatgpt]</li>
    </ol>
    <h2>API Key Safety and Security</h2>
    <p>Your API key serves as the confidential password providing access to your OpenAI account and the resources associated with it. If this key falls into the wrong hands, it can be misused in a variety of detrimental ways, including unauthorized usage, potential data leaks, and the improper application of AI models. It's crucial, therefore, to implement the following protective measures:</p>
    <ol>
    <li>Secure key storage: Ensure your API keys are stored in a safe and secure manner.</li>
    <li>Monitor and review usage: Frequently scrutinize and evaluate the usage of your API key. OpenAI provides handy usage data and records that can assist in detecting unusual activity. For insightful usage statistics, visit <a href="https://platform.openai.com/account/usage" rel="nofollow ugc" target="_blank">https://platform.openai.com/account/usage</a>.</li>
    <li>Establish usage limits: Initially, implement a low hard limit to ensure that if the limit is reached at any point during the month, any further requests will be denied. You can set up both hard and soft limits at <a href="https://platform.openai.com/account/billing/limits" rel="nofollow ugc" target="_blank">https://platform.openai.com/account/billing/limits</a>.</li>
    <li>Regular key rotation: Frequently changing your API keys can reduce the risk of misuse. If you observe any unexpected activity, it's important to immediately revoke your API keys. As a preventative measure, you might want to regularly revoke them to avert misuse. Manage your API keys at  <a href="https://platform.openai.com/account/api-keys" rel="nofollow ugc" target="_blank">https://platform.openai.com/account/api-keys</a>.</li>
    </ol>
    <p>Remember, wielding AI power requires immense responsibility — it's incumbent upon us all to ensure its careful and secure use.</p>
    </div>
    <?php
}
