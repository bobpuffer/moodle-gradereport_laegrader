<?php
  
defined('MOODLE_INTERNAL') || die();


/**
 * Code run after the forum module database tables have been created.
 */
function xmldb_forum_install() {
    global $CFG;
    require($CFG->dirroot . '/mod/forum/lib.php');
    return forum_add_anonymous_user();
}
      