<?php
/**
 * Kognetiks Chatbot - Settings - Reporting
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the reporting settings and other parameters.
 *
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Register Reporting settings - Ver 2.0.7
function chatbot_chatgpt_reporting_settings_init() {

    // Register settings for Reporting
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_reporting_period');
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_enable_conversation_logging');
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_conversation_log_days_to_keep');
    
    // Register settings for Conversation Digest
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_conversation_digest_enabled', 'chatbot_chatgpt_sanitize_conversation_digest_enabled');
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_conversation_digest_frequency', 'chatbot_chatgpt_sanitize_conversation_digest_frequency');
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_conversation_digest_email', 'chatbot_chatgpt_sanitize_conversation_digest_email');
    
    // Register settings for Insights Email
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_insights_email_enabled', 'chatbot_chatgpt_sanitize_insights_email_enabled');
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_insights_email_frequency', 'chatbot_chatgpt_sanitize_insights_email_frequency');
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_insights_email_address', 'chatbot_chatgpt_sanitize_insights_email_address');

    // Reporting Overview Section
    add_settings_section(
        'chatbot_chatgpt_reporting_overview_section',
        'Reporting Overview',
        'chatbot_chatgpt_reporting_overview_section_callback',
        'chatbot_chatgpt_reporting_overview'
    );

    // Reporting Settings Section
    add_settings_section(
        'chatbot_chatgpt_reporting_section',
        '', // Empty title - we'll show it in the callback
        'chatbot_chatgpt_reporting_section_callback',
        'chatbot_chatgpt_reporting'
    );

    // Reporting Settings Field - Reporting Period
    add_settings_field(
        'chatbot_chatgpt_reporting_period',
        'Reporting Period',
        'chatbot_chatgpt_reporting_period_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_reporting_section'
    );

    // Reporting Settings Field - Enable Conversation Logging
    add_settings_field(
        'chatbot_chatgpt_enable_conversation_logging',
        'Enable Conversation Logging',
        'chatbot_chatgpt_enable_conversation_logging_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_reporting_section'
    );

    // Reporting Settings Field - Conversation Log Days to Keep
    add_settings_field(
        'chatbot_chatgpt_conversation_log_days_to_keep',
        'Conversation Log Days to Keep',
        'chatbot_chatgpt_conversation_log_days_to_keep_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_reporting_section'
    );

    // Conversation Digest and Insight Settings Section
    add_settings_section(
        'chatbot_chatgpt_conversation_digest_section',
        'Conversation Digest and Insight Settings',
        'chatbot_chatgpt_conversation_digest_section_callback',
        'chatbot_chatgpt_conversation_digest'
    );

    // Register Conversation Digest settings fields for both free and premium users
    // Conversation Digest Settings Field - Enabled (shown in card, label handled there)
    add_settings_field(
        'chatbot_chatgpt_conversation_digest_enabled',
        '',
        'chatbot_chatgpt_conversation_digest_enabled_callback',
        'chatbot_chatgpt_conversation_digest',
        'chatbot_chatgpt_conversation_digest_section'
    );

    // Conversation Digest Settings Field - Frequency (shown in card, label handled there)
    add_settings_field(
        'chatbot_chatgpt_conversation_digest_frequency',
        '',
        'chatbot_chatgpt_conversation_digest_frequency_callback',
        'chatbot_chatgpt_conversation_digest',
        'chatbot_chatgpt_conversation_digest_section'
    );

    // Conversation Digest Settings Field - Email Address (shown in card, label handled there)
    add_settings_field(
        'chatbot_chatgpt_conversation_digest_email',
        '',
        'chatbot_chatgpt_conversation_digest_email_callback',
        'chatbot_chatgpt_conversation_digest',
        'chatbot_chatgpt_conversation_digest_section'
    );
    
    // Only register premium-only fields if premium is enabled
    // Uses centralized helper function following Freemius best practices
    $is_premium = function_exists('chatbot_chatgpt_is_premium') ? chatbot_chatgpt_is_premium() : false;
    if ($is_premium) {
        
        // Insights Email Settings Field - Enabled (shown in card, label handled there)
        add_settings_field(
            'chatbot_chatgpt_insights_email_enabled',
            '',
            'chatbot_chatgpt_insights_email_enabled_callback',
            'chatbot_chatgpt_conversation_digest',
            'chatbot_chatgpt_conversation_digest_section'
        );
        
        // Insights Email Settings Field - Frequency (shown in card, label handled there)
        add_settings_field(
            'chatbot_chatgpt_insights_email_frequency',
            '',
            'chatbot_chatgpt_insights_email_frequency_callback',
            'chatbot_chatgpt_conversation_digest',
            'chatbot_chatgpt_conversation_digest_section'
        );
        
        // Insights Email Settings Field - Email Address (shown in card, label handled there)
        add_settings_field(
            'chatbot_chatgpt_insights_email_address',
            '',
            'chatbot_chatgpt_insights_email_address_callback',
            'chatbot_chatgpt_conversation_digest',
            'chatbot_chatgpt_conversation_digest_section'
        );
    }

    // Conversation Data Section
    add_settings_section(
        'chatbot_chatgpt_conversation_reporting_section',
        'Conversation Data',
        'chatbot_chatgpt_conversation_reporting_section_callback',
        'chatbot_chatgpt_conversation_reporting'
    );

    add_settings_field(
        'chatbot_chatgpt_conversation_reporting_field',
        'Conversation Data',
        'chatbot_chatgpt_conversation_reporting_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_conversation_reporting_section'
    );

    // Interaction Data Section
    add_settings_section(
        'chatbot_chatgpt_interaction_reporting_section',
        'Interaction Data',
        'chatbot_chatgpt_interaction_reporting_section_callback',
        'chatbot_chatgpt_interaction_reporting'
    );

    add_settings_field(
        'chatbot_chatgpt_interaction_reporting_field',
        'Interaction Data',
        'chatbot_chatgpt_interaction_reporting_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_interaction_reporting_section'
    );

    // // Token Data Section
    add_settings_section(
        'chatbot_chatgpt_token_reporting_section',
        'Token Data',
        'chatbot_chatgpt_token_reporting_section_callback',
        'chatbot_chatgpt_token_reporting'
    );

    add_settings_field(
        'chatbot_chatgpt_token_reporting_field',
        'Token Data',
        'chatbot_chatgpt_token_reporting_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_token_reporting_section'
    );
   
}
add_action('admin_init', 'chatbot_chatgpt_reporting_settings_init');

// Reporting section callback - Ver 1.6.3
function chatbot_chatgpt_reporting_overview_section_callback($args) {
    ?>
    <div>
        <p>Use these setting to select the reporting period for Visitor and User Interactions.</p>
        <p>Please review the section <b>Conversation Logging Overview</b> on the <a href="?page=chatbot-chatgpt&tab=support&dir=support&file=conversation-logging-and-history.md">Support</a> tab of this plugin for more details.</p>
        <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
        <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use the Reporting and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=reporting&file=reporting.md">here</a>.</b></p>
    </div>
    <?php
}

function chatbot_chatgpt_reporting_section_callback($args) {
    ?>
    <div>
        <h3>Reporting Settings</h3>
        <p>Use these settings to select the reporting period for Visitor and User Interactions.</p>
        <p>You will need to Enable Conversation Logging if you want to record chatbot interactions. By default, conversation logging is initially turned <b>Off</b>.</p>
        <p>Conversation Log Days to Keep sets the number of days to keep the conversation log data in the database.</p>
    </div>
    <?php
}

function chatbot_chatgpt_conversation_digest_section_callback($args) {
    // Check if premium is enabled (includes Premium plan check for users who upgraded)
    $is_premium = function_exists('chatbot_chatgpt_is_premium') ? chatbot_chatgpt_is_premium() : false;
    $is_free = !$is_premium;
    
    // Get current values for conditional display
    $digest_enabled = esc_attr(get_option('chatbot_chatgpt_conversation_digest_enabled', 'No'));
    $insights_enabled = esc_attr(get_option('chatbot_chatgpt_insights_email_enabled', 'No'));
    
    // IMPORTANT: Render the fields manually first, then WordPress will also try to render them
    // We need to output the fields here so they're in the form, but we'll hide WordPress's automatic table
    
    if ($is_premium || $is_free) {
        // Show settings for both premium and free (free users can use insights)
        ?>
        <style>
        /* Hide default WordPress settings table for this section - target the table that comes after our cards */
        div:has(.kchat-email-card) + table.form-table,
        div:has(.kchat-email-card) ~ table.form-table {
            display: none !important;
        }
        
        /* Also hide any table rows with empty labels (our fields have empty labels) */
        table.form-table tr th:empty,
        table.form-table tr:has(th:empty) {
            display: none !important;
        }
        
        /* Hide the automatic WordPress table for this specific section */
        #chatbot_chatgpt_conversation_digest_section + table.form-table {
            display: none !important;
        }
        
        .kchat-email-card {
            border: 1px solid #ccd0d4;
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .kchat-email-card h3 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 16px;
            font-weight: 600;
        }
        .kchat-email-card .description {
            color: #646970;
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .kchat-email-card .settings-row {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
            min-height: 32px;
        }
        .kchat-email-card .settings-row label {
            min-width: 200px;
            font-weight: 600;
            padding-top: 5px;
            display: block;
            color: #1e1e1e;
        }
        .kchat-email-card .settings-row .field-wrapper {
            flex: 1;
        }
        .kchat-email-card .settings-row .field-wrapper select,
        .kchat-email-card .settings-row .field-wrapper input[type="email"] {
            width: 100%;
            max-width: 400px;
        }
        .kchat-email-card .test-button-wrapper {
            margin-top: 15px;
            text-align: right;
            padding-top: 10px;
        }
        .kchat-email-card .conditional-field {
            display: none;
        }
        .kchat-email-card.enabled .conditional-field {
            display: flex;
        }
        .kchat-premium-note {
            background-color: #f0f6fc;
            border-left: 4px solid #2271b1;
            padding: 12px;
            margin-top: 15px;
            font-size: 13px;
            color: #1e1e1e;
        }
        </style>
        
        <!-- Card A: Conversation Digest -->
        <div class="kchat-email-card" id="kchat-digest-card" data-enabled="<?php echo esc_attr($digest_enabled); ?>">
            <h3>Conversation Digest</h3>
            <p class="description">Receive periodic summaries of new chatbot conversations. Great for monitoring new activity.</p>
            <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use the Conversation Digest report please click <a href="?page=chatbot-chatgpt&tab=support&dir=analytics-package&file=conversation-digest-email.md">here</a>.</b></p>
            
            <div class="settings-row">
                <label for="chatbot_chatgpt_conversation_digest_enabled">Enabled</label>
                <div class="field-wrapper">
                    <?php chatbot_chatgpt_conversation_digest_enabled_callback([]); ?>
                </div>
            </div>
            
            <div class="settings-row conditional-field" id="digest-frequency-row">
                <label for="chatbot_chatgpt_conversation_digest_frequency">Digest Frequency</label>
                <div class="field-wrapper">
                    <?php chatbot_chatgpt_conversation_digest_frequency_callback([]); ?>
                </div>
            </div>
            
            <div class="settings-row conditional-field" id="digest-email-row">
                <label for="chatbot_chatgpt_conversation_digest_email">Send Reports To</label>
                <div class="field-wrapper">
                    <?php chatbot_chatgpt_conversation_digest_email_callback([]); ?>
                </div>
            </div>
            
            <?php if (is_admin() && current_user_can('manage_options')): ?>
            <div class="test-button-wrapper conditional-field" id="digest-test-row">
                <?php
                $email_address = get_option('chatbot_chatgpt_conversation_digest_email', '');
                $email_display = !empty($email_address) ? $email_address : 'the configured email address';
                $nonce = wp_create_nonce('chatbot_chatgpt_test_conversation_digest');
                ?>
                <button type="button" id="chatbot-test-digest-email-btn" class="button button-secondary" data-nonce="<?php echo esc_attr($nonce); ?>">Test Conversation Digest Report</button>
            </div>
            <?php endif; ?>
            
            <?php if (!$is_premium): ?>
            <div class="kchat-premium-note">
                <strong>Free Reports</strong> are limited to weekly with basic stats (conversation count, pages, visitors/users). <strong>Premium Reports</strong> surface risks sooner and highlight what to fix next.
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Card B: Proof of Value Reports -->
        <div class="kchat-email-card" id="kchat-insights-card" data-enabled="<?php echo esc_attr($insights_enabled); ?>">
            <h3>Proof of Value Reports</h3>
            <p class="description">Automated reports that show chatbot impact, trends, and actionable insights. Designed to help you measure ROI and improve performance.</p>
            <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use the Proof of Value report please click <a href="?page=chatbot-chatgpt&tab=support&dir=analytics-package&file=proof-of-value-reports-email.md">here</a>.</b></p>
            
            <div class="settings-row">
                <label for="chatbot_chatgpt_insights_email_enabled">Enabled</label>
                <div class="field-wrapper">
                    <?php chatbot_chatgpt_insights_email_enabled_callback([]); ?>
                </div>
            </div>
            
            <div class="settings-row conditional-field" id="insights-period-row">
                <label for="chatbot_chatgpt_insights_email_frequency">Report Frequency</label>
                <div class="field-wrapper">
                    <?php chatbot_chatgpt_insights_email_frequency_callback([]); ?>
                </div>
            </div>
            
            <div class="settings-row conditional-field" id="insights-email-row">
                <label for="chatbot_chatgpt_insights_email_address">Send Reports To</label>
                <div class="field-wrapper">
                    <?php chatbot_chatgpt_insights_email_address_callback([]); ?>
                </div>
            </div>
            
            <?php if (is_admin() && current_user_can('manage_options')): ?>
            <div class="test-button-wrapper conditional-field" id="insights-test-row">
                <?php
                $insights_email = get_option('chatbot_chatgpt_insights_email_address', '');
                $insights_email_display = !empty($insights_email) ? $insights_email : get_option('admin_email');
                $insights_nonce = wp_create_nonce('chatbot_chatgpt_test_insights_email');
                ?>
                <button type="button" id="chatbot-test-insights-email-btn" class="button button-secondary" data-nonce="<?php echo esc_attr($insights_nonce); ?>">Test Proof of Value Report</button>
            </div>
            <?php endif; ?>
            
            <?php if ($is_free): ?>
            <div class="kchat-premium-note">
                <strong>Free Reports</strong> include basic activity stats. <strong>Premium Reports</strong> adds top unanswered questions, top pages, and recommended next steps.
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Card C: Upgrade CTA (Free users only) -->
        <?php if ($is_free): ?>
        <div class="kchat-email-card" id="kchat-upgrade-cta-card">
            <div class="kchat-upgrade-cta" style="text-align: left;">
                <p style="margin: 0 0 15px 0; font-size: 15px; font-weight: 600; color: #1e1e1e;">
                    <span style="color: #00a32a; margin-right: 5px;">✓</span> Ready to Upgrade?
                </p>
                <?php
                // Trial-first CTA with safety guards
                if (function_exists('chatbot_chatgpt_freemius')) {
                    $fs = chatbot_chatgpt_freemius();
                    if (is_object($fs)) {
                        // Primary: Start Free Trial button
                        if (method_exists($fs, 'get_upgrade_url')) {
                            // Use monthly billing cycle for trial
                            $trial_url = $fs->get_upgrade_url( WP_FS__PERIOD_MONTHLY, true );
                            ?>
                            <a href="<?php echo esc_url($trial_url); ?>" class="button button-primary" style="background-color: #f56e28; border-color: #f56e28; color: #fff; text-decoration: none; padding: 8px 16px; font-size: 14px; font-weight: 600; display: inline-block; margin-bottom: 10px; margin-right: 10px;">
                                Start Free Trial
                            </a>
                            <?php
                        }
                        // Secondary: View Plans link
                        if (method_exists($fs, 'get_upgrade_url')) {
                            $upgrade_url = $fs->get_upgrade_url();
                            ?>
                            <a href="<?php echo esc_url($upgrade_url); ?>" class="button button-secondary" style="text-decoration: none; padding: 8px 16px; font-size: 14px; display: inline-block; margin-bottom: 10px;">
                                View Plans
                            </a>
                            <?php
                        }
                    }
                }
                ?>
                <div style="margin-top: 10px;">
                    <a href="?page=chatbot-chatgpt&tab=support&dir=analytics-package&file=proof-of-value-reports-email.md" style="color: #2271b1; text-decoration: underline; margin-right: 15px;">Learn more</a>
                    <a href="mailto:support@kognetiks.com" style="color: #2271b1; text-decoration: underline;">Contact Support</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php
        // Prepare variables for JavaScript (outside conditional blocks)
        $insights_email_js = get_option('chatbot_chatgpt_insights_email_address', '');
        $insights_email_display_js = !empty($insights_email_js) ? $insights_email_js : get_option('admin_email');
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Hide default WordPress settings table that appears after our cards
            // WordPress automatically renders fields after the section callback, but we've already rendered them manually
            // So we need to hide the automatic table to avoid duplicate fields
            $('.kchat-email-card').closest('div').next('table.form-table').hide();
            $('.kchat-email-card').closest('div').siblings('table.form-table').hide();
            
            // Also hide any table rows with empty th elements (our fields have empty labels)
            $('table.form-table tr').each(function() {
                var $th = $(this).find('th');
                if ($th.length && ($th.text().trim() === '' || $th.html().trim() === '')) {
                    $(this).hide();
                }
            });
            
            // Remove any duplicate fields that WordPress might have rendered automatically
            // Keep only the ones in our custom cards
            $('.kchat-email-card select[name], .kchat-email-card input[name]').each(function() {
                var fieldName = $(this).attr('name');
                var $duplicates = $('table.form-table select[name="' + fieldName + '"], table.form-table input[name="' + fieldName + '"]').not($(this));
                $duplicates.closest('tr').remove();
            });
            
            // Function to toggle conditional fields
            function toggleConditionalFields(cardId, enabled) {
                var card = $('#' + cardId);
                if (enabled === 'Yes') {
                    card.addClass('enabled');
                } else {
                    card.removeClass('enabled');
                }
            }
            
            // Initialize on page load
            toggleConditionalFields('kchat-digest-card', '<?php echo esc_js($digest_enabled); ?>');
            toggleConditionalFields('kchat-insights-card', '<?php echo esc_js($insights_enabled); ?>');
            
            // Watch for changes to digest enabled
            $('#chatbot_chatgpt_conversation_digest_enabled').on('change', function() {
                toggleConditionalFields('kchat-digest-card', $(this).val());
            });
            
            // Watch for changes to insights enabled
            $('#chatbot_chatgpt_insights_email_enabled').on('change', function() {
                toggleConditionalFields('kchat-insights-card', $(this).val());
            });
            
            // Test Conversation Digest Email
            $('#chatbot-test-digest-email-btn').on('click', function(e) {
                e.preventDefault();
                var email = '<?php echo esc_js($email_display); ?>';
                if (!confirm('Send a test conversation digest email to ' + email + '?')) {
                    return false;
                }
                var btn = $(this);
                var originalText = btn.text();
                btn.prop('disabled', true).text('Sending...');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'chatbot_chatgpt_test_conversation_digest',
                        nonce: btn.data('nonce')
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Test email sent successfully!');
                            // Set flag to prevent beforeunload warning on programmatic reload
                            window.programmaticReload = true;
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data || 'Failed to send test email'));
                            btn.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function() {
                        alert('Error: Failed to send test email. Please try again.');
                        btn.prop('disabled', false).text(originalText);
                    }
                });
            });
            
            // Test Insights Email
            $('#chatbot-test-insights-email-btn').on('click', function(e) {
                e.preventDefault();
                var email = '<?php echo esc_js($insights_email_display_js); ?>';
                if (!confirm('Send a test proof of value report to ' + email + '?')) {
                    return false;
                }
                var btn = $(this);
                var originalText = btn.text();
                btn.prop('disabled', true).text('Sending...');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'chatbot_chatgpt_test_insights_email',
                        nonce: btn.data('nonce')
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Test report sent successfully!');
                            // Set flag to prevent beforeunload warning on programmatic reload
                            window.programmaticReload = true;
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data || 'Failed to send test email'));
                            btn.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function() {
                        alert('Error: Failed to send test email. Please try again.');
                        btn.prop('disabled', false).text(originalText);
                    }
                });
            });
            
        });
        </script>
        <?php
    }
}

