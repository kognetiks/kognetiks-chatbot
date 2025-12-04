# Configuring the Advanced API Settings

The Advanced API Settings allow you to configure critical parameters for the API connection used by the Kognetiks Chatbot. Follow these steps to ensure the plugin is properly set up:

![Advanced API Settings](advanced-api-settings.png)

1. **API Resource Name**:
   - **Description**: This field specifies the resource name for the Azure OpenAI API.
   - **Example**: `test-resource-group`.

2. **API Deployment Name**:
   - **Description**: This field specifies the deployent name for the Azure OpenAI API.
   - **Example**: `test-deployment`.

3. **API Verions**:
   - **Description**: This field specifies the API version for the Azure OpenAI API.
   - **Example**: `2024-07-01-preview`.

4. **Timeout Setting (in seconds)**:
   - **Description**: This setting determines how long the plugin will wait for a response from the API before timing out.
   - **Default Value**: The default is set to 240 seconds.
   - **Customization**: Adjust this value based on your server's performance and network conditions. A higher value might be needed if you experience frequent timeouts, while a lower value can be used to reduce wait times in case of unresponsive requests.

## Steps to Configure

1. Navigate to the Advanced API Settings section of the Kognetiks Chatbot plugin in your WordPress dashboard.
2. Verify the `Base URL for API` is set to `https://api.openai.com/v1`. Change it only if instructed by Azure OpenAI or if you have specific requirements.
3. Set the `Timeout Setting (in seconds)` by entering a numeric value that suits your server and network conditions.
4. Save the settings.

## Tips

- **Avoid Unnecessary Changes**: Unless you have a specific reason, it's best to leave the Base URL as the default provided by Azure OpenAI.
- **Monitor Performance**: If you experience issues with response times or API connectivity, consider adjusting the timeout setting and monitor the performance impact.
- **Consult Documentation**: For more information on API parameters and troubleshooting, refer to the [Azure OpenAI API documentation](https://learn.microsoft.com/en-us/azure/ai-services/openai/how-to/create-resource?pivots=web-portal).

By configuring these settings, you ensure that your Kognetiks Chatbot maintains a stable and efficient connection to the Azure OpenAI API, providing reliable performance for your users.

---

- **[Back to API Azure OpenAI Settings](api-azure-openai-model-settings.md)**
- **[Back to the Overview](/overview.md)**