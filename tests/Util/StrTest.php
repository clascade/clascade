<?php

namespace Clascade\Util;

class StrTest extends \PHPUnit_Framework_TestCase
{
	//== Str::length ==//
	
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
			'' => 0,
			'foo' => 3,
			"\x00" => 1,
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
			// First valid (non-overlong) codepoints for each sequence length.
			
			"A\x00A" => 3,
			"A\xc2\x80A" => 3,
			"A\xe0\xa0\x80A" => 3,
			"A\xf0\x90\x80\x80A" => 3,
			
			// Last valid codepoints for each sequence length.
			
			"A\x7fA" => 3,
			"A\xdf\xbfA" => 3,
			"A\xef\xbf\xbfA" => 3,
			"A\xf4\x8f\xbf\xbfA" => 3,
			
			// Surrogate boundaries.
			
			"A\xed\x9f\xbfA" => 3,
			"A\xee\x80\x80A" => 3,
		]);
	}
	

	public function testLength_invalidCodepoints ()
	{
		$this->assertStrLengths(
		[
			// First codepoint above maximum (detectable at second byte).
			
			"A\xf4\x90\x80\x80A" => 6,
			
			// First codepoint above maximum that is detectable at first byte.
			
			"A\xf5\x80\x80\x80A" => 6,
			
			// Invalid sequence lengths.
			
			"A\xf8\x88\x80\x80\x80A" => 7,
			"A\xfc\x81\x80\x80\x80\x80A" => 8,
			
			// Surrogate boundaries (detectable at second byte).
			
			"A\xed\xa0\x80A" => 5,
			"A\xed\xbf\xbfA" => 5,
		]);
	}
	
	public function testLength_overlong ()
	{
		$this->assertStrLengths(
		[
			// Codepoint 0 encoded in each multibyte length (overlong).
			
			"A\xc0\x80A" => 4,
			"A\xe0\x80\x80A" => 5,
			"A\xf0\x80\x80\x80A" => 6,
			
			// Last overlong sequence of each length.
			
			"A\xc1\xbfA" => 4,
			"A\xe0\x9f\xbfA" => 5,
			"A\xf0\x8f\xbf\xbfA" => 6,
		]);
	}
	
	public function testLength_malformed ()
	{
		$this->assertStrLengths(
		[
			// Unexpected continuation bytes.
			
			"A\x80A" => 3,
			"A\x80\x80A" => 4,
			"A\x80\x80\x80A" => 5,
			"A\x80\x80\x80\x80A" => 6,
			"A\xbfA" => 3,
			"A\xbf\xbfA" => 4,
			"A\xbf\xbf\xbfA" => 5,
			"A\xbf\xbf\xbf\xbfA" => 6,
			
			// Missing continuation bytes.
			
			"A\xc2A" => 3,
			"A\xe0\xa0A" => 3,
			"A\xe0A" => 3,
			"A\xf0\x90\x80A" => 3,
			"A\xf0\x90A" => 3,
			"A\xf0A" => 3,
			
			// Bytes that cannot exist in valid UTF-8.
			
			"A\xfeA" => 3,
			"A\xffA" => 3,
			"A\xfe\xfe\xff\xffA" => 6,
		]);
	}
	
	public function testLength_truncated ()
	{
		$this->assertStrLengths(
		[
			// Sequences that are valid up to the point of truncation.
			
			"A\xc2" => 2,
			"A\xe0\xa0" => 2,
			"A\xe0" => 2,
			"A\xf0\x90\x80" => 2,
			"A\xf0\x90" => 2,
			"A\xf0" => 2,
			
			// Truncated overlong (invalid) sequences.
			
			"A\xe0\x80" => 3,
			"A\xe0" => 2,
			"A\xf0\x80\x80" => 4,
			"A\xf0\x80" => 3,
			"A\xf0" => 2,
		]);
	}
	
	//== Str::slice ==//
	
	public function testSlice_ascii ()
	{
		$this->assertEquals('', Str::slice('', 0));
		$this->assertEquals(false, Str::slice('', 1));
		$this->assertEquals(false, Str::slice('', -1));
		
		$this->assertEquals('abc', Str::slice('abc', 0));
		$this->assertEquals('bc', Str::slice('abc', 1));
		$this->assertEquals('c', Str::slice('abc', 2));
		$this->assertEquals('', Str::slice('abc', 3));
		$this->assertEquals(false, Str::slice('abc', 4));
		
		$this->assertEquals('c', Str::slice('abc', -1));
		$this->assertEquals('bc', Str::slice('abc', -2));
		$this->assertEquals('abc', Str::slice('abc', -3));
		$this->assertEquals(false, Str::slice('abc', -4));
		
		$this->assertEquals('ab', Str::slice('abc', 0, -1));
		$this->assertEquals('a', Str::slice('abc', 0, -2));
		$this->assertEquals('', Str::slice('abc', 0, -3));
		$this->assertEquals(false, Str::slice('abc', 0, -4));
		
		$this->assertEquals('b', Str::slice('abc', 1, -1));
		$this->assertEquals('', Str::slice('abc', 1, -2));
		$this->assertEquals(false, Str::slice('abc', 1, -3));
		
		$this->assertEquals('', Str::slice('abc', -1, -1));
		$this->assertEquals('', Str::slice('abc', -1, -2));
		$this->assertEquals('b', Str::slice('abc', -2, -1));
		$this->assertEquals('', Str::slice('abc', -2, -2));
		$this->assertEquals('', Str::slice('abc', -2, -3));
		$this->assertEquals('ab', Str::slice('abc', -3, -1));
		$this->assertEquals('a', Str::slice('abc', -3, -2));
		$this->assertEquals('', Str::slice('abc', -3, -3));
		$this->assertEquals('', Str::slice('abc', -3, -4));
		
		$this->assertEquals('b', Str::slice('abc', 1, 1));
		$this->assertEquals('c', Str::slice('abc', 2, 1));
		$this->assertEquals('c', Str::slice('abc', 2, 2));
	}
	
	public function testSlice_multibyte ()
	{
		$this->assertEquals("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 0));
		$this->assertEquals("\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 1));
		$this->assertEquals("\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2));
		$this->assertEquals("\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 3));
		$this->assertEquals("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 4));
		$this->assertEquals(false, Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 5));
		
		$this->assertEquals("\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -1));
		$this->assertEquals("\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -2));
		$this->assertEquals("\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -3));
		$this->assertEquals("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -4));
		$this->assertEquals(false, Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -5));
		
		$this->assertEquals("A\xc6\x81\xe1\x8f\xa8", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 0, -1));
		$this->assertEquals("A\xc6\x81", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 0, -2));
		$this->assertEquals("A", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 0, -3));
		$this->assertEquals("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 0, -4));
		$this->assertEquals(false, Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 0, -5));
		
		$this->assertEquals("\xc6\x81\xe1\x8f\xa8", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 1, -1));
		$this->assertEquals("\xc6\x81", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 1, -2));
		$this->assertEquals("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 1, -3));
		$this->assertEquals(false, Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 1, -4));
		
		$this->assertEquals("\xe1\x8f\xa8", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2, -1));
		$this->assertEquals("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2, -2));
		$this->assertEquals(false, Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2, -3));
		
		$this->assertEquals("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -1, -1));
		$this->assertEquals("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -1, -2));
		
		$this->assertEquals("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -1, -1));
		$this->assertEquals("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -1, -2));
		$this->assertEquals("\xe1\x8f\xa8", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -2, -1));
		$this->assertEquals("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -2, -2));
		$this->assertEquals("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -2, -3));
		$this->assertEquals("A\xc6\x81\xe1\x8f\xa8", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -4, -1));
		$this->assertEquals("A\xc6\x81", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -4, -2));
		$this->assertEquals("A", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -4, -3));
		$this->assertEquals("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -4, -4));
		$this->assertEquals("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -4, -5));
		
		$this->assertEquals("\xc6\x81", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 1, 1));
		$this->assertEquals("\xe1\x8f\xa8", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2, 1));
		$this->assertEquals("\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2, 2));
		$this->assertEquals("\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2, 3));
		$this->assertEquals("\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 3, 1));
		$this->assertEquals("\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 3, 2));
	}
	
	public function testSlice_invalid ()
	{
		$this->assertEquals("B", Str::slice("A\x80\x80\x80BA", 4, 1));
		$this->assertEquals("B", Str::slice("A\xf0\x90\x80BA", 2, 1));
		
		// The following assertion demonstrates the current
		// expected behavior when an invalid UTF-8 sequence
		// appears within a slice: the invalid sequence is
		// included in the return value unmodified. The
		// correctness of this behavior is debatable: one could
		// argue that the invalid sequence ought to be replaced
		// by a Unicode replacement character (0xFFFD) instead.
		
		$this->assertEquals("\xf0\x90\x80", Str::slice("A\xf0\x90\x80BA", 1, 1));
	}
}
