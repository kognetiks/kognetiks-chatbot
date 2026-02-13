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

    // Fetch WordPress content
    $corpus = transformer_model_lexical_context_fetch_wordpress_content();
    
    if (empty($corpus)) {
        return "I don't have enough content to generate a response. Please add some posts or pages to your WordPress site.";
    }

    // Build embeddings
    $embeddings = transformer_model_lexical_context_get_cached_embeddings($corpus);

    if (empty($embeddings)) {
        return "I'm having trouble processing the content. Please try again later.";
    }

    // Generate contextual response
    $response = transformer_model_lexical_context_generate_contextual_response($input, $embeddings, $corpus, $max_tokens);

    return $response;

}

// Function to get cached embeddings
function transformer_model_lexical_context_get_cached_embeddings($corpus, $windowSize = 3) {

    // Cache directory path
    $cacheDir = __DIR__ . '/lexical_embeddings_cache';
    
    // Ensure cache directory exists
    if (!file_exists($cacheDir)) {
        if (!wp_mkdir_p($cacheDir)) {
            // If directory creation fails, log error and return empty array
            prod_trace('ERROR', 'Failed to create cache directory: ' . $cacheDir);
            return [];
        }
    }
    
    // Create index.php for security if it doesn't exist
    $indexFile = $cacheDir . '/index.php';
    if (!file_exists($indexFile)) {
        $indexContent = "<?php\n// Silence is golden.\n";
        file_put_contents($indexFile, $indexContent);
    }
    
    $cacheFile = $cacheDir . '/lexical_embeddings_cache.php';
    $cacheVersionFile = $cacheDir . '/lexical_embeddings_cache_version.txt';
    
    // Calculate corpus hash for cache invalidation
    $corpusHash = hash('sha256', $corpus);
    $cacheValid = false;

    // Check if cache exists and is valid
    if (file_exists($cacheFile) && file_exists($cacheVersionFile)) {
        $cachedHash = trim(file_get_contents($cacheVersionFile));
        if ($cachedHash === $corpusHash) {
            $cacheValid = true;
        }
    }

    if ($cacheValid) {
        $embeddings = transformer_model_lexical_context_load_cache($cacheFile);
        // Validate cached embeddings structure
        if (is_array($embeddings) && !empty($embeddings)) {
            return $embeddings;
        }
    }

    // Check for old uncompressed cache and migrate it
    transformer_model_lexical_context_migrate_old_cache($cacheFile);

    // Rebuild cache if invalid or missing
    $embeddings = transformer_model_lexical_context_build_pmi_matrix($corpus, $windowSize);
    
    if (!empty($embeddings)) {
        // Write cache file with compression
        if (transformer_model_lexical_context_save_cache($cacheFile, $embeddings)) {
            file_put_contents($cacheVersionFile, $corpusHash);
        }
    }

    return $embeddings;

}

// Function to fetch WordPress content
function transformer_model_lexical_context_fetch_wordpress_content() {

    global $wpdb;

    // Query to get post and page content with better error handling
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT post_content FROM {$wpdb->posts} WHERE post_status = %s AND (post_type = %s OR post_type = %s) AND post_content != ''",
            'publish', 'post', 'page'
        ),
        ARRAY_A
    );

    if (empty($results) || !is_array($results)) {
        return '';
    }

    // Combine all content into a single string
    $content = '';
    foreach ($results as $row) {
        if (isset($row['post_content']) && !empty($row['post_content'])) {
            $content .= ' ' . $row['post_content'];
        }
    }

    // Clean and normalize content
    $content = wp_strip_all_tags( $content ); // Remove HTML tags
    $content = preg_replace('/\s+/', ' ', $content); // Normalize whitespace
    $content = trim($content);

    return $content;

}

