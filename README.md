# Kognetiks Chatbot for WordPress

The **Kognetiks Chatbot for WordPress** plugin project is centered around revolutionizing digital interactions on WordPress websites looking to incorporate Artificial Intelligent products such as those powered by OpenAI. The plugin is designed to enhance visitor engagement through intelligent and interactive conversational experiences, leveraging advanced AI technology for customer support and other conversational applications on WordPress sites. This project aims to make cutting-edge AI accessible and functional within the WordPress ecosystem.

## Documentation

## What's New in Version 2.0.9

* **Adjusted Module Name Conflict**: Renamed one module that was had a name found to be in conflict with another vendor's plugin.
* **Refactored Inline Styles**: Moved inline styles to an external CSS file for better maintainability and separation of concerns.

## What's New in Version 2.0.8

* **Logic Error Updated**: Corrected a logic error that was causing some visitors and logged-in users to lose their session continuity with the Assistants. This ensures a smoother and more consistent experience for all users.
* **Fixed Special Characters Display Issue**: Improved the way special characters are handled in chatbot names. Previously, the code was converting special characters like '&' into their HTML equivalents (e.g., '&' became '&').

## What's New in Version 2.0.7

* **Model Support**: The latest models available from OpenAI are dynamically added to model picklists.  Available models now include gpt-4o and gpt-4o-mini.  See Chatbot Settings > API/Model > Chat Settings.
* **Manage Chatbot Error Logs**: Added the ability to manage chatbot error logs, including the ability to download and delete logs. See Chatbot Settings > Tools. TIP: You must enable Diagnostics access the Tools tab. See Chatbot Settings > Messages > Messages and Diagnostics.
* **Revised Reporting Settings Layout**: Revised and refreshed the Reporting Settings page layout for better visualization. See Chatbot Settings > Reporting.
* **Conversation Continuation**: Added an additional setting added to enable conversation continuation after returning to a page previously visited. See Chatbot Settings > Settings > Additional Settings.

## What's New in Version 2.0.6

* **Dynamic Shortcode**: Added support for dynamic shortcodes to allow for more flexible Assistant selection. Add all parameters to the shortcode, including the Assistant ID on the GTP Assistant tab. For example, `[chatbot-1]`.
* **Logic Error Updated**: Corrected a logic error that prevented visitors and logged-in users from interacting with Assistants.

## What's New in Version 2.0.5

* **Enhanced Assistant Management**: A new intuitive interface for managing all your chatbot Assistants in one place.
* **Assistant ID Integration**: Easily add Assistants developed in the OpenAI Playground using their unique ID.
* **Improved Shortcode Usage**: Tips for optimal placement and usage of the `[chatbot assistant="Common Name"]` shortcode.
* **Customizable Assistant Attributes**: Tailor each Assistant's settings such as Styling, Target Audience, Voice, Allow File Uploads, Allow Transcript Downloads, Show Assistant Name, Initial Greeting, Subsequent Greeting, Placeholder Prompt, and Additional Instructions.
* **Support Tab**: Reverted the "Support" tab to correctly display the plugin's support documentation overview.
* **Embedded Chatbot Formatting Updated**: Added a closing </div> tag to the embedded chatbot to ensure proper formatting.
* **Force Page Reload on Conversation Cleared**: Added an option to force a page reload when the conversation is cleared.
* **Knowledge Navigator Analysis**: Moved the Knowledge Navigator Analysis for export to the bottom of the Knowledge Navigator tab.
* **Custom Buttons Expanded**: Now supports up to four custom buttons, available on floating only, embedded only, or on both chatbot styles.

## What's New in Version 2.0.4

- Removed session id from the chatbot shortcode and replaced with a unique id for visitors and logged-in users alike.

## What's New in Version 2.0.3

* **Transcript Download Option**: You can now choose whether users can download a transcript of their conversations with the chatbot.
* **Improved Image Sizing**: Images smaller than the chatbot's message view now display in their actual size for better clarity.
* **Knowledge Navigator Settings**: We've added an option to disable the Knowledge Navigator if you only want to use assistants for chatbot interactions.
* **Knowledge Navigator Analysis**: Increased the maximum number of top keywords to 10,000 for more detailed analysis.
* **File Download Support**: The chatbot now supports downloading files generated on the OpenAI platform.

