<?php

/**
 * Form Submissions Extension for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

/**
 * Add the new table in form backend module
 */
$GLOBALS['BE_MOD']['content']['form']['tables'][] = 'tl_wem_form_submission';
$GLOBALS['BE_MOD']['content']['form']['tables'][] = 'tl_wem_form_submission_field';
$GLOBALS['BE_MOD']['content']['form']['tables'][] = 'tl_wem_form_submission_log';
$GLOBALS['BE_MOD']['content']['form']['tables'][] = 'tl_wem_form_submission_answer';
$GLOBALS['BE_MOD']['content']['form']['wemFormStatistics'] = array('WEM\Form\Backend\Callback', 'displayStatistics');

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['getPageLayout'][] = array('WEM\Form\Hooks', 'catchAjaxRequest');
$GLOBALS['TL_HOOKS']['getPageIdFromUrl'][] = array('WEM\Form\Hooks', 'generateConversationView');
$GLOBALS['TL_HOOKS']['compileFormFields'][] = array('WEM\Form\Hooks', 'addHiddenFields');
$GLOBALS['TL_HOOKS']['prepareFormData'][] = array('WEM\Form\Hooks', 'storeFormLogs');
$GLOBALS['TL_HOOKS']['executePostActions'][] = array('WEM\Form\Backend\Callback', 'exportPDF');

/**
 * Models
 */
$GLOBALS['TL_MODELS'][\WEM\Form\Model\Submission::getTable()] = 'WEM\Form\Model\Submission';
$GLOBALS['TL_MODELS'][\WEM\Form\Model\Field::getTable()] = 'WEM\Form\Model\Field';
$GLOBALS['TL_MODELS'][\WEM\Form\Model\Log::getTable()] = 'WEM\Form\Model\Log';
$GLOBALS['TL_MODELS'][\WEM\Form\Model\Answer::getTable()] = 'WEM\Form\Model\Answer';

/**
 * Notifications
 */
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['wem_form_submission']['new_forms'] = array(
	'recipients'			=> array('admin_email'),
	'email_subject'			=> array('form_*'),
	'email_text'			=> array('form_*', 'nbSubmissions'),
	'email_html'			=> array('form_*', 'nbSubmissions'),
	'email_sender_name'		=> array('websiteTitle', 'admin_email', 'form_*'),
	'email_sender_address'	=> array('admin_email', 'form_*'),
	'email_recipient_cc'	=> array('admin_email', 'form_*'),
	'email_recipient_bcc'	=> array('admin_email', 'form_*'),
);
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['wem_form_submission']['new_answer'] = array(
	'recipients'			=> array('email'),
	'email_subject'			=> array('form_*'),
	'email_text'			=> array('form_*', 'conversation_*', 'answer_*', 'sender_*'),
	'email_html'			=> array('form_*', 'conversation_*', 'answer_*', 'sender_*'),
	'email_sender_name'		=> array('websiteTitle', 'sender_*', 'form_*'),
	'email_sender_address'	=> array('sender_*', 'form_*'),
	'email_recipient_cc'	=> array('sender_*', 'form_*'),
	'email_recipient_bcc'	=> array('sender_*', 'form_*'),
);
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['wem_form_submission']['archived_conversation'] = array(
	'recipients'			=> array('email'),
	'email_subject'			=> array('form_*'),
	'email_text'			=> array('form_*', 'conversation_*', 'sender_*'),
	'email_html'			=> array('form_*', 'conversation_*', 'sender_*'),
	'email_sender_name'		=> array('websiteTitle', 'sender_*', 'form_*'),
	'email_sender_address'	=> array('sender_*', 'form_*'),
	'email_recipient_cc'	=> array('sender_*', 'form_*'),
	'email_recipient_bcc'	=> array('sender_*', 'form_*'),
);

/**
 * Setup the CRON Jobs
 */
\WEM\Form\Core::setupCronJobs();