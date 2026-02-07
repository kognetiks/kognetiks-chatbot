# Kognetiks Chatbot - Functional Specification

## Overview

The **Kognetiks Chatbot** is a comprehensive WordPress plugin that integrates advanced AI capabilities into WordPress websites. It provides intelligent conversational experiences through multiple AI platforms, offering both cloud-based and local AI solutions for enhanced visitor engagement, customer support, and interactive assistance.

**Version:** 2.4.5
**License:** GPLv3 or later  
**WordPress Compatibility:** Tested up to WordPress 6.9.1

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

**Dynamic Shortcodes:**
- `[chatbot-1]`, `[chatbot-2]`, etc. - Direct assistant access by number
- `[assistant-1]`, `[assistant-2]`, etc. - Alternative syntax (normalized to chatbot-n)
- `[agent-1]`, `[agent-2]`, etc. - Alternative syntax (normalized to chatbot-n)
- Automatically registered for each configured assistant

**Advanced Shortcodes:**
- `[chatbot style="floating" model="gpt-4"]` - Specific AI model
- ```[chatbot style="embedded" assistant="asst_xxxxxxxxxxxxxxxxxxxxxxxx"]``` - Custom assistant by ID
- `[chatbot style="embedded" assistant="primary"]` - Use primary assistant
- `[chatbot style="embedded" assistant="alternate"]` - Use alternate assistant
- `[chatbot style="floating" audience="logged-in"]` - Audience targeting (all, logged-in, visitors)
- `[chatbot style="embedded" prompt="How can I help you?"]` - Initial prompt (hot prompt)

**Hot Prompt Shortcodes:**
- `[chatbot prompt="What happened on this day in history?"]` - Start conversation with initial prompt
- Works with both floating and embedded styles
- Compatible with assistants

**Model-Specific Shortcodes:**
- `[chatbot style="embedded" model="dall-e-3"]` - Image generation
- `[chatbot style="embedded" model="tts-1-1106"]` - Text-to-speech
- `[chatbot style="embedded" model="whisper-1"]` - Speech recognition

**Conversation History Shortcodes:**
- `[chatbot_chatgpt_history]` - Display logged-in user's conversation history
- `[chatbot_conversation]` - Alternative syntax for conversation history
- `[chat_history]` - Shortest syntax for conversation history
- **Note**: Only available to logged-in users
- Displays conversations grouped by date with collapsible threads

## Advanced Features

### Assistant Management

The plugin supports OpenAI Assistants for enhanced functionality:

- **Unlimited Assistants**: Deploy multiple assistants with unique IDs
- **API Version Selection**: Choose between v1 and v2 Assistant APIs
  - v2 is the default (recommended)
  - v1 available for backward compatibility
  - See OpenAI Migration Guide for details
- **Custom Instructions**: Tailor assistant behavior with specific instructions
- **Additional Instructions**: Extra context for prompts
- **File Upload Support**: Assistants can process uploaded files (multiple file uploads supported)
- **Thread Management**: Maintain conversation context across sessions
- **Thread Retention**: Configurable conversation thread retention periods
  - Default: 36 hours
  - Maximum: 720 hours (30 days)
  - Maintains conversation context across sessions
  - Automatic cleanup of expired threads
- **Enhanced Context for Assistants**: Option to enhance Assistant context with site content
  - Pulls information from posts, pages, products, and custom post types
  - Provides richer and more accurate answers
  - Integrates with Knowledge Navigator data
  - Can include post/page excerpts in responses
- **Assistant Attributes**: Comprehensive configuration options
  - Max Prompt Tokens: Controls maximum prompt token usage
  - Max Completion Tokens: Controls maximum completion token usage
  - Temperature: Controls randomness (0-2)
  - Top P: Controls diversity via nucleus sampling (0-1)
  - Display Assistant Name: Show/hide assistant name in conversations

### Knowledge Navigator

An intelligent content analysis system that enhances chatbot responses:

- **Website Content Analysis**: Scans posts, pages, products, and custom post types
- **TF-IDF Analysis**: Identifies relevant keywords and content relationships
  - Configurable depth parameter based on content length
  - Prioritizes results matching highest number of input words
  - Ordered by relevance and recency
