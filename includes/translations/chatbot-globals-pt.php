<?php
/**
 * Kognetiks Chatbot - Globals Portuguese - Ver 1.6.5
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
    "a", "sobre", "acima", "após", "novamente", "contra", "não é", "todos", "sou", "um", "e", "outro", "qualquer", "são", "não são", "como", "em",
    "ser", "porque", "foi", "antes", "sendo", "abaixo", "entre", "ambos", "mas", "por",
    "pode", "não pode", "não pode", "poderia", "não poderia",
    "ousar", "fez", "não fez", "diferente", "fazer", "faz", "não faz", "fazendo", "não faça", "abaixo", "durante",
    "cada", "ou", "suficiente", "todo", "todos", "tudo",
    "poucos", "primeiro", "para", "de", "mais",
    "obter", "dar", "ir", "indo", "obteve",
    "teve", "não teve", "tem", "não tem", "ter", "não têm", "tendo", "ele", "ele tinha", "ele terá", "ela", "aqui", "aqui está", "dela", "ela mesma", "ele está", "ele", "ele mesmo", "seu", "como", "como está",
    "eu", "eu tinha", "se", "eu irei", "eu estou", "em", "dentro", "é", "não é", "isso", "seu", "é", "si mesmo", "eu tenho",
    "apenas",
    "menos", "menor", "vamos", "pouco", "muito", "muitos",
    "muitos", "pode", "mim", "poderia", "não poderia", "mais", "a maioria", "muito", "deve", "não deve", "meu", "eu mesmo",
    "precisa", "não precisa", "nem", "nunca", "não", "nenhum", "nem", "não", "agora",
    "de", "fora", "em", "uma vez", "somente", "ou", "outro", "deve", "não deve", "nosso", "nossos", "nós mesmos", "fora", "sobre", "próprio",
    "muito",
    "realmente", "certo",
    "mesmo", "vários", "deve", "não deve", "ela", "ela tinha", "ela terá", "ela é", "deveria", "não deveria", "então", "alguém", "alguém", "algo", "alguma vez", "algum lugar", "tal",
    "pegar", "do que", "que", "é", "o", "seus", "delas", "eles", "eles mesmos", "então", "lá", "lá está", "estes", "eles", "eles tinham", "eles terão", "eles estão", "eles têm", "coisa", "coisas", "isso", "aqueles", "através", "tempo", "para", "também",
    "sob", "até", "para cima", "nós",
    "vários", "muito",
    "foi", "não foi", "nós", "nós tínhamos", "nós iremos", "éramos", "nós somos", "não éramos", "nós temos", "o que", "o que é", "quando", "quando é", "onde", "onde está", "qual", "enquanto", "quem", "quem", "quem", "quem é", "por que", "por que é", "vai", "com", "não vai", "gostaria", "não gostaria",
    "ainda", "você", "você tinha", "você terá", "seu", "você está", "seu", "seu", "você mesmo", "vocês mesmos", "você tem",
];

// Global abbreviations array
global $abbreviations;
$abbreviations = [
    // Latin-based Abbreviations
    "ou seja", "por exemplo", "etc.", "et al.", "N.B.", "cf.", "vs.", "viz.", "a.m.", "p.m.",
    // Time and Date
    "DC", "AC", "CE", "BCE", "GMT", "EST", "UTC",
    // Measurement
    "lb", "oz", "km", "cm", "ml", "ft",
    // Titles
    "Sr.", "Sra.", "Srta.", "Dr.", "Prof.",
    // Miscellaneous
    "FAQ", "DIY", "ASAP", "FYI", "RSVP", "P.S.", "AKA", "DOB", "TBD", "TBA", "ETA", "BTW",
];

// Declare the $learningMessages array as global
global $learningMessages;
$learningMessages = [
    " Por favor, observe que estou continuamente melhorando minhas habilidades. Enquanto isso, você pode encontrar mais informações aqui: ",
    " Atualmente estou no processo de expandir meus conhecimentos. Para detalhes adicionais, verifique: ",
    " Apenas um aviso - estou me aprimorando ativamente todos os dias. Enquanto isso, você pode explorar mais informações aqui: ",
    " Como ainda estou aprendendo, encorajo você a visitar: ",
    " Estou constantemente evoluindo e aprendendo. Por enquanto, você pode verificar aqui: ",
    " Tenha em mente que estou em uma jornada de aprendizado contínuo. Sinta-se à vontade para voltar a qualquer momento. Enquanto isso, você pode achar isso útil: ",
    " Ainda estou na fase de aprendizado, então sua paciência é muito apreciada. Talvez você encontre o que procura aqui: "
];

// Declare the $errorResponses array as global
global $errorResponses;
$errorResponses = [
    "Parece que pode ter havido um problema com a API. Vamos tentar novamente mais tarde.",
    "Infelizmente, podemos ter encontrado um problema com a API. Por favor, tente novamente em alguns minutos.",
    "Peço desculpas, mas parece que há um problema com a API no momento. Podemos tentar novamente mais tarde.",
    "A API parece estar enfrentando dificuldades agora. Podemos voltar a isso quando for resolvido.",
    "Desculpe, mas parece que há um erro no lado da API. Por favor, tente novamente mais tarde.",
    "Pode haver um problema temporário com a API. Por favor, tente novamente mais tarde.",
    "A API encontrou um erro, mas não se preocupe, isso acontece. Vamos tentar novamente mais tarde.",
    "Parece que pode haver um problema técnico com a API. Sinta-se à vontade para tentar novamente mais tarde para ver se tudo está funcionando bem."
];

// Declare the $no_matching_content_response array as global
global $no_matching_content_response;
$no_matching_content_response = [
    "Desculpe, mas não consegui encontrar nenhuma informação relevante sobre esse tópico. Você gostaria de tentar outra coisa?",
    "Infelizmente, não consegui localizar nenhuma informação relevante sobre esse tópico. Gostaria de perguntar algo mais?",
    "Receio que não consegui encontrar nenhuma informação relevante sobre esse tópico. Gostaria de tentar outra pergunta?",
    "Não consegui encontrar nenhuma informação relevante sobre esse tópico. Você gostaria de perguntar outra coisa?",
    "Desculpe, mas não consegui encontrar nenhuma informação sobre esse tópico. Gostaria de tentar outra pergunta?"
];

// Declare the $chatbot_chatgpt_bot_prompt as global - Ver 1.6.6
global $chatbot_chatgpt_bot_prompt;
$chatbot_chatgpt_bot_prompt = [
    "Digite sua pergunta ...",
    "Faça-me uma pergunta ...",
    "Estou ouvindo ...",
    "Estou aqui para ajudar ...",
    "Por favor, compartilhe seus pensamentos ...",
    "Sinta-se à vontade para me perguntar qualquer coisa ...",
    "Vá em frente, faça sua pergunta ...",
    "Em que você está pensando ...",
    "Algum pensamento para compartilhar ...",
    "Alguma pergunta específica ...",
    "O que você está ponderando ...",
    "O que está em sua mente ...",
    "Sobre o que você gostaria de falar ..."
];

// Declare the $chatbot_markov_chain_fallback_response as global - Ver 2.1.6.1
global $chatbot_markov_chain_fallback_response;
$chatbot_markov_chain_fallback_response = [
    "Desculpe, não consegui encontrar informações relevantes para responder à sua consulta. Você pode tentar reformular ou perguntar algo diferente?",
    "Não tenho certeza se tenho as informações certas para isso. Você poderia esclarecer ou perguntar de forma diferente?",
    "Parece que não tenho os detalhes exatos que você está procurando. Você poderia reformular a pergunta?",
    "Não consegui encontrar nada sobre esse tópico no momento. Você se importaria de tentar novamente?",
    "Receio que não tenho informações suficientes sobre isso. Você poderia fornecer mais detalhes?",
    "Parece que estou sem a resposta para isso. Talvez você possa reformular ou perguntar algo diferente?"
];

// Declare the $chatbotFallbackResponses array as global
global $chatbotFallbackResponses;
$chatbotFallbackResponses = [
    "Não entendi bem isso. Você pode tentar reformular?",
    "Hmm, não tenho certeza se entendi. Você pode explicar de forma diferente?",
    "Não estou acompanhando. Você poderia perguntar de outra maneira?",
    "Desculpe, isso não faz sentido para mim. Você poderia esclarecer?",
    "Talvez eu tenha perdido o seu ponto. Você pode dizer isso de forma diferente?",
    "Estou tendo dificuldades para entender. Você pode perguntar de outra maneira?",
    "Isso não parece claro para mim. Você pode reformular?",
    "Não tenho certeza do que você quer dizer. Você pode tentar explicar de forma diferente?",
    "Não entendi isso. Você poderia perguntar novamente de outra maneira?",
    "Estou um pouco confuso. Você pode fornecer mais detalhes ou reformular?",
    "Desculpe, não entendi. Você pode tentar reformular?",
    "Isso não está claro para mim. Você pode perguntar de outra forma?"
];

// Declare the $chatbot_chatgpt_fixed_literal_messages
global $chatbot_chatgpt_fixed_literal_messages;
$chatbot_chatgpt_fixed_literal_messages = [
    "Ops! Algo deu errado do nosso lado. Por favor, tente novamente mais tarde.",                           // [0]
    "Ops! Eu caí pelas rachaduras!",                                                                        // [1]
    "Ops! O upload do arquivo falhou.",                                                                     // [2]
    "Ops! Sua chave de API está ausente. Por favor, insira sua chave de API nas configurações do Chatbot.", // [3]
    "Ops! Algo deu errado durante o upload. Por favor, tente novamente mais tarde.",                        // [4]
    "Ops! Por favor, selecione um arquivo para fazer upload.",                                              // [5]
    "Ops! Você atingiu o limite de mensagens. Por favor, tente novamente mais tarde.",                      // [6]
    "Ops! Algo deu errado do nosso lado. Por favor, tente novamente mais tarde.",                           // [7]
    "Ops! Ocorreu um problema ao baixar a transcrição. Por favor, tente novamente mais tarde.",             // [8]
    "Ops! Não há resposta para ler em voz alta.",                                                           // [9]
    "Ops! Esta solicitação expirou. Por favor, tente novamente.",                                           // [10]
    "Ops! Falha ao converter texto em fala. Por favor, tente novamente.",                                   // [11]
    "Ops! Tipo de arquivo não suportado. Por favor, tente novamente.",                                      // [12]
    "Ops! Falha ao fazer upload do arquivo. Por favor, tente novamente.",                                   // [13]
    "Ops! Não foi possível limpar a conversa. Por favor, tente novamente.",                                 // [14]
    "Erro: Chave de API ou mensagem inválida. Por favor, verifique as configurações do plugin.",            // [15]
    "Conversa apagada. Por favor, aguarde enquanto a página recarrega.",                                    // [16]
    "Conversa apagada.",                                                                                    // [17]
    "Conversa não apagada.",                                                                                // [18]
    "O sistema está ocupado processando solicitações. Por favor, tente novamente mais tarde.",              // [19]
    "Mensagem em fila. Processando...",                                                                     // [20]
];
