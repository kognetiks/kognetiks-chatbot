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

## I've created an Assistant but the chatbot is responding generically.

First, make sure to set the ```Use GPT Assistant Id``` to ```Yes``` on GTP Assistant tab in the Chatbot settings.

Be sure to as a valid ```Primary GPT Assistant Id``` or ```Alternate GPT Assistant Id```.  ID are similar to ```asst_gs8KtljqS7F62mjXicjxnAPg``` and found [here](https://platform.openai.com/assistants).

Sometimes caching is the problem.  If so, in the case of WP Engine hosting, you might allow the following:

Action: Set
Name: Cache-Control
Value: max-age=604800, must-revalidate
When: Only on successes

You can try using the cache-control header.  This setting controls how long browsers and intermediary caches store a copy of the resource before checking back with the server.  While it primarily affects the browser's caching behavior, it can also influence the caching policies of intermediary caches.

In the case of hosting on WP Engine, you would set this in the web rules section: [WP Engine Web Rules Engine](https://wpengine.com/support/web-rules-engine/#Header_Rules).

If you’re using a different hosting provider, check their documentation for similar cache-control settings.

### More Information

See [Chatbots and Assistants](support/chatbots-and-assistants.md) for more details on using multiple Assistants.

---

- **[Back to the Overview](/overview.md)**