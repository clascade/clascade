<?php

namespace Clascade\Util;
use Clascade\Util\Diff\Hunk;

class Diff
{
	public $old_data;
	public $new_data;
	public $hunks;
	public $context_size;
	public $intraline;
	
	public function __construct ($old_data, $new_data, $params=null)
	{
		$this->context_size = (int) (isset ($params['context-size']) ? $params['context-size'] : 3);
		$this->intraline = (bool) (isset ($params['intraline']) ? $params['intraline'] : false);
		
		if (is_array($old_data))
		{
			$this->old_data = $old_data;
		}
		else
		{
			preg_match_all('/.+\n?|\n/', $old_data, $old_data, \PREG_PATTERN_ORDER);
			$this->old_data = $old_data[0];
		}
		
		if (is_array($new_data))
		{
			$this->new_data = $new_data;
		}
		else
		{
			preg_match_all('/.+\n?|\n/', $new_data, $new_data, \PREG_PATTERN_ORDER);
			$this->new_data = $new_data[0];
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
			if ($old_size - $old_end < $lines_after || $new_size - $new_end < $lines_after || $this->old_data[$old_end + $lines_after - 1] != $this->new_data[$new_end + $lines_after - 1])
			{
				break;
			}
		}
		
		--$lines_after;
		
		// Initialize hunk.
		
		$hunk = new Hunk($this->intraline);
		$hunk->old_start = $old_start - $lines_before;
		$hunk->old_length = $old_end - $old_start + $lines_before + $lines_after;
		$hunk->new_start = $new_start - $lines_before;
		$hunk->new_length = $new_end - $new_start + $lines_before + $lines_after;
		$hunk->old_trailing_nl = ($old_end == 0 || $old_end != count($this->old_data) || ends_with($this->old_data[$old_end - 1], "\n"));
		$hunk->new_trailing_nl = ($new_end == 0 || $new_end != count($this->new_data) || ends_with($this->new_data[$new_end - 1], "\n"));
		
		// Identical lines before changes.
		
		while ($lines_before > 0)
		{
			$hunk->context_before[] = $this->old_data[$old_start - $lines_before];
			--$lines_before;
		}
		
		// Changes.
		
		if ($this->intraline)
		{
			$old_data = $this->breakIntraline($this->old_data, $old_start, $old_end);
			$new_data = $this->breakIntraline($this->new_data, $new_start, $new_end);
			
			$subdiff = new Diff($old_data, $new_data,
			[
				'context-size' => max(count($old_data), count($new_data)),
			]);
			$subdiff->execute();
			
			foreach ($subdiff->hunks[0]->context_before as $data)
			{
				$hunk->concatChange('both', $data);
			}
			
			foreach ($subdiff->hunks[0]->changes as $change)
			{
				$hunk->concatChange($change['type'], $change['data']);
			}
			
			foreach ($subdiff->hunks[0]->context_after as $data)
			{
				$hunk->concatChange('both', $data);
			}
			
			if ($hunk->changes[count($hunk->changes) - 1]['type'] != 'both')
			{
				// Make sure the version without a trailing newline has
				// a change appearance after the last "both" entry.
				
				if (!$hunk->old_trailing_nl)
				{
					for ($i = count($hunk->changes) - 1; $i >= 0; --$i)
					{
						switch ($hunk->changes[$i]['type'])
						{
						case 'old':
							break 2;
						
						case 'both':
							$hunk->changes[] = ['type' => 'old', 'data' => ''];
							break 2;
						}
					}
				}
				
				if (!$hunk->new_trailing_nl)
				{
					for ($i = count($hunk->changes) - 1; $i >= 0; --$i)
					{
						switch ($hunk->changes[$i]['type'])
						{
						case 'new':
							break 2;
						
						case 'both':
							$hunk->changes[] = ['type' => 'new', 'data' => ''];
							break 2;
						}
					}
				}
			}
		}
		else
		{
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
		}
		
		// Identical lines after changes.
		
		for ($i = 0; $i < $lines_after; ++$i)
		{
			$hunk->context_after[] = $this->old_data[$old_end + $i];
		}
		
		return $hunk;
	}
	
	public function breakIntraline ($data, $start, $end)
	{
		$data = array_slice($data, $start, $end - $start);
		$data = implode('', $data);
		preg_match_all('/.\n?|\n/', $data, $data, \PREG_PATTERN_ORDER);
		return $data[0];
	}
}
