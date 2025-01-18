<?php
/**
 * Kognetiks Chatbot - Globals Russian - Ver 1.6.5
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
    "а", "о", "над", "после", "снова", "против", "не", "все", "я", "один", "и", "другой", "любой", "есть", "не есть", "как", "в",
    "быть", "потому что", "был", "до", "будучи", "под", "между", "оба", "но", "по",
    "может", "не может", "не может", "мог", "не мог",
    "осмелюсь", "сделал", "не сделал", "различный", "делать", "делает", "не делает", "делая", "не делай", "вниз", "во время",
    "каждый", "или", "достаточно", "все", "все", "все",
    "несколько", "первый", "для", "из", "дальше",
    "получить", "дать", "идти", "идет", "получил",
    "имел", "не имел", "имеет", "не имеет", "иметь", "не имеют", "имея", "он", "он бы", "он будет", "ее", "здесь", "здесь есть", "ее", "себя", "он есть", "его", "сам", "его", "как", "как есть",
    "я", "я бы", "если", "я буду", "я есть", "в", "внутри", "есть", "не есть", "это", "его", "это есть", "само", "я имею",
    "только",
    "наименее", "меньше", "давайте", "немного", "много", "множество",
    "многие", "может", "мне", "мог", "не мог", "больше", "большинство", "много", "должен", "не должен", "мой", "я сам",
    "нужда", "не нужда", "ни", "никогда", "нет", "никакой", "ни", "не", "сейчас",
    "из", "вне", "на", "однажды", "только", "или", "другой", "должен", "не должен", "наш", "наш", "мы сами", "снаружи", "сверх", "свой",
    "много",
    "действительно", "право",
    "тот же", "несколько", "должен", "не должен", "она", "она бы", "она будет", "она есть", "должен", "не должен", "так", "кто-то", "кто-то", "что-то", "когда-то", "где-то", "такой",
    "взять", "чем", "это", "это есть", "этот", "их", "их", "их", "сами", "тогда", "там", "там есть", "эти", "они", "они бы", "они будут", "они есть", "они имеют", "вещь", "вещи", "это", "те", "через", "время", "в", "тоже",
    "под", "до", "вверх", "нас",
    "различные", "очень",
    "было", "не было", "мы", "мы бы", "мы будем", "были", "мы есть", "не были", "мы имеем", "что", "что есть", "когда", "когда есть", "где", "где есть", "который", "пока", "кто", "кого", "кого", "кто есть", "почему", "почему есть", "будет", "с", "не будет", "будет", "не будет",
    "еще", "вы", "вы бы", "вы будете", "ваш", "вы есть", "ваше", "ваше", "вы сами", "вы сами", "вы имеете",
];

// Global abbreviations array
global $abbreviations;
$abbreviations = [
    // Latin-based Abbreviations
    "т.е.", "например", "и т.д.", "и др.", "NB.", "ср.", "против.", "например.", "утра.", "вечера.",
    // Time and Date
    "н.э.", "до н.э.", "CE", "BCE", "GMT", "EST", "UTC",
    // Measurement
    "фунт", "унция", "км", "см", "мл", "фут",
    // Titles
    "г-н", "г-жа", "г-ца", "д-р", "проф.",
    // Miscellaneous
    "FAQ", "Сделай сам", "ASAP", "FYI", "RSVP", "P.S.", "AKA", "DOB", "TBD", "TBA", "ETA", "BTW",
];

// Declare the $learningMessages array as global
global $learningMessages;
$learningMessages = [
    " Пожалуйста, обратите внимание, что я постоянно совершенствую свои способности. Пока что вы можете найти больше информации здесь: ",
    " В настоящее время я нахожусь в процессе расширения своих знаний. Для получения дополнительной информации, пожалуйста, проверьте: ",
    " Просто напоминание - я активно совершенствуюсь каждый день. Между тем вы можете изучить больше информации здесь: ",
    " Поскольку я все еще осваиваю основы, я рекомендую вам посетить: ",
    " Я постоянно развиваюсь и учусь. Пока что вы можете проверить это здесь: ",
    " Имейте в виду, что я нахожусь на пути непрерывного обучения. Возвращайтесь в любое время. Между тем вы можете найти это полезным: ",
    " Я все еще нахожусь в процессе обучения, поэтому ваше терпение очень ценится. Возможно, вы найдете то, что ищете, здесь: "
];

// Declare the $errorResponses array as global
global $errorResponses;
$errorResponses = [
    "Кажется, возникла проблема с API. Попробуем позже.",
    "К сожалению, мы могли столкнуться с проблемой API. Пожалуйста, попробуйте снова немного позже.",
    "Извините, но, похоже, API сейчас работает с перебоями. Мы можем попробовать позже.",
    "API, похоже, сейчас испытывает трудности. Мы можем вернуться к этому, когда это будет решено.",
    "Извините, но, похоже, возникла ошибка на стороне API. Попробуйте еще раз чуть позже.",
    "Может быть временная проблема с API. Пожалуйста, попробуйте снова позже.",
    "API столкнулось с ошибкой, но не волнуйтесь, это бывает. Попробуем позже.",
    "Кажется, что это может быть техническая проблема с API. Попробуйте снова позже, чтобы убедиться, что все работает."
];

// Declare the $no_matching_content_response array as global
global $no_matching_content_response;
$no_matching_content_response = [
    "Извините, но я не смог найти соответствующую информацию по этой теме. Хотите попробовать что-то другое?",
    "К сожалению, я не смог найти подходящую информацию по этой теме. Хотите задать другой вопрос?",
    "Боюсь, я не нашел соответствующую информацию по этой теме. Хотите попробовать другой вопрос?",
    "Я не нашел подходящей информации по этой теме. Хотите задать другой вопрос?",
    "Извините, но я не смог найти информацию по этой теме. Хотите попробовать другой вопрос?"
];

// Declare the $chatbot_chatgpt_bot_prompt as global - Ver 1.6.6
global $chatbot_chatgpt_bot_prompt;
$chatbot_chatgpt_bot_prompt = [
    "Введите ваш вопрос ...",
    "Задайте мне вопрос ...",
    "Я вас слушаю ...",
    "Я здесь, чтобы помочь ...",
    "Пожалуйста, поделитесь своими мыслями ...",
    "Не стесняйтесь спросить меня о чем угодно ...",
    "Вперед, задавайте вопрос ...",
    "О чем вы думаете ...",
    "Какие мысли хотите поделиться ...",
    "Есть конкретные вопросы ...",
    "Над чем вы размышляете ...",
    "Что у вас на уме ...",
    "О чем бы вы хотели поговорить ..."
];

// Declare the $chatbot_markov_chain_fallback_response as global - Ver 2.1.6.1
global $chatbot_markov_chain_fallback_response;
$chatbot_chatgpt_bot_prompt = [
    "Извините, я не нашел подходящей информации для ответа на ваш запрос. Попробуйте переформулировать или спросить что-то другое?",
    "Я не уверен, что у меня есть правильная информация для этого. Можете уточнить или задать вопрос по-другому?",
    "Кажется, у меня нет точных данных, которые вы ищете. Можете переформулировать вопрос?",
    "Я ничего не нашел по этой теме на данный момент. Не могли бы вы попробовать снова?",
    "Боюсь, у меня недостаточно информации об этом. Можете предоставить больше деталей?",
    "Кажется, я не могу ответить на это. Возможно, попробуйте переформулировать или задать что-то другое?"
];

// Declare the $chatbotFallbackResponses array as global
$chatbotFallbackResponses = [
    "Я не совсем понял это. Можете попробовать переформулировать?",
    "Хм, я не уверен, что понял. Можете объяснить по-другому?",
    "Я не понимаю. Можете спросить иначе?",
    "Извините, это не имеет смысла для меня. Можете уточнить?",
    "Возможно, я упустил ваш пункт. Можете сказать это иначе?",
    "Мне сложно это понять. Можете спросить иначе?",
    "Это не кажется мне ясным. Можете переформулировать?",
    "Я не уверен, что вы имеете в виду. Можете попробовать объяснить по-другому?",
    "Я не понял это. Можете задать это снова иначе?",
    "Я немного запутался. Можете предоставить больше деталей или переформулировать?",
    "Извините, я не понимаю. Можете попробовать переформулировать?",
    "Это мне неясно. Можете спросить по-другому?"
];