## What's New in Version 2.0.2

* **Documentation Updates:** The Support documentation has been extensively updated with detailed information on the chatbot settings. You can find this in the Support tab under Settings.

* **Data Export:** The export function for Conversation Data, Interaction Data, and Token Usage Data has been revised.

* **PHP 7 Support:** The `str_contains` function has been reverted to `strpos` for compatibility with PHP 7, as `str_contains` is only available in PHP 8.

## What's New in Version 2.0.1

* **Support for Latest Models**
  - Supports OpenAI's latest models: `gpt-4o` and `gpt-4o-2024-05-13`

## 2.0.1 - Configuration Options
* **Max Prompt Tokens - Assistants**
  - Controls the maximum prompt token usage. Limits apply to the total number of tokens used in all completions throughout the run's lifecycle.
  - Example: If set to 500, prompts will be truncated at 500 tokens.
  - [More Info](https://platform.openai.com/docs/assistants/how-it-works/max-completion-and-max-prompt-tokens)

* **Max Completion Tokens - Assistants**
  - Controls the maximum completion token usage. Limits apply to the total number of tokens used in all completions throughout the run's lifecycle.
  - Example: If set to 1000, the completion will cap the output at 1000 tokens.
  - [More Info](https://platform.openai.com/docs/assistants/how-it-works/max-completion-and-max-prompt-tokens)

* **Temperature - Assistants**
  - Controls randomness. Lowering the temperature results in less random completions. As the temperature approaches zero, the model will become deterministic and repetitive.

* **Top P - Assistants**
  - Controls diversity via nucleus sampling. For example, setting Top P to 0.5 means half of all likelihood-weighted options are considered.
  
## 2.0.1 - Speech-to-Text Prompting
* **Whisper API Prompting**
  - You can use a prompt to improve the quality of transcripts generated by the Whisper API. The model will try to match the style of the prompt, so it will be more likely to use capitalization and punctuation if the prompt does too. However, the current prompting system is more limited than other language models and only provides limited control over the generated audio.
  - [More Info](https://platform.openai.com/docs/guides/speech-to-text/prompting)

## What's New in Version 2.0.0

* **Revised Knowledge Navigator Settings:** Reground similar options together on this settings tab including: Suppress Learning Messages, Customer Learnings Messages, and Enhanced Response Limit.

* **Read Aloud Option:** Added an option to allow the Read Aloud option, see API/Model > Voice Settings > Allow Read Aloud = Yes/No.

* **Enhanced Security:** Reduced vulnerabilities associated with file upload options.

## What's New in Version 1.9.9

* **Enhanced Response Clarity:** Responses now feature bullet points for improved clarity and readability.

* **Improved Link Presentation:** Titles are now included alongside links to posts, pages, and products, providing better context for users.

* **Thread Retention Periods:** Added an option for thread retention periods, ensuring conversation continuity with default settings of 36 hours or 720 hours (30 days).

* **Assistant Identification:** Conversation logs now include either the chatbot's or Assistant's name for clearer tracking.

* **Enhanced Conversation History:** Upgraded the conversation history shortcode `[chatbot_chatgpt_history]` to include the Assistant or chatbot's name for easier reference.

* **Transcript Download Option:** Added the ability for users to download conversation transcripts as text files to their computers.

* **Message Input Customization:** Added an option to set the number of rows for message input, ranging from 1 to 10 rows for improved customization.

* **Uninstallation Cleanup:** Implemented comprehensive cleanup procedures upon uninstalling the plugin to ensure little if any residual data or files remain.

## What's New in Version 1.9.8

* **Improved Shortcode Documentation:** We've enhanced the readability of shortcode examples by wrapping them in \<code>\</code> tags, making them clearer and more accessible within the documentation.

* **Corrected Session Handling:** We now ensure that PHP sessions are properly closed after acquiring a session ID, improving resource management and security

## What's New in Version 1.9.7

* **Corrected Enhanced Responses Append:** The repeated "here, here, here" text has been removed when the Suppress Learning Messages option is set to None.

## What's New in Version 1.9.6

* **Optimized Knowledge Navigator:** The Knowledge Navigator has been revised to efficiently handle sites with large numbers of pages, posts, and products, ensuring more accurate search capabilities.

* **Customizable Depth for TF-IDF Scoring:** A new turing parameter allows you to set the depth of TF-IDF scoring based on the content length of pages, posts, or products, enhancing the relevance of search results.

* **Flexible Enhanced Responses:** You can now select between 1 and 10 enhanced responses, which link to the highest matching pages, posts, or products on your site based on visitor input based on matches to the TF-IDF scores.

* **API Version Migration:** Added the option to select between OpenAI-Beta API versions: v1 (assistants=v1) and v2 (assistants=v2, default setting). Refer to the [OpenAI Migration Guide](https://platform.openai.com/docs/assistants/migration/accessing-v1-data-in-v2) for detailed information on the changes.

* **Daily Interaction Message Limit:** A new setting has been introduced to limit visitor interactions with the chatbot from 1 to 999 (the default is 999 messages per day and resets daily). This can be configured in the Chatbot Settings using the Chatbot Daily Message Limit option.

## What's New in Version 1.9.5

The latest update for the Kognetiks Chatbot for WordPress plugin, Version 1.9.5, brings an array of new voice options and output formats, alongside improved audio playback controls and enhanced user interface adjustments for a more streamlined experience.

* **Expanded Voice Options:** We've introduced six new voice options for Text-to-Speech functionalities to cater to diverse preferences and applications. The new voices, including Allow, Echo, Fable, Onyx, Nova, and Shimmer, can be selected to personalize the auditory output of the chatbot. This variety ensures you can choose voices that best fit your brand or personal style.

* **Enhanced Voice Output Formats:** To accommodate various technical needs and quality preferences, we have expanded our range of output formats. Users can now choose from MP3, Opus, AAC, FLAC, WAV, and PCM formats to optimize the audio quality and compatibility of the Text-to-Speech outputs.

* **Repositioned Chatbot Controls:** For an improved user interface, we have moved essential chatbot controls — such as submit, file upload, erase, and text-to-speech buttons — below the input box. This rearrangement enhances accessibility and makes the chat interface cleaner and more intuitive.

* **Redesigned API/Model Settings Page:** The settings page for chat, image, and speech generation parameters has been redesigned for better usability. You can now more easily adjust and tune your settings, ensuring the chatbot performs optimally across all integrated models.

These updates aim to enhance the versatility and visitor experience of the Kognetiks Chatbot for WordPress, continuing our commitment to deliver cutting-edge, customizable, and accessible technology solutions.

## What's New in Version 1.9.4

Version 1.9.4 of the **Kognetiks Chatbot for WordPress** plugin introduces personalized greeting options, displays the Assistant's name sourced from OpenAI, expands support to include image and speech models, and integrates DALL-E for image generation alongside Text-to-Speech functionalities for an enriched user interaction.

* **Personalized Greetings:** Users now have the option to personalize both initial and subsequent greetings for the chatbot, enhancing the user experience with a more individualized interaction. Just add any field from your _users or _usermeta tables in WordPress to the Initial Greeting or Subsequent Greeting, such as: "**Hello [first_name], how can I help you today?**".  This can be found under Settings > Kognetiks Chatbot > Settings.

* **Display Assistant's Name:** We've introduced a feature that allows the display of the Assistant's name, which is sourced directly from the OpenAI platform. This can be found and adjusted under Settings > Kognetiks Chatbot > GPT Assistants > Display GPT Assistant Name.

* **Support for Additional Models:** Our support model range has been expanded to include not just text but also image and speech functionalities. This broadens the chatbot's application in various interactive scenarios.  You can call the Chatbot using the "model" parameter in the shortcode.

* **Image Generation with DALL-E:** The chatbot is now equipped to generate images using OpenAI's DALL-E models, offering users a new dimension of creativity and visual interaction. To generate images using the "dall-e-3" model, use the shortcode **[chatbot style=embedded model=dall-e-3]**.

* **Text-to-Speech Conversion:** With the integration of Text-to-Speech (TTS) models, the chatbot can now convert text inputs into spoken word, making it accessible for auditory communication and enhancing user engagement through speech. To generate speech from text, use the shortcode **[chatbot style=embedded model=tts-1-1106]**.

## Features

Welcome to the future of website interaction with **Kognetiks Chatbot for WordPress**, your gateway to unparalleled visitor engagement powered by OpenAI's ChatGPT Large Language Models (LLMs) and Assistants.

**🌐 Harnessing Large Language Models for Enhanced Engagement**
Dive into the world of advanced AI with Large Language Models at the core of the Chatbot. These models are trained to understand and respond to user queries in a natural, conversational manner. They're not just chatbots; they're intelligent conversational partners that can engage, inform, and assist your visitors in real time. Whether it's providing detailed answers to complex queries or engaging in casual conversation, these models are equipped to elevate the user experience on your website.

**🌟 Assistants: Tailored Conversational Experiences**
Unlock the potential of personalized digital interaction. Use the Assistants you develop, trained with your specific knowledge and skills, to revolutionize your website. From handling FAQs to managing bookings and offering customized suggestions, these Assistants are seamlessly integrated into your WordPress site, promising a dynamic and engaging user experience.

**🔀 Multiple Assistants, Multiple Roles**
The latest version of the plugin allows for virtually unlimited Assistants, allowing you to deploy a unique assistant wherever you placed the shortcode.  Simply pass the "asst_" ID to the shortcode as one of the parameters.  Discover more about these innovative features at [Kognetiks.com](https://kognetiks.com/wordpress-plugins/kognetiks-chatbot/) and [OpenAI's Playground](https://platform.openai.com/assistants).

**🔄 Choose Your Style: Embedded or Floating Chatbots**
Flexibility is key. Display your AI-powered chatbot as an embedded feature on pages or let it float across your site. With simple shortcodes, adapt the chatbot's presence to match your website's design and user needs.

**🔍 Knowledge Navigator: Unearthing Your Content's Essence**
At the heart of the plugin lies the Knowledge Navigator. This powerful tool delves deep into your website, mapping its architecture and content, enabling the chatbot to deliver precise and contextually relevant responses. Enhanced by TF-IDF analysis, the Knowledge Navigator ensures your content's unique keywords shine through, making interactions more meaningful.

**🗎 Conversation Logging**
Conversation Logging in this plugin records and stores chat interactions between users and the chatbot, providing valuable insights for enhancing user experience and chatbot performance. Visit the privacy policy on the Settings Support tab for details on data handling.

**🎭 Personalize with Custom Avatars**
Add a creative touch with customizable avatars. Reflect your site's personality through these visual companions, enhancing user engagement and adding a unique flair to your digital space.

**📊 Direct Traffic with Customizable Buttons**
Guide your visitors where you want them. Customizable buttons can link directly to specific pages, forms, or contact information, facilitating smoother navigation and enhanced user engagement.

**🤖 Tailored Audience Engagement**
Customize accessibility with three audience settings: All Audiences, Logged-in Only, or Visitors Only. Additionally, control presentation to specific audiences. Whether floating or embedded, tailor the chatbot's visibility for a seamless user experience across platforms.

**🎭Personalized Greetings:**
Users now have the option to personalize both initial and subsequent greetings for the chatbot, enhancing the user experience with a more individualized interaction. Just add any field from your _users or _usermeta table in WordPress to the Initial Greeting or Subsequent Greeting, such as: "Hello [first_name], how can I help you today?". This can be found under Settings > Kognetiks Chatbot > Settings.

**🤖Display Assistant's Name:**
We've introduced a feature that allows the display of the Assistant's name, which is sourced directly from the OpenAI platform. This can be found and adjusted under Settings > Kognetiks Chatbot > GPT Assistants > Display GPT Assistant Name.

**🌟Support for Additional Models:**
Our support model range has been expanded to include not just text but also image and speech functionalities. This broadens the chatbot's application in various interactive scenarios. You can call the Chatbot using the "model" parameter in the shortcode.

**🌟Image Generation with DALL-E:**
The chatbot is now equipped to generate images using OpenAI's DALL-E models, offering users a new dimension of creativity and visual interaction. To generate images using the "dall-e-3" model, use the shortcode **[chatbot style=embedded model=dall-e-3]**.

**🔄Text-to-Speech Conversion:**
With the integration of Text-to-Speech (TTS) models, the chatbot can now convert text inputs into spoken word, making it accessible for auditory communication and enhancing user engagement through speech. To generate speech from text, use the shortcode **[chatbot style=embedded model=tts-1-1106]**.

**🤖 Why the Kognetiks Chatbot for WordPress?**
▪ **Natural Conversations:** Experience human-like interactions, thanks to Large Language Model APIs from companies like OpenAI.
▪ **Always Available:** Provide round-the-clock assistance in various domains, from healthcare to education.
▪ **Seamless Integration:** Effortlessly bring your WordPress site to life with an easy-to-use plugin.

**✨ Supported Models from OpenAI**
▪ gpt series of models including gpt-4o and gpt-4o-mini
▪ dall-e series of models
▪ tts series of models
▪ stt series of models

For a full list of models, please see [OpenAI's Model Overview](https://platform.openai.com/docs/models/overview).

**🚀 Elevate Your Website Experience**
The Kognetiks Chatbot for WordPress is more than just a plugin – it's a transformational tool for your website. With advanced AI technology at its core, it promises a unique and interactive experience for your visitors.

Get your Kognetiks Chatbot for WordPress today and redefine your WordPress site with intelligence and a personal touch.

**Note:** This plugin requires an API key from OpenAI. Obtain yours at [OpenAI API Keys](https://platform.openai.com/account/api-keys).

## 🌐 Features at a Glance
* **Quick Setup:** Integrate easily with API from companies like OpenAI.
* **Advanced AI Models:** Includes support for the latest GPT-4 Turbo from OpenAI.
* **Customizable Interfaces:** Choose between floating and embedded chatbot styles.
* **User-Friendly Settings:** Easily manage your API key and other settings.
* **Intelligent Design:** Smart collapsible chatbot for a cleaner website interface.
* **Engaging User Interaction:** Customize greetings and messages for a unique visitor experience.
* **Persistent Memory:** The chatbot remembers interactions, offering continuity across pages.
* **In-depth Content Analysis:** Knowledge Navigator ensures contextually relevant interactions.

## Getting Started

1. Obtain your API key by signing up at [https://platform.openai.com/account/api-keys](https://platform.openai.com/account/api-keys).
2. Install and activate the Chatbot plugin.
3. Navigate to the settings page (Settings > API/Model) and enter your API key.
4. Customize the chatbot appearance and other parameters as needed.
5. For a floating chatbot add the shortcode to your theme's footer: `[chatbot]` or `[chatbotstyle=floating]`
6. For an embedded chatbot on any page add the shortcode: `[chatbot style=embedded]`
7. Use `[chatbot style=floating|embedded assistant=primary|alternate]` to display the chatbot as a floating chatbot or embedded chatbot with a primary or alternate assistant.

Now your website visitors can enjoy a seamless and personalized chat experience with the Kognetiks Chatbot for WordPress.

## Installing the Chatbot on Your WordPress Website

Embark on a journey to elevate your website's interactivity with this Chatbot plugin. Here's how to get started:

1. **Plugin Upload**
   - Begin by downloading the 'chatbot-chatgpt' plugin folder.
   - Navigate to your WordPress website's dashboard.
   - Click on 'Plugins' and select 'Add New'.
   - Choose the 'Upload Plugin' option at the top of the page.
   - Upload the 'chatbot-chatgpt' folder and click 'Install Now'.

2. **Plugin Activation**
   - Once the installation is complete, activate the plugin by clicking 'Activate Plugin'.

3. **API Key Configuration**
   - After activation, head to 'Settings > Chatbot' in your dashboard.
   - Enter your OpenAI API key here. (You can obtain this key from [OpenAI API Keys](https://platform.openai.com/account/api-keys) if you haven't already.)

4. **Customizing Your Chatbot**
   - In the same settings area, tailor the chatbot's appearance and functionality to match your site's style and your specific needs.

5. **Embedding the Chatbot**
   - You can add the chatbot to any page, footer, or sidebar of your theme.
   - Use the shortcode `[chatbot]` for a standard chatbot.
   - For a floating chatbot, use `[chatbot style=floating]`.
   - If you prefer an embedded chatbot, use `[chatbot style=embedded]`.

6. **Knowledge Navigator Setup**
   - To fully utilize the capabilities of Chatbot, go back to 'Settings > Chatbot' and click on the 'Knowledge Navigator' tab.
   - Initiate a site scan to allow the Knowledge Navigator to map and understand your site's content.

7. **Scheduling Knowledge Navigator**
   - Opt for hourly, daily, or weekly scans through the Knowledge Navigator to ensure the chatbot stays updated with your latest content.

## Your Journey Towards an Interactive Website Begins!

With the Kognetiks Chatbot installed, you're now equipped to offer a more dynamic, engaging, and responsive experience to your website visitors.

## Frequently Asked Questions

**How do I obtain an API key for the API?**

To obtain an API key, sign up for an account at [https://platform.openai.com/account/api-keys](https://platform.openai.com/account/api-keys). Once registered, you will have access to your API key.

**Can I customize the appearance of the chatbot?**

Yes, the plugin comes with a default style, but you can easily customize the chatbot's appearance by editing the chatbot-chatgpt.css file or adding custom CSS rules to your WordPress theme.

You can also customize the name of the chatbot, as well as changing the initial greeting and subsequent greeting.

**Is the chatbot available in multiple languages?**

Yes, the Kognetiks Chatbot for WordPress and the OpenAI's ChatGPT API support many different languages. Set the 'Site Language' option in WordPress to your preference.

**Which OpenAI models does the plugin use?**

The plugin supports the gpt-3.5-turbo, gpt-4, gpt-4-1106-preview models from OpenAI.  These are the same models found in the ChatGPT product from OpenAI.

The plugin now supports the latest OpenAI model **gpt-4-turbo (i.e., 'gpt-4-1106-preview')** featuring improved instruction following based on training data up to April 2023.  New models will be added as the become available.

**More FAQs**

You can find more frequently asked questions at [https://kognetiks.com/wordpress-plugins/frequently-asked-questions/](https://kognetiks.com/wordpress-plugins/frequently-asked-questions/).

## API Key Safety and Security

Your API key serves as the confidential password providing access to your OpenAI account and the resources associated with it. If this key falls into the wrong hands, it can be misused in a variety of detrimental ways, including unauthorized usage, potential data leaks, and the improper application of AI models. It's crucial, therefore, to implement the following protective measures:

1. Secure key storage: Ensure your API keys are stored in a safe and secure manner.
2. Monitor and review usage: Frequently scrutinize and evaluate the usage of your API key. OpenAI provides handy usage data and records that can assist in detecting unusual activity. For insightful usage statistics, visit [https://platform.openai.com/account/usage](https://platform.openai.com/account/usage).
3. Establish usage limits: Initially, implement a low hard limit to ensure that if the limit is reached at any point during the month, any further requests will be denied. You can set up both hard and soft limits at [https://platform.openai.com/account/billing/limits](https://platform.openai.com/account/billing/limits).
4. Regular key rotation: Frequently changing your API keys can reduce the risk of misuse. If you observe any unexpected activity, it's important to immediately revoke your API keys. As a preventative measure, you might want to regularly revoke them to avert misuse. Manage your API keys at [https://platform.openai.com/account/api-keys](https://platform.openai.com/account/api-keys).

Remember, wielding AI power requires immense responsibility — it's incumbent upon us all to ensure its careful and secure use.

## License

- License: GPLv3 or later
- License URI: https://www.gnu.org/licenses/gpl-3.0.html

## Support

💬 Looking for **plugin support**, please visit [https://kognetiks.com/wordpress-plugins/plugin-support/](https://kognetiks.com/wordpress-plugins/plugin-support/).

📜 For **frequently asked questions**, please visit [https://kognetiks.com/wordpress-plugins/frequently-asked-questions/](https://kognetiks.com/wordpress-plugins/frequently-asked-questions/).

## Disclaimer

OpenAI, ChatGPT, and related marks are registered trademarks of OpenAI. Kognetiks is not a partner of, endorsed by, or sponsored by OpenAI.

## Thank you for using Kognetiks Chatbot for WordPress

Visit us at [Kognetiks.com](https://kognetiks.com/wordpress-plugins/kognetiks-chatbot/ai-powered-chatbot-for-wordpress/) for more information.
