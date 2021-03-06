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

$xoopsOption['template_main'] = "pedigree_addlitter.html";
include XOOPS_ROOT_PATH.'/header.php';
$xoopsTpl->assign('page_title', "Pedigree database - add a litter");

//check for access
$xoopsModule =& XoopsModule::getByDirname("animal");	
if (empty($xoopsUser)) 
{
	redirect_header("index.php", 3, _NOPERM."<br />"._PED_REGIST);
	exit();
}

//get module configuration
$module_handler =& xoops_gethandler('module');
$module         =& $module_handler->getByDirname('animal');
$config_handler =& xoops_gethandler('config');
$moduleConfig   =& $config_handler->getConfigsByCat(0, $module->getVar('mid'));

if (!isset($_GET['f'])) { addlitter(); }
else
{
	$f = $_GET['f'];
	if ($f == "sire") { sire(); }
	if ($f == "dam") { dam(); }
	if ($f == "check") { check(); }
}

function addlitter()
{
	global $xoopsTpl, $xoopsUser, $xoopsDB, $xoopsOption;
	
	//get module configuration
	$module_handler =& xoops_gethandler('module');
	$module         =& $module_handler->getByDirname('animal');
	$config_handler =& xoops_gethandler('config');
	$moduleConfig   =& $config_handler->getConfigsByCat(0, $module->getVar('mid'));
	
	//create xoopsform
	include XOOPS_ROOT_PATH."/class/xoopsformloader.php";
    $searchform = new XoopsThemeForm(strtr(_PED_ADD_LITTER, array( '[litter]' => $moduleConfig['litter'] )), "searchform", "add_litter.php?f=sire", "post");
    $searchform->addElement(new XoopsFormHiddenToken($name = 'XOOPS_TOKEN_REQUEST', $timeout = 360));
	//create random value
	$random = (rand()%10000);
	$searchform->addElement(new XoopsFormHidden('random', $random));
	//find userid
	$searchform->addElement(new XoopsFormHidden('userid', $xoopsUser->getVar("uid")));
	//create animal object
	$animal = new Animal( );
	//test to find out how many user fields there are...
	$fields = $animal->numoffields();
		
	//create form contents
	for ($count =1; $count<11; $count++)
	{
		//name
		$searchform->addElement(new XoopsFormLabel($count.".", strtr(_PED_KITT_NAME .$count.".", array( '[animalType]' => $moduleConfig['animalType'] ))));
		$textbox[$count] = new XoopsFormText("<b>"._PED_FLD_NAME."</b>", 'name'.$count, $size=50, $maxsize=50, '');
		$searchform->addElement($textbox[$count]);
		//gender
		$gender_radio[$count] = new XoopsFormRadio( "<b>"._PED_FLD_GEND."</b>", 'roft'.$count, $value = '0' );
		$gender_radio[$count] -> addOptionArray( array( '0'=>strtr(_PED_FLD_MALE, array( '[male]' => $moduleConfig['male'] )), '1'=>strtr(_PED_FLD_FEMA, array( '[female]' => $moduleConfig['female'] ))));
		$searchform->addElement( $gender_radio[$count] );
		//add userfields
		for ($i = 0; $i < count($fields); $i++)
		{
			$userfield = new Field( $fields[$i], $animal->getconfig() );
			$fieldType = $userfield->getSetting( "FieldType" );
			$fieldobject = new $fieldType( $userfield, $animal );
			if ($userfield->active() && $userfield->getSetting( "Litter" ) == "1" && !$userfield->isLocked())
			{
				$newentry[$count][$i] = $fieldobject->newField($count);
				$searchform->addElement( $newentry[$count][$i] );
			}
		}
		//add empty place holder as divider
		$searchform->addElement(new XoopsFormLabel("&nbsp;", ""));
	}
	
	$searchform->addElement(new XoopsFormLabel(_PED_ADD_DATA, _PED_DATA_INFO.$moduleConfig['litter'].".</h2>"));
	//add userfields that are not shown in the litter
	for ($i = 0; $i < count($fields); $i++)
	{
		$userfield = new Field( $fields[$i], $animal->getconfig() );
		$fieldType = $userfield->getSetting( "FieldType" );
		$fieldobject = new $fieldType( $userfield, $animal );
		if ($userfield->active() && $userfield->generallitter() && !$userfield->isLocked())
		{
			//add the "-" character to the beginning of the fieldname !!!
			$newentry[$i] = $fieldobject->newField("-");
			$searchform->addElement( $newentry[$i] );
		}	
	}
	//add the breeder to the list for the entire litter
	//no need to add the owner here because they will be different for each animal in the litter.
	if ($moduleConfig['ownerbreeder'] == '1')
	{
		//breeder
		$breeder = new XoopsFormSelect(_PED_FLD_BREE, 'id_fokker', $value='', $size=1, $multiple=false);
		$queryfok = "SELECT ID, firstname, lastname from ".$xoopsDB->prefix("eigenaar")." order by `lastname`";
		$resfok = $xoopsDB->query($queryfok);
		$breeder -> addOption( 0,	$name='Unknown', $disabled=false );
		while ($rowfok = $xoopsDB->fetchArray($resfok))
		{
			$breeder -> addOption( $rowfok['ID'],	$name=$rowfok['lastname'].", ".$rowfok['firstname'], $disabled=false );
		}		
		$searchform->addElement( $breeder );
	}
	
	//submit button
	$searchform->addElement(new XoopsFormButton('', 'submit', strtr(_PED_ADD_SIRE, array( '[father]' => $moduleConfig['father'] )), 'submit'));
	//send to template
	$searchform->assign($xoopsTpl);

}

