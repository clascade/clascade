<?php

namespace Clascade\DB\Platform;

class DBMysql extends \Clascade\DB\Platform
{
	public function doLimit ($query, $limit, $offset)
	{
		if ($limit === null)
		{
			$limit = '18446744073709551615';
		}
		
		return parent::doLimit($query, $limit, $offset);
	}
	
	public function doIdent ($ident)
	{
		return '`'.str_replace('`', '``', $ident).'`';
	}
}
