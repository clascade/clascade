<?php

namespace Clascade\DB\Platform;

class DBOracle extends \Clascade\DB\Platform
{
	public function doLimit ($query, $limit, $offset)
	{
		if ($offset > 0)
		{
			$rownum_col = $this->ident('_clascade_rownum');
			$query = "SELECT * FROM (SELECT clascade_result.*, ROWNUM {$rownum_col} FROM ({$query}) clascade_result";
			
			if ($limit !== null)
			{
				$query .= ' WHERE ROWNUM<='.($offset + $limit);
			}
			
			$query .= ") WHERE {$rownum_col}>{$offset}";
		}
		else
		{
			$query = "SELECT clascade_result.* FROM ({$query}) clascade_result WHERE ROWNUM<={$limit}";
		}
		
		return $query;
	}
}
