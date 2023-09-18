<?php
/**
 * Chatbot ChatGPT for WordPress - Knowledge Navigator - Settings
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * These are all the options for the Knowledge Navigator.
 * 
 *
 * @package chatbot-chatgpt
 */

 // TODO If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

global $topwords, $words, $start_url, $domain, $max_depth, $max_top_words, $chatgpt_diagnostics, $plugin_dir_path, $results_dir_path, $no_of_links_crawled;
$start_url = site_url();
$domain = parse_url($start_url, PHP_URL_HOST);
$max_depth = esc_attr(get_option('chatbot_chatgpt_kn_maximum_depth', 2)); // Default to 2
$max_top_words = esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 25)); // Default to 25
// $no_of_links_crawled = 0; // Default to 0 
// update_option('no_of_links_crawled', $no_of_links_crawled);
 
class WebCrawler {
    private $document;
    private $frequencyData = [];
    private $visitedUrls = [];
    private $pagefrequenceyData = [];
    private $pagetotalWordCount = 0;
    private $totalWordCount = 0;

    public function __construct($url) {

        // TODO COMMENT OUT LATER
        error_log ("FUNCTION - __construct");

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

        // TODO COMMENT OUT LATER
        error_log ("FUNCTION - computeFrequency");

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

        // TODO DANGER DANGER DANGER
        $user_id = 1; // assuming the admin user ID is 1
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );

        $dom = new DOMDocument;
        libxml_use_internal_errors(true); // Suppress HTML parsing errors
        $dom->loadHTML($this->document);
        // error_log('$dom: ' . print_r($dom, true));

        // TODO DANGER DANGER DANGER
        wp_set_current_user(0);  // set the user back to anonymous
        
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

        error_log('$words: ' . print_r($words, true));

        // TODO PAGE WORD COUNTS - XXX
        $this->pagefrequenceyData;
        $this->pagetotalWordCount;
        // TODO PAGE TOP WORD COMPUTER - XXX
    
        // Compute frequencies
        $this->frequencyData = array_merge(array_count_values($words));
        $this->totalWordCount = $this->totalWordCount + count($this->frequencyData);
    }
    
    public function computeTFIDF($term) {

        // TODO COMMENT OUT LATER
        error_log ("FUNCTION - computeTFIDF");

        $tf = $this->frequencyData[$term] / $this->totalWordCount;
        $idf = $this->computeInverseDocumentFrequency($term);

        return $tf * $idf;
    }

    private function computeTermFrequency($term) {

        // TODO COMMENT OUT LATER
        error_log ("FUNCTION - computeTermFrequency");

        return $this->frequencyData[$term] / count($this->frequencyData);
    }

    private function computeInverseDocumentFrequency($term) {

        // TODO COMMENT OUT LATER
        error_log ("FUNCTION - computeInverseDocumentFrequency");

        $numDocumentsWithTerm = 0;
        foreach ($this->frequencyData as $word => $frequency) {
            if ($word === $term) {
                $numDocumentsWithTerm++;
            }
        }
        
        return log(count($this->frequencyData) / ($numDocumentsWithTerm + 1));
    }

    public function getFrequencyData() {

        // TODO COMMENT OUT LATER
        error_log ("FUNCTION - getFrequencyData");

        return $this->frequencyData;
    }

    public function removeWordFromFrequencyData($word) {

        // TODO COMMENT OUT LATER
        error_log ("FUNCTION - removeWordFromFrequencyData");

        unset($this->frequencyData[$word]);
    }

    public function crawl($depth = 0, $domain = '', &$visitedUrls = []) {

        // TODO COMMENT OUT LATER
        error_log ("FUNCTION - crawl");

        // error_log("crawl: top of function");
        if ($depth === 0) {
            $no_of_links_crawled = 0;
            update_option('no_of_links_crawled', $no_of_links_crawled);
        }

        $max_depth = isset($GLOBALS['max_depth']) ? (int) $GLOBALS['max_depth'] : 3;  // default to 3 if not set

        if ($depth > $max_depth) {
            // error_log("crawl: $depth > max_depth");
            return;
        }
    
        try {
            $this->computeFrequency();
    
            $urls = $this->getLinks($domain);

            // TODO COMMENT THIS OUT BEFORE PRODUCTION
            error_log ("DOMAIN: " . $domain);
            error_log("URLS: " . print_r($urls, true));

            foreach ($urls as $url) {

                if (in_array($url, $this->visitedUrls)) {
                    // Skip this URL as it has already been crawled
                    // TODO COMMENT OUT ERROR_LOG
                    error_log ("SKIPPING: " . $url);
                    continue;
                }

                // TODO Log the variables to debug.log
                error_log("CRAWLING: " . $url);

                // The second parameter is the default value if the option is not set.
                $kn_crawler_status = get_option('chatbot_chatgpt_kn_status', 'In Process');

                // Increment the number of links crawled.
                $no_of_links_crawled = get_option('no_of_links_crawled', 0);
                $no_of_links_crawled = (int) $no_of_links_crawled; // Cast to integer
                $no_of_links_crawled += 1;
                update_option('no_of_links_crawled', $no_of_links_crawled);
                // error_log('count' . $no_of_links_crawled);

                // Get the current status without appending the number of links crawled.
                $kn_crawler_status = get_option('chatbot_chatgpt_kn_status', 'In Process');
                update_option('chatbot_chatgpt_kn_status', $kn_crawler_status);

                $start_time = microtime(true);

                // Mark this URL as visited
                $this->visitedUrls[] = $url;

                $crawler = new WebCrawler($url);
                $crawler->crawl($depth + 1, $domain, $visitedUrls);

                // TODO REMOVE BEFORE PRODUCTION
                error_log("CRAWLED: " . $url);
    
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

        // TODO COMMENT OUT LATER
        error_log ("FUNCTION - getLinks");

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
            
            // Skip JavaScript and fragment URLs
            if (strpos($href, '#') !== false || strpos($href, 'javascript:') !== false) {
                continue;
            }
    
            if (strpos($href, 'http') !== 0){
                if (substr($href, 0, 3) === '../') {
                    $href = substr($href, 3);
                }
                $href = rtrim($domain, '/') . '/' . ltrim($href, '/');
            }
    
            if (strpos($href, $domain) === 0 && strpos($rel, 'nofollow') === false){
                $urls[] = $href;
            }
        }
    
        return $urls;
    }

}

