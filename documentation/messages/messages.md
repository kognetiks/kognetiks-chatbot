# Configuring the Diagnostics Settings

The Diagnostics Settings help you monitor the health and performance of the **Kognetiks Chatbot for WordPress**, providing tools for error logging, API connection checks, and other diagnostics. Follow these steps to configure these options effectively:

![Messages](messages.png)

1. **System and Plugin Information**:
   - **Description**: Displays key information about your system and plugin, including PHP version, WordPress version, Chatbot version, and WordPress language code.
   - **Usage**: Use this information for troubleshooting and ensuring compatibility with your environment.

2. **API Test Results**:
   - **Description**: Shows the status of the connection to the OpenAI API.
   - **Details**: Indicates whether the connection was successful.
   - **Usage**: Check this status to verify that the chatbot can communicate with the OpenAI API. A successful connection is necessary for the chatbot to function correctly.

3. **Chatbot Diagnostics**:
   - **Description**: Allows you to select the level of diagnostics to be logged.
   - **Options**: 
     - `Off`: No logging.
     - `Success`: Success messages.
     - `Notice`: General messages.
     - `Failure`: Failure messages.
     - `Warning`: Warning massages.
     - `Error`: Error messages.
   - **Selection**: Choose the appropriate level based on your need for diagnostics information. `Off` is the default and recommended setting for general use. Use `Error` for in-depth troubleshooting. `Error` logs all levels.

   **NOTE:** You can enable error and console logging at anytime, however in the production releases of the chatbot all error logging has been commented.

4. **Suppress Notices and Warnings**:
   - **Description**: Allows you to suppress notices and warnings such as those associated with the **Knowledge Navigator** and other administrative functions.  These messages and warnings are not shown to users, only to administrator.
   - **Options**: `On` or `Off`.
   - **Selection**: Choose `On` to suppress notices and warnings if you prefer a less verbose experience, otherwise set to `Off` to see all administrative messages associated with the chatbot.

5. **Suppress Attribution**:
   - **Description**: Allows you to suppress the attribution message ("Chatbot WordPress plugin by Kognetiks") displayed only in the `floating` style of the chatbot.
   - **Options**: `On` or `Off`.
   - **Selection**: Choose `On` to suppress the attribution message. Set to `Off` to display the message.

6. **Delete Plugin Data on Uninstall**:
   - **Description**: Determines whether to delete all plugin data when the plugin is uninstalled.
   - **Options**: `Yes` or `No`.
   - **Selection**: Choose `Yes` to delete all data when uninstalling the plugin, ensuring no residual data remains. Select `No` to retain data even after uninstallation, which can be useful if you plan to reinstall the plugin later.

## Steps to Configure

1. Navigate to the Diagnostics Settings section of the Kognetiks Chatbot plugin in your WordPress dashboard.

2. Review the **System and Plugin Information** to ensure compatibility and identify the current versions in use.

3. Check the **API Test Results** to confirm a successful connection to the OpenAI API.

4. Set the **Chatbot Diagnostics** level based on your need for error and performance logging.

5. Toggle **Suppress Notices and Warnings** to `On` or `Off` as desired.

6. Toggle **Suppress Attribution** to `On` or `Off` based on whether you want to hide the attribution message.

7. Decide whether to enable **Delete Plugin Data on Uninstall** by setting it to `Yes` or `No`.

8. Click 'Save Settings' to apply your changes.

## Tips

- **Regular Monitoring**: Regularly check the diagnostics settings and logs to ensure the chatbot is functioning correctly and to identify any issues early.

- **Error Logging**: Start with the `Error` logging level and increase to `Warning` or `Debug` if you encounter issues that require more detailed diagnostics.

- **Data Management**: Be cautious with the "Delete Plugin Data on Uninstall" setting if you might need the data in the future.

By configuring these settings, you can effectively monitor and maintain the health and performance of your Kognetiks Chatbot, ensuring a smooth and reliable user experience.
