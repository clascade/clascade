<?php

namespace Clascade\Util\Crypto\Providers;

class FallbackAESProvider
{
	public $bmul9;
	public $bmulb;
	public $bmuld;
	public $bmule;
	public $rco;
	public $temp;
	public $temp2;
	public $x;
	public $y;
	public $enc;
	public $dec;
	
	public function __construct ()
	{
		$this->genTables();
	}
	
	public function encryptCBC ($key, $iv, $data)
	{
		if (strlen($data) == 0)
		{
			return '';
		}
		
		$this->pad($iv);
		$this->pad($data);
		$exp_key = $this->expandKey($key);
		$len = strlen($data);
		$pos = 16;
		
		for ($i = 0; $i < 16; ++$i)
		{
			$data[$i] = $data[$i] ^ $iv[$i];
		}
		
		$this->block($this->enc, $exp_key['enc'], $data, 0);
		
		while ($pos < $len)
		{
			for ($i = 0; $i < 16; ++$i)
			{
				$data[$pos] = $data[$pos] ^ $data[$pos - 16];
				++$pos;
			}
			
			$this->block($this->enc, $exp_key['enc'], $data, $pos - 16);
		}
		
		return $data;
	}
	
	public function decryptCBC ($key, $iv, $data)
	{
		if (strlen($data) == 0)
		{
			return '';
		}
		
		$this->pad($iv);
		$this->pad($data);
		$exp_key = $this->expandKey($key);
		$next_iv = substr($data, 0, 16);
		$len = strlen($data);
		$pos = 0;
		
		while ($pos < $len)
		{
			$this->block($this->dec, $exp_key['dec'], $data, $pos);
			
			for ($i = 0; $i < 16; ++$i)
			{
				$data[$pos] = $data[$pos] ^ $iv[$i];
				++$pos;
			}
			
			$iv = $next_iv;
			$next_iv = substr($data, $pos, 16);
		}
		
		return $data;
	}
	
	public function pad (&$string)
	{
		if (strlen($string) % 16 != 0)
		{
			$string .= str_repeat("\x00", 16 - strlen($string) % 16);
		}
	}
	
	public function pack ($arr, $pos)
	{
		return ($arr[$pos + 3] << 24) | ($arr[$pos + 2] << 16) | ($arr[$pos + 1] << 8) | $arr[$pos];
	}
	
	public function unpack ($value, &$arr, $pos)
	{
		$arr[$pos] = $value & 0xff;
		$arr[$pos + 1] = ($value >> 8) & 0xff;
		$arr[$pos + 2] = ($value >> 16) & 0xff;
		$arr[$pos + 3] = ($value >> 24) & 0xff;
	}
	
	public function packStr ($str, $pos)
	{
		return (ord($str[$pos + 3]) << 24) | (ord($str[$pos + 2]) << 16) | (ord($str[$pos + 1]) << 8) | ord($str[$pos]);
	}
	
	public function rotL8 ($value)
	{
		return (($value << 8) | ($value >> 24)) & 0xffffffff;
	}
	
	public function rotL16 ($value)
	{
		return (($value << 16) | ($value >> 16)) & 0xffffffff;
	}
	
	public function rotL24 ($value)
	{
		return (($value << 24) | ($value >> 8)) & 0xffffffff;
	}
	
	public function xtime ($value)
	{
		return (($value << 1) & 0xff) ^ (($value & 0x80) == 0 ? 0 : 0x1b);
	}
	
	public function subByte ($value)
	{
		$this->unpack($value, $this->temp, 0);
		
		$this->temp[0] = $this->enc['sbox'][$this->temp[0]];
		$this->temp[1] = $this->enc['sbox'][$this->temp[1]];
		$this->temp[2] = $this->enc['sbox'][$this->temp[2]];
		$this->temp[3] = $this->enc['sbox'][$this->temp[3]];
		
		return $this->pack($this->temp, 0);
	}
	
	// Matrix multiplication.
	