function sire()
{
	global $xoopsTpl, $xoopsUser, $xoopsDB;
	//debug option !
	//print_r($_POST); die();
	//get module configuration
	$module_handler =& xoops_gethandler('module');
	$module         =& $module_handler->getByDirname('animal');
	$config_handler =& xoops_gethandler('config');
	$moduleConfig   =& $config_handler->getConfigsByCat(0, $module->getVar('mid'));

	//check for access
	$xoopsModule =& XoopsModule::getByDirname("animal");	
	if (empty($xoopsUser)) 
	{
		redirect_header("javascript:history.go(-1)", 3, _NOPERM."<br />"._PED_REGIST);
		exit();
	}
	$userid = $_POST['userid'];
	if (empty($random)) { $random=$_POST['random']; }
	if (isset($_GET['random'])) { $random = $_GET['random']; }
	if (empty($st)) { $st=0; }
	if (isset($_GET['st'])) { $st=$_GET['st']; }
	$userfields = "";
	for ($count =1; $count<11; $count++)
	{
		$namelitter = "name".$count;
		$roftlitter = "roft".$count;
		//check for an empty name
		if ($_POST[$namelitter] !== "")
		{
			$name .= ":".$_POST[$namelitter];
			$roft .= ":".$_POST[$roftlitter];
		}
		else
		{
			if ($count == "1")
			{	redirect_header("add_litter.php", 3, _PED_ADD_NAMEPLZ);		}
		}
	}
	if (isset($_POST['id_fokker']))
	{
		$id_fokker = $_POST['id_fokker'];
	}
	else
	{
		$id_fokker = "0";
	}
		
	//make the redirect
	if (!isset($_GET['r']))
	{	
		//create animal object
		$animal = new Animal( );
		//test to find out how many user fields there are..
		$fields = $animal->numoffields();
		sort($fields);
		$usersql = "";
		for ($i = 0; $i < count($fields); $i++)
		{
			$userfield = new Field( $fields[$i], $animal->getconfig() );
			$fieldType = $userfield->getSetting( "FieldType" );
			$fieldobject = new $fieldType( $userfield, $animal );
			$defvalue = $fieldobject->defaultvalue;
			//emtpy string to house the different values for this userfield
			$withinfield = "";
			for ($count =1; $count<11; $count++)
			{
				if ($_POST['name'.$count] !== "")
				{
					if(isset($_POST[$count.'user'.$fields[$i]]))
					{
						//debug option
						//echo $count.'user'.$fields[$i]."=".$_POST[$count.'user'.$fields[$i]]."<br />";
						$withinfield .=":".$_POST[$count.'user'.$fields[$i]];
					}
					else
					{
						if ($userfield->active() && $userfield->generallitter() && !$userfield->isLocked())
						{
							//use $_POST value if this is a general litter field
							$withinfield .= ":".$_POST['-user'.$fields[$i]];
						}
						else
						{
							//create $withinfield for fields not added to the litter
							$withinfield .=":".$defvalue;
						}
					}
				}
			}
			//debug option
			//echo "user".$fields[$i]." - ".$withinfield."<br />";
			$user{$fields[$i]} = $withinfield;
		}
		//insert into stamboom_temp
		$query = "INSERT INTO ".$xoopsDB->prefix("stamboom_temp")." VALUES ('".$random."','".unhtmlentities($name)."','0','".$id_fokker."','".$userid."','".$roft."','','','', ''";
		for ($i = 0; $i < count($fields); $i++)
		{
			$userfield = new Field( $fields[$i], $animal->getconfig() );
			$fieldType = $userfield->getSetting( "FieldType" );
			$fieldobject = new $fieldType( $userfield, $animal );
			//do we only need to create a query for active fields ?
			$query .= ",'".$user{$fields[$i]}."'";	
		}
		$query .= ")";
		//debug options
		//echo $query."<br />"; die();
		$xoopsDB->query($query);
		redirect_header("add_litter.php?f=sire&random=".$random."&st=".$st."&r=1&l=a", 1, strtr(_PED_ADD_SIREPLZ, array( '[father]' => $moduleConfig['father'] )));
	}
	//find letter on which to start else set to 'a'
	if (isset($_GET['l'])) { $l=$_GET['l']; }
	else { $l="a"; }
	//assign 'sire' to the template	
	$xoopsTpl->assign("sire", "1");
	//create list of males dog to select from
	$perp = $moduleConfig['perpage'];
	//count total number of dogs
	$numdog = "SELECT ID from ".$xoopsDB->prefix("stamboom")." WHERE roft='0' and NAAM LIKE '".$l."%'";
	$numres = $xoopsDB->query($numdog);
	//total number of dogs the query will find
	$numresults = $xoopsDB -> getRowsNum( $numres );
	//total number of pages
	$numpages = (floor($numresults/$perp))+1;
	if (($numpages * $perp) == ($numresults + $perp))
		{ $numpages = $numpages - 1; }
	//find current page
	$cpage = (floor($st/$perp))+1;
	//create alphabet
	$pages = "";
	for($i=65; $i<=90; $i++) 
	{
		if ($l == chr($i))
		{
			$pages .= "<b><a href=\"add_litter.php?f=sire&r=1&r=1&random=".$random."&l=".chr($i)."\">".chr($i)."</a></b>&nbsp;";
		}
		else
		{
			$pages .= "<a href=\"add_litter.php?f=sire&r=1&r=1&random=".$random."&l=".chr($i)."\">".chr($i)."</a>&nbsp;";
		}	
	}
	$pages .= "-&nbsp;";
	$pages .= "<a href=\"add_litter.php?f=sire&r=1&random=".$random."&l=�\">�</a>&nbsp;";
	$pages .= "<a href=\"add_litter.php?f=sire&r=1&random=".$random."&l=�\">�</a>&nbsp;";
	//create linebreak
	$pages .= "<br />";
	//create previous button
	if ($numpages > 1)
	{
		if ($cpage > 1)	
		{
			$pages .= "<a href=\"add_litter.php?f=sire&r=1&l=".$l."&random=".$random."&st=".($st-$perp)."\">"._PED_PREVIOUS."</a>&nbsp;&nbsp";
		}
	}
	//create numbers
	for ($x=1; $x<($numpages+1); $x++)
	{	
		//create line break after 20 number
		if (($x % 20) == 0)
		{ $pages .= "<br />"; }
		if ($x != $cpage)
		{ $pages .= "<a href=\"add_litter.php?f=sire&r=1&l=".$l."&random=".$random."&st=".($perp*($x-1))."\">".$x."</a>&nbsp;&nbsp;"; }
		else
		{ $pages .= $x."&nbsp;&nbsp";  }
	}
	//create next button
	if ($numpages > 1)
	{
		if ($cpage < ($numpages))	
		{
			$pages .= "<a href=\"add_litter.php?f=sire&r=1&l=".$l."&random=".$random."&st=".($st+$perp)."\">"._PED_NEXT."</a>&nbsp;&nbsp";
		}
	}
	//query
	$queryString = "SELECT * from ".$xoopsDB->prefix("stamboom")." WHERE roft = '0' and NAAM LIKE '".$l."%' ORDER BY NAAM LIMIT ".$st.", ".$perp;
	$result = $xoopsDB->query($queryString);
	
	$animal = new Animal( );
	//test to find out how many user fields there are...
	$fields = $animal->numoffields();
	$numofcolumns = 1;
	$columns[] = array ('columnname' => "Name");
	for ($i = 0; $i < count($fields); $i++)
	{
		$userfield = new Field( $fields[$i], $animal->getconfig() );
		$fieldType = $userfield->getSetting( "FieldType" );
		$fieldobject = new $fieldType( $userfield, $animal );
		//create empty string
		$lookupvalues = "";
		if ($userfield->active() && $userfield->inlist())
		{
			if ($userfield->haslookup())
			{
				$lookupvalues = $userfield->lookup($fields[$i]);
				//debug information
				//print_r($lookupvalues);	
			}
			$columns[] = array ('columnname' => $fieldobject->fieldname, 'columnnumber' => $userfield->getID(), 'lookupval' => $lookupvalues);
			$numofcolumns++;
			unset($lookupvalues);
		}
	}
	
	for ($i = 1; $i < ($numofcolumns); $i++)
	{
		$empty[] = array ('value' => "");		
	}
	$dogs [] = array ('id' => "0", 'name' => "", 'gender' => "", 'link' => "<a href=\"add_litter.php?f=dam&random=".$random."&selsire=0\">".strtr(_PED_ADD_SIREUNKNOWN, array( '[father]' => $moduleConfig['father'] ))."</a>", 'colour' => "", 'number' => "", 'usercolumns' => $empty);
	
	while ($row = $xoopsDB->fetchArray($result)) 
	{
		//create picture information
		if ($row['foto'] != '')
		{ $camera = " <img src=\"images/camera.png\">"; }
		else { $camera = ""; }
		$name = stripslashes($row['NAAM']).$camera;
		//empty array
		unset($columnvalue);
		//fill array
		for ($i = 1; $i < ($numofcolumns); $i++)
		{
			$x = $columns[$i]['columnnumber'];
			if (is_array($columns[$i]['lookupval']))
			{
				foreach ($columns[$i]['lookupval'] as $key => $keyvalue)
				{
					if($key == $row['user'.$x])
					{
						$value = $keyvalue['value'];
					}	
				}	
				//debug information
				///echo $columns[$i]['columnname']."is an array !";
			}
			//format value - cant use object because of query count
			elseif (substr($row['user'.$x], 0, 7) == 'http://')
			{
				$value = "<a href=\"".$row['user'.$x]."\">".$row['user'.$x]."</a>";	
			}
			else { $value = $row['user'.$x]; }		
			$columnvalue[] = array ('value' => $value);	
		}
		$dogs[] = array ('id' => $row['ID'], 'name' => $name, 'gender' => '<img src="images/male.gif">', 'link' => "<a href=\"add_litter.php?f=dam&random=".$random."&selsire=".$row['ID']."\">".$name."</a>",'colour' => "", 'number' => "", 'usercolumns' => $columnvalue);		
	}

	//add data to smarty template
	//assign dog
	$xoopsTpl->assign("dogs", $dogs);
	$xoopsTpl->assign("columns", $columns);
	$xoopsTpl->assign("numofcolumns", $numofcolumns);
	$xoopsTpl->assign("tsarray", sorttable($numofcolumns));
	$xoopsTpl->assign("nummatch", strtr(_PED_ADD_SELSIRE, array( '[father]' => $moduleConfig['father'] )));
	$xoopsTpl->assign("pages", $pages);
}

