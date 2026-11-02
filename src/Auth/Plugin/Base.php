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
use Saf\Debug;

abstract class Base {

	protected string $pluginName = 'Undefined';
	protected array $config = [];

	public function __construct(?array $config = array())
	{
		$this->config = $config;
		if (is_array($this->config) && key_exists('publicLabel', $this->config)) {
			$this->pluginName = $this->config['publicLabel'];
		}
	}

	public function getPublicName()
	{
		return $this->pluginName;
	}	
    
    public function auth(): bool
    {
		if ($this->getProvidedUsername()) {
			Auth::setStatus(true);
			return true;
		}
		return false;
	}

	abstract public function setUsername(string $username): void;

	abstract public function getProvidedUsername(): ?string;

	abstract public function getUserInfo(?string $what = null): ?string;

	public function isLoggedIn()
	{
		$username = $this->getProvidedUsername();
		return !is_null($username) && '' != trim($username);
	}

	public function logout(): bool
	{
		return false;
	}

    public function supportslogout()
	{
		return false;
	}

    public function supportslogin()
	{
		return false;
	}

	public function getExternalLogoutUrl()
	{
		return '';
	}

	public function getExternalLoginUrl()
	{
		return '';
	}

    public function storesPassword()
    {
        return false;
    }

	public function getProvidedPassword()
	{
		return '';
	}

	public function fail()
	{
		if (Debug::isEnabled()) {
			Debug::out('Authentication Declined.');
		}
	}

	public function setPluginStatus($success, $errorCode)
	{
		//#NOTE only some plugins will need to do this.
	}

	public function promptsForInfo()
	{
		return false;
	}

    /**
     * actions the plugin will execute after login
     */
	public function postLogin()
	{
		return true;
	}

    protected function succeed(): bool
	{
		return true;
	}

}