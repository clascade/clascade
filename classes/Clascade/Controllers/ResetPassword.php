<?php

namespace Clascade\Controllers;
use Clascade\Auth;
use Clascade\Mail;
use Clascade\StatusReport;
use Clascade\Util\PassHash;
use Clascade\Util\Str;

class ResetPassword
{
	public function get ($page)
	{
		if (isset ($_GET['k']))
		{
			return $this->getReset($page);
		}
		else
		{
			return $this->getRequest($page);
		}
	}
	
	public function post ($page)
	{
		if (isset ($_POST['new-password']))
		{
			$this->postReset($page);
		}
		else
		{
			$this->postRequest($page);
		}
	}
	
	public function getRequest ($page)
	{
		if (isset ($_GET['sent']))
		{
			return view('pages/reset-password-sent',
			[
				'login-url' => conf('common.urls.login'),
			]);
		}
		else
		{
			return view('pages/reset-password-form');
		}
	}
	
	public function postRequest ($page)
	{
		$v = $page->validate('reset-password-request');
		$start_time = microtime(true);
		$auth_ident = 'email:'.Str::lowerAscii($v['email']);
		
		$store = Auth::getStore();
		$store->begin();
		$user = $store->getUserByAuthIdent($auth_ident);
		$success = false;
		
		if ($user !== false && $user['email'] !== null)
		{
			$reset_tokens = $store->createResetKey();
			
			if ($reset_tokens !== false)
			{
				list ($k, $reset_key) = $reset_tokens;
				$user['reset-key'] = $reset_key;
				$user['reset-time'] = time();
				$user->save('reset-key', 'reset-time');
				$store->commit();
				
				$email_info = conf('password-reset-email');
				$reset_url = url_base().request_path()."?k={$k}";
				
				if (isset ($email_info['text']))
				{
					$email_info['text'] = Str::template($email_info['text'],
					[
						'reset_url' => $reset_url,
					]);
				}
				
				if (isset ($email_info['html']))
				{
					$email_info['html'] = Str::template($email_info['html'],
					[
						'reset_url' => $reset_url,
					]);
				}
				
				Mail::set($email_info)->send();
				$success = true;
			}
		}
		
		if (!$success)
		{
			$store->rollback();
		}
		
		Auth::passwordSleep($start_time);
		redirect("{$page->request_path}?sent");
	}
	
	public function getReset ($page)
	{
		$user = Auth::getStore()->getUserByResetKey($_GET['k']);
		
		if ($user === false)
		{
			$page->redirectWithStatus(StatusReport::error('That confirmation link is already expired. Try requesting a new reset.'), conf('common.urls.reset-password'));
		}
		
		return view('pages/reset-password',
		[
			'reset-key' => $_GET['k'],
		]);
	}
	
	public function postReset ($page)
	{
		$v = $page->validate('reset-password');
		
		// Store the new password hash.
		
		$user = $v['user'];
		$user['pass-hash'] = PassHash::hash($v['new-password']);
		$user['reset-key'] = null;
		$user['reset-time'] = null;
		$user->save('pass-hash', 'reset-key', 'reset-time');
		
		Auth::setUser($user);
		$v->message('Your password has been changed.');
		$page->redirectWithStatus($v, conf('common.urls.login-dest'));
	}
}
