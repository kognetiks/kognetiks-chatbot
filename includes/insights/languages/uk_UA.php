<?php
/**
 * Kognetiks Insights - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: Ukrainian
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words;

// Initialize the Ukrainian language sentiment words dictionary
$sentiment_words = array(
    // Positive words (score: 1-5)
    'відмінний' => 5,
    'неймовірний' => 5,
    'чудовий' => 5,
    'фантастичний' => 5,
    'блискучий' => 5,
    'чудово' => 4,
    'хороший' => 3,
    'приємний' => 3,
    'щасливий' => 3,
    'задоволений' => 3,
    'вдоволений' => 3,
    'корисний' => 3,
    'дякую' => 2,
    'дякувати' => 2,
    'так' => 1,
    'добре' => 1,
    'ок' => 1,
    'гаразд' => 1,
    
    // Negative words (score: -1 to -5)
    'жахливий' => -5,
    'страшний' => -5,
    'жалюгідний' => -5,
    'гірший' => -5,
    'поганий' => -3,
    'бідний' => -3,
    'неправильний' => -3,
    'розчарований' => -3,
    'нещасливий' => -3,
    'вибачте' => -2
);

// Initialize negator words (words that reverse sentiment)
$negator_words = array(
    'не',
    'ніколи',
    'ні',
    'жоден',
    'нічого',
    'ні',
    'ні',
    'ледве',
    'ледве',
    'майже',
    'не є',
    'не був',
    'не були',
    'не мають',
    'не має',
    'не було',
    'не зробить',
    'не зробив би',
    'не',
    'не зробив',
    'не може',
    'не може',
    'не міг',
    'не повинен',
    'може не',
    'не повинен'
);

// Initialize intensifier words (words that amplify sentiment)
$intensifier_words = array(
    'дуже' => 1.5,
    'справді' => 1.5,
    'надзвичайно' => 1.5,
    'абсолютно' => 1.5,
    'повністю' => 1.5,
    'цілком' => 1.5
);

