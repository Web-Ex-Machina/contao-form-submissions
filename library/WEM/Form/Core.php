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
use Contao\FormModel;

use NotificationCenter\Model\Notification;

use WEM\Form\Model\Submission;
use WEM\Form\Model\Field;
use WEM\Form\Model\Log;
use WEM\Form\Model\Answer;

/**
 * Core functions
 */
class Core extends Controller
{
	/**
	 * Send a notification for an answer
	 * @param  [Integer] $intAnswer [Answer ID]
	 * @return [Boolean]            [True if the notification has been sent]
	 */
	public static function sendNotification($intAnswer){
		try{
			$objAnswer = Answer::findByPk($intAnswer);

			if($objAnswer->notificationSent > 0)
				return false;

			$objSubmission = Submission::findByPk($objAnswer->pid);
			$objForm = FormModel::findByPk($objSubmission->pid);

			// Check if we have to send the "new conversation" or the "new answer" notifications
			if(Answer::countItems(["pid"=>$objSubmission->id]) < 3)
				$objNotification = Notification::findByPk($objForm->wemSubmissionNewConversationNotification);
			else
				$objNotification = Notification::findByPk($objForm->wemSubmissionNewMessageNotification);

			if(!$objNotification)
				return false;

			// Fallback
			if(!$objSubmission->token){
				$objSubmission->token = md5(uniqid(mt_rand(), true));
				$objSubmission->save();
			}

			$arrTokens = static::formatAnswerTokens($objAnswer, $objSubmission, $objForm);

			if(!$objNotification->send($arrTokens, $GLOBALS['TL_LANGUAGE']))
				return false;

			$objAnswer->notificationSent = time();
			$objAnswer->save();

			return true;
		}
		catch(Exception $e){
			throw $e;
		}
	}

	/**
	 * Format Notification Tokens
	 * @param  [Object] $objAnswer  	[Answer Model]
	 * @param  [Object] $objSubmission  [Submission Model]
	 * @param  [Object] $objForm  		[Form Model]
	 * @return [Array]              	[Tokens]
	 */
	public static function formatAnswerTokens($objAnswer, $objSubmission, $objForm){
		try{
			$arrTokens = array();

			$arrTokens['sender_name'] = $objAnswer->sender_name;
			$arrTokens['sender_email'] = $objAnswer->sender_email;

			$arrTokens['recipient_name'] = $objAnswer->recipient_name;
			$arrTokens['recipient_email'] = $objAnswer->recipient_email;
			$arrTokens['email'] = $objAnswer->recipient_email;

			$arrTokens['answer_timestamp'] = $objAnswer->createdAt;
			$arrTokens['answer_date'] = date('d/m/Y à H:i', $objAnswer->createdAt);
			$arrTokens['answer_message'] = $objAnswer->message;

			$objAnswers = Answer::findItems(['pid'=>$objSubmission->id], 0, 0, ['order'=>'createdAt ASC']);
			$objFirstAnswer = $objAnswers->first();

			$arrTokens['conversation_timestamp'] = $objFirstAnswer->createdAt;
			$arrTokens['conversation_date'] = date('d/m/Y à H:i', $objFirstAnswer->createdAt);
			$arrTokens['conversation_nbMessages'] = $objAnswers->count();
			$arrTokens['conversation_link'] = \Environment::get('base').'wem-form-conversation/'.$objSubmission->token.'.html?from='.$objAnswer->recipient_email;

			foreach($objForm->row() as $k=>$v)
				$arrTokens['form_'.$k] = $v;

			return $arrTokens;
		}
		catch(Exception $e){
			throw $e;
		}
	}

	/**
	 * Correctly setup the Cron Jobs we need
	 */
	public static function setupCronJobs(){
		if(\FormModel::countBy('wemSubmissionSummaryNotificationFrequency', 'hourly') > 0)
			$GLOBALS['TL_CRON']['hourly'][]  = array('WEM\Form\Core', 'sendHourlySummary');
		if(\FormModel::countBy('wemSubmissionSummaryNotificationFrequency', 'daily') > 0)
			$GLOBALS['TL_CRON']['daily'][]   = array('WEM\Form\Core', 'sendDailySummary');
		if(\FormModel::countBy('wemSubmissionSummaryNotificationFrequency', 'weekly') > 0)
			$GLOBALS['TL_CRON']['weekly'][]  = array('WEM\Form\Core', 'sendWeeklySummary');
		if(\FormModel::countBy('wemSubmissionSummaryNotificationFrequency', 'monthly') > 0)
			$GLOBALS['TL_CRON']['monthly'][] = array('WEM\Form\Core', 'sendMonthlySummary');
	}

	/**
	 * Find and send hourly submissions
	 */
	public function sendHourlySummary(){
		$this->sendSummary('hourly');
	}

	/**
	 * Find and send daily submissions
	 */
	public function sendDailySummary(){
		$this->sendSummary('daily');
	}

	/**
	 * Find and send weekly submissions
	 */
	public function sendWeeklySummary(){
		$this->sendSummary('weekly');
	}

	/**
	 * Find and send monthly submissions
	 */
	public function sendMonthlySummary(){
		$this->sendSummary('monthly');
	}

	/**
	 * Generic function to send summaries
	 * @param  [String] $strMode [Cron mode]
	 */
	public function sendSummary($strMode){
		try{
			$objForms = $this->Database->prepare("SELECT * FROM tl_form WHERE wemSubmissionSummaryNotification != '' AND wemSubmissionSummaryNotificationFrequency = '?'")->execute($strMode);
			
			if(!$objForms || 0 === $objForms->count())
				return;

			while($objForms->next()){
				$intNewSubmissions = Submission::countItems(["pid"=>$objForms->id, "status"=>"created"]);

				if(0 === $intNewSubmissions)
					continue;

				if($objNotification = Notification::findByPk($objForm->wemSubmissionSummaryNotification)){
					$arrTokens = array();
					$arrTokens['websiteTitle'] = \Config::get('websiteTitle');
					$arrTokens['admin_email'] = \Config::get('adminEmail');
					foreach($objForm->row() as $k=>$v)
						$arrTokens['form_'.$k] = $v;
					$arrTokens["nbSubmissions"] = $intNewSubmissions;
					$objNotification->send($arrTokens);

					\System::log(sprintf("Notification (%s) sent for the form %s", $strMode, $objForm->title), __METHOD__, "TL_CRON");
				}
			}
		}
		catch(Exception $e){
			\System::log("Cronjob error : ".$e->getMessage(), __METHOD__, "TL_CRON");
		}
	}
}