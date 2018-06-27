<?php

/**
 * Form Submissions Extension for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\Form;

use Exception;
use Contao\Input;
use Contao\Controller;
use Contao\RequestToken;

use WEM\Form\Model\Submission;
use WEM\Form\Model\Field;
use WEM\Form\Model\Log;

/**
 * Hooks functions
 */
class Hooks extends Controller
{
	/**
	 * Catch AJAX Requests
	 * @param  [Object] $objPage   [Page Model]
	 * @param  [Object] $objLayout [Layout Model]
	 * @param  [Object] $objPty    [Page Type Model]
	 */
	public function catchAjaxRequest($objPage, $objLayout, $objPty){
		if(Input::post("TL_AJAX") && "wem-contao-form-submissions" === Input::post("module")){
			try{
				if(null == Input::post("form"))
					throw new Exception("Aucun formulaire envoyé");

				$objForm = \FormModel::findByPk(Input::post("form"));
				if(!$objForm)
					throw new Exception("Formulaire invalide");

				switch(Input::post("action")){
					case "checkForm":
						if(!$objForm->wemStoreSubmissions)
							$arrResponse = ["status"=>"success", "tobuild"=>0];
						else
							$arrResponse = ["status"=>"success", "tobuild"=>1];
					break;
					
					case "submit":
						// Break the Ajax, since we do not use it anymore. But it costs nothing to keep it.
						throw new Exception("Action interdite");
						
						if(!Input::post("fields") || empty(Input::post("fields")))
							throw new Exception("Pas de champs envoyés");

						$objSubmission = new Submission();
						$objSubmission->tstamp = time();
						$objSubmission->pid = $objForm->id;
						$objSubmission->createdAt = time();
						$objSubmission->status = "created";
						$objSubmission->token = md5(uniqid(mt_rand(), true));

						if(!$objSubmission->save())
							throw new Exception("Une erreur est survenue lors de la validation du formulaire");

						foreach(Input::post("fields") as $field => $value){
							$objField = new Field();
							$objField->tstamp = time();
							$objField->pid = intval($objSubmission->id);
							$objField->field = $field;
							$objField->value = $value;
							$objField->save();
						}

						$arrResponse = ["status"=>"success", "msg"=>"Soumission OK", "submission"=>$objSubmission->id];
					break;
					
					case "log":
						// Break the Ajax, since we do not use it anymore. But it costs nothing to keep it.
						throw new Exception("Action interdite");
						
						if(!Input::post("submission"))
							throw new Exception("Pas de soumission envoyé");

						$objSubmission = Submission::findByPk(Input::post("submission"));
						if(!$objForm)
							throw new Exception("Soumission invalide");

						if(!Input::post("logs") || empty(Input::post("logs")))
							throw new Exception("Pas de logs envoyés");

						$i = 0;
						foreach(Input::post("logs") as $log){
							$objLog = new Log();
							$objLog->tstamp = time();
							$objLog->pid = intval($objSubmission->id);
							$objLog->createdAt = $log["createdAt"];
							$objLog->type = $log["type"];
							$objLog->log = $log["log"];
							
							if($objLog->save())
								$i++;
						}

						$arrResponse = ["status"=>"success", "msg"=>$i." logs ont été créés"];
					break;

					default:
						throw new Exception("Action inconnue");
				}
			}
			catch(Exception $e){
				$arrResponse = ["status"=>"error", "msg"=>$e->getMessage(), "method"=>__METHOD__];
			}

			// Send the answer
			$arrResponse["rt"] = RequestToken::get();
			echo json_encode($arrResponse);
			die;
		}

		// Load JS library
		$GLOBALS["TL_JAVASCRIPT"][] = 'system/modules/wem-contao-form-submissions/assets/js/functions.js';
	}

	/**
	 * Add one hidden input to the form for storage purpose
	 * @param  [Array] $arrFields [Original Form Fields]
	 * @param  [Integer] $intFormId [Form ID]
	 * @param  [Object] $objForm   [Form Row]
	 * @return [Array]            [Updated, or not Form Fields]
	 */
	public function addHiddenFields($arrFields, $intFormId, $objForm){
	    // Break if we don't have the option that interest us
	    if(!$objForm->wemStoreSubmissions)
	    	return $arrFields;

	    $objFormField = new \FormFieldModel();
	    $objFormField->pid = $intFormId;
	    $objFormField->tstamp = time();
	    $objFormField->sorting = 128128;
	    $objFormField->type = "hidden";
	    $objFormField->name = "wem_tracker_logs";

	    $arrFields[] = $objFormField;

	    return $arrFields;
	}

	/**
	 * Check and store the data from form submission
	 * @param  [Array] &$arrSubmitted [Values posted]
	 * @param  [Array] $arrLabels     [Form labels]
	 * @param  [Array] $arrFields     [Form fields]
	 * @param  [Object] $objForm       [Form object]
	 * @return [Array]                [Form fields updated, or not]
	 */
	public function storeFormLogs(&$arrSubmitted, $arrLabels, $arrFields, $objForm){
	    // Break if we don't have the option that interest us
	    if(!$objForm->wemStoreSubmissions)
	    	return;

	    // Create the submission first
	    $objSubmission = new Submission();
		$objSubmission->tstamp = time();
		$objSubmission->pid = $objForm->id;
		$objSubmission->createdAt = time();
		$objSubmission->status = "created";
		$objSubmission->token = md5(uniqid(mt_rand(), true));

		if(!$objSubmission->save())
			throw new Exception("Une erreur est survenue lors de la validation du formulaire");

		// Then, store the fields entered
		if($arrSubmitted){
			foreach($arrSubmitted as $field => $value){
				// Skip the "fake" field
				if("wem_tracker_logs" === $field)
					continue;

				$objField = new Field();
				$objField->tstamp = time();
				$objField->pid = intval($objSubmission->id);
				$objField->field = intval($arrFields[$field]->id);
				$objField->value = $value;
				$objField->save();
			}
		}

		// And finally, store the logs sent
		if($arrSubmitted["wem_tracker_logs"]){
			foreach(json_decode($arrSubmitted["wem_tracker_logs"]) as $log){
				$log = (array)$log;
				$objLog = new Log();
				$objLog->tstamp = time();
				$objLog->pid = intval($objSubmission->id);
				$objLog->createdAt = $log["createdAt"];
				$objLog->type = $log["type"];
				$objLog->log = $log["log"];
				$objLog->save();
			}
		}
	}
}