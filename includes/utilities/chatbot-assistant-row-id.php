<?php
/**
 * Assistant table row identity.
 *
 * Row 0 means no assistants-table row was selected. A positive value is that
 * row's primary key. Missing and invalid values are separate from both.
 *
 * @package chatbot-chatgpt
 */

if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Classify a row ID without using empty(), loose comparison, or bare intval().
 *
 * @param mixed $value Candidate from PHP, JSON, POST, a transient, or a queue item.
 * @return array{state:string,id:?int} state is missing, invalid, or valid.
 */
function chatbot_chatgpt_normalize_assistant_row_id( $value ) {

    if ( null === $value ) {
        return array(
            'state' => 'missing',
            'id'    => null,
        );
    }

    if ( is_int( $value ) ) {
        if ( $value < 0 ) {
            return array(
                'state' => 'invalid',
                'id'    => null,
            );
        }
        return array(
            'state' => 'valid',
            'id'    => $value,
        );
    }

    if ( ! is_string( $value ) || ! preg_match( '/^(0|[1-9][0-9]*)$/', $value ) ) {
        return array(
            'state' => 'invalid',
            'id'    => null,
        );
    }

    $id = (int) $value;
    // Reject values that do not round-trip, including integers PHP cannot hold.
    if ( (string) $id !== $value ) {
        return array(
            'state' => 'invalid',
            'id'    => null,
        );
    }

    return array(
        'state' => 'valid',
        'id'    => $id,
    );

}

/**
 * Canonical decimal string for transients, JSON, POST, and queue records.
 *
 * @param int $row_id Valid row ID, including 0.
 * @return string
 */
function chatbot_chatgpt_assistant_row_id_storage_string( $row_id ) {

    return (string) $row_id;

}

/**
 * Visitor-facing text when a selected row cannot be used.
 *
 * @return string
 */
function chatbot_chatgpt_assistant_row_unavailable_message() {

    return __( 'This chatbot configuration is unavailable.', 'chatbot-chatgpt' );

}

/**
 * Normalize assistant-N and agent-N tags onto chatbot-N.
 *
 * @param string $tag Shortcode tag.
 * @return string
 */
function chatbot_chatgpt_normalize_assistant_shortcode_tag( $tag ) {

    $tag = (string) $tag;
    if ( strpos( $tag, 'assistant-' ) === 0 ) {
        $tag = str_replace( 'assistant-', 'chatbot-', $tag );
    }
    if ( strpos( $tag, 'agent-' ) === 0 ) {
        $tag = str_replace( 'agent-', 'chatbot-', $tag );
    }
    return $tag;

}

/**
 * Positive row requested by a numbered shortcode, or null when the tag is not one.
 *
 * @param string $tag Tag after assistant/agent normalization.
 * @return int|null
 */
function chatbot_chatgpt_numbered_shortcode_row_request( $tag ) {

    if ( ! is_string( $tag ) || ! preg_match( '/^chatbot-([1-9][0-9]*)$/', $tag, $matches ) ) {
        return null;
    }

    $normalized = chatbot_chatgpt_normalize_assistant_row_id( $matches[1] );
    if ( 'valid' !== $normalized['state'] || $normalized['id'] < 1 ) {
        return null;
    }
    return $normalized['id'];

}

/**
 * Positive primary key from a loaded assistants-table row.
 *
 * @param mixed $row Row array.
 * @return int|null
 */
function chatbot_chatgpt_loaded_row_id( $row ) {

    if ( ! is_array( $row ) || ! array_key_exists( 'id', $row ) ) {
        return null;
    }
    $normalized = chatbot_chatgpt_normalize_assistant_row_id( $row['id'] );
    if ( 'valid' !== $normalized['state'] || $normalized['id'] < 1 ) {
        return null;
    }
    return $normalized['id'];

}

/**
 * True when the row's stored external ID is the ID this request resolved.
 *
 * An empty stored ID matches the original alias and an empty resolved ID.
 *
 * @param mixed  $row Row array.
 * @param string $resolved_assistant_id Resolved external ID or alias.
 * @return bool
 */
