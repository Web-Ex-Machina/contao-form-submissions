<?php

/**
 * Form Submissions Extension for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

/**
 * Add ctable to tl_form
 */
$GLOBALS['TL_DCA']['tl_form']['config']['ctable'][] = 'tl_wem_form_submission';

/**
 * Add operations to tl_form
 */
$GLOBALS['TL_DCA']['tl_form']['list']['operations']['wem_submissions'] = array
(
	'label'               => &$GLOBALS['TL_LANG']['tl_form']['wem_submissions'],
	'href'                => 'table=tl_wem_form_submission',
	'icon'                => 'system/modules/wem-contao-form-submissions/assets/backend/icon_submissions_16.png'
);
$GLOBALS['TL_DCA']['tl_form']['list']['operations']['wem_statistics'] = array
(
	'label'               => &$GLOBALS['TL_LANG']['tl_form']['wem_statistics'],
	'href'                => 'key=wemFormStatistics',
	'icon'                => 'system/modules/wem-contao-form-submissions/assets/backend/icon_statistics_16.png'
);

/**
 * Update tl_form palettes
 */

/**
 * Update tl_form fields
 */

/**
 * Extends miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class tl_wem_form extends tl_form
{
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}
}