# Kognetiks Chatbot - Functional Specification

## Overview

The **Kognetiks Chatbot** is a comprehensive WordPress plugin that integrates advanced AI capabilities into WordPress websites. It provides intelligent conversational experiences through multiple AI platforms, offering both cloud-based and local AI solutions for enhanced visitor engagement, customer support, and interactive assistance.

**Version:** 2.4.0  
**License:** GPLv3 or later  
**WordPress Compatibility:** Tested up to WordPress 6.9

## Core Functionality

### AI Platform Integration

The plugin supports multiple AI platforms, allowing users to choose the most suitable option for their needs:

#### Supported AI Platforms

1. **OpenAI**
   - Models: GPT-3.5-turbo, GPT-4, GPT-4-turbo, GPT-4o, GPT-4o-mini, and variants
   - Capabilities: Text generation, image generation (DALL-E), speech synthesis (TTS), speech recognition (Whisper)
   - API Endpoint: https://api.openai.com/v1/

2. **Anthropic Claude**
   - Models: Claude-3-5-sonnet-latest, Claude-3-opus, Claude-3-sonnet, Claude-3-haiku
   - Capabilities: Advanced text generation with safety features
   - API Endpoint: https://api.anthropic.com/

3. **Azure OpenAI**
   - Models: GPT-3.5-turbo, GPT-4, GPT-4-turbo variants
   - Capabilities: Text generation, image generation, speech synthesis
   - Integration with Microsoft Azure cloud services

4. **DeepSeek**
   - Models: DeepSeek-chat, DeepSeek-reasoner (DeepSeek-R1)
   - Capabilities: Text generation, reasoning capabilities
   - API Endpoint: https://api.deepseek.com/

5. **Google Gemini**
   - Models: gemini-2.0-flash, gemini-3.0-flash, gemini-flash-latest and variants
   - Capabilities: Text generation
   - API Endpoint: https://generativelanguage.googleapis.com/v1beta 

6. **Mistral**
   - Models: Mistral-7B-Instruct, Mistral-8x7B-Instruct, Mistral-8x22B-Instruct
   - Capabilities: Text generation, agent functionality
   - API Endpoint: https://api.mistral.ai/
   
7. **NVIDIA NIM (NVIDIA Inference Microservices)**
   - Models: Various NVIDIA-optimized models including Llama variants
   - Capabilities: High-performance text generation
   - Optimized for NVIDIA hardware

8. **Local Server (JAN.AI)**
   - Models: Various open-source models (Llama, Mistral, etc.)
   - Capabilities: Local processing, privacy-focused
   - No external API calls required

9. **Markov Chain (Beta)**
   - Capabilities: Basic text generation using statistical models
   - No external API required

10. **Transformer Models**
   - Models: lexical-context-model, sentential-context-model, sentential-context-model-lite
   - Capabilities: Local transformer-based text generation
   - No external API required

### Display Styles

The chatbot supports two primary display styles:

1. **Floating Style**
   - Appears as a floating widget on the page
   - Can be positioned in corners or fixed locations
   - Minimizable/maximizable interface
   - Mobile-responsive design

2. **Embedded Style**
   - Integrated directly into page content
   - Appears as part of the page layout
   - Suitable for dedicated chatbot pages or sections

### Shortcode Implementation

The plugin uses WordPress shortcodes for easy integration:

**Basic Shortcodes:**
- `[chatbot]` - Default floating chatbot
- `[chatbot style="floating"]` - Explicit floating style
- `[chatbot style="embedded"]` - Embedded style

**Advanced Shortcodes:**
- `[chatbot style="floating" model="gpt-4"]` - Specific AI model
- `[chatbot style="embedded" assistant="asst_xxxxxxxxxxxxxxxxxxxxxxxx"]` - Custom assistant
- `[chatbot style="floating" audience="logged-in"]` - Audience targeting
- `[chatbot style="embedded" prompt="How can I help you?"]` - Initial prompt

**Model-Specific Shortcodes:**
- `[chatbot style="embedded" model="dall-e-3"]` - Image generation
- `[chatbot style="embedded" model="tts-1-1106"]` - Text-to-speech
- `[chatbot style="embedded" model="whisper-1"]` - Speech recognition

