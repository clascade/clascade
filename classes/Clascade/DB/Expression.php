<?php

namespace Clascade\DB;

/**
 * Database query expression.
 *
 * This class represents an SQL expression with associated parameter
 * values. A DB\Expression object may be passed to one of the DB query
 * functions as a parameter value, and it will be automatically
 * incorporated into the overall query at the location of the
 * corresponding placeholder.
 *
 * For example:
 *
 * $db->query('UPDATE foo SET bar=?',
 *   new DB\Expression('MAX(baz, ?)', [100])
 * );
 *
 * This is equivalent to:
 *
 * $db->query('UPDATE foo SET bar=MAX(baz, ?)', 100);
 *
 * The are cases where abstracting part of the expression like this can
 * simplify your code. For example, $db->now() returns a DB\Expression
 * containing 'NOW()', which is useful when preparing INSERT or UPDATE
 * queries.
 */

class Expression
{
	public $sql;
	public $params;
	
	public function __construct ($sql, $params=null)
	{
		$this->sql = $sql;
		$this->params = (array) $params;
	}
}
