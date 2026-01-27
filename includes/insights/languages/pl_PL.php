<?php
/**
 * Kognetiks Insights - Languages - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * Translation: Polish
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Global sentiment analysis variables
global $sentiment_words, $negator_words, $intensifier_words, $fallback_like, $fallback_failure_and_apology, $fallback_deflection_and_generic_assistant_behavior, $fallback_clarification_requests_beyond_rephrasing, $fallback_to_external_help, $fallback_safety_refusal_and_policy_related, $fallback_conversation_breakdown;

// Initialize the Polish language sentiment words dictionary
$sentiment_words = array(
    'doskonały' => 5,
    'niesamowity' => 5,
    'wspaniały' => 5,
    'fantastyczny' => 5,
    'błyskotliwy' => 5,
    'świetny' => 4,
    'dobry' => 3,
    'miły' => 3,
    'szczęśliwy' => 3,
    'zadowolony' => 3,
    'usatysfakcjonowany' => 3,
    'pomocny' => 3,
    'dzięki' => 2,
    'dziękuję' => 2,
    'tak' => 1,
    'okej' => 1,
    'ok' => 1,
    'dobrze' => 1,
    'okropny' => -5,
    'straszny' => -5,
    'okropny' => -5,
    'najgorszy' => -5,
    'zły' => -3,
    'słaby' => -3,
    'zły' => -3,
    'rozczarowany' => -3,
    'nieszczęśliwy' => -3,
    'przepraszam' => -2
);

// Initialize the Polish language negator words dictionary
$negator_words = array(
    'nie',
    'nigdy',
    'nie',
    'żaden',
    'nic',
    'ani',
    'ani',
    'ledwie',
    'zaledwie',
    'ledwie',
    'nie',
    'nie jest',
    'nie był',
    'nie byli',
    'nie mają',
    'nie ma',
    'nie miał',
    'nie będzie',
    'nie chciałby',
    'nie',
    'nie zrobił',
    'nie może',
    'nie może',
    'nie mógł',
    'nie powinien',
    'może nie',
    'nie wolno'
);

// Initialize the Polish language intensifier words dictionary
$intensifier_words = array(
    'bardzo' => 1.5,
    'naprawdę' => 1.5,
    'niezwykle' => 1.5,
    'absolutnie' => 1.5,
    'całkowicie' => 1.5,
    'zupełnie' => 1.5
);

/**
 * Global unanswered questions patterns.
 */
$fallback_like = [
    '%nie nadążam%',
    '%czy możesz o to zapytać%',
    '%to nie jest jasne%',
    '%nie do końca zrozumiałem%',
    '%czy możesz spróbować przeformułować%',
    '%czy możesz przeformułować%',
    '%spróbuj sformułować%',
    '%proszę wyjaśnić%',
];

/*
 * Global explicit failure and apology patterns. 
 */
$fallback_failure_and_apology = [
    '%przepraszam%',
    '%nie wiem%',
    '%nie jestem pewien%',
    '%nie jestem pewna%',
    '%nie mogę w tym pomóc%',
    '%nie mogę pomóc w tym%',
    '%nie mam wystarczających informacji%',
    '%nie mam tych informacji%',
    '%nie mam dostępu%',
    '%nie mam zdolności%',
    '%nie mam szczegółów%',
    '%nie jestem w stanie%',
    '%nie mogę odpowiedzieć%',
    '%nie mogę odpowiedzieć%',
];

/*
 * Global deflection and generic assistant behavior patterns.
 */
$fallback_deflection_and_generic_assistant_behavior = [
    '%jako ai%',
    '%jestem ai%',
    '%jestem modelem językowym%',
    '%nie mam osobistych opinii%',
    '%nie mam czasu rzeczywistego%',
    '%nie mam przeglądania%',
    '%nie mam kontekstu%',
];

/*
 * Global clarification requests beyond rephrasing patterns.
 */
$fallback_clarification_requests_beyond_rephrasing = [
    '%czy możesz podać więcej szczegółów%',
    '%czy możesz podać więcej informacji%',
    '%czy możesz być bardziej konkretny%',
    '%co masz na myśli%',
    '%czy możesz rozwinąć%',
    '%potrzebuję więcej kontekstu%',
    '%niewystarczający kontekst%',
];

/*
 * Global fallback to external help patterns.
 */
$fallback_to_external_help = [
    '%możesz chcieć skontaktować się%',
    '%powinieneś skontaktować się%',
    '%sprawdź z obsługą klienta%',
    '%skontaktuj się z pomocą%',
    '%skonsultuj się z profesjonalistą%',
    '%odwiedź oficjalną stronę%',
];

/*
 * Global safety, refusal, and policy related patterns.
 */
$fallback_safety_refusal_and_policy_related = [
    '%nie mogę pomóc w tym żądaniu%',
    '%nie mogę pomóc w tym żądaniu%',
    '%nie jestem w stanie się dostosować%',
    '%to żądanie nie jest dozwolone%',
    '%nie mogę tego zapewnić%',
];

/*
 * Global conversation breakdown patterns.
 */
$fallback_conversation_breakdown = [
    '%zmieńmy temat%',
    '%może źle rozumiem%',
    '%to nie wydaje się powiązane%',
    '%to jest poza zakresem%',
];
