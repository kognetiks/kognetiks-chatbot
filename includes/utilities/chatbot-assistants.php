<?php
/**
 * Kognetiks Chatbot - Chatbot Assistants - Ver 2.0.4
 *
 * This file contains the code for table actions for managing assistants
 * to display the chatbot conversation on a page on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Create the table for the chatbot assistants
function create_chatbot_chatgpt_assistants_table() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';
    
    $charset_collate = $wpdb->get_charset_collate();

    // Fallback cascade for invalid or unsupported character sets
    if (empty($charset_collate) || strpos($charset_collate, 'utf8mb4') === false) {
        if (strpos($charset_collate, 'utf8') === false) {
            // Fallback to utf8 if utf8mb4 is not supported
            $charset_collate = "CHARACTER SET utf8 COLLATE utf8_general_ci";
        }
    }

    $sql = chatbot_chatgpt_assistants_table_schema_sql( $table_name, $charset_collate );


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute SQL query and create the table
    dbDelta($sql);

    // Check for errors after dbDelta
    if ($wpdb->last_error) {
        back_trace('ERROR', 'Failed to insert row into table: ' . $table_name);
        back_trace('ERROR', 'SQL: ' . $sql);
        back_trace('ERROR', 'Error details: ' . $wpdb->last_error);
        return false;  // Table creation failed
    }

    // Call the upgrade function after creating the table
    upgrade_chatbot_chatgpt_assistants_table();
    chatbot_chatgpt_ensure_assistants_vector_store_column();
    chatbot_chatgpt_ensure_assistants_model_column();

    // Keep the chatbot_chatgpt_number_of_shortcodes option updated - Ver 2.0.6
    // REMOVED - Ver 2.2.7
    // update_chatbot_chatgpt_number_of_shortcodes();

}
// REMVOED - Ver 2.2.7
// register_activation_hook(__FILE__, 'create_chatbot_chatgpt_assistants_table');

/**
 * CREATE TABLE statement for the assistants table.
 *
 * @param string $table_name Prefixed table name.
 * @param string $charset_collate Charset clause from $wpdb->get_charset_collate().
 * @return string
 */
function chatbot_chatgpt_assistants_table_schema_sql( $table_name, $charset_collate ) {

    return "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT,
        assistant_id VARCHAR(255) NOT NULL,
        vector_store_id TEXT NOT NULL DEFAULT '',
        common_name VARCHAR(255) NOT NULL,
        style ENUM('embedded', 'floating') NOT NULL,
        audience ENUM('all', 'visitors', 'logged-in') NOT NULL,
        voice ENUM('alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer', 'none') NOT NULL,
        allow_file_uploads ENUM('Yes', 'No') NOT NULL,
        allow_transcript_downloads ENUM('Yes', 'No') NOT NULL,
        show_assistant_name ENUM('Yes', 'No') NOT NULL,
        initial_greeting TEXT NOT NULL,
        subsequent_greeting TEXT NOT NULL,
        placeholder_prompt TEXT NOT NULL,
        additional_instructions TEXT NOT NULL,
        model VARCHAR(255) NULL DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

}

// Drop the table for the chatbot assistants
function drop_chatbot_chatgpt_assistants_table() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';
    
    $sql = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);

}

// Retrieve a row from the chatbot assistants table using the Common Name
function get_chatbot_chatgpt_assistant_by_common_name($common_name) {

    global $wpdb;
    
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';


    $assistant_details = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE common_name = %s",
            $common_name
        ),
        ARRAY_A
    );

    return $assistant_details;

}

// Retrieve a row from the chatbot assistants table using the id - Ver 2.0.6
function get_chatbot_chatgpt_assistant_by_key($id) {

    global $wpdb;

    $assistant_details = array();
    
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

 
    $assistant_details = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %s",
            $id
        ),
        ARRAY_A
    );

    // Before returning the $assistant_details, rename certain keys for compatibility with the chatbot shortcode
    if ($assistant_details) {
        $assistant_details['chatbot_chatgpt_assistant_id'] = $assistant_details['assistant_id'];
        $assistant_details['common_name'] = $assistant_details['common_name'];
        $assistant_details['chatbot_chatgpt_display_style'] = $assistant_details['style'];
        $assistant_details['chatbot_chatgpt_audience_choice'] = $assistant_details['audience'];
        $assistant_details['chatbot_chatgpt_voice_option'] = $assistant_details['voice'];
        $assistant_details['chatbot_chatgpt_allow_file_uploads'] = $assistant_details['allow_file_uploads'];
        $assistant_details['chatbot_chatgpt_allow_download_transcript'] = $assistant_details['allow_transcript_downloads'];
    }

    // If the assistant is not found, return an empty array
    if (!$assistant_details) {
        return array();
    }

    return $assistant_details;

}

/**
 * Retrieve a row from the chatbot assistants table using the remote ID (asst_ / pmpt_).
 *
 * @param string $assistant_id Remote assistant or prompt ID.
 * @return array Associative row, or empty array when not found.
 */
function get_chatbot_chatgpt_assistant_by_assistant_id( $assistant_id ) {

    global $wpdb;

    $assistant_id = sanitize_text_field( (string) $assistant_id );
    if ( $assistant_id === '' ) {
        return array();
    }

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    $assistant_details = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE assistant_id = %s LIMIT 1",
            $assistant_id
        ),
        ARRAY_A
    );

    if ( empty( $assistant_details ) || ! is_array( $assistant_details ) ) {
        return array();
    }

    return $assistant_details;

}

/**
 * True when a SHOW COLUMNS type is already a TEXT family column.
 *
 * @param string $type Column type from SHOW COLUMNS.
 * @return bool
 */
function chatbot_chatgpt_vector_store_column_is_text( $type ) {

    $type = strtolower( trim( (string) $type ) );
    return 1 === preg_match( '/^(tinytext|text|mediumtext|longtext)\b/', $type );

}

/**
 * Ensure vector_store_id exists and can hold a list of IDs.
 *
 * A missing column is added as TEXT. VARCHAR(255) is widened to TEXT once.
 * A column that is already TEXT is left alone, including its stored values.
 * The table name must be this site's assistants table or a same-prefix test table.
 *
 * @param string $table_name Optional prefixed assistants table. Defaults to this site's table.
 * @return void
 */
function chatbot_chatgpt_ensure_assistants_vector_store_column( $table_name = '' ) {

    global $wpdb;

    $site_table = $wpdb->prefix . 'chatbot_chatgpt_assistants';
    if ( ! is_string( $table_name ) || ! preg_match( '/^' . preg_quote( $site_table, '/' ) . '(?:_[A-Za-z0-9]+)?$/', $table_name ) ) {
        $table_name = $site_table;
    }
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
        return;
    }

    $column = $wpdb->get_row( "SHOW COLUMNS FROM `{$table_name}` WHERE Field = 'vector_store_id'", ARRAY_A );
    if ( is_array( $column ) && isset( $column['Type'] ) && chatbot_chatgpt_vector_store_column_is_text( $column['Type'] ) ) {
        return;
    }

    if ( ! is_array( $column ) ) {
        $wpdb->query( "ALTER TABLE `{$table_name}` ADD `vector_store_id` TEXT NOT NULL DEFAULT ''" );
        return;
    }

    $type = strtolower( (string) $column['Type'] );
    if ( 'varchar(255)' !== $type ) {
        return;
    }

    $wpdb->query( "ALTER TABLE `{$table_name}` MODIFY `vector_store_id` TEXT NOT NULL DEFAULT ''" );

}
add_action( 'admin_init', 'chatbot_chatgpt_ensure_assistants_vector_store_column' );

/**
 * Ensure the assistants table has a nullable model column (existing installs).
 *
 * Runs once per site because the table name uses $wpdb->prefix. A second call
 * sees the column and does not issue ALTER TABLE. Existing rows stay NULL.
 *
 * @param string $table_name Optional prefixed assistants table. Defaults to this site's table.
 * @return void
 */
