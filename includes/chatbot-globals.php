<?php
/**
 * Kognetiks Chatbot for WordPress - Globals - Ver 1.6.5
 *
 * This file contains the code for table actions for reporting
 * to display the Chatbot on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Declare the $stopWords array as global
// List of common stop words to be ignored
global $stopWords;
$stopWords = [
    "a", "about", "above", "after", "again", "against", "all", "am", "an", "and", "any", "are", "aren't", "as", "at",
    "b", "be", "because", "been", "before", "being", "below", "between", "both", "but", "by",
    "c", "can", "can't", "cannot", "could", "couldn't",
    "d", "did", "didn't", "do", "does", "doesn't", "doing", "don't", "down", "during",
    "e", "each",
    "f", "few", "for", "from", "further",
    "g",
    "h", "had", "hadn't", "has", "hasn't", "have", "haven't", "having", "he", "he'd", "he'll", "he's", "her", "here", "here's", "hers", "herself", "him", "himself", "his", "how", "how's",
    "i", "i'd", "i'll", "i'm", "i've", "if", "in", "into", "is", "isn't", "it", "it's", "its", "itself",
    "j", "k",
    "l", "let's",
    "m", "me", "more", "most", "mustn't", "my", "myself",
    "n", "no", "nor", "not",
    "o", "of", "off", "on", "once", "only", "or", "other", "ought", "our", "ours" ,"ourselves", "out", "over", "own",
    "p", "q",
    "r", "re",
    "s", "same", "shan't", "she", "she'd", "she'll", "she's", "should", "shouldn't", "so", "some", "such",
    "t", "than", "that", "that's", "the", "their", "theirs", "them", "themselves", "then", "there", "there's", "these", "they", "they'd", "they'll", "they're", "they've", "this", "those", "through", "to", "too",
    "u", "under", "until", "up",
    "v", "very",
    "w", "was", "wasn't", "we", "we'd", "we'll", "we're", "we've", "were", "weren't", "what", "what's", "when", "when's", "where", "where's", "which", "while", "who", "who's", "whom", "why", "why's", "with", "won't", "would", "wouldn't",
    "x",
    "y", "you", "you'd", "you'll", "you're", "you've", "your", "yours", "yourself", "yourselves",
    "z"
];


// Declare the $learningMessages array as global
global $learningMessages;
$learningMessages = [
    " Also know that I'm still learning, but more information could be found ",
    " Please be aware that I'm in the process of learning, but more information could be found ",
    " Just a heads up, I'm continuously improving, in the meantime check ",
    " Keep in mind that I'm still learning the ropes, but don't hesitate to check out ",
    " I'm in a state of constant learning, for now check out ",
    " Remember, I'm on a learning journey, so you can revisit anytime you'd like. However, you might try ",
    " I'm in the learning phase, so your patience is appreciated. However you might find help here "
];

// Declare the $errorResponses array as global
global $errorResponses;
$errorResponses = [
    " It seems there may have been an issue with the OpenAI API. Let's try again later.",
    " Unfortunately, we might have encountered a problem with the OpenAI API. Please give it another shot in a little while.",
    " I apologize, but it appears there's a hiccup with the OpenAI API at the moment. We can attempt this again later.",
    " The OpenAI API seems to be experiencing difficulties right now. We can come back to this when it's resolved.",
    " I'm sorry, but it seems like there's an error from the OpenAI API side. Please retry in a bit.",
    " There might be a temporary issue with the OpenAI API. Please try your request again in a little while.",
    " The OpenAI API encountered an error, but don't worry, it happens. Let's give it another shot later.",
    " It looks like there could be a technical problem with the OpenAI API. Feel free to try again in a bit to see if things are working smoothly."
];

// Declare the $chatbot_chatgpt_bot_prompt as global - Ver 1.6.6
global $chatbot_chatgpt_bot_prompt;
$chatbot_chatgpt_bot_prompt = [
    "Enter your question ...",
    "Ask me a question ...",
    "I'm listening ...",
    "I'm here to help ...",
    "Please share your thoughts ...",
    "Feel free to ask me anything ...",
    "Go ahead and ask away ...",
    "What are you thinking ...",
    "Any thoughts to share ...",
    "Any specific questions ...",
    "What are you pondering ...",
    "What's on your mind ...",
    "What would you like to talk about ..."
];
