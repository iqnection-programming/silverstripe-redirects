<?php

namespace IQnection\Redirects\Control;


use IQnection\Redirects\Model\Redirect;
use SilverStripe\Control\Director;
use SilverStripe\Core\Extension;

class RedirectsController extends Extension
{
	public function onAfterInit()
	{
		$host = $_SERVER['HTTP_HOST'];
		$uri = $_SERVER['REQUEST_URI'];
		// see if we have a domain redirect to follow
		$isSiteHost = Redirect::isSiteHost($host);
		if (!$isSiteHost)
		{
			if ( ( ($redirect = Redirect::findRedirect($uri)) || ($redirect = Redirect::findRedirect($host.$uri)) ) && ($destination = $redirect->Destination()->getLinkURL()) )
			{
				$redirect->doRedirect();
			}
			if ( (!trim($uri, '/')) && ($redirect = Redirect::findDomainRedirect($host)) && ($destination = $redirect->Destination()->getLinkURL()) )
			{
				$redirect->doRedirect();
			}
		}
		if ($this->owner->ErrorCode == 404)
		{
			if ( ($redirect = Redirect::findRedirect($uri)) && ($destination = $redirect->Destination()->getLinkURL()) )
			{
				$redirect->doRedirect();
			}
		}
	}
}