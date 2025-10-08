<?php
/**
 * Kognetiks Chatbot - Globals Spanish - Ver 1.6.5
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
    "a", "acerca de", "arriba", "después", "de nuevo", "contra", "no", "todo", "soy", "un", "y", "otro", "alguno", "son", "no son", "como", "en",
    "ser", "porque", "estado", "antes", "siendo", "debajo", "entre", "ambos", "pero", "por",
    "puede", "no puede", "no puede", "podría", "no podría",
    "atreverse", "hizo", "no hizo", "diferente", "hacer", "hace", "no hace", "haciendo", "no", "abajo", "durante",
    "cada", "cualquiera", "suficiente", "todos", "todos", "todo",
    "pocos", "primero", "para", "desde", "además",
    "obtener", "dar", "ir", "yendo", "obtenido",
    "tenía", "no tenía", "tiene", "no tiene", "tener", "no tienen", "teniendo", "él", "él había", "él hará", "ella", "aquí", "aquí está", "suya", "ella misma", "él es", "él mismo", "su", "cómo", "cómo está",
    "yo", "yo había", "si", "yo haré", "yo soy", "en", "dentro", "es", "no es", "eso", "su", "es", "sí mismo", "yo tengo",
    "solo",
    "al menos", "menos", "vamos", "poco", "mucho", "muchos",
    "muchos", "puede", "mí", "podría", "no podría", "más", "la mayoría", "mucha", "debe", "no debe", "mi", "yo mismo",
    "necesitar", "no necesitar", "ni", "nunca", "no", "ninguno", "ni", "no", "ahora",
    "de", "fuera", "en", "una vez", "solo", "o", "otro", "debería", "no debería", "nuestro", "nuestros", "nosotros mismos", "fuera", "sobre", "propio",
    "bastante",
    "realmente", "derecho",
    "mismo", "varios", "deberá", "no deberá", "ella", "ella haría", "ella hará", "ella es", "debería", "no debería", "así", "alguien", "alguien", "algo", "algún tiempo", "algún lugar", "tal",
    "tomar", "que", "eso", "eso es", "el", "su", "los", "ellos mismos", "entonces", "ahí", "ahí está", "estos", "ellos", "ellos harían", "ellos harán", "ellos están", "ellos tienen", "cosa", "cosas", "esto", "esos", "a través de", "tiempo", "a", "también",
    "bajo", "hasta", "arriba", "nosotros",
    "varios", "muy",
    "fue", "no fue", "nosotros", "nosotros habíamos", "nosotros haremos", "eran", "nosotros somos", "no eran", "nosotros hemos", "qué", "qué es", "cuando", "cuando es", "dónde", "dónde está", "cual", "mientras", "quién", "a quién", "a quién", "quién es", "por qué", "por qué está", "hará", "con", "no lo hará", "haría", "no haría",
    "sin embargo", "tú", "tú habías", "tú harás", "tu", "tú eres", "tuyo", "tu", "tuyo", "tú mismo", "ustedes mismos", "tú has",
];

// Global abbreviations array
global $abbreviations;
$abbreviations = [
    // Latin-based Abbreviations
    "es decir", "p. ej.", "etc.", "et al.", "N.B.", "cf.", "vs.", "viz.", "a.m.", "p.m.",
    // Time and Date
    "AD", "BC", "CE", "BCE", "GMT", "EST", "UTC",
    // Measurement
    "lb", "oz", "km", "cm", "ml", "ft",
    // Titles
    "Sr.", "Sra.", "Srta.", "Dr.", "Prof.",
    // Miscellaneous
    "FAQ", "DIY", "ASAP", "FYI", "RSVP", "P.S.", "AKA", "DOB", "TBD", "TBA", "ETA", "Por cierto",
];

// Declare the $learningMessages array as global
global $learningMessages;
$learningMessages = [
    " Por favor, tenga en cuenta que estoy mejorando continuamente mis habilidades. Mientras tanto, puede encontrar más información aquí: ",
    " Actualmente estoy en proceso de ampliar mis conocimientos. Para más detalles, por favor revise: ",
    " Solo una nota: estoy mejorando activamente cada día. Mientras tanto, puede explorar más información aquí: ",
    " Como aún estoy aprendiendo, le sugiero que visite: ",
    " Estoy en constante evolución y aprendizaje. Por ahora, puede verificar: ",
    " Tenga en cuenta que estoy en un camino de aprendizaje continuo. Puede volver en cualquier momento. Mientras tanto, podría encontrar útil esto: ",
    " Todavía estoy en la fase de aprendizaje, por lo que su paciencia es muy apreciada. Podría encontrar lo que busca aquí: "
];

// Declare the $errorResponses array as global
global $errorResponses;
$errorResponses = [
    "Parece que puede haber un problema con la API. Intentémoslo de nuevo más tarde.",
    "Desafortunadamente, podríamos haber encontrado un problema con la API. Por favor, intente nuevamente en un momento.",
    "Mis disculpas, pero parece que hay un problema con la API en este momento. Podemos intentarlo de nuevo más tarde.",
    "La API parece estar experimentando dificultades en este momento. Podemos regresar a esto cuando se resuelva.",
    "Lo siento, pero parece que hay un error en el lado de la API. Por favor, inténtelo de nuevo más tarde.",
    "Podría haber un problema temporal con la API. Por favor, intente su solicitud de nuevo en un momento.",
    "La API encontró un error, pero no se preocupe, sucede. Intentémoslo de nuevo más tarde.",
    "Parece que podría haber un problema técnico con la API. Inténtelo de nuevo en un momento para ver si todo funciona sin problemas."
];

// Declare the $no_matching_content_response array as global
global $no_matching_content_response;
$no_matching_content_response = [
    "Lo siento, pero no pude encontrar ninguna información relevante sobre ese tema. ¿Le gustaría intentar otra cosa?",
    "Desafortunadamente, no pude localizar información relevante sobre ese tema. ¿Le gustaría preguntar algo más?",
    "Me temo que no pude encontrar ninguna información relevante sobre ese tema. ¿Le gustaría intentar otra pregunta?",
    "No pude encontrar ninguna información relevante sobre ese tema. ¿Le gustaría preguntar algo más?",
    "Lo siento, pero no pude encontrar información sobre ese tema. ¿Le gustaría intentar otra pregunta?"
];

// Declare the $chatbot_chatgpt_bot_prompt as global - Ver 1.6.6
global $chatbot_chatgpt_bot_prompt;
$chatbot_chatgpt_bot_prompt = [
    "Ingrese su pregunta ...",
    "Hágame una pregunta ...",
    "Estoy escuchando ...",
    "Estoy aquí para ayudar ...",
    "Por favor, comparta sus pensamientos ...",
    "Siéntase libre de preguntarme cualquier cosa ...",
    "Adelante, pregunte ...",
    "¿En qué está pensando ...",
    "¿Tiene algún pensamiento para compartir ...",
    "¿Alguna pregunta específica ...",
    "¿Qué está reflexionando ...",
    "¿Qué tiene en mente ...",
    "¿De qué le gustaría hablar ..."
];

// Declare the $chatbot_markov_chain_fallback_response as global - Ver 2.1.6.1
global $chatbot_markov_chain_fallback_response;
$chatbot_markov_chain_fallback_response = [
    "Lo siento, no pude encontrar información relevante para responder a su consulta. ¿Puede intentar reformular o preguntar algo más?",
    "No estoy seguro de tener la información adecuada para eso. ¿Podría aclararlo o preguntar de otra manera?",
    "Parece que no tengo los detalles exactos que busca. ¿Podría reformular la pregunta?",
    "No pude encontrar nada sobre ese tema en este momento. ¿Le importaría intentarlo de nuevo?",
    "Me temo que no tengo suficiente información sobre eso. ¿Podría proporcionar más detalles?",
    "Parece que me falta la respuesta para eso. Tal vez podría reformular o preguntar algo más."
];

// Declare the $chatbotFallbackResponses array as global
global $chatbotFallbackResponses;
$chatbotFallbackResponses = [
    "No entendí eso del todo. ¿Puede intentar reformularlo?",
    "Hmm, no estoy seguro de haberlo entendido. ¿Puede explicarlo de otra manera?",
    "No lo sigo. ¿Podría preguntar eso de otra forma?",
    "Lo siento, eso no tiene sentido para mí. ¿Podría aclararlo?",
    "Tal vez me perdí su punto. ¿Podría decirlo de otra manera?",
    "Estoy teniendo problemas para entenderlo. ¿Podría preguntar de otra forma?",
    "Eso no parece claro para mí. ¿Puede reformularlo?",
    "No estoy seguro de lo que quiere decir. ¿Podría intentar explicarlo de manera diferente?",
    "No entendí eso. ¿Podría preguntar nuevamente de otra forma?",
    "Estoy un poco confundido. ¿Puede proporcionar más detalles o reformularlo?",
    "Lo siento, no entiendo. ¿Podría intentar reformularlo?",
    "Eso no está claro para mí. ¿Podría preguntarlo de una manera diferente?"
];

// Declare the $chatbot_chatgpt_fixed_literal_messages
global $chatbot_chatgpt_fixed_literal_messages;
$chatbot_chatgpt_fixed_literal_messages = [
    "¡Ups! Algo salió mal de nuestro lado. Por favor, inténtalo de nuevo más tarde.",                   // [0]
    "¡Ups! ¡Me he caído por las grietas!",                                                              // [1]
    "¡Ups! La carga del archivo falló.",                                                                // [2]
    "¡Ups! Falta tu clave API. Por favor, introduce tu clave API en la configuración del chatbot.",     // [3]
    "¡Ups! Algo salió mal durante la carga. Por favor, inténtalo de nuevo más tarde.",                  // [4]
    "¡Ups! Por favor selecciona un archivo para cargar.",                                               // [5]
    "¡Ups! Has alcanzado el límite de mensajes. Por favor, inténtalo de nuevo más tarde.",              // [6]
    "¡Ups! Algo salió mal de nuestro lado. Por favor, inténtalo de nuevo más tarde.",                   // [7]
    "¡Ups! Hubo un problema al descargar la transcripción. Por favor, inténtalo de nuevo más tarde.",   // [8]
    "¡Ups! No hay ninguna respuesta para leer en voz alta.",                                            // [9]
    "¡Ups! Esta solicitud se agotó. Por favor, inténtalo de nuevo.",                                    // [10]
    "¡Ups! Falló la conversión de texto a voz. Por favor, inténtalo de nuevo.",                         // [11]
    "¡Ups! Tipo de archivo no compatible. Por favor, inténtalo de nuevo.",                              // [12]
    "¡Ups! Falló la carga del archivo. Por favor, inténtalo de nuevo.",                                 // [13]
    "¡Ups! No se pudo borrar la conversación. Por favor, inténtalo de nuevo.",                          // [14]
    "Error: Clave API o mensaje no válido. Por favor, verifica la configuración del complemento.",      // [15]
    "Conversación borrada. Por favor, espera mientras se recarga la página.",                           // [16]
    "Conversación borrada.",                                                                            // [17]
    "Conversación no borrada.",                                                                         // [18]
    "El sistema está ocupado procesando solicitudes. Por favor, inténtalo de nuevo más tarde.",         // [19]
    "Mensaje en cola. Procesando...",                                                                   // [20]
];
