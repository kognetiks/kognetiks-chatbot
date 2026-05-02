<?php
/**
 * Kognetiks Chatbot for WordPress - Transformer Model - Lexical Context Model (LCM) - Ver 2.3.0
 *
 * This file contains the code for implementing a Transformer algorithm in PHP
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Last PMI embeddings cache outcome for diagnostics (hit | miss_no_rebuild | miss_rebuilt | unknown).
 *
 * @param string|null $status Set status, or null to read current.
 * @return string
 */
function transformer_model_lexical_context_pmi_cache_fetch_status( $status = null ) {

    static $stored = 'unknown';

    if ( $status !== null ) {
        $stored = (string) $status;
    }

    return $stored;
}

/**
 * Whether get_cached_embeddings may run PMI build on cache miss (default: false — use admin rebuild).
 *
 * @return bool
 */
function transformer_model_lexical_context_may_rebuild_pmi_on_cache_miss() {

    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        return (bool) apply_filters( 'kognetiks_lcm_allow_pmi_rebuild_on_cache_miss', true );
    }

    return (bool) apply_filters( 'kognetiks_lcm_allow_pmi_rebuild_on_cache_miss', false );
}

/**
 * Count sentence chunks across structured documents (for diagnostics).
 *
 * @param array<int, array<string, mixed>> $documents
 * @return int
 */
function transformer_model_lexical_context_count_document_chunks( $documents ) {

    if ( empty( $documents ) || ! is_array( $documents ) ) {
        return 0;
    }

    $n = 0;
    foreach ( $documents as $doc ) {
        if ( ! empty( $doc['chunks'] ) && is_array( $doc['chunks'] ) ) {
            $n += count( $doc['chunks'] );
        } elseif ( ! empty( $doc['normalized_text'] ) ) {
            $n += count( transformer_model_lexical_context_split_into_sentence_chunks( $doc['normalized_text'] ) );
        }
    }

    return $n;
}

/**
 * One-line LCM request summary when KOGNETIKS_LCM_DEBUG is true.
 *
 * @param array<int, array<string, mixed>> $documents
 * @return void
 */
function transformer_model_lexical_context_log_request_start_diagnostics( $documents ) {

    if ( ! transformer_model_lexical_context_is_lcm_diagnostics_enabled() || ! function_exists( 'back_trace' ) ) {
        return;
    }

    $intent_opt = get_option( 'chatbot_lcm_query_intent_expansion', 'No' );
    $intent_on  = transformer_model_lexical_context_query_intent_expansion_enabled();
    $idf_on     = transformer_model_lexical_context_local_idf_enabled();
    $pmi        = transformer_model_lexical_context_pmi_cache_fetch_status();
    $doc_n      = count( $documents );
    $chunk_n    = transformer_model_lexical_context_count_document_chunks( $documents );

    back_trace(
        'NOTICE',
        sprintf(
            '[LCM][start] intent_expansion_enabled=%s intent_opt=%s local_idf_enabled=%s pmi_cache=%s document_count=%d chunk_count=%d',
            $intent_on ? '1' : '0',
            $intent_opt,
            $idf_on ? '1' : '0',
            $pmi,
            $doc_n,
            $chunk_n
        )
    );
}

// Main function to generate a response
function transformer_model_lexical_context_response( $input, $max_tokens = null ) {

    // Maximum tokens - Fixed: removed hardcoded override
    if (empty($max_tokens) || !is_numeric($max_tokens)) {
        $max_tokens = intval(esc_attr(get_option('chatbot_transformer_model_max_tokens', 50)));
    } else {
        $max_tokens = intval($max_tokens);
    }

    // Ensure max_tokens is within reasonable bounds
    $max_tokens = max(10, min(500, $max_tokens));

    // Belt & Suspenders - Check for clean input
    $input = sanitize_text_field($input);
    if (empty($input)) {
        return "I didn't understand that, please try again.";
    }

    // Fetch WordPress content as discrete documents (posts/pages)
    $documents = transformer_model_lexical_context_fetch_wordpress_documents();

    if (empty($documents)) {
        return "I don't have enough content to generate a response. Please add some posts or pages to your WordPress site.";
    }

    transformer_model_lexical_context_pmi_cache_fetch_status( 'unknown' );

    // Build embeddings (PMI windows never cross document boundaries). May skip rebuild on public requests.
    $embeddings = transformer_model_lexical_context_get_cached_embeddings($documents);

    transformer_model_lexical_context_log_request_start_diagnostics( $documents );

    // Generate contextual response (PMI expansion when embeddings exist; lexical-only path when cache miss without rebuild)
    $response = transformer_model_lexical_context_generate_contextual_response($input, $embeddings, $documents, $max_tokens);

    return $response;

}

/**
 * Fetch published posts and pages as structured documents with normalized text and sentence chunks.
 *
 * @return array<int, array<string, mixed>> List of documents.
 */
function transformer_model_lexical_context_fetch_wordpress_documents() {

    global $wpdb;

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_title, post_type, post_content FROM {$wpdb->posts}
             WHERE post_status = %s AND (post_type = %s OR post_type = %s) AND post_content != ''
             ORDER BY ID ASC",
            'publish',
            'post',
            'page'
        ),
        ARRAY_A
    );

    if (empty($results) || !is_array($results)) {
        return [];
    }

    $documents = [];

    foreach ($results as $row) {
        if (empty($row['post_content'])) {
            continue;
        }

        $post_id   = isset($row['ID'] ) ? (int) $row['ID'] : 0;
        $post_type = isset($row['post_type'] ) ? (string) $row['post_type'] : 'post';
        $title     = isset($row['post_title'] ) ? $row['post_title'] : '';

        $normalized = wp_strip_all_tags( (string) $row['post_content'] );
        $normalized = preg_replace( '/\s+/', ' ', $normalized );
        $normalized = trim( $normalized );

        if ( $normalized === '' ) {
            continue;
        }

        $permalink = '';
        if ( $post_id > 0 && function_exists( 'get_permalink' ) ) {
            $permalink = (string) get_permalink( $post_id );
        }

        $chunks = transformer_model_lexical_context_split_into_sentence_chunks( $normalized );

        $documents[] = array(
            'post_id'          => $post_id,
            'post_title'       => $title,
            'post_type'        => $post_type,
            'permalink'        => $permalink,
            'normalized_text'  => $normalized,
            'chunks'           => $chunks,
        );
    }

    return $documents;

}

/**
 * Flatten structured documents into one string (cache fingerprint and legacy PMI compatibility).
 *
 * @param array<int, array<string, mixed>> $documents Documents from transformer_model_lexical_context_fetch_wordpress_documents().
 * @return string
 */
function transformer_model_lexical_context_flatten_documents( $documents ) {

    if ( empty( $documents ) || ! is_array( $documents ) ) {
        return '';
    }

    $parts = array();
    foreach ( $documents as $doc ) {
        if ( ! empty( $doc['normalized_text'] ) ) {
            $parts[] = $doc['normalized_text'];
        }
    }

    return trim( implode( ' ', $parts ) );

}

/**
 * Split normalized text into sentence-level chunks for retrieval ranking.
 *
 * @param string $text Normalized plain text.
 * @return array<int, string>
 */
