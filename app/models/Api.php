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
    $exams = [];

    $sql = "SELECT
                e.exam_id,
                e.title,
                e.description,
                e.duration,
                a.status,
                a.score
            FROM exam_assignments a
            JOIN exams e ON a.exam_id = e.exam_id
            WHERE a.candidate_email = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            if ($row['status'] === 'assigned' || $row['status'] === 'started') {
                if ($session_token) {
                    // Points to SSO, which now redirects to /exam/check/{id}
                    $row['start_link'] = BASE_URL . '/login/sso?session_token=' . urlencode($session_token) . '&exam_id=' . $row['exam_id'];
                } else {
                    // Fallback points directly to System Check
                    $row['start_link'] = BASE_URL . '/exam/check/' . $row['exam_id'];
                }
            } else {
                $row['start_link'] = null;
            }
            $exams[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
    return $exams;
}

/**
 * Fetches candidates from the HR Portal API.
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
 * Fetches candidates from the Interview Portal API.
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


/**
 * Notify the interview portal about an exam status/score update.
 *
 * The remote webhook endpoint expects a JSON POST with at least:
 *   - email
 *   - status
 *   - exam_title
 *   - score
 *   - score_type
 *   - webhook_key (shared secret)
 *
 * We always include these fields in the payload we send.
 *
 * @param string      $email       Candidate email address.
 * @param int         $exam_id     Exam identifier (internal value).
 * @param string      $status      e.g. 'completed', 'graded', 'disqualified'.
 * @param float|null  $score       Optional numeric score.
 * @param string|null $exam_title  Human-readable title of the exam.
 * @param string|null $score_type  e.g. 'auto', 'manual', 'final'.
 * @return bool True if the remote call returned HTTP 200, false otherwise.
 */
function reportExamResultToInterview($email, $exam_id, $status, $score = null, $exam_title = null, $score_type = null) {
    // Prefer dedicated exam status URL; fall back to generic interview API URL if not set.
    $url = getenv('INTERVIEW_EXAM_STATUS_URL');
    if (empty($url)) {
        $url = getenv('INTERVIEW_API_URL');
    }

    // Use the shared exam webhook key; fall back to older interview key if needed.
    $api_key = getenv('EXAM_API_KEY');
    if (empty($api_key)) {
        $api_key = getenv('INTERVIEW_API_KEY');
    }

    if (empty($url) || empty($api_key)) {
        error_log("Cannot report result: INTERVIEW_API_URL or INTERVIEW_API_KEY not configured.");
        return false;
    }

    // Normalise status for the webhook (the DB can still use its own casing/values)
    $status_for_webhook = strtolower(trim($status));

    // Always include the fields the remote portal expects.
    // Also include exam_id as an extra convenience field.
    $payload = [
        'email'       => $email,
        'status'      => $status_for_webhook,
        'exam_title'  => $exam_title !== null ? $exam_title : '',
        'score'       => $score !== null ? (float)$score : null,
        'score_type'  => $score_type !== null ? $score_type : null,
        'webhook_key' => $api_key,
        'exam_id'     => $exam_id,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        // send shared key in header as well as body
        'X-API-KEY: ' . $api_key,
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log("Error reporting interview result: " . curl_error($ch));
    }
    curl_close($ch);

    if ($http_code !== 200) {
        error_log("Interview API returned HTTP " . $http_code . ": " . $response);
        return false;
    }
    return true;
}
