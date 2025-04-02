<?php
/**
 * Kognetiks Chatbot for WordPress - Search - Ver 2.2.4
 *
 * This file contains the code for implementing pre-processor before engaging with an LLM.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Handle the assistant search request
function chatbot_chatgpt_content_search($search_prompt) {

    // DIAG - Diagnostic - Ver 2.2.9
    back_trace('NOTICE', 'chatbot_chatgpt_content_search');
    back_trace('NOTICE', '====== SEARCH REQUEST RECEIVED ======');
    back_trace('NOTICE', 'Request parameters: ' . $search_prompt);

    global $wpdb;

    // Let's find the object of the $search_prompt
    $object = get_object_of_search_prompt($search_prompt);
    back_trace('NOTICE', 'Object: ' . $object);

    // Settings
    $include_excerpt = true;
    $page = 1;
    $per_page = 5;
    $offset = ($page - 1) * $per_page;

    // Get post types to search
    $post_types = get_searchable_post_types();

    // DIAG - Diagnostic - Ver 2.2.9
    back_trace('NOTICE', 'Searchable post types: ' . implode(', ', $post_types));

    // Clean and prepare search terms from the object instead of original prompt
    $search_terms = prepare_search_terms($object);

    // Is $search_terms an array?
    if (!is_array($search_terms)) {
        back_trace('ERROR', 'Search terms are not an array');
    } else {
        back_trace('NOTICE', 'Search terms are an array');
    }

    // DIAG - Diagnostic - Ver 2.2.9
    back_trace('NOTICE', 'Prepared search terms: ' . implode(', ', $search_terms));

    // How many search terms?
    $num_terms = count($search_terms);
    back_trace('NOTICE', 'Number of search terms: ' . $num_terms);

    // Build search conditions
    $search_conditions = [];
    $placeholders = [];

    // Add search conditions for each term
    foreach ($search_terms as $term) {
        $like_term = '%' . $wpdb->esc_like($term) . '%';
        $search_conditions[] = "(post_title LIKE %s OR post_content LIKE %s)";
        $placeholders[] = $like_term;  // For post_title
        $placeholders[] = $like_term;  // For post_content
    }

    // If no search conditions, return empty result
    if (empty($search_conditions)) {
        return [
            'success' => true,
            'total_posts' => 0,
            'total_pages' => 0,
            'current_page' => $page,
            'results' => [],
            'message' => 'No valid search terms.'
        ];
    }

    back_trace('NOTICE', '===========================');
    back_trace('NOTICE', 'Build the main query - AND');
    back_trace('NOTICE', '===========================');

    // Escape and build IN clause for post types
    $in_clause = implode(',', array_map(fn($type) => "'" . esc_sql($type) . "'", $post_types));

    // Build the main query - Try first with AND
    $query = "
        SELECT ID, post_title, post_content, post_excerpt, post_author, post_date, guid
        FROM {$wpdb->posts} 
        WHERE post_type IN ($in_clause)
        AND post_status = 'publish'
        AND (". implode(' AND ', $search_conditions) .")
        ORDER BY post_date DESC
        LIMIT %d OFFSET %d
    ";

    // Add the LIMIT parameters
    $placeholders[] = $per_page;
    $placeholders[] = $offset;

    // DIAG - Diagnostic - Ver 2.2.9
    // back_trace('NOTICE', 'Query template: ' . $query);
    back_trace('NOTICE', 'Placeholders: ' . print_r($placeholders, true));

    try {
        // Prepare and execute the query
        $prepared_query = $wpdb->prepare($query, ...$placeholders);
        // DIAG - Diagnostic - Ver 2.2.9
        // back_trace('NOTICE', 'RAW SQL (DEV ONLY): ' . $prepared_query);
        
        $results = $wpdb->get_results($prepared_query);
        
        if ($wpdb->last_error) {
            back_trace('ERROR', 'Database error: ' . $wpdb->last_error);
        }   
        
    } catch (Exception $e) {
        // DIAG - Diagnostic - Ver 2.2.9
        back_trace('ERROR', 'Exception in query preparation: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Search query failed.',
            'error' => $e->getMessage()
        ];

    }

    // DIAG - Diagnostic - Ver 2.2.9
    back_trace('NOTICE', 'Actual result count: ' . count($results));

    if (!$results) {
        // DIAG - Diagnostic - Ver 2.2.9
        back_trace('NOTICE', 'No results found or query error');
    } else {
        $formatted_results = [];
        foreach ($results as $post) {
            $formatted_results[] = [
                'ID' => $post->ID,
                'title' => $post->post_title,
                'url' => $post->guid,
                'date' => $post->post_date,
                'author' => get_the_author_meta('display_name', $post->post_author),
                'excerpt' => $include_excerpt ? strip_tags($post->post_content) : null
            ];
            // DIAG - Diagnostic - Ver 2.2.9
            back_trace('NOTICE', 'URL: ' . $post->guid );
            back_trace('NOTICE', ' - Title: ' . $post->post_title);
        }

        return [
            'success' => true,
            'total_posts' => count($formatted_results),
            'total_pages' => ceil(count($formatted_results) / $per_page),
            'current_page' => $page,
            'results' => $formatted_results
        ];
    }

    back_trace('NOTICE', '===========================');
    back_trace('NOTICE', 'Build the main query - OR');
    back_trace('NOTICE', '===========================');

    // Escape and build IN clause for post types
    $in_clause = implode(',', array_map(fn($type) => "'" . esc_sql($type) . "'", $post_types));

    // Build the main query - Try first with AND
    $query = "
        SELECT ID, post_title, post_content, post_excerpt, post_author, post_date, guid
        FROM {$wpdb->posts} 
        WHERE post_type IN ($in_clause)
        AND post_status = 'publish'
        AND (". implode(' OR ', $search_conditions) .")
        ORDER BY post_date DESC
        LIMIT %d OFFSET %d
    ";

    // Add the LIMIT parameters
    $placeholders[] = $per_page;
    $placeholders[] = $offset;

    // DIAG - Diagnostic - Ver 2.2.9
    // back_trace('NOTICE', 'Query template: ' . $query);
    back_trace('NOTICE', 'Placeholders: ' . print_r($placeholders, true));

    try {
        // Prepare and execute the query
        $prepared_query = $wpdb->prepare($query, ...$placeholders);
        // DIAG - Diagnostic - Ver 2.2.9
        // back_trace('NOTICE', 'RAW SQL (DEV ONLY): ' . $prepared_query);
        
        $results = $wpdb->get_results($prepared_query);
        
        if ($wpdb->last_error) {
            back_trace('ERROR', 'Database error: ' . $wpdb->last_error);
        }   
        
    } catch (Exception $e) {
        // DIAG - Diagnostic - Ver 2.2.9
        back_trace('ERROR', 'Exception in query preparation: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Search query failed.',
            'error' => $e->getMessage()
        ];

    }

    // DIAG - Diagnostic - Ver 2.2.9
    back_trace('NOTICE', 'Actual result count: ' . count($results));

    if (!$results) {
        // DIAG - Diagnostic - Ver 2.2.9
        back_trace('NOTICE', 'No results found or query error');
    } else {
        $formatted_results = [];
        foreach ($results as $post) {
            $formatted_results[] = [
                'ID' => $post->ID,
                'title' => $post->post_title,
                'url' => $post->guid,
                'date' => $post->post_date,
                'author' => get_the_author_meta('display_name', $post->post_author),
                'excerpt' => $include_excerpt ? strip_tags($post->post_content) : null
            ];
            // DIAG - Diagnostic - Ver 2.2.9
            back_trace('NOTICE', 'URL: ' . $post->guid );
            back_trace('NOTICE', ' - Title: ' . $post->post_title);
        }

        return [
            'success' => true,
            'total_posts' => count($formatted_results),
            'total_pages' => ceil(count($formatted_results) / $per_page),
            'current_page' => $page,
            'results' => $formatted_results
        ];
    }

    // ===================================================================================
    // No results found
    // ===================================================================================

    return [
        'success' => true,
        'total_posts' => 0,
        'total_pages' => 0,
        'current_page' => $page,
        'results' => [],
        'message' => 'No results found.'
    ];

}

// Helper function to get searchable post types
function get_searchable_post_types() {

    global $wpdb;

    $registered_types = get_post_types(['public' => true], 'objects');
    $post_types = [];

    // First, handle registered post types
    foreach ($registered_types as $type) {
        $plural_type = $type->name === 'reference' ? 'references' : $type->name . 's';
        $option_name = 'chatbot_chatgpt_kn_include_' . $plural_type;
        if (esc_attr(get_option($option_name, 'No')) === 'Yes') {
            $post_types[] = $type->name;
        }
    }

    // Add any extra post types from DB
    $db_post_types = $wpdb->get_col("SELECT DISTINCT post_type FROM {$wpdb->posts}");
    foreach ($db_post_types as $type) {
        if (!in_array($type, $post_types)) {
            $plural_type = $type === 'reference' ? 'references' : $type . 's';
            $option_name = 'chatbot_chatgpt_kn_include_' . $plural_type;
            if (esc_attr(get_option($option_name, 'No')) === 'Yes') {
                $post_types[] = $type;
            }
        }
    }

    // DIAG - Diagnostic - Ver 2.2.9
    back_trace('NOTICE', 'Searchable post types: ' . implode(', ', $post_types));

    return $post_types;
    
}

// Helper function to prepare search terms
function prepare_search_terms($search_prompt) {

    global $stopWords;
    
    // Ensure globals are loaded
    if (!isset($stopWords)) {
        // Load the globals if not already loaded
        require_once(plugin_dir_path(__FILE__) . '../chatbot-globals.php');
    }
    
    // Convert to lowercase
    $search_prompt = strtolower($search_prompt);
    
    // Remove punctuation but keep spaces
    $search_prompt = preg_replace('/[^\w\s]/', ' ', $search_prompt);
    
    // Split into words
    $words = preg_split('/\s+/', trim($search_prompt));
    
    // Remove stop words and empty strings
    $words = array_filter($words, function($word) use ($stopWords) {
        return !empty($word) && !in_array($word, $stopWords) && strlen($word) > 2;
    });
    
    // Convert filtered words to terms
    $terms = array_values(array_unique($words));
    
    // DIAG - Diagnostic - Ver 2.2.9
    back_trace('NOTICE', 'Final search terms: ' . implode(', ', $terms));
    
    return $terms;
}

// Helper function to get the object of a search prompt
function get_object_of_search_prompt($search_prompt) {
    global $stopWords;
    
    // Ensure globals are loaded
    if (!isset($stopWords)) {
        // Load the globals if not already loaded
        require_once(plugin_dir_path(__FILE__) . '../chatbot-globals.php');
    }
    
    // Convert to lowercase for consistent matching
    $prompt = strtolower(trim($search_prompt));
    
    // Common question words and their typical positions relative to the object
    $question_markers = [
        'what' => 'after',
        'where' => 'after',
        'who' => 'after',
        'when' => 'after',
        'how' => 'after',
        'why' => 'after',
        'which' => 'after',
        'can you' => 'after',
        'could you' => 'after',
        'tell me about' => 'after',
        'find' => 'after',
        'search for' => 'after',
        'look for' => 'after',
        'show me' => 'after',
        'get' => 'after',
        'is there' => 'after'
    ];

    // Common prepositions that might come before the object
    $prepositions = [
        'about',
        'for',
        'to',
        'regarding',
        'concerning',
        'on',
        'in',
        'at',
        'by'
    ];

    // Common verbs to filter out that aren't part of the search object
    $filter_verbs = [
        'know',
        'tell',
        'want',
        'need',
        'like',
        'think',
        'believe',
        'understand',
        'explain',
        'describe',
        'say',
        'mean',
        'help',
        'see',
        'find',
        'search',
        'look',
        'give',
        'show'
    ];

    // First, try to identify if this is a question or command
    $object = '';
    foreach ($question_markers as $marker => $position) {
        if (strpos($prompt, $marker) === 0) {
            // Split the prompt into words
            $words = explode(' ', $prompt);
            
            // Remove the question word
            $marker_words = explode(' ', $marker);
            $words = array_slice($words, count($marker_words));
            
            // Remove prepositions and filter verbs
            $words = array_filter($words, function($word) use ($prepositions, $filter_verbs) {
                return !in_array($word, $prepositions) && !in_array($word, $filter_verbs);
            });
            
            // Rejoin the remaining words
            $object = implode(' ', $words);
            break;
        }
    }

    // If no question structure was found, try to find noun phrases
    if (empty($object)) {
        // Remove punctuation
        $prompt = preg_replace('/[^\w\s]/', ' ', $prompt);
        
        // Split into words
        $words = explode(' ', $prompt);
        
        // Remove common stop words, prepositions, and filter verbs
        $words = array_filter($words, function($word) use ($stopWords, $prepositions, $filter_verbs) {
            return !in_array($word, $stopWords) && 
                   !in_array($word, $prepositions) && 
                   !in_array($word, $filter_verbs);
        });
        
        // Take the remaining words as the object
        $object = implode(' ', $words);
    }

    // Clean up any extra whitespace
    $object = trim(preg_replace('/\s+/', ' ', $object));

    // If still empty, return the original prompt minus punctuation
    if (empty($object)) {
        $object = trim(preg_replace('/[^\w\s]/', ' ', $prompt));
    }

    // DIAG - Diagnostic
    back_trace('NOTICE', 'Original prompt: ' . $prompt);
    back_trace('NOTICE', 'Extracted object: ' . $object);

    return $object;
}

