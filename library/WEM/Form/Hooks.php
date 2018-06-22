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
use Contao\Controller;

/**
 * Hooks functions
 */
class Hooks extends Controller
{
	/**
	 * Load the form assets
	 * @param  [Array] 		$arrFields    	[Form Fields, as Array]
	 * @param  [Integer] 	$intFormId 		[Form ID]
	 * @param  [Object] 	$objForm 		[Form Object]
	 * @return [Array]            			[Form Fields, updated, or not, I'm a comment, not your boss.]
	 */
	public function loadAssets($arrFields, $intFormId, $objForm){
		// Do stuff only if we have the correct setup
		if($objForm->wemStoreSubmissions){
			$GLOBALS["TL_JAVASCRIPT"][] = 'system/modules/wem-contao-form-submissions/assets/js/functions.js';
		}

		return $arrFields;
	}

	/**
	 * Catch AJAX Requests
	 * @param  [Object] $objPage   [Page Model]
	 * @param  [Object] $objLayout [Layout Model]
	 * @param  [Object] $objPty    [Page Type Model]
	 */
	public function catchAjaxRequest($objPage, $objLayout, $objPty){
		
	}
}