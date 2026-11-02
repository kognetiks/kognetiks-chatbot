<?php
/**
 * Network-free checks for assistants-table row ID propagation.
 *
 * Run: php tools/chatbot-assistant-row-id-tests.php
 *
 * @package chatbot-chatgpt
 */

if ( PHP_SAPI !== 'cli' ) {
    fwrite( STDERR, "Run this file from the command line.\n" );
    exit( 1 );
}

define( 'WPINC', 'wp-includes' );
define( 'ARRAY_A', 'ARRAY_A' );

$failures = 0;

function chatbot_row_id_check( $name, $condition, $detail = '' ) {
    global $failures;
    if ( $condition ) {
        echo "PASS  {$name}\n";
        return;
    }
    $failures++;
    echo "FAIL  {$name}";
    if ( $detail !== '' ) {
        echo " — {$detail}";
    }
    echo "\n";
}

function __( $text, $domain = '' ) {
    unset( $domain );
    return $text;
}

function add_action( $hook, $callback ) {
    unset( $hook, $callback );
}

function sanitize_text_field( $value ) {
    return is_string( $value ) ? trim( $value ) : '';
}

function get_current_user_id() {
    return 7;
}

function wp_salt( $scheme = '' ) {
    unset( $scheme );
    return 'row-id-test-salt';
}

function get_transient( $key ) {
    return isset( $GLOBALS['chatbot_test_transients'][ $key ] ) ? $GLOBALS['chatbot_test_transients'][ $key ] : false;
}

function set_transient( $key, $value, $expiration ) {
    unset( $expiration );
    $GLOBALS['chatbot_test_transients'][ $key ] = $value;
    return true;
}

function delete_transient( $key ) {
    unset( $GLOBALS['chatbot_test_transients'][ $key ] );
    return true;
}

class Chatbot_Row_Id_Wpdb {
    public $prefix = 'wp_';

    public function prepare( $sql, ...$args ) {
        return $sql . '|' . implode( '|', array_map( 'strval', $args ) );
    }

    public function get_row( $query, $output ) {
        unset( $output );
        $rows = isset( $GLOBALS['chatbot_test_rows'] ) ? $GLOBALS['chatbot_test_rows'] : array();
        if ( strpos( $query, 'WHERE id =' ) !== false ) {
            $id = substr( $query, strrpos( $query, '|' ) + 1 );
            foreach ( $rows as $row ) {
                if ( (string) $row['id'] === $id ) {
                    return $row;
                }
            }
            return null;
        }
        if ( strpos( $query, 'assistant_id =' ) !== false ) {
            $assistant_id = substr( $query, strrpos( $query, '|' ) + 1 );
            foreach ( $rows as $row ) {
                if ( (string) $row['assistant_id'] === $assistant_id ) {
                    return $row;
                }
            }
        }
        return null;
    }
}

