<?php

namespace Clascade\Router;
use Clascade\Core;
use Clascade\Exception;
use Clascade\Lang;
use Clascade\Router;
use Clascade\StatusReport;
use Clascade\Util\SimpleElement;
use Clascade\Validator;
use Clascade\View\FieldVar;

class Page extends RouteTarget
{
	public $controller;
	public $param_path;
	
	// Inherited from RouteTarget:
	//   public $route_path;
	//   public $request_path;
	//   public $path_matches = [];
	
	public $initialized = false;
	
	public $head_elements = [];
	public $lang;
	public $return_to;
	public $status;
	public $view_handlers = [];
	public $view_nonce = 0;
	
	public function __construct ($route_path, $controller, $param_path=null)
	{
		$this->controller = $controller;
		$this->param_path = (string) $param_path;
		
		parent::__construct($route_path);
	}
	
	public function init ()
	{
		$this->initialized = true;
		$this->lang = Lang::getBestMatch();
		
		$return_to_name = conf('common.field-names.return-to');
		$this->return_to = (isset ($_POST[$return_to_name]) ? $_POST[$return_to_name] : $_SERVER['REQUEST_URI']);
		
		$this->status = make('Clascade\StatusReport');
		
		$status_name = conf('common.field-names.status');
		
		if (isset ($_GET[$status_name]))
		{
			$this->status->load(nice64_encode($_GET[$status_name]));
		}
	}
	
	public function load ()
	{
		if (!$this->initialized)
		{
			$this->init();
		}
		
		try
		{
			return Core::load($this->controller, $this);
		}
		catch (Exception\ValidationException $e)
		{
			// There was an uncaught validation failure. Turn
			// the results into a report and redirect the user
			// to the page.
			
			$this->redirectWithStatus($e->validator);
		}
		catch (Exception\PageLoadedException $e)
		{
			// This is a more graceful equivalent to exit().
			// Something invoked by the controller has decided
			// that the controller's job is done, and the
			// controller chose not to intercept it.
		}
	}
	
	public function reroute ($base, $request_uri, $run_middleware=null)
	{
		if ($run_middleware === null)
		{
			$run_middleware = true;
		}
		
		// Find a matching controller within the new base.
		
		$target = Router::findController($base, $request_uri);
		
		if ($target === false)
		{
			$target->notFound();
		}
		
		if ($target instanceof RedirectTarget)
		{
			// Add trailing slash to directory requests.
			
			redirect("{$this->request_path}/".request_query());
		}
		
		if ($run_middleware)
		{
			if (!Hook::handle("route:{$target->route_path}"))
			{
				throw new Exception\PageLoadedException("Middleware aborted while loading reroute \"{$target->route_path}:{$target->param_path}\".");
			}
		}
		
		// Load the controller.
		
		$this->controller = $target->controller;
		$this->param_path = $target->param_path;
		$this->route_path = $target->route_path;
		$this->load();
		throw new Exception\PageLoadedException("Loaded reroute \"{$target->route_path}:{$target->param_path}\".");
	}
	
	//== View rendering ==//
	
	public function render ($view, $vars=null)
	{
		$context = make('Clascade\View\Context', $this, $view, $vars);
		++$this->view_nonce;
		
		if (is_string($view) && isset ($this->view_handlers[$view]))
		{
			$context->handler = $this->view_handlers[$view];
		}
		
		$context->renderSelf();
	}
	
	public function getRender ($view, $vars=null)
	{
		ob_start();
		$this->render($view, $vars);
		return ob_get_clean();
	}
	
	//== Head elements ==//
	
	public function addStylesheet ($src, $media=null)
	{
		$this->head_elements[] = new SimpleElement('link',
		[
			'rel' => 'stylesheet',
			'href' => $src,
			'type' => 'text/css',
			'media' => ($media === null ? 'all' : $media),
		]);
	}
	
	public function addScript ($src)
	{
		$this->head_elements[] = new SimpleElement('script',
		[
			'src' => $src,
			'type' => 'text/javascript',
		]);
	}
	
