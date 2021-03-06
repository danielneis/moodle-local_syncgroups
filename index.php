<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details.
 *
 * @package    local_syncgroups
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require("{$CFG->dirroot}/group/lib.php");
require("{$CFG->dirroot}/local/syncgroups/lib.php");
require("{$CFG->dirroot}/local/syncgroups/ui/renderer.php");
require("{$CFG->dirroot}/local/syncgroups/ui/components.php");

$courseid = required_param('courseid', PARAM_INT);
$groups = optional_param_array('groups', 0, PARAM_INT);
$destinations = optional_param_array('destinations', 0, PARAM_INT);
$searchcourses = optional_param('searchcourses', false, PARAM_BOOL);

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);

$url = new moodle_url('/local/syncgroups/index.php', array('courseid'=>$courseid));

$PAGE->set_url($url);

// Make sure that the user has permissions to manage groups.
require_login($course);

$context = context_course::instance($course->id);
require_capability('moodle/course:managegroups', $context);

// Print the page and form
$strsyncgroups = get_string('pluginname', 'local_syncgroups');

/// Print header
$PAGE->set_title($strsyncgroups);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_syncgroups'));

if (!$searchcourses && !empty($groups) && !empty($destinations)) {

        $erro = false;
        if ($origem = get_course($courseid)) {

            list($in_or_equal, $groupparams) = $DB->get_in_or_equal($groups);
            if (!$groups_to_sync = $DB->get_records_select('groups', "courseid = ? AND id {$in_or_equal}",
                                                      array_merge(array($courseid),$groupparams))) {
                $erro = true;
            }
        } else {
            $erro = true;
        }

        $courses_to_sync = array();
        foreach($destinations as $dest) {
            if ($cdest = get_course($dest)) {
                $cdest->context = context_course::instance($dest);
                $courses_to_sync[] = $cdest;
            } else {
                $erro = true;
            }
        }

        if ($erro) {
            print_error('something went wrong');
        } else {
            $trace = new html_list_progress_trace();
            local_syncgroups_do_sync($groups_to_sync, $courses_to_sync, $trace);
            echo html_writer::link($url, get_string('back'));
        }
} else {

    $search = new destination_courses_search(array('url'=>$url), $courseid);

    $renderer = $PAGE->get_renderer('local_syncgroups');
    echo $renderer->destination_courses_selector($url, $search, $courseid);
}
echo $OUTPUT->footer();
