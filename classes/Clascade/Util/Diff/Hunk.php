<?php

namespace Clascade\Util\Diff;

class Hunk
{
	public $intraline;
	
	public $old_start;
	public $old_length;
	public $new_start;
	public $new_length;
	public $context_before = [];
	public $changes = [];
	public $context_after = [];
	public $old_trailing_nl = true;
	public $new_trailing_nl = true;
	
	public function __construct ($intraline=null)
	{
		$this->intraline = (bool) $intraline;
	}
	
	public function __toString ()
	{
		return $this->serializeUniform();
	}
	
	/**
	 * Serialize the hunk using the uniform diff format.
	 */
	
	public function serializeUniform ()
	{
		$serialized = '@@ -';
		
		switch ($this->old_length)
		{
		case 0:
			$serialized .= "{$this->old_start},0";
			break;
		case 1:
			$serialized .= $this->old_start + 1;
			break;
		default:
			$serialized .= ($this->old_start + 1).",{$this->old_length}";
		}
		
		$serialized .= ' +';
		
		switch ($this->new_length)
		{
		case 0:
			$serialized .= "{$this->new_start},0";
			break;
		case 1:
			$serialized .= $this->new_start + 1;
			break;
		default:
			$serialized .= ($this->new_start + 1).",{$this->new_length}";
		}
		
		$serialized .= " @@\n";
		
		foreach ($this->context_before as $line)
		{
			if ($this->intraline)
			{
				$serialized .= " {$line}~\n";
			}
			else
			{
				$serialized .= " {$line}";
			}
		}
		
		// Find the indexes of the last old and new lines.
		
		$last_old = null;
		$last_new = null;
		
		if (empty ($this->context_after) && (!$this->old_trailing_nl || !$this->new_trailing_nl))
		{
			for ($i = count($this->changes) - 1; $i >= 0 && ($last_old === null || $last_new === null); --$i)
			{
				switch ($this->changes[$i]['type'])
				{
				case 'old':
					if ($last_old === null)
					{
						$last_old = $i;
					}
					break;
				
				case 'new':
					if ($last_new === null)
					{
						$last_new = $i;
					}
					break;
				
				case 'both':
					if ($last_old === null)
					{
						$last_old = $i;
					}
					
					if ($last_new === null)
					{
						$last_new = $i;
					}
					break;
				}
			}
		}
		
		$change_prefixes =
		[
			'old' => '-',
			'new' => '+',
			'both' => ' ',
		];
		
		foreach ($this->changes as $key => $change)
		{
			if ($this->intraline)
			{
				$suffix = ends_with($change['data'], "\n") ? "~\n" : "\n";
				$serialized .= $change_prefixes[$change['type']].$change['data'].$suffix;
			}
			else
			{
				$serialized .= $change_prefixes[$change['type']].$change['data'];
			}
			
			if (($key === $last_old && !$this->old_trailing_nl) || ($key === $last_new && !$this->new_trailing_nl))
			{
				$serialized .= ($this->intraline ? "~" : '')."\n\\ No newline at end of file\n";
			}
		}
		
		if (!empty ($this->context_after))
		{
			foreach ($this->context_after as $key => $line)
			{
				if ($this->intraline)
				{
					$suffix = ends_with($line, "\n") ? "~\n" : "\n~\n";
					$serialized .= " {$line}{$suffix}";
				}
				else
				{
					$serialized .= " {$line}";
				}
			}
			
			if (!$this->old_trailing_nl)
			{
				$serialized .= ($this->intraline ? '' : "\n")."\\ No newline at end of file\n";
			}
		}
		
		return $serialized;
	}
	
	public function concatChange ($type, $data)
	{
		$prev_index = count($this->changes) - 1;
		
		if (empty ($this->changes) || $type != $this->changes[$prev_index]['type'] || ends_with($this->changes[$prev_index]['data'], "\n"))
		{
			$this->changes[] = ['type' => $type, 'data' => $data];
		}
		else
		{
			$this->changes[$prev_index]['data'] .= $data;
		}
	}
}
