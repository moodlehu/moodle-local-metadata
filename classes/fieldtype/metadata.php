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
 * @package local_metadata
 * @author Mike Churchward <mike.churchward@poetgroup.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2016 POET
 */

/**
 * Base class for the customisable metadata fields.
 *
 * @package local_metadata
 * @copyright  2016 POET
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @modified by Zhifen Lin 2018 project OER, Humboldt Universität
 */

namespace local_metadata\fieldtype;

defined('MOODLE_INTERNAL') || die();

class metadata {

    // These 2 variables are really what we're interested in.
    // Everything else can be extracted from them.

    /** @var int */
    public $fieldid;

    /** @var int */
    public $instanceid;

    /** @var stdClass */
    public $field;

    /** @var string */
    public $inputname;

    /** @var mixed */
    public $data;

    /** @var string */
    public $dataformat;

    /** @var string */
    protected $name;

    /**
     * Constructor method.
     * @param int $fieldid id of the profile from the local_metadata_field table
     * @param int $instanceid id of the instance for whom we are displaying data
     */
    public function __construct($fieldid=0, $instanceid=0) {
        $this->set_fieldid($fieldid);
        $this->set_instanceid($instanceid);
        $this->load_data();
        if (!isset($this->name)) {
            $this->name = '-- unknown --';
        }
    }

    /**
     * Abstract method: Adds the profile field to the moodle form class
     * @abstract The following methods must be overwritten by child classes
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_add($mform) {
        
        print_error('mustbeoveride', 'debug', '', 'edit_field_add');
    }

    /**
     * Display the data for this field
     * @return string
     */
    public function display_data() {        
        $options = new \stdClass();
        $options->para = false;
        
        return format_text($this->data, FORMAT_MOODLE, $options);
    }

    /**
     * Print out the form field in the edit profile page
     * @param moodleform $mform instance of the moodleform class
     * @return bool
     */
    public function edit_field($mform) {
        
        if (($this->field->visible != PROFILE_VISIBLE_NONE) ||
            (($this->field->contextlevel == CONTEXT_USER) && has_capability('moodle/user:update', \context_system::instance()))) {

            // zl_temp set default lom data
            // general
            $this->set_lom_default($mform, 'general', 'general_identifier');
            $this->set_lom_default($mform, 'general', 'general_title');
            $this->set_lom_default($mform, 'general', 'general_language');
            $this->set_lom_default($mform, 'general', 'general_description');
            $this->set_lom_default($mform, 'general', 'general_keyword');
            $this->set_lom_default($mform, 'general', 'general_structure');
            $this->set_lom_default($mform, 'general', 'general_aggregationLevel');

            // lifecycle
            $this->set_lom_default($mform, 'lifecycle', 'lifecycle_status');
            $this->set_lom_default($mform, 'lifecycle', 'lifecycle_contribute'); //author
            // second field for lifecycle_contribute
            $this->set_lom_default($mform, 'lifecycle', 'lifecycle_contribute_1'); //publisher

            // technical
            $this->set_lom_default($mform, 'technical', 'technical_format');
            $this->set_lom_default($mform, 'technical', 'technical_location');
            $this->set_lom_default($mform, 'technical', 'technical_requirement');
            
            
            // educational
            $this->set_lom_default($mform, 'educational', 'educational_interactivityType');
            $this->set_lom_default($mform, 'educational', 'educational_learningResourceType');
            //$this->set_lom_default($mform, 'educational', 'educational_interactivityLevel');
            $this->set_lom_default($mform, 'educational', 'educational_intendedEndUserRole');
            $this->set_lom_default($mform, 'educational', 'educational_context');

            // rights
            $this->set_lom_default($mform, 'rights', 'rights_cost');
            $this->set_lom_default($mform, 'rights', 'rights_copyrightAndOtherRestrictions');
            $this->set_lom_default($mform, 'rights', 'rights_description');
            
            // relation
            $this->set_lom_default($mform, 'relation', 'relation_kind');
            
            $this->edit_field_add($mform);
            $this->edit_field_set_default($mform);
            
            //  // zl_temp add some fields with help text
            
            $this->edit_field_set_required($mform);
            
            return true;
        }
        return false;
    }

