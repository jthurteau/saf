<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Auth Plugin Class for Shibboleth integration
 */

namespace Saf\Auth\Plugin;

use Saf\Auth;

class Shib extends Base{
    
    protected string $pluginName = 'Shibboleth Auth';

    public function auth(): bool
    {
        $username = $this->getProvidedUsername();
        $idp = $this->getCurrentIdp();
        if (
            $username
            && $this->allowedIdp($idp)
        ) {
            return $this->succeed();
        }
        $this->fail();
        return false;
    }

    public function logout(): bool
    {
        return true;
    }

    public function getProvidedUsername(): ?string
    {
        return
            key_exists('usernameField', $this->config)
                && key_exists($this->config['usernameField'], $_SERVER)
            ? $_SERVER[$this->config['usernameField']]
            : null;
    }

    public function setUsername(string $username): void
    {
        if (array_key_exists('usernameField', $this->config)) {
            $_SERVER[$this->config['usernameField']] = $username;
        }
    }

    public function getUserInfo(?string $what = null): ?string
    {
        if (is_null($what)) {
            return [
                Auth::PLUGIN_INFO_USERNAME => $this->getProvidedUsername(),
                Auth::PLUGIN_INFO_REALM => 'shibboleth' #TODO allow config to set this
            ];
        } else {
            switch($what){
                case Saf_Auth::PLUGIN_INFO_USERNAME :
                    return $this->getProvidedUsername();
                case Saf_Auth::PLUGIN_INFO_REALM :
                    return 'shibboleth';
                default:
                    return null;
            }
        }
    }

    protected function succeed(): bool
    {
        //#TODO implement an Saf\Identity class;
        Auth::setStatus(true, null, '');
        return parent::succeed();
    }

    public function getCurrentIdp(): ?string
    {
        return
            key_exists('idpField', $this->config)
            ? (
                key_exists($this->config['idpField'],$_SERVER)
                ? $_SERVER[$this->config['idpField']]
                : null
            ) : null;
    }

    public function allowedIdp(string $idpId): ?bool
    {
        $searchIdps = key_exists('idpField', $this->config)
            && key_exists('idps', $this->config);
        if ($searchIdps) {
            foreach($this->config['idps'] as $idp=>$idpData){
                if(key_exists('allowed', $idpData)
                        && 'false' == strtolower($idpData['allowed'])
                        && key_exists('idpId', $idpData)
                        && $idpId == $idpData['idpId']
                ){
                    return false;
                }
            }
            return true;
        }
    }

    public function getSpLogoutUrl(?bool $fullyQualified = false): ?string
    {
        return (
            $fullyQualified
            ? APPLICATION_BASE_URL
            : ''
        ) .  $this->config['logoutUrl'];
    }

    public function getIdpLogoutUrl(string $idpId, ?string $redirectTo = ''): ?string
    {
        $searchIdps = key_exists('idpField',  $this->config)
        && key_exists('idps',  $this->_config);
        if ($searchIdps) {
            foreach( $this->_config['idps'] as $idp=>$idpData){
                if(
                    key_exists('idpLogout', $idpData)
                    && key_exists('idpId', $idpData)
                    && $idpId == $idpData['idpId']
                ){
                    $idpLogout = $idpData['idpLogout'];
                }
            }
        } else {
            throw new \Exception('Trying to get logout URL for unknown IDP.');
        }
        return $idpLogout . (
            '' != $redirectTo
            ? ('?' .  $this->_config['returnUrlField'] . '=' . $redirectTo)
            : ''
        );
    }
}