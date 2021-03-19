<?php


namespace IQnection\Redirects\Model;

use Sheadawson\Linkable\Forms\LinkField;
use Sheadawson\Linkable\Models\Link;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Environment;
use SilverStripe\Forms;
use SilverStripe\ORM\DataObject;

class Redirect extends DataObject
{
	private static $table_name = 'Redirect';
	private static $singular_name = 'Redirect';
	private static $plural_name = 'Redirects';

	private static $db = [
		'Enabled' => 'Boolean',
		'Type' => "Enum('301,302','301')",
		'FromPath' => 'Varchar(255)',
		'MatchURL' => 'Varchar(255)',
	];

	private static $has_one = [
		'Destination' => Link::class
	];

	private static $summary_fields = [
		'Enabled.Nice' => 'Enabled',
		'Type' => 'Type',
		'FromPath' => 'From',
		'Destination.getLinkUrl' => 'Destination'
	];

	private static $defaults = [
		'Enabled' => true,
		'Type' => '301'
	];

	public function getCMSFields()
	{
		$fields = parent::getCMSFields();
		$fields->replaceField('MatchURL', $fields->dataFieldByName('MatchURL')->performReadonlyTransformation());
		$fields->replaceField('Type', Forms\OptionsetField::Create('Type','Type')
			->setSource($this->dbObject('Type')->enumValues()));
		$fields->replaceField('DestinationID', LinkField::create('DestinationID','Redirect To'));
		return $fields;
	}

	public function validate()
	{
		$result = parent::validate();
		$MatchURL = $this->generateMatchUrl();
		if (Redirect::get()->Exclude('ID',$this->ID)->Find('MatchURL', $MatchURL))
		{
			$result->addFieldError('FromPath','A redirect is already setup for this URL');
		}
		return $result;
	}

	public function importFrom($value, $record)
	{
		$this->FromPath = $value;
	}

	public function generateMatchUrl()
	{
		$relativePath = Director::makeRelative($this->FromPath);

		list($relativePath, $query) = explode('?',$relativePath);
		$relativePath = trim($relativePath, ' /');
		$host = self::getHostName($this->FromPath);
		if ( (!$host) || (self::isSiteHost($host)) )
		{
			// goal is for final path to be path/to/page
			$this->MatchURL = '/'.ltrim($relativePath, ' /');
		}
		else
		{
			// goal is for final path to be //[www.]?example.com/path/to/page
			$this->MatchURL = '//'.trim($host.'/'.$relativePath, '/');
		}
		if (!empty($query))
		{
			$this->MatchURL .= '?'.$query;
		}
		return $this->MatchURL;
	}

	public function onBeforeWrite()
	{
		parent::onBeforeWrite();
		$this->generateMatchUrl();
	}

	public function doRedirect()
	{
		$response = new HTTPResponse_Exception();
		$type = (Director::isLive()) ? $this->Type : 302;
		$destination = $this->Destination()->getLinkURL();
		if ( (Director::is_relative_url($destination)) && ($SS_BASE_URL = Environment::getEnv('SS_BASE_URL')) )
		{
			$destination = Controller::join_links($SS_BASE_URL, $destination);
		}
		$response->getResponse()->redirect($destination, $type);
		throw $response;
	}

	/**
	* Cleans a URL and returns just the host name
	* Removes the www
	*
	* @param mixed $host
	* @returns string
	*/
	public static function getHostName($url)
	{
		if (preg_match('/\./',$url))
		{
			$host = preg_replace('/^(https?:)?(\/\/)?/','',$url);	// -> [www.]example.com/path/to/page/?query=1
			$host = preg_replace('/^([^\/]+)\/.*$/','$1',$host);	// -> example.com
			return trim($host, ' /');	// safeguard -> example.com
		}
	}

	/**
	* Checks if the provided host is the same host named in the .env
	*
	* @param $url string
	* @returns boolean
	*/
	public static function isSiteHost($host = null)
	{
		$siteHost = self::getHostName(Environment::getEnv('SS_BASE_URL'));	// -> [www.]example.com
		if (!$siteHost)
		{
			// if a site url is not specified in the environment, assume true
			return true;
		}
		if (is_null($host))
		{
			$host = Director::host(Controller::curr()->getRequest());
		}
		$host = self::getHostName($host);
		$host = preg_replace('/^www\./','',$host);
		$siteHost = preg_replace('/^www\./','',$siteHost);
		return ( ($host === $siteHost) || ('www.'.$host === 'www.'.$siteHost) );
	}

	public static function findDomainRedirect($host = null)
	{
		if (is_null($host))
		{
			$host = Director::host(Controller::curr()->getRequest());
		}
		// if this is the site host, then no redirect should exist
		if (!self::isSiteHost($host))
		{
			$siteUrl = self::getHostName($host);	// -> example.com
			return self::get()->Filter('MatchURL', [
				'//'.$siteUrl, // -> //example.com
				'//www.'.$siteUrl // => //www.example.com
			])->First();
		}
	}

	public static function findRedirect($requestURI = null)
	{
		if (is_null($requestURI))
		{
			$requestURI = $_SERVER['REQUEST_URI'];
		}
		// $requestUri -> /path/to/page/?query=1
		$requestURI = current(explode('?',$requestURI)); // -> /path/to/page/
		$requestURI = trim($requestURI, '/');	// -> path/to/page
		$filters = [];
		// check if we're matching a different host name
		if (!self::isSiteHost())
		{
			// include the domain in the db query
			$host = Director::host(Controller::curr()->getRequest());
			$host = preg_replace('/^www\./','',$host);
			$filters[] = '//'.trim($host.'/'.$requestURI,'/'); // -> //example.com/path/to/page
			$filters[] = '//www.'.trim($host.'/'.$requestURI,'/'); // -> //www.example.com/path/to/page
		}
		else
		{
			$filters[] = trim($requestURI, ' /');
			$filters[] = '/'.trim($requestURI, ' /');
		}
		return self::get()->Filter('MatchURL', $filters)->First();
	}
}