- **Scheduled Updates**: Automatic content scanning (hourly, daily, weekly, or cancel)
- **Enhanced Responses**: Provides contextually relevant links to site content
  - Configurable number of enhanced responses (1-10)
  - Can include post/page excerpts
  - Shows titles along with links
- **Custom Post Type Support**: Works with WooCommerce products and other custom types
- **Content Filtering**: 
  - Include/exclude posts, pages, products, and comments
  - Only considers published posts and approved comments
- **Analysis Export**: Download analysis results as CSV for insights
- **Learning Messages**: Option for none, random, or custom learning messages
- **Suppress Learning Messages**: Control display of learning messages

### Insights and Reporting

Comprehensive insights package for monitoring chatbot performance, consisting of three integrated components:

**Note**: The terminology has been updated from "Analytics" to "Insights" throughout the plugin for better clarity.

#### Insights Dashboard

Real-time analytics dashboard accessible directly in the WordPress admin panel:

- **Conversation Statistics**: Track total conversations, unique visitors, and engagement metrics
- **Sentiment Analysis**: Analyze user sentiment in conversations with automated or manual scoring
  - Average sentiment scores (range: -1.0 to +1.0)
  - Positive conversation percentages
  - Separate analysis for visitor/user messages vs chatbot responses
- **Engagement Analysis**: Evaluate chatbot effectiveness
  - High engagement rate tracking
  - Average messages before drop-off
  - Session duration metrics
- **Message Statistics**: Track message volume and types
  - Total messages exchanged
  - Visitor vs chatbot message breakdowns
- **Token Usage Tracking**: Monitor API usage and costs
  - Daily token consumption
  - Cost estimation capabilities
- **Period Comparison**: Compare performance across different time periods (Today vs Yesterday, This Week vs Last Week, etc.)
- **Data Export**: CSV export of conversation, interaction, and token data
- **Performance Metrics**: Response times, success rates, error tracking

#### Conversation Digest Email

Automated email reports delivering conversation summaries and transcripts directly to your inbox:

- **Automated Delivery**: Conversations automatically collected and sent at regular intervals
- **Frequency Options**: 
  - Free users: Weekly only
  - Premium users: Hourly, Daily, or Weekly
- **Free Tier Content**: Summary statistics including total conversations, pages with activity, unique visitors, and logged-in users
- **Premium Tier Content**: Full conversation transcripts with:
  - Complete message-by-message transcripts with timestamps
  - Session IDs, user IDs, page IDs, and thread IDs
  - Assistant names used
  - Clear Visitor vs Chatbot message labeling
- **Smart Tracking**: 
  - Incremental time windows (no duplicates)
  - Session-based grouping
  - Filtered content (excludes token data and system messages)
- **Use Cases**: Monitor activity without daily logins, quality assurance reviews, content planning, customer service follow-ups

#### Proof of Value Reports Email

Premium email reports providing actionable insights and performance metrics:

- **Performance Metrics**: 
  - Total conversations and messages
  - Estimated time saved
  - Resolved rate
  - Engagement depth
- **Top Pages by Chat Activity**: Identify which pages generate the most chatbot interactions
- **Top Assistants Used**: Track which chatbot assistants are most frequently utilized
- **Top Unanswered Questions**: Automatic detection of questions the chatbot couldn't answer
  - Pattern-matching for fallback responses
  - Question identification and frequency ranking
  - Knowledge gap identification
- **Suggested Next Steps**: Actionable recommendations based on chatbot performance
- **Use Cases**: Identify knowledge gaps, improve knowledge base, track performance trends, measure optimization impact

**Configuration**: All insights features require conversation logging to be enabled. Configure email reports in **Chatbot Settings** → **Reporting** tab → **"Conversation Digest and Insight Settings"** section.

### Dashboard Widget

Real-time statistics widget in WordPress admin dashboard:

- **Chat Activity Graph**: Visual representation of conversations over time
  - Bar chart showing conversations per day/hour
  - Scrollable horizontal view
  - Time period selection: 24 hours, 7 days, 30 days, 90 days, 365 days

- **Statistics Display**:
  - Total Sessions or Interactions (selectable view type)
  - Average Conversation Duration (human-readable format)
  - Token Usage: Prompt tokens, Completion tokens, Total tokens

- **View Types**:
  - Sessions: Unique conversations
  - Interactions: Total messages exchanged

