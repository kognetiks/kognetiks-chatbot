# **Kognetiks Chatbot** Plugin Documentation

**Kognetiks Chatbot** is a plugin that allows you to effortlessly integrate conversational chat from OpenAI, Azure, Mistral, NVIDIA, Anthropic, DeepSeek, or local models using open-source servers into your website, providing a powerful, AI-driven chatbot for enhanced user experience and personalized support.

Conversational AI platforms - like those from OpenAI, Azure, Mistral, NVIDIA, Anthropic, DeepSeek, local AI servers, and others - use natural language processing and machine learning algorithms to interact with users in a human-like manner. They are designed to answer questions, provide suggestions, and engage in conversations with users. This is important because it can provide assistance and support to people who need it, especially in situations where human support is not available or is limited. It can also be used to automate customer service, reduce response times, and improve customer satisfaction. Moreover, these platforms can be used in various fields such as healthcare, education, finance, and many more.

The **Kognetiks Chatbot** is powered by OpenAI, Azure, Mistral, NVIDIA, Anthropic, DeepSeek, or other AI platforms, via their APIs and Models to bring artificial intelligence to life within your WordPress website.

## External Services

The **Kognetiks Chatbot** plugin relies on external AI services to provide chatbot functionality. It sends user queries and related data to a third-party AI provider for processing and response generation. By using this plugin, you agree to abide by each service's terms of service and privacy policy:

