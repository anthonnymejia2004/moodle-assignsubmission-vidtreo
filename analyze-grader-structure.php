<?php
/**
 * Analyze the grading interface structure to understand where to place the video
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

require_login();

$cm = get_coursemodule_from_id('assign', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

$PAGE->set_url(new moodle_url('/mod/assign/submission/vidtreo/analyze-grader-structure.php', ['id' => $id, 'userid' => $userid]));
$PAGE->set_context($context);
$PAGE->set_title('Analyze Grader Structure');

echo $OUTPUT->header();

echo '<h2>Grading Interface Structure Analysis</h2>';

echo '<p>This page helps understand where the video should be placed in the grading interface.</p>';

echo '<h3>Key Information:</h3>';
echo '<ul>';
echo '<li>The grading interface in Moodle uses a specific layout</li>';
echo '<li>The left side typically shows the submission content</li>';
echo '<li>The right side shows grading controls</li>';
echo '<li>Different Moodle versions and themes may have different structures</li>';
echo '</ul>';

echo '<h3>Recommended Approach:</h3>';
echo '<p>Instead of trying to move the video with JavaScript (which may break in different themes), we should:</p>';
echo '<ol>';
echo '<li><strong>Keep the video where Moodle naturally places it</strong> (in the submission content area)</li>';
echo '<li><strong>Make the video larger with CSS</strong> to improve visibility</li>';
echo '<li><strong>Ensure it works across all Moodle themes</strong></li>';
echo '</ol>';

echo '<h3>CSS-Only Solution:</h3>';
echo '<p>Add styles to make the video player larger and more prominent:</p>';
echo '<pre>';
echo htmlspecialchars('
/* Make video player larger in grading interface */
.path-mod-assign .vidtreo-player-container {
    max-width: 100%;
    width: 100%;
    margin: 20px 0;
}

.path-mod-assign .vidtreo-player-container vidtreo-player {
    width: 100%;
    max-width: 800px;
    display: block;
    margin: 0 auto;
}

/* Ensure video is responsive */
@media (max-width: 768px) {
    .path-mod-assign .vidtreo-player-container vidtreo-player {
        max-width: 100%;
    }
}
');
echo '</pre>';

echo '<h3>Why This Approach is Better:</h3>';
echo '<ul>';
echo '<li>✓ Works in all Moodle versions (3.9+)</li>';
echo '<li>✓ Works with all themes (Boost, Classic, custom themes)</li>';
echo '<li>✓ No JavaScript manipulation needed</li>';
echo '<li>✓ Respects Moodle\'s layout structure</li>';
echo '<li>✓ Responsive and mobile-friendly</li>';
echo '<li>✓ Won\'t break with Moodle updates</li>';
echo '</ul>';

echo '<p><a href="' . new moodle_url('/mod/assign/view.php', ['id' => $id, 'action' => 'grader', 'userid' => $userid]) . '" class="btn btn-primary">Back to Grading Interface</a></p>';

echo $OUTPUT->footer();
