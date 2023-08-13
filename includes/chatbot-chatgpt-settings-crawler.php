<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Crawler aka Knowledge Navigator
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */
 
global $start_url, $domain, $max_depth, $max_top_words, $results_csv_file, $results_json_file, $chatgpt_diagnostics, $plugin_dir_path, $results_dir_path, $no_of_links_crawled;
$start_url = site_url();
$domain = parse_url($start_url, PHP_URL_HOST);
$max_depth = esc_attr(get_option('chatbot_chatgpt_kn_maximum_depth', 2)); // Default to 2
$max_top_words = esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 25)); // Default to 25
// $no_of_links_crawled = 0; // Default to 0 
// update_option('no_of_links_crawled', $no_of_links_crawled);
 
// Get the absolute path to the plugin directory
$plugin_dir_path = plugin_dir_path(__FILE__);

// Go up one level to the parent directory
$parent_dir_path = dirname($plugin_dir_path);

// Create a "results" subdirectory in the parent directory if it doesn't exist
$results_dir_path = $parent_dir_path . '/results/';

if (!file_exists($results_dir_path)) {
    mkdir($results_dir_path, 0755, true);
}

// Specify the output files' paths
$results_csv_file = $results_dir_path . 'results.csv';
$results_json_file = $results_dir_path . 'results.json';

class WebCrawler {
    private $document;
    private $frequencyData = [];
    private $visitedUrls = [];
    
    public function __construct($url) {
        // Check if URL starts with http:// or https://, if not add http:// as default
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }
        