- **OpenAI**: [Terms of Use](https://platform.openai.com/terms) | [Privacy Policy](https://openai.com/policies/privacy-policy/)
- **NVIDIA**: [Terms of Use](https://www.nvidia.com/en-us/about-nvidia/nv-accounts/) | [Privacy Policy](https://www.nvidia.com/en-us/about-nvidia/privacy-policy/)
- **Anthropic**: [Terms of Service](https://www.anthropic.com/legal/consumer-terms) | [Privacy Policy](https://docs.anthropic.com/en/docs/legal-center/privacy)
- **DeepSeek**: [Terms of Use](https://chat.deepseek.com/downloads/DeepSeek%20User%20Agreement.html) | [Privacy Policy](https://chat.deepseek.com/downloads/DeepSeek%20Privacy%20Policy.html)
- **Mistral**: [Terms of Service](https://mistral.ai/terms#terms-of-service) | [Privacy Policy](https://mistral.ai/terms#privacy-policy)
- **Google**: [Terms of Use](https://ai.google.dev/gemini-api/terms) | [Privacy Policy](https://policies.google.com/privacy)
- **JAN.AI**: [About](https://jan.ai/about) | [Privacy Policy](https://jan.ai/docs/privacy-policy)

**IMPORTANT**:

- This plugin requires an API key from OpenAI, NVIDIA, Anthropic, DeepSeek, Google, Mistral to function. Without an API key, the chatbot cannot process user queries.

- Obtain API keys here:

   - [OpenAI API Keys](https://platform.openai.com/account/api-keys)
   - [Azure API Keys](https://azure.microsoft.com/en-us/pricing/purchase-options/azure-account?icid=ai-services)
   - [NVIDIA API Keys](https://developer.nvidia.com/nim)
   - [Anthropic API Keys](https://www.anthropic.com/)
   - [DeepSeek API Keys](https://platform.deepseek.com/sign_in)
   - [Google API Keys](https://aistudio.google.com/api-keys)
   - [Mistral API Keys](https://console.mistral.ai/api-keys)
   
- By entering your API key from the AI provider of your choice and activating the chatbot, you:

   - Consent to sending user queries and related data to the selected AI provider for processing and response generation.
   - Agree to abide by the provider's terms of service, pricing, and privacy policy.
   - Acknowledge that your data, including text submitted by users, may be transferred to and processed by the AI platform in accordance with its privacy policy.

**NOTE**: You are responsible for any fees associated with the use of the selected AI platform. Be sure to review each provider's pricing and usage policies before proceeding.

## Introducing the Sentential Context Model - BETA FEATURE OFFERING

The **Kognetiks Chatbot** plugin now includes a novel feature: the Sentential Context Model.  This new **beta feature** allows the chatbot to generate intelligent responses by leveraging your website's content - no AI platform connection required.  It's perfect for localized use or content-focused applications, this feature makes the chatbot more versatile than ever.

## What's new in Version 2.4.5

### New Features
* **OpenAI Prompts (Responses API)**: Shortcode now supports prompt IDs (`pmpt_...`) in addition to assistant IDs (`asst_...`) for OpenAI Responses API usage.
* **Conversation Logging**: Added option to retain conversation logs indefinitely.

### Improvements
* **Documentation**: Updated documentation for OpenAI Prompts (Responses API) and Conversation Logging.

### Bug Fixes
* **PHP execution time**: Fixed timeouts on long-running API calls by temporarily adjusting and restoring `max_execution_time` for OpenAI Chat Completions and Assistants API requests.

* Information about past updates can be found [here](updates/updates.md).

---

## Quick Start

- [Overview](support/overview.md)

- [Getting Started](support/getting-started.md)

- [Official Sites](support/official-sites.md)

- [Frequently Asked Questions](support/faqs.md)

---

## 🧱 Core Setup  
*Make it work*

- [General](settings/settings.md)  
- [Messages](messages/messages.md)

---

## 🤖 AI Engines & Models  
*Choose how the chatbot thinks*

- [API / ChatGPT Settings](api-chatgpt-settings/api-chatgpt-model-settings.md)  
- [API / Azure OpenAI Settings](api-azure-openai-settings/api-azure-openai-model-settings.md)  
- [API / NVIDIA Settings](api-nvidia-settings/api-nvidia-model-settings.md)  
- [API / Anthropic Settings](api-anthropic-settings/api-anthropic-model-settings.md)  
- [API / DeepSeek Settings](api-deepseek-settings/api-deepseek-model-settings.md)  
- [API / Google Settings](api-google-settings/api-google-model-settings.md)  
- [API / Mistral Settings](api-mistral-settings/api-mistral-model-settings.md)  
- [API / Local Server Settings](api-local-settings/api-local-model-settings.md)

---

## 🧠 Assistants & Knowledge  
*What the chatbot knows*

- [Assistants](assistants/manage-assistants.md)
- [OpenAI's Responses API](api-openai-responses-api/prompt-agent-build-and-deploy-guide.md)
- [Knowledge Navigator](knowledge-navigator/knowledge-navigator.md)  
- [Knowledge Navigator Analysis](knowledge-navigator/knowledge-navigator-analysis.md)

---

## 🎨 Experience & Interface  
*How it looks and behaves for visitors*

- [Avatars](avatars/avatars.md)  
- [Appearance](appearance/appearance.md)  
- [Buttons](buttons/buttons.md)

---

## 👀 Visibility & Oversight  
*What’s happening and when it matters*

- [Dashboard Widget](dashboard/dashboard.md)  
- [Reporting](reporting/reporting.md)  
- [Insights](analytics/analytics.md)  
- Conversation Digest *(configured within Reporting & Insights)*

---

## 🛠️ Utilities & Management  
*Advanced and supporting tools*

- [Tools](tools/tools.md)

---

## 🔐 Premium Capabilities

Kognetiks Premium is designed to help you **stay aware**, **act with confidence**, and **demonstrate real value** from your chatbot — without constant monitoring.

- **🧠 Insights & Reporting**  
  Understand how conversations are performing over time, identify patterns, and spot when attention is needed.  
  → [Insights & Reporting Overview](analytics-package/analytics-package.md)

- **📧 Conversation Digest Email**  
  Stay informed automatically with scheduled summaries of new chatbot conversations delivered to your inbox.  
  → [Conversation Digest Email](conversation-digest-email.md)

- **💎 Proof of Value Reports Email**  
  Turn chatbot activity into clear, executive-ready summaries that demonstrate impact, performance, and ROI.  
  → [Proof of Value Reports Email](proof-of-value-reports-email.md)

---

## 🧩 Premium Subscription & Support

- **🎧 Premium Support**  
  Priority assistance, guidance, and troubleshooting for Premium users running production chatbots.  
  → [Premium Support](premium-support/premium-support.md)


---

## Functional Details

- [Functional Details](specs/functional-details.md)

---

## Beta Support

- [Enabling Beta Features](beta-features/beta-features.md)

- [API/Transformer Settings](api-transformer-settings/api-transformer-model-settings.md) **BETA FEATURE**

--- 

## Connect with Support

- [How the Kognetiks Chatbot Works](support/how-it-works.md)

- [Chatbots and Assistants](support/chatbots-and-assistants.md)

- [Conversation Logging and History](support/conversation-logging-and-history.md)

- [API Key Safety and Security](support/api-key-safety-and-security.md)

- [Diagnostics - For Developers](support/diagnostics.md)

---

## Notice

While AI-powered applications strive for accuracy, they can sometimes make mistakes. We recommend that you and your users verify critical information to ensure its reliability.

## Disclaimer

WordPress, OpenAI, ChatGPT, NVIDIA, NIM, Anthropic, Claude, DeepSeek, Google, Mistral, Azure and related trademarks are the property of their respective owners. Kognetiks is an independent entity and is not affiliated with, endorsed by, or sponsored by WordPress Foundation, OpenAI, NVIDIA, Anthropic, DeepSeek, Google or Mistral.

---

* **[Back to the Overview](/overview.md)**