function dam()
{
	global $xoopsTpl, $xoopsUser, $xoopsDB;
	
	//get module configuration
	$module_handler =& xoops_gethandler('module');
	$module         =& $module_handler->getByDirname('animal');
	$config_handler =& xoops_gethandler('config');
	$moduleConfig   =& $config_handler->getConfigsByCat(0, $module->getVar('mid'));
	
	if (empty($random)) { $random=$_POST['random']; }
	if (isset($_GET['random'])) { $random = $_GET['random']; }
	if (empty($st)) { $st=0; }
	if (isset($_GET['st'])) { $st=$_GET['st']; }
	//make the redirect
	if (!isset($_GET['r']))
	{	
		//insert into stamboom_temp
		$query = "UPDATE ".$xoopsDB->prefix("stamboom_temp")." SET vader =".$_GET['selsire']." WHERE ID=".$random;
		$xoopsDB->queryf($query);
		redirect_header("add_litter.php?f=dam&random=".$random."&st=".$st."&r=1", 1, strtr(_PED_ADD_SIREOK, array( '[mother]' => $moduleConfig['mother'] )));
	}
	//find letter on which to start else set to 'a'
	if (isset($_GET['l'])) { $l=$_GET['l']; }
	else { $l="a"; }
	//assign sire to the template
	$xoopsTpl->assign("sire", "1");
	//create list of males dog to select from
	$perp = $moduleConfig['perpage'];
	//count total number of dogs
	$numdog = "SELECT ID from ".$xoopsDB->prefix("stamboom")." WHERE roft='1' and NAAM LIKE '".$l."%'";
	$numres = $xoopsDB->query($numdog);
	//total number of dogs the query will find
	$numresults = $xoopsDB -> getRowsNum( $numres );
	//total number of pages
	$numpages = (floor($numresults/$perp))+1;
	if (($numpages * $perp) == ($numresults + $perp))
		{	$numpages = $numpages - 1; }
	//find current page
	$cpage = (floor($st/$perp))+1;
	//create alphabet
	$pages ="";
	for($i=65; $i<=90; $i++) 
	{
		if ($l == chr($i))
		{
			$pages .= "<b><a href=\"add_litter.php?f=dam&r=1&random=".$random."&l=".chr($i)."\">".chr($i)."</a></b>&nbsp;";
		}
		else
		{
			$pages .= "<a href=\"add_litter.php?f=dam&r=1&random=".$random."&l=".chr($i)."\">".chr($i)."</a>&nbsp;";
		}	
	}
	$pages .= "-&nbsp;";
	$pages .= "<a href=\"add_litter.php?f=dam&r=1&random=".$random."&l=�\">�</a>&nbsp;";
	$pages .= "<a href=\"add_litter.php?f=dam&r=1&random=".$random."&l=�\">�</a>&nbsp;";
	//create linebreak
	$pages .= "<br />";
	//create previous button
	if ($numpages > 1)
	{
		if ($cpage > 1)	
		{
			$pages .= "<a href=\"add_litter.php?f=dam&r=1&l=".$l."&random=".$random."&st=".($st-$perp)."\">"._PED_PREVIOUS."</a>&nbsp;&nbsp";
		}
	}
	//create numbers
	for ($x=1; $x<($numpages+1); $x++)
	{	
		//create line break after 20 number
		if (($x % 20) == 0)
		{ $pages .= "<br />"; }
		if ($x != $cpage)
		{ $pages .= "<a href=\"add_litter.php?f=dam&r=1&l=".$l."&random=".$random."&st=".($perp*($x-1))."\">".$x."</a>&nbsp;&nbsp;"; }
		else
		{ $pages .= $x."&nbsp;&nbsp";  }
	}
	//create next button
	if ($numpages > 1)
	{
		if ($cpage < ($numpages))	
		{
			$pages .= "<a href=\"add_litter.php?f=dam&r=1&l=".$l."&random=".$random."&st=".($st+$perp)."\">"._PED_NEXT."</a>&nbsp;&nbsp";
		}
	}
	//query
	$queryString = "SELECT * from ".$xoopsDB->prefix("stamboom")." WHERE roft = '1' and NAAM LIKE '".$l."%' ORDER BY NAAM LIMIT ".$st.", ".$perp;
	$result = $xoopsDB->query($queryString);
	
	$animal = new Animal( );
	//test to find out how many user fields there are...
	$fields = $animal->numoffields();
	$numofcolumns = 1;
	$columns[] = array ('columnname' => "Name");
	for ($i = 0; $i < count($fields); $i++)
	{
		$userfield = new Field( $fields[$i], $animal->getconfig() );
		$fieldType = $userfield->getSetting( "FieldType" );
		$fieldobject = new $fieldType( $userfield, $animal );
		//create empty string
		$lookupvalues = "";
		if ($userfield->active() && $userfield->inlist())
		{
			if ($userfield->haslookup())
			{
				$lookupvalues = $userfield->lookup($fields[$i]);
				//debug information
				//print_r($lookupvalues);	
			}
			$columns[] = array ('columnname' => $fieldobject->fieldname, 'columnnumber' => $userfield->getID(), 'lookupval' => $lookupvalues);
			$numofcolumns++;
			unset($lookupvalues);
		}
	}
	
	for ($i = 1; $i < ($numofcolumns); $i++)
	{
		$empty[] = array ('value' => "");		
	}
	$dogs [] = array ('id' => "0", 'name' => "", 'gender' => "", 'link' => "<a href=\"add_litter.php?f=check&random=".$random."&seldam=0\">".strtr(_PED_ADD_DAMUNKNOWN, array( '[mother]' => $moduleConfig['mother'] ))."</a>", 'colour' => "", 'number' => "", 'usercolumns' => $empty);
	
	while ($row = $xoopsDB->fetchArray($result)) 
	{
		//create picture information
		if ($row['foto'] != '')
		{ $camera = " <img src=\"images/camera.png\">"; }
		else { $camera = ""; }
		$name = stripslashes($row['NAAM']).$camera;
		//empty array
		unset($columnvalue);
		//fill array
		for ($i = 1; $i < ($numofcolumns); $i++)
		{
			$x = $columns[$i]['columnnumber'];
			if (is_array($columns[$i]['lookupval']))
			{
				foreach ($columns[$i]['lookupval'] as $key => $keyvalue)
				{
					if($key == $row['user'.$x])
					{
						$value = $keyvalue['value'];
					}	
				}	
				//debug information
				///echo $columns[$i]['columnname']."is an array !";
			}
			//format value - cant use object because of query count
			elseif (substr($row['user'.$x], 0, 7) == 'http://')
			{
				$value = "<a href=\"".$row['user'.$x]."\">".$row['user'.$x]."</a>";	
			}
			else { $value = $row['user'.$x]; }		
			$columnvalue[] = array ('value' => $value);	
		}
		$dogs[] = array ('id' => $row['ID'], 'name' => $name, 'gender' => '<img src="images/female.gif">', 'link' => "<a href=\"add_litter.php?f=check&random=".$random."&seldam=".$row['ID']."\">".$name."</a>",'colour' => "", 'number' => "", 'usercolumns' => $columnvalue);		
	}
	
	//add data to smarty template
	//assign dog
	$xoopsTpl->assign("dogs", $dogs);
	$xoopsTpl->assign("columns", $columns);
	$xoopsTpl->assign("numofcolumns", $numofcolumns);
	$xoopsTpl->assign("tsarray", sorttable($numofcolumns));
	$xoopsTpl->assign("nummatch", strtr(_PED_ADD_SELDAM, array( '[mother]' => $moduleConfig['mother'] )));
	$xoopsTpl->assign("pages", $pages);	
}