    /**
     * Tweaks the edit form
     * @param moodleform $mform instance of the moodleform class
     * @return bool
     */
    public function edit_after_data($mform) {
        
        if (($this->field->visible != PROFILE_VISIBLE_NONE) ||
            (($this->field->contextlevel == CONTEXT_USER) && has_capability('moodle/user:update', \context_system::instance()))) {
            $this->edit_field_set_locked($mform);
            return true;
        }
        
        return false;
    }

    /**
     * Saves the data coming from form
     * @param stdClass $new data coming from the form
     * @return mixed returns data id if success of db insert/update, false on fail, 0 if not permitted
     */
    public function edit_save_data($new) {
        global $DB;
        
        // zl_temp: changed to handle the sub-structure with id "$this->inputname.$sub"
                
        $subentries = [];
        $has_sub = false;
        $data = new \stdClass(); //original
        
        foreach ($new as $key=>$val) {
            // if $this->inputname is part of, or same as the $key, only handle this->inputname
            if ($pos = strpos($key, $this->inputname) !== false) {
                
                // zl_temp substruct  
                // if $key is langer as $this->inputname, 

                if (strcmp($key, $this->inputname) > 0) {    
                    $subentries[$key] = $val;
                    $len1 = strlen($key);
                    $len2 = strlen($this->inputname);
                    $diff = $len1 - $len2;
                    $over = substr($key, $len2);
                    // if $over start with '_')
                    if (strpos($over, '_') !== false) {
                        $has_sub = true;
                    }
                }

                if (!$has_sub) {    // original code start
                    $new->{$this->inputname} = $this->edit_save_data_preprocess($new->{$this->inputname}, $data);
            
                    $data->instanceid  = $new->id;
                    $data->fieldid = $this->field->id;
                    $data->data    = $new->{$this->inputname};
            
                    if ($dataid = $DB->get_field('local_metadata', 'id', ['instanceid' => $data->instanceid, 'fieldid' => $data->fieldid])) {
                        $data->id = $dataid;                
                        $DB->update_record('local_metadata', $data);
                    } else {
                        $DB->insert_record('local_metadata', $data);
                    }  // original code end
                }
                else {
                    $newdata='';

                    foreach ($subentries as $key=>$val) {

                        // zl_temp handle substruct if $this->inputname is part of, or same as the $key
                        
                        // these cases should be avoid: e.g.if key is local_metadata_field_lifecycle_contribute_1_role#value
                        // $this->inputname is: local_metadata_field_lifecycle_contribute

                        if ($pos = strpos($key, $this->inputname)!== false) {
                            // get field shortname from key
                            $char_is_num = substr($key, $pos + strlen($this->inputname), 1);
                            if (!is_numeric($char_is_num)) {

                                $newdata .= $val;
                                $newdata .= '|';

                                $data->instanceid  = $new->id;
                                $data->fieldid = $this->field->id;
                                $data->data    = $newdata;

                                if ($dataid = $DB->get_field('local_metadata', 'id', ['instanceid' => $data->instanceid, 'fieldid' => $data->fieldid])) {
                                    $data->id = $dataid;
                                    $DB->update_record('local_metadata', $data);
                                } else {
                                    $DB->insert_record('local_metadata', $data);
                                }
                            }
                        }
                    }
                    $newdata='';
                }
            }
            $has_sub = false;
        }
    }

    /**
     * Validate the form field from profile page
     *
     * @param stdClass $new
     * @return  string  contains error message otherwise null
     */
    public function edit_validate_field($new) {
        global $DB;

        $errors = [];
        // Get input value.
        
        if (isset($new->{$this->inputname})) {
            if (is_array($new->{$this->inputname}) && isset($new->{$this->inputname}['text'])) {
                $value = $new->{$this->inputname}['text'];
            } else {
                $value = $new->{$this->inputname};
            }
        } else {
            $value = '';
        }

        // Check for uniqueness of data if required.
        if ($this->is_unique() && (($value !== '') || $this->is_required())) {
            $data = $DB->get_records_sql('
                    SELECT id, instanceid
                      FROM {local_metadata}
                     WHERE fieldid = ?
                       AND ' . $DB->sql_compare_text('data', 255) . ' = ' . $DB->sql_compare_text('?', 255),
                    [$this->field->id, $value]);
            if ($data) {
                $existing = false;
                foreach ($data as $v) {
                    if ($v->instanceid == $new->id) {
                        $existing = true;
                        break;
                    }
                }
                if (!$existing) {
                    $errors[$this->inputname] = get_string('valuealreadyused');
                }
            }
        }
        return $errors;
    }

