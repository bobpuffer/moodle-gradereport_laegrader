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

// Written at Louisiana State University.

require_once($CFG->dirroot . '/blocks/quickmail/lib.php');

class block_quickmail extends block_list {
    public function init() {
        $this->title = quickmail::_s('pluginname');
    }

    public function applicable_formats() {
        return array('site' => false, 'my' => false, 'course' => true);
    }

    public function has_config() {
        return true;
    }

    public function get_content() {
        global $CFG, $COURSE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

        $config = quickmail::load_config($COURSE->id);
        $permission = has_capability('block/quickmail:cansend', $context);

        $icon_class = array('class' => 'icon');

        if ($permission) {
            $cparam = array('courseid' => $COURSE->id);

            $send_email_str = quickmail::_s('composenew');
            $send_email = html_writer::link(
                new moodle_url('/blocks/quickmail/email.php', $cparam),
                $send_email_str
            );
            $this->content->items[] = $send_email;
            $this->content->icons[] = $OUTPUT->pix_icon('i/email', $send_email_str, 'moodle', $icon_class);

            $signature_str = quickmail::_s('signature');
            $signature = html_writer::link(
                new moodle_url('/blocks/quickmail/signature.php', $cparam),
                $signature_str
            );
            $this->content->items[] = $signature;
            $this->content->icons[] = $OUTPUT->pix_icon('i/edit', $signature_str, 'moodle', $icon_class);

            $draft_params = $cparam + array('type' => 'drafts');
            $drafts_email_str = quickmail::_s('drafts');
            $drafts = html_writer::link(
                new moodle_url('/blocks/quickmail/emaillog.php', $draft_params),
                $drafts_email_str
            );
            $this->content->items[] = $drafts;
            $this->content->icons[] = $OUTPUT->pix_icon('i/settings', $drafts_email_str, 'moodle', $icon_class);

            $history_str = quickmail::_s('history');
            $history = html_writer::link(
                new moodle_url('/blocks/quickmail/emaillog.php', $cparam),
                $history_str
            );
            $this->content->items[] = $history;
            $this->content->icons[] = $OUTPUT->pix_icon('i/settings', $history_str, 'moodle', $icon_class);
        }

        if (has_capability('block/quickmail:allowalternate', $context)) {
            $alt_str = quickmail::_s('alternate');
            $alt = html_writer::link(
                new moodle_url('/blocks/quickmail/alternate.php', $cparam),
                $alt_str
            );

            $this->content->items[] = $alt;
            $this->content->icons[] = $OUTPUT->pix_icon('i/edit', $alt_str, 'moodle', $icon_class);
        }

        if (has_capability('block/quickmail:canconfig', $context)) {
            $config_str = quickmail::_s('config');
            $config = html_writer::link(
                new moodle_url('/blocks/quickmail/config.php', $cparam),
                $config_str
            );
            $this->content->items[] = $config;
            $this->content->icons[] = $OUTPUT->pix_icon('i/settings', $config_str, 'moodle', $icon_class);
        }

        return $this->content;
    }
}