- **Access**: Available to administrators in WordPress dashboard

### Remote Widget Access

Cross-domain chatbot deployment with security controls:

- **Domain-Assistant Pair Whitelisting**: Secure access control
  - Pairs domains with specific chatbot shortcodes
  - Format: `domain.com,chatbot-n`
  - Only whitelisted pairs can access chatbots

- **Widget Endpoint**: Dedicated endpoint for remote access
  - URL: `/widgets/chatbot-widget-endpoint.php`
  - Supports iframe embedding
  - Dynamic resizing support

- **Widget Logging**: Track all remote access attempts
  - Valid and invalid access logging
  - Download and manage logs via Tools tab
  - Security monitoring and audit trail

- **Use Cases**:
  - Deploy chatbot on multiple domains
  - Embed chatbot in external websites
  - Multi-site deployments
  - White-label solutions

### User Experience Features

- **Personalized Greetings**: Dynamic greetings using WordPress user data
- **Custom Avatars**: Upload custom avatars or choose from seasonal options
- **Audience Targeting**: Show chatbot to all users, logged-in users, or visitors only
- **Message Limits**: Flexible limit periods
  - Period options: Hourly, Daily, Weekly, Lifetime
  - Separate limits for visitors and logged-in users
  - Message count display shows current usage (e.g., "29/50")
  - Automatic reset based on selected period
- **Message Count Display**: Show message count in responses (e.g., "29/50") to help users track their usage against limits
- **Custom Buttons**: Support for up to 4 customizable buttons
  - Can be displayed on floating only, embedded only, or both styles
  - Each button can link to any URL (pages, contact forms, email, etc.)
  - Configurable button names and links
  - Useful for directing users to important pages or actions
- **Voice Options**: Multiple voice choices for text-to-speech
  - Available voices: Alloy, Echo, Fable, Onyx, Nova, Shimmer
  - Output formats: MP3, Opus, AAC, FLAC, WAV, PCM
  - Read Aloud feature can be enabled/disabled
  - Voice selection per assistant or globally
- **Conversation Locking**: Prevents multiple simultaneous requests
  - Prevents duplicate submissions
  - Improves user experience by handling impatient interactions
  - Reduces race conditions and API errors
- **Conversation Continuity**: Maintain context across page navigation
- **Conversation Continuation**: Maintain conversations when returning to previously visited pages
- **Glyph Rendering**: Enable/disable glyph rendering in responses (enabled by default)
- **Math Handling**: LaTeX rendering for complex mathematical expressions
- **Transcript Downloads**: Users can download conversation transcripts as text files
- **Input Rows Configuration**: Set number of rows for message input (1-10 rows)
- **Force Page Reload**: Option to force page reload when conversation is cleared
- **Suppress Footer Chatbots**: Automatically suppress footer chatbots when embedded chatbot is present on page
- **Mobile Optimization**: Responsive design for all device types

### Security and Privacy

- **API Key Encryption**: Secure storage of API credentials
- **Conversation Locking**: Prevents duplicate requests and race conditions
- **Input Sanitization**: XSS protection and data validation
  - Shortcode attribute sanitization
  - HTML content sanitization with wp_kses_post
  - SQL injection prevention with prepared statements
- **Remote Access Control**: Whitelist specific domains for remote widget access
  - Domain-assistant pair validation
  - Request validation and logging
- **Data Retention Settings**: Configurable conversation log retention periods
- **Error Log Management**: 
  - Download and delete error logs
  - Diagnostic tools for troubleshooting
  - Secure error logging with access controls
- **Session Management**: 
  - Unique visitor identification via cookies
  - Secure cookie handling (HttpOnly, Secure flags)
  - User ID tracking for logged-in users

## Configuration Options

### General Settings

- **AI Platform Selection**: Choose primary AI provider
- **Chatbot Name**: Customize chatbot identity
- **Start Status**: Configure initial display state (Open/Closed)
- **Start Status New Visitor**: Separate setting for new visitors
- **Message Limits**: Set interaction limits per user type
  - Period options: Hourly, Daily, Weekly, Lifetime
  - Separate limits for visitors and logged-in users
  - Display message count option
