<?php
/**
 * Kognetiks Chatbot for WordPress - Chatbot Assistants
 *
 * This file contains the code for table actions for managing assistants
 * to display the chatbot conversation on a page on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Call to create the table
// create_chatbot_chatgpt_assistants_table();

// Call to drop the table
// drop_chatbot_chatgpt_assistants_table();

// Call to add a row
// add_chatbot_chatgpt_assistant('assistant_id_123', 'Assistant 1', 'Embedded', 'All', 'Default Voice', 'Yes', 'No', 'Hello!', 'How can I help you today?', 'Please provide any additional details.');

// Call to update a row
// update_chatbot_chatgpt_assistant(1, 'assistant_id_123', 'Assistant 1', 'Floating', 'Logged-in', 'New Voice', 'No', 'Yes', 'Hi there!', 'What can I do for you?', 'Please be specific.');

// Call to delete a row
// delete_chatbot_chatgpt_assistant(1);

// Call to retrieve a row from the table using the Common Name
// $assistant_details = get_chatbot_chatgpt_assistant_by_common_name('Assistant 1');
// back_trace( 'NOTICE', 'Assistant Details: ' . print_r($assistant_details, true));

// Create the table for the chatbot assistants
function create_chatbot_chatgpt_assistants_table() {
 
    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';
    
    // Check if the table already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        return; // Exit if the table already exists
    }
    
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT,
        assistant_id VARCHAR(255) NOT NULL,
        common_name VARCHAR(255) NOT NULL,
        style ENUM('Embedded', 'Floating') NOT NULL,
        audience ENUM('All', 'Visitors', 'Logged-in') NOT NULL,
        voice ENUM('alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer') NOT NULL,
        allow_file_uploads ENUM('Yes', 'No') NOT NULL,
        allow_transcript_downloads ENUM('Yes', 'No') NOT NULL,
        initial_greeting TEXT NOT NULL,
        subsequent_greeting TEXT NOT NULL,
        additional_instructions TEXT NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
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

// Add a row to the chatbot assistants table
function add_chatbot_chatgpt_assistant($assistant_id, $common_name, $style, $audience, $voice, $allow_file_uploads, $allow_transcript_downloads, $initial_greeting, $subsequent_greeting, $additional_instructions) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    $wpdb->insert(
        $table_name,
        array(
            'assistant_id' => $assistant_id,
            'common_name' => $common_name,
            'style' => $style,
            'audience' => $audience,
            'voice' => $voice,
            'allow_file_uploads' => $allow_file_uploads,
            'allow_transcript_downloads' => $allow_transcript_downloads,
            'initial_greeting' => $initial_greeting,
            'subsequent_greeting' => $subsequent_greeting,
            'additional_instructions' => $additional_instructions
        )
    );
}

// Update a row in the chatbot assistants table
function update_chatbot_chatgpt_assistant($id, $assistant_id, $common_name, $style, $audience, $voice, $allow_file_uploads, $allow_transcript_downloads, $initial_greeting, $subsequent_greeting, $additional_instructions) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    $wpdb->update(
        $table_name,
        array(
            'assistant_id' => $assistant_id,
            'common_name' => $common_name,
            'style' => $style,
            'audience' => $audience,
            'voice' => $voice,
            'allow_file_uploads' => $allow_file_uploads,
            'allow_transcript_downloads' => $allow_transcript_downloads,
            'initial_greeting' => $initial_greeting,
            'subsequent_greeting' => $subsequent_greeting,
            'additional_instructions' => $additional_instructions
        ),
        array('id' => $id)
    );
}

// Delete a row from the chatbot assistants table
function delete_chatbot_chatgpt_assistant($id) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    $wpdb->delete(
        $table_name,
        array('id' => $id)
    );
}

// Display the chatbot assistants table
function display_chatbot_chatgpt_assistants_table() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';
    $assistants = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<style>
        .kflow-templates-display table {
            width: 100%;
            border-collapse: collapse;
            width: 50% !important; /* Adjust table width as needed */
        }
        .kflow-templates-display th, .kflow-templates-display td {
            border: 1px solid #ddd;
            padding: 8px;
            padding: 10px !important; /* Adjust cell padding */
            white-space: normal !important; /* Allow cell content to wrap */
            text-align: center !important; /* Center text-align */
        }
        .kflow-templates-display th {
            background-color: #f2f2f2;
        }
    </style>';

    echo '<div class="wrap kflow-templates-display">';
    echo '<h1>Manage Assistants</h1>';
    echo '<p>Use the table below to manage the chatbot assistants.</p>';
    echo '<p><strong>Note:</strong> The table below is for demonstration purposes only. It is recommended to use the Chatbot Assistants settings page to manage the assistants.</p>';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Assistant ID</th>';
    echo '<th>Common Name</th>';
    echo '<th>Style</th>';
    echo '<th>Audience</th>';
    echo '<th>Voice</th>';
    echo '<th>Allow File Uploads</th>';
    echo '<th>Allow Transcript Downloads</th>';
    echo '<th>Initial Greeting</th>';
    echo '<th>Subsequent Greeting</th>';
    echo '<th>Additional Instructions</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($assistants as $assistant) {
        echo '<tr>';
        echo '<td>' . $assistant->id . '</td>';
        echo '<td><input type="text" name="assistant_id_' . $assistant->id . '" value="' . $assistant->assistant_id . '"></td>';
        echo '<td><input type="text" name="common_name_' . $assistant->id . '" value="' . $assistant->common_name . '"></td>';
        echo '<td><select name="style_' . $assistant->id . '">';
        echo '<option value="Embedded"' . ($assistant->style == 'Embedded' ? ' selected' : '') . '>Embedded</option>';
        echo '<option value="Floating"' . ($assistant->style == 'Floating' ? ' selected' : '') . '>Floating</option>';
        echo '</select></td>';
        echo '<td><select name="audience_' . $assistant->id . '">';
        echo '<option value="All"' . ($assistant->audience == 'All' ? ' selected' : '') . '>All</option>';
        echo '<option value="Visitors"' . ($assistant->audience == 'Visitors' ? ' selected' : '') . '>Visitors</option>';
        echo '<option value="Logged-in"' . ($assistant->audience == 'Logged-in' ? ' selected' : '') . '>Logged-in</option>';
        echo '</select></td>';
        echo '<td><select name="voice_' . $assistant->id . '">';
        echo '<option value="alloy"' . ($assistant->voice == 'alloy' ? ' selected' : '') . '>Alloy</option>';
        echo '<option value="echo"' . ($assistant->voice == 'echo' ? ' selected' : '') . '>Echo</option>';
        echo '<option value="fable"' . ($assistant->voice == 'fable' ? ' selected' : '') . '>Fable</option>';
        echo '<option value="onyx"' . ($assistant->voice == 'onyx' ? ' selected' : '') . '>Onyx</option>';
        echo '<option value="nova"' . ($assistant->voice == 'nova' ? ' selected' : '') . '>Nova</option>';
        echo '<option value="shimmer"' . ($assistant->voice == 'shimmer' ? ' selected' : '') . '>Shimmer</option>';
        echo '</select></td>';
        echo '<td><select name="allow_file_uploads_' . $assistant->id . '">';
        echo '<option value="Yes"' . ($assistant->allow_file_uploads == 'Yes' ? ' selected' : '') . '>Yes</option>';
        echo '<option value="No"' . ($assistant->allow_file_uploads == 'No' ? ' selected' : '') . '>No</option>';
        echo '</select></td>';
        echo '<td><select name="allow_transcript_downloads_' . $assistant->id . '">';
        echo '<option value="Yes"' . ($assistant->allow_transcript_downloads == 'Yes' ? ' selected' : '') . '>Yes</option>';
        echo '<option value="No"' . ($assistant->allow_transcript_downloads == 'No' ? ' selected' : '') . '>No</option>';
        echo '</select></td>';
        echo '<td><textarea name="initial_greeting_' . $assistant->id . '">' . $assistant->initial_greeting . '</textarea></td>';
        echo '<td><textarea name="subsequent_greeting_' . $assistant->id . '">' . $assistant->subsequent_greeting . '</textarea></td>';
        echo '<td><textarea name="additional_instructions_' . $assistant->id . '">' . $assistant->additional_instructions . '</textarea></td>';
        echo '<td>';
        echo '<button class="button-primary" onclick="updateAssistant(' . $assistant->id . ')">Update</button>&nbsp';
        echo '<button class="button-primary" onclick="deleteAssistant(' . $assistant->id . ')">Delete</button>';
        echo '</td>';
        echo '</tr>';
    }

    // echo '</tbody>';
    // echo '</table>';

    // // Add New Assistant Form
    // echo '<h2>Add New Assistant</h2>';
    // echo '<table>';
    // echo '<tbody>';
    echo '<tr>';
    echo '<td>New</td>';
    echo '<td><input type="text" name="new_assistant_id" placeholder="Assistant ID"></td>';
    echo '<td><input type="text" name="new_common_name" placeholder="Common Name"></td>';
    echo '<td><select name="new_style">';
    echo '<option value="Embedded">Embedded</option>';
    echo '<option value="Floating">Floating</option>';
    echo '</select></td>';
    echo '<td><select name="new_audience">';
    echo '<option value="All">All</option>';
    echo '<option value="Visitors">Visitors</option>';
    echo '<option value="Logged-in">Logged-in</option>';
    echo '</select></td>';
    echo '<td><select name="new_voice">';
    echo '<option value="alloy">Alloy</option>';
    echo '<option value="echo">Echo</option>';
    echo '<option value="fable">Fable</option>';
    echo '<option value="onyx">Onyx</option>';
    echo '<option value="nova">Nova</option>';
    echo '<option value="shimmer">Shimmer</option>';
    echo '</select></td>';
    echo '<td><select name="new_allow_file_uploads">';
    echo '<option value="Yes">Yes</option>';
    echo '<option value="No">No</option>';
    echo '</select></td>';
    echo '<td><select name="new_allow_transcript_downloads">';
    echo '<option value="Yes">Yes</option>';
    echo '<option value="No">No</option>';
    echo '</select></td>';
    echo '<td><textarea name="new_initial_greeting" placeholder="Initial Greeting"></textarea></td>';
    echo '<td><textarea name="new_subsequent_greeting" placeholder="Subsequent Greeting"></textarea></td>';
    echo '<td><textarea name="new_additional_instructions" placeholder="Additional Instructions"></textarea></td>';
    echo '<td><button class="button-primary" onclick="addNewAssistant()">Add New Assistant</button></td>';
    echo '</tr>';
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}