## Advanced Features

### Assistant Management

The plugin supports OpenAI Assistants for enhanced functionality:

- **Unlimited Assistants**: Deploy multiple assistants with unique IDs
- **Custom Instructions**: Tailor assistant behavior with specific instructions
- **File Upload Support**: Assistants can process uploaded files
- **Thread Management**: Maintain conversation context across sessions
- **Assistant Attributes**: Configure temperature, token limits, and other parameters

### Knowledge Navigator

An intelligent content analysis system that enhances chatbot responses:

- **Website Content Analysis**: Scans posts, pages, products, and custom post types
- **TF-IDF Analysis**: Identifies relevant keywords and content relationships
- **Scheduled Updates**: Automatic content scanning (hourly, daily, weekly)
- **Enhanced Responses**: Provides contextually relevant links to site content
- **Custom Post Type Support**: Works with WooCommerce products and other custom types

### Analytics and Reporting

Comprehensive analytics package for monitoring chatbot performance:

- **Conversation Statistics**: Track user interactions and engagement
- **Sentiment Analysis**: Analyze user sentiment in conversations
- **Token Usage Tracking**: Monitor API usage and costs
- **Dashboard Widget**: Real-time statistics in WordPress admin
- **Data Export**: CSV export of conversation and interaction data
- **Performance Metrics**: Response times, success rates, error tracking

### User Experience Features

- **Personalized Greetings**: Dynamic greetings using WordPress user data
- **Custom Avatars**: Upload custom avatars or choose from seasonal options
- **Audience Targeting**: Show chatbot to all users, logged-in users, or visitors only
- **Message Limits**: Set daily/hourly/weekly/lifetime limits for users
- **Conversation Continuity**: Maintain context across page navigation
- **Mobile Optimization**: Responsive design for all device types

### Security and Privacy

- **API Key Encryption**: Secure storage of API credentials
- **Conversation Locking**: Prevents duplicate requests and race conditions
- **Input Sanitization**: XSS protection and data validation
- **Remote Access Control**: Whitelist specific domains for remote widget access
- **Data Retention Settings**: Configurable conversation log retention periods

## Configuration Options

### General Settings

- **AI Platform Selection**: Choose primary AI provider
- **Chatbot Name**: Customize chatbot identity
- **Start Status**: Configure initial display state
- **Message Limits**: Set interaction limits per user type
- **Timeout Settings**: Configure request timeout values

### API Configuration

Each AI platform requires specific configuration:

- **API Keys**: Secure credential management
- **Model Selection**: Choose specific models for different tasks
- **Rate Limiting**: Configure request limits and retry logic
- **Endpoint Configuration**: Custom API endpoints for enterprise use

### Appearance Customization

- **CSS Customization**: User-defined stylesheet support
- **Color Schemes**: Background, text, and accent colors
- **Font Settings**: Typography customization
- **Dimensions**: Width, height, and positioning options
- **Icon Customization**: Custom send, upload, and control icons

### Advanced Features

- **Voice Settings**: Text-to-speech configuration
- **File Upload**: Enable/disable file processing
- **Transcript Downloads**: Allow users to download conversation history
- **Remote Widget Access**: Enable cross-domain chatbot access
- **Diagnostic Tools**: Error logging and troubleshooting utilities

## Technical Requirements

### WordPress Requirements

- **WordPress Version**: 5.0 or higher
- **PHP Version**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **Memory**: Minimum 128MB PHP memory limit
- **Storage**: Varies based on conversation logging settings

### External Dependencies

- **API Keys**: Required for cloud-based AI platforms
- **Internet Connection**: Required for API calls (except local server mode)
- **HTTPS**: Recommended for secure API communication

### Database Schema

The plugin creates several database tables:

- **Conversation Logs**: Stores chat interactions
- **Knowledge Navigator Data**: Content analysis results
- **Analytics Data**: Performance and usage metrics
- **Assistant Threads**: OpenAI Assistant conversation threads

## Installation and Setup

### Installation Process

