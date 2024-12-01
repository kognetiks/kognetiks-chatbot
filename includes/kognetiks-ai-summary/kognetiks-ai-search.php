<?php
/**
 * Kognetiks Chatbot for WordPress - Generate AI Search - Ver 2.2.0
 *
 * This file contains the code generating AI search results.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Return an AI summary for the page or post
function kognetiks_ai_search_shortcode() {

    // Output the search form
    $output = '
    <form role="search" method="get" class="kognetiks-ai-search-form" action="' . esc_url( home_url( '/' ) ) . '">
        <label for="kognetiks-ai-search-field">
            <span class="screen-reader-text">' . esc_html__( 'Search for:', 'textdomain' ) . '</span>
            <input type="search" id="kognetiks-ai-search-field" class="kognetiks-search-field" placeholder="' . esc_attr__( 'Search …', 'textdomain' ) . '" value="' . get_search_query() . '" name="s" />
        </label>
        <button type="submit" class="kognetiks-search-submit button button-primary">' . esc_html__( 'Search', 'textdomain' ) . '</button>
    </form>
    ';

    return $output;

}
// Register the shortcode
add_shortcode( 'kognetiks_ai_search', 'kognetiks_ai_search_shortcode' );
add_shortcode( 'ksearch', 'kognetiks_ai_search_shortcode' );

// Enqueue the CSS for the search form
function ai_search_shortcode_styles() {
    wp_enqueue_style( 'ai-search-shortcode-styles', plugin_dir_url( __FILE__ ) . '/kognetiks-ai-search.css' );
}
add_action( 'wp_enqueue_scripts', 'ai_search_shortcode_styles' );
