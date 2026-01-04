<?php
/**
 * Kognetiks Insights - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: Portuguese
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words, $fallback_like, $fallback_failure_and_apology, $fallback_deflection_and_generic_assistant_behavior, $fallback_clarification_requests_beyond_rephrasing, $fallback_to_external_help, $fallback_safety_refusal_and_policy_related, $fallback_conversation_breakdown;

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

/**
 * Global unanswered questions patterns.
 */
$fallback_like = [
    '%não estou seguindo%',
    '%você poderia perguntar isso%',
    '%isso não está claro%',
    '%não entendi bem%',
    '%você poderia tentar reformular%',
    '%você poderia reformular%',
    '%tente formular%',
    '%por favor esclareça%',
];

/*
 * Global explicit failure and apology patterns. 
 */
$fallback_failure_and_apology = [
    '%não sei%',
    '%não tenho certeza%',
    '%não tenho certeza%',
    '%não posso ajudar com isso%',
    '%não posso ajudar com isso%',
    '%não tenho informações suficientes%',
    '%não tenho essas informações%',
    '%não tenho acesso%',
    '%não tenho a capacidade%',
    '%não tenho detalhes%',
    '%não sou capaz%',
    '%não posso responder%',
    '%não posso responder%',
];

/*
 * Global deflection and generic assistant behavior patterns.
 */
$fallback_deflection_and_generic_assistant_behavior = [
    '%como uma ia%',
    '%sou uma ia%',
    '%sou um modelo de linguagem%',
    '%não tenho opiniões pessoais%',
    '%não tenho tempo real%',
    '%não tenho navegação%',
    '%não tenho contexto%',
];

/*
 * Global clarification requests beyond rephrasing patterns.
 */
$fallback_clarification_requests_beyond_rephrasing = [
    '%você pode fornecer mais detalhes%',
    '%você pode dar mais informações%',
    '%você pode ser mais específico%',
    '%o que você quer dizer%',
    '%você pode elaborar%',
    '%preciso de mais contexto%',
    '%contexto insuficiente%',
];

/*
 * Global fallback to external help patterns.
 */
$fallback_to_external_help = [
    '%você pode querer entrar em contato%',
    '%você deveria entrar em contato%',
    '%verifique com o atendimento ao cliente%',
    '%entre em contato com o suporte%',
    '%consulte um profissional%',
    '%visite o site oficial%',
];

/*
 * Global safety, refusal, and policy related patterns.
 */
$fallback_safety_refusal_and_policy_related = [
    '%não posso ajudar com essa solicitação%',
    '%não posso ajudar com esta solicitação%',
    '%não sou capaz de cumprir%',
    '%esta solicitação não é permitida%',
    '%não posso fornecer isso%',
];

/*
 * Global conversation breakdown patterns.
 */
$fallback_conversation_breakdown = [
    '%vamos mudar de assunto%',
    '%posso estar entendendo mal%',
    '%isso não parece relacionado%',
    '%isso está fora do escopo%',
];
