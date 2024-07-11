<?php
/**
 * Kognetiks Chatbot for WordPress - Chatbot Assistants - Ver 2.0.4
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
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Check if the table exists and create/upgrade it if necessary
    dbDelta($sql);

    // Execute SQL query and create the table
    if(dbDelta($sql)) {
        // Table created successfully
    } else {
        // Log the error
        error_log('Failed to create table: ' . $table_name);
        error_log('SQL: ' . $sql);
        // Log the specific reason for the failure
        if($wpdb->last_error !== '') {
            error_log('Error details: ' . $wpdb->last_error);
        }
        return false;  // Table creation failed
    }

    // Call the upgrade function after creating the table
    upgrade_chatbot_chatgpt_assistants_table();

    // Keep the chatbot_chatgpt_number_of_shortcodes option updated - Ver 2.0.6
    update_chatbot_chatgpt_number_of_shortcodes();

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

    // If the assistant is not found, return an empty array
    if (!$assistant_details) {
        return array();
    }

    return $assistant_details;

}

// Keep the chatbot_chatgpt_number_of_shortcodes option updated - Ver 2.0.6
function update_chatbot_chatgpt_number_of_shortcodes() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    // $number_of_shortcodes = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // Check if the table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

    if ($table_exists) {
        // The table exists, proceed with the original query
        $number_of_shortcodes = $wpdb->get_var("SELECT MAX(id) FROM $table_name");

        // If the query fails for any other reason, set $number_of_shortcodes to 0
        if ($number_of_shortcodes === NULL || $number_of_shortcodes === FALSE) {
            $number_of_shortcodes = 0;
        }
    } else {
        // The table doesn't exist, set $number_of_shortcodes to 0 directly
        $number_of_shortcodes = 0;
    }  

    update_option('chatbot_chatgpt_number_of_shortcodes', $number_of_shortcodes);

    // error_log('chatbot-assistants - Number of Shortcodes: ' . $number_of_shortcodes);

}

// Add a row to the chatbot assistants table
function add_chatbot_chatgpt_assistant($assistant_id, $common_name, $style, $audience, $voice, $allow_file_uploads, $allow_transcript_downloads, $show_assistant_name, $initial_greeting, $subsequent_greeting, $placeholder_prompt, $additional_instructions) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    $wpdb->insert(
        $table_name,
        array(
            'assistant_id' => $assistant_id ?? 'Please provide the GPT Assistant Id.',
            'common_name' => $common_name ?? 'Kognetiks Chatbot Assistant',
            'style' => $style ?? 'embedded',
            'audience' => $audience ?? 'All',
            'voice' => $voice ?? 'alloy',
            'allow_file_uploads' => $allow_file_uploads ?? 'Yes',
            'allow_transcript_downloads' => $allow_transcript_downloads ?? 'Yes',
            'show_assistant_name' => $show_assistant_name ?? 'Yes',
            'initial_greeting' => $initial_greeting ?? 'Hello! How can I help you today?',
            'subsequent_greeting' => $subsequent_greeting ?? 'Hello again! How can I help you?',
            'placeholder_prompt' => $placeholder_prompt ?? 'Enter your question ...',
            'additional_instructions' => $additional_instructions ?? ''
        )
    );
}

// Update a row in the chatbot assistants table
function update_chatbot_chatgpt_assistant($id, $assistant_id, $common_name, $style, $audience, $voice, $allow_file_uploads, $allow_transcript_downloads, $show_assistant_name, $initial_greeting, $subsequent_greeting, $placeholder_prompt, $additional_instructions) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    // DIAG - Diagnostics - Ver 2.0.4
    // back_trace( 'NOTICE', '$initial_greeting', $initial_greeting );
    // back_trace( 'NOTICE', '$subsequent_greeting', $subsequent_greeting );

    $wpdb->update(
        $table_name,
        array(
            'assistant_id' => $assistant_id ?? 'Please provide the GPT Assistant Id.',
            'common_name' => $common_name ?? 'Kognetiks Chatbot Assistant',
            'style' => $style ?? 'embedded',
            'audience' => $audience ?? 'All',
            'voice' => $voice ?? 'alloy',
            'allow_file_uploads' => $allow_file_uploads ?? 'Yes',
            'allow_transcript_downloads' => $allow_transcript_downloads ?? 'Yes',
            'show_assistant_name' => $show_assistant_name ?? 'Yes',
            'initial_greeting' => $initial_greeting ?? 'Hello! How can I help you today?',
            'subsequent_greeting' => $subsequent_greeting ?? 'Hello again! How can I help you?',
            'placeholder_prompt' => $placeholder_prompt ?? 'Enter your question ...',
            'additional_instructions' => $additional_instructions ?? ''
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

    // Update the number of shortcodes - Ver 2.0.6
    update_chatbot_chatgpt_number_of_shortcodes();

    echo '<style>
        .asst-templates-display {
            overflow-x: auto; /* Add horizontal scroll if needed */
        }
        .asst-templates-display table {
            width: 100%;
            border-collapse: collapse;
        }
        .asst-templates-display th, .asst-templates-display td {
            border: 1px solid #ddd;
            padding: 8px;
            padding: 10px !important; /* Adjust cell padding */
            white-space: normal !important; /* Allow cell content to wrap */
            word-break: keep-all !important; /* Keep all words together */
            text-align: center !important; /* Center text-align */
        }
        .asst-templates-display th {
            background-color: #f2f2f2;
        }
    </style>';

    echo '<div class="wrap asst-templates-display">';
    echo '<h1>Manage Assistants</h1>';
    echo '<p>Click the <code>Update</code> button to save changes to an Assistant, or the <code>Delete</code> button to remove an Assistant.</p>';
    echo '<p>Click the <code>Add New Assistant</code> button to create a new Assistant.</p>';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Actions</th>';  // Column header for actions
    echo '<th>&#91;Shortcode&#93;</th>';
    echo '<th>Assistant ID</th>';
    echo '<th>Common Name</th>';
    echo '<th>Style</th>';
    echo '<th>Audience</th>';
    echo '<th>Voice</th>';
    echo '<th>Allow File Uploads</th>';
    echo '<th>Allow Transcript Downloads</th>';
    echo '<th>Show Assistant Name</th>';
    echo '<th>Initial Greeting</th>';
    echo '<th>Subsequent Greeting</th>';
    echo '<th>Placeholder Prompt</th>';
    echo '<th>Additional Instructions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    echo '<script>
    function copyToClipboard(text) {
        const tempInput = document.createElement("input");
        tempInput.style.position = "absolute";
        tempInput.style.left = "-9999px";
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand("copy");
        document.body.removeChild(tempInput);
        alert("Shortcode copied to clipboard: " + text);
    }
    </script>';

    foreach ($assistants as $assistant) {
        echo '<tr>';
        echo '<td>';  // Actions column for each assistant row
        // Update button to trigger the updateAssistant function
        echo '<button class="button-primary" onclick="updateAssistant(' . $assistant->id . ')">Update</button>&nbsp';
        // Delete button to trigger the deleteAssistant function
        echo '<button class="button-primary" onclick="deleteAssistant(' . $assistant->id . ')">Delete</button>';
        echo '</td>';
        echo '<td onclick="copyToClipboard(\'[chatbot-' . $assistant->id . ']\')"><b>' . '&#91;chatbot-' . $assistant->id . '&#93;' . '</b></td>';
        echo '<td><input type="text" name="assistant_id_' . $assistant->id . '" value="' . $assistant->assistant_id . '"></td>';
        echo '<td><input type="text" name="common_name_' . $assistant->id . '" value="' . $assistant->common_name . '"></td>';
        echo '<td><select name="style_' . $assistant->id . '">';
        echo '<option value="embedded"' . ($assistant->style == 'embedded' ? ' selected' : '') . '>Embedded</option>';
        echo '<option value="floating"' . ($assistant->style == 'floating' ? ' selected' : '') . '>Floating</option>';
        echo '</select></td>';
        echo '<td><select name="audience_' . $assistant->id . '">';
        echo '<option value="all"' . ($assistant->audience == 'all' ? ' selected' : '') . '>All</option>';
        echo '<option value="visitors"' . ($assistant->audience == 'visitors' ? ' selected' : '') . '>Visitors</option>';
        echo '<option value="logged-in"' . ($assistant->audience == 'logged-in' ? ' selected' : '') . '>Logged-in</option>';
        echo '</select></td>';
        echo '<td><select name="voice_' . $assistant->id . '">';
        echo '<option value="alloy"' . ($assistant->voice == 'alloy' ? ' selected' : '') . '>Alloy</option>';
        echo '<option value="echo"' . ($assistant->voice == 'echo' ? ' selected' : '') . '>Echo</option>';
        echo '<option value="fable"' . ($assistant->voice == 'fable' ? ' selected' : '') . '>Fable</option>';
        echo '<option value="onyx"' . ($assistant->voice == 'onyx' ? ' selected' : '') . '>Onyx</option>';
        echo '<option value="nova"' . ($assistant->voice == 'nova' ? ' selected' : '') . '>Nova</option>';
        echo '<option value="shimmer"' . ($assistant->voice == 'shimmer' ? ' selected' : '') . '>Shimmer</option>';
        echo '<option value="none"' . ($assistant->voice == 'none' ? ' selected' : '') . '>None</option>';
        echo '</select></td>';
        echo '<td><select name="allow_file_uploads_' . $assistant->id . '">';
        echo '<option value="Yes"' . ($assistant->allow_file_uploads == 'Yes' ? ' selected' : '') . '>Yes</option>';
        echo '<option value="No"' . ($assistant->allow_file_uploads == 'No' ? ' selected' : '') . '>No</option>';
        echo '</select></td>';
        echo '<td><select name="allow_transcript_downloads_' . $assistant->id . '">';
        echo '<option value="Yes"' . ($assistant->allow_transcript_downloads == 'Yes' ? ' selected' : '') . '>Yes</option>';
        echo '<option value="No"' . ($assistant->allow_transcript_downloads == 'No' ? ' selected' : '') . '>No</option>';
        echo '</select></td>';
        echo '<td><select name="show_assistant_name_' . $assistant->id . '">';
        echo '<option value="Yes"' . ($assistant->show_assistant_name == 'Yes' ? ' selected' : '') . '>Yes</option>';
        echo '<option value="No"' . ($assistant->show_assistant_name == 'No' ? ' selected' : '') . '>No</option>';
        echo '</select></td>';
        echo '<td><textarea name="initial_greeting_' . $assistant->id . '">' . $assistant->initial_greeting . '</textarea></td>';
        echo '<td><textarea name="subsequent_greeting_' . $assistant->id . '">' . $assistant->subsequent_greeting . '</textarea></td>';
        echo '<td><textarea name="placeholder_prompt_' . $assistant->id . '">' . $assistant->placeholder_prompt . '</textarea></td>';
        echo '<td><textarea name="additional_instructions_' . $assistant->id . '">' . $assistant->additional_instructions . '</textarea></td>';
        echo '</tr>';
    }

    // Row for adding a new assistant
    echo '<tr>';
    echo '<td><button class="button-primary" onclick="addNewAssistant()">Add New Assistant</button></td>';  // Actions column for adding new assistant
    echo '<td>New</td>';
    echo '<td><input type="text" name="new_assistant_id" placeholder="Please provide the GPT Assistant Id."></td>';
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
    echo '<option value="none">None</option>';
    echo '</select></td>';
    echo '<td><select name="new_allow_file_uploads">';
    echo '<option value="Yes">Yes</option>';
    echo '<option value="No">No</option>';
    echo '</select></td>';
    echo '<td><select name="new_allow_transcript_downloads">';
    echo '<option value="Yes">Yes</option>';
    echo '<option value="No">No</option>';
    echo '</select></td>';
    echo '<td><select name="new_show_assistant_name">';
    echo '<option value="Yes">Yes</option>';
    echo '<option value="No">No</option>';
    echo '</select></td>';
    echo '<td><textarea name="new_initial_greeting" placeholder="Hello! How can I help you today?"></textarea></td>';
    echo '<td><textarea name="new_subsequent_greeting" placeholder="Hello again! How can I help you?"></textarea></td>';
    echo '<td><textarea name="new_placeholder_prompt" placeholder="Enter your question ..."></textarea></td>';
    echo '<td><textarea name="new_additional_instructions" placeholder="Added instructions to assistant if needed ...."></textarea></td>';
    echo '</tr>';
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
}


