<?php

namespace Clascade\Util\Diff;

class Hunk
{
	public $old_start;
	public $old_length;
	public $new_start;
	public $new_length;
	public $context_before = [];
	public $changes = [];
	public $context_after = [];
	public $old_trailing_delim;
	public $new_trailing_delim;
	
	public function __toString ()
	{
		return $this->serializeUniform();
	}
	
	/**
	 * Serialize the hunk using the uniform diff format.
	 *
	 * Note: This will only work properly if the diff delimiter is
	 * a newline character.
	 */
	
	public function serializeUniform ()
	{
		$serialized = '@@ -';
		
		switch ($this->old_length)
		{
		case 0:
			$serialized .= ($this->old_start - 1).',0';
			break;
		case 1:
			$serialized .= $this->old_start;
			break;
		default:
			$serialized .= "{$this->old_start},{$this->old_length}";
		}
		
		$serialized .= ' +';
		
		switch ($this->new_length)
		{
		case 0:
			$serialized .= ($this->new_start - 1).',0';
			break;
		case 1:
			$serialized .= $this->new_start;
			break;
		default:
			$serialized .= "{$this->new_start},{$this->new_length}";
		}
		
		$serialized .= " @@\n";
		
		foreach ($this->context_before as $line)
		{
			$serialized .= " {$line}";
		}
		
		// Find the indexes of the last old and new lines.
		
		$last_old = null;
		$last_new = null;
		
		if (empty ($this->context_after))
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
					$last_old = $i;
					$last_new = $i;
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
			$serialized .= $change_prefixes[$change['type']].$change['data'];
			
			if (($key === $last_old && !$this->old_trailing_delim) || ($key === $last_new && !$this->new_trailing_delim))
			{
				$serialized .= "\n\\ No newline at end of file\n";
			}
		}
		
		if (!empty ($this->context_after))
		{
			foreach ($this->context_after as $line)
			{
				$serialized .= " {$line}";
			}
			
			if (!$this->old_trailing_delim)
			{
				$serialized .= "\n\\ No newline at end of file\n";
			}
		}
		
		return $serialized;
	}
}
