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

function xmldb_local_lae_uninstall() {
    global $DB;
    $dbman = $DB->get_manager();

    // By design the anonymous user remains
    // Restore normal forum schema
    $table = new xmldb_table('forum');
    $field = new xmldb_field('anonymous');
    if ($dbman->field_exists($table, $field)) $dbman->drop_field($table, $field);
    // Restore normal forum post schema
    $table = new xmldb_table('forum_posts');
    $field = new xmldb_field('hiddenuserid');
    if ($dbman->field_exists($table, $field)) $dbman->drop_field($table, $field);

    // Restore the normal course schema
    $table = new xmldb_table('course');
    $field = new xmldb_field('filedisplaydefault');
    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field);
    }
    return true;
}
