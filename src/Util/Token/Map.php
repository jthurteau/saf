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
    public const null ALL_DATA = null;

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
        $configMatch = $this->linkSearch($id, $data);
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

    /**
     * return a flat list of all addressable nodes of type
     */
    public function listOfType(string $type, null|array|\Traversable $data = null): array
    { //#TODO handle/return nodes with no local id, but with links
        $list = [];
        foreach($data ?? $this->getMap() as $token => $sub) {
            if (
                Token::token($token) === $type
                && is_array($sub)
                && $this->data(Token::DATA_ID, $sub)
            ) {
                $list[Token::detoken($token)] = $this->data(Token::DATA_ID, $sub);
            } elseif (is_array($sub)) {
                $list += $this->listOfType($type, $sub);
            }
        }
        return $list;
    }

    /**
     * retreives data of name from the current set, returns all data if null|ALL_DATA is passed
     */
    public function data(?string $name, mixed $lookup): mixed
    {
        if ($name === self::ALL_DATA && is_array($lookup)) {
            $data = [];
            foreach($lookup as $token => $value) {
                if (Token::token($token) == Token::TYPE_DATA) {
                    $data[Token::detoken($token)] = $value;
                }
            }
            return $data;
        }
        return 
            is_array($lookup) && key_exists(Token::ize(Token::TYPE_DATA, $name, $this), $lookup) 
            ? $lookup[Token::ize(Token::TYPE_DATA, $name, $this)]
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
            : self::DEFAULT_LINK_DELIM;
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
                $subId = $this->data(Token::DATA_ID, $sub);
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

    /**
     * returns data for a node in the map matching the passed identifier (if found)
     * returns only the matching data field if provided, otherwise returns an array of all data
     * for the matching node
     */
    public function dataFor(string|int $id, ?string $field = null, null|array|\Traversable $data = null): mixed
    { //#TODO support link lookup (detect linkDelimString|DEFAULT_LINK_DELIM)
        $match = is_int($id) ? $this->idSearch($id) : $this->aliasSearch($id);
        return $match ? $this->data(self::ALL_DATA, $match) : null;
    }

    /**
     * get the parent node's full token for a node in the map matched by alias
     */
    public function getParent(string $alias, null|array|\Traversable $data = null): ?array
    {
        foreach ($data ?? $this->getMap() as $token => $sub) {
            if (is_array($sub) && Token::detoken($token) == $alias) {
                return null;
            } elseif ($this->aliasSearch($alias, $sub)) {
                return $token;
            }
        }
        return null;        
    }

    /**
     * get the ancestor (self inclusive) node alias of type, for a node in the map matched by alias
     */
    public function lookupRoot(string $alias, ?string $type = null, null|array|\Traversable $data = null): ?string
    {
        foreach ($data ?? $this->getMap() as $token => $sub) {
            if (Token::token($token) == $type) {
                $currentAlias = Token::detoken($token);
                if (
                    $currentAlias == $alias 
                    || (
                        is_array($sub)
                        && $this->aliasSearch($alias, $sub)
                    )
                ) {
                    return $currentAlias;
                } elseif (is_array($sub)) {
                    $subMatch = $this->lookupRoot($alias, $type, $data);
                    if ($subMatch) {
                        return $subMatch;
                    }
                }
            }
        }
        return null;
    }

    /**
     * search the map for a node with a link matching the provided remote and id, returning the node if found
     */
    public function linkSearch(string $remote, mixed $id,  ?array $data = null) : ?array
    {
        foreach ($data ?? $this->getMap() as $token => $sub) {
            if (is_array($sub)) {
                foreach ($sub as $subToken => $leaf) {
                    if(
                        Token::token($subToken) == Token::TYPE_LINK
                        && Token::detoken($subToken) == $remote
                        && $id === $leaf
                    ) {
                        return self::tempTag($sub, $token);
                        return $sub;
                    }
                }
                $recurse = $this->linkSearch($remote, $id, $sub);
                if ($recurse) {
                    return $recurse;
                }
            }
        }
        return null;
    }

    /**
     * search the map for a node with an alias (tokenized identifier) 
     * matching the provided string, returning the node if found
     */
    public function aliasSearch(string $alias,  ?array $data = null) : ?array
    {
        foreach ($data ?? $this->getMap() as $token => $sub) {
            if (is_array($sub) && Token::detoken($token) == $alias) {
                return self::tempTag($sub, $token);
            } else {
                $inner = $this->aliasSearch($alias, $sub);
                if ($inner) {
                    return $inner;
                }
            }
        }
        return null;
    }

    /**
     * search the map for a data-id matching the provided id, returning the node if found
     */
    public function idSearch(mixed $id,  ?array $data = null) : ?array
    {
        foreach ($data ?? $this->getMap() as $token => $sub) {
            if (is_array($sub)) {
                foreach ($sub as $subToken => $leaf) {
                    if(
                        Token::token($subToken) == Token::TYPE_DATA
                        && Token::detoken($subToken) == Token::DATA_ID
                        && $id === $leaf
                    ) {
                        return self::tempTag($sub, $token);
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

    /**
     * temporary method alternative to ::tag() to ensure backwards compatability
     */
    public static function tempTag(array $node, string $token): array
    {
        $node['type'] = Token::token($token);
        $node['name'] = Token::detoken($token);
        return $node;
    }

    /**
     * return the current node tagged with the token information
     */
    public function tag(array $node, string $token): array
    {
        $node[Token::ize(Token::TYPE_TAG, 'type', $this)] = Token::token($token);
        $node[Token::ize(Token::TYPE_TAG, 'name', $this)] = Token::detoken($token);
        return $node;
    }

}