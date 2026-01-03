# Conversation Digest Email

## Overview

The Conversation Digest email is an automated email report that delivers summaries of your chatbot conversations directly to your inbox. This feature helps you stay informed about user interactions with your chatbot without needing to log into your WordPress admin panel.

## How It Works

The Conversation Digest system automatically collects new conversations from your chatbot's conversation log and sends them to your specified email address at regular intervals.

### Data Collection

The system tracks conversations by:

1. **Tracking New Conversations**: Starting from the last digest timestamp (or the last 24 hours for the first run), the system collects all new conversations that have occurred since the previous digest.

2. **Organizing by Session**: Conversations are grouped by session ID, ensuring that all messages from a single conversation are kept together.

3. **Filtering Content**: Only actual conversation messages (Visitor and Chatbot types) are included. Token usage data and system messages are excluded to keep the digest focused on meaningful interactions.

### Email Frequency

The digest can be sent at different intervals depending on your license tier:

- **Free Users**: Weekly only
- **Premium Users**: Hourly, Daily, or Weekly options

The frequency is set in the Chatbot Settings under the "Conversation Digest and Insight Settings" section.

## Free vs Premium Content

The Conversation Digest provides different levels of detail based on your license tier:

### Free Tier Content

Free users receive a summary report that includes:

- **Summary Statistics**:
  - Total number of new conversations
  - Number of pages where conversations occurred
  - Number of unique visitors who engaged
  - Number of logged-in users who engaged

- **Period Information**: The time period covered by the digest

- **Upgrade Prompt**: Information about premium features available with an upgrade

This summary format gives you a quick overview of chatbot activity without overwhelming detail.

### Premium Tier Content

Premium users receive comprehensive conversation transcripts that include:

- **Summary Statistics**:
  - Total number of conversations
  - Total number of messages exchanged

- **Full Conversation Transcripts**: For each conversation session, you receive:
  - Session ID
  - User ID (if logged in)
  - Page ID where the conversation occurred
  - Thread ID (if applicable)
  - Assistant name used
  - Conversation start time
  - Complete message-by-message transcript with timestamps
  - Clear labeling of Visitor vs Chatbot messages

This detailed format allows you to:
- Review exact user questions and chatbot responses
- Understand conversation flow and context
- Identify patterns in user inquiries
- Monitor chatbot performance in detail
- Track which assistants are being used

## Email Format

The Conversation Digest is sent as a plain text email with:

- **Subject Line**: "Chatbot Conversation Digest - [Date and Time]"
- **Body**: Formatted text with clear sections and separators
- **Chronological Order**: Messages are presented in the order they occurred

## Configuration

To enable and configure the Conversation Digest:

1. Navigate to **Chatbot Settings** → **Reporting** tab
2. Scroll to the **"Conversation Digest and Insight Settings"** section
3. Enable the **"Conversation Digest"** card
4. Set your desired email address
5. Choose your frequency (Weekly for free users, Hourly/Daily/Weekly for premium)
6. Click **"Save Changes"**

The system will automatically schedule the digest emails based on your settings.

## How Conversations Are Tracked

### Session Tracking

Each conversation is identified by a unique session ID. This ensures that:

- All messages from a single conversation are grouped together
- Multiple conversations from the same user are kept separate
- The conversation flow is preserved in chronological order

### Time Window

The digest uses an incremental time window approach:

- **First Run**: Collects conversations from the last 24 hours
- **Subsequent Runs**: Collects only new conversations since the last digest was sent
- **No Duplicates**: Each conversation appears in only one digest email

This ensures you receive all conversations without duplicates or gaps.

### Message Filtering

The system includes only relevant conversation data:

- **Included**: Visitor messages and Chatbot responses
- **Excluded**: Token usage data, system messages, and other non-conversational entries

## Use Cases

The Conversation Digest is useful for:

### Monitoring Activity

- Stay informed about chatbot usage without daily logins
- Track conversation volume over time
- Monitor engagement patterns

### Quality Assurance

- Review chatbot responses for accuracy
- Identify areas where the chatbot may need improvement
- Ensure the chatbot is providing helpful information

### Content Planning

- Discover common questions users ask
- Identify topics that need better coverage
- Plan knowledge base updates based on actual user inquiries

### Customer Service

- Review conversations that may need follow-up
- Understand user concerns and questions
- Track which pages generate the most chatbot interactions

## Best Practices

1. **Regular Review**: Check your digest emails regularly to stay informed about chatbot activity

2. **Action Items**: Use the information to identify:
   - Questions that need better answers
   - Pages that might need more chatbot support
   - Opportunities to improve your knowledge base

3. **Frequency Selection**: Choose a frequency that matches your review schedule:
   - **Hourly**: For high-traffic sites needing constant monitoring
   - **Daily**: For regular review and quality checks
   - **Weekly**: For general awareness and trend tracking

4. **Email Management**: Consider setting up email filters or folders to organize your digest emails

5. **Team Sharing**: Forward relevant conversations to team members who can help improve responses

## Troubleshooting

### Not Receiving Emails

If you're not receiving digest emails, check:

- **Enabled Status**: Ensure the Conversation Digest is enabled in settings
- **Email Address**: Verify the email address is correct and active
- **Email Deliverability**: Check spam/junk folders
- **WordPress Cron**: Ensure WordPress cron jobs are running (check with a cron monitoring plugin)
- **Conversation Logging**: Verify that conversation logging is enabled

### Missing Conversations

If conversations seem to be missing:

- **Time Window**: Remember that only new conversations since the last digest are included
- **First Run**: The first digest includes only the last 24 hours
- **Filtering**: Only Visitor and Chatbot messages are included (token data is excluded)

### Too Many/Few Emails

Adjust the frequency setting:

- **Free Users**: Limited to Weekly
- **Premium Users**: Can choose Hourly, Daily, or Weekly based on your needs

## Related Documentation

- [Analytics Package Overview](./analytics-package.md)
- [Proof of Value Reports Email](./proof-of-value-reports-email.md)
- [Conversation Digest Settings](../settings/conversation-digest.md)

---

- **[Back to the Overview](/overview.md)**

