# **Kognetiks Chatbot for WordPress** Plugin Documentation

**Kognetiks Chatbot for WordPress** is a plugin that allows you to effortlessly integrate OpenAI’s ChatGPT API or NVIDIA's NIM API into your website, providing a powerful, AI-driven chatbot for enhanced user experience and personalized support.

Conversational AI platforms - like those from OpenAI, NVIDIA, and others - use natural language processing and machine learning algorithms to interact with users in a human-like manner. They are designed to answer questions, provide suggestions, and engage in conversations with users. This is important because it can provide assistance and support to people who need it, especially in situations where human support is not available or is limited. It can also be used to automate customer service, reduce response times, and improve customer satisfaction. Moreover, these platforms can be used in various fields such as healthcare, education, finance, and many more.

The **Kognetiks Chatbot for WordPress** is powered by OpenAI, NVIDIA or others, via their APIs and Models to bring artificial intelligence to life within your WordPress website.

**Important Note:** This plugin requires an API key from OpenAI, NVIDIA or other AI platform vendors, to function correctly. You can obtain an API key by signing up at https://platform.openai.com/account/api-keys or https://build.nvidia.com/nim.

## What's new in Version 2.1.9

* **Bug Fix**: Removed extra line brakes after the chatbot's response.

## What's new in Version 2.1.8

* **NVIDIA NIM API Integration**: Added support for NVIDIA's NIM API to provide advanced conversational capabilities for the chatbot.
* **Assistant Management**: Resolved the issue with adding, updating and deleting Assistants when using Firefox browser.
* **Conversation Continuation**: Improved conversation continuity for visitors and logged-in users to ensure a seamless experience across sessions.
* **Additional Security**: Enhanced security to reduce vulnerabilities associated with assistant management.
* **Additional Security**: Enhanced security to reduce vulnerabilities associated with accessing chatbot support pages.

## What's new in Version 2.1.7

* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.1.6.

## What's new in Version 2.1.6

* **Message Limit Periods**: Added options to set message limits periods for visitors and logged-in users, from ```Hourly```, ```Daily```, ```Weekly```, up to ```Lifetime```.
* **Charset Fallback Adjustment**: Added fallback to ```utf8``` character set when ```utf8mb4``` is not supported, ensuring compatibility across different database configurations.
* **Suppress Footer Chatbots**: Suppress chatbot in the footer when the chatbot is embedded on the page.

## What's new in Version 2.1.5

* **Speech Recognition Integration**: Added support for speech recognition to enhance user interaction with the chatbot. Users can now speak to the chatbot, which will transcribe the speech into text for processing.
* **Knowledge Navigator Update**:  Updated the Knowledge Navigator algorithm to prioritize and return search results that match the highest number of input words first, ordered by relevance and recency, to provide the most relevant and recent links.
* **Bug Fix**: Removed unnecessary code that was causing a cannot modify header information in the chatbot-shortcode.php file.

## What's new in Version 2.1.4

* **Improved Table Formatting**: Enhanced the appearance of tables in chatbot responses for better readability.
* **Bug Fixes**: Resolved minor issues and bugs identified during the development process.

## What's new in Version 2.1.3

* **Remote Server Access**: The **Kognetiks Chatbot for WordPress** now includes the advanced feature to allow access to your assistants from remote servers.  Coupled with security measures to control and monitor remote access to your chatbots, you must enable the **Remote Widget Access** feature.  This will allow specific remote servers to interact with your chatbot(s) via an endpoint. To ensure that only authorized servers and chatbots can access your resources, the system uses a whitelisting mechanism that pairs domains with specific chatbot shortcodes.
* **Improving Math Handling**: Integrated code enhances chatbot’s ability to render complex mathematical expressions.
* **Bug Fixes**: Resolved minor issues and bugs identified during the development process.

## What's New in Version 2.1.2

* **Changed Script Load Order**: Adjusted the loading order of scripts to ensure that critical settings are defined before the main chatbot script executes, preventing incorrect style application.

## What's New in Version 2.1.1

* **Code Cleanup and Optimization**: Refined and optimized the codebase for improved performance and maintainability.
* **Variable Unification**: Standardized variable names across the project to ensure consistency and reduce potential errors.
* **User Experience Consistency**: Addressed inconsistencies in the chatbot experience between logged-in and non-logged-in users, ensuring a uniform experience.
* **Bug Fixes**: Resolved minor issues and bugs identified during the development process.

## What's New in Version 2.1.0

* **JavaScript Version Control**: Added JavaScript version control to help with cache busting.
* **Conversation Log CSV Export**: Added a check to determine if $value is not null before calling mb_convert_encoding to prevent PHP warnings.

## What's New in Version 2.0.9

