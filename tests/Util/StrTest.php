<?php

namespace Clascade\Util;

class StrTest extends \PHPUnit_Framework_TestCase
{
	private function assertStrLengths ($tests)
	{
		foreach ($tests as $string => $length)
		{
			$message = 'Input: "'.preg_replace('/../', '\\x$0', bin2hex($string)).'"';
			$this->assertEquals($length, Str::length($string), $message);
		}
	}
	
	public function testLength_smoke ()
	{
		$this->assertStrLengths(
		[
			'foo' => 3,
			"\x00\x00" => 2,
			"\xc6\x92\xc5\x8d\xc5\x8d" => 3, // "ƒōō"
			
			// "いろはにほへとちりぬるを わかよたれそつねならむ うゐのおくやまけふこえて あさきゆめみしゑひもせす"
			"\xe3\x81\x84\xe3\x82\x8d\xe3\x81\xaf\xe3\x81\xab".
			"\xe3\x81\xbb\xe3\x81\xb8\xe3\x81\xa8\xe3\x81\xa1".
			"\xe3\x82\x8a\xe3\x81\xac\xe3\x82\x8b\xe3\x82\x92".
			"\x20\xe3\x82\x8f\xe3\x81\x8b\xe3\x82\x88\xe3\x81".
			"\x9f\xe3\x82\x8c\xe3\x81\x9d\xe3\x81\xa4\xe3\x81".
			"\xad\xe3\x81\xaa\xe3\x82\x89\xe3\x82\x80\x20\xe3".
			"\x81\x86\xe3\x82\x90\xe3\x81\xae\xe3\x81\x8a\xe3".
			"\x81\x8f\xe3\x82\x84\xe3\x81\xbe\xe3\x81\x91\xe3".
			"\x81\xb5\xe3\x81\x93\xe3\x81\x88\xe3\x81\xa6\x20".
			"\xe3\x81\x82\xe3\x81\x95\xe3\x81\x8d\xe3\x82\x86".
			"\xe3\x82\x81\xe3\x81\xbf\xe3\x81\x97\xe3\x82\x91".
			"\xe3\x81\xb2\xe3\x82\x82\xe3\x81\x9b\xe3\x81\x99" => 50,
			
			// High chars.
			
			"\xf4\x8f\xbf\xbb\xf4\x8f\xbf\xbc\xf4\x8f\xbf\xbd\xf4\x8f\xbf\xbe\xf4\x8f\xbf\xbf" => 5,
			
			// Best-practice handling of invalid data (examples from the Unicode 8.0.0 specification, sections 5.22 and 3.9).
			
			"\xf4\x80\x80\x41" => 2,
			"\x41\xc0\xaf\x41\xf4\x80\x80\x41" => 6,
			"\x41\xe0\x9f\x80\x41" => 5,
		]);
	}
	
	public function testLength_validCodepoints ()
	{
		$this->assertStrLengths(
		[
			// First valid codepoints for each sequence length, surrounded by "A".
			
			"\x41\x00\x41" => 3,
			"\x41\xc2\x80\x41" => 3,
			"\x41\xe0\xa0\x80\x41" => 3,
			"\x41\xf0\x90\x80\x80\x41" => 3,
			
			// Last valid codepoints for each sequence length, surrounded by "A".
			
			"\x41\x7f\x41" => 3,
			"\x41\xdf\xbf\x41" => 3,
			"\x41\xef\xbf\xbf\x41" => 3,
			"\x41\xf4\x8f\xbf\xbf\x41" => 3,
			
			// Surrogate boundaries.
			
			"\x41\xed\x9f\xbf\x41" => 3,
			"\x41\xee\x80\x80\x41" => 3,
		]);
	}
	
	public function testLength_invalidCodepoints ()
	{
		$this->assertStrLengths(
		[
			// Above the maximum codepoint.
			
			"\x41\xf4\x90\x80\x80\x41" => 6,
		]);
	}
	
	public function testLength_overlong ()
	{
		$this->assertStrLengths(
		[
		]);
	}
}