// Scripts for the chatbot assistants table
function chatbot_chatgpt_assistants_scripts() {

    ?>
    <script type="text/javascript">
        function updateAssistant(id) {
            var data = {
                action: 'update_assistant',
                id: id,
                assistant_id: document.getElementsByName('assistant_id_' + id)[0].value,
                common_name: document.getElementsByName('common_name_' + id)[0].value,
                style: document.getElementsByName('style_' + id)[0].value,
                audience: document.getElementsByName('audience_' + id)[0].value,
                voice: document.getElementsByName('voice_' + id)[0].value,
                allow_file_uploads: document.getElementsByName('allow_file_uploads_' + id)[0].value,
                allow_transcript_downloads: document.getElementsByName('allow_transcript_downloads_' + id)[0].value,
                initial_greeting: document.getElementsByName('initial_greeting_' + id)[0].value,
                subsequent_greeting: document.getElementsByName('subsequent_greeting_' + id)[0].value,
                additional_instructions: document.getElementsByName('additional_instructions_' + id)[0].value
            };

            jQuery.post(ajaxurl, data, function(response) {
                alert('Assistant updated successfully!');
            });
        }

        function deleteAssistant(id) {
            var data = {
                action: 'delete_assistant',
                id: id
            };

            jQuery.post(ajaxurl, data, function(response) {
                alert('Assistant deleted successfully!');
                location.reload();
            });
        }

        function addNewAssistant() {
            var data = {
                action: 'add_new_assistant',
                assistant_id: document.getElementsByName('new_assistant_id')[0].value,
                common_name: document.getElementsByName('new_common_name')[0].value,
                style: document.getElementsByName('new_style')[0].value,
                audience: document.getElementsByName('new_audience')[0].value,
                voice: document.getElementsByName('new_voice')[0].value,
                allow_file_uploads: document.getElementsByName('new_allow_file_uploads')[0].value,
                allow_transcript_downloads: document.getElementsByName('new_allow_transcript_downloads')[0].value,
                initial_greeting: document.getElementsByName('new_initial_greeting')[0].value,
                subsequent_greeting: document.getElementsByName('new_subsequent_greeting')[0].value,
                additional_instructions: document.getElementsByName('new_additional_instructions')[0].value
            };

            jQuery.post(ajaxurl, data, function(response) {
                alert('New assistant added successfully!');
                location.reload();
            });
        }
    </script>
    <?php
}
add_action('admin_footer', 'chatbot_chatgpt_assistants_scripts');