function chatbot_chatgpt_conversation_reporting_section_callback($args) {
    ?>
    <div>
        <p>Conversation items stored in your DB total <b><?php echo chatbot_chatgpt_count_conversations(); ?></b> rows (includes both Visitor and User input and chatbot responses).</p>
        <p>Conversation items stored take up <b><?php echo chatbot_chatgpt_size_conversations(); ?> MB</b> in your database.</p>
        <p>Use the button (below) to retrieve the conversation data and download as a CSV file.</p>
        <?php
            if (is_admin()) {
                $header = " ";
                $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=chatbot_chatgpt_download_conversation_data')) . '">Download Conversation Data</a>';
                echo $header;
            }
        ?>
    </div>
    <?php
}

function chatbot_chatgpt_interaction_reporting_section_callback($args) {
    ?>
    <div>
        <!-- TEMPORARILY REMOVED AS SOME USERS ARE EXPERIENCING ISSUES WITH THE CHARTS - Ver 1.7.8 -->
        <!-- <p><?php echo do_shortcode('[chatbot_simple_chart from_database="true"]'); ?></p> -->
        <p><?php echo chatbot_chatgpt_interactions_table() ?></p>
        <p>Use the button (below) to retrieve the interactions data and download as a CSV file.</p>
        <?php
            if (is_admin()) {
                $header = " ";
                $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=chatbot_chatgpt_download_interactions_data')) . '">Download Interaction Data</a>';
                echo $header;
            }
        ?>
    </div>
    <?php
}

