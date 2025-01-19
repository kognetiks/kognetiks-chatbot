<?php
/**
 * Kognetiks Chatbot - Globals Italian - Ver 1.6.5
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
    "a", "di", "sopra", "dopo", "ancora", "contro", "non è", "tutti", "sono", "un", "e", "un altro", "qualsiasi", "sono", "non sono", "come", "a",
    "essere", "perché", "stato", "prima", "essere", "sotto", "tra", "entrambi", "ma", "da",
    "può", "non può", "non può", "potrebbe", "non potrebbe",
    "osare", "ha fatto", "non ha fatto", "diverso", "fare", "fa", "non fa", "facendo", "non fa", "giù", "durante",
    "ogni", "o", "abbastanza", "ogni", "tutti", "tutti",
    "pochi", "primo", "per", "da", "ulteriormente",
    "ottenere", "dare", "andare", "andando", "ottenuto",
    "aveva", "non aveva", "ha", "non ha", "avere", "non hanno", "avendo", "lui", "lui avrebbe", "lui farà", "lei", "qui", "qui è", "sua", "se stessa", "lui è", "lui", "se stesso", "suo", "come", "come è",
    "io", "io avrei", "se", "io farò", "io sono", "in", "in", "è", "non è", "esso", "suo", "è", "se stesso", "io ho",
    "solo",
    "meno", "meno", "facciamo", "poco", "molto", "molti",
    "molti", "può", "me", "potrebbe", "non potrebbe", "più", "la maggior parte", "molto", "deve", "non deve", "mio", "me stesso",
    "bisogno", "non bisogno", "né", "mai", "no", "nessuno", "né", "non", "adesso",
    "di", "fuori", "su", "una volta", "solo", "o", "altro", "dovrebbe", "non dovrebbe", "nostro", "nostri", "noi stessi", "fuori", "sopra", "proprio",
    "molto",
    "davvero", "giusto",
    "stesso", "diversi", "deve", "non deve", "lei", "lei avrebbe", "lei farà", "lei è", "dovrebbe", "non dovrebbe", "quindi", "qualcuno", "qualcuno", "qualcosa", "qualche volta", "qualche parte", "tale",
    "prendere", "che", "quello", "è", "il", "il loro", "il loro", "loro", "loro stessi", "allora", "là", "là è", "questi", "essi", "essi avrebbero", "essi faranno", "essi sono", "essi hanno", "cosa", "cose", "questo", "quelli", "attraverso", "tempo", "a", "anche",
    "sotto", "fino a", "su", "noi",
    "vari", "molto",
    "era", "non era", "noi", "noi avremmo", "noi faremo", "erano", "noi siamo", "non erano", "noi abbiamo", "cosa", "che cosa è", "quando", "quando è", "dove", "dove è", "quale", "mentre", "chi", "a chi", "a chi", "chi è", "perché", "perché è", "volontà", "con", "non farà", "farebbe", "non farebbe",
    "tuttavia", "tu", "tu avresti", "tu farai", "tuo", "tu sei", "tuo", "tuo", "te stesso", "voi stessi", "tu hai",
];

// Global abbreviations array
global $abbreviations;
$abbreviations = [
    // Latin-based Abbreviations
    "cioè", "es.", "ecc.", "et al.", "N.B.", "cfr.", "vs.", "viz.", "a.m.", "p.m.",
    // Time and Date
    "d.C.", "a.C.", "CE", "BCE", "GMT", "EST", "UTC",
    // Measurement
    "lb", "oz", "km", "cm", "ml", "ft",
    // Titles
    "Sig.", "Sig.ra", "Sig.na", "Dott.", "Prof.",
    // Miscellaneous
    "FAQ", "Fai-da-te", "ASAP", "FYI", "RSVP", "P.S.", "AKA", "DOB", "TBD", "TBA", "ETA", "BTW",
];

// Declare the $learningMessages array as global
global $learningMessages;
$learningMessages = [
    " Si prega di notare che sto migliorando continuamente le mie capacità. Nel frattempo, puoi trovare maggiori informazioni qui: ",
    " Attualmente sono in fase di ampliamento delle mie conoscenze. Per ulteriori dettagli, controlla: ",
    " Solo un promemoria: sto migliorando attivamente ogni giorno. Nel frattempo, puoi esplorare ulteriori informazioni qui: ",
    " Poiché sto ancora padroneggiando le basi, ti incoraggio a visitare: ",
    " Sono in costante evoluzione e apprendimento. Per ora, puoi controllare qui: ",
    " Tieni presente che sono in un percorso di apprendimento continuo. Sentiti libero di tornare in qualsiasi momento. Nel frattempo, potresti trovare utile questo: ",
    " Sono ancora nella fase di apprendimento, quindi la tua pazienza è molto apprezzata. Potresti trovare quello che stai cercando qui: "
];

// Declare the $errorResponses array as global
global $errorResponses;
$errorResponses = [
    "Sembra che ci sia stato un problema con l'API. Riproviamo più tardi.",
    "Purtroppo, potremmo aver incontrato un problema con l'API. Si prega di riprovare tra un po'.",
    "Mi scuso, ma sembra che ci sia un problema con l'API al momento. Possiamo riprovare più tardi.",
    "L'API sembra avere difficoltà in questo momento. Possiamo tornarci sopra una volta risolto.",
    "Mi dispiace, ma sembra che ci sia un errore dal lato dell'API. Riprova tra un po'.",
    "Potrebbe esserci un problema temporaneo con l'API. Si prega di riprovare tra un po'.",
    "L'API ha riscontrato un errore, ma non preoccuparti, succede. Riproviamo più tardi.",
    "Sembra che ci possa essere un problema tecnico con l'API. Sentiti libero di riprovare tra un po' per vedere se tutto funziona correttamente."
];

// Declare the $no_matching_content_response array as global
global $no_matching_content_response;
$no_matching_content_response = [
    "Mi dispiace, ma non sono riuscito a trovare alcuna informazione pertinente su questo argomento. Vuoi provare qualcos'altro?",
    "Purtroppo non sono riuscito a trovare informazioni pertinenti su questo argomento. Vuoi chiedere qualcos'altro?",
    "Temo di non essere riuscito a trovare informazioni pertinenti su questo argomento. Vuoi provare un'altra domanda?",
    "Non sono riuscito a trovare alcuna informazione pertinente su questo argomento. Vuoi chiedere qualcos'altro?",
    "Mi dispiace, ma non sono riuscito a trovare informazioni su questo argomento. Vuoi provare un'altra domanda?"
];

// Declare the $chatbot_chatgpt_bot_prompt as global - Ver 1.6.6
global $chatbot_chatgpt_bot_prompt;
$chatbot_chatgpt_bot_prompt = [
    "Inserisci la tua domanda ...",
    "Fammi una domanda ...",
    "Ti ascolto ...",
    "Sono qui per aiutarti ...",
    "Per favore, condividi i tuoi pensieri ...",
    "Sentiti libero di chiedermi qualsiasi cosa ...",
    "Vai avanti, fai la tua domanda ...",
    "A cosa stai pensando ...",
    "Hai qualche pensiero da condividere ...",
    "Domande specifiche ...",
    "A cosa stai riflettendo ...",
    "Cosa hai in mente ...",
    "Di cosa vuoi parlare ..."
];

// Declare the $chatbot_markov_chain_fallback_response as global - Ver 2.1.6.1
global $chatbot_markov_chain_fallback_response;
$chatbot_markov_chain_fallback_response = [
    "Scusa, non sono riuscito a trovare informazioni pertinenti per rispondere alla tua richiesta. Puoi provare a riformulare o chiedere qualcos'altro?",
    "Non sono sicuro di avere le informazioni corrette per questo. Potresti chiarire o chiedere in modo diverso?",
    "Sembra che non abbia i dettagli esatti che stai cercando. Potresti riformulare la domanda?",
    "Non sono riuscito a trovare nulla su questo argomento al momento. Ti dispiacerebbe riprovare?",
    "Temo di non avere abbastanza informazioni su questo argomento. Potresti fornire più dettagli?",
    "Sembra che mi manchi la risposta per questo. Forse potresti riformulare o chiedere qualcos'altro?"
];

// Declare the $chatbotFallbackResponses array as global
$chatbotFallbackResponses = [
    "Non ho capito bene. Puoi provare a riformulare?",
    "Hmm, non sono sicuro di aver capito. Puoi spiegarlo in modo diverso?",
    "Non capisco. Potresti porre la domanda in un altro modo?",
    "Scusa, non mi è chiaro. Puoi chiarire?",
    "Forse ho perso il tuo punto. Puoi dirlo in modo diverso?",
    "Ho difficoltà a capire. Puoi chiedere in un altro modo?",
    "Non mi sembra chiaro. Puoi riformularlo?",
    "Non sono sicuro di cosa intendi. Puoi provare a spiegarlo diversamente?",
    "Non ho capito. Potresti chiedere di nuovo in un altro modo?",
    "Sono un po' confuso. Puoi fornire più dettagli o riformulare?",
    "Mi dispiace, non capisco. Puoi provare a riformularlo?",
    "Non mi è chiaro. Puoi porre la domanda in un modo diverso?"
];

// Declare the $chatbot_chatgpt_fixed_literal_messages
global $chatbot_chatgpt_fixed_literal_messages;
$chatbot_chatgpt_fixed_literal_messages = [
    "Ops! Qualcosa è andato storto da parte nostra. Per favore, riprova più tardi.",                            // [0]
    "Ops! Sono caduto attraverso le crepe!",                                                                    // [1]
    "Ops! Caricamento del file non riuscito.",                                                                  // [2]
    "Ops! La tua chiave API è mancante. Inserisci la tua chiave API nelle impostazioni del chatbot.",           // [3]
    "Ops! Si è verificato un errore durante il caricamento. Per favore, riprova più tardi.",                    // [4]
    "Ops! Seleziona un file da caricare, per favore.",                                                          // [5]
    "Ops! Hai raggiunto il limite di messaggi. Per favore, riprova più tardi.",                                 // [6]
    "Ops! Qualcosa è andato storto da parte nostra. Per favore, riprova più tardi.",                            // [7]
    "Ops! Si è verificato un problema durante il download della trascrizione. Per favore, riprova più tardi.",  // [8]
    "Ops! Non c'è alcuna risposta da leggere ad alta voce.",                                                    // [9]
    "Ops! Questa richiesta è scaduta. Per favore, riprova.",                                                    // [10]
    "Ops! Conversione del testo in voce non riuscita. Per favore, riprova.",                                    // [11]
    "Ops! Tipo di file non supportato. Per favore, riprova.",                                                   // [12]
    "Ops! Caricamento del file non riuscito. Per favore, riprova.",                                             // [13]
    "Ops! Impossibile cancellare la conversazione. Per favore, riprova.",                                       // [14]
    "Errore: Chiave API o messaggio non valido. Controlla le impostazioni del plugin.",                         // [15]
    "Conversazione cancellata. Attendere mentre la pagina si ricarica.",                                        // [16]
    "Conversazione cancellata.",                                                                                // [17]
    "Conversazione non cancellata.",                                                                            // [18]
];
