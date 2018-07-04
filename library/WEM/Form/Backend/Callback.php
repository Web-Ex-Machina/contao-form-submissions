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
use Contao\BackendTemplate;
use Contao\FormModel;

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

			// Get all the submissions
			$objSubmissions = Submission::findItems(["pid"=>$objForm->id]);

			// Break if there is no submissions
			if(null == $objSubmissions || 0 == $objSubmissions->count())
				throw new Exception(sprintf("No submissions yet for the form %s", $objForm->id));

			$arrTmp = [];
			while($objSubmissions->next()){
				
				if(!array_key_exists($objSubmissions->status, $arrTmp))
					$arrTmp[$objSubmissions->status] = [];
				
				$strDate = date('Y m', $objSubmissions->createdAt);
				if(!array_key_exists($strDate, $arrTmp[$objSubmissions->status]))
					$arrTmp[$objSubmissions->status][$strDate] = 0;

				$arrTmp[$objSubmissions->status][$strDate]++;
			}

			\System::loadLanguageFile('tl_wem_form_submission');

			// Organize data by status
			$arrDatasets = [];
			$arrLabels = [];
			
			// Format datasets
			foreach($arrTmp as $strStatus => $arrMonths){
				$arrDataset = [
					"label" => $GLOBALS['TL_LANG']['tl_wem_form_submission']['status'][$strStatus]
					,"data" => []
					,"backgroundColor" => static::$arrStatusColors[$strStatus]
				];
				
				foreach($arrMonths as $strMonth => $nbItems){
					if(!in_array($strMonth, $arrLabels))
						$arrLabels[] = $strMonth;

					$arrDataset["data"][] = $nbItems;
				}

				$arrDatasets[] = $arrDataset;
			}

			// Reformat labels depending on the current language
			foreach($arrLabels as $intKey => $strLabel){
				$objDate = new \Date($strLabel, "Y m");
				$arrLabels[$intKey] = date("m/Y", $objDate->timestamp);
			}

			// Send data to template
			$objTemplate->labels = json_encode($arrLabels);
			$objTemplate->datasets = json_encode($arrDatasets);
		}
		catch(Exception $e){
			$objTemplate->isError = true;
			\Message::addError(sprintf("Error found : %s", $e->getMessage()));
		}

		return $objTemplate->parse();
	}
}