function check()
{
	global $xoopsTpl, $xoopsUser, $xoopsDB;
	
	//get module configuration
	$module_handler =& xoops_gethandler('module');
	$module         =& $module_handler->getByDirname('animal');
	$config_handler =& xoops_gethandler('config');
	$moduleConfig   =& $config_handler->getConfigsByCat(0, $module->getVar('mid'));

	if (empty($random)) { $random=$_POST['random']; }
	if (isset($_GET['random'])) { $random = $_GET['random']; }	
	//query
	$queryString = "SELECT * from ".$xoopsDB->prefix("stamboom_temp")." WHERE ID = ".$random;
	$result = $xoopsDB->query($queryString);
	while ($row = $xoopsDB->fetchArray($result)) 
	{
		//pull data apart.
		if ($row['NAAM'] !== "")
		{ 
			$genders = explode(":", $row['roft']);
			$names = explode(":", $row['NAAM']);
			for ($c = 1; $c<count($names); $c++)
			{
				$query = "INSERT INTO ".$xoopsDB->prefix("stamboom")." VALUES ('','".addslashes($names[$c])."','0','".$row['id_fokker']."','".$row['user']."','".$genders[$c]."','".$_GET['seldam']."','".$row['vader']."','',''";
				//create animal object
				$animal = new Animal( );
				//test to find out how many user fields there are..
				$fields = $animal->numoffields();
				sort($fields);
				$usersql = "";
				for ($i = 0; $i < count($fields); $i++)
				{
					$userfields{$fields[$i]} = explode(":", $row['user'.$fields[$i]]);
					$query .= ",'".$userfields{$fields[$i]}[$c]."'";
				}
				//insert into stamboom
				$query .= ");";
				$xoopsDB->queryf($query);
			}
			
		}
		$sqlquery = "DELETE from ".$xoopsDB->prefix("stamboom_temp")." where ID='".$random."'";
	}
	redirect_header("latest.php",1,strtr(_PED_ADD_LIT_OK, array( '[animalTypes]' => $moduleConfig['animalTypes'] )));	
}

//footer
include XOOPS_ROOT_PATH."/footer.php";

?>