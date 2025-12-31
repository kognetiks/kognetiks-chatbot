<?php
/**
 * Kognetiks Insights - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: Polish
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words;

// Initialize the Polish language sentiment words dictionary
$sentiment_words = array(
    'doskonały' => 5,
    'niesamowity' => 5,
    'wspaniały' => 5,
    'fantastyczny' => 5,
    'błyskotliwy' => 5,
    'świetny' => 4,
    'dobry' => 3,
    'miły' => 3,
    'szczęśliwy' => 3,
    'zadowolony' => 3,
    'usatysfakcjonowany' => 3,
    'pomocny' => 3,
    'dzięki' => 2,
    'dziękuję' => 2,
    'tak' => 1,
    'okej' => 1,
    'ok' => 1,
    'dobrze' => 1,
    'okropny' => -5,
    'straszny' => -5,
    'okropny' => -5,
    'najgorszy' => -5,
    'zły' => -3,
    'słaby' => -3,
    'zły' => -3,
    'rozczarowany' => -3,
    'nieszczęśliwy' => -3,
    'przepraszam' => -2
);

// Initialize the Polish language negator words dictionary
$negator_words = array(
    'nie',
    'nigdy',
    'nie',
    'żaden',
    'nic',
    'ani',
    'ani',
    'ledwie',
    'zaledwie',
    'ledwie',
    'nie',
    'nie jest',
    'nie był',
    'nie byli',
    'nie mają',
    'nie ma',
    'nie miał',
    'nie będzie',
    'nie chciałby',
    'nie',
    'nie zrobił',
    'nie może',
    'nie może',
    'nie mógł',
    'nie powinien',
    'może nie',
    'nie wolno'
);

// Initialize the Polish language intensifier words dictionary
$intensifier_words = array(
    'bardzo' => 1.5,
    'naprawdę' => 1.5,
    'niezwykle' => 1.5,
    'absolutnie' => 1.5,
    'całkowicie' => 1.5,
    'zupełnie' => 1.5
);
