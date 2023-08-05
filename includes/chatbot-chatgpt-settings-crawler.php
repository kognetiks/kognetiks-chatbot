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
 
global $start_url, $domain, $visited, $keywords, $max_depth, $batch_size, $start_batch;
$start_url = site_url();
$domain = parse_url($start_url, PHP_URL_HOST);
$visited = array();
$keywords = array();
$max_depth = 2; // Reduce the depth to 2
$batch_size = 500; // Set a batch size
$start_batch = get_option('start_batch', 0); // Get the starting point of the batch

function chatbot_chatgpt_knowledge_navigator_section_callback($args) {

    // NUCLEAR OPTION - OVERRIDE VALUE TO NO
    // update_option('chatbot_chatgpt_knowledge_navigator', 'No');

    global $crawl_logs;

    $run_scanner = get_option('chatbot_chatgpt_knowledge_navigator', 'No');

    if (!isset($run_scanner)) {
        $run_scanner = 'No';
    }

    if (isset($run_scanner)) {
        echo "<p>\$run_scanner: " . $run_scanner . "</p>";
    } else {
        echo "<p>\$run_scanner: NOT SET</p>";
    }

    if($run_scanner === 'Yes'){
        // Run the crawl function and store the result
        crawl($GLOBALS['start_url']);
        output_results();
        $result = 'Knowledge navigation completed! Check the results.csv file in the plugin directory.';
        // print crawl logs
        foreach ($crawl_logs as $log) {
            echo "<p>" . $log . "</p>";
        }
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

function is_subdomain_of($domain, $url){
    $parsed = parse_url($url);
    return $parsed['host'] == $domain || substr($parsed['host'], -strlen($domain)) === $domain;
}

function extract_links($body, $base_url){
    $dom = new DOMDocument;
    @$dom->loadHTML($body);
    $links = array();
    $a_tags = $dom->getElementsByTagName('a');
    foreach($a_tags as $a){
        $url = $a->getAttribute('href');
        if(!parse_url($url, PHP_URL_HOST)){
            $url = $base_url . $url;
        }
        if(is_subdomain_of($GLOBALS['domain'], $url)){
            $links[] = $url;
        }
    }
    return $links;
}

function extract_keywords($body, $url){
    $dom = new DOMDocument;
    @$dom->loadHTML($body);
    $text = $dom->documentElement->textContent;
    $words = preg_split('/\W+/', $text);
    foreach($words as $word){

        if (!isset($GLOBALS['keywords'][$word])) {
            $GLOBALS['keywords'][$word] = array(
                'count' => 0, 
                'urls' => array()
            );
        }

        // if (!isset($GLOBALS['keywords'][$word]) || !is_array($GLOBALS['keywords'][$word])) {
        //     $GLOBALS['keywords'][$word] = array();
        // }

        $GLOBALS['keywords'][$word]['count'] += 1;
        $GLOBALS['keywords'][$word]['urls'][] = $url;
    }
    preg_match_all('/"([^"]*)"/', $text, $matches);

    foreach($matches[1] as $phrase){

        if (!isset($GLOBALS['keywords'][$phrase])) {
            $GLOBALS['keywords'][$phrase] = array(
              'count' => 0,
              'urls' => array() 
            );
        }

        // if (!isset($GLOBALS['keywords'][$phrase]) || !is_array($GLOBALS['keywords'][$phrase])) {
        //     $GLOBALS['keywords'][$phrase] = array(); 
        // }    

        $GLOBALS['keywords'][$phrase]['count'] += 1;
        $GLOBALS['keywords'][$phrase]['urls'][] = $url;
    }
    // Maintain only top 100 keywords/phrases based on the count
    uasort($GLOBALS['keywords'], function($a, $b) {
        return $b['count'] <=> $a['count'];
    });
    $GLOBALS['keywords'] = array_slice($GLOBALS['keywords'], 0, 100, true);
}


// Adjust the output_results function to handle the new array structure
function output_results(){
    $f = fopen($GLOBALS['results_csv_file'], 'w');
    fputcsv($f, array('Keyword', 'Count', 'URL 1', 'URL 2', 'URL 3'));
    foreach($GLOBALS['keywords'] as $kw => $data){
      $url_columns = array_slice($data['urls'], 0, 3);   
      $count = $data['count'];
      fputcsv($f, array_merge(array($kw, $count), $url_columns));
    }
    fclose($f);
    file_put_contents($GLOBALS['results_json_file'], json_encode($GLOBALS['keywords'])); 
  }

function crawl($url, $depth=0){
    global $crawl_logs, $visited, $batch_size, $start_batch;
    if(in_array($url, $visited) || $depth > $GLOBALS['max_depth']){
        return;
    }
    if(count($visited) >= ($start_batch + $batch_size)){
        // If we've reached the batch size, stop the crawl
        update_option('start_batch', $start_batch + $batch_size); // Update the start batch
        return;
    }
    try{
        $response = wp_remote_get($url);
        if(wp_remote_retrieve_response_code($response) == 200){
            $GLOBALS['visited'][] = $url;
            $body = wp_remote_retrieve_body($response);
            extract_keywords($body, $url);
            $crawl_logs[] = "Crawled URL: " . $url;
            echo "<p>" . $url . "</p>";
            foreach(extract_links($body, $url) as $link){
                if(!in_array($link, $visited)){
                    crawl($link, $depth + 1);
                }
            }
        }
    }catch(Exception $e){
        return;
    }
}

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