function transformer_model_lexical_context_split_into_sentence_chunks( $text ) {

    $text = trim( (string) $text );
    if ( $text === '' ) {
        return array();
    }

    $sentences = preg_split( '/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY );
    if ( ! is_array( $sentences ) ) {
        return array();
    }

    $out = array();
    foreach ( $sentences as $sentence ) {
        $sentence = trim( $sentence );
        if ( $sentence !== '' ) {
            $out[] = $sentence;
        }
    }

    return array_values( $out );

}

// Function to get cached embeddings
function transformer_model_lexical_context_get_cached_embeddings( $documents_or_corpus, $windowSize = null ) {

    // Backward compatibility: single concatenated string treated as one synthetic document.
    if ( is_string( $documents_or_corpus ) ) {
        $corpus_flat = $documents_or_corpus;
        $documents   = array(
            array(
                'post_id'         => 0,
                'post_title'      => '',
                'post_type'       => 'legacy',
                'permalink'       => '',
                'normalized_text' => $corpus_flat,
                'chunks'          => transformer_model_lexical_context_split_into_sentence_chunks( $corpus_flat ),
            ),
        );
    } else {
        $documents = $documents_or_corpus;
        if ( empty( $documents ) || ! is_array( $documents ) ) {
            return array();
        }
        $corpus_flat = transformer_model_lexical_context_flatten_documents( $documents );
    }

    if ( $windowSize === null || ! is_numeric( $windowSize ) ) {
        $windowSize = intval( esc_attr( get_option( 'chatbot_transformer_model_word_content_window_size', 3 ) ) );
    }
    $windowSize = max( 1, min( 50, intval( $windowSize ) ) );

    // Cache directory path
    $cacheDir = __DIR__ . '/lexical_embeddings_cache';

    // Ensure cache directory exists
    if ( ! file_exists( $cacheDir ) ) {
        if ( ! wp_mkdir_p( $cacheDir ) ) {
            prod_trace( 'ERROR', 'Failed to create cache directory: ' . $cacheDir );
            return array();
        }
    }

    // Create index.php for security if it doesn't exist
    $indexFile = $cacheDir . '/index.php';
    if ( ! file_exists( $indexFile ) ) {
        $indexContent = "<?php\n// Silence is golden.\n";
        file_put_contents( $indexFile, $indexContent );
    }

    $cacheFile        = $cacheDir . '/lexical_embeddings_cache.php';
    $cacheVersionFile = $cacheDir . '/lexical_embeddings_cache_version.txt';

    // Corpus hash for cache invalidation (stable order via ORDER BY ID in fetch).
    $corpusHash  = hash( 'sha256', $corpus_flat );
    $cacheValid  = false;

    if ( file_exists( $cacheFile ) && file_exists( $cacheVersionFile ) ) {
        $cachedHash = trim( (string) file_get_contents( $cacheVersionFile ) );
        if ( $cachedHash === $corpusHash ) {
            $cacheValid = true;
        }
    }

    if ( $cacheValid ) {
        $embeddings = transformer_model_lexical_context_load_cache( $cacheFile );
        if ( is_array( $embeddings ) && ! empty( $embeddings ) ) {
            transformer_model_lexical_context_pmi_cache_fetch_status( 'hit' );
            return $embeddings;
        }
    }

    transformer_model_lexical_context_migrate_old_cache( $cacheFile );

    if ( ! transformer_model_lexical_context_may_rebuild_pmi_on_cache_miss() ) {
        transformer_model_lexical_context_pmi_cache_fetch_status( 'miss_no_rebuild' );
        if ( function_exists( 'prod_trace' ) ) {
            prod_trace(
                'NOTICE',
                '[LCM][pmi_cache] Embeddings cache missing or stale; PMI rebuild skipped on this request. Rebuild in WordPress → Chatbot → API/Transformer → Delete & Rebuild Lexical Cache (admin).'
            );
        }
        return array();
    }

    $embeddings = transformer_model_lexical_context_build_pmi_matrix_from_documents( $documents, $windowSize );

    if ( ! empty( $embeddings ) ) {
        if ( transformer_model_lexical_context_save_cache( $cacheFile, $embeddings ) ) {
            file_put_contents( $cacheVersionFile, $corpusHash );
        }
        transformer_model_lexical_context_pmi_cache_fetch_status( 'miss_rebuilt' );
    } else {
        transformer_model_lexical_context_pmi_cache_fetch_status( 'miss_rebuilt' );
    }

    return $embeddings;

}

// Function to fetch WordPress content (backward compatibility: flattened documents).
function transformer_model_lexical_context_fetch_wordpress_content() {

    $documents = transformer_model_lexical_context_fetch_wordpress_documents();
    return transformer_model_lexical_context_flatten_documents( $documents );

}

/**
 * Accumulate co-occurrence counts within one token sequence (one document). Does not span sequences.
 *
 * @param array<int, string> $words Token sequence.
 * @param int                $windowSize Context window radius.
 * @param array<string, array<string, int>> $coOccurrenceCounts Mutable global counts (by reference).
 * @param int                $totalCoOccurrences Mutable total pairs (by reference).
 * @return void
 */
function transformer_model_lexical_context_accumulate_cooccurrences_within_sequence( $words, $windowSize, &$coOccurrenceCounts, &$totalCoOccurrences ) {

    $wordCount = count( $words );
    for ( $i = 0; $i < $wordCount; $i++ ) {
        $word = $words[ $i ];
        if ( $word === '' ) {
            continue;
        }

        $contextStart = max( 0, $i - $windowSize );
        $contextEnd   = min( $wordCount - 1, $i + $windowSize );

        for ( $j = $contextStart; $j <= $contextEnd; $j++ ) {
            if ( $i !== $j && isset( $words[ $j ] ) && $words[ $j ] !== '' ) {
                $contextWord = $words[ $j ];
                if ( ! isset( $coOccurrenceCounts[ $word ][ $contextWord ] ) ) {
                    $coOccurrenceCounts[ $word ][ $contextWord ] = 0;
                }
                $coOccurrenceCounts[ $word ][ $contextWord ] += 1;
                $totalCoOccurrences++;
            }
        }
    }

}

/**
 * Build PMI matrix from multiple documents; sliding windows never cross document boundaries.
 *
 * @param array<int, array<string, mixed>> $documents Documents with normalized_text.
 * @param int                               $windowSize Window radius.
 * @return array<string, array<string, float>>
 */
function transformer_model_lexical_context_build_pmi_matrix_from_documents( $documents, $windowSize = 3 ) {

    if ( empty( $documents ) || ! is_array( $documents ) ) {
        return array();
    }

    $windowSize = max( 1, min( 50, intval( $windowSize ) ) );

    $coOccurrenceCounts = array();
    $totalCoOccurrences = 0;
    $wordCounts         = array();
    $totalWords         = 0;

    foreach ( $documents as $doc ) {
        $corpus = isset( $doc['normalized_text'] ) ? (string) $doc['normalized_text'] : '';
        if ( $corpus === '' ) {
            continue;
        }

        $corpus = preg_replace( '/[^\w\s]/u', ' ', $corpus );
        $words  = preg_split( '/\s+/', strtolower( trim( $corpus ) ) );
        $words  = array_filter(
            $words,
            function ( $word ) {
                return ! empty( $word ) && strlen( $word ) > 1;
            }
        );
        $words = array_values( $words );

        if ( empty( $words ) ) {
            continue;
        }

        foreach ( $words as $w ) {
            if ( ! isset( $wordCounts[ $w ] ) ) {
                $wordCounts[ $w ] = 0;
            }
            $wordCounts[ $w ]++;
            $totalWords++;
        }

        transformer_model_lexical_context_accumulate_cooccurrences_within_sequence( $words, $windowSize, $coOccurrenceCounts, $totalCoOccurrences );
    }

    if ( $totalCoOccurrences === 0 || $totalWords === 0 ) {
        return array();
    }

    return transformer_model_lexical_context_finalize_pmi_from_counts( $coOccurrenceCounts, $wordCounts, $totalWords, $totalCoOccurrences );

}

/**
 * PMI matrix from global co-occurrence and word counts.
 *
 * @param array<string, array<string, int>> $coOccurrenceCounts
 * @param array<string, int>                $wordCounts
 * @param int                               $totalWords
 * @param int                               $totalCoOccurrences
 * @return array<string, array<string, float>>
 */
function transformer_model_lexical_context_finalize_pmi_from_counts( $coOccurrenceCounts, $wordCounts, $totalWords, $totalCoOccurrences ) {

    $embeddings = array();

    foreach ( $coOccurrenceCounts as $word => $contexts ) {
        if ( ! isset( $wordCounts[ $word ] ) || (int) $wordCounts[ $word ] === 0 ) {
            continue;
        }

        foreach ( $contexts as $contextWord => $count ) {
            if ( ! isset( $wordCounts[ $contextWord ] ) || (int) $wordCounts[ $contextWord ] === 0 ) {
                continue;
            }

            $p_word         = $wordCounts[ $word ] / $totalWords;
            $p_context      = $wordCounts[ $contextWord ] / $totalWords;
            $p_word_context = $count / $totalCoOccurrences;

            if ( $p_word > 0 && $p_context > 0 && $p_word_context > 0 ) {
                $ratio = $p_word_context / ( $p_word * $p_context );
                if ( $ratio > 0 ) {
                    $pmi = log( $ratio, 2 );
                    if ( $pmi > 0 && is_finite( $pmi ) ) {
                        $pmiThreshold = 0.1;
                        if ( $pmi >= $pmiThreshold ) {
                            $embeddings[ $word ][ $contextWord ] = round( $pmi, 3 );
                        }
                    }
                }
            }
        }
    }

    return $embeddings;

}

// Function to build a PMI matrix for word embeddings (legacy: treats corpus as a single document).
function transformer_model_lexical_context_build_pmi_matrix( $corpus, $windowSize = 3 ) {

    if ( empty( $corpus ) ) {
        return array();
    }

    return transformer_model_lexical_context_build_pmi_matrix_from_documents(
        array(
            array(
                'post_id'          => 0,
                'post_title'       => '',
                'post_type'        => 'synthetic',
                'permalink'        => '',
                'normalized_text'  => $corpus,
                'chunks'           => transformer_model_lexical_context_split_into_sentence_chunks( $corpus ),
            ),
        ),
        $windowSize
    );

}

// Function to migrate old cache format to compressed format
function transformer_model_lexical_context_migrate_old_cache($cacheFile) {
    
    // Check if old uncompressed cache exists
    if (file_exists($cacheFile)) {
        $fileSize = filesize($cacheFile);
        // If file is very large (> 1MB), it's likely the old var_export format
        if ($fileSize > 1048576) {
            prod_trace('NOTICE', 'Migrating old cache format to compressed format. Old size: ' . number_format($fileSize) . ' bytes');
            
            // Try to load old cache
            $embeddings = include $cacheFile;
            if (is_array($embeddings) && !empty($embeddings)) {
                // Save in new compressed format
                if (transformer_model_lexical_context_save_cache($cacheFile, $embeddings)) {
                    // Backup old file before deleting
                    $backupFile = $cacheFile . '.old';
                    if (!file_exists($backupFile)) {
                        copy($cacheFile, $backupFile);
                    }
                    // Delete old uncompressed file (keep backup for safety)
                    // unlink($cacheFile); // Uncomment to delete old file after migration
                    prod_trace('NOTICE', 'Cache migration completed. Backup saved to: ' . basename($backupFile));
                }
            }
        }
    }
    
}

// Function to save cache with compression
function transformer_model_lexical_context_save_cache($cacheFile, $embeddings) {

    // Try compressed serialization first (most efficient)
    $compressedFile = $cacheFile . '.gz';
    $serialized = serialize($embeddings);
    $compressed = gzencode($serialized, 9); // Maximum compression level
    list($createdAt, $updatedAt) = transformer_model_lexical_context_get_cache_timestamps($cacheFile);
    
    if ($compressed !== false) {
        if (file_put_contents($compressedFile, $compressed) !== false) {
            // Also create a PHP wrapper for backward compatibility
            $wrapperContent = "<?php\n";
            $wrapperContent .= "// Lexical embeddings cache (compressed)\n";
            $wrapperContent .= "// Created: {$createdAt}\n";
            $wrapperContent .= "// Updated: {$updatedAt}\n";
            $wrapperContent .= "// File size: " . number_format(filesize($compressedFile)) . " bytes\n";
            $wrapperContent .= "// Original size would be: " . number_format(strlen($serialized)) . " bytes\n";
            $wrapperContent .= "// Compression ratio: " . round((1 - filesize($compressedFile) / strlen($serialized)) * 100, 1) . "%\n";
            $wrapperContent .= "return unserialize(gzdecode(file_get_contents(__FILE__ . '.gz')));\n";
            
            file_put_contents($cacheFile, $wrapperContent);
            return true;
        }
    }
    
    // Fallback to uncompressed serialization if compression fails
    $serializedFile = $cacheFile . '.ser';
    if (file_put_contents($serializedFile, serialize($embeddings)) !== false) {
        $wrapperContent = "<?php\n";
        $wrapperContent .= "// Lexical embeddings cache (serialized)\n";
        $wrapperContent .= "// Created: {$createdAt}\n";
        $wrapperContent .= "// Updated: {$updatedAt}\n";
        $wrapperContent .= "return unserialize(file_get_contents(__FILE__ . '.ser'));\n";
        file_put_contents($cacheFile, $wrapperContent);
        return true;
    }
    
    // Last resort: use var_export (original method, but should rarely be needed)
    $cacheContent = "<?php\n";
    $cacheContent .= "// Lexical embeddings cache (exported)\n";
    $cacheContent .= "// Created: {$createdAt}\n";
    $cacheContent .= "// Updated: {$updatedAt}\n";
    $cacheContent .= 'return ' . var_export($embeddings, true) . ";\n";
    return file_put_contents($cacheFile, $cacheContent) !== false;
    
}

// Function to load cache with automatic format detection
function transformer_model_lexical_context_load_cache($cacheFile) {
    
    // Try compressed format first
    $compressedFile = $cacheFile . '.gz';
    if (file_exists($compressedFile)) {
        $compressed = file_get_contents($compressedFile);
        if ($compressed !== false) {
            $serialized = gzdecode($compressed);
            if ($serialized !== false) {
                $embeddings = unserialize($serialized);
                if ($embeddings !== false) {
                    return $embeddings;
                }
            }
        }
    }
    
    // Try serialized format
    $serializedFile = $cacheFile . '.ser';
    if (file_exists($serializedFile)) {
        $serialized = file_get_contents($serializedFile);
        if ($serialized !== false) {
            $embeddings = unserialize($serialized);
            if ($embeddings !== false) {
                return $embeddings;
            }
        }
    }
    
    // Fallback to PHP include (original var_export format)
    if (file_exists($cacheFile)) {
        $embeddings = include $cacheFile;
        if (is_array($embeddings)) {
            return $embeddings;
        }
    }
    
    return [];
    
}

// Function to capture cache timestamps for metadata comments
function transformer_model_lexical_context_get_cache_timestamps($cacheFile) {

    $createdAt = null;
    $timestampPattern = '/Created:\s*(.+)/';

    if (file_exists($cacheFile)) {
        $existingContent = file_get_contents($cacheFile);
        if ($existingContent && preg_match($timestampPattern, $existingContent, $matches)) {
            $createdAt = trim($matches[1]);
        }
    }

    $currentTimestamp = gmdate('Y-m-d H:i:s') . ' UTC';

    if (empty($createdAt)) {
        $createdAt = $currentTimestamp;
    }

    return [$createdAt, $currentTimestamp];

}

// Function to calculate cosine similarity between two vectors
function transformer_model_lexical_context_cosine_similarity($vectorA, $vectorB) {

    $dotProduct = 0;
    $magnitudeA = 0;
    $magnitudeB = 0;

    $allKeys = array_unique(array_merge(array_keys($vectorA), array_keys($vectorB)));

    foreach ($allKeys as $key) {
        $a = isset($vectorA[$key]) ? $vectorA[$key] : 0;
        $b = isset($vectorB[$key]) ? $vectorB[$key] : 0;

        $dotProduct += $a * $b;
        $magnitudeA += $a * $a;
        $magnitudeB += $b * $b;
    }

    $magnitudeA = sqrt($magnitudeA);
    $magnitudeB = sqrt($magnitudeB);

    if ($magnitudeA * $magnitudeB == 0) {
        return 0;
    }

    return $dotProduct / ($magnitudeA * $magnitudeB);

}

// Function to generate a contextual response
function transformer_model_lexical_context_generate_contextual_response($input, $embeddings, $documents, $responseLength = 50) {

    global $stopWords;

    // Ensure stopWords is initialized
    if (!isset($stopWords) || !is_array($stopWords)) {
        $stopWords = [];
    }

    // Legacy: single concatenated corpus string.
    if ( is_string( $documents ) ) {
        $documents = array(
            array(
                'post_id'         => 0,
                'post_title'      => '',
                'post_type'       => 'legacy',
                'permalink'       => '',
                'normalized_text' => $documents,
                'chunks'          => transformer_model_lexical_context_split_into_sentence_chunks( $documents ),
            ),
        );
    }

    if ( empty( $documents ) || ! is_array( $documents ) ) {
        return "I don't have enough content to generate a response. Please add some posts or pages to your WordPress site.";
    }

    $input_text_for_intent = is_string( $input ) ? $input : '';

    // Improved input preprocessing - filter out stop words
    $input = preg_replace('/[^\w\s]/u', ' ', $input); // Remove punctuation
    $inputWords = preg_split('/\s+/', strtolower(trim($input)));
    
    // Ensure stopWords is initialized
    if (!isset($stopWords) || !is_array($stopWords)) {
        $stopWords = [];
    }
    
    // Filter out stop words using the global $stopWords list
    $inputWords = array_filter($inputWords, function($word) use ($stopWords) {
        return !empty($word) && 
               strlen($word) > 2 && // At least 3 characters
               !in_array($word, $stopWords, true); // Use strict comparison
    });
    $inputWords = array_values($inputWords);
    
    // If we filtered out everything, keep at least the longer words (likely the actual query terms)
    if (empty($inputWords)) {
        $allWords = preg_split('/\s+/', strtolower(trim($input)));
        $inputWords = array_filter($allWords, function($word) use ($stopWords) {
            return !empty($word) && 
                   strlen($word) > 3 && // Keep words longer than 3 chars
                   !in_array($word, $stopWords, true); // Still filter stop words
        });
        $inputWords = array_values($inputWords);
    }

    if (empty($inputWords)) {
        return "I didn't understand that, please try again.";
    }

    // No PMI matrix (cache miss on public request, etc.): rank sentences using query words only — no PMI expansion.
    if ( empty( $embeddings ) ) {
        $sentenceResponseCount = intval( esc_attr( get_option( 'chatbot_transformer_model_sentence_response_length', '5' ) ) );
        $similarityThreshold     = floatval( esc_attr( get_option( 'chatbot_transformer_model_similarity_threshold', '0.3' ) ) );
        $leadingSentencesRatio   = floatval( esc_attr( get_option( 'chatbot_transformer_model_leading_sentences_ratio', '0.2' ) ) );
        $leadingTokenRatio       = floatval( esc_attr( get_option( 'chatbot_transformer_model_leading_token_ratio', '0.2' ) ) );

        $response = transformer_model_lexical_context_build_sentences_from_documents(
            $documents,
            $inputWords,
            $inputWords,
            $responseLength,
            $sentenceResponseCount,
            $similarityThreshold,
            $leadingSentencesRatio,
            $leadingTokenRatio,
            $input_text_for_intent
        );

        if ( empty( $response ) ) {
            return 'The lexical knowledge index is not ready or did not match your question. A site administrator can build it under Transformer settings (Delete & Rebuild Lexical Cache), or try rephrasing your question.';
        }

        $response = removeStopWordFromEnd( $response, $stopWords );

        return transformer_model_lexical_context_format_response( $response );
    }

    // Build input embedding by aggregating word embeddings
    $inputEmbedding = [];
    $wordWeights = [];
    $foundInputWords = [];
    
    foreach ($inputWords as $word) {
        if (isset($embeddings[$word])) {
            $foundInputWords[] = $word;
            // Weight by word frequency in input (TF-like weighting)
            if (!isset($wordWeights[$word])) {
                $wordWeights[$word] = 0;
            }
            $wordWeights[$word] += 1;
            
            foreach ($embeddings[$word] as $contextWord => $value) {
                $inputEmbedding[$contextWord] = ($inputEmbedding[$contextWord] ?? 0) + $value;
            }
        }
    }

    // If no input words found in embeddings, try partial matches or similar words
    if (empty($inputEmbedding)) {
        // Try to find similar words by checking if any embeddings contain similar substrings
        foreach ($inputWords as $inputWord) {
            foreach ($embeddings as $word => $vector) {
                // Check for partial matches (e.g., "deepseek" might match "deep" or "seek")
                if (stripos($word, $inputWord) !== false || stripos($inputWord, $word) !== false) {
                    if (strlen($word) >= 3 && strlen($inputWord) >= 3) { // Only for words 3+ chars
                        foreach ($vector as $contextWord => $value) {
                            $inputEmbedding[$contextWord] = ($inputEmbedding[$contextWord] ?? 0) + $value * 0.5; // Lower weight for partial matches
                        }
                        $foundInputWords[] = $word;
                    }
                }
            }
        }
    }

    // If still empty, return a helpful message
    if (empty($inputEmbedding)) {
        return "I couldn't find relevant information about '" . implode(' ', $inputWords) . "' in my knowledge base. Please try rephrasing your question or asking about a different topic.";
    }

    // Normalize input embedding
    $magnitude = 0;
    foreach ($inputEmbedding as $value) {
        $magnitude += $value * $value;
    }
    $magnitude = sqrt($magnitude);
    if ($magnitude > 0) {
        foreach ($inputEmbedding as $key => $value) {
            $inputEmbedding[$key] = $value / $magnitude;
        }
    }

    // Compute similarities with vocabulary words (excluding stop words and input words)
    $similarities = [];
    $excludeWords = array_merge($inputWords, $stopWords);
    $excludeWords = array_map('strtolower', $excludeWords);
    
    foreach ($embeddings as $word => $vector) {
        $wordLower = strtolower($word);
        // Skip stop words and words already in input
        if (in_array($wordLower, $excludeWords)) {
            continue;
        }
        
        $similarity = transformer_model_lexical_context_cosine_similarity($inputEmbedding, $vector);
        // Use similarity threshold from settings, with adaptive adjustment
        $threshold = floatval(esc_attr(get_option('chatbot_transformer_model_similarity_threshold', '0.3')));
        // Lower threshold slightly if we have input words found (more lenient)
        if (count($foundInputWords) > 0) {
            $threshold = max(0.01, $threshold * 0.5); // Use 50% of threshold if input words found
        }
        if ($similarity > $threshold) {
            $similarities[$word] = $similarity;
        }
    }

    if (empty($similarities)) {
        return "I couldn't find a relevant response. Please try asking about something else.";
    }

    // Sort words by similarity
    arsort($similarities);

    // Get top similar words
    $topWords = array_slice(array_keys($similarities), 0, min($responseLength * 2, count($similarities)));
    
    if (empty($topWords)) {
        return "I couldn't generate a response. Please try again.";
    }

    // Retrieve tuning parameters from settings
    $sentenceResponseCount = intval(esc_attr(get_option('chatbot_transformer_model_sentence_response_length', 5)));
    $similarityThreshold = floatval(esc_attr(get_option('chatbot_transformer_model_similarity_threshold', '0.3')));
    $leadingSentencesRatio = floatval(esc_attr(get_option('chatbot_transformer_model_leading_sentences_ratio', '0.2')));
    $leadingTokenRatio = floatval(esc_attr(get_option('chatbot_transformer_model_leading_token_ratio', '0.2')));
    
    // Try to find actual sentences from corpus that match the input query
    // Prioritize input words over similar words for better query-specific responses
    $queryWords = array_merge($inputWords, array_slice($topWords, 0, 10)); // Combine input words with top similar words
    $response = transformer_model_lexical_context_build_sentences_from_documents(
        $documents,
        $queryWords,
        $inputWords,
        $responseLength,
        $sentenceResponseCount,
        $similarityThreshold,
        $leadingSentencesRatio,
        $leadingTokenRatio,
        $input_text_for_intent
    );
    
    // If we couldn't build sentences from corpus, create structured response from words
    if (empty($response)) {
        $response = transformer_model_lexical_context_build_structured_response($topWords, $similarities, $stopWords, $responseLength);
    }

    // Make sure the response does not end with a stop word
    $response = removeStopWordFromEnd($response, $stopWords);

    // Final cleanup and punctuation
    $response = transformer_model_lexical_context_format_response($response);

    return $response;

}

/**
 * Compare two scored sentence rows (secondary ordering within the same document rank).
 *
 * @param array<string, mixed> $a
 * @param array<string, mixed> $b
 * @return int
 */
function transformer_model_lexical_context_compare_sentence_score_rows( $a, $b ) {

    $aHasSig = isset( $a['hasSignificantMatch'] ) ? $a['hasSignificantMatch'] : false;
    $bHasSig = isset( $b['hasSignificantMatch'] ) ? $b['hasSignificantMatch'] : false;
    if ( $aHasSig != $bHasSig ) {
        return $bHasSig ? 1 : -1;
    }
    if ( $a['inputAtStart'] != $b['inputAtStart'] ) {
        return $b['inputAtStart'] - $a['inputAtStart'];
    }
    if ( $a['inputMatched'] != $b['inputMatched'] ) {
        return $b['inputMatched'] - $a['inputMatched'];
    }
    if ( abs( $a['density'] - $b['density'] ) > 0.1 ) {
        return $b['density'] > $a['density'] ? 1 : -1;
    }
    if ( $a['score'] != $b['score'] ) {
        return $b['score'] - $a['score'];
    }
    if ( abs( $a['wordCount'] - $b['wordCount'] ) > 5 ) {
        return $a['wordCount'] - $b['wordCount'];
    }

    return $b['matched'] - $a['matched'];
}

/**
 * Rank documents by best chunk score, then reorder sentence rows (best documents first).
 *
 * @param array<int, array<string, mixed>> $sentenceScores
 * @return array<int, array<string, mixed>>
 */
function transformer_model_lexical_context_sort_sentence_scores_with_document_priority( $sentenceScores ) {

    $docMax = array();
    foreach ( $sentenceScores as $row ) {
        $pid = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;
        $s   = isset( $row['score'] ) ? (float) $row['score'] : 0.0;
        if ( ! isset( $docMax[ $pid ] ) || $s > $docMax[ $pid ] ) {
            $docMax[ $pid ] = $s;
        }
    }

    if ( empty( $docMax ) ) {
        return $sentenceScores;
    }

    arsort( $docMax );
    $docOrder = array_keys( $docMax );
    $rankOf   = array_flip( $docOrder );

    usort(
        $sentenceScores,
        function ( $a, $b ) use ( $rankOf ) {
            $pa = isset( $a['post_id'] ) ? (int) $a['post_id'] : 0;
            $pb = isset( $b['post_id'] ) ? (int) $b['post_id'] : 0;
            $ra = isset( $rankOf[ $pa ] ) ? (int) $rankOf[ $pa ] : 9999;
            $rb = isset( $rankOf[ $pb ] ) ? (int) $rankOf[ $pb ] : 9999;
            if ( $ra !== $rb ) {
                return $ra <=> $rb;
            }

            return transformer_model_lexical_context_compare_sentence_score_rows( $a, $b );
        }
    );

    return $sentenceScores;
}

/**
 * Drop sentence rows from documents whose best chunk score falls below a ratio of the top document’s max score.
 * Keeps retrieval tight: prefer the winning post, only pull from other posts when they are nearly as strong.
 *
 * @param array<int, array<string, mixed>> $sentenceScores Already sorted (e.g. after sort_sentence_scores_with_document_priority).
 * @param float                             $cross_document_score_ratio Min document max score vs best (e.g. 0.85 = 85%).
 * @return array<int, array<string, mixed>>
 */
function transformer_model_lexical_context_filter_sentence_scores_cross_document_gate( $sentenceScores, $cross_document_score_ratio = 0.85 ) {

    if ( empty( $sentenceScores ) ) {
        return $sentenceScores;
    }

    $docMax = array();
    foreach ( $sentenceScores as $row ) {
        $pid = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;
        $s   = isset( $row['score'] ) ? (float) $row['score'] : 0.0;
        if ( ! isset( $docMax[ $pid ] ) || $s > $docMax[ $pid ] ) {
            $docMax[ $pid ] = $s;
        }
    }

    // Single-document corpus (legacy string path): no cross-document leakage to gate.
    if ( count( $docMax ) <= 1 ) {
        return $sentenceScores;
    }

    $cross_document_score_ratio = max( 0.0, min( 1.0, (float) $cross_document_score_ratio ) );

    $bestDocScore = max( $docMax );
    if ( $bestDocScore <= 0 ) {
        return $sentenceScores;
    }

    $minDocMaxForInclusion = $bestDocScore * $cross_document_score_ratio;

    $allowed = array();
    foreach ( $docMax as $pid => $maxScore ) {
        if ( $maxScore >= $minDocMaxForInclusion ) {
            $allowed[ $pid ] = true;
        }
    }

    $filtered = array();
    foreach ( $sentenceScores as $row ) {
        $pid = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;
        if ( ! empty( $allowed[ $pid ] ) ) {
            $filtered[] = $row;
        }
    }

    // If filtering removed everything (should not happen when best doc has rows), keep original for fallback.
    return ! empty( $filtered ) ? $filtered : $sentenceScores;
}

/**
 * Row-level score gate: after document filtering, keep only chunks whose score is near the best chunk’s score.
 * Drops zero/near-zero scores to reduce generic filler, tags, and weak matches when a strong chunk exists.
 *
 * @param array<int, array<string, mixed>> $sentenceScores Output of cross-document gate (or equivalent).
 * @param float                             $row_score_ratio Min row score vs best row (e.g. 0.65 = 65%).
 * @return array<int, array<string, mixed>>
 */
function transformer_model_lexical_context_filter_sentence_scores_row_gate( $sentenceScores, $row_score_ratio = 0.65 ) {

    if ( empty( $sentenceScores ) ) {
        return $sentenceScores;
    }

    // Absolute floor for “no signal” rows (tags, boilerplate with incidental overlap).
    $near_zero_cutoff = 0.05;

    $best_row_score = 0.0;
    foreach ( $sentenceScores as $row ) {
        $s = isset( $row['score'] ) ? (float) $row['score'] : 0.0;
        if ( is_finite( $s ) && $s > $best_row_score ) {
            $best_row_score = $s;
        }
    }

    // Nothing meaningful to anchor ratio; leave list unchanged.
    if ( $best_row_score <= $near_zero_cutoff ) {
        return $sentenceScores;
    }

    $row_score_ratio = max( 0.0, min( 1.0, (float) $row_score_ratio ) );
    $min_row_score   = $best_row_score * $row_score_ratio;

    $filtered = array();
    foreach ( $sentenceScores as $row ) {
        $s = isset( $row['score'] ) ? (float) $row['score'] : 0.0;
        if ( ! is_finite( $s ) || $s <= $near_zero_cutoff ) {
            continue;
        }
        if ( $s >= $min_row_score ) {
            $filtered[] = $row;
        }
    }

    return ! empty( $filtered ) ? $filtered : $sentenceScores;
}

/**
 * Normalize sentence text for deduplication when merging scored rows.
 *
 * @param string $sentence
 * @return string
 */
function transformer_model_lexical_context_sentence_dedupe_key( $sentence ) {

    $sentence = wp_strip_all_tags( (string) $sentence );
    $sentence = preg_replace( '/\s+/', ' ', trim( $sentence ) );

    return hash( 'sha256', strtolower( $sentence ) );
}

/**
 * After row-level filtering, add extra chunks from the top-ranked document only (up to sentence response cap)
 * so answers can include secondary sentences from the same post without dropping below the global row gate.
 *
 * @param array<int, array<string, mixed>> $after_doc_gate Rows after cross-document gate (full pool per allowed doc).
 * @param array<int, array<string, mixed>> $after_row_gate   Rows after row gate (may be fallback = input).
 * @param float                             $row_score_ratio Matches row gate ratio at call site (reserved / documented parity).
 * @param int                               $max_sentences_per_top_doc Cap aligned with sentence response count setting.
 * @return array<int, array<string, mixed>>
 */
function transformer_model_lexical_context_merge_top_document_expansion( $after_doc_gate, $after_row_gate, $row_score_ratio = 0.65, $max_sentences_per_top_doc = 5 ) {

    if ( empty( $after_doc_gate ) ) {
        return $after_row_gate;
    }

    $near_zero_cutoff = 0.05;

    $doc_max = array();
    foreach ( $after_doc_gate as $row ) {
        $pid = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;
        $s   = isset( $row['score'] ) ? (float) $row['score'] : 0.0;
        if ( ! isset( $doc_max[ $pid ] ) || $s > $doc_max[ $pid ] ) {
            $doc_max[ $pid ] = $s;
        }
    }

    if ( empty( $doc_max ) ) {
        return $after_row_gate;
    }

    arsort( $doc_max );
    reset( $doc_max );
    $top_doc_id = (int) key( $doc_max );

    $merged = is_array( $after_row_gate ) ? $after_row_gate : array();

    $seen = array();
    foreach ( $merged as $r ) {
        if ( ! empty( $r['sentence'] ) ) {
            $seen[ transformer_model_lexical_context_sentence_dedupe_key( $r['sentence'] ) ] = true;
        }
    }

    $from_top = 0;
    foreach ( $merged as $r ) {
        if ( (int) ( $r['post_id'] ?? 0 ) === $top_doc_id ) {
            $from_top++;
        }
    }

    $cap = max( 1, min( 50, (int) $max_sentences_per_top_doc ) );

    $top_doc_rows = array();
    foreach ( $after_doc_gate as $row ) {
        if ( (int) ( $row['post_id'] ?? 0 ) === $top_doc_id ) {
            $top_doc_rows[] = $row;
        }
    }

    usort(
        $top_doc_rows,
        function ( $a, $b ) {
            $sa = isset( $a['score'] ) ? (float) $a['score'] : 0.0;
            $sb = isset( $b['score'] ) ? (float) $b['score'] : 0.0;
            if ( $sa !== $sb ) {
                return $sb <=> $sa;
            }

            return transformer_model_lexical_context_compare_sentence_score_rows( $a, $b );
        }
    );

    // Fill up to $cap distinct sentences from the top-ranked document (score order), adding below–row-gate chunks when needed.
    foreach ( $top_doc_rows as $row ) {
        if ( $from_top >= $cap ) {
            break;
        }

        $sentence = isset( $row['sentence'] ) ? $row['sentence'] : '';
        $key      = transformer_model_lexical_context_sentence_dedupe_key( $sentence );
        if ( isset( $seen[ $key ] ) ) {
            continue;
        }

        $s = isset( $row['score'] ) ? (float) $row['score'] : 0.0;
        if ( ! is_finite( $s ) || $s <= $near_zero_cutoff ) {
            continue;
        }

        $merged[]     = $row;
        $seen[ $key ] = true;
        $from_top++;
    }

    if ( empty( $merged ) ) {
        return $after_row_gate;
    }

    return transformer_model_lexical_context_sort_sentence_scores_with_document_priority( $merged );
}

/**
 * Tokenization aligned with PMI document processing for local IDF (not identical path, same rules).
 *
 * @param string $text Normalized or raw text.
 * @return array<int, string>
 */
function transformer_model_lexical_context_tokenize_for_local_idf( $text ) {

    $corpus = preg_replace( '/[^\w\s]/u', ' ', (string) $text );
    $words  = preg_split( '/\s+/', strtolower( trim( $corpus ) ), -1, PREG_SPLIT_NO_EMPTY );
    $out    = array();
    foreach ( $words as $w ) {
        if ( strlen( $w ) > 1 ) {
            $out[] = $w;
        }
    }

    return $out;
}

/**
 * Per-term IDF from the current LCM document set: idf = log((1+N)/(1+df)) + 1.
 *
 * @param array<int, array<string, mixed>> $documents Same structure as fetch_wordpress_documents().
 * @return array<string, float> Term => idf (empty if no documents).
 */
function transformer_model_lexical_context_build_local_idf_map( $documents ) {

    if ( empty( $documents ) || ! is_array( $documents ) ) {
        return array();
    }

    $df = array();
    $N  = 0;

    foreach ( $documents as $doc ) {
        $corpus = isset( $doc['normalized_text'] ) ? (string) $doc['normalized_text'] : '';
        if ( $corpus === '' ) {
            continue;
        }

        $N++;
        $tokens = transformer_model_lexical_context_tokenize_for_local_idf( $corpus );
        $uniq   = array_unique( $tokens );

        foreach ( $uniq as $t ) {
            if ( $t === '' ) {
                continue;
            }
            if ( ! isset( $df[ $t ] ) ) {
                $df[ $t ] = 0;
            }
            $df[ $t ]++;
        }
    }

    if ( $N === 0 || empty( $df ) ) {
        return array();
    }

    $idf = array();
    foreach ( $df as $term => $dcf ) {
        $idf[ $term ] = log( ( 1 + $N ) / ( 1 + (int) $dcf ) ) + 1.0;
    }

    return $idf;
}

/**
 * Path to the JSON file storing corpus-local IDF (same directory as PMI embeddings cache).
 *
 * @return string
 */
function transformer_model_lexical_context_local_idf_cache_path() {

    return __DIR__ . '/lexical_embeddings_cache/lexical_local_idf_cache.json';
}

/**
 * Persist local IDF map for a corpus hash (admin / scheduled rebuild only — not front-end chat).
 *
 * @param array<int, array<string, mixed>> $documents Same structure as fetch_wordpress_documents().
 * @param string                           $corpus_hash SHA-256 of flattened corpus (same as embeddings version file).
 * @param string|null                      $output_path Optional absolute path for JSON (default: standard lexical_local_idf_cache.json).
 * @return bool True if written successfully.
 */
function transformer_model_lexical_context_save_local_idf_cache_from_documents( $documents, $corpus_hash, $output_path = null ) {

    $corpus_hash = is_string( $corpus_hash ) ? trim( $corpus_hash ) : '';
    if ( $corpus_hash === '' ) {
        return false;
    }

    $default_dir = dirname( transformer_model_lexical_context_local_idf_cache_path() );
    $cache_dir   = ( $output_path !== null && $output_path !== '' )
        ? dirname( $output_path )
        : $default_dir;

    if ( ! file_exists( $cache_dir ) ) {
        if ( ! wp_mkdir_p( $cache_dir ) ) {
            if ( function_exists( 'prod_trace' ) ) {
                prod_trace( 'ERROR', 'LCM local IDF: failed to create cache directory: ' . $cache_dir );
            }
            return false;
        }
    }

    $idf_map = transformer_model_lexical_context_build_local_idf_map( $documents );
    $n_docs  = 0;
    foreach ( $documents as $doc ) {
        if ( ! empty( $doc['normalized_text'] ) ) {
            $n_docs++;
        }
    }

    $payload = array(
        'version'     => 1,
        'corpus_hash' => $corpus_hash,
        'N_docs'      => $n_docs,
        'created_at'  => gmdate( 'c' ),
        'idf_map'     => $idf_map,
    );

    $json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    if ( ! is_string( $json ) || $json === '' ) {
        if ( function_exists( 'prod_trace' ) ) {
            prod_trace( 'ERROR', 'LCM local IDF: wp_json_encode failed.' );
        }
        return false;
    }

    $path = ( $output_path !== null && $output_path !== '' ) ? $output_path : transformer_model_lexical_context_local_idf_cache_path();
    $ok   = ( false !== file_put_contents( $path, $json, LOCK_EX ) );

    if ( ! $ok && function_exists( 'prod_trace' ) ) {
        prod_trace( 'ERROR', 'LCM local IDF: failed to write cache file: ' . $path );
    }

    return $ok;
}

/**
 * Corpus size metrics for lexical cache rebuild scheduling.
 *
 * @param array<int, array<string, mixed>> $documents
 * @return array{ document_count: int, chunk_count: int, corpus_bytes: int }
 */
function transformer_model_lexical_context_lexical_rebuild_corpus_metrics( $documents ) {

    $flat = transformer_model_lexical_context_flatten_documents( $documents );

    return array(
        'document_count' => is_array( $documents ) ? count( $documents ) : 0,
        'chunk_count'    => transformer_model_lexical_context_count_document_chunks( $documents ),
        'corpus_bytes'   => strlen( $flat ),
    );
}

/**
 * Whether a synchronous browser/admin-post rebuild should be deferred to WP-Cron (large corpus).
 *
 * @param array{ document_count: int, chunk_count: int, corpus_bytes: int } $metrics
 * @return bool True = defer to background cron.
 */
function transformer_model_lexical_context_lexical_rebuild_should_defer_to_cron( $metrics ) {

    $max_docs   = (int) apply_filters( 'chatbot_lexical_rebuild_sync_max_documents', 75 );
    $max_bytes  = (int) apply_filters( 'chatbot_lexical_rebuild_sync_max_corpus_bytes', 1500000 );

    if ( ! empty( $metrics['document_count'] ) && (int) $metrics['document_count'] > $max_docs ) {
        return true;
    }
    if ( ! empty( $metrics['corpus_bytes'] ) && (int) $metrics['corpus_bytes'] > $max_bytes ) {
        return true;
    }

    return (bool) apply_filters( 'chatbot_lexical_rebuild_force_async', false );
}

/**
 * Log line for lexical cache rebuild orchestration.
 *
 * @param string $message
 * @return void
 */
function transformer_model_lexical_context_lexical_rebuild_log( $message ) {

    if ( function_exists( 'prod_trace' ) ) {
        prod_trace( 'NOTICE', '[LCM][rebuild] ' . $message );
    }
}

/**
 * Remove temporary staging files matching a token (best-effort).
 *
 * @param string $cache_dir
 * @param string $token     Unique staging token.
 * @return void
 */
function transformer_model_lexical_context_lexical_rebuild_cleanup_staging( $cache_dir, $token ) {

    $cache_dir = trailingslashit( $cache_dir );
    $patterns  = array(
        $cache_dir . 'lexical_embeddings_cache.staging.' . $token . '.php',
        $cache_dir . 'lexical_embeddings_cache.staging.' . $token . '.php.gz',
        $cache_dir . 'lexical_embeddings_cache.staging.' . $token . '.php.ser',
        $cache_dir . 'lexical_embeddings_cache_version.staging.' . $token . '.txt',
        $cache_dir . 'lexical_local_idf_cache.staging.' . $token . '.json',
    );

    foreach ( $patterns as $p ) {
        if ( $p && file_exists( $p ) ) {
            @unlink( $p );
        }
    }
}

/**
 * Build PMI + IDF and atomically replace production cache files (existing cache kept if anything fails).
 *
 * @param string $context Source label for logs: browser_sync|wp_cron|cron|wp_cli (legacy).
 * @return array{ ok: bool, error?: string }
 */
function transformer_model_lexical_context_run_full_lexical_cache_rebuild( $context = 'cron' ) {

    transformer_model_lexical_context_lexical_rebuild_log( 'context=' . $context . ' step=rebuild_started' );

    $documents = transformer_model_lexical_context_fetch_wordpress_documents();
    if ( empty( $documents ) ) {
        transformer_model_lexical_context_lexical_rebuild_log( 'step=aborted reason=empty_corpus' );
        return array( 'ok' => false, 'error' => 'empty_corpus' );
    }

    $metrics = transformer_model_lexical_context_lexical_rebuild_corpus_metrics( $documents );
    transformer_model_lexical_context_lexical_rebuild_log(
        sprintf(
            'step=corpus_metrics document_count=%d chunk_count=%d corpus_bytes=%d',
            $metrics['document_count'],
            $metrics['chunk_count'],
            $metrics['corpus_bytes']
        )
    );

    $corpus_flat = transformer_model_lexical_context_flatten_documents( $documents );
    if ( $corpus_flat === '' ) {
        transformer_model_lexical_context_lexical_rebuild_log( 'step=aborted reason=empty_flat_corpus' );
        return array( 'ok' => false, 'error' => 'empty_corpus' );
    }

    $corpus_hash = hash( 'sha256', $corpus_flat );

    $window_size = intval( get_option( 'chatbot_transformer_model_word_content_window_size', 3 ) );
    $window_size = max( 1, $window_size );

    transformer_model_lexical_context_lexical_rebuild_log( 'step=pmi_build_started' );

    $embeddings = transformer_model_lexical_context_build_pmi_matrix_from_documents( $documents, $window_size );

    transformer_model_lexical_context_lexical_rebuild_log(
        'step=pmi_build_finished embedding_roots=' . ( is_array( $embeddings ) ? count( $embeddings ) : 0 )
    );

    if ( empty( $embeddings ) ) {
        transformer_model_lexical_context_lexical_rebuild_log( 'step=aborted reason=pmi_empty' );
        return array( 'ok' => false, 'error' => 'build_error' );
    }

    $cache_dir = __DIR__ . '/lexical_embeddings_cache';
    if ( ! file_exists( $cache_dir ) ) {
        if ( ! wp_mkdir_p( $cache_dir ) ) {
            transformer_model_lexical_context_lexical_rebuild_log( 'step=aborted reason=cache_dir' );
            return array( 'ok' => false, 'error' => 'write_error' );
        }
    }

    $token = function_exists( 'random_bytes' )
        ? substr( bin2hex( random_bytes( 8 ) ), 0, 16 )
        : substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 16 );
    $staging_php = $cache_dir . '/lexical_embeddings_cache.staging.' . $token . '.php';

    transformer_model_lexical_context_lexical_rebuild_log( 'step=pmi_save_started staging=' . $token );

    if ( ! transformer_model_lexical_context_save_cache( $staging_php, $embeddings ) ) {
        transformer_model_lexical_context_lexical_rebuild_cleanup_staging( $cache_dir, $token );
        transformer_model_lexical_context_lexical_rebuild_log( 'step=aborted reason=pmi_staging_write_failed' );
        return array( 'ok' => false, 'error' => 'write_error' );
    }

    $loaded_check = transformer_model_lexical_context_load_cache( $staging_php );
    if ( empty( $loaded_check ) ) {
        transformer_model_lexical_context_lexical_rebuild_cleanup_staging( $cache_dir, $token );
        transformer_model_lexical_context_lexical_rebuild_log( 'step=aborted reason=pmi_staging_verify_failed' );
        return array( 'ok' => false, 'error' => 'write_error' );
    }

    transformer_model_lexical_context_lexical_rebuild_log( 'step=pmi_save_completed' );

    $idf_staging = $cache_dir . '/lexical_local_idf_cache.staging.' . $token . '.json';

    transformer_model_lexical_context_lexical_rebuild_log( 'step=idf_save_started' );

    if ( ! transformer_model_lexical_context_save_local_idf_cache_from_documents( $documents, $corpus_hash, $idf_staging ) ) {
        transformer_model_lexical_context_lexical_rebuild_cleanup_staging( $cache_dir, $token );
        transformer_model_lexical_context_lexical_rebuild_log( 'step=aborted reason=idf_staging_write_failed' );
        return array( 'ok' => false, 'error' => 'write_error' );
    }

    transformer_model_lexical_context_lexical_rebuild_log( 'step=idf_save_completed' );

    $ver_staging = $cache_dir . '/lexical_embeddings_cache_version.staging.' . $token . '.txt';
    if ( false === file_put_contents( $ver_staging, $corpus_hash, LOCK_EX ) ) {
        transformer_model_lexical_context_lexical_rebuild_cleanup_staging( $cache_dir, $token );
        @unlink( $ver_staging );
        transformer_model_lexical_context_lexical_rebuild_log( 'step=aborted reason=version_staging_write_failed' );
        return array( 'ok' => false, 'error' => 'write_error' );
    }

    $final_php = $cache_dir . '/lexical_embeddings_cache.php';
    $final_ver = $cache_dir . '/lexical_embeddings_cache_version.txt';
    $final_idf = $cache_dir . '/lexical_local_idf_cache.json';

    $backup_sfx = '.lcm_bak_' . $token;

    /**
     * Install staging file over production; roll back one step if rename fails.
     *
     * @param string $staging_path Absolute staging path.
     * @param string $final_path   Absolute production path.
     * @return bool
     */
    $install_one = static function ( $staging_path, $final_path ) use ( $backup_sfx ) {

        if ( ! file_exists( $staging_path ) ) {
            return false;
        }
        if ( file_exists( $final_path ) ) {
            if ( ! @rename( $final_path, $final_path . $backup_sfx ) ) {
                return false;
            }
        }
        if ( ! @rename( $staging_path, $final_path ) ) {
            if ( file_exists( $final_path . $backup_sfx ) ) {
                @rename( $final_path . $backup_sfx, $final_path );
            }
            return false;
        }
        if ( file_exists( $final_path . $backup_sfx ) ) {
            @unlink( $final_path . $backup_sfx );
        }

        return true;
    };

    transformer_model_lexical_context_lexical_rebuild_log( 'step=atomic_swap_started' );

    $ok_move = $install_one( $staging_php, $final_php );

    $gz_staging = $staging_php . '.gz';
    if ( $ok_move && file_exists( $gz_staging ) ) {
        $ok_move = $install_one( $gz_staging, $final_php . '.gz' );
    }

    $ser_staging = $staging_php . '.ser';
    if ( $ok_move && file_exists( $ser_staging ) ) {
        $ok_move = $install_one( $ser_staging, $final_php . '.ser' );
    }

    if ( $ok_move ) {
        $ok_move = $install_one( $ver_staging, $final_ver );
    }
    if ( $ok_move ) {
        $ok_move = $install_one( $idf_staging, $final_idf );
    }

    if ( ! $ok_move ) {
        transformer_model_lexical_context_lexical_rebuild_log( 'step=atomic_swap_failed' );
        return array( 'ok' => false, 'error' => 'write_error' );
    }

    transformer_model_lexical_context_lexical_rebuild_log( 'step=rebuild_completed_ok' );

    return array( 'ok' => true );
}

