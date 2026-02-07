<?php
/**
 * Kognetiks Insights - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: German
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words, $fallback_like, $fallback_failure_and_apology, $fallback_deflection_and_generic_assistant_behavior, $fallback_clarification_requests_beyond_rephrasing, $fallback_to_external_help, $fallback_safety_refusal_and_policy_related, $fallback_conversation_breakdown;

// Initialize the German language sentiment words dictionary
$sentiment_words = array(
    'ausgezeichnet' => 5,
    'erstaunlich' => 5,
    'wunderbar' => 5,
    'fantastisch' => 5,
    'brillant' => 5,
    'großartig' => 4,
    'gut' => 3,
    'nett' => 3,
    'glücklich' => 3,
    'zufrieden' => 3,
    'zufrieden' => 3,
    'hilfreich' => 3,
    'danke' => 2,
    'danken' => 2,
    'ja' => 1,
    'okay' => 1,
    'ok' => 1,
    'in Ordnung' => 1,
    'schrecklich' => -5,
    'grauenhaft' => -5,
    'furchtbar' => -5,
    'schlimmste' => -5,
    'schlecht' => -3,
    'armselig' => -3,
    'falsch' => -3,
    'enttäuscht' => -3,
    'unglücklich' => -3,
    'entschuldigung' => -2
);

// Initialize the German language negator words dictionary
$negator_words = array(
    'nicht',
    'niemals',
    'nein',
    'keiner',
    'nichts',
    'weder',
    'noch',
    'kaum',
    'gerade so',
    'kaum',
    'nicht',
    'ist nicht',
    'war nicht',
    'waren nicht',
    'haben nicht',
    'hat nicht',
    'hatte nicht',
    'wird nicht',
    'würde nicht',
    'nicht',
    'tat nicht',
    'kann nicht',
    'kann nicht',
    'konnte nicht',
    'sollte nicht',
    'vielleicht nicht',
    'darf nicht'
);

// Initialize the German language intensifier words dictionary
$intensifier_words = array(
    'sehr' => 1.5,
    'wirklich' => 1.5,
    'äußerst' => 1.5,
    'absolut' => 1.5,
    'vollständig' => 1.5,
    'total' => 1.5
);

/**
 * Global unanswered questions patterns.
 */
$fallback_like = [
    '%ich folge nicht%',
    '%könnten sie das fragen%',
    '%das ist unklar%',
    '%habe das nicht ganz verstanden%',
    '%könnten sie versuchen umzuformulieren%',
    '%könnten sie umformulieren%',
    '%versuchen sie zu formulieren%',
    '%bitte klären sie%',
];

/*
 * Global explicit failure and apology patterns. 
 */
$fallback_failure_and_apology = [
    '%es tut mir leid%',
    '%ich weiß nicht%',
    '%ich bin mir nicht sicher%',
    '%ich bin mir nicht sicher%',
    '%ich kann damit nicht helfen%',
    '%ich kann damit nicht helfen%',
    '%ich habe nicht genug informationen%',
    '%ich habe diese informationen nicht%',
    '%ich habe keinen zugriff%',
    '%ich habe nicht die fähigkeit%',
    '%ich habe keine details%',
    '%ich bin nicht in der lage%',
    '%ich kann nicht antworten%',
    '%ich kann nicht antworten%',
];

/*
 * Global deflection and generic assistant behavior patterns.
 */
$fallback_deflection_and_generic_assistant_behavior = [
    '%als ki%',
    '%ich bin eine ki%',
    '%ich bin ein sprachmodell%',
    '%ich habe keine persönlichen meinungen%',
    '%ich habe keine echtzeit%',
    '%ich habe kein surfen%',
    '%ich habe keinen kontext%',
];

/*
 * Global clarification requests beyond rephrasing patterns.
 */
$fallback_clarification_requests_beyond_rephrasing = [
    '%können sie mehr details geben%',
    '%können sie mehr informationen geben%',
    '%können sie spezifischer sein%',
    '%was meinen sie%',
    '%können sie ausführen%',
    '%ich brauche mehr kontext%',
    '%nicht genug kontext%',
];

/*
 * Global fallback to external help patterns.
 */
$fallback_to_external_help = [
    '%sie möchten vielleicht kontaktieren%',
    '%sie sollten kontaktieren%',
    '%prüfen sie beim kundensupport%',
    '%wenden sie sich an den support%',
    '%konsultieren sie einen fachmann%',
    '%besuchen sie die offizielle website%',
];

/*
 * Global safety, refusal, and policy related patterns.
 */
$fallback_safety_refusal_and_policy_related = [
    '%ich kann dieser anfrage nicht helfen%',
    '%ich kann bei dieser anfrage nicht helfen%',
    '%ich kann nicht nachkommen%',
    '%diese anfrage ist nicht erlaubt%',
    '%ich kann das nicht bereitstellen%',
];

/*
 * Global conversation breakdown patterns.
 */
$fallback_conversation_breakdown = [
    '%ändern wir das thema%',
    '%ich verstehe vielleicht falsch%',
    '%das scheint nicht zusammenzuhängen%',
    '%das liegt außerhalb des bereichs%',
];
