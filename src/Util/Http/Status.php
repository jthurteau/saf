<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility functions for altering URLs
 */

namespace Saf\Util\Http;

class Status
{

	public const int STATUS_200_OK = 200;
	public const int STATUS_201_CREATED = 201;
	public const int STATUS_202_ACCEPTED = 202;
	public const int STATUS_203_NONAUTHINFO = 203;
	public const int STATUS_204_NOCONTENT = 204;
	public const int STATUS_205_RESETCONTENT = 205;
	public const int STATUS_300_MULTIPLE = 300;
	public const int STATUS_301_PERMANENT = 301;
	public const int STATUS_302_FOUND = 302;
	public const int STATUS_303_OTHER = 303;
	public const int STATUS_304_NOTMODIFIED = 304;
	public const int STATUS_307_TEMPORARY = 307;
	public const int STATUS_400_BADREQUEST = 400;
	public const int STATUS_401_NOTAUTH = 401;
	public const int STATUS_403_FORBIDDEN = 403;
	public const int STATUS_404_NOTFOUND = 404;
	public const int STATUS_405_BADMETHOD = 405;
	public const int STATUS_406_NOTACCEPTABLE = 406;
	public const int STATUS_408_REQUESTTIMEOUT = 408;
	public const int STATUS_409_CONFLICT = 409;
	public const int STATUS_410_GONE = 410;
	public const int STATUS_412_PRECONDITION = 412;
	public const int STATUS_413_ENTITYSIZE = 413;
	public const int STATUS_415_BADMEDIA = 415;
	public const int STATUS_417_EXPECTATION = 417;
	public const int STATUS_500_ERROR = 500;
	public const int STATUS_501_NOTIMPLEMENTED = 501;
	public const int STATUS_502_BADGATEWAY = 502;
	public const int STATUS_503_UNAVAILABLE = 503;
	public const int STATUS_504_GATEWAYTIMEOUT = 504;

	public static function set(int|string $status){
		switch ($status){
			case 200:
			case '200':
				self::header('200 OK');
				break;
			case 201:
			case '201':
				self::header('201 Created');
				break;
			case 202:
			case '202':
				self::header('202 Accepted');
				break;
			case 203:
			case '203':
				self::header('203 Non-Authoritative Information');
				break;
			case 204:
			case '204':
				self::header('204 No Content');
				break;
			case 205:
			case '205':
				self::header('205 Reset Content');
				break;
			case 300:
			case '300':
				self::header('300 Multiple Choices');
				break;
			case 301:
			case '301':
				self::header('301 Moved Permanently');
				//don't keep using the request-uri
				break;
			case 302:
			case '302':
				self::header('302 Found');
				//temporary, keep using the request-uri
				break;
			case 303:
			case '303':
				self::header('303 See Other');
				//context specific, keep using the request-uri POST safe redirect option
				//#TODO #2.0.0 use this for redirect exception, with 302 as the non-default antique browser option
				break;
			case 304:
			case '304':
				self::header('304 Not Modified');
				break;
			case 307:
			case '307':
				self::header('307 Temporary Redirect');
				//temporary, keep using the request-uri stricter alternative to 302
				//which may incorrectly auto-redirect
				break;
			case 400:
			case '400':
				self::header('400 Bad Request');
				break;
			case 401:
			case '401':
				self::header('401 Unauthorized');
				break;
			case 403:
			case '403':
				self::header('403 Forbidden');
				break;
			case 404:
			case '404':
				self::header('404 Not Found');
				break;
			case 405:
			case '405':
				self::header('405 Method Not Allowed');
				break;
			case 406:
			case '406':
				self::header('406 Not Acceptable');
				//cannot formulate a response that would conform to the client's
				//expectations.
				break;
			case 408:
			case '408':
				self::header('408 Request Timeout');
				break;
			case 409:
			case '409':
				self::header('409 Conflict');
				break;
			case 410:
			case '410':
				self::header('410 Gone');
				break;
			case 412:
			case '412':
				self::header('412 Precondition Failed');
				break;
			case 413:
			case '413':
				self::header('413 Request Entity Too Large');
				break;
			case 415:
			case '415':
				self::header('415 Unsupported Media Type');
				break;
			case 416:
			case '416':
				self::header('416 Expectation Failed');
				break;
			case 500:
			case '500':
				self::header('500 Internal Server Error');
				break;
			case 501:
			case '501':
				self::header('501 Not Implemented');
				break;
			case 502:
			case '502':
				self::header('502 Bad Gateway');
				break;
			case 503:
			case '503':
				self::header('503 Service Unavailable');
				break;
			case 504:
			case '504':
				self::header('504 Gateway Timeout');
				break;
			default:
				if (class_exists('\Saf\Debug', false)) {
					\Saf\Debug::out("Unrecognized HTTP Status Set Request: {$status}");
				}
				return false;
		}
		return true;
	}

	/**
	 * outputs a header based on the registered protocol
	 * @param string $string code plus label
	 */
	protected static function header(string $string): void
	{
		if (defined('\Saf\APPLICATION_PROTOCOL') && 'commandline' == \Saf\APPLICATION_PROTOCOL) {
			print("Status: {$string}\r\n");
		} else {
			header("{$_SERVER["SERVER_PROTOCOL"]} {$string}");
		}
	}
}