function chatbot_chatgpt_token_reporting_section_callback($args) {
    ?>
    <div>
        <p><?php echo chatbot_chatgpt_total_tokens() ?></p>
        <p>Use the button (below) to retrieve the interactions data and download as a CSV file.</p>
        <?php
            if (is_admin()) {
                $header = " ";
                $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=chatbot_chatgpt_download_token_usage_data')) . '">Download Token Usage Data</a>';
                echo $header;
            }
        ?>
    </div>
    <?php
}

function chatbot_chatgpt_reporting_settings_callback($args){
    ?>
    <div>
        <h3>Reporting Settings</h3>
    </div>
    <?php
}

// Knowledge Navigator Analysis section callback - Ver 1.6.2
function chatbot_chatgpt_reporting_period_callback($args) {
    // Get the saved chatbot_chatgpt_reporting_period value or default to "Daily"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_reporting_period', 'Daily'));
    // DIAG - Log the output choice
    ?>
    <select id="chatbot_chatgpt_reporting_period" name="chatbot_chatgpt_reporting_period">
        <option value="<?php echo esc_attr( 'Daily' ); ?>" <?php selected( $output_choice, 'Daily' ); ?>><?php echo esc_html( 'Daily' ); ?></option>
        <!-- <option value="<?php echo esc_attr( 'Weekly' ); ?>" <?php selected( $output_choice, 'Weekly' ); ?>><?php echo esc_html( 'Weekly' ); ?></option> -->
        <option value="<?php echo esc_attr( 'Monthly' ); ?>" <?php selected( $output_choice, 'Monthly' ); ?>><?php echo esc_html( 'Monthly' ); ?></option>
        <option value="<?php echo esc_attr( 'Yearly' ); ?>" <?php selected( $output_choice, 'Yearly' ); ?>><?php echo esc_html( 'Yearly' ); ?></option>
    </select>
    <?php
}