/**
 * Load local IDF cache when its corpus_hash matches the current flattened corpus (same key as PMI cache).
 *
 * @param string $expected_corpus_hash SHA-256 from transformer_model_lexical_context_flatten_documents().
 * @return array{ hit: bool, idf_map?: array<string, float>, N_docs?: int, reason?: string }
 */
function transformer_model_lexical_context_load_local_idf_cache_for_corpus( $expected_corpus_hash ) {

    $expected_corpus_hash = is_string( $expected_corpus_hash ) ? trim( $expected_corpus_hash ) : '';
    if ( $expected_corpus_hash === '' ) {
        return array(
            'hit'    => false,
            'reason' => 'empty_hash',
        );
    }

    $path = transformer_model_lexical_context_local_idf_cache_path();
    if ( ! file_exists( $path ) ) {
        return array(
            'hit'    => false,
            'reason' => 'cache_missing',
        );
    }

    $json = file_get_contents( $path );
    if ( $json === false || $json === '' ) {
        return array(
            'hit'    => false,
            'reason' => 'cache_invalid',
        );
    }

    $data = json_decode( $json, true );
    if ( ! is_array( $data ) || empty( $data['corpus_hash'] ) || ! isset( $data['idf_map'] ) ) {
        return array(
            'hit'    => false,
            'reason' => 'cache_invalid',
        );
    }

    if ( (string) $data['corpus_hash'] !== $expected_corpus_hash ) {
        return array(
            'hit'    => false,
            'reason' => 'cache_stale',
        );
    }

    $map = $data['idf_map'];
    if ( ! is_array( $map ) ) {
        return array(
            'hit'    => false,
            'reason' => 'cache_invalid',
        );
    }

    return array(
        'hit'       => true,
        'idf_map'   => $map,
        'N_docs'    => isset( $data['N_docs'] ) ? (int) $data['N_docs'] : 0,
        'created_at'=> isset( $data['created_at'] ) ? (string) $data['created_at'] : '',
    );
}

