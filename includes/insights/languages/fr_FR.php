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
global $sentiment_words, $negator_words, $intensifier_words, $fallback_like, $fallback_failure_and_apology, $fallback_deflection_and_generic_assistant_behavior, $fallback_clarification_requests_beyond_rephrasing, $fallback_to_external_help, $fallback_safety_refusal_and_policy_related, $fallback_conversation_breakdown;

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

/**
 * Global unanswered questions patterns.
 */
$fallback_like = [
    '%je ne suis pas%',
    '%pourriez-vous demander cela%',
    '%ce n\'est pas clair%',
    '%je n\'ai pas bien saisi%',
    '%pourriez-vous essayer de reformuler%',
    '%pourriez-vous reformuler%',
    '%essayez de formuler%',
    '%veuillez clarifier%',
];

/*
 * Global explicit failure and apology patterns. 
 */
$fallback_failure_and_apology = [
    '%je suis désolé%',
    '%je ne sais pas%',
    '%je ne suis pas sûr%',
    '%je ne suis pas sûre%',
    '%je ne peux pas aider avec cela%',
    '%je ne peux pas aider avec cela%',
    '%je n\'ai pas assez d\'informations%',
    '%je n\'ai pas ces informations%',
    '%je n\'ai pas accès%',
    '%je n\'ai pas la capacité%',
    '%je n\'ai pas de détails%',
    '%je ne peux pas%',
    '%je ne peux pas répondre%',
    '%je ne peux pas répondre%',
];

/*
 * Global deflection and generic assistant behavior patterns.
 */
$fallback_deflection_and_generic_assistant_behavior = [
    '%en tant qu\'ia%',
    '%je suis une ia%',
    '%je suis un modèle de langage%',
    '%je n\'ai pas d\'opinions personnelles%',
    '%je n\'ai pas de temps réel%',
    '%je n\'ai pas de navigation%',
    '%je n\'ai pas de contexte%',
];

/*
 * Global clarification requests beyond rephrasing patterns.
 */
$fallback_clarification_requests_beyond_rephrasing = [
    '%pouvez-vous fournir plus de détails%',
    '%pouvez-vous donner plus d\'informations%',
    '%pouvez-vous être plus spécifique%',
    '%que voulez-vous dire%',
    '%pouvez-vous élaborer%',
    '%j\'ai besoin de plus de contexte%',
    '%pas assez de contexte%',
];

/*
 * Global fallback to external help patterns.
 */
$fallback_to_external_help = [
    '%vous voudrez peut-être contacter%',
    '%vous devriez contacter%',
    '%vérifiez avec le service client%',
    '%contactez le support%',
    '%consultez un professionnel%',
    '%visitez le site web officiel%',
];

/*
 * Global safety, refusal, and policy related patterns.
 */
$fallback_safety_refusal_and_policy_related = [
    '%je ne peux pas aider avec cette demande%',
    '%je ne peux pas aider avec cette demande%',
    '%je ne peux pas me conformer%',
    '%cette demande n\'est pas autorisée%',
    '%je ne peux pas fournir cela%',
];

/*
 * Global conversation breakdown patterns.
 */
$fallback_conversation_breakdown = [
    '%changeons de sujet%',
    '%je pourrais mal comprendre%',
    '%cela ne semble pas lié%',
    '%c\'est hors de portée%',
];
