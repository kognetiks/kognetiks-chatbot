<?php
/**
 * Chatbot ChatGPT for WordPress - Settings Links
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */


// Add link to chatgtp options
function chatbot_chatgpt_plugin_action_links($links) {
    $settings_link = '<a class="chatbot-settings" href="../wp-admin/options-general.php?page=chatbot-chatgpt">' . __('Settings', 'chatbot-chatgpt') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Add a WordPress action that handles the AJAX request
add_action('wp_ajax_chatbot_chatgpt_create_nonce', 'chatbot_chatgpt_create_nonce');

function chatbot_chatgpt_create_nonce() {
    $nonce = wp_create_nonce('deactivate-plugin_' . $_POST['plugin_file']);
    echo $nonce;
    wp_die();
}


// Add link to chatgtp deactivation
function chatbot_chatgpt_admin_footer() {

    $plugin_file = 'chatbot-chatgpt/chatbot-chatgpt.php';
    $admin_screen = get_current_screen();

    if ($admin_screen->base != 'plugins') return;

    // The action string should be unique for each URL.
    $action = 'deactivate-plugin_' . $plugin_file;

    // This is the bare URL without the nonce.
    $bare_url = admin_url('plugins.php?action=deactivate&plugin=' . urlencode($plugin_file));

    // This is the URL with the nonce added.
    $nonce_url = wp_nonce_url($bare_url, $action);

    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log("Document ready");
            var modal; // declare variable here to make it accessible in the entire document ready function

            $('a.chatbot-settings').click(function(e) {
              console.log("Settings link clicked");
            });

            var data = {
            'action': 'chatbot_chatgpt_deactivation_feedback'
            };

            // Deactivation reason options
            var options = '<div id="deactivation-feedback">' +
            '<h2>Please share why you are deactivating:</h2>' +
            '<div class="reasons">' +
            '<label><input type="radio" name="reason" value="1"> I no longer need the plugin.</label><br>' +
            '<label><input type="radio" name="reason" value="2"> I found a better plugin.</label><br>' +
            '<label><input type="radio" name="reason" value="3"> The plugin broke my site.</label><br>' +
            '<label><input type="radio" name="reason" value="4"> The plugin didn\'t work.</label><br>' +
            '<label><input type="radio" name="reason" value="5"> Other</label><br>' +
            '</div>' +
            '<div class="textarea-wrap">' +
            '<textarea id="other-text" placeholder="Please specify"></textarea>' +
            '</div>' +
            '<h2>Optional: Your Email Address:</h2>' +
            '<input type="email" id="email-text">' +
            '<div class="buttons">' +
            '<button id="submit-deactivation">Submit and Deactivate</button>' +
            '<button id="just-deactivate">Just Deactivate</button>' +
            '<button id="cancel-deactivation">Cancel Deactivation</button>' +
            '</div>' +
            '</div>';

            modal = $('<div id="chatbot-chatgpt-deactivation-modal">' + options + '</div>').dialog({
                autoOpen: false,
                modal: true,
                width: 500
            });

            // Add some styling
            var css = '<style>' +
                '#chatbot-chatgpt-deactivation-modal {' +
                '    font-family: Arial, sans-serif;' +
                '}' +
                '#deactivation-feedback {' +
                '    display: flex;' +
                '    flex-direction: column;' +
                '    gap: 15px;' +
                '}' +
                '.reasons {' +
                '    display: flex;' +
                '    flex-direction: column;' +
                '    gap: 5px;' +
                '}' +
                '.textarea-wrap {' +
                '    display: flex;' +
                '}' +
                '.textarea-wrap textarea {' +
                '    width: 100%;' +
                '    height: 100px;' +
                '    resize: none;' +
                '}' +
                '.buttons {' +
                '    display: flex;' +
                '    gap: 10px;' +
                '}' +
                '.buttons button {' +
                '    padding: 5px 10px;' +
                '    border: none;' +
                '    border-radius: 5px;' +
                '    cursor: pointer;' +
                '}' +
                '</style>';

            $('head').append(css);

            // Add a class to the deactivation link for later use
            $('a[href*="plugins.php?action=deactivate&plugin=chatbot-chatgpt"]').addClass('chatbot-deactivate-link');
          
            // Handle click on the deactivation link
            $('a[href*="plugins.php?action=deactivate&plugin=chatbot-chatgpt"]').click(function(e) {
                e.preventDefault();
                console.log("Deactivation link clicked");
                modal.dialog('open');
            });

            // Handle click on deactivation button
            $(document).on('click', '#submit-deactivation', function() {
                console.log("Submit and Deactivate clicked");
                data.reason = $('input[name="reason"]:checked').val();
                data.other_text = $('#other-text').val();
                data.email = $('#email-text').val();

                $.post(ajaxurl, data, function(response) {
                  $.post(ajaxurl, {
                    action: 'chatbot_chatgpt_create_nonce',
                    plugin_file: 'chatbot-chatgpt/chatbot-chatgpt.php'
                  }, function(response) {
                    var nonce = response;
                    var url = 'plugins.php?action=deactivate&plugin=chatbot-chatgpt/chatbot-chatgpt.php&_wpnonce=' + nonce;
                    location.href = url;
                  });
                });
              });

            $(document).on('click', '#just-deactivate', function() {    
                console.log("Just Deactivate clicked");

                $.post(ajaxurl, {
                  action: 'chatbot_chatgpt_create_nonce',
                  plugin_file: 'chatbot-chatgpt/chatbot-chatgpt.php'
                }, function(response) {
                  var nonce = response;
                  var url = 'plugins.php?action=deactivate&plugin=chatbot-chatgpt/chatbot-chatgpt.php&_wpnonce=' + nonce;
                  location.href = url;
                });
              });

              // Handle click on cancel deactivation button
              $(document).on('click', '#cancel-deactivation', function() {
                  console.log("Cancel Deactivation clicked");
                  // Redirect to the settings page for the plugin after deactivation
                  location.href = 'plugins.php';
              });
          });
    </script>
    <?php
}

function chatbot_chatgpt_deactivation_feedback() {
    // Here you can process the feedback data
    error_log("Ready Ready");
    $reason = $_POST['reason'];
    $other_text = $_POST['other_text'];
    $email = $_POST['email'];

    // Prepare the email
    $to = 'support@kognetiks.com';
    $subject = 'Chatbot ChatGPT Plugin Deactivation Feedback';
    $body = 'Reason for deactivation: ' . $reason . "\n" .
            'Other text: ' . $other_text . "\n" .
            'User email: ' . $email;
    $headers = array('Content-Type: text/plain; charset=UTF-8');

    // Send the email
    if (wp_mail($to, $subject, $body, $headers)) {
        error_log("Email Sent Successfully");
    } else {
        error_log("Email Failed to Send");
    }

    error_log("Email Sent");

    wp_die();
}

