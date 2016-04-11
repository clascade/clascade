<?php

//== Arrays ==//

if (!function_exists('array_exists'))
{
	function array_exists ($array, $path=null)
	{
		return Clascade\Util\Arr::exists($array, $path);
	}
}

if (!function_exists('array_get'))
{
	function array_get ($array, $path=null, $default=null)
	{
		return Clascade\Util\Arr::get($array, $path, $default);
	}
}

if (!function_exists('array_set'))
{
	function array_set (&$array, $path=null, $value=null)
	{
		return Clascade\Util\Arr::set($array, $path, $value);
	}
}

if (!function_exists('array_delete'))
{
	function array_delete (&$array, $path=null)
	{
		return Clascade\Util\Arr::delete($array, $path);
	}
}

if (!function_exists('array_first'))
{
	function array_first ($array)
	{
		return Clascade\Util\Arr::first($array);
	}
}

if (!function_exists('array_first_key'))
{
	function array_first_key ($array)
	{
		return Clascade\Util\Arr::firstKey($array);
	}
}

if (!function_exists('array_last'))
{
	function array_last ($array)
	{
		return Clascade\Util\Arr::last($array);
	}
}

if (!function_exists('array_last_key'))
{
	function array_last_key ()
	{
		return Clascade\Util\Arr::lastKey($array);
	}
}

//== Base64 ==//

if (!function_exists('nice64_encode'))
{
	function nice64_encode ($data)
	{
		return Clascade\Util\Base64::encodeNice($data);
	}
}

if (!function_exists('nice64_decode'))
{
	function nice64_decode ($data)
	{
		return Clascade\Util\Base64::decodeNice($data);
	}
}

//== Conf ==//

if (!function_exists('conf'))
{
	function conf ($path, $default=null)
	{
		return Clascade\Conf::get($path, $default);
	}
}

//== Math ==//

if (!function_exists('clamp'))
{
	function clamp ($number, $min, $max)
	{
		return Clascade\Util\Math::clamp($number, $min, $max);
	}
}

if (!function_exists('hex'))
{
	function hex ($decimal_number, $num_digits=null)
	{
		return Clascade\Util\Math::dechex($decimal_number, $num_digits);
	}
}

if (!function_exists('mod'))
{
	function mod ($x, $y)
	{
		return Clascade\Util\Math::mod($x, $y);
	}
}

if (!function_exists('normalize_angle'))
{
	function normalize_angle ($angle, $size=null)
	{
		return Clascade\Util\Math::normalizeAngle($angle, $size);
	}
}

if (!function_exists('sign'))
{
	function sign ($number)
	{
		return Clascade\Util\Math::sign($number);
	}
}

if (!function_exists('floor_to'))
{
	function floor_to ($number, $to_multiple_of=null)
	{
		return Clascade\Util\Math::floor($number, $to_multiple_of);
	}
}

if (!function_exists('ceil_to'))
{
	function ceil_to ($number, $to_multiple_of=null)
	{
		return Clascade\Util\Math::ceil($number, $to_multiple_of);
	}
}

if (!function_exists('round_to'))
{
	function round_to ($number, $to_multiple_of=null)
	{
		return Clascade\Util\Math::round($number, $to_multiple_of);
	}
}

if (!function_exists('is_even'))
{
	function is_even ($number)
	{
		return Clascade\Util\Math::isEven($number);
	}
}

if (!function_exists('is_odd'))
{
	function is_odd ($number)
	{
		return Clascade\Util\Math::isOdd($number);
	}
}

//== Random ==//

if (!function_exists('rand_bytes'))
{
	function rand_bytes ($length)
	{
		return Clascade\Util\Rand::getBytes($length);
	}
}

if (!function_exists('rand_int'))
{
	function rand_int ($min, $max)
	{
		return Clascade\Util\Rand::getInt($min, $max);
	}
}

//== Request ==//

if (!function_exists('request_is_https'))
{
	function request_is_https ()
	{
		return Clascade\Request::isHTTPS();
	}
}

if (!function_exists('request_method'))
{
	function request_method ()
	{
		return Clascade\Request::method();
	}
}

