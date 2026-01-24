# Past Updates

## What's new in Version 2.4.3

* **Bug Fixes**: Minor bug fixes and improvements.

## What's new in Version 2.4.2

* **Bug Fixes**: Minor bug fixes and improvements.

## What's new in Version 2.4.1

### New Features
* **Automated Email Reports**: Added Conversation Digest and Proof of Value Reports email functionality with scheduling options
* **Enhanced Fallback Pattern Matching**: Improved detection of unanswered questions by including human messages with clarification/confusion patterns in top unanswered questions query
* **Unsaved Changes Modal**: Added modal to warn users about unsaved changes in settings
* **Insights Tab UI Enhancements**: Minor UI upgrades to the Insights tab for better user experience

### Improvements
* **Email Scheduling**: Enhanced scheduling system for Conversation Digest and Proof of Value emails with proper enable/disable functionality
* **Query Execution**: Added detailed comments and debug logging for SQL query execution in fallback pattern matching
* **Internationalization**: Added global translations for fallback pattern values to various language files
* **Terminology Update**: Renamed "analytics" to "insights" throughout the codebase for better clarity
* **Documentation**: Updated documentation including proof of value email examples and conversation digest email guides
* **Settings UI**: Added links to view reports, "remind me later", and dismiss buttons for better user workflow

### Bug Fixes
* Fixed options saving functionality in settings
* Fixed unscheduling of automated emails when disabled
* Corrected footer content for free and premium tiers in Proof of Value reports
* Removed redundant sections from Proof of Value reporting

## What's new in Version 2.4.0

### New Features
* **Ukrainian Language Support**: Added comprehensive Ukrainian language translation support (`chatbot-globals-uk.php`) with 131+ translated strings for chatbot-user interaction messages, making the plugin accessible to Ukrainian-speaking users.

### Bug Fixes
* **Headers Already Sent Error**: Fixed "headers already sent" errors that were causing issues with assistant API calls and conversation handling.
* **Infinite Retry Loop**: Resolved infinite retry loop issue with OpenAI and Azure assistants by implementing proper retry logic and error handling in assistant API calls.
* **Translation File Fallback**: Improved graceful fallback handling when translation files are missing, preventing errors and ensuring the plugin continues to function properly even if translation files are unavailable.
* **System Busy Processing Error**: Enhanced error handling for "system is busy processing" scenarios to provide better user feedback and prevent request failures.
* **PHP 8.5.0 Compatibility**: Fixed deprecated error messages and warnings after upgrading to PHP 8.5.0, ensuring full compatibility with the latest PHP version.
* **Duplicate Request Detection**: Fixed duplicate request detection errors and clear conversation failures by properly clearing message UUID transients and improving session validation across all API providers (OpenAI, Azure, Anthropic, DeepSeek, Google, Mistral, NVIDIA, Kognetiks, Local, and more).
* **SQL Injection Prevention**: Resolved SQL injection vulnerabilities in transient cleanup functions by implementing proper sanitization and prepared statements.

### Improvements
* **Token Usage Display**: Adjusted Token Usage font styling in the dashboard widget to eliminate overflow issues and improve readability.
* **Documentation Updates**: 
  - Updated OpenAI documentation links for assistants and max tokens settings to reflect current API documentation.
  - Added missing documentation links to parent pages across all API settings documentation.
  - Fixed formatting issues by correcting triple backwards apostrophes to single apostrophes throughout documentation.
  - Updated functional details documentation with improved clarity and accuracy.
  - Minor corrections to README.md file for better accuracy.
* **Code Quality**: Improved code organization and maintainability across multiple files.

## What's new in Version 2.3.9

* **Google API**: Added support for Google's API to provide advanced conversational capabilities for the chatbot.
* **Conversation Digest**: Added a new feature to send a digest of the conversation to the site admin via email.
* **Conversation History**: Improved the conversation history display to show the conversation history for the logged-in user.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.3.8.

## What's new in Version 2.3.8

* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.3.7.

## What's new in Version 2.3.7

* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.3.6.

## What's new in Version 2.3.6

* **Insights**: Comprehensive dashboard providing conversation statistics, sentiment analysis, engagement metrics, and token usage tracking to help optimize chatbot performance and user experience.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.3.5.

## What's new in Version 2.3.5

* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.3.4.

## What's new in Version 2.3.4

* **Conversation Locking**: Implemented conversation locking mechanism to prevent multiple simultaneous requests and improve user experience.
* **Input Processing**: Enhanced input processing to handle impatient user interactions more gracefully.
* **Dashboard Widget**: Fixed dashboard widget errors and improved LaTeX rendering functionality.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.3.3.

## What's new in Version 2.3.3

