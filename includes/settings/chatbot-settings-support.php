<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Support Page
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the support settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Support settings section callback - Ver 1.3.0
function chatbot_chatgpt_support_section_callback() {
    ?>
    <div id='overview'>
        <h3>Overview</h3>
        <p>Kognetiks Chatbot for WordPress is a plugin that allows you to effortlessly integrate OpenAI&#8217;s ChatGPT API into your website, providing a powerful, AI-driven chatbot for enhanced user experience and personalized support.</p>
        <p>Conversational AI platforms - like those from OpenAI - use natural language processing and machine learning algorithms to interact with users in a human-like manner.  They are designed to answer questions, provide suggestions, and engage in conversations with users. This is important because it can provide assistance and support to people who need it, especially in situations where human support is not available or is limited. It can also be used to automate customer service, reduce response times, and improve customer satisfaction. Moreover, these platforms can be used in various fields such as healthcare, education, finance, and many more.</p>
        <p>The Kognetiks Chatbot for WordPress is powered by OpenAI, via it's API and Models to bring artificial intelligence to life within your WordPress website.</p>
        <p><b>Important Note:</b> This plugin requires an API key from OpenAI to function correctly. You can obtain an API key by signing up at <a href="https://platform.openai.com/account/api-keys" rel="nofollow ugc" target="_blank">https://platform.openai.com/account/api-keys</a>.<p>
        <h3>Official Sites:</h3>
            <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
            <li><a href="https://kognetiks.com/wordpress-plugins/kognetiks-chatbot/" rel="nofollow ugc" target="_blank">Kognetiks.com</a></li>
            <li><a href="https://github.com/kognetiks/kognetiks-chatbot" target="_blank">https://github.com/kognetiks/kognetiks-chatbot</a></li>
            <li><a href="https://kognetiks.com/wordpress-plugins/kognetiks-chatbot/ai-powered-chatbot-for-wordpress/" target="_blank">https://wordpress.org/plugins/chatbot-chatgpt/</a></li>
        </ul>
        <h3>Support:</h3>
            <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
            <li><a href="https://discord.gg/nXRzxUKvya" rel="nofollow ugc" target="_blank">Support @ Discord</a></li>
            <li><a href="https://kognetiks.com/wordpress-plugins/plugin-support/" rel="nofollow ugc" target="_blank">Support @ Kognetiks.com</a></li>
            <li><a href="https://wordpress.org/support/plugin/chatbot-chatgpt/" target="_blank">Support @ WordPress.org</a></li>
            <li><a href="https://github.com/kognetiks/kognetiks-chatbot/issues" target="_blank">Support @ GitHub.com</a></li>
        </ul>
        <h3>Features</h3>
        <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
            <li>Easy setup and integration with OpenAI&#8217;s ChatGPT API</li>
            <li>Support for gpt-3.5-turbo</li>
            <li>Support for gpt-4 (Learn how to access the gpt-4 API at <a href="https://help.openai.com/en/articles/7102672-how-can-i-access-gpt-4" rel="nofollow ugc" target="_blank">https://help.openai.com/en/articles/7102672-how-can-i-access-gpt-4</a></li>
            <li>Support for gpt-4-turbo, OpenAI's latest model with knowledge cutoff of April 2023, learn more at <a href="https://help.openai.com/en/articles/8555510-gpt-4-turbo" rel="nofollow ugc" target="_blank">https://help.openai.com/en/articles/8555510-gpt-4-turbo</a></li>
            <li>Floating chatbot interface with customizable appearance</li>
            <li>User-friendly settings page for managing API key and other parameters</li>
            <li>Collapsible chatbot interface when not in use</li>
            <li>Initial greeting message for first-time users</li>
            <li>Shortcode to embed the chatbot on any page or post</li>
            <li>Setting to determine if chatbot should start opened or closed</li>
            <li>Chatbot maintains state when navigating between pages</li>
            <li>Chatbot name and initial and subsequent greetings are configurable</li>
        </ul>
    </div>
    <hr style="border-top: 2px solid black;">
    <div id="getting-started">
        <h3>Getting Started</h3>
        <ol>
            <li>Obtain your API key by signing up at <a href="https://platform.openai.com/account/api-keys" rel="nofollow ugc" target="_blank">https://platform.openai.com/account/api-keys</a>.</li>
            <li>Install and activate the Chatbot plugin.</li>
            <li>Navigate to the settings page (Settings &gt; API/Model) and enter your API key.</li>
            <li>Customize the chatbot appearance and other parameters as needed.</li>
        <li>Add the chatbot to any page or post using the provided shortcode: <b>&#91;chatbot_chatgpt&#93;</b></li>
        </ol>
        <p>Now your website visitors can enjoy a seamless and personalized chat experience powered by OpenAI&#8217;s ChatGPT API.</p>
        <h3>Installation</h3>
        <ol>
            <li>Upload the &#8216;chatbot-chatgpt&#8217; folder to the &#8216;/wp-content/plugins/&#8217; directory.</li>
            <li>Activate the plugin through the &#8216;Plugins&#8217; menu in WordPress.</li>
            <li>Go to the &#8216;Settings &gt; Chatbot&#8217; page and enter your OpenAI API key.</li>
            <li>Customize the chatbot appearance and other parameters as needed.</li>
            <li>Add the chatbot to any page or post using the provided shortcode: <b>&#91;chatbot_chatgpt&#93;<b></li>
            <li><b>Chatbot</b> now support either an embedded chatbot or floating chatbot.</li>
            <li>Use <b>&#91;chatbot_chatgpt&#93;</b> or <b>&#91;chatbot style="floating"&#93;</b> to display the chatbot as a floating chatbot.</li>
            <li>Use <b>&#91;chatbot style="embedded"&#93;</b> to display the chatbot as an embedded chatbot.</li>
            <li>By default, the chatbot will appear in the lower right corner of the page. This is adjustable in the .css file but not recommended for the causal site builders.</li>
            </ol>
    </div>
    <hr style="border-top: 2px solid black;">
    <div id=chatgpt-and-custom-gpt-assistants>
        <h2>ChatGPT and GPT Assistants</h2>
        <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
            <li>In Settings > API/Model, you can select to use ChatGPT (i.e., original) or create a GPT Assistant in the <a href="https://platform.openai.com/playground/" rel="nofollow ugc" target="_blank">https://platform.openai.com/playground/</a>.</li>
            <li>ChatGPT is a conversational AI platform that uses natural language processing and machine learning algorithms to interact with users in a human-like manner.</li>
            <li>It is designed to answer questions, provide suggestions, and engage in conversations with users.</li>
            <li>ChatGPT is important because it can provide assistance and support to people who need it, especially in situations where human support is not available or is limited.</li>
            <li>Coupling the power of ChatGPT or a GPT Assistant with the flexibility of WordPress, Kognetiks Chatbot for WordPress is a plugin that allows you to effortlessly integrate OpenAI&#8217;s ChatGPT API into your website.</li>
            <li>This provides a powerful, AI-driven chatbot for enhanced user experience and personalized support.</li>
            <li>For more information on using assistants, see <a href="https://beta.openai.com/docs/guides/assistants" rel="nofollow ugc" target="_blank">https://beta.openai.com/docs/guides/assistants</a>.</li>
            <li>Additional integration information can be found at <a href="https://kognetiks.com/wordpress-plugins/kognetiks-chatbot/chatbot-setup-and-configuration/" rel="nofollow ugc" target="_blank">https://kognetiks.com/wordpress-plugins/kognetiks-chatbot/chatbot-setup-and-configuration/</a>.</li>
        </ul>
        <h2>Using Multiple Custom GPT Assistants</h2>
        <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
            <li>In Settings > API/Model, you can select to use ChatGPT (i.e., original) or use one of two different Custom GPT Assistants you've created.</li>
            <li>As explain above, build your custom GPT assistants in the OpenAI Playground.</li>
            <li>Decide which one of your assistants will be 'primary' and which one will be 'alternate'.</li>
            <li>Incorporate your assistants in one of several different ways using the <b>&#91;chatbot_chatgpt&#93;</B> shortcode.</li>
        </ul>
        <p>Use the following format to invoke the primary or alternate assistant:</p>
        <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
            <li><b>&#91;chatbot&#93;</b> - Default values, floating style, uses OpenAI's ChatGPT</li>
            <li><b>&#91;chatbot style="floating"&#93;</b> - Floating style, uses OpenAI's ChatGPT</li>
            <li><b>&#91;chatbot style="embedded"&#93;</b> - Embedded style, uses OpenAI's ChatGPT</li>
            <li><b>&#91;chatbot style="floating" assistant="primary"&#93;</b> - Floating style, GPT Assistant as set in Primary setting</li>
            <li><b>&#91;chatbot style="embedded" assistant="alternate"&#93;</b> - Embedded style, GPT Assistant as set in Alternate setting</li>
        </ul>
    </div>
    <hr style="border-top: 2px solid black;">
    <div id="api-key-safety-and-security">
        <h3>API Key Safety and Security</h3>
        <p>Your API key serves as the confidential password providing access to your OpenAI account and the resources associated with it. If this key falls into the wrong hands, it can be misused in a variety of detrimental ways, including unauthorized usage, potential data leaks, and the improper application of AI models. It's crucial, therefore, to implement the following protective measures:</p>
        <ol>
            <li>Secure key storage: Ensure your API keys are stored in a safe and secure manner.</li>
            <li>Monitor and review usage: Frequently scrutinize and evaluate the usage of your API key. OpenAI provides handy usage data and records that can assist in detecting unusual activity. For insightful usage statistics, visit <a href="https://platform.openai.com/account/usage" rel="nofollow ugc" target="_blank">https://platform.openai.com/account/usage</a>.</li>
            <li>Establish usage limits: Initially, implement a low hard limit to ensure that if the limit is reached at any point during the month, any further requests will be denied. You can set up both hard and soft limits at <a href="https://platform.openai.com/account/billing/limits" rel="nofollow ugc" target="_blank">https://platform.openai.com/account/billing/limits</a>.</li>
            <li>Regular key rotation: Frequently changing your API keys can reduce the risk of misuse. If you observe any unexpected activity, it's important to immediately revoke your API keys. As a preventative measure, you might want to regularly revoke them to avert misuse. Manage your API keys at  <a href="https://platform.openai.com/account/api-keys" rel="nofollow ugc" target="_blank">https://platform.openai.com/account/api-keys</a>.</li>
        </ol>
        <p>Remember, wielding AI power requires immense responsibility — it's incumbent upon us all to ensure its careful and secure use.</p>
    </div>
    <hr style="border-top: 2px solid black;">
    <div id="chatbot-conversation-log"><a id="chatbot-conversation-log"></a>
        <h3>Conversation Logging Overview</h3>
        <p>This chatbot logs interactions with visitors to provide insights and enhance user experience. By default, the option to log conversations is turned off. Below is an overview of the table structure and its functionality.</p>

        <h3>Table Structure Overview</h3>
        <p>The table is designed to store key elements of each interaction, including:</p>
        <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
            <li><strong>ID:</strong> Unique identifier for each entry, auto-incremented.</li>
            <li><strong>Session ID:</strong> Identifies the session of the interaction.</li>
            <li><strong>User ID and Page ID:</strong> Identifies the user and the webpage of interaction.</li>
            <li><strong>Interaction Time:</strong> Timestamp of each interaction.</li>
            <li><strong>User Type:</strong> Distinguishes between visitor and chatbot messages.</li>
            <li><strong>Thread ID and Assistant ID:</strong> For identifying specific threads or bot instances.</li>
            <li><strong>Message Text:</strong> Content of each message exchanged.</li>
        </ul>

        <h3>How It Works</h3>
        <p>Each interaction with the chatbot is logged in real-time, capturing all relevant information into the table. This includes automatic and direct data sources for fields like interaction time and message text.</p>

        <h3>Application</h3>
        <p>The conversation log maybe used for:</p>
        <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
            <li><strong>Analysis and Reporting:</strong> Generate reports on user interactions and queries.</li>
            <li><strong>Bot Improvement:</strong> Refine chatbot responses based on logged data.</li>
            <li><strong>User Experience Enhancement:</strong> Utilize insights for improving user interactions.</li>
            <li><strong>Compliance and Record-Keeping:</strong> Maintain logs for regulatory requirements.</li>
        </ul>

        <p>This table is integral to managing and analyzing chatbot interactions, enabling continuous improvement and providing valuable insights into user engagement on your WordPress site.</p>
    </div>
    <div id="chatbot-privacy-considerations">
    <h2>Privacy and User Notification</h2>
        <p>Our commitment to you and your visitors' privacy is paramount when interacting with our chatbot. Below are the key aspects of how we address privacy concerns:</p>

        <h3>Transparent Communication</h3>
        <p>Visitors should be informed that interactions with the chatbot are recorded. This should be communicated through a notice when the chatbot is first engaged.</p>

        <h3>Purpose of Data Collection</h3>
        <p>The data collected may be used to improve user experience and chatbot functionality. You should ensure that all data is handled securely and in compliance with relevant privacy regulations.</p>

        <h3>Data Storage and Use</h3>
        <p>Information on how the collected data is stored and used is provided, and should adhere to privacy standards like GDPR and CCPA.</p>

        <h2>Conversation Log Deletion</h2>
        <p>You can set the retention period in the plugin settings to automatically delete entries in the conversation log after certain periods of days (1, 7, 30, etc.).</p>

        <h3>Privacy Policy and Link</h3>
        <p>We encourage the inclusion of a privacy policy link in the chatbot interface. The policy should detail the management of chatbot data.</p>
        <p>A link to your site's privacy policy should base64_encode included the <b>Example Notification</b> below, which explains the specifics of chatbot data management.</p>
        <p>Please consult with the appropriate legal counsel and professionals to ensure that your privacy policy is compliant with all applicable laws and regulations.</p>

        <h3>Details in Privacy Policy</h3>
        <p>The privacy policy suggests detailed information about data collection, use, legal basis for processing, retention practices, and user rights.</p>

        <h3>Regular Updates</h3>
        <p>The privacy policy should be regularly updated to reflect any changes in data handling practices.</p>

        <h2>Example Notification</h2>
        <blockquote>
            "Please note that your interactions with our chatbot are logged for the purpose of improving our services and providing better support. We respect your privacy, and all data is handled in accordance with our privacy policy, which you can review &lt;a href='https//...link to your privacy page ...'&gt;here&lt;/a&gt;. Your continued use of the chatbot indicates your consent to these practices."
        </blockquote>
    </div>
    <hr style="border-top: 2px solid black;">
    <div id="chatbot-diagnostics">
        <h3>Kognetiks Chatbot Diagnostics - For Developers</h3>
        <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
            <li>By default, the <b>Kognetiks Chatbot Diagnostics</b> setting is disabled. When enabled, the plugin provides useful information about the <b>Chatbot's</b> operation. This information can be used to troubleshoot issues and to better understand how it is functioning.</li>
            <li>The plugin supports Success, Notice, Warning, Failure, and Error, i.e., increasing levels of severity. The default level is Success. The higher the level, the more information is provided.</li>
            <li>In addition to setting the <b>Kognetiks Chatbot's</b> diagnostics reporting level, you will also need to enable WordPress debugging. This can be done by setting the <b>WP_DEBUG</b> constant to true in your wp-config.php file.</li>
            <li>Turning on WordPress debugging will cause all PHP errors, notices, and warnings to be displayed. This is useful for debugging and development purposes.</li>
            <li><b><i>NOTE: It is not recommended to enable WordPress debugging on a production site.</i></b></li>
        </ul>
    </div>
    <hr style="border-top: 2px solid black;">
    <div id="disclaimer">
        <h3>Disclaimer</h3>
        <p>OpenAI, ChatGPT, and related marks are registered trademarks of OpenAI. Kognetiks is not a partner of, endorsed by, or sponsored by OpenAI.</p>
    </div>
    <hr style="border-top: 2px solid black;">
    <div id="thank-you">
        <h2><i>Thank you for using Kognetiks Chatbot for WordPress</i></h2>
    </div>
    <?php
}
