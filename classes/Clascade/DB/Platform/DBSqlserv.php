<?php

namespace Clascade\DB\Platform;

class DBSqlserv extends \Clascade\DB\Platform
{
	public function doLimit ($query, $limit, $offset)
	{
		$query = preg_replace('/^\s*SELECT\s+(?:DISTINCT\s+)?/', '$0TOP '.($offset + $limit).' ', $query);
		
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
	
	public function doRollbackTo ($savepoint)
	{
		return $this->query('ROLLBACK '.$this->ident($savepoint));
	}
	
	public function doSavepoint ($savepoint)
	{
		return $this->query('SAVE '.$this->ident($savepoint));
	}
}
