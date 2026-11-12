# Privacy

The Kognetiks Chatbot keeps the live conversation separate from any saved history. The person using the chatbot, the site administrator, and the AI provider do not all see the same record. Most history features are off until you turn them on.

This page explains how a chat is limited, who can read it, and which settings change that.

## How a chat stays limited

Each visitor receives a signed browser cookie. The cookie is HttpOnly and SameSite=Lax. On an HTTPS site it is also marked Secure. It lasts 30 days. A logged-in person is identified by their WordPress account.

The live conversation is kept for that person, that page, that assistant, and that browser tab.

- A refresh of the same tab continues the conversation.
- A new tab starts a new conversation with the AI provider.
- Closing the tab and coming back later starts a new conversation. The thread retention setting does not reconnect a blank visit to the old provider conversation.
- Clear starts a new conversation in that tab. It removes the on-screen transcript, the model memory for that visit, and the local pointer to the provider conversation.
- Another WordPress account in the same tab does not see the previous account's on-screen transcript.
- Read-aloud speaks the last reply for that account and that open tab, and only for 12 hours.

The AI provider still receives each message you send and keeps its own copy under that vendor's terms and privacy policy. Clear removes the plugin's pointer to that conversation. It does not delete the vendor's copy.

## Who can see a chat

| Who | What they can see | When |
| --- | --- | --- |
| The person at the keyboard | The messages on the screen | During that visit |
| The same person, same account, same tab | The saved on-screen transcript | Conversation Continuation is On |
| The AI provider | The messages sent for a reply | Whenever the chatbot answers |
| A site administrator | Logged message text, CSV export, and reports | Conversation Logging is On. Reporting requires the `manage_options` capability. |
| A logged-in user | That account's own logged messages | The `[chat_history]`, `[chatbot_conversation]`, or `[chatbot_chatgpt_history]` shortcode is on a page, and those messages were logged |
| A digest recipient | Counts on the free plan, or full transcripts on Premium | Conversation Digest is Yes and logged messages exist |
| A transcript email address | The assistant thread | An assistant reply contains `[conversation_transcript]` and Conversation Transcript Email is set |
| A Proof of Value recipient | Visitor questions the chatbot could not answer | Insights email is enabled and those questions were logged |
| Anyone with a file or audio link | That one generated file | For about one hour after it is created |
| WordPress privacy tools | That account's logged messages and private chat data | Tools → Export Personal Data or Erase Personal Data |

A visitor who is not logged in does not see the history shortcode. Anonymous log rows are visible to administrators and, on Premium, in the conversation digest. They are removed by the log retention job, not by Erase Personal Data, because they are not tied to a WordPress account.

The dashboard widget shows token totals. It does not show message text.

## Settings

### Audience for Chatbot

- **Where**: General.
- **Use**: Chooses who can use the chatbot: everyone, visitors only, or logged-in users only.
- **Default**: All.
- **Disclosure**: This limits who can start a chat. It does not hide history that was already stored.

### Message limits

- **Where**: General.
- **Use**: The server counts messages against the saved visitor or user limit before it calls the AI provider. It also refuses a prompt over 4,000 characters, more than 10 guest messages per minute from one address, and more than 20 messages per minute from one account.
- **Default**: Visitors are limited to 20 messages per day until you save another visitor limit. Logged-in accounts are limited to 999 messages over Lifetime until you save another user limit. A saved limit stays as you saved it.
- **Disclosure**: Each counter stores a hash of the visitor cookie, a hash of the network address, or the WordPress user id. The counter does not store the message text. Guests who share an address share that guest allowance.

### Conversation Continuation

- **Where**: General.
- **Use**: Restores the visible transcript when the same account returns to the page in the same browser tab.
- **Options**: `On` or `Off`.
- **Default**: `Off`.
- **Disclosure**: Leave this Off on shared computers. When it is On, the transcript stays in that browser tab for that WordPress user. Another account does not receive it. A later person using the same open tab while nobody is logged in can still see a visitor transcript that was left on the screen.

### Allow Transcript Download

- **Where**: General.
- **Use**: Shows a control so the visitor can save the chat that is on the screen. The file is built in the browser.
- **Options**: `Yes` or `No`.
- **Default**: `Yes`.
- **Disclosure**: The person using the chatbot can keep a copy. Set this to `No` if visitors should not take the transcript with them.

### Force Page Reload on Conversation Clear

- **Where**: General.
- **Use**: Reloads the page after Clear.
- **Options**: `Yes` or `No`.
- **Default**: `No`.
- **Disclosure**: This refreshes the page. It does not delete logged messages.

### Thread Retention Period (hrs)

- **Where**: Assistants.
- **Use**: Sets how long the live conversation and its model memory remain available in the open tab.
- **Options**: 6 to 720 hours.
- **Default**: 36 hours.
- **Disclosure**: A shorter period forgets the open tab sooner. Closing the tab already starts a new provider conversation on the next visit. The AI provider may keep its own copy for longer.

