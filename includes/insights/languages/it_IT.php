<?php
/**
 * Kognetiks Insights - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: Italian
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words;

// Initialize the Italian language sentiment words dictionary
$sentiment_words = array(
    'eccellente' => 5,
    'incredibile' => 5,
    'meraviglioso' => 5,
    'fantastico' => 5,
    'brillante' => 5,
    'ottimo' => 4,
    'buono' => 3,
    'piacevole' => 3,
    'felice' => 3,
    'contento' => 3,
    'soddisfatto' => 3,
    'utile' => 3,
    'grazie' => 2,
    'ringraziare' => 2,
    'sì' => 1,
    'va bene' => 1,
    'ok' => 1,
    'bene' => 1,
    'terribile' => -5,
    'orribile' => -5,
    'pessimo' => -5,
    'peggiore' => -5,
    'cattivo' => -3,
    'scarso' => -3,
    'sbagliato' => -3,
    'deluso' => -3,
    'infelice' => -3,
    'mi dispiace' => -2
);

// Initialize the Italian language negator words dictionary
$negator_words = array(
    'non',
    'mai',
    'no',
    'nessuno',
    'niente',
    'né',
    'né',
    'a malapena',
    'appena',
    'a stento',
    'non',
    'non è',
    'non era',
    'non erano',
    'non hanno',
    'non ha',
    'non aveva',
    'non lo farà',
    'non lo farebbe',
    'non',
    'non ha fatto',
    'non può',
    'non può',
    'non poteva',
    'non dovrebbe',
    'potrebbe non',
    'non deve'
);

// Initialize the Italian language intensifier words dictionary
$intensifier_words = array(
    'molto' => 1.5,
    'davvero' => 1.5,
    'estremamente' => 1.5,
    'assolutamente' => 1.5,
    'completamente' => 1.5,
    'totalmente' => 1.5
);
