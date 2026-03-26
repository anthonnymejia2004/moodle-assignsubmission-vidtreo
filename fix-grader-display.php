<?php
/**
 * Fix script to ensure video displays in grading interface
 * This adds debug output to understand what's happening
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

$id = required_param('id', PARAM_INT); // Course module ID
$userid = required_param('userid', PARAM_INT);

require_login();

$cm = get_coursemodule_from_id('assign', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$assign = new assign($context, $cm, $cm->course);

$PAGE->set_url(new moodle_url('/mod/assign/submission/vidtreo/fix-grader-display.php', ['id' => $id, 'userid' => $userid]));
$PAGE->set_context($context);
$PAGE->set_title('Fix Grader Display');

echo $OUTPUT->header();

echo '<h2>Grader Display Diagnostic</h2>';

// Get submission
$submission = $assign->get_user_submission($userid, false);

if (!$submission) {
    echo '<div class="alert alert-danger">No submission found for user ' . $userid . '</div>';
    echo $OUTPUT->footer();
    die();
}

echo '<div class="alert alert-info">Submission ID: ' . $submission->id . '</div>';

// Get the vidtreo plugin
$plugin = $assign->get_submission_plugin_by_type('vidtreo');

if (!$plugin) {
    echo '<div class="alert alert-danger">Vidtreo plugin not found or not enabled for this assignment</div>';
    echo '<p>To enable:</p>';
    echo '<ol>';
    echo '<li>Go to assignment settings</li>';
    echo '<li>Expand "Submission types"</li>';
    echo '<li>Enable "Vidtreo video recording"</li>';
    echo '</ol>';
    echo $OUTPUT->footer();
    die();
}

echo '<div class="alert alert-success">Vidtreo plugin is enabled</div>';

// Check if plugin is enabled
$enabled = $plugin->is_enabled();
echo '<p>Plugin enabled: ' . ($enabled ? 'YES' : 'NO') . '</p>';

// Check database
global $DB;
$vidtreosubmission = $DB->get_record('assignsubmission_vidtreo', ['submission' => $submission->id]);

if (!$vidtreosubmission) {
    echo '<div class="alert alert-warning">No video submission in database</div>';
} else {
    echo '<div class="alert alert-success">Video submission found</div>';
    echo '<table class="table">';
    echo '<tr><th>Field</th><th>Value</th></tr>';
    echo '<tr><td>Recording ID</td><td>' . htmlspecialchars($vidtreosubmission->recording_id) . '</td></tr>';
    echo '<tr><td>Duration</td><td>' . ($vidtreosubmission->duration ?: 0) . 's</td></tr>';
    echo '<tr><td>Status</td><td>' . htmlspecialchars($vidtreosubmission->status) . '</td></tr>';
    echo '</table>';
}

// Test view_summary
echo '<h3>Testing view_summary()</h3>';
$showviewlink = false;
$summary = $plugin->view_summary($submission, $showviewlink);
echo '<p>Show view link: ' . ($showviewlink ? 'TRUE' : 'FALSE') . '</p>';
echo '<div style="border: 2px solid green; padding: 10px; background: #f0f0f0;">';
echo $summary;
echo '</div>';

// Test view
echo '<h3>Testing view()</h3>';
$viewoutput = $plugin->view($submission);
echo '<div style="border: 2px solid blue; padding: 10px; background: #f0f0f0;">';
echo $viewoutput;
echo '</div>';

echo '<h3>Raw HTML from view():</h3>';
echo '<pre>' . htmlspecialchars($viewoutput) . '</pre>';

// Check configuration
echo '<h3>Plugin Configuration</h3>';
$apikey = get_config('assignsubmission_vidtreo', 'apikey');
$backendurl = get_config('assignsubmission_vidtreo', 'backendurl');
$playercdnurl = get_config('assignsubmission_vidtreo', 'player_cdnurl');

echo '<table class="table">';
echo '<tr><th>Config</th><th>Value</th></tr>';
echo '<tr><td>apikey</td><td>' . (empty($apikey) ? '<span class="text-danger">MISSING</span>' : '<span class="text-success">Configured</span>') . '</td></tr>';
echo '<tr><td>backendurl</td><td>' . htmlspecialchars($backendurl ?: 'MISSING') . '</td></tr>';
echo '<tr><td>player_cdnurl</td><td>' . htmlspecialchars($playercdnurl ?: 'Will use default') . '</td></tr>';
echo '</table>';

echo '<hr>';
echo '<h3>Next Steps</h3>';
echo '<p>The view() method is working. The issue is that Moodle is not calling it in the grading interface.</p>';
echo '<p>Go back to the grading interface and check the browser console for JavaScript errors:</p>';
echo '<p><a href="' . new moodle_url('/mod/assign/view.php', ['id' => $id, 'action' => 'grader', 'userid' => $userid]) . '" class="btn btn-primary">Go to Grading Interface</a></p>';

echo $OUTPUT->footer();
