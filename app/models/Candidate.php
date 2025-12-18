<?php
// This file will hold functions related to fetching candidate data.

/**
 * Fetches all candidates from the HR portal API.
 * @return array An array of candidate data, or an empty array on failure.
 */
function getAllCandidatesFromAPI() {
    // Ensure ROOT_PATH is defined
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', dirname(__DIR__, 2));
        require_once ROOT_PATH . '/config/app.php';
    }

    $apiUrl = getenv('HR_API_URL');
    $apiKey = getenv('HR_API_KEY');

    if (!$apiUrl || !$apiKey) {
        error_log("HR_API_URL or HR_API_KEY not set in environment.");
        return [];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-KEY: ' . $apiKey
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log("cURL Error: " . curl_error($ch));
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("HR API returned HTTP code: " . $httpCode . " Response: " . $response);
        return [];
    }

    $candidates = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error decoding JSON from HR API: " . json_last_error_msg());
        return [];
    }

    return $candidates;
}
