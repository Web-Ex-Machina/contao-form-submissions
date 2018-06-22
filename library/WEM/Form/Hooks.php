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
	 * @param  [Object] $objRow    [Database Row of the form]
	 * @param  [String] $strBuffer [Form Buffer]
	 * @return [String]            [Form Buffer, updated, or not, I'm a comment, not your boss.]
	 */
	public function loadAssets($objRow, $strBuffer){
		// Do stuff only if we have the correct setup
		if($objRow->wemStoreSubmissions){
			dump("TEST");
		}
		//return $strBuffer;
	}
}