<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2003 Kasper Skaarhoj (kasper@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is 
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
* 
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/** 
 * templavoila module cm1
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  115: class tx_templavoila_cm1 extends t3lib_SCbase 
 *  159:     function menuConfig()    
 *  179:     function main()	
 *  205:     function printContent()	
 *
 *              SECTION: MODULE mode
 *  234:     function main_mode()	
 *  307:     function renderFile()	
 *  530:     function renderDSO()	
 *  636:     function renderTO()	
 *  772:     function renderTO_editProcessing($dataStruct,$row,$theFile)	
 *
 *              SECTION: Mapper functions
 *  893:     function renderTemplateMapper($displayFile,$path,$dataStruct=array(),$currentMappingInfo=array())	
 * 1057:     function drawDataStructureMap($dataStruct,$mappingMode=0,$currentMappingInfo=array(),$pathLevels=array(),$optDat=array(),$contentSplittedByMapping=array(),$level=0,$tRows=array(),$formPrefix='',$path='',$mapOK=1)	
 * 1266:     function drawDataStructureMap_editItem($formPrefix,$key,$value,$level)	
 *
 *              SECTION: Helper-functions for File-based DS/TO creation
 * 1386:     function substEtypeWithRealStuff(&$elArray,$v_sub=array())	
 * 1614:     function substEtypeWithRealStuff_contentInfo($content)	
 *
 *              SECTION: Various helper functions
 * 1660:     function getDataStructFromDSO($datString,$file='')	
 * 1676:     function linkForDisplayOfPath($title,$path)	
 * 1696:     function linkThisScript($array)	
 * 1718:     function makeIframeForVisual($file,$path,$limitTags,$showOnly,$preview=0)	
 * 1734:     function explodeMappingToTagsStr($mappingToTags,$unsetAll=0)	
 * 1752:     function unsetArrayPath(&$dataStruct,$ref)	
 * 1769:     function cleanUpMappingInfoAccordingToDS(&$currentMappingInfo,$dataStruct)	
 *
 *              SECTION: DISPLAY mode
 * 1801:     function main_display()	
 * 1846:     function displayFileContentWithMarkup($content,$path,$relPathFix,$limitTags)	
 * 1880:     function displayFileContentWithPreview($content,$relPathFix)	
 * 1916:     function displayFrameError($error)	
 *
 * TOTAL FUNCTIONS: 24
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);	
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:templavoila/cm1/locallang.php');
require_once (PATH_t3lib.'class.t3lib_scbase.php');


require_once(t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_htmlmarkup.php'); 	
require_once(PATH_t3lib.'class.t3lib_tcemain.php');	




/*************************************
 *
 * Short glossary;
 *
 * DS - Data Structure
 * DSO - Data Structure Object (table record)
 * TO - Template Object
 *
 ************************************/







/**
 * Class for controlling the TemplaVoila module.
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_templavoila
 */
class tx_templavoila_cm1 extends t3lib_SCbase {

		// Static:
	var $theDisplayMode = '';	// Set to ->MOD_SETTINGS[]

		// Internal, dynamic:
	var $markupFile = '';		// Used to store the name of the file to mark up with a given path.
	var $markupObj = '';
	var $elNames = array();
	var $templatePID = '';		// The sysfolder's/page's UID which contains Data Structure Objects / Template Objects. Set by TSconfig


		// GPvars:
	var $mode;					// Looking for "&mode", which defines if we draw a frameset (default), the module (mod) or display (display)
	
		// GPvars for MODULE mode
	var $displayFile = '';		// (GPvar "file", shared with DISPLAY mode!) The file to display, if file is referenced directly from filelist module. Takes precedence over displayTable/displayUid
	var $displayTable = '';		// (GPvar "table") The table from which to display element (Data Structure object [tx_templavoila_datastructure], template object [tx_templavoila_tmplobj])
	var $displayUid = '';		// (GPvar "uid") The UID to display (from ->displayTable)
	var $displayPath = '';		// (GPvar "htmlPath") The "HTML-path" to display from the current file

		// GPvars for MODULE mode, specific to mapping a DS:
	var $_preview;
	var $htmlPath;
	var $mapElPath;
	var $doMappingOfPath;
	var $showPathOnly;
	var $mappingToTags;
	var $DS_element;
	var $DS_cmd;
	var $fieldName;	
	
		// GPvars for DISPLAY mode:
	var $show;					// Boolean; if true no mapping-links are rendered.
	var $preview;				// Boolean; if true, the currentMappingInfo preview data is merged in
	var $limitTags;				// String, list of tags to limit display by
	var $path;					// HTML-path to explode in template.
	

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 * 
	 * @return	void		
	 */
	function menuConfig()    {
	    global $LANG;
	    $this->MOD_MENU = Array (
            'displayMode' => array	(
				'explode' => 'Mode: Exploded Visual',
#				'_' => 'Mode: Overlay',
				'source' => 'Mode: HTML Source ',
#				'borders' => 'Mode: Table Borders',
			),
			'showDSxml' => ''
        );
        parent::menuConfig();
    }

	/**
	 * Main function, distributes the load between the module and display modes.
	 * "Display" mode is when the exploded template file is shown in an IFRAME
	 * 
	 * @return	void		
	 */
	function main()	{

			// Getting PID of sysfolder / page containing TO / DSO
		$confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
		$this->templatePID = intval($confArray['config.']['templatePID']);

			// Setting GPvars:
		$this->mode = t3lib_div::GPvar('mode');

			// Selecting display or module mode:
		switch((string)$this->mode)	{
			case 'display':
				$this->main_display();
			break;
			default:
				$this->main_mode();
			break;
		}
	}

	/**
	 * Prints module content. 
	 * Is only used in case of &mode = "mod" since both "display" mode and frameset is outputted + exiting before this is called.
	 * 
	 * @return	void		
	 */
	function printContent()	{
		$this->content.=$this->doc->middle();
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}









	/*****************************************
	 *
	 * MODULE mode
	 *
	 *****************************************/

	/**
	 * Main function of the MODULE. Write the content to $this->content
	 * There are three main modes:
	 * - Based on a file reference, creating/modifying a DS/TO
	 * - Based on a Template Object uid, remapping
	 * - Based on a Data Structure uid, selecting a Template Object to map.
	 * 
	 * @return	void		
	 */
	function main_mode()	{
		global $LANG,$BACK_PATH;
		
			// Draw the header.
		$this->doc = t3lib_div::makeInstance('noDoc');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->docType = 'xhtml_trans';
		$this->doc->inDocStylesArray[]='
			DIV.typo3-noDoc { width: 98%; margin: 0 0 0 0; }
			DIV.typo3-noDoc H2 { width: 100%; }
			TABLE#c-mapInfo {margin-top: 10px; margin-bottom: 5px; }
			TABLE#c-mapInfo TR TD {padding-right: 20px;}
		';

			// General GPvars for module mode:
		$this->displayFile = t3lib_div::GPvar('file');
		$this->displayTable = t3lib_div::GPvar('table');
		$this->displayUid = t3lib_div::GPvar('uid');
		$this->displayPath = t3lib_div::GPvar('htmlPath');
		
			// GPvars specific to the DS listing/table and mapping features:
		$this->_preview = t3lib_div::GPvar('_preview');
		$this->mapElPath = t3lib_div::GPvar('mapElPath');
		$this->doMappingOfPath = t3lib_div::GPvar('doMappingOfPath');
		$this->showPathOnly = t3lib_div::GPvar('showPathOnly');
		$this->mappingToTags = t3lib_div::GPvar('mappingToTags');
		$this->DS_element = t3lib_div::GPvar('DS_element');
		$this->DS_cmd = t3lib_div::GPvar('DS_cmd');
		$this->fieldName = t3lib_div::GPvar('fieldName');
		
		
			// Setting up form-wrapper:
		$this->doc->form='<form action="'.$this->linkThisScript(array()).'" method="post" name="pageform">';

			// JavaScript
		$this->doc->JScode.= $this->doc->wrapScriptTags('
			script_ended = 0;
			function jumpToUrl(URL)	{	//
				document.location = URL;
			}
			function updPath(inPath)	{	//
				document.location = "'.t3lib_div::linkThisScript(array('htmlPath'=>'','doMappingOfPath'=>1)).'&htmlPath="+top.rawurlencode(inPath);
			}
		');
		
			// Setting up the context sensitive menu:
		$CMparts = $this->doc->getContextMenuCode();
		$this->doc->bodyTagAdditions = $CMparts[1];
		$this->doc->JScode.=$CMparts[0];
		$this->doc->postCode.= $CMparts[2];
		
		$this->content.=$this->doc->startPage($LANG->getLL('title'));
		$this->content.=$this->doc->header($LANG->getLL('title'));
		$this->content.=$this->doc->spacer(5);

			// Render content, depending on input values:
		if ($this->displayFile)	{	// Browsing file directly, possibly creating a template/data object records.
			$this->renderFile();
		} elseif ($this->displayTable=='tx_templavoila_datastructure') {	// Data source display
			$this->renderDSO();
		} elseif ($this->displayTable=='tx_templavoila_tmplobj') {	// Data source display
			$this->renderTO();
		}

			// Add spacer:
		$this->content.=$this->doc->spacer(10);
	}
	
	/**
	 * Renders the display of DS/TO creation directly from a file
	 * 
	 * @return	void		
	 */
	function renderFile()	{
		if (@is_file($this->displayFile) && t3lib_div::getFileAbsFileName($this->displayFile))		{
			
			$this->editDataStruct=1;
			$content='';

				// Get session data:							
			if (!t3lib_div::GPvar('_clear'))	{
				$sesDat = $GLOBALS['BE_USER']->getSessionData($this->MCONF['name'].'_mappingInfo');
			} else $GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',array());

			if (t3lib_div::GPvar('_load_ds_xml') && (t3lib_div::GPvar('_load_ds_xml_content') || t3lib_div::GPvar('_load_ds_xml_to')))	{
				$to_uid = t3lib_div::GPvar('_load_ds_xml_to');
				if ($to_uid)	{
					$toREC = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj',$to_uid);
					$tM = unserialize($toREC['templatemapping']);
					$sesDat=array();
					$sesDat['currentMappingInfo'] = $tM['MappingInfo'];
					$dsREC = t3lib_BEfunc::getRecord('tx_templavoila_datastructure',$toREC['datastructure']);
					
					$ds=t3lib_div::xml2array($dsREC['dataprot']);
					$sesDat['dataStruct']['ROOT']=$sesDat['autoDS']['ROOT']=$ds['ROOT'];
					$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
				} else {
					$ds = t3lib_div::xml2array(t3lib_div::GPvar('_load_ds_xml_content'));
					$sesDat=array();
					$sesDat['dataStruct']['ROOT']=$sesDat['autoDS']['ROOT']=$ds['ROOT'];
					$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
				}
			}
			
			$dataStruct = is_array($sesDat['autoDS']) ? $sesDat['autoDS'] : array(
					'meta' => array(
						'langChildren' => 1,
						'langDisable' => 1
					),
					'ROOT' => array (
						'tx_templavoila' => array (
							'title' => 'ROOT',
							'description' => 'Select the HTML element on the page which you want to be the overall container for the template/grab.',
						),
						'type' => 'array',
						'el' => array()
					)
				);
			$currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();
			$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo,$dataStruct);
			
			$inputData = t3lib_div::GPvar('dataMappingForm',1);
			if (t3lib_div::GPvar('_save_data_mapping') && is_array($inputData))	{
				$sesDat['currentMappingInfo'] = $currentMappingInfo = t3lib_div::array_merge_recursive_overrule($currentMappingInfo,$inputData);
				$sesDat['dataStruct'] = $dataStruct;
				$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
			}

			
			if (t3lib_div::GPvar('_updateDS'))	{
				$inDS = t3lib_div::GPvar('autoDS',1);
				if (is_array($inDS))	{
					$dataStruct = $sesDat['autoDS'] = t3lib_div::array_merge_recursive_overrule($dataStruct,$inDS);
					$sesDat['dataStruct'] = $dataStruct;
					$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);							
				}
			}
			
			if (t3lib_div::GPvar('DS_element_DELETE'))	{
				$ref = explode('][',substr(t3lib_div::GPvar('DS_element_DELETE'),1,-1));
				$this->unsetArrayPath($dataStruct,$ref);

					$sesDat['dataStruct'] = $sesDat['autoDS'] = $dataStruct;
					$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);							
			}
			
			if (t3lib_div::GPvar('_showXMLDS') || t3lib_div::GPvar('_saveDSandTO') || t3lib_div::GPvar('_updateDSandTO'))	{
					// Template mapping prepared:
				$templatemapping=array();
				$templatemapping['MappingInfo']=$currentMappingInfo;
					// Getting cached data:
				reset($dataStruct);
				#$firstKey = key($dataStruct);
				$firstKey='ROOT';
				if ($firstKey)	{
					$fileContent = t3lib_div::getUrl($this->displayFile);
					$htmlParse = t3lib_div::makeInstance('t3lib_parsehtml');
					$relPathFix=dirname(substr($this->displayFile,strlen(PATH_site))).'/';
						$fileContent = $htmlParse->prefixResourcePath($relPathFix,$fileContent);
					$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
					$contentSplittedByMapping=$this->markupObj->splitContentToMappingInfo($fileContent,$currentMappingInfo);
					$templatemapping['MappingData_cached']=$contentSplittedByMapping['sub'][$firstKey];
				}
			}

			if (t3lib_div::GPvar('_saveDSandTO'))	{

					// DS:
				$dataArr=array();
				$dataArr['tx_templavoila_datastructure']['NEW']['pid']=$this->templatePID;
				$dataArr['tx_templavoila_datastructure']['NEW']['title']=t3lib_div::GPvar('_saveDSandTO_title');
				$dataArr['tx_templavoila_datastructure']['NEW']['scope']=t3lib_div::GPvar('_saveDSandTO_type');
				$storeDataStruct=$dataStruct;
				if (is_array($storeDataStruct['ROOT']['el']))		$this->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'],$contentSplittedByMapping['sub']['ROOT']);
#debug($storeDataStruct);
				$dataArr['tx_templavoila_datastructure']['NEW']['dataprot']=t3lib_div::array2xml($storeDataStruct,'',0,'T3DataStructure');

				$tce = t3lib_div::makeInstance("t3lib_TCEmain");
				$tce->stripslashes_values=0;
				$tce->start($dataArr,array());
				$tce->process_datamap();
				
				if ($tce->substNEWwithIDs['NEW'])	{


					$dataArr=array();
					$dataArr['tx_templavoila_tmplobj']['NEW']['pid']=$this->templatePID;
					$dataArr['tx_templavoila_tmplobj']['NEW']['title']=t3lib_div::GPvar('_saveDSandTO_title').' [Template]';
					$dataArr['tx_templavoila_tmplobj']['NEW']['datastructure']=intval($tce->substNEWwithIDs['NEW']);
					$dataArr['tx_templavoila_tmplobj']['NEW']['fileref']=substr($this->displayFile,strlen(PATH_site));
					$dataArr['tx_templavoila_tmplobj']['NEW']['templatemapping']=serialize($templatemapping);
#debug($templatemapping);	
					$tce = t3lib_div::makeInstance("t3lib_TCEmain");
					$tce->stripslashes_values=0;
					$tce->start($dataArr,array());
					$tce->process_datamap();
				}

						// WHAT ABOUT slashing of the input values!!!!??? That should be done!
				unset($tce);
				$content.='<strong>SAVED...</strong><br />';
			}
			
			if (t3lib_div::GPvar('_updateDSandTO'))	{
				$toREC = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj',t3lib_div::GPvar('_saveDSandTO_TOuid'));
				$dsREC = t3lib_BEfunc::getRecord('tx_templavoila_datastructure',$toREC['datastructure']);

				if ($toREC['uid'] && $dsREC['uid'])	{
					
						// DS:
					$dataArr=array();
					$storeDataStruct=$dataStruct;
					if (is_array($storeDataStruct['ROOT']['el']))		$this->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'],$contentSplittedByMapping['sub']['ROOT']);
					$dataArr['tx_templavoila_datastructure'][$dsREC['uid']]['dataprot']=t3lib_div::array2xml($storeDataStruct,'',0,'T3DataStructure');

					$tce = t3lib_div::makeInstance("t3lib_TCEmain");
					$tce->stripslashes_values=0;
					$tce->start($dataArr,array());
					$tce->process_datamap();
					
						// TO:
					$dataArr=array();
					$dataArr['tx_templavoila_tmplobj'][$toREC['uid']]['fileref']=substr($this->displayFile,strlen(PATH_site));
					$dataArr['tx_templavoila_tmplobj'][$toREC['uid']]['templatemapping']=serialize($templatemapping);

					$tce = t3lib_div::makeInstance("t3lib_TCEmain");
					$tce->stripslashes_values=0;
					$tce->start($dataArr,array());
					$tce->process_datamap();

							// WHAT ABOUT slashing of the input values!!!!??? That should be done!
					unset($tce);
					$content.='<strong>UPDATED...</strong><br />';
				}
			}
			
			
			if (t3lib_div::GPvar('_showXMLDS'))	{
				$storeDataStruct=$dataStruct;
				if (is_array($storeDataStruct['ROOT']['el']))		$this->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'],$contentSplittedByMapping['sub']['ROOT']);
				$content.='<h3>XML configuration:</h3><pre>'.htmlspecialchars(t3lib_div::array2xml($storeDataStruct,'',0,'T3DataStructure')).'</pre>';
			}
			
			
			
			

			$content.='<h3>Load DS XML</h3>';
			$content.='<textarea cols="" rows="" name="_load_ds_xml_content"></textarea><br />';
			
			$opt=array();
			$opt[]='<option value="0"></option>';
			$query = 'SELECT * FROM tx_templavoila_tmplobj WHERE pid='.$this->templatePID.' AND datastructure>0 '.t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj').' ORDER BY title';
			$res = mysql(TYPO3_db,$query);
			while($row = mysql_fetch_assoc($res))	{
				$opt[]='<option value="'.htmlspecialchars($row['uid']).'">'.htmlspecialchars($row['title']).'</option>';
			}
			$content.='<select name="_load_ds_xml_to">'.implode('',$opt).'</select><br />';
			$content.='<input type="submit" name="_load_ds_xml" value="LOAD" />';
			

			
			
			
			$content.='<h3>Creating Data Structure / Mapping to template:</h3>';
			$content.='<hr><strong>Create new Data Structure and Template Object:</strong><br />
			Title: <input type="text" name="_saveDSandTO_title" /><br />
			Type: <select name="_saveDSandTO_type">
						<option></option>
						<option value="1">Page Template</option>
						<option value="2">Content Element</option>
					</select><br />
			<input type="submit" name="_saveDSandTO" value="Create DS and TO" /> 
			<hr>
			Alternatively, save to existing template record:<br />
			<select name="_saveDSandTO_TOuid">'.implode('',$opt).'</select><br />
			<input type="submit" name="_updateDSandTO" value="Update TO/DS" />
			<hr>
			<input type="submit" name="_showXMLDS" value="Show XML" /> 
			<input type="submit" name="_clear" value="Clear current mappings" /> 
			<input type="submit" name="_DO_NOTHING" value="Refresh..." /> 
			<input type="submit" name="_preview" value="PREVIEW" />
			';
			
			unset($dataStruct['meta']);			
			$content.= $this->renderTemplateMapper($this->displayFile,$this->displayPath,$dataStruct,$currentMappingInfo);
		}
	
		$this->content.=$this->doc->section('Browsing file...',$content,0,1);
	}
	
	/**
	 * Renders the display of Data Structure Objects.
	 * 
	 * @return	void		
	 */
	function renderDSO()	{
		if (intval($this->displayUid)>0)	{
			$row = t3lib_BEfunc::getRecord('tx_templavoila_datastructure',$this->displayUid);
			if (is_array($row))	{

					// Get title and icon:
				$icon = t3lib_iconworks::getIconImage('tx_templavoila_datastructure',$row,$GLOBALS['BACK_PATH'],' align="top" title="UID: '.$this->displayUid.'"');
				$title = t3lib_BEfunc::getRecordTitle('tx_templavoila_datastructure',$row,1);
				$content.=$this->doc->wrapClickMenuOnIcon($icon,'tx_templavoila_datastructure',$row['uid'],1).
						'<strong>'.$title.'</strong><br />';
				
					// Get Data Structure:
				$dataStruct = $this->getDataStructFromDSO($row['dataprot']);
				if (is_array($dataStruct))	{
						// Showing Data Structure:
					unset($dataStruct['meta']);
					$tRows = $this->drawDataStructureMap($dataStruct);
					$content.='
					
					<!--
						Data Structure content:
					-->
					<div id="c-ds">
						<h4>Data Structure in record:</h4>
						<table border="0" cellspacing="2" cellpadding="2">
									<tr class="bgColor5">
										<td><strong>Data Element:</strong></td>
										<td><strong>FieldName:</strong></td>
										<td><strong>Mapping instructions:</strong></td>
										<td><strong>Rules:</strong></td>
									</tr>
						'.implode('',$tRows).'
						</table>
					</div>';
				} else {
					$content.='<h4>ERROR: No Data Structure was defined in the record... (Must be T3DataStructure XML content)</h4>';
				}
				
					// Get Template Objects pointing to this Data Structure
				$query = 'SELECT * FROM tx_templavoila_tmplobj WHERE pid='.$this->templatePID.' AND datastructure='.intval($row['uid']).t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj');
				$res = mysql(TYPO3_db,$query);
				$tRows=array();
				$tRows[]='
							<tr class="bgColor5">
								<td><strong>Title:</strong></td>
								<td><strong>File reference:</strong></td>
								<td><strong>Mapping Data Lgd:</strong></td>
							</tr>';
				$TOicon = t3lib_iconworks::getIconImage('tx_templavoila_tmplobj',array(),$GLOBALS['BACK_PATH'],' align="top"');
				
					// Listing Template Objects with links:
				while($TO_Row = mysql_fetch_assoc($res))	{
					$tRows[]='
							<tr class="bgColor4">
								<td nowrap="nowrap">'.$this->doc->wrapClickMenuOnIcon($TOicon,'tx_templavoila_tmplobj',$TO_Row['uid'],1).
									'<a href="'.htmlspecialchars('index.php?table=tx_templavoila_tmplobj&uid='.$TO_Row['uid'].'&_reload_from=1').'">'.
									t3lib_BEfunc::getRecordTitle('tx_templavoila_tmplobj',$TO_Row,1).'</a>'.
									'</td>
								<td nowrap="nowrap">'.htmlspecialchars($TO_Row['fileref']).' <strong>'.(!t3lib_div::getFileAbsFileName($TO_Row['fileref'],1)?'(NOT FOUND!)':'(OK)').'</strong></td>
								<td>'.strlen($TO_Row['templatemapping']).'</td>
							</tr>';
				}
				
				$content.='
					
					<!--
						Template Objects attached to Data Structure Record:
					-->
					<div id="c-to">
						<h4>Template Objects using this Data Structure:</h4>
						<table border="0" cellpadding="2" cellspacing="2">
						'.implode('',$tRows).'
						</table>
					</div>';
				
					// Display XML of data structure:
				if (is_array($dataStruct))	{
					$content.='
					
					<!--
						Data Structure XML:
					-->
					<br />
					<div id="c-dsxml">
						<h3>Data Structure XML:</h3>
						<p>'.t3lib_BEfunc::getFuncCheck('','SET[showDSxml]',$this->MOD_SETTINGS['showDSxml'],'',t3lib_div::implodeArrayForUrl('',$GLOBALS['HTTP_GET_VARS'],'',1,1)).' Show XML</p>
						<pre>'.
							($this->MOD_SETTINGS['showDSxml'] ? htmlspecialchars(t3lib_div::array2xml($dataStruct,'',0,'T3DataStructure')) : '').'
						</pre>
					</div>
					';
				}
			} else {
				$content.='ERROR: No Data Structure Record with the UID '.$this->displayUid;
			}
			$this->content.=$this->doc->section('Data Structure Object',$content,0,1);
		} else {
			$this->content.=$this->doc->section('Data Structure Object ERROR','No UID was found pointing to a Data Structure Object record.',0,1,3);
		}
	}
	
	/**
	 * Renders the display of template objects.
	 * 
	 * @return	void		
	 */
	function renderTO()	{
		if (intval($this->displayUid)>0)	{
			$row = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj',$this->displayUid);

			if (is_array($row))	{
				
				$tRows=array();
				$tRows[]='
					<tr class="bgColor5">
						<td colspan="2"><strong>Template Object Details:</strong></td>
					</tr>';
				
					// Get title and icon:
				$icon = t3lib_iconworks::getIconImage('tx_templavoila_tmplobj',$row,$GLOBALS['BACK_PATH'],' align="top" title="UID: '.$this->displayUid.'"');
				$title = t3lib_BEfunc::getRecordTitle('tx_templavoila_tmplobj',$row,1);
				$tRows[]='
					<tr class="bgColor4">
						<td>Template Object:</td>
						<td>'.$this->doc->wrapClickMenuOnIcon($icon,'tx_templavoila_tmplobj',$row['uid'],1).$title.'</td>
					</tr>';
				
					// Find the file:
				$theFile = t3lib_div::getFileAbsFileName($row['fileref'],1);
				if ($theFile && @is_file($theFile))	{
					$relFilePath = substr($theFile,strlen(PATH_site));
					$onCl = 'return top.openUrlInWindow(\''.t3lib_div::getIndpEnv('TYPO3_SITE_URL').$relFilePath.'\',\'FileView\');';
					$tRows[]='
						<tr class="bgColor4">
							<td>Template File:</td>
							<td><a href="#" onclick="'.htmlspecialchars($onCl).'">'.htmlspecialchars($relFilePath).'</a></td>
						</tr>';
				
						// Finding Data Structure Record:
					$DSOfile='';	
					$dsValue = $row['datastructure'];
					if ($row['parent'])	{
						$parentRec = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj',$row['parent'],'datastructure');
						$dsValue=$parentRec['datastructure'];
					}
					
					if (t3lib_div::testInt($dsValue))	{
						$DS_row = t3lib_BEfunc::getRecord('tx_templavoila_datastructure',$dsValue);
					} else {
						$DSOfile = t3lib_div::getFileAbsFileName($dsValue);
					}
					if (is_array($DS_row) || @is_file($DSOfile))	{
							
							// Get main DS array:
						if (is_array($DS_row))	{
								// Get title and icon:
							$icon = t3lib_iconworks::getIconImage('tx_templavoila_datastructure',$DS_row,$GLOBALS['BACK_PATH'],' align="top" title="UID: '.$DS_row['uid'].'"');
							$title = t3lib_BEfunc::getRecordTitle('tx_templavoila_datastructure',$DS_row,1);
							$tRows[]='
								<tr class="bgColor4">
									<td>Data Structure Record:</td>
									<td>'.$this->doc->wrapClickMenuOnIcon($icon,'tx_templavoila_datastructure',$DS_row['uid'],1).$title.'</td>
								</tr>';

								// Link to updating DS/TO:
							$onCl = 'index.php?file='.rawurlencode($theFile).'&_load_ds_xml=1&_load_ds_xml_to='.$row['uid'];
							$onClMsg = '
								if (confirm(unescape(\''.rawurlencode('Warning: You should only modify Data Structures and Template Objects which have not been manually edited.'.chr(10).'You risk that manual changes will be removed without further notice!').'\'))) {
									document.location=\''.$onCl.'\';
								}
								return false;
								';
							$tRows[]='
								<tr class="bgColor4">
									<td>&nbsp;</td>
									<td><input type="submit" name="_" value="Modify DS / TO" onclick="'.htmlspecialchars($onClMsg).'"/></td>
								</tr>';

								// Read Data Structure:
							$dataStruct = $this->getDataStructFromDSO($DS_row['dataprot']);
						} else {
								// Show filepath of external XML file:
							$relFilePath = substr($DSOfile,strlen(PATH_site));
							$onCl = 'return top.openUrlInWindow(\''.t3lib_div::getIndpEnv('TYPO3_SITE_URL').$relFilePath.'\',\'FileView\');';
							$tRows[]='
								<tr class="bgColor4">
									<td>Data Structure File:</td>
									<td><a href="#" onclick="'.htmlspecialchars($onCl).'">'.htmlspecialchars($relFilePath).'</a></td>
								</tr>';

								// Read Data Structure:
							$dataStruct = $this->getDataStructFromDSO('',$DSOfile);
						}
						
							// Write header of page:
						$content.='

							<!-- 
								Template Object Header:
							-->
							<table border="0" cellpadding="2" cellspacing="1" id="c-toHeader">
								'.implode('',$tRows).'
							</table>
						';
						
							
							
							// If there is a valid data structure, draw table:
						if (is_array($dataStruct))	{
							unset($dataStruct['meta']);		// Remove the "meta" section, otherwise the mapper will show this!	
	
								// Processing the editing:
							list($editContent,$currentMappingInfo) = $this->renderTO_editProcessing($dataStruct,$row,$theFile);
							
							$content.='

							<!--
								Data Structure mapping table:
							-->
							<h3>Data Structure to be mapped to HTML template:</h3>
							
							'.$this->renderTemplateMapper($theFile,$this->displayPath,$dataStruct,$currentMappingInfo,$editContent);
							
						} else $content.='ERROR: No Data Structure Record could be found with UID "'.$dsValue.'"';
					} else $content.='ERROR: No Data Structure Record could be found with UID "'.$dsValue.'"';
				} else $content.='ERROR: The file "'.$row['fileref'].'" could not be found!';
			} else $content.='ERROR: No Template Object Record with the UID '.$this->displayUid;
			$this->content.=$this->doc->section('',$content,0,1);
		} else {
			$this->content.=$this->doc->section('Template Object ERROR','No UID was found pointing to a Template Object record.',0,1,3);
		}
	}

	/**
	 * Process editing of a TO for renderTO() function
	 * 
	 * @param	array		Data Structure (without 'meta' section). Passed by reference; The sheets found inside will be resolved if found!
	 * @param	array		TO record row
	 * @param	string		Template file path (absolute)
	 * @return	array		Array with two keys (0/1) with a) content and b) currentMappingInfo which is retrieved inside.
	 * @see renderTO()
	 */
	function renderTO_editProcessing(&$dataStruct,$row,$theFile)	{
		$msg = array();
	
			// GPvars, cmd:
		$cmd = '';
		if (t3lib_div::GPvar('_reload_from'))	{
			$cmd = 'reload_from';
		} elseif (t3lib_div::GPvar('_clear'))	{
			$cmd = 'clear';
		} elseif (t3lib_div::GPvar('_save_data_mapping'))	{
			$cmd = 'save_data_mapping';
		} elseif (t3lib_div::GPvar('_save_to'))	{
			$cmd = 'save_to';
		}
		
			// GPvars, data
		$inputData = t3lib_div::GPvar('dataMappingForm',1);
	
			// If that array contains sheets, then traverse them:
		if (is_array($dataStruct['sheets']))	{
			$dSheets = t3lib_div::resolveAllSheetsInDS($dataStruct);
			$dataStruct=array(
				'ROOT' => array (
					'tx_templavoila' => array (
						'title' => 'ROOT of MultiTemplate',
						'description' => 'Select the ROOT container for this template project. Probably just select a body-tag or some other HTML element which encapsulates ALL sub templates!',
					),
					'type' => 'array',
					'el' => array()
				)
			);
			foreach($dSheets['sheets'] as $nKey => $lDS)	{
				if (is_array($lDS['ROOT']))	{
					$dataStruct['ROOT']['el'][$nKey]=$lDS['ROOT'];
				}
			}
		}

			// Getting data from tmplobj
		$templatemapping = unserialize($row['templatemapping']);
		if (!is_array($templatemapping))	$templatemapping=array();

			// Get session data:							
		$sesDat = $GLOBALS['BE_USER']->getSessionData($this->MCONF['name'].'_mappingInfo');
		if ($cmd=='reload_from' || $cmd=='clear')	{
			$currentMappingInfo = is_array($templatemapping['MappingInfo'])&&$cmd!='clear' ? $templatemapping['MappingInfo'] : array();
			$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo,$dataStruct);
			$sesDat['currentMappingInfo'] = $currentMappingInfo;
			$sesDat['dataStruct'] = $dataStruct;
			$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
		} else {
			$currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();
			$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo,$dataStruct);
			if ($cmd=='save_data_mapping' && is_array($inputData))	{
				$sesDat['currentMappingInfo'] = $currentMappingInfo = t3lib_div::array_merge_recursive_overrule($currentMappingInfo,$inputData);
				$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
			}
		}
		
			// SAVE to template object
		if ($cmd=='save_to')	{
			$dataArr=array();
			$templatemapping['MappingInfo'] = $currentMappingInfo;
				// Getting cached data:
			reset($dataStruct);
			$firstKey = key($dataStruct);
			if ($firstKey)	{
				$fileContent = t3lib_div::getUrl($theFile);
				$htmlParse = t3lib_div::makeInstance('t3lib_parsehtml');
				$relPathFix=dirname(substr($theFile,strlen(PATH_site))).'/';
				$fileContent = $htmlParse->prefixResourcePath($relPathFix,$fileContent);
				$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
				$contentSplittedByMapping=$this->markupObj->splitContentToMappingInfo($fileContent,$currentMappingInfo);
				$templatemapping['MappingData_cached']=$contentSplittedByMapping['sub'][$firstKey];
			}
			
			$dataArr['tx_templavoila_tmplobj'][$row['uid']]['templatemapping'] = serialize($templatemapping);
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values=0;
			$tce->start($dataArr,array());
			$tce->process_datamap();
			unset($tce);
			$msg[] = 'Mapping information was saved to the current Template Object!';
			$row = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj',$this->displayUid);
			$templatemapping = unserialize($row['templatemapping']);
		}

			// Making the menu
		$menuItems=array();
		$menuItems[]='<input type="submit" name="_clear" value="Clear all" title="Clears all mapping information currently set." />';
		$menuItems[]='<input type="submit" name="_preview" value="Preview" title="Will merge sample content into the template according to the current mapping information." />';

		if (serialize($templatemapping['MappingInfo']) != serialize($currentMappingInfo))	{
			$menuItems[]='<input type="submit" name="_save_to" value="Save" title="Saving the current mapping information into the Template Object." />';
			$menuItems[]='<input type="submit" name="_reload_from" value="Revert" title="Reverting to the mapping information in the Template Object." />';
			$msg[] = 'The current mapping information is different from the mapping information in the Template Object';
		}
		
		$content = '

			<!--
				Menu for saving Template Objects
			-->
			<table border="0" cellpadding="2" cellspacing="2" id="c-toMenu">
				<tr class="bgColor5">
					<td>'.implode('</td>
					<td>',$menuItems).'</td>
				</tr>
			</table>
		';
		
			// Making messages:
		foreach($msg as $msgStr)	{
			$content.='
			<p><img src="'.$GLOBALS['BACK_PATH'].'gfx/icon_note.gif" width="18" height="16" border="0" align="top" class="absmiddle" alt="" /><strong>'.htmlspecialchars($msgStr).'</strong></p>'; 
		}
		
		
		return array($content,$currentMappingInfo);
	}









	/*******************************
	 *
	 * Mapper functions
	 *
	 *******************************/
	 
	/**
	 * Creates the template mapper table + form for either direct file mapping or Template Object
	 * 
	 * @param	string		The abs file name to read
	 * @param	string		The HTML-path to follow. Eg. 'td#content table[1] tr[1] / INNER | img[0]' or so. Normally comes from clicking a tag-image in the display frame.
	 * @param	array		The data Structure to map to
	 * @param	array		The current mapping information
	 * @param	string		HTML content to show after the Data Structure table.
	 * @return	string		HTML table.
	 */
	function renderTemplateMapper($displayFile,$path,$dataStruct=array(),$currentMappingInfo=array(),$htmlAfterDSTable='')	{

			// Get file content
		$this->markupFile = $displayFile;
		$fileContent = t3lib_div::getUrl($this->markupFile);
					
			// Init mark up object.
		$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
		
			// Load splitted content from currentMappingInfo array (used to show us which elements maps to some real content).
		$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent,$currentMappingInfo);

			// Show path:
		$pathRendered=t3lib_div::trimExplode('|',$path,1);
		$acc=array();
		foreach($pathRendered as $k => $v)	{
			$acc[]=$v;
			$pathRendered[$k]=$this->linkForDisplayOfPath($v,implode('|',$acc));
		}
		array_unshift($pathRendered,$this->linkForDisplayOfPath('[ROOT]',''));
			
			// Get attributes of the extracted content:
		$attrDat=array();
		$contentFromPath = $this->markupObj->splitByPath($fileContent,$path);	// ,'td#content table[1] tr[1]','td#content table[1]','map#cdf / INNER','td#content table[2] tr[1] td[1] table[1] tr[4] td.bckgd1[2] table[1] tr[1] td[1] table[1] tr[1] td.bold1px[1] img[1] / RANGE:img[2]'
		$firstTag = $this->markupObj->htmlParse->getFirstTag($contentFromPath[1]);
		list($attrDat) = $this->markupObj->htmlParse->get_tag_attributes($firstTag,1);

			// Make options:
		$pathLevels = $this->markupObj->splitPath($path);
		$lastEl = end($pathLevels);

		$optDat=array();
		$optDat[$lastEl['path']]='OUTER (Include tag)';
		$optDat[$lastEl['path'].'/INNER']='INNER (Exclude tag)';

			// Tags, which will trigger "INNER" to be listed on top (because it is almost always INNER-mapping that is needed)
		if (t3lib_div::inList('body,span,h1,h2,h3,h4,h5,h6,div,td,p,b,i,u,a',$lastEl['el']))	{
			$optDat =array_reverse($optDat);
		}

			// Add options for "samelevel" elements:
		$sameLevelElements = $this->markupObj->elParentLevel[$lastEl['parent']];
		if (is_array($sameLevelElements))	{
			$startFound=0;
			foreach($sameLevelElements as $rEl) 	{
				if ($startFound)	{
					$optDat[$lastEl['path'].'/RANGE:'.$rEl]='RANGE to "'.$rEl.'"';
				}
				if (trim($lastEl['parent'].' '.$rEl)==$lastEl['path'])	$startFound=1;
			}
		}
		
			// Add options for attributes:
		if (is_array($attrDat))	{
			foreach($attrDat as $attrK => $v)	{
				$optDat[$lastEl['path'].'/ATTR:'.$attrK]='ATTRIBUTE "'.$attrK.'" (= '.t3lib_div::fixed_lgd($v,15).')';
			}
		}
		
			// Create Data Structure table:
		$content.='

			<!--
				Data Structure table:
			-->
			<table border="0" cellspacing="2" cellpadding="2">
			<tr class="bgColor5">
				<td nowrap="nowrap"><strong>Data Element:</strong></td>
				<td nowrap="nowrap"><strong>Fieldname:</strong></td>
				<td nowrap="nowrap"><strong>'.(!$this->_preview?'Mapping instructions:':'Sample Data:').'</strong><br /><img src="clear.gif" width="200" height="1" alt="" /></td>
				<td nowrap="nowrap"><strong>HTML-path:</strong></td>
				<td nowrap="nowrap"><strong>Action:</strong></td>
				<td nowrap="nowrap"><strong>Rules:</strong></td>
			</tr>
			'.implode('',$this->drawDataStructureMap($dataStruct,1,$currentMappingInfo,$pathLevels,$optDat,$contentSplittedByMapping)).'</table>
			'.$htmlAfterDSTable;
		
			// Make mapping window:
		$limitTags = implode(',',array_keys($this->explodeMappingToTagsStr($this->mappingToTags,1)));
		if (($this->mapElPath && !$this->doMappingOfPath) || $this->showPathOnly || $this->_preview)	{
			$content.=
			'

			<!--
				Visual Mapping Window (Iframe)
			-->			
			<h3>Mapping Window:</h3>
			<!-- <p><strong>File:</strong> '.htmlspecialchars($displayFile).'</p> -->
			<p>'.t3lib_BEfunc::getFuncMenu('','SET[displayMode]',$this->MOD_SETTINGS['displayMode'],$this->MOD_MENU['displayMode'],'',t3lib_div::implodeArrayForUrl('',$GLOBALS['HTTP_GET_VARS'],'',1,1)).'</p>';

			if ($this->_preview)	{
				$content.='
					
					<!--
						Preview information table
					-->
					<table border="0" cellpadding="4" cellspacing="2" id="c-mapInfo">
						<tr class="bgColor5"><td><strong>Preview of Data Structure sample data merged into the mapped tags:</strong></td></tr>
					</table>
				';		
									
					// Add the Iframe:
				$content.=$this->makeIframeForVisual($displayFile,'','',0,1);
			} else {
				$tRows=array();
				if ($this->showPathOnly)	{
					$tRows[]='
						<tr class="bgColor4">
							<td class="bgColor5"><strong>HTML path:</strong></td>
							<td>'.htmlspecialchars($this->displayPath).'</td>
						</tr>
					';
				} else {
					$tRows[]='
						<tr class="bgColor4">
							<td class="bgColor5"><strong>Mapping DS element:</strong></td>
							<td>'.$this->elNames[$this->mapElPath]['tx_templavoila']['title'].'</td>
						</tr>
						<tr class="bgColor4">
							<td class="bgColor5"><strong>Limiting to tags:</strong></td>
							<td>'.htmlspecialchars(($limitTags?strtoupper($limitTags):'(ALL TAGS)')).'</td>
						</tr>
						<tr class="bgColor4">
							<td class="bgColor5"><strong>Instructions:</strong></td>
							<td>'.htmlspecialchars($this->elNames[$this->mapElPath]['tx_templavoila']['description']).'</td>
						</tr>
					';
				
				}
				$content.='
					
					<!--
						Mapping information table
					-->
					<table border="0" cellpadding="2" cellspacing="2" id="c-mapInfo">
						'.implode('',$tRows).'
					</table>
				';					

					// Add the Iframe:
				$content.=$this->makeIframeForVisual($displayFile,$this->displayPath,$limitTags,$this->doMappingOfPath);
			}
		}

		return $content;
	}

	/**
	 * Renders the hierarchical display for a Data Structure.
	 * Calls itself recursively
	 * 
	 * @param	[type]		$dataStruct: ...
	 * @param	[type]		$currentMappingInfo: ...
	 * @param	[type]		$pathLevels: ...
	 * @param	[type]		$optDat: ...
	 * @param	[type]		$contentSplittedByMapping: ...
	 * @param	[type]		$level: ...
	 * @param	[type]		$tRows: ...
	 * @param	[type]		$formPrefix: ...
	 * @param	[type]		$path: ...
	 * @param	[type]		$path: ...
	 * @param	[type]		$mapOK: ...
	 * @return	array		Table rows as an array of <tr> tags
	 */
	function drawDataStructureMap($dataStruct,$mappingMode=0,$currentMappingInfo=array(),$pathLevels=array(),$optDat=array(),$contentSplittedByMapping=array(),$level=0,$tRows=array(),$formPrefix='',$path='',$mapOK=1)	{

			// Data Structure array must be ... and array of course...
		if (is_array($dataStruct))	{
			foreach($dataStruct as $key => $value)	{
				if (is_array($value))	{	// The value of each entry must be an array.

						// ********************
						// Making the row:
						// ********************
					$rowCells=array();

						// Icon:
					if ($value['type']=='array')	{
						if (!$value['section'])	{
							$t='co';
							$tt='Container: ';
						} else {
							$t='sc';
							$tt='Sections: ';
						}
					} elseif ($value['type']=='attr')	{
						$t='at';
						$tt='Attribute: ';
					} else {
						$t='el';
						$tt='Element: ';
					}
					$icon = '<img src="item_'.$t.'.gif" width="24" height="16" border="0" alt="" title="'.$tt.$key.'" style="margin-right: 5px;" class="absmiddle" />';
					
						// Composing title-cell:
					$this->elNames[$formPrefix.'['.$key.']']['tx_templavoila']['title'] = $icon.'<strong>'.htmlspecialchars(t3lib_div::fixed_lgd($value['tx_templavoila']['title'],30)).'</strong>';
					$rowCells['title'] = '<img src="clear.gif" width="'.($level*16).'" height="1" alt="" />'.$this->elNames[$formPrefix.'['.$key.']']['tx_templavoila']['title'];
					
						// Description:
					$this->elNames[$formPrefix.'['.$key.']']['tx_templavoila']['description'] = $rowCells['description'] = htmlspecialchars($value['tx_templavoila']['description']);


						// In "mapping mode", render HTML page and Command links:						
					if ($mappingMode)	{

							// HTML-path + CMD links:
						$isMapOK = 0;
						if ($currentMappingInfo[$key]['MAP_EL'])	{	// If mapping information exists...:
							if (isset($contentSplittedByMapping['cArray'][$key]))	{	// If mapping of this information also succeeded...:
								$cF = implode(chr(10),t3lib_div::trimExplode(chr(10),$contentSplittedByMapping['cArray'][$key],1));
								if (strlen($cF)>200)	{
									$cF = t3lib_div::fixed_lgd($cF,90).' '.t3lib_div::fixed_lgd_pre($cF,90);
								}
								
									// Render HTML path:
								list($pI) = $this->markupObj->splitPath($currentMappingInfo[$key]['MAP_EL']);
								$rowCells['htmlPath'] = '<img src="'.$GLOBALS['BACK_PATH'].'gfx/icon_ok2.gif" width="18" height="16" border="0" alt="" title="'.htmlspecialchars($cF?'Content found ('.strlen($contentSplittedByMapping['cArray'][$key]).' chars):'.chr(10).chr(10).$cF:'Content empty.').'" class="absmiddle" />'.
														'<img src="../html_tags/'.$pI['el'].'.gif" height="9" border="0" alt="" hspace="3" class="absmiddle" title="'.htmlspecialchars($currentMappingInfo[$key]['MAP_EL']).'" />'.
														($pI['modifier'] ? $pI['modifier'].($pI['modifier_value']?':'.$pI['modifier_value']:''):'');
								$rowCells['htmlPath'] = '<a href="'.$this->linkThisScript(array('htmlPath'=>$path.($path?'|':'').ereg_replace('\/[^ ]*$','',$currentMappingInfo[$key]['MAP_EL']),'showPathOnly'=>1)).'">'.$rowCells['htmlPath'].'</a>';

									// CMD links, default content:
								$rowCells['cmdLinks'] = '<a href="'.$this->linkThisScript(array('mapElPath'=>$formPrefix.'['.$key.']','htmlPath'=>$path,'mappingToTags'=>$value['tx_templavoila']['tags'])).'">Re-map</a>';
								$rowCells['cmdLinks'].= '/<a href="'.$this->linkThisScript(array('mapElPath'=>$formPrefix.'['.$key.']','htmlPath'=>$path.($path?'|':'').$pI['path'],'doMappingOfPath'=>1)).'" title="Change mode">Ch.Mode</a>';
								
									// If content mapped ok, set flag:
								$isMapOK=1;
							} else {	// Issue warning if mapping was lost:
								$rowCells['htmlPath'] = '<img src="'.$GLOBALS['BACK_PATH'].'gfx/icon_warning.gif" width="18" height="16" border="0" alt="" title="No content found!" class="absmiddle" />'.htmlspecialchars($currentMappingInfo[$key]['MAP_EL']);
							}
						} else {	// For non-mapped cases, just output a no-break-space:
							$rowCells['htmlPath'] = '&nbsp;';
						}

							// CMD links; Content when current element is under mapping, then display control panel or message:
						if ($this->mapElPath == $formPrefix.'['.$key.']')	{
							if ($this->doMappingOfPath)	{

									// Creating option tags:
								$lastLevel = end($pathLevels);
								$tagsMapping = $this->explodeMappingToTagsStr($value['tx_templavoila']['tags']);
								$mapDat = is_array($tagsMapping[$lastLevel['el']]) ? $tagsMapping[$lastLevel['el']] : $tagsMapping['*'];
								unset($mapDat['']);
								if (is_array($mapDat) && !count($mapDat))	unset($mapDat);

									// Create mapping options:
								$didSetSel=0;
								$opt=array();
								foreach($optDat as $k => $v)	{
									list($pI) = $this->markupObj->splitPath($k);

									if (($value['type']=='attr' && $pI['modifier']=='ATTR') || ($value['type']!='attr' && $pI['modifier']!='ATTR'))	{
										if (
												(!$this->markupObj->tags[$lastLevel['el']]['single'] || $pI['modifier']!='INNER') &&
												(!is_array($mapDat) || ($pI['modifier']!='ATTR' && isset($mapDat[strtolower($pI['modifier']?$pI['modifier']:'outer')])) || ($pI['modifier']=='ATTR' && (isset($mapDat['attr']['*']) || isset($mapDat['attr'][$pI['modifier_value']]))))
												
											)	{

											if($k==$currentMappingInfo[$key]['MAP_EL'])	{
												$sel = ' selected="selected"';
												$didSetSel=1;
											} else {
												$sel = '';
											}
											$opt[]='<option value="'.htmlspecialchars($k).'"'.$sel.'>'.htmlspecialchars($v).'</option>';
										}
									}
								}
								if (!$didSetSel && $currentMappingInfo[$key]['MAP_EL'])	{		// IF no element was selected AND there is a value in the array $currentMappingInfo then we add an element holding this value!
#									$opt[]='<option value="'.htmlspecialchars($currentMappingInfo[$key]['MAP_EL']).'" selected="selected">'.htmlspecialchars('[ - CURRENT - ]').'</option>';
								}
									// Finally, put together the selector box:
								$rowCells['cmdLinks'] = '<img src="../html_tags/'.$lastLevel['el'].'.gif" height="9" border="0" alt="" class="absmiddle" title="'.htmlspecialchars($lastLevel['path']).'" /><br />
									<select name="dataMappingForm'.$formPrefix.'['.$key.'][MAP_EL]">
										'.implode('
										',$opt).'
									</select>
									<br />
									<input type="submit" name="_save_data_mapping" value="Save" />
									<input type="submit" name="_" value="Cancel" />';
							} else {
								$rowCells['cmdLinks'] = '<img src="'.$GLOBALS['BACK_PATH'].'gfx/icon_note.gif" width="18" height="16" border="0" alt="" class="absmiddle" /><strong>Click a tag-icon in the window below to map this element.</strong>';
								$rowCells['cmdLinks'].= '<br />
										<input type="submit" value="Cancel" name="_" onclick="document.location=\''.$this->linkThisScript(array()).'\';return false;" />';
							}
						} elseif (!$rowCells['cmdLinks'] && $mapOK && $value['type']!='no_map') {
							$rowCells['cmdLinks'] = '
										<input type="submit" value="Map" name="_" onclick="document.location=\''.$this->linkThisScript(array('mapElPath'=>$formPrefix.'['.$key.']','htmlPath'=>$path,'mappingToTags'=>$value['tx_templavoila']['tags'])).'\';return false;" />';
						}
					}

						// Display mapping rules:
					$rowCells['tagRules']=implode('<br />',t3lib_div::trimExplode(',',strtolower($value['tx_templavoila']['tags']),1));
					if (!$rowCells['tagRules'])	$rowCells['tagRules']='(ALL)';
					
						// Display edit/delete icons:
					if ($this->editDataStruct)	{
						$editAddCol = '<a href="'.$this->linkThisScript(array('DS_element'=>$formPrefix.'['.$key.']')).'">'.
						'<img src="'.$GLOBALS['BACK_PATH'].'gfx/edit2.gif" width="11" height="12" hspace="2" border="0" alt="" title="Edit entry" />'.
						'</a>';
						$editAddCol.= '<a href="'.$this->linkThisScript(array('DS_element_DELETE'=>$formPrefix.'['.$key.']')).'">'.
						'<img src="'.$GLOBALS['BACK_PATH'].'gfx/garbage.gif" width="11" height="12" hspace="2" border="0" alt="" title="DELETE entry" onclick=" return confirm(\'Are you sure to delete this Data Structure entry?\');" />'.
						'</a>';
						$editAddCol = '<td nowrap="nowrap">'.$editAddCol.'</td>';
					} else {
						$editAddCol = '';
					}
					
						// Description:
					if ($this->_preview)	{
						$rowCells['description'] = is_array($value['tx_templavoila']['sample_data']) ? t3lib_div::view_array($value['tx_templavoila']['sample_data']) : '[No sample data]';
					}

						// Put row together
					$tRows[]='
						
						<tr class="bgColor4">
						<td nowrap="nowrap" valign="top">'.$rowCells['title'].'</td>
						<td nowrap="nowrap" valign="top">'.$key.'</td>
						<td>'.$rowCells['description'].'</td>
						'.($mappingMode 
								? 
							'<td nowrap="nowrap">'.$rowCells['htmlPath'].'</td>
							<td>'.$rowCells['cmdLinks'].'</td>' 
								:
							''
						).'
						<td>'.$rowCells['tagRules'].'</td>
						'.$editAddCol.'
					</tr>';
					
						// Getting editing row, if applicable:
					list($addEditRows,$placeBefore) = $this->drawDataStructureMap_editItem($formPrefix,$key,$value,$level);

						// Add edit-row if found and destined to be set BEFORE:
					if ($addEditRows && $placeBefore)	{
						$tRows[]= $addEditRows;
					}

						// Recursive call:
					if ($value['type']=='array')	{
						$tRows = $this->drawDataStructureMap(
							$value['el'],
							$mappingMode,
							$currentMappingInfo[$key]['el'],
							$pathLevels,
							$optDat,
							$contentSplittedByMapping['sub'][$key],
							$level+1,
							$tRows,
							$formPrefix.'['.$key.'][el]',
							$path.($path?'|':'').$currentMappingInfo[$key]['MAP_EL'],
							$isMapOK
						);
					}
					
						// Add edit-row if found and destined to be set AFTER:
					if ($addEditRows && !$placeBefore)	{
						$tRows[]= $addEditRows;
					}
				}
			}
		}

		return $tRows;		
	}

	/**
	 * Creates the editing row for a Data Structure element - when DS's are build...
	 * 
	 * @param	string		Form element prefix
	 * @param	string		Key for form element
	 * @param	array		Values for element
	 * @param	integer		Indentation level
	 * @return	array		Two values, first is addEditRows (string HTML content), second is boolean whether to place row before or after.
	 */
	function drawDataStructureMap_editItem($formPrefix,$key,$value,$level)	{

			// Init:
		$addEditRows='';
		$placeBefore=0;
		
			// If editing command is set:
		if ($this->editDataStruct)	{
			if ($this->DS_element == $formPrefix.'['.$key.']')	{	// If the editing-command points to this element:
			
					// Initialize, detecting either "add" or "edit" (default) mode:
				$autokey='';
				if ($this->DS_cmd=='add')	{
					if (trim($this->fieldName)!='[Enter new fieldname]' && trim($this->fieldName)!='field_')	{
						$autokey = strtolower(ereg_replace('[^[:alnum:]_]','',trim($this->fieldName)));
						if (isset($value['el'][$autokey]))	{
							$autokey.='_'.substr(md5(microtime()),0,2);
						}
					} else {
						$autokey='field_'.substr(md5(microtime()),0,6);
					}
					$formFieldName = 'autoDS'.$formPrefix.'['.$key.'][el]['.$autokey.']';
					$insertDataArray=array();
				} else {
					$formFieldName = 'autoDS'.$formPrefix.'['.$key.']';
					$insertDataArray=$value;
					$placeBefore=1;
				}

					// Create form:
				$form = '
					Mapping Type:<br />
					<select name="'.$formFieldName.'[type]">
						<option value="">Element</option>
						<option value="array"'.($insertDataArray['type']=='array' ? ' selected="selected"' : '').'>Container for elements</option>
						<option value="attr"'.($insertDataArray['type']=='attr' ? ' selected="selected"' : '').'>Attribute</option>
						<option value="no_map"'.($insertDataArray['type']=='no_map' ? ' selected="selected"' : '').'>[Not mapped]</option>
					</select><br />
					<input type="hidden" name="'.$formFieldName.'[section]" value="0" />
					'.(!$autokey && $insertDataArray['type']=='array' ? 
						'<input type="checkbox" value="1" name="'.$formFieldName.'[section]"'.($insertDataArray['section']?' checked="checked"':'').' /> Make this container a SECTION!<br />' :
						''
					).'
					Title:<br />
					<input type="text" size="80" name="'.$formFieldName.'[tx_templavoila][title]" value="'.htmlspecialchars($insertDataArray['tx_templavoila']['title']).'" /><br />
					Mapping instructions:<br />
					<input type="text" size="80" name="'.$formFieldName.'[tx_templavoila][description]" value="'.htmlspecialchars($insertDataArray['tx_templavoila']['description']).'" /><br />

					'.($insertDataArray['type']!='array' ? '
					Sample Data:<br />
					<input type="text" size="80" name="'.$formFieldName.'[tx_templavoila][sample_data][]" value="'.htmlspecialchars($insertDataArray['tx_templavoila']['sample_data'][0]).'" /><br />

					Editing Type:<br />
					<select name="'.$formFieldName.'[tx_templavoila][eType]">
						<option value="input"'.($insertDataArray['tx_templavoila']['eType']=='input' ? ' selected="selected"' : '').'>Plain input field</option>
						<option value="input_h"'.($insertDataArray['tx_templavoila']['eType']=='input_h' ? ' selected="selected"' : '').'>Header field</option>
						<option value="input_g"'.($insertDataArray['tx_templavoila']['eType']=='input_g' ? ' selected="selected"' : '').'>Header field, Graphical</option>
						<option value="text"'.($insertDataArray['tx_templavoila']['eType']=='text' ? ' selected="selected"' : '').'>Text area for bodytext</option>
						<option value="link"'.($insertDataArray['tx_templavoila']['eType']=='link' ? ' selected="selected"' : '').'>Link field</option>
						<option value="int"'.($insertDataArray['tx_templavoila']['eType']=='int' ? ' selected="selected"' : '').'>Integer value</option>
						<option value="image"'.($insertDataArray['tx_templavoila']['eType']=='image' ? ' selected="selected"' : '').'>Image field</option>
						<option value="imagefixed"'.($insertDataArray['tx_templavoila']['eType']=='imagefixed' ? ' selected="selected"' : '').'>Image field, fixed W+H</option>
						<option value="ce"'.($insertDataArray['tx_templavoila']['eType']=='ce' ? ' selected="selected"' : '').'>Content Elements</option>
						<option value="select"'.($insertDataArray['tx_templavoila']['eType']=='select' ? ' selected="selected"' : '').'>Selector box</option>
						<option value="none"'.($insertDataArray['tx_templavoila']['eType']=='none' ? ' selected="selected"' : '').'>[ NONE ]</option>
						<option value="TypoScriptObject"'.($insertDataArray['tx_templavoila']['eType']=='TypoScriptObject' ? ' selected="selected"' : '').'>TypoScript Object Path</option>
					</select><br />

					[Advanced] Mapping rules:<br />
					<input type="text" size="80" name="'.$formFieldName.'[tx_templavoila][tags]" value="'.htmlspecialchars($insertDataArray['tx_templavoila']['tags']).'" /><br />

					' :'').'

					<input type="submit" name="_updateDS" value="Add" />
<!--								<input type="submit" name="'.$formFieldName.'" value="Delete (!)" />  -->
					<input type="submit" name="_" value="Cancel" /><br />
				';
				$addEditRows='<tr class="bgColor4">
					<td nowrap="nowrap" valign="top">'.
					($this->DS_cmd=='add' ? '<img src="clear.gif" width="'.(($level+1)*16).'" height="1" alt="" /><strong>NEW FIELD:</strong> '.$autokey : '').
					'</td>
					<td colspan="5">'.$form.'</td>
				</tr>';							
			} elseif (!$this->DS_element && $value['type']=='array') {
				$addEditRows='<tr class="bgColor4">
					<td colspan="6"><img src="clear.gif" width="'.(($level+1)*16).'" height="1" alt="" />'.
					'<input type="text" name="'.md5($formPrefix.'['.$key.']').'" value="[Enter new fieldname]" onfocus="if (this.value==\'[Enter new fieldname]\'){this.value=\'field_\';}" />'.
					'<input type="submit" name="_" value="Add" onclick="document.location=\''.$this->linkThisScript(array('DS_element'=>$formPrefix.'['.$key.']','DS_cmd'=>'add')).'&amp;fieldName=\'+document.pageform[\''.md5($formPrefix.'['.$key.']').'\'].value; return false;" />'.
					'</td>
				</tr>';							
			}
		}
		
			// Return edit row:
		return array($addEditRows,$placeBefore);
	}









	/****************************************************
	 *
	 * Helper-functions for File-based DS/TO creation
	 *
	 ****************************************************/
	 
	/**
	 * When mapping HTML files to DS the field types are selected amount some presets - this function converts these presets into the actual settings needed in the DS
	 * Typically called like: ->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'],$contentSplittedByMapping['sub']['ROOT']);
	 * 
	 * @param	array		Data Structure, passed by reference!
	 * @param	array		Actual template content splitted by Data Structure
	 * @return	void		Notice, the result is directly written in $elArray
	 * @see renderFile()
	 */
	function substEtypeWithRealStuff(&$elArray,$v_sub=array())	{

			// Traverse array
		foreach($elArray as $key => $value)	{

			if ($elArray[$key]['type']=='array')	{	// If array, then unset:
				unset($elArray[$key]['tx_templavoila']['sample_data']);

			} else {	// Only non-arrays can have configuration (that is elements and attributes)
				
					// Getting some information about the HTML content (eg. images width/height if applicable)
				$contentInfo = $this->substEtypeWithRealStuff_contentInfo(trim($v_sub['cArray'][$key]));

					// Based on the eType (the preset type) we make configuration settings:
				switch($elArray[$key]['tx_templavoila']['eType'])	{
					case 'text':
						$elArray[$key]['TCEforms']['config'] = array(
							'type' => 'text',
							'cols' => '48',
							'rows' => '5',
						);
						$elArray[$key]['tx_templavoila']['proc']['HSC']=1;
					break;
					case 'image':
					case 'imagefixed':
						$elArray[$key]['TCEforms']['config'] = array(
							'type' => 'group',
							'internal_type' => 'file',
							'allowed' => 'gif,png,jpg,jpeg',
							'max_size' => '1000',
							'uploadfolder' => 'uploads/tx_templavoila',
							'show_thumbs' => '1',
							'size' => '1',
							'maxitems' => '1',
							'minitems' => '0'
						);
						
						$maxW = $contentInfo['img']['width'] ? $contentInfo['img']['width'] : 200;
						$maxH = $contentInfo['img']['height'] ? $contentInfo['img']['height'] : 150;
						
						if ($elArray[$key]['tx_templavoila']['eType']=='image')	{
							$elArray[$key]['tx_templavoila']['TypoScript'] = '
10 = IMAGE
10.file.import = uploads/tx_templavoila/
10.file.import.current = 1
10.file.import.listNum = 0
10.file.maxW = '.$maxW.'
						';
						} else {
							$elArray[$key]['tx_templavoila']['TypoScript'] = '
10 = IMAGE
10.file = GIFBUILDER
10.file {
	XY = '.$maxW.','.$maxH.'
	10 = IMAGE
	10.file.import = uploads/tx_templavoila/
	10.file.import.current = 1
	10.file.import.listNum = 0
	10.file.maxW = '.$maxW.'
	10.file.minW = '.$maxW.'
	10.file.maxH = '.$maxH.'
	10.file.minH = '.$maxH.'
}
						';					
						}
						
							// Finding link-fields on same level and set the image to be linked by that TypoLink:
						$elArrayKeys = array_keys($elArray);
						foreach($elArrayKeys as $theKey)	{
							if ($elArray[$theKey]['tx_templavoila']['eType']=='link')	{
								$elArray[$key]['tx_templavoila']['TypoScript'].= '
10.stdWrap.typolink.parameter.field = '.$theKey.'
								';
							}
						}
					break;
					case 'link':
						$elArray[$key]['TCEforms']['config'] = array(
							'type' => 'input',		
							'size' => '15',
							'max' => '256',
							'checkbox' => '',
							'eval' => 'trim',
							'wizards' => Array(
								'_PADDING' => 2,
								'link' => Array(
									'type' => 'popup',
									'title' => 'Link',
									'icon' => 'link_popup.gif',
									'script' => 'browse_links.php?mode=wizard',
									'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
								)
							)
						);
	
						$elArray[$key]['tx_templavoila']['TypoScript'] = '
10 = TEXT
10.typolink.parameter.current = 1
10.typolink.returnLast = url
						';
						$elArray[$key]['tx_templavoila']['proc']['HSC']=1;
					break;
					case 'ce':
						$elArray[$key]['TCEforms']['config'] = array(
							'type' => 'group',
							'internal_type' => 'db',
							'allowed' => 'tt_content',
							'size' => '5',
							'maxitems' => '200',
							'minitems' => '0',
							'show_thumbs' => '1'
						);
						$elArray[$key]['tx_templavoila']['TypoScript'] = '
10= RECORDS
10.source.current=1
10.tables = tt_content
						';
					break;
					case 'int':
						$elArray[$key]['TCEforms']['config'] = array(
							'type' => 'input',	
							'size' => '4',
							'max' => '4',
							'eval' => 'int',
							'checkbox' => '0',
							'range' => Array (
								'upper' => '999',
								'lower' => '25'
							),
							'default' => 0
						);
					break;
					case 'select':
						$elArray[$key]['TCEforms']['config'] = array(
							'type' => 'select',		
							'items' => Array (	
								Array('', ''),
								Array('Value 1', 'Value 1'),
								Array('Value 2', 'Value 2'),
								Array('Value 3', 'Value 3'),
							),
							'default' => '0'
						);
					break;
					case 'input':
					case 'input_h':
					case 'input_g':
						$elArray[$key]['TCEforms']['config'] = array(
							'type' => 'input',
							'size' => '48',
							'eval' => 'trim',
						);
						
						if ($elArray[$key]['tx_templavoila']['eType']=='input_h')	{	// Text-Header
								// Finding link-fields on same level and set the image to be linked by that TypoLink:
							$elArrayKeys = array_keys($elArray);
							foreach($elArrayKeys as $theKey)	{
								if ($elArray[$theKey]['tx_templavoila']['eType']=='link')	{
									$elArray[$key]['tx_templavoila']['TypoScript'] = '
10 = TEXT
10.current = 1
10.typolink.parameter.field = '.$theKey.'
									';
								}
							}					
						} elseif ($elArray[$key]['tx_templavoila']['eType']=='input_g')	{	// Graphical-Header
	
							$maxW = $contentInfo['img']['width'] ? $contentInfo['img']['width'] : 200;
							$maxH = $contentInfo['img']['height'] ? $contentInfo['img']['height'] : 20;
						
							$elArray[$key]['tx_templavoila']['TypoScript'] = '
10 = IMAGE
10.file = GIFBUILDER
10.file {				  
  XY = '.$maxW.','.$maxH.'
  backColor = #999999

  10 = TEXT
  10.text.current = 1
  10.text.case = upper
  10.fontColor = #FFCC00
  10.fontFile =  t3lib/fonts/vera.ttf
  10.niceText = 0
  10.offset = 0,14
  10.fontSize = 14				  
}
							';
						} else {	// Normal output.
							$elArray[$key]['tx_templavoila']['proc']['HSC']=1;
						}
					break;
					case 'TypoScriptObject':
						unset($elArray[$key]['TCEforms']['config']);
	
						$elArray[$key]['tx_templavoila']['TypoScriptObjPath'] = 'lib.myObject';
					break;
					case 'none':
						unset($elArray[$key]['TCEforms']['config']);
					break;
				}
				
					// Setting TCEforms title for element if configuration is found:
				if (is_array($elArray[$key]['TCEforms']['config']))	{
					$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
				}
			}
			
				// Apart from converting eType to configuration, we also clean up other aspects:
			if (!$elArray[$key]['section'])	unset($elArray[$key]['section']);
			if (!$elArray[$key]['type'])	unset($elArray[$key]['type']);
			if (!$elArray[$key]['tx_templavoila']['description'])	unset($elArray[$key]['tx_templavoila']['description']);
			if (!$elArray[$key]['tx_templavoila']['tags'])	unset($elArray[$key]['tx_templavoila']['tags']);
			
				// Run this function recursively if needed:
			if (is_array($elArray[$key]['el']))	{
				$this->substEtypeWithRealStuff($elArray[$key]['el'],$v_sub['sub'][$key]);
			}
		}	// End loop
	}

	/**
	 * Analyzes the input content for various stuff which can be used to generate the DS.
	 * Basically this tries to intelligently guess some settings.
	 * 
	 * @param	string		HTML Content string
	 * @return	array		Configuration
	 * @see substEtypeWithRealStuff()
	 */
	function substEtypeWithRealStuff_contentInfo($content)	{
		if ($content)	{
			if (substr($content,0,4)=='<img')	{
				$attrib = t3lib_div::get_tag_attributes($content);
				if ((!$attrib['width'] || !$attrib['height']) && $attrib['src'])	{
					$pathWithNoDots = t3lib_div::resolveBackPath($attrib['src']);
					$filePath = t3lib_div::getFileAbsFileName($pathWithNoDots);
					if ($filePath && @is_file($filePath))	{
						$imgInfo = @getimagesize($filePath);
						
						if (!$attrib['width'])	$attrib['width']=$imgInfo[0];
						if (!$attrib['height'])	$attrib['height']=$imgInfo[1];
					}
				}
				return array('img'=>$attrib);
			}
		}
	}









	
	
	
	
	

	/*******************************
	 *
	 * Various helper functions
	 *
	 *******************************/

	/**
	 * Returns Data Structure from the $datString
	 * 
	 * @param	string		XML content which is parsed into an array, which is returned.
	 * @param	string		Absolute filename from which to read the XML data. Will override any input in $datString
	 * @return	mixed		The variable $dataStruct. Should be array. If string, then no structures was found and the function returns the XML parser error.
	 */
	function getDataStructFromDSO($datString,$file='')	{
		if ($file)	{
			$dataStruct = t3lib_div::xml2array(t3lib_div::getUrl($file));
		} else {
			$dataStruct = t3lib_div::xml2array($datString);
		}
		return $dataStruct;
	}

	/**
	 * Creating a link to the display frame for display of the "HTML-path" given as $path
	 * 
	 * @param	string		The text to link
	 * @param	string		The path string ("HTML-path")
	 * @return	string		HTML link, pointing to the display frame.
	 */
	function linkForDisplayOfPath($title,$path)	{
		$theArray=array(
			'file' => $this->markupFile,
			'path' => $path,
			'mode' => 'display'
		);
		$p = t3lib_div::implodeArrayForUrl('',$theArray);	
		
		$content.='<strong><a href="'.htmlspecialchars('index.php?'.$p).'" target="display">'.$title.'</a></strong>';
		return $content;
	}

	/**
	 * Creates a link to this script, maintaining the values of the displayFile, displayTable, displayUid variables.
	 * Primarily used by ->drawDataStructureMap
	 * 
	 * @param	array		Overriding parameters.
	 * @return	string		URL, already htmlspecialchars()'ed
	 * @see drawDataStructureMap()
	 */
	function linkThisScript($array)	{
		$theArray=array(
			'file' => $this->displayFile,
			'table' => $this->displayTable,
			'uid' => $this->displayUid,
		);
		$p = t3lib_div::implodeArrayForUrl('',array_merge($theArray,$array),'',1);	
		
		return htmlspecialchars('index.php?'.$p);
	}

	/**
	 * Creates the HTML code for the IFRAME in which the display mode is shown:
	 * 
	 * @param	string		File name to display in exploded mode.
	 * @param	string		HTML-page
	 * @param	string		Tags which is the only ones to show
	 * @param	boolean		If set, the template is only shown, mapping links disabled.
	 * @param	boolean		Preview enabled.
	 * @return	string		HTML code for the IFRAME.
	 * @see main_display()
	 */
	function makeIframeForVisual($file,$path,$limitTags,$showOnly,$preview=0)	{
		$url = 'index.php?mode=display'.
				'&file='.rawurlencode($file).
				'&path='.rawurlencode($path).
				'&preview='.($preview?1:0).
				($showOnly?'&show=1':'&limitTags='.rawurlencode($limitTags));
		return '<iframe width="100%" height="500" src="'.htmlspecialchars($url).'#_MARKED_UP_ELEMENT" style="border: 1xpx solid black;"></iframe>';
	}

	/**
	 * Converts a list of mapping rules to an array
	 * 
	 * @param	string		Mapping rules in a list
	 * @param	boolean		If set, then the ALL rule (key "*") will be unset.
	 * @return	array		Mapping rules in a multidimensional array.
	 */
	function explodeMappingToTagsStr($mappingToTags,$unsetAll=0)	{
		$elements = t3lib_div::trimExplode(',',strtolower($mappingToTags));
		$output=array();
		foreach($elements as $v)	{
			$subparts = t3lib_div::trimExplode(':',$v);
			$output[$subparts[0]][$subparts[1]][($subparts[2]?$subparts[2]:'*')]=1;
		}
		if ($unsetAll)	unset($output['*']);
		return $output;
	}

	/**
	 * General purpose unsetting of elements in a multidimensional array
	 * 
	 * @param	array		Array from which to remove elements (passed by reference!)
	 * @param	array		An array where the values in the specified order points to the position in the array to unset.
	 * @return	void		
	 */
	function unsetArrayPath(&$dataStruct,$ref)	{
		$key = array_shift($ref);

		if (!count($ref))	{
			unset($dataStruct[$key]);
		} elseif (is_array($dataStruct[$key]))	{
			$this->unsetArrayPath($dataStruct[$key],$ref);
		}
	}

	/**
	 * Function to clean up "old" stuff in the currentMappingInfo array. Basically it will remove EVERYTHING which is not known according to the input Data Structure
	 * 
	 * @param	array		Current Mapping info (passed by reference)
	 * @param	array		Data Structure
	 * @return	void		
	 */
	function cleanUpMappingInfoAccordingToDS(&$currentMappingInfo,$dataStruct)	{	
		if (is_array($currentMappingInfo))	{
			foreach($currentMappingInfo as $key => $value)	{
				if (!isset($dataStruct[$key]))	{
					unset($currentMappingInfo[$key]);
				} else {
					if (is_array($dataStruct[$key]['el']))	{
						$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo[$key]['el'],$dataStruct[$key]['el']);
					}
				}
			}
		}
	}







	/*****************************************
	 *
	 * DISPLAY mode
	 *
	 *****************************************/

	/**
	 * Outputs the display of a marked-up HTML file in the IFRAME
	 * 
	 * @return	void		Exits before return
	 * @see makeIframeForVisual()
	 */
	function main_display()	{
	
			// Setting GPvars:
		$this->displayFile = t3lib_div::GPvar('file');
		$this->show = t3lib_div::GPvar('show');
		$this->preview = t3lib_div::GPvar('preview');
		$this->limitTags = t3lib_div::GPvar('limitTags');
		$this->path = t3lib_div::GPvar('path');

			// Checking if the displayFile parameter is set:
		if (@is_file($this->displayFile) && t3lib_div::getFileAbsFileName($this->displayFile))		{	// FUTURE: grabbing URLS?: 		.... || substr($this->displayFile,0,7)=='http://'
			$content = t3lib_div::getUrl($this->displayFile);
			if ($content)	{
				$relPathFix = $GLOBALS['BACK_PATH'].'../'.dirname(substr($this->displayFile,strlen(PATH_site))).'/';

				if ($this->preview)	{	// In preview mode, merge preview data into the template:
						// Add preview data to file:
					$content = $this->displayFileContentWithPreview($content,$relPathFix);
				} else {
						// Markup file:
					$content = $this->displayFileContentWithMarkup($content,$this->path,$relPathFix,$this->limitTags);
				}
					// Output content:
				echo $content;
			} else {
				$this->displayFrameError('No content found in file reference: <em>'.htmlspecialchars($this->displayFile).'</em>');
			}
		} else {
			$this->displayFrameError('No file to display');
		}
		
			// Exit since a full page has been outputted now.
		exit;
	}
	
	/**
	 * This will mark up the part of the HTML file which is pointed to by $path
	 * 
	 * @param	string		The file content as a string
	 * @param	string		The "HTML-path" to split by
	 * @param	string		The rel-path string to fix images/links with.
	 * @param	string		List of tags to show
	 * @return	void		Exits...
	 * @see main_display()
	 */
	function displayFileContentWithMarkup($content,$path,$relPathFix,$limitTags)	{
		$markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
		$markupObj->gnyfImgAdd = $this->show ? '' : 'onclick="return parent.updPath(\'###PATH###\');"';		
		$markupObj->pathPrefix = $path?$path.'|':'';
		$markupObj->onlyElements = $limitTags;
		
		$cParts = $markupObj->splitByPath($content,$path);
		if (is_array($cParts))	{
			$cParts[1] = $markupObj->markupHTMLcontent(
							$cParts[1],
							$GLOBALS['BACK_PATH'],
							$relPathFix,
							implode(',',array_keys($markupObj->tags)),
							$this->MOD_SETTINGS['displayMode']
						);
			$cParts[0] = $markupObj->passthroughHTMLcontent($cParts[0],$relPathFix,$this->MOD_SETTINGS['displayMode']);
			$cParts[2] = $markupObj->passthroughHTMLcontent($cParts[2],$relPathFix,$this->MOD_SETTINGS['displayMode']);
			if (trim($cParts[0]))	{
				$cParts[1]='<a name="_MARKED_UP_ELEMENT"></a>'.$cParts[1];
			}
			return implode('',$cParts);
		} else {
			$this->displayFrameError($cParts);
		}
	}
	
	/**
	 * This will add preview data to the HTML file used as a template according to the currentMappingInfo
	 * 
	 * @param	string		The file content as a string
	 * @param	string		The rel-path string to fix images/links with.
	 * @return	void		Exits...
	 * @see main_display()
	 */
	function displayFileContentWithPreview($content,$relPathFix)	{

			// Getting session data to get currentMapping info:
		$sesDat = $GLOBALS['BE_USER']->getSessionData($this->MCONF['name'].'_mappingInfo');
		$currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();

			// Init mark up object.
		$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
		$this->markupObj->htmlParse = t3lib_div::makeInstance('t3lib_parsehtml');

			// Splitting content, adding a random token for the part to be previewed:
		$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($content,$currentMappingInfo);
		$token = md5(microtime());
		$content = $this->markupObj->mergeSampleDataIntoTemplateStructure($sesDat['dataStruct'],$contentSplittedByMapping,$token);

			// Exploding by that token and traverse content:
		$pp = explode($token,$content);
		foreach($pp as $kk => $vv)	{
			$pp[$kk] = $this->markupObj->passthroughHTMLcontent($vv,$relPathFix,$this->MOD_SETTINGS['displayMode'],$kk==1?'font-size:11px; color:#000066;':'');
		}
		
			// Adding a anchor point (will work in most cases unless put into a table/tr tag etc).
		if (trim($pp[0]))	{
			$pp[1]='<a name="_MARKED_UP_ELEMENT"></a>'.$pp[1];
		}
		
			// Implode content and return it:
		return implode('',$pp);
	}
	
	/**
	 * Outputs a simple HTML page with an error message
	 * 
	 * @param	string		Error message for output in <h2> tags
	 * @return	void		Echos out an HTML page.
	 */
	function displayFrameError($error)	{
			echo '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title>Untitled</title>
</head>

<body bgcolor="#eeeeee">
<h2>ERROR: '.$error.'</h2>
</body>
</html>
			';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/cm1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/cm1/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('tx_templavoila_cm1');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
