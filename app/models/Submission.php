<?php
// This file will hold all database functions related to submissions.

/**
 * Fetches the total count of all submissions.
 * @param mysqli $conn The database connection object.
 * @return int The total number of submissions.
 */
function getTotalSubmissionsCount($conn) {
    $sql = "SELECT COUNT(submission_id) as total FROM submissions";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row ? (int)$row['total'] : 0;
}

/**
 * Fetches the count of submissions for each of the last 7 days.
 * @param mysqli $conn The database connection object.
 * @return array An array formatted for Google Charts.
 */
function getRecentSubmissionsCount($conn) {
    $sql = "SELECT DATE(end_time) as submission_date, COUNT(submission_id) as count
            FROM submissions
            WHERE end_time >= CURDATE() - INTERVAL 7 DAY
            GROUP BY DATE(end_time)
            ORDER BY submission_date ASC";

    $result = mysqli_query($conn, $sql);

    // Initialize an array with the last 7 days
    $chart_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('M d', strtotime("-$i days"));
        $chart_data[$date] = 0;
    }

    // Fill in the counts from the database
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $date = date('M d', strtotime($row['submission_date']));
            if (isset($chart_data[$date])) {
                $chart_data[$date] = (int)$row['count'];
            }
        }
    }

    // Format for Google Charts
    $final_data = [['Day', 'Submissions']];
    foreach ($chart_data as $day => $count) {
        $final_data[] = [$day, $count];
    }

    return $final_data;
}
