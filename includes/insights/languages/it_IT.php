<?php
/**
 * Kognetiks Insights - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: Italian
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words, $fallback_like, $fallback_failure_and_apology, $fallback_deflection_and_generic_assistant_behavior, $fallback_clarification_requests_beyond_rephrasing, $fallback_to_external_help, $fallback_safety_refusal_and_policy_related, $fallback_conversation_breakdown;

// Initialize the Italian language sentiment words dictionary
$sentiment_words = array(
    'eccellente' => 5,
    'incredibile' => 5,
    'meraviglioso' => 5,
    'fantastico' => 5,
    'brillante' => 5,
    'ottimo' => 4,
    'buono' => 3,
    'piacevole' => 3,
    'felice' => 3,
    'contento' => 3,
    'soddisfatto' => 3,
    'utile' => 3,
    'grazie' => 2,
    'ringraziare' => 2,
    'sì' => 1,
    'va bene' => 1,
    'ok' => 1,
    'bene' => 1,
    'terribile' => -5,
    'orribile' => -5,
    'pessimo' => -5,
    'peggiore' => -5,
    'cattivo' => -3,
    'scarso' => -3,
    'sbagliato' => -3,
    'deluso' => -3,
    'infelice' => -3,
    'mi dispiace' => -2
);

// Initialize the Italian language negator words dictionary
$negator_words = array(
    'non',
    'mai',
    'no',
    'nessuno',
    'niente',
    'né',
    'né',
    'a malapena',
    'appena',
    'a stento',
    'non',
    'non è',
    'non era',
    'non erano',
    'non hanno',
    'non ha',
    'non aveva',
    'non lo farà',
    'non lo farebbe',
    'non',
    'non ha fatto',
    'non può',
    'non può',
    'non poteva',
    'non dovrebbe',
    'potrebbe non',
    'non deve'
);

// Initialize the Italian language intensifier words dictionary
$intensifier_words = array(
    'molto' => 1.5,
    'davvero' => 1.5,
    'estremamente' => 1.5,
    'assolutamente' => 1.5,
    'completamente' => 1.5,
    'totalmente' => 1.5
);

/**
 * Global unanswered questions patterns.
 */
$fallback_like = [
    '%non sto seguendo%',
    '%potresti chiedere quello%',
    '%non è chiaro%',
    '%non ho capito bene%',
    '%potresti provare a riformulare%',
    '%potresti riformulare%',
    '%prova a formulare%',
    '%per favore chiarisci%',
];

/*
 * Global explicit failure and apology patterns. 
 */
$fallback_failure_and_apology = [
    '%mi dispiace%',
    '%non lo so%',
    '%non sono sicuro%',
    '%non sono sicura%',
    '%non posso aiutare con quello%',
    '%non posso aiutare con quello%',
    '%non ho abbastanza informazioni%',
    '%non ho quelle informazioni%',
    '%non ho accesso%',
    '%non ho la capacità%',
    '%non ho dettagli%',
    '%non sono in grado%',
    '%non posso rispondere%',
    '%non posso rispondere%',
];

/*
 * Global deflection and generic assistant behavior patterns.
 */
$fallback_deflection_and_generic_assistant_behavior = [
    '%come un\'ia%',
    '%sono un\'ia%',
    '%sono un modello linguistico%',
    '%non ho opinioni personali%',
    '%non ho tempo reale%',
    '%non ho navigazione%',
    '%non ho contesto%',
];

/*
 * Global clarification requests beyond rephrasing patterns.
 */
$fallback_clarification_requests_beyond_rephrasing = [
    '%puoi fornire più dettagli%',
    '%puoi dare più informazioni%',
    '%puoi essere più specifico%',
    '%cosa intendi%',
    '%puoi elaborare%',
    '%ho bisogno di più contesto%',
    '%non abbastanza contesto%',
];

/*
 * Global fallback to external help patterns.
 */
$fallback_to_external_help = [
    '%potresti voler contattare%',
    '%dovresti contattare%',
    '%controlla con il supporto clienti%',
    '%contatta il supporto%',
    '%consulta un professionista%',
    '%visita il sito web ufficiale%',
];

/*
 * Global safety, refusal, and policy related patterns.
 */
$fallback_safety_refusal_and_policy_related = [
    '%non posso aiutare con quella richiesta%',
    '%non posso aiutare con questa richiesta%',
    '%non sono in grado di conformarmi%',
    '%questa richiesta non è consentita%',
    '%non posso fornire quello%',
];

/*
 * Global conversation breakdown patterns.
 */
$fallback_conversation_breakdown = [
    '%cambiamo argomento%',
    '%potrei fraintendere%',
    '%quello non sembra correlato%',
    '%quello è fuori dall\'ambito%',
];