/**
 * Resolve local IDF for a request: only loads from file cache (never builds on front-end).
 *
 * @param array<int, array<string, mixed>> $documents
 * @return array{ map: array<string, float>, active: bool, reason: ?string, source: string }
 */
function transformer_model_lexical_context_resolve_runtime_local_idf( $documents ) {

    $out = array(
        'map'    => array(),
        'active' => false,
        'reason' => null,
        'source' => 'none',
    );

    if ( ! transformer_model_lexical_context_local_idf_enabled() ) {
        return $out;
    }

    $corpus_flat = transformer_model_lexical_context_flatten_documents( $documents );
    if ( $corpus_flat === '' ) {
        $out['reason'] = 'empty_corpus';
        return $out;
    }

    $corpus_hash = hash( 'sha256', $corpus_flat );
    $loaded      = transformer_model_lexical_context_load_local_idf_cache_for_corpus( $corpus_hash );

    if ( ! empty( $loaded['hit'] ) && ! empty( $loaded['idf_map'] ) && is_array( $loaded['idf_map'] ) ) {
        $out['map']    = $loaded['idf_map'];
        $out['active'] = true;
        $out['source'] = 'cache';
        return $out;
    }

    if ( ! empty( $loaded['hit'] ) && empty( $loaded['idf_map'] ) ) {
        $out['reason'] = 'cache_empty';
        return $out;
    }

    $out['reason'] = isset( $loaded['reason'] ) ? (string) $loaded['reason'] : 'cache_missing';
    return $out;
}

