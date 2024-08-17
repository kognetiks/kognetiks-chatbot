# FAQs

## What is Kognetiks Chatbot for WordPress?

Kognetiks Chatbot for WordPress is an AI-powered conversational agent developed to give content creators using WordPress access to new pre-trained AI models developed by OpenAI, such as DALL-E, Codex, GPT-3, and GPT-4. The OpenAI API is designed to add state-of-the-art AI capabilities to virtually any task available in the English language. Kognetiks Chatbot for WordPress is built on top of the GPT (Generative Pre-trained Transformer) architecture, which is a type of deep learning model widely used for natural language processing tasks. ChatGPT is designed to generate human-like responses and engage in interactive conversations with users. ChatGPT is trained on a vast amount of text data from the internet, allowing it to learn patterns, language structures, and context. It can understand and generate coherent and contextually relevant responses, making it suitable for various conversational applications. However, it’s important to note that ChatGPT has limitations. It may sometimes produce incorrect or nonsensical answers, and it can be sensitive to slight changes in input phrasing, leading to inconsistent responses. OpenAI continues to work on improving the system and addressing these limitations.

## Can I have more than one chatbot on the same page?

No, you should **not** put more than one chatbot shortcode on the same page or post.

For now, it will **not** work as expected if you put a floating chatbot using the ```[chatbot style=floating]``` in the footer **and** an embedded chatbot ```[chatbot style=embedded```] on the page or post.

You can put as many different chatbot on different pages, as long as there is only one chatbot per page.

## How many Assistants can I have?

You can have one primary, one alternate, but as many Assistants as you want if you invoke them directly using the ID assigned when you created the Assistant on the OpenAI platform.

Use the following format to invoke the primary or alternate assistant:

- `[chatbot style="floating" assistant="primary"]` - Floating style, Assistant as set in Primary setting

- `[chatbot style="embedded" assistant="alternate"]` - Embedded style, Assistant as set in Alternate setting

Use the following format to invoke an assistant directly by its ID

- `[chatbot style="floating" assistant="asst_xxxxxxxxxxxxxxxxxxxxxxxx"]` - Floating style, Assistant as set in Assistant ID setting

- `[chatbot style="embedded" assistant="asst_xxxxxxxxxxxxxxxxxxxxxxxx"]` - Embedded style, Assistant as set in Assistant ID setting
Mix and match the style and assistant attributes to suit your needs.

**NOTE:** When using the 'embedded' style, it's best to put the shortcode in a page or post, **not** in a footer.

## What is Knowledge Navigator?

Knowledge Navigator is the smart explorer behind our Kognetiks Chatbot for WordPress plugin that’s designed to delve into the core of your website. Like a digital archaeologist, it embarks on an all-encompassing journey through your site’s pages, carefully following every internal link to get a holistic view of your content. Knowledge Navigator sifts through each page, extracting the essence of your content in the form of keywords and phrases, gradually building a meticulous, interactive map of your website’s architecture.

## How does Knowledge Navigator work?

Knowledge Navigator employs a clever technique known as TF-IDF (Term Frequency-Inverse Document Frequency) to unearth the keywords that really matter. The keywords are ranked by their TF-IDF scores, where the score represents the keyword’s relevance to your site. This score is a fine balance between the term’s frequency on your site and its inverse document frequency (which is essentially the log of total instances divided by the number of documents containing the term). In simpler words, it’s a sophisticated measure of how special a keyword is to your content.

## What is the output of Knowledge Navigator?

Both a detailed “results.csv” and “results.json” files are created, tucking away all the valuable information obtained in a dedicated ‘results’ directory within the plugin’s folder. The prime objective of Knowledge Navigator is to enable the Kognetiks Chatbot for WordPress plugin to have a crystal clear understanding of your website’s context and content. As a result, your chatbot will deliver responses that are not just accurate, but also fittingly contextual, thereby crafting a truly bespoke user experience. This all is powered by the advanced AI technology of OpenAI’s Large Language Model (LLM) API.

## How can I tell if the Knowledge Navigator is working?