	public function invMixCol ($value)
	{
		$this->unpack($value, $this->temp, 0);
		
		$this->temp2[0] = $this->bmule[$this->temp[0]] ^ $this->bmulb[$this->temp[1]] ^ $this->bmuld[$this->temp[2]] ^ $this->bmul9[$this->temp[3]];
		$this->temp2[1] = $this->bmul9[$this->temp[0]] ^ $this->bmule[$this->temp[1]] ^ $this->bmulb[$this->temp[2]] ^ $this->bmuld[$this->temp[3]];
		$this->temp2[2] = $this->bmuld[$this->temp[0]] ^ $this->bmul9[$this->temp[1]] ^ $this->bmule[$this->temp[2]] ^ $this->bmulb[$this->temp[3]];
		$this->temp2[3] = $this->bmulb[$this->temp[0]] ^ $this->bmuld[$this->temp[1]] ^ $this->bmul9[$this->temp[2]] ^ $this->bmule[$this->temp[3]];
		
		return $this->pack($this->temp2, 0);
	}
	
	public function genTables ()
	{
		$this->bmul9 = array_fill(0, 256, 0);
		$this->bmulb = array_fill(0, 256, 0);
		$this->bmuld = array_fill(0, 256, 0);
		$this->bmule = array_fill(0, 256, 0);
		$this->rco = array_fill(0, 30, 0);
		$this->temp = array_fill(0, 4, 0);
		$this->temp2 = array_fill(0, 4, 0);
		$this->x = array_fill(0, 8, 0);
		$this->y = array_fill(0, 8, 0);
		
		$this->enc =
		[
			'lookup' => array_fill(0, 256, 0),
			'lookup-l8' => array_fill(0, 256, 0),
			'lookup-l16' => array_fill(0, 256, 0),
			'lookup-l24' => array_fill(0, 256, 0),
			'sbox' => array_fill(0, 256, 0),
		];
		
		$this->dec =
		[
			'lookup' => array_fill(0, 256, 0),
			'lookup-l8' => array_fill(0, 256, 0),
			'lookup-l16' => array_fill(0, 256, 0),
			'lookup-l24' => array_fill(0, 256, 0),
			'sbox' => array_fill(0, 256, 0),
		];
		
		$powers = array_fill(0, 256, 0);
		$logs = array_fill(0, 256, 0);
		
		$powers[0] = 1;
		$powers[1] = 3;
		$logs[3] = 1;
		
		for ($i = 2; $i < 256; ++$i)
		{
			$powers[$i] = $powers[$i - 1] ^ $this->xtime($powers[$i - 1]);
			$logs[$powers[$i]] = $i;
		}
		
		for ($i = 1; $i < 256; ++$i)
		{
			$this->bmul9[$i] = $powers[($logs[0x9] + $logs[$i]) % 255];
			$this->bmulb[$i] = $powers[($logs[0xb] + $logs[$i]) % 255];
			$this->bmuld[$i] = $powers[($logs[0xd] + $logs[$i]) % 255];
			$this->bmule[$i] = $powers[($logs[0xe] + $logs[$i]) % 255];
		}
		
		$this->enc['sbox'][0] = 0x63;
		$this->dec['sbox'][0x63] = 0;
		
		for ($i = 1; $i < 256; ++$i)
		{
			$x = $powers[255 - $logs[$i]];
			
			$value = $x;
			$value ^= ($x >> 7) | ($x << 1);
			$value ^= ($x >> 6) | ($x << 2);
			$value ^= ($x >> 5) | ($x << 3);
			$value ^= ($x >> 4) | ($x << 4);
			$value ^= 0x63;
			
			$value &= 0xff;
			
			$this->enc['sbox'][$i] = $value;
			$this->dec['sbox'][$value] = $i;
		}
		
		for ($i = 0, $value = 1; $i < 30; ++$i)
		{
			$this->rco[$i] = $value;
			$value = $this->xtime($value);
		}
		
		for ($i = 0; $i < 256; ++$i)
		{
			$value = $this->enc['sbox'][$i];
			$this->temp[3] = $value ^ $this->xtime($value);
			$this->temp[2] = $value;
			$this->temp[1] = $value;
			$this->temp[0] = $this->xtime($value);
			$this->enc['lookup'][$i] = $this->pack($this->temp, 0);
			$this->enc['lookup-l8'][$i] = $this->rotL8($this->enc['lookup'][$i]);
			$this->enc['lookup-l16'][$i] = $this->rotL16($this->enc['lookup'][$i]);
			$this->enc['lookup-l24'][$i] = $this->rotL24($this->enc['lookup'][$i]);
			
			$value = $this->dec['sbox'][$i];
			$this->temp[3] = $this->bmulb[$value];
			$this->temp[2] = $this->bmuld[$value];
			$this->temp[1] = $this->bmul9[$value];
			$this->temp[0] = $this->bmule[$value];
			$this->dec['lookup'][$i] = $this->pack($this->temp, 0);
			$this->dec['lookup-l8'][$i] = $this->rotL8($this->dec['lookup'][$i]);
			$this->dec['lookup-l16'][$i] = $this->rotL16($this->dec['lookup'][$i]);
			$this->dec['lookup-l24'][$i] = $this->rotL24($this->dec['lookup'][$i]);
		}
	}
	
