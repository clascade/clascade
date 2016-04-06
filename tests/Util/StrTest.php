<?php

namespace Clascade\Util;

class StrTest extends \PHPUnit_Framework_TestCase
{
	private function assertStrLengths ($tests)
	{
		foreach ($tests as $string => $length)
		{
			$message = 'Input: "'.preg_replace('/../', '\\x$0', bin2hex($string)).'"';
			$this->assertSame($length, Str::length($string), $message);
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
			"A\xf0\x80\x80" => 4,
			"A\xf0\x80" => 3,
		]);
	}
	
	public function testSlice_ascii ()
	{
		$this->assertSame(false, Str::slice('', 0));
		$this->assertSame(false, Str::slice('', 1));
		$this->assertSame(false, Str::slice('', -1));
		
		$this->assertSame('abc', Str::slice('abc', 0));
		$this->assertSame('bc', Str::slice('abc', 1));
		$this->assertSame('c', Str::slice('abc', 2));
		$this->assertSame(false, Str::slice('abc', 3));
		
		$this->assertSame('c', Str::slice('abc', -1));
		$this->assertSame('bc', Str::slice('abc', -2));
		$this->assertSame('abc', Str::slice('abc', -3));
		$this->assertSame(false, Str::slice('abc', -4));
		
		$this->assertSame('ab', Str::slice('abc', 0, -1));
		$this->assertSame('a', Str::slice('abc', 0, -2));
		$this->assertSame('', Str::slice('abc', 0, -3));
		$this->assertSame(false, Str::slice('abc', 0, -4));
		
		$this->assertSame('b', Str::slice('abc', 1, -1));
		$this->assertSame('', Str::slice('abc', 1, -2));
		$this->assertSame(false, Str::slice('abc', 1, -3));
		
		$this->assertSame('', Str::slice('abc', -1, -1));
		$this->assertSame('', Str::slice('abc', -1, -2));
		$this->assertSame('b', Str::slice('abc', -2, -1));
		$this->assertSame('', Str::slice('abc', -2, -2));
		$this->assertSame('', Str::slice('abc', -2, -3));
		$this->assertSame('ab', Str::slice('abc', -3, -1));
		$this->assertSame('a', Str::slice('abc', -3, -2));
		$this->assertSame('', Str::slice('abc', -3, -3));
		$this->assertSame(false, Str::slice('abc', -3, -4));
		
		$this->assertSame('b', Str::slice('abc', 1, 1));
		$this->assertSame('c', Str::slice('abc', 2, 1));
		$this->assertSame('c', Str::slice('abc', 2, 2));
	}
	
	public function testSlice_multibyte ()
	{
		$this->assertSame("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 0));
		$this->assertSame("\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 1));
		$this->assertSame("\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2));
		$this->assertSame("\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 3));
		$this->assertSame(false, Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 4));
		
		$this->assertSame("\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -1));
		$this->assertSame("\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -2));
		$this->assertSame("\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -3));
		$this->assertSame("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -4));
		$this->assertSame(false, Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -5));
		
		$this->assertSame("A\xc6\x81\xe1\x8f\xa8", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 0, -1));
		$this->assertSame("A\xc6\x81", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 0, -2));
		$this->assertSame("A", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 0, -3));
		$this->assertSame("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 0, -4));
		$this->assertSame(false, Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 0, -5));
		
		$this->assertSame("\xc6\x81\xe1\x8f\xa8", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 1, -1));
		$this->assertSame("\xc6\x81", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 1, -2));
		$this->assertSame("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 1, -3));
		$this->assertSame(false, Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 1, -4));
		
		$this->assertSame("\xe1\x8f\xa8", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2, -1));
		$this->assertSame("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2, -2));
		$this->assertSame(false, Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2, -3));
		
		$this->assertSame("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -1, -1));
		$this->assertSame("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -1, -2));
		$this->assertSame("\xe1\x8f\xa8", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -2, -1));
		$this->assertSame("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -2, -2));
		$this->assertSame("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -2, -3));
		$this->assertSame("A\xc6\x81\xe1\x8f\xa8", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -4, -1));
		$this->assertSame("A\xc6\x81", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -4, -2));
		$this->assertSame("A", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -4, -3));
		$this->assertSame("", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -4, -4));
		$this->assertSame(false, Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -4, -5));
		
		$this->assertSame("\xc6\x81", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 1, 1));
		$this->assertSame("\xe1\x8f\xa8", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2, 1));
		$this->assertSame("\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2, 2));
		$this->assertSame("\xe1\x8f\xa8\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2, 3));
		$this->assertSame("\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 3, 1));
		$this->assertSame("\xf0\x90\x8c\x83", Str::slice("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 3, 2));
	}
	
	public function testSlice_invalid ()
	{
		$this->assertSame("B", Str::slice("A\x80\x80\x80BA", 4, 1));
		$this->assertSame("B", Str::slice("A\xf0\x90\x80BA", 2, 1));
		$this->assertSame("B", Str::slice("AB\xf0\x90\x80A", -3, 1));
		
		// The following assertions demonstrate the current
		// expected behavior when an invalid UTF-8 sequence
		// appears within a slice: the invalid sequence is
		// included in the return value unmodified. The
		// correctness of this behavior is debatable: one could
		// argue that the invalid sequence ought to be replaced
		// by a Unicode replacement character (0xFFFD) instead.
		
		$this->assertSame("\xf0\x90\x80", Str::slice("A\xf0\x90\x80A", 1, 1));
		$this->assertSame("\xf0\x90\x80", Str::slice("A\xf0\x90\x80A", -2, 1));
		
		$this->assertSame("\xf0", Str::slice("A\xf0\x8f\xbf\xbfA", 1, 1));
		$this->assertSame("\x8f", Str::slice("A\xf0\x8f\xbf\xbfA", 2, 1));
		$this->assertSame("\xbf", Str::slice("A\xf0\x8f\xbf\xbfA", 3, 1));
		$this->assertSame("\xbf", Str::slice("A\xf0\x8f\xbf\xbfA", 4, 1));
	}
	
	public function testCharAt ()
	{
		$this->assertSame("A", Str::charAt("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 0));
		$this->assertSame("\xc6\x81", Str::charAt("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 1));
		$this->assertSame("\xe1\x8f\xa8", Str::charAt("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2));
		$this->assertSame("\xf0\x90\x8c\x83", Str::charAt("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 3));
		$this->assertSame(false, Str::charAt("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 4));
		
		$this->assertSame("\xf0\x90\x8c\x83", Str::charAt("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -1));
		$this->assertSame("\xe1\x8f\xa8", Str::charAt("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -2));
		$this->assertSame("\xc6\x81", Str::charAt("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -3));
		$this->assertSame("A", Str::charAt("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -4));
		$this->assertSame(false, Str::charAt("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", -5));
		
		// Invalid UTF-8 sequences.
		
		$this->assertSame("B", Str::charAt("A\x80\x80\x80BA", 4));
		$this->assertSame("B", Str::charAt("A\xf0\x90\x80BA", 2));
		$this->assertSame("B", Str::charAt("AB\xf0\x90\x80A", -3, 1));
		$this->assertSame("\xf0\x90\x80", Str::charAt("A\xf0\x90\x80A", 1));
		$this->assertSame("\xf0\x90\x80", Str::charAt("A\xf0\x90\x80A", -2));
		
		$this->assertSame("\xf0", Str::charAt("A\xf0\x8f\xbf\xbfA", 1));
		$this->assertSame("\x8f", Str::charAt("A\xf0\x8f\xbf\xbfA", 2));
		$this->assertSame("\xbf", Str::charAt("A\xf0\x8f\xbf\xbfA", 3));
		$this->assertSame("\xbf", Str::charAt("A\xf0\x8f\xbf\xbfA", 4));
	}
	
	public function testIndexOf ()
	{
		$this->assertSame(0, Str::indexOf("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "A"));
		$this->assertSame(1, Str::indexOf("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\xc6\x81"));
		$this->assertSame(2, Str::indexOf("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\xe1\x8f\xa8"));
		$this->assertSame(3, Str::indexOf("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\xf0\x90\x8c\x83"));
		$this->assertSame(false, Str::indexOf("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "B"));
		
		$this->assertSame(1, Str::indexOf("A\xc6\x81A\xc6\x81A", "\xc6\x81"));
		
		// Invalid needles (beginning with a continuation byte).
		
		$this->assertSame(false, Str::indexOf("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\x8f\xa8"));
		
		// Invalid UTF-8 sequences in the haystacks.
		
		$this->assertSame(4, Str::indexOf("A\x80\x80\x80BA", "B"));
		$this->assertSame(2, Str::indexOf("A\xf0\x90\x80BA", "B"));
		$this->assertSame(1, Str::indexOf("A\xf0\x90\x80BA", "\xf0\x90\x80"));
	}
	
	public function testLastIndexOf ()
	{
		$this->assertSame(0, Str::lastIndexOf("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "A"));
		$this->assertSame(1, Str::lastIndexOf("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\xc6\x81"));
		$this->assertSame(2, Str::lastIndexOf("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\xe1\x8f\xa8"));
		$this->assertSame(3, Str::lastIndexOf("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\xf0\x90\x8c\x83"));
		$this->assertSame(false, Str::lastIndexOf("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "B"));
		
		$this->assertSame(3, Str::lastIndexOf("A\xc6\x81A\xc6\x81A", "\xc6\x81"));
		
		// Invalid needles (beginning with a continuation byte).
		
		$this->assertSame(false, Str::lastIndexOf("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\x8f\xa8"));
		
		// Invalid UTF-8 sequences in the haystacks.
		
		$this->assertSame(8, Str::lastIndexOf("A\x80\x80\x80B\x80\x80\x80B\x80\x80\x80A", "B"));
		$this->assertSame(4, Str::lastIndexOf("A\xf0\x90\x80B\xf0\x90\x80B\xf0\x90\x80A", "B"));
		$this->assertSame(3, Str::lastIndexOf("A\xf0\x90\x80A\xf0\x90\x80A", "\xf0\x90\x80"));
	}
	
	public function testContains ()
	{
		$this->assertSame(true, Str::contains("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "A"));
		$this->assertSame(true, Str::contains("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\xc6\x81"));
		$this->assertSame(true, Str::contains("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\xe1\x8f\xa8"));
		$this->assertSame(true, Str::contains("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\xf0\x90\x8c\x83"));
		$this->assertSame(false, Str::contains("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "B"));
		
		// Str::contains should be safe for byte strings and
		// shouldn't care about character boundaries, so needles
		// can be anything (unlike with Str::indexOf()).
		
		$this->assertSame(true, Str::contains("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\x8f\xa8"));
		
		// Invalid UTF-8 sequences in the haystacks.
		
		$this->assertSame(true, Str::contains("A\x80\x80\x80BA", "B"));
		$this->assertSame(true, Str::contains("A\xf0\x90\x80BA", "B"));
		$this->assertSame(true, Str::contains("A\xf0\x90\x80BA", "\xf0\x90\x80"));
	}
	
	public function testStartsWith ()
	{
		$this->assertSame(true, Str::startsWith("", ""));
		$this->assertSame(true, Str::startsWith("A", ""));
		$this->assertSame(false, Str::startsWith("", "A"));
		$this->assertSame(true, Str::startsWith("A", "A"));
		
		$this->assertSame(true, Str::startsWith("AB", "A"));
		$this->assertSame(false, Str::startsWith("BA", "A"));
		
		$this->assertSame(true, Str::startsWith("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "A"));
		$this->assertSame(true, Str::startsWith("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "A\xc6\x81"));
		$this->assertSame(true, Str::startsWith("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "A\xc6"));
		$this->assertSame(true, Str::startsWith("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83"));
		$this->assertSame(false, Str::startsWith("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83A"));
		$this->assertSame(true, Str::startsWith("\x80A", "\x80"));
		$this->assertSame(true, Str::startsWith("\x80A", "\x80A"));
		$this->assertSame(false, Str::startsWith("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\xc6\x81"));
		$this->assertSame(false, Str::startsWith("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "B"));
		$this->assertSame(false, Str::startsWith("A\xc6\x81B", "B"));
	}
	
	public function testEndsWith ()
	{
		$this->assertSame(true, Str::endsWith("", ""));
		$this->assertSame(true, Str::endsWith("A", ""));
		$this->assertSame(false, Str::endsWith("", "A"));
		$this->assertSame(true, Str::endsWith("A", "A"));
		
		$this->assertSame(true, Str::endsWith("AB", "B"));
		$this->assertSame(false, Str::endsWith("BA", "B"));
		
		$this->assertSame(true, Str::endsWith("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\xf0\x90\x8c\x83"));
		$this->assertSame(true, Str::endsWith("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\x8f\xa8\xf0\x90\x8c\x83"));
		$this->assertSame(true, Str::endsWith("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\x8c\x83"));
		$this->assertSame(true, Str::endsWith("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83"));
		$this->assertSame(false, Str::endsWith("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "AA\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83"));
		$this->assertSame(true, Str::endsWith("A\x80", "\x80"));
		$this->assertSame(true, Str::endsWith("A\x80", "A\x80"));
		$this->assertSame(false, Str::endsWith("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "\x90\x8c"));
		$this->assertSame(false, Str::endsWith("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "B"));
		$this->assertSame(false, Str::endsWith("A\xc6\x81B", "A"));
	}
	
	public function testChunk_smoke ()
	{
		$this->assertSame([], Str::chunk(''));
		$this->assertSame(['A'], Str::chunk('A'));
		$this->assertSame(['A', 'B'], Str::chunk('AB'));
		$this->assertSame(['AB'], Str::chunk('AB', 2));
		$this->assertSame(['AB'], Str::chunk('AB', 3));
		$this->assertSame(['AB', 'C'], Str::chunk('ABC', 2));
		$this->assertSame(['AB', 'CD', 'EF'], Str::chunk('ABCDEF', 2));
		$this->assertSame(false, Str::chunk('AB', 0));
		$this->assertSame(false, Str::chunk('AB', -1));
		
		$this->assertSame(["A", "\xc6\x81", "\xe1\x8f\xa8", "\xf0\x90\x8c\x83"], Str::chunk("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83"));
		$this->assertSame(["A\xc6\x81", "\xe1\x8f\xa8\xf0\x90\x8c\x83"], Str::chunk("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 2));
		$this->assertSame(["A\xc6\x81\xe1\x8f\xa8", "\xf0\x90\x8c\x83"], Str::chunk("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 3));
		$this->assertSame(["A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83"], Str::chunk("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 4));
		$this->assertSame(["A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83"], Str::chunk("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", 5));
	}
	
	public function testChunk_validCodepoints ()
	{
		// First valid (non-overlong) codepoints for each sequence length.
		
		$this->assertSame(["A", "\x00", "A"], Str::chunk("A\x00A"));
		$this->assertSame(["A", "\xc2\x80", "A"], Str::chunk("A\xc2\x80A"));
		$this->assertSame(["A", "\xe0\xa0\x80", "A"], Str::chunk("A\xe0\xa0\x80A"));
		$this->assertSame(["A", "\xf0\x90\x80\x80", "A"], Str::chunk("A\xf0\x90\x80\x80A"));
		
		// Last valid codepoints for each sequence length.
		
		$this->assertSame(["A", "\x7f", "A"], Str::chunk("A\x7fA"));
		$this->assertSame(["A", "\xdf\xbf", "A"], Str::chunk("A\xdf\xbfA"));
		$this->assertSame(["A", "\xef\xbf\xbf", "A"], Str::chunk("A\xef\xbf\xbfA"));
		$this->assertSame(["A", "\xf4\x8f\xbf\xbf", "A"], Str::chunk("A\xf4\x8f\xbf\xbfA"));
		
		// Surrogate boundaries.
		
		$this->assertSame(["A", "\xed\x9f\xbf", "A"], Str::chunk("A\xed\x9f\xbfA"));
		$this->assertSame(["A", "\xee\x80\x80", "A"], Str::chunk("A\xee\x80\x80A"));
	}
	
	public function testChunk_invalidCodepoints ()
	{
		// First codepoint above maximum (detectable at second byte).
		
		$this->assertSame(["A", "\xf4", "\x90", "\x80", "\x80", "A"], Str::chunk("A\xf4\x90\x80\x80A"));
		
		// First codepoint above maximum that is detectable at first byte.
		
		$this->assertSame(["A", "\xf5", "\x80", "\x80", "\x80", "A"], Str::chunk("A\xf5\x80\x80\x80A"));
		
		// Invalid sequence lengths.
		
		$this->assertSame(["A", "\xf8", "\x88", "\x80", "\x80", "\x80", "A"], Str::chunk("A\xf8\x88\x80\x80\x80A"));
		$this->assertSame(["A", "\xfc", "\x81", "\x80", "\x80", "\x80", "\x80", "A"], Str::chunk("A\xfc\x81\x80\x80\x80\x80A"));
		
		// Surrogate boundaries (detectable at second byte).
		
		$this->assertSame(["A", "\xed", "\xa0", "\x80", "A"], Str::chunk("A\xed\xa0\x80A"));
		$this->assertSame(["A", "\xed", "\xbf", "\xbf", "A"], Str::chunk("A\xed\xbf\xbfA"));
	}
	
	public function testChunk_overlong ()
	{
		// Codepoint 0 encoded in each multibyte length (overlong).
		
		$this->assertSame(["A", "\xc0", "\x80", "A"], Str::chunk("A\xc0\x80A"));
		$this->assertSame(["A", "\xe0", "\x80", "\x80", "A"], Str::chunk("A\xe0\x80\x80A"));
		$this->assertSame(["A", "\xf0", "\x80", "\x80", "\x80", "A"], Str::chunk("A\xf0\x80\x80\x80A"));
		
		// Last overlong sequence of each length.
		
		$this->assertSame(["A", "\xc1", "\xbf", "A"], Str::chunk("A\xc1\xbfA"));
		$this->assertSame(["A", "\xe0", "\x9f", "\xbf", "A"], Str::chunk("A\xe0\x9f\xbfA"));
		$this->assertSame(["A", "\xf0", "\x8f", "\xbf", "\xbf", "A"], Str::chunk("A\xf0\x8f\xbf\xbfA"));
	}
	
	public function testChunk_malformed ()
	{
		// Unexpected continuation bytes.
		
		$this->assertSame(["A", "\x80", "A"], Str::chunk("A\x80A"));
		$this->assertSame(["A", "\x80", "\x80", "A"], Str::chunk("A\x80\x80A"));
		$this->assertSame(["A", "\x80", "\x80", "\x80", "A"], Str::chunk("A\x80\x80\x80A"));
		$this->assertSame(["A", "\x80", "\x80", "\x80", "\x80", "A"], Str::chunk("A\x80\x80\x80\x80A"));
		$this->assertSame(["A", "\xbf", "A"], Str::chunk("A\xbfA"));
		$this->assertSame(["A", "\xbf", "\xbf", "A"], Str::chunk("A\xbf\xbfA"));
		$this->assertSame(["A", "\xbf", "\xbf", "\xbf", "A"], Str::chunk("A\xbf\xbf\xbfA"));
		$this->assertSame(["A", "\xbf", "\xbf", "\xbf", "\xbf", "A"], Str::chunk("A\xbf\xbf\xbf\xbfA"));
		
		// Missing continuation bytes.
		
		$this->assertSame(["A", "\xc2", "A"], Str::chunk("A\xc2A"));
		$this->assertSame(["A", "\xe0\xa0", "A"], Str::chunk("A\xe0\xa0A"));
		$this->assertSame(["A", "\xe0", "A"], Str::chunk("A\xe0A"));
		$this->assertSame(["A", "\xf0\x90\x80", "A"], Str::chunk("A\xf0\x90\x80A"));
		$this->assertSame(["A", "\xf0\x90", "A"], Str::chunk("A\xf0\x90A"));
		$this->assertSame(["A", "\xf0", "A"], Str::chunk("A\xf0A"));
		
		// Bytes that cannot exist in valid UTF-8.
		
		$this->assertSame(["A", "\xfe", "A"], Str::chunk("A\xfeA"));
		$this->assertSame(["A", "\xff", "A"], Str::chunk("A\xffA"));
		$this->assertSame(["A", "\xfe", "\xfe", "\xff", "\xff", "A"], Str::chunk("A\xfe\xfe\xff\xffA"));
	}
	
	public function testChunk_truncated ()
	{
		// Sequences that are valid up to the point of truncation.
		
		$this->assertSame(["A", "\xc2"], Str::chunk("A\xc2"));
		$this->assertSame(["A", "\xe0\xa0"], Str::chunk("A\xe0\xa0"));
		$this->assertSame(["A", "\xe0"], Str::chunk("A\xe0"));
		$this->assertSame(["A", "\xf0\x90\x80"], Str::chunk("A\xf0\x90\x80"));
		$this->assertSame(["A", "\xf0\x90"], Str::chunk("A\xf0\x90"));
		$this->assertSame(["A", "\xf0"], Str::chunk("A\xf0"));
		
		// Truncated overlong (invalid) sequences.
		
		$this->assertSame(["A", "\xe0", "\x80"], Str::chunk("A\xe0\x80"));
		$this->assertSame(["A", "\xf0", "\x80", "\x80"], Str::chunk("A\xf0\x80\x80"));
		$this->assertSame(["A", "\xf0", "\x80"], Str::chunk("A\xf0\x80"));
	}
	
	public function testApply ()
	{
		$results = [];
		Str::apply("", function ($char, $char_pos, $byte_pos, $sequence_length) use (&$results)
		{
			$results[] = [$char, $char_pos, $byte_pos, $sequence_length];
		});
		
		$this->assertSame([], $results);
		
		$results = [];
		Str::apply("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", function ($char, $char_pos, $byte_pos, $sequence_length) use (&$results)
		{
			$results[] = [$char, $char_pos, $byte_pos, $sequence_length];
		});
		
		$this->assertSame(
		[
			["A", 0, 0, 1],
			["\xc6\x81", 1, 1, 2],
			["\xe1\x8f\xa8", 2, 3, 3],
			["\xf0\x90\x8c\x83", 3, 6, 4],
		], $results);
		
		$results = [];
		Str::apply("A\xf0\x90\x80A\xf0\x8f\xbf\xbfA", function ($char, $char_pos, $byte_pos, $sequence_length) use (&$results)
		{
			$results[] = [$char, $char_pos, $byte_pos, $sequence_length];
		});
		
		$this->assertSame(
		[
			["A", 0, 0, 1],
			["\xf0\x90\x80", 1, 1, 3],
			["A", 2, 4, 1],
			["\xf0", 3, 5, 1],
			["\x8f", 4, 6, 1],
			["\xbf", 5, 7, 1],
			["\xbf", 6, 8, 1],
			["A", 7, 9, 1],
		], $results);
	}
	
	public function testEquals ()
	{
		$this->assertSame(true, Str::equals("", ""));
		$this->assertSame(true, Str::equals("A", "A"));
		$this->assertSame(false, Str::equals("A", ""));
		$this->assertSame(false, Str::equals("", "A"));
		$this->assertSame(false, Str::equals("A", "a"));
		$this->assertSame(true, Str::equals("ABC", "ABC"));
		$this->assertSame(false, Str::equals("ABC", " ABC"));
		$this->assertSame(false, Str::equals(" ABC", "ABC"));
		$this->assertSame(false, Str::equals("ABC", "ABC "));
		$this->assertSame(false, Str::equals("ABC ", "ABC"));
		$this->assertSame(true, Str::equals(" ABC ", " ABC "));
		$this->assertSame(true, Str::equals("\x00ABC", "\x00ABC"));
		$this->assertSame(false, Str::equals("\x00ABC", "\x00abc"));
		$this->assertSame(true, Str::equals("A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83", "A\xc6\x81\xe1\x8f\xa8\xf0\x90\x8c\x83"));
		$this->assertSame(true, Str::equals("A\x80A\x80A", "A\x80A\x80A"));
	}
	
	public function testBegin ()
	{
		$this->assertSame("", Str::begin("", ""));
		$this->assertSame("A", Str::begin("A", ""));
		$this->assertSame("A", Str::begin("", "A"));
		$this->assertSame("\x00A", Str::begin("A", "\x00"));
		$this->assertSame("A\x00", Str::begin("\x00", "A"));
		$this->assertSame("BA", Str::begin("A", "B"));
		$this->assertSame("CAB", Str::begin("AB", "C"));
		$this->assertSame("CDAB", Str::begin("AB", "CD"));
		$this->assertSame("B\xbfB\xbfBA\x80A\x80A", Str::begin("A\x80A\x80A", "B\xbfB\xbfB"));
		$this->assertSame("A\x80CA\x80B\x80C", Str::begin("A\x80B\x80C", "A\x80C"));
		$this->assertSame("B\x80CA\x80B\x80C", Str::begin("A\x80B\x80C", "B\x80C"));
		$this->assertSame("ABCAB", Str::begin("AB", "ABC"));
		
		$this->assertSame("A", Str::begin("A", "A"));
		$this->assertSame("\x00", Str::begin("\x00", "\x00"));
		$this->assertSame("AB", Str::begin("AB", "AB"));
		$this->assertSame("ABC", Str::begin("ABC", "AB"));
		$this->assertSame("A\x80B\x80C", Str::begin("A\x80B\x80C", "A\x80B"));
	}
	
	public function testFinish ()
	{
		$this->assertSame("", Str::finish("", ""));
		$this->assertSame("A", Str::finish("A", ""));
		$this->assertSame("A", Str::finish("", "A"));
		$this->assertSame("A\x00", Str::finish("A", "\x00"));
		$this->assertSame("\x00A", Str::finish("\x00", "A"));
		$this->assertSame("AB", Str::finish("A", "B"));
		$this->assertSame("ABC", Str::finish("AB", "C"));
		$this->assertSame("ABCD", Str::finish("AB", "CD"));
		$this->assertSame("A\x80A\x80AB\xbfB\xbfB", Str::finish("A\x80A\x80A", "B\xbfB\xbfB"));
		$this->assertSame("A\x80B\x80CA\x80C", Str::finish("A\x80B\x80C", "A\x80C"));
		$this->assertSame("A\x80B\x80CA\x80B", Str::finish("A\x80B\x80C", "A\x80B"));
		$this->assertSame("ABCAB", Str::finish("AB", "CAB"));
		
		$this->assertSame("A", Str::finish("A", "A"));
		$this->assertSame("\x00", Str::finish("\x00", "\x00"));
		$this->assertSame("AB", Str::finish("AB", "AB"));
		$this->assertSame("ABC", Str::finish("ABC", "BC"));
		$this->assertSame("A\x80B\x80C", Str::finish("A\x80B\x80C", "B\x80C"));
	}
	
	public function testIncSuffix ()
	{
		$this->assertSame("1", Str::incSuffix(""));
		$this->assertSame("1", Str::incSuffix("0"));
		$this->assertSame("2", Str::incSuffix("1"));
		$this->assertSame("10", Str::incSuffix("9"));
		$this->assertSame("9999990000", Str::incSuffix("9999989999"));
		$this->assertSame("10000000000", Str::incSuffix("9999999999"));
		
		$this->assertSame("ABC1", Str::incSuffix("ABC"));
		$this->assertSame("ABC1", Str::incSuffix("ABC0"));
		$this->assertSame("ABC2", Str::incSuffix("ABC1"));
		$this->assertSame("ABC10", Str::incSuffix("ABC9"));
		$this->assertSame("ABC9999990000", Str::incSuffix("ABC9999989999"));
		$this->assertSame("ABC10000000000", Str::incSuffix("ABC9999999999"));
		
		$this->assertSame("-2", Str::incSuffix("-1"));
		$this->assertSame("-3", Str::incSuffix("-2"));
		$this->assertSame("ABC-2", Str::incSuffix("ABC-1"));
		$this->assertSame("ABC-3", Str::incSuffix("ABC-2"));
		
		$this->assertSame("ABC1", Str::incSuffix("ABC0000"));
		$this->assertSame("ABC2", Str::incSuffix("ABC0001"));
		$this->assertSame("ABC4.6", Str::incSuffix("ABC4.5"));
		$this->assertSame("ABC4.10", Str::incSuffix("ABC4.9"));
		
		$this->assertSame("ABC3", Str::incSuffix("ABC1", 2));
		$this->assertSame("ABC10", Str::incSuffix("ABC1", 9));
		$this->assertSame("ABC150", Str::incSuffix("ABC42", 108));
		$this->assertSame("ABC42", Str::incSuffix("ABC42", 0));
		$this->assertSame("ABC41", Str::incSuffix("ABC42", -1));
		$this->assertSame("ABC39", Str::incSuffix("ABC42", -3));
		$this->assertSame("ABC9999999999", Str::incSuffix("ABC10000000000", -1));
	}
}