// Function to build a PMI matrix for word embeddings
function transformer_model_lexical_context_build_pmi_matrix($corpus, $windowSize = 3) {

    if (empty($corpus)) {
        return [];
    }

    // Improved tokenization: handle punctuation and normalize
    $corpus = preg_replace('/[^\w\s]/u', ' ', $corpus); // Remove punctuation but keep spaces
    $words = preg_split('/\s+/', strtolower(trim($corpus)));
    $words = array_filter($words, function($word) {
        return !empty($word) && strlen($word) > 1; // Filter out single characters and empty strings
    });
    $words = array_values($words); // Re-index array

    if (empty($words)) {
        return [];
    }

    $vocab = array_unique($words);
    $wordCounts = array_count_values($words);
    $totalWords = count($words);
    $totalCoOccurrences = 0; // Track total co-occurrence pairs

    // Initialize co-occurrence counts
    $coOccurrenceCounts = [];

    $wordCount = count($words);
    for ($i = 0; $i < $wordCount; $i++) {
        $word = $words[$i];
        if (empty($word)) {
            continue;
        }
        
        $contextStart = max(0, $i - $windowSize);
        $contextEnd = min($wordCount - 1, $i + $windowSize);
        
        for ($j = $contextStart; $j <= $contextEnd; $j++) {
            if ($i != $j && isset($words[$j]) && !empty($words[$j])) {
                $contextWord = $words[$j];
                if (!isset($coOccurrenceCounts[$word][$contextWord])) {
                    $coOccurrenceCounts[$word][$contextWord] = 0;
                }
                $coOccurrenceCounts[$word][$contextWord] += 1;
                $totalCoOccurrences += 1;
            }
        }
    }

    if ($totalCoOccurrences == 0) {
        return [];
    }

    // Compute PMI values with corrected formula
    $embeddings = [];
    foreach ($coOccurrenceCounts as $word => $contexts) {
        if (!isset($wordCounts[$word]) || $wordCounts[$word] == 0) {
            continue;
        }
        
        foreach ($contexts as $contextWord => $count) {
            if (!isset($wordCounts[$contextWord]) || $wordCounts[$contextWord] == 0) {
                continue;
            }
            
            // Fixed PMI calculation
            $p_word = $wordCounts[$word] / $totalWords;
            $p_context = $wordCounts[$contextWord] / $totalWords;
            $p_word_context = $count / $totalCoOccurrences; // Fixed: use total co-occurrences, not total words
            
            // Avoid division by zero and negative logarithms
            if ($p_word > 0 && $p_context > 0 && $p_word_context > 0) {
                $ratio = $p_word_context / ($p_word * $p_context);
                if ($ratio > 0) {
                    // Use log base 2 for PMI (standard in NLP)
                    $pmi = log($ratio, 2);
                    if ($pmi > 0 && is_finite($pmi)) {
                        // Optimization: Filter out very low PMI values (sparse storage)
                        // Only store PMI values above threshold (e.g., 0.1) to reduce file size
                        $pmiThreshold = 0.1;
                        if ($pmi >= $pmiThreshold) {
                            // Reduce precision to 3 decimal places to save space
                            $embeddings[$word][$contextWord] = round($pmi, 3);
                        }
                    }
                }
            }
        }
    }

    return $embeddings;
    
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
function transformer_model_lexical_context_generate_contextual_response($input, $embeddings, $corpus, $responseLength = 50) {

    global $stopWords;

    // Ensure stopWords is initialized
    if (!isset($stopWords) || !is_array($stopWords)) {
        $stopWords = [];
    }

    if (empty($embeddings)) {
        return "I'm having trouble understanding that. Could you rephrase your question?";
    }

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
    $response = transformer_model_lexical_context_build_sentences_from_corpus(
        $corpus, 
        $queryWords, 
        $inputWords, 
        $responseLength,
        $sentenceResponseCount,
        $similarityThreshold,
        $leadingSentencesRatio,
        $leadingTokenRatio
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

// Function to build sentences from corpus using query words
function transformer_model_lexical_context_build_sentences_from_corpus($corpus, $searchWords, $inputWords, $maxWords, $sentenceResponseCount = 5, $similarityThreshold = 0.3, $leadingSentencesRatio = 0.2, $leadingTokenRatio = 0.2) {
    
    // Split corpus into sentences
    $sentences = preg_split('/(?<=[.!?])\s+/', $corpus, -1, PREG_SPLIT_NO_EMPTY);
    
    if (empty($sentences)) {
        return '';
    }
    
    // Score sentences based on how well they match the query
    $sentenceScores = [];
    $searchWordsLower = array_map('strtolower', $searchWords);
    $inputWordsLower = array_map('strtolower', $inputWords);
    
    foreach ($sentences as $sentence) {
        $sentenceLower = strtolower($sentence);
        $sentenceTrimmed = trim($sentence);
        $sentenceWordCount = str_word_count($sentenceTrimmed);
        
        // Skip very long sentences (>60 words) - they're likely run-on or contain too much filler
        if ($sentenceWordCount > 60) {
            continue;
        }
        
        // Skip sentences that are mostly citations, author lists, or metadata
        // These often start with patterns like "by Author Name" or contain lots of commas with names
        $citationPatterns = [
            '/^by\s+[A-Z][a-z]+\s+[A-Z]/', // "By Author Name"
            '/^\d{4}[,\s]/', // Year at start
            '/^[A-Z][a-z]+\s+[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*,\s+[A-Z][a-z]+/', // Multiple names
        ];
        $isCitation = false;
        foreach ($citationPatterns as $pattern) {
            if (preg_match($pattern, $sentenceTrimmed)) {
                $isCitation = true;
                break;
            }
        }
        // Also check if sentence has too many commas (likely a list)
        $commaCount = substr_count($sentenceTrimmed, ',');
        if ($commaCount > 5 && $sentenceWordCount < 30) {
            $isCitation = true; // Likely a citation or list
        }
        
        if ($isCitation) {
            continue; // Skip citation-style sentences
        }
        
        // Skip sentences that are just questions without answers
        // These often start with "What is" but don't contain the actual answer
        $questionPatterns = [
            '/^what\s+is\s+[^?]+\?$/i', // "What is X?" - just a question
            '/^what\s+are\s+[^?]+\?$/i', // "What are X?" - just a question
        ];
        $isJustQuestion = false;
        foreach ($questionPatterns as $pattern) {
            if (preg_match($pattern, $sentenceTrimmed)) {
                // Check if it contains any of our input words (if it does, it might be relevant)
                $hasInputWords = false;
                foreach ($inputWordsLower as $inputWord) {
                    if (stripos($sentenceLower, $inputWord) !== false) {
                        $hasInputWords = true;
                        break;
                    }
                }
                // If it's just a question and doesn't have our input words, skip it
                if (!$hasInputWords) {
                    $isJustQuestion = true;
                    break;
                }
            }
        }
        
        if ($isJustQuestion) {
            continue; // Skip sentences that are just questions
        }
        
        $score = 0;
        $matchedWords = [];
        $inputWordsMatched = 0;
        $inputWordsAtStart = 0; // Bonus for input words appearing early in sentence
        
        // Prioritize input words (weight them higher)
        foreach ($inputWordsLower as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $count = preg_match_all($pattern, $sentenceLower);
            if ($count > 0) {
                $score += $count * 10; // Input words weighted 10x higher
                $inputWordsMatched++;
                if (!in_array($word, $matchedWords)) {
                    $matchedWords[] = $word;
                }
                
                // Bonus if input word appears in first 10 words (more direct/relevant)
                $firstWords = implode(' ', array_slice(explode(' ', $sentenceLower), 0, 10));
                if (preg_match($pattern, $firstWords)) {
                    $inputWordsAtStart++;
                    $score += 5; // Extra bonus for early appearance
                }
            }
        }
        
        // Also score based on similar words (but lower weight)
        foreach ($searchWordsLower as $word) {
            // Skip if already counted as input word
            if (in_array($word, $inputWordsLower)) {
                continue;
            }
            
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $count = preg_match_all($pattern, $sentenceLower);
            if ($count > 0) {
                $score += $count; // Similar words weighted normally
                if (!in_array($word, $matchedWords)) {
                    $matchedWords[] = $word;
                }
            }
        }
        
        // Calculate relevance density: how much of the sentence is actually relevant
        // Higher density = more relevant words per total words
        $relevantWordCount = count($matchedWords);
        $density = $sentenceWordCount > 0 ? ($relevantWordCount / $sentenceWordCount) : 0;
        
        // Apply density bonus - prefer sentences where more words are relevant
        $score += $density * 5;
        
        // Apply length penalty - prefer shorter, more concise sentences
        // Ideal length is 10-25 words, penalize sentences outside this range
        if ($sentenceWordCount < 10) {
            $score *= 0.8; // Slightly penalize very short sentences
        } elseif ($sentenceWordCount > 40) {
            $score *= 0.7; // Penalize long sentences more
        } elseif ($sentenceWordCount > 30) {
            $score *= 0.9; // Slight penalty for longer sentences
        }
        
        // REQUIRE that sentences must match at least one significant input word
        // This prevents matching generic "what is" questions that don't answer the query
        $hasSignificantMatch = false;
        if (!empty($inputWordsLower)) {
            foreach ($inputWordsLower as $inputWord) {
                if (strlen($inputWord) >= 4) { // Only check words 4+ chars (significant terms)
                    $pattern = '/\b' . preg_quote($inputWord, '/') . '\b/i';
                    if (preg_match($pattern, $sentenceLower)) {
                        $hasSignificantMatch = true;
                        break;
                    }
                }
            }
        }
        
        // If we have input words but none match, require at least one match
        // Exception: if all input words are short (<4 chars), be more lenient
        $allShortWords = true;
        foreach ($inputWordsLower as $word) {
            if (strlen($word) >= 4) {
                $allShortWords = false;
                break;
            }
        }
        
        // Include sentences that:
        // 1. Match at least one significant input word (4+ chars), OR
        // 2. Match multiple input words (even if short), OR  
        // 3. Have very high similarity score (fallback)
        $minScore = $inputWordsMatched > 0 ? 1 : 10; // Higher threshold if no input words matched
        
        if ($hasSignificantMatch || $inputWordsMatched >= 2 || ($allShortWords && $inputWordsMatched > 0) || $score >= $minScore) {
            $sentenceScores[] = [
                'sentence' => $sentenceTrimmed,
                'score' => $score,
                'matched' => count($matchedWords),
                'inputMatched' => $inputWordsMatched,
                'wordCount' => $sentenceWordCount,
                'density' => $density,
                'inputAtStart' => $inputWordsAtStart,
                'hasSignificantMatch' => $hasSignificantMatch
            ];
        }
    }
    
    if (empty($sentenceScores)) {
        return '';
    }
    
    // Sort by: prioritize concise, direct, relevant sentences
    usort($sentenceScores, function($a, $b) {
        // First priority: sentences with significant matches (actual query terms)
        $aHasSig = isset($a['hasSignificantMatch']) ? $a['hasSignificantMatch'] : false;
        $bHasSig = isset($b['hasSignificantMatch']) ? $b['hasSignificantMatch'] : false;
        if ($aHasSig != $bHasSig) {
            return $bHasSig ? 1 : -1; // Significant matches first
        }
        // Second priority: sentences with input words at the start (more direct)
        if ($a['inputAtStart'] != $b['inputAtStart']) {
            return $b['inputAtStart'] - $a['inputAtStart'];
        }
        // Third priority: sentences with more input words matched
        if ($a['inputMatched'] != $b['inputMatched']) {
            return $b['inputMatched'] - $a['inputMatched'];
        }
        // Fourth priority: relevance density (more relevant words per total words)
        if (abs($a['density'] - $b['density']) > 0.1) {
            return $b['density'] > $a['density'] ? 1 : -1;
        }
        // Fifth priority: total score
        if ($a['score'] != $b['score']) {
            return $b['score'] - $a['score'];
        }
        // Sixth priority: prefer shorter sentences when scores are similar
        if (abs($a['wordCount'] - $b['wordCount']) > 5) {
            return $a['wordCount'] - $b['wordCount']; // Shorter is better
        }
        // Seventh priority: number of unique words matched
        return $b['matched'] - $a['matched'];
    });
    
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
