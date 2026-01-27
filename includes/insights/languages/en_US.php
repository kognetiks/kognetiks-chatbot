<?php
/**
 * Kognetiks Insights - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words, $fallback_like, $fallback_failure_and_apology, $fallback_deflection_and_generic_assistant_behavior, $fallback_clarification_requests_beyond_rephrasing, $fallback_to_external_help, $fallback_safety_refusal_and_policy_related, $fallback_conversation_breakdown;

// Initialize the English language sentiment words dictionary
$sentiment_words = array(
    // Positive words (score: 1-5)
    'excellent' => 5,
    'amazing' => 5,
    'wonderful' => 5,
    'fantastic' => 5,
    'brilliant' => 5,
    'great' => 4,
    'good' => 3,
    'nice' => 3,
    'happy' => 3,
    'pleased' => 3,
    'satisfied' => 3,
    'helpful' => 3,
    'thanks' => 2,
    'thank' => 2,
    'yes' => 1,
    'okay' => 1,
    'ok' => 1,
    'fine' => 1,
    
    // Negative words (score: -1 to -5)
    'terrible' => -5,
    'horrible' => -5,
    'awful' => -5,
    'worst' => -5,
    'bad' => -3,
    'poor' => -3,
    'wrong' => -3,
    'disappointed' => -3,
    'unhappy' => -3,
    'sorry' => -2
);

// Initialize negator words (words that reverse sentiment)
$negator_words = array(
    'not',
    'never',
    'no',
    'none',
    'nothing',
    'neither',
    'nor',
    'hardly',
    'barely',
    'scarcely',
    'doesn\'t',
    'isn\'t',
    'wasn\'t',
    'weren\'t',
    'haven\'t',
    'hasn\'t',
    'hadn\'t',
    'won\'t',
    'wouldn\'t',
    'don\'t',
    'doesn\'t',
    'didn\'t',
    'can\'t',
    'cannot',
    'couldn\'t',
    'shouldn\'t',
    'wouldn\'t',
    'mightn\'t',
    'mustn\'t'
);

// Initialize intensifier words (words that amplify sentiment)
$intensifier_words = array(
    'very' => 1.5,
    'really' => 1.5,
    'extremely' => 1.5,
    'absolutely' => 1.5,
    'completely' => 1.5,
    'totally' => 1.5
);

/**
 * Global unanswered questions patterns.
 */
$fallback_like = [
    '%i\'m not following%',
    '%could you ask that%',
    '%that\'s unclear%',
    '%didn\'t quite catch%',
    '%could you try rephras%',
    '%could you rephrase%',
    '%try phrasing%',
    '%please clarify%',
];

/*
 * Global explicit failure and apology patterns. 
 */
$fallback_failure_and_apology = [
    '%i\'m sorry%',
    '%i don\'t know%',
    '%i am not sure%',
    '%i\'m not sure%',
    '%i can\'t help with that%',
    '%i cannot help with that%',
    '%i don\'t have enough information%',
    '%i don\'t have that information%',
    '%i don\'t have access%',
    '%i don\'t have the ability%',
    '%i don\'t have details%',
    '%i\'m unable to%',
    '%i can\'t answer%',
    '%i cannot answer%',
];

/*
 * Global deflection and generic assistant behavior patterns.
 */
$fallback_deflection_and_generic_assistant_behavior = [
    '%as an ai%',
    '%i am an ai%',
    '%i am a language model%',
    '%i don\'t have personal opinions%',
    '%i don\'t have real-time%',
    '%i don\'t have browsing%',
    '%i don\'t have context%',
];

/*
 * Globaal clarification requests beyond rephrasing patterns.
 */
$fallback_clarification_requests_beyond_rephrasing = [
    '%can you provide more details%',
    '%can you give more information%',
    '%can you be more specific%',
    '%what do you mean%',
    '%can you elaborate%',
    '%i need more context%',
    '%not enough context%',
];

/*
 * Global fallback to external help patterns.
 */
$fallback_to_external_help = [
    '%you may want to contact%',
    '%you should contact%',
    '%check with customer support%',
    '%reach out to support%',
    '%consult a professional%',
    '%visit the official website%',
];

/*
 * Global safety, refusal, and policy related patterns.
 */
$fallback_safety_refusal_and_policy_related = [
    '%i can\'t assist with that request%',
    '%i can\'t help with this request%',
    '%i\'m unable to comply%',
    '%this request is not allowed%',
    '%i can\'t provide that%',
];

/*
 * Global conversation breakdown patterns.
 */
$fallback_conversation_breakdown = [
    '%let\'s change the topic%',
    '%i might be misunderstanding%',
    '%that doesn\'t seem related%',
    '%that\'s outside the scope%',
];
