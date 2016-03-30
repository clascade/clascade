<?php

namespace Clascade\Session;

class Manager
{
	/**
	 * Open a connection to an existing session identified in the
	 * request, or create a new one.
	 */
	
	public function open ()
	{
	}
	
	/**
	 * Commit the written data and close the connection to the
	 * session.
	 */
	
	public function close ()
	{
	}
	
	/**
	 * Return the session data as a string.
	 */
	
	public function read ()
	{
		return false;
	}
	
	/**
	 * Write data to the session as a string.
	 */
	
	public function write ($data)
	{
	}
	
	/**
	 * Get the ID associated with the current active session.
	 */
	
	public function id ()
	{
		return '';
	}
	
	/**
	 * Destroy all data in the current session and create a new one
	 * with a new ID.
	 */
	
	public function clear ()
	{
	}
	
	/**
	 * Create a new session with a new ID, without destroying the
	 * previous session.
	 */
	
	public function createNew ()
	{
	}
}