function chatbot_chatgpt_row_external_id_matches( $row, $resolved_assistant_id ) {

    $stored = '';
    if ( is_array( $row ) && array_key_exists( 'assistant_id', $row ) && ( is_string( $row['assistant_id'] ) || is_int( $row['assistant_id'] ) ) ) {
        $stored = trim( (string) $row['assistant_id'] );
    }
    $resolved = trim( (string) $resolved_assistant_id );
    if ( '' === $stored ) {
        return '' === $resolved || 'original' === $resolved;
    }
    return $stored === $resolved;

}

/**
 * Choose the local row for one shortcode render.
 *
 * @param string $tag Normalized shortcode tag.
 * @param mixed  $attribute_assistant Assistant attribute before a numbered row replaces it.
 * @param mixed  $numbered_row Row loaded for a numbered tag.
 * @param mixed  $common_name_row Row loaded for a Common Name attribute.
 * @return array{result:string,row_id:?int,external_id:string,row:array}
 */
function chatbot_chatgpt_decide_shortcode_row( $tag, $attribute_assistant, $numbered_row, $common_name_row ) {

    $numbered = chatbot_chatgpt_numbered_shortcode_row_request( $tag );
    if ( null !== $numbered ) {
        $loaded = chatbot_chatgpt_loaded_row_id( $numbered_row );
        if ( null === $loaded || $loaded !== $numbered ) {
            return array(
                'result'      => 'unavailable',
                'row_id'      => null,
                'external_id' => '',
                'row'         => array(),
            );
        }
        $external = '';
        if ( is_array( $numbered_row ) && array_key_exists( 'assistant_id', $numbered_row ) && ( is_string( $numbered_row['assistant_id'] ) || is_int( $numbered_row['assistant_id'] ) ) ) {
            $external = trim( (string) $numbered_row['assistant_id'] );
        }
        return array(
            'result'      => 'selected',
            'row_id'      => $loaded,
            'external_id' => $external,
            'row'         => $numbered_row,
        );
    }

    $attribute = is_string( $attribute_assistant ) ? trim( $attribute_assistant ) : '';
    $raw_external = '' !== $attribute && (
        chatbot_chatgpt_id_starts_with( $attribute, 'asst_' )
        || chatbot_chatgpt_id_starts_with( $attribute, 'pmpt_' )
        || chatbot_chatgpt_id_starts_with( $attribute, 'ag:' )
        || chatbot_chatgpt_id_starts_with( $attribute, 'websearch' )
    );
    $reserved_alias = in_array( $attribute, array( 'primary', 'alternate', 'original' ), true );

    if ( '' !== $attribute && ! $raw_external && ! $reserved_alias ) {
        $common_id = chatbot_chatgpt_loaded_row_id( $common_name_row );
        if ( null !== $common_id ) {
            $external = '';
            if ( is_array( $common_name_row ) && array_key_exists( 'assistant_id', $common_name_row ) ) {
                $external = trim( (string) $common_name_row['assistant_id'] );
            }
            return array(
                'result'      => 'selected',
                'row_id'      => $common_id,
                'external_id' => $external,
                'row'         => $common_name_row,
            );
        }
    }

    return array(
        'result'      => 'selected',
        'row_id'      => 0,
        'external_id' => '',
        'row'         => array(),
    );

}

/**
 * Compare a browser-supplied row ID with the row selected while rendering the page.
 *
 * A missing posted field is legacy routing. It is not row 0 and it does not
 * invent a positive row from the transient.
 *
 * @param bool     $posted_present Whether the request included the field.
 * @param mixed    $posted_value Raw posted value.
 * @param bool     $server_present Whether the page transient has a stored ID.
 * @param mixed    $server_value Stored canonical string.
 * @param string   $resolved_assistant_id External ID after assistant resolution.
 * @param callable $load_row function( int $row_id ): array|null
 * @return array{result:string,row_id:?int,error:string}
 */
