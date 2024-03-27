# Kognetiks Chatbot for WordPress

The **Kognetiks Chatbot for WordPress** plugin project is centered around revolutionizing digital interactions on WordPress websites looking to incorporate Artificial Intelligent products such as those powered by OpenAI. The plugin is designed to enhance visitor engagement through intelligent and interactive conversational experiences, leveraging advanced AI technology for customer support and other conversational applications on WordPress sites. This project aims to make cutting-edge AI accessible and functional within the WordPress ecosystem.

## The Newest Features

Version 1.9.4 of the **Kognetiks Chatbot for WordPress** plugin introduces personalized greeting options, displays the Assistant's name sourced from OpenAI, expands support to include image and speech models, and integrates DALL-E for image generation alongside Text-to-Speech functionalities for an enriched user interaction.

- **Personalized Greetings:** Users now have the option to personalize both initial and subsequent greetings for the chatbot, enhancing the user experience with a more individualized interaction. Just add any field from your _users table in WordPress to the Initial Greeting or Subsequent Greeting, such as: "**Hello [display_name], how can I help you today?**".  This can be found under Settings > Kognetiks Chatbot > Settings.

- **Display Assistant's Name:** We've introduced a feature that allows the display of the Assistant's name, which is sourced directly from the OpenAI platform. This can be found and adjusted under Settings > Kognetiks Chatbot > GPT Assistants > Display GPT Assistant Name.

- **Support for Additional Models:** Our support model range has been expanded to include not just text but also image and speech functionalities. This broadens the chatbot's application in various interactive scenarios.  You can call the Chatbot using the "model" parameter in the shortcode.

- **Image Generation with DALL-E:** The chatbot is now equipped to generate images using OpenAI's DALL-E models, offering users a new dimension of creativity and visual interaction. To generate images using the "dall-e-3" model, use the shortcode **[chatbot style=embedded model=dall-e-3]**.

- **Text-to-Speech Conversion:** With the integration of Text-to-Speech (TTS) models, the chatbot can now convert text inputs into spoken word, making it accessible for auditory communication and enhancing user engagement through speech. To generate speech from text, use the shortcode **[chatbot style=embedded model=tts-1-1106]**.

## Features

Welcome to the future of website interaction with **Kognetiks Chatbot for WordPress**, your gateway to unparalleled visitor engagement powered by OpenAI's ChatGPT Large Language Models (LLMs) and Assistants.

**🌐 Harnessing OpenAI's Large Language Models for Enhanced Engagement**

Dive into the world of advanced AI with the **Kognetiks Chatbot for WordPress**.  At the core, the Chatbot takes advantage of API access to Large Language Models such as those powered by OpenAI. These models are trained to understand and respond to user queries in a natural, conversational manner. They're not just chatbots; they're intelligent conversational partners that can engage, inform, and assist your visitors in real time. Whether it's providing detailed answers to complex queries or engaging in casual conversation, these models are equipped to elevate the user experience on your website.

**🌟 Assistants: Tailored Conversational Experiences**

Unlock the potential of personalized digital interaction. Use the Assistants you develop, trained with your specific knowledge and skills, are here to revolutionize your website. From handling FAQs to managing bookings and offering customized suggestions, these Assistants are seamlessly integrated into your WordPress site, promising a dynamic and engaging user experience.

**🔀 Multiple Assistants, Multiple Roles**

Why settle for one when you can have more? With the plugin, deploy multiple Assistants for varied roles. Imagine an Assistant dedicated to your blog and another for your e-commerce platform, each delivering a tailored experience to your visitors.

The latest version of the plugin allows for virtually unlimited Assistants, allowing you to deploy a unique assistant wherever you placed the shortcode.  Simply pass the "asst_" ID to the shortcode as one of the parameters.

Unlock enhanced functionality with the latest feature - **now you can upload files directly to each GPT Assistant**, providing an even more dynamic and tailored user experience across your platforms.

Discover more about these innovative features at [Kognetiks.com](https://kognetiks.com/wordpress-plugins/kognetiks-chatbot/ai-powered-chatbot-for-wordpress/) and [OpenAI's Playground](https://platform.openai.com/assistants).

**🔄 Choose Your Style: Embedded or Floating Chatbots**

Flexibility is key. Display your Chatbot as an embedded feature on pages or let it float across your site. With simple shortcodes, adapt the chatbot's presence to match your website's design and user needs.

**🔍 Knowledge Navigator: Unearthing Your Content's Essence**

At the heart of the plugin lies the Knowledge Navigator. This powerful tool delves deep into your website, mapping its architecture and content, enabling the chatbot to deliver precise and contextually relevant responses. Enhanced by TF-IDF analysis, the Knowledge Navigator ensures your content's unique keywords shine through, making interactions more meaningful.

**🗎 Conversation Logging**

Conversation Logging in this plugin records and stores chat interactions between users and the chatbot, providing valuable insights for enhancing user experience and chatbot performance. Visit the privacy policy on the Settings Support tab for details on data handling.

**🎭 Personalize with Custom Avatars**

Add a creative touch with customizable avatars. Reflect your site's personality through these visual companions, enhancing user engagement and adding a unique flair to your digital space.

**📊 Direct Traffic with Customizable Buttons**

Guide your visitors where you want them. Customizable buttons can link directly to specific pages, forms, or contact information, facilitating smoother navigation and enhanced user engagement.

🤖 Tailored Audience Engagement

Customize accessibility with three audience settings: All Audiences, Logged-in Only, or Visitors Only. Additionally, control presentation to specific audiences. Whether floating or embedded, tailor the chatbot's visibility for a seamless user experience across platforms.

**🤖 Why An Kognetiks Chatbot for WordPress?**

- **Natural Conversations:** Experience human-like interactions, thanks to OpenAI's Large Language Model API.
- **Always Available:** Provide round-the-clock assistance in various domains, from healthcare to education.
- **Seamless Integration:** Effortlessly bring your WordPress site to life with an easy-to-use plugin.

**✨ Supported Models from OpenAI**

- GPT-3.5 (gpt-3.5-turbo)
- GPT-4 (gpt-4 models)
- GPT-4 Turbo ('gpt-4-1106-preview')

**🚀 Elevate Your Website Experience**
A Kognetiks Chatbot for WordPress is more than just a plugin – it's a transformational tool for your website. With advanced AI technology at its core, it promises a unique and interactive experience for your visitors.

Get your Kognetiks Chatbot for WordPress today and redefine your WordPress site with intelligence and a personal touch.

**Note:** This plugin requires an API key from OpenAI. Obtain yours at [OpenAI API Keys](https://platform.openai.com/account/api-keys).

## 🌐 Features at a Glance
- **Quick Setup:** Integrate easily with API from companies like OpenAI.
- **Advanced AI Models:** Includes support for the latest GPT-4 Turbo from OpenAI.
- **Customizable Interfaces:** Choose between floating and embedded chatbot styles.
- **User-Friendly Settings:** Easily manage your API key and other settings.
- **Intelligent Design:** Smart collapsible chatbot for a cleaner website interface.
- **Engaging User Interaction:** Customize greetings and messages for a unique visitor experience.
- **Persistent Memory:** The chatbot remembers interactions, offering continuity across pages.
- **In-depth Content Analysis:** Knowledge Navigator ensures contextually relevant interactions.

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
