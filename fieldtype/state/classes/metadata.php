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
 * State profile field.
 *
 * @package    profilefield_state
 * @Zhifen Lin 2018 project OER, Humboldt Universität
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadatafieldtype_state;

defined('MOODLE_INTERNAL') || die;

/**
 * Class local_metadata_field_state
 *
 */
class metadata extends \local_metadata\fieldtype\metadata {
    /**
     * Constructor method.
     *
     * @param int $fieldid
     * @param int $instanceid
     */
    public function __construct($fieldid=0, $instanceid=0) {
        // First call parent constructor.
        parent::__construct($fieldid, $instanceid);

        $this->name = 'State';
    }

    /**
     * Adds elements for this field type to the edit form.
     * @param moodleform $mform
     */
    public function edit_field_add($mform) {
        global $DB;

        // zl_temp handle the substructure
        $entries = explode("\n", $this->field->defaultdata);
        $data_entry = $DB->get_field('local_metadata', 'data', ['instanceid' => $this->instanceid, 'fieldid' => $this->fieldid]);
        $value = explode('|', $data_entry);
        
        $output = '';
        $index = 0;
        
        if (is_array($entries)) {
            foreach ($entries as $entry) {
                if (!empty($entry)){
                    $entry = trim($entry);
                    // zl_temp add in the metadata form '[sub]'. $entry is the subfield
                    // zl_temp special handling for requirement. (add orComposite)
                    if ($this->field->name == 'requirement') {
                        $showname = get_string($this->field->name,'local_lom') . '[orComposite]'. '[' . get_string($entry, 'local_lom') . ']';
                        
                    }
                    else {
                        $showname = get_string($this->field->name,'local_lom') . '[' . get_string($entry, 'local_lom') . ']';
                    }
                    
                    $inputid = $this->inputname. '_' .$entry;   // zl_temp generate the 3rd level field $inputid

                    // zl_temp, handle 4th level lifecycle_contribute_role
                    //if ((strpos($inputid, 'lifecycle_contribute') !== false) && (strpos($inputid, 'role') !== false)){
                    if (($inputid == 'local_metadata_field_lifecycle_contribute_role')||($inputid == 'local_metadata_field_lifecycle_contribute_1_role')) {
                        $save = $inputid;
                        
                        $showname_a = $showname . '[source]';
                        $inputid_a = $save . '#source';
                        // zl_temp do not show those fields with[source] with 'LOMv1.0'
                        //$mform->addElement('text', $inputid_a, format_string($showname_a),'size="80" ');   //contribute [role][source]

                        $mform->addElement('hidden', $inputid_a);
                        
                        if (!empty($value[$index])) {
                            $mform->setDefault($inputid_a, $value[$index]); // We MUST clean this before display!
                        }
                        
                        $index++;
                        
                        $showname_b = $showname . '[value]';
                        $inputid_b = $save . '#value';
                        $mform->addElement('text', $inputid_b, format_string($showname_b),'size="80" ');   //contribute [role][value]
                        
                        $mform->setType($inputid_b, PARAM_TEXT); // We MUST clean this before display!
                        
                        if (!empty($value[$index])) {
                            $mform->setDefault($inputid_b, $value[$index]); // We MUST clean this before display!
                            $mform->addHelpButton($inputid_b, 'help_lifecycle_contribute_role', 'local_lom');
                        }
                        $index++;
                    }
                    // zl_temp handle 4th level lifecycle_contribute_date
                    elseif (($inputid == 'local_metadata_field_lifecycle_contribute_date') || ($inputid == 'local_metadata_field_lifecycle_contribute_1_date') ||
                        ($inputid == 'local_metadata_field_metaMetadata_contribute_date')) {
                        /*elseif (((strpos($inputid, 'lifecycle_contribute') !== false) && (strpos($inputid, 'date') !== false)) ||
                        ($inputid == 'local_metadata_field_metaMetadata_contribute_date')) {*/
                            
                        $mform->addElement('date_selector', $inputid, format_string($showname));
                        $mform->setType($inputid, PARAM_TEXT);
        
                        if (!empty($data_entry)) {
                            $mform->setDefault($inputid, $value[$index]);
                        }
                        $mform->addHelpButton($inputid, 'help_lifecycle_contribute_date', 'local_lom');
                        $index++;
                    }
                    else { //normal case
                        if (strpos($showname, '[source]') !== false){  // zl_temp: do not show those fields with[source] with 'LOMv1.0'
                        
                            $mform->addElement('hidden', $inputid); //zl_temp hidden source
                        
                            $mform->setType($inputid, PARAM_TEXT);
                            
                            if (!empty($data_entry)) {
                                $mform->setDefault($inputid, $value[$index]);
                            }

                            $index++;
                        }
                        else {

                            $mform->addElement('text', $inputid, format_string($showname),'size="80" ');
                        
                            $mform->setType($inputid, PARAM_TEXT); 


                            if (!empty($data_entry)) {
                                $mform->setDefault($inputid, $value[$index]);
                            }
                            
                            // zl_temp add help button
                            $this->add_help_button($mform, $inputid);

                            $index++;
                        }
                    }
                }
            }
        }
    }

    /**
     * Return the field type and null properties.
     * This will be used for validating the data submitted by a user.
     */
    public function get_field_properties() {
        return [PARAM_TEXT, NULL_NOT_ALLOWED];
    }
    
}


