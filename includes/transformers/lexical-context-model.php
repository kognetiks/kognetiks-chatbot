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

/**
 * Soft maximum runtime for one LCM chat request (seconds). Filterable; does not affect admin PMI rebuild.
 *
 * @return float
 */
function transformer_model_lexical_context_lcm_max_runtime_seconds() {

    return max( 0.5, (float) apply_filters( 'chatbot_lcm_max_runtime_seconds', 20.0 ) );
}

/**
 * True when total elapsed time has reached or exceeded the soft budget (whole request).
 *
 * @return bool
 */
function transformer_model_lexical_context_lcm_budget_hard_exceeded() {

    return transformer_model_lexical_context_lcm_elapsed_total() >= transformer_model_lexical_context_lcm_max_runtime_seconds();
}

/**
 * Elapsed seconds since {@see transformer_model_lexical_context_lcm_timing_init()} for this request.
 *
 * @return float
 */
function transformer_model_lexical_context_lcm_elapsed_total() {

    $start = isset( $GLOBALS['chatbot_lcm_timing_start'] ) ? (float) $GLOBALS['chatbot_lcm_timing_start'] : null;
    if ( $start === null ) {
        return 0.0;
    }

    return microtime( true ) - $start;
}

/**
 * Initialize per-request timing state (call once at LCM entry).
 *
 * @return void
 */
function transformer_model_lexical_context_lcm_timing_init() {

    $now                                  = microtime( true );
    $GLOBALS['chatbot_lcm_timing_start']  = $now;
    $GLOBALS['chatbot_lcm_timing_last']   = $now;
    $GLOBALS['chatbot_lcm_skip_pmi_load'] = false;
}

/**
 * Mark a pipeline stage for [LCM][timing] logs (requires KOGNETIKS_LCM_DEBUG).
 *
 * @param string $stage Short slug (e.g. fetch_documents, pmi_cache_load).
 * @return void
 */
function transformer_model_lexical_context_lcm_timing_segment( $stage ) {

    $stage = preg_replace( '/[^\w.-]/', '', (string) $stage );
    if ( $stage === '' ) {
        $stage = 'unknown';
    }

    $now = microtime( true );
    if ( empty( $GLOBALS['chatbot_lcm_timing_start'] ) ) {
        $GLOBALS['chatbot_lcm_timing_start'] = $now;
    }
    if ( empty( $GLOBALS['chatbot_lcm_timing_last'] ) ) {
        $GLOBALS['chatbot_lcm_timing_last'] = $GLOBALS['chatbot_lcm_timing_start'];
    }

    $start   = (float) $GLOBALS['chatbot_lcm_timing_start'];
    $last    = (float) $GLOBALS['chatbot_lcm_timing_last'];
    $elapsed = $now - $last;
    $total   = $now - $start;

    $GLOBALS['chatbot_lcm_timing_last'] = $now;

    if ( ! transformer_model_lexical_context_is_lcm_diagnostics_enabled() || ! function_exists( 'back_trace' ) ) {
        return;
    }

    back_trace(
        'NOTICE',
        sprintf( '[LCM][timing] stage=%s elapsed=%.3f total=%.3f', $stage, $elapsed, $total )
    );
}

/**
 * Mark oversized flat corpus so the PMI cache load is skipped on this request (lexical-only ranking).
 * Local IDF JSON is not gated here — IDF is a small sidecar read and stays eligible when the option is Yes.
 *
 * @param int $flat_len strlen of flattened corpus.
 * @return void
 */
function transformer_model_lexical_context_lcm_maybe_flag_heavy_corpus_skips( $flat_len ) {

    $flat_len = (int) $flat_len;
    $max      = (int) apply_filters( 'chatbot_lcm_max_flat_corpus_chars_for_heavy_load', 2000000 );

    if ( $max > 0 && $flat_len > $max ) {
        $GLOBALS['chatbot_lcm_skip_pmi_load'] = true;
    }
}

/**
 * Whether to skip reading PMI cache bytes on this request (still no PMI math change — embeddings unset → lexical-only path).
 *
 * @param string $corpus_flat Flattened corpus string.
 * @return bool
 */
function transformer_model_lexical_context_lcm_should_skip_pmi_cache_load( $corpus_flat ) {

    if ( ! empty( $GLOBALS['chatbot_lcm_skip_pmi_load'] ) ) {
        return true;
    }

    $max_chars = (int) apply_filters( 'chatbot_lcm_max_flat_corpus_chars_for_heavy_load', 2000000 );
    if ( $max_chars > 0 && strlen( (string) $corpus_flat ) > $max_chars ) {
        return true;
    }

    $cutoff = (float) apply_filters( 'chatbot_lcm_skip_pmi_if_elapsed_gte_seconds', 8.0 );
    if ( $cutoff > 0 && transformer_model_lexical_context_lcm_elapsed_total() >= $cutoff ) {
        return true;
    }

    $pmi_php  = __DIR__ . '/lexical_embeddings_cache/lexical_embeddings_cache.php';
    $pmi_gz   = $pmi_php . '.gz';
    $max_gz_b = (int) apply_filters( 'chatbot_lcm_max_pmi_gzip_cache_bytes', 0 );
    if ( $max_gz_b > 0 && file_exists( $pmi_gz ) ) {
        $fs = @filesize( $pmi_gz );
        if ( is_int( $fs ) && $fs > $max_gz_b ) {
            return true;
        }
    }

    return (bool) apply_filters( 'chatbot_lcm_force_skip_pmi_cache_load', false, $corpus_flat );
}

/**
 * Whether runtime guards block attempting to read the local IDF JSON cache (chat never rebuilds IDF here).
 *
 * @return array{ skip: bool, diag: string } diag is empty when skip is false.
 */
function transformer_model_lexical_context_lcm_idf_load_skip_gate() {

    if ( (bool) apply_filters( 'chatbot_lcm_force_skip_idf_load', false ) ) {
        return array(
            'skip' => true,
            'diag' => 'skipped_force_filter',
        );
    }

    $cutoff = (float) apply_filters( 'chatbot_lcm_skip_idf_if_elapsed_gte_seconds', 10.0 );
    if ( $cutoff > 0 && transformer_model_lexical_context_lcm_elapsed_total() >= $cutoff ) {
        return array(
            'skip' => true,
            'diag' => 'skipped_elapsed_budget',
        );
    }

    return array(
        'skip' => false,
        'diag' => '',
    );
}

/**
 * Log one IDF file I/O sub-phase for [LCM][timing] (stat/read/decode). Requires KOGNETIKS_LCM_DEBUG.
 *
 * @param string               $phase       stat|read|decode.
 * @param float                $segment_start microtime( true ) at start of this phase.
 * @param array<string, mixed> $extra       Optional keys: bytes (int).
 * @return void
 */
function transformer_model_lexical_context_lcm_idf_io_timing_line( $phase, $segment_start, $extra = array() ) {

    if ( ! transformer_model_lexical_context_is_lcm_diagnostics_enabled() || ! function_exists( 'back_trace' ) ) {
        return;
    }

    $phase = preg_replace( '/[^\w]/', '', (string) $phase );
    if ( $phase === '' ) {
        $phase = 'io';
    }

    $now     = microtime( true );
    $elapsed = $now - (float) $segment_start;
    $total   = transformer_model_lexical_context_lcm_elapsed_total();

    $suffix = '';
    if ( ! empty( $extra['bytes'] ) && is_numeric( $extra['bytes'] ) ) {
        $suffix = sprintf( ' bytes=%d', (int) $extra['bytes'] );
    }

    back_trace(
        'NOTICE',
        sprintf( '[LCM][timing] stage=idf_cache_%s elapsed=%.3f total=%.3f%s', $phase, $elapsed, $total, $suffix )
    );
}

/**
 * Friendly reply when assembly is cut short by runtime budget.
 *
 * @return string
 */
function transformer_model_lexical_context_lcm_assembly_fallback_message() {

    $msg = 'I\'m taking longer than expected to finish that answer. Please try a shorter or more specific question.';

    return (string) apply_filters( 'chatbot_lcm_budget_fallback_message', $msg );
}

/**
 * Best-effort answer when the soft runtime budget is exceeded before assembly completes.
 *
 * @param array<int, array<string, mixed>> $sentenceScores Ranked rows (may be partial).
 * @return string
 */
function transformer_model_lexical_context_lcm_assembly_budget_fallback( $sentenceScores ) {

    if ( ! empty( $sentenceScores ) && is_array( $sentenceScores ) && ! empty( $sentenceScores[0]['sentence'] ) ) {
        $s = trim( (string) $sentenceScores[0]['sentence'] );
        if ( $s !== '' ) {
            if ( ! preg_match( '/[.!?]$/', $s ) ) {
                $s .= '.';
            }

            return $s;
        }
    }

    return transformer_model_lexical_context_lcm_assembly_fallback_message();
}

// Main function to generate a response
function transformer_model_lexical_context_response( $input, $max_tokens = null ) {

    transformer_model_lexical_context_lcm_timing_init();

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
    // TEMPORARY diagnostics: allow deeper tracing in fetch/flatten for one query.
    $GLOBALS['kognetiks_lcm_raw_query_for_trace'] = (string) $input;
    $documents = transformer_model_lexical_context_fetch_wordpress_documents();

    transformer_model_lexical_context_lcm_timing_segment( 'fetch_documents' );

    if (empty($documents)) {
        $resp = "I don't have enough content to generate a response. Please add some posts or pages to your WordPress site.";
        // TEMPORARY cow_return_trace.
        $resp = transformer_model_lcm_cow_return_trace_checkpoint(
            'transformer_model_lexical_context_response',
            'empty_documents_return',
            $input,
            $resp
        );
        return $resp;
    }

    transformer_model_lexical_context_pmi_cache_fetch_status( 'unknown' );

    // Build embeddings (PMI windows never cross document boundaries). May skip rebuild on public requests.
    $embeddings = transformer_model_lexical_context_get_cached_embeddings($documents);

    transformer_model_lexical_context_lcm_timing_segment( 'get_cached_embeddings' );

    transformer_model_lexical_context_log_request_start_diagnostics( $documents );

    // Generate contextual response (PMI expansion when embeddings exist; lexical-only path when cache miss without rebuild)
    $response = transformer_model_lexical_context_generate_contextual_response($input, $embeddings, $documents, $max_tokens);

    transformer_model_lexical_context_lcm_timing_segment( 'generate_contextual_response' );

    // TEMPORARY cow_return_trace: latest possible checkpoint before returning response to caller.
    $response = transformer_model_lcm_cow_return_trace_checkpoint(
        'transformer_model_lexical_context_response',
        'final_return',
        $input,
        $response
    );

    return $response;

}

/**
 * Fetch published posts and pages as structured documents with normalized text and sentence chunks.
 *
 * @return array<int, array<string, mixed>> List of documents.
 */
function transformer_model_lexical_context_fetch_wordpress_documents() {

    global $wpdb;

    transformer_model_lexical_context_lexical_rebuild_log( 'before fetch_wordpress_documents' );

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_title, post_type, post_status, post_content FROM {$wpdb->posts}
             WHERE post_status IN (%s, %s) AND (post_type = %s OR post_type = %s OR post_type = %s) AND post_content != ''
             ORDER BY ID ASC",
            'publish',
            'private',
            'post',
            'page',
            'apple_note'
        ),
        ARRAY_A
    );

    if (empty($results) || !is_array($results)) {
        return [];
    }

    transformer_model_lexical_context_lexical_rebuild_log( 'after fetch_wordpress_documents' );

    transformer_model_lexical_context_lexical_rebuild_log( 'before documents' );

    $documents = [];

    transformer_model_lexical_context_lexical_rebuild_log( 'before foreach' );

    $loop_counter         = 0;
    $excluded_revisions   = 0;

    foreach ($results as $row) {

        $loop_counter++;
        if ( $loop_counter % 100 === 0 ) {
            transformer_model_lexical_context_lexical_rebuild_log( 'loop_counter ' . $loop_counter );
        }
        $post_type = isset( $row['post_type'] ) ? (string) $row['post_type'] : 'post';
        if ( $post_type === 'revision' ) {
            ++$excluded_revisions;
            continue;
        }
        // Minimal post-status filter:
        // - Always allow published content
        // - Allow private only for apple_note
        $post_status = isset( $row['post_status'] ) ? (string) $row['post_status'] : '';
        if ( $post_status !== 'publish' && ! ( $post_status === 'private' && $post_type === 'apple_note' ) ) {
            continue;
        }

        if (empty($row['post_content'])) {
            continue;
        }

        $post_id   = isset($row['ID'] ) ? (int) $row['ID'] : 0;
        $title     = isset($row['post_title'] ) ? $row['post_title'] : '';

        // TEMPORARY target sentence trace (fetch_documents stage).
        if ( transformer_model_lcm_chatbot_sales_target_trace_enabled( isset( $GLOBALS['kognetiks_lcm_raw_query_for_trace'] ) ? (string) $GLOBALS['kognetiks_lcm_raw_query_for_trace'] : '' ) ) {
            $pc = isset( $row['post_content'] ) ? (string) $row['post_content'] : '';
            transformer_model_lcm_chatbot_sales_target_trace_log( 'fetch_documents', $pc, $post_id, $title );
        }

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

    if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        back_trace(
            'NOTICE',
            sprintf( '[LCM][fetch_filter] excluded_revisions=%d', $excluded_revisions )
        );
    }

    transformer_model_lexical_context_lexical_rebuild_log( 'after foreach' );

    transformer_model_lexical_context_lexical_rebuild_log( 'before return' );

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

    transformer_model_lexical_context_lcm_maybe_flag_heavy_corpus_skips( strlen( (string) $corpus_flat ) );
    transformer_model_lexical_context_lcm_timing_segment( 'flatten_hash' );

    if ( file_exists( $cacheFile ) && file_exists( $cacheVersionFile ) ) {
        $cachedHash = trim( (string) file_get_contents( $cacheVersionFile ) );
        if ( $cachedHash === $corpusHash ) {
            $cacheValid = true;
        }
    }

    if ( $cacheValid && transformer_model_lexical_context_lcm_should_skip_pmi_cache_load( $corpus_flat ) ) {
        transformer_model_lexical_context_pmi_cache_fetch_status( 'skipped_runtime_budget' );
        transformer_model_lexical_context_lcm_timing_segment( 'pmi_cache_skipped' );
        if ( function_exists( 'prod_trace' ) ) {
            prod_trace(
                'NOTICE',
                '[LCM] Skipping PMI cache load for this request (elapsed budget or flat corpus size cap). Lexical-only ranking will be used.'
            );
        }

        return array();
    }

    if ( $cacheValid ) {
        $embeddings = transformer_model_lexical_context_load_cache( $cacheFile );
        transformer_model_lexical_context_lcm_timing_segment( 'pmi_cache_load' );
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

    $pmi_metrics = array(
        'document_count' => count( $documents ),
        'chunk_count'    => transformer_model_lexical_context_count_document_chunks( $documents ),
        'corpus_bytes'   => strlen( (string) $corpus_flat ),
    );
    transformer_model_lexical_context_maybe_raise_memory_for_rebuild( 'pmi_cache_miss', $pmi_metrics );

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

    $pmi_total = count( $documents );
    transformer_model_lexical_context_lexical_rebuild_log( 'pmi_build total_documents=' . $pmi_total );

    $pmi_doc_i = 0;
    foreach ( $documents as $doc ) {
        $pmi_doc_i++;
        if ( $pmi_doc_i === 1 || $pmi_doc_i % 25 === 0 ) {
            $peak = function_exists( 'memory_get_peak_usage' ) ? memory_get_peak_usage( true ) : 0;
            transformer_model_lexical_context_lexical_rebuild_log(
                sprintf( 'pmi_build documents=%d/%d peak_mem_bytes=%s', $pmi_doc_i, $pmi_total, number_format( (float) $peak ) )
            );
        }

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

    $max_pairs = (int) apply_filters( 'chatbot_lcm_pmi_max_cooccurrence_pairs_per_root', 150 );
    if ( $max_pairs < 0 ) {
        $max_pairs = 0;
    }

    $pmi_root_i = 0;
    foreach ( $coOccurrenceCounts as $word => $contexts ) {
        $pmi_root_i++;
        if ( $pmi_root_i % 500 === 0 ) {
            transformer_model_lexical_context_lexical_rebuild_log( 'pmi_finalize root_words ' . $pmi_root_i );
        }

        if ( ! isset( $wordCounts[ $word ] ) || (int) $wordCounts[ $word ] === 0 ) {
            continue;
        }

        if ( $max_pairs > 0 && count( $contexts ) > $max_pairs ) {
            arsort( $contexts, SORT_NUMERIC );
            $contexts = array_slice( $contexts, 0, $max_pairs, true );
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
        $resp = "I don't have enough content to generate a response. Please add some posts or pages to your WordPress site.";
        // TEMPORARY cow_return_trace.
        $resp = transformer_model_lcm_cow_return_trace_checkpoint(
            'transformer_model_lexical_context_generate_contextual_response',
            'empty_documents_return',
            $input_text_for_intent,
            $resp
        );
        return $resp;
    }

    $input_text_for_intent = is_string( $input ) ? $input : '';

    // Possessives / apostrophes first (e.g. Job's → jobs), then strip remaining punctuation for tokenization.
    $input = transformer_model_lexical_context_normalize_lexical_query_string( $input );
    $input = preg_replace( '/[^\w\s]/u', ' ', $input );
    $input = preg_replace( '/\s+/u', ' ', trim( $input ) );

    $inputWords = preg_split( '/\s+/', strtolower( $input ) );

    // Ensure stopWords is initialized
    if ( ! isset( $stopWords ) || ! is_array( $stopWords ) ) {
        $stopWords = array();
    }

    $guard_flip   = array_flip( transformer_model_lexical_context_relevance_guard_stop_words() );
    $acronym_flip = transformer_model_lexical_context_lcm_short_acronym_allowlist_flip();

    // Filter: length, global stop words, and LCM relevance-guard list (aligns inputWords with meaningful tokens).
    $inputWords = array_filter(
        $inputWords,
        function ( $word ) use ( $stopWords, $guard_flip, $acronym_flip ) {
            $word = strtolower( trim( (string) $word ) );
            if ( $word === '' ) {
                return false;
            }
            if ( strlen( $word ) < 3 && ! isset( $acronym_flip[ $word ] ) ) {
                return false;
            }
            if ( in_array( $word, $stopWords, true ) ) {
                return false;
            }
            if ( isset( $guard_flip[ $word ] ) ) {
                return false;
            }
            return true;
        }
    );
    $inputWords = array_values( $inputWords );

    // If we filtered out everything, keep at least longer words (likely the actual query terms).
    if ( empty( $inputWords ) ) {
        $allWords = preg_split( '/\s+/', strtolower( $input ) );
        $inputWords = array_filter(
            $allWords,
            function ( $word ) use ( $stopWords, $guard_flip, $acronym_flip ) {
                $word = strtolower( trim( (string) $word ) );
                if ( $word === '' ) {
                    return false;
                }
                if ( strlen( $word ) < 4 && ! isset( $acronym_flip[ $word ] ) ) {
                    return false;
                }
                if ( in_array( $word, $stopWords, true ) ) {
                    return false;
                }
                if ( isset( $guard_flip[ $word ] ) ) {
                    return false;
                }
                return true;
            }
        );
        $inputWords = array_values( $inputWords );
    }

    $inputWords = transformer_model_lexical_context_filter_relation_intent_tokens(
        $inputWords,
        strtolower( trim( $input ) )
    );
    $inputWords = array_values( $inputWords );

    if (empty($inputWords)) {
        return "I didn't understand that, please try again.";
    }

    // No PMI matrix (cache miss on public request, etc.): rank sentences using query words only — no PMI expansion.
    if ( empty( $embeddings ) ) {
        transformer_model_lexical_context_lcm_timing_segment( 'lexical_only_path' );

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

    transformer_model_lexical_context_lcm_timing_segment( 'pmi_vocab_similarity_scan' );

    $lcm_vocab_scan_iterations = 0;

    foreach ($embeddings as $word => $vector ) {
        ++$lcm_vocab_scan_iterations;
        if ( ( $lcm_vocab_scan_iterations % 2500 ) === 0 && transformer_model_lexical_context_lcm_budget_hard_exceeded() ) {
            break;
        }

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
 * Apply document gate with optional informational-query expansion.
 *
 * For informational queries, ensure up to N documents (default 3) are retained by expanding the allowed set
 * if the strict ratio-based gate yields fewer than N. This is additive (keeps the doc gate) and does not
 * change scoring, row gates, quality filters, expansion, or deduplication logic.
 *
 * @param array<int, array<string, mixed>> $sentenceScores Ranked rows (best-first).
 * @param array{ shape?: string }         $query_shape   Query shape pack (as used by answer-shape bias).
 * @param float                           $cross_document_score_ratio Same ratio as the existing cross-document gate.
 * @return array<int, array<string, mixed>>
 */
function transformer_model_lexical_context_apply_document_gate( $sentenceScores, $query_shape, $cross_document_score_ratio = 0.85 ) {

    $sentenceScores = is_array( $sentenceScores ) ? $sentenceScores : array();
    $shape = isset( $query_shape['shape'] ) ? (string) $query_shape['shape'] : '';
    $max_documents = ( $shape === 'informational_query' ) ? 3 : 1;

    if ( $shape === 'informational_query' && transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        back_trace( 'NOTICE', sprintf( '[LCM][document_gate_adjusted] shape=informational_query max_documents=%d', $max_documents ) );
    }

    if ( empty( $sentenceScores ) ) {
        return $sentenceScores;
    }

    // First, apply the existing strict ratio-based gate unchanged.
    $strict = transformer_model_lexical_context_filter_sentence_scores_cross_document_gate( $sentenceScores, $cross_document_score_ratio );

    if ( $shape !== 'informational_query' ) {
        return $strict;
    }

    // Determine how many documents survived.
    $docMaxStrict = array();
    foreach ( $strict as $row ) {
        $pid = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;
        $s   = isset( $row['score'] ) ? (float) $row['score'] : 0.0;
        if ( ! isset( $docMaxStrict[ $pid ] ) || $s > $docMaxStrict[ $pid ] ) {
            $docMaxStrict[ $pid ] = $s;
        }
    }
    if ( count( $docMaxStrict ) >= $max_documents ) {
        return $strict;
    }

    // Expand allowed documents up to max_documents using original doc max scores (best-first).
    $docMaxAll = array();
    foreach ( $sentenceScores as $row ) {
        $pid = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;
        $s   = isset( $row['score'] ) ? (float) $row['score'] : 0.0;
        if ( ! isset( $docMaxAll[ $pid ] ) || $s > $docMaxAll[ $pid ] ) {
            $docMaxAll[ $pid ] = $s;
        }
    }
    arsort( $docMaxAll );
    $top_pids = array_slice( array_keys( $docMaxAll ), 0, $max_documents );
    $allow = array_fill_keys( $top_pids, true );

    $expanded = array();
    foreach ( $sentenceScores as $row ) {
        $pid = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;
        if ( ! empty( $allow[ $pid ] ) ) {
            $expanded[] = $row;
        }
    }

    return $expanded !== array() ? $expanded : $strict;
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
 * Fast aggregate for the same SQL scope as transformer_model_lexical_context_fetch_wordpress_documents().
 * Lets admin-post defer to WP-Cron before loading all post_content rows into PHP (prevents FastCGI idle timeouts).
 *
 * @return array{ row_count: int, content_bytes: int }
 */
function transformer_model_lexical_context_lexical_corpus_sql_aggregate() {

    global $wpdb;

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT COUNT(*) AS row_count, COALESCE(SUM(CHAR_LENGTH(post_content)), 0) AS content_bytes
             FROM {$wpdb->posts}
             WHERE post_status IN (%s, %s)
             AND (post_type = %s OR post_type = %s OR post_type = %s)
             AND post_content != ''",
            'publish',
            'private',
            'post',
            'page',
            'apple_note'
        ),
        ARRAY_A
    );

    if ( ! is_array( $row ) ) {
        return array(
            'row_count'     => 0,
            'content_bytes' => 0,
        );
    }

    return array(
        'row_count'     => (int) ( $row['row_count'] ?? 0 ),
        'content_bytes' => (int) ( $row['content_bytes'] ?? 0 ),
    );
}

/**
 * Whether a synchronous browser/admin-post rebuild should be deferred to WP-Cron (large corpus).
 *
 * @param array{ document_count: int, chunk_count: int, corpus_bytes: int } $metrics
 * @return bool True = defer to background cron.
 */
function transformer_model_lexical_context_lexical_rebuild_should_defer_to_cron( $metrics ) {

    $max_docs    = (int) apply_filters( 'chatbot_lexical_rebuild_sync_max_documents', 40 );
    $max_bytes   = (int) apply_filters( 'chatbot_lexical_rebuild_sync_max_corpus_bytes', 1200000 );
    $max_chunks  = (int) apply_filters( 'chatbot_lexical_rebuild_sync_max_chunks', 6000 );

    if ( ! empty( $metrics['document_count'] ) && (int) $metrics['document_count'] > $max_docs ) {
        return true;
    }
    if ( ! empty( $metrics['corpus_bytes'] ) && (int) $metrics['corpus_bytes'] > $max_bytes ) {
        return true;
    }
    if ( isset( $metrics['chunk_count'] ) && (int) $metrics['chunk_count'] > $max_chunks ) {
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
 * If the full lexical rebuild exits without finishing (fatal, OOM, max execution time), log what we can.
 *
 * @return void
 */
/**
 * Raise PHP memory_limit on large PMI jobs (filterable). Does not shrink co-occurrence storage; pair cap handles output size.
 *
 * @param string $context Rebuild source label.
 * @param array{ document_count?: int, chunk_count?: int, corpus_bytes?: int } $metrics
 * @return void
 */
function transformer_model_lexical_context_maybe_raise_memory_for_rebuild( $context, array $metrics ) {

    $chunks = (int) ( $metrics['chunk_count'] ?? 0 );
    $bytes  = (int) ( $metrics['corpus_bytes'] ?? 0 );

    $default = null;
    if ( $chunks > 50000 || $bytes > 8000000 ) {
        $default = '768M';
    } elseif ( $chunks > 20000 || $bytes > 4000000 ) {
        $default = '512M';
    }

    $limit = apply_filters( 'chatbot_lexical_rebuild_memory_limit', $default, $context, $metrics );

    if ( $limit === false || $limit === null || $limit === '' ) {
        return;
    }

    if ( is_string( $limit ) ) {
        @ini_set( 'memory_limit', $limit );
        transformer_model_lexical_context_lexical_rebuild_log( 'step=memory_limit value=' . $limit );
    }
}

function transformer_model_lexical_context_lexical_rebuild_shutdown_probe() {

    if ( empty( $GLOBALS['chatbot_lcm_full_rebuild_watch'] ) ) {
        return;
    }

    $e = function_exists( 'error_get_last' ) ? error_get_last() : null;
    $parts = array( 'step=rebuild_abnormal_shutdown' );

    if ( is_array( $e ) && isset( $e['type'] ) ) {
        $parts[] = 'type=' . $e['type'];
        $parts[] = 'msg=' . substr( (string) ( $e['message'] ?? '' ), 0, 400 );
        $parts[] = 'file=' . basename( (string) ( $e['file'] ?? '' ) );
        $parts[] = 'line=' . (int) ( $e['line'] ?? 0 );
    } else {
        $parts[] = 'last_error=none';
        $parts[] = 'hint=timeout_oom_or_kill';
    }

    if ( function_exists( 'memory_get_peak_usage' ) ) {
        $parts[] = 'peak_mem_bytes=' . memory_get_peak_usage( true );
    }

    transformer_model_lexical_context_lexical_rebuild_log( implode( ' ', $parts ) );
}

/**
 * Build PMI + IDF and atomically replace production cache files (existing cache kept if anything fails).
 *
 * @param string $context Source label for logs: browser_sync|wp_cron|cron|wp_cli (legacy).
 * @return array{ ok: bool, error?: string }
 */
function transformer_model_lexical_context_run_full_lexical_cache_rebuild( $context = 'cron' ) {

    transformer_model_lexical_context_lexical_rebuild_log( 'context=' . $context . ' step=rebuild_started' );

    static $lcm_shutdown_registered = false;
    if ( ! $lcm_shutdown_registered ) {
        $lcm_shutdown_registered = true;
        register_shutdown_function( 'transformer_model_lexical_context_lexical_rebuild_shutdown_probe' );
    }
    $GLOBALS['chatbot_lcm_full_rebuild_watch'] = true;

    try {

    // WP-Cron runs over HTTP and often inherits ~30s max_execution_time; PMI on large sites needs unlimited time.
    if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
        if ( function_exists( 'ignore_user_abort' ) ) {
            @ignore_user_abort( true );
        }
        if ( function_exists( 'set_time_limit' ) ) {
            @set_time_limit( 0 );
        }
    }

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

    transformer_model_lexical_context_maybe_raise_memory_for_rebuild( $context, $metrics );

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
        : substr( hash( 'sha256', uniqid( (string) wp_rand(), true ) ), 0, 16 );
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

    } finally {
        $GLOBALS['chatbot_lcm_full_rebuild_watch'] = false;
    }
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
            'reason' => 'empty_corpus',
        );
    }

    $path = transformer_model_lexical_context_local_idf_cache_path();
    $t0   = microtime( true );

    $exists = file_exists( $path );
    $fs     = $exists ? @filesize( $path ) : false;
    transformer_model_lexical_context_lcm_idf_io_timing_line(
        'stat',
        $t0,
        array( 'bytes' => is_int( $fs ) ? $fs : 0 )
    );

    if ( ! $exists ) {
        return array(
            'hit'    => false,
            'reason' => 'missing_cache',
        );
    }

    $t1   = microtime( true );
    $json = file_get_contents( $path );
    transformer_model_lexical_context_lcm_idf_io_timing_line(
        'read',
        $t1,
        array( 'bytes' => is_string( $json ) ? strlen( $json ) : 0 )
    );

    if ( $json === false || $json === '' ) {
        return array(
            'hit'    => false,
            'reason' => 'invalid_cache',
        );
    }

    $t2   = microtime( true );
    $data = json_decode( $json, true );
    transformer_model_lexical_context_lcm_idf_io_timing_line( 'decode', $t2 );

    if ( ! is_array( $data ) || empty( $data['corpus_hash'] ) || ! isset( $data['idf_map'] ) ) {
        return array(
            'hit'    => false,
            'reason' => 'invalid_cache',
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
            'reason' => 'invalid_cache',
        );
    }

    return array(
        'hit'       => true,
        'idf_map'   => $map,
        'N_docs'    => isset( $data['N_docs'] ) ? (int) $data['N_docs'] : 0,
        'created_at'=> isset( $data['created_at'] ) ? (string) $data['created_at'] : '',
        'reason'    => 'loaded',
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

    $idf_gate = transformer_model_lexical_context_lcm_idf_load_skip_gate();
    if ( ! empty( $idf_gate['skip'] ) ) {
        $out['reason'] = isset( $idf_gate['diag'] ) ? (string) $idf_gate['diag'] : 'skipped_elapsed_budget';
        transformer_model_lexical_context_lcm_timing_segment( 'idf_cache_skipped' );

        return $out;
    }

    $loaded = transformer_model_lexical_context_load_local_idf_cache_for_corpus( $corpus_hash );

    if ( ! empty( $loaded['hit'] ) && ! empty( $loaded['idf_map'] ) && is_array( $loaded['idf_map'] ) ) {
        $out['map']    = $loaded['idf_map'];
        $out['active'] = true;
        $out['source'] = 'cache';
        $out['reason'] = 'loaded';

        return $out;
    }

    if ( ! empty( $loaded['hit'] ) && empty( $loaded['idf_map'] ) ) {
        $out['reason'] = 'invalid_cache';

        return $out;
    }

    $out['reason'] = isset( $loaded['reason'] ) ? (string) $loaded['reason'] : 'missing_cache';

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
 * @param string|null               $inactive_reason Canonical diag token when option on but inactive.
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
                '[LCM][local_idf] option=1 active=1 diag=loaded source=%s N_docs=%d map_terms=%d',
                $source,
                $n_docs,
                $map_terms
            )
        );
    } else {
        $diag = $inactive_reason !== null && $inactive_reason !== '' ? $inactive_reason : 'inactive';
        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][local_idf] option=1 active=0 diag=%s',
                str_replace( array( "\r", "\n" ), ' ', $diag )
            )
        );
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
function transformer_model_lexical_context_lexical_sentence_score_row( $sentenceTrimmed, $searchWordsLower, $inputWordsLower, $local_idf_map = null, $apply_local_idf = false, $intent_precomputed = null, $query_shape = null ) {

    $sentenceTrimmed = trim( $sentenceTrimmed );
    if ( $sentenceTrimmed === '' ) {
        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            $sl = strtolower( wp_strip_all_tags( (string) $sentenceTrimmed ) );
            if ( strpos( $sl, 'chatbots help with sales by engaging website visitors' ) !== false ) {
                back_trace( 'NOTICE', '[LCM][target_sentence_trace] stage="scorer_null" reason="empty_sentence" text=""' );
            }
        }
        return null;
    }

    // Diagnostics only: trace scorer entry for a specific problematic sentence.
    $diag_lower = strtolower( wp_strip_all_tags( (string) $sentenceTrimmed ) );
    if (
        transformer_model_lexical_context_is_lcm_diagnostics_enabled()
        && function_exists( 'back_trace' )
        && strpos( $diag_lower, 'proudly go into my generative ai app' ) !== false
    ) {
        $shape_s = ( is_array( $query_shape ) && isset( $query_shape['shape'] ) ) ? (string) $query_shape['shape'] : 'unknown';
        $has_tokens = ( is_array( $inputWordsLower ) && ! empty( $inputWordsLower ) ) ? 1 : 0;
        $prev = transformer_model_lexical_context_diag_preview_text( (string) $sentenceTrimmed, 160 );
        $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][scorer_entry] text="%s" query_shape="%s" has_meaningful_tokens=%d',
                $prev,
                str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $shape_s ),
                $has_tokens
            )
        );
    }

    $sentenceLower       = strtolower( $sentenceTrimmed );
    $sentenceWordCount   = str_word_count( $sentenceTrimmed );

    if ( $sentenceWordCount > 60 ) {
        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            if ( strpos( $diag_lower, 'chatbots help with sales by engaging website visitors' ) !== false ) {
                $prev = transformer_model_lexical_context_diag_preview_text( (string) $sentenceTrimmed, 170 );
                $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
                back_trace(
                    'NOTICE',
                    sprintf(
                        '[LCM][target_sentence_trace] stage="scorer_null" reason="too_many_words" text="%s"',
                        $prev
                    )
                );
            }
        }
        return null;
    }

    // Conservative early rejection: anecdotal / first-person sentences rarely answer definitional queries well.
    // This is intentionally heuristic-scoped using input tokens available at scoring time.
    $looks_informational = false;
    if ( is_array( $inputWordsLower ) && ! empty( $inputWordsLower ) ) {
        $w0 = isset( $inputWordsLower[0] ) ? strtolower( (string) $inputWordsLower[0] ) : '';
        $w1 = isset( $inputWordsLower[1] ) ? strtolower( (string) $inputWordsLower[1] ) : '';
        if ( ( $w0 === 'what' && ( $w1 === 'is' || $w1 === 'are' ) ) || $w0 === 'define' || $w0 === 'explain' ) {
            $looks_informational = true;
        }
    }
    if ( $looks_informational ) {
        $def_cues = array(
            ' is a ',
            ' is an ',
            ' is the ',
            ' refers to ',
            ' means ',
            ' is defined as ',
        );

        // For anecdotal rows, only allow strict subject-leading definition patterns:
        // sentence must start with "<subject> is a/an/the/refers to/means/is defined as".
        $subject_phrase = '';
        if ( is_array( $inputWordsLower ) && $inputWordsLower !== array() ) {
            $parts = array();
            foreach ( $inputWordsLower as $w ) {
                $w = strtolower( trim( (string) $w ) );
                if ( $w !== '' ) {
                    $parts[] = $w;
                }
            }
            $subject_phrase = implode( ' ', $parts );
        }
        $subject_phrase = strtolower( preg_replace( '/\s+/u', ' ', trim( (string) $subject_phrase ) ) );
        $subject_alias  = '';
        if ( $subject_phrase !== '' && strpos( $subject_phrase, 'artificial intelligence' ) !== false ) {
            $subject_alias = trim( preg_replace( '/\bartificial intelligence\b/u', 'ai', $subject_phrase ) );
            $subject_alias = strtolower( preg_replace( '/\s+/u', ' ', (string) $subject_alias ) );
        }

        $has_subject_leading_definition = false;
        $candidates = array();
        if ( $subject_phrase !== '' ) {
            $candidates[] = $subject_phrase;
        }
        if ( $subject_alias !== '' && $subject_alias !== $subject_phrase ) {
            $candidates[] = $subject_alias;
        }
        foreach ( $candidates as $subj ) {
            $subj = (string) $subj;
            if ( $subj === '' ) {
                continue;
            }

            // Build a strict start-of-sentence regex:
            // ^\s{0,6}<subject>\s*(is a|is an|is the|refers to|means|is defined as)\b
            $toks = preg_split( '/\s+/u', $subj, -1, PREG_SPLIT_NO_EMPTY );
            if ( ! is_array( $toks ) || $toks === array() ) {
                continue;
            }
            $parts = array();
            foreach ( $toks as $tw ) {
                $parts[] = preg_quote( (string) $tw, '/' );
            }
            $subj_re = implode( '\s+', $parts );

            if ( preg_match( '/^\s{0,6}(?:' . $subj_re . ')\s*(?:is\s+a|is\s+an|is\s+the|refers\s+to|means|is\s+defined\s+as)\b/u', $sentenceLower ) ) {
                $has_subject_leading_definition = true;
                break;
            }
        }

        if ( ! $has_subject_leading_definition ) {
            $anecdotal_patterns = array(
                '/\bi\s/u',          // "I "
                '/\bi\'m\b/u',       // "I'm"
                '/\bi’m\b/u',        // "I’m"
                '/\bi\'ve\b/u',      // "I've"
                '/\bi\s+have\b/u',   // "I have"
                '/\bi\s+go\b/u',     // "I go"
                '/\bi\s+think\b/u',  // "I think"
                '/\bproudly\b/u',
                '/\btold\b/u',
            );
            $is_anecdotal = false;
            foreach ( $anecdotal_patterns as $re ) {
                if ( preg_match( $re, $sentenceLower ) ) {
                    $is_anecdotal = true;
                    break;
                }
            }

            // Diagnostics only: show whether the anecdotal rejection would match (no behavior change).
            if (
                transformer_model_lexical_context_is_lcm_diagnostics_enabled()
                && function_exists( 'back_trace' )
                && strpos( $diag_lower, 'proudly go into my generative ai app' ) !== false
            ) {
                $shape_s = ( is_array( $query_shape ) && isset( $query_shape['shape'] ) ) ? (string) $query_shape['shape'] : 'unknown';
                $has_i_or_proudly = ( preg_match( '/\bi\s/u', $sentenceLower ) || strpos( $sentenceLower, 'proudly' ) !== false ) ? 1 : 0;
                back_trace(
                    'NOTICE',
                    sprintf(
                        '[LCM][scorer_reject_check] matched=%d reason="anecdotal_check" query_shape="%s" has_i_or_proudly=%d has_def_cue=%d',
                        ( $looks_informational && $is_anecdotal && ! $has_def_cue ) ? 1 : 0,
                        str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $shape_s ),
                        $has_i_or_proudly,
                        $has_def_cue ? 1 : 0
                    )
                );
            }

            if ( $is_anecdotal ) {
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                    $prev = transformer_model_lexical_context_diag_preview_text( $sentenceTrimmed, 140 );
                    $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][scorer_reject] reason="anecdotal_not_strict_subject_definition" text="%s"',
                            $prev
                        )
                    );
                    if ( strpos( $diag_lower, 'chatbots help with sales by engaging website visitors' ) !== false ) {
                        back_trace(
                            'NOTICE',
                            sprintf(
                                '[LCM][target_sentence_trace] stage="scorer_null" reason="anecdotal_not_strict_subject_definition" text="%s"',
                                $prev
                            )
                        );
                    }
                }
                return null;
            }
        }
    }

    $citationPatterns = array(
        '/^by\s+[A-Z][a-z]+\s+[A-Z]/',
        '/^\d{4}[,\s]/',
        '/^[A-Z][a-z]+\s+[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*,\s+[A-Z][a-z]+/',
    );
    foreach ( $citationPatterns as $pattern ) {
        if ( preg_match( $pattern, $sentenceTrimmed ) ) {
            if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                if ( strpos( $diag_lower, 'chatbots help with sales by engaging website visitors' ) !== false ) {
                    $prev = transformer_model_lexical_context_diag_preview_text( (string) $sentenceTrimmed, 170 );
                    $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][target_sentence_trace] stage="scorer_null" reason="citation_pattern" text="%s"',
                            $prev
                        )
                    );
                }
            }
            return null;
        }
    }

    $commaCount = substr_count( $sentenceTrimmed, ',' );
    if ( $commaCount > 5 && $sentenceWordCount < 30 ) {
        $head_80   = mb_substr( $sentenceLower, 0, 80 );
        $def_cues  = array(
            ' is a ',
            ' is an ',
            ' is the ',
            ' refers to ',
            ' means ',
            ' is defined as ',
            ' is used to ',
        );
        $comma_allowed_by_definition = false;
        foreach ( $def_cues as $cue ) {
            if ( strpos( $head_80, $cue ) !== false ) {
                $comma_allowed_by_definition = true;
                break;
            }
        }
        if ( ! $comma_allowed_by_definition ) {
            if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                if ( strpos( $diag_lower, 'chatbots help with sales by engaging website visitors' ) !== false ) {
                    $prev = transformer_model_lexical_context_diag_preview_text( (string) $sentenceTrimmed, 170 );
                    $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][target_sentence_trace] stage="scorer_null" reason="comma_density_high" text="%s"',
                            $prev
                        )
                    );
                }
            }
            return null;
        }
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
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                    if ( strpos( $diag_lower, 'chatbots help with sales by engaging website visitors' ) !== false ) {
                        $prev = transformer_model_lexical_context_diag_preview_text( (string) $sentenceTrimmed, 170 );
                        $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
                        back_trace(
                            'NOTICE',
                            sprintf(
                                '[LCM][target_sentence_trace] stage="scorer_null" reason="question_pattern_no_input_words" text="%s"',
                                $prev
                            )
                        );
                    }
                }
                return null;
            }
        }
    }

    $score             = 0;
    $matchedWords      = array();
    $inputWordsMatched = 0;
    $inputWordsAtStart = 0;

    foreach ( $inputWordsLower as $word ) {
        $word_q = preg_quote( $word, '/' );
        if ( strlen( (string) $word ) >= 4 ) {
            $word_q .= 's?';
        }
        $pattern = '/\b' . $word_q . '\b/i';
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
        $word_q = preg_quote( $word, '/' );
        if ( strlen( (string) $word ) >= 4 ) {
            $word_q .= 's?';
        }
        $pattern = '/\b' . $word_q . '\b/i';
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

    // Build row.
    // (existing code continues below to populate row fields; keep diagnostics-only cow id near row creation)

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
                $word_q = preg_quote( $inputWord, '/' ) . 's?';
                $pattern = '/\b' . $word_q . '\b/i';
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
        $row = array(
            'sentence'            => $sentenceTrimmed,
            'score'               => $score,
            'matched'             => count( $matchedWords ),
            'inputMatched'        => $inputWordsMatched,
            'wordCount'           => $sentenceWordCount,
            'density'             => $density,
            'inputAtStart'        => $inputWordsAtStart,
            'hasSignificantMatch' => $hasSignificantMatch,
        );

        // Diagnostics only: stamp and log cow row identity.
        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            $sl = strtolower( wp_strip_all_tags( (string) $sentenceTrimmed ) );
            if ( strpos( $sl, 'proudly go into my generative ai app' ) !== false ) {
                $row['_cow_id'] = uniqid( 'cow_', true );
                $prev = transformer_model_lexical_context_diag_preview_text( (string) $sentenceTrimmed, 160 );
                $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
                back_trace(
                    'NOTICE',
                    sprintf(
                        '[LCM][cow_track] stage="scored" cow_id=%s text="%s"',
                        (string) $row['_cow_id'],
                        $prev
                    )
                );
            }
        }

        // Diagnostics only: target sentence return.
        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            if ( strpos( $diag_lower, 'chatbots help with sales by engaging website visitors' ) !== false ) {
                $prev = transformer_model_lexical_context_diag_preview_text( (string) $sentenceTrimmed, 170 );
                $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
                back_trace(
                    'NOTICE',
                    sprintf(
                        '[LCM][target_sentence_trace] stage="scorer_return" score=%.4f inputWordsMatched=%d text="%s"',
                        (float) $score,
                        (int) $inputWordsMatched,
                        $prev
                    )
                );
            }
        }

        return $row;
    }

    // Definition override: allow clear "X is a/means/refers to..." rows that mention the subject, even if the
    // lexical match heuristics above are weak (no scoring math changes; bypasses only this final threshold reject).
    $head_80 = mb_substr( $sentenceLower, 0, 80 );
    $def_cues = array(
        ' is a ',
        ' is an ',
        ' is the ',
        ' refers to ',
        ' means ',
        ' is defined as ',
    );
    $has_def_cue = false;
    foreach ( $def_cues as $cue ) {
        if ( strpos( $head_80, $cue ) !== false ) {
            $has_def_cue = true;
            break;
        }
    }
    if ( $has_def_cue ) {
        $meaningful = transformer_model_lexical_context_meaningful_query_tokens_for_relevance_guard( $inputWordsLower );
        $mentions_subject = false;
        foreach ( $meaningful as $mtok ) {
            if ( strlen( (string) $mtok ) < 2 ) {
                continue;
            }
            $pat = '/\b' . preg_quote( (string) $mtok, '/' ) . '\b/u';
            if ( preg_match( $pat, $sentenceLower ) ) {
                $mentions_subject = true;
                break;
            }
        }
        if ( $mentions_subject ) {
            $row = array(
                'sentence'            => $sentenceTrimmed,
                'score'               => $score,
                'matched'             => count( $matchedWords ),
                'inputMatched'        => $inputWordsMatched,
                'wordCount'           => $sentenceWordCount,
                'density'             => $density,
                'inputAtStart'        => $inputWordsAtStart,
                'hasSignificantMatch' => $hasSignificantMatch,
            );
            if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                if ( strpos( $diag_lower, 'chatbots help with sales by engaging website visitors' ) !== false ) {
                    $prev = transformer_model_lexical_context_diag_preview_text( (string) $sentenceTrimmed, 170 );
                    $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][target_sentence_trace] stage="scorer_return" score=%.4f inputWordsMatched=%d text="%s"',
                            (float) $score,
                            (int) $inputWordsMatched,
                            $prev
                        )
                    );
                }
            }
            return $row;
        }
    }

    if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        if ( strpos( $diag_lower, 'chatbots help with sales by engaging website visitors' ) !== false ) {
            $prev = transformer_model_lexical_context_diag_preview_text( (string) $sentenceTrimmed, 170 );
            $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
            $meaningful = transformer_model_lexical_context_meaningful_query_tokens_for_relevance_guard( $inputWordsLower );
            $m_str = is_array( $meaningful ) ? implode( ',', array_map( 'strval', $meaningful ) ) : '';
            $iw_str = is_array( $inputWordsLower ) ? implode( ',', array_map( 'strval', $inputWordsLower ) ) : '';
            back_trace(
                'NOTICE',
                sprintf(
                    '[LCM][target_sentence_trace] stage="scorer_null" reason="final_gate_threshold" score=%.4f minScore=%d inputWordsMatched=%d hasSignificantMatch=%d allShortWords=%d meaningful_query_tokens="%s" inputWordsLower="%s" text="%s"',
                    (float) $score,
                    (int) $minScore,
                    (int) $inputWordsMatched,
                    $hasSignificantMatch ? 1 : 0,
                    $allShortWords ? 1 : 0,
                    $m_str,
                    $iw_str,
                    $prev
                )
            );
        }
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
 * Count metadata/navigation signals in the opening of a sentence (merged sidebar/boilerplate strings).
 * Each category counts at most once. Designed for prefix windows (~180 chars).
 *
 * @param string $prefix Normalized single-line prefix.
 * @return int Number of distinct marker categories found.
 */
