<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Hash;
use Session;
use Redirect;
use DB;
use App\Hours;
use App\Transaction; 
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Mail;
use Config;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';
    

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
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        if(session()->exists('user'))
        { 
            return redirect()->action('HomeController@index');

       //  $userid =Session()->get('userid');
       //$data['user'] = User::getbycondition(array('id'=>$userid));
       // return redirect('/home',$data);
        //$userid =Session()->get('userid');
       // $data['user'] = User::getbycondition(array('id'=>$userid));
        // return view('/user/home',$data);
        }else{
          return view('auth.login');
        }
    
    }

    public function confirmEmail($token)
    {
        User::whereToken($token)->firstOrFail()->confirmEmail();
        return redirect('/auth/login');
    }
    
    public function authenticate(\Illuminate\Http\Request $request)
    {    $request['status'] = 1;
        $credentials = $request->only('email', 'password', 'status');

        if (Auth::attempt($credentials)) {
            // Authentication passed...
            return redirect()->intended('dashboard');
        }
    }
    
    /*protected function credentials(\Illuminate\Http\Request $request )
{
    $request['status'] = 1;
        return $request->only($this->username(), 'password', 'status');
     //return ['email' ,'=', $request->{$this->username()},'password' ,'=', $request->password, 'status' ,'!=', '2'];
      // $data = $request->only($this->username(), 'password');
    //$data['status'] = 1;
    return $data;
   /* $credentials = $request->only($this->username(), 'password');

    $credentials['status'] = 1;

    return $credentials;*/

/*}*/

public function login(\Illuminate\Http\Request $request)
{
       $email = $request->email;
       $password = $request->password;
       $where = [['email','=', $email],['status','!=', '2']];
       //$vendors = User::where($where)->get();
       $vendors = User::where('status','!=','2')->where(function($q) use ($email) {
             $q->where('email', $email)
               ->orWhere('phone', $email);
         })->get(); 
       if (count($vendors) > 0) {
            $wereh= [['email','=',$email],['status','=','1']];
          // $users =  User::getbycondition($wereh);
           $users = User::where('status','1')->where(function($q) use ($email) {
             $q->where('email', $email)
               ->orWhere('phone', $email);
         })->get(); 
           if(count($users) > 0 )
           { 
                //$hashedPassword= User::getdetailsuserret2($wereh,'password');
               
                $dataas = User::where('status','1')->where(function($q) use ($email) {
                $q->where('email', $email)
                ->orWhere('phone', $email);
                })->first(); 
                 $hashedPassword= $dataas->password;
                if(!empty($hashedPassword))
                {
                    if (Hash::check($password, $hashedPassword)) 
                    { // $where = [['email','=',$email],['status','=','1']];
                        //$vendors = User::where($where)->first();
                    $vendors = User::where('status','1')->where(function($q) use ($email) {
                    $q->where('email', $email)
                    ->orWhere('phone', $email);
                    })->first(); 
                        if($vendors->package_id == '1')
                        {
                            $expiry = DB::table('users_hours')->where('user_id',$vendors->id)->select('expiry')->first();
                            if(date('Y-m-d') >= date('Y-m-d',strtotime($expiry->expiry))){
                                $start_date =date('Y-m-d');  
                                $date = strtotime($start_date);
                                $date = date('Y-m-d',strtotime("+7 day", $date));  
                                 
                                $update_question_count=array(
                                'total_hours'=>'00:10:00',
                                'package_id'=>'1',
                                'expiry'=>$date,
                                'current_question_count'=>'0',
                                );
                                Hours::updateoption2($update_question_count,array('user_id'=>$vendors->id));
                                
                                $transaction_data=array(
                                'transaction_id'=>'0',
                                'user_id'=>$vendors->id,
                                'package_id'=>'1',
                                'status'=>'completed',
                                'currency'=>"",
                                'amount'=>'0',
                                'walletuse'=>'0',
                                'exp'=>$date
                                );
                                Transaction::insertUser($transaction_data);
                            }
                        }
                        if (!empty($vendors)) {
                            $userdata = array(
                            'id'=> $vendors->id ,
                            'name' => $vendors->name ,
                            'lname' => $vendors->lname ,
                            'email' => $vendors->email ,
                            );
                            if($vendors->authentication=='1')
                            {
                                /* With two factor authentication */
                                $id = $vendors->id; 
                                $name = $vendors->name;
                                $hash    = uniqid(rand(), true);
                                $string  = $id."&".$hash;
                               /* $iv = $string; */
                                $iv = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                                $htmls = str_replace("#code#",$iv,Config::get('constants.Otp_html'));
                                $header = 'OTP Code';
                                $buttonhtml = '';
                                $pass_url  = ''; 
                                $path = url('resources/views/email.html');
                                $subject = 'OTP Code Multiple Choice Online';
                                $to_email= $vendors->email;
                                $email_path    = file_get_contents($path);
                                $email_content = array('[name]','[pass_url]','[htmls]','[buttonhtml]','[header]');
                                $replace  = array($name,$pass_url,$htmls,$buttonhtml,$header);
                                $message = str_replace($email_content,$replace,$email_path);
                                $header = 'From: '.env("IMAP_HOSTNAME_TEST").'' . "\r\n";
                                $header = "MIME-Version: 1.0\r\n";
                                $header = "Content-type: text/html\r\n";
                                $retval = mail($to_email,$subject,$message,$header); 
                                 if($retval)
                                 {
                                     $users =  User::updateUser(array('otp'=>$iv),$vendors->id);
                                    return Redirect::to('/login?otpgenrates=yes')->withInput($request->only($this->username(), 'remember'))->with('success', "We've sent an OTP to your email.");
                                 }else
                                 {
                                      return Redirect::to('/login')->withInput($request->only($this->username(), 'remember'))->with('error', "Something is wrong.");
                                 }
                            }else
                            {
                                /* With no authentication */
                                Session::put('user',$userdata);
                                Session::put('userid', $vendors->id);
                                Session::save();
                               return  redirect(url('home'));
                            }
                        }else
                        { 
                            return Redirect::to('/login')->withInput($request->only($this->username(), 'remember'))->with('error', 'The selected email is invalid or the account has been disabled.');

                        }
                    }else
                    {
                        return Redirect::to('/login')->withInput($request->only($this->username(), 'remember'))->with('error', "Your credentials doesn't match with our record.");
                    }
                }else
                {  
                     return Redirect::to('/login')->withInput($request->only($this->username(), 'remember'))->with('error', 'The selected email is invalid or the account has been disabled.');
                }
           }else
           { 
                return Redirect::to('/login')->withInput($request->only($this->username(), 'remember'))->with('error', 'The selected email is invalid or the account has been disabled.');
           }
       }else
       {  
            return Redirect::to('/login')->withInput($request->only($this->username(), 'remember'))->with('error', "Your credentials doesn't match with our record.");
       }
}
    
    protected function validateLogin(\Illuminate\Http\Request $request)
{
      

    $this->validate($request, [
        $this->username() => 'required|exists:users,' . $this->username() . ',status,1',
        'password' => 'required',
    ], [
        $this->username() . '.exists' => 'The selected email is invalid or the account has been disabled.'
    ]);
    
}
    
    /* protected function credentials(\Illuminate\Http\Request $request)
    {
        //return $request->only($this->username(), 'password');
        return ['email' => $request->{$this->username()}, 'password' => $request->password, 'status' => 1];
    } */
}