	public function expandKey ($key)
	{
		$exp_key =
		[
			'key-length' => max(strlen($key) >> 2, 4),
		];
		
		$exp_key['num-rounds'] = 6 + $exp_key['key-length'];
		
		$num = ($exp_key['num-rounds'] + 1) * 4;
		
		$exp_key['enc'] =
		[
			'inc' => array_fill(0, 12, 0),
			'key' => array_fill(0, $num, 0),
			'num-rounds' => $exp_key['num-rounds'],
		];
		
		$exp_key['dec'] =
		[
			'inc' => array_fill(0, 12, 0),
			'key' => array_fill(0, $num, 0),
			'num-rounds' => $exp_key['num-rounds'],
		];
		
		for ($m = $i = 0; $i < 4; ++$i, $m += 3)
		{
			$exp_key['enc']['inc'][$m] = ($i + 1) & 0x3;
			$exp_key['enc']['inc'][$m + 1] = ($i + 2) & 0x3;
			$exp_key['enc']['inc'][$m + 2] = ($i + 3) & 0x3;
			
			$exp_key['dec']['inc'][$m] = ($i + 3) & 0x3;
			$exp_key['dec']['inc'][$m + 1] = ($i + 2) & 0x3;
			$exp_key['dec']['inc'][$m + 2] = ($i + 1) & 0x3;
		}
		
		for ($i = $exp_key['key-length'] - 1; $i >= 0; --$i)
		{
			$exp_key['enc']['key'][$i] = $this->packStr($key, $i << 2);
		}
		
		for ($i = $exp_key['key-length'], $j = 0; $i < $num; $i += $exp_key['key-length'], ++$j)
		{
			$exp_key['enc']['key'][$i] = $exp_key['enc']['key'][$i - $exp_key['key-length']] ^ $this->subByte($this->rotL24($exp_key['enc']['key'][$i - 1])) ^ $this->rco[$j];
			
			if ($exp_key['key-length'] <= 6)
			{
				for ($k = 1; $k < $exp_key['key-length'] && $i + $k < $num; ++$k)
				{
					$exp_key['enc']['key'][$i + $k] = $exp_key['enc']['key'][$i + $k - $exp_key['key-length']] ^ $exp_key['enc']['key'][$i + $k - 1];
				}
			}
			else
			{
				for ($k = 1; $k < 4 && $i + $k < $num; ++$k)
				{
					$exp_key['enc']['key'][$i + $k] = $exp_key['enc']['key'][$i + $k - $exp_key['key-length']] ^ $exp_key['enc']['key'][$i + $k - 1];
				}
				
				if ($i + 4 < $num)
				{
					$exp_key['enc']['key'][$i + 4] = $exp_key['enc']['key'][$i + 4 - $exp_key['key-length']] ^ $this->subByte($exp_key['enc']['key'][$i + 3]);
				}
				
				for ($k = 5; $k < $exp_key['key-length'] && ($i + $k) < $num; ++$k)
				{
					$exp_key['enc']['key'][$i + $k] = $exp_key['enc']['key'][$i + $k - $exp_key['key-length']] ^ $exp_key['enc']['key'][$i + $k - 1];
				}
			}
		}
		
		for ($i = 0; $i < 4; ++$i)
		{
			$exp_key['dec']['key'][$i + $num - 4] = $exp_key['enc']['key'][$i];
		}
		
		for ($i = 4; $i < $num - 4; $i += 4)
		{
			$j = $num - 4 - $i;
			
			for ($k = 0; $k < 4; ++$k)
			{
				$exp_key['dec']['key'][$j + $k] = $this->invMixCol($exp_key['enc']['key'][$i + $k]);
			}
		}
		
		for ($i = $num - 4; $i < $num; ++$i)
		{
			$exp_key['dec']['key'][$i - $num + 4] = $exp_key['enc']['key'][$i];
		}
		
		return $exp_key;
	}
	
