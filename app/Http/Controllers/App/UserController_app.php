<?php

namespace App\Http\Controllers;
require 'app/paypal-php-sdk/autoload.php';
use Illuminate\Http\Request;
use Redirect;
use Session;
use Config;
use App\Admin;
use App\course;
use Auth;
use Hash;
use App\Years;
use App\Grades;
use App\Notification;
use App\Faqs;
use App\Pre_questiondetails;
use App\Question_answers;
use App\Options;
use App\Testimonials;
use App\User_test_answers;
use App\country;
use App\User;
use App\Transaction;
use App\Reffer;
use App\Hours;
use App\Withdraw;
use App\Subscription_content;
use App\User_test;
use DB;
use Mail;
use App\Test;
use Illuminate\Routing\Redirector;
use PayPal\Api\ChargeModel;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Common\PayPalModel;
use PayPal\Api\Agreement;
use PayPal\Api\Payer;
use PayPal\Api\ShippingAddress;
use App\paypal_javascript_express_checkout\autoload;
use Illuminate\Http\Response;
use Route;

class UserController_app extends Controller
{
   /**
     * Create a new controller instance.
     *
     * @return void
     */
     public $notification_count;
     
    public function __construct(Request $request, Redirector $redirect)
    {   //include(app_path() . 'paypal-php-sdk/autoload.php');
         DB::statement("SET NAMES 'utf8'");
        header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Access-Control-Allow-Origin: *");
        //$this->middleware('auth');
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
     
public function login(Request $request)
{
        $postdata = file_get_contents("php://input");
		$request= json_decode($postdata,true);
       $email = $request['email'];
       $password = $request['password'];
       //$where = [['email','=', $email],['status','!=', '2']];
     //  $vendors = User::where($where)->get();
        $vendors = User::where('status','!=','2')->where(function($q) use ($email) {
             $q->where('email', $email)
               ->orWhere('phone', $email);
         })->get(); 
       if (count($vendors) > 0) {
            //$wereh= [['email','=',$email],['status','=','1']];
          /// $users =  User::getbycondition($wereh);
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
                    {  //$where = [['email','=',$email],['status','=','1']];
                       // $vendors = User::where($where)->first();
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
                            'profile'=>$vendors->profile ? $vendors->profile: 'null',
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
                                $cur_year = date('Y');
                                $email_path    = file_get_contents($path);
                                $email_content = array('[name]','[pass_url]','[htmls]','[buttonhtml]','[header]','[cur_year]');
                                $replace  = array($name,$pass_url,$htmls,$buttonhtml,$header,$cur_year);
                                $message = str_replace($email_content,$replace,$email_path);
                                $header = 'From: '.env("IMAP_HOSTNAME_TEST").'' . "\r\n";
                                $header .= "MIME-Version: 1.0\r\n";
                                $header .= "Content-type: text/html\r\n";
                                $retval = mail($to_email,$subject,$message,$header); 
                             if($retval)
                             {
                                    $data['msg']="We've sent an OTP to your email.";
                                    $data['status'] = 2;
                                    $data['data'] = array();
                                    $data['otp'] = 'get';
                                 $users =  User::updateUser(array('otp'=>$iv),$vendors->id);
                             }else
                             {
                                $data['msg']='Something is wrong.';
                                $data['status'] = 0;
                                $data['data'] = 'null';
                                  
                             }
                            }else
                            {
                            $data['msg']='You logged in successfully.';
                            $data['status'] = 1;
                            $data['data'] = $userdata;
                            }
                        }else
                        {   
                            $data['msg']='The selected email is invalid or the account has been disabled.';
                            $data['status'] = 0;
                            $data['data'] = 'null';
                        }
                    }else
                    {
                        $data['msg']="Your credentials doesn't match with our record.";
                        $data['status'] = 0;
                        $data['data'] = 'null';
                    }
                }else
                {  
                        $data['msg']="The selected email is invalid or the account has been disabled.";
                        $data['status'] = 0;
                        $data['data'] = 'null';
                }
           }else
           { 
                        $data['msg']="The selected email is invalid or the account has been disabled.";
                        $data['status'] = 0;
                        $data['data'] = 'null';
           }
       }else
       {  
                        $data['msg']="Your credentials doesn't match with our record.";
                        $data['status'] = 0;
                        $data['data'] = 'null';
       }
       return json_encode($data);
}

public function getnotificationcount($id)
{
     $count = Notification::getbycondition(array('status'=>'1','w_to'=>'user','to_id'=>$id));
     $data['count'] =count($count);
     return json_encode($data);
}

