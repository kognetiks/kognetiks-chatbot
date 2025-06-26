<?php
/**
 * Kognetiks Analytics - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: Spanish
 * 
 * @package kognetiks-analytics
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words;

// Initialize the Spanish language sentiment words dictionary
$sentiment_words = array(
    'excelente' => 5,
    'increíble' => 5,
    'maravilloso' => 5,
    'fantástico' => 5,
    'brillante' => 5,
    'genial' => 4,
    'bueno' => 3,
    'agradable' => 3,
    'feliz' => 3,
    'satisfecho' => 3,
    'satisfecho' => 3,
    'útil' => 3,
    'gracias' => 2,
    'agradecer' => 2,
    'sí' => 1,
    'vale' => 1,
    'ok' => 1,
    'bien' => 1,
    'terrible' => -5,
    'horrible' => -5,
    'espantoso' => -5,
    'peor' => -5,
    'malo' => -3,
    'pobre' => -3,
    'equivocado' => -3,
    'decepcionado' => -3,
    'infeliz' => -3,
    'lo siento' => -2
);

// Initialize the Spanish language negator words dictionary
$negator_words = array(
    'no',
    'nunca',
    'no',
    'ninguno',
    'nada',
    'ni',
    'ni',
    'apenas',
    'apenas',
    'escasamente',
    'no',
    'no es',
    'no fue',
    'no fueron',
    'no han',
    'no ha',
    'no había',
    'no lo hará',
    'no lo haría',
    'no',
    'no lo hizo',
    'no puede',
    'no puede',
    'no pudo',
    'no debería',
    'podría no',
    'no debe'
);

// Initialize the Spanish language intensifier words dictionary
$intensifier_words = array(
    'muy' => 1.5,
    'realmente' => 1.5,
    'extremadamente' => 1.5,
    'absolutamente' => 1.5,
    'completamente' => 1.5,
    'totalmente' => 1.5
);