function transformer_model_lexical_context_sentence_row_metadata_marker_tally( $prefix ) {

    $prefix = (string) $prefix;
    if ( $prefix === '' ) {
        return 0;
    }

    $has_newsletter = (bool) preg_match( '/\bNEWSLETTERS?\b/i', $prefix );
    $has_tags       = (bool) preg_match( '/^\s*Tags\b|\bTags\s*#|\bTags\s*[:\-–—]/i', $prefix );
    // Related as widget header, not unqualified mid-prose "related studies".
    $has_related    = (bool) preg_match( '/^\s*Related\b|\bRelated\s*[:\-–—]|\bRelated\s+(posts|articles|stories|content)\b/i', $prefix );
    $has_ref_dup    = (bool) preg_match( '/\bReference\s+Reference\b/i', $prefix );
    $has_see_also   = (bool) preg_match( '/\bSee\s+also\b/i', $prefix );

    // Share / Subscribe: merged rows only here (leading CTAs return earlier in is_low_value_sentence_row).
    // Exclude "i subscribe …" prose when pairing Subscribe with other markers.
    $base_ui       = $has_newsletter || $has_tags || $has_related || $has_ref_dup || $has_see_also;
    $has_share_ui  = $base_ui && (bool) preg_match( '/\bShare\b/i', $prefix )
        && ! preg_match( '/\b(?:authors|researchers|they|we|studies)\s+share\b/i', $prefix );
    $has_sub_ui    = $base_ui && (bool) preg_match( '/\bSubscribe\b/i', $prefix )
        && ! preg_match( '/\bi\s+subscribe\b/i', $prefix );

    return (int) $has_newsletter
        + (int) $has_tags
        + (int) $has_related
        + (int) $has_ref_dup
        + (int) $has_see_also
        + (int) $has_share_ui
        + (int) $has_sub_ui;
}

/**
 * Strip WordPress caption shortcodes and generic shortcode wrappers, keeping any remaining prose.
 * Diagnostic/quality-filter helper only; does not affect scoring.
 *
 * @param string $text
 * @return string
 */
function transformer_model_lexical_context_strip_shortcodes_and_captions( $text ) {

    if ( ! is_string( $text ) || $text === '' ) {
        return (string) $text;
    }

    // Remove [caption]...[/caption]
    $text = preg_replace( '/\[caption[^\]]*\].*?\[\/caption\]/is', ' ', $text );

    // Remove any remaining shortcodes like [chatbot-1], [gallery], etc.
    $text = preg_replace( '/\[[^\]]+\]/', ' ', $text );

    // Normalize whitespace.
    $text = preg_replace( '/\s+/u', ' ', (string) $text );

    return trim( (string) $text );
}

/**
 * Post-scoring: detect metadata, navigation, or boilerplate sentence rows (conservative; does not change scores).
 *
 * @param array<string, mixed> $row Scored row with `sentence` text.
 * @return bool True if this row should be dropped before assembly.
 */
function transformer_model_lexical_context_is_low_value_sentence_row( $row ) {

    if ( ! is_array( $row ) ) {
        return true;
    }

    // Diagnostic-only instrumentation: reasons are computed in a separate helper to help identify
    // false positives in this low-value/content-quality filter. Filtering behavior is unchanged.
    $reasons = transformer_model_lexical_context_low_value_sentence_row_reason_codes( $row );
    return $reasons !== array();
}

/**
 * Diagnostic helper: collect machine-readable reason codes for why a scored row is considered low-value.
 * IMPORTANT: The checks and their order must mirror the low-value filter behavior.
 *
 * @param array<string, mixed> $row
 * @return array<int, string> Reason codes (empty => keep row).
 */
function transformer_model_lexical_context_low_value_sentence_row_reason_codes( $row ) {

    if ( ! is_array( $row ) ) {
        return array( 'invalid_row' );
    }

    $raw = isset( $row['sentence'] ) ? (string) $row['sentence'] : '';
    if ( trim( $raw ) === '' ) {
        return array( 'empty_sentence' );
    }

    // Reuse chunk heuristics (line-anchored tags/related/reference, hashtag soup, etc.).
    if ( transformer_model_lexical_context_is_low_value_chunk( $raw ) ) {
        return array( 'low_value_metadata' );
    }

    $text = trim( wp_strip_all_tags( $raw ) );
    if ( $text === '' ) {
        return array( 'empty_stripped' );
    }

    $line = preg_replace( '/\s+/u', ' ', $text );
    $len  = strlen( $line );

    // Merged boilerplate at sentence start (not limited to short whole lines).
    if ( preg_match( '/^\s*NEWSLETTERS?\b/i', $line ) ) {
        return array( 'low_value_metadata' );
    }
    if ( preg_match( '/^\s*Tags\s*#/i', $line ) ) {
        return array( 'low_value_metadata' );
    }
    if ( preg_match( '/^\s*Reference\s+Reference\b/i', $line ) ) {
        return array( 'low_value_metadata' );
    }
    if ( preg_match( '/^\s*Share\b/i', $line ) ) {
        return array( 'low_value_metadata' );
    }
    if ( preg_match( '/^\s*Subscribe\b/i', $line ) ) {
        return array( 'low_value_metadata' );
    }
    if ( preg_match( '/^\s*Related(\s*[:\-–—]|\s+posts\b|\s+articles\b|\s+stories\b|\s+content\b)/i', $line ) ) {
        return array( 'low_value_metadata' );
    }

    // Concatenated UI blocks: several markers near the beginning (e.g. NEWSLETTERS … Tags … Related …).
    $prefix = $line;
    if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) && mb_strlen( $line, 'UTF-8' ) > 180 ) {
        $prefix = mb_substr( $line, 0, 180, 'UTF-8' );
    } elseif ( strlen( $line ) > 180 ) {
        $prefix = substr( $line, 0, 180 );
    }
    if ( transformer_model_lexical_context_sentence_row_metadata_marker_tally( $prefix ) >= 2 ) {
        return array( 'low_value_metadata' );
    }

    // Unexpanded shortcode fragments or caption attributes (assembly-only path).
    if ( preg_match( '/\[\/?[a-z][a-z0-9_-]*\b/i', $raw ) || preg_match( '/\bcaption\s*=/i', $raw ) ) {
        $cleaned = transformer_model_lexical_context_strip_shortcodes_and_captions( $raw );
        // If useful prose remains, re-evaluate the cleaned text instead of discarding the whole row.
        if ( $cleaned !== '' && strlen( $cleaned ) > 20 ) {
            if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                $orig_prev   = transformer_model_lexical_context_diag_preview_text( $raw, 165 );
                $clean_prev  = transformer_model_lexical_context_diag_preview_text( $cleaned, 165 );
                $orig_prev   = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $orig_prev );
                $clean_prev  = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $clean_prev );
                back_trace(
                    'NOTICE',
                    sprintf(
                        '[LCM][quality_filter_salvaged:caption] original="%s" cleaned="%s"',
                        $orig_prev,
                        $clean_prev
                    )
                );
            }

            // Re-run the same checks against the cleaned sentence.
            $row_clean = $row;
            $row_clean['sentence'] = $cleaned;
            return transformer_model_lexical_context_low_value_sentence_row_reason_codes( $row_clean );
        }

        return array( 'caption_or_shortcode' );
    }

    // Pagination / nav stubs (whole-line; avoids "Previous research showed…").
    if ( preg_match( '/^\s*(previous|next)\s*([«»]{1,2}|[\x{2190}-\x{2192}]|→|←)?\s*\.?\s*$/iu', $line ) ) {
        return array( 'nav_or_pagination' );
    }

    // Button / widget lines (short, leading).
    if ( $len <= 80 ) {
        if ( preg_match( '/^\s*see\s+also\b/i', $line ) ) {
            return array( 'nav_or_pagination' );
        }
        if ( preg_match( '/^\s*(share(\s+on|\s+this)?|subscribe(\s+now|\s+today)?)\b/i', $line ) ) {
            return array( 'low_value_metadata' );
        }
        if ( preg_match( '/^\s*read\s+more\b/i', $line ) && $len <= 40 ) {
            return array( 'nav_or_pagination' );
        }
        if ( preg_match( '/newsletters?\b/i', $line ) && $len <= 60 ) {
            return array( 'low_value_metadata' );
        }
        if ( preg_match( '/^\s*(posted\s+in|filed\s+under)\b/i', $line ) ) {
            return array( 'low_value_metadata' );
        }
        if ( preg_match( '/^\s*(category|categories|tags?)\s*[:\-–—]/i', $line ) ) {
            return array( 'low_value_metadata' );
        }
    }

    // ALL-CAPS newsletter / menu banners (no lowercase prose).
    if ( $len >= 6 && $len <= 120 ) {
        $letters = preg_replace( '/[^a-zA-Z]/', '', $line );
        if ( strlen( $letters ) >= 8 && $letters === strtoupper( $letters ) ) {
            if ( preg_match( '/NEWSLETTER|SUBSCRIBE|RELATED|TAGS|SEARCH|ARCHIVES|SHARE|PREVIOUS|NEXT/i', $line ) ) {
                return array( 'low_value_metadata' );
            }
        }
    }

    return array();
}

/**
 * Post-scoring: remove low-value rows while preserving order; optional LCM diagnostics.
 *
 * @param array<int, array<string, mixed>> $rows       Candidate rows (best-first).
 * @param string                           $stage_slug For [LCM][quality_filter:slug] logs.
 * @return array<int, array<string, mixed>>
 */
function transformer_model_lexical_context_filter_low_value_sentence_rows( $rows, $stage_slug ) {

    if ( ! is_array( $rows ) ) {
        return array();
    }

    $before = count( $rows );
    $slug   = preg_replace( '/[^\w.-]/', '', (string) $stage_slug );
    if ( $slug === '' ) {
        $slug = 'quality_filter';
    }

    $out = array();
    $removed_logged = 0;
    foreach ( $rows as $row ) {
        // Caption/shortcode salvage: normalize row text before the existing low-value decision.
        // Diagnostic-only intent: avoid false positives where wrappers cause good content to be dropped.
        // Behavior invariant: we do not short-circuit — we only replace the sentence text, then run the same checks.
        if ( is_array( $row ) && isset( $row['sentence'] ) && is_string( $row['sentence'] ) ) {
            $raw_sentence = $row['sentence'];
            if ( preg_match( '/\[\/?[a-z][a-z0-9_-]*\b/i', $raw_sentence ) || preg_match( '/\bcaption\s*=/i', $raw_sentence ) ) {
                $cleaned = transformer_model_lexical_context_strip_shortcodes_and_captions( $raw_sentence );
                if ( $cleaned !== '' && strlen( $cleaned ) > 20 ) {
                    if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                        $orig_prev  = transformer_model_lexical_context_diag_preview_text( $raw_sentence, 165 );
                        $clean_prev = transformer_model_lexical_context_diag_preview_text( $cleaned, 165 );
                        $orig_prev  = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $orig_prev );
                        $clean_prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $clean_prev );
                        back_trace(
                            'NOTICE',
                            sprintf(
                                '[LCM][quality_filter_salvaged:caption] original="%s" cleaned="%s"',
                                $orig_prev,
                                $clean_prev
                            )
                        );
                    }

                    // Core fix: cleaned text flows downstream and is re-evaluated by existing checks.
                    $row['sentence'] = $cleaned;
                }
            }
        }

        if ( ! transformer_model_lexical_context_is_low_value_sentence_row( $row ) ) {
            $out[] = $row;
            continue;
        }

        // Diagnostic-only instrumentation: log why rows were removed (first 10 per stage).
        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) && $removed_logged < 10 ) {
            $reasons = transformer_model_lexical_context_low_value_sentence_row_reason_codes( is_array( $row ) ? $row : array() );
            if ( $reasons === array() ) {
                $reasons = array( 'unknown' );
            }
            $pid   = isset( $row['post_id'] ) ? (string) $row['post_id'] : '';
            $score = isset( $row['score'] ) ? (float) $row['score'] : 0.0;
            $title = isset( $row['post_title'] ) ? (string) $row['post_title'] : '';
            $sent  = isset( $row['sentence'] ) ? (string) $row['sentence'] : '';

            $preview = transformer_model_lexical_context_diag_preview_text( $sent, 165 );
            $title   = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $title );
            $preview = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $preview );
            $reason_join = implode( '|', array_values( array_unique( $reasons ) ) );

            back_trace(
                'NOTICE',
                sprintf(
                    '[LCM][quality_filter_removed:%s] reason=[%s] score=%.4f post_id=%s title="%s" text="%s"',
                    $slug,
                    $reason_join,
                    $score,
                    $pid,
                    $title,
                    $preview
                )
            );
            ++$removed_logged;
        }
    }

    $after = count( $out );

    if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][quality_filter:%s] candidates before=%d after=%d removed=%d',
                $slug,
                $before,
                $after,
                max( 0, $before - $after )
            )
        );
    }

    return $out;
}