if (!function_exists('url_base'))
{
	function url_base ($userinfo=null)
	{
		return Clascade\Request::urlBase($userinfo);
	}
}

if (!function_exists('request_url'))
{
	function request_url ()
	{
		return Clascade\Request::url();
	}
}

if (!function_exists('request_path'))
{
	function request_path ($request_uri=null)
	{
		return Clascade\Request::path($request_uri);
	}
}

if (!function_exists('request_query'))
{
	function request_query ()
	{
		return Clascade\Request::query();
	}
}

if (!function_exists('request_redirect_to'))
{
	function request_redirect_to ($default=null, $params=null)
	{
		return Clascade\Request::getRedirectTo($default, $params);
	}
}

//== Session ==//

if (!function_exists('session_exists'))
{
	function session_exists ($path=null)
	{
		return Clascade\Session::exists($path);
	}
}

if (!function_exists('session_get'))
{
	function session_get ($path=null, $default=null)
	{
		return Clascade\Session::get($path, $default);
	}
}

if (!function_exists('session_set'))
{
	function session_set ($path=null, $value=null)
	{
		return Clascade\Session::set($path, $value);
	}
}

if (!function_exists('session_delete'))
{
	function session_delete ($path=null)
	{
		return Clascade\Session::delete($path);
	}
}

//== Strings ==//

if (!function_exists('u_strlen'))
{
	function u_strlen ($string)
	{
		return Clascade\Util\Str::length($string);
	}
}

if (!function_exists('u_substr'))
{
	function u_substr ($string, $start, $length=null)
	{
		return Clascade\Util\Str::slice($string, $start, $length);
	}
}

if (!function_exists('u_char_at'))
{
	function u_char_at ($string, $pos)
	{
		return Clascade\Util\Str::charAt($string, $pos);
	}
}

if (!function_exists('u_strpos'))
{
	function u_strpos ($haystack, $needle, $offset=null)
	{
		return Clascade\Util\Str::indexOf($haystack, $needle, $offset);
	}
}

if (!function_exists('u_strrpos'))
{
	function u_strrpos ($haystack, $needle, $offset)
	{
		return Clascade\Util\Str::lastIndexOf($haystack, $needle, $offset);
	}
}

if (!function_exists('str_contains'))
{
	function str_contains ($haystack, $needle)
	{
		return Clascade\Util\Str::contains($haystack, $needle);
	}
}

if (!function_exists('starts_with'))
{
	function starts_with ($haystack, $needle)
	{
		return Clascade\Util\Str::startsWith($haystack, $needle);
	}
}

if (!function_exists('ends_with'))
{
	function ends_with ($haystack, $needle)
	{
		return Clascade\Util\Str::endsWith($haystack, $needle);
	}
}

if (!function_exists('str_equals'))
{
	function str_equals ($known_string, $user_string)
	{
		return Clascade\Util\Str::equals($known_string, $user_string);
	}
}

if (!function_exists('str_begin'))
{
	function str_begin ($string, $prefix)
	{
		return Clascade\Util\Str::begin($string, $prefix);
	}
}

if (!function_exists('str_finish'))
{
	function str_finish ($string, $suffix)
	{
		return Clascade\Util\Str::finish($string, $suffix);
	}
}

if (!function_exists('u_strtoupper'))
{
	function u_strtoupper ($string)
	{
		return Clascade\Util\Str::upper($string);
	}
}

if (!function_exists('u_strtolower'))
{
	function u_strtolower ($string)
	{
		return Clascade\Util\Str::lower($string);
	}
}

if (!function_exists('u_strtotitle'))
{
	function u_strtotitle ($string, $exceptions=null)
	{
		return Clascade\Util\Str::title($string, $exceptions);
	}
}

if (!function_exists('u_ucfirst'))
{
	function u_ucfirst ($string)
	{
		return Clascade\Util\Str::ucFirst($string);
	}
}

if (!function_exists('u_ucwords'))
{
	function u_ucwords ($string, $exceptions)
	{
		return Clascade\Util\Str::titleWords($string, $exceptions);
	}
}

if (!function_exists('a_strtoupper'))
{
	function a_strtoupper ($string)
	{
		return Clascade\Util\Str::upperAscii($string);
	}
}