function chatbot_chatgpt_ensure_assistants_model_column( $table_name = '' ) {

    global $wpdb;

    $site_table = $wpdb->prefix . 'chatbot_chatgpt_assistants';
    if ( ! is_string( $table_name ) || ! preg_match( '/^' . preg_quote( $site_table, '/' ) . '(?:_[A-Za-z0-9]+)?$/', $table_name ) ) {
        $table_name = $site_table;
    }
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
        return;
    }

    $columns = $wpdb->get_col( "SHOW COLUMNS FROM `{$table_name}`" );
    if ( is_array( $columns ) && in_array( 'model', $columns, true ) ) {
        return;
    }

    $wpdb->query( "ALTER TABLE `{$table_name}` ADD `model` VARCHAR(255) NULL DEFAULT NULL" );

}
add_action( 'admin_init', 'chatbot_chatgpt_ensure_assistants_model_column' );

/**
 * Normalize an assistants-table model identifier.
 *
 * An empty value is an explicit clear. A non-empty value that is not an
 * identifier is rejected so the caller can leave the stored value unchanged.
 * This is not a catalog of models that currently exist.
 *
 * @param mixed $raw Submitted model value.
 * @return string|null|false String to store, null to clear, false to ignore.
 */
function chatbot_chatgpt_normalize_assistant_model( $raw ) {

    if ( ! is_scalar( $raw ) ) {
        return false;
    }
    $model = sanitize_text_field( (string) wp_unslash( $raw ) );
    $model = trim( $model );
    if ( '' === $model ) {
        return null;
    }
    if ( strlen( $model ) > 255 || ! preg_match( '/^[A-Za-z0-9][A-Za-z0-9._:\/-]{0,254}$/', $model ) ) {
        return false;
    }

    return $model;

}

/**
 * Visible reason a submitted model was rejected.
 *
 * @param string $reason invalid, unrelated, or unlisted.
 * @return string
 */
function chatbot_chatgpt_assistant_model_error_message( $reason ) {

    if ( 'unrelated' === $reason ) {
        return 'Image, audio, speech, embedding, and moderation models cannot be saved for an assistant. The assistant was not changed.';
    }
    if ( 'unlisted' === $reason ) {
        return 'That model is not in the current OpenAI model list. The assistant was not changed.';
    }

    return 'The model identifier is not valid. The assistant was not changed.';

}

/**
 * Stop an assistant save and show the model error.
 *
 * @param string $message Administrator-facing message. It must not include credentials.
 * @return void
 */
function chatbot_chatgpt_reject_assistant_model( $message ) {

    if ( wp_doing_ajax() ) {
        wp_send_json_error( $message );
    }
    wp_die( $message );

}

/**
 * Decide whether a request writes the model column.
 *
 * A missing model field is not a request to clear the stored value.
 * An empty value stores NULL. A verified list is consulted only when one is already cached,
 * so saving does not call the models endpoint.
 *
 * @param array       $post          Request fields.
 * @param string|null $stored_model  Model currently stored for this row.
 * @return array{action:string,value:?string,message:string} action is omit, store, or error.
 */
function chatbot_chatgpt_assistant_model_write( $post, $stored_model = null ) {

    $stored = ( is_string( $stored_model ) && '' !== trim( $stored_model ) ) ? trim( $stored_model ) : null;

    if ( ! is_array( $post ) || ! array_key_exists( 'model', $post ) ) {
        return array(
            'action'  => 'omit',
            'value'   => null,
            'message' => '',
        );
    }

    $normalized = chatbot_chatgpt_normalize_assistant_model( $post['model'] );
    if ( false === $normalized ) {
        return array(
            'action'  => 'error',
            'value'   => null,
            'message' => chatbot_chatgpt_assistant_model_error_message( 'invalid' ),
        );
    }

    if ( null === $normalized ) {
        return array(
            'action'  => 'store',
            'value'   => null,
            'message' => '',
        );
    }

    if ( null !== $stored && $stored === $normalized ) {
        return array(
            'action'  => 'store',
            'value'   => $normalized,
            'message' => '',
        );
    }

    if ( chatbot_chatgpt_openai_assistant_model_is_unrelated( $normalized ) ) {
        return array(
            'action'  => 'error',
            'value'   => null,
            'message' => chatbot_chatgpt_assistant_model_error_message( 'unrelated' ),
        );
    }

    $listed = function_exists( 'chatbot_chatgpt_openai_cached_assistant_model_ids' ) ? chatbot_chatgpt_openai_cached_assistant_model_ids() : null;
    if ( is_array( $listed ) && ! in_array( $normalized, $listed, true ) ) {
        return array(
            'action'  => 'error',
            'value'   => null,
            'message' => chatbot_chatgpt_assistant_model_error_message( 'unlisted' ),
        );
    }

    return array(
        'action'  => 'store',
        'value'   => $normalized,
        'message' => '',
    );

}

/**
 * Split a vector-store field into accepted IDs and rejected tokens.
 *
 * Accepted IDs match vs_ followed by letters or digits. The first occurrence
 * of each accepted ID is kept. Rejected tokens are reported and are not
 * rewritten into IDs. This does not contact OpenAI.
 *
 * @param string $raw Stored or submitted field.
 * @return array{valid:string[],invalid:string[]}
 */
function chatbot_chatgpt_classify_vector_store_input( $raw ) {

    $valid = array();
    $invalid = array();
    $parts = preg_split( '/[\s,]+/', (string) $raw );
    if ( ! is_array( $parts ) ) {
        return array(
            'valid'   => array(),
            'invalid' => array(),
        );
    }

    foreach ( $parts as $part ) {
        $part = trim( $part );
        if ( '' === $part ) {
            continue;
        }
        if ( preg_match( '/^vs_[A-Za-z0-9]+$/', $part ) ) {
            $valid[] = $part;
        } else {
            $invalid[] = $part;
        }
    }

    return array(
        'valid'   => array_values( array_unique( $valid ) ),
        'invalid' => array_values( array_unique( $invalid ) ),
    );

}

/**
 * Parse vs_ IDs from a table field (comma or space separated).
 *
 * Callers that only need the accepted IDs keep this array. Rejected tokens
 * are available from chatbot_chatgpt_classify_vector_store_input().
 *
 * @param string $raw Stored or submitted field.
 * @return string[]
 */
function chatbot_chatgpt_parse_vector_store_ids( $raw ) {

    $classified = chatbot_chatgpt_classify_vector_store_input( $raw );
    return $classified['valid'];

}

/**
 * Administrator-facing reason a vector-store list was rejected.
 *
 * @param string[] $invalid Rejected tokens.
 * @return string
 */
function chatbot_chatgpt_vector_store_error_message( $invalid ) {

    $tokens = array();
    foreach ( $invalid as $token ) {
        if ( is_string( $token ) && '' !== $token ) {
            $tokens[] = $token;
        }
    }
    $list = implode( ', ', $tokens );

    return sprintf(
        /* translators: %s: comma-separated tokens that are not vs_ IDs */
        __( 'These vector store entries are not valid vs_ IDs: %s. The assistant was not changed. Use IDs such as vs_abc123, separated by commas. This check does not confirm that a store exists.', 'chatbot-chatgpt' ),
        $list
    );

}

/**
 * Decide whether a submitted vector-store list can be stored.
 *
 * An empty value stores no WordPress vector stores. Any malformed token
 * rejects the save. The stored text is not rewritten when every token is valid.
 *
 * @param mixed $raw Submitted field.
 * @return array{action:string,value:string,invalid:string[],message:string}
 */
function chatbot_chatgpt_vector_store_write( $raw ) {

    if ( ! is_scalar( $raw ) ) {
        return array(
            'action'  => 'error',
            'value'   => '',
            'invalid' => array(),
            'message' => __( 'The vector store list is not valid. The assistant was not changed.', 'chatbot-chatgpt' ),
        );
    }

    $value = sanitize_text_field( (string) wp_unslash( $raw ) );
    $value = trim( $value );
    if ( strlen( $value ) > 65535 ) {
        return array(
            'action'  => 'error',
            'value'   => '',
            'invalid' => array(),
            'message' => __( 'The vector store list is too long. The assistant was not changed.', 'chatbot-chatgpt' ),
        );
    }

    $classified = chatbot_chatgpt_classify_vector_store_input( $value );
    if ( ! empty( $classified['invalid'] ) ) {
        return array(
            'action'  => 'error',
            'value'   => '',
            'invalid' => $classified['invalid'],
            'message' => chatbot_chatgpt_vector_store_error_message( $classified['invalid'] ),
        );
    }

    return array(
        'action'  => 'store',
        'value'   => $value,
        'invalid' => array(),
        'message' => '',
    );

}

