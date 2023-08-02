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
 
// require 'vendor/autoload.php';
// use GuzzleHttp\Client;
// use Symfony\Component\DomCrawler\Crawler;

$start_url = site_url();
$domain = parse_url($start_url, PHP_URL_HOST);
$visited = array();
$keywords = array();
$max_depth = 4;

 // Outputs the HTML for the settings page
 function chatbot_chatgpt_crawler_callback($args) {
    global $crawl_logs;
    if(isset($_POST['run-scanner'])){
        // Run the crawl function and store the result
        crawl($GLOBALS['start_url']);
        output_results();
        $result = 'Knowledge navigation completed! Check the results.csv file in the plugin directory.';
        // print crawl logs
        foreach ($crawl_logs as $log) {
            echo "<p>" . $log . "</p>";
        }
    }
    ?>

    <div class="wrap">
        <h1>Knowledge Navigator&trade;</h1>
        <p>The <b>Knowledge Navigator&trade;</b> is an innovative component of our ChatGPT plugin designed to perform an in-depth analysis of your website. It initiates a comprehensive crawl through your website's pages, following internal links to thoroughly explore the depth and breadth of your site's content. As it navigates your site, it meticulously extracts keywords and phrases from each page, aggregating them into a detailed map of your site's information architecture. This data is then output to "results.csv" and "results.json" files, stored in a dedicated 'results' directory within the plugin's folder. The goal of the <b>Knowledge Navigator&trade;</b> is to help the chatbot plugin understand the content and structure of your website better, which in turn allows it to provide more accurate and contextually relevant responses to user inquiries. Ultimately, this empowers you to offer a more interactive and tailored user experience, fueled by the sophisticated AI capabilities of OpenAI's Large Language Model (LLM) API.</p>
        <p>If you're ready, click '<b>Run Scanner</b>' to start the knowledge navigation of your site.</p>
        <p>This may take a few minutes, when the process is complete you'll a confirmation message.</p>
        <form method="post">
            <input type="submit" name="run-scanner" class="button button-primary" value="Run Scanner" />
        </form>
        <?php if (isset($result)): ?>
            <p><?php echo $result; ?></p>
        <?php endif; ?>
    </div>

    <!-- Add CSS to hide the "Save Settings" button -->
    <style type="text/css">
        input[type="submit"][name="submit"] {
            display: none;
        }
    </style>

    <?php
}

function is_subdomain_of($domain, $url){
    $parsed = parse_url($url);
    return $parsed['host'] == $domain || substr($parsed['host'], -strlen($domain)) === $domain;
}

function extract_links($body, $base_url){
    $crawler = new Crawler($body);
    $links = array();
    $crawler->filter('a')->each(function (Crawler $node) use (&$links, $base_url){
        $url = $node->attr('href');
        if(!parse_url($url, PHP_URL_HOST)){
            $url = $base_url . $url;
        }
        if(is_subdomain_of($GLOBALS['domain'], $url)){
            $links[] = $url;
        }
    });
    return $links;
}

function extract_keywords($body, $url){
    $crawler = new Crawler($body);
    $text = $crawler->filter('body')->text();
    $words = preg_split('/\W+/', $text);
    foreach($words as $word){
        $GLOBALS['keywords'][$word][] = $url;
    }
    preg_match_all('/"([^"]*)"/', $text, $matches);
    foreach($matches[1] as $phrase){
        $GLOBALS['keywords'][$phrase][] = $url;
    }
}

function crawl($url, $depth=0){
    global $crawl_logs;
    if(in_array($url, $GLOBALS['visited']) || $depth > $GLOBALS['max_depth']){
        return;
    }
    try{
        $client = new Client();
        $res = $client->request('GET', $url);
        if($res->getStatusCode() == 200){
            $GLOBALS['visited'][] = $url;
            extract_keywords($res->getBody(), $url);
            $crawl_logs[] = "Crawled URL: " . $url;
            foreach(extract_links($res->getBody(), $url) as $link){
                if(!in_array($link, $GLOBALS['visited'])){
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

function output_results(){
    $f = fopen($GLOBALS['results_csv_file'], 'w');
    fputcsv($f, array('Keyword', 'Count', 'URL 1', 'URL 2', 'URL 3'));
    foreach($GLOBALS['keywords'] as $kw => $urls){
        $url_columns = array_slice($urls, 0, 3);
        $count = count($urls);
        fputcsv($f, array_merge(array($kw, $count), $url_columns));
    }
    fclose($f);
    file_put_contents($GLOBALS['results_json_file'], json_encode($GLOBALS['keywords']));
}
