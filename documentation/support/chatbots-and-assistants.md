# Chatbots and Assistants

- In Settings > API/Model, you can select to use ChatGPT (i.e., original) or create a GPT Assistant in the https://platform.openai.com/playground/.

- ChatGPT is a conversational AI platform that uses natural language processing and machine learning algorithms to interact with users in a human-like manner.

- It is designed to answer questions, provide suggestions, and engage in conversations with users.

- ChatGPT is important because it can provide assistance and support to people who need it, especially in situations where human support is not available or is limited.

- Coupling the power of ChatGPT or a GPT Assistant with the flexibility of WordPress, **Kognetiks Chatbot for WordPress** is a plugin that allows you to effortlessly integrate OpenAI’s ChatGPT API into your website.

- This provides a powerful, AI-driven chatbot for enhanced user experience and personalized support.
For more information on using assistants, see https://beta.openai.com/docs/guides/assistants.

- Additional integration information can be found at https://kognetiks.com/wordpress-plugins/kognetiks-chatbot/chatbot-setup-and-configuration/.

# Using Multiple Custom Assistants

- In Settings > API/Model, you can select to use ChatGPT (i.e., original) or use one of two different custom Assistants you've created.

- As explain above, build your custom Assistants in the OpenAI Playground.

- Decide which one of your Assistants will be 'primary' and which one will be 'alternate'.

- Incorporate your Assistants in one of several different ways using the [chatbot_chatgpt] shortcode.

## Examples

Use one of the following formats to invoke the chatbot, or a primary or alternate Assistant:

- ```[chatbot]``` - Default values, floating style, uses OpenAI's ChatGPT

- ```[chatbot style="floating"]``` - Floating style, uses OpenAI's ChatGPT

- ```[chatbot style="embedded"]``` - Embedded style, uses OpenAI's ChatGPT

- ```[chatbot style="floating" assistant="primary"]``` - Floating style, GPT Assistant as set in Primary setting

- ```[chatbot style="embedded" assistant="alternate"]``` - Embedded style, GPT Assistant as set in Alternate setting

You can have an unlimited number of Assistants on you site if you reference them directly by their Assistant ID.

- ```[chatbot style="floating" assistant="asst_...123"]``` - Floating style, GPT Assistant specified

- ```[chatbot style="embedded" assistant="asst_...456"]``` - Embedded style, GPT Assistant specified