// Conversation Logging - Ver 1.7.6
function  chatbot_chatgpt_enable_conversation_logging_callback($args) {
    // Get the saved chatbot_chatgpt_enable_conversation_logging value or default to "Off"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_enable_conversation_logging', 'Off'));
    // DIAG - Log the output choice
    ?>
    <select id="chatbot_chatgpt_enable_conversation_logging" name="chatbot_chatgpt_enable_conversation_logging">
        <option value="<?php echo esc_attr( 'On' ); ?>" <?php selected( $output_choice, 'On' ); ?>><?php echo esc_html( 'On' ); ?></option>
        <option value="<?php echo esc_attr( 'Off' ); ?>" <?php selected( $output_choice, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
    </select>
    <?php
}

// Conversation log retention period - Ver 1.7.6
function chatbot_chatgpt_conversation_log_days_to_keep_callback($args) {
    // Get the saved chatbot_chatgpt_conversation_log_days_to_keep value or default to "30"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_conversation_log_days_to_keep', '30'));
    // DIAG - Log the output choice
    ?>
    <select id="chatbot_chatgpt_conversation_log_days_to_keep" name="chatbot_chatgpt_conversation_log_days_to_keep">
        <option value="<?php echo esc_attr( '1' ); ?>" <?php selected( $output_choice, '7' ); ?>><?php echo esc_html( '1' ); ?></option>
        <option value="<?php echo esc_attr( '7' ); ?>" <?php selected( $output_choice, '7' ); ?>><?php echo esc_html( '7' ); ?></option>
        <option value="<?php echo esc_attr( '30' ); ?>" <?php selected( $output_choice, '30' ); ?>><?php echo esc_html( '30' ); ?></option>
        <option value="<?php echo esc_attr( '60' ); ?>" <?php selected( $output_choice, '60' ); ?>><?php echo esc_html( '60' ); ?></option>
        <option value="<?php echo esc_attr( '90' ); ?>" <?php selected( $output_choice, '90' ); ?>><?php echo esc_html( '90' ); ?></option>
        <option value="<?php echo esc_attr( '180' ); ?>" <?php selected( $output_choice, '180' ); ?>><?php echo esc_html( '180' ); ?></option>
        <option value="<?php echo esc_attr( '365' ); ?>" <?php selected( $output_choice, '365' ); ?>><?php echo esc_html( '365' ); ?></option>
    </select>
    <?php
}

// Conversation Digest Enabled - Ver 2.3.9
function chatbot_chatgpt_conversation_digest_enabled_callback($args) {
    // Get the saved chatbot_chatgpt_conversation_digest_enabled value or default to "No"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_conversation_digest_enabled', 'No'));
    ?>
    <select id="chatbot_chatgpt_conversation_digest_enabled" name="chatbot_chatgpt_conversation_digest_enabled">
        <option value="<?php echo esc_attr( 'Yes' ); ?>" <?php selected( $output_choice, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="<?php echo esc_attr( 'No' ); ?>" <?php selected( $output_choice, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php
}

// Conversation Digest Frequency - Ver 2.3.9
function chatbot_chatgpt_conversation_digest_frequency_callback($args) {
    // Check if premium is enabled (includes Premium plan check for users who upgraded)
    $is_premium = function_exists('chatbot_chatgpt_is_premium') ? chatbot_chatgpt_is_premium() : false;
    
    // Get the saved chatbot_chatgpt_conversation_digest_frequency value (stored as lowercase)
    // Note: Do NOT modify the saved value here - only display it
    // Validation and resetting should happen in the sanitization callback
    $saved_choice = strtolower(esc_attr(get_option('chatbot_chatgpt_conversation_digest_frequency', 'weekly')));
    
    // Free users can only use Weekly - but don't reset here, let sanitization handle it
    if (!$is_premium) {
        // For free users, always show Weekly (but don't modify the DB value in the callback)
        $saved_choice = 'weekly';
        ?>
        <select id="chatbot_chatgpt_conversation_digest_frequency" disabled>
            <option value="<?php echo esc_attr( 'weekly' ); ?>" selected><?php echo esc_html( 'Weekly' ); ?> (Free)</option>
        </select>
        <input type="hidden" name="chatbot_chatgpt_conversation_digest_frequency" value="weekly" />
        <p class="description" style="margin-top: 5px; color: #646970;">Daily and Hourly frequencies available with Premium.</p>
        <?php
    } else {
        // Premium users get all options - display proper case but store lowercase
        ?>
        <select id="chatbot_chatgpt_conversation_digest_frequency" name="chatbot_chatgpt_conversation_digest_frequency">
            <option value="<?php echo esc_attr( 'hourly' ); ?>" <?php selected( $saved_choice, 'hourly' ); ?>><?php echo esc_html( 'Hourly' ); ?></option>
            <option value="<?php echo esc_attr( 'daily' ); ?>" <?php selected( $saved_choice, 'daily' ); ?>><?php echo esc_html( 'Daily' ); ?></option>
            <option value="<?php echo esc_attr( 'weekly' ); ?>" <?php selected( $saved_choice, 'weekly' ); ?>><?php echo esc_html( 'Weekly' ); ?></option>
        </select>
        <?php
    }
}

// Conversation Digest Email Address - Ver 2.3.9
function chatbot_chatgpt_conversation_digest_email_callback($args) {
    // Get the saved chatbot_chatgpt_conversation_digest_email value or default to empty
    $email_value = esc_attr(get_option('chatbot_chatgpt_conversation_digest_email', ''));
    ?>
    <input type="email" id="chatbot_chatgpt_conversation_digest_email" name="chatbot_chatgpt_conversation_digest_email" value="<?php echo esc_attr($email_value); ?>" class="regular-text" />
    <p class="description">Enter the email address where proof of value reports should be sent.</br>NOTE: Remember to 'Save Changes' after updating the email address.</p>
    <?php
}

// Insights Email Enabled (Proof of Value Reports)
function chatbot_chatgpt_insights_email_enabled_callback($args) {
    // Get the saved chatbot_chatgpt_insights_email_enabled value or default to "No"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_insights_email_enabled', 'No'));
    ?>
    <select id="chatbot_chatgpt_insights_email_enabled" name="chatbot_chatgpt_insights_email_enabled">
        <option value="<?php echo esc_attr( 'Yes' ); ?>" <?php selected( $output_choice, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="<?php echo esc_attr( 'No' ); ?>" <?php selected( $output_choice, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php
}

// Insights Email Frequency (Report Frequency)
function chatbot_chatgpt_insights_email_frequency_callback($args) {
    // Check if premium is enabled (includes Premium plan check for users who upgraded)
    $is_premium = function_exists('chatbot_chatgpt_is_premium') ? chatbot_chatgpt_is_premium() : false;
    
    // Get the saved chatbot_chatgpt_insights_email_frequency value (stored as lowercase)
    // Note: Do NOT modify the saved value here - only display it
    // Validation and resetting should happen in the sanitization callback
    $saved_choice = strtolower(esc_attr(get_option('chatbot_chatgpt_insights_email_frequency', 'weekly')));
    
    // Free users can only use Weekly - but don't reset here, let sanitization handle it
    if (!$is_premium) {
        // For free users, always show Weekly (but don't modify the DB value in the callback)
        $saved_choice = 'weekly';
        ?>
        <select id="chatbot_chatgpt_insights_email_frequency" disabled>
            <option value="<?php echo esc_attr( 'weekly' ); ?>" selected><?php echo esc_html( 'Weekly' ); ?> (Free)</option>
        </select>
        <input type="hidden" name="chatbot_chatgpt_insights_email_frequency" value="weekly" />
        <p class="description" style="margin-top: 5px; color: #646970;">Daily and Monthly frequencies available with Premium.</p>
        <?php
    } else {
        // Premium users get all options
        ?>
        <select id="chatbot_chatgpt_insights_email_frequency" name="chatbot_chatgpt_insights_email_frequency">
            <option value="<?php echo esc_attr( 'daily' ); ?>" <?php selected( $saved_choice, 'daily' ); ?>><?php echo esc_html( 'Daily' ); ?></option>
            <option value="<?php echo esc_attr( 'weekly' ); ?>" <?php selected( $saved_choice, 'weekly' ); ?>><?php echo esc_html( 'Weekly' ); ?></option>
            <option value="<?php echo esc_attr( 'monthly' ); ?>" <?php selected( $saved_choice, 'monthly' ); ?>><?php echo esc_html( 'Monthly' ); ?></option>
        </select>
        <?php
    }
}

// Insights Email Address (Send Reports To)
function chatbot_chatgpt_insights_email_address_callback($args) {
    // Get the saved chatbot_chatgpt_insights_email_address value or default to empty
    $email_value = esc_attr(get_option('chatbot_chatgpt_insights_email_address', ''));
    ?>
    <input type="email" id="chatbot_chatgpt_insights_email_address" name="chatbot_chatgpt_insights_email_address" value="<?php echo esc_attr($email_value); ?>" class="regular-text" />
    <p class="description">Enter the email address where proof of value reports should be sent.</br>NOTE: Remember to 'Save Changes' after updating the email address.</p>
    <?php
}

// Chatbot Simple Chart - Ver 1.6.3
function generate_gd_bar_chart($labels, $data, $colors, $name) {
    // Create an image
    $width = 500;
    $height = 300;
    $image = imagecreatetruecolor($width, $height);

    // Allocate colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $light_blue = imagecolorallocate($image, 173, 216, 230); // Light Blue color

    // Fill the background
    imagefill($image, 0, 0, $white);

    // Add title
    $title = "Visitor Interactions";
    $font = 5;
    $title_x = ($width - imagefontwidth($font) * strlen($title)) / 2;
    $title_y = 5;
    imagestring($image, $font, $title_x, $title_y, $title, $black);

    // Calculate number of bars and bar width
    $bar_count = count($data);
    // $bar_width = (int)($width / ($bar_count * 2));
    $bar_width = round($width / ($bar_count * 2));

    // Offset for the chart
    $offset_x = 25;
    $offset_y = 25;
    $top_padding = 5;

    // Bottom line
    imageline($image, 0, $height - $offset_y, $width, $height - $offset_y, $black);

    // Font size for data and labels
    $font_size = 8;

    // Draw bars
    $chart_title_height = 30; // adjust this to the height of your chart title
    for ($i = 0; $i < $bar_count; $i++) {
        $bar_height = (int)(($data[$i] * ($height - $offset_y - $top_padding - $chart_title_height)) / max($data));
        $x1 = $i * $bar_width * 2 + $offset_x;
        $y1 = $height - $bar_height - $offset_y + $top_padding;
        $x2 = ($i * $bar_width * 2) + $bar_width + $offset_x;
        $y2 = $height - $offset_y;

        // Draw a bar
        imagefilledrectangle($image, $x1, $y1, $x2, $y2, $light_blue);

        // Draw data and labels
        $center_x = $x1 + ($bar_width / 2);
        $data_value_x = $center_x - (imagefontwidth($font_size) * strlen($data[$i]) / 2);
        $data_value_y = $y1 - 15;
        $data_value_y = max($data_value_y, 0);

        // Draw a bar
        imagefilledrectangle($image, $x1, $y1, $x2, $y2, $light_blue);

        // Draw data and labels
        $center_x = round($x1 + ($bar_width / 2));

        $data_value_x = $center_x - (imagefontwidth(round($font_size)) * strlen($data[$i]) / 2);
        $label_x = $center_x - (imagefontwidth(round($font_size)) * strlen($labels[$i]) / 2);

        $data_value_y = $y1 - 5; // Moves the counts up or down
        $data_value_y = max($data_value_y, 0);

        // Fix: Explicitly cast to int
        $data_value_x = (int)($data_value_x);
        $data_value_y = (int)($data_value_y);

        // https://fonts.google.com/specimen/Roboto - Ver 1.6.7
        $fontFile = plugin_dir_path(__FILE__) . 'assets/fonts/roboto/Roboto-Black.ttf';

        imagettftext($image, $font_size, 0, $data_value_x, $data_value_y, $black, $fontFile, $data[$i]);

        $label_x = $center_x - ($font_size * strlen($labels[$i]) / 2) + 7; // Moves the dates left or right
        $label_y = $height - $offset_y + 15; // Moves the dates up or down

        imagettftext($image, $font_size, 0, $label_x, $label_y, $black, $fontFile, $labels[$i]);

    }

    // Save the image
    $img_path = plugin_dir_path(__FILE__) . 'assets/images/' . $name . '.png';
    imagepng($image, $img_path);

    // Free memory
    imagedestroy($image);

    return $img_path;
}


// Chatbot Charts - Ver 1.6.3
function chatbot_chatgpt_simple_chart_shortcode_function( $atts ) {

    // Check is GD Library is installed - Ver 1.6.3
    if (!extension_loaded('gd')) {
        // GD Library is installed and loaded
        // DIAG - Log the output choice
        chatbot_chatgpt_general_admin_notice('Chatbot requires the GD Library to function correctly, but it is not installed or enabled on your server. Please install or enable the GD Library.');
        // DIAG - Log the output choice
        // Disable the shortcode functionality
        return;
    }

    // Retrieve the reporting period
    $reporting_period = esc_attr(get_option('chatbot_chatgpt_reporting_period'));

    // Parsing shortcode attributes
    $a = shortcode_atts( array(
        'name' => 'visitorsChart_' . rand(100, 999),
        'type' => 'bar',
        'labels' => 'label',
        ), $atts );

    if(isset($atts['from_database']) && $atts['from_database'] == 'true') {

        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_chatgpt_interactions';
        
        // Get the reporting period from the options
        $reporting_period = gesc_attr(et_option('chatbot_chatgpt_reporting_period'));
        
        // Calculate the start date and group by clause based on the reporting period
        if($reporting_period === 'Daily') {
            $start_date = date('Y-m-d', strtotime("-7 days"));
            // $group_by = "DATE_FORMAT(date, '%Y-%m-%d')";
            $group_by = "DATE_FORMAT(date, '%m-%d')";
        } elseif($reporting_period === 'Monthly') {
            $start_date = date('Y-m-01', strtotime("-3 months"));
            $group_by = "DATE_FORMAT(date, '%Y-%m')";
        } else {
            $start_date = date('Y-01-01', strtotime("-3 years"));
            $group_by = "DATE_FORMAT(date, '%Y')";
        }
        
        // Modify the SQL query to group the results based on the reporting period
        $results = $wpdb->get_results("SELECT $group_by AS date, SUM(count) AS count FROM $table_name WHERE date >= '$start_date' GROUP BY $group_by");

        if(!empty($wpdb->last_error)) {
            // DIAG - Handle the error
            return;
        } else if(!empty($results)) {
            $labels = [];
            $data = [];
            foreach ($results as $result) {
                $labels[] = $result->date;
                $data[] = $result->count;
            }
            
            $a['labels'] = $labels;
            $atts['data'] = $data;
        }
    }

    if (empty( $a['labels']) || empty($atts['data'])) {
        // return '<p>You need to specify both the labels and data for the chart to work.</p>';
        return '<p>No data to chart at this time. Plesae visit again later.</p>';
    }

    // Generate the chart
    $img_path = generate_gd_bar_chart($a['labels'], $atts['data'], $atts['color'] ?? null, $a['name']);
    $img_url = plugin_dir_url(__FILE__) . 'assets/images/' . $a['name'] . '.png';

    wp_schedule_single_event(time() + 60, 'chatbot_chatgpt_delete_chart', array($img_path)); // 60 seconds delay

    return '<img src="' . $img_url . '" alt="Bar Chart">';
}
// TEMPORARILY REMOVED AS SOME USERS ARE EXPERIENCING ISSUES WITH THE CHARTS - Ver 1.7.8
// Add shortcode
// add_shortcode('chatbot_chatgpt_simple_chart', 'chatbot_chatgpt_simple_chart_shortcode_function');
// add_shortcode('chatbot_simple_chart', 'chatbot_chatgpt_simple_chart_shortcode_function');


// Clean up ../image subdirectory - Ver 1.6.3
function chatbot_chatgpt_delete_chart() {
    $img_dir_path = plugin_dir_path(__FILE__) . 'assets/images/'; // Replace with your actual directory path
    $png_files = glob($img_dir_path . '*.png'); // Search for .png files in the directory

    foreach ($png_files as $png_file) {
        unlink($png_file); // Delete each .png file
    }
}
add_action('chatbot_chatgpt_delete_chart', 'chatbot_chatgpt_delete_chart');

// Return Interactions data in a table - Ver 1.7.8
function chatbot_chatgpt_interactions_table() {

    global $wpdb;

    // Use conversation_log table for consistency with dashboard widget
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        return '<p>No data to report at this time. Please visit again later.</p>';
    }

    // Get the reporting period from the options
    $reporting_period = esc_attr(get_option('chatbot_chatgpt_reporting_period'));
    
    // Calculate the start date and group by clause based on the reporting period
    if($reporting_period === 'Daily') {
        $start_date = date('Y-m-d H:i:s', strtotime("-7 days"));
        $group_by = "DATE_FORMAT(interaction_time, '%%m-%%d')";
        $order_by = "MIN(interaction_time) ASC";
    } elseif($reporting_period === 'Monthly') {
        $start_date = date('Y-m-01 00:00:00', strtotime("-3 months"));
        $group_by = "DATE_FORMAT(interaction_time, '%%Y-%%m')";
        $order_by = "DATE_FORMAT(interaction_time, '%%Y-%%m') ASC";
    } else {
        $start_date = date('Y-01-01 00:00:00', strtotime("-3 years"));
        $group_by = "DATE_FORMAT(interaction_time, '%%Y')";
        $order_by = "DATE_FORMAT(interaction_time, '%%Y') ASC";
    }
    
    // Query conversation_log table similar to dashboard widget
    // Count interactions by dividing by 2 (each interaction has Visitor + Chatbot messages)
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            $group_by AS date, 
            COUNT(*) / 2 AS count 
        FROM $table_name 
        WHERE interaction_time >= %s 
        AND user_type IN ('Chatbot', 'Visitor')
        GROUP BY $group_by
        ORDER BY $order_by",
        $start_date
    ));

    if(!empty($wpdb->last_error)) {
        // DIAG - Handle the error
        return '<p>Error retrieving interaction data. Please try again later.</p>';
    } else if(!empty($results)) {
        $labels = [];
        $data = [];
        foreach ($results as $result) {
            $labels[] = $result->date;
            $data[] = $result->count;
        }
        
        $a['labels'] = $labels;
        $atts['data'] = $data;

        $output = '<table class="widefat striped" style="table-layout: fixed; width: auto;">';
        $output .= '<thead><tr><th style="width: 96px;">Date</th><th style="width: 96px;">Count</th></tr></thead>';
        $output .= '<tbody>';
        foreach ($results as $result) {
            $output .= '<tr>';
            $output .= '<td style="width: 96px;">' . esc_html($result->date) . '</td>';
            $output .= '<td style="width: 96px;">' . esc_html(number_format($result->count, 0)) . '</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table>';            

        return $output;

    } else {
        return '<p>No data to report at this time. Please visit again later.</p>';
    }

}

// Count the number of conversations stored - Ver 1.7.6
function chatbot_chatgpt_count_conversations() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    $results = $wpdb->get_results("SELECT COUNT(id) AS count FROM $table_name");
    // TODO - Handle errors
    return $results[0]->count;

}

// Calculated size of the conversations stored - Ver 1.7.6
function chatbot_chatgpt_size_conversations() {

    global $wpdb;

    // Use the DB_NAME constant instead of directly accessing the protected property
    $database_name = DB_NAME;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    // Prepare the SQL query
    $query = $wpdb->prepare("
        SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS `Size_in_MB`
        FROM information_schema.TABLES
        WHERE table_schema = %s
          AND table_name = %s
    ", $database_name, $table_name);

    // Execute the query
    $results = $wpdb->get_results($query);

    // Handle errors
    if (is_wp_error($results)) {
        return 'Error: ' . $results->get_error_message();
    }

    // Check if results are returned
    if (empty($results)) {
        return 'No results found';
    }

    // Return the size in MB
    return $results[0]->Size_in_MB;

}

// Total Prompt Tokens, Completion Tokens, and Total Tokens - Ver 1.8.5
function chatbot_chatgpt_total_tokens() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        return '<p>No data to report at this time. Please visit again later.</p>';
    }
    
    // Get the reporting period from the options
    $reporting_period = esc_attr(get_option('chatbot_chatgpt_reporting_period'));
    
    // Calculate the start date and group by clause based on the reporting period
    if ($reporting_period === 'Daily') {
        $start_date = date('Y-m-d H:i:s', strtotime("-7 days"));
        $group_by = "DATE_FORMAT(interaction_time, '%%m-%%d')";
        $order_by = "MIN(interaction_time) ASC";
    } elseif ($reporting_period === 'Monthly') {
        $start_date = date('Y-m-01 00:00:00', strtotime("-3 months"));
        $group_by = "DATE_FORMAT(interaction_time, '%%Y-%%m')";
        $order_by = "DATE_FORMAT(interaction_time, '%%Y-%%m') ASC";
    } else {
        $start_date = date('Y-01-01 00:00:00', strtotime("-3 years"));
        $group_by = "DATE_FORMAT(interaction_time, '%%Y')";
        $order_by = "DATE_FORMAT(interaction_time, '%%Y') ASC";
    }
    
    // Use prepared statement for security and proper date comparison
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            $group_by AS interaction_time, 
            SUM(CASE WHEN user_type = 'Total Tokens' THEN CAST(message_text AS UNSIGNED) ELSE 0 END) AS count 
        FROM $table_name 
        WHERE interaction_time >= %s 
        GROUP BY $group_by
        ORDER BY $order_by",
        $start_date
    ));
    
    if (!empty($wpdb->last_error)) {
        // Handle the error
        return '<p>Error retrieving data: ' . esc_html($wpdb->last_error) . '</p>';
    } else if (!empty($results)) {
        $labels = [];
        $data = [];
        foreach ($results as $result) {
            $labels[] = $result->interaction_time;
            $data[] = $result->count;
        }
        
        $output = '<table class="widefat striped" style="table-layout: fixed; width: auto;">';
        $output .= '<thead><tr><th>Date</th><th>Total Tokens</th></tr></thead>';
        $output .= '<tbody>';
        foreach ($results as $result) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html($result->interaction_time) . '</td>';
            $output .= '<td>' . number_format($result->count) . '</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table>';
    
        return $output;
    } else {
        return '<p>No data to report at this time. Please visit again later.</p>';
    }
    

}

