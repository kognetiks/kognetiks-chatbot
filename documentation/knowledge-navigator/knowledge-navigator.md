# Knowledge Navigator

Introducing **Knowledge Navigator** - the smart explorer behind our Kognetiks Chatbot plugin that's designed to delve into the core of your website. Like a digital archaeologist, it embarks on an all-encompassing journey through your site's published pages, posts, products and approved comments, carefully following every internal link to get a holistic view of your content. The exciting part? It sifts through each page, extracting the essence of your content in the form of keywords and phrases, gradually building a meticulous, interactive map of your website's architecture.

What's the outcome? Detailed "results.csv" and "results.json" files are created, tucking away all this valuable information in a dedicated 'results' directory within the plugin's folder. The prime objective of Knowledge Navigator is to enable the Kognetiks Chatbot plugin to have a crystal clear understanding of your website's context and content. The result? Your chatbot will deliver responses that are not just accurate, but also fittingly contextual, thereby crafting a truly bespoke user experience. This all is powered by the advanced AI technology of OpenAI's Large Language Model (LLM) API.

And how does the **Knowledge Navigator** do all this? It employs a clever technique known as **TF-IDF (Term Frequency-Inverse Document Frequency)** to unearth the keywords that really matter. The keywords are ranked by their TF-IDF scores, where the score represents the keyword's relevance to your site. This score is a fine balance between the term's frequency on your site and its inverse document frequency (which is essentially the log of total instances divided by the number of documents containing the term). In simpler words, it's a sophisticated measure of how special a keyword is to your content.

## Sections

- [Knowledge Navigator Status](knowledge-navigator-status.md)

- [Knowledge Navigator Scheduling](knowledge-navigator-scheduling.md)

- [Knowledge Navigator Include/Exclude Settings](knowledge-navigator-include-exclude-settings.md)

- [Knowledge Navigator Enhanced Response Settings](knowledge-navigator-enhanced-response-settings.md)

---

- **[Back to the Overview](/overview.md)**