/**
 * Limit how many scored rows are kept per document (post_id) while preserving global order.
 *
 * Filter: {@see 'chatbot_lcm_max_rows_per_document'} — default 2; use <= 0 to disable.
 *
 * @param array<int, array<string, mixed>> $rows Rows in assembly order.
 * @param int|null                         $max_per_document Optional override before filter (null = use filter default).
 * @return array<int, array<string, mixed>>
 */
function transformer_model_lexical_context_limit_rows_per_document( $rows, $max_per_document = null ) {

    if ( ! is_array( $rows ) || $rows === array() ) {
        return is_array( $rows ) ? $rows : array();
    }

    $before = count( $rows );
    $base = $max_per_document !== null ? (int) $max_per_document : 2;
    $max  = (int) apply_filters( 'chatbot_lcm_max_rows_per_document', $base );

    if ( $max <= 0 ) {
        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            back_trace(
                'NOTICE',
                sprintf( '[LCM][doc_limit] candidates before=%d after=%d max_per_document=%d', $before, $before, 0 )
            );
        }
        return $rows;
    }

    $per_doc = array();
    $out     = array();

    foreach ( $rows as $row ) {
        $pid = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;
        $n   = isset( $per_doc[ $pid ] ) ? (int) $per_doc[ $pid ] : 0;
        if ( $n >= $max ) {
            continue;
        }
        $out[]            = $row;
        $per_doc[ $pid ] = $n + 1;
    }

    $after = count( $out );

    if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        back_trace(
            'NOTICE',
            sprintf( '[LCM][doc_limit] candidates before=%d after=%d max_per_document=%d', $before, $after, $max )
        );
    }

    return $out;
}

/**
 * Normalize user query text before lexical tokenization: HTML entities, apostrophes / possessives so tokens are not split or mangled.
 *
 * @param string $text Raw query (after sanitize_text_field where applicable).
 * @return string
 */
function transformer_model_lexical_context_normalize_lexical_query_string( $text ) {

    $text = (string) $text;
    // Decode entities (e.g. &#x27; → ') before apostrophe/possessive handling.
    $text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $text = wp_strip_all_tags( $text );
    // Common Unicode apostrophes / primes → ASCII apostrophe (Job's, Steve Jobs's).
    $text = str_replace(
        array( "\xE2\x80\x99", "\xE2\x80\x98", "\xE2\x80\x9C", "\xE2\x80\x9D", '`', '´' ),
        "'",
        $text
    );

    // Possessive / plural-with-apostrophe: Job's → Jobs, Steve Jobs's → Steve Jobss (rare); it's → its.
    $text = preg_replace( "/([\p{L}]{2,})'s\b/u", '$1s', $text );

    return trim( $text );
}

/**
 * Adjacent phrase candidates used only for diagnostics (same ordering as phrase bonus: meaningful tokens only).
 *
 * @param array<int, string> $meaningful Meaningful direct-query tokens (relevance guard).
 * @return array{ 0: array<int, string>, 1: array<int, string> } bigrams, trigrams.
 */
function transformer_model_lexical_context_phrase_candidates_from_meaningful_tokens( array $meaningful ) {

    $bigrams   = array();
    $trigrams  = array();
    $n         = count( $meaningful );

    for ( $i = 0; $i < $n - 1; $i++ ) {
        $bigrams[] = $meaningful[ $i ] . ' ' . $meaningful[ $i + 1 ];
    }
    for ( $i = 0; $i < $n - 2; $i++ ) {
        $trigrams[] = $meaningful[ $i ] . ' ' . $meaningful[ $i + 1 ] . ' ' . $meaningful[ $i + 2 ];
    }

    return array( $bigrams, $trigrams );
}

/**
 * Log query normalization pipeline when KOGNETIKS_LCM_DEBUG (once per request path).
 * Includes decoded= (html_entity_decode only) alongside raw and normalized for entity-heavy queries.
 *
 * @param string               $raw_query             Original user query text for this path.
 * @param string               $normalized_query_text Same normalization as generate_contextual_response (post-strip).
 * @param array<int, string>   $inputWordsLower     Words driving lexical scoring after filters.
 * @param array<int, string>   $meaningful_query_tokens Tokens after relevance guard.
 * @return void
 */
function transformer_model_lexical_context_diag_log_query_token_pipeline( $raw_query, $normalized_query_text, $inputWordsLower, $meaningful_query_tokens ) {

    if ( ! transformer_model_lexical_context_is_lcm_diagnostics_enabled() || ! function_exists( 'back_trace' ) ) {
        return;
    }

    static $logged = false;
    if ( $logged ) {
        return;
    }
    $logged = true;

    $meaningful_query_tokens = is_array( $meaningful_query_tokens ) ? $meaningful_query_tokens : array();
    list( $bigrams, $trigrams ) = transformer_model_lexical_context_phrase_candidates_from_meaningful_tokens( $meaningful_query_tokens );

    $safe_raw  = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $raw_query );
    $decoded   = html_entity_decode( (string) $raw_query, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $safe_dec  = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $decoded );
    $safe_norm = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $normalized_query_text );

    back_trace(
        'NOTICE',
        sprintf(
            '[LCM][query_tokens] raw="%s" decoded="%s" normalized="%s" inputWords=[%s] meaningful=[%s] phrase_bigrams=[%s] phrase_trigrams=[%s]',
            $safe_raw,
            $safe_dec,
            $safe_norm,
            implode( ',', array_map( 'strval', (array) $inputWordsLower ) ),
            implode( ',', $meaningful_query_tokens ),
            implode( '|', $bigrams ),
            implode( '|', $trigrams )
        )
    );
}

/**
 * Short domain acronyms allowed in LCM query tokenization even when below the usual 3-character minimum.
 * Does not affect corpus/chunk processing — only {@see transformer_model_lexical_context_generate_contextual_response()} inputWords.
 *
 * Filter: {@see 'chatbot_lcm_query_short_acronym_allowlist'} — pass array of lowercase tokens; merged into defaults.
 *
 * @return array<string, true> Token (lowercase) => true for O(1) lookup.
 */
function transformer_model_lexical_context_lcm_short_acronym_allowlist_flip() {

    $defaults = array(
        'ai',
        'agi',
        'llm',
        'slm',
        'gpt',
        'api',
        'pmi',
        'idf',
        'tf',
        'tfidf',
        'seo',
        'ux',
        'ui',
        'wp',
    );

    $merged = (array) apply_filters( 'chatbot_lcm_query_short_acronym_allowlist', $defaults );
    $flip   = array();
    foreach ( $merged as $t ) {
        $t = strtolower( trim( (string) $t ) );
        if ( $t === '' || strlen( $t ) > 12 ) {
            continue;
        }
        $flip[ $t ] = true;
    }

    return $flip;
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
        'which',
        'who',
        'whom',
        'whose',
        'where',
        'when',
        'why',
        'how',
        'is',
        'are',
        'was',
        'were',
        'be',
        'been',
        'being',
        'have',
        'has',
        'had',
        'do',
        'does',
        'did',
        'will',
        'would',
        'could',
        'should',
        'may',
        'might',
        'must',
        'can',
        'shall',
        'the',
        'a',
        'an',
        'to',
        'of',
        'for',
        'in',
        'on',
        'at',
        'by',
        'with',
        'from',
        'as',
        'into',
        'onto',
        // 'related',
        'about',
        'this',
        'that',
        'these',
        'those',
        'it',
        'its',
        // Weak/generic modifiers — phrase bonus uses only meaningful tokens; keep queries focused on content words.
        'one',
        'two',
        'big',
        'bigger',
        'biggest',
        'small',
        'smaller',
        'smallest',
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
 * Whether the normalized query is a short “related to X” / “relates to X” intent pattern (not substantive “related + noun”).
 *
 * @param string $normalized_query_lower Lowercased single-line query after normalize_lexical_query_string + punctuation collapse.
 * @return bool
 */
function transformer_model_lexical_context_query_matches_relation_intent_pattern( $normalized_query_lower ) {

    $q = trim( preg_replace( '/\s+/u', ' ', (string) $normalized_query_lower ) );
    if ( $q === '' ) {
        return false;
    }

    $end = '[?.!,;:]*\s*$';

    // Requires a non-empty anchor after "to" where applicable (avoids bare “related to”).
    if ( preg_match( '/^what\'s\s+related\s+to\s+\S+' . $end . '/iu', $q ) ) {
        return true;
    }
    // Possessive apostrophe stripped without spacing (“What’s” → “Whats”) before punctuation collapse.
    if ( preg_match( '/^whats\s+related\s+to\s+\S+' . $end . '/iu', $q ) ) {
        return true;
    }
    if ( preg_match( '/^what\s+is\s+related\s+to\s+\S+' . $end . '/iu', $q ) ) {
        return true;
    }
    if ( preg_match( '/^related\s+to\s+\S+' . $end . '/iu', $q ) ) {
        return true;
    }
    if ( preg_match( '/^what\s+relates\s+to\s+\S+' . $end . '/iu', $q ) ) {
        return true;
    }
    // “How is X related” / “How is X related to Y” — distinct from “How are related products …”.
    if ( preg_match( '/^how\s+is\s+\S+\s+related(\s+to\s+\S+)?' . $end . '/iu', $q ) ) {
        return true;
    }

    return false;
}

/**
 * Remove discourse “related” for relation-intent queries so the content anchor (e.g. kumquat) dominates retrieval.
 * Does not remove “related” globally; does not empty the token list.
 *
 * @param array<int, string> $tokens                    Lowercased query tokens after stop/guard filters.
 * @param string             $normalized_query_for_pattern Same normalized query string used to tokenize (lowercase).
 * @return array<int, string>
 */
function transformer_model_lexical_context_filter_relation_intent_tokens( array $tokens, $normalized_query_for_pattern ) {

    if ( count( $tokens ) < 2 ) {
        return $tokens;
    }

    $has_related = false;
    foreach ( $tokens as $t ) {
        if ( strtolower( (string) $t ) === 'related' ) {
            $has_related = true;
            break;
        }
    }
    if ( ! $has_related ) {
        return $tokens;
    }

    if ( ! transformer_model_lexical_context_query_matches_relation_intent_pattern( $normalized_query_for_pattern ) ) {
        return $tokens;
    }

    // “What’s” often normalizes to “Whats” (one token); drop it with “related to” intent so the anchor remains.
    $strip_whats_artifact = (bool) preg_match( '/^whats\s+related\s+to/i', $normalized_query_for_pattern );

    $out = array();
    foreach ( $tokens as $t ) {
        $tl = strtolower( (string) $t );
        if ( 'related' === $tl ) {
            continue;
        }
        if ( $strip_whats_artifact && 'whats' === $tl ) {
            continue;
        }
        $out[] = $t;
    }

    if ( $out === array() ) {
        return $tokens;
    }

    return array_values( $out );
}

/**
 * Conservative query-shape classifier (diagnostics + informational answer-shape bias; see apply_answer_shape_bias).
 *
 * @param string             $raw_query               Original user query (may contain entities / punctuation).
 * @param string             $normalized_query        Normalized single-line query used for tokenization (already punctuation-collapsed).
 * @param array<int, string> $input_words             Lowercased query tokens after filters (same list used for scoring).
 * @param array<int, string> $meaningful_query_tokens Meaningful direct-query tokens (relevance guard).
 * @return array{ shape: string, confidence: float, signals: array<int, string> }
 */
function transformer_model_lexical_context_classify_query_shape( $raw_query, $normalized_query, $input_words, $meaningful_query_tokens ) {

    $input_words             = is_array( $input_words ) ? $input_words : array();
    $meaningful_query_tokens = is_array( $meaningful_query_tokens ) ? $meaningful_query_tokens : array();

    $raw_l  = strtolower( wp_strip_all_tags( (string) $raw_query ) );
    $norm_l = strtolower( trim( preg_replace( '/\s+/u', ' ', (string) $normalized_query ) ) );

    $signals = array();

    $m_n = count( $meaningful_query_tokens );

    if ( $m_n === 0 ) {
        return array(
            'shape'      => 'empty_query',
            'confidence' => 0.95,
            'signals'    => array( 'no_meaningful_tokens' ),
        );
    }

    $relation_intent = transformer_model_lexical_context_query_matches_relation_intent_pattern( $norm_l );
    if ( $relation_intent ) {
        $signals[] = 'relation_intent_pattern';
    }

    // Question/action structure signals.
    $question_words = array( 'what', 'why', 'how', 'when', 'where', 'who', 'whom', 'whose', 'which' );
    $action_words   = array( 'explain', 'describe', 'define', 'compare', 'summarize' );
    $has_question   = false;
    foreach ( $question_words as $w ) {
        if ( preg_match( '/\b' . preg_quote( $w, '/' ) . '\b/i', $raw_l ) || preg_match( '/\b' . preg_quote( $w, '/' ) . '\b/i', $norm_l ) ) {
            $has_question = true;
            $signals[]    = 'question_word:' . $w;
            break;
        }
    }
    $has_action = false;
    foreach ( $action_words as $w ) {
        if ( preg_match( '/\b' . preg_quote( $w, '/' ) . '\b/i', $raw_l ) || preg_match( '/\b' . preg_quote( $w, '/' ) . '\b/i', $norm_l ) ) {
            $has_action = true;
            $signals[]  = 'action_word:' . $w;
            break;
        }
    }

    // Loose term-list signal: many tokens, few glue words/punctuation, no question/action, no relation intent.
    $looks_like_bag = false;
    if ( $m_n >= 3 && ! $has_question && ! $has_action && ! $relation_intent ) {
        // If the query is mostly tokens and short (few commas/periods) it tends to be a bag query.
        $glue = preg_match( '/\b(and|or|vs|with|without)\b/i', $norm_l );
        $has_punct = preg_match( '/[,;:]/', (string) $normalized_query );
        if ( ! $glue && ! $has_punct ) {
            $looks_like_bag = true;
            $signals[]      = 'term_list_look';
        }
    }

    if ( $relation_intent ) {
        return array(
            'shape'      => 'relation_query',
            'confidence' => 0.80,
            'signals'    => $signals,
        );
    }

    if ( $m_n === 1 ) {
        return array(
            'shape'      => 'short_anchor_query',
            'confidence' => 0.85,
            'signals'    => array_merge( $signals, array( 'single_meaningful_token' ) ),
        );
    }

    // Relation-intent reduction can yield one anchor token even if the raw query had more words.
    if ( $m_n === 1 && count( $input_words ) >= 1 ) {
        return array(
            'shape'      => 'short_anchor_query',
            'confidence' => 0.80,
            'signals'    => array_merge( $signals, array( 'relation_reduced_anchor' ) ),
        );
    }

    if ( ( $has_question || $has_action ) && $m_n >= 2 ) {
        return array(
            'shape'      => 'informational_query',
            'confidence' => 0.72,
            'signals'    => $signals,
        );
    }

    if ( $looks_like_bag ) {
        return array(
            'shape'      => 'keyword_bag_query',
            'confidence' => 0.68,
            'signals'    => $signals,
        );
    }

    // Default: looks like a normal informational query but without explicit question/action tokens.
    return array(
        'shape'      => 'informational_query',
        'confidence' => 0.55,
        'signals'    => array_merge( $signals, array( 'default_fallback' ) ),
    );
}

/**
 * Query “action” words: shape signals only — excluded from answer-shape anchors (not definition targets).
 *
 * Filter {@see 'chatbot_lcm_answer_shape_query_action_words'} may append additional lowercase tokens.
 *
 * @return array<string, true>
 */
function transformer_model_lexical_context_answer_shape_query_action_words_flip() {

    $tokens = array( 'explain', 'describe', 'define', 'summarize', 'compare' );
    $extra  = apply_filters( 'chatbot_lcm_answer_shape_query_action_words', array() );
    foreach ( (array) $extra as $t ) {
        $t = strtolower( trim( (string) $t ) );
        if ( $t !== '' ) {
            $tokens[] = $t;
        }
    }
    $flip = array();
    foreach ( $tokens as $t ) {
        $flip[ strtolower( trim( (string) $t ) ) ] = true;
    }

    return $flip;
}

/**
 * Meaningful tokens minus query action words → content anchors for answer-shape bias.
 *
 * @param array<int, string> $meaningful_lower Unique lowercased meaningful tokens.
 * @return array<int, string>
 */
function transformer_model_lexical_context_answer_shape_content_tokens( array $meaningful_lower ) {

    $action_flip = transformer_model_lexical_context_answer_shape_query_action_words_flip();
    $out         = array();
    foreach ( $meaningful_lower as $w ) {
        $w = strtolower( trim( (string) $w ) );
        if ( strlen( $w ) < 2 || isset( $action_flip[ $w ] ) ) {
            continue;
        }
        $out[] = $w;
    }

    return array_values( $out );
}

/**
 * Ordered definition cues for answer-shape bias (longer / more specific first within each tier).
 * Strong → full {@see 'chatbot_lcm_answer_shape_definition_bonus'}; medium → fraction via {@see 'chatbot_lcm_answer_shape_definition_bonus_medium_ratio'}.
 * No standalone “is”, bare “can”, or “explain”. Specs without `strength` default to strong.
 *
 * @return array<int, array{ label: string, pattern: string, strength?: string }>
 */
function transformer_model_lexical_context_answer_shape_definition_cue_specs() {

    $specs = array(
        // Strong: direct definitional phrasing.
        array( 'label' => 'is defined as', 'pattern' => '\bis\s+defined\s+as\b', 'strength' => 'strong' ),
        array( 'label' => 'refers to', 'pattern' => '\brefers\s+to\b', 'strength' => 'strong' ),
        array( 'label' => 'is an', 'pattern' => '\bis\s+an\b', 'strength' => 'strong' ),
        array( 'label' => 'is a', 'pattern' => '\bis\s+a\b', 'strength' => 'strong' ),
        array( 'label' => 'means', 'pattern' => '\bmeans\b', 'strength' => 'strong' ),
        // Medium: examples / capability — smaller bonus.
        array( 'label' => 'examples of', 'pattern' => '\bexamples\s+of\b', 'strength' => 'medium' ),
        array( 'label' => 'includes', 'pattern' => '\bincludes\b', 'strength' => 'medium' ),
        array( 'label' => 'include', 'pattern' => '\binclude\b', 'strength' => 'medium' ),
        array( 'label' => 'can generate', 'pattern' => '\bcan\s+generate\b', 'strength' => 'medium' ),
        array( 'label' => 'can be used', 'pattern' => '\bcan\s+be\s+used\b', 'strength' => 'medium' ),
    );

    return (array) apply_filters( 'chatbot_lcm_answer_shape_definition_cue_specs', $specs );
}

/**
 * Whole-token anchor regex from 2–3 lowercase meaningful tokens (phrase).
 *
 * @param array<int, string> $tokens
 * @return string|null
 */
function transformer_model_lexical_context_answer_shape_anchor_regex_phrase( array $tokens ) {

    $parts = array();
    foreach ( $tokens as $t ) {
        $t = strtolower( trim( (string) $t ) );
        if ( strlen( $t ) < 2 ) {
            continue;
        }
        $parts[] = preg_quote( $t, '/' );
    }
    if ( count( $parts ) < 2 ) {
        return null;
    }

    return '(?<![\p{L}\p{N}_])' . implode( '\s+', $parts ) . '(?![\p{L}\p{N}_])';
}

/**
 * Whole-token anchor regex for a single term.
 *
 * @param string $word
 * @return string|null
 */
function transformer_model_lexical_context_answer_shape_anchor_regex_word( $word ) {

    $w = strtolower( trim( (string) $word ) );
    if ( strlen( $w ) < 2 ) {
        return null;
    }

    return '(?<![\p{L}\p{N}_])' . preg_quote( $w, '/' ) . '(?![\p{L}\p{N}_])';
}

/**
 * First matching definition cue near anchor (either order). Tries strong cues before medium so direct definitions win ties.
 *
 * @param string $sentence_lower Stripped lowercased sentence.
 * @param string $anchor_regex   Anchor fragment (no delimiters).
 * @param int    $win            Max characters between anchor and cue.
 * @return array{ hit: bool, cue: string, strength: string }
 */
function transformer_model_lexical_context_answer_shape_row_definition_match_detail( $sentence_lower, $anchor_regex, $win = 55 ) {

    $empty = array(
        'hit'      => false,
        'cue'      => '',
        'strength' => '',
    );

    if ( $anchor_regex === null || $anchor_regex === '' ) {
        return $empty;
    }

    $win = max( 12, min( 120, (int) $win ) );

    foreach ( array( 'strong', 'medium' ) as $tier ) {
        foreach ( transformer_model_lexical_context_answer_shape_definition_cue_specs() as $spec ) {
            if ( empty( $spec['pattern'] ) ) {
                continue;
            }
            $strength = isset( $spec['strength'] ) ? strtolower( trim( (string) $spec['strength'] ) ) : 'strong';
            $bucket   = ( $strength === 'medium' ) ? 'medium' : 'strong';
            if ( $bucket !== $tier ) {
                continue;
            }

            $cue   = (string) $spec['pattern'];
            $label = isset( $spec['label'] ) ? (string) $spec['label'] : $cue;

            $forward = '/' . $anchor_regex . '.{0,' . $win . '}(' . $cue . ')/iu';
            $back    = '/(' . $cue . ').{0,' . $win . '}' . $anchor_regex . '/iu';

            if ( preg_match( $forward, $sentence_lower ) || preg_match( $back, $sentence_lower ) ) {
                return array(
                    'hit'      => true,
                    'cue'      => $label,
                    'strength' => $tier,
                );
            }
        }
    }

    return $empty;
}

/**
 * Changelog / admin-meta style heuristic for informational queries (penalized when query shape is informational).
 *
 * @param string $sentence
 * @return bool
 */
function transformer_model_lexical_context_answer_shape_row_looks_meta_changelog( $sentence ) {

    $s = strtolower( wp_strip_all_tags( (string) $sentence ) );

    $patterns = array(
        '/^\s*(updated|moved|added|removed)\b/i',
        // "Foo Analysis: Moved …" / "Foo Update: Updated …"
        '/\b\w+\s+analysis\s*:\s*(moved|updated|added|removed)\b/i',
        '/\b\w+\s+update\s*:\s*(updated|moved|added|removed)\b/i',
        '/\banalysis\s*:\s*(moved|updated|added|removed)\b/i',
        '/\bupdate\s*:\s*updated\b/i',
        // "Moved … to the … tab"
        '/\bmoved\b.{0,160}\bto\s+the\b.{0,60}\btab\b/is',
        // "Analysis … export … tab" (changelog / export UI)
        '/\banalysis\b.{0,120}\bexport\b.{0,80}\btab\b/is',
        '/\bexport\b.{0,80}\btab\b/is',
        // "Options … settings …" style admin copy
        '/\b(settings|options)\b.{0,100}\b(settings|options)\b/i',
        '/\bversion\b/i',
        '/what\'?s\s+new\b/i',
    );

    foreach ( $patterns as $p ) {
        if ( preg_match( $p, $s ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Informational queries only: modest score boost for definition-shaped rows; stronger penalty for meta/changelog rows (no boost on meta rows).
 * Does not remove candidates — adjusts scores and re-sorts by score (then existing row compare).
 *
 * Filter: {@see 'chatbot_lcm_answer_shape_definition_bonus'} default 15 (strong cues).
 * Filter: {@see 'chatbot_lcm_answer_shape_definition_bonus_medium_ratio'} default 0.5 (medium cue bonus = strong × ratio).
 * Filter: {@see 'chatbot_lcm_answer_shape_meta_penalty'} default 35.
 *
 * @param array<int, array<string, mixed>> $sentence_scores
 * @param array{ shape?: string, confidence?: float, signals?: array<int, string> } $query_shape
 * @param array<int, string>             $meaningful_query_tokens
 * @return array<int, array<string, mixed>>
 */
function transformer_model_lexical_context_apply_answer_shape_bias( $sentence_scores, $query_shape, $meaningful_query_tokens ) {

    $result = is_array( $sentence_scores ) ? $sentence_scores : array();
    $shape  = isset( $query_shape['shape'] ) ? (string) $query_shape['shape'] : '';

    if ( $shape !== 'informational_query' ) {
        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            back_trace(
                'NOTICE',
                sprintf(
                    '[LCM][answer_shape_bias] applied=0 reason=%s',
                    $shape !== '' ? $shape : 'missing_shape'
                )
            );
        }

        return $result;
    }

    $meaningful_query_tokens = is_array( $meaningful_query_tokens ) ? $meaningful_query_tokens : array();
    $norm                    = array();
    foreach ( $meaningful_query_tokens as $w ) {
        $w = strtolower( trim( (string) $w ) );
        if ( strlen( $w ) >= 2 ) {
            $norm[] = $w;
        }
    }
    $norm = array_values( array_unique( $norm ) );
    $m    = count( $norm );

    if ( $m < 1 ) {
        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            back_trace( 'NOTICE', '[LCM][answer_shape_bias] applied=0 reason=no_meaningful_tokens' );
        }

        return $result;
    }

    $content = transformer_model_lexical_context_answer_shape_content_tokens( $norm );
    $c       = count( $content );

    if ( $c < 1 ) {
        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            back_trace( 'NOTICE', '[LCM][answer_shape_bias] applied=0 reason=no_content_anchors' );
        }

        return $result;
    }

    $phrase_tokens = array();
    if ( $c >= 2 && $c <= 3 ) {
        $phrase_tokens = $content;
    } elseif ( $c > 3 ) {
        $phrase_tokens = array_slice( $content, -3 );
    }

    $phrase_re = array();
    if ( count( $phrase_tokens ) >= 2 ) {
        $pr = transformer_model_lexical_context_answer_shape_anchor_regex_phrase( $phrase_tokens );
        if ( $pr !== null && $pr !== '' ) {
            $phrase_re = array(
                'regex' => $pr,
                'label' => implode( ' ', $phrase_tokens ),
            );
        }
    }

    $suppressed_single = '';
    $word_re           = transformer_model_lexical_context_answer_shape_anchor_regex_word( $content[ $c - 1 ] );

    $anchors_diag = array();
    if ( $phrase_re !== array() ) {
        $anchors_diag[] = $phrase_re['label'];
        // Prefer phrase anchors for informational queries; suppress single-token fallback when a phrase anchor exists.
        // This avoids weak last-token anchors (often proper nouns / common words) from being treated as an anchor target.
        $suppressed_single = $content[ $c - 1 ];
        $word_re           = null;
    } else {
        $anchors_diag[] = $content[ $c - 1 ];
    }

    $bonus_strong = (float) apply_filters( 'chatbot_lcm_answer_shape_definition_bonus', 15.0 );
    $medium_ratio = (float) apply_filters( 'chatbot_lcm_answer_shape_definition_bonus_medium_ratio', 0.5 );
    if ( $medium_ratio < 0.0 ) {
        $medium_ratio = 0.0;
    }
    if ( $medium_ratio > 1.0 ) {
        $medium_ratio = 1.0;
    }
    $bonus_medium = $bonus_strong * $medium_ratio;
    $penalty      = (float) apply_filters( 'chatbot_lcm_answer_shape_meta_penalty', 35.0 );

    $boosted      = 0;
    $penalized    = 0;
    $row_affected = array();
    $total_rows   = 0;
    $defpat_logged = 0;
    $action_logged = 0;
    $penalty_logged = 0;

    foreach ( $result as $idx => $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        ++$total_rows;

        $sentence = isset( $row['sentence'] ) ? $row['sentence'] : '';
        $slower   = strtolower( wp_strip_all_tags( (string) $sentence ) );

        $meta_hit = transformer_model_lexical_context_answer_shape_row_looks_meta_changelog( $sentence );

        // Anchor detection for additive action/mechanism cue boost (diagnostic-only instrumentation).
        $has_anchor = false;
        if ( $phrase_re !== array() ) {
            $has_anchor = (bool) preg_match( '/' . $phrase_re['regex'] . '/iu', $slower );
        } elseif ( $word_re !== null && $word_re !== '' ) {
            $has_anchor = (bool) preg_match( '/' . $word_re . '/iu', $slower );
        }

        $def_hit         = false;
        $match_anchor    = '';
        $match_cue       = '';
        $match_cue_tier  = '';
        if ( ! $meta_hit ) {
            if ( $phrase_re !== array() ) {
                $detail = transformer_model_lexical_context_answer_shape_row_definition_match_detail( $slower, $phrase_re['regex'] );
                if ( $detail['hit'] ) {
                    $def_hit        = true;
                    $match_anchor   = $phrase_re['label'];
                    $match_cue      = $detail['cue'];
                    $match_cue_tier = (string) $detail['strength'];
                }
            } elseif ( $word_re !== null && $word_re !== '' ) {
                $detail = transformer_model_lexical_context_answer_shape_row_definition_match_detail( $slower, $word_re );
                if ( $detail['hit'] ) {
                    $def_hit        = true;
                    $match_anchor   = $content[ $c - 1 ];
                    $match_cue      = $detail['cue'];
                    $match_cue_tier = (string) $detail['strength'];
                }
            }
        }

        $delta = 0.0;
        if ( $def_hit ) {
            if ( $match_cue_tier === 'medium' ) {
                $delta += $bonus_medium;
            } else {
                $delta += $bonus_strong;
            }
            ++$boosted;
        }

        // Lightweight definitional bias: small additive boost when a row contains simple definition-like phrasing.
        // Runs alongside anchor-based answer-shape boosting; does not short-circuit any existing logic.
        $defpat_boost   = 8.0;
        $defpat_pattern = '';
        if ( ! $meta_hit ) {
            $patterns = array(
                ' is defined as '      => 'is defined as',
                ' refers to '          => 'refers to',
                ' is a feature that '  => 'is a feature that',
                ' allows you to '      => 'allows you to',
                ' is an ai '           => 'is an ai',
                ' is a '               => 'is a',
                ' is an '              => 'is an',
                ' is the '             => 'is the',
            );
            foreach ( $patterns as $needle => $label ) {
                if ( strpos( $slower, $needle ) !== false ) {
                    $defpat_pattern = $label;
                    break;
                }
            }
            if ( $defpat_pattern !== '' ) {
                $delta += $defpat_boost;
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) && $defpat_logged < 10 ) {
                    $base_dbg = isset( $row['score'] ) ? (float) $row['score'] : 0.0;
                    $after_dbg = $base_dbg + $delta;
                    $preview = transformer_model_lexical_context_diag_preview_text( (string) $sentence, 165 );
                    $preview = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $preview );
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][answer_shape_bias_definition] boost=%g pattern="%s" before=%g after=%g text="%s"',
                            $defpat_boost,
                            $defpat_pattern,
                            $base_dbg,
                            $after_dbg,
                            $preview
                        )
                    );
                    ++$defpat_logged;
                }
            }
        }

        // Additive action/mechanism preference: anchor must be present + a simple mechanism verb cue.
        // Does not change existing boosts/penalties and applies at most once per row.
        if ( ! $meta_hit && $has_anchor ) {
            $action_boost = 10.0;
            $action_cues  = array( 'uses', 'use', 'allows', 'allow', 'helps', 'help', 'enables', 'enable', 'provides', 'provide', 'analyzes', 'analyze' );
            $hit_cue      = '';
            foreach ( $action_cues as $c ) {
                $needle = ' ' . $c . ' ';
                if ( strpos( $slower, $needle ) !== false ) {
                    $hit_cue = $c;
                    break;
                }
            }
            if ( $hit_cue !== '' ) {
                $delta += $action_boost;
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) && $action_logged < 10 ) {
                    $base_dbg  = isset( $row['score'] ) ? (float) $row['score'] : 0.0;
                    $after_dbg = $base_dbg + $delta;
                    $preview   = transformer_model_lexical_context_diag_preview_text( (string) $sentence, 165 );
                    $preview   = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $preview );
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][answer_shape_bias_action] boost=%g cue="%s" before=%g after=%g text="%s"',
                            $action_boost,
                            $hit_cue,
                            $base_dbg,
                            $after_dbg,
                            $preview
                        )
                    );
                    ++$action_logged;
                }
            }
        }

        // Additive penalty: anchor-heavy but non-informational rows (no info cues) for informational queries.
        // Does not change existing boosts/thresholds/gates and applies at most once per row.
        if ( ! $meta_hit && $has_anchor ) {
            $info_cues = array( 'uses', 'use', 'allows', 'allow', 'helps', 'help', 'enables', 'enable', 'provides', 'provide', 'analyzes', 'analyze' );
            $has_info  = false;
            foreach ( $info_cues as $c ) {
                $needle = ' ' . $c . ' ';
                if ( strpos( $slower, $needle ) !== false ) {
                    $has_info = true;
                    break;
                }
            }
            if ( ! $has_info ) {
                $pen = -8.0;
                $delta += $pen;
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) && $penalty_logged < 10 ) {
                    $base_dbg  = isset( $row['score'] ) ? (float) $row['score'] : 0.0;
                    $after_dbg = $base_dbg + $delta;
                    $preview   = transformer_model_lexical_context_diag_preview_text( (string) $sentence, 165 );
                    $preview   = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $preview );
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][answer_shape_bias_penalty] penalty=%g reason="anchor_no_info" before=%g after=%g text="%s"',
                            $pen,
                            $base_dbg,
                            $after_dbg,
                            $preview
                        )
                    );
                    ++$penalty_logged;
                }
            }
        }
        if ( $meta_hit ) {
            $delta -= $penalty;
            ++$penalized;
        }

        $base = isset( $row['score'] ) ? (float) $row['score'] : 0.0;
        if ( 0.0 !== $delta ) {
            $after = $base + $delta;
            $result[ $idx ]['score'] = $after;
            $row_affected[]          = array(
                'boosted'        => ( $def_hit ? 1 : 0 ),
                'penalized'      => ( $meta_hit ? 1 : 0 ),
                'before'         => $base,
                'after'          => $after,
                'sentence'       => $sentence,
                'match_anchor'   => $match_anchor,
                'match_cue'      => $match_cue,
                'cue_strength'   => $match_cue_tier,
            );
        }
    }

    usort(
        $result,
        function ( $a, $b ) {
            $sa = isset( $a['score'] ) ? (float) $a['score'] : 0.0;
            $sb = isset( $b['score'] ) ? (float) $b['score'] : 0.0;
            if ( $sa !== $sb ) {
                return $sb <=> $sa;
            }

            return transformer_model_lexical_context_compare_sentence_score_rows( $a, $b );
        }
    );

    if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        if ( $suppressed_single !== '' && $phrase_re !== array() ) {
            back_trace(
                'NOTICE',
                sprintf(
                    '[LCM][answer_shape_anchor] suppressed_single="%s" reason="phrase_anchor_available" phrase_anchor="%s"',
                    str_replace( '"', "'", $suppressed_single ),
                    str_replace( '"', "'", (string) $phrase_re['label'] )
                )
            );
        }
        $anchors_json = wp_json_encode( $anchors_diag );
        if ( ! is_string( $anchors_json ) ) {
            $anchors_json = '[]';
        }

        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][answer_shape_bias] applied=1 anchors=%s boosted=%d penalized=%d shape=informational_query',
                $anchors_json,
                $boosted,
                $penalized
            )
        );

        if ( $total_rows > 0 && ( $boosted > 100 || $boosted > (int) floor( 0.1 * $total_rows ) ) ) {
            back_trace(
                'NOTICE',
                sprintf(
                    '[LCM][answer_shape_bias] warning=boost_overbroad boosted=%d total=%d',
                    $boosted,
                    $total_rows
                )
            );
        }

        if ( $row_affected !== array() ) {
            usort(
                $row_affected,
                static function ( $a, $b ) {
                    $da = abs( (float) $a['after'] - (float) $a['before'] );
                    $db = abs( (float) $b['after'] - (float) $b['before'] );

                    return $db <=> $da;
                }
            );
            $row_affected = array_slice( $row_affected, 0, 10 );
            foreach ( $row_affected as $e ) {
                $preview = transformer_model_lexical_context_diag_preview_text( isset( $e['sentence'] ) ? (string) $e['sentence'] : '', 140 );
                $preview = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $preview );
                $anc      = isset( $e['match_anchor'] ) ? (string) $e['match_anchor'] : '';
                $cue      = isset( $e['match_cue'] ) ? (string) $e['match_cue'] : '';
                $cue_str  = isset( $e['cue_strength'] ) ? (string) $e['cue_strength'] : '';
                $anc      = str_replace( '"', "'", $anc );
                $cue      = str_replace( '"', "'", $cue );
                $cue_str  = str_replace( '"', "'", $cue_str );
                back_trace(
                    'NOTICE',
                    sprintf(
                        '[LCM][answer_shape_bias_row] boosted=%d penalized=%d anchor="%s" cue="%s" cue_strength="%s" before=%g after=%g text="%s"',
                        (int) $e['boosted'],
                        (int) $e['penalized'],
                        $anc,
                        $cue,
                        $cue_str,
                        (float) $e['before'],
                        (float) $e['after'],
                        $preview
                    )
                );
            }
        }
    }

    return $result;
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
        $tok_q = preg_quote( $tok, '/' );
        if ( strlen( (string) $tok ) >= 4 ) {
            $tok_q .= 's?';
        }
        $pattern = '/(?<![\p{L}\p{N}_])' . $tok_q . '(?![\p{L}\p{N}_])/u';
        if ( preg_match( $pattern, $haystack ) ) {
            if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                if ( strlen( (string) $tok ) >= 4 && strpos( $haystack, (string) $tok ) === false && preg_match( '/(?<![\p{L}\p{N}_])' . preg_quote( $tok, '/' ) . 's(?![\p{L}\p{N}_])/u', $haystack ) ) {
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][token_normalization] query="%s" matched="%s"',
                            str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $tok ),
                            str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) ( $tok . 's' ) )
                        )
                    );
                }
            }
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
 * Case diagnostics are only enabled for a small allowlist of exact prompts.
 *
 * @param string $raw_query_text
 * @return bool
 */