function chatbot_chatgpt_download_interactions_data() {

    // Export data from the chatbot_chatgpt_interactions table to a csv file
    chatbot_chatgpt_export_data('chatbot_chatgpt_interactions', 'Chatbot-ChatGPT-Interactions');

}

function chatbot_chatgpt_download_conversation_data() {

    // Export data from the chatbot_chatgpt_conversation_log table to a csv file
    chatbot_chatgpt_export_data('chatbot_chatgpt_conversation_log', 'Chatbot-ChatGPT-Conversation Logs');
    
}

function chatbot_chatgpt_download_token_usage_data() {

    // Export data from the chatbot_chatgpt_conversation_log table to a csv file
    chatbot_chatgpt_export_data('chatbot_chatgpt_conversation_log', 'Chatbot-ChatGPT-Token Usage');

}

// Download the conversation data - Ver 1.7.6
function chatbot_chatgpt_export_data( $t_table_name, $t_file_name ) {

    global $chatbot_chatgpt_plugin_dir_path;

    // Export data from the chatbot_chatgpt_conversation_log table to a csv file
    global $wpdb;
    $table_name = $wpdb->prefix . $t_table_name;

    if ( $t_file_name === 'Chatbot-ChatGPT-Token Usage' ) {
        $results = $wpdb->get_results("SELECT id, session_id, user_id, interaction_time, user_type, message_text FROM $table_name WHERE user_type IN ('Prompt Tokens', 'Completion Tokens', 'Total Tokens')", ARRAY_A);
    } else {
        $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
    }

    // Check for empty results
    if (empty($results)) {
        $message = __( 'No data in the file. Please enable conversation and interaction logging if currently off.', 'chatbot-chatgpt' );
        set_transient('chatbot_chatgpt_admin_error', $message, 60); // Expires in 60 seconds
        wp_safe_redirect(admin_url('options-general.php?page=chatbot-chatgpt&tab=reporting')); // Redirect to your settings page
        exit;
    }

    // Check for errors
    if (!empty($wpdb->last_error)) {
        $message = __( 'Error reading table: ' . $wpdb->last_error, 'chatbot-chatgpt' );
        set_transient('chatbot_chatgpt_admin_error', $message, 60); // Expires in 60 seconds
        wp_safe_redirect(admin_url('options-general.php?page=chatbot-chatgpt&tab=reporting')); // Redirect to your settings page
        exit;
    }

    // Ask user where to save the file
    $filename = $t_file_name . '-' . date('Y-m-d') . '.csv';
    // Replace spaces with - in the filename
    $filename = str_replace(' ', '-', $filename);
    $results_dir_path = $chatbot_chatgpt_plugin_dir_path . 'results/';

    // Ensure the directory exists or attempt to create it
    if (!create_directory_and_index_file($results_dir_path)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        return;
    }

    $results_csv_file = $results_dir_path . $filename;
    
    // Open file for writing
    $file = fopen($results_csv_file, 'w');

    // Check if file opened successfully
    if ($file === false) {
        $message = __( 'Error opening file for writing. Please try again.', 'chatbot-chatgpt' );
        set_transient('chatbot_chatgpt_admin_error', $message, 60); // Expires in 60 seconds
        wp_safe_redirect(admin_url('options-general.php?page=chatbot-chatgpt&tab=reporting')); // Redirect to your settings page
        exit;
    }

    // Write headers to file
    if (isset($results[0]) && is_array($results[0])) {
        $keys = array_keys($results[0]);
        fputcsv($file, $keys);
    } else {
        $class = 'notice notice-error';
        $message = __( 'Chatbot No data in the file. Please enable conversation logging if currently off.', 'chatbot-chatgpt' );
        // printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
        chatbot_chatgpt_general_admin_notice($message);
        return;
    }

    // Write results to file
    foreach ($results as $result) {
        $result = array_map(function($value) {
            return $value !== null ? mb_convert_encoding($value, 'UTF-8', 'auto') : '';
        }, $result);
        fputcsv($file, $result);
    }

    // Close the file
    fclose($file);

    // Exit early if the file doesn't exist
    if (!file_exists($results_csv_file)) {
        $class = 'notice notice-error';
        $message = __( 'File not found!' . $results_csv_file, 'chatbot-chatgpt' );
        // printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
        chatbot_chatgpt_general_admin_notice($message);
        return;
    }

    // DIAG - Diagnostics - Ver 2.0.2.1

    if (!file_exists($results_csv_file)) {
        return;
    }
    
    if (!is_readable($results_csv_file)) {
        return;
    }
    
    $csv_data = file_get_contents(realpath($results_csv_file));
    if ($csv_data === false) {
        $class = 'notice notice-error';
        $message = __( 'Error reading file', 'chatbot-chatgpt' );
        chatbot_chatgpt_general_admin_notice($message);
        return;
    }
    
    if (!is_writable($results_csv_file)) {
        return;
    }  
    
    // Deliver the file for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=' . $filename);
    echo $csv_data;

    // Delete the file
    unlink($results_csv_file);
    exit;

}
add_action('admin_post_chatbot_chatgpt_download_conversation_data', 'chatbot_chatgpt_download_conversation_data');
add_action('admin_post_chatbot_chatgpt_download_interactions_data', 'chatbot_chatgpt_download_interactions_data');
add_action('admin_post_chatbot_chatgpt_download_token_usage_data', 'chatbot_chatgpt_download_token_usage_data');