1. **Plugin Upload**: Upload plugin files to WordPress
2. **Activation**: Activate through WordPress admin
3. **API Configuration**: Enter API keys for chosen AI platform
4. **Basic Settings**: Configure chatbot name, greetings, and display options
5. **Shortcode Placement**: Add shortcodes to desired pages/posts
6. **Knowledge Navigator**: Run initial content scan (optional)

### Initial Configuration Steps

1. Choose AI platform in General Settings
2. Enter API key in corresponding API settings tab
3. Configure chatbot appearance and behavior
4. Set up Knowledge Navigator (optional)
5. Test chatbot functionality
6. Deploy shortcodes to live pages

## Use Cases

### Business Applications

- **Customer Support**: 24/7 automated customer assistance
- **Lead Generation**: Qualify and capture potential customers
- **FAQ Automation**: Answer common questions automatically
- **Product Recommendations**: Suggest products based on user queries

### Educational Applications

- **Student Support**: Answer academic questions
- **Course Information**: Provide course details and enrollment info
- **Learning Assistance**: Help with homework and assignments

### E-commerce Applications

- **Product Support**: Answer product-related questions
- **Order Assistance**: Help with order status and returns
- **Shopping Guidance**: Assist with product selection

### Content Applications

- **Content Discovery**: Help users find relevant content
- **Site Navigation**: Guide users through website sections
- **Information Retrieval**: Answer questions about site content

## Support and Documentation

### Documentation Structure

- **Overview**: General plugin information
- **Settings**: Detailed configuration guides
- **API Documentation**: Platform-specific setup instructions
- **Assistants**: OpenAI Assistant configuration
- **Analytics**: Reporting and analytics features
- **Support**: Troubleshooting and help resources

### Support Channels

- **Plugin Support**: https://kognetiks.com/plugin-support/
- **Documentation**: Comprehensive online documentation
- **Diagnostic Tools**: Built-in troubleshooting utilities
- **Error Logging**: Detailed error tracking and reporting

## Compliance and Legal

### Data Handling

- **Conversation Logging**: Configurable data retention
- **Privacy Controls**: User data protection measures
- **GDPR Compliance**: European data protection compliance
- **Data Export**: User data export capabilities

### External Service Agreements

Users must agree to terms of service for each AI platform:

- **OpenAI**: Terms of Use and Privacy Policy
- **Anthropic**: Anthropic Terms of Service and Privacy Policy
- **Azure OpenAI**: Microsoft Terms and Privacy Statement
- **DeepSeek**: DeepSeek User Agreement and Privacy Policy
- **Google**: Google User Ageement and Privacy Policy
- **Mistral**: Mistral Terms of Service and Privacy Policy
- **NVIDIA**: NVIDIA Terms and Privacy Policy
- **JAN.AI**: JAN.AI About and Privacy Policy

## Version History

### Recent Updates (Version 2.3.9)

* **Google API**: Added support for Google's API to provide advanced conversational capabilities for the chatbot.
* **Conversation Digest**: Added a new feature to send a digest of the conversation to the site admin via email.
* **Conversation History**: Improved the conversation history display to show the conversation history for the logged-in user.
* **Bug Fixes**: Resolved minor issues and bugs identified after release of version 2.3.8.

### Key Milestones

- **Version 2.3.8**: Google Gemini API integration
- **Version 2.3.0**: Mistral API integration
- **Version 2.2.9**: Message count display
- **Version 2.2.6**: Azure OpenAI and Local Server support
- **Version 2.2.2**: DeepSeek API integration
- **Version 2.2.1**: Anthropic API integration
- **Version 2.1.8**: NVIDIA NIM API integration

## Summary

The Kognetiks Chatbot provides a comprehensive, flexible solution for integrating AI-powered conversational experiences into WordPress websites. With support for multiple AI platforms, advanced features like Knowledge Navigator and Analytics, and extensive customization options, it offers both technical sophistication and user-friendly implementation for businesses, educators, and content creators seeking to enhance their website's interactive capabilities.

The plugin's modular architecture allows for easy maintenance and future enhancements, while its comprehensive documentation and support resources ensure successful deployment and ongoing management.

---

- **[Back to the Overview](/overview.md)**