        // Validate the URL before passing it to file_get_contents
        if(filter_var($url, FILTER_VALIDATE_URL)) {
            $this->document = @file_get_contents($url);
            
            // Check if the document is empty after fetching
            if ($this->document === false) {
                throw new Exception("Failed to fetch content from URL: $url");
            } else if (empty($this->document)) {
                throw new Exception("Content from URL is empty: $url");
            }
        } else {
            throw new Exception('Invalid URL');
        }
    }
    
    public function computeFrequency() {
        // List of common stop words to be ignored
        $stopWords = ['a', 'about', 'above', 'after', 'again', 'against', 'all', 'am', 'an', 'and', 'any', 'are', "aren't", 'as', 'at'];
        $stopWords = array_merge($stopWords, ['b', 'be', 'because', 'been', 'before', 'being', 'below', 'between', 'both', 'but', 'by']);
        $stopWords = array_merge($stopWords, ['c', 'can', "can't", 'cannot', 'could', "couldn't"]);
        $stopWords = array_merge($stopWords, ['d', 'did', "didn't", 'do', 'does', "doesn't", 'doing', "don't", 'down', 'during']);
        $stopWords = array_merge($stopWords, ['e', 'each']);
        $stopWords = array_merge($stopWords, ['f', 'few', 'for', 'from', 'further']);
        $stopWords = array_merge($stopWords, ['g']);
        $stopWords = array_merge($stopWords, ['h', 'had', "hadn't", 'has', "hasn't", 'have', "haven't", 'having', 'he', "he'd", "he'll", "he's", 'her', 'here', "here's", 'hers', 'herself', 'him', 'himself', 'his', 'how', "how's"]);
        $stopWords = array_merge($stopWords, ['i', "i'd", "i'll", "i'm", "i've", 'if', 'in', 'into', 'is', "isn't", 'it', "it's", 'its', 'itself']);
        $stopWords = array_merge($stopWords, ['j', 'k']);
        $stopWords = array_merge($stopWords, ['l', "let's"]);
        $stopWords = array_merge($stopWords, ['m', 'me', 'more', 'most', "mustn't", 'my', 'myself']);
        $stopWords = array_merge($stopWords, ['n', 'no', 'nor', 'not']);
        $stopWords = array_merge($stopWords, ['o', 'of', 'off', 'on', 'once', 'only', 'or', 'other', 'ought', 'our', 'ours' ,'ourselves', 'out', 'over', 'own']);
        $stopWords = array_merge($stopWords, ['p', 'q']);
        $stopWords = array_merge($stopWords, ['r', 're']);
        $stopWords = array_merge($stopWords, ['s', 'same', "shan't", 'she', "she'd", "she'll", "she's", 'should', "shouldn't", 'so', 'some', 'such']);
        $stopWords = array_merge($stopWords, ['t', 'than', 'that', "that's", 'the', 'their', 'theirs', 'them', 'themselves', 'then', 'there', "there's", 'these', 'they', "they'd", "they'll", "they're", "they've", 'this', 'those', 'through', 'to', 'too']);
        $stopWords = array_merge($stopWords, ['u', 'under', 'until', 'up']);
        $stopWords = array_merge($stopWords, ['v', 'very']);
        $stopWords = array_merge($stopWords, ['w', 'was', "wasn't", 'we', "we'd", "we'll", "we're", "we've", 'were', "weren't", 'what', "what's", 'when', "when's", 'where', "where's", 'which', 'while', 'who', "who's", 'whom', 'why', "why's", 'with', "won't", 'would', "wouldn't"]);
        $stopWords = array_merge($stopWords, ['x']);
        $stopWords = array_merge($stopWords, ['y', 'you', "you'd", "you'll", "you're", "you've", 'your', 'yours', 'yourself', 'yourselves']);
        $stopWords = array_merge($stopWords, ['z']);
    
        // Upgraded the above - Ver 1.6.1
        $dom = new DOMDocument;
        libxml_use_internal_errors(true); // Suppress HTML parsing errors
        $dom->loadHTML($this->document);
        
        // Remove script and style elements
        foreach ($dom->getElementsByTagName('script') as $script) {
            $script->parentNode->removeChild($script);
        }
        foreach ($dom->getElementsByTagName('style') as $style) {
            $style->parentNode->removeChild($style);
        }
        
        // Extract text content only from specific tags
        $textContent = '';
        foreach (['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'a'] as $tagName) {
            $elements = $dom->getElementsByTagName($tagName);
            foreach ($elements as $element) {
                $textContent .= ' ' . $element->textContent;
            }
        }
        
        // Replace all non-word characters with a space
        $documentWithoutTags = preg_replace('/\W+/', ' ', $textContent);
        
        // Get words and convert to lower case
        $words = str_word_count(strtolower($documentWithoutTags), 1);    
    
        // Filter out stop words
        $words = array_diff($words, $stopWords);
    
        // Compute frequencies
        $this->frequencyData = array_count_values($words);
        $this->totalWordCount = count($this->frequencyData);
    }
    
    public function computeTFIDF($term) {
        $tf = $this->frequencyData[$term] / $this->totalWordCount;
        $idf = $this->computeInverseDocumentFrequency($term);

        return $tf * $idf;
    }

    private function computeTermFrequency($term) {
        return $this->frequencyData[$term] / count($this->frequencyData);
    }

    private function computeInverseDocumentFrequency($term) {
        $numDocumentsWithTerm = 0;
        foreach ($this->frequencyData as $word => $frequency) {
            if ($word === $term) {
                $numDocumentsWithTerm++;
            }
        }
        
        return log(count($this->frequencyData) / ($numDocumentsWithTerm + 1));
    }

    public function getFrequencyData() {
        return $this->frequencyData;
    }

    public function removeWordFromFrequencyData($word) {
        unset($this->frequencyData[$word]);
    }

    public function crawl($depth = 0, $domain = '') {

        // error_log("crawl: top of function");
        // $no_of_links_crawled = 0;
        // update_option('no_of_links_crawled', $no_of_links_crawled);

        $max_depth = isset($GLOBALS['max_depth']) ? (int) $GLOBALS['max_depth'] : 3;  // default to 3 if not set

        if ($depth > $max_depth) {
            // error_log("crawl: $depth > max_depth");
            return;
        }
    
        try {
            $this->computeFrequency();
    
            $urls = $this->getLinks($domain);
            foreach ($urls as $url) {

                if (in_array($url, $this->visitedUrls)) {
                    // Skip this URL as it has already been crawled
                    continue;
                }

                // TODO Log the variables to debug.log
                // error_log("CRAWLING :" . $url);

                // The second parameter is the default value if the option is not set.
                $kn_crawler_status = get_option('chatbot_chatgpt_kn_status', 'In Process');

                // Increment the number of links crawled.
                $no_of_links_crawled = get_option('no_of_links_crawled', 0);
                $no_of_links_crawled += 1;
                update_option('no_of_links_crawled', $no_of_links_crawled);

                // Get the current status without appending the number of links crawled.
                $kn_crawler_status = get_option('chatbot_chatgpt_kn_status', 'In Process');
                update_option('chatbot_chatgpt_kn_status', $kn_crawler_status);

                $start_time = microtime(true);

                // Mark this URL as visited
                $this->visitedUrls[] = $url;
    
                $crawler = new WebCrawler($url);
                $crawler->crawl($depth + 1, $domain);
    
                $end_time = microtime(true);
                $duration = $end_time - $start_time;
    
                // Added the adaptive delay. Multiply the duration by a factor to determine the delay.
                // The value of the factor would depends on any specific requirements. In this case, the default chosen 2.
                $adaptive_delay = (int)($duration * 2);
                
                // Set a maximum limit to the delay to prevent it from being too long.
                $max_delay = 5;
                $adaptive_delay = min($adaptive_delay, $max_delay);
                
                sleep($adaptive_delay);
            }
        } catch (Exception $e) {
            // Log the exception and continue with the next URL
            // error_log("Crawl failed: " . $e->getMessage());
        }
    }

    public function getLinks($domain) {
        if (empty($this->document)) {
            throw new Exception("Document is empty. Cannot parse HTML.");
        }
    
        $dom = new DOMDocument();
        @$dom->loadHTML($this->document);
        $links = $dom->getElementsByTagName('a');
    
        $urls = [];
        foreach ($links as $link){
            $href = $link->getAttribute('href');
            $rel = $link->getAttribute('rel');
            
            if (strpos($href, 'http') !== 0){
                $href = rtrim($domain, '/') . '/' . ltrim($href, '/');
            }
    
            // Simple check to only include http/https links
            // More validation may be needed based on requirements
            if (strpos($href, $domain) === 0 && strpos($rel, 'nofollow') === false){
                $urls[] = $href;
            }
        }

        return $urls;
    }
}