$wpdb = new Chatbot_Row_Id_Wpdb();
$GLOBALS['chatbot_test_transients'] = array();
$GLOBALS['chatbot_test_rows'] = array(
    array(
        'id' => '12', 'assistant_id' => 'pmpt_abc123', 'common_name' => 'Support',
        'additional_instructions' => "Line one\nLine two", 'vector_store_id' => 'vs_aaa',
        'style' => 'floating', 'audience' => 'all', 'voice' => 'none',
        'allow_file_uploads' => 'No', 'allow_transcript_downloads' => 'No',
    ),
    array(
        'id' => '13', 'assistant_id' => 'pmpt_abc123', 'common_name' => 'Sales',
        'additional_instructions' => 'Sell fairly', 'vector_store_id' => 'vs_bbb',
        'style' => 'embedded', 'audience' => 'all', 'voice' => 'none',
        'allow_file_uploads' => 'No', 'allow_transcript_downloads' => 'No',
    ),
    array(
        'id' => '14', 'assistant_id' => 'asst_shared', 'common_name' => 'Alpha',
        'additional_instructions' => 'Alpha rules', 'vector_store_id' => 'vs_alpha',
        'style' => 'floating', 'audience' => 'all', 'voice' => 'none',
        'allow_file_uploads' => 'No', 'allow_transcript_downloads' => 'No',
    ),
    array(
        'id' => '15', 'assistant_id' => 'asst_shared', 'common_name' => 'Beta',
        'additional_instructions' => 'Beta rules', 'vector_store_id' => 'vs_beta',
        'style' => 'floating', 'audience' => 'all', 'voice' => 'none',
        'allow_file_uploads' => 'No', 'allow_transcript_downloads' => 'No',
    ),
    array(
        'id' => '16', 'assistant_id' => '', 'common_name' => 'Plain',
        'additional_instructions' => 'Plain rules', 'vector_store_id' => '',
        'style' => 'floating', 'audience' => 'all', 'voice' => 'none',
        'allow_file_uploads' => 'No', 'allow_transcript_downloads' => 'No',
    ),
    array(
        'id' => '17', 'assistant_id' => 'hello', 'common_name' => 'Greeting',
        'additional_instructions' => 'Say hello', 'vector_store_id' => '',
        'style' => 'floating', 'audience' => 'all', 'voice' => 'none',
        'allow_file_uploads' => 'No', 'allow_transcript_downloads' => 'No',
    ),
);

$root = dirname( __DIR__ );
require $root . '/includes/utilities/chatbot-names.php';
require $root . '/includes/utilities/chatbot-assistant-row-id.php';
require $root . '/includes/utilities/chatbot-assistants.php';
require $root . '/includes/chatbot-call-openai-api-responses.php';
require $root . '/includes/utilities/chatbot-conversation-keys.php';

$invalid = array( '01', '12abc', '-1', '', ' 12', '12.0', true, false, 1.5, array( 12 ), '12 ' );
foreach ( $invalid as $index => $value ) {
    $normalized = chatbot_chatgpt_normalize_assistant_row_id( $value );
    chatbot_row_id_check(
        'invalid value ' . $index . ' is rejected',
        $normalized['state'] === 'invalid' && $normalized['id'] === null,
        json_encode( $normalized )
    );
}

$missing = chatbot_chatgpt_normalize_assistant_row_id( null );
chatbot_row_id_check( 'null is missing rather than zero', $missing['state'] === 'missing' && $missing['id'] === null );

$zero_string = chatbot_chatgpt_normalize_assistant_row_id( '0' );
$zero_int = chatbot_chatgpt_normalize_assistant_row_id( 0 );
chatbot_row_id_check( 'string zero is explicit row zero', $zero_string['state'] === 'valid' && $zero_string['id'] === 0 );
chatbot_row_id_check( 'integer zero is explicit row zero', $zero_int['state'] === 'valid' && $zero_int['id'] === 0 );
chatbot_row_id_check(
    'canonical zero survives a string-only store',
    chatbot_chatgpt_assistant_row_id_storage_string( 0 ) === '0' && empty( '0' ) === true
);
chatbot_row_id_check(
    'positive decimal string round-trips',
    chatbot_chatgpt_normalize_assistant_row_id( '13' )['id'] === 13
    && chatbot_chatgpt_assistant_row_id_storage_string( 13 ) === '13'
);

foreach ( array( 'chatbot', 'chatbot_chatgpt', 'kognetiks_chatbot', 'chatbot-chatgpt' ) as $tag ) {
    $decision = chatbot_chatgpt_decide_shortcode_row( $tag, null, null, null );
    chatbot_row_id_check(
        "unnumbered {$tag} selects row 0",
        $decision['result'] === 'selected' && $decision['row_id'] === 0
    );
}