// Test Conversation Digest Email - Ver 2.3.9 (AJAX Handler)
function chatbot_chatgpt_test_conversation_digest_ajax() {
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('You do not have sufficient permissions to access this page.');
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'chatbot_chatgpt_test_conversation_digest')) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.');
    }
    
    // Get the email address
    $email_address = esc_attr(get_option('chatbot_chatgpt_conversation_digest_email', ''));
    
    // If email is empty, show error
    if (empty($email_address)) {
        wp_send_json_error('Please enter an email address in the Conversation Digest Settings before testing.');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // Get conversations from the last 24 hours for test
    $start_time = date('Y-m-d H:i:s', strtotime('-24 hours'));
    
    // Query for conversations (only Visitor and Chatbot messages, not token data)
    $query = $wpdb->prepare("
        SELECT id, session_id, user_id, page_id, interaction_time, user_type, message_text, thread_id, assistant_id, assistant_name
        FROM $table_name
        WHERE interaction_time > %s
        AND user_type IN ('Chatbot', 'Visitor')
        ORDER BY interaction_time ASC
        LIMIT 50
    ", $start_time);
    
    $conversations = $wpdb->get_results($query);
    
    // Build email content
    $subject = 'Test: Kognetiks Chatbot Conversation Digest - ' . date('Y-m-d H:i:s');
    $message = "TEST EMAIL - Kognetiks Chatbot Conversation Digest\n\n";
    $message .= "This is a test email to verify your Conversation Digest settings are working correctly.\n\n";
    $message .= "Period: " . date('Y-m-d H:i:s', strtotime($start_time)) . " to " . current_time('mysql') . "\n\n";
    
    if (!empty($conversations)) {
        // Organize conversations by session
        $conversations_by_session = array();
        foreach ($conversations as $conversation) {
            $session_id = $conversation->session_id;
            if (!isset($conversations_by_session[$session_id])) {
                $conversations_by_session[$session_id] = array();
            }
            $conversations_by_session[$session_id][] = $conversation;
        }
        
        $message .= "Total Conversations: " . count($conversations_by_session) . "\n";
        $message .= "Total Messages: " . count($conversations) . "\n\n";
        $message .= "---\n\n";
        
        // Add each conversation session
        $session_count = 0;
        foreach ($conversations_by_session as $session_id => $session_conversations) {
            $session_count++;
            $message .= "Conversation #" . $session_count . " (Session ID: " . $session_id . ")\n";
            
            // Get session metadata from first message
            $first_message = $session_conversations[0];
            if (!empty($first_message->user_id)) {
                $message .= "User ID: " . $first_message->user_id . "\n";
            }
            if (!empty($first_message->page_id)) {
                $message .= "Page ID: " . $first_message->page_id . "\n";
            }
            if (!empty($first_message->thread_id)) {
                $message .= "Thread ID: " . $first_message->thread_id . "\n";
            }
            if (!empty($first_message->assistant_name)) {
                $message .= "Assistant: " . $first_message->assistant_name . "\n";
            }
            $message .= "Started: " . $first_message->interaction_time . "\n";
            $message .= "\n";
            
            // Add messages in chronological order
            foreach ($session_conversations as $msg) {
                $user_label = ($msg->user_type === 'Visitor') ? 'Visitor' : 'Chatbot';
                $message .= "[" . $msg->interaction_time . "] " . $user_label . ": " . $msg->message_text . "\n";
            }
            
            $message .= "\n---\n\n";
        }
    } else {
        $message .= "No conversations found in the last 24 hours.\n\n";
        $message .= "This is normal if conversation logging has just been enabled or if there have been no recent chatbot interactions.\n\n";
    }
    
    $message .= "\nThis is a test email from your Kognetiks Chatbot Conversation Digest system.\n";
    $message .= "If you receive this email, your Kognetiks Chatbot Conversation Digest settings are configured correctly.\n";
    
    // Log email attempt
    if (function_exists('back_trace')) {
    }
    
    // Send the email
    $sent = wp_mail($email_address, $subject, $message);
    
    // Log result
    if (function_exists('back_trace')) {
        if ($sent) {
        } else {
            // Check for PHP mail errors
            $last_error = error_get_last();
            if ($last_error && strpos($last_error['message'], 'mail') !== false) {
            }
        }
    }
    
    if ($sent) {
        $success_message = 'Test email sent successfully to: ' . $email_address;
        $success_message .= ' Note: If you don\'t receive the email, your local server may not be configured to send mail.';
        wp_send_json_success($success_message);
    } else {
        $error_message = 'Failed to send test email. ';
        $error_message .= 'Local Development Recommendations: Install an SMTP plugin, use Mailtrap/MailHog, or test on a staging server.';
        wp_send_json_error($error_message);
    }
}
// Register the AJAX action hook - Ver 2.3.9
add_action('wp_ajax_chatbot_chatgpt_test_conversation_digest', 'chatbot_chatgpt_test_conversation_digest_ajax');

// Test Insights Email - AJAX Handler
function chatbot_chatgpt_test_insights_email_ajax() {
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('You do not have sufficient permissions to access this page.');
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'chatbot_chatgpt_test_insights_email')) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.');
    }
    
    // Ensure the automated-emails.php file is loaded
    if (!function_exists('kognetiks_insights_send_proof_of_value_email')) {
        $automated_emails_file = plugin_dir_path(__FILE__) . '../insights/automated-emails.php';
        if (file_exists($automated_emails_file)) {
            require_once $automated_emails_file;
        } else {
            wp_send_json_error('Insights email function not available. Please ensure the insights module is loaded.');
            return;
        }
    }
    
    // Get the email address (use insights email or fall back to admin email)
    $email_address = get_option('chatbot_chatgpt_insights_email_address', '');
    if (empty($email_address)) {
        $email_address = get_option('admin_email');
    }
    
    // Send test insights email
    if (function_exists('kognetiks_insights_send_proof_of_value_email')) {
        $result = kognetiks_insights_send_proof_of_value_email([
            'period'     => 'weekly',
            'email_to'   => $email_address,
            'force_tier' => '', // Use actual tier detection
        ]);
        
        if ($result && !empty($result['subject'])) {
            $success_message = 'Test insights email sent successfully to: ' . $email_address;
            $success_message .= ' Note: If you don\'t receive the email, your local server may not be configured to send mail.';
            wp_send_json_success($success_message);
        } else {
            wp_send_json_error('Failed to send test insights email. Please check your email configuration.');
        }
    } else {
        wp_send_json_error('Insights email function not available. Please ensure the insights module is loaded.');
    }
}
// Register the AJAX action hook
add_action('wp_ajax_chatbot_chatgpt_test_insights_email', 'chatbot_chatgpt_test_insights_email_ajax');