// TODO CODE MOVED TO SCHEDULER


function chatbot_chatgpt_knowledge_navigator_section_callback($args) {

    // TODO COMMENT OUT LATER
    error_log ("FUNCTION - chatbot_chatgpt_knowledge_navigator_section_callback");

    // NUCLEAR OPTION - OVERRIDE VALUE TO NO
    // update_option('chatbot_chatgpt_knowledge_navigator', 'No');

    global $topWords;

    // Must be one of: Now, Hourly, Twice Daily, Weekly
    $run_scanner = get_option('chatbot_chatgpt_knowledge_navigator', 'No');

    if (!isset($run_scanner)) {
        $run_scanner = 'No';
    }

    if (in_array($run_scanner, ['Now', 'Hourly', 'Daily', 'Twice Daily', 'Weekly', 'Cancel'])) {

        // TODO Log the variables to debug.log
        error_log("chatbot_chatgpt_knowledge_navigator_section_callback: " . $run_scanner);
        error_log("max_top_words: " . serialize($GLOBALS['max_top_words']));
        error_log("max_depth: " . serialize($GLOBALS['max_depth']));
        error_log("domain: " . serialize($GLOBALS['domain']));
        error_log("start_url: " . serialize($GLOBALS['start_url']));

        // WP Cron Scheduler - VER 1.6.2
        // error_log('BEFORE wp_clear_scheduled_hook');
        wp_clear_scheduled_hook('crawl_scheduled_event_hook'); // Clear before rescheduling
        // error_log('AFTER wp_clear_scheduled_hook');

        if ($run_scanner === 'Cancel') {
            $run_scanner = 'No';
            update_option('chatbot_chatgpt_knowledge_navigator', 'No');
            update_option('chatbot_chatgpt_scan_interval', 'No Schedule');
        } else {
            if (!wp_next_scheduled('crawl_scheduled_event_hook')) {

                // RESET THE NO OF LINKS CRAWLED HERE
                update_option('no_of_links_crawled', 0);
                
                // RESET THE STATUS MESSAGE
                update_option('chatbot_chatgpt_kn_status', 'In Process');

                // Log action to debug.log
                // error_log("BEFORE crawl_scehedule_event_hook");

                // TODO WP Cron Scheduler - VER 1.6.2
                // https://chat.openai.com/share/b1de5d84-966c-4f0f-b24d-329af3e55616
                // A standard system cron job runs at specified intervals regardless of the 
                // website's activity or traffic, but WordPress cron jobs are triggered by visits
                // to your site.
                // https://wpshout.com/wp_schedule_event-examples/
                // wp_schedule_single_event(time(), 'crawl_scheduled_event_hook');

                // TODO
                // TODO Change the name from crawl_scheduled_event_hook to knowledge_navigator_scan_event and knowledge_navigator_scan_event_hook
                // TODO

                $interval_mapping = [
                    'Now' => 10, // For immediate execution, just delay by 10 seconds
                    'Hourly' => 'hourly',
                    'Twice Daily' => 'twicedaily',
                    'Daily' => 'daily',
                    'Weekly' => 'weekly' // assuming you've defined a custom 'weekly' schedule
                ];

                if (in_array($run_scanner, array_keys($interval_mapping))) {
                    $timestamp = time() + 10; // Always run 10 seconds from now
                    $interval = $interval_mapping[$run_scanner];
                    $hook = 'crawl_scheduled_event_hook';
                    if ($run_scanner === 'Now') {
                        wp_schedule_single_event($timestamp, $hook); // Schedule a one-time event if 'Now' is selected
                    } else {
                        wp_schedule_event($timestamp, $interval, $hook); // Schedule a recurring event for other intervals
                    }
                }
                
                // TODO Log action to debug.log
                // error_log("AFTER crawl_scehedule_event_hook");

                // Log scan interval - Ver 1.6.3
                if ($interval === 'Now') {
                    update_option('chatbot_chatgpt_scan_interval', 'No Schedule');
                } else {
                    update_option('chatbot_chatgpt_scan_interval', $run_scanner);
                }

                // Reset before reloading the page
                $run_scanner = 'No';
                update_option('chatbot_chatgpt_knowledge_navigator', 'No');
            }
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
            <p><b>Knowledge Navigator</b></p>
            <p><b>Schedule: </b><?php echo esc_attr(get_option('chatbot_chatgpt_scan_interval', 'No Schedule')); ?></p>
            <p><b>Status: </b><?php echo esc_attr(get_option('chatbot_chatgpt_kn_status', 'In Process')); ?> - Links Crawled: <?php echo esc_attr(get_option('no_of_links_crawled', 0)); ?></p>
        </div>
        <p>Refresh this page to determine the progress and status of Knowledge Navigation!</p>
    </div>

    <?php
}

// Select Frequency of Crawl - Ver 1.6.2
function chatbot_chatgpt_knowledge_navigator_callback($args) {
    $chatbot_chatgpt_knowledge_navigator = esc_attr(get_option('chatbot_chatgpt_knowledge_navigator', 'No'));
    ?>
    <select id="chatbot_chatgpt_knowledge_navigator" name="chatbot_chatgpt_knowledge_navigator">
        <option value="No" <?php selected($chatbot_chatgpt_knowledge_navigator, 'No'); ?>><?php echo esc_html('No'); ?></option>
        <option value="Now" <?php selected($chatbot_chatgpt_knowledge_navigator, 'Now'); ?>><?php echo esc_html('Now'); ?></option>
        <option value="Hourly" <?php selected($chatbot_chatgpt_knowledge_navigator, 'Hourly'); ?>><?php echo esc_html('Hourly'); ?></option>
        <option value="Twice Daily" <?php selected($chatbot_chatgpt_knowledge_navigator, 'Twice Daily'); ?>><?php echo esc_html('Twice Daily'); ?></option>
        <option value="Daily" <?php selected($chatbot_chatgpt_knowledge_navigator, 'Daily'); ?>><?php echo esc_html('Daily'); ?></option>
        <option value="Weekly" <?php selected($chatbot_chatgpt_knowledge_navigator, 'Weekly'); ?>><?php echo esc_html('Weekly'); ?></option>
        <option value="Cancel" <?php selected($chatbot_chatgpt_knowledge_navigator, 'Cancel'); ?>><?php echo esc_html('Cancel'); ?></option>
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

    // TODO COMMENT OUT LATER
    error_log ("FUNCTION - chatbot_chatgpt_kn_maximum_top_words_callback");

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
