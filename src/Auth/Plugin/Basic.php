<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base Auth Plugin Class and handler for Apache Basic Auth integration
 */

namespace Saf\Auth\Plugin;

use Saf\Auth;

class Basic extends Base {

	protected string $pluginName = 'Apache Basic Auth';

	public function setUsername(string $username): void
	{
		$_SERVER['PHP_AUTH_USER'] = $username;
	}

	public function getProvidedUsername(): ?string
	{
		return 
			key_exists('PHP_AUTH_USER', $_SERVER)
			? trim($_SERVER['PHP_AUTH_USER'])
			: null;
	}

	public function getUserInfo(?string $what = null): ?string
	{
		if (is_null($what)) {
			return array(
				Auth::PLUGIN_INFO_USERNAME => $this->getProvidedUsername(),
				Auth::PLUGIN_INFO_REALM => 'apache'
			);
		} else {
			switch($what){
				case Auth::PLUGIN_INFO_USERNAME :
					return $this->getProvidedUsername();
				case Auth::PLUGIN_INFO_REALM :
					return 'apache';
				default:
					return null;
			}
		}
	}
}