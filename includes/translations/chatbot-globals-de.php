<?php
/**
 * Kognetiks Chatbot - Globals German - Ver 1.6.5
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
    "ein", "über", "oben", "nach", "wieder", "gegen", "nicht", "alle", "bin", "eine", "und", "ein anderer", "irgendwelche", "sind", "sind nicht", "wie", "bei",
    "sei", "weil", "gewesen", "bevor", "sein", "unter", "zwischen", "beide", "aber", "von",
    "kann", "kann nicht", "kann nicht", "könnte", "konnte nicht",
    "wage", "habe", "hatte nicht", "hat", "hat nicht", "habe", "habe nicht", "tun", "tut", "tut nicht", "mache", "mache nicht", "nach unten", "während",
    "jeder", "entweder", "genug", "jeder", "jeder", "jeder", "alles",
    "wenige", "zuerst", "für", "von", "weiter",
    "bekommen", "geben", "gehen", "geht", "bekommen",
    "hatte", "hatte nicht", "hat", "hat nicht", "haben", "haben nicht", "hatte", "er", "er würde", "er wird", "sie", "hier", "hier ist", "ihre", "sich selbst", "er ist", "ihn", "sich selbst", "sein", "wie", "wie ist",
    "ich", "ich würde", "wenn", "ich werde", "ich bin", "in", "in", "ist", "ist nicht", "es", "seine", "es ist", "es selbst", "ich habe",
    "gerade",
    "am wenigsten", "weniger", "lass uns", "wenig", "viel", "viele",
    "viele", "darf", "mich", "könnte", "könnte nicht", "mehr", "die meisten", "viel", "muss", "muss nicht", "mein", "mich selbst",
    "brauche", "brauche nicht", "weder", "niemals", "nein", "keiner", "noch", "nicht", "jetzt",
    "von", "aus", "auf", "einmal", "nur", "oder", "andere", "sollte", "sollte nicht", "unser", "unsere", "uns", "aus", "über", "eigen",
    "genug",
    "wirklich", "richtig",
    "dasselbe", "mehrere", "soll", "soll nicht", "sie", "sie würde", "sie wird", "sie ist", "sollte", "sollte nicht", "so", "jemand", "jemand", "etwas", "irgendwann", "irgendwo", "solche",
    "nehmen", "als", "das", "das ist", "die", "ihre", "ihnen", "sich selbst", "dann", "dort", "da ist", "diese", "sie", "sie würden", "sie werden", "sie sind", "sie haben", "Ding", "Dinge", "dies", "jene", "durch", "Zeit", "zu", "zu",
    "unter", "bis", "hoch", "uns",
    "verschieden", "sehr",
    "war", "war nicht", "wir", "wir würden", "wir werden", "waren", "wir sind", "waren nicht", "wir haben", "was", "was ist", "wann", "wann ist", "wo", "wo ist", "welches", "während", "wer", "wen", "wen", "wer ist", "warum", "warum ist", "wird", "mit", "wird nicht", "würde", "würde nicht",
    "doch", "du", "du würdest", "du wirst", "dein", "du bist", "dein", "deins", "dein", "deine", "du hast",
];

// Global abbreviations array
global $abbreviations;
$abbreviations = [
    // Latin-based Abbreviations
    "z.B.", "u.a.", "etc.", "et al.", "N.B.", "vgl.", "vs.", "viz.", "a.m.", "p.m.",
    // Time and Date
    "n. Chr.", "v. Chr.", "n. Chr.", "v. Chr.", "GMT", "MEZ", "UTC",
    // Measurement
    "kg", "g", "km", "cm", "ml", "ft",
    // Titles
    "Hr.", "Fr.", "Dr.", "Prof.",
    // Miscellaneous
    "FAQ", "DIY", "ASAP", "FYI", "RSVP", "P.S.", "AKA", "Geb.", "TBD", "TBA", "ETA", "Übrigens",
];

// Declare the $learningMessages array as global
global $learningMessages;
$learningMessages = [
    " Bitte beachten Sie, dass ich meine Fähigkeiten ständig erweitere. In der Zwischenzeit finden Sie weitere Informationen hier: ",
    " Ich befinde mich derzeit in der Erweiterung meines Wissens. Für weitere Details schauen Sie bitte hier: ",
    " Kleiner Hinweis - ich verbessere mich jeden Tag. In der Zwischenzeit können Sie hier mehr Informationen finden: ",
    " Da ich noch dabei bin, die Grundlagen zu meistern, empfehle ich Ihnen einen Besuch hier: ",
    " Ich entwickle und lerne ständig weiter. Vorerst können Sie dies überprüfen: ",
    " Denken Sie daran, dass ich mich auf einer Reise des kontinuierlichen Lernens befinde. Sie können jederzeit zurückkehren. In der Zwischenzeit könnte dies nützlich sein: ",
    " Ich befinde mich noch in der Lernphase, daher schätze ich Ihre Geduld sehr. Vielleicht finden Sie hier, was Sie suchen: "
];

// Declare the $errorResponses array as global
global $errorResponses;
$errorResponses = [
    "Es scheint, als gäbe es ein Problem mit der API. Versuchen wir es später noch einmal.",
    "Leider könnten wir auf ein Problem mit der API gestoßen sein. Bitte versuchen Sie es in Kürze erneut.",
    "Ich entschuldige mich, aber es scheint momentan ein Problem mit der API zu geben. Wir können es später erneut versuchen.",
    "Die API scheint momentan Schwierigkeiten zu haben. Wir können darauf zurückkommen, sobald das Problem behoben ist.",
    "Es tut mir leid, aber es scheint ein Fehler auf der API-Seite zu sein. Bitte versuchen Sie es später erneut.",
    "Es könnte ein vorübergehendes Problem mit der API geben. Bitte versuchen Sie es in Kürze noch einmal.",
    "Die API hat einen Fehler festgestellt, aber keine Sorge, das passiert. Versuchen wir es später noch einmal.",
    "Es sieht aus, als gäbe es ein technisches Problem mit der API. Versuchen Sie es in Kürze noch einmal, um zu sehen, ob alles reibungslos funktioniert."
];

// Declare the $no_matching_content_response array as global
global $no_matching_content_response;
$no_matching_content_response = [
    "Es tut mir leid, aber ich konnte keine relevanten Informationen zu diesem Thema finden. Möchten Sie etwas anderes versuchen?",
    "Leider konnte ich keine relevanten Informationen zu diesem Thema finden. Möchten Sie etwas anderes fragen?",
    "Ich fürchte, ich konnte keine relevanten Informationen zu diesem Thema finden. Möchten Sie eine andere Frage versuchen?",
    "Ich konnte keine relevanten Informationen zu diesem Thema finden. Möchten Sie etwas anderes fragen?",
    "Es tut mir leid, aber ich konnte keine Informationen zu diesem Thema finden. Möchten Sie eine andere Frage stellen?"
];

// Declare the $chatbot_chatgpt_bot_prompt as global - Ver 1.6.6
global $chatbot_chatgpt_bot_prompt;
$chatbot_chatgpt_bot_prompt = [
    "Geben Sie Ihre Frage ein ...",
    "Stellen Sie mir eine Frage ...",
    "Ich höre zu ...",
    "Ich bin hier, um zu helfen ...",
    "Bitte teilen Sie Ihre Gedanken ...",
    "Fragen Sie mich alles, was Sie möchten ...",
    "Nur zu, stellen Sie Ihre Frage ...",
    "Woran denken Sie ...",
    "Möchten Sie etwas teilen ...",
    "Haben Sie konkrete Fragen ...",
    "Was überlegen Sie ...",
    "Was beschäftigt Sie ...",
    "Worüber möchten Sie sprechen ..."
];

// Declare the $chatbot_markov_chain_fallback_response as global - Ver 2.1.6.1
global $chatbot_markov_chain_fallback_response;
$chatbot_markov_chain_fallback_response = [
    "Entschuldigung, ich konnte keine relevanten Informationen zu Ihrer Anfrage finden. Können Sie es neu formulieren oder etwas anderes fragen?",
    "Ich bin mir nicht sicher, ob ich die richtigen Informationen dafür habe. Können Sie es klären oder anders fragen?",
    "Es scheint, dass ich nicht die genauen Details habe, die Sie suchen. Können Sie die Frage neu formulieren?",
    "Ich konnte momentan nichts zu diesem Thema finden. Möchten Sie es erneut versuchen?",
    "Ich fürchte, ich habe nicht genug Informationen darüber. Könnten Sie mehr Details angeben?",
    "Es sieht so aus, als würde mir die Antwort fehlen. Vielleicht könnten Sie es neu formulieren oder etwas anderes fragen?"
];

// Declare the $chatbotFallbackResponses array as global
$chatbotFallbackResponses = [
    "Das habe ich nicht ganz verstanden. Können Sie es anders formulieren?",
    "Hmm, ich bin mir nicht sicher, ob ich das verstanden habe. Können Sie es anders erklären?",
    "Ich verstehe nicht ganz. Könnten Sie das anders fragen?",
    "Entschuldigung, das ergibt für mich keinen Sinn. Könnten Sie es klarstellen?",
    "Ich habe Ihren Punkt vielleicht übersehen. Könnten Sie es anders ausdrücken?",
    "Ich habe Schwierigkeiten zu verstehen. Könnten Sie es anders fragen?",
    "Das ist für mich nicht ganz klar. Können Sie es neu formulieren?",
    "Ich bin mir nicht sicher, was Sie meinen. Können Sie es anders erklären?",
    "Das habe ich nicht verstanden. Könnten Sie es anders formulieren?",
    "Ich bin ein wenig verwirrt. Können Sie mehr Details angeben oder es neu formulieren?",
    "Entschuldigung, ich verstehe das nicht. Können Sie es umformulieren?",
    "Das ist für mich unklar. Könnten Sie es anders stellen?"
];