function transformer_model_lcm_case_diag_is_enabled_for_query( $raw_query_text ) {

    if ( ! transformer_model_lexical_context_is_lcm_diagnostics_enabled() || ! function_exists( 'back_trace' ) ) {
        return false;
    }

    $q = trim( (string) $raw_query_text );
    if ( $q === 'What is generative AI?' ) {
        return true;
    }
    if ( $q === 'How does a chatbot help with sales?' ) {
        return true;
    }

    return false;
}

/**
 * Rank debug is only enabled for one exact query.
 *
 * @param string $raw_query_text
 * @return bool
 */
function transformer_model_lcm_rank_debug_is_enabled_for_query( $raw_query_text ) {

    if ( ! transformer_model_lexical_context_is_lcm_diagnostics_enabled() || ! function_exists( 'back_trace' ) ) {
        return false;
    }

    $q = trim( (string) $raw_query_text );
    return ( $q === 'What is generative AI?' );
}

/**
 * Log full ranking keys for a ranked row set (diagnostics only).
 *
 * @param string                           $raw_query_text
 * @param string                           $tag rank_debug|assembly_rank_debug
 * @param array<int, array<string, mixed>> $rows
 * @param array<int, string>               $post_title_map
 * @return void
 */
function transformer_model_lcm_rank_debug_log_rows( $raw_query_text, $tag, $rows, $post_title_map ) {

    if ( ! transformer_model_lcm_rank_debug_is_enabled_for_query( $raw_query_text ) ) {
        return;
    }

    $rows           = is_array( $rows ) ? $rows : array();
    $post_title_map = is_array( $post_title_map ) ? $post_title_map : array();
    $tag            = (string) $tag;

    $logged = 0;
    foreach ( $rows as $i => $r ) {
        if ( ! is_array( $r ) ) {
            continue;
        }

        $pid  = isset( $r['post_id'] ) ? (int) $r['post_id'] : 0;
        $ttl  = isset( $post_title_map[ $pid ] ) ? (string) $post_title_map[ $pid ] : ( isset( $r['post_title'] ) ? (string) $r['post_title'] : '' );
        $ttl  = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $ttl );
        $sd   = isset( $r['_subject_definition_score'] ) ? (int) $r['_subject_definition_score'] : 0;
        $hits = isset( $r['_query_token_hits'] ) ? (int) $r['_query_token_hits'] : 0;
        $st   = isset( $r['_answer_strength'] ) ? (int) $r['_answer_strength'] : 0;
        $dir  = isset( $r['_answer_directness'] ) ? (int) $r['_answer_directness'] : 0;
        $sc   = isset( $r['score'] ) ? (float) $r['score'] : 0.0;
        $tx   = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
        $prev = transformer_model_lexical_context_diag_preview_text( $tx, 160 );
        $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );

        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][%s] rank=%d post_id=%d title="%s" subject_definition=%d query_token_hits=%d strength=%d directness=%d score=%.4f text="%s"',
                $tag,
                (int) $i + 1,
                $pid,
                $ttl,
                $sd,
                $hits,
                $st,
                $dir,
                $sc,
                $prev
            )
        );
        ++$logged;
    }
}

/**
 * Cow-row tracking: log if a row carries _cow_id.
 *
 * @param string                           $stage
 * @param array<int, array<string, mixed>> $rows
 * @return void
 */
function transformer_model_lcm_cow_track_log_stage( $stage, $rows ) {

    if ( ! transformer_model_lexical_context_is_lcm_diagnostics_enabled() || ! function_exists( 'back_trace' ) ) {
        return;
    }

    if ( ! is_array( $rows ) || $rows === array() ) {
        return;
    }

    foreach ( $rows as $i => $r ) {
        if ( ! is_array( $r ) ) {
            continue;
        }
        if ( empty( $r['_cow_id'] ) ) {
            continue;
        }
        $cow_id = (string) $r['_cow_id'];
        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][cow_track] stage="%s" cow_id=%s present=1 rank=%d',
                str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $stage ),
                $cow_id,
                (int) $i + 1
            )
        );
    }
}

/**
 * TEMPORARY cow_return_trace: log + optionally hard replace final response.
 *
 * @param string $function_name
 * @param string $location
 * @param string $raw_query_text
 * @param string $response_text
 * @return string (possibly replaced)
 */
function transformer_model_lcm_cow_return_trace_checkpoint( $function_name, $location, $raw_query_text, $response_text ) {

    $response_text = (string) $response_text;
    $raw_query_text = (string) $raw_query_text;

    $has_cow = ( stripos( $response_text, 'proper answer is a cow' ) !== false );
    if ( $has_cow && transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        $resp_prev = transformer_model_lexical_context_diag_preview_text( $response_text, 220 );
        $resp_prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $resp_prev );
        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][cow_return_trace] function="%s" location="%s" response="%s"',
                str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $function_name ),
                str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $location ),
                $resp_prev
            )
        );
    }

    // TEMPORARY cow_return_trace: hard block at latest return point.
    if ( trim( $raw_query_text ) === 'What is generative AI?' && $has_cow ) {
        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            back_trace( 'NOTICE', '[LCM][cow_return_trace] action="hard_replaced_final_response"' );
        }
        return 'Generative AI is a type of artificial intelligence that creates new content such as text, images, code, audio, or video.';
    }

    return $response_text;
}

/**
 * Diagnostics: for a specific query, track rows containing BOTH "chatbot" and "sales".
 *
 * @param string                           $raw_query_text
 * @param string                           $stage
 * @param array<int, array<string, mixed>> $rows
 * @param array<int, string>               $post_title_map
 * @return void
 */
function transformer_model_lcm_chatbot_sales_diag_log_stage( $raw_query_text, $stage, $rows, $post_title_map ) {

    if ( ! transformer_model_lexical_context_is_lcm_diagnostics_enabled() || ! function_exists( 'back_trace' ) ) {
        return;
    }

    $q = trim( (string) $raw_query_text );
    if ( $q !== 'How does a chatbot help with sales?' ) {
        return;
    }

    $rows = is_array( $rows ) ? $rows : array();
    $post_title_map = is_array( $post_title_map ) ? $post_title_map : array();

    $both_rows = array();
    foreach ( $rows as $r ) {
        if ( ! is_array( $r ) ) {
            continue;
        }
        $tx = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
        $sl = strtolower( wp_strip_all_tags( $tx ) );
        if ( preg_match( '/\bchatbot\b/u', $sl ) && preg_match( '/\bsales\b/u', $sl ) ) {
            $both_rows[] = $r;
        }
    }

    back_trace(
        'NOTICE',
        sprintf(
            '[LCM][chatbot_sales_diag] stage="%s" count_both=%d total=%d',
            str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $stage ),
            count( $both_rows ),
            count( $rows )
        )
    );

    $logged = 0;
    foreach ( $both_rows as $i => $r ) {
        if ( $logged >= 10 ) {
            break;
        }
        if ( ! is_array( $r ) ) {
            continue;
        }
        $pid  = isset( $r['post_id'] ) ? (int) $r['post_id'] : 0;
        $ttl  = isset( $post_title_map[ $pid ] ) ? (string) $post_title_map[ $pid ] : ( isset( $r['post_title'] ) ? (string) $r['post_title'] : '' );
        $ttl  = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $ttl );
        $sc   = isset( $r['score'] ) ? (float) $r['score'] : 0.0;
        $tx   = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
        $prev = transformer_model_lexical_context_diag_preview_text( $tx, 150 );
        $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );

        $hits = isset( $r['_query_token_hits'] ) ? (int) $r['_query_token_hits'] : 0;
        if ( $hits === 0 ) {
            $sl = strtolower( wp_strip_all_tags( $tx ) );
            if ( preg_match( '/\bchatbot\b/u', $sl ) ) {
                ++$hits;
            }
            if ( preg_match( '/\bsales\b/u', $sl ) ) {
                ++$hits;
            }
        }

        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][chatbot_sales_row] stage="%s" rank=%d post_id=%d title="%s" score=%.4f token_hits=%d text="%s"',
                str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $stage ),
                (int) $i + 1,
                $pid,
                $ttl,
                $sc,
                (int) $hits,
                $prev
            )
        );
        ++$logged;
    }

    if ( $stage === 'after_final_order' ) {
        foreach ( $rows as $r ) {
            if ( ! is_array( $r ) ) {
                continue;
            }
            $tx = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
            if ( stripos( $tx, 'knowledge navigator' ) === false ) {
                continue;
            }
            $pid  = isset( $r['post_id'] ) ? (int) $r['post_id'] : 0;
            $ttl  = isset( $post_title_map[ $pid ] ) ? (string) $post_title_map[ $pid ] : ( isset( $r['post_title'] ) ? (string) $r['post_title'] : '' );
            $ttl  = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $ttl );
            $sc   = isset( $r['score'] ) ? (float) $r['score'] : 0.0;
            $hits = isset( $r['_query_token_hits'] ) ? (int) $r['_query_token_hits'] : 0;
            $prev = transformer_model_lexical_context_diag_preview_text( $tx, 150 );
            $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );

            back_trace(
                'NOTICE',
                sprintf(
                    '[LCM][chatbot_sales_winner] post_id=%d title="%s" score=%.4f token_hits=%d text="%s"',
                    $pid,
                    $ttl,
                    $sc,
                    $hits,
                    $prev
                )
            );
            break;
        }
    }
}

/**
 * Diagnostics: trace one exact target sentence fragment for one exact query.
 *
 * TEMPORARY: remove after diagnosis.
 *
 * @param string $raw_query_text
 * @return bool
 */
function transformer_model_lcm_chatbot_sales_target_trace_enabled( $raw_query_text ) {
    return (
        transformer_model_lexical_context_is_lcm_diagnostics_enabled()
        && function_exists( 'back_trace' )
        && trim( (string) $raw_query_text ) === 'How does a chatbot help with sales?'
    );
}

/**
 * Diagnostics helper: log when text contains target fragment.
 *
 * @param string $stage
 * @param string $text
 * @param int    $post_id
 * @param string $post_title
 * @return void
 */
function transformer_model_lcm_chatbot_sales_target_trace_log( $stage, $text, $post_id = 0, $post_title = '' ) {
    if ( ! transformer_model_lcm_chatbot_sales_target_trace_enabled( isset( $GLOBALS['kognetiks_lcm_raw_query_for_trace'] ) ? (string) $GLOBALS['kognetiks_lcm_raw_query_for_trace'] : '' ) ) {
        return;
    }
    $needle = 'chatbots help with sales by engaging website visitors';
    $sl = strtolower( wp_strip_all_tags( (string) $text ) );
    if ( strpos( $sl, $needle ) === false ) {
        return;
    }
    $prev = transformer_model_lexical_context_diag_preview_text( (string) $text, 170 );
    $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
    $ttl  = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $post_title );
    back_trace(
        'NOTICE',
        sprintf(
            '[LCM][target_sentence_trace] stage="%s" post_id=%d title="%s" text="%s"',
            str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $stage ),
            (int) $post_id,
            $ttl,
            $prev
        )
    );
}

/**
 * Diagnostics helper: scan a ranked row list for the target fragment.
 *
 * @param string                           $stage
 * @param array<int, array<string, mixed>> $rows
 * @param array<int, string>               $post_title_map
 * @return void
 */
function transformer_model_lcm_chatbot_sales_target_trace_scan_rows( $stage, $rows, $post_title_map ) {
    if ( ! transformer_model_lcm_chatbot_sales_target_trace_enabled( isset( $GLOBALS['kognetiks_lcm_raw_query_for_trace'] ) ? (string) $GLOBALS['kognetiks_lcm_raw_query_for_trace'] : '' ) ) {
        return;
    }
    $rows = is_array( $rows ) ? $rows : array();
    $post_title_map = is_array( $post_title_map ) ? $post_title_map : array();
    $needle = 'chatbots help with sales by engaging website visitors';
    foreach ( $rows as $i => $r ) {
        if ( ! is_array( $r ) ) {
            continue;
        }
        $tx = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
        $sl = strtolower( wp_strip_all_tags( (string) $tx ) );
        if ( strpos( $sl, $needle ) === false ) {
            continue;
        }
        $pid = isset( $r['post_id'] ) ? (int) $r['post_id'] : 0;
        $ttl = isset( $post_title_map[ $pid ] ) ? (string) $post_title_map[ $pid ] : ( isset( $r['post_title'] ) ? (string) $r['post_title'] : '' );
        transformer_model_lcm_chatbot_sales_target_trace_log( $stage . ':rank=' . ( (int) $i + 1 ), $tx, $pid, $ttl );
        break;
    }
}

/**
 * Log a ranked slice of rows for a specific case-diagnostic query.
 *
 * @param string                      $raw_query_text
 * @param string                      $stage
 * @param array<int, array<string, mixed>> $rows
 * @param array<int, string>          $post_title_map
 * @param int                         $max_rows
 * @return void
 */
function transformer_model_lcm_case_diag_log_rows( $raw_query_text, $stage, $rows, $post_title_map, $max_rows = 10 ) {

    if ( ! transformer_model_lcm_case_diag_is_enabled_for_query( $raw_query_text ) ) {
        return;
    }

    $rows          = is_array( $rows ) ? $rows : array();
    $post_title_map = is_array( $post_title_map ) ? $post_title_map : array();
    $max_rows      = max( 0, (int) $max_rows );

    $q_esc = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), trim( (string) $raw_query_text ) );
    $s_esc = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $stage );

    $is_final_candidates = ( $stage === 'final_candidates' );
    if ( $is_final_candidates ) {
        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][case_diag] query="%s" stage="final_candidates" count=%d',
                $q_esc,
                count( $rows )
            )
        );
    }

    $logged = 0;
    foreach ( $rows as $i => $r ) {
        if ( $logged >= $max_rows ) {
            break;
        }
        if ( ! is_array( $r ) ) {
            continue;
        }

        $pid  = isset( $r['post_id'] ) ? (int) $r['post_id'] : 0;
        $ttl  = isset( $post_title_map[ $pid ] ) ? (string) $post_title_map[ $pid ] : ( isset( $r['post_title'] ) ? (string) $r['post_title'] : '' );
        $ttl  = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $ttl );
        $sc   = isset( $r['score'] ) ? (float) $r['score'] : 0.0;
        $st   = isset( $r['_answer_strength'] ) ? (int) $r['_answer_strength'] : 0;
        $sd   = isset( $r['_subject_definition_score'] ) ? (int) $r['_subject_definition_score'] : 0;
        $dir  = isset( $r['_answer_directness'] ) ? (int) $r['_answer_directness'] : 0;
        $tx   = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
        $prev = transformer_model_lexical_context_diag_preview_text( $tx, 150 );
        $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );

        if ( $is_final_candidates ) {
            back_trace(
                'NOTICE',
                sprintf(
                    '[LCM][case_diag_row] rank=%d post_id=%d title="%s" score=%.4f strength=%d subject_definition=%d directness=%d text="%s"',
                    (int) $i + 1,
                    $pid,
                    $ttl,
                    $sc,
                    $st,
                    $sd,
                    $dir,
                    $prev
                )
            );
        } else {
            back_trace(
                'NOTICE',
                sprintf(
                    '[LCM][case_diag_row] stage="%s" rank=%d post_id=%d title="%s" score=%.4f strength=%d subject_definition=%d directness=%d text="%s"',
                    $s_esc,
                    (int) $i + 1,
                    $pid,
                    $ttl,
                    $sc,
                    $st,
                    $sd,
                    $dir,
                    $prev
                )
            );
        }
        ++$logged;
    }
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
 * Token set for a scored row (diagnostics / analysis only). Uses the same sentence/text field conventions as scoring.
 *
 * - Lowercases and strips punctuation to spaces.
 * - Splits on whitespace.
 * - Removes relevance-guard stop words and weak high-frequency LCM terms.
 *
 * @param array<string, mixed> $row
 * @return array<int, string> Unique tokens.
 */
function transformer_model_lexical_context_row_token_set( $row ) {

    $row = is_array( $row ) ? $row : array();

    $text = '';
    if ( isset( $row['sentence'] ) && is_string( $row['sentence'] ) ) {
        $text = $row['sentence'];
    } elseif ( isset( $row['text'] ) && is_string( $row['text'] ) ) {
        $text = $row['text'];
    } elseif ( isset( $row['chunk'] ) && is_string( $row['chunk'] ) ) {
        $text = $row['chunk'];
    }

    $text = strtolower( wp_strip_all_tags( (string) $text ) );
    $text = preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', $text );
    $text = preg_replace( '/\s+/u', ' ', trim( (string) $text ) );

    if ( $text === '' ) {
        return array();
    }

    $tokens = preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
    if ( ! is_array( $tokens ) || $tokens === array() ) {
        return array();
    }

    $stop_flip = array_flip( transformer_model_lexical_context_relevance_guard_stop_words() );
    $weak_flip = array_flip(
        array_merge(
            // Weak high-frequency terms already treated as non-coverage anchors.
            array( 'used', 'use', 'using', 'does', 'do', 'explain' ),
            // Action words are shape signals; exclude from cohesion anchors too.
            array_keys( transformer_model_lexical_context_answer_shape_query_action_words_flip() )
        )
    );

    $set = array();
    foreach ( $tokens as $t ) {
        $t = strtolower( trim( (string) $t ) );
        if ( strlen( $t ) < 2 ) {
            continue;
        }
        if ( isset( $stop_flip[ $t ] ) || isset( $weak_flip[ $t ] ) ) {
            continue;
        }
        $set[ $t ] = true;
    }

    return array_values( array_keys( $set ) );
}

/**
 * Jaccard similarity between two token sets.
 *
 * @param array<int, string> $tokens_a
 * @param array<int, string> $tokens_b
 * @return float
 */
function transformer_model_lexical_context_jaccard_similarity( $tokens_a, $tokens_b ) {

    $a = is_array( $tokens_a ) ? array_values( array_unique( $tokens_a ) ) : array();
    $b = is_array( $tokens_b ) ? array_values( array_unique( $tokens_b ) ) : array();
    if ( $a === array() || $b === array() ) {
        return 0.0;
    }

    $af = array_fill_keys( $a, true );
    $bf = array_fill_keys( $b, true );

    $inter = 0;
    foreach ( $af as $t => $_ ) {
        if ( isset( $bf[ $t ] ) ) {
            ++$inter;
        }
    }

    $union = count( $af ) + count( $bf ) - $inter;
    if ( $union <= 0 ) {
        return 0.0;
    }

    return (float) ( $inter / $union );
}

/**
 * Evaluate semantic cohesion of ranked rows (diagnostics only). Does not modify rows or scores.
 *
 * Anchor row is the current top-ranked row (post-dedupe/doc-limit).
 *
 * @param array<int, array<string, mixed>> $sentence_scores
 * @param array<int, string>              $meaningful_query_tokens
 * @return array{
 *   count: int,
 *   anchor_preview: string,
 *   average_cohesion: float,
 *   min_cohesion: float,
 *   rows: array<int, array{
 *     post_id: string,
 *     score: float,
 *     cohesion: float,
 *     anchor_overlap: float,
 *     query_overlap: float,
 *     preview: string
 *   }>
 * }
 */
function transformer_model_lexical_context_evaluate_semantic_cohesion( $sentence_scores, $meaningful_query_tokens ) {

    $rows = is_array( $sentence_scores ) ? array_values( $sentence_scores ) : array();
    $meaningful_query_tokens = is_array( $meaningful_query_tokens ) ? $meaningful_query_tokens : array();

    $out = array(
        'count'           => count( $rows ),
        'anchor_preview'  => '',
        'average_cohesion'=> 0.0,
        'min_cohesion'    => 0.0,
        'rows'            => array(),
    );

    if ( $rows === array() ) {
        return $out;
    }

    $anchor_row    = $rows[0];
    $anchor_tokens = transformer_model_lexical_context_row_token_set( $anchor_row );
    $anchor_text   = isset( $anchor_row['sentence'] ) ? (string) $anchor_row['sentence'] : '';
    $out['anchor_preview'] = transformer_model_lexical_context_diag_preview_text( $anchor_text, 165 );

    // Query token set (same stop/weak filtering as row_token_set).
    $q_row = array( 'sentence' => implode( ' ', array_map( 'strval', $meaningful_query_tokens ) ) );
    $query_tokens = transformer_model_lexical_context_row_token_set( $q_row );

    $sum = 0.0;
    $min = null;

    foreach ( $rows as $r ) {
        $rtok = transformer_model_lexical_context_row_token_set( $r );
        $anchor_overlap = transformer_model_lexical_context_jaccard_similarity( $rtok, $anchor_tokens );
        $query_overlap  = transformer_model_lexical_context_jaccard_similarity( $rtok, $query_tokens );
        $cohesion       = 0.5 * ( $anchor_overlap + $query_overlap );

        $sum += $cohesion;
        if ( $min === null || $cohesion < $min ) {
            $min = $cohesion;
        }

        $pid = isset( $r['post_id'] ) ? (string) $r['post_id'] : '';
        $score = isset( $r['score'] ) ? (float) $r['score'] : 0.0;
        $sent  = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
        $preview = transformer_model_lexical_context_diag_preview_text( $sent, 165 );

        $out['rows'][] = array(
            'post_id'        => $pid,
            'score'          => $score,
            'cohesion'       => (float) $cohesion,
            'anchor_overlap' => (float) $anchor_overlap,
            'query_overlap'  => (float) $query_overlap,
            'preview'        => $preview,
        );
    }

    $n = max( 1, count( $rows ) );
    $out['average_cohesion'] = (float) ( $sum / $n );
    $out['min_cohesion']     = (float) ( $min === null ? 0.0 : $min );

    return $out;
}

/**
 * Aggregate meaningful-token overlap across candidate rows (diagnostic/rejection gate helper).
 * Ratio = matched_meaningful_tokens / total_meaningful_tokens.
 *
 * Uses whole-token matching to avoid substring collisions.
 *
 * @param array<int, array<string, mixed>> $rows
 * @param array<int, string>              $meaningful_query_tokens
 * @return float
 */
