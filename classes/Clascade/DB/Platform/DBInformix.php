<?php

namespace Clascade\DB\Platform;

class DBInformix extends \Clascade\DB\Platform
{
	public function doLimit ($query, $limit, $offset)
	{
		return preg_replace_callback('/\sFROM\s/i', function ($match) use ($limit, $offset)
		{
			$replacement = '';
			
			if ($offset > 0)
			{
				$replacement .= " SKIP {$offset}";
			}
			
			if ($limit !== null)
			{
				$replacement .= " FIRST {$limit}";
			}
			
			return $replacement.$match[0];
		}, $query);
	}
}
