<?php

/**
 * Form Submissions Extension for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

/**
 * Table tl_wem_form_submission_answer
 */
$GLOBALS['TL_DCA']['tl_wem_form_submission_answer'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'enableVersioning'            => true,
		'ptable'                      => 'tl_wem_form_submission',
		'onsubmit_callback'			  => array
		(
			array('tl_wem_form_submission_answer', 'sendNotification'),
		),
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
			'panelLayout'             => 'filter;search,limit',
			'headerFields'            => array('createdAt', 'status'),
			'child_record_callback'   => array('tl_wem_form_submission_answer', 'listItems')
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
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_form_submission_answer']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_form_submission_answer']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_form_submission_answer']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{general_legend},createdAt,sender_name,sender_email,recipient_name,recipient_email,message',
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
			'foreignKey'              => 'tl_wem_form_submission.id',
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'notificationSent' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

		'createdAt' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_form_submission_answer']['createdAt'],
			'default'                 => time(),
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 8,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'sender_name' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_form_submission_answer']['sender_name'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'clr w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'sender_email' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_form_submission_answer']['sender_email'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'rgxp'=>'email', 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'recipient_name' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_form_submission_answer']['recipient_name'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'clr w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'recipient_email' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_form_submission_answer']['recipient_email'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'rgxp'=>'email', 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'message' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_form_submission_answer']['message'],
			'exclude'                 => true,
			'search'				  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>true, 'tl_class'=>'clr', 'doNotCopy'=>true),
			'sql'                     => "text NULL"
		),
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class tl_wem_form_submission_answer extends Backend
{
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	public function sendNotification($dc){
		if(\WEM\Form\Core::sendNotification($dc->id))
			\Message::addConfirmation("La notification a été envoyée");
		else
			\Message::addInfo("Le message a été sauvegardé mais la notification n'a pas été envoyée");
	}

	public function listItems($row){
		return sprintf('Créé le %s | %s | %s', date('d/m/Y à H:i:s', $row['createdAt']), $row['author'], $row['message']);
	}
}