// Notify outcomes - Ver 1.6.1
function display_option_value_admin_notice() {
    $kn_results = get_option('chatbot_chatgpt_kn_results');

    // Dismissable notice - Ver 1.6.1
    if ($kn_results) {
        echo '<div class="notice notice-success is-dismissible"><p>Knowledge Navigator Outcome: ' . $kn_results . ' <a href="?page=chatbot-chatgpt&tab=crawler&dismiss_chatgpt_notice=1">Dismiss</a></p></div>';
    }
    
    // Dismissable notice - Ver 1.6.1
    // if ($kn_results) {
    //     echo '<div class="notice notice-success is-dismissible"><p>Knowledge Navigator Outcome: ' . $kn_results . '</p></div>';
    // }

}
add_action('admin_notices', 'display_option_value_admin_notice');

// Handle outcome notification dismissal - Ver 1.6.1
function dismiss_chatgpt_notice() {
    if (isset($_GET['dismiss_chatgpt_notice'])) {
        delete_option('chatbot_chatgpt_kn_results');
    }
}
add_action('admin_init', 'dismiss_chatgpt_notice');


// Handle long running scripts with a schedule devent function - Ver 1.6.1
function crawl_scheduled_event() {

    global $topWords;

    $run_scanner = get_option('chatbot_chatgpt_knowledge_navigator', 'No');

    // The second parameter is the default value if the option is not set.
   update_option('chatbot_chatgpt_kn_status', 'In Process');

    if (!isset($run_scanner)) {
        $run_scanner = 'No';
    }

    // TODO Log the variables to debug.log
    // error_log("ENTERING crawl_scehedule_event_hook");
    update_option('chatbot_chatgpt_crawler_status', 'In Process');

    $result = "";
    // Reset the results message
    update_option('chatbot_chatgpt_kn_results', $result);

    $crawler = new WebCrawler($GLOBALS['start_url']);
    $crawler->crawl(0, $GLOBALS['domain']);

    // Computer the TF-IDF (Term Frequency-Inverse Document Frequency)
    $crawler->computeFrequency();

    // Collect top N words with the highest TF-IDF scores.
    $topWords = [];
    for ($i = 0; $i < $GLOBALS['max_top_words']; $i++) {
        $maxTFIDF = 0;
        $maxWord = null;

        foreach ($crawler->getFrequencyData() as $word => $frequency) {
            $tfidf = $crawler->computeTFIDF($word);

            if ($tfidf > $maxTFIDF) {
                $maxTFIDF = $tfidf;
                $maxWord = $word;
            }
        }

        if ($maxWord !== null) {
            $topWords[$maxWord] = $maxTFIDF;
            $crawler->removeWordFromFrequencyData($maxWord);
        }
    }

    // TODO Diagnostics - Ver 1.6.1
    // var_dump($topWords);

    // Store the results
    output_results($topWords);

    // String together the $topWords
    $chatbot_chatgpt_kn_conversation_context = "This site includes references to and information about the following topics: ";
    foreach ($topWords as $word => $tfidf) {
        $chatbot_chatgpt_kn_conversation_context .= $word . ", ";
        }
    $chatbot_chatgpt_kn_conversation_context .= "and more.";
    
    // Save the results message value into the option
    update_option('chatbot_chatgpt_kn_conversation_context', $chatbot_chatgpt_kn_conversation_context);

    // Save the results message value into the option
    $kn_results = 'Knowledge Navigation completed! Check the results.csv file in the plugin directory.';
    update_option('chatbot_chatgpt_kn_results', $kn_results);

    // Notify outcome for up to 3 minutes
    set_transient('chatbot_chatgpt_kn_results', $kn_results);

    // TODO Log the variables to debug.log
    // error_log("EXITING crawl_scehedule_event_hook");

    // Get the current date and time.
    $date_time_completed = date("Y-m-d H:i:s");

    // Concatenate the status message with the date and time.
    $status_message = 'Completed on ' . $date_time_completed;

    // Update the option with the new status message.
    update_option('chatbot_chatgpt_kn_status', $status_message);

}
add_action('crawl_scheduled_event_hook', 'crawl_scheduled_event');

