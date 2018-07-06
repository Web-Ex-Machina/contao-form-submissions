<?php

/**
 * Form Submissions Extension for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\Form\Backend;

use Exception;
use Contao\Backend;
use Contao\Date;
use Contao\BackendTemplate;
use Contao\FormModel;
use Haste\Input\Input;

use WEM\Form\Model\Submission;
use WEM\Form\Model\Field;
use WEM\Form\Model\Log;

/**
 * Backend functions
 */
class Callback extends Backend
{
	/**
	 * Status Colors
	 * @var array
	 */
	protected static $arrStatusColors = array(
		"created" => "rgba(46, 204, 113,1.0)"
		,"seen" => "rgba(52, 152, 219,1.0)"
		,"answered" => "rgba(155, 89, 182,1.0)"
		,"archived" => "rgba(52, 73, 94,1.0)"
		,"aborted" => "rgba(231, 76, 60,1.0)"
	);

	/**
	 * Generate and display form statistics
	 * @param  [Object] $objDc [Datacontainer]
	 * @return [String]        [HTML string]
	 */
	public function displayStatistics($objDc){
		$objTemplate = new BackendTemplate('mod_wem_form_submissions_statistics');

		try{
			if (\Input::get('key') != 'wemFormStatistics')
				throw new Exception("Wrong key");

			// Break if we don't find the basics data
			if(!$objDc || !$objDc->id || !$objForm = FormModel::findByPk($objDc->id))
				throw new Exception("No data found for the form");

			// Adjust the config if there is filters
			$intStart = 0;
			$intStop = 0;

			if(Input::post('ctrl_start')){
				$objDate = new Date(Input::post('ctrl_start'), 'd/m/Y');
				$intStart = $objDate->timestamp;
				$objTemplate->ctrl_start = Input::post('ctrl_start');
			}

			if(Input::post('ctrl_stop')){
				$objDate = new Date(Input::post('ctrl_stop'), 'd/m/Y');
				$intStop = $objDate->timestamp;
				$objTemplate->ctrl_stop = Input::post('ctrl_stop');
			}

			// Get all the submissions
			$objSubmissions = Submission::findItems(["pid"=>$objForm->id, "createdAt_start"=>$intStart, "createdAt_stop"=>$intStop]);

			// Break if there is no submissions
			if(null == $objSubmissions || 0 == $objSubmissions->count())
				throw new Exception(sprintf("No submissions yet for the form %s", $objForm->id));
			
			// Load language files
			\System::loadLanguageFile('tl_wem_form_submission');

			// Organize data by status
			$arrStatus = [];
			$arrMonths = [];
			$arrChart1 = ['datasets'=>[]];
			$arrChart2 = ['datasets'=>[0=>["data"=>[],"backgroundColor"=>[]]]];

			while($objSubmissions->next()){
				if(!in_array($GLOBALS['TL_LANG']['tl_wem_form_submission']['status'][$objSubmissions->status], $arrStatus))
					$arrStatus[] = $GLOBALS['TL_LANG']['tl_wem_form_submission']['status'][$objSubmissions->status];

				$strDate = date('m/Y', $objSubmissions->createdAt);
				if(!in_array($strDate, $arrMonths))
					$arrMonths[] = $strDate;

				$k1 = array_search($GLOBALS['TL_LANG']['tl_wem_form_submission']['status'][$objSubmissions->status], $arrStatus);
				$k2 = array_search($strDate, $arrMonths);

				if(!array_key_exists($k1, $arrChart1['datasets'])){
					$arrChart1['datasets'][$k1] = [
						"label" => $arrStatus[$k1]
						,"data" => []
						,"backgroundColor" => static::$arrStatusColors[$objSubmissions->status]
					];
				}

				if(!array_key_exists($k2, $arrChart1['datasets'][$k1]["data"]))
					$arrChart1['datasets'][$k1]["data"][$k2] = 0;
				$arrChart1['datasets'][$k1]["data"][$k2]++;

				if(!array_key_exists($k1, $arrChart2['datasets'][0]['data'])){
					$arrChart2['datasets'][0]['data'][$k1] = 0;
					$arrChart2['datasets'][0]['backgroundColor'][$k1] = static::$arrStatusColors[$objSubmissions->status];
				}
				$arrChart2['datasets'][0]['data'][$k1]++;
			}

			// Send data to template
			$objTemplate->chart1_datasets = json_encode($arrChart1['datasets']);
			$objTemplate->chart2_datasets = json_encode($arrChart2['datasets']);

			$objTemplate->status = json_encode($arrStatus);
			$objTemplate->months = json_encode($arrMonths);
		}
		catch(Exception $e){
			$objTemplate->isError = true;
			\Message::addError(sprintf("Error found : %s", $e->getMessage()));
		}

		// Add WEM styles to template
		$GLOBALS['TL_CSS'][] = 'system/modules/wem-contao-form-submissions/assets/backend/style.css';

		return $objTemplate->parse();
	}
}