After you select a schedule and save the settings, the Knowledge Navigator is run shortly thereafter (usually about 10 seconds later). The status will show initially as ‘in process’. After which the selected schedule (hourly, daily, weekly) is set and the Knowledge Navigator will run on that schedule until canceled. If you have installed a plugin like WP Cron you will find the crawl event amongst the other scheduled activities on your site. You can also visit the Knowledge Navigator tab on the plugin settings to see when the Knowledge Navigator last sifted through your content.

## How do I embed a Custom GPT Assistant in my website using Kognetiks Chatbot for WordPress?

If you’ve built a Custom GPT Assistant in OpenAI’s ChatGPT the URL (for example https://chat.openai.com/g/g-LnpnSZn02-ichimoku-insights) is not the “assistant ID” needed to work with Kognetiks Chatbot for WordPress. You’ll need to build your Custom GPT Assistant in the OpenAI Playground (at https://platform.openai.com/assistants). Once built there, you will see the Assistant ID below the name of your Assistant. It will start with “asst_” followed by upper- and lower-case letters and numbers, for example: asst_12AB34CD56EF78GH90IJ. Once you have this ID and installed the latest version of the Chatbot ChatGPT (at least version 1.6.9), navigate to Settings > API/Model, where you will see two configuration options. Set “Use Custom GPT Assistant ID” = “Yes” and enter your “asst_” ID in the “Custom GPT Assistant ID” field. Don’t forget to click “Save Settings” at the bottom of the screen. Return to the website where you’ve installed the shortcode for Kognetiks Chatbot for WordPress, [chatbot], and refresh the page, and your Custom GPT Assistant will now be embedded within your site.

## How do I access OpenAI’s GPT-4 model?

In an OpenAI help blog post this week (August 23, 2023), it appears that OpenAI has updated their terms for accessing the GPT-4 API. As of this writing, you will need to set up pre-paid billing by purchasing credits before accessing the GPT-4 API. You can read more [here](https://help.openai.com/en/articles/6825453-billing) and [here](https://help.openai.com/en/articles/6825453-billing) about instant access and prepaid billing from OpenAI. If you’re experiencing an error after enabling the Kognetiks Chatbot for WordPress this may resolve your issues.

## I've created an Assistant but the chatbot is responding generically.

First, make sure to set the ```Use GPT Assistant Id``` to ```Yes``` on GTP Assistant tab in the Chatbot settings.

Be sure to use a valid ```Primary GPT Assistant Id``` or ```Alternate GPT Assistant Id```.  Assistant IDs are similar to ```asst_gs8KtljqS7F62mjXicjxnAPg``` and found [here](https://platform.openai.com/assistants).

Sometimes caching is the problem.  If so, in the case of WP Engine hosting, you might allow the following:

Action: Set
Name: Cache-Control
Value: max-age=604800, must-revalidate
When: Only on successes

You can try using the cache-control header.  This setting controls how long browsers and intermediary caches store a copy of the resource before checking back with the server.  While it primarily affects the browser's caching behavior, it can also influence the caching policies of intermediary caches.

In the case of hosting on WP Engine, you would set this in the web rules section: [WP Engine Web Rules Engine](https://wpengine.com/support/web-rules-engine/#Header_Rules).

If you’re using a different hosting provider, check their documentation for similar cache-control settings.

### Diagnosing the Issue:

This issue could be caused by several factors, including caching plugins, theme conflicts, or differences in how WordPress handles logged-in vs. non-logged-in users. Here are a few potential reasons and solutions:

1. **Caching Plugins**:
   - **Issue**: Caching plugins like W3 Total Cache, WP Super Cache, or any other caching mechanism might serve cached pages to non-logged-in users. These cached pages may not process shortcodes dynamically as they do for logged-in users.
   - **Solution**: Exclude the pages with your shortcodes from being cached or configure the caching plugin to dynamically process these pages for non-logged-in users.

2. **Theme Conflicts**:
   - **Issue**: Some themes might handle shortcodes differently or have custom functions that alter the behavior of shortcodes based on the user’s logged-in status.
   - **Solution**: Test with a default WordPress theme like Twenty Twenty-One to see if the issue persists. If the problem resolves with a default theme, the issue likely lies within the custom theme’s functions.

3. **User Role Capabilities**:
   - **Issue**: Certain shortcode functions might be restricted to specific user roles or capabilities, which are not available to non-logged-in users.
   - **Solution**: Ensure that the shortcodes and their corresponding functions do not have role-based restrictions unless necessary. You can check the capabilities required for executing the shortcodes and adjust them accordingly.

4. **Session and Cookies**:
   - **Issue**: Some shortcodes may rely on session data or cookies, which can behave differently for logged-in and non-logged-in users.
   - **Solution**: Ensure that any session or cookie-based data is correctly handled for all users. You might need to review how sessions are initiated and maintained in your plugin.

5. **Custom Query Variables**:
   - **Issue**: If your shortcode relies on custom query variables, these might be stripped or not passed correctly for non-logged-in users due to URL rewriting or security plugins.
   - **Solution**: Use `add_query_var()` to register your custom query variables and ensure they are recognized by WordPress. This helps maintain custom variables across requests.

6. **Security Plugins**:
   - **Issue**: Security plugins may block or alter the behavior of certain queries or scripts for non-logged-in users.
   - **Solution**: Check the settings of any security plugins to see if they are restricting access to certain scripts or query parameters for non-logged-in users.

Here are a few steps to diagnose and potentially resolve the issue:

- **Disable Caching**: Temporarily disable any caching plugins and test the shortcodes.
- **Switch Themes**: Temporarily switch to a default WordPress theme and see if the issue persists.
- **Check User Capabilities**: Review and adjust any role or capability checks within your shortcodes.
- **Inspect Query Variables**: Ensure custom query variables are registered and handled properly.
- **Review Security Settings**: Check the settings of security plugins that might be blocking or altering requests.

By following these steps, you should be able to identify and address the root cause of the issue.

## How can I inspect the conversation logs to ensure the Assistant is being activated?

Follow these steps to enable conversation logging and inspect the logs:

1. **Enable Conversation Logging:**
    - Go to the Chatbot Settings.
    - Click on the Reporting tab.
    - Scroll to the bottom of the page.
    - Set ```Enable Conversation Logging``` to ```On```.
    - Click “Save Settings”.

2. **Refresh the Chatbot Page:**
    - Open the page where the chatbot resides.
    - Use ```CTRL-SHIFT-R``` to refresh the page.

3. **Test the Chatbot:**
    - Enter your prompt and wait for the response.

4. **Download and Inspect Conversation Data:**
    - Go back to the Chatbot Settings.
    - Click on the Reporting tab again.
    - Click ```Download Conversation Data```.

    You should notice that the ```Conversation items stored in your DB total NNNN rows``` where NNNN is the number of prompts and responses. When you click the ```Download Conversation Data```, you’ll be prompted to save the CSV to your local machine. Once downloaded, you should be able to open it with either Excel or Sheets or any other CSV reader.

5. **Check for Assistant Information:**
    - Ensure that columns G, H, and I in the CSV file are populated with your Assistant’s information.
    - Scroll down to the last entry.

    **Interpretation:**
    - If columns G, H, and I are filled with your assistant’s correct data (i.e., the Assistant ID and Assistant Name are correct), this indicates that the problem lies elsewhere.
    - If columns G, H, and I are blank, this indicates that the assistant is not correctly being invoked on your site.

    **Possible Issues:**
    - If the assistant is not invoked, it could be a server caching issue.
    - In some cases, such as with WP Engine, active installations have had to make a minor change to the way their server handles passed parameters on shortcodes. More information can be found [here](https://wpengine.com/support/web-rules-engine/#Header_Rules).

### More Information

See [Chatbots and Assistants](support/chatbots-and-assistants.md) for more details on using multiple Assistants.

# Support

## How do I obtain support for the Kognetiks Chatbot for WordPress Plugin?

Please use one of these resources to obtain support for the **Kognetiks Chatbot for WordPress** plugin.

- [Support @ Discord](https://discord.gg/nXRzxUKvya)

- [Support @ Kognetiks.com](https://kognetiks.com/plugin-support/)

- [Support @ WordPress.org](https://wordpress.org/support/plugin/chatbot-chatgpt/)

- [Support @ GitHub.com](https://github.com/kognetiks/kognetiks-chatbot/issues)

You can also contact support by visiting our support page [here](https://kognetiks.com/plugin-support/) and filling out the form.

---

- **[Back to the Overview](/overview.md)**
