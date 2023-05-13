=== Chatbot ChatGPT for WordPress ===
Contributors: Kognetiks
Tags: chatbot, chatgpt, openai, ai, customer-support, conversational chat
Donate link: https://kognetiks.com/wordpress-plugins/donate/
Requires at least: 5.0
Tested up to: 6.2
Stable tag: 1.4.2
Requires PHP: 7.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily integrate OpenAI's ChatGPT API into your WordPress website with this powerful, AI-driven chatbot plugin for personalized support and engagement.

== Description ==

Chatbot ChatGPT for WordPress is a plugin that allows you to effortlessly integrate OpenAI's ChatGPT API into your website, providing a powerful, AI-driven chatbot for enhanced user experience and personalized support.

ChatGPT is a conversational AI platform that uses natural language processing and machine learning algorithms to interact with users in a human-like manner. It is designed to answer questions, provide suggestions, and engage in conversations with users. ChatGPT is important because it can provide assistance and support to people who need it, especially in situations where human support is not available or is limited. It can also be used to automate customer service, reduce response times, and improve customer satisfaction. Moreover, ChatGPT can be used in various fields such as healthcare, education, finance, and many more.

Chatbot ChatGPT leverages the OpenAI platform using the gpt-3.5-turbo or the gpt-4 model brings it to life within your WordPress Website.

**Important Note:** This plugin requires an API key from OpenAI to function correctly. You can obtain an API key by signing up at [https://platform.openai.com/account/api-keys](https://platform.openai.com/account/api-keys).

Official website: [Kognetiks.com](https://kognetiks.com/wordpress-plugins/chatbot-chatgpt/)

== Features ==

* Easy setup and integration with OpenAI's ChatGPT API
* Support for gpt-3.5-turbo
* Support for gpt-4 (Learn how to access the gpt-4 API at [https://help.openai.com/en/articles/7102672-how-can-i-access-gpt-4](https://help.openai.com/en/articles/7102672-how-can-i-access-gpt-4))
* Floating chatbot interface with customizable appearance
* User-friendly settings page for managing API key and other parameters
* Collapsible chatbot interface when not in use
* Initial greeting message for first-time users
* Shortcode to embed the chatbot on any page or post
* Setting to determine if chatbot should start opened or closed
* Chatbot maintains state when navigating between pages
* Chatbot name and initial and subsequent greetings are configurable

== Getting Started ==

1. Obtain your API key by signign up at [https://platform.openai.com/account/api-keys](https://platform.openai.com/account/api-keys).
2. Install and activate the Chatbot ChatGPT plugin.
3. Navigate to the settings page (Settings > API/Model) and enter your API key.
4. Customize the chatbot appearance and other parameters as needed.
5. Add the chatbot to any page or post using the provided shortcode: [chatbot_chatgpt]

Now your website visitors can enjoy a seamless and personalized chat experience powered by OpenAI's ChatGPT API.

== Installation ==

1. Upload the 'chatbot-chatgpt' folder to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the 'Settings > Chatbot ChatGPT' page and enter your OpenAI API key.
4. Customize the chatbot appearance and other parameters as needed.
5. Add the chatbot to any page or post using the provided shortcode: [chatbot_chatgpt]

== Frequently Asked Questions ==

= How do I obtain an API key for ChatGPT? =

To obtain an API key, sign up for an account at [https://platform.openai.com/account/api-keys](https://platform.openai.com/account/api-keys). Once registered, you will have access to your API key.

= Can I customize the appearance of the chatbot? =

Yes, the plugin comes with a default style, but you can easily customize the chatbot's appearance by editing the chatbot-chatgpt.css file or adding custom CSS rules to your WordPress theme.

You can also customize the name of the chatbot, as well as changing the initial greeting and subsequent greeting.

= Is the chatbot available in multiple languages? =

The ChatGPT API currently supports English. However, you can configure the chatbot to work with other languages supported by OpenAI in the future as the API evolves.

= Which OpenAI model does the plugin use? =

The plugin supports the gpt-3.5-turbo and gpt-4 models from OpenAI.  These are the same models found in the ChatGPT product from OpenAI.

== Screenshots ==

1. Inital Chatbot ChatGPT display
2. Ask Chatbot ChatGPT any question
3. Get a response from Chatbot ChatGPT
4. Settings and Options
5. Mobile Experience - Initial Chatbot ChatGPT display
6. Mobile Experience - Ask Chatbot ChatGTP any question
7. Mobile Experience - Get a response from Chatbot ChatGPT
8. Mobile Experience - Chatbot ChatGPT minimize (Lower Right)
9. Chatbot ChatGPT shown in Wide mode

== Changelog ==

= 1.4.2 =
* Added support for the GPT-4 API in settings - requires access to gpt-4 API, see [https://openai.com/waitlist/gpt-4-api](https://openai.com/waitlist/gpt-4-api)
* Added support for max tokens (the maximum number of tokens to generate in the completion)
* Added support for narrow or wide bot message modes (other options coming soon)

= 1.4.1 = 
* Updated start bot open or closed
* Add or remove default AI disclaimer

= 1.4.0 =
* SVN Update Error - 1.2.0 did not update to 1.3.0

= 1.3.0 =
* Updated Setting Page adding tabs for API/Model, Greetings, and Support
* Updated directory assets

= 1.2.0 =
* Removed initial styling on bot to ensure it renders at the appropriate time.
* Save the conversation locally between bot sessions in local storage.

= 1.1.0 =
* If bot is closed stay closed or if open stay open when navigating between pages.
* Ensure the Dashicons font is properly enqueued.
* Added options to change Bot Name, start with the bot Open or Closed, and option to personalize Initial and Subsequent Greetings by the bot.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
* Initial release.
