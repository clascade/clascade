<?php

namespace Clascade\DB;
use Clascade\Core;
use Clascade\Util\Str;

class QueryBuilder
{
	public $db;
	
	public $type;
	public $parts;
	public $sql;
	public $params;
	public $uses_named_params;
	
	public function __construct ($db)
	{
		$this->db = $db;
		$this->reset();
	}
	
	//== Query execution ==//
	
	public function query ($mode=null)
	{
		$query = $this->getQuery();
		
		if ($query === false)
		{
			return false;
		}
		
		return $this->db->queryByArray($query, $this->params, $mode);
	}
	
	public function row ($row_offset=null, $mode=null)
	{
		if ($this->parts['limit'] === null || $this->parts['limit'] > 1)
		{
			$this->limit(1);
		}
		
		$query = $this->getQuery();
		
		if ($query === false)
		{
			return false;
		}
		
		return $this->db->rowByArray($query, $this->params, $row_offset, $mode);
	}
	
	public function value ($col_offset=null, $row_offset=null)
	{
		if ($this->parts['limit'] === null || $this->parts['limit'] > 1)
		{
			$this->limit(1);
		}
		
		$query = $this->getQuery();
		
		if ($query === false)
		{
			return false;
		}
		return $this->db->valueByArray($query, $this->params, $col_offset, $row_offset);
	}
	
	public function col ($col_offset=null)
	{
		$query = $this->getQuery();
		
		if ($query === false)
		{
			return false;
		}
		
		return $this->db->colByArray($query, $this->params, $col_offset);
	}
	
	public function rowExists ($row_offset)
	{
		if ($this->parts['limit'] === null || $this->parts['limit'] > 1)
		{
			$this->limit(1);
		}
		
		$query = $this->getQuery();
		
		if ($query === false)
		{
			return false;
		}
		
		return $this->db->rowExistsByArray($query, $this->params, $row_offset);
	}
	
	public function reset ()
	{
		$this->type = null;
		$this->parts =
		[
			'select' => null,
			'distinct' => false,
			'from' => null,
			'join' => [],
			'cols' => null,
			'values' => null,
			'set' => null,
			'where' => null,
			'group-by' => null,
			'having' => null,
			'order-by' => null,
			'limit' => null,
			'offset' => null,
		];
		$this->sql = null;
		$this->params = null;
		$this->uses_named_params = false;
		return $this;
	}
	
	//== Commands ==//
	
	public function deleteFrom ($table, $alias=null)
	{
		$this->type = 'delete';
		return $this->from($table, $alias);
	}
	
	public function insertInto ($table, $alias=null)
	{
		$this->type = 'insert';
		return $this->from($table, $alias);
	}
	
	public function selectByArray ($select=null, $params=null)
	{
		$this->sql = null;
		$this->type = 'select';
		
		if ($select === null)
		{
			$select = new Expression('*');
		}
		elseif (is_array($select))
		{
			$params = [];
			
			foreach ($select as $key => $value)
			{
				if ($value instanceof Expression)
				{
					$params[] = $value;
					$value = '?';
				}
				else
				{
					$value = $this->db->ident($value);
				}
				
				if (!is_array($value) && !is_int($key))
				{
					$value .= ' '.$this->db->ident($key);
				}
				
				$select[$key] = $value;
			}
			
			$select = implode(', ', $select);
			$select = new Expression($select, $params);
		}
		elseif (!($select instanceof Expression))
		{
			$select = new Expression($select, $params);
		}
		
		$this->parts['select'] = $select;
		return $this;
	}
	
