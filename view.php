<?php

/**
 * Prints a particular instance of swadfiles
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_swadfiles
 * @copyright  2014 Marta Muñoz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/// (Replace swadfiles with the name of your module and remove this line)

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot.'/mod/swadtest/lib.php');
require_once($CFG->dirroot.'/mod/folder/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // swadfiles instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('swadfiles', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $swadfiles  = $DB->get_record('swadfiles', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $swadfiles  = $DB->get_record('swadfiles', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $swadfiles->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('swadfiles', $swadfiles->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

add_to_log($course->id, 'swadfiles', 'view', "view.php?id={$cm->id}", $swadfiles->name, $cm->id);

/// Print the page header
$PAGE->set_url('/mod/swadfiles/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($swadfiles->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Output starts here
echo $OUTPUT->header();

if ($swadfiles->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('swadfiles', $swadfiles, $cm->id), 'generalbox mod_introbox', 'swadfilesintro');
}

//-------------------------------------------------INICIO FUNCIONES SOAP-------------------------------------------------
    // ENCRIPTACIÓN DE CONTRASEÑA DE SWAD
    $pass = hash('sha512', $swadfiles->swadpass, true);    
    $pass = base64url_encode($pass);
    $appKey = "martamod";

    $parameters = array(
        'userID' => $swadfiles->swaduser,
        'userPassword' => $pass,
        'appKey' => $appKey
    );
    
    $client = new SoapClient("http://swad.ugr.es/ws/swad.wsdl");
   
    $login = $client->__call( "loginByUserPasswordKey", array($parameters));
    
    $parameters = array(
        'wsKey' => $login->wsKey
    );
    
    $coursesinfo = $client->__call( "getCourses", array($parameters));
    
    for ($j=0 ; $j<sizeof($coursesinfo->coursesArray->item); $j++) {
        if ($coursesinfo->coursesArray->item[$j]->courseCode == $swadfiles->swadcourse) {
            $courseinfo = $coursesinfo->coursesArray->item[$j]; 
        }
    }
    
    echo $OUTPUT->heading($courseinfo->courseFullName);
    
    $parameters = array(
        'wsKey' => $login->wsKey,
        'courseCode' => $swadfiles->swadcourse,
        'groupCode' => 0,
        'treeCode' => 1
    );
    $dirtree = $client->__call("getDirectoryTree", array($parameters));
    
    $lines = explode("\n", $dirtree->tree);
    
    echo "<pre>";

    $path = array();
    
    $filedir = $courseinfo->courseShortName.date(DATE_ATOM).".txt";
    
    array_push($path, $filedir);
    
    $contextuser = context_user::instance($USER->id);
    $fs = get_file_storage();
    
    $dirinfo = array(
      'contextid' => $contextuser->id,
      'component' => 'user',     
      'filearea'  => 'private',   
      'itemid'    => 0,             
      'filepath'  => "/".$filedir."/");  
                
    $fs->create_directory( $dirinfo['contextid'], $dirinfo['component'], $dirinfo['filearea'], 
                       $dirinfo['itemid'], $dirinfo['filepath'], null);    
   
    foreach ($lines as $a) {
        $pattern = '<dir name=\"([^"]+)\"> ';
        if ( strstr($a, "</dir>") ) {
            array_pop($path);
        } else if ( preg_match($pattern, $a, $result) ) {
        
            echo "<br>";
            
            array_push($path, $result[1]);

            $contextuser = context_user::instance($USER->id);

            $fs = get_file_storage();
            $filepath = "/";
            foreach ($path as $p) {
                $filepath .= $p."/";
                echo "   ";
            }
            
            echo $result[1];
            
            $dirinfo = array(
                'contextid' => $contextuser->id, 
                'component' => 'user',     
                'filearea'  => 'private',     
                'itemid'    => 0,               
                'filepath'  => $filepath);  
                
            $fs->create_directory( $dirinfo['contextid'], $dirinfo['component'], $dirinfo['filearea'], 
                       $dirinfo['itemid'], $dirinfo['filepath'], null);    
            
        } else if ( strstr($a, "<file name") ) {
        
	    echo "<br>";
            $filepath = "/";
            foreach ($path as $p) {
                $filepath .= $p."/";
                echo "   ";
            }
            
            $filedownload = new SimpleXMLElement($a);
            
            $parameters = array(
                'wsKey' => $login->wsKey,
                'fileCode' => $filedownload->code
            );
            
            echo $filedownload["name"];
            
            $dir = $CFG->dataroot."/temp/".$filedownload["name"];
            
            // LLAMADA SOAP DESCARGA DEL ARCHIVO
            $file = $client->__call("getFile", array($parameters));
                                                
            # open file to write
            $fp = fopen ($dir, 'w+');
            # start curl
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $file->URL );
            # set return transfer to false
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false );
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true );
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
            # increase timeout to download big files
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10 );
            # write data to local file
            curl_setopt($ch, CURLOPT_FILE, $fp );
            # execute curl
            curl_exec($ch);
            # close curl
            curl_close($ch);
            # close local file
            fclose($fp);
            
            // CONTEXTO DEL USUARIO PARA QUE SEAN ARCHIVOS PRIVADOS DEL USUARIO
            $contextuser = context_user::instance($USER->id);

            $fs = get_file_storage();
            
            // Párametros para crear archivo
            $fileinfo = array(
                'contextid' => $contextuser->id, 
                'component' => 'user',     
                'filearea'  => 'private',     
                'itemid'    => 0,               
                'filepath'  => $filepath,           
                'filename'  => $file->fileName,
                'author'    => $file->publisherName,
                'license'   => $file->license); 
            
            $fs->create_file_from_pathname($fileinfo, $dir);
        }
    }
    echo "<br>";
    echo "<br>";
    echo $OUTPUT->box( get_string( 'directory_loaded', 'swadfiles') );

//----------------------------------------------------FIN FUNCIONES SOAP------------------------------------------------------

// Finish the page
echo $OUTPUT->footer();