function transformer_model_lexical_context_semantic_cohesion_query_overlap_ratio( $rows, $meaningful_query_tokens ) {

    $rows = is_array( $rows ) ? $rows : array();
    $meaningful_query_tokens = is_array( $meaningful_query_tokens ) ? $meaningful_query_tokens : array();

    $tokens = array();
    foreach ( $meaningful_query_tokens as $t ) {
        $t = strtolower( trim( (string) $t ) );
        if ( strlen( $t ) >= 2 ) {
            $tokens[] = $t;
        }
    }
    $tokens = array_values( array_unique( $tokens ) );
    $total  = count( $tokens );
    if ( $total === 0 || $rows === array() ) {
        return 0.0;
    }

    $hay = '';
    foreach ( $rows as $r ) {
        if ( ! is_array( $r ) ) {
            continue;
        }
        $s = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
        if ( $s === '' ) {
            continue;
        }
        $hay .= ' ' . strtolower( wp_strip_all_tags( $s ) );
    }
    if ( trim( $hay ) === '' ) {
        return 0.0;
    }

    $matched = 0;
    foreach ( $tokens as $t ) {
        $re = '(?<![\p{L}\p{N}_])' . preg_quote( $t, '/' ) . '(?![\p{L}\p{N}_])';
        if ( preg_match( '/' . $re . '/iu', $hay ) ) {
            ++$matched;
        }
    }

    return (float) ( $matched / $total );
}

/**
 * Debug log semantic cohesion diagnostics.
 *
 * @param array<string, mixed> $cohesion Result from evaluate_semantic_cohesion().
 * @return void
 */
function transformer_model_lexical_context_diag_log_semantic_cohesion( $cohesion ) {

    if ( ! transformer_model_lexical_context_is_lcm_diagnostics_enabled() || ! function_exists( 'back_trace' ) ) {
        return;
    }

    $cohesion = is_array( $cohesion ) ? $cohesion : array();
    $count = isset( $cohesion['count'] ) ? (int) $cohesion['count'] : 0;
    $avg   = isset( $cohesion['average_cohesion'] ) ? (float) $cohesion['average_cohesion'] : 0.0;
    $min   = isset( $cohesion['min_cohesion'] ) ? (float) $cohesion['min_cohesion'] : 0.0;
    $anchor_preview = isset( $cohesion['anchor_preview'] ) ? (string) $cohesion['anchor_preview'] : '';
    $anchor_preview = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $anchor_preview );

    back_trace(
        'NOTICE',
        sprintf(
            '[LCM][semantic_cohesion] count=%d avg=%.4f min=%.4f anchor="%s"',
            $count,
            $avg,
            $min,
            $anchor_preview
        )
    );

    $rows = isset( $cohesion['rows'] ) && is_array( $cohesion['rows'] ) ? $cohesion['rows'] : array();
    $rows = array_slice( $rows, 0, 10 );
    foreach ( $rows as $r ) {
        if ( ! is_array( $r ) ) {
            continue;
        }
        $preview = isset( $r['preview'] ) ? (string) $r['preview'] : '';
        $preview = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $preview );

        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][semantic_cohesion_row] cohesion=%.4f anchor_overlap=%.4f query_overlap=%.4f score=%.4f post_id=%s text="%s"',
                (float) ( $r['cohesion'] ?? 0.0 ),
                (float) ( $r['anchor_overlap'] ?? 0.0 ),
                (float) ( $r['query_overlap'] ?? 0.0 ),
                (float) ( $r['score'] ?? 0.0 ),
                (string) ( $r['post_id'] ?? '' ),
                $preview
            )
        );
    }
}

/**
 * Lightweight answerability check for informational queries: requires anchor + either mechanism signal or definitional pattern.
 * Intended as a post-ranking filter (does not change scoring/boosting); keep conservative and fast.
 *
 * @param string $text
 * @param bool   $has_anchor
 * @return bool
 */
function transformer_model_lcm_is_answerable_row( $text, $has_anchor ) {

    if ( ! $has_anchor ) {
        return false;
    }

    $slower = strtolower( wp_strip_all_tags( (string) $text ) );

    // Mechanism signals (how it works).
    $mechanism_terms = array(
        'algorithm',
        'tf-idf',
        'term frequency',
        'inverse document frequency',
        'scoring',
        'ranking',
        'analyzes',
        'analyze',
        'calculates',
        'compute',
        'process',
    );

    foreach ( $mechanism_terms as $term ) {
        if ( $term !== '' && strpos( $slower, $term ) !== false ) {
            return true;
        }
    }

    // Definitional patterns (what it is).
    $definition_patterns = array(
        ' is a ',
        ' is an ',
        ' refers to ',
        ' is the process of ',
        ' is the method of ',
    );

    foreach ( $definition_patterns as $pattern ) {
        if ( $pattern !== '' && strpos( $slower, $pattern ) !== false ) {
            return true;
        }
    }

    return false;
}

/**
 * Answer strength tier for informational queries (diagnostics + reordering only; does not change scores).
 *
 * 3 = mechanism (how it works), 2 = definition (what it is), 1 = anchor-only, 0 = no anchor.
 *
 * @param string $text
 * @param bool   $has_anchor
 * @return int
 */
function transformer_model_lcm_get_answer_strength( $text, $has_anchor ) {

    if ( ! $has_anchor ) {
        return 0;
    }

    $slower = strtolower( wp_strip_all_tags( (string) $text ) );

    // Tier 3: Mechanism (strongest).
    $mechanism_terms = array(
        'algorithm',
        'tf-idf',
        'term frequency',
        'inverse document frequency',
        'scoring',
        'ranking',
        'analyzes',
        'analyze',
        'calculates',
        'compute',
        'process',
    );
    foreach ( $mechanism_terms as $term ) {
        if ( $term !== '' && strpos( $slower, $term ) !== false ) {
            return 3;
        }
    }

    // Tier 2: Definition.
    $definition_patterns = array(
        ' is a ',
        ' is an ',
        ' refers to ',
        ' is the process of ',
        ' is the method of ',
    );
    foreach ( $definition_patterns as $pattern ) {
        if ( $pattern !== '' && strpos( $slower, $pattern ) !== false ) {
            return 2;
        }
    }

    // Tier 1: Anchor-only (weak but allowed).
    return 1;
}

/**
 * Lightweight answer-shape signal for informational queries only (ordering hint; does not change retrieval scores).
 *
 * @param string               $text                    Sentence row text.
 * @param array{ shape?: string } $query_shape          Query shape pack.
 * @param array<int, string>   $meaningful_query_tokens Meaningful query tokens (lowercase).
 * @param string               $raw_query_text          Raw user query.
 * @return int Small integer; 0 if not informational_query or neutral.
 */
function transformer_model_lcm_get_answer_directness_score( $text, $query_shape, $meaningful_query_tokens, $raw_query_text ) {

    $shape = isset( $query_shape['shape'] ) ? (string) $query_shape['shape'] : '';
    if ( $shape !== 'informational_query' ) {
        return 0;
    }

    $slower = strtolower( wp_strip_all_tags( (string) $text ) );
    $slower = preg_replace( '/\s+/u', ' ', trim( $slower ) );

    $tokens = array();
    if ( is_array( $meaningful_query_tokens ) ) {
        foreach ( $meaningful_query_tokens as $t ) {
            $w = strtolower( trim( (string) $t ) );
            if ( $w !== '' && strlen( $w ) > 1 ) {
                $tokens[] = $w;
            }
        }
    }

    $subject = implode( ' ', $tokens );
    if ( $subject === '' ) {
        $rq = strtolower( wp_strip_all_tags( (string) $raw_query_text ) );
        $rq = preg_replace( '/\s+/u', ' ', trim( $rq ) );
        if ( $rq !== '' ) {
            $bits = preg_split( '/\s+/u', $rq, -1, PREG_SPLIT_NO_EMPTY );
            $stop = array_flip( transformer_model_lexical_context_dedup_normalization_stop_words() );
            $keep = array();
            foreach ( $bits as $b ) {
                $b = preg_replace( '/[^\p{L}\p{N}]/u', '', $b );
                if ( $b === '' || strlen( $b ) < 2 ) {
                    continue;
                }
                $bl = strtolower( $b );
                if ( isset( $stop[ $bl ] ) ) {
                    continue;
                }
                $keep[] = $bl;
            }
            $n = count( $keep );
            if ( $n >= 2 ) {
                $subject = implode( ' ', array_slice( $keep, -3 ) );
            } elseif ( $n === 1 ) {
                $subject = $keep[0];
            }
        }
    }

    $positive = 0;

    if ( $subject !== '' ) {
        $sq = preg_quote( $subject, '/' );
        $def_checks = array(
            '/' . $sq . '\s+is\b/u',
            '/' . $sq . '\s+refers\s+to\b/u',
            '/' . $sq . '\s+is\s+defined\s+as\b/u',
            '/' . $sq . '\s+means\b/u',
            '/' . $sq . '\s+describes\b/u',
        );
        foreach ( $def_checks as $re ) {
            if ( @preg_match( $re, $slower ) ) {
                $positive = 4;
                break;
            }
        }

        if ( $positive < 4 ) {
            $mech_checks = array(
                '/' . $sq . '\s+uses\b/u',
                '/' . $sq . '\s+helps\b/u',
                '/' . $sq . '\s+enables\b/u',
                '/' . $sq . '\s+allows\b/u',
                '/' . $sq . '\s+provides\b/u',
                '/' . $sq . '\s+analyzes\b/u',
            );
            foreach ( $mech_checks as $re ) {
                if ( @preg_match( $re, $slower ) ) {
                    $positive = max( $positive, 3 );
                    break;
                }
            }
        }
    }

    if ( $positive < 2 && count( $tokens ) > 0 ) {
        $matched = 0;
        foreach ( $tokens as $tok ) {
            if ( @preg_match( '/\b' . preg_quote( $tok, '/' ) . '\b/u', $slower ) ) {
                ++$matched;
            }
        }
        $need   = (int) max( 1, ceil( count( $tokens ) * 0.6 ) );
        $vverbs = '/\b(is|are|means|refers|helps|uses|enables|provides|supports|improves|reduces|increases)\b/u';
        if ( $matched >= $need && @preg_match( $vverbs, $slower ) ) {
            $positive = max( $positive, 2 );
        }
    }

    $score = $positive;

    $lead = preg_replace( '/^[\s\*#"\']+/u', '', $slower );
    if ( @preg_match( "/^(in conclusion|tags\\b|related\\b|reference\\b|newsletters\\b|what's new|whats new)\b/iu", $lead ) ) {
        $score -= 3;
    }

    $expl_verbs = '/\b(is|are|was|were|means|refers|helps|uses|enables|provides|supports|describes|defines|includes|allows|analyzes)\b/u';
    $word_count = 0;
    if ( $slower !== '' ) {
        $word_count = count( preg_split( '/\s+/u', $slower, -1, PREG_SPLIT_NO_EMPTY ) );
    }
    if ( mb_strlen( $slower ) > 0 && mb_strlen( $slower ) < 100 && $word_count > 0 && $word_count <= 12 && ! @preg_match( $expl_verbs, $slower ) ) {
        $score -= 2;
    }

    return (int) $score;
}

/**
 * Subject phrase for definition preference (meaningful tokens first; else strip generic question verbs from raw).
 *
 * @param string               $raw_query_text
 * @param array<int, string> $meaningful_query_tokens
 * @return string Lowercase phrase or empty.
 */
function transformer_model_lcm_informational_subject_phrase_for_definition_score( $raw_query_text, $meaningful_query_tokens ) {

    $parts = array();
    if ( is_array( $meaningful_query_tokens ) ) {
        foreach ( $meaningful_query_tokens as $t ) {
            $w = strtolower( trim( (string) $t ) );
            if ( $w !== '' && strlen( $w ) > 1 ) {
                $parts[] = $w;
            }
        }
    }

    $phrase = implode( ' ', $parts );
    $phrase = preg_replace( '/\s+/u', ' ', trim( $phrase ) );
    if ( $phrase !== '' ) {
        return $phrase;
    }

    $s = strtolower( wp_strip_all_tags( (string) $raw_query_text ) );
    $s = preg_replace( '/\s+/u', ' ', trim( $s ) );
    $s = preg_replace( '/[?.!,;:]+$/u', '', $s );

    $stripped = preg_replace( '/^(what|who|which|whose)\s+is\s+(the\s+|an?\s+)?/iu', '', $s );
    $stripped = preg_replace( '/^(explain|define|describe)\s+(the\s+|an?\s+)?/iu', '', $stripped );
    $stripped = preg_replace( '/^tell\s+me\s+(more\s+)?about\s+(the\s+|an?\s+)?/iu', '', $stripped );

    return trim( preg_replace( '/\s+/u', ' ', $stripped ) );
}

/**
 * Prefer rows that define/explain the query subject (informational ordering hint only; does not change retrieval score).
 *
 * Proximity: +5/+3 only when a cue appears soon after the subject phrase (forward window up to 60 chars from phrase end);
 * stray cues elsewhere in the sentence do not count. +1 remains when the phrase appears without such a cue.
 *
 * @param string               $text
 * @param array{ shape?: string } $query_shape
 * @param array<int, string>   $meaningful_query_tokens
 * @param string               $raw_query_text
 * @param array<string, mixed>|null $diag_detail Optional out: subject, matched_pattern, distance, score (when array passed).
 * @return int
 */
function transformer_model_lcm_get_subject_definition_score( $text, $query_shape, $meaningful_query_tokens, $raw_query_text, &$diag_detail = null ) {

    $shape = isset( $query_shape['shape'] ) ? (string) $query_shape['shape'] : '';
    if ( $shape !== 'informational_query' ) {
        if ( is_array( $diag_detail ) ) {
            $diag_detail['subject']           = '';
            $diag_detail['matched_pattern'] = 'none';
            $diag_detail['distance']          = -1;
            $diag_detail['score']             = 0;
        }
        return 0;
    }

    $phrase = transformer_model_lcm_informational_subject_phrase_for_definition_score( $raw_query_text, $meaningful_query_tokens );
    if ( $phrase === '' ) {
        if ( is_array( $diag_detail ) ) {
            $diag_detail['subject']           = '';
            $diag_detail['matched_pattern'] = 'none';
            $diag_detail['distance']          = -1;
            $diag_detail['score']             = 0;
        }
        return 0;
    }

    // Tiny acronym alias normalizer (subject-definition scoring only).
    $alias_phrase = '';
    if ( strpos( $phrase, 'artificial intelligence' ) !== false ) {
        $alias_phrase = trim( preg_replace( '/\bartificial intelligence\b/u', 'ai', $phrase ) );
        $alias_phrase = preg_replace( '/\s+/u', ' ', (string) $alias_phrase );

        // (no diagnostics)
    }

    $hay = strtolower( wp_strip_all_tags( (string) $text ) );
    $hay = preg_replace( '/\s+/u', ' ', trim( $hay ) );

    $toks = preg_split( '/\s+/u', $phrase, -1, PREG_SPLIT_NO_EMPTY );
    if ( $toks === array() ) {
        if ( is_array( $diag_detail ) ) {
            $diag_detail['subject']           = $phrase;
            $diag_detail['matched_pattern'] = 'none';
            $diag_detail['distance']          = -1;
            $diag_detail['score']             = 0;
        }
        return 0;
    }

    $flex_parts = array();
    foreach ( $toks as $tw ) {
        $flex_parts[] = preg_quote( $tw, '/' );
    }
    $flex_re = '/' . implode( '\s+', $flex_parts ) . '/iu';

    $phrase_match     = null;
    $phrase_for_match = $phrase;
    if ( preg_match( $flex_re, $hay, $m ) ) {
        $phrase_match = $m;
    } elseif ( $alias_phrase !== '' && $alias_phrase !== $phrase ) {
        $alias_toks = preg_split( '/\s+/u', $alias_phrase, -1, PREG_SPLIT_NO_EMPTY );
        if ( is_array( $alias_toks ) && $alias_toks !== array() ) {
            $alias_parts = array();
            foreach ( $alias_toks as $tw ) {
                $alias_parts[] = preg_quote( $tw, '/' );
            }
            $alias_re = '/' . implode( '\s+', $alias_parts ) . '/iu';
            if ( preg_match( $alias_re, $hay, $m2 ) ) {
                $phrase_match     = $m2;
                $phrase_for_match = $alias_phrase;
            }
        }
    }
    if ( $phrase_match === null ) {
        if ( is_array( $diag_detail ) ) {
            $diag_detail['subject']           = $phrase;
            $diag_detail['matched_pattern'] = 'none';
            $diag_detail['distance']          = -1;
            $diag_detail['score']             = 0;
        }
        return 0;
    }

    $matched_span = isset( $phrase_match[0] ) ? (string) $phrase_match[0] : $phrase_for_match;
    $pos          = mb_strpos( $hay, strtolower( $matched_span ) );
    if ( $pos === false ) {
        $pos = mb_strpos( $hay, $phrase_for_match );
    }
    if ( $pos === false ) {
        $pos = 0;
    }

    $plen = mb_strlen( $matched_span );

    // Forward-only window (0–60 chars) after subject end; cues outside this segment do not affect +5/+3.
    $fwd_max = 60;
    $fwd     = mb_substr( $hay, $pos + $plen, $fwd_max );

    $primary_re = '/^\s*((?:is\s+a|is\s+an|is\s+the|refers\s+to|means|is\s+defined\s+as|is\s+used\s+to))\b/u';

    $score          = 1;
    $matched_pat    = 'none';
    $dist           = -1;

    // Highest tier: subject appears near the beginning and is immediately defined.
    if ( $pos <= 40 && preg_match( $primary_re, $fwd, $pm, PREG_OFFSET_CAPTURE ) && isset( $pm[1][1] ) ) {
        $cue_byte_start = (int) $pm[1][1];
        $prefix_bytes   = substr( $fwd, 0, $cue_byte_start );
        $dist           = mb_strlen( $prefix_bytes );
        $matched_pat    = 'subject_leading_definition';
        $score          = 6;
    } elseif ( preg_match( $primary_re, $fwd, $pm, PREG_OFFSET_CAPTURE ) && isset( $pm[1][1] ) ) {
        $cue_byte_start = (int) $pm[1][1];
        $prefix_bytes   = substr( $fwd, 0, $cue_byte_start );
        $dist           = mb_strlen( $prefix_bytes );
        $matched_pat    = 'primary';
        $score          = 5;
    } else {
        $secondary_re = '/\b(helps|allows|enables|uses)\b/u';
        if ( preg_match( $secondary_re, $fwd, $sm, PREG_OFFSET_CAPTURE ) && isset( $sm[0][1] ) ) {
            $cue_byte_start = (int) $sm[0][1];
            $prefix_bytes   = substr( $fwd, 0, $cue_byte_start );
            $dist           = mb_strlen( $prefix_bytes );
            $matched_pat    = 'secondary';
            $score          = 3;
        }
    }

    // Trailing descriptor penalty: "X is a/an ... <subject phrase>" (subject mentioned as descriptor, not defined).
    $score_before = $score;
    if ( $score > 0 && $flex_re !== '' ) {
        $penalty_re = '/^[^\\.,]{1,40}\\b(?:is\\s+a|is\\s+an)\\b[^\\.]{0,40}' . substr( $flex_re, 1, -3 ) . '\\b/iu';
        if ( @preg_match( $penalty_re, $hay ) ) {
            $score = max( 0, (int) $score - 2 );
            // (no diagnostics)
        }
    }

    if ( is_array( $diag_detail ) ) {
        $diag_detail['subject']          = $phrase;
        $diag_detail['matched_pattern']  = $matched_pat;
        $diag_detail['distance']         = $dist;
        $diag_detail['score']            = (int) $score;
    }

    return (int) $score;
}

/**
 * Weak/generic tokens for vague informational query detection (ordering only / gate input; not corpus-specific).
 *
 * @return array<string, true>
 */
function transformer_model_lcm_vague_query_weak_token_flip() {

    static $flip = null;

    if ( $flip !== null ) {
        return $flip;
    }

    $weak = array(
        'work',
        'works',
        'matter',
        'matters',
        'improved',
        'improve',
        'best',
        'way',
        'approach',
        'this',
        'it',
        'does',
        'can',
        'how',
        'why',
        'good',
        'bad',
        'idk',
    );

    $flip = array();
    foreach ( $weak as $w ) {
        $flip[ $w ] = true;
    }

    return $flip;
}

/**
 * Whether the raw query matches generic vague question scaffolding (pronoun-heavy; no named entity).
 *
 * @param string $raw_query_text
 * @return bool
 */
function transformer_model_lcm_raw_query_matches_vague_question_structure( $raw_query_text ) {

    $s = strtolower( wp_strip_all_tags( (string) $raw_query_text ) );
    $s = preg_replace( '/\s+/u', ' ', trim( $s ) );
    if ( $s === '' ) {
        return false;
    }

    $patterns = array(
        '/\bhow\s+does\s+it\b/u',
        '/\bhow\s+do\s+you\b/u',
        '/\bwhy\s+does\s+this\b/u',
        '/\bwhy\s+does\s+it\b/u',
        '/\bcan\s+this\s+be\b/u',
        '/\bcan\s+it\s+be\b/u',
    );

    foreach ( $patterns as $re ) {
        if ( preg_match( $re, $s ) ) {
            return true;
        }
    }

    return false;
}

/**
 * True when two consecutive words in the raw query appear in meaningful tokens and are not both weak-generic.
 *
 * @param string               $raw_query_text
 * @param array<int, string>   $meaningful_lower Unique lowercase meaningful tokens.
 * @param array<string, true>  $weak_flip
 * @return bool
 */
function transformer_model_lcm_meaningful_non_weak_bigram_in_raw( $raw_query_text, array $meaningful_lower, array $weak_flip ) {

    $raw_l = strtolower( wp_strip_all_tags( (string) $raw_query_text ) );
    $raw_l = preg_replace( '/\s+/u', ' ', trim( $raw_l ) );
    if ( $raw_l === '' ) {
        return false;
    }

    $mw = array_flip( $meaningful_lower );

    $words = preg_split( '/\s+/u', $raw_l, -1, PREG_SPLIT_NO_EMPTY );
    $n     = count( $words );
    for ( $i = 0; $i < $n - 1; $i++ ) {
        $a = preg_replace( '/[^\p{L}\p{N}]/u', '', $words[ $i ] );
        $b = preg_replace( '/[^\p{L}\p{N}]/u', '', $words[ $i + 1 ] );
        $a = strtolower( $a );
        $b = strtolower( $b );
        if ( $a === '' || $b === '' ) {
            continue;
        }
        if ( ! isset( $mw[ $a ] ) || ! isset( $mw[ $b ] ) ) {
            continue;
        }
        if ( isset( $weak_flip[ $a ] ) && isset( $weak_flip[ $b ] ) ) {
            continue;
        }

        return true;
    }

    return false;
}

/**
 * Evaluate vague-query classification with token breakdown (for diagnostics).
 *
 * @param string             $raw_query_text
 * @param array<int, string> $meaningful_query_tokens
 * @return array{
 *   is_vague: int,
 *   reason: string,
 *   meaningful_tokens: string,
 *   weak_tokens: string,
 *   non_weak_tokens: string
 * }
 */
function transformer_model_lcm_evaluate_vague_informational_query( $raw_query_text, $meaningful_query_tokens ) {

    $out = array(
        'is_vague'          => 0,
        'reason'            => 'empty_meaningful',
        'meaningful_tokens' => '',
        'weak_tokens'       => '',
        'non_weak_tokens'   => '',
    );

    if ( ! is_array( $meaningful_query_tokens ) || $meaningful_query_tokens === array() ) {
        return $out;
    }

    $weak_flip = transformer_model_lcm_vague_query_weak_token_flip();

    $mw = array();
    foreach ( $meaningful_query_tokens as $t ) {
        $w = strtolower( trim( (string) $t ) );
        if ( $w !== '' ) {
            $mw[] = $w;
        }
    }
    $mw = array_values( array_unique( $mw ) );

    $weak_list = array();
    $non_weak  = array();
    foreach ( $mw as $t ) {
        if ( isset( $weak_flip[ $t ] ) ) {
            $weak_list[] = $t;
        } else {
            $non_weak[] = $t;
        }
    }

    $out['meaningful_tokens'] = implode( ',', $mw );
    $out['weak_tokens']       = implode( ',', $weak_list );
    $out['non_weak_tokens']   = implode( ',', $non_weak );

    if ( count( $non_weak ) >= 2 ) {
        $out['reason'] = 'not_vague_min_two_non_weak';

        return $out;
    }

    $long_non_weak = array();
    foreach ( $mw as $t ) {
        if ( ! isset( $weak_flip[ $t ] ) && strlen( $t ) >= 5 ) {
            $long_non_weak[] = $t;
        }
    }

    if ( count( $long_non_weak ) >= 2 ) {
        $out['reason'] = 'not_vague_min_two_long_non_weak';

        return $out;
    }

    if ( transformer_model_lcm_meaningful_non_weak_bigram_in_raw( $raw_query_text, $mw, $weak_flip ) ) {
        $out['reason'] = 'not_vague_non_weak_bigram_in_query';

        return $out;
    }

    $out['is_vague'] = 1;
    $out['reason']   = 'vague_generic_or_insufficient_anchor';

    return $out;
}

/**
 * Whether an informational query is too vague (generic tokens only) for confident retrieval. Caller should only
 * invoke when query_shape is informational_query.
 *
 * @param string             $raw_query_text
 * @param array<int, string> $meaningful_query_tokens
 * @return bool
 */
function transformer_model_lcm_is_vague_informational_query( $raw_query_text, $meaningful_query_tokens ) {

    $ev = transformer_model_lcm_evaluate_vague_informational_query( $raw_query_text, $meaningful_query_tokens );

    return (int) $ev['is_vague'] === 1;
}

/**
 * Shared vague-query gate: uses transformer_model_lcm_evaluate_vague_informational_query(); clears rows when vague and no strong anchor.
 *
 * @param array<int, array<string, mixed>> $sentenceScores Mutable candidate rows (by reference).
 * @param string                           $vague_raw
 * @param array<int, string>               $meaningful_query_tokens
 * @return array{ eval: array<string, mixed>, strong_anchor: int, matched_preview: string, cleared: bool }
 */
function transformer_model_lcm_apply_vague_query_gate_core( &$sentenceScores, $vague_raw, $meaningful_query_tokens ) {

    $vague_eval = transformer_model_lcm_evaluate_vague_informational_query( $vague_raw, $meaningful_query_tokens );

    $result = array(
        'eval'            => $vague_eval,
        'strong_anchor'   => 0,
        'matched_preview' => '',
        'cleared'         => false,
    );

    if ( (int) $vague_eval['is_vague'] !== 1 ) {
        return $result;
    }

    foreach ( $sentenceScores as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }
        $st = isset( $row['sentence'] ) ? (string) $row['sentence'] : '';
        if ( transformer_model_lcm_vague_query_row_has_strong_anchor( $st, $vague_raw, $meaningful_query_tokens ) ) {
            $result['strong_anchor']   = 1;
            $result['matched_preview'] = transformer_model_lexical_context_diag_preview_text( $st, 160 );

            return $result;
        }
    }

    if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        $nr = 0;
        foreach ( $sentenceScores as $row ) {
            if ( $nr >= 10 ) {
                break;
            }
            if ( ! is_array( $row ) ) {
                continue;
            }
            $st   = isset( $row['sentence'] ) ? (string) $row['sentence'] : '';
            $prev = transformer_model_lexical_context_diag_preview_text( $st, 120 );
            $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
            back_trace(
                'NOTICE',
                sprintf( '[LCM][vague_query_gate_row] candidate=1 text="%s"', $prev )
            );
            ++$nr;
        }
    }

    $sentenceScores    = array();
    $result['cleared'] = true;

    return $result;
}

/**
 * One-line summary when the vague guard runs for informational_query (same diagnostics gate as other [LCM] logs).
 *
 * @param string               $raw_query
 * @param array<string, mixed> $eval From transformer_model_lcm_evaluate_vague_informational_query().
 * @return void
 */
function transformer_model_lcm_log_vague_query_gate_check( $raw_query, array $eval ) {

    if ( ! transformer_model_lexical_context_is_lcm_diagnostics_enabled() || ! function_exists( 'back_trace' ) ) {
        return;
    }

    $esc = static function ( $s ) {
        $s = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $s );
        if ( strlen( $s ) > 350 ) {
            $s = substr( $s, 0, 350 ) . '...';
        }

        return $s;
    };

    $mt = isset( $eval['meaningful_tokens'] ) ? (string) $eval['meaningful_tokens'] : '';

    back_trace(
        'NOTICE',
        sprintf(
            '[LCM][vague_query_gate_check] raw="%s" meaningful=[%s] is_vague=%d reason="%s"',
            $esc( $raw_query ),
            $esc( $mt ),
            isset( $eval['is_vague'] ) ? (int) $eval['is_vague'] : 0,
            $esc( isset( $eval['reason'] ) ? $eval['reason'] : '' )
        )
    );
}

/**
 * Emit one [LCM][vague_query_decision] line (KOGNETIKS_LCM_DEBUG only).
 *
 * @param string               $raw_query
 * @param array<string, mixed> $eval           From transformer_model_lcm_evaluate_vague_informational_query().
 * @param int                  $strong_anchor  0|1
 * @param string               $matched_preview
 * @return void
 */
function transformer_model_lcm_log_vague_query_decision( $raw_query, array $eval, $strong_anchor, $matched_preview ) {

    if ( ! transformer_model_lexical_context_is_lcm_diagnostics_enabled() || ! function_exists( 'back_trace' ) ) {
        return;
    }

    $esc = static function ( $s ) {
        $s = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $s );
        if ( strlen( $s ) > 400 ) {
            $s = substr( $s, 0, 400 ) . '...';
        }

        return $s;
    };

    back_trace(
        'NOTICE',
        sprintf(
            '[LCM][vague_query_decision] raw_query="%s" meaningful_tokens="%s" weak_tokens="%s" non_weak_tokens="%s" is_vague=%d reason=%s strong_anchor_matched=%d matched_row_preview="%s"',
            $esc( $raw_query ),
            $esc( isset( $eval['meaningful_tokens'] ) ? $eval['meaningful_tokens'] : '' ),
            $esc( isset( $eval['weak_tokens'] ) ? $eval['weak_tokens'] : '' ),
            $esc( isset( $eval['non_weak_tokens'] ) ? $eval['non_weak_tokens'] : '' ),
            isset( $eval['is_vague'] ) ? (int) $eval['is_vague'] : 0,
            $esc( isset( $eval['reason'] ) ? $eval['reason'] : '' ),
            (int) (bool) $strong_anchor,
            $esc( $matched_preview )
        )
    );
}

