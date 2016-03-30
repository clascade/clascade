<?php

namespace Clascade;

class DB extends StaticProxy
{
	public static function getProviderClass ()
	{
		return 'Clascade\DB\ConnectionManager';
	}
	
	//== Connection management ==//
	
	public static function get ($connection_name=null)
	{
		return static::provider()->get($connection_name);
	}
	
	public static function replaceConnection ($connection_name, $new_connection)
	{
		return static::provider()->replaceConnection($connection_name, $new_connection);
	}
	
	public static function restoreConnection ($connection_name)
	{
		return static::provider()->restoreConnection($connection_name);
	}
	
	public static function resolveConnectionName($connection_name=null)
	{
		return static::provider()->resolveConnectionName($connection_name);
	}
	
	//== Basic queries ==//
	
	public static function query ($query)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return static::provider()->get()->queryByArray($query, $params);
	}
	
	public static function queryAs ($mode, $query)
	{
		$params = func_get_args();
		$params = array_slice($params, 2);
		return static::provider()->get()->queryByArray($query, $params, $mode);
	}
	
	public static function queryByArray ($query, $params=null, $mode=null)
	{
		return static::provider()->get()->queryByArray($query, $params, $mode);
	}
	
	public static function queryRaw ($query, $mode=null)
	{
		return static::provider()->get()->queryRaw($query, $mode);
	}
	
	//== Row select shorthands ==//
	
	public static function row ($query)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return static::provider()->get()->rowByArray($query, $params);
	}
	
	public static function rowAs ($mode, $query)
	{
		$params = func_get_args();
		$params = array_slice($params, 2);
		return static::provider()->get()->rowByArray($query, $params, 0, $mode);
	}
	
	public static function rowByArray ($query, $params=null, $row_offset=null, $mode=null)
	{
		return static::provider()->get()->rowByArray($query, $params, $row_offset, $mode);
	}
	
	//== Value select shorthands ==//
	
	public static function value ($query)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return static::provider()->get()->valueByArray($query, $params);
	}
	
	public static function valueByArray ($query, $params=null, $col_offset=null, $row_offset=null)
	{
		return static::provider()->get()->valueByArray($query, $params, $col_offset, $row_offset);
	}
	
	//== Column select shorthands ==//
	
	public static function col ($query)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return static::provider()->get()->colByArray($query, $params);
	}
	
	public static function colByArray ($query, $params=null, $col_offset=null)
	{
		return static::provider()->get()->colByArray($query, $params, $col_offset);
	}
	
	//== Row exists shorthands ==//
	
	public static function rowExists ($query)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return static::provider()->get()->rowExistsByArray($query, $params);
	}
	
	public static function rowExistsByArray ($query, $params=null, $row_offset=null)
	{
		return static::provider()->get()->rowExistsByArray($query, $params, $row_offset);
	}
	
	public static function rowExistsIn ($table_and_where)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return static::provider()->get()->rowExistsInByArray($table_and_where, $params);
	}
	
	public static function rowExistsInByArray ($table_and_where, $params=null)
	{
		return static::provider()->get()->rowExistsInByArray($table_and_where, $params);
	}
	
	//== Transaction shortcuts ==//
	
	public static function begin ()
	{
		return static::provider()->get()->begin();
	}
	
	public static function commit ()
	{
		return static::provider()->get()->commit();
	}
	
	public static function rollback ($savepoint=null, $is_raw=null)
	{
		return static::provider()->get()->rollback($savepoint, $is_raw);
	}
	
	public static function savepoint ($savepoint, $is_raw=null)
	{
		return static::provider()->get()->savepoint($savepoint, $is_raw);
	}
	
	public static function inTransaction ($ask_driver=null)
	{
		return static::provider()->get()->inTransaction($ask_driver);
	}
	
	//== Escaping ==//
	
	public static function ident ($ident)
	{
		return static::provider()->get()->ident($ident);
	}
	
	public static function escapeLike ($string)
	{
		return static::provider()->get()->escapeLike($string);
	}
	
	public static function escapeString ($string)
	{
		return static::provider()->get()->escapeString($string);
	}
	
	//== Fluent query builders ==//
	
	public static function deleteFrom ($table, $alias=null)
	{
		return static::provider()->get()->deleteFrom($table, $alias);
	}
	
	public static function insertInto ($table, $alias=null)
	{
		return static::provider()->get()->insertInto($table, $alias);
	}
	
	public static function select ($select_list=null)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return static::provider()->get()->createQueryBuilder()->selectByArray($select_list, $params);
	}
	
	public static function update ($table, $alias=null)
	{
		return static::provider()->get()->update($table, $alias);
	}
	
	public static function createQueryBuilder ()
	{
		return static::provider()->get()->createQueryBuilder();
	}
	
	//== Miscellaneous ==//
	
	public static function limit ($query, $limit, $offset=null)
	{
		return static::provider()->get()->limit($query, $limit, $offset);
	}
	
	public static function lastInsertId ($table=null, $col=null)
	{
		return static::provider()->get()->lastInsertId($table, $col);
	}
	
	public static function lastSequenceId ($sequence)
	{
		return static::provider()->get()->lastSequenceId($sequence);
	}
	
	public static function marks ($num_marks, $num_sets=null)
	{
		return static::provider()->get()->marks($num_marks, $num_sets);
	}
	
	/**
	 * Replace any Clascade-specific syntax with regular SQL.
	 */
	
	public static function normalize ($query, &$params=null, $convert_to_positional=null)
	{
		return static::provider()->get()->normalize($query, $params, $convert_to_positional);
	}
	
	//== Expressions ==//
	
	public static function expr ($sql)
	{
		$params = func_get_args();
		$params = array_slice($params, 1);
		return static::provider()->get()->exprByArray($sql, $params);
	}
	
	public static function exprByArray ($sql, $params=null)
	{
		return static::provider()->get()->exprByArray($sql, $params);
	}
	
	public static function now ()
	{
		return static::provider()->get()->now();
	}
}
