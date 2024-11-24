<?php
/**
 * Kognetiks Chatbot for WordPress - Transformer Model - Scheduler - Ver 2.2.0
 *
 * This is the file that schedules the building of the Transformer Model.
 * Scheduling can be set to now, daily, weekly, etc.
 * 
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Create the sentential embeddings table if it doesn't exist
function create_sentential_embeddings_table() {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'create_sentential_embeddings_table' );

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_sentential_embeddings';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        post_id BIGINT UNSIGNED NOT NULL,
        context TEXT NOT NULL,
        word TEXT NOT NULL,
        count INT UNSIGNED NOT NULL,
        INDEX post_id_idx (post_id),
        INDEX context_word_idx (context(100), word(100))
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Handle the case where the table was not created
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        prod_trace('ERROR', 'Failed to create sentential embeddings table.');
    }

}

// Reinitialize the sentential embeddings table
function reinitialize_sentential_embeddings_table() {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'reinitialize_sentential_embeddings_table' );
   
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_sentential_embeddings';

    $wpdb->query("TRUNCATE TABLE $table_name");

    // Handle the case where the table was not truncated
    if ($wpdb->get_var("SELECT COUNT(*) FROM $table_name") !== '0') {
        prod_trace('ERROR', 'Failed to reinitialize sentential embeddings table.');
    }

}

// Create the sentence vectors table if it doesn't exist
function create_sentential_sentence_vectors_table() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_sentential_sentence_vectors';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        batch_id INT(11) NOT NULL,
        sentence_index INT(11) NOT NULL,
        sentence_text TEXT NOT NULL,
        vector LONGTEXT NOT NULL,
        PRIMARY KEY (id),
        INDEX (batch_id),
        INDEX (sentence_index)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    if ($wpdb->last_error) {
        prod_trace( 'ERROR', 'Error creating sentence vectors table: ' . $wpdb->last_error);
    }

}

// Reinitialize the sentence vectors table
function reinitialize_sentential_sentence_vectors_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_sentential_sentence_vectors';

    $sql = "TRUNCATE TABLE $table_name;";

    $wpdb->query($sql);

    if ($wpdb->last_error) {
        prod_trace( 'ERROR', 'Error reinitializing sentence vectors table: ' . $wpdb->last_error);
    }

}

// Create the chatbot_sentential_precomputed_vectors table if it doesn't exist
function create_chatbot_sentential_precomputed_vectors_table() {

    back_trace( 'NOTICE', 'create_chatbot_sentential_precomputed_vectors_table' );

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_sentential_precomputed_vectors';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        sentence_index INT(11) NOT NULL,
        vector LONGTEXT NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY sentence_index (sentence_index)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    if ($wpdb->last_error) {
        prod_trace( 'ERROR', 'Error creating chatbot_sentential_precomputed_vectors table: ' . $wpdb->last_error);
    }

}

// Reinitialize the chatbot_sentential_precomputed_vectors table
function reinitialize_chatbot_sentential_precomputed_vectors_table() {

    back_trace( 'NOTICE', 'reinitialize_chatbot_sentential_precomputed_vectors_table' );

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_sentential_precomputed_vectors';

    $sql = "TRUNCATE TABLE $table_name;";

    $wpdb->query($sql);

    if ($wpdb->last_error) {
        prod_trace( 'ERROR', 'Error reinitializing chatbot_sentential_precomputed_vectors table: ' . $wpdb->last_error);
    }

}

// Create the chatbot_sentential_word_embeddings table if it doesn't exist  
function create_chatbot_sentential_word_embeddings_table() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_sentential_word_embeddings';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        word VARCHAR(255) NOT NULL,
        embedding LONGTEXT NOT NULL,
        UNIQUE KEY word (word)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    if ($wpdb->last_error) {
        prod_trace('ERROR', 'Error creating chatbot_sentential_word_embeddings table: ' . $wpdb->last_error);
    } else {
        back_trace('NOTICE', 'chatbot_sentential_word_embeddings table created successfully.');
    }

}

// Add embeddings to the table
function add_sentential_embeddings_to_table($post_id, $embeddings) {

    // DIAG - Diagnostic - Ver 2.2.0
    // back_trace( 'NOTICE', 'add_sentential_embeddings_to_table' );

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_sentential_embeddings';

    foreach ($embeddings as $context => $words) {
        foreach ($words as $word => $count) {
            $wpdb->insert(
                $table_name,
                [
                    'post_id' => $post_id,
                    'context' => $context,
                    'word' => $word,
                    'count' => $count,
                ],
                ['%d', '%s', '%s', '%d']
            );
        }
    }

}

// Retrieve embeddings from the table
function get_sentential_embeddings_from_table($post_id) {

    // DIAG - Diagnostic - Ver 2.2.0
    // back_trace( 'NOTICE', 'get_sentential_embeddings_from_table' );

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_sentential_embeddings';

    $results = $wpdb->get_results(
        $wpdb->prepare("SELECT context, word, count FROM $table_name WHERE post_id = %d", $post_id),
        ARRAY_A
    );

    $embeddings = [];
    foreach ($results as $row) {
        $embeddings[$row['context']][$row['word']] = $row['count'];
    }

    return $embeddings;

}

// Fetch embeddings for words
function fetch_embeddings_for_words($words) {

    back_trace('NOTICE', 'fetch_embeddings_for_words');

    global $wpdb;
    $embeddingsTable = $wpdb->prefix . 'chatbot_sentential_word_embeddings';

    // Validate input
    if (empty($words) || !is_array($words)) {
        prod_trace('ERROR', 'Invalid words provided.');
        return [];
    }

    // Prepare placeholders
    $placeholders = implode(',', array_fill(0, count($words), '%s'));

    // Securely prepare the query
    $query = $wpdb->prepare(
        "SELECT word, embedding FROM $embeddingsTable WHERE word IN ($placeholders)",
        ...$words // Spread operator ensures each word matches a placeholder
    );

    $results = $wpdb->get_results($query, ARRAY_A);

    $embeddings = [];
    foreach ($results as $row) {
        $decoded = json_decode($row['embedding'], true); // Assuming embeddings are stored as JSON

        // Add error handling for JSON decoding
        if (json_last_error() !== JSON_ERROR_NONE) {
            prod_trace('ERROR', "Failed to decode embedding for word: {$row['word']}");
            continue;
        }

        $embeddings[$row['word']] = $decoded;
    }

    // Ensure $sentenceWords is defined and is an array
    $sentenceWords = $sentenceWords ?? [];

    // Check if $sentenceWords is an array before using it in implode
    if (is_array($sentenceWords)) {
        $sentenceWordsString = implode(', ', $sentenceWords);
    } else {
        // Handle the case where $sentenceWords is not an array
        $sentenceWordsString = '';
    }

    // back_trace('NOTICE', 'Fetching embeddings for: ' . implode(', ', $sentenceWords));
    // if (empty($embeddings)) {
    //     back_trace('ERROR', 'No embeddings found for words: ' . implode(', ', $sentenceWords));
    // }

    return $embeddings;

}

// Build a co-occurrence matrix for sentential context
function transformer_model_sentential_context_cosine_similarity($vectorA, $vectorB) {

    // DIAG - Diagnostic - Ver 2.2.0
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_cosine_similarity' );

    $dotProduct = 0.0;
    $magnitudeA = 0.0;
    $magnitudeB = 0.0;

    foreach ($vectorA as $key => $valueA) {
        $valueB = $vectorB[$key] ?? 0;
        $dotProduct += $valueA * $valueB;
        $magnitudeA += $valueA * $valueA;
        $magnitudeB += $valueB * $valueB;
    }

    if ($magnitudeA == 0 || $magnitudeB == 0) {
        return 0.0; // Prevent division by zero
    }

    return $dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB));

}

// Scheduler to build the transformer model
function chatbot_transformer_model_scheduler() {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace('NOTICE', 'chatbot_transformer_model_scheduler started.');

    create_sentential_embeddings_table();

    reinitialize_sentential_embeddings_table();

    create_sentential_sentence_vectors_table();

    reinitialize_sentential_sentence_vectors_table();

    create_chatbot_sentential_precomputed_vectors_table();

    reinitialize_chatbot_sentential_precomputed_vectors_table();

    create_chatbot_sentential_word_embeddings_table();

    // Log the tables created by querying the db and logging the results
    global $wpdb;
    $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}chatbot_sentential_%'", ARRAY_N);
    for ($i = 0; $i < count($tables); $i++) {
        back_trace('NOTICE', 'Table: ' . $tables[$i][0]);
    }

    // Get schedule setting
    $schedule = esc_attr(get_option('chatbot_transformer_model_build_schedule', 'Disable'));

    // Check if scheduling is disabled
    if (in_array($schedule, ['No', 'Disable', 'Cancel'])) {
        wp_clear_scheduled_hook('chatbot_transformer_model_scan_hook');
        update_option('chatbot_transformer_model_build_status', 'No Schedule');
        back_trace('NOTICE', 'Scheduler disabled. Exiting.');
        return;
    }

    // Initialize the build process
    update_option('chatbot_transformer_model_build_status', 'In Process');
    if (get_option('chatbot_transformer_model_offset', 0) === 0) {
        reinitialize_sentential_embeddings_table();
        update_option('chatbot_transformer_model_offset', 0);
        back_trace('NOTICE', 'Reinitialized embeddings table.');
    }

    // Schedule the first scan
    if (!wp_next_scheduled('chatbot_transformer_model_scan_hook')) {
        wp_schedule_single_event(time() + 10, 'chatbot_transformer_model_scan_hook');
        back_trace('NOTICE', 'Scan scheduled.');
    } else {
        back_trace('NOTICE', 'Scan hook already scheduled.');
    }

}
add_action('chatbot_transformer_model_scheduler_hook', 'chatbot_transformer_model_scheduler');

// Scan and build embeddings
function chatbot_transformer_model_scan() {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'chatbot_transformer_model_scan' );

    $offset = intval(get_option('chatbot_transformer_model_offset', 0));
    $batchSize = intval(get_option('chatbot_transform_model_batch_size', 50));
    $corpus = transformer_model_sentential_context_fetch_content($offset, $batchSize);

    // Precompute the vectors
    $corpus = array_map(function($row) {
        return strip_tags(html_entity_decode($row['post_content'], ENT_QUOTES | ENT_HTML5));
    }, $corpus);
    sentential_transformer_model_precompute_vectors($corpus);

    if (empty($corpus)) {
        update_option('chatbot_transformer_model_build_status', 'Completed');
        update_option('chatbot_transformer_model_offset', 0);
        back_trace( 'NOTICE', 'Transformer model build completed.' );
        return;
    }

    foreach ($corpus as $row) {
        $post_id = $row['ID'];
        $content = strip_tags(html_entity_decode($row['post_content'], ENT_QUOTES | ENT_HTML5));
        $embeddings = transformer_model_sentential_context_build_cooccurrence_matrix($content, 2);
        add_sentential_embeddings_to_table($post_id, $embeddings);
    }

    update_option('chatbot_transformer_model_offset', $offset + count($corpus));
    wp_schedule_single_event(time() + 10, 'chatbot_transformer_model_scan_hook');

}
add_action('chatbot_transformer_model_scan_hook', 'chatbot_transformer_model_scan');

// Precompute vectors for sentences
function sentential_transformer_model_precompute_vectors($corpus) {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'sentential_transformer_model_precompute_vectors' );

    // Validate input
    if (!empty($corpus)) {
        back_trace('NOTICE', 'First corpus item: ' . print_r($corpus[0], true));
    } else {
        prod_trace('ERROR', 'Invalid corpus provided.');
        return;
    }

    // Estimate size of $corpus in MB
    $corpusSize = 0;
    foreach ($corpus as $sentence) {
        if (is_array($sentence)) {
            $sentence = implode(' ', $sentence);
        }
        $corpusSize += strlen($sentence);
    }
    $corpusSize /= 1024 * 1024; // Convert to MB
    back_trace( 'NOTICE', 'Estimated corpus size: ' . round($corpusSize, 2) . ' MB' );
    
    $sentenceVectors = [];

    foreach ($corpus as $sentence) {

        if (is_array($sentence)) {
            $sentence = implode(' ', $sentence);
        }
        $sentenceWords = preg_split('/\s+/', strtolower($sentence));
        $sentenceWords = transformer_model_sentential_context_remove_stop_words($sentenceWords);

        // Fetch embeddings for words in the current sentence
        $embeddings = fetch_embeddings_for_words($sentenceWords);

        if (empty($embeddings)) {
            back_trace('NOTICE', 'Skipping sentence due to missing embeddings: ' . $sentence);
            continue;
        }

        $sentenceVector = [];
        $wordCount = 0;

        foreach ($sentenceWords as $word) {
            if (isset($embeddings[$word])) {
                foreach ($embeddings[$word] as $contextWord => $value) {
                    $sentenceVector[$contextWord] = ($sentenceVector[$contextWord] ?? 0) + (is_array($value) ? 0 : $value);
                }
                $wordCount++;
            }
        }

        // Normalize the vector
        if ($wordCount > 0) {
            foreach ($sentenceVector as $key => $value) {
                $sentenceVector[$key] /= $wordCount;
            }
        }

        $sentenceVectors[] = $sentenceVector;

    }

    // Save precomputed vectors for the batch
    save_precomputed_vectors($sentenceVectors);

}

// Save precomputed vectors to the database
function save_precomputed_vectors($sentenceVectors) {

    back_trace( 'NOTICE', 'save_precomputed_vectors' );

    back_trace('NOTICE', 'Saving ' . count($sentenceVectors) . ' sentence vectors.');
    if (empty($sentenceVectors) || !is_array($sentenceVectors)) {
        prod_trace('ERROR', 'Invalid sentence vectors provided.');
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_sentential_precomputed_vectors';
    
    foreach ($sentenceVectors as $index => $vector) {
        $wpdb->insert(
            $table_name,
            [
                'sentence_index' => $index,
                'vector' => json_encode($vector), // Store as JSON
            ],
            ['%d', '%s']
        );
    }

}

// Fetch content from WordPress
function transformer_model_sentential_context_fetch_content($offset, $batchSize) {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'transformer_model_sentential_context_fetch_content' );
    back_trace( 'NOTICE', 'Offset: ' . $offset );
    back_trace( 'NOTICE', 'Batch Size: ' . $batchSize );

    global $wpdb;

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_status = %s AND (post_type = %s OR post_type = %s) LIMIT %d, %d",
            'publish', 'post', 'page', $offset, $batchSize
        ),
        ARRAY_A
    );

    return $results;

}

// Populate the chatbot_sentential_word_embeddings table
function populate_chatbot_sentential_word_embeddings_table($embeddingsData) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_sentential_word_embeddings';

    // Validate embeddings data
    if (empty($embeddingsData) || !is_array($embeddingsData)) {
        prod_trace('ERROR', 'Invalid embeddings data provided for population.');
        return;
    }

    foreach ($embeddingsData as $word => $embedding) {
        $wpdb->insert(
            $table_name,
            [
                'word' => $word,
                'embedding' => json_encode($embedding), // Convert embedding to JSON
            ],
            ['%s', '%s']
        );

        if ($wpdb->last_error) {
            prod_trace('ERROR', 'Error inserting embedding for word "' . $word . '": ' . $wpdb->last_error);
        }
    }

    back_trace('NOTICE', 'Populated chatbot_sentential_word_embeddings table with ' . count($embeddingsData) . ' entries.');

}