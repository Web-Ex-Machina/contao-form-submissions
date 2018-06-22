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
$GLOBALS['TL_HOOKS']['compileFormFields'][] = array('WEM\Form\Hooks', 'loadAssets');
$GLOBALS['TL_HOOKS']['getPageLayout'][] = array('WEM\Form\Hooks', 'catchAjaxRequest');