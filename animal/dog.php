<?php
// ------------------------------------------------------------------------- 

require_once "../../mainfile.php";
if ( file_exists(XOOPS_ROOT_PATH ."/modules/animal/language/".$xoopsConfig['language']."/templates.php") ) 
    require_once XOOPS_ROOT_PATH ."/modules/animal/language/".$xoopsConfig['language']."/templates.php";
else 
	include_once XOOPS_ROOT_PATH ."/modules/animal/language/english/templates.php";
// Include any common code for this module.
require_once(XOOPS_ROOT_PATH ."/modules/animal/include/functions.php");
require_once(XOOPS_ROOT_PATH ."/modules/animal/include/class_field.php");

// Get all HTTP post or get parameters into global variables that are prefixed with "param_"
import_request_variables("gp", "param_");

$xoopsOption['template_main'] = "pedigree_dog.html";

include XOOPS_ROOT_PATH.'/header.php';

global $xoopsUser, $xoopsTpl, $xoopsDB, $xoopsModuleConfig;

//get module configuration
$module_handler =& xoops_gethandler('module');
$module         =& $module_handler->getByDirname('animal');
$config_handler =& xoops_gethandler('config');
$moduleConfig   =& $config_handler->getConfigsByCat(0, $module->getVar('mid'));

$myts = &MyTextSanitizer::getInstance();


if(isset($_GET['id']))
{
	$id = $_GET['id'];
}
else
{
	echo "No dog has been selected"; die();	
}

if($_GET['delpicture'] == 'true')
{
	$delpicsql = "UPDATE ".$xoopsDB->prefix("stamboom")." SET foto = '' WHERE ID = '".$id."'";
	mysql_query($delpicsql);	
}
//query
$queryString = "SELECT * from ".$xoopsDB->prefix("stamboom")." WHERE ID=".$id;
$result = $xoopsDB->query($queryString);

