<?php

namespace Clascade\DB\Platform;

class DBIbm extends \Clascade\DB\Platform
{
	public function doLimit ($query, $limit, $offset)
	{
		$rownum_col = $this->ident('_clascade_rownum');
		$query = "WITH clascade_cte AS ({$query}) SELECT * FROM (SELECT *, ROW_NUMBER() OVER (ORDER BY (SELECT 0)) {$rownum_col} FROM clascade_cte) WHERE ";
		
		if ($limit !== null)
		{
			$query .= "{$rownum_col}<=".($offset + $limit);
		}
		
		if ($offset > 0)
		{
			if ($limit !== null)
			{
				$query .= ' AND ';
			}
			
			$query .= "{$rownum_col}>{$offset}";
		}
		
		$query .= " ORDER BY {$rownum_col}";
		return $query;
	}
}
