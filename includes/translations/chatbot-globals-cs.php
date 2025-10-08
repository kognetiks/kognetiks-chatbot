<?php
/**
 * Kognetiks Chatbot - Globals Czech - Ver 1.6.5
 *
 * This file contains the code for global variables used
 * by the program.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Declare the $stopWords array as global
// List of common stop words to be ignored
global $stopWords;
$stopWords = [
    "a", "o", "nad", "po", "znovu", "proti", "není", "vše", "jsem", "nějaký", "a", "jiný", "jakýkoli", "jsou", "nejsou", "jako", "na",
    "být", "protože", "byl", "před", "být", "pod", "mezi", "oba", "ale", "tím", 
    "moci", "nemůže", "nemůže", "mohl", "nemohl", 
    "odvážit", "udělal", "neudělal", "jiný", "dělat", "dělá", "nedělá", "dělání", "nedělej", "dolů", "během", 
    "každý", "buď", "dost", "každý", "každý", "všichni", "vše",
    "málo", "první", "pro", "z", "dále", 
    "získat", "dát", "jít", "jít", "dostal",
    "měl", "neměl", "má", "nemá", "mít", "nemám", "mající", "on", "on by", "on bude", "její", "tady", "tady je", "její", "ona sama", "on je", "jej", "on sám", "jeho", "jak", "jak je", 
    "já", "já bych", "jestli", "já budu", "já jsem", "v", "do", "je", "není", "to", "její", "to je", "to samo", "já mám",
    "právě", 
    "nejméně", "méně", "pojďme", "málo", "hodně", "hodně", 
    "mnoho", "možná", "mě", "mohl", "mohl by ne", "více", "nejvíce", "nejvíce", "hodně", "musí", "nesmí", "moje", "já sám", 
    "potřeba", "není třeba", "ani", "nikdy", "ne", "žádný", "ani", "ne", "teď", 
    "z", "pryč", "na", "jednou", "pouze", "nebo", "jiný", "měl by", "neměl by", "náš", "naše", "nás samotné", "ven", "přes", "vlastní", 
    "hodně", 
    "opravdu", "správný", 
    "stejný", "několik", "měla by", "nebude", "ona", "ona by", "ona bude", "ona je", "měla by", "neměla by", "takže", "nějaký", "někdo", "někdo", "něco", "někdy", "někde", "takový", 
    "vzít", "než", "že", "to je", "ten", "jejich", "jejich", "je", "oni sami", "pak", "tam", "tam je", "tyto", "oni", "oni by", "oni budou", "oni jsou", "oni mají", "věc", "věci", "tohle", "ty", "skrze", "čas", "na", "také", 
    "pod", "až do", "nahoru", "nás", 
    "různé", "velmi", 
    "byl", "nebyl", "my", "my bychom", "my budeme", "byli", "my jsme", "nebyli", "my máme", "co", "co je", "kdy", "kdy je", "kde", "kde je", "který", "zatímco", "kdo", "komu", "kdo je", "proč", "proč je", "bude", "s", "nebude", "by", "nebylo by", 
    "přesto", "ty", "ty bys", "ty budeš", "tvůj", "ty jsi", "váš", "váš", "ty sám", "vy sami", "vy máte"
];

// Global abbreviations array
global $abbreviations;
$abbreviations = [
    // Latinské zkratky
    "tj.", "např.", "atd.", "aj.", "N.B.", "srov.", "vs.", "viz.", "dopo.", "odpo.",
    // Čas a datum
    "n. l.", "př. n. l.", "CE", "BCE", "GMT", "EST", "UTC",
    // Míry
    "lb", "oz", "km", "cm", "ml", "ft",
    // Tituly
    "p.", "paní", "slečna", "Dr.", "Prof.",
    // Různé
    "FAQ", "DIY", "ASAP", "FYI", "RSVP", "P.S.", "AKA", "DOB", "TBD", "TBA", "ETA", "BTW",
];

// Declare the $learningMessages array as global
global $learningMessages;
$learningMessages = [
    " Upozorňujeme, že neustále zdokonaluji své schopnosti. Mezitím můžete najít více informací zde: ",
    " V současné době rozšiřuji své znalosti. Pro další podrobnosti prosím navštivte: ",
    " Jen malá poznámka - každý den se aktivně zlepšuji. Mezitím můžete prozkoumat více informací zde: ",
    " Jelikož se stále učím, doporučuji navštívit: ",
    " Neustále se vyvíjím a učím. Prozatím se můžete podívat na: ",
    " Mějte na paměti, že jsem na cestě neustálého učení. Kdykoli se sem můžete vrátit. Mezitím by se vám mohlo hodit toto: ",
    " Stále se učím, takže ocením vaši trpělivost. To, co hledáte, byste mohli najít zde: "
];

// Declare the $errorResponses array as global
global $errorResponses;
$errorResponses = [
    " Zdá se, že došlo k problému s API. Zkusme to znovu později.",
    " Bohužel jsme mohli narazit na problém s API. Zkuste to prosím znovu za chvíli.",
    " Omlouvám se, ale vypadá to, že momentálně je s API problém. Můžeme to zkusit znovu později.",
    " Zdá se, že API má nyní potíže. Můžeme se k tomu vrátit, až to bude vyřešeno.",
    " Omlouvám se, ale vypadá to, že na straně API nastala chyba. Zkuste to prosím znovu za chvíli.",
    " Mohlo by se jednat o dočasný problém s API. Zkuste prosím svůj požadavek znovu za chvíli.",
    " API narazilo na chybu, ale nebojte, to se stává. Zkusme to znovu později.",
    " Vypadá to, že by mohl být technický problém s API. Zkuste to znovu za chvíli a uvidíte, jestli vše funguje správně."
];

// Declare the $no_matching_content_response array as global
global $no_matching_content_response;
$no_matching_content_response = [
    " Omlouvám se, ale nenašel jsem žádné relevantní informace k tomuto tématu. Chtěli byste zkusit něco jiného?",
    " Bohužel jsem nenašel žádné relevantní informace k tomuto tématu. Chtěli byste se zeptat na něco jiného?",
    " Obávám se, že jsem nenašel žádné relevantní informace k tomuto tématu. Chtěli byste zkusit jinou otázku?",
    " Nenašel jsem žádné relevantní informace k tomuto tématu. Chtěli byste se zeptat na něco jiného?",
    " Omlouvám se, ale nenašel jsem žádné informace k tomuto tématu. Chtěli byste zkusit jinou otázku?"
];

// Declare the $chatbot_chatgpt_bot_prompt as global - Ver 1.6.6
global $chatbot_chatgpt_bot_prompt;
$chatbot_chatgpt_bot_prompt = [
    "Zadejte svou otázku ...",
    "Zeptejte se mě na něco ...",
    "Naslouchám ...",
    "Jsem tu, abych pomohl ...",
    "Sdělte mi prosím své myšlenky ...",
    "Klidně se zeptejte na cokoli ...",
    "Jen do toho, ptejte se ...",
    "Co vás zajímá ...",
    "Chcete se podělit o své myšlenky ...",
    "Máte nějaké konkrétní otázky ...",
    "O čem přemýšlíte ...",
    "Co máte na srdci ...",
    "O čem byste chtěli mluvit ..."
];

// Declare the $chatbot_markov_chain_fallback_response as global - Ver 2.1.6.1
global $chatbot_markov_chain_fallback_response;
$chatbot_markov_chain_fallback_response = [
    "Omlouvám se, ale nenašel jsem žádné relevantní informace k vaší otázce. Můžete ji přeformulovat nebo zkusit něco jiného?",
    "Nejsem si jistý, jestli mám správné informace k tomu. Můžete to upřesnit nebo se zeptat jinak?",
    "Zdá se, že nemám přesné detaily, které hledáte. Můžete otázku přeformulovat?",
    "Momentálně jsem nenašel nic k tomuto tématu. Mohli byste to zkusit znovu?",
    "Obávám se, že nemám dostatek informací k tomu. Můžete poskytnout více podrobností?",
    "Zdá se, že mi chybí odpověď na tuto otázku. Možná byste ji mohli přeformulovat nebo zkusit něco jiného?"
];

// Declare the $chatbotFallbackResponses array as global
global $chatbotFallbackResponses;
$chatbotFallbackResponses = [
    "Nerozuměl jsem tomu úplně. Můžete to zkusit přeformulovat?",
    "Hmm, nejsem si jistý, jestli jsem to pochopil. Můžete to vysvětlit jinak?",
    "Nerozumím. Můžete to říct jiným způsobem?",
    "Omlouvám se, to mi nedává smysl. Můžete to upřesnit?",
    "Možná jsem nepochopil váš záměr. Můžete to říct jinak?",
    "Mám problém s porozuměním. Můžete se zeptat jinak?",
    "To mi nepřipadá jasné. Můžete to přeformulovat?",
    "Nejsem si jistý, co tím myslíte. Můžete to zkusit vysvětlit jinak?",
    "Tomu jsem nerozuměl. Můžete to zkusit říct jinak?",
    "Jsem trochu zmatený. Můžete poskytnout více podrobností nebo to přeformulovat?",
    "Omlouvám se, nerozumím. Můžete to zkusit přeformulovat?",
    "To mi není jasné. Můžete to položit jiným způsobem?"
];

// Declare the $chatbot_chatgpt_fixed_literal_messages
global $chatbot_chatgpt_fixed_literal_messages;
$chatbot_chatgpt_fixed_literal_messages = [
    "Jejda! Na naší straně došlo k chybě. Zkuste to prosím znovu později!",                         // [0]
    "Jejda! Propadl jsem trhlinou!",                                                                // [1]
    "Jejda! Nahrání souboru selhalo.",                                                              // [2]
    "Jejda! Chybí váš API klíč. Prosím, zadejte svůj API klíč v nastavení Chatbota.",               // [3]
    "Jejda! Během nahrávání došlo k chybě. Zkuste to prosím znovu později.",                        // [4]
    "Jejda! Prosím vyberte soubor k nahrání.",                                                      // [5]
    "Jejda! Dosáhli jste limitu zpráv. Zkuste to prosím znovu později.",                            // [6]
    "Jejda! Na naší straně došlo k chybě. Zkuste to prosím znovu později.",                         // [7]
    "Jejda! Došlo k problému při stahování přepisu. Zkuste to prosím znovu později.",               // [8]
    "Jejda! Neexistuje žádná odpověď k předčítání.",                                                // [9]
    "Jejda! Tento požadavek vypršel. Zkuste to prosím znovu.",                                      // [10]
    "Jejda! Nepodařilo se převést text na řeč. Zkuste to prosím znovu.",                            // [11]
    "Jejda! Nepodporovaný typ souboru. Zkuste to prosím znovu.",                                    // [12]
    "Jejda! Nahrání souboru selhalo. Zkuste to prosím znovu.",                                      // [13]
    "Jejda! Nelze vymazat konverzaci. Zkuste to prosím znovu.",                                     // [14]
    "Chyba: Neplatný klíč API nebo zpráva. Zkontrolujte prosím nastavení pluginu.",                 // [15]
    "Rozhovor byl vymazán. Počkejte, prosím, až se stránka znovu načte.",                           // [16]
    "Rozhovor byl vymazán.",                                                                        // [17]
    "Konverzace nebyla vymazána.",                                                                  // [18]                       
    "Systém je zaneprázdněn zpracováním požadavků. Zkuste to prosím znovu později.",                // [19]
    "Zpráva byla do fronty přidána. Probíhá zpracování...",                                         // [20]
];
