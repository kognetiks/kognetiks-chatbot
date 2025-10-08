<?php
/**
 * Kognetiks Chatbot - File Helper - Ver 2.0.3
 *
 * This file contains the code for uploading files as part
 * in support of Custom GPT Assistants via the Chatbot.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Handle non-image attachments
function chatbot_chatgpt_text_attachment($prompt, $file_id, $beta_version = 'assistants=v1') {

    $chatbot_ai_platform_choice = esc_attr( get_option( 'chatbot_ai_platform_choice', 'OpenAI' ) );

    if ( $chatbot_ai_platform_choice == 'Azure OpenAI' ) {
        $beta_version = 'assistants=v0';
    }

    if ( $chatbot_ai_platform_choice == 'OpenAI') {
        // Set up the data payload
        $data = [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'text',
                    'text' => $prompt,
                ]
            ],
        ];
    } elseif ( $chatbot_ai_platform_choice == 'Azure OpenAI') {
        // Setup the data payload
        $data = [
            'role' => 'user',
            'content' => $prompt,
        ];
    }

    // Add the non-images files to the data payload
    if ( !empty($file_id) && is_array($file_id) && !empty($file_id[0]) ) {
        
        // DEBUG: Log what files are being sent to the API
        error_log('API REQUEST DEBUG: Sending files to OpenAI API');
        error_log('API REQUEST DEBUG: File count: ' . count($file_id));
        error_log('API REQUEST DEBUG: Files: ' . print_r($file_id, true));
        if ( $beta_version == 'assistants=v0') {
            // assistants=v2 - Ver 1.9.6 - 2024 04 24
            $data = $data + [
                "attachments" => [],
            ];
            foreach ($file_id as $file_item) {
                // Skip invalid file_item entries
                if (substr($file_item, 0, 10) !== 'assistant-') {
                    continue;
                }
                $attachment = [
                    "file_id" => $file_item,
                    "tools" => [
                        ["type" => "file_search"]
                    ]
                ];
                // Add each attachment to the attachments array in the main data structure
                $data['attachments'][] = $attachment;
            }
        } elseif ( $beta_version == 'assistants=v1' ) {
            // assistants=v1 - Ver 1.9.6 - 2024 04 24
            $data['file_ids'] = $file_id;
        } else {
            // assistants=v2 - Ver 1.9.6 - 2024 04 24
            $data = $data + [
                "attachments" => [],
            ];
            foreach ($file_id as $file_item) {
                // Skip invalid file_item entries
                if (substr($file_item, 0, 5) !== 'file-') {
                    continue;
                }
                $attachment = [
                    "file_id" => $file_item,
                    "tools" => [
                        ["type" => "file_search"]
                    ]
                ];
                // Add each attachment to the attachments array in the main data structure
                $data['attachments'][] = $attachment;
            }
        }
    }

    // DIAG - Diagnostics
    // back_trace( 'NOTICE' , '$beta_version: ' . $beta_version );
    // back_trace( 'NOTICE' , '$data: ' . print_r($data, true) );

    return $data;

}

// Handle image attachments
function chatbot_chatgpt_image_attachment($prompt, $file_id, $beta_version) {

    // Set up the data payload
    $data = [
        'role' => 'user',
    ];

    // Add the image files to the data payload
    if ( !empty($file_id && !empty($file_id[0]) )) {
        if ( $beta_version == 'assistants=v1' ) {
            // assistants=v1 - Ver 1.9.6 - 2024 04 24
            $data['file_ids'] = $file_id;
        } else {
            // assistants=v2 - Ver 1.9.6 - 2024 04 24
            $data = $data + [
                'content' => [],
            ];
            foreach ($file_id as $file_item) {
                // Skip invalid file_item entries
                if (substr($file_item, 0, 5) !== 'file-') {
                    continue;
                }
                $attachment = [
                    'type' => 'image_file',
                    'image_file' => [
                        'file_id' => $file_item,
                        'detail' => 'auto'
                    ]
                ];
                // Add each image attachment to the attachments array in the main data structure
                $data['content'][] = $attachment;
            }
        }
    }

    // Finish off with the text prompt
    $data['content'][] = [
            'type' => 'text',
            'text' => $prompt,
        ];

    return $data;

}