// Keep the chatbot_chatgpt_number_of_shortcodes option updated - Ver 2.0.6
function update_chatbot_chatgpt_number_of_shortcodes() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    // Check if the table exists using a prepared statement
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) == $table_name;

    if ($table_exists) {
        // The table exists, proceed with retrieving the maximum ID as the count of shortcodes
        $number_of_shortcodes = $wpdb->get_var("SELECT MAX(id) FROM $table_name");

        // If the query fails for any reason, set $number_of_shortcodes to 0
        if ($number_of_shortcodes === null || $number_of_shortcodes === false) {
            $number_of_shortcodes = 0;
        }
    } else {
        // The table doesn't exist, set $number_of_shortcodes to 0 directly
        $number_of_shortcodes = 0;
    }  

    update_option('chatbot_chatgpt_number_of_shortcodes', $number_of_shortcodes);
    
}

/**
 * DOM id for one Manage Assistants control.
 *
 * Derived from the existing field name so each row keeps a unique id.
 *
 * @param string $field_name Field name attribute.
 * @return string
 */
function chatbot_chatgpt_assistant_control_id( $field_name ) {

    $safe = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $field_name );
    if ( ! is_string( $safe ) || '' === $safe ) {
        $safe = 'field';
    }
    return 'kchat-field-' . $safe;

}

/**
 * Field name for an existing row or the new-assistant row.
 *
 * Existing rows stay `{field}_{id}`. The new row stays `new_{field}`.
 *
 * @param string $base    Field base name.
 * @param string $row_key Row primary key, or "new".
 * @return string
 */
function chatbot_chatgpt_assistant_field_name( $base, $row_key ) {

    if ( 'new' === $row_key ) {
        return 'new_' . $base;
    }
    return $base . '_' . $row_key;

}

/**
 * Dropdown for one assistant row's optional model.
 *
 * An empty option stores NULL. A saved id missing from the inventory stays selected.
 * The name attribute stays first so existing render checks can find the control.
 *
 * @param string $field_name  Form field name.
 * @param mixed  $saved_model Stored model, or null.
 * @param array  $inventory   Result of chatbot_chatgpt_openai_assistant_model_inventory().
 * @return void
 */
function chatbot_chatgpt_render_assistant_model_select( $field_name, $saved_model, $inventory ) {

    $saved = ( is_string( $saved_model ) ) ? $saved_model : '';
    $ids   = ( isset( $inventory['ids'] ) && is_array( $inventory['ids'] ) ) ? $inventory['ids'] : array();
    $live  = ! empty( $inventory['live'] );
    $known = ( '' !== $saved && in_array( $saved, $ids, true ) );

    echo '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( chatbot_chatgpt_assistant_control_id( $field_name ) ) . '">';
    $global_model = function_exists( 'chatbot_chatgpt_resolve_openai_chat_model' )
        ? chatbot_chatgpt_resolve_openai_chat_model()
        : '';
    $global_label = sprintf(
        /* translators: %s: current ChatGPT Model Default identifier */
        __( 'Use ChatGPT Model Default (currently: %s)', 'chatbot-chatgpt' ),
        $global_model
    );
    echo '<option value=""' . selected( $saved, '', false ) . '>' . esc_html( $global_label ) . '</option>';
    if ( '' !== $saved && ! $known ) {
        $label = $live
            ? sprintf(
                /* translators: %s: saved model identifier */
                __( '%s (saved model, not in the current list)', 'chatbot-chatgpt' ),
                $saved
            )
            : sprintf(
                /* translators: %s: saved model identifier */
                __( '%s (saved model, not verified)', 'chatbot-chatgpt' ),
                $saved
            );
        echo '<option value="' . esc_attr( $saved ) . '" selected="selected">' . esc_html( $label ) . '</option>';
    }
    foreach ( $ids as $id ) {
        if ( ! is_string( $id ) || '' === $id ) {
            continue;
        }
        echo '<option value="' . esc_attr( $id ) . '"' . selected( $saved, $id, false ) . '>' . esc_html( $id ) . '</option>';
    }
    echo '</select>';
    if ( '' !== $saved && ! $known ) {
        if ( $live ) {
            echo '<p class="description">' . esc_html__( 'This saved model is not in the current OpenAI model list. It stays selected until you choose a different model.', 'chatbot-chatgpt' ) . '</p>';
        } else {
            echo '<p class="description">' . esc_html__( 'This saved model could not be verified because the OpenAI model list is unavailable. It stays selected until you choose Use ChatGPT Model Default.', 'chatbot-chatgpt' ) . '</p>';
        }
    }

}

/**
 * One labeled select in an assistant Details section.
 *
 * @param string $field_name Field name.
 * @param string $label      Visible label.
 * @param array  $options    Value => label.
 * @param string $selected   Stored value.
 * @return void
 */
function chatbot_chatgpt_render_assistant_select_field( $field_name, $label, $options, $selected ) {

    $control_id = chatbot_chatgpt_assistant_control_id( $field_name );
    echo '<div class="kchat-assistant-field">';
    echo '<label for="' . esc_attr( $control_id ) . '">' . esc_html( $label ) . '</label>';
    echo '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $control_id ) . '">';
    foreach ( $options as $value => $text ) {
        echo '<option value="' . esc_attr( (string) $value ) . '"' . selected( (string) $selected, (string) $value, false ) . '>' . esc_html( (string) $text ) . '</option>';
    }
    echo '</select>';
    echo '</div>';

}

/**
 * One labeled textarea in an assistant Details section.
 *
 * @param string $field_name  Field name.
 * @param string $label       Visible label.
 * @param string $value       Stored value.
 * @param string $placeholder Placeholder text.
 * @param bool   $wide        Span the details grid.
 * @param string $class_name  Extra textarea class.
 * @return void
 */
function chatbot_chatgpt_render_assistant_textarea_field( $field_name, $label, $value, $placeholder, $wide, $class_name ) {

    $control_id = chatbot_chatgpt_assistant_control_id( $field_name );
    $classes    = 'kchat-assistant-field';
    if ( $wide ) {
        $classes .= ' kchat-assistant-field-wide';
    }
    echo '<div class="' . esc_attr( $classes ) . '">';
    echo '<label for="' . esc_attr( $control_id ) . '">' . esc_html( $label ) . '</label>';
    echo '<textarea name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $control_id ) . '"';
    if ( '' !== $class_name ) {
        echo ' class="' . esc_attr( $class_name ) . '"';
    }
    if ( '' !== $placeholder ) {
        echo ' placeholder="' . esc_attr( $placeholder ) . '"';
    }
    echo '>' . esc_textarea( (string) $value ) . '</textarea>';
    echo '</div>';

}

/**
 * Details toggle for one overview row.
 *
 * The control is a button so it does not submit the settings form.
 *
 * @param string $row_key Row primary key, or "new".
 * @param string $label   Accessible name.
 * @return void
 */
function chatbot_chatgpt_render_assistant_details_toggle( $row_key, $label ) {

    $row_key = (string) $row_key;
    $js_arg  = ( 'new' === $row_key ) ? "'new'" : (string) absint( $row_key );
    echo '<button type="button" class="button button-secondary button-small kchat-assistant-details-toggle"';
    echo ' id="kchat-assistant-details-toggle-' . esc_attr( $row_key ) . '"';
    echo ' aria-expanded="false" aria-controls="kchat-assistant-details-' . esc_attr( $row_key ) . '"';
    echo ' aria-label="' . esc_attr( $label ) . '"';
    echo ' onclick="toggleChatgptAssistantDetails(' . $js_arg . ', event); return false;">';
    echo esc_html__( 'Details', 'chatbot-chatgpt' );
    echo '</button>';

}

/**
 * Editable configuration for one assistant, inside its Details row.
 *
 * @param string      $row_key         Row primary key, or "new".
 * @param object|null $assistant       Database row. Null for the new row.
 * @param array       $model_inventory Model picker inventory.
 * @return void
 */