// Scripts for the chatbot assistants table
function chatbot_chatgpt_assistants_scripts() {

    ?>
    <script type="text/javascript">

        // Function to update an assistant's details
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
                show_assistant_name: document.getElementsByName('show_assistant_name_' + id)[0].value,
                initial_greeting: document.getElementsByName('initial_greeting_' + id)[0].value,
                subsequent_greeting: document.getElementsByName('subsequent_greeting_' + id)[0].value,
                placeholder_prompt: document.getElementsByName('placeholder_prompt_' + id)[0].value,
                additional_instructions: document.getElementsByName('additional_instructions_' + id)[0].value
            };

            // Send the update request via AJAX
            jQuery.post(ajaxurl, data, function(response) {
                alert('Assistant updated successfully!');
                location.reload();  // Reload the page to reflect the deletion
            });
        }

        // Function to delete an assistant
        function deleteAssistant(id) {
            var data = {
                action: 'delete_assistant',
                id: id
            };

            // Send the delete request via AJAX
            jQuery.post(ajaxurl, data, function(response) {
                alert('Assistant deleted successfully!');
                location.reload();  // Reload the page to reflect the deletion
            });
        }

        // Function to add a new assistant
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
                show_assistant_name: document.getElementsByName('new_show_assistant_name')[0].value,
                initial_greeting: document.getElementsByName('new_initial_greeting')[0].value,
                subsequent_greeting: document.getElementsByName('new_subsequent_greeting')[0].value,
                placeholder_prompt: document.getElementsByName('new_placeholder_prompt')[0].value,
                additional_instructions: document.getElementsByName('new_additional_instructions')[0].value
            };

            // Send the add request via AJAX
            jQuery.post(ajaxurl, data, function(response) {
                alert('New assistant added successfully!');
                location.reload();  // Reload the page to reflect the addition
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
    $show_assistant_name = sanitize_text_field($_POST['show_assistant_name']);
    $initial_greeting = sanitize_textarea_field($_POST['initial_greeting']);
    $subsequent_greeting = sanitize_textarea_field($_POST['subsequent_greeting']);
    $placeholder_prompt = sanitize_textarea_field($_POST['placeholder_prompt']);
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
            'show_assistant_name' => $show_assistant_name,
            'initial_greeting' => $initial_greeting,
            'subsequent_greeting' => $subsequent_greeting,
            'placeholder_prompt' => $placeholder_prompt,
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
    $show_assistant_name = sanitize_text_field($_POST['show_assistant_name']);
    $initial_greeting = sanitize_textarea_field($_POST['initial_greeting']);
    $subsequent_greeting = sanitize_textarea_field($_POST['subsequent_greeting']);
    $placeholder_prompt = sanitize_textarea_field($_POST['placeholder_prompt']);
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
            'show_assistant_name' => $show_assistant_name,
            'initial_greeting' => $initial_greeting,
            'subsequent_greeting' => $subsequent_greeting,
            'placeholder_prompt' => $placeholder_prompt,
            'additional_instructions' => $additional_instructions
        )
    );

    wp_die();
}
add_action('wp_ajax_add_new_assistant', 'add_new_assistant');

// Upgrade the old primary and alternate assistant settings to the new chatbot assistants table
function upgrade_chatbot_chatgpt_assistants_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    // Retrieve options from wp_options table
    $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id'), '');
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
    }

    // Delete options from wp_options table
    delete_option('chatbot_chatgpt_assistant_id');
    delete_option('chatbot_chatgpt_assistant_id_alternate');
    delete_option('chatbot_chatgpt_assistant_instructions');
    delete_option('chatbot_chatgpt_assistant_instructions_alternate');
    
}