// Sanitize conversation digest enabled setting - Ver 2.3.9
function chatbot_chatgpt_sanitize_conversation_digest_enabled($value) {
    $allowed_values = array('Yes', 'No');
    if (in_array($value, $allowed_values)) {
        return $value;
    }
    return 'No';
}

// Sanitize conversation digest frequency setting - Ver 2.3.9
function chatbot_chatgpt_sanitize_conversation_digest_frequency($value) {
    // If value is empty, try to get it from POST directly (WordPress might pass empty)
    if (empty($value) && isset($_POST['chatbot_chatgpt_conversation_digest_frequency'])) {
        $value = $_POST['chatbot_chatgpt_conversation_digest_frequency'];
    }
    
    // Check if premium is enabled (includes Premium plan check for users who upgraded)
    $is_premium = function_exists('chatbot_chatgpt_is_premium') ? chatbot_chatgpt_is_premium() : false;
    
    // Sanitize and normalize to lowercase (best practice: store lowercase in DB)
    $value = sanitize_text_field($value);
    $value = strtolower(trim($value));
    
    // If still empty, return current value or default
    if (empty($value)) {
        $current = get_option('chatbot_chatgpt_conversation_digest_frequency', 'weekly');
        return strtolower($current);
    }
    
    // Define allowed values based on premium status (all lowercase)
    if ($is_premium) {
        $allowed_values = array('hourly', 'daily', 'weekly');
    } else {
        // Free users can only use Weekly
        $allowed_values = array('weekly');
    }
    
    // Validate the value
    if (in_array($value, $allowed_values)) {
        return $value;
    }
    
    // If invalid value, return current value or default (always lowercase)
    $current = get_option('chatbot_chatgpt_conversation_digest_frequency', 'weekly');
    return strtolower($current);
}

// Sanitize conversation digest email setting - Ver 2.3.9
function chatbot_chatgpt_sanitize_conversation_digest_email($value) {
    $value = sanitize_email($value);
    
    // Get the enabled setting to validate
    $enabled = isset($_POST['chatbot_chatgpt_conversation_digest_enabled']) ? sanitize_text_field($_POST['chatbot_chatgpt_conversation_digest_enabled']) : get_option('chatbot_chatgpt_conversation_digest_enabled', 'No');
    
    // If enabled is Yes, email must not be blank
    if ($enabled === 'Yes' && empty($value)) {
        add_settings_error(
            'chatbot_chatgpt_conversation_digest_email',
            'chatbot_chatgpt_conversation_digest_email_error',
            'Email address is required when Conversation Digest is enabled.',
            'error'
        );
        // Return the old value to prevent saving empty email
        return get_option('chatbot_chatgpt_conversation_digest_email', '');
    }
    
    return $value;
}

// Sanitize insights email enabled setting
function chatbot_chatgpt_sanitize_insights_email_enabled($value) {
    $allowed_values = array('Yes', 'No');
    if (in_array($value, $allowed_values)) {
        return $value;
    }
    return 'No';
}

// Sanitize insights email frequency setting
function chatbot_chatgpt_sanitize_insights_email_frequency($value) {
    // If value is empty, try to get it from POST directly (WordPress might pass empty)
    if (empty($value) && isset($_POST['chatbot_chatgpt_insights_email_frequency'])) {
        $value = $_POST['chatbot_chatgpt_insights_email_frequency'];
    }
    
    // Check if premium is enabled (includes Premium plan check for users who upgraded)
    $is_premium = function_exists('chatbot_chatgpt_is_premium') ? chatbot_chatgpt_is_premium() : false;
    
    // Sanitize and normalize to lowercase (best practice: store lowercase in DB)
    $value = sanitize_text_field($value);
    $value = strtolower(trim($value));
    
    // If still empty, return current value or default
    if (empty($value)) {
        $current = get_option('chatbot_chatgpt_insights_email_frequency', 'weekly');
        return strtolower($current);
    }
    
    // Define allowed values based on premium status (all lowercase)
    if ($is_premium) {
        $allowed_values = array('daily', 'weekly', 'monthly');
    } else {
        // Free users can only use Weekly
        $allowed_values = array('weekly');
    }
    
    // Validate the value
    if (in_array($value, $allowed_values)) {
        return $value;
    }
    
    // If invalid value, return current value or default (always lowercase)
    $current = get_option('chatbot_chatgpt_insights_email_frequency', 'weekly');
    return strtolower($current);
}

// Sanitize insights email address setting
function chatbot_chatgpt_sanitize_insights_email_address($value) {
    $value = sanitize_email($value);
    
    // Get the enabled setting to validate
    $enabled = isset($_POST['chatbot_chatgpt_insights_email_enabled']) ? sanitize_text_field($_POST['chatbot_chatgpt_insights_email_enabled']) : get_option('chatbot_chatgpt_insights_email_enabled', 'No');
    
    // If enabled is Yes and email is provided, validate it
    if ($enabled === 'Yes' && !empty($value) && !is_email($value)) {
        add_settings_error(
            'chatbot_chatgpt_insights_email_address',
            'chatbot_chatgpt_insights_email_address_error',
            'Please enter a valid email address for Insights Email.',
            'error'
        );
        // Return the old value to prevent saving invalid email
        return get_option('chatbot_chatgpt_insights_email_address', '');
    }
    
    return $value;
}

// Handle conversation digest cron scheduling when settings are saved - Ver 2.3.9
function chatbot_chatgpt_handle_conversation_digest_scheduling($old_value, $new_value) {
    // Get the current enabled value (this is the new value being saved)
    $enabled = $new_value;
    
    // Get the old enabled value
    $old_enabled = $old_value;
    
    // If Conversation Digest is being enabled, automatically enable Conversation Logging
    if ($old_enabled === 'No' && $enabled === 'Yes') {
        $logging_enabled = get_option('chatbot_chatgpt_enable_conversation_logging', 'Off');
        if ($logging_enabled !== 'On') {
            update_option('chatbot_chatgpt_enable_conversation_logging', 'On');
        }
        chatbot_chatgpt_schedule_conversation_digest();
    }
    // Also handle case where it's already Yes (in case it wasn't scheduled before)
    elseif ($enabled === 'Yes') {
        $logging_enabled = get_option('chatbot_chatgpt_enable_conversation_logging', 'Off');
        if ($logging_enabled !== 'On') {
            update_option('chatbot_chatgpt_enable_conversation_logging', 'On');
        }
        chatbot_chatgpt_schedule_conversation_digest();
    }
    // If enabled changed from Yes to No, unschedule the cron
    elseif ($old_enabled === 'Yes' && $enabled === 'No') {
        wp_clear_scheduled_hook('kognetiks_insights_send_conversation_digest_email_hook');
    }
    // If enabled is Yes, check if we need to reschedule (frequency might have changed)
    elseif ($enabled === 'Yes') {
        chatbot_chatgpt_schedule_conversation_digest();
    }
}
add_action('update_option_chatbot_chatgpt_conversation_digest_enabled', 'chatbot_chatgpt_handle_conversation_digest_scheduling', 10, 2);

// Handle frequency changes - Ver 2.3.9
function chatbot_chatgpt_handle_conversation_digest_frequency_change($old_value, $new_value) {
    // Only reschedule if digest is enabled
    $enabled = get_option('chatbot_chatgpt_conversation_digest_enabled', 'No');
    if ($enabled === 'Yes' && $old_value !== $new_value) {
        chatbot_chatgpt_schedule_conversation_digest();
    }
}
add_action('update_option_chatbot_chatgpt_conversation_digest_frequency', 'chatbot_chatgpt_handle_conversation_digest_frequency_change', 10, 2);

