<?php
// This file will hold functions related to fetching candidate data.

/**
 * Fetches all candidates from the HR portal API (Internal).
 * @return array An array of candidate data, or an empty array on failure.
 */
function getAllCandidatesFromAPI() {
    $apiUrl = getenv('HR_API_URL');
    $apiKey = getenv('HR_API_KEY');

    if (!$apiUrl || !$apiKey) {
        error_log("HR_API_URL or HR_API_KEY not set in environment.");
        return [];
    }

    return fetchCandidatesFromApiEndpoint($apiUrl, $apiKey);
}

/**
 * Fetches all candidates from the Interview portal API.
 * @return array An array of candidate data, or an empty array on failure.
 */
function getInterviewCandidatesFromAPI() {
    $apiUrl = getenv('INTERVIEW_API_URL');
    $apiKey = getenv('INTERVIEW_API_KEY');

    if (!$apiUrl || !$apiKey) {
        error_log("INTERVIEW_API_URL or INTERVIEW_API_KEY not set in environment.");
        return [];
    }

    return fetchCandidatesFromApiEndpoint($apiUrl, $apiKey);
}

/**
 * A generic cURL function to fetch data from a given API endpoint.
 * @param string $apiUrl The URL of the API.
 * @param string $apiKey The API key for authentication.
 * @return array The decoded JSON data, or an empty array on failure.
 */
function fetchCandidatesFromApiEndpoint($apiUrl, $apiKey) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-KEY: ' . $apiKey
    ]);
    // It's good practice to set a timeout
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log("cURL Error for " . $apiUrl . ": " . curl_error($ch));
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("API at " . $apiUrl . " returned HTTP code: " . $httpCode . " Response: " . $response);
        return [];
    }

    $candidates = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error decoding JSON from " . $apiUrl . ": " . json_last_error_msg());
        return [];
    }

    return $candidates;
}