	public function select ($select_list=null)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return $this->selectByArray($select_list, $params);
	}
	
	public function update ($table, $alias=null)
	{
		$this->type = 'update';
		return $this->from($table, $alias);
	}
	
	//== Components ==//
	
	public function distinct ($distinct=null)
	{
		$this->sql = null;
		$this->parts['distinct'] = ($distinct === null ? true : $distinct);
		return $this;
	}
	
	public function from ($table, $alias=null)
	{
		$this->sql = null;
		
		if (is_array($table))
		{
			$params = [];
			
			foreach ($table as $key => $value)
			{
				if ($value instanceof Expression)
				{
					$params[] = $value;
					$value = '?';
				}
				else
				{
					$value = $this->db->ident($value);
				}
				
				if (!is_array($value) && !is_int($key))
				{
					$value .= ' '.$this->db->ident($key);
				}
				
				$table[$key] = $value;
			}
			
			$table = implode(', ', $table);
			$table = new Expression($table, $params);
		}
		elseif (!($table instanceof Expression))
		{
			$table = ($alias === null ? $table : [$table, $alias]);
			$table = $this->db->ident($table);
			$table = new Expression($table);
		}
		
		$this->parts['from'] = $table;
		return $this;
	}
	
	public function join ($table, $condition)
	{
		$params = func_get_args();
		$params = array_slice($params, 2);
		return $this->joinByArray($table, $condition, $params);
	}
	
	public function joinByArray ($table, $condition, $params=null)
	{
		return $this->joinCustom('inner', $table, $condition, $params);
	}
	
	public function innerJoin ($table, $condition)
	{
		$params = func_get_args();
		$params = array_slice($params, 2);
		return $this->innerJoinByArray($table, $condition, $params);
	}
	
	public function innerJoinByArray ($table, $condition, $params=null)
	{
		return $this->innerJoinCustom('inner', $table, $condition, $params);
	}
	
	public function leftJoin ($table, $condition)
	{
		$params = func_get_args();
		$params = array_slice($params, 2);
		return $this->leftJoinByArray($table, $condition, $params);
	}
	
	public function leftJoinByArray ($table, $condition, $params=null)
	{
		return $this->joinCustom('left', $table, $condition, $params);
	}
	
	public function rightJoin ($table, $condition)
	{
		$params = func_get_args();
		$params = array_slice($params, 2);
		return $this->rightJoinByArray($table, $condition, $params);
	}
	
	public function rightJoinByArray ($table, $condition, $params=null)
	{
		return $this->joinCustom('right', $table, $condition, $params);
	}
	
	public function joinCustom ($type, $table, $condition, $params=null)
	{
		$this->sql = null;
		
		if (!($table instanceof Expression))
		{
			$table = $this->db->ident($table);
			$table = new Expression($table);
		}
		
		if (is_array($condition))
		{
			$params = [];
			
			foreach ($condition as $key => $value)
			{
				$params[] = $value;
				$condition[$key] = $this->db->ident($key).'=?';
			}
			
			$condition = implode(' AND ', $condition);
			$condition = new Expression($condition, $params);
		}
		elseif (!($condition instanceof Expression))
		{
			$condition = new Expression($condition, (array) $params);
		}
		
		$this->parts['join'][] =
		[
			'type' => $type,
			'table' => $table,
			'condition' => $condition,
		];
		return $this;
	}
	
	public function cols ($cols)
	{
		$this->sql = null;
		
		if (!is_array($cols))
		{
			$cols = preg_split('/\s*,\s*/', $cols);
		}
		
		$this->parts['cols'] = $cols;
		return $this;
	}
	
	public function values ($values)
	{
		$this->sql = null;
		
		if (!is_array($values))
		{
			$values = func_get_args();
		}
		
		if (!(array_key_exists(0, $values) && is_array($values[0])))
		{
			$values = [$values];
		}
		
		$this->parts['values'] = $values;
		return $this;
	}
	
	public function set ($set)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return $this->setByArray($set, $params);
	}
	
	public function setByArray ($set, $params=null)
	{
		$this->sql = null;
		
		if ($this->type == 'insert')
		{
			$this->parts['cols'] = array_keys($set);
			$this->parts['values'] = [array_values($set)];
			return $this;
		}
		
		if (is_array($set))
		{
			$params = [];
			
			foreach ($set as $key => $value)
			{
				$params[] = $value;
				$set[$key] = $this->db->ident($key).'=?';
			}
			
			$set = implode(', ', $set);
			$set = new Expression($set, $params);
		}
		elseif (!($set instanceof Expression))
		{
			$set = new Expression($set, (array) $params);
		}
		
		$this->parts['set'] = $set;
		return $this;
	}
	
	public function where ($where)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return $this->whereByArray($where, $params);
	}
	
	public function whereByArray ($where, $params=null)
	{
		$this->sql = null;
		
		if (is_array($where))
		{
			$params = [];
			
			foreach ($where as $key => $value)
			{
				$params[] = $value;
				$where[$key] = $this->db->ident($key).'=?';
			}
			
			$where = implode(' AND ', $where);
			$where = new Expression($where, $params);
		}
		elseif (!($where instanceof Expression))
		{
			$where = new Expression($where, (array) $params);
		}
		
		$this->parts['where'] = $where;
		return $this;
	}
	
	public function groupBy ($group)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return $this->groupByArray($group, $params);
	}
	
	public function groupByArray ($group, $params=null)
	{
		$this->sql = null;
		
		if (is_array($group))
		{
			$params = [];
			
			foreach ($group as $key => $value)
			{
				if ($value instanceof Expression)
				{
					$params[] = $value;
					$value = '?';
				}
				else
				{
					$value = $this->db->ident($value);
				}
				
				$group[$key] = $value;
			}
			
			$group = implode(', ', $group);
			$group = new Expression($group, $params);
		}
		elseif (!($group instanceof Expression))
		{
			$group = new Expression($group, (array) $params);
		}
		
		$this->parts['group-by'] = $group;
		return $this;
	}
	
	public function having ($having)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return $this->havingByArray($having, $params);
	}
	
	public function havingByArray ($having, $params=null)
	{
		$this->sql = null;
		
		if (is_array($having))
		{
			$params = [];
			
			foreach ($having as $key => $value)
			{
				$params[] = $value;
				$having[$key] = $this->db->ident($key).'=?';
			}
			
			$having = implode(' AND ', $having);
			$having = new Expression($having, $params);
		}
		elseif (!($having instanceof Expression))
		{
			$having = new Expression($having, (array) $params);
		}
		
		$this->parts['having'] = $having;
		return $this;
	}
	
	public function orderBy ($order)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return $this->orderByArray($order, $params);
	}
	
	public function orderByArray ($order, $params=null)
	{
		$this->sql = null;
		
		if (is_array($order))
		{
			$params = [];
			
			foreach ($order as $key => $value)
			{
				if ($value instanceof Expression)
				{
					$params[] = $value;
					$value = '?';
				}
				else
				{
					if (!is_array($value) && !is_int($key))
					{
						$value = [$key, $value];
					}
					
					$value = (array) $value;
					$value[0] = $this->db->ident($value[0]);
					
					if (isset ($value[1]) && (!$value[1] || Str::lowerAscii($value[1]) == 'desc'))
					{
						$value = "{$value[0]} DESC";
					}
					else
					{
						$value = $value[0];
					}
				}
				
				$order[$key] = $value;
			}
			
			$order = implode(', ', $order);
			$order = new Expression($order, $params);
		}
		elseif (!($order instanceof Expression))
		{
			$order = new Expression($order, (array) $params);
		}
		
		$this->parts['order-by'] = $order;
		return $this;
	}
	
	public function limit ($limit=null, $offset=null)
	{
		$this->sql = null;
		$this->parts['limit'] = $limit;
		
		if ($offset !== null)
		{
			return $this->offset($offset);
		}
		
		return $this;
	}
	
	public function offset ($offset=null)
	{
		$this->sql = null;
		$this->parts['offset'] = $offset;
		return $this;
	}
	
	//== Query builders ==//
	
	public function build ()
	{
		switch ($this->type)
		{
		case 'delete':
			$this->buildDelete();
			break;
		
		case 'insert':
			$this->buildInsert();
			break;
		
		case 'select':
			$this->buildSelect();
			break;
		
		case 'update':
			$this->buildUpdate();
			break;
		
		default:
			$this->params = [];
			$this->sql = '';
		}
	}
	
	public function buildDelete ()
	{
		$this->params = [];
		
		if ($this->parts['from'] === null)
		{
			$this->sql = false;
			return;
		}
		
		$this->sql = 'DELETE FROM ?';
		$this->params[] = $this->parts['from'];
		
		if ($this->parts['where'] !== null)
		{
			$this->sql .= ' WHERE (?)';
			$this->params[] = $this->parts['where'];
		}
	}
	
	public function buildInsert ()
	{
		$this->params = [];
		
		if ($this->parts['from'] === null || empty ($this->parts['cols']) || empty ($this->parts['values']))
		{
			$this->sql = false;
			return;
		}
		
		$cols = [];
		
		foreach ($this->parts['cols'] as $col)
		{
			$cols[] = $this->db->ident($col);
		}
		
		$cols = implode(', ', $cols);
		$cols = new Expression($cols);
		
		$this->sql = "INSERT INTO ? (?) VALUES ?";
		$this->params[] = $this->parts['from'];
		$this->params[] = $cols;
		$this->params[] = $this->parts['values'];
	}
	
	public function buildSelect ()
	{
		$this->params = [];
		
		if (empty ($this->parts['select']))
		{
			$this->sql = false;
			return;
		}
		
		if ($this->parts['distinct'])
		{
			$this->sql = 'SELECT DISTINCT ?';
		}
		else
		{
			$this->sql = 'SELECT ?';
		}
		
		$this->params[] = $this->parts['select'];
		
		if ($this->parts['from'] !== null)
		{
			$this->sql .= ' FROM ?';
			$this->params[] = $this->parts['from'];
		}
		
		foreach ($this->parts['join'] as $join)
		{
			$this->sql .= ' '.Str::upperAscii($join['type']).' JOIN ? ON (?)';
			$this->params[] = $join['table'];
			$this->params[] = $join['condition'];
		}
		
		if ($this->parts['where'] !== null)
		{
			$this->sql .= ' WHERE (?)';
			$this->params[] = $this->parts['where'];
		}
		
		if ($this->parts['group-by'] !== null)
		{
			$this->sql .= ' GROUP BY ?';
			$this->params[] = $this->parts['group-by'];
		}
		
		if ($this->parts['having'] !== null)
		{
			$this->sql .= ' HAVING (?)';
			$this->params[] = $this->parts['having'];
		}
		
		if ($this->parts['order-by'] !== null)
		{
			$this->sql .= ' ORDER BY ?';
			$this->params[] = $this->parts['order-by'];
		}
		
		if ($this->parts['limit'] !== null || $this->parts['offset'] !== null)
		{
			$this->sql = $this->db->limit($this->sql, $this->parts['limit'], $this->parts['offset']);
		}
	}
	
	public function buildUpdate ()
	{
		$this->params = [];
		
		if ($this->parts['from'] === null)
		{
			$this->sql = false;
			return;
		}
		
		if (!empty ($this->parts['set']))
		{
			$set = $this->parts['set'];
		}
		elseif (!empty ($this->parts['cols']) && !empty ($this->parts['values']) && array_key_exists(0, $this->parts['values']))
		{
			$set = [];
			$params = [];
			
			foreach ($this->parts['cols'] as $key => $col)
			{
				$params[] = $this->parts['values'][0][$key];
				$set[$col] = $this->db->ident($col).'=?';
			}
			
			$set = implode(', ', $set);
			$set = new Expression($set, $params);
		}
		else
		{
			$this->sql = false;
			return;
		}
		
		$this->sql = 'UPDATE ? SET ?';
		$this->params[] = $this->parts['from'];
		$this->params[] = $set;
		
		if ($this->parts['where'] !== null)
		{
			$this->sql .= ' WHERE (?)';
			$this->params[] = $this->parts['where'];
		}
	}
	
	public function getExpression ()
	{
		$query = $this->getQuery();
		
		if ($query === false)
		{
			return false;
		}
		
		return new Expression($query, $this->params);
	}
	
	public function getQuery ()
	{
		if ($this->sql === null)
		{
			$this->build();
		}
		
		return $this->sql;
	}
	
	public function getParams ()
	{
		if ($this->getQuery() === false)
		{
			return false;
		}
		
		return $this->params;
	}
}
