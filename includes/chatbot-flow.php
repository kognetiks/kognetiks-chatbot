<?php
/**
 * Kognetiks Chatbot for WordPress - Flow Integration - Ver 1.9.2
 *
 * This file contains the code for integrating the chatbot with the Flows
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Example usage:
// To fetch data for a specific sequence, pass the SequenceID as an argument
// $specificSequenceData = fetchAndOrganizeData(1); // Replace 1 with your specific SequenceID

// To fetch data for all sequences, call the function without arguments
// $allSequencesData = fetchAndOrganizeData();

// If you need to print the results:
// echo "<pre>" . print_r($specificSequenceData, true) . "</pre>";
// echo "<pre>" . print_r($allSequencesData, true) . "</pre>";
function fetchAndOrganizeData($sequenceID = null) {

    global $wpdb;

    $tables = [
        "{$wpdb->prefix}kognetiks_kflow_sequences",
        "{$wpdb->prefix}kognetiks_kflow_steps",
        "{$wpdb->prefix}kognetiks_kflow_prompts",
        "{$wpdb->prefix}kognetiks_kflow_templates"
    ];
    
    foreach ($tables as $table) {
        if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            // Table doesn't exist, handle it here
            return;
        }
    }

    // Prepare the basic SQL query
    $sql = "SELECT
            seq.SequenceID,
            seq.SequenceName,
            seq.SequenceStatus,
            seq.CreatedAt AS SequenceCreatedAt,
            seq.LastModifiedAt AS SequenceLastModifiedAt,
            prompt.PromptID,
            prompt.PromptText,
            prompt.PromptStatus,
            prompt.CreatedAt AS PromptCreatedAt,
            prompt.LastModifiedAt AS PromptLastModifiedAt,
            step.StepID,
            step.StepOrder,
            step.StepStatus,
            step.CreatedAt AS StepCreatedAt,
            step.LastModifiedAt AS StepLastModifiedAt,
            temp.TemplateID,
            temp.TemplateName,
            temp.TemplateStatus,
            temp.TemplateContents,
            temp.CreatedAt AS TemplateCreatedAt,
            temp.LastModifiedAt AS TemplateLastModifiedAt
        FROM
            {$wpdb->prefix}kognetiks_kflow_sequences AS seq
        LEFT JOIN
            {$wpdb->prefix}kognetiks_kflow_steps AS step ON seq.SequenceID = step.SequenceID
        LEFT JOIN
            {$wpdb->prefix}kognetiks_kflow_prompts AS prompt ON step.PromptID = prompt.PromptID
        LEFT JOIN
            {$wpdb->prefix}kognetiks_kflow_templates AS temp ON seq.SequenceID = temp.SequenceID
    ";

    // If a specific SequenceID is provided, modify the SQL query to filter by that ID
    if (!is_null($sequenceID)) {
        $sql .= $wpdb->prepare(" WHERE seq.SequenceID = %d", $sequenceID);
    }

    // Execute the query
    $sequences = $wpdb->get_results($sql);

    // Organize data
    $organizedData = [];
    foreach ($sequences as $sequence) {
        $seqID = $sequence->SequenceID;
        if (!isset($organizedData[$seqID])) {
            $organizedData[$seqID] = [
                'SequenceName' => $sequence->SequenceName,
                'SequenceStatus' => $sequence->SequenceStatus,
                'Prompts' => [],
                'Steps' => [],
                'Templates' => [],
            ];
        }

        // Add prompt, step, and template data if available
        if ($sequence->PromptText && !in_array($sequence->PromptText, $organizedData[$seqID]['Prompts'])) {
            $organizedData[$seqID]['Prompts'][] = $sequence->PromptText;
        }

        if ($sequence->StepOrder !== null && !in_array($sequence->StepOrder, $organizedData[$seqID]['Steps'])) {
            $organizedData[$seqID]['Steps'][] = $sequence->StepOrder;
        }

        if ($sequence->TemplateName && !in_array($sequence->TemplateName, $organizedData[$seqID]['Templates'])) {
            $organizedData[$seqID]['Templates'][] = $sequence->TemplateName;
        }

        if ($sequence->TemplateName && !in_array($sequence->TemplateContents, $organizedData[$seqID]['Templates'])) {
            $organizedData[$seqID]['Templates'][] = $sequence->TemplateContents;
        }
        
    }

    // Return the organized data instead of printing
    return $organizedData;

}