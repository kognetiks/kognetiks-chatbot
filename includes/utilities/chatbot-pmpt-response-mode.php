<?php
/**
 * Per-row Bridge or Local mode for OpenAI hosted prompts.
 *
 * The assistants table is unchanged. The mode is a site option keyed by the
 * numeric row ID. Missing and invalid values stay on Bridge. An explicit
 * Local setting that no longer matches its row fails instead of falling back.
 *
 * @package chatbot-chatgpt
 */

if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Option name for the row-ID mode map.
 *
 * @return string
 */
function chatbot_chatgpt_pmpt_row_mode_option_name() {
    return 'chatbot_chatgpt_pmpt_row_modes';
}

/**
 * Read the mode map. A missing option is an empty map, which means Bridge.
 *
 * @return array<string|int,mixed>
 */
function chatbot_chatgpt_get_pmpt_row_mode_map() {
    $map = get_option( chatbot_chatgpt_pmpt_row_mode_option_name(), array() );
    return is_array( $map ) ? $map : array();
}

/**
 * Replace the mode map. An empty map removes the option.
 *
 * @param array<string|int,mixed> $map Mode map.
 * @return void
 */
function chatbot_chatgpt_write_pmpt_row_mode_map( $map ) {
    $name = chatbot_chatgpt_pmpt_row_mode_option_name();
    if ( ! is_array( $map ) || array() === $map ) {
        delete_option( $name );
        return;
    }
    update_option( $name, $map );
}

/**
 * One map entry for a canonical row ID.
 *
 * @param array<string|int,mixed> $map Mode map.
 * @param int                     $row_id Positive row ID.
 * @return mixed|null
 */
function chatbot_chatgpt_pmpt_row_mode_entry( $map, $row_id ) {
    $key = chatbot_chatgpt_assistant_row_id_storage_string( $row_id );
    if ( isset( $map[ $key ] ) ) {
        return $map[ $key ];
    }
    $numeric = (int) $key;
    if ( isset( $map[ $numeric ] ) ) {
        return $map[ $numeric ];
    }
    return null;
}

/**
 * Remove every stored form of one row key.
 *
 * @param array<string|int,mixed> $map Mode map.
 * @param int                     $row_id Positive row ID.
 * @return array<string|int,mixed>
 */
function chatbot_chatgpt_pmpt_row_mode_without( $map, $row_id ) {
    $key = chatbot_chatgpt_assistant_row_id_storage_string( $row_id );
    unset( $map[ $key ], $map[ (int) $key ] );
    return $map;
}

/**
 * Whether the row itself has the instruction text Local mode requires.
 *
 * Additional Instructions are the copied hosted-prompt text. A Common Name
 * or the site-wide conversation fallback does not satisfy this check.
 *
 * @param mixed $row Assistants-table row.
 * @return bool
 */
function chatbot_chatgpt_pmpt_row_has_local_instructions( $row ) {
    if ( ! is_array( $row ) || ! isset( $row['additional_instructions'] ) || ! is_string( $row['additional_instructions'] ) ) {
        return false;
    }
    return '' !== trim( $row['additional_instructions'] );
}

/**
 * Decide how a pmpt_ request is built.
 *
 * Non-prompt IDs are unchanged. Row 0, a missing row field, and a raw
 * external ID stay on Bridge. An explicit Local entry that no longer matches
 * its row is an error and does not select another row.
 *
 * @param string $assistant_id External ID for this request.
 * @param mixed  $assistant_row_id Validated row ID, or null when absent.
 * @param mixed  $local_row Row loaded for that ID.
 * @return array{result:string,error:string}
 */
