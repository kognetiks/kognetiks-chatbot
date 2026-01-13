# Reporting

The Reporting settings in your Kognetiks Chatbot plugin for WordPress help you manage, analyze, and export data related to chatbot interactions. These settings provide insights into user engagement, conversation logs, token usage, and interaction counts. This high-level overview will guide you through using these settings effectively. Detailed explanations will be provided in the subsections.

Please review the section [Conversation Logging and History](../support/conversation-logging-and-history.md) overview in the Support section of this plugin for more details.

## Sections

- [Conversation Data](conversation-data.md)

- [Interaction Data](interaction-data.md)

- [Token Data](token-data.md)

- [Reporting Settings](reporting-settings.md)

- [Analytics Package](analytics-package/analytics-package.md)

- [Conversation Digest Settings](analytics-package/conversation-digest-email.md)

- [Proof of Value Settings](analytics-package/proof-of-value-reports-email.md)


## How to Use Reporting Settings

1. **Conversation Data**
   - **Description**: Displays the total number of conversation items (both user inputs and chatbot responses) stored in the database.
   - **Action**: Use the "Download Conversation Data" button to export this data as a CSV file for analysis or record-keeping.
   
2. **Interactions Data**
   - **Description**: Shows the count of interactions per day, helping you track user engagement over time.
   - **Action**: Click the "Download Interaction Data" button to export this data as a CSV file for further analysis.

3. **Token Data**
   - **Description**: Details the total number of tokens used daily, providing insights into API usage and costs.
   - **Action**: Use the "Download Token Usage Data" button to download the token data as a CSV file for monitoring and budget management.

4. **Reporting Settings**
   - **Description**: Allows you to configure how conversation data is logged and retained.
   - **Options**:
     - **Reporting Period**: Choose how frequently reports are generated (Daily, Weekly, Monthly).
     - **Enable Conversation Logging**: Toggle to turn logging on or off.
     - **Conversation Log Days to Keep**: Set the number of days to retain conversation logs.
   - **Action**: Adjust these settings to fit your monitoring and data retention needs, then click "Save Settings" to apply.

5. **Conversation Digest Settings**
   - **Description**: Configure automatic email summaries of new chatbot conversations to be sent to a specified email address.
   - **Options**:
     - **Enabled**: Toggle to enable or disable conversation digest emails (Yes/No).
     - **Frequency**: Choose how often digest emails are sent (Hourly, Daily, Weekly).
     - **Email Address**: Enter the email address where conversation digests should be sent.
   - **Action**: Enable the feature, set your preferred frequency, enter an email address, and click "Save Settings". Use the "Test Email" button to verify your email configuration.
   - **Note**: Conversation logging must be enabled in Reporting Settings for digest emails to include conversation data.

---

- **[Back to the Overview](/overview.md)**