    /**
     * Sets the default data for the field in the form object
     * @param  moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_default($mform) {
        if (!empty($this->field->defaultdata)) {
            $mform->setDefault($this->inputname, $this->field->defaultdata);
        }
    }
 
    /** 
     * zl_temp added set_lom_default
     * Sets the lom default general/description for the field in the form object
     * @param  cat, field
     */
    public function set_lom_default($mform, $cat, $cat_field) {
        global $DB;
        $default_time='';
        $default_value='';
        // set title         
        
        if ($categoryid = $DB->get_field('local_metadata_category', 'id', ['name' => $cat])) {
            if ($this->fieldid == $DB->get_field('local_metadata_field', 'id', ['categoryid' => $categoryid, 'shortname' => $cat_field])) {

                switch ($cat_field) {
                    case 'general_identifier':  // dc:identifier
                        $coursename = $DB->get_field('course', 'fullname', ['id' => $this->instanceid]);
                        $default_value = "MOODLE|" . "OER-" . $coursename;
                        break;

                    case 'general_title':  //dc:title
                        $default_value = $DB->get_field('course', 'fullname', ['id' => $this->instanceid]);
                        break;

                    case 'general_language':  //dc:language
                        $default_value = $DB->get_field('course', 'lang', ['id' => $this->instanceid]);
                        if (empty($default_value)) {
                            $default_value = current_language();
                        }
                        break;

                    case 'general_description':  // dc:description
                        $default_value = strip_tags($DB->get_field('course', 'summary', ['id' => $this->instanceid]));
                        break;

                    case 'general_keyword':
                        $tag_id = $DB->get_field('tag_instance', 'id', ['itemid' => $this->instanceid]);
                        if (!empty($tag_id)) {   
                            $default_value = $DB->get_field('tag', 'name', ['id' => $tag_id]);
                        }
                        break;
                    
                    case 'general_structure':
                        $kursformat = $DB->get_field('course', 'format', ['id' => $this->instanceid]);
                        $default_value = 'LOMv1.0|MoodleCourse';
                        break;

                    case 'general_aggregationLevel':
                        $default_value = 3;
                        break;
                        
                    case 'lifecycle_status':
                        $default_value = 'LOMv1.0|';
                        break;
                        
                    case 'lifecycle_contribute':  // dc:date
                        $createtime = $DB->get_field('course', 'timecreated', ['id' => $this->instanceid]);
                        $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
                        $context = get_context_instance(CONTEXT_COURSE, $this->instanceid);

                        $teachers = get_role_users($role->id, $context);
                        
                        $author = '';
                        $author_id = '';
                        $serverName = $_SERVER['SERVER_NAME'];
                        foreach ($teachers as $teacher) {
                            $author .= $teacher->firstname.' '.$teacher->lastname .',';
                            $author_id = $teacher->id;
                        }
                        
                        //$author_chop = substr($author, 0, -1);  // cut the last ','
                        //$default_value = 'LOMv1.0|author|'.$author_chop.'|'.$createtime;

                        $is_https = isset($_SERVER['HTTPS']);

                        if (!empty($author_id)) {
                            if (empty($is_https)) {
                                $author_profile = 'http://' .$serverName .'/user/profile.php?id=' .$author_id;
                            } else {
                                $author_profile = 'https://' .$serverName .'/user/profile.php?id=' .$author_id;
                            }
                        } else {
                            $author_profile = '';
                        }                        
                        $default_value = 'LOMv1.0|author|'.$author_profile.'|'.$createtime;
                        
                        $default_time = "|||" .strftime("%F", $createtime);

                        break;
                        
                    case 'lifecycle_contribute_1':  // dc:publisher
                        $createtime = $DB->get_field('course', 'timecreated', ['id' => $this->instanceid]);
                        $default_value = 'LOMv1.0|publisher||'.$createtime;
                        
                        $default_time = "|||" .strftime("%F", $createtime);

                        break;    

                    case 'technical_format':  //dc:format
                        //$default_value = $DB->get_field('course', 'format', ['id' => $this->instanceid]);
                        $default_value = 'text/html';
                        break;

                    case 'technical_location':
                        $serverName = $_SERVER['SERVER_NAME'];
                        $is_https = isset($_SERVER['HTTPS']);
                        if (empty($is_https)) {
                            $default_value = 'http://' .$serverName .'/course/view.php?id=' .$this->instanceid;
                        } else {
                            $default_value = 'https://' .$serverName .'/course/view.php?id=' .$this->instanceid;
                        }
                        break;
                        
                    case 'technical_requirement':
                        $version = $DB->get_field('config', 'value', ['name' => 'backup_release']);
                        $build = $DB->get_field('config', 'value', ['name' => 'version']);
                        $moodle = 'Moodle ' .$version .' Build:' . $build;

                        $default_value = 'https://download.moodle.org|LMS Moodle|' .$moodle .'|';
                        break;
                        
                    case 'educational_interactivityType':  
                        $default_value = "LOMv1.0|";
                        break;
                        
                    case 'educational_learningResourceType':  //dc:type
                        $default_value = "LOMv1.0|";
                        break;

                    case 'educational_interactivityLevel':
                        $default_value = "very high";
                        break;
 
                    case 'educational_intendedEndUserRole':
                        $default_value = "LOMv1.0|student";
                        break;                       

                    case 'educational_context':
                        $default_value = "LOMv1.0|higher education";
                        break;
                        
                    case 'rights_cost':
                        $default_value = "LOMv1.0|" . "no";
                        break;
                        
                    case 'rights_copyrightAndOtherRestrictions':
                        $default_value = "LOMv1.0|" . "yes";
                        break;
                    
                    case 'rights_description':
                        $default_value = "http://creativecommons.org/licenses/by/3.0/";
                        break;
                    
                    case 'relation_kind':
                        $default_value = "LOMv1.0|";
                        break;
                        
                    default:
                        $default_value = '';
                        break;
                }

                $value = $DB->get_field('local_metadata', 'data', ['instanceid' => $this->instanceid, 'fieldid' => $this->fieldid]);


                if (empty($value)) {

                    // zl_temp set to form as default
                    if (!empty($default_time)) {
                        $mform->setDefault($this->inputname, $default_time);
                    } else {
                        $mform->setDefault($this->inputname, $default_value);
                    }
                    // zl_temp set to database
                    $data = new \stdClass();
                    $data->instanceid = $this->instanceid;
                    $data->fieldid = $this->fieldid;
                    $data->data    = $default_value;

                    if ($dataid = $DB->get_field('local_metadata', 'id', ['instanceid' => $data->instanceid, 'fieldid' => $data->fieldid])) {
                        $data->id = $dataid;
                        $DB->update_record('local_metadata', $data);
                    }
                    else {
                        $DB->insert_record('local_metadata', $data);
                    }
                }
            }
        }
    }
    