function update_assistant() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    $id = intval($_POST['id']);
    $assistant_id = sanitize_text_field($_POST['assistant_id']);
    $common_name = sanitize_text_field($_POST['common_name']);
    $style = sanitize_text_field($_POST['style']);
    $audience = sanitize_text_field($_POST['audience']);
    $voice = sanitize_text_field($_POST['voice']);
    $allow_file_uploads = sanitize_text_field($_POST['allow_file_uploads']);
    $allow_transcript_downloads = sanitize_text_field($_POST['allow_transcript_downloads']);
    $initial_greeting = sanitize_textarea_field($_POST['initial_greeting']);
    $subsequent_greeting = sanitize_textarea_field($_POST['subsequent_greeting']);
    $additional_instructions = sanitize_textarea_field($_POST['additional_instructions']);

    $wpdb->update(
        $table_name,
        array(
            'assistant_id' => $assistant_id,
            'common_name' => $common_name,
            'style' => $style,
            'audience' => $audience,
            'voice' => $voice,
            'allow_file_uploads' => $allow_file_uploads,
            'allow_transcript_downloads' => $allow_transcript_downloads,
            'initial_greeting' => $initial_greeting,
            'subsequent_greeting' => $subsequent_greeting,
            'additional_instructions' => $additional_instructions
        ),
        array('id' => $id)
    );

    wp_die();
}
add_action('wp_ajax_update_assistant', 'update_assistant');