/**
 * Optional local IDF weighting for lexical sentence scores (transformer advanced setting).
 *
 * @return bool
 */
function transformer_model_lexical_context_local_idf_enabled() {

    $v = get_option( 'chatbot_transformer_model_lexical_local_idf', 'No' );

    return ( $v === 'Yes' || $v === '1' || $v === 1 || $v === true );
}

/**
 * Log local IDF summary when KOGNETIKS_LCM_DEBUG is on.
 *
 * @param bool                      $option_on Setting is Yes.
 * @param bool                      $active      Non-empty IDF map will be applied to scoring.
 * @param array<string, float>      $idf_map
 * @param array<int, array<string, mixed>> $documents
 * @param string|null               $inactive_reason When option on but inactive (cache miss/stale/etc.).
 * @param string                    $source          'cache' when loaded from file.
 * @return void
 */
function transformer_model_lexical_context_diag_log_local_idf( $option_on, $active, $idf_map, $documents, $inactive_reason = null, $source = 'none' ) {

    if ( ! transformer_model_lexical_context_is_lcm_diagnostics_enabled() || ! function_exists( 'back_trace' ) ) {
        return;
    }

    if ( ! $option_on ) {
        return;
    }

    $n_docs = 0;
    foreach ( $documents as $doc ) {
        if ( ! empty( $doc['normalized_text'] ) ) {
            $n_docs++;
        }
    }

    $map_terms = is_array( $idf_map ) ? count( $idf_map ) : 0;

    if ( $active && ! empty( $idf_map ) ) {
        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][local_idf] option=1 active=1 source=%s N_docs=%d map_terms=%d',
                $source,
                $n_docs,
                $map_terms
            )
        );
    } else {
        $reason = $inactive_reason !== null && $inactive_reason !== '' ? $inactive_reason : 'inactive';
        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][local_idf] option=1 active=0 reason=%s',
                str_replace( array( "\r", "\n" ), ' ', $reason )
            )
        );
        return;
    }
}

/**
 * Phrase + proximity bonus from meaningful direct-query tokens (merged stop-word logic).
 *
 * Bigrams/trigrams follow meaningful token order. Proximity uses tokenizer-aligned chunk tokens.
 *
 * @param string               $sentenceTrimmed Original chunk text.
 * @param string               $sentenceLower   Lowercased chunk text.
 * @param array<int, string>   $inputWordsLower Lowercased query tokens.
 * @return array{ bonus: float, phrases: array<int, string>, proximity: string }
 */
function transformer_model_lexical_context_compute_phrase_proximity_bonus( $sentenceTrimmed, $sentenceLower, $inputWordsLower ) {

    $result = array(
        'bonus'      => 0.0,
        'phrases'    => array(),
        'proximity'  => '',
    );

    $meaningful = transformer_model_lexical_context_meaningful_query_tokens_for_relevance_guard( $inputWordsLower );
    $n          = count( $meaningful );
    if ( $n < 2 ) {
        return $result;
    }

    $phrase_raw = 0.0;
    $matched    = array();

    for ( $i = 0; $i < $n - 1; $i++ ) {
        $a = $meaningful[ $i ];
        $b = $meaningful[ $i + 1 ];
        $pattern = '/\b' . preg_quote( $a, '/' ) . '\s+' . preg_quote( $b, '/' ) . '\b/iu';
        if ( preg_match( $pattern, $sentenceLower ) ) {
            $phrase_raw += 8.0;
            $matched[]   = $a . ' ' . $b;
        }
    }

    for ( $i = 0; $i < $n - 2; $i++ ) {
        $a = $meaningful[ $i ];
        $b = $meaningful[ $i + 1 ];
        $c = $meaningful[ $i + 2 ];
        $pattern = '/\b' . preg_quote( $a, '/' ) . '\s+' . preg_quote( $b, '/' ) . '\s+' . preg_quote( $c, '/' ) . '\b/iu';
        if ( preg_match( $pattern, $sentenceLower ) ) {
            $phrase_raw += 14.0;
            $matched[]   = $a . ' ' . $b . ' ' . $c;
        }
    }

    $prox_raw     = 0.0;
    $prox_details = array();

    $chunk_tokens = transformer_model_lexical_context_tokenize_for_local_idf( $sentenceTrimmed );
    $len          = count( $chunk_tokens );
    if ( $len >= 2 ) {
        $mean_flip = array_flip( $meaningful );
        $got_w12   = false;
        $got_w8    = false;
        for ( $s = 0; $s < $len && ! $got_w12; $s++ ) {
            $e = min( $s + 11, $len - 1 );
            $present = array();
            for ( $p = $s; $p <= $e; $p++ ) {
                $t = $chunk_tokens[ $p ];
                if ( isset( $mean_flip[ $t ] ) ) {
                    $present[ $t ] = true;
                }
            }
            if ( count( $present ) >= 3 ) {
                $prox_raw      += 10.0;
                $prox_details[] = 'w12>=3';
                $got_w12        = true;
            }
        }

        for ( $s = 0; $s < $len && ! $got_w8; $s++ ) {
            $e = min( $s + 7, $len - 1 );
            $present = array();
            for ( $p = $s; $p <= $e; $p++ ) {
                $t = $chunk_tokens[ $p ];
                if ( isset( $mean_flip[ $t ] ) ) {
                    $present[ $t ] = true;
                }
            }
            if ( count( $present ) >= 2 ) {
                $prox_raw      += 4.0;
                $prox_details[] = 'w8>=2';
                $got_w8         = true;
            }
        }
    }

    $total = $phrase_raw + $prox_raw;
    if ( $total > 25.0 ) {
        $total = 25.0;
    }

    $result['bonus']     = $total;
    $result['phrases']   = $matched;
    $result['proximity'] = implode( ';', $prox_details );

    return $result;
}

/**
 * Whether LCM query-intent expansion (Kognetiks/WordPress content) is applied for scoring bonus only.
 *
 * Default off (`chatbot_lcm_query_intent_expansion` !== Yes). Override with filter `kognetiks_lcm_query_intent_expansion_enabled`.
 *
 * @return bool
 */
function transformer_model_lexical_context_query_intent_expansion_enabled() {

    $opt = get_option( 'chatbot_lcm_query_intent_expansion', 'No' );
    $on  = ( $opt === 'Yes' || $opt === '1' || $opt === 1 || $opt === true );

    return (bool) apply_filters( 'kognetiks_lcm_query_intent_expansion_enabled', $on );
}

/**
 * Return plugin-specific intent expansion terms when the user query matches content-use patterns.
 * Used for scoring bonus only; does not affect the relevance guard.
 *
 * @param array<int, string> $meaningful_query_tokens Meaningful direct-query tokens (for filters; not used for guard).
 * @param string             $input_text              Original user query text.
 * @return array{singles: array<int, string>, phrases: array<int, string>}
 */
function transformer_model_lexical_context_expand_query_intent_terms( $meaningful_query_tokens, $input_text ) {

    $empty = array(
        'singles' => array(),
        'phrases' => array(),
    );

    if ( ! transformer_model_lexical_context_query_intent_expansion_enabled() ) {
        return $empty;
    }

    $t = strtolower( wp_strip_all_tags( (string) $input_text ) );
    if ( $t === '' ) {
        return $empty;
    }

    $qtoks = transformer_model_lexical_context_tokenize_for_local_idf( $t );
    $qflip = array_flip( $qtoks );

    $has_wordpress = isset( $qflip['wordpress'] );
    $has_content   = isset( $qflip['content'] );
    $has_site      = isset( $qflip['site'] );

    $trigger = ( $has_wordpress && $has_content )
        || ( $has_site && $has_content )
        || ( false !== stripos( $t, 'use content' ) )
        || ( false !== stripos( $t, 'uses content' ) )
        || ( false !== stripos( $t, 'website content' ) );

    if ( ! $trigger ) {
        return $empty;
    }

    $singles = array( 'posts', 'pages' );
    $phrases = array(
        'site content',
        'knowledge navigator',
        'knowledge base',
        'indexed content',
        'content discovery',
        'content retrieval',
    );

    $pack = array(
        'singles' => $singles,
        'phrases' => $phrases,
    );

    /**
     * Filter expanded singles/phrases for intent scoring (after triggers matched).
     *
     * @param array{singles: array<int, string>, phrases: array<int, string>} $pack
     * @param array<int, string>                                                $meaningful_query_tokens
     * @param string                                                            $input_text
     */
    return apply_filters( 'kognetiks_lcm_query_intent_expansion_terms', $pack, $meaningful_query_tokens, $input_text );
}

/**
 * Build intent expansion package once per request (filters run once). Returns null when inactive or empty.
 *
 * @param array<int, string> $inputWordsLower Lowercased query words.
 * @param string             $input_text_raw  Original query text for triggers.
 * @return array{ active: true, singles: array<int, string>, phrase_seqs: array<int, array<int, string>> }|null
 */
function transformer_model_lexical_context_precompute_intent_expansion_for_request( $inputWordsLower, $input_text_raw ) {

    if ( ! transformer_model_lexical_context_query_intent_expansion_enabled() ) {
        return null;
    }

    $meaningful = transformer_model_lexical_context_meaningful_query_tokens_for_relevance_guard( $inputWordsLower );
    $pack       = transformer_model_lexical_context_expand_query_intent_terms( $meaningful, $input_text_raw );

    if ( empty( $pack['phrases'] ) && empty( $pack['singles'] ) ) {
        return null;
    }

    $singles = array();
    if ( ! empty( $pack['singles'] ) && is_array( $pack['singles'] ) ) {
        foreach ( $pack['singles'] as $w ) {
            $w = strtolower( trim( (string) $w ) );
            if ( $w !== '' ) {
                $singles[] = $w;
            }
        }
    }
    $singles = array_values( array_unique( $singles ) );

    $phrase_seqs = array();
    if ( ! empty( $pack['phrases'] ) && is_array( $pack['phrases'] ) ) {
        foreach ( $pack['phrases'] as $ph ) {
            $parts = preg_split( '/\s+/u', strtolower( trim( (string) $ph ) ), -1, PREG_SPLIT_NO_EMPTY );
            if ( count( $parts ) >= 2 ) {
                $phrase_seqs[] = $parts;
            }
        }
    }

    return array(
        'active'      => true,
        'singles'     => $singles,
        'phrase_seqs' => $phrase_seqs,
    );
}

/**
 * True if consecutive token sequence $seq appears in $chunk_tokens (LCM tokenizer; no regex).
 *
 * @param array<int, string> $chunk_tokens
 * @param array<int, string> $seq
 * @return bool
 */
function transformer_model_lexical_context_chunk_contains_token_sequence( $chunk_tokens, $seq ) {

    $sn = count( $seq );
    $tn = count( $chunk_tokens );
    if ( $sn < 2 || $tn < $sn ) {
        return false;
    }

    for ( $i = 0; $i <= $tn - $sn; $i++ ) {
        $ok = true;
        for ( $j = 0; $j < $sn; $j++ ) {
            if ( $chunk_tokens[ $i + $j ] !== $seq[ $j ] ) {
                $ok = false;
                break;
            }
        }
        if ( $ok ) {
            return true;
        }
    }

    return false;
}

/**
 * Intent bonus from precomputed singles / phrase token sequences (cap 20). One tokenize per chunk; no regex.
 *
 * @param string                                                                                                  $sentenceTrimmed
 * @param array{ active?: bool, singles?: array<int, string>, phrase_seqs?: array<int, array<int, string>> }|null $precomputed
 * @return array{ bonus: float, matched: array<int, string> }
 */
