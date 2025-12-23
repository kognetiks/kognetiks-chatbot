# Diagnostics - For Developers

- By default, the Kognetiks Chatbot Diagnostics setting is disabled. When enabled, the plugin provides useful information about the Chatbot's operation. This information can be used to troubleshoot issues and to better understand how it is functioning.

- The plugin supports **Success**, **Notice**, **Warning**, **Failure**, and **Error**, i.e., increasing levels of severity. The default level is Success. The higher the level, the more information is provided.

- In addition to setting the Kognetiks Chatbot's diagnostics reporting level, you will also need to enable WordPress debugging. This can be done by setting the **WP_DEBUG** constant to true in your wp-config.php file.

- Turning on WordPress debugging will cause all PHP errors, notices, and warnings to be displayed. This is useful for debugging and development purposes.

## Calling the Diagnostic Function

Use the following example code to call the diagnostic function:

`// back_trace( 'LEVEL' , 'Message' );`

- Where **LEVEL** is one of: **SUCCESS**, **NOTICE**, **WARNING**, **FAILURE**, or **ERROR**

- Where **Message** is a text message to output to the debug log.

## Examples

Coming soon.

---

**NOTE:** It is not recommended to enable WordPress debugging on a production site.

---

- **[Back to the Overview](/overview.md)**
