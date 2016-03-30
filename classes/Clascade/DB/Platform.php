<?php

namespace Clascade\DB;

class Platform
{
	public $pdo;
	public $connection_name;
	public $type;
	
	public $table_prefix = '';
	public $transaction_level = 0;
	
	public function __construct($pdo)
	{
		$this->pdo = $pdo;
		$this->type = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
	}
	
	//== Basic queries ==//
	
	public function query ($query)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return $this->queryByArray($query, $params);
	}
	
	public function queryAs ($mode, $query)
	{
		$params = func_get_args();
		$params = array_slice($params, 2);
		return $this->queryByArray($query, $params, $mode);
	}
	
	public function queryByArray ($query, $params=null, $mode=null)
	{
		if ($query instanceof QueryBuilder)
		{
			return $query->query($options);
		}
		elseif ($query instanceof Expression)
		{
			$params = $query->params;
			$query = $query->sql;
		}
		else
		{
			$params = (array) $params;
		}
		
		$query = $this->normalize($query, $params);
		
		// Prepare and execute the query.
		
		$statement = $this->pdo->prepare($query);
		
		if ($mode === null)
		{
			$statement->setFetchMode(\PDO::FETCH_OBJ);
		}
		elseif (is_int($mode))
		{
			$statement->setFetchMode($mode);
		}
		else
		{
			$statement->setFetchMode(\PDO::FETCH_CLASS, $mode);
		}
		
		$statement->execute($params);
		return $statement;
	}
	
	public function queryRaw ($query, $mode=null)
	{
		if ($mode === null)
		{
			return $this->pdo->query($query, \PDO::FETCH_OBJ);
		}
		elseif (is_int($mode))
		{
			return $this->pdo->query($query, $mode);
		}
		else
		{
			return $this->pdo->query($query, \PDO::FETCH_CLASS, $mode);
		}
	}
	
	//== Row select shorthands ==//
	
	public function row ($query)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return $this->rowByArray($query, $params);
	}
	
	public function rowAs ($mode, $query)
	{
		$params = func_get_args();
		$params = array_slice($params, 2);
		return $this->rowByArray($query, $params, 0, $mode);
	}
	
	public function rowByArray ($query, $params=null, $row_offset=null, $mode=null)
	{
		if ($query instanceof QueryBuilder)
		{
			return $query->row($row_offset, $mode);
		}
		
		if ($row_offset === null)
		{
			$row_offset = 0;
		}
		elseif ($row_offset < 0)
		{
			return false;
		}
		
		$statement = $this->queryByArray($query, $params, $mode);
		
		do
		{
			$row = $statement->fetch();
			--$row_offset;
		}
		while ($row_offset >= 0);
		
		return $row;
	}
	
	//== Value select shorthands ==//
	
	public function value ($query)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return $this->valueByArray($query, $params);
	}
	
	public function valueByArray ($query, $params=null, $col_offset=null, $row_offset=null)
	{
		if ($query instanceof QueryBuilder)
		{
			return $query->value($col_offset, $row_offset);
		}
		
		if ($col_offset === null)
		{
			$col_offset = 0;
		}
		elseif ($col_offset < 0)
		{
			return false;
		}
		
		$row = $this->rowByArray($query, $params, $row_offset, \PDO::FETCH_NUM);
		
		if (!isset ($row[$col_offset]))
		{
			return false;
		}
		
		return $row[$col_offset];
	}
	
	//== Column select shorthands ==//
	
	public function col ($query)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return $this->colByArray($query, $params);
	}
	
	public function colByArray ($query, $params=null, $col_offset=null)
	{
		if ($query instanceof QueryBuilder)
		{
			return $query->col($col_offset);
		}
		
		if ($col_offset === null)
		{
			$col_offset = 0;
		}
		elseif ($col_offset < 0)
		{
			return false;
		}
		
		$statement = $this->queryByArray($query, $params, \PDO::FETCH_NUM);
		
		if ($col_offset >= $statement->columnCount())
		{
			return false;
		}
		
		$col = [];
		
		foreach ($statement as $row)
		{
			$col[] = $row[$col_offset];
		}
		
		return $col;
	}
	
	//== Row exists shorthands ==//
	
	public function rowExists ($query)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return $this->rowExistsByArray($query, $params);
	}
	
	public function rowExistsByArray ($query, $params=null, $row_offset=null)
	{
		if ($query instanceof QueryBuilder)
		{
			return $query->rowExists($row_offset);
		}
		
		if ($row_offset === null)
		{
			$row_offset = 0;
		}
		elseif ($row_offset < 0)
		{
			return false;
		}
		
		$statement = $this->queryByArray($query, $params, \PDO::FETCH_NUM);
		return ($statement->rowCount() > $row_offset);
	}
	
	public function rowExistsIn ($table_and_where)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return $this->rowExistsInByArray($table_and_where, $params);
	}
	
	public function rowExistsInByArray ($table_and_where, $params=null)
	{
		$query = $this->limit("SELECT 1 FROM {$table_and_where}", 1);
		return $this->rowExistsByArray($query, $params);
	}
	
	//== Transaction shortcuts ==//
	
	public function begin ()
	{
		if ($this->transaction_level > 0)
		{
			++$this->transaction_level;
			return $this->savepoint("s{$this->transaction_level}", true);
		}
		else
		{
			$result = $this->pdo->beginTransaction();
			
			if ($result)
			{
				++$this->transaction_level;
			}
			
			return $result;
		}
	}
	
	public function commit ()
	{
		if ($this->transaction_level > 1)
		{
			$result = true;
		}
		else
		{
			$result = $this->pdo->commit();
		}
		
		if ($this->transaction_level > 0)
		{
			--$this->transaction_level;
		}
		
		return true;
	}
	
	public function rollback ($savepoint=null, $is_raw=null)
	{
		if ($savepoint === null)
		{
			if ($this->transaction_level > 1)
			{
				$result = $this->rollback("s{$this->transaction_level}", true);
			}
			else
			{
				$result = $this->pdo->rollBack();
			}
			
			if ($this->transaction_level > 0)
			{
				--$this->transaction_level;
			}
			
			return $result;
		}
		else
		{
			if (!$is_raw)
			{
				$savepoint = "s{$this->transaction_level}_{$savepoint}";
			}
			
			return $this->doRollbackTo($savepoint);
		}
	}
	
	public function savepoint ($savepoint, $is_raw=null)
	{
		if (!$is_raw)
		{
			$savepoint = "s{$this->transaction_level}_{$savepoint}";
		}
		
		return $this->doSavepoint($savepoint);
	}
	
	public function inTransaction ($ask_driver=null)
	{
		if ($ask_driver)
		{
			return $this->pdo->inTransaction();
		}
		
		return $this->transaction_level > 0;
	}
	
	//== Escaping ==//
	
	public function ident ($ident)
	{
		if (is_array($ident))
		{
			if (isset ($ident[1]))
			{
				$alias = $ident[1];
			}
			
			$ident = $ident[0];
		}
		else
		{
			$alias = null;
		}
		
		$sql = $this->doIdent($ident);
		
		if ($alias !== null && !is_array($alias))
		{
			$sql .= ' '.$this->doIdent($alias);
		}
		
		return $sql;
	}
	
	public function escapeLike ($string)
	{
		return strtr($string,
		[
			'\\' => '\\\\',
			'%' => '\\%',
			'_' => '\\_',
		]);
	}
	
	public function escapeString ($string)
	{
		return $this->pdo->quote($string);
	}
	
	//== Fluent query builders ==//
	
	public function deleteFrom ($table, $alias=null)
	{
		return $this->createQueryBuilder()->deleteFrom($table, $alias);
	}
	
	public function insertInto ($table, $alias=null)
	{
		return $this->createQueryBuilder()->insertInto($table, $alias);
	}
	
	public function select ($select_list=null)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return $this->createQueryBuilder()->selectByArray($select_list, $params);
	}
	
	public function update ($table, $alias=null)
	{
		return $this->createQueryBuilder()->update($table, $alias);
	}
	
	public function createQueryBuilder ()
	{
		return new QueryBuilder($this);
	}
	
	//== Miscellaneous ==//
	
	public function limit ($query, $limit, $offset=null)
	{
		$offset = (int) $offset;
		
		if ($limit === null && $offset <= 0)
		{
			return $query;
		}
		
		if ($limit !== null)
		{
			$limit = (int) $limit;
			
			if ($offset < 0)
			{
				$limit += $offset;
				$offset = 0;
			}
			
			if ($limit < 0)
			{
				$limit = 0;
			}
		}
		
		return $this->doLimit($query, $limit, $offset);
	}
	
	public function lastInsertId ($table=null, $col=null)
	{
		try
		{
			$this->doLastInsertId($table, $col);
		}
		catch (\PDOException $e)
		{
			return false;
		}
	}
	
	public function lastSequenceId ($sequence)
	{
		return $this->pdo->lastInsertId($sequence);
	}
	
	public function marks ($num_marks, $num_sets=null)
	{
		if (is_array($num_sets) || $num_sets instanceof \Countable)
		{
			$num_sets = count($num_sets);
		}
		
		if ($num_sets === 0)
		{
			return '';
		}
		
		if (is_array($num_marks) || $num_marks instanceof \Countable)
		{
			$num_marks = count($num_marks);
		}
		
		if ($num_marks == 0)
		{
			$marks = '';
		}
		else
		{
			$marks = '?'.str_repeat(', ?', $num_marks - 1);
		}
		
		if ($num_sets === null)
		{
			return $marks;
		}
		else
		{
			return "({$marks})".str_repeat(", ({$marks})", $num_sets - 1);
		}
	}
	
	/**
	 * Replace any Clascade-specific syntax with regular SQL.
	 */
	
	public function normalize ($query, &$params=null, $convert_to_positional=null)
	{
		if ($params === null)
		{
			$params = [];
		}
		
		if ($convert_to_positional === null)
		{
			$convert_to_positional = false;
		}
		
		$pos_params = ($convert_to_positional ? [] : null);
		$i = 0;
		
		$query = preg_replace_callback('/\?|:(?<!::)(?:#|[a-zA-Z0-9_]+)/', function ($match) use (&$params, &$i, &$pos_params)
		{
			$match = $match[0];
			$next_i = $i;
			
			if ($match == ':#')
			{
				return $this->table_prefix;
			}
			elseif ($match == '?')
			{
				$key = $i;
				$next_i = $i + 1;
			}
			elseif (array_key_exists($match, $params))
			{
				// Parameter keyed with ":" prefix.
				
				$key = $match;
			}
			else
			{
				// Parameter keyed without ":" prefix.
				
				$key = substr($match, 1);
			}
			
			if (!array_key_exists($key, $params))
			{
				// This placeholder doesn't have a corresponding
				// parameter. We'll leave the placeholder in the
				// raw query.
				
				return $match;
			}
			
			$param = $params[$key];
			
			if (is_array($param))
			{
				$first_value = array_slice($param, 0, 1);
				$first_value = $first_value[0];
				
				if (is_array($first_value))
				{
					$marks = $this->marks($first_value, $param);
					$param = call_user_func_array('array_merge', $param);
				}
				else
				{
					$marks = $this->marks($param);
				}
				
				$param = new Expression($marks, $param);
			}
			
			if ($param instanceof Expression)
			{
				$exp_params = $param->params;
				$expression = $this->normalize($param->sql, $exp_params, true);
				
				if ($pos_params !== null)
				{
					// Convert to positional.
					
					foreach ($exp_params as $value)
					{
						$pos_params[] = $value;
					}
					
					$i = $next_i;
					return $expression;
				}
				elseif ($match == '?')
				{
					array_splice($params, $i, 1, $exp_params);
					$i += count($exp_params);
					return $expression;
				}
				else
				{
					// Remove the unflattened value from the parameters.
					
					unset ($params[$key]);
					
					$key = substr($match, 1);
					$replacement = '';
					$pos = 0;
					$suffix = 0;
					
					foreach ($exp_params as $value)
					{
						$next_pos = strpos($expression, '?', $pos);
						
						if ($next_pos === false)
						{
							break;
						}
						
						$suffix = $this->getNextNamedParamSuffix($params, "{$key}_", $suffix);
						$params[":{$key}_{$suffix}"] = $value;
						$replacement .= substr($expression, $pos, $next_pos - $pos).":{$key}_{$suffix}";
						$pos = $next_pos + 1;
					}
					
					$replacement .= substr($expression, $pos);
					return $replacement;
				}
			}
			elseif ($pos_params !== null)
			{
				// Convert to positional.
				
				$pos_params[] = $param;
				$i = $next_i;
				return '?';
			}
			
			$i = $next_i;
			return $match;
		}, $query);
		
		if ($convert_to_positional)
		{
			$params = $pos_params;
		}
		
		return $query;
	}
	
	public function getNextNamedParamSuffix ($params, $prefix, $suffix=null)
	{
		if ($suffix === null)
		{
			$suffix = 0;
		}
		
		while (array_key_exists("{$prefix}{$suffix}", $params) || array_key_exists(":{$prefix}{$suffix}", $params))
		{
			++$suffix;
		}
		
		return $suffix;
	}
	
	//== Expressions ==//
	
	public function expr ($sql)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return $this->exprByArray($sql, $params);
	}
	
	public function exprByArray ($sql, $params=null)
	{
		return new Expression($sql, $params);
	}
	
	public function now ()
	{
		return $this->expr('NOW()');
	}
	
	//== Platform methods ==//
	
	public function doLimit ($query, $limit, $offset)
	{
		if ($limit !== null)
		{
			$query .= " LIMIT {$limit}";
		}
		
		if ($offset > 0)
		{
			$query .= " OFFSET {$offset}";
		}
		
		return $query;
	}
	
	public function doIdent ($ident)
	{
		return '"'.str_replace('"', '""', $ident).'"';
	}
	
	public function doLastInsertId ($table, $col)
	{
		return $this->pdo->lastInsertId();
	}
	
	public function doRollbackTo ($savepoint)
	{
		return $this->query('ROLLBACK TO SAVEPOINT '.$this->ident($savepoint));
	}
	
	public function doSavepoint ($savepoint)
	{
		return $this->query('SAVEPOINT '.$this->ident($savepoint));
	}
}