function chatbot_chatgpt_knowledge_navigator_section_callback($args) {

    // NUCLEAR OPTION - OVERRIDE VALUE TO NO
    // update_option('chatbot_chatgpt_knowledge_navigator', 'No');

    global $topWords;

    $run_scanner = get_option('chatbot_chatgpt_knowledge_navigator', 'No');

    if (!isset($run_scanner)) {
        $run_scanner = 'No';
    }

    if($run_scanner === 'Yes'){

        // Log the variables to debug.log
        // error_log("chatbot_chatgpt_knowledge_navigator_section_callback: " . $run_scanner);
        // error_log("max_top_words: " . serialize($GLOBALS['max_top_words']));
        // error_log("max_depth: " . serialize($GLOBALS['max_depth']));
        // error_log("domain: " . serialize($GLOBALS['domain']));
        // error_log("start_url: " . serialize($GLOBALS['start_url']));

        if (!wp_next_scheduled('crawl_scheduled_event_hook')) {

            // RESET THE NO OF LINKS CRAWLED HERE
            update_option('no_of_links_crawled', 0);
            
            // RESET THE STATUS MESSAGE
            update_option('chatbot_chatgpt_kn_status', 'In Process');

            // TODO Log the variables to debug.log
            // error_log("BEFORE crawl_scehedule_event_hook");

            // WP Cron Scheduler - VER 1.6.1
            // wp_schedule_single_event(time(), 'crawl_scheduled_event_hook');

            // WP Cron Scheduler - VER 1.6.2
            // https://chat.openai.com/share/b1de5d84-966c-4f0f-b24d-329af3e55616
            // $timestamp = time() + 3600; // 1 hour from now
            // $interval = 'daily'; // The recurrence interval (could also be 'hourly', 'twicedaily', etc.)
            // $hook = 'crawl_scheduled_event_hook';
            // $args = array('argument_1', 'argument_2');
            // wp_schedule_event($timestamp, $interval, $hook, $args);

            // WP Cron Scheduler - VER 1.6.2
            // https://chat.openai.com/share/b1de5d84-966c-4f0f-b24d-329af3e55616
            // A standard system cron job runs at specified intervals regardless of the 
            // website's activity or traffic, but WordPress cron jobs are triggered by visits
            //  to your site.
            $timestamp = time(); // run it now
            $interval = 'daily'; // The recurrence interval (could also be 'hourly', 'twicedaily', 'daily' or custom schedule)
            // $interval = 'weekly'; // The custom recurrence interval - see function at bottom
            $hook = 'crawl_scheduled_event_hook';
            wp_schedule_event($timestamp, $interval, $hook);
            
            // TODO Log the variables to debug.log
            // error_log("AFTER crawl_scehedule_event_hook");

            // Reset before reloading the page
            $run_scanner = 'No';
            update_option('chatbot_chatgpt_knowledge_navigator', 'No');  

        }
    }
 
    // DO NOT REMOVE
    ?>

    <div class="wrap">
        <p>Introducing <b>Knowledge Navigator</b> - the smart explorer behind our ChatGPT plugin that's designed to delve into the core of your website. Like a digital archaeologist, it embarks on an all-encompassing journey through your site's pages, carefully following every internal link to get a holistic view of your content. The exciting part? It sifts through each page, extracting the essence of your content in the form of keywords and phrases, gradually building a meticulous, interactive map of your website's architecture. </p>
        <p>What's the outcome? Detailed "results.csv" and "results.json" files are created, tucking away all this valuable information in a dedicated 'results' directory within the plugin's folder. The prime objective of <b>Knowledge Navigator</b> is to enable the ChatGPT plugin to have a crystal clear understanding of your website's context and content. The result? Your chatbot will deliver responses that are not just accurate, but also fittingly contextual, thereby crafting a truly bespoke user experience. This all is powered by the advanced AI technology of OpenAI's Large Language Model (LLM) API.</p>
        <p>And how does the <b>Knowledge Navigator</b> do all this? It employs a clever technique known as TF-IDF (Term Frequency-Inverse Document Frequency) to unearth the keywords that really matter. The keywords are ranked by their TF-IDF scores, where the score represents the keyword's relevance to your site. This score is a fine balance between the term's frequency on your site and its inverse document frequency (which is essentially the log of total instances divided by the number of documents containing the term). In simpler words, it's a sophisticated measure of how special a keyword is to your content.</p>
        <h2>Knowledge Navigator Settings</h2>
        <p><b><i>When you're ready to scan you website, set the 'Run Knowledge Navigator' to 'Yes', then click 'Save Settings'.</i></b></p>
        <p>Runtimes for the <b>Knowledge Navigator</b> can increase exponentially.  It is suggested to start with a maximum depth of 2 and maximum number of top words at 50.</p>
        <div style="background-color: white; border: 1px solid #ccc; padding: 10px; margin: 10px; display: inline-block;">
            <p><b>Knowledge Navigator</b> Status: <?php echo get_option('chatbot_chatgpt_kn_status', 'In Process'); ?> - Links Crawled: <?php echo get_option('no_of_links_crawled', 0); ?></p>
        </div>
        <p>Refresh this page to determine the progress and status of Knowledge Navigation!</p>
    </div>

    <?php
}

