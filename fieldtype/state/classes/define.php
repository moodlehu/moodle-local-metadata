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
 * State profile field definition.
 *
 * @package    profilefield_state
 * @Zhifen Lin 2018 project OER, Humboldt Universität
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadatafieldtype_state;

defined('MOODLE_INTERNAL') || die;

/**
 * Class local_metadata_define_state.
 *
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class define extends \local_metadata\fieldtype\define_base {

    /**
     * Add elements for creating/editing a state profile field.
     * @param moodleform $form
     */
    public function define_form_specific($form) {
        // Default data.
        $form->addElement('textarea', 'defaultdata', get_string('profiledefaultdata', 'admin'), ['rows' => 5, 'cols' => 80]);
        $form->setType('defaultdata', PARAM_TEXT); // We have to trust person with capability to edit this default description.
    }

}