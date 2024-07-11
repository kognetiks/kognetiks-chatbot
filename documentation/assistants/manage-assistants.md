## Managing Assistants

Effortlessly manage you chatbot Assistants all in one place using an intuitive interface.

You will no longer need to remember all the Assistant options, as they are all available on the GTP Assistants tab for you to view and edit.

Tailor each Assistant to meet the unique needs of your audience, ensuring an engaging and personalized experience for all.

If you have developed an Assistant in the OpenAI Playground, you will need the id of the assistant - it usually starts with ```asst_```.

More information can be found here https://platform.openai.com/playground?mode=assistant.

When you\'re ready to use an Assistant, simply add the shortcode ```[chatbot assistant="Common Name"]``` to your page.

TIP: For best results ensure that the shortcode appears only once on the page.

TIP: When using the ```embedded``` style, it's best to put the shortcode in a page or post, not in the footer.

![Managing Assistants](managing-assistants.png)

### Field Descriptions

1. **Actions**: `Update`, `Delete`, `Add New Assistant`
   - **Update/Delete**: Use these buttons to update or delete an assistant.
   - **Add New Assistant**: Use this button to add a new assistant.

2. **ID**:
   - **Description**: The unique identifier for each assistant.
   - **Input**: Automatically generated.

3. **Assistant ID**:
   - **Description**: The specific ID for the assistant provided by OpenAI at the time you set up your assistant (it usually starts with ```asst_```).
   - **Input**: Enter the OpenAI Assistant ID.
   - **Required**: This is a required field.

4. **Common Name**:
   - **Description**: A user-friendly name for the assistant.
   - **Input**: Enter a name that easily identifies the assistant.  You'll use this name to call the assistant form the shortcode.
   - **Required**: This is a required field.
   - **Usage**: ```[chatbot assistant="Common Name"]```
   - **Tip**: Be sure sure to use regular quote marks around the "Common Name" if there are any spaces.

5. **Style**:
   - **Description**: Determines how the assistant is displayed on your site.
   - **Input**: Choose between ```Embedded``` and ```Floating```.

6. **Audience**:
   - **Description**: Specifies the target audience for the assistant.
   - **Input**: Options include ```All```, ```Visitors```, and ```Logged-in```.

7. **Voice**:
   - **Description**: The voice used by the assistant, one of ```Alloy```, ```Echo```, ```Fable```, ```Onyx```, ```Nova```, or ```Shimmer```.  Select ```None``` to disable the Read Aloud options for this Assistant.
   - **Input**: Select from available voice options.

8. **Allow File Uploads**:
   - **Description**: Indicates whether users can upload files to the Assistant.
   - **Input**: Choose ```Yes``` or ```No```.

9. **Allow Transcript Downloads**:
   - **Description**: Allows users to download a transcript of their conversation with the assistant.
   - **Input**: Choose ```Yes``` or ```No```.

10. **Show Assistant Name**:
   - **Description**: Displays the assistant's name in interactions.
   - **Input**: Choose ```Yes``` or ```No```.

11. **Initial Greeting**:
   - **Description**: The first message the assistant sends to users.
   - **Input**: Enter the greeting message.

12. **Subsequent Greeting**:
   - **Description**: Messages the assistant sends after the initial greeting.
   - **Input**: Enter the follow-up greeting messages.

13. **Placeholder Prompt**:
   - **Description**: A sample prompt shown in the input field.
   - **Input**: Enter a placeholder prompt to guide user input.

14. **Additional Instructions**:
   - **Description**: Extra instructions or context for the assistant.
   - **Input**: Enter any additional instructions needed for the assistant.
   
---

- **[Back to the Overview](/overview.md)**