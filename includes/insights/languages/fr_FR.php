<?php
/**
 * Kognetiks Insights - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: French
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words;

// Initialize the French language sentiment words dictionary
$sentiment_words = array(
    'excellent' => 5,
    'incroyable' => 5,
    'merveilleux' => 5,
    'fantastique' => 5,
    'brillant' => 5,
    'super' => 4,
    'bon' => 3,
    'agréable' => 3,
    'heureux' => 3,
    'content' => 3,
    'satisfait' => 3,
    'utile' => 3,
    'merci' => 2,
    'remercier' => 2,
    'oui' => 1,
    "d'accord" => 1,
    'ok' => 1,
    'bien' => 1,
    'terrible' => -5,
    'horrible' => -5,
    'affreux' => -5,
    'pire' => -5,
    'mauvais' => -3,
    'pauvre' => -3,
    'faux' => -3,
    'déçu' => -3,
    'malheureux' => -3,
    'désolé' => -2
);

// Initialize the French language negator words dictionary
$negator_words = array(
    'ne',
    'jamais',
    'non',
    'aucun',
    'rien',
    'ni',
    'ni',
    'à peine',
    'à peine',
    'rarement',
    'ne pas',
    "n'est pas",
    "n'était pas",
    "n'étaient pas",
    "n'ont pas",
    "n'a pas",
    "n'avait pas",
    'ne va pas',
    'ne voudrait pas',
    'ne',
    "n'a pas",
    'ne peut pas',
    'ne peut pas',
    'ne pouvait pas',
    'ne devrait pas',
    'ne pourrait pas',
    'ne doit pas'
);

// Initialize the French language intensifier words dictionary
$intensifier_words = array(
    'très' => 1.5,
    'vraiment' => 1.5,
    'extrêmement' => 1.5,
    'absolument' => 1.5,
    'complètement' => 1.5,
    'totalement' => 1.5
);
