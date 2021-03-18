<?php


namespace IQnection\Redirects\Admin;

use IQnection\Redirects\Dev\RedirectBulkLoader;
use IQnection\Redirects\Model\Redirect;
use SilverStripe\Admin\ModelAdmin;

class RedirectsModelAdmin extends ModelAdmin
{
	private static $url_segment = 'redirects';

	private static $menu_title = 'Redirects';

	private static $managed_models = [
		Redirect::class
	];

	private static $model_importers = [
		Redirect::class => RedirectBulkLoader::class
	];
}
