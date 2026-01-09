<?php
/**
 * Kognetiks Chatbot for WordPress - Search - Ver 2.2.4 - Updated in Ver 2.2.9
 *
 * This file contains the code for implementing pre-processor before engaging with an LLM.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

function chatbot_chatgpt_content_search($search_prompt) {

    // DIAG - Diagnostics - Ver 2.2.9

    global $wpdb;

    $object = chatbot_chatgpt_get_object_of_search_prompt($search_prompt);

    $include_excerpt = true;
    $page = 1;
    $per_page = 5;
    $offset = ($page - 1) * $per_page;

    $post_types = chatbot_chatgpt_get_searchable_post_types();
    $search_terms = chatbot_chatgpt_prepare_search_terms($object);

    if (!is_array($search_terms) || empty($search_terms)) {
        return [
            'success' => true,
            'total_posts' => 0,
            'total_pages' => 0,
            'current_page' => $page,
            'results' => [],
            'message' => 'No valid search terms.'
        ];
    }

    // DIAG - Diagnostics - Ver 2.2.9

    $search_conditions = [];
    $search_values = [];

    foreach ($search_terms as $term) {
        $like_term = '%' . $wpdb->esc_like($term) . '%';
        $search_conditions[] = "(post_title LIKE %s OR post_content LIKE %s)";
        $search_values[] = $like_term;
        $search_values[] = $like_term;
    }

    $in_clause = implode(',', array_map(fn($type) => "'" . esc_sql($type) . "'", $post_types));

    // DIAG - Diagnostics - Ver 2.2.9

    // === TRY: AND query ===
    $and_query = "
        SELECT ID, post_title, post_content, post_excerpt, post_author, post_date, guid
        FROM {$wpdb->posts}
        WHERE post_type IN ($in_clause)
        AND post_status = 'publish'
        AND (" . implode(' AND ', $search_conditions) . ")
        ORDER BY post_date DESC
        LIMIT %d OFFSET %d
    ";

    $and_placeholders = array_merge($search_values, [$per_page, $offset]);

    try {
        $prepared_query = $wpdb->prepare($and_query, ...$and_placeholders);
        $results = $wpdb->get_results($prepared_query);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Search query failed.',
            'error' => $e->getMessage()
        ];
    }

    // DIAG - Diagnostics - Ver 2.2.9

    if (!empty($results)) {
        return chatbot_chatgpt_format_search_results($results, $include_excerpt, $page, $per_page);
    }

    // DIAG - Diagnostics - Ver 2.2.9

    // === FALLBACK: OR query ===
    $or_query = "
        SELECT ID, post_title, post_content, post_excerpt, post_author, post_date, guid
        FROM {$wpdb->posts}
        WHERE post_type IN ($in_clause)
        AND post_status = 'publish'
        AND (" . implode(' OR ', $search_conditions) . ")
        ORDER BY post_date DESC
        LIMIT %d OFFSET %d
    ";

    $or_placeholders = array_merge($search_values, [$per_page, $offset]);

    // DIAG - Diagnostics - Ver 2.2.9

    try {
        $prepared_query = $wpdb->prepare($or_query, ...$or_placeholders);
        $results = $wpdb->get_results($prepared_query);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Search query failed.',
            'error' => $e->getMessage()
        ];
    }

    // DIAG - Diagnostics - Ver 2.2.9

    if (!empty($results)) {
        return chatbot_chatgpt_format_search_results($results, $include_excerpt, $page, $per_page);
    }

    return [
        'success' => true,
        'total_posts' => 0,
        'total_pages' => 0,
        'current_page' => $page,
        'results' => [],
        'message' => 'No results found.'
    ];

}

function chatbot_chatgpt_format_search_results($results, $include_excerpt, $page, $per_page) {

    // DIAG - Diagnostics - Ver 2.2.9

    $formatted_results = array_map(function ($post) use ($include_excerpt) {
        return [
            'ID' => $post->ID,
            'title' => $post->post_title,
            'url' => $post->guid,
            'date' => $post->post_date,
            'author' => get_the_author_meta('display_name', $post->post_author),
            'excerpt' => $include_excerpt ? strip_tags($post->post_content) : null
        ];
    }, $results);

    return [
        'success' => true,
        'total_posts' => count($formatted_results),
        'total_pages' => ceil(count($formatted_results) / $per_page),
        'current_page' => $page,
        'results' => $formatted_results
    ];

}

// Helper function to get searchable post types
function chatbot_chatgpt_get_searchable_post_types() {

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

    // FALLBACK: If no post types are selected, include basic types to prevent SQL error
    if (empty($post_types)) {
        $post_types = ['post', 'page']; // Default to posts and pages
    }

    // DIAG - Diagnostic - Ver 2.2.9

    return $post_types;
    
}

// Helper function to prepare search terms
function chatbot_chatgpt_prepare_search_terms($search_prompt) {

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
    
    return $terms;
}

// Helper function to get the object of a search prompt
function chatbot_chatgpt_get_object_of_search_prompt($search_prompt) {
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

    return $object;
}