function transformer_model_lexical_context_apply_precomputed_intent_bonus( $sentenceTrimmed, $precomputed ) {

    $out = array(
        'bonus'   => 0.0,
        'matched' => array(),
    );

    if ( empty( $precomputed ) || empty( $precomputed['active'] ) ) {
        return $out;
    }

    $chunk_tokens = transformer_model_lexical_context_tokenize_for_local_idf( $sentenceTrimmed );
    $token_flip   = array_flip( $chunk_tokens );

    $raw = 0.0;

    if ( ! empty( $precomputed['singles'] ) ) {
        foreach ( $precomputed['singles'] as $w ) {
            if ( isset( $token_flip[ $w ] ) ) {
                $raw += 4.0;
                $out['matched'][] = $w;
            }
        }
    }

    if ( ! empty( $precomputed['phrase_seqs'] ) ) {
        foreach ( $precomputed['phrase_seqs'] as $seq ) {
            if ( ! is_array( $seq ) || count( $seq ) < 2 ) {
                continue;
            }
            if ( transformer_model_lexical_context_chunk_contains_token_sequence( $chunk_tokens, $seq ) ) {
                $raw += 10.0;
                $out['matched'][] = implode( ' ', $seq );
            }
        }
    }

    $out['bonus'] = min( 20.0, $raw );

    return $out;
}

/**
 * Score one sentence/chunk for lexical retrieval (query + PMI-expanded words).
 *
 * @param string               $sentenceTrimmed
 * @param array<int, string>   $searchWordsLower
 * @param array<int, string>   $inputWordsLower
 * @param array<string, float>|null $local_idf_map Optional IDF weights (runtime: from lexical_local_idf_cache.json when corpus hash matches).
 * @param bool                 $apply_local_idf        When true and map non-empty, add a small direct-query-only IDF bonus (not expansion).
 * @param array<string, mixed>|null $intent_precomputed From precompute_intent_expansion_for_request(); null skips intent scoring.
 * @return array<string, mixed>|null
 */
function transformer_model_lexical_context_lexical_sentence_score_row( $sentenceTrimmed, $searchWordsLower, $inputWordsLower, $local_idf_map = null, $apply_local_idf = false, $intent_precomputed = null ) {

    $sentenceTrimmed = trim( $sentenceTrimmed );
    if ( $sentenceTrimmed === '' ) {
        return null;
    }

    $sentenceLower       = strtolower( $sentenceTrimmed );
    $sentenceWordCount   = str_word_count( $sentenceTrimmed );

    if ( $sentenceWordCount > 60 ) {
        return null;
    }

    $citationPatterns = array(
        '/^by\s+[A-Z][a-z]+\s+[A-Z]/',
        '/^\d{4}[,\s]/',
        '/^[A-Z][a-z]+\s+[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*,\s+[A-Z][a-z]+/',
    );
    foreach ( $citationPatterns as $pattern ) {
        if ( preg_match( $pattern, $sentenceTrimmed ) ) {
            return null;
        }
    }

    $commaCount = substr_count( $sentenceTrimmed, ',' );
    if ( $commaCount > 5 && $sentenceWordCount < 30 ) {
        return null;
    }

    $questionPatterns = array(
        '/^what\s+is\s+[^?]+\?$/i',
        '/^what\s+are\s+[^?]+\?$/i',
    );
    foreach ( $questionPatterns as $pattern ) {
        if ( preg_match( $pattern, $sentenceTrimmed ) ) {
            $hasInputWords = false;
            foreach ( $inputWordsLower as $inputWord ) {
                if ( stripos( $sentenceLower, $inputWord ) !== false ) {
                    $hasInputWords = true;
                    break;
                }
            }
            if ( ! $hasInputWords ) {
                return null;
            }
        }
    }

    $score             = 0;
    $matchedWords      = array();
    $inputWordsMatched = 0;
    $inputWordsAtStart = 0;

    foreach ( $inputWordsLower as $word ) {
        $pattern = '/\b' . preg_quote( $word, '/' ) . '\b/i';
        $count   = preg_match_all( $pattern, $sentenceLower );
        if ( $count > 0 ) {
            $score += $count * 10;
            $inputWordsMatched++;
            if ( ! in_array( $word, $matchedWords, true ) ) {
                $matchedWords[] = $word;
            }
            $firstWords = implode( ' ', array_slice( explode( ' ', $sentenceLower ), 0, 10 ) );
            if ( preg_match( $pattern, $firstWords ) ) {
                $inputWordsAtStart++;
                $score += 5;
            }
        }
    }

    foreach ( $searchWordsLower as $word ) {
        if ( in_array( $word, $inputWordsLower, true ) ) {
            continue;
        }
        $pattern = '/\b' . preg_quote( $word, '/' ) . '\b/i';
        $count   = preg_match_all( $pattern, $sentenceLower );
        if ( $count > 0 ) {
            $score += $count;
            if ( ! in_array( $word, $matchedWords, true ) ) {
                $matchedWords[] = $word;
            }
        }
    }

    $relevantWordCount = count( $matchedWords );
    $density           = $sentenceWordCount > 0 ? ( $relevantWordCount / $sentenceWordCount ) : 0;
    $score            += $density * 5;

    if ( $sentenceWordCount < 10 ) {
        $score *= 0.8;
    } elseif ( $sentenceWordCount > 40 ) {
        $score *= 0.7;
    } elseif ( $sentenceWordCount > 30 ) {
        $score *= 0.9;
    }

    $phrase_prox = transformer_model_lexical_context_compute_phrase_proximity_bonus( $sentenceTrimmed, $sentenceLower, $inputWordsLower );
    $score      += $phrase_prox['bonus'];

    if ( $phrase_prox['bonus'] > 0.0 && transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        static $lcm_phrase_bonus_sample_logged = false;
        if ( ! $lcm_phrase_bonus_sample_logged ) {
            $ph_list = array();
            foreach ( $phrase_prox['phrases'] as $ph ) {
                $ph_list[] = str_replace( array( "\r", "\n", '|' ), array( ' ', ' ', '/' ), (string) $ph );
            }
            $ph_str  = ! empty( $ph_list ) ? implode( '|', $ph_list ) : '';
            $prox_s  = ( $phrase_prox['proximity'] !== '' ) ? $phrase_prox['proximity'] : 'none';
            back_trace(
                'NOTICE',
                sprintf(
                    '[LCM][phrase_bonus] bonus=%g phrases=[%s] proximity=%s',
                    $phrase_prox['bonus'],
                    $ph_str,
                    $prox_s
                )
            );
            $lcm_phrase_bonus_sample_logged = true;
        }
    }

    $intent_pack = transformer_model_lexical_context_apply_precomputed_intent_bonus( $sentenceTrimmed, $intent_precomputed );
    $score      += $intent_pack['bonus'];

    if ( $intent_pack['bonus'] > 0.0 && transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        static $lcm_intent_bonus_sample_logged = false;
        if ( ! $lcm_intent_bonus_sample_logged ) {
            $matched_safe = array();
            foreach ( $intent_pack['matched'] as $m ) {
                $matched_safe[] = str_replace( array( "\r", "\n", '|' ), array( ' ', ' ', '/' ), (string) $m );
            }
            back_trace(
                'NOTICE',
                sprintf(
                    '[LCM][intent_bonus] bonus=%g matched=[%s]',
                    $intent_pack['bonus'],
                    implode( '|', $matched_safe )
                )
            );
            $lcm_intent_bonus_sample_logged = true;
        }
    }

    if ( $apply_local_idf && ! empty( $local_idf_map ) && is_array( $local_idf_map ) ) {
        // Direct query tokens only (same notion as relevance guard); PMI expansion terms excluded.
        $direct_query_tokens = transformer_model_lexical_context_meaningful_query_tokens_for_relevance_guard( $inputWordsLower );
        $idf_sum_capped      = 0.0;
        $matched_query_idfs  = array();

        foreach ( $direct_query_tokens as $word ) {
            if ( strlen( $word ) < 2 ) {
                continue;
            }
            $pattern = '/\b' . preg_quote( $word, '/' ) . '\b/i';
            if ( ! preg_match( $pattern, $sentenceLower ) ) {
                continue;
            }
            if ( ! isset( $local_idf_map[ $word ] ) ) {
                continue;
            }
            $idf_raw = (float) $local_idf_map[ $word ];
            $idf_sum_capped += min( 3.0, $idf_raw );
            $matched_query_idfs[ $word ] = $idf_raw;
        }

        if ( $idf_sum_capped > 0.0 ) {
            $idf_bonus = $idf_sum_capped * 2.0;
            $score    += $idf_bonus;

            if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                static $lcm_local_idf_bonus_sample_logged = false;
                if ( ! $lcm_local_idf_bonus_sample_logged ) {
                    $term_parts = array();
                    foreach ( $matched_query_idfs as $tw => $iw ) {
                        $tw_safe      = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $tw );
                        $term_parts[] = sprintf( '%s=%g', $tw_safe, $iw );
                    }
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][local_idf] sample direct_query_idf bonus=%g sum_capped=%g terms=[%s]',
                            $idf_bonus,
                            $idf_sum_capped,
                            implode( ', ', $term_parts )
                        )
                    );
                    $lcm_local_idf_bonus_sample_logged = true;
                }
            }
        }
    }

    $hasSignificantMatch = false;
    if ( ! empty( $inputWordsLower ) ) {
        foreach ( $inputWordsLower as $inputWord ) {
            if ( strlen( $inputWord ) >= 4 ) {
                $pattern = '/\b' . preg_quote( $inputWord, '/' ) . '\b/i';
                if ( preg_match( $pattern, $sentenceLower ) ) {
                    $hasSignificantMatch = true;
                    break;
                }
            }
        }
    }

    $allShortWords = true;
    foreach ( $inputWordsLower as $word ) {
        if ( strlen( $word ) >= 4 ) {
            $allShortWords = false;
            break;
        }
    }

    $minScore = $inputWordsMatched > 0 ? 1 : 10;

    if ( $hasSignificantMatch || $inputWordsMatched >= 2 || ( $allShortWords && $inputWordsMatched > 0 ) || $score >= $minScore ) {
        return array(
            'sentence'            => $sentenceTrimmed,
            'score'               => $score,
            'matched'             => count( $matchedWords ),
            'inputMatched'        => $inputWordsMatched,
            'wordCount'           => $sentenceWordCount,
            'density'             => $density,
            'inputAtStart'        => $inputWordsAtStart,
            'hasSignificantMatch' => $hasSignificantMatch,
        );
    }

    return null;
}

/**
 * Detect boilerplate / navigation chunks that should not participate in lexical scoring.
 *
 * @param string $text Sentence or chunk text.
 * @return bool True if this chunk should be skipped before scoring.
 */
