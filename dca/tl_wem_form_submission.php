<?php

/**
 * Form Submissions Extension for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

/**
 * Table tl_wem_form_submission
 */
$GLOBALS['TL_DCA']['tl_wem_form_submission'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'enableVersioning'            => true,
		'ptable'                      => 'tl_form',
		//'ctable'					  => array('tl_wem_form_submission_field'),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('createdAt'),
			'panelLayout'             => 'filter;sorting,limit',
			'headerFields'            => array('title', 'tstamp', 'formID', 'storeValues', 'sendViaEmail', 'recipient', 'subject'),
			'child_record_callback'   => array('tl_wem_form_submission', 'listItems')
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_form_submission']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_form_submission']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_form_submission']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{general_legend},createdAt,status,tags,fields',
	),

	// Subpalettes
	'subpalettes' => array
	(

	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'foreignKey'              => 'tl_form.title',
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'token' => array
		(
			'eval'                    => array('unique'=>true, 'doNotCopy'=>true),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'createdAt' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_form_submission']['createdAt'],
			'default'                 => time(),
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 8,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'status' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_form_submission']['status'],
			'default'                 => 'created',
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'options'        		  => array('created', 'seen', 'answered', 'archived'),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_wem_form_submission']['status'],
			'eval'                    => array('chosen'=>true, 'mandatory'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default 'created'"
		),
		'tags' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_form_submission']['tags'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_wem_form_submission', 'getFormTags'),
			'eval'                    => array('chosen'=>true, 'multiple'=>true, 'tl_class'=>'clr'),
			'sql'                     => "blob NULL'"
		),

		'fields' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_wem_form_submission']['fields'],
		    'inputType'             => 'dcaWizard',
		    'foreignTable'          => 'tl_wem_form_submission_field',
		    'foreignField'          => 'pid',
		    'params'                  => array
		    (
		        'do'                  => 'form',
		    ),
		    'eval'                  => array
		    (
		        'fields' => array('field', 'value'),
		        'orderField' => 'tstamp',
		        'showOperations' => true,
		        'operations' => array('edit', 'delete'),
		        'tl_class'=>'clr',
		    ),
		),
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class tl_wem_form_submission extends Backend
{
	/**
	 * Import the back end user object
	 */
	public function __construct(){
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	public function listItems($row){
		return sprintf('Créé le %s | %s', date('d/m/Y à H:i', $row['createdAt']), $GLOBALS['TL_LANG']['tl_wem_form_submission']['status'][$row['status']]);
	}

	public function getFormTags($objDc){
		$objFormSubmission = $this->Database->prepare("SELECT pid FROM tl_wem_form_submission WHERE id = ?")->execute($objDc->id);
		$objForm = $this->Database->prepare("SELECT wemSubmissionTags FROM tl_form WHERE id = ?")->execute($objFormSubmission->pid);
		return deserialize($objForm->wemSubmissionTags);
	}
}