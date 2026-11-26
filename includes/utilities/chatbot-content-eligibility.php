<?php
/**
 * Kognetiks Chatbot - Content eligibility for knowledge and AI context.
 *
 * Only demonstrably public WordPress content may be ingested or sent to an AI provider.
 * A logged-in editor, a post password, or the ability to view the content is not authorization.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * SQL fragment for a published post with an empty password.
 *
 * The alias, when supplied, must be a simple SQL identifier controlled by this plugin.
 *
 * @param string $alias Table alias, or an empty string.
 * @return string
 */
function chatbot_chatgpt_knowledge_post_sql_clause( $alias = '' ) {
    $alias = (string) $alias;
    if ( $alias !== '' && ! preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', $alias ) ) {
        $alias = '';
    }

    $prefix = ( $alias === '' ) ? '' : $alias . '.';

    return $prefix . "post_status = 'publish' AND (" . $prefix . "post_password = '' OR " . $prefix . "post_password IS NULL)";
}

/**
 * Whether a post type is public in WordPress.
 *
 * Unregistered and non-public types are not eligible. This does not detect membership or paywall plugins.
 *
 * @param string $post_type Post type slug.
 * @return bool
 */
function chatbot_chatgpt_is_public_post_type( $post_type ) {
    $post_type = (string) $post_type;
    if ( $post_type === '' || ! function_exists( 'get_post_type_object' ) ) {
        return false;
    }

    $object = get_post_type_object( $post_type );

    return ( is_object( $object ) && ! empty( $object->public ) );
}

/**
 * Keep only public post types from a list chosen by settings or the database.
 *
 * @param mixed $post_types Post type slugs.
 * @return string[]
 */
function chatbot_chatgpt_filter_public_post_types( $post_types ) {
    if ( ! is_array( $post_types ) ) {
        return array();
    }

    $filtered = array();
    foreach ( $post_types as $post_type ) {
        $post_type = (string) $post_type;
        if ( chatbot_chatgpt_is_public_post_type( $post_type ) ) {
            $filtered[] = $post_type;
        }
    }

    return array_values( array_unique( $filtered ) );
}

/**
 * Load the post WordPress currently stores for an id or an incomplete row.
 *
 * @param mixed $post Post ID, WP_Post, or a row object.
 * @return object|null
 */
function chatbot_chatgpt_resolve_post_for_eligibility( $post ) {
    if ( is_numeric( $post ) || ( is_object( $post ) && isset( $post->ID ) ) ) {
        $post_id = is_object( $post ) ? (int) $post->ID : (int) $post;
        if ( $post_id <= 0 || ! function_exists( 'get_post' ) ) {
            return null;
        }

        $resolved = get_post( $post_id );
        if ( is_object( $resolved ) && isset( $resolved->post_status, $resolved->post_type ) ) {
            return $resolved;
        }

        return null;
    }

    if ( is_object( $post ) && isset( $post->post_status, $post->post_type ) ) {
        return $post;
    }

    return null;
}

/**
 * Allow or deny the fields already on a post object, without loading it again.
 *
 * Use this for a before/after snapshot. The ID-based check reloads the current row.
 *
 * @param object|null $post Post snapshot.
 * @return bool
 */
function chatbot_chatgpt_post_snapshot_is_eligible( $post ) {
    if ( ! is_object( $post ) ) {
        return false;
    }

    $status = isset( $post->post_status ) ? (string) $post->post_status : '';
    if ( $status !== 'publish' ) {
        return false;
    }

    $password = '';
    if ( isset( $post->post_password ) && is_string( $post->post_password ) ) {
        $password = $post->post_password;
    }
    if ( $password !== '' ) {
        return false;
    }

    $post_type = isset( $post->post_type ) ? (string) $post->post_type : '';

    return chatbot_chatgpt_is_public_post_type( $post_type );
}

/**
 * Explicit allow or deny for one post.
 *
 * Allowed: a real post, public post type, publish status, and an empty password.
 * Denied: missing posts, every other status, non-public types, and any nonempty password.
 *
 * @param mixed $post Post ID, WP_Post, or a row object.
 * @return bool
 */
function chatbot_chatgpt_is_post_eligible_for_knowledge( $post ) {
    return chatbot_chatgpt_post_snapshot_is_eligible( chatbot_chatgpt_resolve_post_for_eligibility( $post ) );
}

/**
 * Post body for knowledge or prompt context. Ineligible posts contribute nothing.
 *
 * @param mixed $post Post ID, WP_Post, or a row object.
 * @return string
 */
function chatbot_chatgpt_post_content_for_knowledge( $post ) {
    $resolved = chatbot_chatgpt_resolve_post_for_eligibility( $post );
    if ( ! is_object( $resolved ) || ! chatbot_chatgpt_is_post_eligible_for_knowledge( $resolved ) ) {
        return '';
    }

    return isset( $resolved->post_content ) ? (string) $resolved->post_content : '';
}

/**
 * Approved comments are eligible only when the parent post is eligible.
 *
 * Missing, zero, and deleted parent ids are denied.
 *
 * @param mixed $comment Comment ID, WP_Comment, array, or row.
 * @return bool
 */
function chatbot_chatgpt_is_comment_eligible_for_knowledge( $comment ) {
    if ( is_numeric( $comment ) ) {
        if ( ! function_exists( 'get_comment' ) ) {
            return false;
        }
        $comment = get_comment( (int) $comment );
    }

    if ( is_array( $comment ) ) {
        $comment = (object) $comment;
    }

    if ( ! is_object( $comment ) ) {
        return false;
    }

    $approved = '';
    if ( isset( $comment->comment_approved ) && ( is_string( $comment->comment_approved ) || is_int( $comment->comment_approved ) ) ) {
        $approved = (string) $comment->comment_approved;
    }
    if ( $approved !== '1' ) {
        return false;
    }

    $parent_id = isset( $comment->comment_post_ID ) ? (int) $comment->comment_post_ID : 0;
    if ( $parent_id <= 0 || ! function_exists( 'get_post' ) ) {
        return false;
    }

    $parent = get_post( $parent_id );
    if ( ! is_object( $parent ) ) {
        return false;
    }

    return chatbot_chatgpt_is_post_eligible_for_knowledge( $parent );
}