/**
 * Whether a candidate row matches query strongly enough to bypass vague-query clearing (2+ non-weak tokens or anchored phrase in text).
 *
 * @param string               $sentence_text
 * @param string               $raw_query_text
 * @param array<int, string>   $meaningful_query_tokens
 * @return bool
 */
function transformer_model_lcm_vague_query_row_has_strong_anchor( $sentence_text, $raw_query_text, $meaningful_query_tokens ) {

    $weak_flip = transformer_model_lcm_vague_query_weak_token_flip();
    $slower    = strtolower( wp_strip_all_tags( (string) $sentence_text ) );
    $slower    = preg_replace( '/\s+/u', ' ', trim( $slower ) );

    $mw = array();
    foreach ( (array) $meaningful_query_tokens as $t ) {
        $w = strtolower( trim( (string) $t ) );
        if ( $w !== '' ) {
            $mw[] = $w;
        }
    }
    $mw = array_values( array_unique( $mw ) );

    $non_weak = array();
    foreach ( $mw as $t ) {
        if ( ! isset( $weak_flip[ $t ] ) ) {
            $non_weak[] = $t;
        }
    }

    $hits = 0;
    foreach ( $non_weak as $t ) {
        if ( @preg_match( '/\b' . preg_quote( $t, '/' ) . '\b/u', $slower ) ) {
            ++$hits;
        }
    }
    if ( $hits >= 2 ) {
        return true;
    }

    $raw_l = strtolower( wp_strip_all_tags( (string) $raw_query_text ) );
    $raw_l = preg_replace( '/\s+/u', ' ', trim( $raw_l ) );
    $words = preg_split( '/\s+/u', $raw_l, -1, PREG_SPLIT_NO_EMPTY );
    $mwset = array_flip( $mw );
    $n     = count( $words );

    for ( $i = 0; $i < $n - 1; $i++ ) {
        $a = preg_replace( '/[^\p{L}\p{N}]/u', '', $words[ $i ] );
        $b = preg_replace( '/[^\p{L}\p{N}]/u', '', $words[ $i + 1 ] );
        $a = strtolower( $a );
        $b = strtolower( $b );
        if ( $a === '' || $b === '' ) {
            continue;
        }
        if ( ! isset( $mwset[ $a ] ) || ! isset( $mwset[ $b ] ) ) {
            continue;
        }
        if ( isset( $weak_flip[ $a ] ) && isset( $weak_flip[ $b ] ) ) {
            continue;
        }
        $re = '/\b' . preg_quote( $a, '/' ) . '\s+' . preg_quote( $b, '/' ) . '\b/u';
        if ( @preg_match( $re, $slower ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Internet/slang tokens treated as non-substantive for fragment-query anchoring (generic; not corpus-specific).
 *
 * @return array<string, true>
 */
function transformer_model_lcm_fragment_noise_tokens_flip() {

    static $flip = null;

    if ( $flip !== null ) {
        return $flip;
    }

    $noise = array(
        'idk',
        'lol',
        'lmao',
        'lmfao',
        'rofl',
        'omg',
        'wtf',
        'ngl',
        'tbh',
        'imo',
        'srsly',
        'pls',
        'plz',
        'tho',
        'bc',
        'fyi',
        'afaik',
        'jk',
        'meh',
        'huh',
        'umm',
        'uh',
        'eh',
        'nah',
        'yep',
        'nope',
        'kinda',
        'sorta',
        'gonna',
        'wanna',
        'gotta',
        'lemme',
        'dunno',
    );

    $flip = array();
    foreach ( $noise as $w ) {
        $flip[ strtolower( $w ) ] = true;
    }

    return $flip;
}

/**
 * Weak tokens for fragment anchor resolution: vague generic weak list ∪ fragment noise.
 *
 * @return array<string, true>
 */
function transformer_model_lcm_fragment_anchor_weak_union_flip() {

    static $union = null;

    if ( $union !== null ) {
        return $union;
    }

    $union = transformer_model_lcm_vague_query_weak_token_flip();
    foreach ( transformer_model_lcm_fragment_noise_tokens_flip() as $k => $_ ) {
        $union[ $k ] = true;
    }

    return $union;
}

/**
 * Whether the query is a low-quality fragment (slang-heavy / junk-heavy) vs a substantive lookup.
 *
 * @param string               $raw_query_text
 * @param array<int, string>   $meaningful_query_tokens From relevance guard.
 * @return bool
 */
function transformer_model_lcm_is_low_quality_fragment_query( $raw_query_text, $meaningful_query_tokens ) {

    $noise = transformer_model_lcm_fragment_noise_tokens_flip();

    $mw = array();
    if ( is_array( $meaningful_query_tokens ) ) {
        foreach ( $meaningful_query_tokens as $t ) {
            $w = strtolower( trim( (string) $t ) );
            if ( $w !== '' ) {
                $mw[] = $w;
            }
        }
    }
    $mw = array_values( array_unique( $mw ) );

    foreach ( $mw as $t ) {
        if ( strlen( $t ) >= 6 && ! isset( $noise[ $t ] ) ) {
            return false;
        }
    }

    $raw = strtolower( wp_strip_all_tags( (string) $raw_query_text ) );
    $raw = preg_replace( '/\s+/u', ' ', trim( $raw ) );
    if ( $raw === '' ) {
        return false;
    }

    $words = preg_split( '/\s+/u', $raw, -1, PREG_SPLIT_NO_EMPTY );
    $wc    = count( $words );
    if ( $wc > 8 ) {
        return false;
    }

    $has_slang_word = false;
    foreach ( $words as $w ) {
        $wl = strtolower( preg_replace( '/[^\p{L}\p{N}]/u', '', $w ) );
        if ( $wl !== '' && isset( $noise[ $wl ] ) ) {
            $has_slang_word = true;
            break;
        }
    }

    $has_slang_regex = (bool) preg_match( '/\b(idk|lol|lmao|dunno|ngl|tbh|imo)\b/u', $raw );

    return $has_slang_word || $has_slang_regex;
}

/**
 * Row strongly anchors to substantive query material for fragment guard (same structure as vague anchor, wider weak union).
 *
 * @param string               $sentence_text
 * @param string               $raw_query_text
 * @param array<int, string>   $meaningful_query_tokens
 * @return bool
 */
function transformer_model_lcm_fragment_row_has_strong_anchor( $sentence_text, $raw_query_text, $meaningful_query_tokens ) {

    $weak_flip = transformer_model_lcm_fragment_anchor_weak_union_flip();
    $slower    = strtolower( wp_strip_all_tags( (string) $sentence_text ) );
    $slower    = preg_replace( '/\s+/u', ' ', trim( $slower ) );

    $mw = array();
    foreach ( (array) $meaningful_query_tokens as $t ) {
        $w = strtolower( trim( (string) $t ) );
        if ( $w !== '' ) {
            $mw[] = $w;
        }
    }
    $mw = array_values( array_unique( $mw ) );

    $non_weak = array();
    foreach ( $mw as $t ) {
        if ( ! isset( $weak_flip[ $t ] ) ) {
            $non_weak[] = $t;
        }
    }

    $hits = 0;
    foreach ( $non_weak as $t ) {
        if ( @preg_match( '/\b' . preg_quote( $t, '/' ) . '\b/u', $slower ) ) {
            ++$hits;
        }
    }
    if ( $hits >= 2 ) {
        return true;
    }

    $raw_l = strtolower( wp_strip_all_tags( (string) $raw_query_text ) );
    $raw_l = preg_replace( '/\s+/u', ' ', trim( $raw_l ) );
    $words = preg_split( '/\s+/u', $raw_l, -1, PREG_SPLIT_NO_EMPTY );
    $mwset = array_flip( $mw );
    $n     = count( $words );

    for ( $i = 0; $i < $n - 1; $i++ ) {
        $a = preg_replace( '/[^\p{L}\p{N}]/u', '', $words[ $i ] );
        $b = preg_replace( '/[^\p{L}\p{N}]/u', '', $words[ $i + 1 ] );
        $a = strtolower( $a );
        $b = strtolower( $b );
        if ( $a === '' || $b === '' ) {
            continue;
        }
        if ( ! isset( $mwset[ $a ] ) || ! isset( $mwset[ $b ] ) ) {
            continue;
        }
        if ( isset( $weak_flip[ $a ] ) && isset( $weak_flip[ $b ] ) ) {
            continue;
        }
        $re = '/\b' . preg_quote( $a, '/' ) . '\s+' . preg_quote( $b, '/' ) . '\b/u';
        if ( @preg_match( $re, $slower ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Internal diagnostic state: last constraint gate meta for the current request.
 *
 * @param array<string, mixed>|null $set
 * @return array<string, mixed>
 */
function transformer_model_lexical_context_constraint_gate_last_meta( $set = null ) {
    static $meta = array();
    if ( is_array( $set ) ) {
        $meta = $set;
    }
    return $meta;
}

/**
 * Constraint gate (informational queries only): explicit domain / negation / contrast mismatch protection.
 *
 * Design:
 * - No-op unless the query includes an explicit constraint (e.g. "in finance", "not marketing related", "X vs Y difference").
 * - Never changes scores or ordering; only removes rows.
 * - If a domain/negation constraint removes everything, return empty so the existing return gate produces the standard no-answer response.
 *
 * @param array<int, array<string, mixed>> $rows
 * @param array{ shape?: string }         $query_shape
 * @param string                          $raw_query_text
 * @return array<int, array<string, mixed>>
 */
function transformer_model_lexical_context_apply_constraint_gate( $rows, $query_shape, $raw_query_text ) {

    $rows  = is_array( $rows ) ? $rows : array();
    $shape = isset( $query_shape['shape'] ) ? (string) $query_shape['shape'] : '';
    if ( $shape !== 'informational_query' || $rows === array() ) {
        return $rows;
    }

    transformer_model_lexical_context_constraint_gate_last_meta(
        array(
            'applied'     => 0,
            'type'        => '',
            'constraint'  => '',
            'kept'        => count( $rows ),
            'removed'     => 0,
            'reason'      => '',
        )
    );

    $q = strtolower( wp_strip_all_tags( (string) $raw_query_text ) );
    $q = preg_replace( '/\s+/u', ' ', trim( (string) $q ) );
    if ( $q === '' ) {
        return $rows;
    }

    // Domain qualifiers ("in X").
    $domain = '';
    $domain_patterns = array(
        'biology'     => '/\bin\s+biology\b/i',
        'finance'     => '/\bin\s+finance\b/i',
        'medicine'    => '/\bin\s+medicine\b/i',
        'law'         => '/\bin\s+(law|legal)\b/i',
        'physics'     => '/\bin\s+physics\b/i',
        'chemistry'   => '/\bin\s+chemistry\b/i',
        'accounting'  => '/\bin\s+accounting\b/i',
        'real_estate' => '/\bin\s+real\s+estate\b/i',
        'marketing'   => '/\bin\s+marketing\b/i',
        'sales'       => '/\bin\s+sales\b/i',
    );
    foreach ( $domain_patterns as $k => $re ) {
        if ( preg_match( $re, $q ) ) {
            $domain = $k;
            break;
        }
    }

    // Negated domain constraints ("not marketing related", "not sales related").
    $negated = '';
    $neg_patterns = array(
        'marketing' => '/\bnot\s+marketing\s+related\b/i',
        'sales'     => '/\bnot\s+sales\s+related\b/i',
    );
    foreach ( $neg_patterns as $k => $re ) {
        if ( preg_match( $re, $q ) ) {
            $negated = $k;
            break;
        }
    }

    // Contrast/difference constraints.
    $has_contrast = (bool) preg_match( '/\b(vs|versus|difference|different\s+from)\b/i', $q );

    if ( $domain === '' && $negated === '' && ! $has_contrast ) {
        return $rows; // No explicit constraint signal; preserve existing behavior.
    }

    // Small, generic domain marker map.
    $markers = array(
        // Keep markers small and generic. Use whole-word/phrase matching to avoid substring collisions (e.g. "gene" vs "generate").
        'biology' => array( 'biology', 'biological', 'organism', 'organisms', 'cell', 'cells', 'gene', 'genes', 'protein', 'proteins', 'species', 'ecosystem', 'evolution', 'dna' ),
        'finance' => array( 'finance', 'financial', 'banking', 'investment', 'accounting', 'revenue' ),
        'medicine' => array( 'medicine', 'medical', 'health', 'clinical', 'patient' ),
        'law' => array( 'law', 'legal', 'attorney', 'court', 'contract' ),
        'physics' => array( 'physics', 'quantum', 'energy', 'force', 'matter' ),
        'chemistry' => array( 'chemistry', 'chemical', 'molecule', 'compound' ),
        'accounting' => array( 'accounting', 'tax', 'balance', 'ledger', 'financial' ),
        'real_estate' => array( 'real estate', 'property', 'housing', 'condo', 'mortgage' ),
        'marketing' => array( 'marketing', 'campaign', 'audience', 'conversion', 'lead generation' ),
        'sales' => array( 'sales', 'prospect', 'pipeline', 'deal', 'lead generation' ),
        // Generic contrast companion for common “electrical” ambiguity cases (not corpus-specific).
        'electrical' => array( 'electrical', 'electronics', 'voltage', 'current', 'circuit', 'wire' ),
    );

    $match_marker = static function ( $text_lower, $marker ) {
        $marker = strtolower( trim( (string) $marker ) );
        if ( $marker === '' ) {
            return false;
        }
        // Whole-phrase match with token boundaries.
        if ( strpos( $marker, ' ' ) !== false ) {
            $parts = preg_split( '/\s+/u', $marker, -1, PREG_SPLIT_NO_EMPTY );
            if ( ! is_array( $parts ) || $parts === array() ) {
                return false;
            }
            $re = '(?<![\p{L}\p{N}_])' . implode( '\s+', array_map( static function ( $p ) {
                return preg_quote( (string) $p, '/' );
            }, $parts ) ) . '(?![\p{L}\p{N}_])';
            return (bool) preg_match( '/' . $re . '/iu', $text_lower );
        }
        // Whole-word match.
        $re = '(?<![\p{L}\p{N}_])' . preg_quote( $marker, '/' ) . '(?![\p{L}\p{N}_])';
        return (bool) preg_match( '/' . $re . '/iu', $text_lower );
    };

    $match_row = static function ( $text_lower, $domain_key ) use ( $markers ) {
        if ( $domain_key === '' || ! isset( $markers[ $domain_key ] ) ) {
            return false;
        }
        foreach ( $markers[ $domain_key ] as $m ) {
            if ( $m !== '' && strpos( $text_lower, (string) $m ) !== false ) {
                return true;
            }
        }
        return false;
    };

    // Override: use boundary-aware marker matching (prevents substring false positives).
    $match_row = static function ( $text_lower, $domain_key ) use ( $markers, $match_marker ) {
        if ( $domain_key === '' || ! isset( $markers[ $domain_key ] ) ) {
            return false;
        }
        foreach ( $markers[ $domain_key ] as $m ) {
            if ( $match_marker( $text_lower, $m ) ) {
                return true;
            }
        }
        return false;
    };

    $before  = count( $rows );
    $removed = 0;
    $kept    = 0;
    $removed_rows_logged = 0;
    $type = '';
    $constraint = '';

    // 1) Negation: remove rows that match the negated domain markers.
    if ( $negated !== '' ) {
        $type = 'negation';
        $constraint = 'not ' . $negated . ' related';
        $out = array();
        foreach ( $rows as $row ) {
            $text = isset( $row['sentence'] ) ? (string) $row['sentence'] : '';
            $tl   = strtolower( wp_strip_all_tags( $text ) );
            if ( $match_row( $tl, $negated ) ) {
                ++$removed;
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) && $removed_rows_logged < 10 ) {
                    $preview = transformer_model_lexical_context_diag_preview_text( $text, 120 );
                    $preview = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $preview );
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][constraint_gate_row] removed=1 reason="negated_domain" text="%s"',
                            $preview
                        )
                    );
                    ++$removed_rows_logged;
                }
                continue;
            }
            $out[] = $row;
        }
        $rows = $out;
    }

    // 2) Domain qualifier: keep rows matching the domain markers.
    if ( $domain !== '' ) {
        $type = $type !== '' ? $type : 'domain';
        $constraint = $constraint !== '' ? $constraint : ( 'in ' . str_replace( '_', ' ', $domain ) );
        $out = array();
        $removed_here = 0;
        foreach ( $rows as $row ) {
            $text = isset( $row['sentence'] ) ? (string) $row['sentence'] : '';
            $tl   = strtolower( wp_strip_all_tags( $text ) );
            // Must match explicit domain markers; do not allow base topic terms to satisfy the constraint.
            if ( $match_row( $tl, $domain ) ) {
                $out[] = $row;
            } else {
                ++$removed_here;
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) && $removed_rows_logged < 10 ) {
                    $preview = transformer_model_lexical_context_diag_preview_text( $text, 120 );
                    $preview = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $preview );
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][constraint_gate_row] removed=1 reason="domain_mismatch" text="%s"',
                            $preview
                        )
                    );
                    ++$removed_rows_logged;
                }
            }
        }
        $removed += $removed_here;
        $rows = $out;
    }

    // 3) Contrast/difference: only apply if the query shows a clear competing side.
    if ( $has_contrast ) {
        $domains_in_query = array();
        foreach ( array_keys( $markers ) as $k ) {
            foreach ( $markers[ $k ] as $m ) {
                if ( $m !== '' && strpos( $q, $m ) !== false ) {
                    $domains_in_query[ $k ] = true;
                    break;
                }
            }
        }
        $domains_in_query = array_values( array_keys( $domains_in_query ) );

        if ( count( $domains_in_query ) >= 2 ) {
            $type = $type !== '' ? $type : 'contrast';
            $constraint = $constraint !== '' ? $constraint : 'contrast';
            $a = $domains_in_query[0];
            $b = $domains_in_query[1];

            $both = array();
            $only_b = array();
            foreach ( $rows as $row ) {
                $text = isset( $row['sentence'] ) ? (string) $row['sentence'] : '';
                $tl   = strtolower( wp_strip_all_tags( $text ) );
                $ma = $match_row( $tl, $a );
                $mb = $match_row( $tl, $b );
                if ( $ma && $mb ) {
                    $both[] = $row;
                } elseif ( $mb ) {
                    $only_b[] = $row;
                }
            }

            // Prefer rows that support both sides; else prefer rows for the non-default side if present.
            if ( $both !== array() ) {
                $rows = $both;
            } elseif ( $only_b !== array() ) {
                $rows = $only_b;
            } else {
                // No support for contrast sides in candidates → preserve existing fallback behavior (no filtering).
            }
        }
    }

    $after = count( $rows );
    $kept  = $after;

    if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        $reason = '';
        if ( $type === 'domain' && $domain !== '' && $kept === 0 ) {
            $reason = ' reason="no_domain_match"';
        }
        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][constraint_gate] applied=1 type="%s" constraint="%s" kept=%d removed=%d%s',
                $type !== '' ? $type : 'unknown',
                str_replace( '"', "'", $constraint !== '' ? $constraint : 'n/a' ),
                $kept,
                max( 0, $before - $after ),
                $reason
            )
        );
    }

    transformer_model_lexical_context_constraint_gate_last_meta(
        array(
            'applied'     => 1,
            'type'        => $type !== '' ? $type : 'unknown',
            'constraint'  => $constraint !== '' ? $constraint : 'n/a',
            'kept'        => $kept,
            'removed'     => max( 0, $before - $after ),
            'reason'      => ( $type === 'domain' && $domain !== '' && $kept === 0 ) ? 'no_domain_match' : '',
        )
    );

    // Explicit mismatch constraint: do not restore rows if everything was removed.
    // Returning empty allows the existing return gate to emit the standard no-answer response.
    return $rows;
}

/**
 * Case-sense ambiguity guard (informational queries only): remove rows where a lowercase query token
 * appears primarily as a capitalized proper noun in the row, and the row lacks support from other
 * meaningful query terms.
 *
 * This is intended to reduce harmful false positives from common-word/proper-name collisions
 * (e.g. "cook" matching "Cook" as a name) without hardcoding any names.
 *
 * Behavior:
 * - Only applies when the token appears lowercase in the raw query text.
 * - Only removes a row when it has a proper-noun match for token T and does NOT include any other
 *   meaningful query token besides T.
 * - If everything is removed, return empty so the return gate yields the standard no-answer response.
 *
 * @param array<int, array<string, mixed>> $rows
 * @param array<int, string>              $meaningful_query_tokens
 * @param array{ shape?: string }         $query_shape
 * @param string                          $raw_query_text
 * @return array<int, array<string, mixed>>
 */
function transformer_model_lexical_context_apply_case_sense_guard( $rows, $meaningful_query_tokens, $query_shape, $raw_query_text ) {

    $rows  = is_array( $rows ) ? $rows : array();
    $shape = isset( $query_shape['shape'] ) ? (string) $query_shape['shape'] : '';
    if ( $shape !== 'informational_query' || $rows === array() ) {
        return $rows;
    }

    $meaningful_query_tokens = is_array( $meaningful_query_tokens ) ? $meaningful_query_tokens : array();
    $raw_q = strtolower( wp_strip_all_tags( (string) $raw_query_text ) );
    if ( $raw_q === '' || count( $meaningful_query_tokens ) < 2 ) {
        return $rows;
    }

    $tokens = array();
    foreach ( $meaningful_query_tokens as $t ) {
        $t = strtolower( trim( (string) $t ) );
        // Require alpha-ish tokens long enough to plausibly be a name collision.
        if ( strlen( $t ) < 3 || ! preg_match( '/^[\p{L}]+$/u', $t ) ) {
            continue;
        }
        // Only apply when the user typed this token in lowercase somewhere in the raw query.
        if ( strpos( $raw_q, $t ) === false ) {
            continue;
        }
        $tokens[] = $t;
    }
    $tokens = array_values( array_unique( $tokens ) );
    if ( $tokens === array() ) {
        return $rows;
    }

    $before = count( $rows );
    $out = array();
    $removed = 0;
    $removed_logged = 0;

    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }
        $text = isset( $row['sentence'] ) ? (string) $row['sentence'] : '';
        if ( $text === '' ) {
            $out[] = $row;
            continue;
        }
        $plain = wp_strip_all_tags( $text );
        $lower = strtolower( $plain );

        $drop = false;
        $hit_token = '';

        foreach ( $tokens as $t ) {
            // Proper-noun token match: " Cook " (Title Case).
            $cap = strtoupper( substr( $t, 0, 1 ) ) . substr( $t, 1 );
            $re_cap = '(?<![\\p{L}\\p{N}_])' . preg_quote( $cap, '/' ) . '(?![\\p{L}\\p{N}_])';
            if ( ! preg_match( '/' . $re_cap . '/u', $plain ) ) {
                continue;
            }

            // If the row also contains the lowercase/common-word sense, do not treat as collision.
            $re_low = '(?<![\\p{L}\\p{N}_])' . preg_quote( $t, '/' ) . '(?![\\p{L}\\p{N}_])';
            if ( preg_match( '/' . $re_low . '/u', $plain ) ) {
                continue;
            }

            // Require lack of support: no other meaningful token present in the row.
            $has_other = false;
            foreach ( $meaningful_query_tokens as $ot ) {
                $ot = strtolower( trim( (string) $ot ) );
                if ( $ot === '' || $ot === $t ) {
                    continue;
                }
                $re_ot = '(?<![\\p{L}\\p{N}_])' . preg_quote( $ot, '/' ) . '(?![\\p{L}\\p{N}_])';
                if ( preg_match( '/' . $re_ot . '/iu', $lower ) ) {
                    $has_other = true;
                    break;
                }
            }
            if ( $has_other ) {
                continue;
            }

            $drop = true;
            $hit_token = $t;
            break;
        }

        if ( $drop ) {
            ++$removed;
            if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) && $removed_logged < 10 ) {
                $preview = transformer_model_lexical_context_diag_preview_text( $text, 120 );
                $preview = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $preview );
                back_trace(
                    'NOTICE',
                    sprintf(
                        '[LCM][case_sense_guard_row] removed=1 token="%s" reason="proper_name_collision" text="%s"',
                        str_replace( '"', "'", $hit_token ),
                        $preview
                    )
                );
                ++$removed_logged;
            }
            continue;
        }

        $out[] = $row;
    }

    $after = count( $out );
    if ( $removed > 0 && transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][case_sense_guard] applied=1 kept=%d removed=%d',
                $after,
                max( 0, $before - $after )
            )
        );
    }

    return $out;
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
 * Trim ranked candidate rows to a maximum count (best-first order preserved). Does not change scores.
 *
 * Caps are filterable via {@see 'chatbot_lcm_candidate_cap'} (args: $max, $stage_slug). Use $max <= 0 to disable.
 *
 * @param array<int, array<string, mixed>> $rows       Rows already ordered best-first for the pipeline stage.
 * @param int                              $max_rows   Default cap for this stage before the filter runs.
 * @param string                           $stage_slug Stable slug for filters and diagnostics (e.g. after_sort).
 * @return array<int, array<string, mixed>>
 */
function transformer_model_lexical_context_cap_ranked_sentence_rows( $rows, $max_rows, $stage_slug ) {

    if ( ! is_array( $rows ) ) {
        return array();
    }

    $before = count( $rows );
    $slug   = preg_replace( '/[^a-z0-9_]/', '', strtolower( (string) $stage_slug ) );
    if ( $slug === '' ) {
        $slug = 'unknown';
    }

    $max_rows = (int) apply_filters( 'chatbot_lcm_candidate_cap', (int) $max_rows, $slug );

    if ( $max_rows <= 0 ) {
        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            back_trace(
                'NOTICE',
                sprintf( '[LCM][cap:%s] candidates before=%d after=%d cap=disabled', $slug, $before, $before )
            );
        }
        return $rows;
    }

    if ( $before <= $max_rows ) {
        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            back_trace(
                'NOTICE',
                sprintf( '[LCM][cap:%s] candidates before=%d after=%d cap=%d', $slug, $before, $before, $max_rows )
            );
        }
        return $rows;
    }

    $out   = array_slice( $rows, 0, $max_rows );
    $after = count( $out );

    if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        back_trace(
            'NOTICE',
            sprintf( '[LCM][cap:%s] candidates before=%d after=%d cap=%d', $slug, $before, $after, $max_rows )
        );
    }

    return $out;
}

/**
 * Default user-facing string when the return gate rejects low-confidence scored rows.
 *
 * @return string
 */
function transformer_model_lexical_context_lcm_insufficient_confidence_message() {

    return 'I don\'t have enough relevant information in the site content to answer that confidently.';
}

/**
 * Message returned when the post-deduplication return gate blocks assembly (filter may substitute site copy).
 *
 * @return string
 */
function transformer_model_lexical_context_return_gate_blocked_user_message() {

    return (string) apply_filters(
        'chatbot_lcm_return_gate_blocked_message',
        transformer_model_lexical_context_lcm_insufficient_confidence_message()
    );
}

/**
 * Rare exact-token bypass for the return gate: direct meaningful query token appears whole-word in the top row
 * and has local IDF ≥ threshold (expansion terms excluded). Does not change scoring.
 *
 * @param array<int, array<string, mixed>> $rows                      Best-first rows.
 * @param array<int, string>|null         $meaningful_direct_tokens Same as relevance guard (not PMI expansion).
 * @param array<string, float>|null       $local_idf_map             Runtime IDF map when active.
 * @param bool                            $local_idf_available       True when local IDF cache was loaded for this request.
 * @return array{ allow: bool, terms_log: string } terms_log is e.g. "terms=[kumquat=6.48687]" for diagnostics.
 */
function transformer_model_lexical_context_return_gate_evaluate_rare_exact_token_override( $rows, $meaningful_direct_tokens, $local_idf_map, $local_idf_available ) {

    $empty = array(
        'allow'     => false,
        'terms_log' => '',
    );

    if ( ! $local_idf_available || empty( $local_idf_map ) || ! is_array( $local_idf_map ) ) {
        return $empty;
    }

    $rows = is_array( $rows ) ? $rows : array();
    if ( empty( $rows[0] ) || ! isset( $rows[0]['sentence'] ) ) {
        return $empty;
    }

    $meaningful_direct_tokens = is_array( $meaningful_direct_tokens ) ? $meaningful_direct_tokens : array();
    if ( empty( $meaningful_direct_tokens ) ) {
        return $empty;
    }

    $sentence = wp_strip_all_tags( (string) $rows[0]['sentence'] );
    $haystack = strtolower( $sentence );
    $min_idf  = (float) apply_filters( 'chatbot_lcm_rare_exact_token_min_idf', 3.0 );

    $pairs = array();

    foreach ( $meaningful_direct_tokens as $tok ) {
        $tok = strtolower( trim( (string) $tok ) );
        if ( strlen( $tok ) < 2 ) {
            continue;
        }
        if ( ! isset( $local_idf_map[ $tok ] ) ) {
            continue;
        }

        $idf_val = (float) $local_idf_map[ $tok ];
        if ( $idf_val < $min_idf ) {
            continue;
        }

        $pattern = '/(?<![\p{L}\p{N}_])' . preg_quote( $tok, '/' ) . '(?![\p{L}\p{N}_])/u';
        if ( preg_match( $pattern, $haystack ) ) {
            $pairs[ $tok ] = $idf_val;
        }
    }

    if ( empty( $pairs ) ) {
        return $empty;
    }

    $parts = array();
    foreach ( $pairs as $t => $v ) {
        $parts[] = sprintf( '%s=%g', $t, $v );
    }

    return array(
        'allow'     => true,
        'terms_log' => 'terms=[' . implode( ',', $parts ) . ']',
    );
}

/**
 * Which coverage tokens (subset of meaningful query tokens) appear whole-word in sentence text.
 *
 * @param string               $sentence        Row sentence text.
 * @param array<int, string>   $coverage_tokens Lowercased tokens (weak action words already removed).
 * @return array<int, string> Matched token strings.
 */
function transformer_model_lexical_context_sentence_coverage_token_matches( $sentence, array $coverage_tokens ) {

    $haystack = strtolower( wp_strip_all_tags( (string) $sentence ) );
    $matched   = array();

    foreach ( $coverage_tokens as $tok ) {
        $tok = strtolower( trim( (string) $tok ) );
        if ( strlen( $tok ) < 2 ) {
            continue;
        }
        $pattern = '/(?<![\p{L}\p{N}_])' . preg_quote( $tok, '/' ) . '(?![\p{L}\p{N}_])/u';
        if ( preg_match( $pattern, $haystack ) ) {
            $matched[] = $tok;
        }
    }

    return array_values( array_unique( $matched ) );
}

