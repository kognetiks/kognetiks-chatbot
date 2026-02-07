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
global $sentiment_words, $negator_words, $intensifier_words, $fallback_like, $fallback_failure_and_apology, $fallback_deflection_and_generic_assistant_behavior, $fallback_clarification_requests_beyond_rephrasing, $fallback_to_external_help, $fallback_safety_refusal_and_policy_related, $fallback_conversation_breakdown;

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

/**
 * Global unanswered questions patterns.
 */
$fallback_like = [
    '%я не слідкую%',
    '%чи могли б ви запитати це%',
    '%це незрозуміло%',
    '%не зовсім зрозумів%',
    '%чи могли б ви спробувати переформулювати%',
    '%чи могли б ви переформулювати%',
    '%спробуйте сформулювати%',
    '%будь ласка уточніть%',
];

/*
 * Global explicit failure and apology patterns. 
 */
$fallback_failure_and_apology = [
    '%вибачте%',
    '%я не знаю%',
    '%я не впевнений%',
    '%я не впевнена%',
    '%я не можу допомогти з цим%',
    '%я не можу допомогти з цим%',
    '%у мене недостатньо інформації%',
    '%у мене немає цієї інформації%',
    '%у мене немає доступу%',
    '%у мене немає здатності%',
    '%у мене немає деталей%',
    '%я не можу%',
    '%я не можу відповісти%',
    '%я не можу відповісти%',
];

/*
 * Global deflection and generic assistant behavior patterns.
 */
$fallback_deflection_and_generic_assistant_behavior = [
    '%як ші%',
    '%я ші%',
    '%я мова модель%',
    '%у мене немає особистих думок%',
    '%у мене немає реального часу%',
    '%у мене немає перегляду%',
    '%у мене немає контексту%',
];

/*
 * Global clarification requests beyond rephrasing patterns.
 */
$fallback_clarification_requests_beyond_rephrasing = [
    '%чи можете ви надати більше деталей%',
    '%чи можете ви дати більше інформації%',
    '%чи можете ви бути більш конкретним%',
    '%що ви маєте на увазі%',
    '%чи можете ви розвинути%',
    '%мені потрібно більше контексту%',
    '%недостатньо контексту%',
];

/*
 * Global fallback to external help patterns.
 */
$fallback_to_external_help = [
    '%ви можете зв\'язатися%',
    '%вам слід зв\'язатися%',
    '%перевірте зі службою підтримки%',
    '%зверніться до служби підтримки%',
    '%консультуйтеся зі спеціалістом%',
    '%відвідайте офіційний веб-сайт%',
];

/*
 * Global safety, refusal, and policy related patterns.
 */
$fallback_safety_refusal_and_policy_related = [
    '%я не можу допомогти з цим запитом%',
    '%я не можу допомогти з цим запитом%',
    '%я не можу виконати%',
    '%цей запит не дозволено%',
    '%я не можу надати це%',
];

/*
 * Global conversation breakdown patterns.
 */
$fallback_conversation_breakdown = [
    '%давайте змінимо тему%',
    '%я, можливо, неправильно розумію%',
    '%це не здається пов\'язаним%',
    '%це виходить за межі%',
];
