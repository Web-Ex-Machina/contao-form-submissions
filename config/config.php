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

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['getPageLayout'][] = array('WEM\Form\Hooks', 'catchAjaxRequest');
$GLOBALS['TL_HOOKS']['compileFormFields'][] = array('WEM\Form\Hooks', 'addHiddenFields');
$GLOBALS['TL_HOOKS']['prepareFormData'][] = array('WEM\Form\Hooks', 'storeFormLogs');

/**
 * Models
 */
$GLOBALS['TL_MODELS'][\WEM\Form\Model\Submission::getTable()] = 'WEM\Form\Model\Submission';
$GLOBALS['TL_MODELS'][\WEM\Form\Model\Field::getTable()] = 'WEM\Form\Model\Field';
$GLOBALS['TL_MODELS'][\WEM\Form\Model\Log::getTable()] = 'WEM\Form\Model\Log';