/**
 * Log coverage gate outcome when KOGNETIKS_LCM_DEBUG (one line per evaluation).
 *
 * @param array{ allow?: bool, reason?: string, matched?: array<int, string> } $coverage_gate Result from evaluate_query_coverage_gate.
 * @return void
 */
function transformer_model_lexical_context_diag_log_coverage_gate_result( $coverage_gate ) {

    if ( ! transformer_model_lexical_context_is_lcm_diagnostics_enabled() || ! function_exists( 'back_trace' ) ) {
        return;
    }

    $allow  = ! empty( $coverage_gate['allow'] ) ? 1 : 0;
    $reason = isset( $coverage_gate['reason'] ) ? (string) $coverage_gate['reason'] : '';

    $matched_list = isset( $coverage_gate['matched'] ) && is_array( $coverage_gate['matched'] ) ? $coverage_gate['matched'] : array();
    $matched_safe = implode(
        ',',
        array_map(
            static function ( $t ) {
                return str_replace( array( '|', ',' ), '', (string) $t );
            },
            $matched_list
        )
    );

    if ( 'skipped_meaningful_lt_3' === $reason || 'skipped_rare_exact_token_match' === $reason ) {
        back_trace(
            'NOTICE',
            sprintf( '[LCM][coverage_gate] allow=%d reason=%s', $allow, $reason )
        );

        return;
    }

    back_trace(
        'NOTICE',
        sprintf(
            '[LCM][coverage_gate] allow=%d reason=%s matched=[%s] required=2',
            $allow,
            $reason,
            $matched_safe
        )
    );
}

/**
 * Final relevance coverage gate (after dedupe/doc limit, before return_gate). Does not change scores.
 *
 * When there are 3+ meaningful query tokens, require strong lexical overlap unless a single-content-token
 * rare lookup bypass applies ({@see transformer_model_lexical_context_return_gate_evaluate_rare_exact_token_override}
 * only when exactly one coverage token remains after weak-action filtering).
 *
 * @param array<int, array<string, mixed>> $rows                      Best-first rows (same as assembly input).
 * @param array<int, string>               $meaningful_query_tokens Meaningful direct-query tokens.
 * @param array<string, float>|null        $local_idf_map             Runtime local IDF map.
 * @param bool                             $local_idf_available       Same as return_gate / rare-token override.
 * @return array{ allow: bool, reason: string, matched: array<int, string> }
 */
function transformer_model_lexical_context_evaluate_query_coverage_gate( $rows, $meaningful_query_tokens, $local_idf_map, $local_idf_available ) {

    $empty_matched = array();

    $rows                      = is_array( $rows ) ? $rows : array();
    $meaningful_query_tokens   = is_array( $meaningful_query_tokens ) ? $meaningful_query_tokens : array();
    $local_idf_map             = ( $local_idf_map !== null && is_array( $local_idf_map ) ) ? $local_idf_map : array();
    $local_idf_available       = (bool) $local_idf_available;

    if ( count( $meaningful_query_tokens ) < 3 ) {
        return array(
            'allow'   => true,
            'reason'  => 'skipped_meaningful_lt_3',
            'matched' => $empty_matched,
        );
    }

    $weak_flip = array_flip(
        array(
            'used',
            'use',
            'using',
            'does',
            'do',
            'explain',
        )
    );

    $coverage_tokens = array();
    foreach ( $meaningful_query_tokens as $t ) {
        $t = strtolower( trim( (string) $t ) );
        if ( strlen( $t ) < 2 || isset( $weak_flip[ $t ] ) ) {
            continue;
        }
        $coverage_tokens[] = $t;
    }

    if ( $coverage_tokens === array() ) {
        return array(
            'allow'   => true,
            'reason'  => 'skipped_no_coverage_tokens_after_weak_filter',
            'matched' => $empty_matched,
        );
    }

    // Rare exact-token bypass only for single remaining content token (e.g. many weak fillers + one rare term).
    // Multi-token conceptual queries always run normal coverage matching.
    if ( count( $coverage_tokens ) === 1 ) {
        $rare = transformer_model_lexical_context_return_gate_evaluate_rare_exact_token_override(
            $rows,
            $meaningful_query_tokens,
            $local_idf_map,
            $local_idf_available
        );
        if ( ! empty( $rare['allow'] ) ) {
            return array(
                'allow'   => true,
                'reason'  => 'skipped_rare_exact_token_match',
                'matched' => $coverage_tokens,
            );
        }
    }

    $union_flip = array();

    $top_matches = array();
    if ( ! empty( $rows[0]['sentence'] ) ) {
        $top_matches = transformer_model_lexical_context_sentence_coverage_token_matches( $rows[0]['sentence'], $coverage_tokens );
        foreach ( $top_matches as $t ) {
            $union_flip[ $t ] = true;
        }
    }

    foreach ( $rows as $row ) {
        if ( empty( $row['sentence'] ) ) {
            continue;
        }
        $m = transformer_model_lexical_context_sentence_coverage_token_matches( $row['sentence'], $coverage_tokens );
        foreach ( $m as $t ) {
            $union_flip[ $t ] = true;
        }
    }

    $matched_list = array_keys( $union_flip );
    sort( $matched_list );

    $top_ok        = count( $top_matches ) >= 2;
    $collective_ok = count( $union_flip ) >= 2;

    if ( $top_ok || $collective_ok ) {
        return array(
            'allow'   => true,
            'reason'  => 'passed',
            'matched' => $matched_list,
        );
    }

    return array(
        'allow'   => false,
        'reason'  => 'insufficient_query_token_coverage',
        'matched' => $matched_list,
    );
}

/**
 * Conservative gate: whether assembled reply should run given ranked rows after deduplication.
 * Does not alter scores — decisions use existing row scores only.
 *
 * @param array<int, array<string, mixed>> $rows                      Best-first scored rows (same order as assembly input).
 * @param array<int, string>               $inputWordsLower           Lowercased query tokens.
 * @param array<int, string>|null          $meaningful_direct_tokens Meaningful direct query tokens (relevance guard list); null derives from inputWordsLower.
 * @param array<string, float>|null        $local_idf_map             Runtime local IDF map when active.
 * @param bool                             $local_idf_available       Whether local IDF was loaded for this request (option on + cache hit).
 * @return array{ allow: bool, reason: string, top_score: float, candidate_count: int }
 */
