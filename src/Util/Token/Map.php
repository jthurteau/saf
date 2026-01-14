<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility functions for token indexed data
 */

declare(strict_types=1);

namespace Saf\Util\Token;

use Saf\Hash;
use Saf\Util\Token;

trait Map
{
    
    public const string FOUND = '!';
    public const array DEFAULT_WILDCARDS = ['*'];
    public const string DEFAULT_LINK_DELIM = '+';

    /**
     * objects that leverage this trait must return a token map (array or Traversable)
     */
    abstract function getMap(): array|\Traversable;

    /**
     * returns a flat list of all detokened strings
     */
    public static function flatten(?array $data = null) : array
    {
        $return = [];
        foreach($data ?? [] as $token => $subData) {
            if (!in_array(Token::token($token), self::LEAF_TYPES)) {
                $return[] = Token::detoken($token);
                $return = array_merge($return, self::flatten($subData));
            }
        }
        return $return;
    }

    /**
     * returns a nested structure of token strings
     */
    public static function map(array|\Traversable $data) : array
    {
        $return = [];
        foreach($data as $token => $sub) {
            if (!in_array(Token::token($token), Token::LEAF_TYPES)) {
                $return[Token::detoken($token)] = Token::source($sub);
                $return = array_merge($return, self::map($sub));
            }
        }
        return $return;
    }

    public function listSub(int|string $id, null|array|\Traversable $data = null) : array
    {
        is_null($data) && ($data = $this->getMap());
        $result = [];
        $configMatch = self::idSearch($id, $data);
        if ($configMatch) {
            foreach($configMatch as $token => $match) {
                $id = 
                    Token::token($token, $this)
                    ? Token::detoken($token, $this)
                    : null; 
                if ($id) {
                    $source = Token::link($id);
                    $source && ($result[$source[0]] = $source[1]);
                }
            }
        }
        return $result;
        $result = [];
        // $configMatch = self::idSearch($zoneId, $this->zoneMap);
        // if ($configMatch) {
        //     foreach ($configMatch as $areaToken => $areaData) {
        //         $areaName = 
        //             self::TOKEN_DELIM != self::token($areaToken)
        //             ? self::detoken($areaToken)
        //             : null; 
        //         if ($areaName) {
        //             $source = $this->getSource($areaName);
        //             if ($source) {
        //                 $result[$source[0]] = $source[1];
        //             }
        //         }
        //     }
        // }
        // return $result;
    }

    public function listOfType(string $type, null|array|\Traversable $data = null): array
    {
        $list = [];
        foreach($data ?? $this->getMap() as $token => $sub) {
            if (
                Token::token($token) === $type
                && is_array($sub)
                && self::data('id', $sub)
            ) {
                $list[Token::detoken($token)] = self::data('id', $sub);
            } elseif (is_array($sub)) {
                $list += $this->listOfType($type, $sub);
            }
        }
        return $list;
    }

    /**
     * retreives data of name from the current set
     */
    public static function data(string $name, mixed $lookup): mixed
    {
        $t = Token::TYPE_DATA;
        return 
            is_array($lookup) && key_exists("{$t}-{$name}", $lookup) 
            ? $lookup["{$t}-{$name}"]
            : null;
    }

    /**
     * gets all links in the map related to an alias, 
     * including sub-nodes if $expanded
     */
    public function getLinks(string|array $aliases, bool $expanded = true, null|array|\Traversable $data = null): array
    {
        $wildcard = 
            method_exists($this, 'wildcardMatchStrings') 
            ? $this->wildcardMatchStrings() 
            : self::DEFAULT_WILDCARDs; //#TODO enforce array of strings
        if (
            (!is_array($aliases) && in_array($aliases, $wildcard))
            || (is_array($aliases) && array_intersect($aliases, $wildcard))
        ) {
            return $this->getAllLinks();
        }
        $remotes = [];
        foreach($data ?? $this->getMap() as $token => $sub){
            $type = Token::token($token);
            if($type != Token::TYPE_LINK) {
                $insideMatch = $expanded && $aliases === self::FOUND;
                $outsideMatch = in_array(Token::detoken($token), Hash::coerce($aliases), true);
                $match = $insideMatch || $outsideMatch;
                $recurseAliases = $match && $expanded ? self::FOUND : $aliases;
                !in_array($type, Token::LEAF_TYPES) 
                    && ($remotes = array_merge($remotes, $this->getLinks($recurseAliases, $expanded, $sub)));
            } elseif ($aliases == self::FOUND) {
                $remote = Token::detoken($token);
                foreach (is_array($sub) ? $sub : [$sub] as $remoteId) {
                    $remotes[] = $this->link($remote, $remoteId);
                }
            }
        }
        return array_unique($remotes);
    }