while ($row = $xoopsDB->fetchArray($result)) 
{
	//name
	$naam = stripslashes($row['NAAM']);
	$xoopsTpl->assign('xoops_pagetitle', $naam." -- detailed information");
	//owner
	if($row['id_eigenaar'] != '0')
	{
		$queryeig = "SELECT ID, lastname, firstname from ".$xoopsDB->prefix("eigenaar")." WHERE ID=".$row['id_eigenaar'];
		$reseig = $xoopsDB->query($queryeig);
		while ($roweig = $xoopsDB->fetchArray($reseig))
		{
			$eig = "<a href=\"owner.php?ownid=".$roweig['ID']."\">".$roweig['firstname']." ".$roweig['lastname']."</a>";
		}
	}
	else
	{
		$eig = "<a href=\"update.php?id=".$row['ID']."&fld=ow\">"._PED_UNKNOWN."</a>";
	}
	//breeder
	if($row['id_fokker'] != '0')
	{
		$queryfok = "SELECT ID, lastname, firstname from ".$xoopsDB->prefix("eigenaar")." WHERE ID=".$row['id_fokker'];
		$resfok = $xoopsDB->query($queryfok);
		while ($rowfok = $xoopsDB->fetchArray($resfok))
		{
			$fok = "<a href=\"owner.php?ownid=".$rowfok['ID']."\">".$rowfok['firstname']." ".$rowfok['lastname']."</a>";
		}
	}
	else
	{
		$fok = "<a href=\"update.php?id=".$row['ID']."&fld=br\">"._PED_UNKNOWN."</a>";	
	}
	//gender
	if ($row['roft'] == 0) { $gender = "<img src=\"images/male.gif\"> ".strtr(_PED_FLD_MALE, array( '[male]' => $moduleConfig['male'] )); }
	else { $gender = "<img src=\"images/female.gif\"> ".strtr(_PED_FLD_FEMA, array( '[female]' => $moduleConfig['female'] )); }
	//Sire
	if ($row['vader'] != 0)
	{
		$querysire = "SELECT NAAM from ".$xoopsDB->prefix("stamboom")." WHERE ID=".$row['vader'];
		$ressire = $xoopsDB->query($querysire);
		while ($rowsire = $xoopsDB->fetchArray($ressire))
		{
			$sire = "<img src=\"images/male.gif\"><a href=\"dog.php?id=".$row['vader']."\">".stripslashes($rowsire['NAAM'])."</a>";
		}
	}
	else
	{
		$sire = "<img src=\"images/male.gif\"><a href=\"seldog.php?curval=".$row['ID']."&gend=0&letter=a\">"._PED_UNKNOWN."</a>";	
	}
	//Dam
	if ($row['moeder'] != 0)
	{
		$querydam = "SELECT NAAM from ".$xoopsDB->prefix("stamboom")." WHERE ID=".$row['moeder'];
		$resdam = $xoopsDB->query($querydam);
		while ($rowdam = $xoopsDB->fetchArray($resdam))
		{
			$dam = "<img src=\"images/female.gif\"><a href=\"dog.php?id=".$row['moeder']."\">".stripslashes($rowdam['NAAM'])."</a>";
		}
	}
	else
	{
		$dam = "<img src=\"images/female.gif\"><a href=\"seldog.php?curval=".$row['ID']."&gend=1&letter=a\">"._PED_UNKNOWN."</a>";	
	}
	//picture
	if ($row['foto'] != "")
	{
		$picture = "<img src=images/thumbnails/".$row['foto']."_400.jpeg>";	
	}
	else
	{
		$picture = "<a href=\"update.php?id=".$row['ID']."&fld=pc\">"._PED_UNKNOWN."</a>";	
	}
	//inbred precentage
	if ($row['coi'] == '')
	{
		if ($row['vader'] != 0 && $row['moeder'] != 0)
		{
			$inbred = "<a href=\"coi.php?s=".$row['vader']."&d=".$row['moeder']."&dogid=".$row['ID']."&detail=1\">".strtr(_PED_COI_WAIT, array( '[animalType]' => $moduleConfig['animalType'] ))."</a>";
		}
		else
		{
			$inbred = _PED_COI_MORE;
		}
	}
	else
	{
		$inbred = "<a href=\"coi.php?s=".$row['vader']."&d=".$row['moeder']."&dogid=".$row['ID']."&detail=1\" title=\"".strtr(_PED_COI_WAIT, array( '[animalType]' => $moduleConfig['animalType'] ))."\">".$row['coi']." %</a>";
	}
	//brothers and sisters
	$bas = bas($id, $row['vader'], $row['moeder']);
	//pups
	if ($moduleConfig['pups'] == '1')
	{
		$pups = pups($id,$row['roft']);
	}
	//check for edit rights
	$access = 0;
	$xoopsModule =& XoopsModule::getByDirname("animal");
	if (!empty($xoopsUser))
	{
		if ($xoopsUser->isAdmin($xoopsModule->mid()))
		{ 
			$access = 1; 
		}
		if ($row['user'] == $xoopsUser->getVar("uid"))
		{
			$access = 1;	
		}
	}

	//name
	$items[] = array ('header' 	=> _PED_FLD_NAME, 
						'data' 	=> "<a href=\"pedigree.php?pedid=".$row['ID']."\">".$naam."</a> (click to view pedigree)",
						'edit'	=>	"<a href=\"update.php?id=".$row['ID']."&fld=nm\">Edit</a>" );
	if ($moduleConfig['ownerbreeder'] == '1')
	{
		//owner
		$items[] = array ('header' 	=> _PED_FLD_OWNE, 
							'data' 	=> $eig,
							'edit'	=>	"<a href=\"update.php?id=".$row['ID']."&fld=ow\">Edit</a>" );
		//breeder
		$items[] = array ('header' 	=> _PED_FLD_BREE, 
							'data' 	=> $fok,
							'edit'	=>	"<a href=\"update.php?id=".$row['ID']."&fld=br\">Edit</a>" );
	}
	//gender
	$items[] = array ('header' 	=> _PED_FLD_GEND, 
						'data' 	=> $gender,
						'edit'	=>	"<a href=\"update.php?id=".$row['ID']."&fld=sx\">Edit</a>" );
	//sire
	$items[] = array ('header' 	=> strtr(_PED_FLD_FATH, array( '[father]' => $moduleConfig['father'] )), 
						'data' 	=> $sire, 
						'edit'	=>	"<a href=\"seldog.php?curval=".$row['ID']."&gend=0&letter=a\">Edit</a>" );
	//dam
	$items[] = array ('header' 	=> strtr(_PED_FLD_MOTH, array( '[mother]' => $moduleConfig['mother'] )), 
						'data' 	=> $dam,
						'edit'	=>	"<a href=\"seldog.php?curval=".$row['ID']."&gend=1&letter=a\">Edit</a>" );
	//picture
	$items[] = array ('header' 	=> _PED_FLD_PICT, 
						'data' 	=> $picture,
						'edit'	=>	"<a href=\"update.php?id=".$row['ID']."&fld=pc\">Edit</a><br /><a href=\"dog.php?id=".$row['ID']."&delpicture=true\">Delete</a>" );

	//userdefined fields

		$a = (!isset($_GET['id']) ? $a = 1 : $a = $_GET['id']);
		$animal = new Animal( $a );
	
	//test to find out how many user fields there are..
	$fields = $animal->numoffields();
	//create userfields and populate them
	for ($i = 0; $i < count($fields); $i++)
	{
		$userfield = new Field( $fields[$i], $animal->getconfig() );
		if ($userfield->active())
		{	
			$fieldType = $userfield->getSetting( "FieldType" );
			$fieldobject = new $fieldType( $userfield, $animal );	
			if($userfield->isLocked())
			{
				$items[] = array ('header' 	=> $userfield->getSetting( "FieldName" ), 
						'data' 	=> $fieldobject->showValue(), 
						'edit'	=> "" );
			}
			else
			{
				$items[] = array ('header' 	=> $userfield->getSetting( "FieldName" ), 
						'data' 	=> $fieldobject->showValue(), 
						'edit'	=> "<a href=\"update.php?id=".$row['ID']."&fld=".$fields[$i]."\">Edit</a>" );
			}
		}
		unset($fieldobject);
		unset($userfield);
	}
	
	if ($moduleConfig['proversion'] == '1')
	{
	//inbred percentage
		$items[] = array ('header' 	=> _PED_FLD_INBR, 
							'data' 	=> $inbred, 
							'edit'	=> "" );
	}
	if ($moduleConfig['pups'] == '1')
	{
	//pups
		if ($nummatch == "0")
		{
			$pups = "";
		}
		else
		{
			$pups = "pups";
		}
		$items[] = array ('header' 	=> $moduleConfig['children'], 
							'data' 	=> $pups, 
							'edit' 	=> "" );
	}
	if ($moduleConfig['brothers'] == '1')
	{
		//bas (brothers and sisters)
		if ($nummatch1 == "0")
		{
			$bas = "";
		}
		else
		{
			$bas = "bas";
		}
		$items[] = array ('header'  => _PED_FLD_BAS,
							'data'  => $bas,
							'edit'  => "");
	}
	//database user
	if ($moduleConfig['proversion'] == '1')
	{
		$items[] = array ('header' 	=> _PED_FLD_DBUS, 
							'data' 	=> xoops_getLinkedUnameFromId($row['user']), 
							'edit'	=> "" );
	}
	//inbred pedigree
	if ($moduleConfig['proversion'] == '1')
	{
		$items[] = array ('header' 	=> "Inbred Pedigree", 
							'data' 	=> "<a href=\"mpedigree.php?pedid=".$row['ID']."\">Inbreeding pedigree</a>", 
							'edit'	=> "" );
	}
	$id = $row['ID'];

}

