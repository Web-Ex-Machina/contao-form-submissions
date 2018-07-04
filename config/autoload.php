<?php

/**
 * Form Submissions Extension for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

/**
 * Register PSR-0 namespace
 */
if (class_exists('NamespaceClassLoader'))
{
    NamespaceClassLoader::add('WEM', 'system/modules/wem-contao-form-submissions/library');
}

/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'mod_wem_form_submissions_statistics' => 'system/modules/wem-contao-form-submissions/templates/backend',
));