if (!function_exists('a_strtolower'))
{
	function a_strtolower ($string)
	{
		return Clascade\Util\Str::lowerAscii($string);
	}
}

if (!function_exists('a_strtotitle'))
{
	function a_strtotitle ($string, $exceptions=null)
	{
		return Clascade\Util\Str::titleAscii($string, $exceptions);
	}
}

if (!function_exists('a_ucfirst'))
{
	function a_ucfirst ($string)
	{
		return Clascade\Util\Str::ucFirstAscii($string);
	}
}

if (!function_exists('a_ucwords'))
{
	function a_ucwords ($string, $exceptions)
	{
		return Clascade\Util\Str::titleWordsAscii($string, $exceptions);
	}
}

if (!function_exists('str_plural'))
{
	function str_plural ($singular, $num=null, $plural=null, $lang=null)
	{
		return Clascade\Util\Str::plural($singular, $num, $plural, $lang);
	}
}

if (!function_exists('str_limit'))
{
	function str_limit ($string, $max_length=null, $terminator=null, $word_break_search_length=null, $trim_input=null)
	{
		return Clascade\Util\Str::limit($string, $max_length, $terminator, $word_break_search_length, $trim_input);
	}
}

//== Views ==//

if (!function_exists('csrf_token'))
{
	function csrf_token ()
	{
		return Clascade\Session::csrfToken();
	}
}

if (!function_exists('csrf_token_name'))
{
	function csrf_token_name ()
	{
		return Clascade\Conf::get('common.field-names.csrf-token');
	}
}

if (!function_exists('escape'))
{
	function escape ($value)
	{
		return Clascade\View\ViewVar::wrap($value);
	}
}

if (!function_exists('post_fields'))
{
	function post_fields ()
	{
		$fields = make('Clascade\View\Context', 'post-fields',
		[
			'csrf-token-name' => csrf_token_name(),
			'csrf-token' => csrf_token(),
			'return-to-name' => conf('common.field-names.return-to'),
			'return-to' => request_url(),
		]);
		return new Clascade\View\HTMLVar($fields->getRender());
	}
}

if (!function_exists('o'))
{
	function o ($message_key, $params=null, $lang=null)
	{
		return Clascade\View\ViewVar::wrap(Clascade\Lang::translate($message_key, $params, $lang));
	}
}

if (!function_exists('view'))
{
	function view ($view, $vars=null)
	{
		return new Clascade\View\Context($view, $vars);
	}
}

//== Miscellaneous ==//

if (!function_exists('app_base'))
{
	function app_base ()
	{
		return Clascade\Router::appBase();
	}
}

if (!function_exists('db'))
{
	function db ($connection_name=null)
	{
		return Clascade\DB::get($connection_name);
	}
}

if (!function_exists('lang'))
{
	function lang ()
	{
		return Clascade\Lang::currentLang();
	}
}

if (!function_exists('make'))
{
	function make ($class_name)
	{
		$args = func_get_args();
		$args = array_slice($args, 1);
		return Clascade\Core::makeByArray($class_name, $args);
	}
}

if (!function_exists('page'))
{
	function page ()
	{
		return Clascade\Router::provider()->target;
	}
}

if (!function_exists('param_path'))
{
	function param_path ()
	{
		return Clascade\Router::provider()->target->param_path;
	}
}

if (!function_exists('path'))
{
	function path ($rel_path)
	{
		return Clascade\Core::getEffectivePath($rel_path);
	}
}

if (!function_exists('redirect'))
{
	function redirect ($location=null, $status=null)
	{
		return Clascade\Response::redirect($location, $status);
	}
}

if (!function_exists('send_file'))
{
	function send_file ($path)
	{
		return Clascade\Response::sendFile($path);
	}
}

if (!function_exists('trans'))
{
	function trans ($message_key, $params=null, $lang=null)
	{
		return Clascade\Lang::translate($message_key, $params, $lang);
	}
}

if (!function_exists('user'))
{
	function user ($auth_ident=null)
	{
		return Clascade\Auth::getUser($auth_ident);
	}
}