chatbot_row_id_check( 'chatbot-0 is not a numbered request', chatbot_chatgpt_numbered_shortcode_row_request( 'chatbot-0' ) === null );
chatbot_row_id_check( 'chatbot-012 is not a numbered request', chatbot_chatgpt_numbered_shortcode_row_request( 'chatbot-012' ) === null );
chatbot_row_id_check( 'chatbot-abc is not a numbered request', chatbot_chatgpt_numbered_shortcode_row_request( 'chatbot-abc' ) === null );

$sales = get_chatbot_chatgpt_assistant_by_key( 13 );
$numbered = chatbot_chatgpt_decide_shortcode_row( 'chatbot-13', null, $sales, null );
chatbot_row_id_check( 'chatbot-13 selects row 13', $numbered['result'] === 'selected' && $numbered['row_id'] === 13 && $numbered['external_id'] === 'pmpt_abc123' );
$assistant_tag = chatbot_chatgpt_decide_shortcode_row( chatbot_chatgpt_normalize_assistant_shortcode_tag( 'assistant-15' ), null, get_chatbot_chatgpt_assistant_by_key( 15 ), null );
chatbot_row_id_check( 'assistant-15 selects row 15', $assistant_tag['row_id'] === 15 && $assistant_tag['external_id'] === 'asst_shared' );
$agent_tag = chatbot_chatgpt_decide_shortcode_row( chatbot_chatgpt_normalize_assistant_shortcode_tag( 'agent-14' ), null, get_chatbot_chatgpt_assistant_by_key( 14 ), null );
chatbot_row_id_check( 'agent-14 selects row 14', $agent_tag['row_id'] === 14 );

$deleted = chatbot_chatgpt_decide_shortcode_row( 'chatbot-5', null, array(), null );
chatbot_row_id_check(
    'deleted numbered row is unavailable',
    $deleted['result'] === 'unavailable' && $deleted['row_id'] === null
);
chatbot_row_id_check( 'deleted numbered row is not rewritten to zero', $deleted['row_id'] !== 0 );

$raw_prompt = chatbot_chatgpt_decide_shortcode_row( 'chatbot', 'pmpt_abc123', null, $sales );
$raw_assistant = chatbot_chatgpt_decide_shortcode_row( 'chatbot', 'asst_shared', null, get_chatbot_chatgpt_assistant_by_key( 15 ) );
$primary = chatbot_chatgpt_decide_shortcode_row( 'chatbot', 'primary', null, $sales );
$alternate = chatbot_chatgpt_decide_shortcode_row( 'chatbot', 'alternate', null, $sales );
chatbot_row_id_check( 'raw pmpt stays row 0', $raw_prompt['row_id'] === 0 && $raw_prompt['external_id'] === '' );
chatbot_row_id_check( 'raw asst stays row 0', $raw_assistant['row_id'] === 0 );
chatbot_row_id_check( 'primary stays row 0', $primary['row_id'] === 0 );
chatbot_row_id_check( 'alternate stays row 0', $alternate['row_id'] === 0 );

$common = chatbot_chatgpt_decide_shortcode_row( 'chatbot', 'Sales', null, $sales );
chatbot_row_id_check( 'common name selects its row', $common['result'] === 'selected' && $common['row_id'] === 13 && $common['external_id'] === 'pmpt_abc123' );

$plain = chatbot_chatgpt_decide_shortcode_row( 'chatbot-16', null, get_chatbot_chatgpt_assistant_by_key( 16 ), null );
$greeting = chatbot_chatgpt_decide_shortcode_row( 'chatbot-17', null, get_chatbot_chatgpt_assistant_by_key( 17 ), null );
chatbot_row_id_check( 'empty external ID keeps its row', $plain['row_id'] === 16 && $plain['external_id'] === '' );
chatbot_row_id_check( 'unrecognized external ID is not rewritten', $greeting['row_id'] === 17 && $greeting['external_id'] === 'hello' );

