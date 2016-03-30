<?php

namespace Clascade\DB\Platform;

class DBFirebird extends \Clascade\DB\Platform
{
	public function doLimit ($query, $limit, $offset)
	{
		return preg_replace_callback('/^\s*SELECT\s/i', function ($match) use ($limit, $offset)
		{
			if ($limit !== null)
			{
				$match[0] .= "FIRST {$limit} ";
			}
			
			if ($offset > 0)
			{
				$match[0] .= "SKIP {$offset} ";
			}
			
			return $match[0];
		}, $query);
	}
	
	public function doLastInsertId ($table, $col)
	{
		$table = $this->normalize($table);
		return $this->pdo->lastInsertId("{$table}_{$col}_seq");
	}
}
