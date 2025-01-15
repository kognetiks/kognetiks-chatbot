<?php
/**
 * Kognetiks Chatbot - Globals - Ver 1.6.5
 *
 * This file contains the code for global variables used
 * by the program.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Declare the $stopWords array as global
// List of common stop words to be ignored
global $stopWords;
$stopWords = [
    "a", "about", "above", "after", "again", "against", "ain't", "all", "am", "an", "and", "another", "any", "are", "aren't", "as", "at",
    "b", "be", "because", "been", "before", "being", "below", "between", "both", "but", "by",
    "c", "can", "cannot", "can't", "could", "couldn't",
    "d", "dare", "did", "didn't", "different", "do", "does", "doesn't", "doing", "don't", "down", "during",
    "e", "each", "either", "enough", "every", "everybody", "everyone", "everything",
    "f", "few", "first", "for", "from", "further",
    "g", "get", "give", "go", "going", "got", 
    "h", "had", "hadn't", "has", "hasn't", "have", "haven't", "having", "he", "he'd", "he'll", "her", "here", "here's", "hers", "herself", "he's", "him", "himself", "his", "how", "how's",
    "i", "i'd", "if", "i'll", "i'm", "in", "into", "is", "isn't", "it", "its", "it's", "itself", "i've",
    "j", "just",
    "k",
    "l", "least", "less", "let's", "little", "lot", "lots",
    "m", "many", "may", "me", "might", "mightn't", "more", "most", "most", "much", "must", "mustn't", "my", "myself",
    "n", "need", "needn't", "neither", "never", "no", "none", "nor", "not", "now",
    "o", "of", "off", "on", "once", "only", "or", "other", "ought", "oughtn't", "our", "ours", "ourselves", "out", "over", "own",
    "p", "plenty",
    "q",
    "r", "re", "really", "right",
    "s", "same", "several", "shall", "shan't", "she", "she'd", "she'll", "she's", "should", "shouldn't", "so", "some", "somebody", "someone", "something", "sometime", "somewhere", "such",
    "t", "take", "than", "that", "that's", "the", "their", "theirs", "them", "themselves", "then", "there", "there's", "these", "they", "they'd", "they'll", "they're", "they've", "thing", "things", "this", "those", "through", "time", "to", "too", 
    "u", "under", "until", "up", "us",
    "v", "various", "very",
    "w", "was", "wasn't", "we", "we'd", "we'll", "were", "we're", "weren't", "we've", "what", "what's", "when", "when's", "where", "where's", "which", "while", "who", "whom", "whom", "who's", "why", "why's", "will", "with", "won't", "would", "wouldn't",
    "x",
    "y", "yet", "you", "you'd", "you'll", "your", "you're", "yours", "your's", "yourself", "yourselves", "you've",
    "z", 
];

// Global abbreviations array
global $abbreviations;
$abbreviations = [
    // Latin-based Abbreviations
    "i.e.", "e.g.", "etc.", "et al.", "N.B.", "cf.", "vs.", "viz.", "a.m.", "p.m.",
    // Time and Date
    "AD", "BC", "CE", "BCE", "GMT", "EST", "UTC", 
    // Measurement
    "lb", "oz", "km", "cm", "ml", "ft",
    // Titles
    "Mr.", "Mrs.", "Ms.", "Dr.", "Prof.",
    // Miscellaneous
    "FAQ", "DIY", "ASAP", "FYI", "RSVP", "P.S.", "AKA", "DOB", "TBD", "TBA", "ETA", "BTW",
];

// Declare the $learningMessages array as global
global $learningMessages;
$learningMessages = [
    " Please note that I'm continuously enhancing my abilities. In the meantime, you can find more information here: ",
    " I am currently in the process of expanding my knowledge. For additional details, please check: ",
    " Just a heads up - I am actively improving each day. Meanwhile, you can explore more information at: ",
    " As I am still mastering the ropes, I encourage you to visit: ",
    " I am constantly evolving and learning. For now, you can check out: ",
    " Keep in mind, I'm on a journey of continuous learning. Feel free to revisit any time. Meanwhile, you might find this useful: ",
    " I'm still in the learning phase, so your patience is greatly appreciated. You might find what you're looking for here: "
];

// Declare the $errorResponses array as global
global $errorResponses;
$errorResponses = [
    " It seems there may have been an issue with the API. Let's try again later.",
    " Unfortunately, we might have encountered a problem with the API. Please give it another shot in a little while.",
    " I apologize, but it appears there's a hiccup with the API at the moment. We can attempt this again later.",
    " The API seems to be experiencing difficulties right now. We can come back to this when it's resolved.",
    " I'm sorry, but it seems like there's an error from the API side. Please retry in a bit.",
    " There might be a temporary issue with the API. Please try your request again in a little while.",
    " The API encountered an error, but don't worry, it happens. Let's give it another shot later.",
    " It looks like there could be a technical problem with the API. Feel free to try again in a bit to see if things are working smoothly."
];

// Declare the $no_matching_content_response array as global
global $no_matching_content_response;
$no_matching_content_response = [
    " I'm sorry, but I couldn't find any relevant information on that topic. Would you like to try something else?",
    " Unfortunately, I couldn't locate any relevant information on that topic. Would you like to ask something else?",
    " I'm afraid I couldn't find any relevant information on that topic. Would you like to try another question?",
    " I couldn't find any relevant information on that topic. Would you like to ask something else?",
    " I'm sorry, but I couldn't find any information on that topic. Would you like to try another question?",
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

// Declare the $chatbot_markov_chain_fallback_response as global - Ver 2.1.6.1
global $chatbot_markov_chain_fallback_response;
$chatbot_markov_chain_fallback_response = [
    "Sorry, I couldn't find any relevant information to respond to your query. Can you try rephrasing or asking something else?",
    "I'm not sure I have the right information for that. Could you clarify or ask differently?",
    "It seems I don't have the exact details you're looking for. Could you rephrase the question?",
    "I couldn't find anything on that topic at the moment. Would you mind trying again?",
    "I'm afraid I don't have enough information on that. Could you provide more details?",
    "It looks like I'm missing the answer for that. Perhaps you could rephrase or ask something else?"
];

// Declare the $chatbotFallbackResponses array as global
$chatbotFallbackResponses = [
    "I didn't quite catch that. Could you try rephrasing?",
    "Hmm, I'm not sure I understood. Can you explain differently?",
    "I'm not following. Could you ask that in another way?",
    "Sorry, that doesn't make sense to me. Could you clarify?",
    "I may have missed your point. Could you say it differently?",
    "I'm having trouble understanding. Could you ask another way?",
    "That doesn't seem clear to me. Can you rephrase?",
    "I'm not sure what you mean. Could you try explaining it differently?",
    "I didn't get that. Could you ask again in another way?",
    "I'm a bit confused. Can you provide more details or rephrase?",
    "I'm sorry, I don't understand. Could you try rewording it?",
    "That's unclear to me. Could you ask it in a different way?"
];
