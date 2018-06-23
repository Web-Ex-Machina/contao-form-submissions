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
}