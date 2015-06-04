<?php
/*========================================================================
*   Open eClass 2.3
*   E-learning and Course Management System
* ========================================================================
*  Copyright(c) 2003-2010  Greek Universities Network - GUnet
*  A full copyright notice can be read in "/info/copyright.txt".
*
*  Developers Group:	Costas Tsibanis <k.tsibanis@noc.uoa.gr>
*			Yannis Exidaridis <jexi@noc.uoa.gr>
*			Alexandros Diamantidis <adia@noc.uoa.gr>
*			Tilemachos Raptis <traptis@noc.uoa.gr>
*
*  For a full list of contributors, see "credits.txt".
*
*  Open eClass is an open platform distributed in the hope that it will
*  be useful (without any warranty), under the terms of the GNU (General
*  Public License) as published by the Free Software Foundation.
*  The full license can be read in "/info/license/license_gpl.txt".
*
*  Contact address: 	GUnet Asynchronous eLearning Group,
*  			Network Operations Center, University of Athens,
*  			Panepistimiopolis Ilissia, 15784, Athens, Greece
*  			eMail: info@openeclass.org
* =========================================================================*/

function is_admin($username, $password, $mysqlMainDb) {

    mysql_select_db($mysqlMainDb);
    $r = mysql_query("SELECT * FROM user, admin WHERE admin.idUser = user.user_id
            AND user.username = '$username' AND user.password = '$password'");
    if (!$r or mysql_num_rows($r) == 0) {
        return FALSE;
    } else {
        //$row = mysql_fetch_array($r);
        //$_SESSION['uid'] = $row['user_id'];
        //we need to return the user id
        //or setup session UID with the admin's User ID so that it validates @ init.php
        return TRUE;
    }
}

session_start();

//Flag for fixing relative path
//See init.php to undestand its logic
$path2add=2;

include '../include/baseTheme.php';
include '../include/lib/fileUploadLib.inc.php';
include '../include/lib/forcedownload.php';

set_time_limit(0);

// We need some messages from all languages to upgrade course accueil table
foreach ($native_language_names as $code => $name) {
        $templang = langcode_to_name($code);
        // include_messages
        include("${webDir}modules/lang/$templang/common.inc.php");
        $extra_messages = "${webDir}/config/$templang.inc.php";
        if (file_exists($extra_messages)) {
                include $extra_messages;
        } else {
                $extra_messages = false;
        }
        include("${webDir}modules/lang/$templang/messages.inc.php");
        if ($extra_messages) {
                include $extra_messages;
        }
        $global_messages['langCourseUnits'][$templang] = $langCourseUnits;
}
// include_messages
$language = "greek";
include("${webDir}modules/lang/$language/common.inc.php");
$extra_messages = "${webDir}/config/$language.inc.php";
if (file_exists($extra_messages)) {
        include $extra_messages;
} else {
        $extra_messages = false;
}
include("${webDir}modules/lang/$language/messages.inc.php");
if ($extra_messages) {
        include $extra_messages;
}

$nameTools = $langUpgrade;
$tool_content = "";

$auth_methods = array("imap","pop3","ldap","db");
$OK = "[<font color='green'> $langSuccessOk </font>]";
$BAD = "[<font color='red'> $langSuccessBad </font>]";

// default quota values  (if needed)
$diskQuotaDocument = 40000000;
$diskQuotaGroup = 40000000;
$diskQuotaVideo = 20000000;
$diskQuotaDropbox = 40000000;

$fromadmin = true;

if (isset($_POST['submit_upgrade'])) {
	$fromadmin = false;
}

if (!defined('UTF8')) {
        $Institution = iconv('ISO-8859-7', 'UTF-8', $Institution);
        $postaddress = iconv('ISO-8859-7', 'UTF-8', $postaddress);
}

if (!isset($_POST['submit2'])) {
        if(isset($encryptedPasswd) and $encryptedPasswd) {
                $newpass = md5(@$_REQUEST['password']);
        } else {
                // plain text password since the passwords are not hashed
                $newpass = @$_REQUEST['password'];
        }

        if (!is_admin(@mysql_real_escape_string($_REQUEST['login']), mysql_real_escape_string($newpass), $mysqlMainDb)) {
                $tool_content .= "<p>$langUpgAdminError</p>
                        <center><a href=\"index.php\">$langBack</a></center>";
                draw($tool_content, 0);
                exit;
        }
}

// Make sure 'video' subdirectory exists and is writable
if (!file_exists('../video')) {
        if (!mkdir('../video')) {
                die("$langUpgNoVideoDir");
        }
} elseif (!is_dir('../video')) {
        die("$langUpgNoVideoDir2");
} elseif (!is_writable('../video')) {
        die("$langUpgNoVideoDir3");
}

// ********************************************
// upgrade config.php
// *******************************************
if (!@chdir("../config/")) {
     die ("$langConfigError4");
}


        $closeregdefault = $close_user_registration? ' checked="checked"': '';
        // get old contact values
        $tool_content .= "<form action='$_SERVER[PHP_SELF]' method='post'>" .
                "<div class='kk'>" .
                "<p>$langConfigFound" .
                "<br />$langConfigMod</p>" .
                "<fieldset><legend>$langUpgContact</legend>" .
                "<table><tr><td style='border: 1px solid #FFFFFF;'>$langInstituteShortName:</td>" .
                "<td style='border: 1px solid #FFFFFF;'>&nbsp;<input class=auth_input_admin type='text' size='40' name='Institution' value='".@$Institution."'></td></tr>" .
                "<tr><td style='border: 1px solid #FFFFFF;'>$langUpgAddress</td>" .
                "<td style='border: 1px solid #FFFFFF;'>&nbsp;<textarea rows='3' cols='40' class=auth_input_admin name='postaddress'>".@$postaddress."</textarea></td></tr>" .
                "<tr><td style='border: 1px solid #FFFFFF;'>$langUpgTel</td>" .
                "<td style='border: 1px solid #FFFFFF;'>&nbsp;<input class=auth_input_admin type='text' name='telephone' value='".@$telephone."'></td></tr>" .
                "<tr><td style='border: 1px solid #FFFFFF;'>Fax:</td>" .
                "<td style='border: 1px solid #FFFFFF;'>&nbsp;<input class=auth_input_admin type='text' name='fax' value='".@$fax."'></td></tr></table></fieldset>
                <fieldset><legend>$langUpgReg</legend>
                <table cellpadding='1' cellspacing='2' border='0' width='99%'>
                <tr><td style='border: 1px solid #FFFFFF;''>
                <span class='explanationtext'>$langViaReq</span></td>
                <td style='border: 1px solid #FFFFFF;'><input type='checkbox' name='reguser' $closeregdefault></td>
                </tr>
                </table></fieldset>
                <p><input name='submit2' value='$langCont' type='submit'></p>
                </div></form>";

draw($tool_content, 0);
