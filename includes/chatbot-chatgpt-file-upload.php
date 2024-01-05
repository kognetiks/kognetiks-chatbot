<?php
/**
 * Chatbot ChatGPT for WordPress - File Uploads - Ver 1.7.6
 *
 * This file contains the code for uploading files as part
 * in support of Custom GPT Assistants via the Chatbot ChatGPT.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Upload Files to the Assistant
function chatbot_chatgpt_upload_file_to_assistant () {

    // Get the API key
    $api_key = get_option('chatgpt_api_key');
    if (empty($api_key)) {
        // If the API key is empty, then return an error
        $response = array(
            'status' => 'error',
            'message' => 'API key is missing. Please enter your API key in the Chatbot ChatGPT settings.'
        );
        return $response;
    }

    // Ask the user to select a file to upload
    $filePath = $_FILES['file']['tmp_name'];

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/files');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $api_key
    ));

    // Add the file to upload and the purpose
    $postFields = array(
        'file' => new CURLFile($filePath),
        'purpose' => 'fine-tune'
    );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

    // Execute the cURL session
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }

    // Close the cURL session
    curl_close($ch);

    // Decode the response
    $responseData = json_decode($response, true);

    // Handle the response
    if (isset($responseData['id'])) {
        // DIAG - Diagnostic - Ver 1.7.6
        chatbot_chatgpt_back_trace( 'NOTICE', "File uploaded successfully. File ID " . $responseData['id']);
        // Handle the response
        $response = array(
            'status' => 'success',
            'message' => 'File uploaded successfully.'
        );
        return $response;
    } else {
        // DIAG - Diagnostic - Ver 1.7.6
        chatbot_chatgpt_back_trace( 'NOTICE', "File upload failed. File ID " . $responseData['id']);
        $response = array(
            'status' => 'error',
            'message' => 'Failed to upload file. Please try again.'
        );
        return $response;
    }

    // Belt and Suspenders
    // DIAG - Diagnostic - Ver 1.7.6
    chatbot_chatgpt_back_trace( 'NOTICE', "File upload fell thru. File ID " . $responseData['id']);
    $response = array(
        'status' => 'error',
        'message' => 'Oops, I fell through the cracks!'
    );
    return $response;

}