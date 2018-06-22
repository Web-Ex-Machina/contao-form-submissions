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
$GLOBALS['BE_MOD']['content']['form']['tables'][] = 'tl_wem_form_log';
$GLOBALS['BE_MOD']['content']['form']['tables'][] = 'tl_wem_form_submission';
$GLOBALS['BE_MOD']['content']['form']['tables'][] = 'tl_wem_form_submission_field';
$GLOBALS['BE_MOD']['content']['form']['tables'][] = 'tl_wem_form_submission_log';

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['getForm'][] = array('WEM\Form\Hooks', 'loadAssets');