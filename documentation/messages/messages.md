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

4. **Custom Error Message**:
   - **Description**: This setting allows administrators to define a custom error message that will be displayed to users when the chatbot encounters an issue. This ensures a more consistent and branded user experience, even in cases of unexpected errors.
   - **Options**: Any text string that you want to use as the error message.
   - **Selection**: Enter your preferred error message in the provided text field on the "Messages" tab. An example of a custom error message could be:
     ```
     Sorry, it appears our chat isn't working right now. If you're looking for support, click <a href="https://www.kognetiks.com/">here</a>.
     ```
   - **Additional Requirements**:
     - **Chatbot Diagnostics**: Ensure that the Chatbot Diagnostics setting is turned from `Off` to `Error` to enable the display of custom error messages.
     - **WordPress Error Logging**: You may also need to turn on WordPress error logging to fully utilize this feature.

5. **Suppress Notices and Warnings**:
   - **Description**: Allows you to suppress notices and warnings such as those associated with the **Knowledge Navigator** and other administrative functions.  These messages and warnings are not shown to users, only to administrator.
   - **Options**: `On` or `Off`.
   - **Selection**: Choose `On` to suppress notices and warnings if you prefer a less verbose experience, otherwise set to `Off` to see all administrative messages associated with the chatbot.

6. **Suppress Attribution**:
   - **Description**: Allows you to suppress the attribution message ("Chatbot WordPress plugin by Kognetiks") displayed only in the `floating` style of the chatbot.
   - **Options**: `On` or `Off`.
   - **Selection**: Choose `On` to suppress the attribution message. Set to `Off` to display the message.

7. **Delete Plugin Data on Uninstall**:
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

## How To Enable Error Logging

To enable error logging in WordPress, you need to modify the `wp-config.php` file in your WordPress installation directory.

**NOTE: Enabling debugging in WordPress is useful for troubleshooting issues, but it may expose sensitive information and affect site performance. Be sure to disable debugging on live sites after resolving issues to maintain security and optimal performance.**

Here are the basic steps to enable error logging:

1. **Access the `wp-config.php` File**:
   - Use an FTP client or your hosting provider's file manager to navigate to the root directory of your WordPress installation. This is typically where you will find the `wp-config.php` file.

2. **Edit the `wp-config.php` File**:
   - Open the `wp-config.php` file in a text editor.

3. **Enable Debugging**:
   - Locate the following line in the file (if it exists):

     ```define('WP_DEBUG', false);```

   - Change `false` to `true` to enable debugging:

     ```define('WP_DEBUG', true);```

4. **Enable Debug Log**:
   - Add or modify the following lines to enable the debug log:

     ```define('WP_DEBUG_LOG', true);```

     ```define('WP_DEBUG_DISPLAY', false);```
     
     ```@ini_set('display_errors', 0);```

   - This will log errors to a file named `debug.log` located in the `wp-content` directory, but it will not display errors on the screen.

5. **Save and Upload the File**:
   - Save the changes to the `wp-config.php` file and upload it back to your server if you are using an FTP client.

### Optional: More Detailed Logging

If you want more detailed logging, you can also add the following lines to `wp-config.php`:

```define('SCRIPT_DEBUG', true);```

```define('SAVEQUERIES', true);```

- `SCRIPT_DEBUG`: Forces WordPress to use the "dev" versions of core CSS and JavaScript files rather than the minified versions.

- `SAVEQUERIES`: Saves the database queries to an array and makes them available via the global `$wpdb->queries`.

### Accessing the Error Log

- You can access the error log by navigating to the `wp-content` directory and opening the `debug.log` file.

By enabling these settings, you can track and troubleshoot errors that occur within your WordPress site. If you need more advanced logging or custom error handling, consider using a logging plugin like WP Debugging or Error Log Monitor.

---

- **[Back to the Overview](/overview.md)**