public function loginotp(Request $request)
    {  
    $postdata = file_get_contents("php://input");
		$data=json_decode($postdata,true);
    $dat1= array('email'=>$data['email']);
    $dat2= array('phone'=>$data['email']);
     $messags = array();
    $vendors = User::getmacthemailphone($data['otp'],$data['email']);
    if(!empty($vendors))
    {
        $userdata = array(
        'id'=> $vendors->id ,
        'name' => $vendors->name ,
        'lname' => $vendors->lname ,
        'email' => $vendors->email ,
        'profile'=>$vendors->profile ? $vendors->profile: 'null',
        );
         $users =  User::updateUser(array('otp'=>rand()),$vendors->id);
            $messags['msg']='You logged in successfully.';
            $messags['status'] = 1;
            $messags['data'] = $userdata;
    }else
    {
        $messags['msg']="Your credentials doesn't match with our record.";
        $messags['status'] = 0;
        $messags['data'] = 'null';
    }
    return json_encode($messags);
      
    }

   
      public function register(Request $request,$id)
    {
       $postdata = file_get_contents("php://input");
		$requests=json_decode($postdata,true);
       $messags = array();
     if(!empty($requests['email'])){
         
         if(isset($requests['refercode']) && !empty($requests['refercode']))
         { 
             $ivs= $requests['refercode'];
              unset($requests['refercode']);
               $packages = Subscription_content::getbycondition(array('id'=>$requests['package_id']));
               $amounts=$packages[0]->referrel_amount;
              
         }
         if(isset($requests['usertoken']) && !empty($requests['usertoken']) )
         {
             $iv= $requests['usertoken'];
             $amounts = $requests['referrel_amount'];
             unset($requests['usertoken']);
             unset($requests['referrel_amount']);
             
         }
         
         if(isset($requests['usertoken2']) && !empty($requests['usertoken2']))
         {
             $ivs= $requests['usertoken2'];
             $amounts = $requests['referrel_amount'];
             unset($requests['referrel_amount']);
             unset($requests['usertoken2']); 
         }
        
       $data=array(
           'name'=>$requests['name'],
           'lname'=>$requests['lname'],
           'email'=>$requests['email'],
           'phone'=>$requests['phone'],
           'country'=>$requests['country'],
           'package_id'=>$requests['package_id'],
           'dob'=> $requests['dob'] ? date('Y-m-d H:i:s',strtotime($requests['dob'])): '',
           'password'=>Hash::make($requests['password']),
           'status'=>'1',
           'refferal_code'=>time().uniqid(rand()),
           'company_name'=>$requests['company_name'],
           );  
           if(isset($data['dob']) && empty($data['dob']))
           {
               unset($data['dob']);
           }
         $email = [['email','=',$requests['email']],['status','!=','2']];
        $exists = User::getUsermatch($email);
        if(count($exists) > 0 )
        {
            $messags['message'] = "Email already exist.";
            $messags['status']= 0;
            $messags['data']= 'null'; 
        }
       
            if(User::insertUser($data))
                                        {
                                            $userdatas = User::getbycondition(array('email'=>$requests['email']));
                                            if(count($userdatas)>0  && !empty($userdatas))
                                            {
                                            foreach($userdatas as $u){
                                            $users = $u;
                                            }
                                            $userdata = array(
                                            'id'=> $users->id ,
                                            'name' => $users->name ,
                                            'lname' => $users->lname ,
                                            'email' => $users->email ,
                                            'profile'=>$vendors->profile ? $vendors->profile: 'null',
                                            );
                                            $date = '';
                                            if($requests['package_id'] == '1'){
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+7 day", $date));  
                                            }
                                            if($requests['package_id'] == '3')
                                            {
                                                $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+1 year", $date));   
                                            }
                                            if($requests['package_id'] == '2')
                                            {
                                                $start_date =date('Y-m-d');  
                                                $date = strtotime($start_date);
                                                $date = date('Y-m-d',strtotime("+1 month", $date));  
                                            }
                                            $transaction_data=array(
                                            'transaction_id'=>$requests['transaction_id'],
                                            'user_id'=>$users->id,
                                             'package_id'=>$requests['package_id'],
                                             'status'=>$requests['status'],
                                             'currency'=>$requests['currency'],
                                            'amount'=>$requests['amount'],
                                            'exp'=>$date
                                
                                             );
           
                                           Transaction::insertUser($transaction_data);
                                           if($requests['package_id'] == '1'){
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+7 day", $date));  
                                             
                                               
                                             $hours_data=array(
                                            'user_id'=>$users->id,
                                            'package_id'=>$requests['package_id'],
                                            'total_questions_uploaded'=>'0',
                                            'total_hours'=>'00:10:00',
                                            'expiry'=>$date,
                                            'current_question_count'=>0,
                                
                                             );
                                             }elseif($requests['package_id'] == '3'){
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+1 year", $date));  
                                             
                                               
                                             $hours_data=array(
                                            'user_id'=>$users->id,
                                            'package_id'=>$requests['package_id'],
                                            'total_questions_uploaded'=>'0',
                                            'total_hours'=>'00:00:00',
                                            'expiry'=>$date,
                                            'current_question_count'=>0,
                                
                                             );
                                             }else
                                             {
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+1 month", $date));  
                                               
                                             $hours_data=array(
                                            'user_id'=>$users->id,
                                            'package_id'=>$requests['package_id'],
                                            'total_questions_uploaded'=>'0',
                                            'total_hours'=>'00:00:00',
                                            'expiry'=>$date,
                                            'current_question_count'=>0,
                                
                                             );
                                             }
                                       
                                        if(!empty($iv) && !empty($requests['email']))
                                        {  
                                            $datas = array(
                                            'status'=>'1',
                                            'friend_id'=>$users->id,
                                            'amount' => $amounts,
                                            'token'=> rand()
                                            );
                                            $were3 =  array(
                                            'token'=>$iv
                                            );
                                            $were34 =  array(
                                            'token'=>$iv,
                                            'friend_email'=>$requests['email']
                                            ); 
                                            $getdatas = Reffer::getbycondition($were34);
                                            if(empty($getdatas) && count($getdatas) < 1)
                                            {   $getdatas = Reffer::getbycondition($were34);
                                                Reffer::updateoption2($datas,$were3);
                                            }else
                                            {
                                               Reffer::updateoption2($datas,$were34);  
                                            }
                                            //$getdatas = Reffer::getbycondition($were3);
                                           
                                           
                                            $data['uid'] = $users->id;
                                            $weress= [['id','=',$getdatas[0]->uid]];
                                            $adminemail = User::getbycondition($weress);
                                            $were= [['id','=', $users->id]];
                                            $user = User::getbycondition($were);
                                            foreach($user as $u){
                                            $r = $u;
                                            }
                                            if(count($user)!=0)
                                            {
                                            $id = $r->id; 
                                            $name = $adminemail[0]->name;
                                            $hash    = md5(uniqid(rand(), true));
                                            $string  = $id."&".$hash;
                                            $iv = base64_encode($string);
                                            $htmls = $r->name.' has been registered with your refferal link, Please visit the following link given below:';
                                            $header = 'Registered with refferal';
                                            $buttonhtml = 'Click here to visit';
                                            $pass_url  = url('user/referral'); 
                                            $path = url('resources/views/email.html');
                                            $subject = 'Registered with refferal';
                                            $to_email= $adminemail[0]->email;
                                            $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                                            $arrays =[
                                            'w_from' => 'user',
                                            'from_id' => $r->id,
                                            'w_to' => 'user',
                                            'to_id' => $getdatas[0]->uid,
                                            'title' => $r->name.' has been registered with your refferal link.',
                                            'description' => $r->name.' has been registered with your refferal link.',
                                            'url' => 'user/referral',
                                            'tbl'=>'reffer_friend',
                                            'status'=>'1'
                                            ];
                                            Notification::insertoption($arrays);
                                            }
                                        }
                                        
                                        if(!empty($ivs) && !empty($requests['email']))
                                        {  
                                             $were34 =  array(
                                            'friend_email'=>$requests['email']
                                            ); 
                                             $getdatas = Reffer::getbycondition($were34);
                                             if(count($getdatas) < 1)
                                            {
                                            $getdatas = User::getbycondition(array('refferal_code'=>$ivs));
                                            $datas = array(
                                            'uid'=>$getdatas[0]->id,
                                            'friend_email'=>$requests['email'],
                                            'status'=>'1',
                                            'friend_id'=>$users->id,
                                            'amount' => $amounts,
                                            'token'=> rand()
                                            );
                                            Reffer::insertoption($datas);
                                            }else
                                            {
                                               $datas = array(
                                                'status'=>'1',
                                                'friend_id'=>$users->id,
                                                'amount' => $amounts,
                                                'token'=> rand()
                                                );
                                               Reffer::updateoption2($datas,$were34); 
                                            }
                                            $were3 =  array(
                                            'friend_id'=>$users->id,
                                            'uid'=>$getdatas[0]->id
                                            );
                                            $getdatass = Reffer::getbycondition($were3);
                                            $data['uid'] = $users->id;
                                            $weress= [['id','=',$getdatass[0]->uid]];
                                            $adminemail = User::getbycondition($weress);
                                            $were= [['id','=', $users->id]];
                                            $user = User::getbycondition($were);
                                            foreach($user as $u){
                                            $r = $u;
                                            }
                                            if(count($user)!=0)
                                            {
                                            $id = $r->id; 
                                            $name = $adminemail[0]->name;
                                            $hash    = md5(uniqid(rand(), true));
                                            $string  = $id."&".$hash;
                                            $iv = base64_encode($string);
                                            $htmls = $r->name.' has been registered with your refferal link, Please visit the following link given below:';
                                            $header = 'Registered with refferal';
                                            $buttonhtml = 'Click here to visit';
                                            $pass_url  = url('user/referral'); 
                                            $path = url('resources/views/email.html');
                                            $subject = 'Registered with refferal';
                                            $to_email= $adminemail[0]->email;
                                            $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                                            $arrays =[
                                            'w_from' => 'user',
                                            'from_id' => $r->id,
                                            'w_to' => 'user',
                                            'to_id' => $getdatass[0]->uid,
                                            'title' => $r->name.' has been registered with your refferal link.',
                                            'description' => $r->name.' has been registered with your refferal link.',
                                            'url' => 'user/referral',
                                            'tbl'=>'reffer_friend',
                                            'status'=>'1'
                                            ];
                                            Notification::insertoption($arrays);
                                            }
                                        }
                                        
                                        
                                            Hours::insertUser($hours_data);
                                            } 
                                             
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['status']= 1;
                                            $messags['data']= $userdata; 
                                        }
           
          
           
           
     }
     echo json_encode($messags);
                         die;
    }
    
     /* Forgetpassword Send link */
  public function forgetpass(Request $request)
  {
      $postdata = file_get_contents("php://input");
	  $requests=json_decode($postdata,true);
    
	$messags = array();
         $were= ['email'=> $requests['email']];

            /* match email is exists or not */
           $user = User::getbycondition($were);
                      
          foreach($user as $u){
                    $r = $u;
                  }

                    if(count($user)!=0)
                    {
                        $id = $r->id; 
                        $name = $r->name;
                        $hash    = md5(uniqid(rand(), true));
                        $string  = $id."&".$hash;
                        $iv = base64_encode($string);
                        $htmls = Config::get('constants.Forgetpass_html');
                        $header = Config::get('constants.Forgetpass_header');
                        $buttonhtml = Config::get('constants.Forgetpass_btn_html');
                        $pass_url  = url('reset_passwords/'.$iv); 
                        $path = url('resources/views/email.html');
                        $email_path    = file_get_contents($path);
                        $cur_year = date('Y');
                          $email_content = array('[name]','[pass_url]','[htmls]','[buttonhtml]','[header]','[cur_year]');
                          $replace  = array($name,$pass_url,$htmls,$buttonhtml,$header,$cur_year);
                         $message = str_replace($email_content,$replace,$email_path);
                          $subject = Config::get('constants.Forgetpass_subject');
                          $header = 'From: '.env("IMAP_HOSTNAME_TEST").'' . "\r\n";
                          $header .= "MIME-Version: 1.0\r\n";
                          $header .= "Content-type: text/html\r\n";
                         $retval = mail($r->email,$subject,$message,$header); 

                          /* send email for the resetpassword */

                           if($retval)
                           {
                              /* update token in data base  */
                                   DB::table('users')
                                ->where(['email'=> $r->email])
                                ->update(
                                ['forget_pass' => $iv]
                                );
                                $messags['message'] = "We have e-mailed your password reset link!";
                                $messags['status'] = 1;
                                $messags['data'] = 'null';
                           }else
                           {
                              DB::table('users')
                                ->where(['email'=> $r->email])
                                ->update(
                                ['forget_pass' => ' ']
                                );
                           }                          
                          
                    }else
                     {
                        $messags['message'] = "Email does not exists.";
                        $messags['status']= 0;
                        $messags['data']= 'Null'; 
				  }
        echo json_encode($messags);
             die;
  }
  
   /* Dashboard data */
  public function dashboard(Request $request,$id)
  {
      $postdata = file_get_contents("php://input");
	  $requests=json_decode($postdata,true);
         $data['title']='Home';
            $were = [['user_id','=',$id],['status', '!=','2' ]];
               $testes = User_test::getbycondition(array('user_id'=>$id));
            $getids = array();
            $data['questions']=array();
            $totalquestion=0;
            $correctanswers= 0;
             foreach($testes as $test)
             {
                 $totalquestion += $test->total_questions;
                 $correctanswers += $test->correct_answers;
                 $were = [['id','=',$test->test_id],['status', '!=','2' ]];
                $getids =  Pre_questiondetails::getoption($were);
             }
             foreach ($testes as $value) 
             {
                  $arrays[] =  (array) $value;
             }
            $data['questions']=User_test::getbyconditionpagination22(array('user_id'=>$id));
             
                                              
            
            foreach($data['questions'] as $key=>$dd)
            {    $data['questions'][$key]['date'] = date('d/m/Y', strtotime($dd['test_date']));
                  $were2 = [['test_id','=',$dd['id']],['suggested_answer','!=','']];
                  $data['questions'][$key]['suggested'] = User_test_answers::getbycount($were2);
                  $were1 = [['test_id','=',$dd['id']],['answer','!=','']];
                  $data['questions'][$key]['attempt_answer'] = User_test_answers::getbycount($were1);
                  $data['questions'][$key]['score'] = $dd['correct_answers'].'/'.$dd['total_questions'];
            } 
         
            $data['total_test'] = Test::gettotaltest($id);
            $data['total_questions'] = Question_answers::countall2($id);
            if(!empty($totalquestion))
            {
            $data['total_score'] = $correctanswers.'/'.$totalquestion;
            }else
            {
               $data['total_score'] = '0/0';
            }
            $messags['message'] = "";
            $messags['status']= 1;
            $messags['data']= $data; 
             echo json_encode($messags);
             die;
  }
  
   /* questions list */
  public function questions_list(Request $request,$id)
  {
        $postdata = file_get_contents("php://input");
        $requests=json_decode($postdata,true);
        $data['title']='Questions List';
        $data['page']='questionlist';
        $were = [['user_id','=',$id],['status', '!=','2' ]];
        $data['questions'] = Question_answers::getbyconditionaltp($were);
        if(count($data['questions']['data']) > 0)
        {
        foreach($data['questions']['data'] as $k=>$d)
        {  
            if(!empty($d['country']))
            {
                 $gettags = [['parent', '=', $d['country']],['status', '=', '1']]; 
                 $data['questions']['data'][$k]['state_state'] = country::getbycondition($gettags);
            }
            
            if(!empty($d['course']))
            {
                $gettags = [['parent', '=', $d['course']],['type', '=', '2'],['status', '=', '1']]; 
                 $data['questions']['data'][$k]['subject_data'] = course::getbycondition($gettags);
            }
            
            if(!empty($d['subject']))
            {
                $gettags = [['parent', '=', $d['subject']],['type', '=', '3'],['status', '=', '1']]; 
                 $data['questions']['data'][$k]['chapter_data'] =course::getbycondition($gettags);
            }
        } 
        }else
        {
            $data['questions']['data'] = array();
        }
        $gettags = [['type', '=', '2'],['status', '=', '1']]; 
        $gettags2 = [['type', '=', '3'],['status', '=', '1']]; 
        $data['subject']=course::getbycondition($gettags);
        $data['chapter']=course::getbycondition($gettags2);
        $data['courses']= course::getoption();
        $messags['message'] = "";
        $messags['status']= 1;
        $messags['data']= $data; 
        echo json_encode($messags);
        die;
  }
  
   /* Add question */
  public function addquestions(Request $request,$ide)
    {   $postdata = file_get_contents("php://input");
        $data=json_decode($postdata,true);
        $messags = array();
                $data= array_filter($data);
               
                if(isset($data['country']))
                {
                    $datas['country']=$data['country'];
                    unset($data['country']);
                }
                if(isset($data['state']))
                {
                    $datas['state']=$data['state'];
                    unset($data['state']);
                }
                if(isset($data['course']))
                {
                    $datas['course']=$data['course'];
                    unset($data['course']);
                }
                if(isset($data['grade']))
                {
                    $datas['grade']=$data['grade'];
                    unset($data['grade']);
                }
                if(isset($data['year']))
                {
                    $datas['year']=$data['year'];
                    unset($data['year']);
                }
                if(isset($data['subject']))
                {
                    $datas['subject']=$data['subject'];
                    unset($data['subject']);
                }
                 if(isset($data['chapter']))
                {
                    $datas['chapter']=$data['chapter'];
                    unset($data['chapter']);
                }
                $datas['is_admin']='0';
                $datas['user_id']=$ide;
                $wheress= array('coulmn_name'=>'aproval');
                $ap = Options::getoptionmatch2($wheress);
                $ap[0];
                if($ap[0]=='1')
                {
                   $status='0';
                }else
                {
                   $status='1'; 
                }
                $datas['status']=$status; 
                 $id = Pre_questiondetails::insertoption2($datas);
                if($id !='')
                { 
                    foreach($data['question'] as $key=>$ques)
                    {
                        $input = [
                        'type' => $ques['type'],
                        'question' => $ques['question'],
                        'optiona' => $ques['optiona'],
                        'optionb' => $ques['optionb'],
                        'optionc' => $ques['optionc'],
                        'optiond' => $ques['optiond'],
                        'answer' => $ques['answer'],
                        'question_id' => $id,
                         'qstatus'=>$status
                        ];
                    $ddis=  Question_answers::insertoption2($input);
                      Question_answers::updateoption($datas,$ddis);
                }
                     if($ap[0]=='0')
                        {
                            $getall =  Question_answers::getbycondition(array('user_id'=>$ide));
                            $getall2 =  Question_answers::getbycondition(array('user_id'=>$ide,'qstatus'=>'1'));
                            $update_question_count=array(
                            'total_questions_uploaded'=>count($getall),
                            );
                            Hours::updateoption2($update_question_count,array('user_id'=>$ide));
                            $res=Hours::getdetailsuser($ide);
                            if(!empty($res))
                            {
                                if($res->package_id == '1')
                                {
                                
                                if(count($getall2) > $res->apporved_questions || (count($getall2) == $res->apporved_questions && count($getall2) > 10))
                                {  
                                    $count = count($getall2) - $res->apporved_questions;
                                    if($count > 10 || $count == '10')
                                    {
                                        /*$timestamp = strtotime($res->total_hours) + 60*60;*/
                                          $timestamp = strtotime($res->total_hours."+ 10 minutes");
                                        $time = date('H:i:s', $timestamp);
                                        $update_question_count=array(
                                            'current_question_count'=>'0',
                                            'apporved_questions'=>$res->apporved_questions + '10',
                                            'total_hours'=>$time,
                                            );
                                    }
                                } 
                                
                                }
                                Hours::updateoption2($update_question_count,array('user_id'=>$ide));
                            }
                          $messags['message'] = "Question has been added successfully.";
                          $messags['status']= 1;
                          $messags['data']= 'Null';
                        }else
                        {
                            $weres= [['id','!=','']];
                            $adminemail = Admin::getUsermatch($weres);
                            $were= [['id','=', $ide]];
                            $user = User::getbycondition($were);
                                foreach($user as $u){
                                 $r = $u;
                                }
                            if(count($user)!=0)
                            {
                            $id = $r->id; 
                            $name = 'Admin';
                            $hash    = md5(uniqid(rand(), true));
                            $string  = $id."&".$hash;
                             $iv = base64_encode($string);
                           $htmls = str_replace('#name#',$r->name,Config::get('constants.Addquestions_html')).', Please visit the following link given below:';
                            $header = Config::get('constants.Addquestions_header');
                            $buttonhtml = Config::get('constants.Addquestions_btn_html');
                            $pass_url  = url('admin/questions'); 
                            $path = url('resources/views/email.html');
                            $subject = Config::get('constants.Addquestions_subject');
                            $to_email=$adminemail[0];
                              $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                              
                                $arrays =[
                                'w_from' => 'user',
                                'from_id' => $id,
                                'w_to' => 'admin',
                                'to_id' => '1',
                                'title' => str_replace('#name#',$r->name,Config::get('constants.Addquestions_html')),
                                'description' => str_replace('#name#',$r->name,Config::get('constants.Addquestions_html')),
                                'url' => 'admin/questions/',
                                'tbl'=>'pre_questiondetails',
                                'status'=>'1'
                                ];
                                Notification::insertoption($arrays);
                            }
                          $messags['message'] = "Question has been added successfully. Please wait for approval.";
                          $messags['status']= 1;
                          $messags['data']= 'Null';
                        }
                }else
                {
                  $messags['message'] = "Error to add a question.";
                  $messags['status']= 0; 
                  $messags['data']= 'Null';
                }
        echo json_encode($messags);
                         die;
    }
    /* Edit question */
    public function editquestionview($id)
    {
        if($id!='')
        {
             $were = [['id','=',$id],['status', '!=','2' ]];
             
             $data['questions']=Question_answers::getbycondition($were);
            $data['grades']= Grades::getbycondition(array('status'=>'1'));
            $data['years']= Years::getbycondition(array('status'=>'1'));
            $data['courses']= course::getoption();
            $data['countries']= country::getoption();
             foreach($data['questions'] as $ques)
             {
                 if(!empty($ques->country))
                 {
                    $gettagss = [['parent', '=', $ques->country],['status', '=', '1']];
                    $data['states']=country::getbycondition($gettagss);  
                 }
                 
                 if(!empty($ques->course))
                 {
                    $gettagssub = [['parent', '=', $ques->course],['type', '=', '2'],['status', '=', '1']]; 
                    $data['subjects']=course::getbycondition($gettagssub); 
                 }
                 
                 if(!empty($ques->subject))
                 {
                     $gettagschap = [['parent', '=', $ques->subject],['type', '=', '3'],['status', '=', '1']];
                    $data['chapter']=course::getbycondition($gettagschap);
                 }
                 
                 
             }
                $messags['message'] = "data get.";
                $messags['status']= 1; 
                $messags['data']= $data;
                echo json_encode($messags);
                         die;
        }
    }
    
     /* Edit question */
    public function editquestions(Request $request,$ide)
    {   $postdata = file_get_contents("php://input");
        $data=json_decode($postdata,true);
        $messags = array();
                $data= $request->all();
                $data= array_filter($data);
                if(isset($data['country']))
                {
                    $datas['country']=$data['country'];
                    unset($data['country']);
                }
                if(isset($data['state']))
                {
                    $datas['state']=$data['state'];
                    unset($data['state']);
                }else
                {
                    $datas['state']='0';
                }
                if(isset($data['course']))
                {
                    $datas['course']=$data['course'];
                    unset($data['course']);
                }
                if(isset($data['grade']))
                {
                    $datas['grade']=$data['grade'];
                    unset($data['grade']);
                }
                if(isset($data['year']))
                {
                    $datas['year']=$data['year'];
                    unset($data['year']);
                }
                if(isset($data['subject']))
                {
                    $datas['subject']=$data['subject'];
                    unset($data['subject']);
                }else
                {
                    $datas['subject']='0';
                }
                 if(isset($data['chapter']))
                {
                    $datas['chapter']=$data['chapter'];
                    unset($data['chapter']);
                }else
                {
                    $datas['chapter']='0';
                }
                $wheress= array('coulmn_name'=>'aproval');
                $ap = Options::getoptionmatch2($wheress);
                $ap[0];
                if($ap[0]=='1')
                {
                   $status='0';
                }else
                {
                   $status='1'; 
                }
               
                /*if(isset($data['id']))
                {    
                     $id = $data['id'];
                      unset($data['id']);
                } */
                 $datas['status']=$status; 
                 $datas['qstatus'] = $status;
                    if(!empty($data))
                    {  
                       
                       foreach($data['question'] as $key=>$ques)
                        {
                            $input = [
                            'type' => $ques['type'],
                            'question' => $ques['question'],
                            'optiona' => $ques['optiona'],
                            'optionb' => $ques['optionb'],
                            'optionc' => $ques['optionc'],
                            'optiond' => $ques['optiond'],
                            'answer' => $ques['answer'],
                            'question_id' => 0,
                            'qstatus'=>$status,
                            ];
                            
                           Question_answers::updateoption($input,$ques['question_id']); 
                           Question_answers::updateoption($datas,$ques['question_id']); 
                        }
                        
                        if($ap[0]=='0')
                        {
                          $messags['message'] = "Question has been updated successfully.";
                          $messags['status']= 1;
                          $messags['data']= 'Null';
                        }else
                        {
                            $weres= [['id','!=','']];
                            $adminemail = Admin::getUsermatch($weres);
                            $were= [['id','=', $ide]];
                            $user = User::getbycondition($were);
                                foreach($user as $u){
                                 $r = $u;
                                }
                            if(count($user)!=0)
                            {
                            $id = $r->id; 
                            $name = 'Admin';
                            $hash    = md5(uniqid(rand(), true));
                            $string  = $id."&".$hash;
                             $iv = base64_encode($string);
                            $htmls = str_replace("#name#",$r->name,Config::get('constants.Editquestions_html')).', Please visit the following link given below:';
                            $header = Config::get('constants.Editquestions_header');
                            $buttonhtml = Config::get('constants.Editquestions_btn_html');
                            $pass_url  = url('admin/questions/'); 
                            $path = url('resources/views/email.html');
                            $subject = Config::get('constants.Editquestions_subject');
                            $to_email=$adminemail[0];
                              $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                                $arrays =[
                                'w_from' => 'user',
                                'from_id' => $id,
                                'w_to' => 'admin',
                                'to_id' => '1',
                                'title' => str_replace("#name#",$r->name,Config::get('constants.Editquestions_notification_description')),
                                'description' => str_replace("#name#",$r->name,Config::get('constants.Editquestions_notification_description')),
                                'url' => 'admin/questions/',
                                'tbl'=>'pre_questiondetails',
                                'status'=>'1'
                                ];
                                Notification::insertoption($arrays);
                            }
                          $messags['message'] = "Question has been updated successfully. Please wait for approval.";
                          $messags['status']= 1;
                          $messags['data']= 'Null';
                        }
                    }else
                    {
                      $messags['message'] = "Error to Update a questions.";
                      $messags['status']= 0; 
                      $messags['data']= 'Null';
                    }
        echo json_encode($messags);
                         die;
    }
    
    
    /* to delet questions by user */
     public function question_delete(Request $request)
    {
        $postdata = file_get_contents("php://input");
        $data=json_decode($postdata,true);
        $id2 = $data['id'];
          $data2 = [
               'status' => '2',
               'qstatus'=> '2',
            ];
         $this->updateData('question_answers',array('id'=>$id2), $data2);
            $messags['message'] = "Question has been deleted successfully.";
            $messags['status']= 1; 
            $messags['data']= 'Null';
        
          echo json_encode($messags);
                         die;
    }
    
    /* Detail view of a question */
    public function viewquestion($id='')
    {
        $this->middleware('auth');
        $prewere = [['id','=',$id],['status', '!=','2' ]];
        $data['questions']=Question_answers::getbyconditionall($prewere);
        foreach($data['questions'] as $key=> $d)
        {
            if(!empty($d['country']))
            {  $gettagss = [['id', '=', $d['country']],['parent', '=', 0],['status', '=', '1']];
               $data['questions'][$key]['country']= country::getoptionmatchall2($gettagss);
                if(!empty($d['state']))
                {   $gettagss2 = [['parent', '=', $d['country']],['id', '=', $d['state']],['status', '=', '1']];
                  $data['questions'][$key]['state']= country::getoptionmatchall2($gettagss2);
                }else
                { 
                  $data['questions'][$key]['state']= 'Not Specified';
                }
            }else
            {
               $data['questions'][$key]['country']= 'Not Specified';
               $data['questions'][$key]['state']= 'Not Specified';
            }
            
            
            
            if(!empty($d['year']))
            {  $were = array('status'=>'1','id'=>$d['year']);
               $data['questions'][$key]['year']= Years::getoptionmatchall2($were);  
            }else
            { 
               $data['questions'][$key]['year']= 'Not Specified';
            }
            
            if(!empty($d['grade']))
            { 
             $were = array('id'=>$d['grade']);
             $data['questions'][$key]['grade']= Grades::getoptionmatchall2($were);  
            }else
            {
             $data['questions'][$key]['grade']= 'Not Specified';
            }
            
            if(!empty($d['course']))
            {
                $gettagss2 = array('id'=>$d['course']);
                $data['questions'][$key]['course']=course::getoptionmatchall2($gettagss2);
                if(!empty($d['subject']))
                 {
                    $gettagssub22 = [['parent', '=', $d['course']],['id', '=', $d['subject']],['type', '=', '2']]; 
                    $data['questions'][$key]['subject']=course::getoptionmatchall2($gettagssub22);  
                    if(!empty($d['chapter']))
                    {
                        $gettagschap = [['parent', '=', $d['subject']],['id', '=', $d['chapter']],['type', '=', '3']];
                        $data['questions'][$key]['chapter']=course::getoptionmatchall2($gettagschap);
                    }else
                    {
                      $data['questions'][$key]['chapter']='Not Specified';  
                    }
                 }else
                 {
                    $data['questions'][$key]['subject']='Not Specified';
                    $data['questions'][$key]['chapter']='Not Specified';
                 }
            }else
            {
              $data['questions'][$key]['course']= 'Not Specified'; 
               $data['questions'][$key]['subject']='Not Specified';
               $data['questions'][$key]['chapter']='Not Specified';
            }
            
        } 
      
            $messags['message'] = "Question data.";
            $messags['status']= 1; 
            $messags['data']= $data;  
          echo json_encode($messags);
                         die;
    }
    
    /* wallet data shown */
    public function wallet(Request $request,$userid,$type)
    {       
        if($type=='request')
          {
            $were = array('uid'=>$userid);
            $data['applyrequests'] = Withdraw::getbycondition22($were);
            if(count($data['applyrequests']['data']) > 0)
            {
             foreach($data['applyrequests']['data'] as $key=>$dd)
            { 
                if($dd['status']=='0')
                {
                 $data['applyrequests']['data'][$key]['status'] = 'Pending';
                }else if($dd['status']=='1')
                {
                 $data['applyrequests']['data'][$key]['status'] = 'Declined';   
                }else
                {
                   $data['applyrequests']['data'][$key]['status'] = 'Approve';  
                }
               
            } 
            }else
            {
                $data['applyrequests']['data'] = array();
            }
            $were = array('uid'=>$userid,'status'=>'2');
           
            $data2['applyrequests2'] = Withdraw::getbycondition($were);
            $were2 = array('uid'=>$userid,'status'=>'1');
            $data2['reffered'] = Reffer::getbycondition($were2);
            $data2['transactions2'] = Transaction::getbycondition(array('user_id'=>$userid));
            $data['walletamount']=0;
            $data['reffer_amount'] =0;
            foreach($data2['reffered'] as $reffer)
            {
                if(!empty($reffer->amount))
                {
                    $data['walletamount'] += $reffer->amount;
                    $data['reffer_amount'] += $reffer->amount;
                }
            }
            
             foreach($data2['transactions2'] as $reffers)
            {
                if(!empty($reffers->walletuse))
                {
                 $data['walletamount'] -= $reffers->walletuse;
                }
            }
            
            $data['withdrwaamount']=0;
            foreach($data2['applyrequests2'] as $reffers)
            {
                if(!empty($reffers->amount))
                {
                    $data['walletamount'] -= $reffers->amount;
                    $data['withdrwaamount'] +=$reffers->amount;
                }
            }
            
            if(count($data2['reffered']) == 0 || $data['walletamount'] < 0)
            {
             $data['walletamount']=0;   
            }

            
          }
        else
        { 
            $data['transactions'] = Transaction::getoption23(array('user_id'=>$userid));
            foreach($data['transactions']['data'] as $key=>$dd)
            { 
                
                if($dd['amount'] != '0' && $dd['transaction_id'] =='0')
                {
                    $data['transactions']['data'][$key]['transaction_id'] = 'Wallet';
                }
                elseif($dd['transaction_id'] != '0')
                {
                  $data['transactions']['data'][$key]['transaction_id'] = '#'.$dd['transaction_id'];
                }else
                {
                    $data['transactions']['data'][$key]['transaction_id'] = 'Free';
                }
                
                $data['transactions']['data'][$key]['packagename'] = Subscription_content::getname($dd['package_id']);
                
                if($dd['package_id'] == '1')
                {
                  $data['transactions']['data'][$key]['method'] = '--';
                }
                elseif($dd['amount'] != '0' && $dd['transaction_id'] =='0')
                {
                    $data['transactions']['data'][$key]['method'] ='Wallet';
                }
                else
                 {
                    $data['transactions']['data'][$key]['method'] ='Card';
                 }
                 
                if($data['transactions']['current_page']=='1')
                {
                    $datek = Hours::getdetailsuserret($userid,'expiry');
                    $data['transactions']['data'][$key]['expiry'] = date('M-d Y',strtotime($datek));
                }else
                {
                    if(!empty($dd['exp']) &&  $dd['exp']!='')
                    {
                        $data['transactions']['data'][$key]['expiry'] = date('M-d Y',strtotime($dd['exp']));
                    }else
                    {
                    
                    $date = strtotime($dd['created']);
                    if($dd['package_id'] == '1'){
                     $data['transactions']['data'][$key]['expiry'] = date('M-d Y',strtotime("+7 day", $date));
                     }else if($dd['package_id'] == '3')
                     {  
                        $data['transactions']['data'][$key]['expiry'] = date('M-d Y',strtotime("+1 year", $date));  
                     }
                     else{
                         $data['transactions']['data'][$key]['expiry'] = date('M-d Y',strtotime("+1 month", $date)); 
                     }
                    } 
                }
            }
        }

            $messags['message'] = "Wallet data.";
            $messags['status']= 1; 
            $messags['data']= $data;  
            echo json_encode($messags);
            die;
    }
    
    /* to send refferal code to friend */
    public function reffer_friend(Request $request)
    {
        $postdata = file_get_contents("php://input");
        $data=json_decode($postdata,true);
        $id = $data['id'];
                $email = [['email','=',$data['email']],['status','!=','2']];
                $exists = User::getUsermatch($email);
                $messags= array();
                if(count($exists) > 0)
                {
                    $messags['message'] = "Email already exist.";
                    $messags['status']= 0; 
                    $messags['data']= 'Null'; 
                }else
                {     $id = $id;
                    $weres = [['friend_email','=',$data['email']],['uid','=',$id],['status','=','1']];
                     $reffercheck =  Reffer::getoptionmatch($weres);
                        if(count($reffercheck) > 0)
                        {
                            $messags['message'] = "Email already exist.";
                            $messags['status']= 0; 
                           $messags['data']= 'Null'; 
                        }else
                        {
                            $were= [['id','=', $id]];
                            $user = User::getbycondition($were);
                                foreach($user as $u){
                                 $r = $u;
                                }
                            if(count($user)!=0)
                            {
                                $user = User::getbycondition($were);
                                foreach($user as $u){
                                 $r = $u;
                                }
                                
                                
                                $options = Options::getoption();
                                if(!empty($options) && count($options) > 0)
                                {
                                foreach($options as $option)
                                {
                                    if($option->coulmn_name == 'r_email_text')
                                    {
                                     $r_email_text= $option->coulmn_value; 
                                    }
                                }
                                }else
                                {
                                    $r_email_text='';
                                }
                                $id = $r->id; 
                                $name = '';
                                $hash    = str_pad(rand(0,999), 5, "0", STR_PAD_LEFT);
                                $string  = $hash;
                                $iv = base64_encode($string); 
                                if(empty($r_email_text))
                                {
                                $htmls = str_replace("#name#",ucfirst($r->name),Config::get('constants.Reffer_html'));
                                }else
                                {
                                  $htmls = str_replace("#name#",ucfirst($r->name),$r_email_text); 
                                }
                                
                                //$id = $r->id; 
                               // $name = '';
                               // $hash    = md5(uniqid(rand(), true));
                               // $string  = $id."&".$hash;
                               //  $iv = base64_encode($string);
                                //$htmls = str_replace("#name#",ucfirst($r->name),Config::get('constants.Reffer_html'));
                                $header = Config::get('constants.Reffer_header'); 
                                $buttonhtml = Config::get('constants.Reffer_btn_html');
                                $pass_url  = url('getinvitation/'.$iv); 
                                $path = url('resources/views/email.html');
                                $subject = Config::get('constants.Reffer_subject');
                                $to_email=$data['email'];
                                if($this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email))
                                {        $amout = Subscription_content::getUsermatch(array('id'=>'3'));
                
                                    $weres2 = [['friend_email','=',$data['email']],['uid','=',$id]];
                                    $reffercheck2 =  Reffer::getoptionmatch($weres2);
                                   
                                      if(count($reffercheck2) > 0)
                                    {
                                       
                                        $datas = array(
                                            'friend_email'=>$data['email'],
                                            'uid'=>$id,
                                            'amount'=>'0',
                                            'token'=>$iv
                                            );
                                            $were3 =  array(
                                            'friend_email'=>$data['email'],
                                            'uid'=>$id
                                            );
                                        if(Reffer::updateoption2($datas,$were3))
                                        {
                                            $messags['message'] = "Referreal email has been sent successfully.";
                                            $messags['status']= 1; 
                                            $messags['data']= 'Null';   
                                        }else
                                        {
                                            $messags['message'] = "Error to send referreal email.";
                                            $messags['status']= 0; 
                                            $messags['data']= 'Null'; 
                                        }
                                    }else
                                    {    $datas = array(
                                            'friend_email'=>$data['email'],
                                            'uid'=>$id,
                                            'amount'=>'0',
                                            'token'=>$iv
                                            );
                                        if(Reffer::insertoption($datas))
                                        {
                                        $messags['message'] = "Referreal email has been sent successfully.";
                                        $messags['status']= 1; 
                                        $messags['data']= 'Null';  
                                        }else
                                        {
                                        $messags['message'] = "Error to send referreal email.";
                                        $messags['status']= 0; 
                                        $messags['data']= 'Null';  
                                        }  
                                    }
                                }
                             
                            }
                            
                        }
                }
        echo json_encode($messags);
        die;
    }
    
    /* all drop down on change data */
    public function get_dropdowns($id='',$type='')
    {
        if($id!='')
        { $text='';
             if($type=='country')
                {
                 $text = 'Country data';
                 $data['country']=country::getbycondition(array('status'=>'1','parent'=>0));
                 $data['course']=course::getoption();
                 $data['year']= Years::getbycondition(array('status'=>'1'));
                 $data['grade']= Grades::getbycondition(array('status'=>'1'));
                 $messags['data']= $data;  
                }
                
                if($type=='state')
                {
                 $gettags = [['parent', '=', $id],['status', '=', '1']]; 
                 $text = 'States data';
                 $states=country::getbycondition($gettags);
                 $messags['data']= $states;  
                }
                 if($type=='subject')
                {
                 $gettags = [['parent', '=', $id],['type', '=', '2'],['status', '=', '1']]; 
                 $text = 'Subject data';
                 $subject=course::getbycondition($gettags);
                 $messags['data']= $subject;  
                }
                if($type=='chapter')
                {
                 $gettags = [['parent', '=', $id],['type', '=', '3'],['status', '=', '1']]; 
                 $text = 'Chapter data';
                 $chapter=course::getbycondition($gettags);
                 $messags['data']= $chapter;  
                }
                $messags['message'] = $text;
                $messags['status']= 1; 
                
        }else
        {
            $messags['message'] = 'No data found';
            $messags['status']= 0; 
            $messags['data']= 'Null';   
        }
        
        echo json_encode($messags);
            die;
    }
    /* Report page shown data without search */
    public function report($id)
    {  
            $were = array('status'=>'1');
            $data['grades']= Grades::getbycondition($were);
            $data['years']= Years::getbycondition($were);
            $data['courses']= course::getoption();
            $data['countries']= country::getoption();
            $data['results']=User_test::getbyconditionpagination233(array('user_id'=>$id));
            foreach($data['results']['data'] as $key=>$dd)
            {
                $data['results']['data'][$key]['date'] = date('d/m/Y', strtotime($dd['test_date']));
                $were2 = [['test_id','=',$dd['id']],['suggested_answer','!=','']];
                $data['results']['data'][$key]['suggested'] = User_test_answers::getbycount($were2);
                $data['results']['data'][$key]['score'] = $dd['correct_answers'].'/'.$dd['total_questions'];
                $were1 = [['test_id','=',$dd['id']],['answer','!=','']];
              $data['results']['data'][$key]['attempt_answer'] = User_test_answers::getbycount($were1);
            } 
            
            if(count($data['results']) < 1)
            {
                $data['results']['data'] = array();
            }
            $messags['message'] = "Report data";
            $messags['status']= 1; 
            $messags['data']= $data;
            echo json_encode($messags);
            die;
    }
    
     /* Report search data */
     public function getsearch(Request $request)
    { 
        $postdata = file_get_contents("php://input");
        $data=json_decode($postdata,true);
           $were = array("status"=>'1');
          // $data['user_id'] = $id;
                if(isset($data['country']) && $data['country']!='' && $data['country']!='0')
                {
                   $were1= array('country'=>$data['country']);
                  $were = array_merge($were,$were1);
                }
                
                if(isset($data['state']) && $data['state']!='' && $data['state']!='0')
                {
                    $were2= array('state'=>$data['state']);
                    $were = array_merge($were,$were2);
                }
                
                if(isset($data['course']) && $data['course']!='' && $data['course']!='0')
                {
                    $were3= array('course'=>$data['course']);
                    $were = array_merge($were,$were3);
                }
                
                if(isset($data['grade']) && $data['grade']!='' && $data['grade']!='0')
                {
                  $were4= array('grade'=>$data['grade']);
                  $were = array_merge($were,$were4);
                }
                
                if(isset($data['year']) && $data['year']!='' && $data['year']!='0')
                {
                    $were5= array('year'=>$data['year']);
                    $were = array_merge($were,$were5);
                }
                
                
                if(isset($data['subject']) && $data['subject']!='' && $data['subject']!='0')
                {
                     $were6= array('subject'=>$data['subject']);
                     $were = array_merge($were,$were6);
                }
                
                if(isset($data['chapter']) && $data['chapter']!='' && $data['chapter']!='0')
                {
                     $were7= array('chapter'=>$data['chapter']);
                     $were = array_merge($were,$were7);
                }
                if(count($were) > 0)
                {   $data['user_id']=$data['user_id'];
                
                $result2 = DB::table('user_test');
                $result2 = $result2->leftjoin('user_test_answers', 'user_test.id', '=', 'user_test_answers.test_id');
                $result2 = $result2->leftjoin('question_answers', 'question_answers.id', '=', 'user_test_answers.question_id');
                
               /* $result2 = DB::table('user_test_answers');
                $result2 = $result2->join('question_answers', 'question_answers.id', '=', 'user_test_answers.question_id');
                $result2 = $result2->join('user_test', 'user_test.id', '=', 'user_test_answers.test_id');*/
                if(isset($data['country']) && $data['country']!='' && $data['country']!='0')
                { 
                $result2 = $result2->where('user_test.country', $data['country']);
                }
                if(isset($data['state']) && $data['state']!='' && $data['state']!='0')
                {
                $result2 = $result2->where('user_test.state', $data['state']);
                }
                if(isset($data['course']) && $data['course']!='' && $data['course']!='0')
                {
                $result2 = $result2->where('user_test.course', $data['course']);
                }
                if(isset($data['grade']) && $data['grade']!='' &&  $data['grade']!='0')
                {
                $result2 = $result2->where('user_test.grade', $data['grade']);
                }
                if(isset($data['year']) && $data['year']!='' && $data['year']!='0')
                {
                $result2 = $result2->where('user_test.year', $data['year']);
                }
                if(isset($data['subject']) && $data['subject']!='' && $data['subject']!='0')
                {
                $result2 = $result2->where('user_test.subject', $data['subject']);
                }
                if(isset($data['chapter']) && $data['chapter']!='' && $data['chapter']!='0')
                {
                $result2 = $result2->where('user_test.chapter', $data['chapter']);
                }
                $result2 = $result2->where('user_test.user_id',$data['user_id']);
                $results = $result2->distinct()->select('user_test.*');
                $data['results'] = $result2->orderBy('user_test.id', 'desc')->paginate(10)->toArray();
           
                        if(count($data['results']['data']) > 0 && $data['results']['data'][0]->id!='')
                        {
                            foreach($data['results']['data'] as $key=>$dd)
                            {
                            $data['results']['data'][$key]->date = date('d/m/Y', strtotime($dd->test_date));
                            $were2 = [['test_id','=',$dd->id],['suggested_answer','!=','']];
                            $data['results']['data'][$key]->suggested = User_test_answers::getbycount($were2);
                            $data['results']['data'][$key]->score = $dd->correct_answers.'/'.$dd->total_questions;
                            $were1 = [['test_id','=',$dd->id],['answer','!=','']];
                             $data['results']['data'][$key]->attempt_answer = User_test_answers::getbycount($were1);
                            }
                            $messags['message'] = "Report search data";
                            $messags['status']= 1; 
                            $messags['data']= $data;
                        }else
                        {
                           $data['results']['data']=array();
                            $messags['message'] = "Report search no data found";
                            $messags['status']= 0; 
                            $messags['data']= $data;
                        }
                }else
                {  
                    $data['user_id']=$data['user_id'];
                    $data['results']=User_test::getbyconditionpagination233(array('user_id'=>$data['user_id']));
                    foreach($data['results']['data'] as $key=>$dd)
                    {
                    $data['results']['data'][$key]['date'] = date('d/m/Y', strtotime($dd['test_date']));
                    $were2 = [['test_id','=',$dd['id']],['suggested_answer','!=','']];
                    $data['results']['data'][$key]['suggested'] = User_test_answers::getbycount($were2);
                    $data['results']['data'][$key]['score'] = $dd['correct_answers'].'/'.$dd['total_questions'];
                    }
                    $messags['message'] = "Report search data";
                    $messags['status']= 1; 
                    $messags['data']= $data;
                }
            
            echo json_encode($messags);
            die;
    }
     /* Data of reffer friends list*/
    public function referral_listing(Request $request,$id)
    {
            $were = array('uid'=>$id);
            $data['reffered'] = Reffer::getbycondition22($were);
            if(count($data['reffered']['data']) > 0)
            {
            foreach($data['reffered']['data'] as $ky=>$dd)
            {
                if(!empty($dd['friend_id']))
                {
                    $data['reffered']['data'][$ky]['name']=User::getdetailsuserret($dd['friend_id'],'');
                }else
                {
                  $data['reffered']['data'][$ky]['name']='';
                }
            }
            }else
            {
                $data['reffered']['data'] = array();
            }
            $reffers= User::getbycondition(array('id'=>$were,'status'=>'1'));
            $data['refferal_code'] = $reffers[0]->refferal_code;
            $data['refferal_code_url'] = url('getinvitations2/'.$reffers[0]->refferal_code);
            $messags['message'] = "refferal data";
            $messags['status']= 1; 
            $messags['data']= $data;
            echo json_encode($messags);
            die;
    }
    /* to send all types of mails */
    public function createnotification($id='',$name='',$htmls='',$header='',$buttonhtml='',$pass_url='',$path='',$subject='',$to_email='')
    {  
            $email_path    = file_get_contents($path);
            $cur_year = date('Y');
            $email_content = array('[name]','[pass_url]','[htmls]','[buttonhtml]','[header]','[cur_year]');
            $replace  = array($name,$pass_url,$htmls,$buttonhtml,$header,$cur_year);
            $message = str_replace($email_content,$replace,$email_path);
            $header = 'From: '.env("IMAP_HOSTNAME_TEST").'' . "\r\n";
            $header .= "MIME-Version: 1.0\r\n";
            $header .= "Content-type: text/html\r\n";
            $retval = mail($to_email,$subject,$message,$header); 
             if($retval)
             {
               return true;    
             }else
             {
                 return false;
             }
    }
    /* get data of user to show in update profile page */
    public function profile_get($id='')
    {
        $data['users'] = User::getfirst(array('id'=>$id));
        foreach($data['users'] as $ky=>$user)
        {  
            if(!empty($user['country']))
            { 
            $gettags = [['parent', '=', $user['country']],['status', '=', '1']]; 
            $states = country::getbycondition($gettags);
            $data['users'][$ky]['state_data'] = $states;
            }else
            {
               $data['users'][$ky]['state_data'] = ''; 
            }
        } 
        $gettags = [['parent', '=', $id],['status', '=', '1']]; 
        $text = 'States data';
        $states=country::getbycondition($gettags);
        $messags['data']= $states;  
        $data['countries'] = country::getoption();
        $data['states'] = array();
        $transactions = Transaction::getbycondition(array('user_id'=>$id));
        $data['show'] ='0';
        foreach($transactions as $k=>$tr)
        {
           if($tr->recurring=='1')
          { 
            if (strpos($tr->transaction_id, 'I-') !== false) 
               {
                      $data['show'] = '1';
                     $data['id'] = $tr->transaction_id;
                }
          }   
        }
        $messags['message'] = "profile data.";
        $messags['status']= 1; 
        $messags['data']= $data;
        
        
        echo json_encode($messags);
            die;
    }
    
     /* update profile data */
 public function update_profile(Request $request,$userid='')
  {
      $messags = array();
       $postdata = file_get_contents("php://input");
        $data=json_decode($postdata,true);
        
        if($data['isPasswordUpdate'] == 1)
        {
            if(!empty($data['oldpassword']))
            {
              $wereh= [['email','=',$data["email"]],['id','=',$userid]];
              $hashedPassword= User::getdetailsuserret2($wereh,'password');
                if (Hash::check($data['oldpassword'], $hashedPassword)) 
                {
                    unset($data['oldpassword']);
                }else
                {
                    $messags['message'] = "The old password you entered does not match our records, Please try again.";
                    $messags['status']= 0;  
                    echo json_encode($messags);
                     die; 
                }
            }
          if(!empty($data['newpassword']) && empty($data['conpassword']))
          {
                  $messags['message'] = "Confirm password is required.";
                  $messags['status']= 0; 
                   echo json_encode($messags);
                 die;
          }
          else if(!empty($data['conpassword']) && empty($data['newpassword']))
          {
                $messags['message'] = "New password is required.";
                $messags['status']= 0; 
                 echo json_encode($messags);
                 die;
          }
          else if(!empty($data['conpassword']) && !empty($data['newpassword']))
          {
            if($data['conpassword'] == $data['newpassword'])   
            {
                $data['password'] = Hash::make( $data['newpassword'] );
                unset($data['conpassword']);
                unset($data['newpassword']);
            }else
            {
              $messags['message'] = "Please enter confirm password same as new password.";
                $messags['status']= 0;   
                 echo json_encode($messags);
                 die;
            }
          }
        }
              $data['status']='1';
              if(isset($data['dob']) && !empty($data['dob']))
              {
                  $data['dob'] = date('Y-m-d H:i:s',strtotime($data['dob']));
              }
              $were= [['email','=',$data["email"]],['id','!=',$userid],['status','!=','2']];
              $exists= User::getUsermatch($were);
              if(count($exists) > 0)
              {
                  $messags['message'] = "Email already exist.";
                  $messags['status']= 0;   
              }else
              {   
                  if(!empty($data["phone"]))
                   {
                        $were= [['phone','=',$data["phone"]],['id','!=',$userid],['status','!=','2']];
                        $exists= User::getUsermatch($were);
                        if(count($exists) > 0)
                        {
                         $messags['message'] = "Phone number is already exist.";
                         $messags['status']= 0;   
                        }else
                        {
                            if(User::updateUser($data,$userid))
                            {
                                 $messags['message'] = "Your profile has been updated sucessfully.";
                                 $messags['status']= 1;    
                            }else
                            {
                                 $messags['message'] = "Error to update your profile.";
                                 $messags['status']= 0;   
                            } 
                        }
                   }else
                   {
                    if(User::updateUser($data,$userid))
                    {
                     $messags['message'] = "Your profile has been updated sucessfully.";
                     $messags['status']= 1;    
                    }else
                    {
                     $messags['message'] = "Error to update your profile.";
                     $messags['status']= 0;   
                    } 
                   }
              }
                 echo json_encode($messags);
                 die;
  }
  
  /* upload file of sites */
 public function uploadfile(Request $request,$id)
   {
       $data = [];
		$path = (!empty($_POST['path'])?$_POST['path']:"");
		$target_dir = public_path('/'.$_POST['path'].'/');
		// Loop through each file
			$target_file = $target_dir . basename($_FILES["image"]["name"]);
			if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
				$data = basename($_FILES["image"]["name"]);
				User::updateUser(array('profile'=>basename($_FILES["image"]["name"])),$id);
			} 
	
		if(empty($data)) {
			$data['imgs'] = "";
			$data['message']  = "Sorry, there was an error uploading your file.";
			$data['status']  = 0;
		}
	
	echo json_encode($data);
             die;
       
   }
   
   public function uploadfile2(Request $request)
   {
       $data = [];
		$path = (!empty($_POST['path'])?$_POST['path']:"");
		$target_dir = public_path('/'.$_POST['path'].'/');
		// Loop through each file
			$target_file = $target_dir . basename($_FILES["image"]["name"]);
			if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
				$data = basename($_FILES["image"]["name"]);
			} 
	
		if(empty($data)) {
			$data['imgs'] = "";
			$data['message']  = "Sorry, there was an error uploading your file.";
			$data['status']  = 0;
		}
	
	echo json_encode($data);
             die;
       
   }
   
    /* Data get searched questions during attempt test */
   public function getquestions(Request $request,$id='')
    { 
            $postdata = file_get_contents("php://input");
            $data=json_decode($postdata,true);
            $were = array('status'=>'1');
                if(isset($data['country']) && $data['country']!='')
                {
                   $were1= array('country'=>$data['country']);
                  $were = array_merge($were,$were1);
                }
                
                if(isset($data['state']) && $data['state']!='')
                {
                    $were2= array('state'=>$data['state']);
                    $were = array_merge($were,$were2);
                }
                
                if(isset($data['course']) && $data['course']!='')
                {
                    $were3= array('course'=>$data['course']);
                    $were = array_merge($were,$were3);
                }
                
                if(isset($data['grade']) && $data['grade']!='')
                {
                  $were4= array('grade'=>$data['grade']);
                  $were = array_merge($were,$were4);
                }
                
                if(isset($data['year']) && $data['year']!='')
                {
                    $were5= array('year'=>$data['year']);
                    $were = array_merge($were,$were5);
                }
                
                
                if(isset($data['subject']) && $data['subject']!='')
                {
                     $were6= array('subject'=>$data['subject']);
                     $were = array_merge($were,$were6);
                }
                
                if(isset($data['chapter']) && $data['chapter']!='')
                {
                     $were7= array('chapter'=>$data['chapter']);
                     $were = array_merge($were,$were7);
                }
                $weres = array();
                if(count($were) > 0 )
                {
                    $weres = $were;
                    unset($weres['status']); 
                }
               
                 $ids='';
                 $result2 = array();
                $result2 = DB::table('question_answers');
                $result2->leftJoin('users', function ($result2) {
                $result2->on('users.id', '=', 'question_answers.user_id')
                ->where('users.status', '!=', 2);
                });
                 $result2->leftJoin('admins', 'admins.id', '=', 'question_answers.is_admin');
                 if(isset($data['country']) && $data['country']!='')
                {
                $result2 = $result2->where('question_answers.country', $data['country']);
                }
                if(isset($data['state']) && $data['state']!='')
                {
                $result2 = $result2->where('question_answers.state', $data['state']);
                }
                if(isset($data['course']) && $data['course']!='')
                {
                $result2 = $result2->where('question_answers.course', $data['course']);
                }
                if(isset($data['grade']) && $data['grade']!='')
                {
                $result2 = $result2->where('question_answers.grade', $data['grade']);
                }
                if(isset($data['year']) && $data['year']!='')
                {
                $result2 = $result2->where('question_answers.year', $data['year']);
                }
                if(isset($data['subject']) && $data['subject']!='')
                {
                $result2 = $result2->where('question_answers.subject', $data['subject']);
                }
                if(isset($data['chapter']) && $data['chapter']!='')
                {
                $result2 = $result2->where('question_answers.chapter', $data['chapter']);
                }
                $result2 = $result2->where('question_answers.qstatus','=','1');
                $result2 = $result2->select('question_answers.*');
                $result2 = $result2->orderBy(DB::raw('RAND()'))->distinct('question_answers.id')->limit(10)->get();
               
                if((count($result2) > 0 && !empty($result2)))
                {   
                  if($ids=='')
                  {
                    $ids = $result2[0]->id;   
                  }
                  $answers = $result2;
                    if(count($answers) > 0)
                    {
                    $totals = array();
                    $all = array();
                     foreach($answers as $resultk)
                     {
                         $totals[] = $resultk->question;
                         $all[] = $resultk->id;
                     } 
                      $input = [
                        'user_id'=>$id,
                        'test_id'=>'0',
                        'total_questions' => count($totals),
                        'attempt_answer' => '0',
                        'correct_answers' => '0',
                        'all_questions'=>implode(',',$all)
                        ];
                       $pre_que_id =  User_test::insertoption2($input);
                       User_test::updateoption($weres,$pre_que_id);
                       $data['test_id']=$pre_que_id;
                     
                    }else
                    {
                     $answers=array(); 
                     $pre_que_id='';
                     $data['test_id']=$pre_que_id;
                        $data['message']  = "no data found.";
                        $data['status']  = 0;
                        $data['data']  = array();
                      
                    }
                    $data['message']  = "data found.";
                    $data['status']  = 1;
                    $data['data']  = $answers;
                }else
                { 
                   $data['message']  = "no data found.";
			        $data['status']  = 0;
			         $data['data']  = array();
                }
                return json_encode($data);
             die;
    }
    
    /* Data to check question exist on change of dropdown during attempt test */
    public function checkquestion(Request $request)
    {   
         $postdata = file_get_contents("php://input");
            $data=json_decode($postdata,true);
         $message = array();
            $were = array('status'=>'1');
                 $result2 = array();
                $result2 = DB::table('question_answers');
                $result2->leftJoin('users', function ($result2) {
                $result2->on('users.id', '=', 'question_answers.user_id')
                ->where('users.status', '!=', 2);
                });
                 $result2->leftJoin('admins', 'admins.id', '=', 'question_answers.is_admin');
                 if(isset($data['country']) && $data['country']!='')
                {
                  $result2 = $result2->where('question_answers.country', $data['country']);
                }
                if(isset($data['state']) && $data['state']!='')
                {
                  $result2 = $result2->where('question_answers.state', $data['state']);
                }
                if(isset($data['course']) && $data['course']!='')
                {
                  $result2 = $result2->where('question_answers.course', $data['course']);
                }
                if(isset($data['grade']) && $data['grade']!='')
                {
                  $result2 = $result2->where('question_answers.grade', $data['grade']);
                }
                if(isset($data['year']) && $data['year']!='')
                {
                  $result2 = $result2->where('question_answers.year', $data['year']);
                }
                if(isset($data['subject']) && $data['subject']!='')
                {
                  $result2 = $result2->where('question_answers.subject', $data['subject']);
                }
                if(isset($data['chapters']) && $data['chapters']!='')
                {
                  $result2 = $result2->where('question_answers.chapter', $data['chapters']);
                }
                $result2 = $result2->where('question_answers.qstatus','=','1');
                $result2 = $result2->select('question_answers.*');
                 $result2 = $result2->orderBy(DB::raw('RAND()'))->distinct('question_answers.id')->limit(10)->get();
                
               if((count($result2) > 0 && !empty($result2)))
                {  
                    $message['message']  = "Questions found.";
                        $message['status']  = 1;
                        
               }else
                    {
                      $message['message']  = "No Questions found.";
                        $message['status']  = 0;
                       
                    }
         
         
         return json_encode($message);
             die;
       
    }
    
    /* Data to submit answer and sugesstion during attempt test */
    public function addsugestion(Request $request,$userid)
    {
          $postdata = file_get_contents("php://input");
            $data=json_decode($postdata,true);
            if(isset($data['suggested_answer']))
            {
                /* Data to suggested answer during attempt test */
               $thisid = $data['id'];
               unset($data['id']);
               if(User_test_answers::updateoption($data,$thisid))
               { 
                    $data['uid'] = $userid;
                    $weress= [['id','!=','']];
                    $adminemail = Admin::getUsermatch($weress);
                      $were= [['id','=', $userid]];
                            $user = User::getbycondition($were);
                                foreach($user as $u){
                                 $r = $u;
                                }
                            if(count($user)!=0)
                            {
                                    $variavle = Config::get('constants.Suggested_question_html');
                                    $variavles = explode(' ',$variavle);
                                        foreach($variavles as $key=> $variavle)
                                        {
                                            if($variavle=='#name#')
                                            {
                                             $variavles[$key]=ucfirst($r->name);
                                            }
                                        }
                
                                $id = $r->id; 
                                $name = 'Admin';
                                $hash    = md5(uniqid(rand(), true));
                                $string  = $id."&".$hash;
                                 $iv = base64_encode($string);
                                 $htmls = implode(' ',$variavles).' , Please visit the following link given below:';
                                $header = Config::get('constants.Suggested_header');
                                $buttonhtml = Config::get('constants.Applyamount_btn_html');
                                $pass_url  = url('admin/suggestion_detail/'.$thisid); 
                                $path = url('resources/views/email.html');
                                $subject = Config::get('constants.Suggested_header');
                                $to_email=$adminemail[0];
                                $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                                 $arrays =[
                                'w_from' => 'user',
                                'from_id' => $r->id,
                                'w_to' => 'admin',
                                'to_id' => '1',
                                'title' =>implode(' ',$variavles),
                                'description' => implode(' ',$variavles),
                                'url' => 'admin/suggestion_detail/'.$thisid,
                                'tbl'=>'user_test_answers',
                                'status'=>'1'
                                ];
                                Notification::insertoption($arrays);
                            }
                 $messags['message'] = "Your suggestion has been added successfully.";
                 $messags['status']= 1;   
               }else
               {
                   $messags['message'] = "Error to add your suggestion.";
                   $messags['status']= 0; 
               }
            }else
            {   /* Data to submitt answer during attempt test */
                $getanswer = Question_answers::getbycondition(array('id'=>$data['question_id']));
                if(count($getanswer) > 0 )
                {
                   if($getanswer[0]->answer == $data['answer']) 
                   {
                      $ans ='1'; 
                   }else
                   {
                      $ans ='0'; 
                   }
                }else
                {
                    $ans ='0';
                }
                $wersid= $data['test_id'];
                $prequestions = User_test::getbycondition(array('id'=>$wersid));
                $u = '';
                foreach($prequestions as $prequestion)
                {
                    $u =$prequestion;
                   
                }
                    $core = $u->correct_answers+$ans;
                     $atemp = $u->attempt_answer+1;
                     $inputs =[
                        'correct_answers'=>$core,
                        'attempt_answer'=>$atemp
                        ]; 
             
                   User_test::updateoption($inputs,$wersid);
                 $data['user_id']=$userid;
                $answer_id =  User_test_answers::insertoption2($data);
                if($answer_id!='')
                {
                    $messags['message'] = "Your Answer Submitted Successfully.";
                    $messags['status']= 1; 
                    $messags['id']= $answer_id; 
                    
                }else
                {
                    $messags['message'] = "Error to submit your answer.";
                    $messags['status']= 0; 
                }  
            }
           
         echo json_encode($messags);
                         die;
        
    }
    
    /* To cancel the recurring */
 public function cancelrecurring($id,$userid)
  {
      if($userid!='')
        {
            if(Options::getoptionmatch3('paypal_mode')=='0')
            {    
             $username = Options::getoptionmatch3('sandbox_username');
             $password = Options::getoptionmatch3('sandbox_password');
              $signature = Options::getoptionmatch3('sandbox_signature');
            }else
            {
                $username = Options::getoptionmatch3('live_username');
                 $password = Options::getoptionmatch3('live_password');
                  $signature = Options::getoptionmatch3('live_signature');
            }
      $curl = curl_init();
    $user_id=$userid;
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
   curl_setopt($curl, CURLOPT_POST, true);
   curl_setopt($curl, CURLOPT_URL, 'https://api-3t.sandbox.paypal.com/nvp');
   curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
       'USER' => $username,  //Your API User
       'PWD' => $password,  //Your API Password
       'SIGNATURE' => $signature,   //Your API Signature

       'VERSION' => '108',
       'METHOD' => 'ManageRecurringPaymentsProfileStatus',
       'PROFILEID' => $id,         //here add your profile id                      
       'ACTION'    => 'Cancel' //this can be selected in these default paypal variables (Suspend, Cancel, Reactivate)
   )));

   $response =    curl_exec($curl);

   curl_close($curl);

   $nvp = array();

   if (preg_match_all('/(?<name>[^\=]+)\=(?<value>[^&]+)&?/', $response, $matches)) {
       foreach ($matches['name'] as $offset => $name) {
           $nvp[$name] = urldecode($matches['value'][$offset]);
       }
   }
   

   //printf("<pre>%s</pre>",print_r($nvp, true)); die; 
           if($nvp['ACK']=='Success')
           {
               Transaction::updateoption2(array('recurring'=>'0'),array('transaction_id'=>$id,'user_id'=>$userid));
               $messags['message'] = "Recurring has been canceled successfully.";
               $messags['status']= 1; 
           }else
           {
                $messags['message'] = "Error to cancel a recurring.";
               $messags['status']= 0; 
           }
        }
        
         echo json_encode($messags);
                         die;
  }
  
   /* Data after attempt test in result of  test */
  public function attempttest(Request $request,$id)
    {
        $data = $request->all();
       //$id= $data['pre_que_id'];
        $data['scores'] = User_test::getbycondition(array('id'=>$id));
        $data['realanswers'] =  Question_answers::gettotalresult($id);
        $messags['message'] = "Result data.";
        $messags['status']= 1; 
        $messags['data']= $data;
        echo json_encode($messags);
        die;
    }
    
     /* View page of notification */
    public function notification(Request $request,$userid)
    {    
      $were = [['w_to','=','user'],['status','!=','2'],['to_id','=',$userid]];
      $data['notifications'] = Notification::getbycondition234($were);
     if(count($data['notifications']['data']) > 0 )
     {
      foreach($data['notifications']['data'] as $k=>$noty)
      {
          if(!empty($noty['from_id']))
          {
              $img =User::getdetailsuserret2(array('id'=>$noty['from_id']),'profile');
              if(!empty($img))
              {
                $data['notifications']['data'][$k]['img']=url('/public/profile/'.$img);
              }else
              {
                $data['notifications']['data'][$k]['img']=url('/uploads/Dummy-image.jpg');  
              }
          }
      }
     }else
     {
         $data['notifications']['data'] = array();
     }
        $messags['message'] = "notification data.";
        $messags['status']= 1; 
        $messags['data']= $data;
        echo json_encode($messags);
        die;
    }
    
    /* data of search report */
     public function test_detail(Request $request,$id='',$user_id)
    {
           $realanswers2 =  User_test::getbycondition(array('id'=>$id,'user_id'=>$user_id));
            $realanswers = array();

           if(count($realanswers2) > 0)
           {  $alls =  explode(',',$realanswers2[0]->all_questions);
           
               foreach($alls as $realanswer)
               {
                    $data = DB::table('question_answers');
                    $data->join('user_test_answers', 'user_test_answers.question_id', '=', 'question_answers.id');
                    $data->join('user_test', 'user_test.id', '=', 'user_test_answers.test_id')
                    ->where('user_test.id',$id);
                    $data =  $data->where('question_answers.id',$realanswer);
                    $data =  $data->select('question_answers.*','user_test_answers.*','user_test_answers.answer as myanswer','question_answers.answer as realanswer');
                    $data =  $data->distinct('question_answers.id')->get();
                    if(count($data) == '0')
                    {
                       $data = DB::table('question_answers'); 
                       $data =  $data->where('question_answers.id',$realanswer);
                     $data=  $data->select('question_answers.*')->distinct('question_answers.id')->get();
                      
                    }
                   $data = array_push($realanswers,$data[0]);
               }
           }else
           {
               $realanswers = array();
           }
            $messags['message'] = "notification data.";
            $messags['status']= 1; 
            $messags['data']= $realanswers;
            echo json_encode($messags);
        die;
                 
    }
    
    public function facebooklogin(Request $request)
    {
           $postdata = file_get_contents("php://input");
            $data=json_decode($postdata,true);
           $messags = array();
                    $data['status'] = '1';
                   if(!empty($data['fb_id']) || !empty($data['email']))
                   {
                       if(!empty($data['email']))
                       {
                           $condition1 = [['email','=',$data['email']],['status','!=','2']];
                           $d1 = User::getUsermatch($condition1);
                               if(count($d1) > 0)
                               {
                                   $condition2 = [['email','=',$data['email']],['status','!=','0']];
                                   $d2 = User::getUsermatch($condition2);
                                    if(count($d2) > 0)
                                    {
                                        
                                      $userdatas = User::getbycondition(array('email'=>$data['email']));
                                      	if(count($userdatas)>0  && !empty($userdatas))
                                        {
                                            foreach($userdatas as $u){
                                            $users = $u;
                                            }
                                            $userdata = array(
                                            'id'=> $users->id ,
                                            'name' => $users->name ,
                                            'lname' => $users->lname ,
                                            'email' => $users->email ,
                                            'profile'=>$users->profile ? $users->profile: 'null',
                                            );
                                        }
                                        $messags['message'] = "You logged in successfully.";
                                        $messags['status']= 1;
                                        $messags['data']= $userdata; 
                                    }else
                                    {
                                        $messags['message'] = "Your profile is exists, but your account is inactive.";
                                        $messags['status']= 0;
                                        $messags['data']= 'null'; 
                                    }
                               }else
                               {
                                    $condition3 = [['fb_id','=',$data['fb_id']],['status','!=','2']];
                                    $d3 = User::getUsermatchdb($condition3);
                                   if(count($d3) > 0)
                                    { 
                                        $condition4 = [['fb_id','=',$data['fb_id']],['status','!=','0']];
                                        $d4 = User::getUsermatch($condition4);
                                        if(count($d4) > 0)
                                        {
                                            //$condition3 = User::getbycondition(array('fb_id'=>$data['fb_id']));
                                            $userdatas = User::getbycondition(array('fb_id'=>$data['fb_id']));
                                            if(count($userdatas)>0  && !empty($userdatas))
                                            {
                                            foreach($userdatas as $u){
                                            $users = $u;
                                            }
                                            $userdata = array(
                                            'id'=> $users->id ,
                                            'name' => $users->name ,
                                            'lname' => $users->lname ,
                                            'email' => $users->email ,
                                            'profile'=>$users->profile ? $users->profile: 'null',
                                            );
                                            } 
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['status']= 1;
                                            $messags['data']= $userdata;
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['status']= 0;
                                            $messags['data']= 'null'; 
                                        }
                                    }else
                                    {
                                        
                                        if(isset($data['picture']))
                                        {
                                                $temp = explode('/', url('/'));
                                                $url = $data['picture'];
                                                $destination_folder = public_path('/profile/');
                                                $uniquesavename=time().uniqid(rand());
                                                $newfname = $destination_folder.$uniquesavename.'.jpeg'; //set your file ext
                                                $savepath= $uniquesavename.'.jpeg';
                                                $file = fopen ($url, "rb");
                                                if ($file) {
                                                $newf = fopen ($newfname, "a"); // to overwrite existing file
                                                
                                                if ($newf)
                                                while(!feof($file)) {
                                                fwrite($newf, fread($file, 1024 * 8 ), 1024 * 8 );
                                                
                                                }
                                                }
                                                
                                                if ($file) {
                                                fclose($file);
                                                }
                                                
                                                if ($newf) {
                                                fclose($newf);
                                                }
                                                
                                                $data['profile'] = $savepath;
                                                 unset($data['picture']);
                                        }
                                        
                                       
                                        $data = array_filter($data);
                                        $data['package_id']='1';
                                        $data['refferal_code']=time().uniqid(rand());
                                        if(User::insertUser($data))
                                        {
                                            $userdatas = User::getbycondition(array('fb_id'=>$data['fb_id']));
                                            if(count($userdatas)>0  && !empty($userdatas))
                                            {
                                            foreach($userdatas as $u){
                                            $users = $u;
                                            }
                                            $userdata = array(
                                            'id'=> $users->id ,
                                            'name' => $users->name ,
                                            'lname' => $users->lname ,
                                            'email' => $users->email ,
                                            'profile'=>$users->profile ? $users->profile: 'null',
                                            );
                                            
                                            /* add free package user logged in by facebook*/
                                           
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+7 day", $date));  
                                            $transaction_data=array(
                                            'transaction_id'=>'0',
                                            'user_id'=>$users->id,
                                             'package_id'=>'1',
                                             'status'=>'completed',
                                             'currency'=>'AUD',
                                            'amount'=>'0',
                                            'exp'=>$date
                                
                                             );
                                           if(Transaction::insertUser($transaction_data))
                                           {
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+7 day", $date));  
                                             $hours_data=array(
                                            'user_id'=>$users->id,
                                            'package_id'=>'1',
                                            'total_questions_uploaded'=>'0',
                                            'total_hours'=>'00:10:00',
                                            'expiry'=>$date,
                                            'current_question_count'=>0,
                                            
                                
                                             );
                                              Hours::insertUser($hours_data);
                                             }
                                            /* end free package**/
                                            
                                            
                                            } 
                                            
                                            if(!empty($users->email))
                                            {  $name= $users->name;
                                               $id = $users->id;
                                                $iv = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                                                $htmls = str_replace('#password#',$iv,str_replace('#name#',$users->name,Config::get('constants.Fb_password'))).', Please visit the following link given below:';
                                                $header = Config::get('constants.Fb_header');
                                                $buttonhtml = Config::get('constants.Fb_btn_html');
                                                $pass_url  = url('/login'); 
                                                $path = url('resources/views/email.html');
                                                $subject = Config::get('constants.Fb_Subject');
                                                $to_email=$users->email;
                                                $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                                            }
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['status']= 1;
                                            $messags['data']=$userdata; 
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['status']= 0;
                                            $messags['data']= 'null'; 
                                        }
                                        
                                    }
                               }
                             
                           }else
                           {
                                 $condition3 = [['fb_id','=',$data['fb_id']],['status','!=','2']];
                                $d5 =  User::getUsermatchdb($condition3);
                                   if(count($d5) > 0)
                                    { 
                                        $condition4 = [['fb_id','=',$data['fb_id']],['status','!=','0']];
                                       $d6 =  User::getUsermatch($condition4);
                                        if(count($d6) > 0)
                                        {
                                           // $condition3 = User::getbycondition(array('fb_id'=>$data['fb_id']));
                                            $userdatas = User::getbycondition(array('fb_id'=>$data['fb_id']));
                                            if(count($userdatas)>0  && !empty($userdatas))
                                            {
                                            foreach($userdatas as $u){
                                            $users = $u;
                                            }
                                            $userdata = array(
                                            'id'=> $users->id ,
                                            'name' => $users->name ,
                                            'lname' => $users->lname ,
                                            'email' => $users->email ,
                                            'profile'=>$users->profile ? $users->profile: 'null',
                                            );
                                            
                                            }
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['status']= 1;
                                            $messags['data']=$userdata; 
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['status']= 0;
                                            $messags['data']= 'null'; 
                                        }
                                    }else
                                    {
                                        
                                         if(isset($data['picture']))
                                        {
                                                $temp = explode('/', url('/'));
                                                $url = $data['picture'];
                                                $destination_folder = public_path('/profile/');
                                                $uniquesavename=time().uniqid(rand());
                                                $newfname = $destination_folder.$uniquesavename.'.jpeg'; //set your file ext
                                                $savepath= $uniquesavename.'.jpeg';
                                                $file = fopen ($url, "rb");
                                                if ($file) {
                                                $newf = fopen ($newfname, "a"); // to overwrite existing file
                                                
                                                if ($newf)
                                                while(!feof($file)) {
                                                fwrite($newf, fread($file, 1024 * 8 ), 1024 * 8 );
                                                
                                                }
                                                }
                                                
                                                if ($file) {
                                                fclose($file);
                                                }
                                                
                                                if ($newf) {
                                                fclose($newf);
                                                }
                                                
                                                $data['profile'] = $savepath;
                                                 unset($data['picture']);
                                        }
                                        $data = array_filter($data);
                                        $data['package_id']='1';
                                         $data['refferal_code']=time().uniqid(rand());
                                        if(User::insertUser($data))
                                        {
                                            $userdatas = User::getbycondition(array('fb_id'=>$data['fb_id']));
                                            if(count($userdatas)>0  && !empty($userdatas))
                                            {
                                            foreach($userdatas as $u){
                                            $users = $u;
                                            }
                                            $userdata = array(
                                            'id'=> $users->id ,
                                            'name' => $users->name ,
                                            'lname' => $users->lname ,
                                            'email' => $users->email ,
                                            'profile'=>$users->profile ? $users->profile: 'null',
                                            );
                                            /* add free package user logged in by facebook*/
                                           
                                             $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+7 day", $date)); 
                                            $transaction_data=array(
                                            'transaction_id'=>'0',
                                            'user_id'=>$users->id,
                                             'package_id'=>'1',
                                             'status'=>'completed',
                                             'currency'=>'AUD',
                                            'amount'=>'0',
                                             'exp'=>$date
                                
                                             );
                                           if(Transaction::insertUser($transaction_data))
                                           {
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+7 day", $date));  
                                             $hours_data=array(
                                            'user_id'=>$users->id,
                                            'package_id'=>'1',
                                            'total_questions_uploaded'=>'0',
                                            'total_hours'=>'00:10:00',
                                            'expiry'=>$date,
                                            'current_question_count'=>0,
                                
                                             );
                                              Hours::insertUser($hours_data);
                                             }
                                            /* end free package**/
                                            } 
                                            
                                            if(!empty($users->email))
                                            {  $name= $users->name;
                                               $id = $users->id;
                                                $iv = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                                                $htmls = str_replace('#password#',$iv,str_replace('#name#',$users->name,Config::get('constants.Fb_password'))).', Please visit the following link given below:';
                                                $header = Config::get('constants.Fb_header');
                                                $buttonhtml = Config::get('constants.Fb_btn_html');
                                                $pass_url  = url('/login'); 
                                                $path = url('resources/views/email.html');
                                                $subject = Config::get('constants.Fb_Subject');
                                                $to_email=$users->email;
                                                $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                                            }
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['status']= 1;
                                            $messags['data']= $userdata; 
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['status']= 0;
                                            $messags['data']= 'null'; 
                                        } 
                                    }
                           }
                   }else
                   {
                        $messags['message'] = "Error to login, try again later.";
                        $messags['status']= 0;
                        $messags['data']= 'null';
                   }
        echo json_encode($messags);
                         die;
    }
    
     public function googlelogin(Request $request)
    {
      $messags = array();
                $postdata = file_get_contents("php://input");
                $data=json_decode($postdata,true);
                    $data['status'] = '1';
                   if(!empty($data['g_id']) || !empty($data['email']))
                   {
                       if(!empty($data['email']))
                       {
                           $condition1 = [['email','=',$data['email']],['status','!=','2']];
                           $d1 = User::getUsermatch($condition1);
                               if(count($d1) > 0)
                               {
                                   $condition2 = [['email','=',$data['email']],['status','!=','0']];
                                   $d2 = User::getUsermatch($condition2);
                                    if(count($d2) > 0)
                                    {
                                      $userdatas = User::getbycondition(array('email'=>$data['email']));
                                        if(count($userdatas)>0  && !empty($userdatas))
                                        {
                                        foreach($userdatas as $u){
                                        $users = $u;
                                        }
                                        $userdata = array(
                                        'id'=> $users->id ,
                                        'name' => $users->name ,
                                        'lname' => $users->lname ,
                                        'email' => $users->email ,
                                        'profile'=>$users->profile ? $users->profile: 'null',
                                        );
                                         
                                        }
                                      
                                        $messags['message'] = "You logged in successfully.";
                                        $messags['status']= 1;
                                        $messags['data']= $userdata; 
                                    }else
                                    {
                                        $messags['message'] = "Your profile is exists, but your account is inactive.";
                                        $messags['status']= 0;
                                        $messags['data']= 'null'; 
                                    }
                               }else
                               {
                                    $condition3 = [['g_id','=',$data['g_id']],['status','!=','2']];
                                    $d3 = User::getUsermatchdb($condition3);
                                   if(count($d3) > 0)
                                    { 
                                        $condition4 = [['g_id','=',$data['g_id']],['status','!=','0']];
                                        $d4 = User::getUsermatch($condition4);
                                        if(count($d4) > 0)
                                        {
                                            //$condition3 = User::getbycondition(array('fb_id'=>$data['fb_id']));
                                            $userdatas = User::getbycondition(array('g_id'=>$data['g_id']));
                                            if(count($userdatas)>0  && !empty($userdatas))
                                            {
                                            foreach($userdatas as $u){
                                            $users = $u;
                                            }
                                            $userdata = array(
                                            'id'=> $users->id ,
                                            'name' => $users->name ,
                                            'lname' => $users->lname ,
                                            'email' => $users->email ,
                                             'profile'=>$users->profile ? $users->profile: 'null',
                                            );
                                            
                                            } 
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['status']= 1;
                                            $messags['data']= $userdata;
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['status']= 0;
                                            $messags['data']= 'null'; 
                                        }
                                    }else
                                    {
                                       if(isset($data['picture']))
                                        {
                                                $temp = explode('/', url('/'));
                                                $url = $data['picture'];
                                                $destination_folder = public_path('/profile/');
                                                $uniquesavename=time().uniqid(rand());
                                                $newfname = $destination_folder.$uniquesavename.'.jpeg'; //set your file ext
                                                $savepath= $uniquesavename.'.jpeg';
                                                $file = fopen ($url, "rb");
                                                if ($file) {
                                                $newf = fopen ($newfname, "a"); // to overwrite existing file
                                                
                                                if ($newf)
                                                while(!feof($file)) {
                                                fwrite($newf, fread($file, 1024 * 8 ), 1024 * 8 );
                                                
                                                }
                                                }
                                                
                                                if ($file) {
                                                fclose($file);
                                                }
                                                
                                                if ($newf) {
                                                fclose($newf);
                                                }
                                                
                                                $data['profile'] = $savepath;
                                                 unset($data['picture']);
                                        }
                                        $data = array_filter($data);
                                        $data['package_id']='1';
                                         $data['refferal_code']=time().uniqid(rand());
                                        if(User::insertUser($data))
                                        {
                                            $userdatas = User::getbycondition(array('g_id'=>$data['g_id']));
                                            if(count($userdatas)>0  && !empty($userdatas))
                                            {
                                            foreach($userdatas as $u){
                                            $users = $u;
                                            }
                                            $userdata = array(
                                            'id'=> $users->id ,
                                            'name' => $users->name ,
                                            'lname' => $users->lname ,
                                            'email' => $users->email ,
                                             'profile'=>$users->profile ? $users->profile: 'null',
                                            );
                                            /* add free package user logged in by facebook*/
                                           
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+7 day", $date));
                                            $transaction_data=array(
                                            'transaction_id'=>'0',
                                            'user_id'=>$users->id,
                                             'package_id'=>'1',
                                             'status'=>'completed',
                                             'currency'=>'AUD',
                                            'amount'=>'0',
                                             'exp'=>$date
                                
                                             );
                                           if(Transaction::insertUser($transaction_data))
                                           {
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+7 day", $date));  
                                             $hours_data=array(
                                            'user_id'=>$users->id,
                                            'package_id'=>'1',
                                            'total_questions_uploaded'=>'0',
                                            'total_hours'=>'00:10:00',
                                            'expiry'=>$date,
                                            'current_question_count'=>0,
                                
                                             );
                                              Hours::insertUser($hours_data);
                                             }
                                            /* end free package**/
                                            }
                                            
                                            if(!empty($users->email))
                                            {  $name= $users->name;
                                               $id = $users->id;
                                                $iv = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                                                $htmls = str_replace('#password#',$iv,str_replace('#name#',$users->name,Config::get('constants.Fb_password'))).', Please visit the following link given below:';
                                                $header = Config::get('constants.Fb_header');
                                                $buttonhtml = Config::get('constants.Fb_btn_html');
                                                $pass_url  = url('/login'); 
                                                $path = url('resources/views/email.html');
                                                $subject = Config::get('constants.Fb_Subject');
                                                $to_email=$users->email;
                                                $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                                            }
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['status']= 1;
                                            $messags['data']= $userdata; 
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['status']= 0;
                                            $messags['data']= 'null'; 
                                        }
                                        
                                    }
                               }
                             
                           }else
                           {
                                 $condition3 = [['g_id','=',$data['g_id']],['status','!=','2']];
                                $d5 =  User::getUsermatchdb($condition3);
                                   if(count($d5) > 0)
                                    { 
                                        $condition4 = [['g_id','=',$data['g_id']],['status','!=','0']];
                                       $d6 =  User::getUsermatch($condition4);
                                        if(count($d6) > 0)
                                        {
                                           // $condition3 = User::getbycondition(array('fb_id'=>$data['fb_id']));
                                            $userdatas = User::getbycondition(array('g_id'=>$data['g_id']));
                                            if(count($userdatas)>0  && !empty($userdatas))
                                            {
                                            foreach($userdatas as $u){
                                            $users = $u;
                                            }
                                            $userdata = array(
                                            'id'=> $users->id ,
                                            'name' => $users->name ,
                                            'lname' => $users->lname ,
                                            'email' => $users->email ,
                                             'profile'=>$users->profile ? $users->profile: 'null',
                                            );
                                            } 
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['status']= 1;
                                            $messags['data']= $userdata; 
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['status']= 0;
                                            $messags['data']= 'null'; 
                                        }
                                    }else
                                    {
                                         if(isset($data['picture']))
                                        {
                                                $temp = explode('/', url('/'));
                                                $url = $data['picture'];
                                                $destination_folder = public_path('/profile/');
                                                $uniquesavename=time().uniqid(rand());
                                                $newfname = $destination_folder.$uniquesavename.'.jpeg'; //set your file ext
                                                $savepath= $uniquesavename.'.jpeg';
                                                $file = fopen ($url, "rb");
                                                if ($file) {
                                                $newf = fopen ($newfname, "a"); // to overwrite existing file
                                                
                                                if ($newf)
                                                while(!feof($file)) {
                                                fwrite($newf, fread($file, 1024 * 8 ), 1024 * 8 );
                                                
                                                }
                                                }
                                                
                                                if ($file) {
                                                fclose($file);
                                                }
                                                
                                                if ($newf) {
                                                fclose($newf);
                                                }
                                                
                                                $data['profile'] = $savepath;
                                                 unset($data['picture']);
                                        }
                                        $data = array_filter($data);
                                        $data['package_id']='1';
                                         $data['refferal_code']=time().uniqid(rand());
                                        if(User::insertUser($data))
                                        {
                                            $userdatas = User::getbycondition(array('g_id'=>$data['g_id']));
                                            if(count($userdatas)>0  && !empty($userdatas))
                                            {
                                            foreach($userdatas as $u){
                                            $users = $u;
                                            }
                                            $userdata = array(
                                            'id'=> $users->id ,
                                            'name' => $users->name ,
                                            'lname' => $users->lname ,
                                            'email' => $users->email ,
                                             'profile'=>$users->profile ? $users->profile: 'null',
                                            );
                                            /* add free package user logged in by facebook*/
                                           
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+7 day", $date));
                                            $transaction_data=array(
                                            'transaction_id'=>'0',
                                            'user_id'=>$users->id,
                                             'package_id'=>'1',
                                             'status'=>'completed',
                                             'currency'=>'AUD',
                                            'amount'=>'0',
                                             'exp'=>$date
                                
                                             );
                                           if(Transaction::insertUser($transaction_data))
                                           {
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+7 day", $date));  
                                             $hours_data=array(
                                            'user_id'=>$users->id,
                                            'package_id'=>'1',
                                            'total_questions_uploaded'=>'0',
                                            'total_hours'=>'00:10:00',
                                            'expiry'=>$date,
                                            'current_question_count'=>0,
                                
                                             );
                                              Hours::insertUser($hours_data);
                                             }
                                            /* end free package**/
                                           
                                            }
                                            
                                            if(!empty($users->email))
                                            {  $name= $users->name;
                                               $id = $users->id;
                                                $iv = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                                                $htmls = str_replace('#password#',$iv,str_replace('#name#',$users->name,Config::get('constants.Fb_password'))).', Please visit the following link given below:';
                                                $header = Config::get('constants.Fb_header');
                                                $buttonhtml = Config::get('constants.Fb_btn_html');
                                                $pass_url  = url('/login'); 
                                                $path = url('resources/views/email.html');
                                                $subject = Config::get('constants.Fb_Subject');
                                                $to_email=$users->email;
                                                $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                                            }
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['status']= 1;
                                            $messags['data']= $userdata; 
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['status']= 0;
                                            $messags['data']= 'null'; 
                                        } 
                                    }
                                
                           }
                       
                       
                   }else
                   {
                        $messags['message'] = "Error to login, try again later.";
                        $messags['status']= 0;
                        $messags['data']= 'null';
                        
                   }
        echo json_encode($messags);
                         die;  
    }
    
    
    public function applyamount(Request $request,$userid)
    {
         $messags= array();
               $postdata = file_get_contents("php://input");
                $data=json_decode($postdata,true);
               $data['uid'] = $userid;
                $weress= [['id','!=','']];
                $adminemail = Admin::getUsermatch($weress);
               $were= [['id','=', $userid]];
                            $user = User::getbycondition($were);
                                foreach($user as $u){
                                 $r = $u;
                                }
                            if(count($user)!=0)
                            {
                                Withdraw::insertoption($data);
                                $idd= Withdraw::getdetailsuserret();
                                
                                
                    $variavle = Config::get('constants.Applyamount_html');
                    $variavles = explode(' ',$variavle);
                    foreach($variavles as $key=> $variavle)
                    {
                        if($variavle=='#name#')
                        {
                         $variavles[$key]=ucfirst($r->name);
                        }
                        if($variavle=='#amount#')
                        {
                         $variavles[$key]=$data['amount'];
                        }
                        
                    }
                
                                $id = $r->id; 
                                $name = 'Admin';
                                $hash    = md5(uniqid(rand(), true));
                                $string  = $id."&".$hash;
                                 $iv = base64_encode($string);
                                 $htmls = implode(' ',$variavles).' , Please visit the following link given below:';
                               // $htmls = ucfirst($r->name).' created request to withdraw amount of $'.$data['amount'].', Please visit the following link given below:';
                                $header = Config::get('constants.Applyamount_header');
                                $buttonhtml = Config::get('constants.Applyamount_btn_html');
                                $pass_url  = url('admin/request_detail/'.$idd); 
                                $path = url('resources/views/email.html');
                                $subject = Config::get('constants.Applyamount_subject');
                                $to_email=$adminemail[0];
                                $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                                 $arrays =[
                                'w_from' => 'user',
                                'from_id' => $r->id,
                                'w_to' => 'admin',
                                'to_id' => '1',
                                'title' => Config::get('constants.Applyamount_notification_title'),
                                'description' => implode(' ',$variavles),
                                'url' => 'admin/request_detail/'.$idd,
                                'tbl'=>'withdraw',
                                'status'=>'1'
                                ];
                                Notification::insertoption($arrays);
                            }
                           
                        $messags['message'] = "Your Request Send Successfully.";
                        $messags['status']= 1; 
           
         echo json_encode($messags);
                         die;
        
    }
    
    /* add values in drop down add the time of addquestion*/
      public function adddropdowns(Request $request)
    {
        $postdata = file_get_contents("php://input");
        $data=json_decode($postdata,true);
        $messags = array();
                if($data['type'] == 'country')
                {
                    $datacountry = country::getoptionmatch([['name','=',$data['name']],['status','!=','2'],['parent','=','0']]);

                    if(!empty($datacountry) && count($datacountry) > 0)
                    {
                        $messags['message'] = "Country already exists.";
                        $messags['status']= 0;  
                    }else
                    {
                       $id = country::insertoption2(array('name'=>$data['name'],'status'=>'1','parent'=>'0'));
                        $messags['message'] = "Country has been added successfully.";
                        $messags['status']= 1;
                        $messags['id']= $id;
                        $states = country::getoption();
                       
                        $messags['id']= $id;
                        $messags['data']= $states;
                        $messags['type']= 'country';
                         $messags['parents']= 'null';
                    }
                }else if($data['type'] == 'state')
                {
                  $datacountry = country::getoptionmatch([['name','=',$data['name']],['status','!=','2'],['parent','=',$data['country']]]);
                    if(!empty($datacountry) && count($datacountry) > 0)
                    {
                        $messags['message'] = "State already exists.";
                        $messags['status']= 0;  
                    }else
                    {
                        $id = country::insertoption2(array('name'=>$data['name'],'status'=>'1','parent'=>$data['country']));
                        $messags['message'] = "State has been added successfully.";
                        $messags['status']= 1;
                        $messags['id']= $id;
                        $gettags = [['parent', '=', $data['country']],['status', '=', '1']]; 
                        $states=country::getbycondition($gettags);
                        $messags['data']= $states;
                        $messags['type']= 'state';
                        $messags['parents']= $data['country'];
                    }  
                }
                else if($data['type'] == 'grade')
                {
                  $datacountry = Grades::getoptionmatch([['name','=',$data['name']],['status','!=','2']]);
                    if(!empty($datacountry) && count($datacountry) > 0)
                    {
                        $messags['message'] = "Grade already exists.";
                        $messags['status']= 0;  
                    }else
                    {
                        $id = Grades::insertoption2(array('name'=>$data['name'],'status'=>'1'));
                        $messags['message'] = "Grade has been added successfully.";
                        $messags['status']= 1;
                        $messags['id']= $id;
                        $were = array('status'=>'1');
                        $states=Grades::getbycondition($were);
                        $messags['data']= $states;
                        $messags['type']= 'grade';
                         $messags['parents']= 'null';
                    }  
                }
                else if($data['type'] == 'year')
                {
                  $datacountry = Years::getoptionmatch([['name','=',$data['name']],['status','!=','2']]);
                    if(!empty($datacountry) && count($datacountry) > 0)
                    {
                        $messags['message'] = "Year already exists.";
                        $messags['status']= 0;  
                    }else
                    {
                        $id = Years::insertoption2(array('name'=>$data['name'],'status'=>'1'));
                        $messags['message'] = "Year has been added successfully.";
                        $messags['status']= 1;
                        $messags['id']= $id;
                        $were = array('status'=>'1');
                        $states= Years::getbycondition($were);
                        $messags['data']= $states;
                        $messags['type']= 'year';
                         $messags['parents']= 'null';
                    }  
                }
                else if($data['type'] == 'course')
                {
                  $datacountry = course::getoptionmatch([['name','=',$data['name']],['parent','=','0'],['type','=','1'],['status','!=','2']]);
                    if(!empty($datacountry) && count($datacountry) > 0)
                    {
                        $messags['message'] = "Course already exists.";
                        $messags['status']= 0;  
                    }else
                    {
                       $id = course::insertoption2(array('name'=>$data['name'],'parent'=>'0','status'=>'1','type'=>'1'));
                        $messags['message'] = "Course has been added successfully.";
                        $messags['status']= 1;
                        $messags['id']= $id;
                        $states= course::getoption();
                        $messags['data']= $states;
                        $messags['type']= 'course';
                        $messags['parents']= 'null';
                    }  
                }
                else if($data['type'] == 'subject')
                {
                  $datacountry = course::getoptionmatch([['name','=',$data['name']],['parent','=',$data['course']],['type','=','2'],['status','!=','2']]);
                    if(!empty($datacountry) && count($datacountry) > 0)
                    {
                        $messags['message'] = "Subject already exists.";
                        $messags['status']= 0;  
                    }else
                    {
                       $id =  course::insertoption2(array('name'=>$data['name'],'parent'=>$data['course'],'status'=>'1','type'=>'2'));
                        $messags['message'] = "Subject has been added successfully.";
                        $messags['status']= 1;
                        $messags['id']= $id;
                        $gettags = [['parent', '=', $data['course']],['type', '=', '2'],['status', '=', '1']]; 
                        $states=course::getbycondition($gettags);
                        $messags['data']= $states;
                        $messags['type']= 'subject';
                        $messags['parents']= $data['course'];
                    }  
                }
                else if($data['type'] == 'chapter')
                {
                  $datacountry = course::getoptionmatch([['name','=',$data['name']],['parent','=',$data['subject1']],['type','=','3'],['status','!=','2']]);
                    if(!empty($datacountry) && count($datacountry) > 0)
                    {
                        $messags['message'] = "Chapter already exists.";
                        $messags['status']= 0;  
                    }else
                    {
                       $id =  course::insertoption2(array('name'=>$data['name'],'parent'=>$data['subject1'],'status'=>'1','type'=>'3'));
                        $messags['message'] = "Chapter has been added successfully.";
                        $messags['status']= 1;
                        $messags['id']= $id;
                         $gettags = [['parent', '=', $data['subject1']],['type', '=', '3'],['status', '=', '1']]; 
                        $states=course::getbycondition($gettags);
                        $messags['data']= $states;
                        $messags['type']= 'chapter';
                        $messags['parents']= $data['subject1'];
                    }  
                }
                
            
        echo json_encode($messags);
            die;
    }
    
    public function update_hours($user_id)
  {
       $postdata = file_get_contents("php://input");
        $data=json_decode($postdata,true);
        $update_question_count=array(
                                    'total_hours'=>$data['newtime'],
                                    );
        Hours::updateoption2($update_question_count,array('user_id'=>$user_id));
        $messags['message'] = 'done';
        $messags['status'] = '1';
        echo json_encode($messags);
            die;
  }
  
  
   public function hours_left($user_id)
    {
             $hours=Hours::getdetailsuser($user_id);
             if(!empty($hours))
             {
             if($hours->package_id == '1')
             {
                 $messags['data'] = $hours->total_hours;
                 $messags['message'] = 'notpaid';
                 $messags['status'] = '1';
        }
        else{
            $messags['message'] = 'paid';
            $messags['status'] = '0';
        }
        }else
        {
           $messags['message'] = 'paid';
            $messags['status'] = '0'; 
        }
        echo json_encode($messags);
            die;
        
    }


   public function readnotification(Request $request)
    {
        $postdata = file_get_contents("php://input");
        $data=json_decode($postdata,true);
         $messags = array();
                  Notification::updateoption(array('status'=>'0'),$data['id']);
                $messags['message'] = "Notification read Successfully.";
                 $messags['status']= 1;
        echo json_encode($messags);
            die;
    }
    
    public function notifocation_delete(Request $request)
    {
       $postdata = file_get_contents("php://input");
        $data=json_decode($postdata,true);
         $messags = array();
                  Notification::updateoption(array('status'=>'2'),$data['id']);
                $messags['message'] = "Notification has been deleted successfully.";
                 $messags['status']= 1;
        echo json_encode($messags);
            die; 
    }
	
   
    
  
    
}