	public function block ($direction, $dir_exp_key, &$data, $pos)
	{
		for ($i = 0; $i < 4; ++$i, $pos += 4)
		{
			$this->x[$i] = $this->packStr($data, $pos) ^ $dir_exp_key['key'][$i];
		}
		
		$k = 4;
		
		for ($i = 0; $i < $dir_exp_key['num-rounds']; $i += 2)
		{
			if ($i != 0)
			{
				for ($m = $j = 0; $j < 4; ++$j, ++$k, $m += 3)
				{
					$this->x[$j] =
						$dir_exp_key['key'][$k] ^ $direction['lookup'][$this->y[$j] & 0xff] ^
						$direction['lookup-l8'][($this->y[$dir_exp_key['inc'][$m]] >> 8) & 0xff] ^
						$direction['lookup-l16'][($this->y[$dir_exp_key['inc'][$m + 1]] >> 16) & 0xff] ^
						$direction['lookup-l24'][$this->y[$dir_exp_key['inc'][$m + 2]] >> 24];
				}
			}
			
			for ($m = $j = 0; $j < 4; ++$j, ++$k, $m += 3)
			{
				$this->y[$j] =
					$dir_exp_key['key'][$k] ^ $direction['lookup'][$this->x[$j] & 0xff] ^
					$direction['lookup-l8'][($this->x[$dir_exp_key['inc'][$m]] >> 8) & 0xff] ^
					$direction['lookup-l16'][($this->x[$dir_exp_key['inc'][$m + 1]] >> 16) & 0xff] ^
					$direction['lookup-l24'][$this->x[$dir_exp_key['inc'][$m + 2]] >> 24];
			}
		}
		
		for ($m = $j = 0; $j < 4; ++$j, ++$k, $m += 3)
		{
			$this->x[$j] =
				$dir_exp_key['key'][$k] ^ $direction['sbox'][$this->y[$j] & 0xff] ^
				($direction['sbox'][($this->y[$dir_exp_key['inc'][$m]] >> 8) & 0xff] << 8) ^
				($direction['sbox'][($this->y[$dir_exp_key['inc'][$m + 1]] >> 16) & 0xff] << 16) ^
				($direction['sbox'][$this->y[$dir_exp_key['inc'][$m + 2]] >> 24] << 24);
		}
		
		for ($i = 0, $pos -= 16; $i < 4; ++$i, $pos += 4)
		{
			$value = $this->x[$i];
			
			$data[$pos] = chr($value & 0xff);
			$data[$pos + 1] = chr(($value >> 8) & 0xff);
			$data[$pos + 2] = chr(($value >> 16) & 0xff);
			$data[$pos + 3] = chr($value >> 24);
		}
	}
}