function delete_assistant() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    $id = intval($_POST['id']);
    $wpdb->delete($table_name, array('id' => $id));

    wp_die();
}
add_action('wp_ajax_delete_assistant', 'delete_assistant');

function add_new_assistant() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    $assistant_id = sanitize_text_field($_POST['assistant_id']);
    $common_name = sanitize_text_field($_POST['common_name']);
    $style = sanitize_text_field($_POST['style']);
    $audience = sanitize_text_field($_POST['audience']);
    $voice = sanitize_text_field($_POST['voice']);
    $allow_file_uploads = sanitize_text_field($_POST['allow_file_uploads']);
    $allow_transcript_downloads = sanitize_text_field($_POST['allow_transcript_downloads']);
    $initial_greeting = sanitize_textarea_field($_POST['initial_greeting']);
    $subsequent_greeting = sanitize_textarea_field($_POST['subsequent_greeting']);
    $additional_instructions = sanitize_textarea_field($_POST['additional_instructions']);

    $wpdb->insert(
        $table_name,
        array(
            'assistant_id' => $assistant_id,
            'common_name' => $common_name,
            'style' => $style,
            'audience' => $audience,
            'voice' => $voice,
            'allow_file_uploads' => $allow_file_uploads,
            'allow_transcript_downloads' => $allow_transcript_downloads,
            'initial_greeting' => $initial_greeting,
            'subsequent_greeting' => $subsequent_greeting,
            'additional_instructions' => $additional_instructions
        )
    );

    wp_die();
}
add_action('wp_ajax_add_new_assistant', 'add_new_assistant');