* **Local Server**: Updated support for the latest release of JAN.AI local server v0.6.8. See [JAN.AI](https://jan.ai/) for more information.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.3.2.

## What's new in Version 2.3.2

* **Local Server**: Fixed status check message for local server.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.3.1.

## What's new in Version 2.3.1

* **Mistral API Websearch**: Added support for realtime websearch using a Mistral Assistant.
* **LocalServer**: JAN.AI requires an API key for local models which is set when server is started.
* **Error Logging**: For developers improved error logging for troubleshooting and debugging.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.3.0.

## What's new in Version 2.3.0

* **Mistral API**: Added Settings and API for Mistral's API for chat completions and agents.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.2.9.

## What's new in Version 2.2.9

* **Display Message Count**: Added a setting to display the message count in the chatbot's response, such as `(29/50)`, i.e., 29 prompts out of 50 limited, to help visitors and logged-in users understand how many exchanges they have had with the chatbot.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.2.8.

## What's new in Version 2.2.8

* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.2.7.

## What's new in Version 2.2.7

* **Conversation Transcript**: Added a new feature to send the conversation transcript to site admins when OpenAI Assistants are instructed to do so (see Support tab in Settings).
* **Dashboard Widget**: Added a dashboard widget to display chatbot statistics and token usage in the WordPress admin dashboard.
* **Custom Post Types**: Added support for custom post types to the Knowledge Navigator.
* **Performance Improvements**: Minimized unnecessary calls to the database to improve performance.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.2.6.

## What's new in Version 2.2.6

* **Azure OpenAI**: Added support for the Azure OpenAI API to provide advanced conversational capabilities for the chatbot.
* **Local Server**: Added support for the JAN.AI local server, enabling users to run AI models on their own servers for enhanced control and flexibility.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.2.5.

## What's New in Version 2.2.5

* **Enhanced Context for Assistants**: Added option to enhance Assistant context with site content for improved responses. When enabled, this feature allows the chatbot to pull information from your site's existing content, such as posts, pages, products, and other custom post types, to provide richer and more accurate answers.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.2.4.

## What's New in Version 2.2.4

* **Improved Knowledge Navigator**: Enhanced the Knowledge Navigator to provide more accurate and relevant responses based on your site's content.
* **Glyph Rendering**: Added support to enable/disable glyph rendering for the chatbot's response, enabled by default.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.2.3.

## What's new in Version 2.2.2

* **DeepSeek Reasoner**: Added a select for DeepSeek's Reasoner model (which points to the new DeepSeek-R1 model) supporting advanced conversational capabilities for the chatbot.
* **Response Formating**: Improved the formatting of chatbot responses to ensure better readability and clarity.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.2.2.

## What's new in Version 2.2.1

* **Anthropic API Integration**: Added support for Anthropic's API to provide advanced conversational capabilities for the chatbot.
* **NVIDIA Settings**: Added support documentation for the NVIDIA API settings.
* **Sentential Context Model**: Added beta support for the Sentential Context Model, enabling response generation using your site's content without relying on external AI platforms.
* **Knowledge Navigator Update**: Added option to include post or page excerpts in chatbot responses when enhanced responses is enabled.
* **Documentation Updates**: Revised several section of the online documentation to align with current options and previous updates.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.2.0.

## What's new in Version 2.2.0

* **Rate Limit Exceeded Errors**: Added improved error handling for rate limit exceeded errors to retry the request after the delay specified by the API.

## What's new in Version 2.1.9

* **Bug Fixes**: Removed extra line breaks after the chatbot's response, among other minor issues identified after the release of version 2.1.8.

## What's new in Version 2.1.8

* **NVIDIA NIM API Integration**: Added support for NVIDIA's NIM API to provide advanced conversational capabilities for the chatbot.
* **Assistant Management**: Resolved the issue with adding, updating and deleting Assistants when using Firefox browser.
* **Conversation Continuation**: Improved conversation continuity for visitors and logged-in users to ensure a seamless experience across sessions.
* **Additional Security**: Enhanced security to reduce vulnerabilities associated with assistant management.
* **Additional Security**: Enhanced security to reduce vulnerabilities associated with accessing chatbot support pages.

## What's new in Version 2.1.7

* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.1.6.

## What's new in Version 2.1.6

* **Message Limit Periods**: Added options to set message limits periods for visitors and logged-in users, from `Hourly`, `Daily`, `Weekly`, up to `Lifetime`.
* **Charset Fallback Adjustment**: Added fallback to `utf8` character set when `utf8mb4` is not supported, ensuring compatibility across different database configurations.
* **Suppress Footer Chatbots**: Suppress chatbot in the footer when the chatbot is embedded on the page.

## What's new in Version 2.1.5

* **Speech Recognition Integration**: Added support for speech recognition to enhance user interaction with the chatbot. Users can now speak to the chatbot, which will transcribe the speech into text for processing.
* **Knowledge Navigator Update**:  Updated the Knowledge Navigator algorithm to prioritize and return search results that match the highest number of input words first, ordered by relevance and recency, to provide the most relevant and recent links.
* **Bug Fix**: Removed unnecessary code that was causing a cannot modify header information in the chatbot-shortcode.php file.

## What's new in Version 2.1.4

* **Improved Table Formatting**: Enhanced the appearance of tables in chatbot responses for better readability.
* **Bug Fixes**: Resolved minor issues and bugs identified during the development process.

## What's new in Version 2.1.3

* **Remote Server Access**: The **Kognetiks Chatbot** now includes the advanced feature to allow access to your assistants from remote servers.  Coupled with security measures to control and monitor remote access to your chatbots, you must enable the **Remote Widget Access** feature.  This will allow specific remote servers to interact with your chatbot(s) via an endpoint. To ensure that only authorized servers and chatbots can access your resources, the system uses a whitelisting mechanism that pairs domains with specific chatbot shortcodes.
* **Improving Math Handling**: Integrated code enhances chatbot's ability to render complex mathematical expressions.
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
* **Alternate Attribution Message**: Allows for replacing the attribution message with 'Chatbot plugin by Kognetiks' with a text message of your choosing.
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

---

* **[Back to the Overview](/overview.md)**