//add data to smarty template
//assign dog
//pups
$xoopsTpl->assign("dogs", $dogs);
$xoopsTpl->assign("columns", $columns);
$xoopsTpl->assign("numofcolumns", $numofcolumns);
$xoopsTpl->assign("nummatch", $nummatch." Animals found.");

//bas
$xoopsTpl->assign("dogs1", $dogs1);
$xoopsTpl->assign("columns1", $columns1);
$xoopsTpl->assign("numofcolumns1", $numofcolumns1);
$xoopsTpl->assign("nummatch1", $nummatch1." Animals found.");

//both pups and bas
$xoopsTpl->assign("width", 100/$numofcolumns);
$xoopsTpl->assign("tsarray", sorttable($numofcolumns));

$xoopsTpl->assign("access", $access);
$xoopsTpl->assign("items", $items);
$xoopsTpl->assign("name", $naam);
$xoopsTpl->assign("id", $id);
$xoopsTpl->assign("sdvins", _PED_SDVINS);
$xoopsTpl->assign("vpo", _PED_VPO);
$xoopsTpl->assign("vpo2", _PED_VPO2);
$xoopsTpl->assign("sii", _PED_SII);
$xoopsTpl->assign("sip", _PED_SIP);
$xoopsTpl->assign("id", $id);
$xoopsTpl->assign("delete", _PED_BTN_DELE);

//comments and footer
include XOOPS_ROOT_PATH.'/include/comment_view.php';
include XOOPS_ROOT_PATH."/footer.php";

?>