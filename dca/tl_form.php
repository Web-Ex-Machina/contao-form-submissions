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
//$GLOBALS['TL_DCA']['tl_form']['config']['ctable'][] = 'tl_wem_form_submission';
//$GLOBALS['TL_DCA']['tl_form']['config']['ctable'][] = 'tl_wem_form_log';

/**
 * Add operations to tl_form
 */
$GLOBALS['TL_DCA']['tl_form']['list']['operations']['wem_submissions'] = array
(
	'label'               => &$GLOBALS['TL_LANG']['tl_form']['wem_submissions'],
	'href'                => 'table=tl_wem_form_submission',
	'icon'                => 'system/modules/wem-contao-form-submissions/assets/backend/icon_submissions_16.gif',
	'button_callback'	  => array('tl_wem_form', 'checkFormConfig'),
);
$GLOBALS['TL_DCA']['tl_form']['list']['operations']['wem_statistics'] = array
(
	'label'               => &$GLOBALS['TL_LANG']['tl_form']['wem_statistics'],
	'href'                => 'key=wemFormStatistics',
	'icon'                => 'system/modules/wem-contao-form-submissions/assets/backend/icon_statistics_16.png',
	'button_callback'	  => array('tl_wem_form', 'checkFormConfig'),
);

/**
 * Update tl_form palettes
 */
$GLOBALS['TL_DCA']['tl_form']['palettes']['__selector__'][] = 'wemStoreSubmissions';
$GLOBALS['TL_DCA']['tl_form']['palettes']['__selector__'][] = 'wemSubmissionMessages';
$GLOBALS['TL_DCA']['tl_form']['palettes']['default'] .= ';{wem_submission_legend},wemStoreSubmissions';

/**
 * Update tl_form subpalettes
 */
$GLOBALS['TL_DCA']['tl_form']['subpalettes']['wemStoreSubmissions'] = 'wemSubmissionTags,wemSubmissionSummaryNotification,wemSubmissionSummaryNotificationFrequency,wemSubmissionMessages';
$GLOBALS['TL_DCA']['tl_form']['subpalettes']['wemSubmissionMessages'] = 'wemSubmissionMessageNotification';

/**
 * Update tl_form fields
 */
$GLOBALS['TL_DCA']['tl_form']['fields']['wemStoreSubmissions'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_form']['wemStoreSubmissions'],
	'exclude'                 => true,
	'filter'                  => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('submitOnChange'=>true),
	'sql'                     => "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_form']['fields']['wemSubmissionTags'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_form']['wemSubmissionTags'],
	'exclude'                 => true,
	'inputType'               => 'listWizard',
	'eval'                    => array('allowHtml'=>true, 'tl_class'=>'clr'),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_form']['fields']['wemSubmissionSummaryNotification'] = array
(
    'label'                     => &$GLOBALS['TL_LANG']['tl_form']['wemSubmissionSummaryNotification'],
    'exclude'                   => true,
    'inputType'                 => 'select',
    'options_callback'          => array('tl_wem_form', 'getSummaryNotifications'),
    'eval'                      => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50 clr'),
    'sql'                       => "int(10) unsigned NOT NULL default '0'"
);
$GLOBALS['TL_DCA']['tl_form']['fields']['wemSubmissionSummaryNotificationFrequency'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_form']['wemSubmissionSummaryNotificationFrequency'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'				  => array('monthly', 'weekly', 'daily', 'hourly'),
	'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "varchar(16) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_form']['fields']['wemSubmissionMessages'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_form']['wemSubmissionMessages'],
	'exclude'                 => true,
	'filter'                  => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'clr'),
	'sql'                     => "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_form']['fields']['wemSubmissionMessageNotification'] = array
(
    'label'                     => &$GLOBALS['TL_LANG']['tl_form']['wemSubmissionMessageNotification'],
    'exclude'                   => true,
    'inputType'                 => 'select',
    'options_callback'          => array('tl_wem_form', 'getMessageNotifications'),
    'eval'                      => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'clr'),
    'sql'                       => "int(10) unsigned NOT NULL default '0'"
);

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
	public function __construct(){
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	/**
	 * Return the operation button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function checkFormConfig($row, $href, $label, $title, $icon, $attributes){
		if(!$row['wemStoreSubmissions'])
			return '';

		$href .= '&amp;id='.$row['id'];
		return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
	}

	/**
     * Get notification choices
     *
     * @return array
     */
    public function getSummaryNotifications()
    {
        $arrChoices = array();
        $objNotifications = \Database::getInstance()->execute("SELECT id,title FROM tl_nc_notification WHERE type='new_forms' ORDER BY title");

        while ($objNotifications->next()) {
            $arrChoices[$objNotifications->id] = $objNotifications->title;
        }

        return $arrChoices;
    }

    /**
     * Get notification choices
     *
     * @return array
     */
    public function getMessageNotifications()
    {
        $arrChoices = array();
        $objNotifications = \Database::getInstance()->execute("SELECT id,title FROM tl_nc_notification WHERE type='new_answer' ORDER BY title");

        while ($objNotifications->next()) {
            $arrChoices[$objNotifications->id] = $objNotifications->title;
        }

        return $arrChoices;
    }
}