function transformer_model_lexical_context_is_low_value_chunk( $text ) {

    $text = trim( wp_strip_all_tags( (string) $text ) );
    if ( $text === '' ) {
        return true;
    }

    // Metadata / section headers: Tags, Related, Reference (whole-line or leading).
    if ( preg_match( '/^\s*(tags|related|references?)(\s*[:\-–—]|\s*$)/i', $text ) ) {
        return true;
    }
    if ( preg_match( '/^\s*tags\s+#/i', $text ) ) {
        return true;
    }
    // Lines dominated by label words + hashtags (e.g. "Tags #x Related Reference - Other").
    if ( preg_match( '/#/', $text ) && preg_match( '/\b(tags|related|reference)\b/i', $text ) && str_word_count( $text ) <= 14 ) {
        return true;
    }

    $words = preg_split( '/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY );
    $n     = count( $words );
    if ( $n === 0 ) {
        return true;
    }

    $hash_count = 0;
    foreach ( $words as $w ) {
        if ( preg_match( '/^#/', $w ) ) {
            $hash_count++;
        }
    }
    $hash_ratio = $hash_count / $n;

    if ( $hash_ratio >= 0.28 ) {
        return true;
    }
    if ( $n <= 12 && $hash_count >= 2 ) {
        return true;
    }

    // Same token repeated too often (keyword / tag soup).
    $norm = array();
    foreach ( $words as $w ) {
        $norm[] = strtolower( preg_replace( '/^#/', '', preg_replace( '/[.,!?;:]+$/', '', $w ) ) );
    }
    $freq = array_count_values( $norm );
    arsort( $freq );
    $top_c = (int) reset( $freq );
    if ( $n >= 4 && ( $top_c / $n ) >= 0.5 ) {
        return true;
    }

    // Mostly very short tokens and no word long enough to be substantive prose.
    $long_words = 0;
    $char_sum   = 0;
    foreach ( $words as $w ) {
        $bare = preg_replace( '/^#/', '', $w );
        $bare = preg_replace( '/[.,!?;:]+$/', '', $bare );
        $len  = strlen( $bare );
        $char_sum += $len;
        if ( $len >= 6 ) {
            $long_words++;
        }
    }
    $avg_len = $char_sum / $n;

    if ( $n >= 5 && $long_words === 0 && $avg_len <= 3.6 ) {
        return true;
    }

    // Thin lines: hashtags present but no substance and no typical verb/noun glue.
    $has_substance_token = ( $long_words > 0 ) || preg_match( '/\b[a-z]{5,}\b/i', $text );
    $has_verbish         = preg_match(
        '/\b(is|are|was|were|been|being|have|has|had|do|does|did|will|would|could|should|may|might|can|must|shall|protect|using|include|help|read|work|make|made|see|get|go|use|uses|say|said|call|find|show|give|take|come|look|want|need|keep|let|put|mean|set|end|seem|may|might)\b/i',
        $text
    );

    if ( $n <= 9 && $hash_count > 0 && ! $has_verbish && ! $has_substance_token ) {
        return true;
    }

    return false;
}

/**
 * Stop words removed when deriving meaningful query tokens for the relevance guard and local IDF bonus.
 *
 * Merges the built-in LCM list with global `$stopWords` (from translations/globals) when that variable
 * exists and is an array; all entries are normalized to lowercase for lookup.
 *
 * @return array<int, string> Lowercased unique stop words.
 */
function transformer_model_lexical_context_relevance_guard_stop_words() {

    $local = array(
        'what',
        'is',
        'are',
        'the',
        'a',
        'an',
        'to',
        'of',
        'for',
        'in',
        'on',
        'with',
        'related',
        'about',
        'how',
        'do',
        'does',
    );

    $merged = array();
    foreach ( $local as $w ) {
        $w = strtolower( trim( (string) $w ) );
        if ( $w !== '' ) {
            $merged[ $w ] = true;
        }
    }

    global $stopWords;
    if ( isset( $stopWords ) && is_array( $stopWords ) ) {
        foreach ( $stopWords as $sw ) {
            $sw = strtolower( trim( (string) $sw ) );
            if ( $sw !== '' ) {
                $merged[ $sw ] = true;
            }
        }
    }

    return array_keys( $merged );
}

/**
 * Query tokens that must overlap a chunk for it to be scored (excludes guard stop words).
 *
 * @param array<int, string> $inputWordsLower Lowercased input words from the user query.
 * @return array<int, string>
 */
function transformer_model_lexical_context_meaningful_query_tokens_for_relevance_guard( $inputWordsLower ) {

    $stop = array_flip( transformer_model_lexical_context_relevance_guard_stop_words() );
    $out  = array();

    foreach ( $inputWordsLower as $w ) {
        $w = strtolower( trim( (string) $w ) );
        if ( strlen( $w ) < 2 ) {
            continue;
        }
        if ( isset( $stop[ $w ] ) ) {
            continue;
        }
        $out[] = $w;
    }

    return array_values( array_unique( $out ) );
}

/**
 * True if the chunk contains at least one meaningful query token (whole-word / Unicode word chars).
 *
 * @param string               $chunkText
 * @param array<int, string>   $meaningfulTokens
 * @return bool
 */
function transformer_model_lexical_context_chunk_has_meaningful_query_overlap( $chunkText, $meaningfulTokens ) {

    if ( empty( $meaningfulTokens ) ) {
        return true;
    }

    $haystack = strtolower( wp_strip_all_tags( (string) $chunkText ) );

    foreach ( $meaningfulTokens as $tok ) {
        if ( strlen( $tok ) < 2 ) {
            continue;
        }
        $pattern = '/(?<![\p{L}\p{N}_])' . preg_quote( $tok, '/' ) . '(?![\p{L}\p{N}_])/u';
        if ( preg_match( $pattern, $haystack ) ) {
            return true;
        }
    }

    return false;
}

/**
 * User-facing message when meaningful query terms exist but no corpus chunk contains any of them.
 *
 * @return string
 */
function transformer_model_lexical_context_no_query_overlap_message() {

    return "I'm sorry, but I couldn't find any relevant information on that topic. Would you like to try something else?";
}

/**
 * Whether Lexical Context Model pipeline diagnostics are enabled (logs only; no behavior change).
 *
 * @return bool
 */
function transformer_model_lexical_context_is_lcm_diagnostics_enabled() {

    return defined( 'KOGNETIKS_LCM_DEBUG' ) && KOGNETIKS_LCM_DEBUG;
}

/**
 * Map post_id => post_title for diagnostic lines.
 *
 * @param array<int, array<string, mixed>> $documents
 * @return array<int, string>
 */
function transformer_model_lexical_context_post_title_map_from_documents( $documents ) {

    $map = array();
    if ( empty( $documents ) || ! is_array( $documents ) ) {
        return $map;
    }

    foreach ( $documents as $doc ) {
        $pid = isset( $doc['post_id'] ) ? (int) $doc['post_id'] : 0;
        $map[ $pid ] = isset( $doc['post_title'] ) ? (string) $doc['post_title'] : '';
    }

    return $map;
}

/**
 * Short plain-text preview for logs (HTML stripped, whitespace normalized).
 *
 * @param string $text    Raw chunk/sentence text.
 * @param int    $max_len Max characters before ellipsis (default ~165).
 * @return string
 */
function transformer_model_lexical_context_diag_preview_text( $text, $max_len = 165 ) {

    $text = wp_strip_all_tags( (string) $text );
    $text = preg_replace( '/\s+/', ' ', trim( $text ) );

    if ( strlen( $text ) > $max_len ) {
        return substr( $text, 0, max( 0, $max_len - 3 ) ) . '...';
    }

    return $text;
}

/**
 * Log top candidate rows for one pipeline stage via back_trace() (NOTICE).
 *
 * @param string                             $stage_slug      Short stage name for [LCM][slug].
 * @param array<int, array<string, mixed>>   $rows            Candidate rows (partial rows allowed for pre-scoring).
 * @param array<int, string>                 $post_title_map  Fallback titles by post_id.
 * @return void
 */
function transformer_model_lexical_context_diag_log_pipeline_stage( $stage_slug, $rows, $post_title_map ) {

    if ( ! transformer_model_lexical_context_is_lcm_diagnostics_enabled() || ! function_exists( 'back_trace' ) ) {
        return;
    }

    $stage_slug = preg_replace( '/[^\w.-]/', '', (string) $stage_slug );
    if ( $stage_slug === '' ) {
        $stage_slug = 'stage';
    }

    if ( empty( $rows ) ) {
        back_trace( 'NOTICE', '[LCM][' . $stage_slug . '] no candidates' );

        return;
    }

    $work = array_values( $rows );
    $has_score = false;

    foreach ( $work as $r ) {
        if ( isset( $r['score'] ) && is_numeric( $r['score'] ) ) {
            $has_score = true;
            break;
        }
    }

    if ( $has_score ) {
        usort(
            $work,
            function ( $a, $b ) {
                return ( (float) ( $b['score'] ?? 0 ) ) <=> ( (float) ( $a['score'] ?? 0 ) );
            }
        );
    }

    $work = array_slice( $work, 0, 5 );

    foreach ( $work as $row ) {
        $score = 'n/a';
        if ( isset( $row['score'] ) && is_numeric( $row['score'] ) ) {
            $score = sprintf( '%g', (float) $row['score'] );
        }

        $pid = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;

        $title = isset( $row['post_title'] ) ? (string) $row['post_title'] : '';
        if ( $title === '' && isset( $post_title_map[ $pid ] ) ) {
            $title = $post_title_map[ $pid ];
        }

        $sentence       = isset( $row['sentence'] ) ? $row['sentence'] : '';
        $preview        = transformer_model_lexical_context_diag_preview_text( $sentence, 165 );
        $title_safe     = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $title );
        $preview_safe   = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $preview );

        $line = sprintf(
            '[LCM][%s] score=%s | post_id=%d | title="%s" | text="%s"',
            $stage_slug,
            $score,
            $pid,
            $title_safe,
            $preview_safe
        );

        back_trace( 'NOTICE', $line );
    }
}

/**
 * Rank sentence chunks per document, prefer top matching posts, then assemble the reply.
 *
 * @param array<int, array<string, mixed>> $documents
 * @param string                           $input_text_raw Optional original user query for intent expansion scoring (not used for relevance guard).
 * @return string
 */
function transformer_model_lexical_context_build_sentences_from_documents( $documents, $searchWords, $inputWords, $maxWords, $sentenceResponseCount = 5, $similarityThreshold = 0.3, $leadingSentencesRatio = 0.2, $leadingTokenRatio = 0.2, $input_text_raw = '' ) {

    $sentenceScores   = array();
    $searchWordsLower = array_map( 'strtolower', $searchWords );
    $inputWordsLower  = array_map( 'strtolower', $inputWords );

    $intent_precomputed = transformer_model_lexical_context_precompute_intent_expansion_for_request( $inputWordsLower, $input_text_raw );

    $meaningful_query_tokens          = transformer_model_lexical_context_meaningful_query_tokens_for_relevance_guard( $inputWordsLower );
    $corpus_had_meaningful_overlap    = false;

    $post_title_map             = transformer_model_lexical_context_is_lcm_diagnostics_enabled()
        ? transformer_model_lexical_context_post_title_map_from_documents( $documents )
        : array();
    $lcm_pre_scoring_candidates = array();

    $resolved_idf     = transformer_model_lexical_context_resolve_runtime_local_idf( $documents );
    $local_idf_map    = isset( $resolved_idf['map'] ) && is_array( $resolved_idf['map'] ) ? $resolved_idf['map'] : array();
    $apply_local_idf  = ! empty( $resolved_idf['active'] );
    $option_local_idf = transformer_model_lexical_context_local_idf_enabled();
    $local_idf_reason = isset( $resolved_idf['reason'] ) ? $resolved_idf['reason'] : null;
    $local_idf_source = isset( $resolved_idf['source'] ) ? (string) $resolved_idf['source'] : 'none';

    transformer_model_lexical_context_diag_log_local_idf( $option_local_idf, $apply_local_idf, $local_idf_map, $documents, $local_idf_reason, $local_idf_source );

    foreach ( $documents as $doc ) {
        $pid = isset( $doc['post_id'] ) ? (int) $doc['post_id'] : 0;
        $chunks = array();
        if ( ! empty( $doc['chunks'] ) && is_array( $doc['chunks'] ) ) {
            $chunks = $doc['chunks'];
        } elseif ( ! empty( $doc['normalized_text'] ) ) {
            $chunks = transformer_model_lexical_context_split_into_sentence_chunks( $doc['normalized_text'] );
        }

        $raw_chunks = $chunks;
        $filtered   = array();
        foreach ( $chunks as $sentence ) {
            $trimmed = trim( $sentence );
            if ( $trimmed === '' ) {
                continue;
            }
            if ( ! transformer_model_lexical_context_is_low_value_chunk( $trimmed ) ) {
                $filtered[] = $trimmed;
            }
        }

        if ( empty( $filtered ) && ! empty( $raw_chunks ) ) {
            foreach ( $raw_chunks as $sentence ) {
                $t = trim( $sentence );
                if ( $t !== '' ) {
                    $filtered[] = $t;
                }
            }
        }

        $chunks = $filtered;

        foreach ( $chunks as $sentence ) {
            $trimmed = trim( $sentence );
            if ( ! empty( $meaningful_query_tokens ) ) {
                if ( ! transformer_model_lexical_context_chunk_has_meaningful_query_overlap( $trimmed, $meaningful_query_tokens ) ) {
                    continue;
                }
                $corpus_had_meaningful_overlap = true;
            }

            if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() ) {
                $lcm_pre_scoring_candidates[] = array(
                    'sentence'   => $trimmed,
                    'post_id'    => $pid,
                    'post_title' => isset( $doc['post_title'] ) ? (string) $doc['post_title'] : '',
                    'score'      => null,
                );
            }

            $row = transformer_model_lexical_context_lexical_sentence_score_row( $trimmed, $searchWordsLower, $inputWordsLower, $local_idf_map, $apply_local_idf, $intent_precomputed );
            if ( $row !== null ) {
                $row['post_id'] = $pid;
                $sentenceScores[] = $row;
            }
        }
    }

    transformer_model_lexical_context_diag_log_pipeline_stage( 'pre_scoring_candidates', $lcm_pre_scoring_candidates, $post_title_map );

    if ( empty( $sentenceScores ) ) {
        if ( ! empty( $meaningful_query_tokens ) && ! $corpus_had_meaningful_overlap ) {
            return transformer_model_lexical_context_no_query_overlap_message();
        }

        return '';
    }

    transformer_model_lexical_context_diag_log_pipeline_stage( 'after_scoring', $sentenceScores, $post_title_map );

    $sentenceScores = transformer_model_lexical_context_sort_sentence_scores_with_document_priority( $sentenceScores );
    // Conservative cross-document gate: only include weaker posts if their document-level max score is within 85% of the best post’s max.
    $after_doc_gate = transformer_model_lexical_context_filter_sentence_scores_cross_document_gate( $sentenceScores, 0.85 );
    // Row-level gate: keep chunks near the best chunk score; drops weak filler when a strong match exists.
    $after_row_gate = transformer_model_lexical_context_filter_sentence_scores_row_gate( $after_doc_gate, 0.65 );

    transformer_model_lexical_context_diag_log_pipeline_stage( 'after_document_gate', $after_doc_gate, $post_title_map );
    transformer_model_lexical_context_diag_log_pipeline_stage( 'after_row_gate', $after_row_gate, $post_title_map );

    // Enrich from the top-ranked document only (up to sentence response cap), including next-best same-post chunks.
    $sentenceScores = transformer_model_lexical_context_merge_top_document_expansion(
        $after_doc_gate,
        $after_row_gate,
        0.65,
        $sentenceResponseCount
    );

    transformer_model_lexical_context_diag_log_pipeline_stage( 'after_expansion', $sentenceScores, $post_title_map );

    $sentenceScores = transformer_model_lexical_context_deduplicate_near_duplicate_sentence_rows( $sentenceScores );

    transformer_model_lexical_context_diag_log_pipeline_stage( 'after_deduplication', $sentenceScores, $post_title_map );

    return transformer_model_lexical_context_assemble_response_from_scored_sentences(
        $sentenceScores,
        $maxWords,
        $sentenceResponseCount,
        $similarityThreshold,
        $leadingSentencesRatio,
        $leadingTokenRatio
    );
}

// Function to build sentences from corpus using query words (legacy single-string corpus).
function transformer_model_lexical_context_build_sentences_from_corpus( $corpus, $searchWords, $inputWords, $maxWords, $sentenceResponseCount = 5, $similarityThreshold = 0.3, $leadingSentencesRatio = 0.2, $leadingTokenRatio = 0.2 ) {

    $documents = array(
        array(
            'post_id'         => 0,
            'post_title'      => '',
            'post_type'       => 'legacy',
            'permalink'       => '',
            'normalized_text' => $corpus,
            'chunks'          => transformer_model_lexical_context_split_into_sentence_chunks( $corpus ),
        ),
    );

    return transformer_model_lexical_context_build_sentences_from_documents(
        $documents,
        $searchWords,
        $inputWords,
        $maxWords,
        $sentenceResponseCount,
        $similarityThreshold,
        $leadingSentencesRatio,
        $leadingTokenRatio,
        ''
    );
}

/**
 * Stop words removed when comparing sentences for near-duplicate detection.
 *
 * @return array<int, string>
 */
function transformer_model_lexical_context_dedup_normalization_stop_words() {

    return array(
        'the', 'a', 'an', 'to', 'of', 'for', 'in', 'on', 'at', 'by', 'with', 'from', 'as', 'into', 'onto',
        'and', 'or', 'but', 'nor', 'so', 'yet', 'both', 'either', 'neither', 'not', 'only', 'same', 'such',
        'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
        'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'shall',
        'this', 'that', 'these', 'those', 'it', 'its', 'they', 'them', 'their', 'we', 'you', 'he', 'she',
        'what', 'which', 'who', 'whom', 'whose', 'how', 'when', 'where', 'why',
        'if', 'then', 'than', 'too', 'very', 'just', 'also', 'even', 'still', 'once',
        'here', 'there', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'any', 'all',
    );
}

/**
 * Meaningful tokens for deduplication (lowercase, no punctuation, stop words removed).
 *
 * @param string $text
 * @return array<int, string>
 */