    /**
     * Sets the required flag for the field in the form object
     *
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_required($mform) {
        global $USER;

        // Handling for specific contexts. TODO - Abstract this.
        if ($this->is_required() &&
            (($this->field->contextlevel != CONTEXT_USER) ||
            ($this->instanceid == $USER->id || isguestuser()))) {
            $mform->addRule($this->inputname, get_string('required'), 'required', null, 'client');
        }
    }

    /**
     * HardFreeze the field if locked.
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_locked($mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked() &&
            (($this->field->contextlevel == CONTEXT_USER) && !has_capability('moodle/user:update', context_system::instance()))) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, $this->data);
        }
    }

    /**
     * Hook for child classess to process the data before it gets saved in database
     * @param stdClass $data
     * @param stdClass $datarecord The object that will be used to save the record
     * @return  mixed
     */
    public function edit_save_data_preprocess($data, $datarecord) {
        return $data;
    }

    /**
     * Loads a instance object with data for this field ready for the edit profile
     * form
     * @param stdClass $instance a context object
     */
    public function edit_load_instance_data($instance) {

        if ($this->data !== null) {
            $instance->{$this->inputname} = $this->data;
        }
    }

    /**
     * Check if the field data should be loaded into the instance object
     * By default it is, but for field types where the data may be potentially
     * large, the child class should override this and return false
     * @return bool
     */
    public function is_instance_object_data() {
        return true;
    }