function chatbot_chatgpt_validate_assistant_row_selection( $posted_present, $posted_value, $server_present, $server_value, $resolved_assistant_id, $load_row ) {

    if ( ! $posted_present ) {
        return array(
            'result' => 'legacy',
            'row_id' => null,
            'error'  => '',
        );
    }

    $posted = chatbot_chatgpt_normalize_assistant_row_id( $posted_value );
    if ( 'valid' !== $posted['state'] || ! $server_present ) {
        return array(
            'result' => 'rejected',
            'row_id' => null,
            'error'  => chatbot_chatgpt_assistant_row_unavailable_message(),
        );
    }

    $server = chatbot_chatgpt_normalize_assistant_row_id( $server_value );
    if ( 'valid' !== $server['state'] || $server['id'] !== $posted['id'] ) {
        return array(
            'result' => 'rejected',
            'row_id' => null,
            'error'  => chatbot_chatgpt_assistant_row_unavailable_message(),
        );
    }

    if ( 0 === $posted['id'] ) {
        return array(
            'result' => 'trusted',
            'row_id' => 0,
            'error'  => '',
        );
    }

    $row = is_callable( $load_row ) ? call_user_func( $load_row, $posted['id'] ) : null;
    if ( chatbot_chatgpt_loaded_row_id( $row ) !== $posted['id'] || ! chatbot_chatgpt_row_external_id_matches( $row, $resolved_assistant_id ) ) {
        return array(
            'result' => 'rejected',
            'row_id' => null,
            'error'  => chatbot_chatgpt_assistant_row_unavailable_message(),
        );
    }

    return array(
        'result' => 'trusted',
        'row_id' => $posted['id'],
        'error'  => '',
    );

}

/**
 * Validate the row ID stored on a queue item.
 *
 * Absence of the field is a legacy item. A stored positive ID that no longer
 * matches its row is rejected and is not treated as row 0.
 *
 * @param mixed    $item Queue item.
 * @param callable $load_row function( int $row_id ): array|null
 * @return array{result:string,row_id:?int,error:string}
 */
function chatbot_chatgpt_validate_queued_assistant_row( $item, $load_row ) {

    if ( ! is_array( $item ) || ! array_key_exists( 'assistant_row_id', $item ) ) {
        return array(
            'result' => 'legacy',
            'row_id' => null,
            'error'  => '',
        );
    }

    $normalized = chatbot_chatgpt_normalize_assistant_row_id( $item['assistant_row_id'] );
    if ( 'valid' !== $normalized['state'] ) {
        return array(
            'result' => 'rejected',
            'row_id' => null,
            'error'  => chatbot_chatgpt_assistant_row_unavailable_message(),
        );
    }

    if ( 0 === $normalized['id'] ) {
        return array(
            'result' => 'trusted',
            'row_id' => 0,
            'error'  => '',
        );
    }

    $row = is_callable( $load_row ) ? call_user_func( $load_row, $normalized['id'] ) : null;
    $queued_assistant = isset( $item['assistant_id'] ) ? $item['assistant_id'] : '';
    if ( chatbot_chatgpt_loaded_row_id( $row ) !== $normalized['id'] || ! chatbot_chatgpt_row_external_id_matches( $row, $queued_assistant ) ) {
        return array(
            'result' => 'rejected',
            'row_id' => null,
            'error'  => chatbot_chatgpt_assistant_row_unavailable_message(),
        );
    }

    return array(
        'result' => 'trusted',
        'row_id' => $normalized['id'],
        'error'  => '',
    );

}

/**
 * Add a trusted row ID to a queue item. Null leaves older items unchanged.
 *
 * @param array      $item Queue item.
 * @param int|string|null $assistant_row_id Trusted ID, or null to omit the field.
 * @return array|null Null when the supplied ID is not canonical.
 */
function chatbot_chatgpt_annotate_queue_item_row_id( $item, $assistant_row_id = null ) {

    if ( ! is_array( $item ) ) {
        return null;
    }
    if ( null === $assistant_row_id ) {
        return $item;
    }

    $normalized = chatbot_chatgpt_normalize_assistant_row_id( $assistant_row_id );
    if ( 'valid' !== $normalized['state'] ) {
        return null;
    }

    $item['assistant_row_id'] = chatbot_chatgpt_assistant_row_id_storage_string( $normalized['id'] );
    return $item;

}