function chatbot_chatgpt_resolve_pmpt_responses_mode( $assistant_id, $assistant_row_id, $local_row ) {
    $assistant_id = is_string( $assistant_id ) ? trim( $assistant_id ) : '';
    $is_prompt = function_exists( 'chatbot_chatgpt_id_starts_with' )
        ? chatbot_chatgpt_id_starts_with( $assistant_id, 'pmpt_' )
        : ( 0 === strpos( $assistant_id, 'pmpt_' ) );
    if ( ! $is_prompt ) {
        return array(
            'result' => 'assistant',
            'error'  => '',
        );
    }

    $normalized = chatbot_chatgpt_normalize_assistant_row_id( $assistant_row_id );
    if ( 'valid' !== $normalized['state'] || $normalized['id'] < 1 ) {
        return array(
            'result' => 'bridge',
            'error'  => '',
        );
    }

    $entry = chatbot_chatgpt_pmpt_row_mode_entry( chatbot_chatgpt_get_pmpt_row_mode_map(), $normalized['id'] );
    $activated = is_array( $entry )
        && isset( $entry['mode'], $entry['assistant_id'], $entry['confirmed'] )
        && 'local' === $entry['mode']
        && ( '1' === $entry['confirmed'] || 1 === $entry['confirmed'] )
        && is_string( $entry['assistant_id'] )
        && '' !== trim( $entry['assistant_id'] );
    if ( ! $activated ) {
        return array(
            'result' => 'bridge',
            'error'  => '',
        );
    }

    $loaded_id = function_exists( 'chatbot_chatgpt_loaded_row_id' ) ? chatbot_chatgpt_loaded_row_id( $local_row ) : null;
    $current_id = ( is_array( $local_row ) && isset( $local_row['assistant_id'] ) && is_string( $local_row['assistant_id'] ) )
        ? trim( $local_row['assistant_id'] )
        : '';
    if ( $loaded_id !== $normalized['id'] || $current_id !== trim( $entry['assistant_id'] ) || ! chatbot_chatgpt_pmpt_row_has_local_instructions( $local_row ) ) {
        return array(
            'result' => 'error',
            'error'  => __( 'Error: This assistant is set to Local mode, but its copied instructions or hosted prompt ID no longer match the saved row. Restore the instructions in Manage Assistants, or switch the row back to Bridge.', 'chatbot-chatgpt' ),
        );
    }

    return array(
        'result' => 'local',
        'error'  => '',
    );
}

/**
 * Store Bridge or Local for one row without changing the assistants table.
 *
 * @param mixed  $row_id Requested row ID.
 * @param string $requested_mode bridge or local.
 * @param mixed  $confirmed Explicit administrator confirmation.
 * @param mixed  $row Row loaded by that ID.
 * @param bool   $authorized Whether capability and nonce checks passed.
 * @return array{ok:bool,error:string,mode:string}
 */
