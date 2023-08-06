<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Crawler aka Knowledge Navigator(TM)
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */
 
 global $start_url, $domain, $max_depth, $max_top_words, $results_csv_file, $results_json_file, $chatgpt_diagnostics, $plugin_dir_path, $results_dir_path;
 $start_url = site_url();
 $domain = parse_url($start_url, PHP_URL_HOST);
 $max_depth = esc_attr(get_option('chatbot_chatgpt_kn_maximum_depth', 2)); // Default to 2
 $max_top_words = esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 10)); // Default to 10
 
 
 // Diagnostics = Ver 1.4.2
 $chatgpt_diagnostics = esc_attr(get_option('chatgpt_diagnostics', 'Off'));
 
 // Get the absolute path to the plugin directory
 $plugin_dir_path = plugin_dir_path(__FILE__);
 
 // Create a "results" subdirectory if it doesn't exist
 $results_dir_path = $plugin_dir_path . 'results/';
 
 if (!file_exists($results_dir_path)) {
     mkdir($results_dir_path, 0755, true);
 }
 
 // Specify the output files' paths
 $results_csv_file = $results_dir_path . 'results.csv';
 $results_json_file = $results_dir_path . 'results.json';

 function validateUrl($url) {
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }

    return filter_var($url, FILTER_VALIDATE_URL);
}

class WebCrawler {
    private $document;
    private $frequencyData = [];
    
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
        $words = str_word_count(strtolower($this->document), 1);
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
        if ($depth > $GLOBALS['max_depth']) {
            return;
        }
    
        try {
            $this->computeFrequency();
    
            $urls = $this->getLinks($domain);
            foreach ($urls as $url) {
                $crawler = new WebCrawler($url);
                $crawler->crawl($depth + 1, $domain);
            }
        } catch (Exception $e) {
            // Log the exception and continue with the next URL
            error_log("Crawl failed: " . $e->getMessage());
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

function chatbot_chatgpt_knowledge_navigator_section_callback($args) {

    // NUCLEAR OPTION - OVERRIDE VALUE TO NO
    // update_option('chatbot_chatgpt_knowledge_navigator', 'No');

    global $topWords;

    $run_scanner = get_option('chatbot_chatgpt_knowledge_navigator', 'No');

    if (!isset($run_scanner)) {
        $run_scanner = 'No';
    }

    if($run_scanner === 'Yes'){
        // Run the crawl function
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
    
        var_dump($topWords);
    
        // Store the results
        output_results($topWords);
        $result = 'Knowledge Navigation completed! Check the results.csv file in the plugin directory.';
        // Reset before reloading the page
        $run_scanner = 'No';
        update_option('chatbot_chatgpt_knowledge_navigator', 'No');

    }
 
    // DO NOT REMOVE
    ?>

    <div class="wrap">
        <h1>Knowledge Navigator&trade;</h1>
        <p>The <b>Knowledge Navigator&trade;</b> is an innovative component of our ChatGPT plugin designed to perform an in-depth analysis of your website. It initiates a comprehensive crawl through your website's pages, following internal links to thoroughly explore the depth and breadth of your site's content. As it navigates your site, it meticulously extracts keywords and phrases from each page, aggregating them into a detailed map of your site's information architecture. This data is then output to "results.csv" and "results.json" files, stored in a dedicated 'results' directory within the plugin's folder. The goal of the <b>Knowledge Navigator&trade;</b> is to help the chatbot plugin understand the content and structure of your website better, which in turn allows it to provide more accurate and contextually relevant responses to user inquiries. Ultimately, this empowers you to offer a more interactive and tailored user experience, fueled by the sophisticated AI capabilities of OpenAI's Large Language Model (LLM) API.</p>
        <!-- <p>If you're ready, click '<b>Run Scanner</b>' to start the knowledge navigation of your site.</p> -->
        <p>This may take a few minutes, when the process is complete you'll a confirmation message.</p>
        <p><b><i>When you're ready to scan you website, set the 'Run Knowledge Navigator&trade;' to 'Yes', then click 'Save Settings'</i></b></p>
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
    $GLOBALS['max_top_words'] = intval(get_option('chatbot_chatgpt_kn_maximum_top_words', 10));
    ?>
    <select id="chatbot_chatgpt_kn_maximum_top_words" name="chatbot_chatgpt_kn_maximum_top_words">
        <?php
        for ($i = 10; $i <= 100; $i += 10) {
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