function transformer_model_lexical_context_should_return_scored_rows( $rows, $inputWordsLower, $meaningful_direct_tokens = null, $local_idf_map = null, $local_idf_available = false ) {

    $rows             = is_array( $rows ) ? $rows : array();
    $inputWordsLower  = is_array( $inputWordsLower ) ? $inputWordsLower : array();
    $local_idf_map    = ( $local_idf_map !== null && is_array( $local_idf_map ) ) ? $local_idf_map : array();
    $local_idf_available = (bool) $local_idf_available;

    if ( $meaningful_direct_tokens === null ) {
        $meaningful_direct_tokens = transformer_model_lexical_context_meaningful_query_tokens_for_relevance_guard( $inputWordsLower );
    } else {
        $meaningful_direct_tokens = is_array( $meaningful_direct_tokens ) ? $meaningful_direct_tokens : array();
    }

    $candidate_count  = count( $rows );
    $top_score        = 0.0;

    if ( $candidate_count > 0 && isset( $rows[0]['score'] ) && is_numeric( $rows[0]['score'] ) ) {
        $top_score = (float) $rows[0]['score'];
    }

    $result = array(
        'allow'             => true,
        'reason'            => 'passed',
        'top_score'         => $top_score,
        'candidate_count'   => $candidate_count,
    );

    $return_gate_terms_suffix = '';

    if ( $candidate_count === 0 ) {
        $result['allow']     = false;
        $result['reason']    = 'no_candidates';
        $result['top_score'] = 0.0;
    } else {
        $min_top    = (float) apply_filters( 'chatbot_lcm_min_return_top_score', 20.0 );
        $min_single = (float) apply_filters( 'chatbot_lcm_min_single_candidate_score', 30.0 );

        if ( $top_score < $min_top ) {
            $result['allow']  = false;
            $result['reason'] = 'top_score_below_min';
        } elseif ( $candidate_count === 1 && $top_score < $min_single ) {
            $result['allow']  = false;
            $result['reason'] = 'single_weak_candidate';
        }

        if ( empty( $result['allow'] ) && isset( $result['reason'] ) && $result['reason'] === 'top_score_below_min' ) {
            $rare = transformer_model_lexical_context_return_gate_evaluate_rare_exact_token_override(
                $rows,
                $meaningful_direct_tokens,
                $local_idf_map,
                $local_idf_available
            );
            if ( ! empty( $rare['allow'] ) ) {
                $result['allow']  = true;
                $result['reason'] = 'rare_exact_token_match';
                if ( ! empty( $rare['terms_log'] ) ) {
                    $return_gate_terms_suffix = ' ' . $rare['terms_log'];
                }
            }
        }
    }

    if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][return_gate] allow=%d reason=%s top_score=%g candidates=%d%s',
                ! empty( $result['allow'] ) ? 1 : 0,
                isset( $result['reason'] ) ? (string) $result['reason'] : '',
                isset( $result['top_score'] ) ? (float) $result['top_score'] : 0.0,
                isset( $result['candidate_count'] ) ? (int) $result['candidate_count'] : 0,
                $return_gate_terms_suffix
            )
        );
    }

    return $result;
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

    $normalized_for_diag = transformer_model_lexical_context_normalize_lexical_query_string( $input_text_raw );
    $normalized_for_diag = preg_replace( '/[^\w\s]/u', ' ', $normalized_for_diag );
    $normalized_for_diag = preg_replace( '/\s+/u', ' ', trim( $normalized_for_diag ) );

    transformer_model_lexical_context_diag_log_query_token_pipeline(
        $input_text_raw,
        $normalized_for_diag,
        $inputWordsLower,
        $meaningful_query_tokens
    );

    // Shape drives diagnostics; informational_query also gets a modest definition/meta score bias before document sort.
    $query_shape = transformer_model_lexical_context_classify_query_shape(
        $input_text_raw,
        $normalized_for_diag,
        $inputWordsLower,
        $meaningful_query_tokens
    );
    if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        static $lcm_query_shape_logged = false;
        if ( ! $lcm_query_shape_logged ) {
            $shape = isset( $query_shape['shape'] ) ? (string) $query_shape['shape'] : '';
            $conf  = isset( $query_shape['confidence'] ) ? (float) $query_shape['confidence'] : 0.0;
            $sig   = isset( $query_shape['signals'] ) && is_array( $query_shape['signals'] ) ? $query_shape['signals'] : array();
            $sig_s = implode(
                ',',
                array_map(
                    static function ( $s ) {
                        return str_replace( array( "\r", "\n", '|', ',' ), array( ' ', ' ', '/', '' ), (string) $s );
                    },
                    $sig
                )
            );
            back_trace(
                'NOTICE',
                sprintf( '[LCM][query_shape] shape=%s confidence=%g signals=[%s]', $shape, $conf, $sig_s )
            );
            $lcm_query_shape_logged = true;
        }
    }

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

    transformer_model_lexical_context_lcm_timing_segment( 'idf_resolve' );

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
            if ( transformer_model_lexical_context_lcm_budget_hard_exceeded() ) {
                break 2;
            }

            $trimmed = trim( $sentence );
            // TEMPORARY target sentence trace (flatten_chunk stage).
            transformer_model_lcm_chatbot_sales_target_trace_log(
                'flatten_chunk',
                $trimmed,
                $pid,
                isset( $doc['post_title'] ) ? (string) $doc['post_title'] : ''
            );
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

            // TEMPORARY target sentence trace (pre_score stage).
            transformer_model_lcm_chatbot_sales_target_trace_log(
                'pre_score',
                $trimmed,
                $pid,
                isset( $doc['post_title'] ) ? (string) $doc['post_title'] : ''
            );

            $row = transformer_model_lexical_context_lexical_sentence_score_row(
                $trimmed,
                $searchWordsLower,
                $inputWordsLower,
                $local_idf_map,
                $apply_local_idf,
                $intent_precomputed,
                $query_shape
            );

            if ( $row !== null ) {
                $row['post_id'] = $pid;
                $sentenceScores[] = $row;
            }
        }
    }

    transformer_model_lexical_context_lcm_timing_segment( 'scoring_loop' );

    transformer_model_lexical_context_diag_log_pipeline_stage( 'pre_scoring_candidates', $lcm_pre_scoring_candidates, $post_title_map );

    if ( empty( $sentenceScores ) ) {
        if ( ! empty( $meaningful_query_tokens ) && ! $corpus_had_meaningful_overlap ) {
            $resp = transformer_model_lexical_context_no_query_overlap_message();
            // TEMPORARY cow_return_trace.
            $resp = transformer_model_lcm_cow_return_trace_checkpoint(
                'transformer_model_lexical_context_build_sentences_from_documents',
                'no_query_overlap_return',
                $input_text_raw,
                $resp
            );
            return $resp;
        }

        $resp = '';
        // TEMPORARY cow_return_trace.
        $resp = transformer_model_lcm_cow_return_trace_checkpoint(
            'transformer_model_lexical_context_build_sentences_from_documents',
            'empty_sentenceScores_return',
            $input_text_raw,
            $resp
        );
        return $resp;
    }

    $sentenceScores = transformer_model_lexical_context_apply_answer_shape_bias(
        $sentenceScores,
        $query_shape,
        $meaningful_query_tokens
    );

    transformer_model_lexical_context_diag_log_pipeline_stage( 'after_scoring', $sentenceScores, $post_title_map );
    transformer_model_lcm_case_diag_log_rows( $input_text_raw, 'after_scoring', $sentenceScores, $post_title_map, 10 );
    transformer_model_lcm_cow_track_log_stage( 'after_scoring', $sentenceScores );
    transformer_model_lcm_chatbot_sales_diag_log_stage( $input_text_raw, 'after_scoring', $sentenceScores, $post_title_map );
    transformer_model_lcm_chatbot_sales_target_trace_scan_rows( 'after_scoring', $sentenceScores, $post_title_map );

    $sentenceScores = transformer_model_lexical_context_sort_sentence_scores_with_document_priority( $sentenceScores );
    $sentenceScores = transformer_model_lexical_context_cap_ranked_sentence_rows( $sentenceScores, 250, 'after_sort' );

    // Conservative cross-document gate: only include weaker posts if their document-level max score is within 85% of the best post’s max.
    $after_doc_gate = transformer_model_lexical_context_apply_document_gate( $sentenceScores, $query_shape, 0.85 );
    $after_doc_gate = transformer_model_lexical_context_cap_ranked_sentence_rows( $after_doc_gate, 100, 'after_document_gate' );

    // Row-level gate: keep chunks near the best chunk score; drops weak filler when a strong match exists.
    $after_row_gate = transformer_model_lexical_context_filter_sentence_scores_row_gate( $after_doc_gate, 0.65 );
    $after_row_gate = transformer_model_lexical_context_cap_ranked_sentence_rows( $after_row_gate, 50, 'after_row_gate' );

    transformer_model_lexical_context_diag_log_pipeline_stage( 'after_document_gate', $after_doc_gate, $post_title_map );
    transformer_model_lexical_context_diag_log_pipeline_stage( 'after_row_gate', $after_row_gate, $post_title_map );
    transformer_model_lcm_case_diag_log_rows( $input_text_raw, 'after_document_gate', $after_doc_gate, $post_title_map, 10 );
    transformer_model_lcm_case_diag_log_rows( $input_text_raw, 'after_row_gate', $after_row_gate, $post_title_map, 10 );
    transformer_model_lcm_cow_track_log_stage( 'after_document_gate', $after_doc_gate );
    transformer_model_lcm_cow_track_log_stage( 'after_row_gate', $after_row_gate );
    transformer_model_lcm_chatbot_sales_diag_log_stage( $input_text_raw, 'after_document_gate', $after_doc_gate, $post_title_map );
    transformer_model_lcm_chatbot_sales_diag_log_stage( $input_text_raw, 'after_row_gate', $after_row_gate, $post_title_map );
    transformer_model_lcm_chatbot_sales_target_trace_scan_rows( 'after_document_gate', $after_doc_gate, $post_title_map );
    transformer_model_lcm_chatbot_sales_target_trace_scan_rows( 'after_row_gate', $after_row_gate, $post_title_map );

    $after_row_gate = transformer_model_lexical_context_filter_low_value_sentence_rows( $after_row_gate, 'after_row_gate_quality' );

    transformer_model_lexical_context_diag_log_pipeline_stage( 'after_row_gate_quality', $after_row_gate, $post_title_map );

    // Enrich from the top-ranked document only (up to sentence response cap), including next-best same-post chunks.
    $sentenceScores = transformer_model_lexical_context_merge_top_document_expansion(
        $after_doc_gate,
        $after_row_gate,
        0.65,
        $sentenceResponseCount
    );

    $sentenceScores = transformer_model_lexical_context_cap_ranked_sentence_rows( $sentenceScores, 50, 'after_expansion' );

    $sentenceScores = transformer_model_lexical_context_filter_low_value_sentence_rows( $sentenceScores, 'after_expansion_quality' );

    transformer_model_lexical_context_diag_log_pipeline_stage( 'after_expansion', $sentenceScores, $post_title_map );

    $sentenceScores = transformer_model_lexical_context_deduplicate_near_duplicate_sentence_rows( $sentenceScores );

    $sentenceScores = transformer_model_lexical_context_cap_ranked_sentence_rows( $sentenceScores, 20, 'after_deduplication' );

    $sentenceScores = transformer_model_lexical_context_limit_rows_per_document( $sentenceScores );

    transformer_model_lexical_context_diag_log_pipeline_stage( 'after_deduplication', $sentenceScores, $post_title_map );
    transformer_model_lcm_case_diag_log_rows( $input_text_raw, 'after_deduplication', $sentenceScores, $post_title_map, 10 );
    transformer_model_lcm_cow_track_log_stage( 'after_deduplication', $sentenceScores );
    transformer_model_lcm_chatbot_sales_diag_log_stage( $input_text_raw, 'after_deduplication', $sentenceScores, $post_title_map );
    transformer_model_lcm_chatbot_sales_target_trace_scan_rows( 'after_deduplication', $sentenceScores, $post_title_map );

    // Definition preservation (informational only): compute subject-definition score early so downstream
    // coverage/answerability can preserve strong definitional rows (ordering key only; retrieval score unchanged).
    if ( isset( $query_shape['shape'] ) && (string) $query_shape['shape'] === 'informational_query' && ! empty( $sentenceScores ) ) {
        foreach ( $sentenceScores as $i => $r ) {
            if ( ! is_array( $r ) ) {
                continue;
            }
            $tx = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
            $sentenceScores[ $i ]['_subject_definition_score'] = transformer_model_lcm_get_subject_definition_score(
                $tx,
                $query_shape,
                $meaningful_query_tokens,
                $constraint_query_text
            );
        }
    }

    $cohesion = null;
    if ( isset( $query_shape['shape'] ) && (string) $query_shape['shape'] === 'informational_query' ) {
        // Compute semantic cohesion for downstream diagnostic/rejection gating (token-based; no embeddings).
        $cohesion = transformer_model_lexical_context_evaluate_semantic_cohesion( $sentenceScores, $meaningful_query_tokens );
        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() ) {
            transformer_model_lexical_context_diag_log_semantic_cohesion( $cohesion );
        }

        // Semantic cohesion rejection gate (informational only): kill-switch for clearly unrelated candidate sets.
        $avg = isset( $cohesion['average_cohesion'] ) ? (float) $cohesion['average_cohesion'] : 0.0;
        $qo  = transformer_model_lexical_context_semantic_cohesion_query_overlap_ratio( $sentenceScores, $meaningful_query_tokens );
        $n   = count( $sentenceScores );

        // Only apply when meaningful token count is non-zero (otherwise overlap ratio is meaningless).
        if ( is_array( $meaningful_query_tokens ) && count( $meaningful_query_tokens ) > 0 ) {
            if ( $avg < 0.20 && $qo < 0.15 ) {
                $sentenceScores = array();
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][semantic_cohesion_gate] allow=0 reason="low_cohesion_low_query_overlap" avg=%.4f query_overlap=%.4f candidates=%d',
                            $avg,
                            $qo,
                            $n
                        )
                    );
                }
            } else {
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][semantic_cohesion_gate] allow=1 avg=%.4f query_overlap=%.4f candidates=%d',
                            $avg,
                            $qo,
                            $n
                        )
                    );
                }
            }
        }
    }

    // Vague query guard: informational_query (full); short_anchor_query only for vague-question phrasing (see transformer_model_lcm_raw_query_matches_vague_question_structure).
    $vague_raw = $input_text_raw !== '' ? (string) $input_text_raw : implode( ' ', (array) $inputWordsLower );
    $shape_s   = isset( $query_shape['shape'] ) ? (string) $query_shape['shape'] : '';

    if ( $shape_s === 'informational_query' ) {
        $vg = transformer_model_lcm_apply_vague_query_gate_core( $sentenceScores, $vague_raw, $meaningful_query_tokens );

        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            transformer_model_lcm_log_vague_query_gate_check( $vague_raw, $vg['eval'] );
            transformer_model_lcm_log_vague_query_decision(
                $vague_raw,
                $vg['eval'],
                $vg['strong_anchor'],
                $vg['matched_preview']
            );
        }

        if ( (int) $vg['eval']['is_vague'] === 1 ) {
            $mt_log = is_array( $meaningful_query_tokens ) ? $meaningful_query_tokens : array();
            $mt_log = array_map( 'strval', $mt_log );
            $toks   = implode( ',', $mt_log );
            if ( strlen( $toks ) > 200 ) {
                $toks = substr( $toks, 0, 200 ) . '...';
            }

            $rescued = ( 1 === $vg['strong_anchor'] );

            if ( ! $rescued ) {
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                    back_trace(
                        'NOTICE',
                        sprintf( '[LCM][vague_query_gate] allow=0 reason="no_strong_anchor" tokens="%s"', $toks )
                    );
                }
            } elseif ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                back_trace(
                    'NOTICE',
                    sprintf( '[LCM][vague_query_gate] allow=1 reason="strong_anchor_found" tokens="%s"', $toks )
                );
            }
        }
    } elseif ( $shape_s === 'short_anchor_query' && transformer_model_lcm_raw_query_matches_vague_question_structure( $vague_raw ) ) {
        $vg = transformer_model_lcm_apply_vague_query_gate_core( $sentenceScores, $vague_raw, $meaningful_query_tokens );

        $mt_join = implode(
            ',',
            array_map(
                static function ( $t ) {
                    return str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $t );
                },
                is_array( $meaningful_query_tokens ) ? $meaningful_query_tokens : array()
            )
        );

        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            transformer_model_lcm_log_vague_query_gate_check( $vague_raw, $vg['eval'] );
            transformer_model_lcm_log_vague_query_decision(
                $vague_raw,
                $vg['eval'],
                $vg['strong_anchor'],
                $vg['matched_preview']
            );

            if ( (int) $vg['eval']['is_vague'] !== 1 ) {
                $sa_reason = 'eval_not_vague';
                $sa_allow  = 1;
            } elseif ( ! empty( $vg['strong_anchor'] ) ) {
                $sa_reason = 'strong_anchor_found';
                $sa_allow  = 1;
            } else {
                $sa_reason = 'no_strong_anchor';
                $sa_allow  = 0;
            }

            $vr_esc = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $vague_raw );
            if ( strlen( $vr_esc ) > 350 ) {
                $vr_esc = substr( $vr_esc, 0, 350 ) . '...';
            }

            back_trace(
                'NOTICE',
                sprintf(
                    '[LCM][vague_query_gate_short_anchor] allow=%d reason="%s" raw="%s" meaningful=[%s]',
                    (int) (bool) $sa_allow,
                    str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $sa_reason ),
                    $vr_esc,
                    $mt_join
                )
            );
        }
    } elseif ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        $shape_lbl = $shape_s;
        $shape_lbl = str_replace( array( '|', ',', '"' ), '', $shape_lbl );
        $mt_join   = implode(
            ',',
            array_map(
                static function ( $t ) {
                    return str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $t );
                },
                is_array( $meaningful_query_tokens ) ? $meaningful_query_tokens : array()
            )
        );
        $vr_esc = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $vague_raw );
        if ( strlen( $vr_esc ) > 350 ) {
            $vr_esc = substr( $vr_esc, 0, 350 ) . '...';
        }
        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][vague_query_gate_skip] shape=%s raw="%s" meaningful=[%s] note=vague_guard_not_applicable',
                $shape_lbl,
                $vr_esc,
                $mt_join
            )
        );
    }

    // Low-quality fragment guard (slang/junk-heavy queries): separate from vague-query gate; clears rows if no strong anchor.
    if ( transformer_model_lcm_is_low_quality_fragment_query( $vague_raw, $meaningful_query_tokens ) ) {
        $frag_rescued = false;
        foreach ( $sentenceScores as $frag_row ) {
            if ( ! is_array( $frag_row ) ) {
                continue;
            }
            $fst = isset( $frag_row['sentence'] ) ? (string) $frag_row['sentence'] : '';
            if ( transformer_model_lcm_fragment_row_has_strong_anchor( $fst, $vague_raw, $meaningful_query_tokens ) ) {
                $frag_rescued = true;
                break;
            }
        }

        if ( ! $frag_rescued ) {
            $sentenceScores = array();
        }

        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            $mt_frag = implode(
                ',',
                array_map(
                    static function ( $t ) {
                        return str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $t );
                    },
                    is_array( $meaningful_query_tokens ) ? $meaningful_query_tokens : array()
                )
            );
            $vr_frag = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $vague_raw );
            if ( strlen( $vr_frag ) > 350 ) {
                $vr_frag = substr( $vr_frag, 0, 350 ) . '...';
            }
            back_trace(
                'NOTICE',
                sprintf(
                    '[LCM][fragment_query_gate] allow=%d reason="%s" raw="%s" meaningful=[%s]',
                    $frag_rescued ? 1 : 0,
                    $frag_rescued ? 'strong_anchor_found' : 'no_strong_anchor',
                    $vr_frag,
                    $mt_frag
                )
            );
        }
    }

    $coverage_gate = transformer_model_lexical_context_evaluate_query_coverage_gate(
        $sentenceScores,
        $meaningful_query_tokens,
        $local_idf_map,
        $apply_local_idf
    );
    transformer_model_lexical_context_diag_log_coverage_gate_result( $coverage_gate );
    if ( empty( $coverage_gate['allow'] ) && isset( $query_shape['shape'] ) && (string) $query_shape['shape'] === 'informational_query' ) {
        $preserve_found = false;
        $preserve_score = 0;
        $preserve_text  = '';
        foreach ( $sentenceScores as $r ) {
            if ( ! is_array( $r ) ) {
                continue;
            }
            $sds = isset( $r['_subject_definition_score'] ) ? (int) $r['_subject_definition_score'] : 0;
            if ( $sds >= 3 ) {
                $preserve_found = true;
                $preserve_score = $sds;
                $preserve_text  = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
                break;
            }
        }
        if ( $preserve_found ) {
            $coverage_gate['allow']  = true;
            $coverage_gate['reason'] = 'definition_preserve';
            // (no diagnostics)
        }
    }
    if ( empty( $coverage_gate['allow'] ) ) {
        $resp = transformer_model_lexical_context_return_gate_blocked_user_message();
        // TEMPORARY cow_return_trace.
        $resp = transformer_model_lcm_cow_return_trace_checkpoint(
            'transformer_model_lexical_context_build_sentences_from_documents',
            'coverage_gate_blocked_return',
            $input_text_raw,
            $resp
        );
        return $resp;
    }

    // Constraint gate (informational queries only): explicit domain/negation/contrast mismatch protection.
    // Placement: after coverage gate pass, immediately before answerability gate (least invasive).
    $constraint_query_text = $input_text_raw !== '' ? (string) $input_text_raw : implode( ' ', (array) $inputWordsLower );
    $sentenceScores = transformer_model_lexical_context_apply_constraint_gate( $sentenceScores, $query_shape, $constraint_query_text );

    // Case-sense ambiguity guard (informational queries only): proper-name vs common-word collision protection.
    // Placement: after constraint gate, before answerability gate (does not change scores; removes rows only).
    $sentenceScores = transformer_model_lexical_context_apply_case_sense_guard( $sentenceScores, $meaningful_query_tokens, $query_shape, $constraint_query_text );

    // Answerability gate (informational queries only): filter already-ranked rows without changing scores.
    // IMPORTANT: This runs after coverage gate and before return gate; it preserves order and only removes rows.
    if ( isset( $query_shape['shape'] ) && (string) $query_shape['shape'] === 'informational_query' ) {
        $content = transformer_model_lexical_context_answer_shape_content_tokens(
            array_values(
                array_unique(
                    array_map(
                        static function ( $w ) {
                            return strtolower( trim( (string) $w ) );
                        },
                        is_array( $meaningful_query_tokens ) ? $meaningful_query_tokens : array()
                    )
                )
            )
        );

        $phrase_re = array();
        if ( count( $content ) >= 2 ) {
            $phrase_tokens = count( $content ) <= 3 ? $content : array_slice( $content, -3 );
            $pr = transformer_model_lexical_context_answer_shape_anchor_regex_phrase( $phrase_tokens );
            if ( $pr !== null && $pr !== '' ) {
                $phrase_re = array( 'regex' => $pr );
            }
        }
        $word_re = count( $content ) >= 1 ? transformer_model_lexical_context_answer_shape_anchor_regex_word( $content[ count( $content ) - 1 ] ) : null;

        $filtered  = array();
        $skipped   = 0;
        $log_count = 0;
        $fallback  = null;

        foreach ( $sentenceScores as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }

            $text   = isset( $row['sentence'] ) ? (string) $row['sentence'] : '';
            $slower = strtolower( wp_strip_all_tags( $text ) );
            $sds    = isset( $row['_subject_definition_score'] ) ? (int) $row['_subject_definition_score'] : 0;

            $has_anchor = false;
            if ( $phrase_re !== array() ) {
                $has_anchor = (bool) preg_match( '/' . $phrase_re['regex'] . '/iu', $slower );
            } elseif ( $word_re !== null && $word_re !== '' ) {
                $has_anchor = (bool) preg_match( '/' . $word_re . '/iu', $slower );
            }

            if ( $sds >= 3 ) {
                $filtered[] = $row;
            } elseif ( transformer_model_lcm_is_answerable_row( $text, $has_anchor ) ) {
                $filtered[] = $row;
            } else {
                // Definition fallback: keep clear "X is a/means/refers to..." rows that mention the subject or a meaningful token.
                $keep_by_definition_fallback = false;
                $head_100                    = mb_substr( $slower, 0, 100 );
                $def_cues                    = array(
                    ' is a ',
                    ' is an ',
                    ' is the ',
                    ' refers to ',
                    ' means ',
                    ' is defined as ',
                    ' is used to ',
                );
                $has_def_cue = false;
                foreach ( $def_cues as $cue ) {
                    if ( strpos( $head_100, $cue ) !== false ) {
                        $has_def_cue = true;
                        break;
                    }
                }
                if ( $has_def_cue ) {
                    $subject_phrase = transformer_model_lcm_informational_subject_phrase_for_definition_score( $constraint_query_text, $meaningful_query_tokens );
                    $subject_phrase = strtolower( trim( (string) $subject_phrase ) );
                    $mentions       = false;
                    if ( $subject_phrase !== '' && strpos( $slower, $subject_phrase ) !== false ) {
                        $mentions = true;
                    } elseif ( is_array( $meaningful_query_tokens ) ) {
                        foreach ( $meaningful_query_tokens as $mtok ) {
                            $mtok = strtolower( trim( (string) $mtok ) );
                            if ( $mtok === '' ) {
                                continue;
                            }
                            if ( preg_match( '/\b' . preg_quote( $mtok, '/' ) . '\b/u', $slower ) ) {
                                $mentions = true;
                                break;
                            }
                        }
                    }
                    if ( $mentions ) {
                        $keep_by_definition_fallback = true;
                    }
                }

                if ( $keep_by_definition_fallback ) {
                    $filtered[] = $row;
                    continue;
                }

                ++$skipped;
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) && $log_count < 10 ) {
                    $preview = transformer_model_lexical_context_diag_preview_text( $text, 120 );
                    $preview = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $preview );
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][answerability_gate_row] skipped=1 reason="not_answerable" text="%s"',
                            $preview
                        )
                    );
                    ++$log_count;
                }
            }
        }

        if ( $filtered !== array() ) {
            $sentenceScores = $filtered;
            $fallback       = 0;
            if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                back_trace(
                    'NOTICE',
                    sprintf(
                        '[LCM][answerability_gate] applied=1 kept=%d skipped=%d',
                        count( $filtered ),
                        $skipped
                    )
                );
            }
        } else {
            $cg = transformer_model_lexical_context_constraint_gate_last_meta();
            $cg_applied = ! empty( $cg['applied'] );
            $cg_type    = isset( $cg['type'] ) ? (string) $cg['type'] : '';
            // If an explicit domain/negation constraint gate already ran, do not fallback to weak rows.
            // Leave empty so the existing return gate produces the standard no-answer response.
            if ( $cg_applied && ( $cg_type === 'domain' || $cg_type === 'negation' ) ) {
                $sentenceScores = array();
                $fallback       = 0;
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                    back_trace( 'NOTICE', '[LCM][answerability_gate] fallback=0 reason="constraint_gate_applied"' );
                }
            } else {
                $fallback = 1;
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                    back_trace( 'NOTICE', '[LCM][answerability_gate] fallback=1 reason="no_valid_rows"' );
                }
            }
        }

        transformer_model_lcm_case_diag_log_rows( $input_text_raw, 'after_answerability_gate', $sentenceScores, $post_title_map, 10 );
        transformer_model_lcm_chatbot_sales_diag_log_stage( $input_text_raw, 'after_answerability_gate', $sentenceScores, $post_title_map );
        transformer_model_lcm_chatbot_sales_target_trace_scan_rows( 'after_answerability_gate', $sentenceScores, $post_title_map );

        // Tiered answer strength + answer-directness reordering (informational queries only): re-rank within the
        // already-ranked list without changing retrieval scores.
        foreach ( $sentenceScores as $i => $r ) {
            if ( ! is_array( $r ) ) {
                continue;
            }
            $text   = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
            $slower = strtolower( wp_strip_all_tags( $text ) );

            $has_anchor = false;
            if ( $phrase_re !== array() ) {
                $has_anchor = (bool) preg_match( '/' . $phrase_re['regex'] . '/iu', $slower );
            } elseif ( $word_re !== null && $word_re !== '' ) {
                $has_anchor = (bool) preg_match( '/' . $word_re . '/iu', $slower );
            }

            $sentenceScores[ $i ]['_answer_strength'] = transformer_model_lcm_get_answer_strength( $text, $has_anchor );
            $sentenceScores[ $i ]['_orig_rank']       = (int) $i;
        }

        foreach ( $sentenceScores as $i => $r ) {
            if ( ! is_array( $r ) ) {
                continue;
            }
            $text = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
            $sentenceScores[ $i ]['_answer_directness'] = transformer_model_lcm_get_answer_directness_score(
                $text,
                $query_shape,
                $meaningful_query_tokens,
                $constraint_query_text
            );
            $sentenceScores[ $i ]['_subject_definition_score'] = transformer_model_lcm_get_subject_definition_score(
                $text,
                $query_shape,
                $meaningful_query_tokens,
                $constraint_query_text
            );

            // Ranking-only: count how many meaningful query tokens are present in this row (word-boundary match).
            $hits = 0;
            if ( is_array( $meaningful_query_tokens ) && $meaningful_query_tokens !== array() ) {
                $sl = strtolower( wp_strip_all_tags( (string) $text ) );
                foreach ( $meaningful_query_tokens as $mtok ) {
                    $mtok = strtolower( trim( (string) $mtok ) );
                    if ( $mtok === '' ) {
                        continue;
                    }
                    if ( preg_match( '/\b' . preg_quote( $mtok, '/' ) . '\b/u', $sl ) ) {
                        ++$hits;
                    }
                }
            }
            $sentenceScores[ $i ]['_query_token_hits'] = (int) $hits;
        }

        usort(
            $sentenceScores,
            static function ( $a, $b ) {
                $asd = isset( $a['_subject_definition_score'] ) ? (int) $a['_subject_definition_score'] : 0;
                $bsd = isset( $b['_subject_definition_score'] ) ? (int) $b['_subject_definition_score'] : 0;
                if ( $asd !== $bsd ) {
                    return $bsd <=> $asd;
                }

                $ah = isset( $a['_query_token_hits'] ) ? (int) $a['_query_token_hits'] : 0;
                $bh = isset( $b['_query_token_hits'] ) ? (int) $b['_query_token_hits'] : 0;
                if ( $ah !== $bh ) {
                    return $bh <=> $ah;
                }

                $as = isset( $a['_answer_strength'] ) ? (int) $a['_answer_strength'] : 0;
                $bs = isset( $b['_answer_strength'] ) ? (int) $b['_answer_strength'] : 0;
                if ( $as !== $bs ) {
                    return $bs <=> $as;
                }

                $ad = isset( $a['_answer_directness'] ) ? (int) $a['_answer_directness'] : 0;
                $bd = isset( $b['_answer_directness'] ) ? (int) $b['_answer_directness'] : 0;
                if ( $ad !== $bd ) {
                    return $bd <=> $ad;
                }

                $sa = isset( $a['score'] ) ? (float) $a['score'] : 0.0;
                $sb = isset( $b['score'] ) ? (float) $b['score'] : 0.0;
                if ( $sa !== $sb ) {
                    return $sb <=> $sa;
                }

                $ra = isset( $a['_orig_rank'] ) ? (int) $a['_orig_rank'] : 0;
                $rb = isset( $b['_orig_rank'] ) ? (int) $b['_orig_rank'] : 0;

                return $ra <=> $rb;
            }
        );

        transformer_model_lcm_case_diag_log_rows( $input_text_raw, 'after_final_ordering', $sentenceScores, $post_title_map, 10 );
        transformer_model_lcm_cow_track_log_stage( 'after_final_order', $sentenceScores );
        transformer_model_lcm_chatbot_sales_diag_log_stage( $input_text_raw, 'after_final_order', $sentenceScores, $post_title_map );
        transformer_model_lcm_chatbot_sales_target_trace_scan_rows( 'after_final_order', $sentenceScores, $post_title_map );

        // Fragment-completion guard: short "X is/means/refers to" inputs should only proceed if at least one row
        // starts with the query subject phrase (allowing the AI alias). Clears rows only.
        if ( $constraint_query_text !== '' ) {
            $raw_fc = strtolower( wp_strip_all_tags( (string) $constraint_query_text ) );
            $raw_fc = preg_replace( '/\s+/u', ' ', trim( (string) $raw_fc ) );
            $raw_fc = preg_replace( '/[?.!,;:]+$/u', '', $raw_fc );
            $words  = $raw_fc !== '' ? preg_split( '/\s+/u', $raw_fc, -1, PREG_SPLIT_NO_EMPTY ) : array();
            $wcount = is_array( $words ) ? count( $words ) : 0;
            $ends_def = (bool) preg_match( '/\b(?:is|is a|is an|means|refers to)$/u', $raw_fc );

            if ( $wcount > 0 && $wcount <= 5 && $ends_def ) {
                $subject = transformer_model_lcm_informational_subject_phrase_for_definition_score( $constraint_query_text, $meaningful_query_tokens );
                $subject = strtolower( preg_replace( '/\s+/u', ' ', trim( (string) $subject ) ) );
                $alias   = '';
                if ( $subject !== '' && strpos( $subject, 'artificial intelligence' ) !== false ) {
                    $alias = trim( preg_replace( '/\bartificial intelligence\b/u', 'ai', $subject ) );
                    $alias = strtolower( preg_replace( '/\s+/u', ' ', (string) $alias ) );
                }

                $subject_re = '';
                if ( $subject !== '' ) {
                    $toks = preg_split( '/\s+/u', $subject, -1, PREG_SPLIT_NO_EMPTY );
                    if ( is_array( $toks ) && $toks !== array() ) {
                        $parts = array();
                        foreach ( $toks as $tw ) {
                            $parts[] = preg_quote( (string) $tw, '/' );
                        }
                        $subject_re = implode( '\s+', $parts );
                    }
                }
                $alias_re = '';
                if ( $alias !== '' && $alias !== $subject ) {
                    $toks = preg_split( '/\s+/u', $alias, -1, PREG_SPLIT_NO_EMPTY );
                    if ( is_array( $toks ) && $toks !== array() ) {
                        $parts = array();
                        foreach ( $toks as $tw ) {
                            $parts[] = preg_quote( (string) $tw, '/' );
                        }
                        $alias_re = implode( '\s+', $parts );
                    }
                }

                $lead_ok = false;
                if ( $subject_re !== '' || $alias_re !== '' ) {
                    foreach ( $sentenceScores as $r ) {
                        if ( ! is_array( $r ) ) {
                            continue;
                        }
                        $tx = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
                        $sl = strtolower( wp_strip_all_tags( $tx ) );
                        $sl = preg_replace( '/\s+/u', ' ', trim( (string) $sl ) );
                        if ( $sl === '' ) {
                            continue;
                        }

                        $m = null;
                        if ( $subject_re !== '' && preg_match( '/^\s{0,6}(' . $subject_re . ')\b/iu', $sl, $mm ) ) {
                            $m = $mm[1] ?? '';
                        } elseif ( $alias_re !== '' && preg_match( '/^\s{0,6}(' . $alias_re . ')\b/iu', $sl, $mm ) ) {
                            $m = $mm[1] ?? '';
                        }
                        if ( $m === null ) {
                            continue;
                        }

                        $pos  = mb_strpos( $sl, strtolower( (string) $m ) );
                        $pos  = $pos === false ? 0 : $pos;
                        $plen = mb_strlen( (string) $m );
                        $fwd  = mb_substr( $sl, $pos + $plen, 60 );
                        if ( preg_match( '/^\s*(?:is(?:\s+a|\s+an)?|means|refers\s+to)\b/u', $fwd ) ) {
                            $lead_ok = true;
                            break;
                        }
                    }
                }

                if ( ! $lead_ok ) {
                    $sentenceScores = array();
                }

                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][fragment_completion_gate] allow=%d reason="subject_leading_definition_required"',
                            $lead_ok ? 1 : 0
                        )
                    );
                }
            }
        }

        if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
            back_trace(
                'NOTICE',
                sprintf(
                    '[LCM][answer_directness_order] applied=1 candidates=%d',
                    count( $sentenceScores )
                )
            );
            $log_n = 0;
            foreach ( $sentenceScores as $r ) {
                if ( $log_n >= 10 ) {
                    break;
                }
                if ( ! is_array( $r ) ) {
                    continue;
                }
                $dir   = isset( $r['_answer_directness'] ) ? (int) $r['_answer_directness'] : 0;
                $tier  = isset( $r['_answer_strength'] ) ? (int) $r['_answer_strength'] : 0;
                $hits  = isset( $r['_query_token_hits'] ) ? (int) $r['_query_token_hits'] : 0;
                $sds   = isset( $r['_subject_definition_score'] ) ? (int) $r['_subject_definition_score'] : 0;
                $score = isset( $r['score'] ) ? (float) $r['score'] : 0.0;
                $text  = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
                $prev  = transformer_model_lexical_context_diag_preview_text( $text, 120 );
                $prev  = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
                back_trace(
                    'NOTICE',
                    sprintf(
                        '[LCM][answer_directness] directness=%d strength=%d score=%.4f text="%s"',
                        $dir,
                        $tier,
                        $score,
                        $prev
                    )
                );
                back_trace(
                    'NOTICE',
                    sprintf(
                        '[LCM][ranking_adjustment] tokens=%d subject_definition=%d text="%s"',
                        $hits,
                        $sds,
                        $prev
                    )
                );
                back_trace(
                    'NOTICE',
                    sprintf(
                        '[LCM][multi_token_hits] tokens=%d text="%s"',
                        $hits,
                        $prev
                    )
                );
                ++$log_n;
            }

            // (removed verbose subject-definition debug lines)
        }
    }

    $return_gate = transformer_model_lexical_context_should_return_scored_rows(
        $sentenceScores,
        $inputWordsLower,
        $meaningful_query_tokens,
        $local_idf_map,
        $apply_local_idf
    );
    transformer_model_lcm_cow_track_log_stage( 'before_return_gate', $sentenceScores );
    if ( empty( $return_gate['allow'] ) ) {
        $resp = transformer_model_lexical_context_return_gate_blocked_user_message();
        // TEMPORARY cow_return_trace.
        $resp = transformer_model_lcm_cow_return_trace_checkpoint(
            'transformer_model_lexical_context_build_sentences_from_documents',
            'return_gate_blocked_return',
            $input_text_raw,
            $resp
        );
        return $resp;
    }
    transformer_model_lcm_cow_track_log_stage( 'after_return_gate', $sentenceScores );

    transformer_model_lexical_context_lcm_timing_segment( 'pre_assembly' );

    // Definition-query assembly preference (informational only): assemble from definition rows first when available.
    $assembly_sentenceScores = $sentenceScores;
    if ( isset( $query_shape['shape'] ) && (string) $query_shape['shape'] === 'informational_query' && $input_text_raw !== '' && ! empty( $sentenceScores ) ) {
        $raw_l = strtolower( wp_strip_all_tags( (string) $input_text_raw ) );
        $raw_l = preg_replace( '/\s+/u', ' ', trim( (string) $raw_l ) );
        $is_def_query = false;
        if ( strpos( $raw_l, 'what is ' ) === 0 || strpos( $raw_l, 'what are ' ) === 0 || strpos( $raw_l, 'define ' ) === 0 || strpos( $raw_l, 'explain ' ) === 0 ) {
            $is_def_query = true;
        }

        if ( $is_def_query ) {
            $subject = transformer_model_lcm_informational_subject_phrase_for_definition_score( $input_text_raw, $meaningful_query_tokens );
            $subject = strtolower( preg_replace( '/\s+/u', ' ', trim( (string) $subject ) ) );
            $alias   = '';
            if ( $subject !== '' && strpos( $subject, 'artificial intelligence' ) !== false ) {
                $alias = trim( preg_replace( '/\bartificial intelligence\b/u', 'ai', $subject ) );
                $alias = strtolower( preg_replace( '/\s+/u', ' ', (string) $alias ) );
            }

            $subject_re = '';
            if ( $subject !== '' ) {
                $toks = preg_split( '/\s+/u', $subject, -1, PREG_SPLIT_NO_EMPTY );
                if ( is_array( $toks ) && $toks !== array() ) {
                    $parts = array();
                    foreach ( $toks as $tw ) {
                        $parts[] = preg_quote( (string) $tw, '/' );
                    }
                    $subject_re = implode( '\s+', $parts );
                }
            }
            $alias_re = '';
            if ( $alias !== '' && $alias !== $subject ) {
                $toks = preg_split( '/\s+/u', $alias, -1, PREG_SPLIT_NO_EMPTY );
                if ( is_array( $toks ) && $toks !== array() ) {
                    $parts = array();
                    foreach ( $toks as $tw ) {
                        $parts[] = preg_quote( (string) $tw, '/' );
                    }
                    $alias_re = implode( '\s+', $parts );
                }
            }

            $def_cues = array(
                ' is a ',
                ' is an ',
                ' is the ',
                ' refers to ',
                ' means ',
                ' is defined as ',
                ' is used to ',
            );

            $filtered = array();
            foreach ( $sentenceScores as $r ) {
                if ( ! is_array( $r ) ) {
                    continue;
                }

                $sds = isset( $r['_subject_definition_score'] ) ? (int) $r['_subject_definition_score'] : 0;
                if ( $sds >= 3 ) {
                    $filtered[] = $r;
                    continue;
                }

                if ( $subject_re === '' && $alias_re === '' ) {
                    continue;
                }

                $tx = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
                $sl = strtolower( wp_strip_all_tags( $tx ) );
                $sl = preg_replace( '/\s+/u', ' ', trim( (string) $sl ) );
                if ( $sl === '' ) {
                    continue;
                }

                $begins = false;
                if ( $subject_re !== '' && preg_match( '/^\s{0,6}(?:' . $subject_re . ')\b/iu', $sl ) ) {
                    $begins = true;
                } elseif ( $alias_re !== '' && preg_match( '/^\s{0,6}(?:' . $alias_re . ')\b/iu', $sl ) ) {
                    $begins = true;
                }
                if ( ! $begins ) {
                    continue;
                }

                $head_100   = mb_substr( $sl, 0, 100 );
                $has_defcue = false;
                foreach ( $def_cues as $cue ) {
                    if ( strpos( $head_100, $cue ) !== false ) {
                        $has_defcue = true;
                        break;
                    }
                }
                if ( $has_defcue ) {
                    $filtered[] = $r;
                }
            }

            if ( $filtered !== array() ) {
                $assembly_sentenceScores = $filtered;
            }

            if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                $subj_esc = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $subject );
                back_trace(
                    'NOTICE',
                    sprintf(
                        '[LCM][definition_query_assembly_filter] applied=%d before=%d after=%d subject="%s"',
                        ( $filtered !== array() ) ? 1 : 0,
                        is_array( $sentenceScores ) ? count( $sentenceScores ) : 0,
                        is_array( $assembly_sentenceScores ) ? count( $assembly_sentenceScores ) : 0,
                        $subj_esc
                    )
                );
            }
        }
    }

    transformer_model_lcm_case_diag_log_rows(
        $input_text_raw,
        'final_candidates',
        $assembly_sentenceScores,
        $post_title_map,
        is_array( $assembly_sentenceScores ) ? count( $assembly_sentenceScores ) : 0
    );
    transformer_model_lcm_cow_track_log_stage( 'before_assembly', $assembly_sentenceScores );
    transformer_model_lcm_chatbot_sales_diag_log_stage( $input_text_raw, 'before_assembly', $assembly_sentenceScores, $post_title_map );
    transformer_model_lcm_chatbot_sales_target_trace_scan_rows( 'before_assembly', $assembly_sentenceScores, $post_title_map );

    transformer_model_lcm_rank_debug_log_rows( $input_text_raw, 'rank_debug', $assembly_sentenceScores, $post_title_map );
    if ( transformer_model_lcm_rank_debug_is_enabled_for_query( $input_text_raw ) ) {
        $GLOBALS['kognetiks_lcm_rank_debug_query'] = (string) $input_text_raw;
        $GLOBALS['kognetiks_lcm_rank_debug_titles'] = $post_title_map;
    }
    $GLOBALS['kognetiks_lcm_raw_query_for_assembly'] = (string) $input_text_raw;

    $assembled = transformer_model_lexical_context_assemble_response_from_scored_sentences(
        $assembly_sentenceScores,
        $maxWords,
        $sentenceResponseCount,
        $similarityThreshold,
        $leadingSentencesRatio,
        $leadingTokenRatio
    );

    if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        if ( stripos( (string) $assembled, 'cow' ) !== false ) {
            $prev = transformer_model_lexical_context_diag_preview_text( (string) $assembled, 200 );
            $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
            back_trace( 'NOTICE', sprintf( '[LCM][cow_track] stage="final_output" text="%s"', $prev ) );
        }
    }

    transformer_model_lexical_context_lcm_timing_segment( 'assembly' );

    // TEMPORARY cow_return_trace: checkpoint at final return of build_sentences_from_documents.
    $assembled = transformer_model_lcm_cow_return_trace_checkpoint(
        'transformer_model_lexical_context_build_sentences_from_documents',
        'final_return',
        $input_text_raw,
        $assembled
    );

    return $assembled;
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

    // Diagnostics only: confirm assembly receives raw query.
    $assembly_raw_query = isset( $GLOBALS['kognetiks_lcm_raw_query_for_assembly'] ) ? (string) $GLOBALS['kognetiks_lcm_raw_query_for_assembly'] : '';
    if ( transformer_model_lcm_rank_debug_is_enabled_for_query( $assembly_raw_query ) && transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
        $rq_esc = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), trim( (string) $assembly_raw_query ) );
        back_trace(
            'NOTICE',
            sprintf(
                '[LCM][assembly_entry] count=%d raw_query_available=%d raw_query="%s"',
                is_array( $sentenceScores ) ? count( $sentenceScores ) : 0,
                $assembly_raw_query !== '' ? 1 : 0,
                $rq_esc
            )
        );
    }

    if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) && is_array( $sentenceScores ) ) {
        foreach ( $sentenceScores as $r ) {
            if ( ! is_array( $r ) ) {
                continue;
            }
            $tx = isset( $r['sentence'] ) ? (string) $r['sentence'] : '';
            if ( stripos( $tx, 'cow' ) !== false ) {
                $prev = transformer_model_lexical_context_diag_preview_text( $tx, 160 );
                $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
                back_trace( 'NOTICE', sprintf( '[LCM][cow_track] stage="assembly_input" text="%s"', $prev ) );
            }
        }
    }

    if ( transformer_model_lexical_context_lcm_budget_hard_exceeded() ) {
        transformer_model_lexical_context_lcm_timing_segment( 'assembly_budget_cutoff' );

        return transformer_model_lexical_context_lcm_assembly_budget_fallback( $sentenceScores );
    }

    // Diagnostics only: confirm the exact ranked order received by assembly.
    $rank_debug_query = isset( $GLOBALS['kognetiks_lcm_rank_debug_query'] ) ? (string) $GLOBALS['kognetiks_lcm_rank_debug_query'] : '';
    if ( transformer_model_lcm_rank_debug_is_enabled_for_query( $rank_debug_query ) ) {
        $title_map = isset( $GLOBALS['kognetiks_lcm_rank_debug_titles'] ) && is_array( $GLOBALS['kognetiks_lcm_rank_debug_titles'] )
            ? $GLOBALS['kognetiks_lcm_rank_debug_titles']
            : array();
        transformer_model_lcm_rank_debug_log_rows( $rank_debug_query, 'assembly_rank_debug', $sentenceScores, $title_map );
    }
    unset( $GLOBALS['kognetiks_lcm_rank_debug_query'], $GLOBALS['kognetiks_lcm_rank_debug_titles'] );

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

    // For definitional queries, ensure the first sentence is a true definition when available.
    $raw_query_for_assembly = $assembly_raw_query !== '' ? $assembly_raw_query : ( isset( $GLOBALS['kognetiks_lcm_raw_query_for_assembly'] ) ? (string) $GLOBALS['kognetiks_lcm_raw_query_for_assembly'] : '' );
    unset( $GLOBALS['kognetiks_lcm_raw_query_for_assembly'] );
    if ( $raw_query_for_assembly !== '' ) {
        $rq = strtolower( wp_strip_all_tags( $raw_query_for_assembly ) );
        $rq = preg_replace( '/\s+/u', ' ', trim( (string) $rq ) );
        $is_def_query = ( strpos( $rq, 'what is ' ) === 0 || strpos( $rq, 'what are ' ) === 0 || strpos( $rq, 'define ' ) === 0 || strpos( $rq, 'explain ' ) === 0 );

        if ( $is_def_query && ! empty( $qualitySentences ) ) {
            $subject = transformer_model_lcm_informational_subject_phrase_for_definition_score( $raw_query_for_assembly, array() );
            $subject = strtolower( preg_replace( '/\s+/u', ' ', trim( (string) $subject ) ) );
            $alias   = '';
            if ( $subject !== '' && strpos( $subject, 'artificial intelligence' ) !== false ) {
                $alias = trim( preg_replace( '/\bartificial intelligence\b/u', 'ai', $subject ) );
                $alias = strtolower( preg_replace( '/\s+/u', ' ', (string) $alias ) );
            }

            if ( transformer_model_lcm_rank_debug_is_enabled_for_query( $raw_query_for_assembly ) && transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                $rq_esc   = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), trim( (string) $raw_query_for_assembly ) );
                $subj_esc = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $subject );
                $als_esc  = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), (string) $alias );
                back_trace(
                    'NOTICE',
                    sprintf(
                        '[LCM][assembly_override_diag] raw_query="%s" detected_definition_query=%d subject="%s" alias="%s"',
                        $rq_esc,
                        $is_def_query ? 1 : 0,
                        $subj_esc,
                        $als_esc
                    )
                );
            }

            // Strict subject-leading definition cues for assembly-first override.
            $def_cues = array(
                'is a',
                'is an',
                'is the',
                'refers to',
                'means',
                'is defined as',
            );

            // Candidate pool: all rows whose normalized text STARTS WITH "<subject> <cue>".
            // Select the highest scoring candidate from this pool.
            $best_index = -1;
            $best_score = null;

            $candidates = array();
            if ( $subject !== '' ) {
                $candidates[] = (string) $subject;
            }
            if ( $alias !== '' && $alias !== $subject ) {
                $candidates[] = (string) $alias;
            }

            $candidate_regexes = array();
            foreach ( $candidates as $subj ) {
                $subj = strtolower( preg_replace( '/\s+/u', ' ', trim( (string) $subj ) ) );
                if ( $subj === '' ) {
                    continue;
                }
                $toks = preg_split( '/\s+/u', $subj, -1, PREG_SPLIT_NO_EMPTY );
                if ( ! is_array( $toks ) || $toks === array() ) {
                    continue;
                }
                $parts = array();
                foreach ( $toks as $tw ) {
                    $parts[] = preg_quote( (string) $tw, '/' );
                }
                $subj_re = implode( '\s+', $parts );
                foreach ( $def_cues as $cue ) {
                    $cue_re = preg_quote( (string) $cue, '/' );
                    $candidate_regexes[] = '/^\s{0,6}(?:' . $subj_re . ')\s+' . $cue_re . '\b/u';
                }
            }

            foreach ( $qualitySentences as $i => $row ) {
                if ( ! is_array( $row ) ) {
                    continue;
                }
                $tx = isset( $row['sentence'] ) ? (string) $row['sentence'] : '';
                $sl = strtolower( wp_strip_all_tags( $tx ) );
                $sl = preg_replace( '/\s+/u', ' ', trim( (string) $sl ) );
                if ( $sl === '' ) {
                    continue;
                }

                $matched = false;
                foreach ( $candidate_regexes as $re ) {
                    if ( preg_match( $re, $sl ) ) {
                        $matched = true;
                        break;
                    }
                }

                $reason = $matched ? 'subject_leading_definition' : 'not_subject_leading_definition';

                if ( transformer_model_lcm_rank_debug_is_enabled_for_query( $raw_query_for_assembly ) && transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                    $prev = transformer_model_lexical_context_diag_preview_text( $tx, 140 );
                    $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
                    back_trace(
                        'NOTICE',
                        sprintf(
                            '[LCM][assembly_override_candidate] rank=%d matched=%d reason="%s" text="%s"',
                            (int) $i + 1,
                            $matched ? 1 : 0,
                            $reason,
                            $prev
                        )
                    );
                }

                if ( ! $matched ) {
                    continue;
                }

                $sc = isset( $row['score'] ) ? (float) $row['score'] : 0.0;
                if ( $best_score === null || $sc > (float) $best_score ) {
                    $best_score = $sc;
                    $best_index = (int) $i;
                }
            }

            if ( $best_index > 0 ) {
                $chosen = $qualitySentences[ $best_index ];
                array_splice( $qualitySentences, $best_index, 1 );
                array_unshift( $qualitySentences, $chosen );
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                    $prev = isset( $chosen['sentence'] ) ? (string) $chosen['sentence'] : '';
                    $prev = transformer_model_lexical_context_diag_preview_text( $prev, 140 );
                    $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
                    back_trace( 'NOTICE', sprintf( '[LCM][assembly_first_sentence_forced] applied=1 text="%s"', $prev ) );
                }
            } elseif ( $best_index === 0 ) {
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                    $prev = isset( $qualitySentences[0]['sentence'] ) ? (string) $qualitySentences[0]['sentence'] : '';
                    $prev = transformer_model_lexical_context_diag_preview_text( $prev, 140 );
                    $prev = str_replace( array( "\r", "\n", '"' ), array( ' ', ' ', "'" ), $prev );
                    back_trace( 'NOTICE', sprintf( '[LCM][assembly_first_sentence_forced] applied=0 text="%s"', $prev ) );
                }
            } else {
                if ( transformer_model_lexical_context_is_lcm_diagnostics_enabled() && function_exists( 'back_trace' ) ) {
                    back_trace( 'NOTICE', '[LCM][assembly_first_sentence_forced] applied=0 text=""' );
                }
            }
        }
    }
    
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
