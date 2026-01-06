<?php
/**
 * Kognetiks Insights - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: Russian
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words, $fallback_like, $fallback_failure_and_apology, $fallback_deflection_and_generic_assistant_behavior, $fallback_clarification_requests_beyond_rephrasing, $fallback_to_external_help, $fallback_safety_refusal_and_policy_related, $fallback_conversation_breakdown;

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

/**
 * Global unanswered questions patterns.
 */
$fallback_like = [
    '%я не следую%',
    '%не могли бы вы спросить%',
    '%это неясно%',
    '%не совсем понял%',
    '%не могли бы вы попробовать переформулировать%',
    '%не могли бы вы переформулировать%',
    '%попробуйте сформулировать%',
    '%пожалуйста уточните%',
];

/*
 * Global explicit failure and apology patterns. 
 */
$fallback_failure_and_apology = [
    '%я не знаю%',
    '%я не уверен%',
    '%я не уверена%',
    '%я не могу помочь с этим%',
    '%я не могу помочь с этим%',
    '%у меня недостаточно информации%',
    '%у меня нет этой информации%',
    '%у меня нет доступа%',
    '%у меня нет способности%',
    '%у меня нет деталей%',
    '%я не могу%',
    '%я не могу ответить%',
    '%я не могу ответить%',
];

/*
 * Global deflection and generic assistant behavior patterns.
 */
$fallback_deflection_and_generic_assistant_behavior = [
    '%как ии%',
    '%я ии%',
    '%я языковая модель%',
    '%у меня нет личных мнений%',
    '%у меня нет реального времени%',
    '%у меня нет просмотра%',
    '%у меня нет контекста%',
];

/*
 * Global clarification requests beyond rephrasing patterns.
 */
$fallback_clarification_requests_beyond_rephrasing = [
    '%можете ли вы предоставить больше деталей%',
    '%можете ли вы дать больше информации%',
    '%можете ли вы быть более конкретным%',
    '%что вы имеете в виду%',
    '%можете ли вы развить%',
    '%мне нужно больше контекста%',
    '%недостаточно контекста%',
];

/*
 * Global fallback to external help patterns.
 */
$fallback_to_external_help = [
    '%вы можете связаться%',
    '%вам следует связаться%',
    '%проверьте со службой поддержки%',
    '%обратитесь в службу поддержки%',
    '%проконсультируйтесь со специалистом%',
    '%посетите официальный сайт%',
];

/*
 * Global safety, refusal, and policy related patterns.
 */
$fallback_safety_refusal_and_policy_related = [
    '%я не могу помочь с этим запросом%',
    '%я не могу помочь с этим запросом%',
    '%я не могу выполнить%',
    '%этот запрос не разрешен%',
    '%я не могу предоставить это%',
];

/*
 * Global conversation breakdown patterns.
 */
$fallback_conversation_breakdown = [
    '%давайте сменим тему%',
    '%я, возможно, неправильно понимаю%',
    '%это не кажется связанным%',
    '%это выходит за рамки%',
];
