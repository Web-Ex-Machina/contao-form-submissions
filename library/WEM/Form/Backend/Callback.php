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
		"default"=>"rgba(200,200,200,1.0)"
		,"created"=>"rgba(46,204,113,1.0)"
		,"seen"=>"rgba(52,152,219,1.0)"
		,"answered"=>"rgba(155,89,182,1.0)"
		,"archived"=>"rgba(52,73,94,1.0)"
		,"aborted"=>"rgba(231,76,60,1.0)"
	);

	/**
	 * Generate and display form statistics
	 * @param  [Object] $objDc [Datacontainer]
	 * @return [String]        [HTML string]
	 */
	public function displayStatistics($objDc){
		$objTemplate = new BackendTemplate('mod_wem_form_submissions_statistics');

		try{
			if (Input::get('key') != 'wemFormStatistics')
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

			// Get form fields
			$objFields = \FormFieldModel::findByPid($objForm->id, ["order"=>"sorting ASC"]);
			$arrFields = [];
			$arrFieldsNames = [];
			while($objFields->next()){
				$arrFields[$objFields->id] = $objFields->row();

				if($objFields->name && !in_array($objFields->name, $arrFieldsNames))
					$arrFieldsNames[] = $objFields->name;
			}
			
			// Load language files
			\System::loadLanguageFile('tl_wem_form_submission');

			// Organize data by status
			$arrStatus = [];
			$arrMonths = [];
			$arrChart1 = [0=>["label"=>"Formulaires", "data"=>[],"backgroundColor"=>$this->getColor(2, 0.3),"hoverBackgroundColor"=>$this->getColor(2, 0.4),"borderColor"=>$this->getColor(2, 0.5)]];
			$arrChart2 = [0=>["data"=>[],"backgroundColor"=>[]]];
			$arrChart3 = [0=>["label"=>"Pourcentages", "data"=>[],"backgroundColor"=>$this->getColor(4, 0.3),"hoverBackgroundColor"=>$this->getColor(4, 0.4),"borderColor"=>$this->getColor(4, 0.5)]];

			while($objSubmissions->next()){
				if(!in_array($GLOBALS['TL_LANG']['tl_wem_form_submission']['status'][$objSubmissions->status], $arrStatus))
					$arrStatus[] = $GLOBALS['TL_LANG']['tl_wem_form_submission']['status'][$objSubmissions->status];

				$strDate = date('m/Y', $objSubmissions->createdAt);
				if(!in_array($strDate, $arrMonths))
					$arrMonths[] = $strDate;

				$k1 = array_search($GLOBALS['TL_LANG']['tl_wem_form_submission']['status'][$objSubmissions->status], $arrStatus);
				$k2 = array_search($strDate, $arrMonths);

				if(!array_key_exists($k2, $arrChart1[0]['data'])){
					$arrChart1[0]['data'][$k2] = 0;
				}
				$arrChart1[0]['data'][$k2]++;

				if(!array_key_exists($k1, $arrChart2[0]['data'])){
					$arrChart2[0]['data'][$k1] = 0;
					$arrChart2[0]['backgroundColor'][$k1] = static::$arrStatusColors[$objSubmissions->status];
				}
				$arrChart2[0]['data'][$k1]++;

				// Get the submissions fields
				$objFields = Field::findBy('pid', $objSubmissions->id);
				if($objFields){
					while($objFields->next()){
						$k3 = array_search($arrFields[$objFields->field]['name'], $arrFieldsNames);
						if(!$k3)
							$k3 = 0;
						if(!array_key_exists($k3, $arrChart3[0]['data']))
							$arrChart3[0]['data'][$k3] = 0;
						if($objFields->value)
							$arrChart3[0]['data'][$k3]++;
					}
				}
			}

			// Adjust chart 3 values
			if(!empty($arrChart3[0]['data'])){
				foreach($arrChart3[0]['data'] as &$value){
					$value = number_format($value / $objSubmissions->count() * 100, 2);
				}
			}
			$arrChart3[0]['data'] = array_values($arrChart3[0]['data']);

			// Send data to template
			$objTemplate->chart1_datasets = json_encode($arrChart1);
			$objTemplate->chart2_datasets = json_encode($arrChart2);
			$objTemplate->chart3_datasets = json_encode($arrChart3);

			$objTemplate->status = json_encode($arrStatus);
			$objTemplate->months = json_encode($arrMonths);
			$objTemplate->fields = json_encode($arrFieldsNames);

			$objTemplate->start = $intStart;
			$objTemplate->stop = $intStop;
		}
		catch(Exception $e){
			$objTemplate->isError = true;
			\Message::addError(sprintf("Error found : %s", $e->getMessage()));
		}

		// Add WEM styles to template
		$GLOBALS['TL_CSS'][] = 'system/modules/wem-contao-form-submissions/assets/backend/style.css';

		return $objTemplate->parse();
	}

	public function exportPDF($strAction, $objDc){
		if($strAction == 'exportPDF'){
			try{
				// Break if we don't find the basics data
				if(!$objDc || !$objDc->id || !$objForm = FormModel::findByPk($objDc->id))
					throw new Exception("No data found for the form");

				$intStart = 0;
				$intStop = 0;
				if(Input::post('start'))
					$intStart = Input::post('start');
				if(Input::post('stop'))
					$intStop = Input::post('stop');
				
				$img = Input::post('picture');
				$img = str_replace('data:image/png;base64,', '', $img);
				$img = str_replace(' ', '+', $img);
				$imgdata = base64_decode($img);

				$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
				$pdf->SetCreator("Web ex Machina");
				$pdf->SetAuthor('Web ex Machina');
				$pdf->SetTitle('Export des statistiques des formulaires - '.date('d/m/Y à H:i'));
				$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
				$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);

				$pdf->AddPage();
				$pdf->setJPEGQuality(100);

				$html = sprintf('<h1>Statistiques du formulaire %s</h1>', $objForm->title);
				$html.= '<p>Date de l\'export : '.date('d/m/Y à H:i');
				if($intStart > 0 && $intStop > 0)
					$html.= '<br />Filtres : Du '.date('d/m/Y', $intStart).' au '.date('d/m/Y', $intStop);
				else if($intStart > 0)
					$html.= '<br />Filtre : A partir du '.date('d/m/Y', $intStart);
				else if($intStop > 0)
					$html.= '<br />Filtre : Jusqu\'au '.date('d/m/Y', $intStop);
				$html.='</p><br />';

				$pdf->writeHTML($html, true, false, true, false, '');

				$pdf->Image('@'.$imgdata, '', '', 0, 0, '', '', '', true, 300, '', false, false, 0, true, false, true);

				$strFilename = date('Y-m-d_H-i-s').'_form-statistics.pdf';
				$pdf->Output(__DIR__ . '/../../../../assets/tmp/'.$strFilename, 'F');

				$arrResponse = ["status"=>"success","filename"=>\Environment::get('base')."system/modules/wem-contao-form-submissions/assets/tmp/".$strFilename];
			}
			catch(Exception $e){
				$arrResponse = ["status"=>"error","msg"=>$e->getMessage()];
			}

			echo json_encode($arrResponse); die;
		}
	}

	protected function getColor($strKey, $opacity = false){

		$arrColors = ["#1abc9c","#16a085","#2ecc71","#27ae60","#3498db","#2980b9","#9b59b6","#8e44ad","#34495e","#2c3e50","#f1c40f","#f39c12","#e67e22","#d35400","#e74c3c","#c0392b","#95a5a6","#7f8c8d","#f6b93b","#4a69bd","#3c6382","#079992","#b71540","#b8e994","#78e08f","#3498db"];

        return $this->hex2rgba($arrColors[$strKey], $opacity);
	}

	protected function hex2rgba($color, $opacity = false){
	 
		$default = 'rgb(0,0,0)';
	 
		//Return default if no color provided
		if(empty($color))
	          return $default; 
	 
		//Sanitize $color if "#" is provided 
        if ($color[0] == '#' ) {
        	$color = substr( $color, 1 );
        }
 
        //Check if color has 6 or 3 characters and get values
        if (strlen($color) == 6) {
                $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
        } elseif ( strlen( $color ) == 3 ) {
                $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
        } else {
                return $default;
        }
 
        //Convert hexadec to rgb
        $rgb =  array_map('hexdec', $hex);
 
        //Check if opacity is set(rgba or rgb)
        if($opacity){
        	if(abs($opacity) > 1)
        		$opacity = 1.0;
        	$output = 'rgba('.implode(",",$rgb).','.$opacity.')';
        } else {
        	$output = 'rgb('.implode(",",$rgb).')';
        }
 
        //Return rgb(a) color string
        return $output;
	} 
}