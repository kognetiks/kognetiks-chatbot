<?php
/**
 * Kognetiks Chatbot - Globals French - Ver 1.6.5
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
    "a", "à propos", "au-dessus", "après", "encore", "contre", "n'est pas", "tout", "suis", "un", "et", "un autre", "aucun", "sont", "ne sont pas", "comme", "à",
    "être", "parce que", "été", "avant", "être", "en dessous", "entre", "les deux", "mais", "par",
    "peut", "ne peut pas", "ne peut pas", "pourrait", "ne pourrait pas",
    "oser", "fait", "n'a pas fait", "différent", "faire", "fait", "ne fait pas", "faisant", "ne fait pas", "en bas", "pendant",
    "chaque", "soit", "assez", "tous", "tout le monde", "chacun", "tout",
    "peu", "premier", "pour", "de", "plus loin",
    "obtenir", "donner", "aller", "aller", "obtenu",
    "avait", "n'avait pas", "a", "n'a pas", "avoir", "n'ont pas", "ayant", "il", "il avait", "il aura", "elle", "ici", "voici", "la sienne", "elle-même", "il est", "lui", "lui-même", "son", "comment", "comment est",
    "je", "j'avais", "si", "je vais", "je suis", "dans", "dans", "est", "n'est pas", "cela", "son", "c'est", "lui-même", "j'ai",
    "juste",
    "le moins", "moins", "allons-y", "peu", "beaucoup", "beaucoup",
    "beaucoup", "peut-être", "moi", "pourrait", "ne pourrait pas", "plus", "la plupart", "beaucoup", "doit", "ne doit pas", "mon", "moi-même",
    "besoin", "n'a pas besoin", "ni", "jamais", "non", "aucun", "ni", "pas", "maintenant",
    "de", "dehors", "sur", "une fois", "seulement", "ou", "autre", "devrait", "ne devrait pas", "notre", "le nôtre", "nous-mêmes", "sortir", "par-dessus", "propre",
    "beaucoup",
    "vraiment", "droit",
    "même", "plusieurs", "doit", "ne doit pas", "elle", "elle avait", "elle aura", "elle est", "devrait", "ne devrait pas", "donc", "quelqu'un", "quelqu'un", "quelque chose", "quelque temps", "quelque part", "tel",
    "prendre", "que", "cela", "c'est", "le", "leur", "les leurs", "eux", "eux-mêmes", "alors", "là", "voici", "ceux-ci", "ils", "ils avaient", "ils auront", "ils sont", "ils ont", "chose", "choses", "ce", "ceux-là", "à travers", "temps", "à", "trop",
    "sous", "jusqu'à", "en haut", "nous",
    "divers", "très",
    "était", "n'était pas", "nous", "nous avions", "nous allons", "étaient", "nous sommes", "n'étaient pas", "nous avons", "quoi", "qu'est-ce que", "quand", "quand est", "où", "où est", "lequel", "pendant", "qui", "à qui", "à qui", "qui est", "pourquoi", "pourquoi est", "volonté", "avec", "ne fera pas", "voudrait", "ne ferait pas",
    "pourtant", "vous", "vous aviez", "vous aurez", "votre", "vous êtes", "le vôtre", "votre", "le vôtre", "vous-même", "vous-mêmes", "vous avez",
];

// Global abbreviations array
global $abbreviations;
$abbreviations = [
    // Latin-based Abbreviations
    "c.-à-d.", "p. ex.", "etc.", "et al.", "N.B.", "cf.", "vs.", "viz.", "a.m.", "p.m.",
    // Time and Date
    "AD", "BC", "CE", "BCE", "GMT", "EST", "UTC",
    // Measurement
    "lb", "oz", "km", "cm", "ml", "ft",
    // Titles
    "M.", "Mme", "Mlle", "Dr", "Prof.",
    // Miscellaneous
    "FAQ", "DIY", "ASAP", "FYI", "RSVP", "P.S.", "AKA", "DOB", "TBD", "TBA", "ETA", "BTW",
];

// Declare the $learningMessages array as global
global $learningMessages;
$learningMessages = [
    " Veuillez noter que j'améliore continuellement mes capacités. En attendant, vous pouvez trouver plus d'informations ici : ",
    " Je suis actuellement en train d'élargir mes connaissances. Pour plus de détails, veuillez consulter : ",
    " Juste pour vous avertir - je m'améliore activement chaque jour. En attendant, vous pouvez explorer plus d'informations ici : ",
    " Comme je maîtrise encore les bases, je vous encourage à visiter : ",
    " Je suis en constante évolution et en apprentissage. Pour l'instant, vous pouvez vérifier ici : ",
    " Gardez à l'esprit que je suis sur un chemin d'apprentissage continu. N'hésitez pas à revenir à tout moment. En attendant, vous pourriez trouver cela utile : ",
    " Je suis encore en phase d'apprentissage, donc votre patience est grandement appréciée. Vous pourriez trouver ce que vous cherchez ici : "
];

// Declare the $errorResponses array as global
global $errorResponses;
$errorResponses = [
    "Il semble qu'il puisse y avoir un problème avec l'API. Réessayons plus tard.",
    "Malheureusement, nous avons peut-être rencontré un problème avec l'API. Veuillez réessayer dans un moment.",
    "Je m'excuse, mais il semble qu'il y ait un problème avec l'API pour le moment. Nous pouvons réessayer plus tard.",
    "L'API semble rencontrer des difficultés en ce moment. Nous pouvons revenir à cela une fois résolu.",
    "Je suis désolé, mais il semble qu'il y ait une erreur du côté de l'API. Veuillez réessayer plus tard.",
    "Il pourrait y avoir un problème temporaire avec l'API. Veuillez réessayer votre demande dans un moment.",
    "L'API a rencontré une erreur, mais ne vous inquiétez pas, cela arrive. Réessayons plus tard.",
    "Il semble qu'il puisse y avoir un problème technique avec l'API. N'hésitez pas à réessayer dans un moment pour voir si tout fonctionne correctement."
];

// Declare the $no_matching_content_response array as global
global $no_matching_content_response;
$no_matching_content_response = [
    "Je suis désolé, mais je n'ai trouvé aucune information pertinente sur ce sujet. Souhaitez-vous essayer autre chose ?",
    "Malheureusement, je n'ai pas pu localiser d'informations pertinentes sur ce sujet. Souhaitez-vous poser une autre question ?",
    "Je crains de ne pas avoir trouvé d'informations pertinentes sur ce sujet. Voulez-vous essayer une autre question ?",
    "Je n'ai trouvé aucune information pertinente sur ce sujet. Souhaitez-vous poser une autre question ?",
    "Je suis désolé, mais je n'ai trouvé aucune information sur ce sujet. Souhaitez-vous essayer une autre question ?",
];

// Declare the $chatbot_chatgpt_bot_prompt as global - Ver 1.6.6
global $chatbot_chatgpt_bot_prompt;
$chatbot_chatgpt_bot_prompt = [
    "Entrez votre question ...",
    "Posez-moi une question ...",
    "Je vous écoute ...",
    "Je suis là pour aider ...",
    "Veuillez partager vos pensées ...",
    "N'hésitez pas à me poser n'importe quelle question ...",
    "Allez-y, posez votre question ...",
    "À quoi pensez-vous ...",
    "Avez-vous des pensées à partager ...",
    "Des questions spécifiques ...",
    "À quoi réfléchissez-vous ...",
    "Qu'est-ce qui vous préoccupe ...",
    "De quoi souhaitez-vous parler ..."
];

// Declare the $chatbot_markov_chain_fallback_response as global - Ver 2.1.6.1
global $chatbot_markov_chain_fallback_response;
$chatbot_markov_chain_fallback_response = [
    "Désolé, je n'ai trouvé aucune information pertinente pour répondre à votre requête. Pouvez-vous reformuler ou poser une autre question ?",
    "Je ne suis pas sûr d'avoir les bonnes informations pour cela. Pourriez-vous clarifier ou poser la question différemment ?",
    "Il semble que je n'ai pas les détails exacts que vous recherchez. Pourriez-vous reformuler la question ?",
    "Je n'ai rien trouvé sur ce sujet pour le moment. Pourriez-vous réessayer ?",
    "Je crains de ne pas avoir suffisamment d'informations sur ce sujet. Pourriez-vous fournir plus de détails ?",
    "Il semble que je manque la réponse à cela. Peut-être pourriez-vous reformuler ou poser une autre question ?"
];

// Declare the $chatbotFallbackResponses array as global
global $chatbotFallbackResponses;
$chatbotFallbackResponses = [
    "Je n'ai pas bien saisi cela. Pouvez-vous essayer de reformuler ?",
    "Hmm, je ne suis pas sûr d'avoir compris. Pouvez-vous expliquer différemment ?",
    "Je ne suis pas sûr de comprendre. Pouvez-vous poser la question autrement ?",
    "Désolé, cela ne me semble pas clair. Pouvez-vous clarifier ?",
    "J'ai peut-être manqué votre point. Pouvez-vous le formuler différemment ?",
    "J'ai du mal à comprendre. Pouvez-vous poser la question autrement ?",
    "Cela ne me semble pas clair. Pouvez-vous reformuler ?",
    "Je ne suis pas sûr de ce que vous voulez dire. Pouvez-vous essayer de l'expliquer différemment ?",
    "Je n'ai pas compris cela. Pouvez-vous poser à nouveau d'une autre manière ?",
    "Je suis un peu confus. Pouvez-vous fournir plus de détails ou reformuler ?",
    "Je suis désolé, je ne comprends pas. Pouvez-vous essayer de reformuler ?",
    "Ce n'est pas clair pour moi. Pouvez-vous poser la question d'une manière différente ?"
];

// Declare the $chatbot_chatgpt_fixed_literal_messages
global $chatbot_chatgpt_fixed_literal_messages;
$chatbot_chatgpt_fixed_literal_messages = [
    "Oups ! Quelque chose s'est mal passé de notre côté. Veuillez réessayer plus tard.",                        // [0]
    "Oups ! Je suis tombé à travers les mailles du filet !",                                                    // [1]
    "Oups ! Le téléchargement du fichier a échoué.",                                                            // [2]
    "Oups ! Votre clé API est manquante. Veuillez entrer votre clé API dans les paramètres du chatbot.",        // [3]
    "Oups ! Une erreur s'est produite pendant le téléchargement. Veuillez réessayer plus tard.",                // [4]
    "Oups ! Veuillez sélectionner un fichier à télécharger.",                                                   // [5]
    "Oups ! Vous avez atteint la limite de messages. Veuillez réessayer plus tard.",                            // [6]
    "Oups ! Quelque chose s'est mal passé de notre côté. Veuillez réessayer plus tard.",                        // [7]
    "Oups ! Un problème est survenu lors du téléchargement de la transcription. Veuillez réessayer plus tard.", // [8]
    "Oups ! Il n'y a pas de réponse à lire à haute voix.",                                                      // [9]
    "Oups ! Cette requête a expiré. Veuillez réessayer.",                                                       // [10]
    "Oups ! La conversion du texte en parole a échoué. Veuillez réessayer.",                                    // [11]
    "Oups ! Type de fichier non pris en charge. Veuillez réessayer.",                                           // [12]
    "Oups ! Le téléchargement du fichier a échoué. Veuillez réessayer.",                                        // [13]
    "Oups ! Impossible de supprimer la conversation. Veuillez réessayer.",                                      // [14]
    "Erreur : Clé API ou message invalide. Veuillez vérifier les paramètres du plugin.",                        // [15]
    "Conversation effacée. Veuillez patienter pendant que la page se recharge.",                                // [16]
    "Conversation effacée.",                                                                                    // [17]
    "Conversation non effacée.",                                                                                // [18]
];
