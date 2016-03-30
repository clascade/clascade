<?php

namespace Clascade\Util;

class Patcher
{
	public function patch ($data, $patch, $is_reverse=null)
	{
		if ($is_reverse === null)
		{
			$is_reverse = false;
		}
		
		$patch = (string) $patch;
		$data_pos = 0;
		$patch_pos = 0;
		$data_line = 0;
		$last_op = null;
		$old_nl = true;
		$new_nl = true;
		$added_nl = false;
		
		if (substr($data, -1) != "\n")
		{
			$data .= "\n";
			$added_nl = true;
		}
		
		$line = $this->seekLine($patch, $patch_pos);
		
		// Headers.
		
		if ($line !== false && substr($line, 0, 4) == '--- ')
		{
			$line = $this->seekLine($patch, $patch_pos);
		}
		
		if ($line !== false && substr($line, 0, 4) == '+++ ')
		{
			$line = $this->seekLine($patch, $patch_pos);
		}
		
		// Body.
		
		$delta = 0;
		
		while ($line !== false)
		{
			if (substr($line, 0, 4) == '@@ -')
			{
				// Chunk header.
				
				if (!preg_match('/^@@ -(\d+(?:,\d+)?) +\+(\d+(?:,\d+)?) +@/', $line, $match))
				{
					// Malformed.
					
					return false;
				}
				
				$match[1] = explode(',', $match[1], 2);
				$match[2] = explode(',', $match[2], 2);
				
				$old_pos = $match[1][0];
				$old_len = isset ($match[1][1]) ? $match[1][1] : 1;
				$new_pos = $match[2][0];
				$new_len = isset ($match[2][1]) ? $match[2][1] : 1;
				
				if ($is_reverse)
				{
					$ref_line = $new_pos + $delta;
					
					if ($new_len != 0)
					{
						--$ref_line;
					}
				}
				else
				{
					$ref_line = $old_pos + $delta;
					
					if ($old_len != 0)
					{
						--$ref_line;
					}
				}
				
				while ($data_line < $ref_line)
				{
					// Seek to the starting line.
					
					$data_pos = strpos($data, "\n", $data_pos) + 1;
					++$data_line;
				}
			}
			else
			{
				$type = substr($line, 0, 1);
				$line = substr($line, 1);
				$line_len = strlen($line);
				
				switch ($type)
				{
				case '-':
				case '+':
					if ($type == '+' xor $is_reverse)
					{
						$data = substr_replace($data, $line, $data_pos, 0);
						
						$last_op = '+';
						$data_pos += $line_len;
						++$data_line;
						++$delta;
					}
					else
					{
						if (substr($data, $data_pos, $line_len) !== $line)
						{
							// Patch doesn't match.
							
							return false;
						}
						
						$last_op = '-';
						$data = substr_replace($data, '', $data_pos, $line_len);
						--$delta;
					}
					break;
				
				case ' ':
					if (substr($data, $data_pos, $line_len) !== $line)
					{
						// Patch doesn't match.
						
						return false;
					}
					
					$last_op = ' ';
					$data_pos += $line_len;
					++$data_line;
					break;
				
				case '\\':
					if ($last_op != '-')
					{
						$new_nl = false;
					}
					
					if ($last_op != '+')
					{
						$old_nl = false;
					}
					
					$last_op = '\\';
					break;
				
				default:
					// Malformed.
					
					return false;
				}
			}
			
			$line = $this->seekLine($patch, $patch_pos);
		}
		
		if ($added_nl xor $old_nl xor $new_nl)
		{
			$data = substr($data, 0, -1);
		}
		
		return $data;
	}
	
	public function seekLine ($string, &$pos)
	{
		$nl_pos = strpos($string, "\n", $pos);
		
		if ($nl_pos === false)
		{
			$line = substr($string, $pos);
			$pos = strlen($string);
		}
		else
		{
			++$nl_pos;
			$line = substr($string, $pos, $nl_pos - $pos);
			$pos = $nl_pos;
		}
		
		return $line;
	}
}