* **Adjusted Module Name Conflict**: Renamed one module that had a name conflict with another vendor's plugin.
* **Reworked Conversation Continuity**: Improved the way the chatbot handles conversation continuity for visitors and logged-in users, ensuring a seamless experience across pages.
* **Alternate Attribution Message**: Allows for replacing the attribution message with 'Chatbot WordPress plugin by Kognetiks' with a text message of your choosing.
* **Refactored Inline Styles**: Moved inline styles to an external CSS file for better maintainability and separation of concerns.
* **floating-style CSS Class Rename**: Renamed the .floating-style CSS class to chatbot-floating-style to avoid conflicts with other plugins or themes.
* **embedded-style CSS Class Rename**: Renamed the .embedded-style CSS class to chatbot-embedded-style to avoid conflicts with other plugins or themes.
* **chatgptTitle CSS ID Rename**: Renamed the chatgptTitle CSS ID renamed to chatbot-chatgpt-title to avoid conflicts with other plugins or themes.
* **chatbot-user-text CSS Class Rename**: Renamed the user-text CSS class to chatbot-user-text to avoid conflicts with other plugins or themes.
* **bot-text CSS Class Rename**: Renamed the bot-text CSS class to chatbot-bot-text to avoid conflicts with other plugins or themes.

## What's New in Version 2.0.8

* **Logic Error Updated**: Corrected a logic error that was causing some visitors and logged-in users to lose their session continuity with the Assistants. This ensures a smoother and more consistent experience for all users.
* **Fixed Special Characters Display Issue**: Improved the way special characters are handled in chatbot names. Previously, the code was converting special characters like '&' into their HTML equivalents (e.g., '&' became '&').

## What's New in Version 2.0.7

* **Model Support**: The latest models available from the AI platform you choose and are dynamically added to model picklists.
* **Manage Chatbot Error Logs**: Added the ability to manage chatbot error logs, including the ability to download and delete logs. See Chatbot Settings > Tools. TIP: You must enable Diagnostics access the Tools tab. See Chatbot Settings > Messages > Messages and Diagnostics.
* **Revised Reporting Settings Layout**: Revised and refreshed the Reporting Settings page layout for better visualization. See Chatbot Settings > Reporting.
* **Conversation Continuation**: Added a setting to enable conversation continuation after returning to a page previously visited. See Chatbot Settings > Settings > Additional Settings.

## What's New in Version 2.0.6

* **Dynamic Shortcode**: Added support for dynamic shortcodes to allow for more flexible Assistant selection. Add all parameters to the shortcode, including the Assistant ID on the GTP Assistant tab. For example, `[chatbot-1]`.
* **Logic Error Updated**: Corrected a logic error that prevented visitors and logged-in users from interacting with Assistants.

## What's New in Version 2.0.5

* **Enhanced Assistant Management**: A new intuitive interface for managing all your chatbot Assistants in one place.

* **Assistant ID Integration**: Easily add Assistants developed in the OpenAI Playground using their unique ID.

* **Improved Shortcode Usage**: Tips for optimal placement and usage of the `[chatbot assistant="Common Name"]` shortcode.

* **Customizable Assistant Attributes**: Tailor each Assistant's settings such as Styling, Target Audience, Voice, Allow File Uploads, Allow Transcript Downloads, Show Assistant Name, Initial Greeting, Subsequent Greeting, Placeholder Prompt, and Additional Instructions.

* **Support Tab**: Reverted the "Support" tab to correctly display the plugin's support documentation overview.

* **Embedded Chatbot Formatting Updated**: Added a closing `</div>` tag to the embedded chatbot to ensure proper formatting.

* **Force Page Reload on Conversation Cleared**: Added an option to force a page reload when the conversation is cleared.

* **Knowledge Navigator Analysis**: Moved the Knowledge Navigator Analysis for export to the bottom of the Knowledge Navigator tab.

* **Custom Buttons Expanded**: Now supports up to four custom buttons, available on floating only, embedded only, or on both chatbot styles.

## Quick Start

- [Overview](support/overview.md)

- [Getting Started](support/getting-started.md)

- [Official Sites](support/official-sites.md)

- [Frequently Asked Questions](support/faqs.md)

## Sections

- [General](settings/settings.md)

- [API/ChatGPT Settings](api-chatgpt-settings/api-chatgpt-model-settings.md)

- [API/NVIDIA Settings](api-nvidia-settings/api-nvidia-model-settings.md)

- [Assistants](assistants/manage-assistants.md)

- [Avatars](avatars/avatars.md)

- [Appearance](appearance/appearance.md)

- [Buttons](buttons/buttons.md)

- [Knowledge Navigator](knowledge-navigator/knowledge-navigator.md)

- [Analysis](analysis/analysis.md)

- [Reporting](reporting/reporting.md)

- [Messages](messages/messages.md)

- [Tools](tools/tools.md)

## Support

- [How the Kognetiks Chatbot Works](support/how-it-works.md)

- [Chatbots and Assistants](support/chatbots-and-assistants.md)

- [Conversation Logging and History](support/conversation-logging-and-history.md)

- [API Key Safety and Security](support/api-key-safety-and-security.md)

- [Diagnostics - For Developers](support/diagnostics.md)

## Demos

- Coming Soon

## Notice

While AI-powered applications strive for accuracy, they can sometimes make mistakes. We recommend that you and your users verify critical information to ensure its reliability.

## Disclaimer

OpenAI, ChatGPT, and their respective trademarks are registered trademarks of OpenAI. NVIDIA, NIM, and their respective trademarks are registered trademarks of NVIDIA. Anthropic, Claude, and their respective trademarks are registered trademarks of Anthropic. Kognetiks is an independent entity and is neither affiliated with, endorsed by, nor sponsored by OpenAI, NVIDIA, or Anthropic.

---

* **[Back to the Overview](/overview.md)**