    /**
     * Accessor method: set the instanceid for this instance
     * @internal This method should not generally be overwritten by child classes.
     * @param integer $instanceid id from the instance table
     */
    public function set_instanceid($instanceid) {
        $this->instanceid = $instanceid;
    }

    /**
     * Accessor method: set the fieldid for this instance
     * @internal This method should not generally be overwritten by child classes.
     * @param integer $fieldid id from the local_metadata_field table
     */
    public function set_fieldid($fieldid) {
        $this->fieldid = $fieldid;
    }

    /**
     * Accessor method: Load the field record and instance data associated with the
     * object's fieldid and instanceis
     * @internal This method should not generally be overwritten by child classes.
     */
    public function load_data() {
        global $DB;
        
        // Load the field object.
        if (($this->fieldid == 0) || (!($field = $DB->get_record('local_metadata_field', ['id' => $this->fieldid])))) {
            $this->field = null;
            $this->inputname = '';
        } else {
            $this->field = $field;
            $categoryname = $DB->get_field('local_metadata_category', 'name', ['id' => $field->categoryid]); 
            $this->inputname = 'local_metadata_field_'.$field->shortname;  // zl_temp,field->shortname is already changed to {catname}_{fieldname}
        }
         
        if (!empty($this->field)) {
            $params = ['instanceid' => $this->instanceid, 'fieldid' => $this->fieldid];
            if ($data = $DB->get_record('local_metadata', $params, 'data, dataformat')) {
                $this->data = $data->data;
                $this->dataformat = $data->dataformat;            
            } else {
                $this->data = $this->field->defaultdata;
                $this->dataformat = FORMAT_HTML;
            }
        } else {
            $this->data = null;
        }        
    }

    /**
     * Check if the field data is visible to the current user
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_visible() {
        global $USER;

        switch ($this->field->visible) {
            case PROFILE_VISIBLE_ALL:
                return true;
            case PROFILE_VISIBLE_PRIVATE:
                if ($this->userid == $USER->id) {
                    return true;
                } else {
                    return (($this->field->contextlevel != CONTEXT_USER) ||
                            has_capability('moodle/user:viewalldetails', \context_user::instance($this->userid)));
                }
            default:
                return (($this->field->contextlevel != CONTEXT_USER) ||
                        has_capability('moodle/user:viewalldetails', \context_user::instance($this->userid)));
        }
    }

    /**
     * Check if the field data is considered empty
     * @internal This method should not generally be overwritten by child classes.
     * @return boolean
     */
    public function is_empty() {
        return (($this->data != '0') && empty($this->data));
    }

    /**
     * Check if the field is required on the edit profile page
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_required() {
        return (boolean)$this->field->required;
    }

    /**
     * Check if the field is locked on the edit profile page
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_locked() {
        return (boolean)$this->field->locked;
    }

    /**
     * Check if the field data should be unique
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_unique() {
        return (boolean)$this->field->forceunique;
    }

    /**
     * Check if the field should appear on the signup page
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_signup_field() {
        return (boolean)$this->field->signup;
    }

    /**
     * Return the field settings suitable to be exported via an external function.
     * By default it return all the field settings.
     *
     * @return array all the settings
     * @since Moodle 3.2
     */
    public function get_field_config_for_external() {
        return (array)$this->field;
    }

    /**
     * Return the field type and null properties.
     * This will be used for validating the data submitted by a user.
     *
     * @return array the param type and null property
     * @since Moodle 3.2
     */
    public function get_field_properties() {
        return [PARAM_RAW, NULL_NOT_ALLOWED];
    }

    /**
     * Magic method for getting properties.
     * @param string $name
     * @return mixed
     * @throws \coding_exception
     */
    public function __get($name) {
        $allowed = ['name'];
        if (in_array($name, $allowed)) {
            return $this->$name;
        } else {
            throw new \coding_exception($name.' is not a publicly accessible property of '.get_class($this));
        }
    }
    
    /**
     * Add some help text (not for field type 'state'
     *
     * @param moodleform $mform instance of the moodleform class
     */
    public function add_help_button($mform, $inputname) {
        
        $shortname = str_replace('local_metadata_field_', '', $inputname);
        $helpname = 'help_' .$shortname;
        
        $mform->addHelpButton($inputname, $helpname, 'local_lom');

    }
}
