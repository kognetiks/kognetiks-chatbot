# FAQs

## Can I have more than one chatbot on the same page?

No, you should **not** put more than one chatbot shortcode on the same page or post.

For now, it will **not** work as expected if you put a floating chatbot using the ```[chatbot style=floating]``` in the footer **and** an embedded chatbot ```[chatbot style=embedded```] on the a page or post.

You can put as many different chatbot on different pages, as long as there is only one chatbot per page.

## How many Assistants can I have?

You can have one primary, one alternate, but as many Assistants as you want if you invoke them directly using the ID assigned when you created the Assistant on the OpenAI platform.

Use the following format to invoke the primary or alternate assistant:

- `[chatbot style="floating" assistant="primary"]` - Floating style, Assistant as set in Primary setting

- `[chatbot style="embedded" assistant="alternate"]` - Embedded style, Assistant as set in Alternate setting

Use the following format to invoke an assistant directly by its ID

- `[chatbot style="floating" assistant="asst_xxxxxxxxxxxxxxxxxxxxxxxx"]` - Floating style, Assistant as set in Assistant ID setting

- `[chatbot style="embedded" assistant="asst_xxxxxxxxxxxxxxxxxxxxxxxx"]` - Embedded style, Assistant as set in Assistant ID setting
Mix and match the style and assistant attributes to suit your needs.

**NOTE:** When using the 'embedded' style, it's best to put the shortcode in a page or post, **not** in a footer.

### More Information

See [Chatbots and Assistants](support/chatbots-and-assistants.md) for more details on using multiple Assistants.

---

- **[Back to the Overview](/overview.md)**