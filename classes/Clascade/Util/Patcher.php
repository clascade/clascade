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
		$join_start = 0;
		$needs_join = false;
		$extra_nl = false;
		
		if (!ends_with($data, "\n"))
		{
			$data .= "\n";
			$extra_nl = true;
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
				
				if ($needs_join)
				{
					// A previous tentative match wasn't confirmed by a join.
					
					return false;
				}
				
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
				
				$join_start = $data_pos;
			}
			else
			{
				$type = substr($line, 0, 1);
				$line = (string) substr($line, 1);
				$line_len = strlen($line);
				
				switch ($type)
				{
				case '-':
				case '+':
					if ($type == '+' xor $is_reverse)
					{
						$data = substr_replace($data, $line."\n", $data_pos, 0);
						$last_op = '+';
						$data_pos += $line_len + 1;
						++$data_line;
						++$delta;
					}
					else
					{
						if ((string) substr($data, $data_pos, $line_len) !== $line)
						{
							// Patch doesn't match.
							
							return false;
						}
						elseif (substr($data, $data_pos + $line_len, 1) !== "\n")
						{
							$needs_join = true;
						}
						else
						{
							++$line_len;
							--$delta;
						}
						
						$last_op = '-';
						$data = substr_replace($data, '', $data_pos, $line_len);
					}
					break;
				
				case ' ':
					if ((string) substr($data, $data_pos, $line_len) !== $line)
					{
						// Patch doesn't match.
						
						return false;
					}
					elseif (substr($data, $data_pos + $line_len, 1) !== "\n")
					{
						$needs_join = true;
					}
					else
					{
						++$line_len;
						++$data_line;
					}
					
					$last_op = ' ';
					$data_pos += $line_len;
					break;
				
				case '\\':
					if ($last_op == '+' || $last_op == ' ')
					{
						--$data_pos;
						$join_start = $data_pos;
						$data = substr_replace($data, '', $data_pos, 1);
					}
					
					if ($last_op == '-' || $last_op == ' ')
					{
						$extra_nl = false;
					}
					
					break;
				
				case '~':
					$join_data = substr($data, $join_start, $data_pos - $join_start);
					$join_data = str_replace("\n", '', $join_data, $count);
					$delta -= $count;
					$data_line -= $count;
					
					if ($last_op != '-')
					{
						$join_data .= "\n";
						++$data_line;
						++$delta;
					}
					
					$data = substr_replace($data, $join_data, $join_start, $data_pos - $join_start);
					$data_pos = strlen($join_data) + $join_start;
					$join_start = $data_pos;
					$needs_join = false;
					break;
				
				default:
					// Malformed.
					
					return false;
				}
			}
			
			$line = $this->seekLine($patch, $patch_pos);
		}
		
		if ($needs_join)
		{
			// A previous tentative match wasn't confirmed by a join.
			
			return false;
		}
		
		if ($extra_nl)
		{
			// We know the input didn't end with a newline, but
			// we never saw the no-newline marker. This means
			// the end of the data wasn't in a change hunk,
			// which means both versions have the same end. So,
			// let's remove the newline we added.
			
			if (!ends_with($data, "\n"))
			{
				// Our newline somehow vanished. Something went wrong.
				
				return false;
			}
			
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
			$line = substr($string, $pos, $nl_pos - $pos - 1);
			$pos = $nl_pos;
		}
		
		return $line;
	}
}