$legacy_lookup = chatbot_chatgpt_get_responses_local_row( 'pmpt_abc123', null );
$selected_prompt = chatbot_chatgpt_get_responses_local_row( 'pmpt_abc123', 13 );
chatbot_row_id_check( 'shared pmpt without a row uses the legacy first row', isset( $legacy_lookup['common_name'] ) && $legacy_lookup['common_name'] === 'Support' );
chatbot_row_id_check( 'shared pmpt with row 13 uses Sales', isset( $selected_prompt['common_name'] ) && $selected_prompt['common_name'] === 'Sales' && $selected_prompt['vector_store_id'] === 'vs_bbb' );

$legacy_asst = chatbot_chatgpt_get_responses_local_row( 'asst_shared', null );
$selected_asst = chatbot_chatgpt_get_responses_local_row( 'asst_shared', '15' );
chatbot_row_id_check( 'shared asst without a row uses Alpha', isset( $legacy_asst['common_name'] ) && $legacy_asst['common_name'] === 'Alpha' );
chatbot_row_id_check( 'shared asst with row 15 uses Beta', isset( $selected_asst['common_name'] ) && $selected_asst['common_name'] === 'Beta' && $selected_asst['vector_store_id'] === 'vs_beta' );

$prompt_payload = chatbot_chatgpt_apply_responses_assistant_fields( array( 'input' => 'hi' ), 'pmpt_abc123', $selected_prompt, 'Sell fairly', 'gpt-should-not-be-sent' );
chatbot_row_id_check(
    'pmpt payload still sends prompt.id',
    is_array( $prompt_payload )
    && isset( $prompt_payload['prompt']['id'] )
    && $prompt_payload['prompt']['id'] === 'pmpt_abc123'
    && ! array_key_exists( 'model', $prompt_payload )
    && isset( $prompt_payload['tools'][0]['vector_store_ids'][0] )
    && $prompt_payload['tools'][0]['vector_store_ids'][0] === 'vs_bbb'
);

$assistant_payload = chatbot_chatgpt_apply_responses_assistant_fields( array(), 'asst_shared', $selected_asst, 'Beta rules', 'gpt-5.6-terra' );
chatbot_row_id_check(
    'asst payload uses the selected row and no prompt.id',
    is_array( $assistant_payload )
    && ! isset( $assistant_payload['prompt'] )
    && $assistant_payload['model'] === 'gpt-5.6-terra'
    && strpos( $assistant_payload['instructions'], 'You are Beta.' ) !== false
    && strpos( $assistant_payload['instructions'], 'Beta rules' ) !== false
    && $assistant_payload['tools'][0]['vector_store_ids'][0] === 'vs_beta'
);

$forged = chatbot_chatgpt_validate_assistant_row_selection( true, '14', true, '13', 'pmpt_abc123', 'get_chatbot_chatgpt_assistant_by_key' );
chatbot_row_id_check( 'forged positive ID is rejected', $forged['result'] === 'rejected' && $forged['row_id'] === null );
chatbot_row_id_check( 'forged positive ID does not become row 0', $forged['row_id'] !== 0 && chatbot_chatgpt_responses_row_id_from_decision( $forged ) === null );

$mismatch = chatbot_chatgpt_validate_assistant_row_selection( true, '13', true, '13', 'asst_other', 'get_chatbot_chatgpt_assistant_by_key' );
chatbot_row_id_check( 'mismatched external ID is rejected', $mismatch['result'] === 'rejected' && $mismatch['row_id'] === null );

$posted_without_server = chatbot_chatgpt_validate_assistant_row_selection( true, '13', false, null, 'pmpt_abc123', 'get_chatbot_chatgpt_assistant_by_key' );
chatbot_row_id_check( 'positive ID without a server selection is rejected', $posted_without_server['result'] === 'rejected' );

$deleted_posted = chatbot_chatgpt_validate_assistant_row_selection( true, '5', true, '5', 'original', 'get_chatbot_chatgpt_assistant_by_key' );
chatbot_row_id_check( 'deleted posted row is rejected', $deleted_posted['result'] === 'rejected' && $deleted_posted['row_id'] === null );

