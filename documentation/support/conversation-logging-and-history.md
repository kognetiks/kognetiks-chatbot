# Conversation History

You can now add a shortcode on your site to retrieve the logged-in user’s conversation history.

Use the following format to invoke the conversation history anywhere you can include a shortcode:

- ```[chatbot_chatgpt_history]```

# Conversation Logging Overview

This chatbot logs interactions with visitors to provide insights and enhance user experience. By default, the option to log conversations is turned off. Below is an overview of the table structure and its functionality.

### Table Structure Overview

The table is designed to store key elements of each interaction, including:

- **ID:** Unique identifier for each entry, auto-incremented.

- **Session ID:** Identifies the session of the interaction.

- **User ID and Page ID:** Identifies the user and the webpage of interaction.

- **Interaction Time:** Timestamp of each interaction.
User Type: Distinguishes between visitor and chatbot messages.

- **Thread ID and Assistant ID:** For identifying specific threads or bot instances.

- **Message Text:** Content of each message exchanged.

### How It Works

Each interaction with the chatbot is logged in real-time, capturing all relevant information into the table. This includes automatic and direct data sources for fields like interaction time and message text.

### Possible Applications and Uses

The conversation log may be used for:

- **Analysis and Reporting:** Generate reports on user interactions and queries.

- **Bot Improvement:** Refine chatbot responses based on logged data.

- **User Experience Enhancement:** Utilize insights for improving user interactions.

- **Compliance and Record-Keeping:** Maintain logs for regulatory requirements.

- This table is integral to managing and analyzing chatbot interactions, enabling continuous improvement and providing valuable insights into user engagement on your WordPress site.

# Privacy and User Notification

Our commitment to you and your visitors' privacy is paramount when interacting with our chatbot. Below are the key aspects of how we address privacy concerns:

### Transparent Communication

Visitors should be informed that interactions with the chatbot are recorded. This should be communicated through a notice when the chatbot is first engaged.

### Purpose of Data Collection

The data collected may be used to improve user experience and chatbot functionality. You should ensure that all data is handled securely and in compliance with relevant privacy regulations.

### Data Storage and Use

Information on how the collected data is stored and used is provided, and should adhere to privacy standards like GDPR and CCPA.

### Conversation Log Deletion

You can set the retention period in the plugin settings to automatically delete entries in the conversation log after certain periods of days (1, 7, 30, etc.).

### Privacy Policy and Link

We encourage the inclusion of a privacy policy link in the chatbot interface. The policy should detail the management of chatbot data.

A link to your site's privacy policy should base64_encode included the Example Notification below, which explains the specifics of chatbot data management.

Please consult with the appropriate legal counsel and professionals to ensure that your privacy policy is compliant with all applicable laws and regulations.

### Details in Privacy Policy

The privacy policy suggests detailed information about data collection, use, legal basis for processing, retention practices, and user rights.

### Regular Updates

The privacy policy should be regularly updated to reflect any changes in data handling practices.

### Example Notification

- "Please note that your interactions with our chatbot are logged for the purpose of improving our services and providing better support. We respect your privacy, and all data is handled in accordance with our privacy policy, which you can review ```<a href='https//...link to your privacy page ...'>here</a>```. Your continued use of the chatbot indicates your consent to these practices."

---

- **[Back to the Overview](/overview.md)**
