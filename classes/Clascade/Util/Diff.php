<?php

namespace Clascade\Util;
use Clascade\Util\Diff\Hunk;

class Diff
{
	public $old_data;
	public $new_data;
	public $hunks;
	public $context_size = 3;
	
	public function __construct ($old_data, $new_data, $params=null)
	{
		$this->old_data = preg_split('/(?<=\n)(?!$)/', $old_data);
		$this->new_data = preg_split('/(?<=\n)(?!$)/', $new_data);
		
		if ($params !== null)
		{
			if (isset ($params['context-size']))
			{
				$this->context_size = $params['context-size'];
			}
		}
	}
	
	public function __toString ()
	{
		return $this->getDiff();
	}
	
	public function getDiff ()
	{
		return implode('', $this->getHunks());
	}
	
	public function getHunks ()
	{
		if ($this->hunks === null)
		{
			$this->execute();
		}
		
		return $this->hunks;
	}
	
	public function execute ()
	{
		$this->hunks = $this->getSegmentHunks(0, count($this->old_data), 0, count($this->new_data));
	}
	
	public function getSegmentHunks ($old_start, $old_end, $new_start, $new_end)
	{
		// Is this an empty segment?
		
		if ($old_start == $old_end && $new_start == $new_end)
		{
			// Yes. Don't bother with the extra logic.
			
			return [];
		}
		
		// Find the longest common subsequence in this segment.
		
		list ($old_pos, $new_pos, $match_len) = $this->getLongestMatch($old_start, $old_end, $new_start, $new_end);
		$is_changed = false;
		
		if ($match_len == 0)
		{
			// No matches were found in this segment.
			
			$is_changed = true;
		}
		elseif ($match_len <= $this->context_size * 2)
		{
			// This is a short match. It's worth including in the diff
			// if it will combine adjacent hunks.
			
			// Make sure the match isn't on the edge of the segment.
			
			if ($old_pos != $old_start || $new_pos != $new_start)
			{
				// The match isn't at the start of the segment.
				
				if ($old_pos + $match_len != $old_end || $new_pos + $match_len != $new_end)
				{
					// The match isn't at the end of the segment either.
					// This means we have changes on both sides of this
					// match, so we'll benefit from including this match
					// in the diff.
					
					$is_changed = true;
					
				}
			}
		}
		
		if ($is_changed)
		{
			// There are no significant matches in this segment. This is a change.
			
			return [$this->getHunk($old_start, $old_end, $new_start, $new_end)];
		}
		
		// Found a string match in this segment. Recursively
		// check the unmatched string segments around it.
		
		$hunks = [];
		
		foreach ($this->getSegmentHunks($old_start, $old_pos, $new_start, $new_pos) as $hunk)
		{
			$hunks[] = $hunk;
		}
		
		foreach ($this->getSegmentHunks($old_pos + $match_len, $old_end, $new_pos + $match_len, $new_end) as $hunk)
		{
			$hunks[] = $hunk;
		}
		
		return $hunks;
	}
	
	public function getLongestMatch ($old_start, $old_end, $new_start, $new_end)
	{
		$old_pos = 0;
		$new_pos = 0;
		$match_len = 0;
		
		$num_planes = min($old_end - $old_start, $new_end - $new_start);
		
		$prev_row = [];
		$prev_col = [];
		
		for ($i = 0; $i < $num_planes; ++$i)
		{
			$row = [];
			$col = [];
			$init_old = $old_start + $i;
			$init_new = $new_start + $i;
			
			$line = $this->new_data[$init_new];
			
			for ($j = $init_old; $j < $old_end; ++$j)
			{
				if ($line === $this->old_data[$j])
				{
					$len = isset ($prev_col[$j - 1]) ? $prev_col[$j - 1] + 1 : 1;
					$col[$j] = $len;
					
					if ($len > $match_len)
					{
						$old_pos = $j + 1 - $len;
						$new_pos = $init_new + 1 - $len;
						$match_len = $len;
					}
				}
			}
			
			$line = $this->old_data[$init_old];
			
			for ($j = $init_new + 1; $j < $new_end; ++$j)
			{
				if ($line === $this->new_data[$j])
				{
					$len = isset ($prev_row[$j - 1]) ? $prev_row[$j - 1] + 1 : 1;
					$row[$j] = $len;
					
					if ($len > $match_len)
					{
						$old_pos = $init_old + 1 - $len;
						$new_pos = $j + 1 - $len;
						$match_len = $len;
					}
				}
			}
			
			$prev_row = $row;
			$prev_col = $col;
		}
		
		return [$old_pos, $new_pos, $match_len];
	}
	
	public function getHunk ($old_start, $old_end, $new_start, $new_end)
	{
		// Determine how many identical lines we have before the changes.
		
		for ($lines_before = 1; $lines_before <= $this->context_size; ++$lines_before)
		{
			if ($old_start < $lines_before || $new_start < $lines_before || $this->old_data[$old_start - $lines_before] != $this->new_data[$new_start - $lines_before])
			{
				break;
			}
		}
		
		--$lines_before;
		
		// Determine how many identical lines we have after the changes.
		
		$old_size = count($this->old_data);
		$new_size = count($this->new_data);
		
		for ($lines_after = 1; $lines_after <= $this->context_size; ++$lines_after)
		{
			if ($old_size - $old_end < $lines_after || $new_size - $new_end < $lines_after || $this->old_data[$old_end + $lines_after] != $this->new_data[$new_end + $lines_after])
			{
				break;
			}
		}
		
		--$lines_after;
		
		// Initialize hunk.
		
		$hunk = new Hunk();
		$hunk->old_start = $old_start - $lines_before + 1;
		$hunk->old_length = $old_end - $old_start + $lines_before + $lines_after;
		$hunk->new_start = $new_start - $lines_before + 1;
		$hunk->new_length = $new_end - $new_start + $lines_before + $lines_after;
		$hunk->old_trailing_delim = ends_with(array_last($this->old_data), "\n");
		$hunk->new_trailing_delim = ends_with(array_last($this->new_data), "\n");
		
		// Identical lines before changes.
		
		while ($lines_before > 0)
		{
			$hunk->context_before[] = $this->old_data[$old_start - $lines_before];
			--$lines_before;
		}
		
		// Changes.
		
		$i = $old_start;
		$j = $new_start;
		
		while ($i < $old_end || $j < $new_end)
		{
			$found_identical = false;
			
			while ($i < $old_end)
			{
				for ($k = $j; $k < $new_end; ++$k)
				{
					if ($this->new_data[$k] == $this->old_data[$i])
					{
						// Found an identical line in the middle of the change hunk.
						
						$found_identical = true;
						break 2;
					}
				}
				
				$hunk->changes[] = ['type' => 'old', 'data' => $this->old_data[$i]];
				++$i;
			}
			
			while ($j < $new_end && (!$found_identical || $j < $k))
			{
				$hunk->changes[] = ['type' => 'new', 'data' => $this->new_data[$j]];
				++$j;
			}
			
			if ($found_identical)
			{
				do
				{
					$hunk->changes[] = ['type' => 'both', 'data' => $this->old_data[$i]];
					++$i;
					++$j;
				}
				while ($i < $old_end && $j < $new_end && $this->old_data[$i] == $this->new_data[$j]);
			}
		}
		
		// Identical lines after changes.
		
		for ($i = 0; $i < $lines_after; ++$i)
		{
			$hunk->context_after[] = $this->old_data[$old_start - $lines_before];
		}
		
		return $hunk;
	}
}
