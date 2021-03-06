<?php
// ------------------------------------------------------------------------- 

require_once "../../mainfile.php";
if ( file_exists(XOOPS_ROOT_PATH ."/modules/animal/language/".$xoopsConfig['language']."/templates.php") ) 
    require_once XOOPS_ROOT_PATH ."/modules/animal/language/".$xoopsConfig['language']."/templates.php";
else 
	include_once XOOPS_ROOT_PATH ."/modules/animal/language/english/templates.php";
// Include any common code for this module.
require_once(XOOPS_ROOT_PATH ."/modules/animal/include/functions.php");
//require_once(XOOPS_ROOT_PATH ."/modules/animal/include/css.php");
// Get all HTTP post or get parameters into global variables that are prefixed with "param_"
import_request_variables("gp", "param_");

// This page uses smarty templates. Set "$xoopsOption['template_main']" before including header
$xoopsOption['template_main'] = "pedigree_pedigree.html";


require_once(XOOPS_ROOT_PATH."/class/xoopsformloader.php");
require_once(XOOPS_ROOT_PATH ."/modules/animal/include/class_field.php");

include XOOPS_ROOT_PATH.'/header.php';
//get module configuration
$module_handler =& xoops_gethandler('module');
$module         =& $module_handler->getByDirname('animal');
$config_handler =& xoops_gethandler('config');
$moduleConfig   =& $config_handler->getConfigsByCat(0, $module->getVar('mid'));

//draw pedigree
pedigree_main($_GET['pedid']);


//comments and footer
include XOOPS_ROOT_PATH."/footer.php";

//
// Displays the "Main" tab of the module
//
function pedigree_main($ID)
{
	global $xoopsTpl;
	global $xoopsDB;
	global $moduleConfig;
	
	$a = (!isset($_GET['pedid']) ? $a = 1 : $a = $_GET['pedid']);
	$animal = new Animal( $a );
	//test to find out how many user fields there are..
	$fields = $animal->numoffields();

	$qarray = array('d', 'f', 'm', 'ff', 'mf', 'fm' ,'mm', 'fff', 'ffm', 'fmf', 'fmm', 'mmf', 'mff', 'mfm', 'mmm');

	$querystring = "SELECT ";
	
	foreach ($qarray as $key)
	{
		$querystring .= $key.".id as ".$key."_id, ";
		$querystring .= $key.".naam as ".$key."_naam, ";
		$querystring .= $key.".moeder as ".$key."_moeder, ";
		$querystring .= $key.".vader as ".$key."_vader, ";
		$querystring .= $key.".roft as ".$key."_roft, ";	
		$querystring .= $key.".foto as ".$key."_foto, ";
	}
	
	$querystring .= "mmm.coi as mmm_coi FROM ".$xoopsDB->prefix("stamboom")." d 
	LEFT JOIN ".$xoopsDB->prefix("stamboom")." f ON d.vader = f.id 
	LEFT JOIN ".$xoopsDB->prefix("stamboom")." m ON d.moeder = m.id 
	LEFT JOIN ".$xoopsDB->prefix("stamboom")." ff ON f.vader = ff.id 
	LEFT JOIN ".$xoopsDB->prefix("stamboom")." fff ON ff.vader = fff.id 
	LEFT JOIN ".$xoopsDB->prefix("stamboom")." ffm ON ff.moeder = ffm.id 
	LEFT JOIN ".$xoopsDB->prefix("stamboom")." mf ON m.vader = mf.id 
	LEFT JOIN ".$xoopsDB->prefix("stamboom")." mff ON mf.vader = mff.id 
	LEFT JOIN ".$xoopsDB->prefix("stamboom")." mfm ON mf.moeder = mfm.id 
	LEFT JOIN ".$xoopsDB->prefix("stamboom")." fm ON f.moeder = fm.id 
	LEFT JOIN ".$xoopsDB->prefix("stamboom")." fmf ON fm.vader = fmf.id 
	LEFT JOIN ".$xoopsDB->prefix("stamboom")." fmm ON fm.moeder = fmm.id 
	LEFT JOIN ".$xoopsDB->prefix("stamboom")." mm ON m.moeder = mm.id 
	LEFT JOIN ".$xoopsDB->prefix("stamboom")." mmf ON mm.vader = mmf.id 
	LEFT JOIN ".$xoopsDB->prefix("stamboom")." mmm ON mm.moeder = mmm.id 
	where d.id=$ID";
		
	$result = $xoopsDB->query($querystring);

	while ($row = $xoopsDB->fetchArray($result)) 
	{
		//create array for animal (and all parents)
		foreach ($qarray as $key)
		{		
			$d[$key]['id']			= 	$row[$key.'_id'];
			$d[$key]['name'] 		= 	stripslashes($row[$key.'_naam']);
			$d[$key]['moeder']		=	$row[$key.'_moeder'];
			$d[$key]['vader']		=	$row[$key.'_vader'];
			$d[$key]['roft']		= 	$row[$key.'_roft'];
			$d[$key]['nhsb']		= 	'';
			if (strlen($key) == 3 && $moduleConfig['lastimage'] == 0)
			{
				//do not show image in last row of pedigree	
			}
			else
			{
				//check if image exists
				if ($row[$key.'_foto'] != '')
				{
					$d[$key]['photo']		= 	"images/thumbnails/".$row[$key.'_foto']."_150.jpeg";
				}
			}
			
			$d[$key]['overig']		= 	'';
			// $pedidata to hold viewable data to be shown in pedigree
			$pedidata = "";
			
				if (!$d[$key]['id'] == '')
				{
					//if exists create animal object
					$animal = new Animal( $d[$key]['id'] );
					$fields = $animal->numoffields();
				}
				for ($i = 0; $i < count($fields); $i++)
				{
					$userfield = new Field( $fields[$i], $animal->getconfig() );
					if ($userfield->active() && $userfield->inpedigree())
					{	
							$fieldType = $userfield->getSetting( "FieldType" );
							$fieldobject = new $fieldType( $userfield, $animal );
							$pedidata	.=	$fieldobject->showField()."<br />";
					}
					$d[$key]['hd'] 	= 	$pedidata;	
				}

			
		}		
		
	}
	
	//add data to smarty template
	$xoopsTpl->assign('page_title', stripslashes($row['d_naam']));
	//assign dog
	$xoopsTpl->assign("d", $d);
	//assign config options

	$xoopsTpl->assign("male", "<img src=\"images/male.gif\">");
	$xoopsTpl->assign("female", "<img src=\"images/female.gif\">");		

	//assign extra display options
	$xoopsTpl->assign("unknown", "Unknown");
	$xoopsTpl->assign("SD", _PED_SD);
	$xoopsTpl->assign("PA", _PED_PA);
	$xoopsTpl->assign("GP", _PED_GP);
	$xoopsTpl->assign("GGP", _PED_GGP);
}



?>