function chatbot_chatgpt_render_assistant_configuration( $row_key, $assistant, $model_inventory ) {

    $is_new = ( 'new' === (string) $row_key );
    $style = ( ! $is_new && isset( $assistant->style ) ) ? (string) $assistant->style : '';
    $audience = ( ! $is_new && isset( $assistant->audience ) ) ? (string) $assistant->audience : '';
    $voice = ( ! $is_new && isset( $assistant->voice ) ) ? (string) $assistant->voice : '';
    $uploads = ( ! $is_new && isset( $assistant->allow_file_uploads ) ) ? (string) $assistant->allow_file_uploads : '';
    $transcripts = ( ! $is_new && isset( $assistant->allow_transcript_downloads ) ) ? (string) $assistant->allow_transcript_downloads : '';
    $show_name = ( ! $is_new && isset( $assistant->show_assistant_name ) ) ? (string) $assistant->show_assistant_name : '';
    $initial = ( ! $is_new && isset( $assistant->initial_greeting ) ) ? (string) $assistant->initial_greeting : '';
    $subsequent = ( ! $is_new && isset( $assistant->subsequent_greeting ) ) ? (string) $assistant->subsequent_greeting : '';
    $placeholder_prompt = ( ! $is_new && isset( $assistant->placeholder_prompt ) ) ? (string) $assistant->placeholder_prompt : '';
    $instructions = ( ! $is_new && isset( $assistant->additional_instructions ) ) ? (string) $assistant->additional_instructions : '';
    $vector = ( ! $is_new && isset( $assistant->vector_store_id ) ) ? (string) $assistant->vector_store_id : '';
    $model = ( ! $is_new && isset( $assistant->model ) ) ? $assistant->model : '';

    $style_options = $is_new
        ? array(
            'Embedded' => 'Embedded',
            'Floating' => 'Floating',
        )
        : array(
            'embedded' => 'Embedded',
            'floating' => 'Floating',
        );
    $audience_options = $is_new
        ? array(
            'All'       => 'All',
            'Visitors'  => 'Visitors',
            'Logged-in' => 'Logged-in',
        )
        : array(
            'all'       => 'All',
            'visitors'  => 'Visitors',
            'logged-in' => 'Logged-in',
        );
    $voice_options = array(
        'alloy'   => 'Alloy',
        'echo'    => 'Echo',
        'fable'   => 'Fable',
        'onyx'    => 'Onyx',
        'nova'    => 'Nova',
        'shimmer' => 'Shimmer',
        'none'    => 'None',
    );
    $yes_no = array(
        'Yes' => 'Yes',
        'No'  => 'No',
    );

    $heading_id = 'kchat-assistant-details-heading-' . $row_key;
    $region_id  = 'kchat-assistant-details-' . $row_key;
    if ( $is_new ) {
        $heading = __( 'Details for a new assistant', 'chatbot-chatgpt' );
    } else {
        $common_name = ( isset( $assistant->common_name ) && '' !== (string) $assistant->common_name )
            ? (string) $assistant->common_name
            : __( '(no common name)', 'chatbot-chatgpt' );
        $heading = sprintf(
            /* translators: 1: shortcode such as [chatbot-1], 2: common name */
            __( 'Details for %1$s — %2$s', 'chatbot-chatgpt' ),
            '[chatbot-' . $row_key . ']',
            $common_name
        );
    }

    echo '<div id="' . esc_attr( $region_id ) . '" class="kchat-assistant-details" role="region" aria-labelledby="' . esc_attr( $heading_id ) . '">';
    echo '<h3 id="' . esc_attr( $heading_id ) . '">' . esc_html( $heading ) . '</h3>';
    if ( ! $is_new && function_exists( 'chatbot_chatgpt_render_pmpt_row_mode_row' ) ) {
        echo chatbot_chatgpt_render_pmpt_row_mode_row( $assistant );
    }
    echo '<div id="kchat-assistant-feedback-' . esc_attr( (string) $row_key ) . '" class="notice notice-error inline kchat-assistant-feedback" hidden><p></p></div>';
    echo '<p class="description">' . esc_html__( 'Assistant ID and Common Name stay in the overview row. Closing Details keeps unsaved values in this section.', 'chatbot-chatgpt' ) . '</p>';

    echo '<h4>' . esc_html__( 'Appearance', 'chatbot-chatgpt' ) . '</h4>';
    echo '<div class="kchat-assistant-fields">';
    chatbot_chatgpt_render_assistant_select_field( chatbot_chatgpt_assistant_field_name( 'style', $row_key ), __( 'Style', 'chatbot-chatgpt' ), $style_options, $style );
    chatbot_chatgpt_render_assistant_select_field( chatbot_chatgpt_assistant_field_name( 'audience', $row_key ), __( 'Audience', 'chatbot-chatgpt' ), $audience_options, $audience );
    chatbot_chatgpt_render_assistant_select_field( chatbot_chatgpt_assistant_field_name( 'voice', $row_key ), __( 'Voice', 'chatbot-chatgpt' ), $voice_options, $voice );
    chatbot_chatgpt_render_assistant_select_field( chatbot_chatgpt_assistant_field_name( 'allow_file_uploads', $row_key ), __( 'Allow File Uploads', 'chatbot-chatgpt' ), $yes_no, $uploads );
    chatbot_chatgpt_render_assistant_select_field( chatbot_chatgpt_assistant_field_name( 'allow_transcript_downloads', $row_key ), __( 'Allow Transcript Downloads', 'chatbot-chatgpt' ), $yes_no, $transcripts );
    chatbot_chatgpt_render_assistant_select_field( chatbot_chatgpt_assistant_field_name( 'show_assistant_name', $row_key ), __( 'Show Assistant Name', 'chatbot-chatgpt' ), $yes_no, $show_name );
    $model_name = chatbot_chatgpt_assistant_field_name( 'model', $row_key );
    echo '<div class="kchat-assistant-field">';
    echo '<label for="' . esc_attr( chatbot_chatgpt_assistant_control_id( $model_name ) ) . '">' . esc_html__( 'Model', 'chatbot-chatgpt' ) . '</label>';
    chatbot_chatgpt_render_assistant_model_select( $model_name, $model, $model_inventory );
    echo '</div>';
    echo '</div>';

    echo '<h4>' . esc_html__( 'Messages', 'chatbot-chatgpt' ) . '</h4>';
    echo '<div class="kchat-assistant-fields">';
    chatbot_chatgpt_render_assistant_textarea_field( chatbot_chatgpt_assistant_field_name( 'initial_greeting', $row_key ), __( 'Initial Greeting', 'chatbot-chatgpt' ), $initial, $is_new ? 'Hello! How can I help you today?' : '', true, '' );
    chatbot_chatgpt_render_assistant_textarea_field( chatbot_chatgpt_assistant_field_name( 'subsequent_greeting', $row_key ), __( 'Subsequent Greeting', 'chatbot-chatgpt' ), $subsequent, $is_new ? 'Hello again! How can I help you?' : '', true, '' );
    chatbot_chatgpt_render_assistant_textarea_field( chatbot_chatgpt_assistant_field_name( 'placeholder_prompt', $row_key ), __( 'Placeholder Prompt', 'chatbot-chatgpt' ), $placeholder_prompt, $is_new ? 'Enter your question ...' : '', true, '' );
    chatbot_chatgpt_render_assistant_textarea_field( chatbot_chatgpt_assistant_field_name( 'additional_instructions', $row_key ), __( 'Additional Instructions', 'chatbot-chatgpt' ), $instructions, $is_new ? 'Added instructions to assistant if needed ....' : '', true, 'kchat-additional-instructions' );
    echo '</div>';

    echo '<h4>' . esc_html__( 'Knowledge', 'chatbot-chatgpt' ) . '</h4>';
    echo '<div class="kchat-assistant-fields">';
    $vector_name = chatbot_chatgpt_assistant_field_name( 'vector_store_id', $row_key );
    echo '<div class="kchat-assistant-field kchat-assistant-field-wide">';
    echo '<label for="' . esc_attr( chatbot_chatgpt_assistant_control_id( $vector_name ) ) . '">' . esc_html__( 'Vector Store ID', 'chatbot-chatgpt' ) . '</label>';
    echo '<textarea name="' . esc_attr( $vector_name ) . '" id="' . esc_attr( chatbot_chatgpt_assistant_control_id( $vector_name ) ) . '" class="kchat-vector-store" placeholder="vs_aaa, vs_bbb">' . esc_textarea( $vector ) . '</textarea>';
    if ( ! $is_new && function_exists( 'chatbot_chatgpt_classify_vector_store_input' ) ) {
        $stored_stores = chatbot_chatgpt_classify_vector_store_input( $vector );
        if ( ! empty( $stored_stores['invalid'] ) && function_exists( 'chatbot_chatgpt_vector_store_error_message' ) ) {
            echo '<p class="description">' . esc_html( chatbot_chatgpt_vector_store_error_message( $stored_stores['invalid'] ) ) . '</p>';
        }
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';

}

/**
 * Overview row and collapsed Details row for one saved assistant.
 *
 * @param object $assistant       Database row.
 * @param array  $model_inventory Model picker inventory.
 * @return void
 */
function chatbot_chatgpt_render_manage_assistant_rows( $assistant, $model_inventory ) {

    $row_id    = absint( $assistant->id );
    $row_key   = (string) $row_id;
    $shortcode = '[chatbot-' . $row_key . ']';

    echo '<tr class="kchat-assistant-overview-row" data-assistant-row="' . esc_attr( $row_key ) . '">';
    echo '<td class="kchat-assistant-actions">';
    echo '<button type="button" class="button button-primary button-small" onclick="updateChatgptAssistant(' . $row_id . '); return false;" aria-label="' . esc_attr( sprintf( __( 'Update %s', 'chatbot-chatgpt' ), $shortcode ) ) . '">' . esc_html__( 'Update', 'chatbot-chatgpt' ) . '</button>';
    echo '<button type="button" class="button button-primary button-small" onclick="deleteChatgptAssistant(' . $row_id . '); return false;" aria-label="' . esc_attr( sprintf( __( 'Delete %s', 'chatbot-chatgpt' ), $shortcode ) ) . '">' . esc_html__( 'Delete', 'chatbot-chatgpt' ) . '</button>';
    chatbot_chatgpt_render_assistant_details_toggle( $row_key, sprintf( __( 'Details for %s', 'chatbot-chatgpt' ), $shortcode ) );
    echo '</td>';
    echo '<td class="kchat-col-shortcode"><code>' . esc_html( $shortcode ) . '</code> ';
    echo '<button type="button" class="button button-small" onclick="copyToClipboard(\'' . esc_js( $shortcode ) . '\'); return false;" aria-label="' . esc_attr( sprintf( __( 'Copy shortcode %s', 'chatbot-chatgpt' ), $shortcode ) ) . '">' . esc_html__( 'Copy', 'chatbot-chatgpt' ) . '</button></td>';
    echo '<td class="kchat-col-assistant-id"><input type="text" name="assistant_id_' . $row_id . '" id="' . esc_attr( chatbot_chatgpt_assistant_control_id( 'assistant_id_' . $row_key ) ) . '" value="' . esc_attr( $assistant->assistant_id ) . '" aria-label="' . esc_attr( sprintf( __( 'Assistant ID for %s', 'chatbot-chatgpt' ), $shortcode ) ) . '"></td>';
    echo '<td class="kchat-col-common-name"><input type="text" name="common_name_' . $row_id . '" id="' . esc_attr( chatbot_chatgpt_assistant_control_id( 'common_name_' . $row_key ) ) . '" value="' . esc_attr( $assistant->common_name ) . '" aria-label="' . esc_attr( sprintf( __( 'Common Name for %s', 'chatbot-chatgpt' ), $shortcode ) ) . '"></td>';
    echo '</tr>';

    echo '<tr class="kchat-assistant-details-row" id="kchat-assistant-details-row-' . esc_attr( $row_key ) . '" data-assistant-row="' . esc_attr( $row_key ) . '" hidden>';
    echo '<td colspan="4">';
    chatbot_chatgpt_render_assistant_configuration( $row_key, $assistant, $model_inventory );
    echo '</td>';
    echo '</tr>';

}

/**
 * Overview row and collapsed Details row for adding an assistant.
 *
 * @param array $model_inventory Model picker inventory.
 * @return void
 */
function chatbot_chatgpt_render_new_manage_assistant_rows( $model_inventory ) {

    echo '<tr class="kchat-assistant-overview-row kchat-assistant-new-row" data-assistant-row="new">';
    echo '<td class="kchat-assistant-actions">';
    echo '<button type="button" class="button button-primary button-small" onclick="addNewChatgptAssistant(); return false;">' . esc_html__( 'Add New Assistant', 'chatbot-chatgpt' ) . '</button>';
    chatbot_chatgpt_render_assistant_details_toggle( 'new', __( 'Details for a new assistant', 'chatbot-chatgpt' ) );
    echo '</td>';
    echo '<td class="kchat-col-shortcode">' . esc_html__( 'New', 'chatbot-chatgpt' ) . '</td>';
    echo '<td class="kchat-col-assistant-id"><input type="text" name="new_assistant_id" id="' . esc_attr( chatbot_chatgpt_assistant_control_id( 'new_assistant_id' ) ) . '" placeholder="' . esc_attr__( 'Please provide the GPT Assistant Id.', 'chatbot-chatgpt' ) . '" aria-label="' . esc_attr__( 'Assistant ID for a new assistant', 'chatbot-chatgpt' ) . '"></td>';
    echo '<td class="kchat-col-common-name"><input type="text" name="new_common_name" id="' . esc_attr( chatbot_chatgpt_assistant_control_id( 'new_common_name' ) ) . '" placeholder="' . esc_attr__( 'Common Name', 'chatbot-chatgpt' ) . '" aria-label="' . esc_attr__( 'Common Name for a new assistant', 'chatbot-chatgpt' ) . '"></td>';
    echo '</tr>';
    echo '<tr class="kchat-assistant-details-row" id="kchat-assistant-details-row-new" data-assistant-row="new" hidden>';
    echo '<td colspan="4">';
    chatbot_chatgpt_render_assistant_configuration( 'new', null, $model_inventory );
    echo '</td>';
    echo '</tr>';

}

// Display the chatbot assistants table
function display_chatbot_chatgpt_assistants_table() {

    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error( 'Unauthorized user', 403 );
        wp_die();
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';
    chatbot_chatgpt_ensure_assistants_vector_store_column();
    chatbot_chatgpt_ensure_assistants_model_column();
    $assistants = $wpdb->get_results("SELECT * FROM $table_name");

    $refresh_models = false;
    if ( isset( $_GET['chatbot_refresh_openai_models'] ) ) {
        $refresh_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        if ( wp_verify_nonce( $refresh_nonce, 'chatbot_refresh_openai_models' ) ) {
            $refresh_models = true;
        }
    }
    $model_inventory = function_exists( 'chatbot_chatgpt_openai_assistant_model_inventory' )
        ? chatbot_chatgpt_openai_assistant_model_inventory( $refresh_models )
        : array(
            'live'   => false,
            'ids'    => array(),
            'notice' => 'The OpenAI model list is unavailable. Saved models were not changed.',
        );

    // Update the number of shortcodes - Ver 2.0.6
    update_chatbot_chatgpt_number_of_shortcodes();

    echo '<style>
        .asst-templates-display {
            max-width: 100%;
        }
        table.kchat-assistants-overview {
            table-layout: fixed;
            width: 100%;
            max-width: 100%;
        }
        table.kchat-assistants-overview col.kchat-col-actions { width: 22%; }
        table.kchat-assistants-overview col.kchat-col-shortcode { width: 16%; }
        table.kchat-assistants-overview col.kchat-col-assistant-id { width: 31%; }
        table.kchat-assistants-overview col.kchat-col-common-name { width: 31%; }
        table.kchat-assistants-overview th,
        table.kchat-assistants-overview td {
            vertical-align: top;
            text-align: left;
        }
        table.kchat-assistants-overview .kchat-col-assistant-id,
        table.kchat-assistants-overview .kchat-col-common-name {
            overflow-wrap: anywhere;
        }
        table.kchat-assistants-overview input[type="text"],
        table.kchat-assistants-overview select,
        table.kchat-assistants-overview textarea {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
        table.kchat-assistants-overview .kchat-assistant-actions .button {
            margin: 0 4px 4px 0;
        }
        table.kchat-assistants-overview tr.kchat-assistant-details-row[hidden] {
            display: none !important;
        }
        table.kchat-assistants-overview tr.kchat-assistant-details-row > td {
            background: #f6f7f7;
            text-align: left;
        }
        .kchat-assistant-details h3,
        .kchat-assistant-details h4 {
            margin: 0.6em 0 0.4em;
        }
        .kchat-assistant-fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
            gap: 12px 16px;
            margin-bottom: 8px;
        }
        .kchat-assistant-field-wide {
            grid-column: 1 / -1;
        }
        .kchat-assistant-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .kchat-assistant-field textarea {
            min-height: 5.5em;
        }
        .kchat-assistant-field textarea.kchat-additional-instructions {
            min-height: 12em;
        }
        .kchat-assistant-field textarea.kchat-vector-store {
            min-height: 6em;
            font-family: Consolas, Monaco, monospace;
        }
        .kchat-pmpt-mode-row {
            margin-top: 12px;
            padding: 12px;
            background: #fff;
            border: 1px solid #c3c4c7;
            text-align: left;
        }
        table.kchat-assistants-overview .kchat-pmpt-mode-row select {
            width: auto;
            min-width: 16rem;
            max-width: 100%;
        }
    </style>';

    echo '<div class="wrap asst-templates-display">';
    echo '<h1>Manage Assistants</h1>';
    echo '<p>' . esc_html__( 'Click Update to save one assistant, Delete to remove it, or Details to show or hide that assistant\'s settings. Details does not save changes. Closing Details keeps unsaved values.', 'chatbot-chatgpt' ) . '</p>';
    echo '<p>' . esc_html__( 'Open Details on the new row to set style, greetings, instructions, vector stores, and the model before adding an assistant.', 'chatbot-chatgpt' ) . '</p>';
    echo '<p>' . wp_kses(
        sprintf(
            /* translators: 1: Vector Store ID label, 2: vs_ ID prefix example */
            __( 'Greetings, placeholder, and Additional Instructions are edited in Details. %1$s (%2$s) is there as well. It does not replace Additional Instructions.', 'chatbot-chatgpt' ),
            '<code>' . esc_html__( 'Vector Store ID', 'chatbot-chatgpt' ) . '</code>',
            '<code>vs_…</code>'
        ),
        array( 'code' => array() )
    ) . '</p>';
    echo '<p>' . esc_html__( 'Enter one or more vector store IDs, separated by commas. An empty field stores no WordPress vector stores. An entry that is not a vs_ ID is shown and the assistant is not saved. Saving does not contact OpenAI and does not confirm that a store exists.', 'chatbot-chatgpt' ) . '</p>';
    echo '<p>' . esc_html__( 'Choose Use ChatGPT Model Default to store no model on this row. That choice follows the ChatGPT Model Default setting. A selected model is used for Local and asst_ requests. Bridge mode uses the hosted prompt\'s model and does not send this saved model. Saving a model does not change Local or Bridge mode.', 'chatbot-chatgpt' ) . '</p>';
    echo '<p>' . esc_html__( 'A model in this list was returned by the OpenAI models endpoint. That does not prove it supports the Responses API or file search.', 'chatbot-chatgpt' ) . '</p>';
    if ( ! empty( $model_inventory['notice'] ) ) {
        echo '<p class="description">' . esc_html( $model_inventory['notice'] ) . '</p>';
    }
    $refresh_url = wp_nonce_url(
        add_query_arg(
            array(
                'page'                           => 'chatbot-chatgpt',
                'tab'                            => 'gpt_assistants',
                'chatbot_refresh_openai_models'  => '1',
            ),
            admin_url( 'admin.php' )
        ),
        'chatbot_refresh_openai_models'
    );
    echo '<p><a class="button" href="' . esc_url( $refresh_url ) . '">' . esc_html__( 'Refresh OpenAI model list', 'chatbot-chatgpt' ) . '</a></p>';
    echo '<table class="widefat striped kchat-assistants-overview">';
    echo '<colgroup>';
    echo '<col class="kchat-col-actions">';
    echo '<col class="kchat-col-shortcode">';
    echo '<col class="kchat-col-assistant-id">';
    echo '<col class="kchat-col-common-name">';
    echo '</colgroup>';
    echo '<thead>';
    echo '<tr>';
    echo '<th scope="col">' . esc_html__( 'Actions', 'chatbot-chatgpt' ) . '</th>';
    echo '<th scope="col">' . esc_html__( '[Shortcode]', 'chatbot-chatgpt' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'Assistant ID', 'chatbot-chatgpt' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'Common Name', 'chatbot-chatgpt' ) . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($assistants as $assistant) {
        chatbot_chatgpt_render_manage_assistant_rows( $assistant, $model_inventory );
    }

    chatbot_chatgpt_render_new_manage_assistant_rows( $model_inventory );
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
}

// Scripts for the chatbot assistants table
function chatbot_chatgpt_assistants_scripts() {

    if ( ! current_user_can('manage_options') ) {
        return;
    }

    $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
    $tab  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
    if ( $page !== 'chatbot-chatgpt' || $tab !== 'gpt_assistants' ) {
        return;
    }

    $nonce = wp_create_nonce('chatbot_nonce_action');

        ?>
        <script type="text/javascript">

            var chatbot_nonce = "<?php echo esc_js($nonce); ?>";

            function copyToClipboard(text) {
                var tempInput = document.createElement('input');
                tempInput.style.position = 'absolute';
                tempInput.style.left = '-9999px';
                tempInput.value = text;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                alert('Shortcode copied to clipboard: ' + text);
            }

            function toggleChatgptAssistantDetails(id, event) {
                if (event && event.preventDefault) {
                    event.preventDefault();
                }
                var row = document.getElementById('kchat-assistant-details-row-' + id);
                var toggle = document.getElementById('kchat-assistant-details-toggle-' + id);
                if (!row || !toggle) {
                    return;
                }
                var expanded = toggle.getAttribute('aria-expanded') === 'true';
                if (expanded) {
                    row.hidden = true;
                    toggle.setAttribute('aria-expanded', 'false');
                } else {
                    row.hidden = false;
                    toggle.setAttribute('aria-expanded', 'true');
                }
            }

            function revealChatgptAssistantDetails(id) {
                var row = document.getElementById('kchat-assistant-details-row-' + id);
                var toggle = document.getElementById('kchat-assistant-details-toggle-' + id);
                if (!row || !toggle) {
                    return;
                }
                row.hidden = false;
                toggle.setAttribute('aria-expanded', 'true');
                if (row.scrollIntoView) {
                    row.scrollIntoView({ block: 'nearest' });
                }
            }

            function showChatgptAssistantFeedback(id, message) {
                var box = document.getElementById('kchat-assistant-feedback-' + id);
                if (!box) {
                    return;
                }
                var paragraph = box.querySelector('p');
                if (paragraph) {
                    paragraph.textContent = message;
                }
                box.hidden = false;
            }

            function clearChatgptAssistantFeedback(id) {
                var box = document.getElementById('kchat-assistant-feedback-' + id);
                if (!box) {
                    return;
                }
                var paragraph = box.querySelector('p');
                if (paragraph) {
                    paragraph.textContent = '';
                }
                box.hidden = true;
            }

            function rememberChatgptAssistantDetails(id) {
                try {
                    sessionStorage.setItem('kchatAssistantDetailsOpen', String(id));
                } catch (e) {}
            }

            function chatbotAssistantSaveFinished(response, successMessage, id) {
                if (response && response.success === false) {
                    var message = 'The assistant was not saved.';
                    if (typeof response.data === 'string' && response.data !== '') {
                        message = response.data;
                    }
                    if (id) {
                        revealChatgptAssistantDetails(id);
                        showChatgptAssistantFeedback(id, message);
                    }
                    alert(message);
                    return;
                }
                if (id && id !== 'new') {
                    rememberChatgptAssistantDetails(id);
                }
                alert(successMessage);
                window.programmaticReload = true;
                location.reload();
            }

            // Function to update an assistant's details
            function saveChatgptPromptMode(id) {
                clearChatgptAssistantFeedback(id);
                var modeField = document.getElementsByName('pmpt_response_mode_' + id)[0];
                var confirmField = document.getElementsByName('pmpt_mode_confirmed_' + id)[0];
                var data = {
                    action: 'chatbot_chatgpt_set_pmpt_row_mode',
                    id: id,
                    mode: modeField ? modeField.value : 'bridge',
                    confirmed: confirmField && confirmField.checked ? '1' : '0'
                };

                data._wpnonce = chatbot_nonce;

                jQuery.post(ajaxurl, data, function(response) {
                    if (response && response.success) {
                        rememberChatgptAssistantDetails(id);
                        alert('Prompt mode saved.');
                        window.programmaticReload = true;
                        location.reload();
                        return;
                    }
                    var message = 'Prompt mode was not saved.';
                    if (response && typeof response.data === 'string' && response.data !== '') {
                        message = response.data;
                    }
                    revealChatgptAssistantDetails(id);
                    showChatgptAssistantFeedback(id, message);
                    alert(message);
                });
            }

            function updateChatgptAssistant(id) {
                clearChatgptAssistantFeedback(id);
                var vectorStoreField = document.getElementsByName('vector_store_id_' + id)[0];
                var modelField = document.getElementsByName('model_' + id)[0];
                var data = {
                    action: 'update_assistant',
                    id: id,
                    assistant_id: document.getElementsByName('assistant_id_' + id)[0].value,
                    vector_store_id: vectorStoreField ? vectorStoreField.value : '',
                    common_name: document.getElementsByName('common_name_' + id)[0].value,
                    style: document.getElementsByName('style_' + id)[0].value,
                    audience: document.getElementsByName('audience_' + id)[0].value,
                    voice: document.getElementsByName('voice_' + id)[0].value,
                    allow_file_uploads: document.getElementsByName('allow_file_uploads_' + id)[0].value,
                    allow_transcript_downloads: document.getElementsByName('allow_transcript_downloads_' + id)[0].value,
                    show_assistant_name: document.getElementsByName('show_assistant_name_' + id)[0].value,
                    initial_greeting: document.getElementsByName('initial_greeting_' + id)[0].value,
                    subsequent_greeting: document.getElementsByName('subsequent_greeting_' + id)[0].value,
                    placeholder_prompt: document.getElementsByName('placeholder_prompt_' + id)[0].value,
                    additional_instructions: document.getElementsByName('additional_instructions_' + id)[0].value
                };
                if (modelField) {
                    data.model = modelField.value;
                }

                data._wpnonce = chatbot_nonce;

                jQuery.post(ajaxurl, data).done(function(response) {
                    chatbotAssistantSaveFinished(response, 'Assistant updated successfully!', id);
                }).fail(function() {
                    revealChatgptAssistantDetails(id);
                    showChatgptAssistantFeedback(id, 'The assistant was not saved.');
                    alert('The assistant was not saved.');
                });
            }

            // Function to delete an assistant
            function deleteChatgptAssistant(id) {
                var data = {
                    action: 'delete_assistant',
                    id: id
                };

                data._wpnonce = chatbot_nonce;

                // Send the delete request via AJAX
                jQuery.post(ajaxurl, data, function(response) {
                    alert('Assistant deleted successfully!');
                    window.programmaticReload = true;
                    location.reload();  // Reload the page to reflect the deletion
                });
            }

            // Function to add a new assistant
            function addNewChatgptAssistant() {
                clearChatgptAssistantFeedback('new');
                var newVectorStoreField = document.getElementsByName('new_vector_store_id')[0];
                var newModelField = document.getElementsByName('new_model')[0];
                var data = {
                    action: 'add_new_assistant',
                    assistant_id: document.getElementsByName('new_assistant_id')[0].value,
                    vector_store_id: newVectorStoreField ? newVectorStoreField.value : '',
                    common_name: document.getElementsByName('new_common_name')[0].value,
                    style: document.getElementsByName('new_style')[0].value,
                    audience: document.getElementsByName('new_audience')[0].value,
                    voice: document.getElementsByName('new_voice')[0].value,
                    allow_file_uploads: document.getElementsByName('new_allow_file_uploads')[0].value,
                    allow_transcript_downloads: document.getElementsByName('new_allow_transcript_downloads')[0].value,
                    show_assistant_name: document.getElementsByName('new_show_assistant_name')[0].value,
                    initial_greeting: document.getElementsByName('new_initial_greeting')[0].value,
                    subsequent_greeting: document.getElementsByName('new_subsequent_greeting')[0].value,
                    placeholder_prompt: document.getElementsByName('new_placeholder_prompt')[0].value,
                    additional_instructions: document.getElementsByName('new_additional_instructions')[0].value
                };
                if (newModelField) {
                    data.model = newModelField.value;
                }

                data._wpnonce = chatbot_nonce;

                jQuery.post(ajaxurl, data).done(function(response) {
                    chatbotAssistantSaveFinished(response, 'New assistant added successfully!', 'new');
                }).fail(function() {
                    revealChatgptAssistantDetails('new');
                    showChatgptAssistantFeedback('new', 'The assistant was not saved.');
                    alert('The assistant was not saved.');
                });
            }

            (function () {
                var openId = '';
                try {
                    openId = sessionStorage.getItem('kchatAssistantDetailsOpen') || '';
                    sessionStorage.removeItem('kchatAssistantDetailsOpen');
                } catch (e) {
                    openId = '';
                }
                if (openId) {
                    revealChatgptAssistantDetails(openId);
                }
            })();

        </script>
        <?php

}
add_action('admin_footer', 'chatbot_chatgpt_assistants_scripts');

// Update Assistant AJAX action
function update_assistant() {

    check_ajax_referer('chatbot_nonce_action', '_wpnonce');

    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error( 'Unauthorized user', 403 );
        wp_die();
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    chatbot_chatgpt_ensure_assistants_vector_store_column();
    chatbot_chatgpt_ensure_assistants_model_column();

    $id = intval($_POST['id']);
    $existing_assistant = function_exists( 'get_chatbot_chatgpt_assistant_by_key' ) ? get_chatbot_chatgpt_assistant_by_key( $id ) : array();
    $previous_assistant_id = ( is_array( $existing_assistant ) && isset( $existing_assistant['assistant_id'] ) ) ? (string) $existing_assistant['assistant_id'] : '';
    $assistant_id = sanitize_text_field($_POST['assistant_id']);
    // An omitted field is not a request to clear the stored list. An empty
    // submitted field is an explicit clear. A malformed list rejects the save.
    $vector_store_present = array_key_exists( 'vector_store_id', $_POST );
    if ( $vector_store_present ) {
        $vector_store_write = chatbot_chatgpt_vector_store_write( $_POST['vector_store_id'] );
        if ( 'error' === $vector_store_write['action'] ) {
            chatbot_chatgpt_reject_assistant_model( $vector_store_write['message'] );
        }
        $vector_store_id = $vector_store_write['value'];
    }
    $common_name = sanitize_text_field($_POST['common_name']);
    $style = sanitize_text_field($_POST['style']);
    $audience = sanitize_text_field($_POST['audience']);
    $voice = sanitize_text_field($_POST['voice']);
    $allow_file_uploads = sanitize_text_field($_POST['allow_file_uploads']);
    $allow_transcript_downloads = sanitize_text_field($_POST['allow_transcript_downloads']);
    $show_assistant_name = sanitize_text_field($_POST['show_assistant_name']);
    $initial_greeting = sanitize_textarea_field($_POST['initial_greeting']);
    $subsequent_greeting = sanitize_textarea_field($_POST['subsequent_greeting']);
    $placeholder_prompt = sanitize_textarea_field($_POST['placeholder_prompt']);
    $additional_instructions = sanitize_textarea_field($_POST['additional_instructions']);

    $stored_model = ( is_array( $existing_assistant ) && isset( $existing_assistant['model'] ) ) ? $existing_assistant['model'] : null;
    $model_write = chatbot_chatgpt_assistant_model_write( $_POST, $stored_model );
    if ( 'error' === $model_write['action'] ) {
        chatbot_chatgpt_reject_assistant_model( $model_write['message'] );
    }

    $update_data = array(
        'assistant_id' => $assistant_id,
        'common_name' => $common_name,
        'style' => $style,
        'audience' => $audience,
        'voice' => $voice,
        'allow_file_uploads' => $allow_file_uploads,
        'allow_transcript_downloads' => $allow_transcript_downloads,
        'show_assistant_name' => $show_assistant_name,
        'initial_greeting' => $initial_greeting,
        'subsequent_greeting' => $subsequent_greeting,
        'placeholder_prompt' => $placeholder_prompt,
        'additional_instructions' => $additional_instructions
    );
    if ( $vector_store_present ) {
        $update_data['vector_store_id'] = $vector_store_id;
    }
    if ( 'store' === $model_write['action'] ) {
        $update_data['model'] = $model_write['value'];
    }

    $wpdb->update(
        $table_name,
        $update_data,
        array('id' => $id)
    );

    // Check for errors after update
    if ($wpdb->last_error) {
        back_trace('ERROR', 'Failed to update row in table: ' . $table_name);
        back_trace('ERROR', 'Error details: ' . $wpdb->last_error);
        return false;  // Row update failed
    }

    if ( function_exists( 'chatbot_chatgpt_release_pmpt_row_mode_on_identity_change' ) ) {
        chatbot_chatgpt_release_pmpt_row_mode_on_identity_change( $id, $previous_assistant_id, $assistant_id );
    }

    wp_die();

}
add_action('wp_ajax_update_assistant', 'update_assistant');

// Delete Assistant AJAX action
function delete_assistant() {

    check_ajax_referer('chatbot_nonce_action', '_wpnonce');

    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error( 'Unauthorized user', 403 );
        wp_die();
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    $id = intval($_POST['id']);
    $wpdb->delete($table_name, array('id' => $id));

    // Check for errors after delete
    if ($wpdb->last_error) {
        back_trace('ERROR', 'Failed to delete row from table: ' . $table_name);
        back_trace('ERROR', 'Error details: ' . $wpdb->last_error);
        return false;  // Row deletion failed
    }

    if ( function_exists( 'chatbot_chatgpt_remove_pmpt_row_mode' ) ) {
        chatbot_chatgpt_remove_pmpt_row_mode( $id );
    }

    wp_die();

}
add_action('wp_ajax_delete_assistant', 'delete_assistant');

function add_new_assistant() {

    check_ajax_referer('chatbot_nonce_action', '_wpnonce');

    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error( 'Unauthorized user', 403 );
        wp_die();
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    // Check that the table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        // Table doesn't exist then add the table
        create_chatbot_chatgpt_assistants_table();
    }

    chatbot_chatgpt_ensure_assistants_vector_store_column();
    chatbot_chatgpt_ensure_assistants_model_column();

    $assistant_id = sanitize_text_field($_POST['assistant_id']);
    // A new row has nothing to preserve. An omitted field starts empty. A
    // submitted empty field is also empty. A malformed list rejects the insert.
    $vector_store_present = array_key_exists( 'vector_store_id', $_POST );
    if ( $vector_store_present ) {
        $vector_store_write = chatbot_chatgpt_vector_store_write( $_POST['vector_store_id'] );
        if ( 'error' === $vector_store_write['action'] ) {
            chatbot_chatgpt_reject_assistant_model( $vector_store_write['message'] );
        }
        $vector_store_id = $vector_store_write['value'];
    } else {
        $vector_store_id = '';
    }
    $common_name = sanitize_text_field($_POST['common_name']);
    $style = sanitize_text_field($_POST['style']);
    $audience = sanitize_text_field($_POST['audience']);
    $voice = sanitize_text_field($_POST['voice']);
    $allow_file_uploads = sanitize_text_field($_POST['allow_file_uploads']);
    $allow_transcript_downloads = sanitize_text_field($_POST['allow_transcript_downloads']);
    $show_assistant_name = sanitize_text_field($_POST['show_assistant_name']);
    $initial_greeting = sanitize_textarea_field($_POST['initial_greeting']);
    $subsequent_greeting = sanitize_textarea_field($_POST['subsequent_greeting']);
    $placeholder_prompt = sanitize_textarea_field($_POST['placeholder_prompt']);
    $additional_instructions = sanitize_textarea_field($_POST['additional_instructions']);

    $model_write = chatbot_chatgpt_assistant_model_write( $_POST, null );
    if ( 'error' === $model_write['action'] ) {
        chatbot_chatgpt_reject_assistant_model( $model_write['message'] );
    }

    $insert_data = array(
        'assistant_id' => $assistant_id,
        'vector_store_id' => $vector_store_id,
        'common_name' => $common_name,
        'style' => $style,
        'audience' => $audience,
        'voice' => $voice,
        'allow_file_uploads' => $allow_file_uploads,
        'allow_transcript_downloads' => $allow_transcript_downloads,
        'show_assistant_name' => $show_assistant_name,
        'initial_greeting' => $initial_greeting,
        'subsequent_greeting' => $subsequent_greeting,
        'placeholder_prompt' => $placeholder_prompt,
        'additional_instructions' => $additional_instructions
    );
    if ( 'store' === $model_write['action'] && is_string( $model_write['value'] ) ) {
        $insert_data['model'] = $model_write['value'];
    }

    $wpdb->insert(
        $table_name,
        $insert_data
    );

    // Check for errors after insert
    if ($wpdb->last_error) {
        back_trace('ERROR', 'Failed to insert row into table: ' . $table_name);
        back_trace('ERROR', 'Error details: ' . $wpdb->last_error);
        return false;  // Row insertion failed
    }

    wp_die();

}
add_action('wp_ajax_add_new_assistant', 'add_new_assistant');

