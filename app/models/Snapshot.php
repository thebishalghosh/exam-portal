<?php
// This file will hold all functions related to proctoring snapshots.

/**
 * Finds all snapshot image files for a given submission.
 * @param int $exam_id The ID of the exam.
 * @param int $user_id The ID of the user.
 * @return array An array of snapshot filenames.
 */
function getSnapshotsForSubmission($exam_id, $user_id) {
    $snapshots = [];
    $storage_dir = ROOT_PATH . '/storage/snapshots/';

    // The filename format is: exam_{exam_id}_user_{user_id}_{timestamp}.jpg
    $file_pattern = "exam_{$exam_id}_user_{$user_id}_*.jpg";

    // Use glob to find all matching files
    $files = glob($storage_dir . $file_pattern);

    if ($files) {
        foreach ($files as $file) {
            $snapshots[] = basename($file);
        }
    }

    // Sort by timestamp (the last part of the filename)
    usort($snapshots, function($a, $b) {
        $ts_a = (int)explode('_', pathinfo($a, PATHINFO_FILENAME))[3];
        $ts_b = (int)explode('_', pathinfo($b, PATHINFO_FILENAME))[3];
        return $ts_a - $ts_b;
    });

    return $snapshots;
}