function chatbot_chatgpt_set_pmpt_row_mode( $row_id, $requested_mode, $confirmed, $row, $authorized ) {
    if ( true !== $authorized ) {
        return array(
            'ok'    => false,
            'error' => __( 'You are not allowed to change this assistant mode.', 'chatbot-chatgpt' ),
            'mode'  => '',
        );
    }

    $normalized = chatbot_chatgpt_normalize_assistant_row_id( $row_id );
    $loaded_id = function_exists( 'chatbot_chatgpt_loaded_row_id' ) ? chatbot_chatgpt_loaded_row_id( $row ) : null;
    if ( 'valid' !== $normalized['state'] || $normalized['id'] < 1 || $loaded_id !== $normalized['id'] ) {
        return array(
            'ok'    => false,
            'error' => __( 'This assistant row was not found. No mode was saved.', 'chatbot-chatgpt' ),
            'mode'  => '',
        );
    }

    $mode = is_string( $requested_mode ) ? strtolower( trim( $requested_mode ) ) : '';
    $map = chatbot_chatgpt_pmpt_row_mode_without( chatbot_chatgpt_get_pmpt_row_mode_map(), $normalized['id'] );
    if ( 'bridge' === $mode ) {
        chatbot_chatgpt_write_pmpt_row_mode_map( $map );
        return array(
            'ok'    => true,
            'error' => '',
            'mode'  => 'bridge',
        );
    }
    if ( 'local' !== $mode ) {
        return array(
            'ok'    => false,
            'error' => __( 'Prompt mode was not recognized. The saved mode was left unchanged.', 'chatbot-chatgpt' ),
            'mode'  => '',
        );
    }

    $assistant_id = ( is_array( $row ) && isset( $row['assistant_id'] ) && is_string( $row['assistant_id'] ) ) ? trim( $row['assistant_id'] ) : '';
    $is_prompt = function_exists( 'chatbot_chatgpt_id_starts_with' )
        ? chatbot_chatgpt_id_starts_with( $assistant_id, 'pmpt_' )
        : ( 0 === strpos( $assistant_id, 'pmpt_' ) );
    $confirmation = is_string( $confirmed ) ? trim( $confirmed ) : '';
    if ( ! $is_prompt || ( '1' !== $confirmation && 1 !== $confirmed ) || ! chatbot_chatgpt_pmpt_row_has_local_instructions( $row ) ) {
        return array(
            'ok'    => false,
            'error' => __( 'Local mode was not enabled. Confirm that this row is a hosted prompt and that its instructions have been copied into Additional Instructions.', 'chatbot-chatgpt' ),
            'mode'  => '',
        );
    }

    $map[ chatbot_chatgpt_assistant_row_id_storage_string( $normalized['id'] ) ] = array(
        'mode'         => 'local',
        'assistant_id' => $assistant_id,
        'confirmed'    => '1',
    );
    chatbot_chatgpt_write_pmpt_row_mode_map( $map );
    return array(
        'ok'    => true,
        'error' => '',
        'mode'  => 'local',
    );
}

/**
 * Drop a Local setting when the row's external ID changes.
 *
 * The new ID stays on Bridge. Instructions and the stored ID are not edited here.
 *
 * @param mixed  $row_id Row ID.
 * @param string $previous_assistant_id External ID before the edit.
 * @param string $new_assistant_id External ID after the edit.
 * @return void
 */
function chatbot_chatgpt_release_pmpt_row_mode_on_identity_change( $row_id, $previous_assistant_id, $new_assistant_id ) {
    $previous = is_string( $previous_assistant_id ) ? trim( $previous_assistant_id ) : '';
    $new = is_string( $new_assistant_id ) ? trim( $new_assistant_id ) : '';
    if ( $previous === $new ) {
        return;
    }
    $normalized = chatbot_chatgpt_normalize_assistant_row_id( $row_id );
    if ( 'valid' !== $normalized['state'] || $normalized['id'] < 1 ) {
        return;
    }
    $map = chatbot_chatgpt_pmpt_row_mode_without( chatbot_chatgpt_get_pmpt_row_mode_map(), $normalized['id'] );
    chatbot_chatgpt_write_pmpt_row_mode_map( $map );
}

/**
 * Remove the mode entry for a deleted row.
 *
 * @param mixed $row_id Row ID.
 * @return void
 */
function chatbot_chatgpt_remove_pmpt_row_mode( $row_id ) {
    chatbot_chatgpt_release_pmpt_row_mode_on_identity_change( $row_id, 'previous', 'deleted' );
}

/**
 * Administrator-only AJAX save. Public chatbot requests have no hook here.
 *
 * @return void
 */