	public function addInlineScript ($code)
	{
		$this->head_elements[] = new SimpleElement('script',
		[
			'type' => 'text/javascript',
		], $code);
	}
	
	//== Error pages ==//
	
	public function errorPage ($error_page)
	{
		require (path("/error-pages/{$error_page}.php"));
		throw new Exception\PageLoadedException("Loaded error page \"{$error_page}\".");
	}
	
	public function notFound ()
	{
		$this->errorPage('not-found');
	}
	
	public function forbidden ()
	{
		$this->errorPage('forbidden');
	}
	
	public function unauthorized ()
	{
		$this->errorPage('unauthorized');
	}
	
	//== Validation ==//
	
	public function validate ($validation_script, $params=null)
	{
		if ($params !== null && !is_array($params))
		{
			// The parameters were given as something other than
			// an array. We'll treat it as database connection.
			
			$params = ['db' => db($params)];
		}
		
		$v = make('Clascade\Validator', path("/validators/{$validation_script}.php"), $params);
		$v->validate($_POST);
		return $v;
	}
	
	public function validateWithRollback ($validation_script, $db=null, $params=null)
	{
		$params = (array) $params;
		$db = db($db);
		$params['db'] = $db;
		
		try
		{
			$v = $this->validate($validation_script, $params);
		}
		catch (Exception\ValidationException $e)
		{
			$db->rollback();
			throw $e;
		}
		
		return $v;
	}
	
	public function redirectWithStatus ($status, $redirect_to=null)
	{
		if ($redirect_to === null)
		{
			$redirect_to = $this->return_to;
		}
		
		if ($status instanceof Validator)
		{
			$status = make('Clascade\StatusReport', $status);
		}
		
		if ($status instanceof StatusReport)
		{
			$status = $status->save();
		}
		
		$status = nice64_encode((string) $status);
		
		// We want to add the status report ID to the redirect URL's
		// query string. Check whether it has a query string already.
		
		$status_name = conf('common.field-names.status');
		
		if (str_contains($redirect_to, '?'))
		{
			// The redirect URL has a query component. Parse it and
			// insert the status report ID, replacing any existing
			// parameter of the same name.
			
			list ($url, $query) = explode('?', $redirect_to, 2);
			parse_str($query, $params);
			$params[$status_name] = nice64_encode($status);
			$redirect_to = "{$url}?".http_build_query($params);
		}
		else
		{
			// The redirect URL doesn't contain a query string yet.
			
			$redirect_to .= '?'.rawurlencode($status_name).'='.nice64_encode($status);
		}
		
		redirect($redirect_to);
	}
	
	public function reportStatus ($report=null)
	{
		if ($report === null)
		{
			$report = $this->status;
		}
		
		$result = '';
		
		if (!empty ($report->errors))
		{
			$result = $this->getRender('errors',
			[
				'errors' => $report->errors,
			]);
		}
		
		$result .= $this->getRender('messages',
		[
			'messages' => $report->messages
		]);
		
		return $result;
	}
	
	public function fieldValue ($field_name, $default=null)
	{
		if (empty ($this->status->errors))
		{
			return new FieldVar($default, $this->status, $field_name);
		}
		
		return new FieldVar($this->status->value($field_name, $default), $this->status, $field_name);
	}
	
	//== Basic permissions ==//
	
	public function requireLogin ()
	{
		if (user()->isGuest())
		{
			// Allow the configured authentication sources to
			// redirect the user to a login page or some other
			// mechanism to log in.
			
			Auth::prompt();
			
			// No solution was found. Display an error page.
			
			$this->forbidden();
		}
	}
	
	public function requireAdmin ()
	{
		$this->requireLogin();
		
		if (!user()->get('is-admin'))
		{
			$this->forbidden();
		}
	}
	
	public function userIsAdmin ($user=null)
	{
		$user = user($user);
		
		if ($user === false)
		{
			return false;
		}
		
		return ($user['is-admin'] == true);
	}
}