$explicit_zero = chatbot_chatgpt_validate_assistant_row_selection( true, '0', true, '0', 'pmpt_abc123', 'get_chatbot_chatgpt_assistant_by_key' );
chatbot_row_id_check( 'explicit zero is trusted as no selected row', $explicit_zero['result'] === 'trusted' && $explicit_zero['row_id'] === 0 );
chatbot_row_id_check( 'explicit zero does not select a Responses row', chatbot_chatgpt_responses_row_id_from_decision( $explicit_zero ) === null );
chatbot_row_id_check(
    'explicit zero keeps the pmpt provider route',
    chatbot_chatgpt_routes_openai_responses( 'OpenAI', 'Yes', 'pmpt_abc123' ) === true
);
chatbot_row_id_check(
    'explicit zero does not turn original into Responses',
    chatbot_chatgpt_routes_openai_responses( 'OpenAI', 'No', 'original' ) === false
);

$legacy_request = chatbot_chatgpt_validate_assistant_row_selection( false, null, true, '13', 'pmpt_abc123', 'get_chatbot_chatgpt_assistant_by_key' );
chatbot_row_id_check( 'missing row field stays legacy', $legacy_request['result'] === 'legacy' && $legacy_request['row_id'] === null );
chatbot_row_id_check( 'missing row field is not converted to zero', $legacy_request['row_id'] !== 0 );

$matched = chatbot_chatgpt_validate_assistant_row_selection( true, '13', true, '13', 'pmpt_abc123', 'get_chatbot_chatgpt_assistant_by_key' );
chatbot_row_id_check( 'matching positive ID is trusted', $matched['result'] === 'trusted' && chatbot_chatgpt_responses_row_id_from_decision( $matched ) === 13 );
$empty_match = chatbot_chatgpt_validate_assistant_row_selection( true, '16', true, '16', 'original', 'get_chatbot_chatgpt_assistant_by_key' );
chatbot_row_id_check( 'empty stored ID matches original', $empty_match['result'] === 'trusted' && $empty_match['row_id'] === 16 );
chatbot_row_id_check(
    'empty stored ID does not enter Responses',
    chatbot_chatgpt_routes_openai_responses( 'OpenAI', 'No', 'original' ) === false
);

$queued_id = chatbot_chatgpt_enqueue_message( 7, 10, 'session-a', 'pmpt_abc123', 'Hello', 'client-1', 13 );
chatbot_row_id_check( 'enqueue accepts a positive row', is_string( $queued_id ) && $queued_id === 'client-1' );
set_transient( 'chatbot_chatgpt_assistant_row_id_10_session-a', '99', 3600 );
$queued_status = chatbot_chatgpt_get_queue_status( 7, 10, 'session-a', 'pmpt_abc123' );
$queued_item = $queued_status['next_message'];
chatbot_row_id_check(
    'queued item keeps row 13 after the page transient changes',
    is_array( $queued_item ) && isset( $queued_item['assistant_row_id'] ) && $queued_item['assistant_row_id'] === '13' && $queued_item['assistant_id'] === 'pmpt_abc123'
);
$queued_decision = chatbot_chatgpt_validate_queued_assistant_row( $queued_item, 'get_chatbot_chatgpt_assistant_by_key' );
chatbot_row_id_check( 'queued row 13 is trusted from the item', $queued_decision['result'] === 'trusted' && $queued_decision['row_id'] === 13 );

$saved_row = $GLOBALS['chatbot_test_rows'][1];
unset( $GLOBALS['chatbot_test_rows'][1] );
$deleted_queue = chatbot_chatgpt_validate_queued_assistant_row( $queued_item, 'get_chatbot_chatgpt_assistant_by_key' );
chatbot_row_id_check( 'deleted queued row is rejected', $deleted_queue['result'] === 'rejected' && $deleted_queue['row_id'] === null );
chatbot_row_id_check( 'deleted queued row is not processed as zero', $deleted_queue['row_id'] !== 0 );
$GLOBALS['chatbot_test_rows'][1] = $saved_row;
ksort( $GLOBALS['chatbot_test_rows'] );