function transformer_model_lexical_context_normalize_sentence_to_dedup_tokens( $text ) {

    $text = strtolower( wp_strip_all_tags( (string) $text ) );
    $text = preg_replace( '/[^\p{L}\p{N}\s]/u', ' ', $text );
    $text = preg_replace( '/\s+/', ' ', trim( $text ) );

    $words = preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
    if ( empty( $words ) ) {
        return array();
    }

    $stop = array_flip( transformer_model_lexical_context_dedup_normalization_stop_words() );
    $out  = array();

    foreach ( $words as $w ) {
        if ( strlen( $w ) < 2 ) {
            continue;
        }
        if ( isset( $stop[ $w ] ) ) {
            continue;
        }
        $out[] = $w;
    }

    return $out;
}

/**
 * Whether two sentences are near-duplicates by containment or high token overlap on meaningful tokens.
 *
 * @param string $a Raw sentence text.
 * @param string $b Raw sentence text.
 * @return bool
 */
function transformer_model_lexical_context_are_sentences_near_duplicates( $a, $b ) {

    $a = trim( (string) $a );
    $b = trim( (string) $b );

    if ( $a === '' || $b === '' ) {
        return false;
    }

    if ( strcasecmp( $a, $b ) === 0 ) {
        return true;
    }

    $tok_a = transformer_model_lexical_context_normalize_sentence_to_dedup_tokens( $a );
    $tok_b = transformer_model_lexical_context_normalize_sentence_to_dedup_tokens( $b );

    $norm_a = implode( ' ', $tok_a );
    $norm_b = implode( ' ', $tok_b );

    if ( $norm_a !== '' && $norm_b !== '' ) {
        if ( $norm_a === $norm_b ) {
            return true;
        }
        if ( strpos( $norm_a, $norm_b ) !== false || strpos( $norm_b, $norm_a ) !== false ) {
            return true;
        }
    }

    if ( empty( $tok_a ) || empty( $tok_b ) ) {
        $fa = preg_replace( '/[^\p{L}\p{N}]/u', '', strtolower( wp_strip_all_tags( $a ) ) );
        $fb = preg_replace( '/[^\p{L}\p{N}]/u', '', strtolower( wp_strip_all_tags( $b ) ) );
        if ( strlen( $fa ) >= 4 && strlen( $fb ) >= 4 ) {
            return ( strpos( $fa, $fb ) !== false || strpos( $fb, $fa ) !== false );
        }

        return false;
    }

    $set_a = array_unique( $tok_a );
    $set_b = array_unique( $tok_b );
    $inter = count( array_intersect( $set_a, $set_b ) );
    $min_c = min( count( $set_a ), count( $set_b ) );

    if ( $min_c > 0 && ( $inter / $min_c ) >= 0.75 ) {
        return true;
    }

    return false;
}

/**
 * Drop near-duplicate scored rows; keeps higher score, then longer sentence on ties (sort order).
 *
 * @param array<int, array<string, mixed>> $rows
 * @return array<int, array<string, mixed>>
 */
function transformer_model_lexical_context_deduplicate_near_duplicate_sentence_rows( $rows ) {

    if ( empty( $rows ) ) {
        return $rows;
    }

    usort(
        $rows,
        function ( $a, $b ) {
            $sa = isset( $a['score'] ) ? (float) $a['score'] : 0.0;
            $sb = isset( $b['score'] ) ? (float) $b['score'] : 0.0;
            if ( $sa !== $sb ) {
                return $sb <=> $sa;
            }
            $la = strlen( isset( $a['sentence'] ) ? (string) $a['sentence'] : '' );
            $lb = strlen( isset( $b['sentence'] ) ? (string) $b['sentence'] : '' );

            return $lb <=> $la;
        }
    );

    $kept = array();

    foreach ( $rows as $row ) {
        $sentence = isset( $row['sentence'] ) ? trim( (string) $row['sentence'] ) : '';
        if ( $sentence === '' ) {
            continue;
        }

        $tok_new = transformer_model_lexical_context_normalize_sentence_to_dedup_tokens( $sentence );
        $join_new = implode( ' ', $tok_new );

        // Longer sentence subsumes a shorter prefix already kept (same topic, more detail).
        if ( $join_new !== '' ) {
            foreach ( $kept as $ki => $existing ) {
                $ex = isset( $existing['sentence'] ) ? trim( (string) $existing['sentence'] ) : '';
                if ( $ex === '' ) {
                    continue;
                }
                $join_ex = implode( ' ', transformer_model_lexical_context_normalize_sentence_to_dedup_tokens( $ex ) );
                if ( $join_ex !== '' && strpos( $join_new, $join_ex ) !== false && strlen( $sentence ) > strlen( $ex ) ) {
                    unset( $kept[ $ki ] );
                }
            }
            $kept = array_values( $kept );
        }

        $skip = false;
        foreach ( $kept as $existing ) {
            $ex = isset( $existing['sentence'] ) ? trim( (string) $existing['sentence'] ) : '';
            if ( $ex !== '' && transformer_model_lexical_context_are_sentences_near_duplicates( $sentence, $ex ) ) {
                $skip = true;
                break;
            }
        }

        if ( ! $skip ) {
            $kept[] = $row;
        }
    }

    return $kept;
}

/**
 * Build stitched response from sorted/filtered sentence score rows.
 *
 * @param array<int, array<string, mixed>> $sentenceScores
 * @return string
 */
function transformer_model_lexical_context_assemble_response_from_scored_sentences( $sentenceScores, $maxWords, $sentenceResponseCount, $similarityThreshold, $leadingSentencesRatio, $leadingTokenRatio ) {

    // Filter out lower quality matches using similarity threshold from settings
    // Calculate quality threshold based on top score and similarity threshold setting
    $topScore = !empty($sentenceScores) ? $sentenceScores[0]['score'] : 0;
    $hasInputMatches = !empty($sentenceScores) && $sentenceScores[0]['inputMatched'] > 0;
    
    // Use similarity threshold to determine quality threshold
    // Convert similarity threshold (0-1) to score threshold (percentage of top score)
    $scoreThresholdRatio = $similarityThreshold; // Use similarity threshold as ratio
    $qualityThreshold = max(
        $topScore * $scoreThresholdRatio, // Percentage of top score based on similarity threshold
        $hasInputMatches ? 1 : 5 // Minimum threshold
    );
    
    // Filter sentences by quality threshold
    $qualitySentences = array_filter($sentenceScores, function($item) use ($qualityThreshold) {
        return $item['score'] >= $qualityThreshold;
    });
    
    // Re-index array after filtering
    $qualitySentences = array_values($qualitySentences);
    
    // Use sentence response count from settings
    $maxSentences = min($sentenceResponseCount, count($qualitySentences));
    
    // Calculate token distribution using leading ratios
    $tokensBefore = floor($maxWords * $leadingTokenRatio);
    $tokensAfter = $maxWords - $tokensBefore;
    $sentencesBefore = floor($maxSentences * $leadingSentencesRatio);
    $sentencesAfter = $maxSentences - $sentencesBefore;
    
    // Build response from top quality sentences
    // Use sentence response count and token ratios to control response length and structure
    $response = '';
    $wordCount = 0;
    
    // Track usage
    $tokensUsedBefore = 0;
    $tokensUsedAfter = 0;
    $sentencesAdded = 0;
    $sentencesAddedBefore = 0;
    
    // Always start with the best matching sentence (index 0)
    // But first check if it has significant matches - if not, we might want to skip it
    if (!empty($qualitySentences)) {
        $bestSentence = $qualitySentences[0];
        $hasSignificantContent = isset($bestSentence['hasSignificantMatch']) && $bestSentence['hasSignificantMatch'];
        $hasInputWords = isset($bestSentence['inputMatched']) && $bestSentence['inputMatched'] > 0;
        
        // Only use the best sentence if it has significant matches or input words
        // This prevents returning generic "what is" questions
        if ($hasSignificantContent || $hasInputWords) {
            $sentence = trim($bestSentence['sentence']);
            if (!empty($sentence)) {
                // Ensure sentence ends with punctuation
                if (!preg_match('/[.!?]$/', $sentence)) {
                    $sentence .= '.';
                }
                $response = $sentence;
                $wordCount = str_word_count($sentence);
                $tokensUsedAfter += $wordCount; // Count best match as part of "after" tokens
                $sentencesAdded = 1;
            }
        } else {
            // Best sentence doesn't have significant matches - return empty to trigger fallback
            return '';
        }
    }
    
    // Add additional sentences from the sorted list (they're already sorted by relevance)
    // Use the ratios to determine how many tokens/sentences to allocate
    // Prefer adding fewer, higher quality sentences for tighter responses
    for ($i = 1; $i < count($qualitySentences) && $sentencesAdded < $maxSentences && $wordCount < $maxWords; $i++) {
        $sentence = trim($qualitySentences[$i]['sentence']);
        if (empty($sentence)) {
            continue;
        }
        
        $sentenceWordCount = isset($qualitySentences[$i]['wordCount']) 
            ? $qualitySentences[$i]['wordCount'] 
            : str_word_count($sentence);
        
        // Skip sentences that are too long (unless they're very high quality)
        // This helps keep responses tight and concise
        if ($sentenceWordCount > 35 && $qualitySentences[$i]['inputMatched'] == 0) {
            continue; // Skip long sentences without input word matches
        }
        
        // Check if we can add this sentence within our limits
        if ($wordCount + $sentenceWordCount <= $maxWords) {
            // For tighter responses, prefer adding after the best match (trailing context)
            // Only add leading context if the sentence is very relevant (has input words)
            $hasInputWords = isset($qualitySentences[$i]['inputMatched']) && $qualitySentences[$i]['inputMatched'] > 0;
            $shouldAddBefore = ($hasInputWords && $tokensUsedBefore < $tokensBefore && $sentencesAddedBefore < $sentencesBefore);
            
            // Ensure sentence ends with punctuation
            if (!preg_match('/[.!?]$/', $sentence)) {
                $sentence .= '.';
            }
            
            if ($shouldAddBefore) {
                $response = $sentence . ' ' . $response;
                $tokensUsedBefore += $sentenceWordCount;
                $sentencesAddedBefore++;
            } else {
                $response .= ' ' . $sentence;
                $tokensUsedAfter += $sentenceWordCount;
            }
            
            $wordCount += $sentenceWordCount;
            $sentencesAdded++;
            
            // Early stopping: if we have a very good match and reasonable length, stop
            // This prevents run-on responses
            if ($hasInputWords && $wordCount >= 50 && $sentencesAdded >= 2) {
                break; // Got good matches, stop here for tighter response
            }
        } else {
            break; // Can't fit more sentences
        }
    }
    
    return trim($response);
}

// Function to build structured response from words when corpus sentences aren't available
function transformer_model_lexical_context_build_structured_response($topWords, $similarities, $stopWords, $maxWords) {
    
    // Take only the top words we need
    $words = array_slice($topWords, 0, $maxWords);
    
    if (empty($words)) {
        return '';
    }
    
    // Group words into phrases/sentences
    $response = '';
    $currentPhrase = [];
    $phraseLength = 0;
    $targetPhraseLength = wp_rand( 8, 15 ); // Variable phrase length for naturalness
    $stopWordsLower = is_array($stopWords) ? array_map('strtolower', $stopWords) : [];
    
    foreach ($words as $index => $word) {
        $wordLower = strtolower($word);
        
        // Check if we should end the phrase
        $shouldEndPhrase = false;
        
        // End phrase if we've reached target length
        if ($phraseLength >= $targetPhraseLength) {
            $shouldEndPhrase = true;
        }
        
        // End phrase if we hit certain words that often end phrases
        $phraseEnders = ['said', 'know', 'think', 'use', 'make', 'work', 'help', 'find', 'create', 'build'];
        if (in_array($wordLower, $phraseEnders) && $phraseLength >= 5) {
            $shouldEndPhrase = true;
        }
        
        $currentPhrase[] = $word;
        $phraseLength++;
        
        if ($shouldEndPhrase || $index == count($words) - 1) {
            // Build the phrase
            $phrase = implode(' ', $currentPhrase);
            
            if (!empty($response)) {
                $response .= ' ';
            }
            $response .= $phrase;
            
            // Add punctuation (period, comma, or question mark based on position)
            if ($index < count($words) - 1) {
                // Not the last phrase - use comma or period randomly
                $response .= (wp_rand( 0, 2 ) === 0 ? ',' : '.');
            } else {
                // Last phrase - always end with period
                $response .= '.';
            }
            
            // Reset for next phrase
            $currentPhrase = [];
            $phraseLength = 0;
            $targetPhraseLength = wp_rand( 8, 15 );
        }
    }
    
    return trim($response);
}

// Function to format and clean up the response
function transformer_model_lexical_context_format_response($response) {
    
    if (empty($response)) {
        return "I couldn't generate a meaningful response. Please try again.";
    }
    
    // Remove extra spaces
    $response = preg_replace('/\s+/', ' ', $response);
    
    // Fix spacing around punctuation
    $response = preg_replace('/\s+([,.!?;:])/', '$1', $response); // Remove space before punctuation
    $response = preg_replace('/([,.!?;:])([^\s])/', '$1 $2', $response); // Add space after punctuation if missing
    
    // Capitalize first letter
    $response = trim($response);
    if (!empty($response)) {
        $response = ucfirst($response);
        
        // Ensure it ends with punctuation
        if (!preg_match('/[.!?]$/', $response)) {
            $response .= '.';
        }
        
        // Capitalize after sentence endings
        $response = preg_replace_callback('/([.!?]\s+)([a-z])/', function($matches) {
            return $matches[1] . strtoupper($matches[2]);
        }, $response);
    }
    
    return $response;
}

// Trim off any stop words from the end of the response
function removeStopWordFromEnd($response, $stopWords) {

    // Safety check
    if (empty($response)) {
        return $response;
    }

    if (!is_array($stopWords) || empty($stopWords)) {
        return $response;
    }

    // Normalize stop words to lowercase for comparison
    $stopWordsLower = array_map('strtolower', $stopWords);
    
    // Split the response into words
    $responseWords = preg_split('/\s+/', rtrim($response, " \t\n\r\0\x0B.,!?;:"));
    
    if (empty($responseWords)) {
        return $response;
    }

    // Limit recursion depth to prevent infinite loops
    $maxIterations = 10;
    $iterations = 0;
    
    while ($iterations < $maxIterations && !empty($responseWords)) {
        $lastWord = strtolower(end($responseWords));
        
        // Check if the last word is a stop word
        if (in_array($lastWord, $stopWordsLower, true)) {
            array_pop($responseWords); // Remove the last word
            $iterations++;
        } else {
            break; // Found a non-stop word, exit loop
        }
    }

    // Reconstruct the response
    $response = implode(' ', $responseWords);
    
    return $response;

}
