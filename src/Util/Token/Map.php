<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility functions for nested, token indexed data
 * tokens are a combined, delimited identifier of type and alias (name)
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
     * objects that leverage this trait must return a token map of data (array or Traversable)
     * this map is used by many traversal methods when no map is provided
     */
    abstract function getMap(): array|\Traversable;

    /**
     * returns trait contstant link delimiter unless the composing class defines one
     */
    protected function selectLinkDelim(): string
    {
        return method_exists($this, 'linkDelimString')
        ? $this->linkDelimString()
        : self::DEFAULT_LINK_DELIM;
    }

    /**
     * returns trait contstant wildcard matches unless the composing class defines them
     */
    protected function selectWildcardMatch(): array
    {
        return method_exists($this, 'wildcardMatchStrings') 
        ? $this->wildcardMatchStrings() 
        : self::DEFAULT_WILDCARDs; //#TODO enforce array of strings
    }

    /**
     * returns a flat list of all detokened strings
     */
    public static function flatten(?array $data = null) : array
    {
        $return = [];
        foreach($data ?? [] as $token => $subData) {
            if (!in_array(Token::token($token), Token::LEAF_TYPES)) {
                $return[] = Token::detoken($token);
                $return = array_merge($return, self::flatten($subData));
            }
        }
        return $return;
    }

    /**
     * returns a nested structure of detoken strings
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

    /**
     * return a list of all subnodes by alias
     */
    public function listSub(int|string $id, null|array|\Traversable $data = null) : array
    {
        $list = [];
        if ($id === $this->data(Token::DATA_ID, $data)) {
            return $this->listSub(self::FOUND, $data);
        }
        foreach($data ?? $this->getMap() as $token => $sub) {
            if ($id === Token::detoken($token)) {
                return $this->listSub(self::FOUND, $sub);
            } elseif (!in_array(Token::token($token), Token::LEAF_TYPES)) {
                $match = $id === self::FOUND ? self::FOUND : ''; 
                if ($match) { 
                    $list[] = Token::detoken($token);
                }
                is_array($sub) && $list = array_merge($list, $this->listSub($match ?: $id, $sub));
            }
        }
        return $list;
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
     * retreives data of name from the current set, 
     * returns all data as an array if null|ALL_DATA is passed as the lookup
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
     * returns the id for a provided node (array), alias, or link
     */
    public function id(string|array $source, null|array|\Traversable $data = null): ?int
    {
        return 
            is_array($source) 
            ? $this->data(Token::DATA_ID, $source) 
            : ($this->getData($source, Token::DATA_ID, $data)[Token::DATA_ID] ?: null);
    }

    /**
     * gets all links in the map related to one or more aliases, 
     * including sub-nodes if $expanded
     */
    public function getLinks(string|array $aliases, bool $expanded = true, null|array|\Traversable $data = null): array
    {
        $wildcard = $this->selectWildcardMatch();
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
     * returns true if the provided string is a link (not an alias)
     */
    public function isLink(string $string): bool
    {
        return strpos($string, $this->selectLinkDelim()) !== FALSE;
    }

    /**
     * returns the string link for a remote and remoteId
     */
    public function link(string $remote, int|string $remoteId): string
    {        
        $remoteDelim = $this->selectLinkDelim();
        return "{$remote}{$remoteDelim}{$remoteId}";
    }

    /**
     * gets the alias (string) for a local id or link string
     */
    public function getAlias(int|string $id, null|array|\Traversable $data = null): ?string
    {
        foreach($data ?? $this->getMap() as $token => $sub){
            $alias = Token::detoken($token);
            if (is_array($sub)) {
                if (is_int($id)) {
                    $dataId = $this->data(Token::DATA_ID, $sub);
                    if ($dataId && $id == $dataId) {
                        return $alias;
                    }
                }
                $inner = $this->getAlias($id, $sub);
                if ($inner === self::FOUND) {
                    return $alias;
                } elseif ($inner) {
                    return $inner;
                }
            } elseif (
                is_string($id)
                && Token::token($token) == Token::TYPE_LINK
                && Token::detoken($token) == $this->address($id)
                && (string)$sub == $this->dereference($id) 
            ) {
                return self::FOUND;
            }
        }
        return null;
    }

    /**
     * returns internal data on one or more nodes, tagged with their alias
     * if and array is passed, the results are indexed by the provided indexes
     */
    public function getData(
        int|string|array|\Traversable $zones, 
        ?string $field = null, 
        null|array|\Traversable $data = null
    ): mixed {
        $results = [];
        foreach(Hash::coerce($zones) as $index => $zone) {
            $results[$index] = 
                is_int($zone) || is_string($zone)
                ? $this->dataFor($zone, $data)
                : null;
            $alias = //#TODO get the full token instead and also tag with type...
                is_string($zone) && !$this->isLink($zone)
                ? $zone
                : $this->getAlias($zone, $data);
            if ($alias && !is_null($results[$index])) {
                $results[$index][Token::ize(Token::TYPE_TAG, 'name', $this)] = $zone;
            }
        }
        return Hash::traversable($zones) ? $results : reset($results);
        //return Hash::traversable($zones) ? $results : array_first($results); //#TODO PHP 8.5
    }

    /**
     * returns an array of all remote links in the map
     */
    public function getAllLinks(null|array|\Traversable $data = null): array
    { //#TODO duplicativeish with getLinks?
        $all  = [];
        foreach ($data ?? $this->getMap() as $token => $sub) {
            if (
                Token::token($token) == Token::TYPE_LINK
            ) {
                $remote = Token::detoken($token);
                $all[] = $this->link($remote, $sub);
            } elseif (is_array($sub)) {
                $all = array_merge($all, $this->getAllLinks($sub));
            }
        }
        return $all;
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
     * return the remote id for a link
     */
    public function dereference(string $link): ?int
    { // #TODO add optional remote name check
        return 
            str_contains($link, $this->selectLinkDelim()) 
            ? (int)substr($link, strpos($link, $this->selectLinkDelim()) + 1)
            : null;
    }

    /**
     * return the remote source for a link
     */
    public function address(string $link): ?string
    { // #TODO add optional remote name check
        return 
            str_contains($link, $this->selectLinkDelim()) 
            ? substr($link, 0, strpos($link, $this->selectLinkDelim()))
            : null;
    }

    /**
     * returns data for a node in the map matching the passed identifier (if found)
     * returns only the matching data field if provided, otherwise returns an array of all data
     * for the matching node
     */
    public function dataFor(int|string $id, ?string $field = null, null|array|\Traversable $data = null): mixed
    { //#TODO support link lookup (detect linkDelimString|DEFAULT_LINK_DELIM)
        $match = 
            is_int($id) || is_numeric($id)
            ? $this->idSearch((int) $id, $data) 
            : (
                $this->isLink($id)
                ? $this->linkSearch($id, $data)
                : $this->aliasSearch($id, $data)
            );
            $data = $match ? $this->data(self::ALL_DATA, $match) : null;
        return $field ? (is_array($data) && key_exists($field, $data) ? $data[$field] : null) : $data;
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
     * takes a list of node identifiers and returns 
     * a list of aliases including any sub-nodes.
     */
    public function ungroup(int|string|array $nodes, null|array|\Traversable $data = null): array
    {
        $list = [];
        is_null($data) && ($data = $this->getMap());
        foreach(Hash::coerce($nodes) as $identifier) {
            $alias = 
                is_int($identifier) || is_numeric($identifier)
                ? $this->getAlias((int) $identifier)
                : $identifier; //#TODO handle links
            if (is_string($identifier)) {
                $list = array_merge($list, [$alias], $this->listSub($alias, $data));
            }
        }
        return array_unique($list);
    }

    /**
     * get the ancestor (self inclusive) node alias of type, for a node in the map matched by alias
     */
    public function lookupRoot(string $alias, ?string $type = null, null|array|\Traversable $data = null): ?string
    {
        foreach ($data ?? $this->getMap() as $token => $sub) {
            $typeMatch = is_null($type) || Token::token($token) == $type;
            $currentAlias = Token::detoken($token);
            if ($typeMatch) {
                if ($currentAlias === $alias) {
                    return $currentAlias; //$type is_null ? found : $currentAlias?
                } 
                // elseif (is_array($sub)) {
                //     $subMatch = $this->lookupRoot($alias, null, $sub);
                //     if ($subMatch) {
                //         return $currentAlias;
                //     }
                // }
            }
            if (is_array($sub)) {
                $subMatch = $this->lookupRoot($alias, $typeMatch ? null : $type, $sub);
                if ($subMatch) {
                    return $typeMatch ? $currentAlias : $subMatch;
                }
            }
        }
        return null;
    }

    /**
     * search the map for a node with a link matching the provided remote and id, 
     * returning the node tagged with alias and type if found
     */
    public function linkSearch(string $remote, mixed $id, ?array $data = null) : ?array
    {
        foreach ($data ?? $this->getMap() as $token => $sub) {
            if (is_array($sub)) {
                foreach ($sub as $subToken => $leaf) {
                    if(
                        Token::token($subToken) == Token::TYPE_LINK
                        && Token::detoken($subToken) == $remote
                        && $id === $leaf
                    ) {
                        return self::tag($sub, $token);
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
     * matching the provided string,
     * returning the node tagged with its alias and type if found
     */
    public function aliasSearch(string $alias,  ?array $data = null) : ?array
    {
        foreach ($data ?? $this->getMap() as $token => $sub) {
            if (is_array($sub) && Token::detoken($token) == $alias) {
                return self::tag($sub, $token);
            } elseif (is_array($sub)) {
                $inner = $this->aliasSearch($alias, $sub);
                if ($inner) {
                    return $inner;
                }
            }
        }
        return null;
    }

    /**
     * search the map for a data-id matching the provided id, 
     * returning the node tagged with its alias and type
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
                        return self::tag($sub, $token);
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

    // /**
    //  * temporary method alternative to ::tag() to ensure backwards compatability
    //  */
    // public static function tempTag(array $node, string $token): array
    // {
    //     $node['type'] = Token::token($token);
    //     $node['name'] = Token::detoken($token);
    //     return $node;
    // }

    /**
     * return the current node tagged with the token information
     */
    public function tag(array $node, string $token): array
    {
        $node[Token::ize(Token::TYPE_TAG, 'type', $this)] = Token::token($token);
        $node[Token::ize(Token::TYPE_TAG, 'name', $this)] = Token::detoken($token);
        return $node;
    }

    /**
     * return the tag index for a tag name
     */
    public function getTag(string $name): string
    {
        return Token::ize(Token::TYPE_TAG, $name, $this);
    }

}