$queued_item['assistant_id'] = 'asst_other';
$mismatched_queue = chatbot_chatgpt_validate_queued_assistant_row( $queued_item, 'get_chatbot_chatgpt_assistant_by_key' );
chatbot_row_id_check( 'mismatched queued row is rejected', $mismatched_queue['result'] === 'rejected' && $mismatched_queue['row_id'] === null );

$old_item = array( 'message' => 'old', 'assistant_id' => 'pmpt_abc123' );
$old_decision = chatbot_chatgpt_validate_queued_assistant_row( $old_item, 'get_chatbot_chatgpt_assistant_by_key' );
chatbot_row_id_check( 'older queued item without a row field stays legacy', $old_decision['result'] === 'legacy' && $old_decision['row_id'] === null && ! array_key_exists( 'assistant_row_id', $old_item ) );

$zero_item = chatbot_chatgpt_annotate_queue_item_row_id( array( 'assistant_id' => 'original' ), 0 );
$zero_queue = chatbot_chatgpt_validate_queued_assistant_row( $zero_item, 'get_chatbot_chatgpt_assistant_by_key' );
chatbot_row_id_check( 'queued zero stays zero', is_array( $zero_item ) && $zero_item['assistant_row_id'] === '0' && $zero_queue['result'] === 'trusted' && $zero_queue['row_id'] === 0 );

$invalid_enqueue = chatbot_chatgpt_enqueue_message( 7, 11, 'session-b', 'pmpt_abc123', 'Nope', 'client-2', '01' );
chatbot_row_id_check( 'invalid row is not queued', $invalid_enqueue === false );
chatbot_row_id_check( 'invalid enqueue writes no queue', chatbot_chatgpt_get_queue_status( 7, 11, 'session-b', 'pmpt_abc123' )['count'] === 0 );

$legacy_enqueue = chatbot_chatgpt_enqueue_message( 7, 12, 'session-c', 'asst_shared', 'Legacy', 'client-3', null );
$legacy_status = chatbot_chatgpt_get_queue_status( 7, 12, 'session-c', 'asst_shared' );
chatbot_row_id_check(
    'omitted row ID is not stored as zero',
    $legacy_enqueue === 'client-3' && is_array( $legacy_status['next_message'] ) && ! array_key_exists( 'assistant_row_id', $legacy_status['next_message'] )
);

$routes = new ReflectionFunction( 'chatbot_chatgpt_routes_openai_responses' );
chatbot_row_id_check( 'Responses routing has no row ID parameter', $routes->getNumberOfParameters() === 3 );
$platforms = array( 'Azure OpenAI', 'Mistral', 'Anthropic', 'NVIDIA', 'DeepSeek', 'Google', 'Markov Chain', 'Transformer', 'Local Server' );
foreach ( $platforms as $platform ) {
    chatbot_row_id_check(
        $platform . ' is not forced onto OpenAI Responses',
        chatbot_chatgpt_routes_openai_responses( $platform, 'Yes', 'asst_shared' ) === false
        && chatbot_chatgpt_routes_openai_responses( $platform, 'Yes', 'pmpt_abc123' ) === false
    );
}
chatbot_row_id_check( 'Anthropic assistant setting is turned off', chatbot_chatgpt_use_assistant_id_for_platform( 'Anthropic', 'Yes' ) === 'No' );
chatbot_row_id_check( 'Local Server assistant setting is turned off', chatbot_chatgpt_use_assistant_id_for_platform( 'Local Server', 'Yes' ) === 'No' );
chatbot_row_id_check( 'OpenAI assistant setting stays on', chatbot_chatgpt_use_assistant_id_for_platform( 'OpenAI', 'Yes' ) === 'Yes' );
chatbot_row_id_check( 'Azure assistant setting stays on', chatbot_chatgpt_use_assistant_id_for_platform( 'Azure OpenAI', 'Yes' ) === 'Yes' );
chatbot_row_id_check( 'Mistral assistant setting stays on', chatbot_chatgpt_use_assistant_id_for_platform( 'Mistral', 'Yes' ) === 'Yes' );