- **Timeout Settings**: Configure request timeout values
- **Conversation Continuation**: Enable/disable conversation continuation after page return
- **Force Page Reload**: Option to force page reload when conversation cleared

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
  - Voice selection (Alloy, Echo, Fable, Onyx, Nova, Shimmer)
  - Output format selection (MP3, Opus, AAC, FLAC, WAV, PCM)
  - Read Aloud enable/disable
- **File Upload**: Enable/disable file processing
  - Multiple file upload support
  - File type restrictions
  - Security measures for file handling
- **Transcript Downloads**: Allow users to download conversation history
- **Remote Widget Access**: Enable cross-domain chatbot access
  - Domain whitelisting
  - Widget logging
- **Diagnostic Tools**: Error logging and troubleshooting utilities
  - Error log management (download/delete)
  - Diagnostic mode toggle
  - Verbose logging options
- **Glyph Rendering**: Enable/disable glyph rendering in responses
- **Math Handling**: LaTeX rendering for mathematical expressions
- **Input Rows**: Configure number of rows for message input (1-10)
- **Custom Error Messages**: Display custom error messages to users
- **Unsaved Changes Warning**: Modal to warn about unsaved settings changes

## Technical Requirements

### WordPress Requirements

- **WordPress Version**: 5.0 or higher
- **PHP Version**: 7.4 or higher (tested with PHP 8.5.0)
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
  - Session IDs, User IDs, Page IDs
  - Message text, timestamps
  - Thread IDs, Assistant IDs
  - Token usage (prompt, completion, total)
  - Sentiment scores
- **Knowledge Navigator Data**: Content analysis results
  - TF-IDF scores
  - Keyword relationships
  - Content metadata
- **Insights Data**: Performance and usage metrics
  - Conversation statistics
  - Engagement metrics
  - Token usage tracking
- **Assistant Threads**: OpenAI Assistant conversation threads
  - Thread retention management
  - Context preservation
- **Widget Logs**: Remote widget access logs
  - Access attempts (valid/invalid)
  - Domain and assistant tracking
  - Timestamp and request details

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
- **Insights**: Reporting and insights features
- **Support**: Troubleshooting and help resources

### Support Channels

- **Plugin Support**: https://kognetiks.com/plugin-support/
- **Documentation**: Comprehensive online documentation
- **Diagnostic Tools**: Built-in troubleshooting utilities
- **Error Logging**: Detailed error tracking and reporting

### Internationalization and Language Support

- **Multi-Language Ready**: Automatically adapts to WordPress site language
- **Translated Languages**: 
  - Czech, German, Spanish, French, Italian, Polish, Portuguese, Russian, Ukrainian
  - 131+ translated strings for chatbot-user interaction messages
- **Fallback Handling**: Graceful fallback when translation files are missing
- **Localized Content**: 
  - Stop words localization
  - Learning messages localization
  - Error messages localization
  - Fallback pattern translations

### Error Handling and Diagnostics

- **Error Logging**: Comprehensive error tracking and reporting
  - Detailed error logs with timestamps
  - Error log management (download/delete)
  - Diagnostic mode toggle
- **Error Types Handled**:
  - API errors (rate limits, timeouts, invalid keys)
  - Network errors
  - Database errors
  - Validation errors
- **User-Friendly Error Messages**: Custom error messages for users
- **Developer Tools**: 
  - Diagnostic utilities
  - Verbose logging options
  - Error log analysis

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
- **Google**: Google User Agreement and Privacy Policy
- **Mistral**: Mistral Terms of Service and Privacy Policy
- **NVIDIA**: NVIDIA Terms and Privacy Policy
- **JAN.AI**: JAN.AI About and Privacy Policy

## Version History

## What's new in Version 2.4.4

### Improvements
* **Documentation**: Added Unanswered Questions Detection Analysis documentation.
* **Vendor management**: Refined free→trial→premium upgrade path and messaging.
* **Reporting**: Modal prompts conversation logging when enabling digest or proof-of-value reports.
* **Uninstall**: Improved uninstall process and version handling.

### Bug Fixes
* Suppressed vendor notices and quieted third-party warnings.
* Replaced select error_log calls with back_trace for cleaner debugging.

## What's new in Version 2.4.3

* **Bug Fixes**: Fixed unsaved changes modal and assistant management.

## What's new in Version 2.4.2

