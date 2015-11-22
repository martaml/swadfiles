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
 * The main swadfiles configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_swadfiles
 * @copyright  2014 Marta Muñoz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 */
class mod_swadfiles_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = $this->_form;

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('swadfilesname', 'swadfiles'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'swadfilesname', 'swadfiles');

        // Adding the standard "intro" and "introformat" fields
        $this->add_intro_editor();

        //-------------------------------------------------------------------------------
        
        // Swad user for SOAP connection
        $mform->addElement('text', 'swaduser', get_string('swaduser', 'swadfiles'), array('size'=>'32'));
        $mform->addRule('swaduser', null, 'required', null, 'client');
        $mform->setType('swaduser',PARAM_TEXT);
        $mform->addHelpButton('swaduser', 'swaduser', 'swadtest');
        
        // Swad password for SOAP connection
        $mform->addElement('passwordunmask', 'swadpass', get_string('swadpass', 'swadfiles'), array('size'=>'32'));
        $mform->addRule('swadpass', null, 'required', null, 'client');
        $mform->setType('swadpass',PARAM_TEXT);
        $mform->addHelpButton('swadpass', 'swadpass', 'swadtest');
        
        // Swad course code
        $mform->addElement('text', 'swadcourse', get_string('swadcoursecode', 'swadfiles'), array('size'=>'32'));
        $mform->addRule('swadcourse', 'Need a number', 'numeric', null, 'client');
        $mform->addRule('swadcourse', null, 'required', null, 'client');
        $mform->setType('swadcourse',PARAM_INT);
        $mform->addHelpButton('swadcourse', 'swadcoursecode', 'swadtest');
        
        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }
}
