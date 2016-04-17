<?php

namespace Clascade;
use Clascade\Lang\Pack;
use Clascade\Util\Escape;
use Clascade\Util\Str;
use Clascade\View\ViewVar;

class Lang
{
	public static $plural_categories =
	[
		0 => 'zero',
		1 => 'one',
		2 => 'two',
		3 => 'few',
		4 => 'many',
		5 => 'other',
	];
	
	public static function currentLang ()
	{
		$page = page();
		
		if (isset ($page->lang))
		{
			return $page->lang;
		}
		
		return conf('common.intl.default-lang');
	}
	
	public static function translate ($message_key, $params=null, $lang=null)
	{
		if ($lang === null)
		{
			$lang = static::currentLang();
		}
		
		$choices = static::translationChoices($message_key, $lang);
		
		if ($choices === null)
		{
			return null;
		}
		
		// Unwrap any ViewVars.
		
		if ($params instanceof ViewVar)
		{
			$params = $params->raw;
		}
		elseif (is_array($params))
		{
			foreach ($params as $key => $value)
			{
				if ($value instanceof ViewVar)
				{
					$params[$key] = $value->raw;
				}
			}
		}
		
		return static::selectTranslation($choices, $params, $lang);
	}
	
	public static function translationChoices ($message_key, $lang=null)
	{
		if ($lang === null)
		{
			$lang = static::currentLang();
		}
		
		$message_key = explode('.', $message_key, 2);
		
		if (count($message_key) < 2)
		{
			return null;
		}
		
		list ($lang_pack, $message_key) = $message_key;
		$translations = static::getPack("{$lang}/{$lang_pack}");
		
		while ($translations === null || !isset ($translations[$message_key]))
		{
			// Try the next generalized form of the language.
			
			$pos = strrpos($lang, '_');
			
			if ($pos === false)
			{
				break;
			}
			
			$lang = substr($lang, 0, $pos);
			$translations = static::getPack("{$lang}/{$lang_pack}");
		}
		
		if ($translations === null || !isset ($translations[$message_key]))
		{
			// Fall back to the default language.
			
			$translations = static::getPack(conf('common.intl.default-lang')."/{$lang_pack}");
			
			if ($translations === null || !isset ($translations[$message_key]))
			{
				// Couldn't find a matching translation.
				
				return null;
			}
		}
		
		return $translations[$message_key];
	}
	
	public static function selectTranslation ($choices, $params=null, $lang=null)
	{
		if ($lang === null)
		{
			$lang = static::currentLang();
		}
		
		if (!is_array($params))
		{
			// Treat $params as the value of the special "num" param.
			
			$params = ['num' => $params];
		}
		
		// If the "num" param is missing or null, default to 1.
		
		if (!isset ($params['num']))
		{
			$params['num'] = 1;
		}
		
		$translation = static::translationChoice($choices, $params['num'], $lang);
		$translation = Str::template($translation, $params);
		return $translation;
	}
	
