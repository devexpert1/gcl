<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Session;

class AdminLoginController extends Controller
{
   /**
     * Show the application’s login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        
       if(session()->exists('admin'))
        { 
        return redirect()->action('AdminController@index');
        }else
        {
       return redirect('/admin/dashboard');
        }
    }
    protected function guard(){
        return Auth::guard('admin');
    }
    
    use AuthenticatesUsers;
    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin/dashboard';
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        $this->middleware('guest:admin')->except('logout');
    }
public function logincheck()
{
    return view('auth.admin-login');
    
}
    public function logout()
    {
       //$this->middleware('guest:admin')->except('logout');
        Auth::logout();
        Session::flush();
        return redirect('/admin');
    }
}