$key_reflection = new ReflectionFunction( 'chatbot_chatgpt_conversation_storage_key' );
$key_names = array();
foreach ( $key_reflection->getParameters() as $parameter ) {
    $key_names[] = $parameter->getName();
}
$same_key = chatbot_chatgpt_conversation_storage_key( 7, 'session-a', 10, 'pmpt_abc123' );
$other_key = chatbot_chatgpt_conversation_storage_key( 7, 'session-a', 10, 'asst_shared' );
chatbot_row_id_check(
    'conversation key parameters are unchanged',
    $key_names === array( 'user_id', 'session_id', 'page_id', 'assistant_id' ) && $same_key !== '' && $same_key !== $other_key
);
chatbot_row_id_check(
    'conversation key is stable',
    $same_key === chatbot_chatgpt_conversation_storage_key( 7, 'session-a', 10, 'pmpt_abc123' )
);

$js = file_get_contents( $root . '/assets/js/chatbot-chatgpt.js' );
chatbot_row_id_check(
    'browser transcript key still uses the external assistant ID',
    strpos( $js, "sessionStorage.setItem('chatbot_chatgpt_conversation' + '_' + assistant_id, conversation.html());" ) !== false
);
chatbot_row_id_check(
    'browser transcript key does not use the row ID',
    strpos( $js, "chatbot_chatgpt_conversation' + '_' + assistant_row_id" ) === false
);
chatbot_row_id_check(
    'browser sends a present row ID including zero',
    strpos( $js, "Object.prototype.hasOwnProperty.call(kchat_settings, 'assistant_row_id')" ) !== false
    && strpos( $js, 'requestData.assistant_row_id = String(kchat_settings.assistant_row_id);' ) !== false
);

$assistants_source = file_get_contents( $root . '/includes/utilities/chatbot-assistants.php' );
chatbot_row_id_check( 'assistants table has no new row-id column', strpos( $assistants_source, 'assistant_row_id' ) === false );
chatbot_row_id_check(
    'assistants primary key is unchanged',
    strpos( $assistants_source, 'id BIGINT(20) UNSIGNED AUTO_INCREMENT' ) !== false
    && strpos( $assistants_source, 'PRIMARY KEY (id)' ) !== false
);

$shortcode_source = file_get_contents( $root . '/includes/chatbot-shortcode.php' );
chatbot_row_id_check(
    'hyphenated chatbot-chatgpt is not registered',
    strpos( $shortcode_source, "add_shortcode('chatbot-chatgpt'" ) === false
    && strpos( $shortcode_source, "'chatbot_chatgpt'" ) !== false
);
$unavailable_at = strpos( $shortcode_source, 'chatbot_chatgpt_assistant_row_unavailable_message()' );
$row_transient_at = strpos( $shortcode_source, "set_chatbot_chatgpt_transients( 'assistant_row_id'" );
chatbot_row_id_check(
    'unavailable shortcode returns before writing the row transient',
    $unavailable_at !== false && $row_transient_at !== false && $unavailable_at < $row_transient_at
);

$handler_source = file_get_contents( $root . '/chatbot-chatgpt.php' );
chatbot_row_id_check( 'plugin version was not bumped', strpos( $handler_source, 'Version:     2.5.6' ) !== false );
chatbot_row_id_check(
    'queued identity blocks a later page transient',
    strpos( $handler_source, '! $queue_row_locked && !empty($kchat_settings[\'assistant_id\'])' ) !== false
);

if ( $failures > 0 ) {
    echo "FAILED {$failures}\n";
    exit( 1 );
}

echo "ALL PASSED\n";
exit( 0 );