    /**
     * returns the string link for a remote and remoteId
     */
    public function link(string $remote, int|string $remoteId): string
    {        
        $remoteDelim = 
            method_exists($this, 'linkDelimString')
            ? $this->linkDelimString()
            : self::DEFAULT_LINK_DELIM; //#TODO enforce array of strings
        return "{$remote}{$remoteDelim}{$remoteId}";
    }

    /**
     * gets the alias (string) for a local id or link string
     */
    public function getAlias(int|string $id, null|array|\Traversable $data = null): ?string
    {
        foreach($data ?? $this->getMap() as $token => $sub){
            $alias = Token::detoken($token);
            if (is_string($id) && $this->getLink($id, $sub)) {
                return $alias;
            } elseif (is_array($sub) && is_int($id)) {
                $subId = self::data('id', $sub);
                if ($subId && $id == $subId) {
                    return $alias;
                }
                $inner = getAlias($id, $sub);
                if ($inner) {
                    return $inner;
                }
            }
            // if($type != 'source') {
            //     $inner = $this->getAliasForRemoteId($remote, $id, $data);
            //     if ($inner) {
            //         return  $inner === self::TOKEN_FOUND ? self::detoken($areaToken) : $inner;
            //     }
            // } elseif ($remote == self::detoken($areaToken)) {
            //     if ($data == $id || is_array($data) && in_array($id, $data)) {
            //         return self::TOKEN_FOUND;
            //     }
            // }
        }
        return null;
    }

    public function getAllLinks(null|array|\Traversable $map = null): array
    {

    }

    /**
     * returns the first link for an alias
     */
    public function getLink(string $alias, null|array|\Traversable $data = null): ?array
    {
        foreach ($data ?? $this->getMap() as $token => $sub) {
            if (is_array($sub)) {
                if (
                    Token::detoken($token) == $alias
                ) {
                    foreach($sub as $token => $leaf) {
                        if (Token::token($token) == Token::TYPE_LINK) {
                            return [$leaf, Token::detoken($token)];
                        }
                    }
                }
                $recurse = $this->getLink($alias, $sub);
                if ($recurse) {
                    return $recurse;
                }
            }
        }
        return null;
    }

    public function dataFor(string|int $id, string $field, null|array|\Traversable $data): mixed
    {
        return null;
            foreach($data ?? $this->map as $areaToken => $data){
                $type = Token::token($areaToken);
                if($type != 'source') {
                    $inner = $this->getAliasForRemoteId($remote, $id, $data);
                    if ($inner) {
                        return  $inner === self::TOKEN_FOUND ? Token::detoken($areaToken) : $inner;
                    }
                } elseif ($remote == Token::detoken($areaToken)) {
                    if ($data == $id || is_array($data) && in_array($id, $data)) {
                        return self::TOKEN_FOUND;
                    }
                }
            }
            return null;
    }

    public function getRoot(string $alias, ?string $type = null, ?int $maxDepth = 10): ?string
    {
        !$maxDepth && \Saf\Util\Profile::ping("max depth in Root Zone search {$original} > {$alias}");
        $list = $this->getZoneList();
        $zoneData = key_exists($alias, $list) ? $list[$alias] : [];
        if (
            is_null($type) 
            || (key_exists('type', $zoneData) && $type == $zoneData['type'])
        ) {
            return $alias;
        }
        $parent = key_exists('parent', $zoneData) && $maxDepth > 0
            ? $this->getRootZone((string)$zoneData['parent'], $type, --$maxDepth)
            : null;
        return $parent ?: $alias;
    }

        /**
     * lookup remote ids?
     */
    public function idSearch($id,  ?array $data = null) : ?array
    {
        foreach ($data ?? $this->getMap() as $token => $sub) {
            if (is_array($sub)) {
                foreach ($sub as $subToken => $leaf) {
                    if(
                        Token::token($subToken) == Token::TYPE_LINK
                        && $id === $leaf
                    ) {
                        $sub['type'] = Token::token($token);
                        $sub['name'] = Token::detoken($token);
                        return $sub;
                    }
                }
                $recurse = $this->idSearch($id, $sub);
                if ($recurse) {
                    return $recurse;
                }
            }
        }
        return null;
    }

}