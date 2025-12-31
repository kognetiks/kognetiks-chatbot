<?php
/**
 * Kognetiks Insights - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: German
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words;

// Initialize the German language sentiment words dictionary
$sentiment_words = array(
    'ausgezeichnet' => 5,
    'erstaunlich' => 5,
    'wunderbar' => 5,
    'fantastisch' => 5,
    'brillant' => 5,
    'großartig' => 4,
    'gut' => 3,
    'nett' => 3,
    'glücklich' => 3,
    'zufrieden' => 3,
    'zufrieden' => 3,
    'hilfreich' => 3,
    'danke' => 2,
    'danken' => 2,
    'ja' => 1,
    'okay' => 1,
    'ok' => 1,
    'in Ordnung' => 1,
    'schrecklich' => -5,
    'grauenhaft' => -5,
    'furchtbar' => -5,
    'schlimmste' => -5,
    'schlecht' => -3,
    'armselig' => -3,
    'falsch' => -3,
    'enttäuscht' => -3,
    'unglücklich' => -3,
    'entschuldigung' => -2
);

// Initialize the German language negator words dictionary
$negator_words = array(
    'nicht',
    'niemals',
    'nein',
    'keiner',
    'nichts',
    'weder',
    'noch',
    'kaum',
    'gerade so',
    'kaum',
    'nicht',
    'ist nicht',
    'war nicht',
    'waren nicht',
    'haben nicht',
    'hat nicht',
    'hatte nicht',
    'wird nicht',
    'würde nicht',
    'nicht',
    'tat nicht',
    'kann nicht',
    'kann nicht',
    'konnte nicht',
    'sollte nicht',
    'vielleicht nicht',
    'darf nicht'
);

// Initialize the German language intensifier words dictionary
$intensifier_words = array(
    'sehr' => 1.5,
    'wirklich' => 1.5,
    'äußerst' => 1.5,
    'absolut' => 1.5,
    'vollständig' => 1.5,
    'total' => 1.5
);
