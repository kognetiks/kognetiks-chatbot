<?php
/**
 * Kognetiks Insights - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: Spanish
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words, $fallback_like, $fallback_failure_and_apology, $fallback_deflection_and_generic_assistant_behavior, $fallback_clarification_requests_beyond_rephrasing, $fallback_to_external_help, $fallback_safety_refusal_and_policy_related, $fallback_conversation_breakdown;

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

/**
 * Global unanswered questions patterns.
 */
$fallback_like = [
    '%no estoy siguiendo%',
    '%podrías preguntar eso%',
    '%eso no está claro%',
    '%no capté bien%',
    '%podrías intentar reformular%',
    '%podrías reformular%',
    '%intenta formular%',
    '%por favor aclara%',
];

/*
 * Global explicit failure and apology patterns. 
 */
$fallback_failure_and_apology = [
    '%no sé%',
    '%no estoy seguro%',
    '%no estoy segura%',
    '%no puedo ayudar con eso%',
    '%no puedo ayudar con eso%',
    '%no tengo suficiente información%',
    '%no tengo esa información%',
    '%no tengo acceso%',
    '%no tengo la capacidad%',
    '%no tengo detalles%',
    '%no puedo%',
    '%no puedo responder%',
    '%no puedo responder%',
];

/*
 * Global deflection and generic assistant behavior patterns.
 */
$fallback_deflection_and_generic_assistant_behavior = [
    '%como una ia%',
    '%soy una ia%',
    '%soy un modelo de lenguaje%',
    '%no tengo opiniones personales%',
    '%no tengo tiempo real%',
    '%no tengo navegación%',
    '%no tengo contexto%',
];

/*
 * Global clarification requests beyond rephrasing patterns.
 */
$fallback_clarification_requests_beyond_rephrasing = [
    '%puedes proporcionar más detalles%',
    '%puedes dar más información%',
    '%puedes ser más específico%',
    '%qué quieres decir%',
    '%puedes elaborar%',
    '%necesito más contexto%',
    '%no hay suficiente contexto%',
];

/*
 * Global fallback to external help patterns.
 */
$fallback_to_external_help = [
    '%quizás quieras contactar%',
    '%deberías contactar%',
    '%consulta con el servicio al cliente%',
    '%comunícate con el soporte%',
    '%consulta con un profesional%',
    '%visita el sitio web oficial%',
];

/*
 * Global safety, refusal, and policy related patterns.
 */
$fallback_safety_refusal_and_policy_related = [
    '%no puedo ayudar con esa solicitud%',
    '%no puedo ayudar con esta solicitud%',
    '%no puedo cumplir%',
    '%esta solicitud no está permitida%',
    '%no puedo proporcionar eso%',
];

/*
 * Global conversation breakdown patterns.
 */
$fallback_conversation_breakdown = [
    '%cambiemos de tema%',
    '%podría estar entendiendo mal%',
    '%eso no parece relacionado%',
    '%eso está fuera del alcance%',
];
