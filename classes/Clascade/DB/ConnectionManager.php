<?php

namespace Clascade\DB;
use Clascade\Exception;

class ConnectionManager
{
	public $connections = [];
	public $replaced_connections = [];
	
	public function get ($connection_name=null)
	{
		if ($connection_name === null)
		{
			$connection_name = 'default';
		}
		
		if ($connection_name == 'default' && isset ($this->connections['default']))
		{
			// Shortcut for default connection, which
			// doesn't require name resolution.
			
			return $this->connections['default'];
		}
		elseif ($connection_name instanceof Platform)
		{
			return $connection_name;
		}
		elseif ($connection_name instanceof \PDO)
		{
			$pdo = $connection_name;
			$conf = [];
			$connection_name = null;
		}
		else
		{
			if (is_array($connection_name))
			{
				// Connection information was provided directly.
				// We'll use it instead of loading from conf.
				
				$conf = $connection_name;
				$connection_name = null;
			}
			else
			{
				// Resolve the connection name.
				
				$resolved_connection_name = $this->resolveConnectionName($connection_name);
				
				if ($connection_name === false)
				{
					// Unconfigured connection name.
					
					$message = "Attempt to establish database connection named \"{$resolved_connection_name}\" not present in configuration.";
					
					if ($connection_name != $resolved_connection_name)
					{
						$message .= " Connection name was resolved from \"{$connection_name}\".";
					}
					
					throw new Exception\UnrecognizedDBConnectionNameException($message);
				}
				
				// Use a cached connection if available.
				
				if (isset ($this->connections[$resolved_connection_name]))
				{
					return $this->connections[$resolved_connection_name];
				}
				
				$connection_name = $resolved_connection_name;
				$conf = conf("db.{$connection_name}");
			}
			
			$pdo_class = isset ($conf['pdo-class']) ? $conf['pdo-class'] : '\PDO';
			
			// Attempt to connect.
			
			try
			{
				$pdo = make($pdo_class,
					$conf['dsn'],
					isset ($conf['username']) ? $conf['username'] : '',
					isset ($conf['password']) ? $conf['password'] : '',
					isset ($conf['driver-options']) ? $conf['driver-options'] : []
				);
			}
			catch (\PDOException $e)
			{
				throw new Exception\DBConnectionFailedException("Connection to database \"{$connection_name}\" failed with error: {$pdo_message}", 0, $e);
			}
		}
		
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		
		// Prepare the wrapper.
		
		if (isset ($conf['platform-class']))
		{
			$platform_class = $conf['platform-class'];
		}
		else
		{
			$platform_class = ucfirst($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
			$platform_class = "Clascade\\DB\\Platform\\DB{$platform_class}";
			
			if (!class_exists($platform_class))
			{
				$platform_class = 'Clascade\\DB\\Platform';
			}
		}
		
		$db = make($platform_class, $pdo);
		$db->connection_name = $connection_name;
		
		if (isset ($conf['table-prefix']))
		{
			$db->table_prefix = $conf['table-prefix'];
		}
		
		if ($connection_name !== null)
		{
			// Cache the connection.
			
			$this->connections[$connection_name] = $db;
		}
		
		return $db;
	}
	
	public function replaceConnection ($connection_name, $new_connection)
	{
		if ($connection_name === null)
		{
			$connection_name = 'default';
		}
		elseif ($connection_name != 'default')
		{
			$connection_name = $this->resolveConnectionName($connection_name);
		}
		
		$this->replaced_connections[$connection_name][] = $this->connections[$connection_name];
		$this->connections[$connection_name] = $new_connection;
	}
	
	public function restoreConnection ($connection_name)
	{
		if ($connection_name === null)
		{
			$connection_name = 'default';
		}
		elseif ($connection_name != 'default')
		{
			$connection_name = $this->resolveConnectionName($connection_name);
		}
		
		$this->connections[$connection_name] = array_pop($this->replaced_connections[$connection_name]);
	}
	
	public function resolveConnectionName ($connection_name=null)
	{
		if ($connection_name === null)
		{
			$connection_name = 'default';
		}
		
		$conf = conf("db.{$connection_name}");
		
		while ($connection_name != 'default' && is_string($conf))
		{
			$connection_name = $conf;
			$conf = conf("db.{$connection_name}");
		}
		
		if ($conf === null)
		{
			return false;
		}
		
		return $connection_name;
	}
}