### Enable Conversation Logging

- **Where**: Reporting.
- **Use**: Saves each visitor message and chatbot reply in the WordPress database, with the session, user, page, assistant, and time.
- **Options**: `On` or `Off`.
- **Default**: `Off`.
- **Disclosure**: While this is Off, new chats are not written to the conversation log, the history shortcode, the CSV export, or the digest. Turning it On stores the full message text until the retention period removes it. Messages that were logged earlier remain until retention or Erase Personal Data removes them. The daily cleanup runs only while logging is On. Turning logging Off stops new rows and also pauses that cleanup.

See [Conversation Logging and History](../support/conversation-logging-and-history.md) and [Reporting Settings](../reporting/reporting-settings.md).

### Conversation Log Days to Keep

- **Where**: Reporting.
- **Use**: Deletes logged messages older than the selected period.
- **Options**: 1, 7, 30, 60, 90, 180, 365 days, or Indefinitely.
- **Default**: 30 days.
- **Disclosure**: Indefinitely keeps every logged message until you delete it. Clear does not remove these rows. WordPress Erase Personal Data removes an account's rows. Anonymous rows wait for this cleanup, and the cleanup runs only while Conversation Logging is On.

### Conversation Digest

- **Where**: Reporting.
- **Use**: Emails a summary of new logged conversations.
- **Enabled**: `Yes` or `No`. Default `No`.
- **Frequency**: Weekly on the free plan. Premium can use Hourly, Daily, or Weekly.
- **Email Address**: The inbox that receives the digest.
- **Disclosure**: The free digest sends counts. The Premium digest sends the message text, user id, page id, and session id. The digest reads messages that are already in the log, including messages from a period when logging was On and is now Off. Leave this at `No` unless a named inbox should receive that report.

See [Conversation Digest Settings](../reporting/conversation-digest.md) and [Conversation Digest Email](../analytics-package/conversation-digest-email.md).

### Insights Email (Proof of Value)

- **Where**: Reporting.
- **Use**: Emails performance metrics. On Premium, the report can list visitor questions the chatbot did not answer.
- **Default**: `No`.
- **Disclosure**: Those unanswered questions are taken from logged messages. The report is not a full transcript. Enable it only for an inbox that should see those questions.

See [Proof of Value Reports Email](../analytics-package/proof-of-value-reports-email.md).

### Conversation Transcript Email

- **Where**: Assistants.
- **Use**: Sends a transcript when an assistant reply contains the marker `[conversation_transcript]`. The marker is removed before the visitor sees the reply.
- **Default**: Empty.
- **Disclosure**: The address you enter receives that assistant conversation. Leave the field empty if you do not want transcripts emailed.

### Widget Logging

- **Where**: The widget access log for the remote widget.
- **Use**: Records that the widget was requested.
- **Default**: `No`.
- **Disclosure**: When it is On, the log stores a keyed token in place of the raw IP address. The log folder is blocked from direct web access on Apache. On Nginx, add a matching deny rule for `widget-logs` and `chatbot-logs`, because Nginx does not read `.htaccess`.

## What Clear removes

Clear affects the open tab:

- The on-screen transcript.
- The model memory for that page, assistant, and tab.
- The local pointer to the Assistants or Responses conversation.
- The read-aloud copy of the last reply.

Clear leaves these in place:

- Rows already stored by Conversation Logging.
- The history shortcode, admin export, and digest, which read those rows.
- The AI provider's own copy of the conversation.
- A generated file or audio link until its one-hour cleanup.

## WordPress privacy tools

Tools → Export Personal Data includes that account's chatbot messages.

Tools → Erase Personal Data deletes that account's logged messages and the private chat values stored for that account.

Anonymous visitors have no WordPress user, so those tools do not cover their rows. Use Conversation Log Days to Keep for anonymous history.

## Disclosure risks that remain

These are present even with the live-chat limits above.

- **The AI provider.** Every reply sends the visitor's message to the selected vendor. That vendor's retention and privacy policy apply. Review the provider links in the [documentation overview](../overview.md).
- **Conversation Logging.** Full message text is stored in WordPress until the retention period, or forever if Days to Keep is Indefinitely. Administrators with access to Reporting can read and export it.
- **Premium Conversation Digest.** The configured inbox receives full transcripts of logged chats.
- **Conversation Transcript Email.** A configured address receives an assistant transcript when the reply contains `[conversation_transcript]`.
- **Transcript download.** With the default `Yes`, the visitor can save the on-screen chat.
- **Generated files and speech audio.** The link is public for about one hour. Someone who receives the link can open that file without logging in.
- **Shared computers.** Continuation is Off by default. Turn it on only when the same person should see the previous on-screen chat in that tab.
- **Server log folders on Nginx.** Apache is given a deny rule for the chatbot log folders. Nginx needs the same rule in the server configuration.

For API key handling, see [API Key Safety and Security](../support/api-key-safety-and-security.md).

---

* **[Back to the Overview](../overview.md)**