/**
 * Positive row ID to pass into the Responses local-row lookup.
 *
 * Trusted 0 and legacy both return null so the lookup stays on the external ID.
 *
 * @param array $decision Validation result.
 * @return int|null
 */
function chatbot_chatgpt_responses_row_id_from_decision( $decision ) {

    if ( ! is_array( $decision ) || ! isset( $decision['result'] ) || 'trusted' !== $decision['result'] ) {
        return null;
    }
    if ( ! isset( $decision['row_id'] ) || ! is_int( $decision['row_id'] ) || $decision['row_id'] < 1 ) {
        return null;
    }
    return $decision['row_id'];

}

/**
 * Apply a browser-supplied assistant ID without replacing a stored selection.
 *
 * An empty server assistant may be filled by a specialized posted ID. A stored
 * value, including explicit "original", stays when the posted value differs.
 * The caller must validate the row and only then persist the result.
 *
 * @param string $server_assistant_id Assistant ID already stored for this page.
 * @param string $server_alias Alias already stored for this page.
 * @param bool   $posted_present Whether the request included assistant_id.
 * @param string $posted_assistant_id Sanitized posted assistant ID.
 * @return array{assistant_id:string,assistant_alias:string}
 */
function chatbot_chatgpt_merge_posted_assistant_selection( $server_assistant_id, $server_alias, $posted_present, $posted_assistant_id ) {

    $assistant_id = is_string( $server_assistant_id ) ? $server_assistant_id : '';
    $alias        = is_string( $server_alias ) ? $server_alias : '';
    $posted       = is_string( $posted_assistant_id ) ? $posted_assistant_id : '';

    $specialized = false;
    if ( $posted_present && '' !== $posted ) {
        if ( function_exists( 'chatbot_chatgpt_is_specialized_assistant_id' ) ) {
            $specialized = chatbot_chatgpt_is_specialized_assistant_id( $posted );
        } else {
            $specialized = chatbot_chatgpt_id_starts_with( $posted, 'asst_' )
                || chatbot_chatgpt_id_starts_with( $posted, 'pmpt_' )
                || chatbot_chatgpt_id_starts_with( $posted, 'ag:' )
                || chatbot_chatgpt_id_starts_with( $posted, 'websearch' );
        }
    }

    if ( $specialized && '' === $assistant_id ) {
        $assistant_id = $posted;
        if ( '' === $alias || 'original' === $alias ) {
            $alias = $posted;
        }
    }

    return array(
        'assistant_id'    => $assistant_id,
        'assistant_alias' => $alias,
    );

}

/**
 * Assistant APIs are implemented for these platforms only.
 *
 * The row ID is not an input. Platform selection stays the existing setting.
 *
 * @param string $platform Platform choice.
 * @param string $use_assistant_id Yes or No.
 * @return string
 */
function chatbot_chatgpt_use_assistant_id_for_platform( $platform, $use_assistant_id ) {

    if ( 'Yes' === $use_assistant_id && ! in_array( $platform, array( 'OpenAI', 'Azure OpenAI', 'Mistral' ), true ) ) {
        return 'No';
    }
    return $use_assistant_id;

}

/**
 * True only for an OpenAI asst_ or pmpt_ request that is still allowed to use an assistant.
 *
 * @param string $platform Platform choice.
 * @param string $use_assistant_id Yes or No before or after the platform guard.
 * @param string $assistant_id Resolved external ID.
 * @return bool
 */
function chatbot_chatgpt_routes_openai_responses( $platform, $use_assistant_id, $assistant_id ) {

    $use_assistant_id = chatbot_chatgpt_use_assistant_id_for_platform( $platform, $use_assistant_id );
    if ( 'Yes' !== $use_assistant_id || 'OpenAI' !== $platform ) {
        return false;
    }
    $assistant_id = (string) $assistant_id;
    if ( function_exists( 'chatbot_chatgpt_id_starts_with' ) ) {
        return chatbot_chatgpt_id_starts_with( $assistant_id, 'asst_' ) || chatbot_chatgpt_id_starts_with( $assistant_id, 'pmpt_' );
    }
    return 0 === strpos( $assistant_id, 'asst_' ) || 0 === strpos( $assistant_id, 'pmpt_' );

}