function chatbot_chatgpt_ajax_set_pmpt_row_mode() {
    check_ajax_referer( 'chatbot_nonce_action', '_wpnonce' );

    $authorized = current_user_can( 'manage_options' );
    if ( ! $authorized ) {
        wp_send_json_error( __( 'You are not allowed to change this assistant mode.', 'chatbot-chatgpt' ), 403 );
    }

    $row_id = isset( $_POST['id'] ) ? wp_unslash( $_POST['id'] ) : null;
    $requested = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : '';
    $confirmed = isset( $_POST['confirmed'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmed'] ) ) : '';
    $normalized = chatbot_chatgpt_normalize_assistant_row_id( $row_id );
    $row = ( 'valid' === $normalized['state'] && $normalized['id'] > 0 && function_exists( 'get_chatbot_chatgpt_assistant_by_key' ) )
        ? get_chatbot_chatgpt_assistant_by_key( $normalized['id'] )
        : array();
    $result = chatbot_chatgpt_set_pmpt_row_mode( $row_id, $requested, $confirmed, $row, true );
    if ( ! $result['ok'] ) {
        wp_send_json_error( $result['error'] );
    }
    wp_send_json_success( $result['mode'] );
}
add_action( 'wp_ajax_chatbot_chatgpt_set_pmpt_row_mode', 'chatbot_chatgpt_ajax_set_pmpt_row_mode' );

/**
 * Bridge is the only pmpt_ body that also sends a developer message.
 *
 * @param string $assistant_id External ID.
 * @param string $request_body bridge or local.
 * @param string $additional_instructions Resolved extra instructions.
 * @return bool
 */
function chatbot_chatgpt_pmpt_bridge_adds_developer_message( $assistant_id, $request_body, $additional_instructions ) {
    $assistant_id = is_string( $assistant_id ) ? $assistant_id : '';
    $is_prompt = function_exists( 'chatbot_chatgpt_id_starts_with' )
        ? chatbot_chatgpt_id_starts_with( $assistant_id, 'pmpt_' )
        : ( 0 === strpos( $assistant_id, 'pmpt_' ) );
    return $is_prompt && 'bridge' === $request_body && is_string( $additional_instructions ) && '' !== $additional_instructions;
}

/**
 * Model and parsed store IDs shown before Local mode is enabled.
 *
 * @param mixed $row Assistants-table row.
 * @return array{model:string,stores:string[]}
 */
function chatbot_chatgpt_pmpt_local_request_preview( $row ) {
    $model = function_exists( 'chatbot_chatgpt_resolve_openai_chat_model' )
        ? chatbot_chatgpt_resolve_openai_chat_model()
        : '';
    $raw = ( is_array( $row ) && isset( $row['vector_store_id'] ) && is_string( $row['vector_store_id'] ) ) ? $row['vector_store_id'] : '';
    $stores = function_exists( 'chatbot_chatgpt_parse_vector_store_ids' )
        ? chatbot_chatgpt_parse_vector_store_ids( $raw )
        : array();
    return array(
        'model'  => is_string( $model ) ? $model : '',
        'stores' => $stores,
        'raw'    => $raw,
    );
}

/**
 * Extra Manage Assistants row for one hosted prompt.
 *
 * @param object $assistant Database row object.
 * @return string
 */
function chatbot_chatgpt_render_pmpt_row_mode_row( $assistant ) {
    $assistant_id = isset( $assistant->assistant_id ) ? trim( (string) $assistant->assistant_id ) : '';
    $is_prompt = function_exists( 'chatbot_chatgpt_id_starts_with' )
        ? chatbot_chatgpt_id_starts_with( $assistant_id, 'pmpt_' )
        : ( 0 === strpos( $assistant_id, 'pmpt_' ) );
    if ( ! $is_prompt ) {
        return '';
    }

    $row_id = isset( $assistant->id ) ? absint( $assistant->id ) : 0;
    $row = array(
        'id'                      => (string) $row_id,
        'assistant_id'            => $assistant_id,
        'common_name'             => isset( $assistant->common_name ) ? (string) $assistant->common_name : '',
        'additional_instructions' => isset( $assistant->additional_instructions ) ? (string) $assistant->additional_instructions : '',
        'vector_store_id'         => isset( $assistant->vector_store_id ) ? (string) $assistant->vector_store_id : '',
    );
    $decision = chatbot_chatgpt_resolve_pmpt_responses_mode( $assistant_id, $row_id, $row );
    $preview = chatbot_chatgpt_pmpt_local_request_preview( $row );
    $store_text = empty( $preview['stores'] ) ? __( 'none', 'chatbot-chatgpt' ) : implode( ', ', $preview['stores'] );
    $status = isset( $decision['result'] ) ? $decision['result'] : 'bridge';
    if ( 'local' === $status ) {
        $status_text = __( 'Current mode: Local.', 'chatbot-chatgpt' );
        $selected_mode = 'local';
    } elseif ( 'error' === $status ) {
        $status_text = __( 'Current mode: Local — Configuration Error.', 'chatbot-chatgpt' );
        $selected_mode = 'local';
    } else {
        $status_text = __( 'Current mode: Bridge.', 'chatbot-chatgpt' );
        $selected_mode = 'bridge';
    }

    $html = '<tr class="kchat-pmpt-mode-row"><td colspan="15" style="text-align:left;">';
    $html .= '<p><strong>' . esc_html__( 'Hosted prompt mode', 'chatbot-chatgpt' ) . '</strong> ';
    $html .= esc_html( $status_text ) . '</p>';
    if ( 'error' === $status ) {
        $html .= '<p><strong>' . esc_html( $decision['error'] ) . '</strong></p>';
        $html .= '<p>' . esc_html__( 'Repair the configuration, or choose Bridge and click Save prompt mode. Opening this page does not change the saved mode.', 'chatbot-chatgpt' ) . '</p>';
    }
    $html .= '<p>' . esc_html__( 'Bridge sends prompt.id to the hosted prompt. OpenAI retires hosted prompts on 30 November 2026. Hosted tools, the hosted model, and other hosted prompt settings are not copied automatically. Additional Instructions do not by themselves prove that the copy is complete.', 'chatbot-chatgpt' ) . '</p>';
    $html .= '<p>' . esc_html(
        sprintf(
            /* translators: 1: model ID, 2: parsed vector store IDs or none */
            __( 'Local mode would send model %1$s and parsed WordPress vector stores: %2$s. It would not send the hosted prompt ID.', 'chatbot-chatgpt' ),
            '' !== $preview['model'] ? $preview['model'] : __( '(none resolved)', 'chatbot-chatgpt' ),
            $store_text
        )
    ) . '</p>';
    if ( '' !== trim( $preview['raw'] ) && empty( $preview['stores'] ) ) {
        $html .= '<p>' . esc_html__( 'The vector store value was not recognized, so Local mode would not attach a file search store.', 'chatbot-chatgpt' ) . '</p>';
    }
    $html .= '<p>' . esc_html__( 'Changing mode reuses this assistant’s existing conversation. Earlier hosted-prompt turns remain in that conversation.', 'chatbot-chatgpt' ) . '</p>';
    $html .= '<label>' . esc_html__( 'Mode', 'chatbot-chatgpt' ) . ' <select name="pmpt_response_mode_' . esc_attr( (string) $row_id ) . '">';
    $html .= '<option value="bridge"' . selected( $selected_mode, 'bridge', false ) . '>' . esc_html__( 'Bridge', 'chatbot-chatgpt' ) . '</option>';
    $html .= '<option value="local"' . selected( $selected_mode, 'local', false ) . '>' . esc_html__( 'Local', 'chatbot-chatgpt' ) . '</option>';
    $html .= '</select></label> ';
    $html .= '<label><input type="checkbox" name="pmpt_mode_confirmed_' . esc_attr( (string) $row_id ) . '" value="1"> ';
    $html .= esc_html__( 'I confirm the hosted prompt instructions have been copied into Additional Instructions for this row.', 'chatbot-chatgpt' ) . '</label> ';
    $html .= '<button type="button" class="button button-secondary button-small" onclick="saveChatgptPromptMode(' . esc_attr( (string) $row_id ) . ')">' . esc_html__( 'Save prompt mode', 'chatbot-chatgpt' ) . '</button>';
    $html .= '</td></tr>';
    return $html;
}