	public static function translationChoice ($translation, $num, $lang=null)
	{
		if ($lang === null)
		{
			$lang = static::currentLang();
		}
		
		if (!is_array($translation))
		{
			$translation = explode('|', $translation);
		}
		
		$auto_choices = [];
		
		foreach ($translation as $key => $form)
		{
			$form = trim($form);
			
			// Check for an explicit number set/range.
			
			if (preg_match('/^
				(?:
					\{\s*
						([+\-]?(?:\d+(?:\.\d*)?|\d*\.\d+)(?:\s*,\s*[+\-]?(?:\d+(?:\.\d*)?|\d*\.\d+))*|zero|one|two|few|many|other)
					\s*\}
				|
					[[\](]\s*
						([+\-]?inf|[+\-]?(?:\d+(?:\.\d*)?|\d*\.\d+)|)
						\s*,\s*
						([+\-]?inf|[+\-]?(?:\d+(?:\.\d*)?|\d*\.\d+)|)
					\s*[\][)]
				)\s*/xi', $form, $match)
			)
			{
				$form = substr($form, strlen($match[0]));
				
				if ($match[0][0] == '{')
				{
					if (Str::isAlpha($match[1][0]))
					{
						// Pluralization category.
						
						$category = Str::lowerAscii($match[1]);
						
						if ($num === $category)
						{
							return $form;
						}
						
						$auto_choices[Str::lowerAscii($match[1])] = $form;
					}
					else
					{
						// Number set.
						
						$values = preg_split('/\s*,\s*/', $match[1]);
						
						foreach ($values as $value)
						{
							if ($value == $num)
							{
								return $form;
							}
						}
					}
				}
				else
				{
					// Number range.
					
					$lower = Str::lowerAscii($match[2]);
					$upper = Str::lowerAscii($match[3]);
					
					if ($lower != 'inf' && $lower != '+inf' && $upper != '-inf')
					{
						$lower = ($lower == '' || $lower == '-inf') ? -INF : +$lower;
						$upper = ($upper == '' || $upper == 'inf' || $upper == '+inf') ? INF : +$upper;
						
						if (
							(substr($match[0], 0, 1) == '[' ? $num >= $lower : $num > $lower) &&
							(substr($match[0], -1)   == ']' ? $num <= $upper : $num < $upper)
						)
						{
							return $form;
						}
					}
				}
			}
			else
			{
				$auto_choices[] = $form;
			}
			
			if ($key == 0)
			{
				$fallback = $form;
			}
		}
		
		// No explicit match found. Select automatically based
		// on the language and number.
		
		if (!empty ($auto_choices))
		{
			$model = static::getPluralizationModel($lang);
			
			switch ($num)
			{
			case 'zero':
			case 'one':
			case 'two':
			case 'few':
			case 'many':
			case 'other':
				$category = $num;
				$index = static::getPluralizationIndexFromCategory($category, $model);
				break;
			
			default:
				$index = static::getPluralizationIndex($num, $model);
				$category = static::getPluralizationCategoryFromIndex($index, $model);
				
				if (isset ($auto_choices[$category]))
				{
					return $auto_choices[$category];
				}
			}
			
			if (isset ($auto_choices[$index]))
			{
				return $auto_choices[$index];
			}
			elseif (isset ($auto_choices['other']))
			{
				return $auto_choices['other'];
			}
			elseif (isset ($auto_choices[0]))
			{
				return $auto_choices[0];
			}
		}
		
		// No translation was provided for this plural. Default
		// to the first translation.
		
		return $fallback;
	}
	
	/**
	 * Given a string representing preferred languages, and an array
	 * of supported languages, return the most preferred supported
	 * language.
	 *
	 * $lang_accept may be in the format of the HTTP Accept-Language
	 * header, specifying multiple options with optional "q" values
	 * indicating priority.
	 */
	
	public static function getBestMatch ($lang_accept=null, $choices=null, $default=null)
	{
		if ($default === null)
		{
			$default = conf('common.intl.default-lang');
		}
		
		if ($lang_accept === null)
		{
			if (!isset ($_SERVER['HTTP_ACCEPT_LANGUAGE']))
			{
				return $default;
			}
			
			$lang_accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}
		
		if ($choices === null)
		{
			$choices = conf('common.intl.supported-langs');
		}
		
		if ($lang_accept == '' || empty ($choices))
		{
			return $default;
		}
		
		// Remove whitespace. This should take care of any LWS
		// that is allowed in the HTTP Accept-Language header.
		
		$lang_accept = preg_replace('/[\r\n\t ]+/', '', $lang_accept);
		
		$lang_accept = explode(',', $lang_accept);
		$lang_scores = [];
		$nonmatch_score = 0;
		
		foreach ($lang_accept as $lang)
		{
			$lang = explode(';', $lang, 3);
			$q = 1;
			
			if (isset ($lang[1]) && Str::lowerAscii(substr($lang[1], 0, 2)) == 'q=')
			{
				$q = (float) substr($lang[1], 2);
			}
			
			if ($lang == '*')
			{
				$nonmatch_score = $q;
			}
			else
			{
				$lang = Str::lowerAscii($lang[0]).'-';
				
				if (!isset ($lang_scores[$lang]) || $lang_scores[$lang] < $q)
				{
					$lang_scores[$lang] = $q;
				}
			}
		}
		
		uksort($lang_scores, function ($a, $b)
		{
			return strlen($b) - strlen($a);
		});
		
		$best_choice = $default;
		$best_score = 0;
		
		foreach ($choices as $choice)
		{
			$choice_match = static::toBCP47($choice);
			$choice_match = Str::lowerAscii($choice_match);
			$choice_match .= '-';
			$lang_match = null;
			
			// Find the longest match ($lang_scores should already
			// be sorted by length in descending order).
			
			foreach ($lang_scores as $lang => $score)
			{
				if (substr($choice_match, 0, strlen($lang)) == $lang)
				{
					$lang_match = $lang;
					break;
				}
			}
			
			$score = ($lang_match === null ? $nonmatch_score : $lang_scores[$lang_match]);
			
			if ($score > $best_score)
			{
				$best_choice = $choice;
				$best_score = $score;
			}
		}
		
		return $best_choice;
	}
	
	public static function getPack ($lang_file)
	{
		return Pack::get($lang_file);
	}
	
	public static function toBCP47 ($lang)
	{
		$lang = str_replace('_', '-', $lang);
		
		if (Str::lowerAscii(substr("{$lang}_", 0, 5)) == 'root_')
		{
			$lang = 'und'.substr($lang, 4);
		}
		
		return $lang;
	}
	
	public static function getDictFilename ($word)
	{
		$filename = Str::slice($word, 0, 2);
		
		if (preg_match('/^[A-Za-z]{2}$/', $filename))
		{
			return Str::lowerAscii($filename);
		}
		elseif (Str::length($filename) < 2 || Str::isAscii($filename))
		{
			return '_';
		}
		else
		{
			$filename = Str::lower($filename);
			
			return preg_replace_callback('/[^0-9A-Za-z]/u', function ($match)
			{
				$codepoint = Str::codePointAt($match[0]);
				return '_'.hex($codepoint, 6);
			}, $filename);
		}
	}
	
	public static function getPluralChoicesByWord ($word, $lang=null)
	{
		if ($lang === null)
		{
			$lang = static::currentLang();
		}
		
		// Attempt to find a defined pluralization for this word.
		
		$filename = static::getDictFilename($word);
		$choices = static::translationChoices("dict/plurals/{$filename}.{$word}", $lang);
		
		if ($choices === null)
		{
			// Try a lowercase match.
			
			$choices = static::translationChoices("dict/plurals/{$filename}.".Str::lower($word), $lang);
		}
		
		if ($choices !== null)
		{
			return $choices;
		}
		
		// Fall back to generalized pluralization rules.
		
		return static::getPluralChoicesGeneralized($word, $lang);
	}
	
	public static function getPluralChoicesGeneralized ($word, $lang=null)
	{
		if ($lang === null)
		{
			$lang = static::currentLang();
		}
		
		// English.
		
		if (starts_with(Str::lowerAscii($lang).'_', 'en_'))
		{
			$lower = Str::lowerAscii($word);
			$last = substr($lower, -1);
			$last2 = substr($lower, -2);
			
			if ($last == 'y' && strspn($last2, 'bcdfghjklmnpqrstvwxz') >= 1)
			{
				$plural = substr($word, 0, -1).'ies';
			}
			elseif ($last == 's' || $last == 'x' || $last == 'z' || $last2 == 'ch' || $last2 == 'sh')
			{
				$plural = "{$word}es";
			}
			elseif (($last == 'f' || $last2 == 'fe') && strspn(substr($lower, -4), 'aeiou') < 2)
			{
				$plural = substr($word, 0, strrpos($word, 'f')).'ves';
			}
			else
			{
				$plural = "{$word}s";
			}
			
			return Escape::translation($word).'|'.Escape::translation($plural);
		}
		
		return Escape::translation($word);
	}
	
	public static function getPluralizationCategoryFromIndex ($index, $pluralization_model)
	{
		return static::$plural_categories[$pluralization_model[$index]];
	}
	
	public static function getPluralizationIndexFromCategory ($category, $pluralization_model)
	{
		return strpos($pluralization_model, (string) array_search($category, static::$plural_categories));
	}
	
	public static function getPluralizationVars ($num)
	{
		$n = abs($num);
		$i = is_int($n) ? $n : floor($n);
		
		if (is_string($num) && Str::isSimpleNumber($num, true))
		{
			// This is a string. We can distinguish trailing
			// zeros in the fractional part, if present.
			
			$pos = strpos($num, '.');
			
			if ($pos === false || $pos == strlen($num) - 1)
			{
				// This has no fractional part.
				
				$w = $v = $t = $f = 0;
			}
			else
			{
				// This has a fractional part. Extract it.
				
				$f = substr($num, $pos + 1);
				$t = rtrim($f, '0');
				$v = strlen($f);
				$w = strlen($t);
				$f = +$f;
				$t = +$t;
			}
		}
		elseif ($n == $i)
		{
			// This is a whole number. Treat it as having no
			// fractional part.
			
			$w = $v = $t = $f = 0;
		}
		else
		{
			// This is a number with a fractional part. We
			// can't correctly handle trailing zeros in the
			// fractional part, because they aren't actually
			// stored. Let's carefully extract the fractional
			// part.
			
			// Remove integer part.
			
			$f = fmod($n, 1);
			
			// Write out the digits without scientific notation.
			// We'll go up to 9 digits, which means it will be
			// safe to represent the fractional digits as a
			// signed 32-bit integer.
			
			$f = number_format($f, 9, '.', '');
			
			// Remove the leading zero and trailing zeros.
			
			$f = trim($f, '0');
			
			// Trim the decimal point.
			
			$f = substr($f, 1);
			
			// Set the rest of our variables.
			
			$w = $v = strlen($f);
			$t = $f = +$f;
		}
		
		return compact('n', 'i', 'f', 't', 'v', 'w');
	}
	
	public static function getPluralizationIndex ($num, $pluralization_model)
	{
		// Prepare the variables we'll use in pluralization rules.
		
		extract(static::getPluralizationVars($num));
		
		// For information about these rules, see:
		// http://www.unicode.org/cldr/charts/27/supplemental/language_plural_rules.html
		
		switch ($pluralization_model)
		{
		case '5a':
			return 0;
		
		case '15a':
			return $n == 1 ? 0 : 1;
		
		case '15b':
			return ($i == 1 && $v == 0) ? 0 : 1;
		
		case '15c':
			return ($n == 0 || $n == 1) ? 0 : 1;
		
		case '15d':
			return ($i == 0 || $n == 1) ? 0 : 1;
		
		case '15e':
			return ($i == 0 || $i == 1) ? 0 : 1;
		
		case '15f':
			$i10 = $i % 10;
			$f10 = $f % 10;
			
			return ($v == 0 && (($i >= 1 && $i <= 3) || ($i10 != 4 && $i10 != 6 && $i10 != 9) || ($f10 != 4 && $f10 != 6 && $f10 != 9))) ? 0 : 1;
		
		case '15g':
			return ($n == 1 || ($t != 0 && ($i == 0 || $i == 1))) ? 0 : 1;
		
		case '15h':
			return (($t == 0 && $i % 10 == 1 && $i % 100 != 11) || $t != 0) ? 0 : 1;
		
		case '15i':
			return (($v == 0 && $i % 10 == 1) || $f % 10 == 1) ? 0 : 1;
		
		case '15j':
			return ($n == 1 && $v == 0) ? 0 : 1;
		
		case '15k':
			return ($n == 0 || $n == 1 || ($i == 0 && $f == 1)) ? 0 : 1;
		
		case '15l':
			return ($w == 0 && ($n == 0 || $n == 1 || ($n >= 11 && $n <= 99))) ? 0 : 1;
		
		case '015a':
			$n10 = $n % 10;
			$n100 = $n % 100;
			$f10 = $f % 10;
			$f100 = $f % 100;
			
			return ($n10 == 0 || ($w == 0 && $n100 >= 11 && $n100 <= 19) || ($v == 2 && $f100 >= 11 && $f100 <= 19)) ? 0 :
			(
				(($n10 == 1 && $n100 != 11) || ($v == 2 && $f10 == 1 && $f100 != 11) || ($v != 2 && $f10 == 1)) ? 1 : 2
			);
		
		case '015b':
			return $n == 0 ? 0 :
			(
				$n == 1 ? 1 : 2
			);
		
		case '015c':
			return $n == 0 ? 0 :
			(
				(($i == 0 || $i == 1) && $n != 0) ? 1 : 2
			);
		
		case '125a':
			return $n == 1 ? 0 :
			(
				$n == 2 ? 1 : 2
			);
		
		case '135a':
			$i10 = $i % 10;
			$i100 = $i % 100;
			$f10 = $f % 10;
			$f100 = $f % 100;
			
			return (($v == 0 && $i10 == 1 && $i100 != 11) || ($f10 == 1 && $f100 != 11)) ? 0 :
			(
				(($v == 0 && $i10 >= 2 && $i10 <= 4 && ($i100 < 12 || $i100 > 14)) || ($f10 >= 2 && $f10 <= 14 && ($f100 < 12 || $f100 > 14))) ? 1 : 2
			);
		
		case '135b':
			$n100 = $n % 100;
			
			return ($i == 1 && $v == 0) ? 0 :
			(
				($v != 0 || $n == 0 || ($n != 1 && $n100 >= 1 && $n100 <= 19)) ? 1 : 2
			);
		
		case '135c':
			return ($i == 0 || $n == 1) ? 0 :
			(
				($w == 0 && $n >= 2 && $n <= 10) ? 1 : 2
			);
		
		case '1235a':
			$i100 = $i % 100;
			$f100 = $f % 100;
			
			return (($v == 0 && $i100 == 1) || $f100 == 1) ? 0 :
			(
				(($v == 0 && $i100 == 2) || $f100 == 2) ? 1 :
				(
					(($v == 0 && ($i100 == 3 || $i100 == 4)) || $f100 == 3 || $f100 == 4) ? 2 : 3
				)
			);
		
		case '1235b':
			return ($n == 1 || $n == 11) ? 0 :
			(
				($n == 2 || $n == 12) ? 1 :
				(
					($w == 0 && (($n >= 3 && $n <= 10) || ($n >= 13 && $n <= 19))) ? 2 : 3
				)
			);
		
		case '1235c':
			$i100 = $i % 100;
			
			return ($v == 0 && $i100 == 1) ? 0 :
			(
				($v == 0 && $i100 == 2) ? 1 :
				(
					(($v == 0 && ($i100 == 3 || $i100 == 4)) || $v != 0) ? 2 : 3
				)
			);
		
		case '1245a':
			return ($i == 1 && $v == 0) ? 0 :
			(
				($i == 2 && $v == 0) ? 1 :
				(
					($v == 0 && ($n < 0 || $n > 10) && $n % 10 == 0) ? 2 : 3
				)
			);
		
		case '1345a':
			return ($i == 1 && $v == 0) ? 0 :
			(
				($i >= 2 && $i <= 4 && $v == 0) ? 1 :
				(
					$v != 0 ? 2 : 3
				)
			);
		
		case '1345b':
			$i10 = $i & 10;
			$i100 = $i % 100;
			
			return ($v == 0 && $i10 == 1 && $i100 != 11) ? 0 :
			(
				($v == 0 && $i10 >= 2 && $i10 <= 4 && ($i100 < 12 || $i100 > 14)) ? 1 :
				(
					($v == 0 && ($i10 == 0 || ($i10 >= 5 && $i10 <= 9) || ($i100 >= 11 && $i100 <= 14))) ? 2 : 3
				)
			);
		
		case '1345c':
			$n10 = $n % 10;
			$n100 = $n % 100;
			
			return ($n10 == 1 && $n100 != 11) ? 0 :
			(
				($w == 0 && $n10 >= 2 && $n10 <= 4 && ($n100 < 12 || $n100 > 14)) ? 1 :
				(
					($w == 0 && ($n10 == 0 || ($n10 >= 5 && $n10 <= 9) || ($n100 >= 11 && $n100 <= 14))) ? 2 : 3
				)
			);
		
		case '1345d':
			$n10 = $n % 10;
			$n100 = $n % 100;
			
			return ($n10 == 1 && ($n100 < 11 || $n100 > 19)) ? 0 :
			(
				($w == 0 && $n10 >= 2 && $n10 <= 9 && ($n100 < 11 || $n100 > 19)) ? 1 :
				(
					$f != 0 ? 2 : 3
				)
			);
		
		case '1345e':
			$n100 = $n % 100;
			
			return $n == 1 ? 0 :
			(
				($n == 0 || ($w == 0 && $n100 >= 2 && $n100 <= 10)) ? 1 :
				(
					($w == 0 && $n100 >= 11 && $n100 <= 19) ? 2 : 3
				)
			);
		
		case '1345f':
			$i10 = $i % 10;
			$i100 = $i % 100;
			
			return ($i == 1 && $v == 0) ? 0 :
			(
				($v == 0 && $i10 >= 2 && $i10 <= 4 && ($i100 < 12 || $i100 > 14)) ? 1 :
				(
					($v == 0 && (($i != 1 && ($i10 == 0 || $i10 == 1)) || ($i10 >= 5 && $i10 <= 9) || ($i100 >= 12 && $i100 <= 14))) ? 2 : 3
				)
			);
		
		case '12345a':
			$n10 = $n % 10;
			$n100 = $n % 100;
			
			return ($n10 == 1 && $n100 != 11 && $n100 != 71 && $n100 != 91) ? 0 :
			(
				($n10 == 2 && $n100 != 12 && $n100 != 72 && $n100 != 92) ? 1 :
				(
					(($n10 == 3 || $n10 == 4 || $n10 == 9) && ($n100 < 10 || $n100 > 19) && ($n100 < 70 || $n100 > 79) && ($n100 < 90 && $n100 > 99)) ? 2 :
					(
						($n != 0 && $n % 1000000 == 0) ? 3 : 4
					)
				)
			);
		
		case '12345b':
			return $n == 1 ? 0 :
			(
				$n == 2 ? 1 :
				(
					($w == 0 && $n >= 3 && $n <= 6) ? 2 :
					(
						($w == 0 && $n >= 7 && $n <= 10) ? 3 : 4
					)
				)
			);
		
		case '12345c':
			$i10 = $i % 10;
			
			return ($v == 0 && $i10 == 1) ? 0 :
			(
				($v == 0 && $i10 == 2) ? 1 :
				(
					($v == 0 && $i % 20 == 0) ? 2 :
					(
						$v != 0 ? 3 : 4
					)
				)
			);
		
		case '012345a':
			$n100 = $n % 100;
			
			return $n == 0 ? 0 :
			(
				$n == 1 ? 1 :
				(
					$n == 2 ? 2 :
					(
						$w == 0 && $n100 >= 3 && $n100 <= 10 ? 3 :
						(
							$w == 0 && $n100 >= 11 && $n100 <= 99 ? 4 : 5
						)
					)
				)
			);
		
		case '012345b':
			return $n == 0 ? 0 :
			(
				$n == 1 ? 1 :
				(
					$n == 2 ? 2 :
					(
						$n == 3 ? 3 :
						(
							$n == 6 ? 4 : 5
						)
					)
				)
			);
		}
		
		// No matching rule found. Use a generic fallback.
		
		return 0;
	}
	
	public static function getPluralizationModel ($lang)
	{
		$lang = Str::lowerAscii($lang);
		
		while ($lang != '')
		{
			switch ($lang)
			{
			case 'bm':
			case 'bo':
			case 'dz':
			case 'id':
			case 'ig':
			case 'ii':
			case 'ja':
			case 'jbo':
			case 'jv':
			case 'kde':
			case 'kea':
			case 'km':
			case 'ko':
			case 'lkt':
			case 'lo':
			case 'ms':
			case 'my':
			case 'nqo':
			case 'sah':
			case 'ses':
			case 'sg':
			case 'th':
			case 'to':
			case 'vi':
			case 'wo':
			case 'yo':
			case 'zh':
				return '5a';
			
			case 'af':
			case 'asa':
			case 'az':
			case 'bem':
			case 'bez':
			case 'bg':
			case 'brx':
			case 'ce':
			case 'cgg':
			case 'chr':
			case 'ckb':
			case 'dv':
			case 'ee':
			case 'el':
			case 'eo':
			case 'es':
			case 'eu':
			case 'fo':
			case 'fur':
			case 'gsw':
			case 'ha':
			case 'haw':
			case 'hu':
			case 'jgo':
			case 'jmc':
			case 'ka':
			case 'kaj':
			case 'kcg':
			case 'kk':
			case 'kkj':
			case 'kl':
			case 'ks':
			case 'ksb':
			case 'ku':
			case 'ky':
			case 'lb':
			case 'lg':
			case 'mas':
			case 'mgo':
			case 'ml':
			case 'mn':
			case 'nah':
			case 'nd':
			case 'ne':
			case 'nnh':
			case 'nb':
			case 'nn':
			case 'no':
			case 'nr':
			case 'ny':
			case 'nyn':
			case 'om':
			case 'or':
			case 'os':
			case 'pap':
			case 'ps':
			case 'rm':
			case 'rof':
			case 'rwk':
			case 'saq':
			case 'seh':
			case 'sn':
			case 'so':
			case 'sq':
			case 'ss':
			case 'ssy':
			case 'st':
			case 'syr':
			case 'ta':
			case 'te':
			case 'teo':
			case 'tig':
			case 'tk':
			case 'tn':
			case 'tr':
			case 'ts':
			case 'ug':
			case 'uz':
			case 've':
			case 'vo':
			case 'vun':
			case 'wae':
			case 'xh':
			case 'xog':
				return '15a';
			
			case 'ast':
			case 'ca':
			case 'de':
			case 'en':
			case 'et':
			case 'fi':
			case 'fy':
			case 'gl':
			case 'it':
			case 'ji':
			case 'nl':
			case 'sv':
			case 'sw':
			case 'ur':
			case 'yi':
				return '15b';
			
			case 'ak':
			case 'bh':
			case 'fa':
			case 'guw':
			case 'ln':
			case 'mg':
			case 'nso':
			case 'pa':
			case 'pt':
			case 'ti':
			case 'wa':
				return '15c';
			
			case 'am':
			case 'as':
			case 'bn':
			case 'gu':
			case 'hi':
			case 'kn':
			case 'mr':
			case 'zu':
				return '15d';
			
			case 'ff':
			case 'fr':
			case 'hy':
			case 'kab':
				return '15e';
			
			case 'fil':
			case 'tl':
				return '15f';
			
			case 'da':
				return '15g';
			
			case 'is':
				return '15h';
			
			case 'mk':
				return '15i';
			
			case 'pt_pt':
				return '15j';
			
			case 'si':
				return '15k';
			
			case 'tzm':
				return '15l';
			
			case 'lv':
			case 'prg':
				return '015a';
			
			case 'ksh':
				return '015b';
			
			case 'lag':
				return '015c';
			
			case 'iu':
			case 'kw':
			case 'naq':
			case 'sma':
			case 'smi':
			case 'smj':
			case 'smn':
			case 'sms':
			case 'se':
				return '125a';
			
			case 'bs':
			case 'hr':
			case 'sh':
			case 'sr':
				return '135a';
			
			case 'mo':
			case 'ro':
				return '135b';
			
			case 'shi':
				return '135c';
			
			case 'dsb':
			case 'hsb':
				return '1235a';
			
			case 'gd':
				return '1235b';
			
			case 'sl':
				return '1235c';
			
			case 'he':
				return '1245a';
			
			case 'cs':
			case 'sk':
				return '1345a';
			
			case 'ru':
			case 'uk':
				return '1345b';
			
			case 'be':
				return '1345c';
			
			case 'lt':
				return '1345d';
			
			case 'mt':
				return '1345e';
			
			case 'pl':
				return '1345f';
			
			case 'br':
				return '12345a';
			
			case 'ga':
				return '12345b';
			
			case 'gv':
				return '12345c';
			
			case 'ar':
				return '012345a';
			
			case 'cy':
				return '012345b';
			
			default:
				// Retry with a more generic language code.
				
				$lang = substr($lang, 0, strrpos($lang, '_'));
			}
		}
		
		return '5a';
	}
}
