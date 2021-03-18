<?php


namespace IQnection\Redirects\Dev;

use IQnection\Redirects\Model\Redirect;
use SilverStripe\Assets\File;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Dev\CsvBulkLoader;

class RedirectBulkLoader extends CsvBulkLoader
{
	public $duplicateChecks = [
		'FromPath' => [
			'callback' => 'findFromExisting'
		]
	];

	public $relationCallbacks = [
		'Destination' => [
			'relationname' => 'Destination',
			'callback' => 'findToLink'
		]
	];

	public function findFromExisting($value, $record)
	{
		if (Director::is_site_url($value))
		{
			$value = Director::makeRelative($value);
		}
		return Redirect::get()->Find('FromPath', $value);
	}

	public function findToLink($obj, $val, $record)
	{
		$link = $obj->Destination();
		$link->Title = 'Redirect';
		try {
			// is this a link to another domain?
			if (Director::is_site_url($val))
			{
				$url = Director::makeRelative($val);
				// is this a link to a file?
				if (preg_match('/^assets\//',$url))
				{
					if ($File = File::find('FileFilename', preg_replace('/assets\//','',$url)))
					{
						$link->Type = 'File';
						$link->FileID = $file->ID;
						$link->write();
						return $link;
					}
				}
				// is this a link to a Page?
				if ($page = SiteTree::get_by_link($url))
				{
					$link->Type = 'SiteTree';
					$link->SiteTreeID = $page->ID;
					$link->write();
					return $link;
				}
			}
			$link->URL = $val;
			$link->Type = 'URL';
			$link->write();
		} catch (\Exception $e) { }
		return $link;
	}
}
