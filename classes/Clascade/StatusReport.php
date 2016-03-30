<?php

namespace Clascade;
use Clascade\Util\Crypto;

class StatusReport
{
	public $key;
	
	public $errors;
	public $messages;
	public $values;
	
	public function __construct ($source=null)
	{
		if ($source instanceof Validator)
		{
			$this->populateFromValidator($source);
		}
		else
		{
			$this->populateFromArray(is_array($source) ? $source : []);
		}
	}
	
	public function populateFromValidator ($validator)
	{
		$this->errors = $validator->errors;
		$this->messages = $validator->messages;
		$this->values = [];
		$redactions = array_flip($validator->redactions);
		
		foreach ($validator->init_values as $key => $value)
		{
			if (!isset ($redactions[$key]))
			{
				$this->values[$key] = $value;
			}
		}
	}
	
	public function populateFromArray ($array)
	{
		$this->errors = (isset ($array['errors']) ? (array) $array['errors'] : []);
		$this->messages = (isset ($array['messages']) ? (array) $array['messages'] : []);
		$this->values = (isset ($array['values']) ? (array) $array['values'] : []);
	}
	
	public function value ($field_name, $default=null)
	{
		return (isset ($this->values[$field_name]) ? $this->values[$field_name] : $default);
	}
	
	//== Storage and retrieval ==//
	
	public function save ()
	{
		$reports = (array) session_get('clascade.status');
		$this->key = $this->saveTo($reports);
		$max_reports = conf('common.status.max-reports');
		
		if ($max_reports !== null)
		{
			// Limit the number of reports we're keeping in a session at a time.
			
			if (count($reports) > $max_reports)
			{
				array_splice($reports, 0, count($report_list) - $max_reports);
			}
		}
		
		session_set('clascade.status', $reports);
		return $this->key;
	}
	
	public function load ($key)
	{
		$reports = (array) session_get('clascade.status');
		$this->loadFrom($key, $reports);
	}
	
	public function saveTo (&$report_list)
	{
		$encrypted = null;
		
		$report =
		[
			'errors' => $this->errors,
			'messages' => $this->messages,
			'values' => $this->values,
		];
		
		// Serialize the report data as JSON.
		
		$report = json_encode($report);
		
		// Prepare an array with 3 entries. We'll feed this into
		// Crypto::encrypt to retrieve an additional derived key
		// which will become the report ID.
		
		$keys = array_fill(0, 3, null);
		
		do
		{
			// Generate a 48-bit report key to serve as a unique
			// identifier for the report.
			
			$key = rand_bytes(6);
			
			// Derive a master key by hashing together the report
			// key and the session's encryption key.
			
			$master_key = hash_hmac('sha512', $key, Session::getKey(), true);
			
			// Encrypt the report data. This should also produce
			// three derived keys, the last of which will be our
			// report ID.
			
			$encrypted = Crypto::encrypt($master_key, $report, $keys);
			
		} // Ensure that the derived report ID is unique.
		while (isset ($report_list[$keys[2]]));
		
		$report_list[$keys[2]] = $encrypted;
		return $key;
	}
	
	public function loadFrom ($key, $report_list=null)
	{
		$this->key = $key;
		
		// Derive a master key by hashing together the report
		// key and the session's encryption key.
		
		$master_key = hash_hmac('sha512', $key, Session::getKey(), true);
		
		// Derive the encryption key, HMAC key, and report ID
		// from the master key.
		
		$keys = Crypto::hkdf('sha512', $master_key, 3);
		$report_id = $keys[2];
		
		if (isset ($report_list[$report_id]))
		{
			// Decrypt the report data.
			
			$report = Crypto::decrypt($master_key, $report_list[$report_id], $keys);
			
			if ($report !== false)
			{
				$report = json_decode($report, true);
				$this->errors = $report['errors'];
				$this->messages = $report['messages'];
				$this->values = $report['values'];
				return true;
			}
		}
		
		return false;
	}
	
	//== Report creation shortcuts ==//
	
	public static function error ($error, $values=null)
	{
		return make('Clascade\StatusReport',
		[
			'errors' => [null => [$error]],
			'values' => (array) $values,
		]);
	}
	
	public static function message ($message, $values=null)
	{
		return make('Clascade\StatusReport',
		[
			'messages' => [$message],
			'values' => (array) $values,
		]);
	}
}
