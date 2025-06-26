<?php
/**
 * Kognetiks Analytics - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: Russian
 * 
 * @package kognetiks-analytics
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words;

$sentiment_words = array(
    'отличный' => 5,
    'удивительный' => 5,
    'замечательный' => 5,
    'фантастический' => 5,
    'блестящий' => 5,
    'прекрасный' => 4,
    'хороший' => 3,
    'приятный' => 3,
    'счастливый' => 3,
    'довольный' => 3,
    'удовлетворённый' => 3,
    'полезный' => 3,
    'спасибо' => 2,
    'благодарю' => 2,
    'да' => 1,
    'хорошо' => 1,
    'ок' => 1,
    'в порядке' => 1,
    'ужасный' => -5,
    'страшный' => -5,
    'отвратительный' => -5,
    'худший' => -5,
    'плохой' => -3,
    'бедный' => -3,
    'неправильный' => -3,
    'разочарованный' => -3,
    'несчастный' => -3,
    'извините' => -2
);

// Initialize the Russian language negator words dictionary
$negator_words = array(
    'не',
    'никогда',
    'нет',
    'ни один',
    'ничего',
    'ни',
    'ни',
    'едва',
    'еле-еле',
    'едва ли',
    'не',
    'не является',
    'не был',
    'не были',
    'не имеют',
    'не имеет',
    'не имел',
    'не будет',
    'не стал бы',
    'не',
    'не сделал',
    'не может',
    'не может',
    'не мог',
    'не следует',
    'может не',
    'нельзя'
);

// Initialize the Russian language intensifier words dictionary
$intensifier_words = array(
    'очень' => 1.5,
    'действительно' => 1.5,
    'чрезвычайно' => 1.5,
    'абсолютно' => 1.5,
    'полностью' => 1.5,
    'совершенно' => 1.5
);
