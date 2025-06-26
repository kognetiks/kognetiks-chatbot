<?php
/**
 * Kognetiks Analytics - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: Czech
 * 
 * @package kognetiks-analytics
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words;

// Initialize the Czech language sentiment words dictionary
$sentiment_words = array(
    'vynikající' => 5,
    'úžasný' => 5,
    'nádherný' => 5,
    'fantastický' => 5,
    'skvělý' => 5,
    'skvělý' => 4,
    'dobrý' => 3,
    'pěkný' => 3,
    'šťastný' => 3,
    'potěšený' => 3,
    'spokojený' => 3,
    'nápomocný' => 3,
    'díky' => 2,
    'děkuji' => 2,
    'ano' => 1,
    'dobře' => 1,
    'ok' => 1,
    'v pořádku' => 1,
    'strašný' => -5,
    'hrozný' => -5,
    'otřesný' => -5,
    'nejhorší' => -5,
    'špatný' => -3,
    'chudý' => -3,
    'špatný' => -3,
    'zklamaný' => -3,
    'nešťastný' => -3,
    'promiň' => -2
);

// Initialize the Czech language negator words dictionary
$negator_words = array(
    'ne',
    'nikdy',
    'ne',
    'žádný',
    'nic',
    'ani',
    'ani',
    'stěží',
    'sotva',
    'sotva',
    'ne',
    'není',
    'nebyl',
    'nebyli',
    'nemají',
    'nemá',
    'neměl',
    'nebude',
    'neby',
    'ne',
    'neudělal',
    'nemůže',
    'nemůže',
    'nemohl',
    'neměl by',
    'možná ne',
    'nesmí'
);

// Initialize the Czech language intensifier words dictionary
$intensifier_words = array(
    'velmi' => 1.5,
    'opravdu' => 1.5,
    'extrémně' => 1.5,
    'naprosto' => 1.5,
    'zcela' => 1.5,
    'úplně' => 1.5
);
