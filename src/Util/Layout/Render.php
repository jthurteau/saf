<?php

/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility trait for styling data as HTML
 */

declare(strict_types=1);

namespace Saf\Util\Layout;

use Saf\Util\Layout\Icons\FontAwesome;
use Saf\Util\Filter\Html;

trait Render 
{
    public const int ATT_ENCODE = ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML401;
    public const int SUMMARY_LENGTH = 256;
    public const int SUMMARY_IDEAL = 32;
    public const string DEFAULT_ICON = 'question';
    public const array STATUS_ICONS = [
        'bug' => FontAwesome::BUG,
        'db' => FontAwesome::DATABASE,
        'error' => FOntAwesome::ERROR,
        'up' => FOntAwesome::CHECK_CIRCLE,
        'down' => FOntAwesome::TIMES_CIRCLE,
        'info' => FontAwesome::INFO_CIRCLE,
        'question' => FontAwesome::QUESTION_CIRCLE,
        'unknown' => FontAwesome::QUESTION_CIRCLE,
    ];
    public const array STATUS_KEYWORDS = [
        'not configured' => 'bug',
        'not connected' => 'down',
        'not ready' => 'down',
        'connected' => 'up',
        'ready' => 'up',
        'success' => 'up',
        'failed' => 'down',
        'failure' => 'down',
        'error' => 'error',
        'exception' => 'error',
    ];
    public const array STATUS_HEADERS = [
        'connection status',
    ];
    public const string DEFAULT_SYSTEM_TAG = 'span';

    /**
     * render an icon with tooltip data based on status
     */
    public static function systemIcon (string|array|object $status): string
    {
        $hint = '';
        if (is_object($status) || is_array($status)) {
            return self::systemIconData($status);
        } elseif (is_string($status)){
            if (str_starts_with(ltrim($status), '{') && str_ends_with(rtrim($status), '}')) {
                $array = decode();
                if ($array) {
                    return self::systemIconData($array);
                } else {
                    $hint = 'unable to parse status';
                    $status = 'error';
                    if (class_exists('\Saf\Debug',false) && \Saf\Debug::isEnabled()) {
                        $maxStatus = substr($status, 0, self::SUMMARY_LENGTH);
                        $hint .= ":{$status}";
                    }
                }
            } else {
                $hint = $status;
                $status = self::summarizeStatus($status);
            }
        }
        $icon = self::getIconForStatus($status);
        return self::systemIconData([
            'status' => $status ?: 'unknown', 
            'details' => $hint, 
            'icon' => $icon ?: self::DEFAULT_ICON,
        ]);
    }

    /**
     * render an icon with tooltip data based on status (object or array)
     */
    public static function systemIconData (array|object $status, null|bool|string $wrap = self::DEFAULT_SYSTEM_TAG): string
    {
        if (is_array($status) || (is_object($status) && is_a($status, 'ArrayAccess'))) {
            $hint = $status['details'] ?? null;
            $statusString = $status['status'] ?? 'unknown';
            $icon = $status['icon'] ?? self::getIconForStatus($statusString);
        } else {
            $hint = method_exists($status, 'getDetails') ? (string) $status->getDetails() : null;
            $statusString = method_exists($status, 'getStatus') ? (string) $status->getStatus() : 'unknown';
            $icon = method_exists($status, 'getSymbol') 
                ? (string) $status->getSymbol() 
                : self::getIconForStatus($statusString);
        }
        $hintAttribute = $hint ? (' data-tooltip="' . htmlentities($hint, self::ATT_ENCODE) . '"') : '';
        $hintBody = htmlentities($hint);
        $wrapTag = is_string($wrap) ? Html::tagSafe($wrap) : self::DEFAULT_SYSTEM_TAG;
        $lengthClass = strlen($hintBody) > self::SUMMARY_IDEAL ? ' lengthy': '';
        return 
            ($wrap ? "<{$wrapTag} class=\"system-details status{$lengthClass}\">" : '')
            . "<i class=\"fa fa-{$icon}\"{$hintAttribute}></i>"
            . "<span class=\"details\">{$hintBody}</span>"
            . ($wrap ? "</{$wrapTag}>" : '');
    }

    /**
     * select an icon for a given status
     */
    public static function getIconForStatus (string $status): string
    {
        return self::STATUS_ICONS[strtolower(trim($status))] ?: self::STATUS_ICONS[self::DEFAULT_ICON];
    }

    /**
     * parse a long string status and pick the best category to summarize the result
     */
    public static function summarizeStatus (string $string): string
    {
        $shorter = strtolower(
            strpos($string, ':') !== false 
            ? substr($string, 0, strpos($string, ':')) 
            : substr($string, 0, self::SUMMARY_LENGTH)
        );
        if (strpos($string, ':') !== false && in_array($shorter, self::STATUS_HEADERS)) {
            $shorter = substr(substr($string, strpos($string, ':') + 1), 0, self::SUMMARY_LENGTH);
        }
        $summary = self::scanStatus($shorter) ?: 'info';
        return $summary;
    }

    /**
     * look for keywords in a string, and return an alias for the first keyword found
     */
    public static function scanStatus (string $string): ?string
    {
        foreach(self::STATUS_KEYWORDS as $word => $alias) {
            if (strpos($string, $word) !== false) {
                return $alias;
            }
        }
        return null;
    }
}