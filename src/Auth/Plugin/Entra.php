<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Auth Plugin Class for Entra integration
 */

namespace Saf\Auth\Plugin;

use Saf\Auth;

class Entra extends Base{
    
    protected string $pluginName = 'Microsoft Entra Auth';

    /**
     * attempts to accept the OIDC claims returns true/false based on configured vailidy
     */
    public function auth(): bool
    {
        $username = $this->getProvidedUsername();
        $provider = $this->getCurrentProvider();
        if (
            $username
            //&& $this->allowedProvider($provider ?? '')
        ) {
            return $this->succeed();
        }
        $this->fail();
        return false;
    }

    /**
     * attempts to log the user out (remotely) and returns the success/fail as boolean
     * if unused, it will just return true
     */
    public function logout(): bool
    {
        return true;
    }

    /**
     * gets the configured username from the remote claim data
     */
    public function getProvidedUsername(): ?string
    {
        return
            key_exists('usernameField', $this->config)
                && key_exists($this->config['usernameField'], $_SERVER)
            ? $_SERVER[$this->config['usernameField']]
            : null;
    }

    /**
     * sets the username (locally)
     */
    public function setUsername(string $username): void
    {
        if (key_exists('usernameField', $this->config)) {
            $_SERVER[$this->config['usernameField']] = $username;
        }
    }

    /**
     * gets data on the current user
     */
    public function getUserInfo(?string $what = null): ?string
    {
        if (is_null($what)) {
            return [
                Auth::PLUGIN_INFO_USERNAME => $this->getProvidedUsername(),
                Auth::PLUGIN_INFO_REALM => 'entra'
            ];
        } else {
            switch($what){
                case Auth::PLUGIN_INFO_USERNAME :
                    return $this->getProvidedUsername();
                case Auth::PLUGIN_INFO_REALM :
                    return 'entra';
                default:
                    return null;
            }
        }
    }

    /**
     * Works with the Auth manager to log the user in
     */
    protected function succeed(): bool
    {
        //#TODO implement an Saf\Identity class;
        Auth::setStatus(true, null, '');
        return parent::succeed();
    }

    /**
     * returns the Provider's URI
     */
    public function getCurrentProvider(): ?string
    {
        return
            key_exists('providerField', $this->config)
            ? (
                key_exists($this->config['providerField'], $_SERVER)
                ? $_SERVER[$this->config['providerField']]
                : null
            ) : null;
    }

    /**
     * checks a Provider's URI against the list of those allowed
     * if Provider validation isn't enabled, return null
     */
    public function allowedProvider(string $providerId): ?bool
    {
        $searchProviders = 
            key_exists('providerField', $this->config)
            && key_exists('providers', $this->config);
        if ($searchProviders) {
            foreach ($this->config['providers'] as $providerIndex => $providerData) {
                if(
                    key_exists('allowed', $providerData)
                    && key_exists('providerId', $providerData)
                    && $providerId == $providerData['providerId']
                ){
                    return (
                        is_bool($providerData['allowed'])
                        && $providerData['allowed']
                    ) || (
                        'false' != strtolower((string) $providerData['allowed'])
                    );
                }
            }
            return false;
        }
        return null;
    }

    /**
     * returns the SP logout URL
     */
    public function getSpLogoutUrl(?bool $fullyQualified = false): ?string
    {
        return 
            key_exists() 
            ? (
                ($fullyQualified && defined('\\APPLICATION_BASE_URL') ? \APPLICATION_BASE_URL : '') 
                .  $this->config['logoutUrl']
            ) : null;
    }

    /**
     * gets the provider's logout URL if configured
     */
    public function getProviderLogoutUrl(string $providerId, ?string $redirectTo = ''): ?string
    {
        $searchProviderss = 
            key_exists('providerField',  $this->config)
            && key_exists('providers',  $this->config);
        if ($searchProviders) {
            foreach( $this->config['providers'] as $providerIndex => $providerData){
                if(
                    key_exists('providerLogout', $providerData)
                    && key_exists('providerId', $providerData)
                    && $providerId == $providerData['providerId']
                ){
                    $providerLogout = $providerData['providerLogout'];
                }
            }
        } else {
            throw new \Exception('Trying to get logout URL for unknown Provider.');
        }
        return $providerLogout . (
            '' != $redirectTo
            ? ('?' .  $this->config['returnUrlField'] . '=' . $redirectTo)
            : ''
        );
    }
}