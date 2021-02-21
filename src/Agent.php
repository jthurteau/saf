<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base class for managing framework instances AKA Guru
 */

namespace Saf;

use Saf\Agent\Guru;
use Saf\Agent\Identity;

require_once(dirname(__FILE__) . '/Agent/Guru.php');
require_once(dirname(__FILE__) . '/Agent/Identity.php');

abstract class Agent {
	use Guru, Identity;

	public const OPTION_NAME = 'instanceName';
	public const OPTION_MODE = 'kickstartMode';

	public const MODE_DELIM = '@';
    public const MODE_AUTODETECT = null;
	public const MODE_NONE = 'none';
	public const MODE_ZFMVC = 'zendmvc'; //NOTE deprecated in 2.0
	public const MODE_ZFNONE = 'zendbare'; //NOTE deprecated in 2.0
	public const MODE_SAF = 'saf';
	public const MODE_SAF_LEGACY = 'saf-legacy';
	public const MODE_MEZ = 'mezzio';
	public const MODE_LAMMVC = 'laminas-mvc'; //#TODO #2.1.0 support Laravel
	public const MODE_LF5 = 'laravel5'; //#TODO #2.1.0 support Laravel
	public const MODE_SLIM = 'slim'; //#TODO #2.1.0 support Slim

	public const MEDITATION_KICKSTART = 'KICKSTART_ERROR';
	public const MEDITATION_BOOTSTRAP = 'BOOTSTRAP_ERROR';
	public const MEDITATION_MIDDLEWARE = 'MIDDLEWARE_ERROR';
	public const MEDITATION_REMOTE = 'REMOTE_ERROR';
	public const MEDITATION_SHUTDOWN = 'SHUTDOWN';
	public const MEDITATION_FATAL_EXCEPTION = 'FATAL_EXCEPTION';
	public const MEDITATION_WARNING = 'WARNING';
	public const MEDITATION_NOTICE = 'NOTICE';
	public const MEDITATION_DEBUG = 'DEBUG';
	public const MEDITATION_TIME = 'PROFILE_TIME';
	public const MEDITATION_MEMORY = 'PROFILE_MEMORY';

    protected const DEFAULT_INSTANCE = 'LOCAL_INSTANCE';

	/**
	 * list of potentionally fatal meditations
	 */
	protected static $criticalMeditations = [
		self::MEDITATION_KICKSTART,
		self::MEDITATION_BOOTSTRAP,
		self::MEDITATION_MIDDLEWARE,
		self::MEDITATION_SHUTDOWN,
		self::MEDITATION_FATAL_EXCEPTION,
		//self::MEDITATION_REMOTE,
	];

	/**
	 * list of meditations that should be tracked a certain way
	 */
	protected static $profileMeditations = [
		self::MEDITATION_TIME,
		self::MEDITATION_MEMORY,
		//self::MEDITATION_REMOTE,
	];

	protected static $idSeed = 0;

	/**
	 * Instantiated agents are bound to an enviroment
	 */
	protected $environment = [];

    public static function detectableModes()
	{
		return [
			self::MODE_SAF,
			self::MODE_SAF_LEGACY,
			self::MODE_MEZ,
			self::MODE_LAMMVC,
			self::MODE_LF5,
			self::MODE_SLIM,
            self::MODE_ZFMVC, #NOTE this is supported under MODE_SAF_LEGACY
		];
	}

	public static function availableModes()
	{
		return array_merge(
            [
                self::MODE_AUTODETECT,
                self::MODE_ZFNONE,
            ], self::detectableModes()
        );
	}

//-- instantiated methods

	public function run()
	{ //#TODO handle both run and main

	}

//-- required Identity trait methods

    /**
     * returns the instance name option key
     */
    public static function instanceOption()
	{
		return self::OPTION_NAME;
	}

    /**
     * returns the kickstart mode option key
     */
    public static function modeOption()
	{
		return self::OPTION_MODE;
	}
    /**
     * returns the agent's signifier for running with no framework
     */
    public static function noMode()
	{
		return self::MODE_NONE;
	}

    /**
	 * Search all supported modes for the first match that supports $instance with $options
     * returns the agent's signifier for auto-detecting frameworks if no instance specified
	 * @param string $instance
     * @param array $options additional instance options to test compatability
	 * @return string matching mode or auto-detect mode signifier
     */
    public static function autoMode(?string $instance = null, array $options = [])
	{
		if (is_null($instance)) {
			return self::MODE_AUTODETECT;
		}
		foreach(self::detectableModes() as $mode) {
			if(self::test($instance, $mode, $options)){ 
				return $mode;
			}
		}
		return self::MODE_NONE;
	}

    /**
     * returns the agent's chosen default instance
     */
    public static function defaultInstance()
	{
		return self::DEFAULT_INSTANCE;
	}

    /**
     * returns the agent mode delimiter
     */
    public static function modeDelim()
	{
		return self::MODE_DELIM;
	}

//-- required Guru trait methods

    /**
     * perform shutdown after a fatal meditation
     */
	protected static function letGo()
	{
		#TODO #2.0.0 decide how to implement.
		die();
	}

	/**
	 * generates a unique id for passed meditation
	 */
	protected static function idStrategy($e)
	{
		return ++self::$idSeed;
	}

	/**
	 * registers a meditation level, marks it critical unless otherwise specified
	 */
	public static function regiterMeditation(string $level, bool $critical = true)
	{
		if ($critical && !in_array($level, self::$criticalMeditations)) {
			self::$criticalMeditations[] = $level;
		} else {
			self::$profileMeditations[] = $level;
		}
	}

	/*
    * returns the first matching existing script, 
    * scripts should handle Exception $e
    * @return string php script path (absolute or relative) 
    */
	protected static function initMeditation()
	{
		$installPath = defined('INSTALL_PATH') ? INSTALL_PATH : '.';
		$applicationPath = 
			defined('APPLICATION_PATH') 
				? APPLICATION_PATH 
				: (INSTALL_PATH . "/application");
		$possibilities = [

			"{$installPath}/error.php"
		];
		foreach($possibilities as $path){
			if (file_exists($path)) {
				return $path;
			}
		}
		return realpath("{$applicationPath}/views/scripts/exception.php");
	}

	/**
	 * returns the specified meditation, or the most recent one
	 * @param mixed meditation id
	 * @return array meditation
	 */
	public static function getMeditation($id = null)
	{
		if (!is_null($id) && array_key_exists($id, self::$meditations)) {
			return self::$meditations[$id];
		} elseif (is_null($id)) {
			return self::$meditations[array_key_last(self::$meditations)];
		}
		return null;
	}
}