// Handle insights email cron scheduling when settings are saved
function chatbot_chatgpt_handle_insights_email_scheduling($old_value, $new_value) {
    // Ensure the automated-emails.php file is loaded
    if (!function_exists('kognetiks_insights_schedule_proof_of_value_email')) {
        $automated_emails_file = plugin_dir_path(__FILE__) . '../insights/automated-emails.php';
        if (file_exists($automated_emails_file)) {
            require_once $automated_emails_file;
        } else {
            return; // File doesn't exist, can't proceed
        }
    }
    
    // Get the current enabled value (this is the new value being saved)
    $enabled = $new_value;
    
    // Get the old enabled value
    $old_enabled = $old_value;
    
    // If Proof of Value is being enabled, automatically enable Conversation Logging
    if ($old_enabled === 'No' && $enabled === 'Yes') {
        $logging_enabled = get_option('chatbot_chatgpt_enable_conversation_logging', 'Off');
        if ($logging_enabled !== 'On') {
            update_option('chatbot_chatgpt_enable_conversation_logging', 'On');
        }
        $period = get_option('chatbot_chatgpt_insights_email_frequency', 'weekly');
        $email = get_option('chatbot_chatgpt_insights_email_address', '');
        if (function_exists('kognetiks_insights_schedule_proof_of_value_email')) {
            kognetiks_insights_schedule_proof_of_value_email($period, $email);
        }
    }
    // If enabled changed from Yes to No, unschedule the cron
    elseif ($old_enabled === 'Yes' && $enabled === 'No') {
        if (function_exists('kognetiks_insights_unschedule_proof_of_value_email')) {
            kognetiks_insights_unschedule_proof_of_value_email();
        } else {
            // Fallback: clear the hook directly if function doesn't exist
            wp_clear_scheduled_hook('kognetiks_insights_send_proof_of_value_email_hook');
        }
    }
    // Also handle case where it's already No (in case it wasn't cleared before)
    elseif ($enabled === 'No') {
        if (function_exists('kognetiks_insights_unschedule_proof_of_value_email')) {
            kognetiks_insights_unschedule_proof_of_value_email();
        } else {
            // Fallback: clear the hook directly if function doesn't exist
            wp_clear_scheduled_hook('kognetiks_insights_send_proof_of_value_email_hook');
        }
    }
    // If enabled is Yes, check if we need to reschedule (period might have changed)
    elseif ($enabled === 'Yes') {
        $logging_enabled = get_option('chatbot_chatgpt_enable_conversation_logging', 'Off');
        if ($logging_enabled !== 'On') {
            update_option('chatbot_chatgpt_enable_conversation_logging', 'On');
        }
        $period = get_option('chatbot_chatgpt_insights_email_frequency', 'weekly');
        $email = get_option('chatbot_chatgpt_insights_email_address', '');
        if (function_exists('kognetiks_insights_schedule_proof_of_value_email')) {
            kognetiks_insights_schedule_proof_of_value_email($period, $email);
        }
    }
}
add_action('update_option_chatbot_chatgpt_insights_email_enabled', 'chatbot_chatgpt_handle_insights_email_scheduling', 10, 2);

// Handle insights email frequency changes
function chatbot_chatgpt_handle_insights_email_frequency_change($old_value, $new_value) {
    // Ensure the automated-emails.php file is loaded
    if (!function_exists('kognetiks_insights_schedule_proof_of_value_email')) {
        $automated_emails_file = plugin_dir_path(__FILE__) . '../insights/automated-emails.php';
        if (file_exists($automated_emails_file)) {
            require_once $automated_emails_file;
        } else {
            return; // File doesn't exist, can't proceed
        }
    }
    
    // Only reschedule if insights email is enabled
    $enabled = get_option('chatbot_chatgpt_insights_email_enabled', 'No');
    if ($enabled === 'Yes' && $old_value !== $new_value) {
        $email = get_option('chatbot_chatgpt_insights_email_address', '');
        if (function_exists('kognetiks_insights_schedule_proof_of_value_email')) {
            kognetiks_insights_schedule_proof_of_value_email($new_value, $email);
        }
    }
}
add_action('update_option_chatbot_chatgpt_insights_email_frequency', 'chatbot_chatgpt_handle_insights_email_frequency_change', 10, 2);

// Fallback: Ensure email report settings are saved when form is submitted
// This runs after WordPress processes the form to catch any settings that weren't saved
function chatbot_chatgpt_ensure_email_report_settings_saved() {
    // Only run on the reporting tab
    if (!isset($_GET['page']) || $_GET['page'] !== 'chatbot-chatgpt' || !isset($_GET['tab']) || $_GET['tab'] !== 'reporting') {
        return;
    }
    
    // Only run after form submission
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['option_page']) || $_POST['option_page'] !== 'chatbot_chatgpt_reporting') {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'chatbot_chatgpt_reporting-options')) {
        return;
    }
    
    // Save Conversation Digest settings if they're in POST
    if (isset($_POST['chatbot_chatgpt_conversation_digest_enabled'])) {
        $value = sanitize_text_field($_POST['chatbot_chatgpt_conversation_digest_enabled']);
        if (in_array($value, array('Yes', 'No'))) {
            $current = get_option('chatbot_chatgpt_conversation_digest_enabled');
            if ($current !== $value) {
                update_option('chatbot_chatgpt_conversation_digest_enabled', $value);
                // If Conversation Digest is being enabled, automatically enable Conversation Logging
                if ($value === 'Yes') {
                    $logging_enabled = get_option('chatbot_chatgpt_enable_conversation_logging', 'Off');
                    if ($logging_enabled !== 'On') {
                        update_option('chatbot_chatgpt_enable_conversation_logging', 'On');
                    }
                }
            }
        }
    }
    
    if (isset($_POST['chatbot_chatgpt_conversation_digest_frequency'])) {
        $value = strtolower(trim(sanitize_text_field($_POST['chatbot_chatgpt_conversation_digest_frequency'])));
        $is_premium = function_exists('chatbot_chatgpt_is_premium') ? chatbot_chatgpt_is_premium() : false;
        $allowed = $is_premium ? array('hourly', 'daily', 'weekly') : array('weekly');
        if (in_array($value, $allowed)) {
            $current = get_option('chatbot_chatgpt_conversation_digest_frequency');
            if ($current !== $value) {
                update_option('chatbot_chatgpt_conversation_digest_frequency', $value);
            }
        }
    }
    
    if (isset($_POST['chatbot_chatgpt_conversation_digest_email'])) {
        $value = sanitize_email($_POST['chatbot_chatgpt_conversation_digest_email']);
        $current = get_option('chatbot_chatgpt_conversation_digest_email');
        if ($current !== $value) {
            update_option('chatbot_chatgpt_conversation_digest_email', $value);
        }
    }
    
    // Save Insights Email settings if they're in POST
    if (isset($_POST['chatbot_chatgpt_insights_email_enabled'])) {
        $value = sanitize_text_field($_POST['chatbot_chatgpt_insights_email_enabled']);
        if (in_array($value, array('Yes', 'No'))) {
            $current = get_option('chatbot_chatgpt_insights_email_enabled');
            if ($current !== $value) {
                update_option('chatbot_chatgpt_insights_email_enabled', $value);
                // If Proof of Value is being enabled, automatically enable Conversation Logging
                if ($value === 'Yes') {
                    $logging_enabled = get_option('chatbot_chatgpt_enable_conversation_logging', 'Off');
                    if ($logging_enabled !== 'On') {
                        update_option('chatbot_chatgpt_enable_conversation_logging', 'On');
                    }
                }
            }
        }
    }
    
    if (isset($_POST['chatbot_chatgpt_insights_email_frequency'])) {
        $value = strtolower(trim(sanitize_text_field($_POST['chatbot_chatgpt_insights_email_frequency'])));
        if (in_array($value, array('weekly', 'monthly'))) {
            $current = get_option('chatbot_chatgpt_insights_email_frequency');
            if ($current !== $value) {
                update_option('chatbot_chatgpt_insights_email_frequency', $value);
            }
        }
    }
    
    if (isset($_POST['chatbot_chatgpt_insights_email_address'])) {
        $value = sanitize_email($_POST['chatbot_chatgpt_insights_email_address']);
        $current = get_option('chatbot_chatgpt_insights_email_address');
        if ($current !== $value) {
            update_option('chatbot_chatgpt_insights_email_address', $value);
        }
    }
}
add_action('admin_init', 'chatbot_chatgpt_ensure_email_report_settings_saved', 20);

// Function to display the reporting message - Ver 1.7.9
function chatbot_chatgpt_admin_notice() {
    $error_message = get_transient('chatbot_chatgpt_admin_error');
    if (!empty($error_message)) {
        printf('<div class="%1$s"><p><b>Chatbot: </b>%2$s</p></div>', 'notice notice-error is-dismissible', $error_message);
        delete_transient('chatbot_chatgpt_admin_error'); // Clear the transient after displaying the message
    }
    
    $success_message = get_transient('chatbot_chatgpt_admin_success');
    if (!empty($success_message)) {
        printf('<div class="%1$s"><p><b>Chatbot: </b>%2$s</p></div>', 'notice notice-success is-dismissible', $success_message);
        delete_transient('chatbot_chatgpt_admin_success'); // Clear the transient after displaying the message
    }
}
add_action('admin_notices', 'chatbot_chatgpt_admin_notice');
