<?php
/**
 * Kognetiks Analytics - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: Portuguese
 * 
 * @package kognetiks-analytics
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words;

// Initialize the Portuguese language sentiment words dictionary
$sentiment_words = array(
    'excelente' => 5,
    'incrível' => 5,
    'maravilhoso' => 5,
    'fantástico' => 5,
    'brilhante' => 5,
    'ótimo' => 4,
    'bom' => 3,
    'agradável' => 3,
    'feliz' => 3,
    'satisfeito' => 3,
    'satisfeito' => 3,
    'útil' => 3,
    'obrigado' => 2,
    'agradecer' => 2,
    'sim' => 1,
    'tudo bem' => 1,
    'ok' => 1,
    'bem' => 1,
    'terrível' => -5,
    'horrível' => -5,
    'péssimo' => -5,
    'pior' => -5,
    'ruim' => -3,
    'pobre' => -3,
    'errado' => -3,
    'decepcionado' => -3,
    'infeliz' => -3,
    'desculpa' => -2
);

// Initialize the Portuguese language negator words dictionary
$negator_words = array(
    'não',
    'nunca',
    'não',
    'nenhum',
    'nada',
    'nem',
    'nem',
    'dificilmente',
    'mal',
    'raramente',
    'não',
    'não é',
    'não foi',
    'não foram',
    'não têm',
    'não tem',
    'não tinha',
    'não vai',
    'não faria',
    'não',
    'não fez',
    'não pode',
    'não pode',
    'não pôde',
    'não deveria',
    'talvez não',
    'não deve'
);

// Initialize the Portuguese language intensifier words dictionary
$intensifier_words = array(
    'muito' => 1.5,
    'realmente' => 1.5,
    'extremamente' => 1.5,
    'absolutamente' => 1.5,
    'completamente' => 1.5,
    'totalmente' => 1.5
);