// Upgrade the old primary and alternate assistant settings to the new chatbot assistants table
function upgrade_chatbot_chatgpt_assistants_table() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    // Retrieve options from wp_options table
    $assistant_id = esc_attr(get_option('assistant_id'), '');
    $assistant_id_alternate = esc_attr(get_option('chatbot_chatgpt_assistant_id_alternate'), '');
    $assistant_instructions = esc_attr(get_option('chatbot_chatgpt_assistant_instructions'), '');
    $assistant_instructions_alternate = esc_attr(get_option('chatbot_chatgpt_assistant_instructions_alternate'), '');

    // Insert options into chatbot_chatgpt_assistants table
    if ($assistant_id) {
        $wpdb->insert(
            $table_name,
            array(
                'assistant_id' => $assistant_id,
                'common_name' => 'primary',
                'style' => 'embedded',
                'audience' => 'all',
                'voice' => 'alloy',
                'allow_file_uploads' => 'Yes',
                'allow_transcript_downloads' => 'Yes',
                'show_assistant_name' => 'Yes',
                'initial_greeting' => '',
                'subsequent_greeting' => '',
                'placeholder_prompt' => '',
                'additional_instructions' => $assistant_instructions
            )
        );

        // Check for errors after insert
        if ($wpdb->last_error) {
            back_trace('ERROR', 'Failed to insert row into table: ' . $table_name);
            back_trace('ERROR', 'Error details: ' . $wpdb->last_error);
            return false;  // Row insertion failed
        }

    }

    if ($assistant_id_alternate) {
        $wpdb->insert(
            $table_name,
            array(
                'assistant_id' => $assistant_id_alternate,
                'common_name' => 'alternate',
                'style' => 'embedded',
                'audience' => 'all',
                'voice' => 'alloy',
                'allow_file_uploads' => 'Yes',
                'allow_transcript_downloads' => 'Yes',
                'show_assistant_name' => 'Yes',
                'initial_greeting' => '',
                'subsequent_greeting' => '',
                'placeholder_prompt' => '',
                'additional_instructions' => $assistant_instructions_alternate
            )
        );

        // Check for errors after insert
        if ($wpdb->last_error) {
            back_trace('ERROR', 'Failed to insert row into table: ' . $table_name);
            back_trace('ERROR', 'Error details: ' . $wpdb->last_error);
            return false;  // Row insertion failed
        }
        
    }

    // Delete options from wp_options table
    delete_option('assistant_id');
    delete_option('chatbot_chatgpt_assistant_id_alternate');
    delete_option('chatbot_chatgpt_assistant_instructions');
    delete_option('chatbot_chatgpt_assistant_instructions_alternate');
    
}