function chatbot_chatgpt_knowledge_navigator_callback($args) {
    $chatbot_chatgpt_knowledge_navigator = esc_attr(get_option('chatbot_chatgpt_knowledge_navigator', 'No'));
    ?>
    <select id="chatbot_chatgpt_knowledge_navigator" name="chatbot_chatgpt_knowledge_navigator">
        <option value="No" <?php selected( $chatbot_chatgpt_knowledge_navigator, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
        <option value="Yes" <?php selected( $chatbot_chatgpt_knowledge_navigator, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
    </select>
    <?php
}

function chatbot_chatgpt_kn_maximum_depth_callback($args) {
    $GLOBALS['max_depth'] = intval(get_option('chatbot_chatgpt_kn_maximum_depth', 2));
    ?>
    <select id="chatbot_chatgpt_kn_maximum_depth" name="chatbot_chatgpt_kn_maximum_depth">
        <?php
        for ($i = 1; $i <= 5; $i++) {
            echo '<option value="' . $i . '"' . selected($GLOBALS['max_depth'], $i, false) . '>' . $i . '</option>';
        }
        ?>
    </select>
    <?php
}

function chatbot_chatgpt_kn_maximum_top_words_callback($args) {
    $GLOBALS['max_top_words'] = intval(get_option('chatbot_chatgpt_kn_maximum_top_words', 25));
    ?>
    <select id="chatbot_chatgpt_kn_maximum_top_words" name="chatbot_chatgpt_kn_maximum_top_words">
        <?php
        for ($i = 25; $i <= 500; $i += 25) {
            echo '<option value="' . $i . '"' . selected($GLOBALS['max_top_words'], $i, false) . '>' . $i . '</option>';
        }
        ?>
    </select>
    <?php
}

// Save the results to a file
function output_results(){
    global $topWords;

    // Open file in write mode ('w')
    $f = fopen($GLOBALS['results_csv_file'], 'w');

    // Write headers to CSV file
    fputcsv($f, array('Word', 'TF-IDF'));

    // Loop through $topWords and write each to CSV
    foreach ($topWords as $word => $tfidf) {
        fputcsv($f, array($word, $tfidf));
    }

    // Close the file
    fclose($f);

    // Write JSON to file
    file_put_contents($GLOBALS['results_json_file'], json_encode($topWords));
}

// Custom Schedules - Ver 1.6.2
// https://chat.openai.com/share/b1de5d84-966c-4f0f-b24d-329af3e55616
// A standard system cron job runs at specified intervals regardless of the 
// website's activity or traffic, but WordPress cron jobs are triggered by visits
//  to your site.
function chatbot_chatgpt_custom_cron_schedule($schedules) {
    $schedules['weekly'] = array(
        'interval' => 604800, // Number of seconds in a week
        'display'  => __('Every Week'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'chatbot_chatgpt_custom_cron_schedule');
