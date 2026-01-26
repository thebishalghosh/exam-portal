<?php
// This file will hold all database functions related to external API requests.

/**
 * Fetches all exams assigned to a specific candidate by their email.
 * @param mysqli $conn The database connection object.
 * @param string $email The email of the candidate.
 * @param string|null $session_token The user's session token from the HR portal.
 * @return array An array of assigned exams.
 */
function getAssignedExamsByEmail($conn, $email, $session_token = null) {
    // ... (existing code)
}

/**
 * Fetches candidates from the HR Portal API, filtered by a search term.
 * @param string $search The search term for name or email.
 * @return array An array of candidate objects.
 */
function fetchCandidatesFromHR($search = '') {
    $url = getenv('HR_API_URL');
    $api_key = getenv('HR_API_KEY');

    if (empty($url)) return [];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-KEY: ' . $api_key]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) return [];

    $data = json_decode($response, true);
    $all_candidates = [];

    if (is_array($data)) {
        if (isset($data[0]) && is_array($data[0])) {
            $all_candidates = $data;
        } elseif (isset($data['candidates']) && is_array($data['candidates'])) {
            $all_candidates = $data['candidates'];
        }
    }

    if (empty($all_candidates)) return [];

    // Filter results in PHP if a search term is provided
    if (!empty($search)) {
        $search = strtolower($search);
        $all_candidates = array_filter($all_candidates, function($candidate) use ($search) {
            $name = strtolower($candidate['full_name'] ?? $candidate['name'] ?? '');
            $email = strtolower($candidate['email'] ?? '');
            return strpos($name, $search) !== false || strpos($email, $search) !== false;
        });
    }

    return array_map(function($candidate) {
        $candidate['source'] = 'hr_portal';
        return $candidate;
    }, $all_candidates);
}

/**
 * Fetches candidates from the Interview Portal API, filtered by a search term.
 * @param string $search The search term for name or email.
 * @return array An array of candidate objects.
 */
function fetchCandidatesFromInterview($search = '') {
    $url = getenv('INTERVIEW_API_URL');
    $api_key = getenv('INTERVIEW_API_KEY');

    if (empty($url)) return [];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-KEY: ' . $api_key]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) return [];

    $data = json_decode($response, true);
    $all_candidates = [];

    if (is_array($data)) {
        if (isset($data[0]) && is_array($data[0])) {
            $all_candidates = $data;
        } elseif (isset($data['candidates']) && is_array($data['candidates'])) {
            $all_candidates = $data['candidates'];
        }
    }

    if (empty($all_candidates)) return [];

    // Filter results in PHP if a search term is provided
    if (!empty($search)) {
        $search = strtolower($search);
        $all_candidates = array_filter($all_candidates, function($candidate) use ($search) {
            $name = strtolower($candidate['full_name'] ?? $candidate['name'] ?? '');
            $email = strtolower($candidate['email'] ?? '');
            return strpos($name, $search) !== false || strpos($email, $search) !== false;
        });
    }

    return array_map(function($candidate) {
        $candidate['source'] = 'interview_portal';
        return $candidate;
    }, $all_candidates);
}
