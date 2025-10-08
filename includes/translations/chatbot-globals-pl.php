<?php
/**
 * Kognetiks Chatbot - Globals Polish - Ver 1.6.5
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
    "a", "o", "nad", "po", "znowu", "przeciwko", "nie", "wszyscy", "jestem", "an", "i", "kolejny", "każdy", "są", "nie są", "jako", "w",
    "być", "ponieważ", "był", "przed", "bycie", "poniżej", "między", "oba", "ale", "przez",
    "może", "nie może", "nie może", "mógł", "nie mógł",
    "odważ się", "zrobił", "nie zrobił", "różny", "zrobić", "robi", "nie robi", "robi", "nie robi", "w dół", "podczas",
    "każdy", "albo", "wystarczająco", "każdy", "wszyscy", "wszystko",
    "kilku", "pierwszy", "dla", "z", "dalej",
    "zdobyć", "dać", "iść", "iść", "zdobył",
    "miał", "nie miał", "ma", "nie ma", "mieć", "nie mają", "mając", "on", "on by", "on będzie", "jej", "tutaj", "tutaj jest", "jej", "sama", "on jest", "go", "sam", "jego", "jak", "jak jest",
    "ja", "ja bym", "jeśli", "ja będę", "ja jestem", "w", "do", "jest", "nie jest", "to", "jego", "to jest", "to samo", "mam",
    "tylko",
    "najmniej", "mniej", "chodźmy", "mało", "dużo", "wiele",
    "wiele", "może", "mnie", "mógł", "nie mógł", "więcej", "najwięcej", "dużo", "musi", "nie musi", "mój", "ja sam",
    "potrzeba", "nie potrzeba", "ani", "nigdy", "nie", "żaden", "ani", "nie", "teraz",
    "z", "poza", "na", "raz", "tylko", "lub", "inny", "powinien", "nie powinien", "nasz", "nasze", "my sami", "na zewnątrz", "nad", "własny",
    "dużo",
    "naprawdę", "prawo",
    "ten sam", "kilka", "powinien", "nie powinien", "ona", "ona by", "ona będzie", "ona jest", "powinien", "nie powinien", "więc", "ktoś", "ktoś", "coś", "kiedyś", "gdzieś", "taki",
    "zabrać", "niż", "że", "to jest", "te", "ich", "ich", "ich", "siebie", "wtedy", "tam", "tam jest", "te", "oni", "oni by", "oni będą", "oni są", "oni mają", "rzecz", "rzeczy", "to", "te", "przez", "czas", "do", "też",
    "pod", "aż", "do góry", "nas",
    "różne", "bardzo",
    "było", "nie było", "my", "my byśmy", "my będziemy", "byli", "my jesteśmy", "nie byli", "my mamy", "co", "co jest", "kiedy", "kiedy jest", "gdzie", "gdzie jest", "który", "podczas", "kto", "kogo", "kogo", "kto jest", "dlaczego", "dlaczego jest", "będzie", "z", "nie będzie", "byłoby", "nie byłoby",
    "jeszcze", "ty", "ty byś", "ty będziesz", "twoje", "ty jesteś", "twoje", "twoje", "ty sam", "wy sami", "ty masz",
];

// Global abbreviations array
global $abbreviations;
$abbreviations = [
    // Latin-based Abbreviations
    "np.", "tj.", "itd.", "et al.", "NB.", "cf.", "vs.", "viz.", "a.m.", "p.m.",
    // Time and Date
    "n.e.", "p.n.e.", "CE", "BCE", "GMT", "EST", "UTC",
    // Measurement
    "lb", "oz", "km", "cm", "ml", "ft",
    // Titles
    "Pan", "Pani", "Panna", "Dr", "Prof.",
    // Miscellaneous
    "FAQ", "Zrób-to-sam", "ASAP", "FYI", "RSVP", "P.S.", "AKA", "DOB", "TBD", "TBA", "ETA", "BTW",
];

// Declare the $learningMessages array as global
global $learningMessages;
$learningMessages = [
    " Proszę pamiętać, że nieustannie doskonalę swoje umiejętności. Tymczasem możesz znaleźć więcej informacji tutaj: ",
    " Obecnie poszerzam swoją wiedzę. Aby uzyskać więcej szczegółów, sprawdź tutaj: ",
    " Tylko przypomnienie - każdego dnia aktywnie się doskonalę. Tymczasem możesz zbadać więcej informacji tutaj: ",
    " Ponieważ nadal opanowuję podstawy, zachęcam cię do odwiedzenia: ",
    " Ciągle się rozwijam i uczę. Na razie możesz sprawdzić to: ",
    " Pamiętaj, że jestem w trakcie ciągłego uczenia się. Możesz wrócić w dowolnym momencie. Tymczasem może to być przydatne: ",
    " Wciąż jestem w fazie nauki, więc twoja cierpliwość jest bardzo doceniana. Może znajdziesz to, czego szukasz tutaj: "
];

// Declare the $errorResponses array as global
global $errorResponses;
$errorResponses = [
    "Wygląda na to, że mogło dojść do problemu z API. Spróbujmy później ponownie.",
    "Niestety, moglibyśmy napotkać problem z API. Spróbuj ponownie za chwilę.",
    "Przepraszam, ale wygląda na to, że obecnie jest problem z API. Możemy spróbować ponownie później.",
    "API wydaje się mieć trudności w tej chwili. Możemy wrócić do tego, gdy zostanie rozwiązane.",
    "Przepraszam, ale wygląda na to, że jest błąd po stronie API. Spróbuj ponownie za chwilę.",
    "Może występować tymczasowy problem z API. Spróbuj ponownie za chwilę.",
    "API napotkało błąd, ale nie martw się, zdarza się. Spróbujmy ponownie później.",
    "Wygląda na to, że może być problem techniczny z API. Spróbuj ponownie za chwilę, aby zobaczyć, czy wszystko działa sprawnie."
];

// Declare the $no_matching_content_response array as global
global $no_matching_content_response;
$no_matching_content_response = [
    "Przepraszam, ale nie znalazłem żadnych istotnych informacji na ten temat. Czy chciałbyś spróbować czegoś innego?",
    "Niestety, nie mogłem znaleźć żadnych istotnych informacji na ten temat. Czy chciałbyś zadać inne pytanie?",
    "Obawiam się, że nie znalazłem żadnych istotnych informacji na ten temat. Czy chciałbyś spróbować innego pytania?",
    "Nie znalazłem żadnych istotnych informacji na ten temat. Czy chciałbyś zadać inne pytanie?",
    "Przepraszam, ale nie znalazłem żadnych informacji na ten temat. Czy chciałbyś spróbować innego pytania?"
];

// Declare the $chatbot_chatgpt_bot_prompt as global - Ver 1.6.6
global $chatbot_chatgpt_bot_prompt;
$chatbot_chatgpt_bot_prompt = [
    "Wpisz swoje pytanie ...",
    "Zadaj mi pytanie ...",
    "Słucham ...",
    "Jestem tutaj, aby pomóc ...",
    "Proszę, podziel się swoimi myślami ...",
    "Nie wahaj się zapytać mnie o cokolwiek ...",
    "Śmiało, zadaj pytanie ...",
    "O czym myślisz ...",
    "Masz jakieś przemyślenia do podzielenia się ...",
    "Jakieś konkretne pytania ...",
    "Nad czym się zastanawiasz ...",
    "Co cię trapi ...",
    "O czym chciałbyś porozmawiać ..."
];

// Declare the $chatbot_markov_chain_fallback_response as global - Ver 2.1.6.1
global $chatbot_markov_chain_fallback_response;
$chatbot_markov_chain_fallback_response = [
    "Przepraszam, nie znalazłem żadnych istotnych informacji, aby odpowiedzieć na twoje zapytanie. Czy możesz spróbować przeformułować lub zapytać o coś innego?",
    "Nie jestem pewien, czy mam odpowiednie informacje na ten temat. Czy możesz wyjaśnić lub zapytać inaczej?",
    "Wydaje się, że nie mam dokładnych szczegółów, których szukasz. Czy możesz przeformułować pytanie?",
    "Nie znalazłem nic na ten temat w tej chwili. Czy możesz spróbować ponownie?",
    "Obawiam się, że nie mam wystarczających informacji na ten temat. Czy możesz podać więcej szczegółów?",
    "Wygląda na to, że brakuje mi odpowiedzi na to pytanie. Może możesz je przeformułować lub zapytać o coś innego?"
];

// Declare the $chatbotFallbackResponses array as global
global $chatbotFallbackResponses;
$chatbotFallbackResponses = [
    "Nie do końca to zrozumiałem. Czy możesz spróbować przeformułować?",
    "Hmm, nie jestem pewien, czy to zrozumiałem. Czy możesz to wyjaśnić inaczej?",
    "Nie rozumiem. Czy możesz zapytać w inny sposób?",
    "Przepraszam, to dla mnie nie ma sensu. Czy możesz wyjaśnić?",
    "Może przegapiłem twój punkt. Czy możesz powiedzieć to inaczej?",
    "Mam trudności ze zrozumieniem. Czy możesz zapytać w inny sposób?",
    "To nie wydaje mi się jasne. Czy możesz to przeformułować?",
    "Nie jestem pewien, co masz na myśli. Czy możesz spróbować to wyjaśnić inaczej?",
    "Nie zrozumiałem tego. Czy możesz zapytać ponownie w inny sposób?",
    "Jestem trochę zdezorientowany. Czy możesz podać więcej szczegółów lub to przeformułować?",
    "Przepraszam, nie rozumiem. Czy możesz spróbować to przeformułować?",
    "To jest dla mnie niejasne. Czy możesz zapytać w inny sposób?"
];

// Declare the $chatbot_chatgpt_fixed_literal_messages
global $chatbot_chatgpt_fixed_literal_messages;
$chatbot_chatgpt_fixed_literal_messages = [
    "Ups! Coś poszło nie tak po naszej stronie. Spróbuj ponownie później.",                     // [0]
    "Ups! Wpadłem w szczelinę!",                                                                // [1]
    "Ups! Przesyłanie pliku nie powiodło się.",                                                 // [2]
    "Ups! Brakuje Twojego klucza API. Wprowadź swój klucz API w ustawieniach Chatbota.",        // [3]
    "Ups! Coś poszło nie tak podczas przesyłania. Spróbuj ponownie później.",                   // [4]
    "Ups! Wybierz plik do przesłania.",                                                         // [5]
    "Ups! Osiągnąłeś limit wiadomości. Spróbuj ponownie później.",                              // [6]
    "Ups! Coś poszło nie tak po naszej stronie. Spróbuj ponownie później.",                     // [7]
    "Ups! Wystąpił problem podczas pobierania transkrypcji. Spróbuj ponownie później.",         // [8]
    "Ups! Brak odpowiedzi do odczytania na głos.",                                              // [9]
    "Ups! Żądanie przekroczyło limit czasu. Spróbuj ponownie.",                                 // [10]
    "Ups! Konwersja tekstu na mowę nie powiodła się. Spróbuj ponownie.",                        // [11]
    "Ups! Nieobsługiwany typ pliku. Spróbuj ponownie.",                                         // [12]
    "Ups! Przesyłanie pliku nie powiodło się. Spróbuj ponownie.",                               // [13]
    "Ups! Nie można usunąć rozmowy. Spróbuj ponownie.",                                         // [14]
    "Błąd: Nieprawidłowy klucz API lub wiadomość. Sprawdź ustawienia wtyczki.",                 // [15]
    "Rozmowa została usunięta. Proszę czekać, aż strona się odświeży.",                         // [16]
    "Rozmowa została usunięta.",                                                                // [17]
    "Rozmowa nie została usunięta.",                                                            // [18]
    "System jest zajęty przetwarzaniem żądań. Spróbuj ponownie później.",                       // [19]
    "Wiadomość w kolejce. Przetwarzanie...",                                                    // [20]
];