* **Bug Fixes**: Minor bug fixes and improvements.

## What's new in Version 2.4.1

### New Features
* **Automated Email Reports**: Added Conversation Digest and Proof of Value Reports email functionality with scheduling options
* **Enhanced Fallback Pattern Matching**: Improved detection of unanswered questions by including human messages with clarification/confusion patterns
* **Unsaved Changes Modal**: Added modal to warn users about unsaved changes in settings
* **Insights Tab UI Enhancements**: Minor UI upgrades to the Insights tab for better user experience

### Improvements
* **Email Scheduling**: Enhanced scheduling system for Conversation Digest and Proof of Value emails with proper enable/disable functionality
* **Internationalization**: Added global translations for fallback pattern values to various language files
* **Terminology Update**: Renamed "analytics" to "insights" throughout the codebase for better clarity

### Bug Fixes
* Fixed options saving functionality, unscheduling of automated emails, and corrected footer content for free and premium tiers

### Key Milestones

- **Version 2.4.1**: Automated email reports, enhanced fallback pattern matching, unsaved changes modal
- **Version 2.4.0**: Insights Package overhaul with Insights Dashboard, Conversation Digest Email, and Proof of Value Reports Email, Ukrainian language support
- **Version 2.3.9**: Google API support, Conversation Digest email feature, improved conversation history display
- **Version 2.3.8**: Google Gemini API integration
- **Version 2.3.6**: Comprehensive Insights Dashboard with conversation statistics, sentiment analysis, engagement metrics
- **Version 2.3.4**: Conversation locking mechanism, improved input processing, dashboard widget fixes
- **Version 2.3.3**: Updated JAN.AI local server support (v0.6.8)
- **Version 2.3.1**: Mistral API websearch support, JAN.AI API key requirement
- **Version 2.3.0**: Mistral API integration
- **Version 2.2.9**: Message count display feature
- **Version 2.2.7**: Conversation transcript feature, dashboard widget, custom post types support
- **Version 2.2.6**: Azure OpenAI and Local Server (JAN.AI) support
- **Version 2.2.5**: Enhanced context for Assistants with site content
- **Version 2.2.4**: Improved Knowledge Navigator, glyph rendering support
- **Version 2.2.3**: DeepSeek Reasoner model (DeepSeek-R1) support
- **Version 2.2.2**: DeepSeek API integration, multiple language translations, customizable icons
- **Version 2.2.1**: Anthropic API integration, Sentential Context Model, Knowledge Navigator updates
- **Version 2.1.8**: NVIDIA NIM API integration, improved assistant management, conversation continuation
- **Version 2.1.6**: Message limit periods (Hourly, Daily, Weekly, Lifetime)
- **Version 2.1.5**: Speech recognition integration, Knowledge Navigator algorithm improvements
- **Version 2.1.3**: Remote server access with whitelisting mechanism, math handling improvements
- **Version 2.1.0**: JavaScript version control, conversation log CSV export improvements
- **Version 2.0.9**: Conversation continuity improvements, custom CSS support
- **Version 2.0.7**: Dynamic model support, error log management, conversation continuation
- **Version 2.0.6**: Dynamic shortcodes with Assistant ID selection
- **Version 2.0.5**: Enhanced Assistant Management interface, custom buttons expanded to 4
- **Version 2.0.4**: Unique ID system for visitors and logged-in users
- **Version 2.0.3**: Transcript download option, file download support, custom error handling
- **Version 2.0.2**: Support documentation overhaul, export function improvements
- **Version 2.0.1**: GPT-4o model support, Max Prompt/Completion Tokens, Temperature, Top P settings
- **Version 2.0.0**: Knowledge Navigator settings reorganization, Read Aloud option

## Summary

The Kognetiks Chatbot provides a comprehensive, flexible solution for integrating AI-powered conversational experiences into WordPress websites. With support for multiple AI platforms, advanced features like Knowledge Navigator and Insights, and extensive customization options, it offers both technical sophistication and user-friendly implementation for businesses, educators, and content creators seeking to enhance their website's interactive capabilities.

The plugin's modular architecture allows for easy maintenance and future enhancements, while its comprehensive documentation and support resources ensure successful deployment and ongoing management.

---

- **[Back to the Overview](/overview.md)**
