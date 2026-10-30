<?php
/**
 * Kognetiks Chatbot - All in One SEO shortcode compatibility.
 *
 * AIOSEO expands shortcodes while it builds descriptions. Chatbot widget
 * shortcodes are not metadata: they print the widget and can call out to
 * resolve an assistant or prompt name. Listing them on
 * `aioseo_conflicting_shortcodes` tells AIOSEO to set those registrations
 * aside for that pass and restore them afterward.
 *
 * The callback runs when AIOSEO applies the filter, so it sees the shortcode
 * registry as it exists then. Shortcodes themselves are still registered on
 * `init` and still run on a normal front-end request. This file does not
 * call AIOSEO, and it does not require any other Kognetiks plugin.
 *
 * The temporary mu-plugin kognetiks-aioseo-shortcode-compat.php may stay
 * installed during migration. That callback uniques the list, and this one
 * appends a tag only when it is not already present. AIOSEO then removes
 * each tag once, so the two registrations do not stack.
 *
 * @package chatbot-chatgpt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the AIOSEO conflicting-shortcode callback once.
 *
 * Registration is immediate, not deferred to `init`. The tag list is built
 * later, when the filter runs, which is after Chatbot aliases are registered
 * for description generation.
 */
function chatbot_chatgpt_register_aioseo_shortcode_compat() {
    if ( false !== has_filter( 'aioseo_conflicting_shortcodes', 'chatbot_chatgpt_aioseo_conflicting_shortcodes' ) ) {
        return;
    }

    add_filter( 'aioseo_conflicting_shortcodes', 'chatbot_chatgpt_aioseo_conflicting_shortcodes' );
}

/**
 * Tag names AIOSEO should not execute while building metadata.
 *
 * Always includes the three base widget tags. Numbered chatbot-N,
 * assistant-N, and agent-N tags are read from the live registry, as are any
 * other tags registered to the widget callback. Unregistered numbers are
 * not invented.
 *
 * @return string[]
 */
function chatbot_chatgpt_aioseo_conflicting_shortcode_tags() {
    $tags = array(
        'chatbot',
        'chatbot_chatgpt',
        'kognetiks_chatbot',
    );

    global $shortcode_tags;

    if ( ! is_array( $shortcode_tags ) ) {
        return $tags;
    }

    foreach ( $shortcode_tags as $tag => $callback ) {
        $tag = (string) $tag;
        if ( '' === $tag ) {
            continue;
        }

        if ( preg_match( '/^(?:chatbot|assistant|agent)-[0-9]+$/', $tag ) ) {
            $tags[] = $tag;
            continue;
        }

        if ( is_string( $callback ) && 'chatbot_chatgpt_shortcode' === $callback ) {
            $tags[] = $tag;
        }
    }

    return $tags;
}

/**
 * Add Chatbot widget shortcodes to AIOSEO's conflicting-shortcode list.
 *
 * Non-arrays are returned unchanged, matching the tested mu-plugin. Existing
 * keys and values are left in place. Tags already present are not appended
 * again, so a second copy of this callback (or the mu-plugin) does not
 * duplicate entries.
 *
 * @param mixed $shortcodes Tag list supplied by AIOSEO or an earlier callback.
 * @return mixed
 */
function chatbot_chatgpt_aioseo_conflicting_shortcodes( $shortcodes ) {
    if ( ! is_array( $shortcodes ) ) {
        return $shortcodes;
    }

    foreach ( chatbot_chatgpt_aioseo_conflicting_shortcode_tags() as $tag ) {
        if ( ! in_array( $tag, $shortcodes, true ) ) {
            $shortcodes[] = $tag;
        }
    }

    return $shortcodes;
}

chatbot_chatgpt_register_aioseo_shortcode_compat();
