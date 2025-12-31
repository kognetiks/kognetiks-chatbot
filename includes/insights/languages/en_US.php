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
global $sentiment_words, $negator_words, $intensifier_words;

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
