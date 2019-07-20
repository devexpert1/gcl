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
use App\Test;
use App\Hours;
use App\Withdraw;
use App\Subscription_content;
use Illuminate\Support\Facades\Validator;
use App\User_test;
use DB;
use Mail;
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
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;
use Stripe\Plan as Plans;

class UserController extends Controller
{
   /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Redirector $redirect)
    {   //include(app_path() . 'paypal-php-sdk/autoload.php');
         DB::statement("SET NAMES 'utf8'");
        header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
         $this->middleware(function ($request, $next){
             if(Session()->exists('user'))
            { 
           
              $userid =Session()->get('userid');
              $were= [['id','=',$userid],['status','=','1']];
              $exists= User::getUsermatch($were);
              if(count($exists) > 0)
              {  $exists2= User::getbycondition($were);
                  if($exists2[0]->refferal_code =='')
                  {  User::updateUser(array('refferal_code'=>time().uniqid(rand())),$userid);
                      
                  }
                $user_id = session('user_id');
                return $next($request);
              }else
              { 
                $this->middleware('auth');
                Auth::logout();
                Session::flush('user');
                session()->forget('user');
                session()->flush('user');
                return Redirect('/'); 
              }
            }
              return $next($request);
            });
                  

        //$this->middleware('auth');
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    { 
        
        if(Auth::user()){
            $data['options'] = App\Options::getoption();
            $data['testimonials'] = App\Testimonials::getoption();
            return view('index',$data);
        }else
        {
            $data['options'] = App\Options::getoption();
            $data['testimonials'] = App\Testimonials::getoption();
            return view('index',$data);   
        }
    }
    
    public function loginotp(Request $request)
    {  $data = $request->all();
    
    $dat1= array('email'=>$data['email']);
    $dat2= array('phone'=>$data['email']);
    $vendors = User::getmacthemailphone($data['otp'],$data['email']);
    if(!empty($vendors))
    {
        $userdata = array(
        'id'=> $vendors->id ,
        'name' => $vendors->name ,
        'lname' => $vendors->lname ,
        'email' => $vendors->email ,
        );
         $users =  User::updateUser(array('otp'=>rand()),$vendors->id);
         Session::put('user',$userdata);
        Session::put('userid', $vendors->id);
        Session::save();
        return  redirect(url('home'));
    }else
    {
        return Redirect::to('/login?otpgenrates=yes')->withInput($request->all())->with('error', "Your credentials doesn't match with our record.");
    }
      
    }

    public function stripe(Request $request){
      echo '';
      
    }
    
    public function update_user_info(Request $request)
    {
        $data = $request->all();
         $messags = array();
        if(isset($data['stripe_mode']))
        {
            $insert = array(
               'paypal_email'=> array_key_exists('paypal_email',$data) ? $data['paypal_email'] : '',
                );
                $insert = array_filter($insert);
                $insert = array_merge($insert,array('gateway_type'=>$data['stripe_mode']));
                $users =  User::updateUser($insert,Session()->get('userid'));
                $messags['message'] = "Payment gateway information has been updated successfully!!.";
                $messags['erro']= 101;
                $messags['url']= ''; 
        }else{
                $messags['message'] = "Error to upate payment gateway information.";
                $messags['erro']= 101;
                $messags['url']= ''; 
        }
      echo  json_encode($messags); die; 
    }
    

   
public function postPaymentWithStripe(Request $request)
  {
     $data = $request->all();
     
    
            if(!empty($data['email'])){
            
               
                        if(isset($data['refercode']) && !empty($data['refercode']))
         { 
             $ivs= $data['refercode'];
              unset($data['refercode']);
               $packages = Subscription_content::getbycondition(array('id'=>$data['package_id']));
               $amounts=$packages[0]->referrel_amount;
              
         }
         if(isset($data['usertoken']) && !empty($data['usertoken']) )
         {
             $iv= $data['usertoken'];
             $amounts = $data['referrel_amount'];
             unset($data['usertoken']);
             unset($data['referrel_amount']);
             
         }
         
         if(isset($data['usertoken2']) && !empty($data['usertoken2']))
         {
             $ivs= $data['usertoken2'];
             $amounts = $data['referrel_amount'];
             unset($data['referrel_amount']);
             unset($data['usertoken2']); 
         }
     
                        $datas=array(
                        'name'=>$data['name'],
                        'lname'=>$data['lname'],
                        'email'=>$data['email'],
                        'phone'=>$data['phone'],
                        'country'=>$data['country'],
                        'package_id'=>$data['package_id'],
                        'dob'=> $data['dob'] ? date('Y-m-d H:i:s',strtotime($data['dob'])): '',
                        'password'=>Hash::make($data['password']),
                        'status'=>'1',
                        'refferal_code'=>time().uniqid(rand()),
                        'company_name'=>$data['company_name'],
                        );  
                        if(isset($datas['dob']) && empty($datas['dob']))
                        {
                        unset($datas['dob']);
                        }
                        $email = [['email','=',$datas['email']],['status','!=','2']];
                        $exists = User::getUsermatch($email);
                        if(count($exists) > 0 )
                        {
                        $messags['message'] = "Email already exist.";
                        $messags['erro']= 202;
                        $messags['url']= ''; 
                        }

                        if(User::insertUser($datas))
                        {
                           
                        if(!empty($data['stripeToken']))
                        {
                        Stripe::setApiKey(env('STRIPE_SECRET'));
                        $token  =$data['stripeToken'];
                        $email  =$data['stripeEmail'];
                        
                        if($data['product_interval'] =='1')
                        {
                        $int='month';
                        }elseif($data['product_interval'] =='12')
                        {
                        $int='year'; 
                        }
                        
                        $plan = Plans::create(array( "product" => [ "name" => $data['product_name'] ],
                        "nickname" => $data['product_name'] ,
                        "interval" =>$int,
                        "interval_count" => 1, 
                        "currency" => "usd", 
                        "amount" => $data['product_amount'], ));
                        
                        $customer = Customer::create(array(
                        'email' => $email,
                        'source'  => $token
                        ));
                        
                        $subscription = \Stripe\Subscription::create(array(
                        "customer" => $customer->id,
                        "items" => array(
                        array(
                        "plan" => $plan->id,
                        ),
                        ),
                        ));
                        }
                        
                            $userdatas = User::getbycondition(array('email'=>$datas['email']));
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
                            );
                            $date = '';
                            if($data['package_id'] == '1'){
                            $start_date =date('Y-m-d');  
                            $date = strtotime($start_date);
                            $date = date('Y-m-d',strtotime("+7 day", $date));  
                            }
                            if($data['package_id'] == '3')
                            {
                                $start_date =date('Y-m-d');  
                            $date = strtotime($start_date);
                            $date = date('Y-m-d',strtotime("+1 year", $date));   
                            }
                            if($data['package_id'] == '2')
                            {
                                $start_date =date('Y-m-d');  
                                $date = strtotime($start_date);
                                $date = date('Y-m-d',strtotime("+1 month", $date));  
                            }
                            $transaction_data=array(
                            'transaction_id'=>$subscription->id,
                            'user_id'=>$users->id,
                             'package_id'=>$data['package_id'],
                             'status'=>'completed',
                             'currency'=>'usd',
                            'amount'=>$data['amount'],
                            'exp'=>$date,
                            'recurring'=>'1'
                
                             );
        
                           Transaction::insertUser($transaction_data);
                           if($data['package_id'] == '1'){
                            $start_date =date('Y-m-d');  
                            $date = strtotime($start_date);
                            $date = date('Y-m-d',strtotime("+7 day", $date));  
                             
                               
                             $hours_data=array(
                            'user_id'=>$users->id,
                            'package_id'=>$data['package_id'],
                            'total_questions_uploaded'=>'0',
                            'total_hours'=>'00:10:00',
                            'expiry'=>$date,
                            'current_question_count'=>0,
                
                             );
                             }
                             elseif($data['package_id'] == '3'){
                            $start_date =date('Y-m-d');  
                            $date = strtotime($start_date);
                            $date = date('Y-m-d',strtotime("+1 year", $date));  
                             
                               
                             $hours_data=array(
                            'user_id'=>$users->id,
                            'package_id'=>$data['package_id'],
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
                                'package_id'=>$data['package_id'],
                                'total_questions_uploaded'=>'0',
                                'total_hours'=>'00:00:00',
                                'expiry'=>$date,
                                'current_question_count'=>0,
                    
                                 );
                                 }
                                       
                                         
                                        if(!empty($iv) && !empty($data['email']))
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
                                            'friend_email'=>$data['email']
                                            ); 
                                            $getdatas = Reffer::getbycondition($were34);
                                            
                                            if(empty($getdatas) && count($getdatas) < 1)
                                            {   $getdatas = Reffer::getbycondition($were34);
                                                Reffer::updateoption2($datas,$were3);
                                            }else
                                            {
                                               Reffer::updateoption2($datas,$were34);  
                                            }
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
                                        
                                        if(!empty($ivs) && !empty($data['email']))
                                        {     
                                             $were34 =  array(
                                            'friend_email'=>$data['email']
                                            );  
                                            $getdatas = Reffer::getbycondition($were34);
                                            if(count($getdatas) < 1)
                                            { 
                                            $getdatas = User::getbycondition(array('refferal_code'=>$ivs));
                                                $datas = array(
                                                'uid'=>$getdatas[0]->id,
                                                'friend_email'=>$data['email'],
                                                'status'=>'1',
                                                'friend_id'=>$users->id,
                                                'amount' => $amounts,
                                                'token'=> rand()
                                                );
                                                Reffer::insertoption($datas);
                                            }else
                                            { 
                                                $getdatas = Reffer::getbycondition(array('refferal_code'=>$ivs));
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
                                            
                                            Session::put('user',$userdata);
                                            Session::put('userid', $users->id);
                                            Session::save(); 
                                            } 
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['erro']= 101;
                                           // return redirect(url('/home'));  die;
                                          // return Redirect::intended('/');
                                           //$request = Request::create('/home', 'get'); 
                                           //return Route::dispatch($request)->getContent();
                                           echo '<script type="text/javascript"> window.location = "'.url('/home').'" </script>';

                                        }
           
          
           
            }
            
            return redirect(url('/home')); 

  }  
    
    
    
    
   /* public function stripe_update_plan(Request $request){
         $data = $request->all();
         if(session()->exists('user'))
        {
            //cancel active subscription for stripe 
            $transactions = Transaction::getbycondition(array('user_id'=>$userid));
            $show = '0';
            
            foreach($transactions as $tr)
            { 
            if($tr->recurring=='1')
            { 
            if (strpos($tr->transaction_id, 'I-') !== false) {
            $show = '1';
            $id = $tr->transaction_id;
            }
            elseif (strpos($tr->transaction_id, 'sub') !== false) {
            $show = '2';
            $id = $tr->transaction_id;
            }
            }        
            }
            if($show='2')
            {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $sub =  \Stripe\Subscription::retrieve($id);
            $sub->cancel();
            Transaction::updateoption2(array('recurring'=>'0'),array('transaction_id'=>$id,'user_id'=>$user_id));
            }
        //end of cancel stripe  
        //create new subscription with stripe
         if(!empty($data['stripeToken']))
                        {
                        Stripe::setApiKey(env('STRIPE_SECRET'));
                        $token  =$data['stripeToken'];
                        $email  =$data['stripeEmail'];
                        $subscription_d = Subscription_content::where('id',$data['package_id'])->first();
                       
                        if($subscription_d->month =='1')
                        {
                        $int='month';
                        }elseif($subscription_d->month =='12')
                        {
                        $int='year'; 
                        }
                        
                        $plan = Plans::create(array( "product" => [ "name" => $subscription_d->title ],
                        "nickname" => $subscription_d->title ,
                        "interval" =>$int,
                        "interval_count" => 1, 
                        "currency" => "usd", 
                        "amount" => $data['amount'], ));
                        
                        $customer = Customer::create(array(
                        'email' => $email,
                        'source'  => $token
                        ));
                        
                        $subscription = \Stripe\Subscription::create(array(
                        "customer" => $customer->id,
                        "items" => array(
                        array(
                        "plan" => $plan->id,
                        ),
                        ),
                        ));
                        
                        //insert new transaction record into database 
                        $start_date =date('Y-m-d');  
                        $date = strtotime($start_date);
                        $lastpakcgae = Hours::getbycondition([['user_id','=',$user_id],['package_id','!=','1']]);
                        $lastpakcgae2 = Hours::getbycondition([['user_id','=',$user_id],['package_id','=','1']]);
                        if($data['package_id'] == '1'){
                        $date1 = date('Y-m-d',strtotime("+7 day", $date)); 
                        $update_question_count=array(
                        'total_hours'=>'00:10:00',
                        'package_id'=>$data['package_id'],
                        'expiry'=>$date1,
                        'current_question_count'=>'0',
                        ); 
                        Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                        $transaction_data=array(
                                'transaction_id'=>'0',
                                'user_id'=>$user_id,
                                'package_id'=>$data['package_id'],
                                'status'=>'completed',
                                'currency'=>"",
                                'amount'=>'0',
                                'walletuse'=>'',
                                'exp'=>$date1
                                );
                        Transaction::insertUser($transaction_data);
                        }
                        //end package 1
                        else if($data['package_id'] == '3')
                        {       
                        if(count($lastpakcgae) > 0 )
                        {  
                        //$start_date =date('Y-m-d',strtotime($lastpakcgae[0]->expiry));  
                        $date = strtotime($lastpakcgae[0]->expiry);
                        $date = date('Y-m-d',strtotime("+1 year", $date));  
                        $hours_data=array(
                        'package_id'=>$data['package_id'],
                        'total_hours'=>'00:00:00',
                        'expiry'=>$date,
                        'current_question_count'=>0,
                        );
                        Hours::updateoption2($hours_data,array('user_id'=>Session()->get('userid')));
                        $transaction_data=array(
                                    'transaction_id'=>$subscription->id,
                                    'user_id'=>$user_id,
                                    'package_id'=>$data['package_id'],
                                    'status'=>"completed",
                                    'currency'=>"usd",
                                    'amount'=>$data['amount'],
                                    'walletuse'=>$data['walletuse'],
                                    'exp'=>$date
                                    );
                        Transaction::insertUser($transaction_data);
           }else
           {
               
               if(count($lastpakcgae2) > 0)
               {
                   $start_date =date('Y-m-d');  
                   $start_date = date('Y-m-d',strtotime("+7 day",$start_date));  
                    $date = strtotime($start_date);
                    $date = date('Y-m-d',strtotime("+1 year", $date));  
                     $hours_data=array(
                    'package_id'=>$data['package_id'],
                    'total_hours'=>'00:00:00',
                    'expiry'=>$date,
                    'current_question_count'=>0,
                     );
              Hours::updateoption2($hours_data,array('user_id'=>Session()->get('userid')));
               $transaction_data=array(
                                    'transaction_id'=>$subscription->id,
                                    'user_id'=>$user_id,
                                    'package_id'=>$data['package_id'],
                                   'status'=>"completed",
                                    'currency'=>"usd",
                                    'amount'=>$data['amount'],
                                    'walletuse'=>$data['walletuse'],
                                    'exp'=>$date
                                    );
               Transaction::insertUser($transaction_data);
                   
               }else
               {
                    $start_date = strtotime(date('Y-m-d'));  
                    $date = strtotime($start_date);
                    $date = date('Y-m-d',strtotime("+1 year", $date));  
                     $hours_data=array(
                    'package_id'=>$data['package_id'],
                    'total_hours'=>'00:00:00',
                    'expiry'=>$date,
                    'current_question_count'=>0,
                     );
              Hours::updateoption2($hours_data,array('user_id'=>Session()->get('userid')));
               $transaction_data=array(
                                    'transaction_id'=>$subscription->id,
                                    'user_id'=>$user_id,
                                    'package_id'=>$data['package_id'],
                                   'status'=>"completed",
                                    'currency'=>"usd",
                                    'amount'=>$data['amount'],
                                    'walletuse'=>$data['walletuse'],
                                    'exp'=>$date
                                    );
               Transaction::insertUser($transaction_data);
               }
           }
        }else
        {
            
                 
           if(count($lastpakcgae) > 0 )
           {      $date = strtotime($lastpakcgae[0]->expiry);
               //$start_dates =date('Y-m-d',strtotime($lastpakcgae[0]->expiry));  
               $date1 = date('Y-m-d',strtotime("+1 month", $date)); 
                $update_question_count=array(
                                            'total_hours'=>'00:00:00',
                                            'package_id'=>$data['package_id'],
                                            'expiry'=>$date1,
                                            'current_question_count'=>'0',
                                            );
                Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                
                 $transaction_data=array(
                                        'transaction_id'=>$subscription->id,
                                        'user_id'=>$user_id,
                                        'package_id'=>$data['package_id'],
                                        'status'=>"completed",
                                        'currency'=>"usd",
                                        'amount'=>$data['amount'],
                                        'walletuse'=>$data['walletuse'],
                                        'exp'=>$date1
                                        );
                Transaction::insertUser($transaction_data);
           }else
           {
               
               if(count($lastpakcgae2) > 0)
               {
                   $start_date =strtotime(date('Y-m-d'));
                   $start_date = date('Y-m-d',strtotime("+7 day",$start_date));  
                    $date = strtotime($start_date);
                    //$date = date('Y-m-d',strtotime("+1 year", $date));
                    //$date = strtotime($lastpakcgae[0]->expiry);
               //$start_dates =date('Y-m-d',strtotime($lastpakcgae[0]->expiry));  
               $date1 = date('Y-m-d',strtotime("+1 month", $date)); 
                $update_question_count=array(
                                            'total_hours'=>'00:00:00',
                                            'package_id'=>$data['package_id'],
                                            'expiry'=>$date1,
                                            'current_question_count'=>'0',
                                            );
                Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                
                 $transaction_data=array(
                                        'transaction_id'=>$subscription->id,
                                        'user_id'=>$user_id,
                                        'package_id'=>$data['package_id'],
                                        'status'=>"completed",
                                        'currency'=>"usd",
                                        'amount'=>$data['amount'],
                                        'walletuse'=>$data['walletuse'],
                                        'exp'=>$date1
                                        );
                Transaction::insertUser($transaction_data);
               }else
               {
                $date1 = date('Y-m-d',strtotime("+1 month", $date)); 
                $update_question_count=array(
                                            'total_hours'=>'00:00:00',
                                            'package_id'=>$data['package_id'],
                                            'expiry'=>$date1,
                                            'current_question_count'=>'0',
                                            );
                Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                
                 $transaction_data=array(
                                        'transaction_id'=>$subscription->id,
                                        'user_id'=>$user_id,
                                         'package_id'=>$data['package_id'],
                                        'status'=>"completed",
                                        'currency'=>"usd",
                                        'amount'=>$data['amount'],
                                        'walletuse'=>$data['walletuse'],
                                        'exp'=>$date1
                                        );
                Transaction::insertUser($transaction_data);
               }
           }
        }
                            
                            
                            
                            
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        }
        
        
            
        }
        else{
            
         
        } 
        
        
    }*/
    
    public function simplePay(Request $request)
        {
         

          //  $paypal_conf = \Config::get('paypal');
            $this->_api_context = new \PayPal\Rest\ApiContext(new \PayPal\Auth\OAuthTokenCredential('AUy8QI7ABm9Mxtx7emYitH49UgKsRh2hEcDCsLOqY--cvXck9Wqqf_0zurTmgQzAoEhZNx28EM6E01hD', 'EIynqMz02rCAnum2ZRXrp-F12yNmZ3sP-L5MAZysP0raTx6C6waz5m7KRlJfmxeqekb4IzohWDL5i3rG'));
            //$this->_api_context->setConfig($paypal_conf['settings']);
            $payouts = new \PayPal\Api\Payout();
            $senderBatchHeader = new \PayPal\Api\PayoutSenderBatchHeader();
            $senderBatchHeader->setSenderBatchId(uniqid())->setEmailSubject("You have a Payout!");
            $senderItem = new \PayPal\Api\PayoutItem();
            $senderItem->setRecipientType('Email')
            ->setNote('Thanks for your patronage!')
            ->setReceiver('priya_priya@gmail.com')
            ->setSenderItemId("001")
            ->setAmount(new \PayPal\Api\Currency('{
                                    "value":"0.01",
                                    "currency":"AUD"
                                }'));
            $payouts->setSenderBatchHeader($senderBatchHeader)->addItem($senderItem);
            $request = clone $payouts;
            try {
                //$output = $payouts->createSynchronous($this->_api_context);
                $output = $payouts->create(array('sync_mode' => 'false'),$this->_api_context);
              //  $output = $payouts->createAsynchronous($this->_api_context);
            } catch (\Exception $ex) {
                echo '111111111111<pre>';  
                print_r($request); 
                echo $ex->getCode();
                echo $ex->getData();
               // print_r($ex);
                //  \ResultPrinter::printError("Created Single Synchronous Payout", "Payout", null, $request, $ex);
                exit(1);
            }    echo '22222222222<pre>'; 
            // \ResultPrinter::printResult("Created Single Synchronous Payout", "Payout", $output->getBatchHeader()->getPayoutBatchId(), $request, $output);
            echo $output->getBatchHeader()->getPayoutBatchId();
            echo '<pre>'; print_r($output); die; 
        }
    
    public function payplapayout(Request $request)
    {
    
    /*$datam = '{
  "sender_batch_header": {
    "sender_batch_id": "Payouts_2018_100007",
    "email_subject": "You have a payout!",
    "email_message": "You have received a payout! Thanks for using our service!"
  },
  "items": [
    {
      "recipient_type": "EMAIL",
      "amount": {
        "value": "9.87",
        "currency": "USD"
      },
      "note": "Thanks for your patronage!",
      "sender_item_id": "201403140001",
      "receiver": "29userdemo-facilitator@gmail.com"
    }
  ],
}';    
    
    
    
    $ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.sandbox.paypal.com/v1/oauth2/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_USERPWD, 'AXrKYDjkulUK0qLT25rjOMBIfm6SYKQcevQzhiHNjbaNGJ8FKl0dnJKT14x4QYpMVCiLy7h5uATCGb_A:ELYnb58QriRgcYSWClxSZG0ZPLXfRVgITo2JilABVNlSbikxwu0GmFGYIact-vrhK2htjT2A1g2OA9tN');
$headers = array(); 
$headers[] = 'Accept: application/json';
$headers[] = 'Accept-Language: en_US';
$headers[] = 'Content-Type: application/x-www-form-urlencoded';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$result = curl_exec($ch);
$data = json_decode($result,true);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);

    $s = curl_init("https://api.sandbox.paypal.com/v1/payments/payouts");
    
    curl_setopt($s, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/payments/payouts");
    curl_setopt($s, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $data['access_token']));
    curl_setopt($s,CURLOPT_POST,true);
    curl_setopt($s, CURLOPT_POSTFIELDS, $datam);
    
    $result = curl_exec($s);
    curl_close($s);
        
        
      echo '<pre>'; print_r($result); die;  */
        
        
        
        
        
        
        
        
        
        
        
        
        $apiContext = new \PayPal\Rest\ApiContext(
        new \PayPal\Auth\OAuthTokenCredential('AXrKYDjkulUK0qLT25rjOMBIfm6SYKQcevQzhiHNjbaNGJ8FKl0dnJKT14x4QYpMVCiLy7h5uATCGb_A','ELYnb58QriRgcYSWClxSZG0ZPLXfRVgITo2JilABVNlSbikxwu0GmFGYIact-vrhK2htjT2A1g2OA9tN'));
        $payouts = new \PayPal\Api\Payout();
        $senderBatchHeader = new \PayPal\Api\PayoutSenderBatchHeader();
        $senderBatchHeader->setSenderBatchId(uniqid())
         ->setEmailSubject("You have a Payout!");
        $senderItem = new \PayPal\Api\PayoutItem();
        $senderItem->setRecipientType('Email')
        ->setNote('Thanks for your patronage!')
        ->setReceiver('priya_priya@gmail.com')
        ->setSenderItemId("2014031400023")
        ->setAmount(new \PayPal\Api\Currency('{
        "value":"1.0",
        "currency":"USD"
        }'));
        $payouts->setSenderBatchHeader($senderBatchHeader)
        ->addItem($senderItem);
        // For Sample Purposes Only.
        $request = clone $payouts;
        try {
           // $output = $payouts->createSynchronous($apiContext);
            $output = $payouts->create(array('sync_mode' => 'false'), $apiContext);

        } catch (Exception $ex) {
            echo '<pre>'; print_r($request); print_r($ex);
        // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
           // ResultPrinter::printError("Created Single Synchronous Payout", "Payout", null, $request, $ex);
            exit(1);
        }
       echo $output->getBatchHeader()->getPayoutBatchId(); print_r($request); print_r($output);
        // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
        //ResultPrinter::printResult("Created Single Synchronous Payout", "Payout", $output->getBatchHeader()->getPayoutBatchId(), $request, $output);
        //return $output;

        echo '<pre>'; print_r($output); echo '</pre>'; die;
    }
    
    public function paypal_rec(Request $request){
      $data = $request->all();
      if(session()->exists('user'))
        {
        
           
            //cancel active subscription for stripe 
            $this->middleware('auth');
            $users = Auth::user();
            $user_id=Session()->get('userid');
            $userid=Session()->get('userid');
            $transactions = Transaction::getbycondition(array('user_id'=>$userid));
            $show = '0';
            
            foreach($transactions as $tr)
            { 
            if($tr->recurring=='1')
            { 
            if (strpos($tr->transaction_id, 'I-') !== false) {
            $show = '1';
            $id = $tr->transaction_id;
            }
            elseif (strpos($tr->transaction_id, 'sub') !== false) {
            $show = '2';
            $id = $tr->transaction_id;
            }
            }        
            }
            if($show=='2')
            {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $sub =  \Stripe\Subscription::retrieve($id);
            $sub->cancel();
            Transaction::updateoption2(array('recurring'=>'0'),array('transaction_id'=>$id,'user_id'=>$user_id));
            }
        //end of cancel stripe                                     
       
        }
           $client_id='';
           $client_secrate='';
            if(Options::getoptionmatch3('paypal_mode')=='0')
            {    
             $client_id = Options::getoptionmatch3('paypal_client_id_sandbox');
             $client_secrate = Options::getoptionmatch3('paypal_client_secrate_sandbox');
            }else
            {
                $client_id = Options::getoptionmatch3('paypal_client_id_live');
                 $client_secrate = Options::getoptionmatch3('paypal_client_secrate_live');
            }
      $amount = $data['amount'];
      $currency = $data['currency'];
      $temp_data = $request->all();
      if((isset($temp_data['package']) && $temp_data['package'] == '2') || (isset($temp_data['package_id']) && $temp_data['package_id'] == '2'))
      {
          $duration = 'Month';
      }else
      {
          $duration = 'Year';
      }
      $is_signup = $temp_data['is_signup'];
      $apiContext = new \PayPal\Rest\ApiContext(
        new \PayPal\Auth\OAuthTokenCredential($client_id,$client_secrate));
      // Create a new billing plan
      $plan = new Plan();
      $plan->setName('Multiple choice Subscription.')
        ->setDescription('Recurring Package')
        ->setType('infinite');

      // Set billing plan definitions
      $paymentDefinition = new PaymentDefinition();
     
      $paymentDefinition->setName('Regular Payments')
        ->setType('REGULAR')
        ->setFrequency($duration)
        ->setFrequencyInterval('1')
        ->setCycles(0)
        ->setAmount(new Currency(array('value' => $amount, 'currency' => $currency)));

      // Set merchant preferences
      $merchantPreferences = new MerchantPreferences();
      $merchantPreferences->setReturnUrl(url('user/paypal_response?success=true'))
        ->setCancelUrl(url('user/paypal_response'))
        ->setAutoBillAmount('yes')
        ->setInitialFailAmountAction('CONTINUE')
        ->setMaxFailAttempts('0');
        // ->setSetupFee(new Currency(array('value' => 1, 'currency' => 'USD')));

      $plan->setPaymentDefinitions(array($paymentDefinition));
      $plan->setMerchantPreferences($merchantPreferences);

      //create plan
      try {
        $createdPlan = $plan->create($apiContext);

        try {
          $patch = new Patch();
          $value = new PayPalModel('{"state":"ACTIVE"}');
          $patch->setOp('replace')
            ->setPath('/')
            ->setValue($value);
          $patchRequest = new PatchRequest();
          $patchRequest->addPatch($patch);
          $createdPlan->update($patchRequest, $apiContext);
          $plan = Plan::get($createdPlan->getId(), $apiContext);

          // Output plan id
          $plan_id = $plan->getId();
          $this->paypal_agreement($plan_id,$temp_data,$is_signup);
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
          echo $ex->getCode();
          echo $ex->getData();
          die($ex);
        } catch (Exception $ex) {
          die($ex);
        }
      } catch (PayPal\Exception\PayPalConnectionException $ex) {
        echo $ex->getCode();
        echo $ex->getData();
        die($ex);
      } catch (Exception $ex) {
        die($ex);
      }
    }
    
    
    public function paypal_agreement($plan_id,$temp_data,$is_signup){
        $client_id=''; $client_secrate='';
        if(Options::getoptionmatch3('paypal_mode')=='0')
            {    
             $client_id = Options::getoptionmatch3('paypal_client_id_sandbox');
             $client_secrate = Options::getoptionmatch3('paypal_client_secrate_sandbox');
            }else
            {
                $client_id = Options::getoptionmatch3('paypal_client_id_live');
                 $client_secrate = Options::getoptionmatch3('paypal_client_secrate_live');
            }
    $apiContext = new \PayPal\Rest\ApiContext(
      new \PayPal\Auth\OAuthTokenCredential($client_id,$client_secrate));
    // Create new agreement
    $agreement = new Agreement();
    $agreement->setName('Multiple Choice Agreement')
      ->setDescription('Recurring Payment Agreement')
      ->setStartDate(date('Y-m-d').'T23:59:59Z');

    // Set plan id
    $plan = new Plan();
    $plan->setId($plan_id);
    $agreement->setPlan($plan);

    // Add payer type
    $payer = new Payer();
    $payer->setPaymentMethod('paypal');
    $agreement->setPayer($payer);

    try {
    // set user temp data into session
      ////$_SESSION['user_temp_data'] = $temp_data;
      //$_SESSION['is_signup_value'] = $is_signup;
      Session::put('user_temp_data',$temp_data);
     Session::put('is_signup_value', $is_signup);
     Session::save(); 
    // Create agreement
      $agreement = $agreement->create($apiContext);
      // Extract approval URL to redirect user
      echo $approvalUrl = $agreement->getApprovalLink();
    } catch (PayPal\Exception\PayPalConnectionException $ex) {
      echo $ex->getCode();
      echo $ex->getData();
      die($ex);
    } catch (Exception $ex) {
      die($ex);
    }
  }

  public function paypal_response(){
      $client_id=''; $client_secrate='';
        if(Options::getoptionmatch3('paypal_mode')=='0')
            {    
             $client_id = Options::getoptionmatch3('paypal_client_id_sandbox');
             $client_secrate = Options::getoptionmatch3('paypal_client_secrate_sandbox');
            }else
            {
                $client_id = Options::getoptionmatch3('paypal_client_id_live');
                 $client_secrate = Options::getoptionmatch3('paypal_client_secrate_live');
            }
    if (isset($_GET['success']) && $_GET['success'] == 'true') {
      $apiContext = new \PayPal\Rest\ApiContext(
      new \PayPal\Auth\OAuthTokenCredential($client_id,$client_secrate));
      $token = $_GET['token'];
      $agreement = new \PayPal\Api\Agreement();

      try {
        // Execute agreement
        $agreement->execute($token, $apiContext);
        //echo "success";
        //echo $agreement->getId();
        $data = Session()->get('user_temp_data');
         if(Session()->get('is_signup_value')!='' && Session()->get('is_signup_value')!=NULL && Session()->get('is_signup_value') == '1'){
             $this->recurring_register($agreement->getId());
          }
          else{
         $this->renew_subscription_submit($agreement->getId(),'1');
           }
      } catch (PayPal\Exception\PayPalConnectionException $ex) {
        echo $ex->getCode();
        echo $ex->getData();
        die($ex);
      } catch (Exception $ex) {
        die($ex);
      }
    } else {
        return redirect(url('/login/'))->with('error', 'User has been canceled agreement'); 

    }
  }
  
  public function recurring_register($agreementid= null)
  {
       $data = Session()->get('user_temp_data');
        $data['transaction_id'] = $agreementid;
        unset($data['is_signup']);
         $messags = array();
     if(!empty($data['email'])){
         
         if(isset($data['refercode']) && !empty($data['refercode']))
         { 
             $ivs= $data['refercode'];
              unset($data['refercode']);
               $packages = Subscription_content::getbycondition(array('id'=>$data['package_id']));
               $amounts=$packages[0]->referrel_amount;
              
         }
         if(isset($data['usertoken']) && !empty($data['usertoken']) )
         {
             $iv= $data['usertoken'];
             $amounts = $data['referrel_amount'];
             unset($data['usertoken']);
             unset($data['referrel_amount']);
             
         }
         
         if(isset($data['usertoken2']) && !empty($data['usertoken2']))
         {
             $ivs= $data['usertoken2'];
             $amounts = $data['referrel_amount'];
             unset($data['referrel_amount']);
             unset($data['usertoken2']); 
         }
        
       $datas=array(
           'name'=>$data['name'],
           'lname'=>$data['lname'],
           'email'=>$data['email'],
           'phone'=>$data['phone'],
           'country'=>$data['country'],
           'package_id'=>$data['package_id'],
           'dob'=> $data['dob'] ? date('Y-m-d H:i:s',strtotime($data['dob'])): '',
           'password'=>Hash::make($data['password']),
           'status'=>'1',
           'refferal_code'=>time().uniqid(rand()),
           'company_name'=>$data['company_name'],
           );  
           if(isset($datas['dob']) && empty($datas['dob']))
           {
               unset($datas['dob']);
           }
         $email = [['email','=',$datas['email']],['status','!=','2']];
        $exists = User::getUsermatch($email);
        if(count($exists) > 0 )
        {
            $messags['message'] = "Email already exist.";
            $messags['erro']= 202;
            $messags['url']= ''; 
        }
       
            if(User::insertUser($datas))
                                        {
                                            $userdatas = User::getbycondition(array('email'=>$datas['email']));
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
                                            );
                                            $date = '';
                                            if($data['package_id'] == '1'){
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+7 day", $date));  
                                            }
                                            if($data['package_id'] == '3')
                                            {
                                                $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+1 year", $date));   
                                            }
                                            if($data['package_id'] == '2')
                                            {
                                                $start_date =date('Y-m-d');  
                                                $date = strtotime($start_date);
                                                $date = date('Y-m-d',strtotime("+1 month", $date));  
                                            }
                                            $transaction_data=array(
                                            'transaction_id'=>$data['transaction_id'],
                                            'user_id'=>$users->id,
                                             'package_id'=>$data['package_id'],
                                             'status'=>$data['status'],
                                             'currency'=>$data['currency'],
                                            'amount'=>$data['amount'],
                                            'exp'=>$date,
                                            'recurring'=>'1'
                                
                                             );
           
                                           Transaction::insertUser($transaction_data);
                                           if($data['package_id'] == '1'){
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+7 day", $date));  
                                             
                                               
                                             $hours_data=array(
                                            'user_id'=>$users->id,
                                            'package_id'=>$data['package_id'],
                                            'total_questions_uploaded'=>'0',
                                            'total_hours'=>'00:10:00',
                                            'expiry'=>$date,
                                            'current_question_count'=>0,
                                
                                             );
                                             }elseif($data['package_id'] == '3'){
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+1 year", $date));  
                                             
                                               
                                             $hours_data=array(
                                            'user_id'=>$users->id,
                                            'package_id'=>$data['package_id'],
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
                                            'package_id'=>$data['package_id'],
                                            'total_questions_uploaded'=>'0',
                                            'total_hours'=>'00:00:00',
                                            'expiry'=>$date,
                                            'current_question_count'=>0,
                                
                                             );
                                             }
                                       
                                        if(!empty($iv) && !empty($data['email']))
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
                                            'friend_email'=>$data['email']
                                            ); 
                                            $getdatas = Reffer::getbycondition($were34);
                                            
                                            if(empty($getdatas) && count($getdatas) < 1)
                                            {   $getdatas = Reffer::getbycondition($were34);
                                                Reffer::updateoption2($datas,$were3);
                                            }else
                                            {
                                               Reffer::updateoption2($datas,$were34);  
                                            }
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
                                        
                                        if(!empty($ivs) && !empty($data['email']))
                                        {  
                                             $were34 =  array(
                                            'friend_email'=>$data['email']
                                            );  
                                            $getdatas = Reffer::getbycondition($were34);
                                            if(count($getdatas) < 1)
                                            { 
                                            $getdatas = User::getbycondition(array('refferal_code'=>$ivs));
                                                $datas = array(
                                                'uid'=>$getdatas[0]->id,
                                                'friend_email'=>$data['email'],
                                                'status'=>'1',
                                                'friend_id'=>$users->id,
                                                'amount' => $amounts,
                                                'token'=> rand()
                                                );
                                                Reffer::insertoption($datas);
                                            }else
                                            { 
                                                $getdatas = Reffer::getbycondition(array('refferal_code'=>$ivs));
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
                                            
                                            Session::put('user',$userdata);
                                            Session::put('userid', $users->id);
                                            Session::save(); 
                                            } 
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['erro']= 101;
                                           // return redirect(url('/home'));  die;
                                          // return Redirect::intended('/');
                                           //$request = Request::create('/home', 'get'); 
                                           //return Route::dispatch($request)->getContent();
                                           echo '<script type="text/javascript"> window.location = "'.url('/home').'" </script>';

                                        }
           
          
           
           
     }
     return redirect(url('/home')); 
  }
  public function cancelrecurring_stripe($id)
  {  
        $user_id=Session()->get('userid');
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $sub =  \Stripe\Subscription::retrieve($id);
        $sub->cancel();
        Transaction::updateoption2(array('recurring'=>'0'),array('transaction_id'=>$id,'user_id'=>$user_id));
         echo '1'; die; 
             
  }
  public function cancelrecurring($id)
  {
      if(session()->exists('user'))
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
            $user_id=Session()->get('userid');
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
               Transaction::updateoption2(array('recurring'=>'0'),array('transaction_id'=>$id,'user_id'=>$user_id));
             echo '1'; die;  
           }else
           {
               echo '2'; die;
           }
        }
  }
  
  public function renew_subscription_submit($transaction_id = null,$is_recurring = null){
      $data = Session()->get('user_temp_data');
        $data['transaction_id'] = $transaction_id;
        $user_id=Session()->get('userid');
       if(session()->exists('user'))
        {
            $transactionses = Transaction::getbycondition(array('user_id'=>$user_id));
        foreach($transactionses as $tr)
        { 
          if($tr->recurring=='1')
          { 
            if (strpos($tr->transaction_id, 'I-') !== false) {
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
                    
                                     $ides = $tr->transaction_id;
                                     $curl = curl_init();
                    $user_id=Session()->get('userid');
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
                       'PROFILEID' => $ides,         //here add your profile id                      
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
                    if($nvp['ACK']=='Success')
                   {    
                     Transaction::updateoption2(array('recurring'=>'0'),array('transaction_id'=>$ides,'user_id'=>$user_id));
                   }
                }
          }        
        }
        $user_id=Session()->get('userid');
        $start_date =date('Y-m-d');  
        $date = strtotime($start_date);
        $lastpakcgae = Hours::getbycondition([['user_id','=',$user_id],['package_id','!=','1']]);
        $lastpakcgae2 = Hours::getbycondition([['user_id','=',$user_id],['package_id','=','1']]);
         if($data['package'] == '3')
        {       
           if(count($lastpakcgae) > 0 )
           {  
               //$start_date =date('Y-m-d',strtotime($lastpakcgae[0]->expiry));  
                    $date = strtotime($lastpakcgae[0]->expiry);
                    $date = date('Y-m-d',strtotime("+1 year", $date));  
                     $hours_data=array(
                    'package_id'=>$data['package'],
                    'total_hours'=>'00:00:00',
                    'expiry'=>$date,
                    'current_question_count'=>0,
                     );
              Hours::updateoption2($hours_data,array('user_id'=>Session()->get('userid')));
               $transaction_data=array(
                                    'transaction_id'=>$data['transaction_id'],
                                    'user_id'=>$user_id,
                                    'package_id'=>$data['package'],
                                    'status'=>$data['status'],
                                    'currency'=>$data['currency'],
                                    'amount'=>$data['amount'],
                                    'walletuse'=>$data['walletuse'],
                                    'exp'=>$date,
                                    'recurring'=>'1',
                                    );
               Transaction::insertUser($transaction_data);
           }else
           {
               
               if(count($lastpakcgae2) > 0)
               {
                   $start_date =date('Y-m-d');  
                   $start_date = date('Y-m-d',strtotime("+7 day",$start_date));  
                    $date = strtotime($start_date);
                    $date = date('Y-m-d',strtotime("+1 year", $date));  
                     $hours_data=array(
                    'package_id'=>$data['package'],
                    'total_hours'=>'00:00:00',
                    'expiry'=>$date,
                    'current_question_count'=>0,
                     );
              Hours::updateoption2($hours_data,array('user_id'=>Session()->get('userid')));
               $transaction_data=array(
                                    'transaction_id'=>$data['transaction_id'],
                                    'user_id'=>$user_id,
                                    'package_id'=>$data['package'],
                                    'status'=>$data['status'],
                                    'currency'=>$data['currency'],
                                    'amount'=>$data['amount'],
                                    'walletuse'=>$data['walletuse'],
                                    'exp'=>$date,
                                    'recurring'=>'1',
                                    );
               Transaction::insertUser($transaction_data);
                   
               }else
               {
                    $start_date = strtotime(date('Y-m-d'));  
                    $date = strtotime($start_date);
                    $date = date('Y-m-d',strtotime("+1 year", $date));  
                     $hours_data=array(
                    'package_id'=>$data['package'],
                    'total_hours'=>'00:00:00',
                    'expiry'=>$date,
                    'current_question_count'=>0,
                     );
              Hours::updateoption2($hours_data,array('user_id'=>Session()->get('userid')));
               $transaction_data=array(
                                    'transaction_id'=>$data['transaction_id'],
                                    'user_id'=>$user_id,
                                    'package_id'=>$data['package'],
                                    'status'=>$data['status'],
                                    'currency'=>$data['currency'],
                                    'amount'=>$data['amount'],
                                    'walletuse'=>$data['walletuse'],
                                    'exp'=>$date,
                                    'recurring'=>'1',
                                    );
               Transaction::insertUser($transaction_data);
               }
           }
        }
        else
        {
            
                 
           if(count($lastpakcgae) > 0 )
           {      $date = strtotime($lastpakcgae[0]->expiry);
               //$start_dates =date('Y-m-d',strtotime($lastpakcgae[0]->expiry));  
               $date1 = date('Y-m-d',strtotime("+1 month", $date)); 
                $update_question_count=array(
                                            'total_hours'=>'00:00:00',
                                            'package_id'=>$data['package'],
                                            'expiry'=>$date1,
                                            'current_question_count'=>'0',
                                            );
                Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                
                 $transaction_data=array(
                                        'transaction_id'=>$data['transaction_id'],
                                        'user_id'=>$user_id,
                                        'package_id'=>$data['package'],
                                        'status'=>$data['status'],
                                        'currency'=>$data['currency'],
                                        'amount'=>$data['amount'],
                                        'walletuse'=>$data['walletuse'],
                                        'exp'=>$date1,
                                        'recurring'=>'1',
                                        );
                Transaction::insertUser($transaction_data);
           }else
           {
               
               if(count($lastpakcgae2) > 0)
               {
                   $start_date =strtotime(date('Y-m-d'));
                   $start_date = date('Y-m-d',strtotime("+7 day",$start_date));  
                    $date = strtotime($start_date);
                    //$date = date('Y-m-d',strtotime("+1 year", $date));
                    //$date = strtotime($lastpakcgae[0]->expiry);
               //$start_dates =date('Y-m-d',strtotime($lastpakcgae[0]->expiry));  
               $date1 = date('Y-m-d',strtotime("+1 month", $date)); 
                $update_question_count=array(
                                            'total_hours'=>'00:00:00',
                                            'package_id'=>$data['package'],
                                            'expiry'=>$date1,
                                            'current_question_count'=>'0',
                                            );
                Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                
                 $transaction_data=array(
                                        'transaction_id'=>$data['transaction_id'],
                                        'user_id'=>$user_id,
                                        'package_id'=>$data['package'],
                                        'status'=>$data['status'],
                                        'currency'=>$data['currency'],
                                        'amount'=>$data['amount'],
                                        'walletuse'=>$data['walletuse'],
                                        'exp'=>$date1,
                                        'recurring'=>'1',
                                        );
                Transaction::insertUser($transaction_data);
               }else
               {
                $date1 = date('Y-m-d',strtotime("+1 month", $date)); 
                $update_question_count=array(
                                            'total_hours'=>'00:00:00',
                                            'package_id'=>$data['package'],
                                            'expiry'=>$date1,
                                            'current_question_count'=>'0',
                                            );
                Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                
                 $transaction_data=array(
                                        'transaction_id'=>$data['transaction_id'],
                                        'user_id'=>$user_id,
                                        'package_id'=>$data['package'],
                                        'status'=>$data['status'],
                                        'currency'=>$data['currency'],
                                        'amount'=>$data['amount'],
                                        'walletuse'=>$data['walletuse'],
                                        'exp'=>$date1,
                                        'recurring'=>'1',
                                        );
                Transaction::insertUser($transaction_data);
               }
           }
        }
        
        $weres = [['friend_id','=',$user_id]];
        $reffercheck =  Reffer::getbycondition($weres);
        if(count($reffercheck) > 0)
        {
             $packages = Subscription_content::getbycondition(array('id'=>$data['package']));
             $amounts=$packages[0]->referrel_amount;
              if(!empty($amounts))
                {  
                    $were3 =  array(
                    'friend_id'=>$user_id,
                    'uid'=>$reffercheck[0]->uid
                    );
                   $amountss =  $reffercheck[0]->amount;
                    $amountss +=$packages[0]->referrel_amount;
                   Reffer::updateoption2(array('amount'=>$amountss),$were3);
                    
                }
        }
        $userid =Session()->get('userid');
        $data['uid'] = $userid;
        $weress= [['id','!=','']];
        $adminemail = Admin::getUsermatch($weress);
        $were= [['id','=', Session()->get('userid')]];
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
             $htmls = $r->name.' has been upgraded a plan, Please visit the following link given below:';
            $header = 'Upgarde a plan';
            $buttonhtml = 'Click here to visit';
            $pass_url  = url('admin/subscription_list'); 
            $path = url('resources/views/email.html');
            $subject = 'Upgarde a plan';
            $to_email=$adminemail[0];
            $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
             $arrays =[
            'w_from' => 'user',
            'from_id' => $r->id,
            'w_to' => 'admin',
            'to_id' => '1',
            'title' => $r->name.' has been upgraded a plan.',
            'description' => $r->name.' has been upgraded a plan.',
            'url' => 'admin/subscription_list',
            'tbl'=>'transaction',
            'status'=>'1'
            ];
            Notification::insertoption($arrays);
        }
         echo '<script type="text/javascript"> window.location = "'.url('/home').'" </script>';
        }
        else{
        }
    }
    
    
    
      public function register($id)
    {
       
       $messags = array();
     if(!empty($_POST['email'])){
         
         if(isset($_POST['refercode']) && !empty($_POST['refercode']))
         { 
             $ivs= $_POST['refercode'];
              unset($_POST['refercode']);
               $packages = Subscription_content::getbycondition(array('id'=>$_POST['package_id']));
               $amounts=$packages[0]->referrel_amount;
              
         }
         if(isset($_POST['usertoken']) && !empty($_POST['usertoken']) )
         {
             $iv= $_POST['usertoken'];
             $amounts = $_POST['referrel_amount'];
             unset($_POST['usertoken']);
             unset($_POST['referrel_amount']);
             
         }
         
         if(isset($_POST['usertoken2']) && !empty($_POST['usertoken2']))
         {
             $ivs= $_POST['usertoken2'];
             $amounts = $_POST['referrel_amount'];
             unset($_POST['referrel_amount']);
             unset($_POST['usertoken2']); 
         }
        
       $data=array(
           'name'=>$_POST['name'],
           'lname'=>$_POST['lname'],
           'email'=>$_POST['email'],
           'phone'=>$_POST['phone'],
           'country'=>$_POST['country'],
           'package_id'=>$_POST['package_id'],
           'dob'=> $_POST['dob'] ? date('Y-m-d H:i:s',strtotime($_POST['dob'])): '',
           'password'=>Hash::make($_POST['password']),
           'status'=>'1',
           'refferal_code'=>time().uniqid(rand()),
           'company_name'=>$_POST['company_name'],
           );  
           if(isset($data['dob']) && empty($data['dob']))
           {
               unset($data['dob']);
           }
         $email = [['email','=',$_POST['email']],['status','!=','2']];
        $exists = User::getUsermatch($email);
        if(count($exists) > 0 )
        {
            $messags['message'] = "Email already exist.";
            $messags['erro']= 202;
            $messags['url']= ''; 
        }
       
            if(User::insertUser($data))
                                        {
                                            $userdatas = User::getbycondition(array('email'=>$_POST['email']));
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
                                            );
                                            $date = '';
                                            if($_POST['package_id'] == '1'){
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+7 day", $date));  
                                            }
                                            if($_POST['package_id'] == '3')
                                            {
                                                $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+1 year", $date));   
                                            }
                                            if($_POST['package_id'] == '2')
                                            {
                                                $start_date =date('Y-m-d');  
                                                $date = strtotime($start_date);
                                                $date = date('Y-m-d',strtotime("+1 month", $date));  
                                            }
                                            $transaction_data=array(
                                            'transaction_id'=>$_POST['transaction_id'],
                                            'user_id'=>$users->id,
                                             'package_id'=>$_POST['package_id'],
                                             'status'=>$_POST['status'],
                                             'currency'=>$_POST['currency'],
                                            'amount'=>$_POST['amount'],
                                            'exp'=>$date
                                
                                             );
           
                                           Transaction::insertUser($transaction_data);
                                           if($_POST['package_id'] == '1'){
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+7 day", $date));  
                                             
                                               
                                             $hours_data=array(
                                            'user_id'=>$users->id,
                                            'package_id'=>$_POST['package_id'],
                                            'total_questions_uploaded'=>'0',
                                            'total_hours'=>'00:10:00',
                                            'expiry'=>$date,
                                            'current_question_count'=>0,
                                
                                             );
                                             }elseif($_POST['package_id'] == '3'){
                                            $start_date =date('Y-m-d');  
                                            $date = strtotime($start_date);
                                            $date = date('Y-m-d',strtotime("+1 year", $date));  
                                             
                                               
                                             $hours_data=array(
                                            'user_id'=>$users->id,
                                            'package_id'=>$_POST['package_id'],
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
                                            'package_id'=>$_POST['package_id'],
                                            'total_questions_uploaded'=>'0',
                                            'total_hours'=>'00:00:00',
                                            'expiry'=>$date,
                                            'current_question_count'=>0,
                                
                                             );
                                             }
                                       
                                        if(!empty($iv) && !empty($_POST['email']))
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
                                            $getdatas = Reffer::getbycondition($were3);
                                            Reffer::updateoption2($datas,$were3);
                                           
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
                                        
                                        if(!empty($ivs) && !empty($_POST['email']))
                                        {  
                                            $getdatas = User::getbycondition(array('refferal_code'=>$ivs));
                                            $datas = array(
                                            'uid'=>$getdatas[0]->id,
                                            'friend_email'=>$_POST['email'],
                                            'status'=>'1',
                                            'friend_id'=>$users->id,
                                            'amount' => $amounts,
                                            'token'=> rand()
                                            );
                                            Reffer::insertoption($datas);
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
                                            
                                            Session::put('user',$userdata);
                                            Session::put('userid', $users->id);
                                            Session::save(); 
                                            } 
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['erro']= 101;
                                            $messags['url']= url('/home'); 
                                        }
           
          
           
           
     }
    }
    public function facebooklogin(Request $request)
    {
           $messags = array();
                if($request->isMethod('post'))
                {
                    $data = $request->all();
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
                                            );
                                            Session::put('user',$userdata);
                                            Session::put('userid', $users->id);
	                                        Session::save(); 
                                        }
                                        $messags['message'] = "You logged in successfully.";
                                        $messags['erro']= 101;
                                        $messags['url']= url('/home'); 
                                    }else
                                    {
                                        $messags['message'] = "Your profile is exists, but your account is inactive.";
                                        $messags['erro']= 202;
                                        $messags['url']= ''; 
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
                                            );
                                            Session::put('user',$userdata);
                                            Session::put('userid', $users->id);
                                            Session::save(); 
                                            } 
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['erro']= 101;
                                            $messags['url']= url('/home');
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['erro']= 202;
                                            $messags['url']= ''; 
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
                                         $ivp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                                        $data['password'] = Hash::make($ivp);
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
                                            
                                            if(!empty($users->email))
                                            {  $name= $users->name;
                                               $id = $users->id;
                                                $iv = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                                                $htmls = str_replace('#password#',$ivp,str_replace('#name#',$users->name,Config::get('constants.Fb_password'))).', Please visit the following link given below:';
                                                $header = Config::get('constants.Fb_header');
                                                $buttonhtml = Config::get('constants.Fb_btn_html');
                                                $pass_url  = url('/login'); 
                                                $path = url('resources/views/email.html');
                                                $subject = Config::get('constants.Fb_Subject');
                                                $to_email=$users->email;
                                                $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                                            }
                                            Session::put('user',$userdata);
                                            Session::put('userid', $users->id);
                                            Session::save(); 
                                            } 
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['erro']= 101;
                                            $messags['url']= url('/home'); 
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['erro']= 202;
                                            $messags['url']= ''; 
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
                                            );
                                            Session::put('user',$userdata);
                                            Session::put('userid', $users->id);
                                            Session::save(); 
                                            }
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['erro']= 101;
                                            $messags['url']= url('/home'); 
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['erro']= 202;
                                            $messags['url']= ''; 
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
                                          $ivp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                                            $data['password'] = Hash::make($ivp);
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
                                             if(!empty($users->email))
                                            {  $name= $users->name;
                                               $id = $users->id;
                                                $iv = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                                                $htmls = str_replace('#password#',$ivp,str_replace('#name#',$users->name,Config::get('constants.Fb_password'))).', Please visit the following link given below:';
                                                $header = Config::get('constants.Fb_header');
                                                $buttonhtml = Config::get('constants.Fb_btn_html');
                                                $pass_url  = url('/login'); 
                                                $path = url('resources/views/email.html');
                                                $subject = Config::get('constants.Fb_Subject');
                                                $to_email=$users->email;
                                                $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                                            }
                                            /* end free package**/
                                            Session::put('user',$userdata);
                                            Session::put('userid', $users->id);
                                            Session::save(); 
                                            } 
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['erro']= 101;
                                            $messags['url']= url('/home'); 
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['erro']= 202;
                                            $messags['url']= ''; 
                                        } 
                                    }
                                
                           }
                       
                       
                   }else
                   {
                        $messags['message'] = "Error to login, try again later.";
                        $messags['erro']= 202;
                        $messags['url']= '';
                        
                   }
                   
                }else
                {
                    return Redirect::route('myprofile'); 
                }
        
        echo json_encode($messags);
                         die;
    }
    
    public function getSignOut(Request $request)
    {
        $this->middleware('auth');
        Auth::logout();
        Session::flush();
        $request->session()->forget('user');
        $request->session()->flush();
	    return Redirect('/'); 
    }
    
    public function googlelogin(Request $request)
    {
      $messags = array();
                if($request->isMethod('post'))
                {
                    $data = $request->all();
                    //echo '<pre>'; print_r($data); die;
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
                                        );
                                        Session::put('user',$userdata);
                                        Session::put('userid', $users->id);
                                        Session::save(); 
                                        }
                                      
                                        $messags['message'] = "You logged in successfully.";
                                        $messags['erro']= 101;
                                        $messags['url']= url('/home'); 
                                    }else
                                    {
                                        $messags['message'] = "Your profile is exists, but your account is inactive.";
                                        $messags['erro']= 202;
                                        $messags['url']= ''; 
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
                                            );
                                            Session::put('user',$userdata);
                                            Session::put('userid', $users->id);
                                            Session::save(); 
                                            } 
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['erro']= 101;
                                            $messags['url']= url('/home');
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['erro']= 202;
                                            $messags['url']= ''; 
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
                                         $ivp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                                          $data['password'] = Hash::make($ivp);
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
                                             
                                             if(!empty($users->email))
                                            {  $name= $users->name;
                                               $id = $users->id;
                                                
                                                $htmls = str_replace('#password#',$ivp,str_replace('#name#',$users->name,Config::get('constants.Fb_password'))).', Please visit the following link given below:';
                                                $header = Config::get('constants.Fb_header');
                                                $buttonhtml = Config::get('constants.Fb_btn_html');
                                                $pass_url  = url('/login'); 
                                                $path = url('resources/views/email.html');
                                                $subject = Config::get('constants.Fb_Subject');
                                                $to_email=$users->email;
                                                $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                                            }
                                            /* end free package**/
                                            Session::put('user',$userdata);
                                            Session::put('userid', $users->id);
                                            Session::save(); 
                                            }
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['erro']= 101;
                                            $messags['url']= url('/home'); 
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['erro']= 202;
                                            $messags['url']= ''; 
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
                                            );
                                            Session::put('user',$userdata);
                                            Session::put('userid', $users->id);
                                            Session::save(); 
                                            } 
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['erro']= 101;
                                            $messags['url']= url('/home'); 
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['erro']= 202;
                                            $messags['url']= ''; 
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
                                         $ivp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                                            $data['password'] = Hash::make($ivp);
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
                                             
                                             if(!empty($users->email))
                                            {  $name= $users->name;
                                               $id = $users->id;
                                                //$iv = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                                                $htmls = str_replace('#password#',$ivp,str_replace('#name#',$users->name,Config::get('constants.Fb_password'))).', Please visit the following link given below:';
                                                $header = Config::get('constants.Fb_header');
                                                $buttonhtml = Config::get('constants.Fb_btn_html');
                                                $pass_url  = url('/login'); 
                                                $path = url('resources/views/email.html');
                                                $subject = Config::get('constants.Fb_Subject');
                                                $to_email=$users->email;
                                                $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                                            }
                                            /* end free package**/
                                            Session::put('user',$userdata);
                                            Session::put('userid', $users->id);
                                            Session::save(); 
                                            }
                                            $messags['message'] = "You logged in successfully.";
                                            $messags['erro']= 101;
                                            $messags['url']= url('/home'); 
                                        }else
                                        {
                                            $messags['message'] = "Your profile is exists, but your account is inactive.";
                                            $messags['erro']= 202;
                                            $messags['url']= ''; 
                                        } 
                                    }
                                
                           }
                       
                       
                   }else
                   {
                        $messags['message'] = "Error to login, try again later.";
                        $messags['erro']= 202;
                        $messags['url']= '';
                        
                   }
                   
                }else
                {
                    return Redirect::route('myprofile'); 
                }
        
        echo json_encode($messags);
                         die;  
    }
    
    public function checkemail(Request $request)
    {
        $messags = array();
        if($request->isMethod('post'))
        {
          $data = $request->all();
          
          if(isset($data['refercode']) && !empty($data['refercode']))
              {
                  $data['getdatas'] = User::getbycondition([['refferal_code','=',$data['refercode']],['status','=','1']]);
                  if(count($data['getdatas']) == 0) 
                  {
                    $messags['message'] = "Refferal code is invalid.";
                    $messags['erro']= 2202;   
                     echo json_encode($messags);
                     die;
                  }
              }
          $where= [['email','=',$data['email']],['status','!=','2']];
           $userdatas = User::getbycondition($where);
           if(count($userdatas) > 0)
           {
               $messags['message'] = "exists";
                $messags['erro']= 101;
           }else
           {   if(!empty($data['phone']))
              {
                $where= [['phone','=',$data['phone']],['status','!=','2']];
                $userdatas = User::getbycondition($where);
                if(count($userdatas) > 0)
                {
                 $messags['message'] = "exists";
                 $messags['erro']= 1012;
                }else
                {
                    $messags['message'] = "We can't find a user with that e-mail address.";
                    $messags['erro']= 202;
                }
              }else
              {
                $messags['message'] = "We can't find a user with that e-mail address.";
                $messags['erro']= 202;  
              }
           }
        }else
        {
            $messags['message'] = "Email is required.";
            $messags['erro']= 202;
        }
            echo json_encode($messags);
            die;
        
    }
    
    public function forgetpass(Request $request)
  {
    if($request->isMethod('post'))
     {
	$messags = array();
      $data= $request->all();
		      
         $were= ['email'=> $data['email']];

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
                                return \Redirect::back()->withSuccess( 'We have e-mailed your password reset link!' );
                                //$messags['message'] = "We have e-mailed your password reset link!";
                               // $messags['erro']= 101;
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
                         return \Redirect::back()->with('error','Email does not exists');
                         
                        //$messags['message'] = "Email does not exists";
                        //$messags['erro']= 202;
				  }

     }else
     {
      return redirect('login');
     }
     
  }
  
  public function reset_passwords(Request $request,$ids='')
  {
   if(!empty($ids))
     {
           $were= ['forget_pass'=> $ids];

          /* get match the token match or not */
           $user = User::getbycondition($were);
          $data['id'] = $ids;
           if(count($user)>0)
           {
              return view('auth.passwords.resetpassword',$data);
          
           }else
           {
           
             return redirect()->intended(route('login'))->with('error','Link has been expired');                              
           } 
                      
          
     }
  }


  /* post password to set a new password */
  public function resetpassword(Request $request)
   {

    $messags = array();
    $data = $request->all();
    if(!empty($data['id']) && !empty($data['password']))
     {
       
            $were= ['forget_pass'=> $data['id']];
           $user = User::getbycondition($were);
         
          
           if(count($user)>0)
           {     
             foreach($user as $u){
                    $r = $u;
                  }
                                          
                   $iv = base64_encode((rand()));
                   $update = ['forget_pass' => $iv,'password'=> $password = Hash::make( $data['password'] ) ];
                  if(User::updateUser($update,$r->id))
                  {
                   $messags['message'] = "Your password reset successfully.";
	            $messags['erro']= 101;   
                  }else
                  {
                    $messags['message'] = "Erro: to reset password.";
	            $messags['erro']= 202;
                    
                  }

           }else
           {
              $messags['message'] = "Erro: link has been expired.";
              $messags['erro']= 202;
                                           
           } 
     }else
     {
          $messags['message'] = "Erro: link has been expired.";
          $messags['erro']= 202;     
     }
    echo json_encode($messags);
    die;
     
   }
  
  
  public function profile(Request $request)
  {
      $messags = array();
    if($request->isMethod('post'))
    {
        $data = $request->all();
      if(session()->exists('user'))
      {
            if(!empty($data['oldpassword']))
            {
              $userid =Session()->get('userid');
              $wereh= [['email','=',$data["email"]],['id','=',$userid]];
              $hashedPassword= User::getdetailsuserret2($wereh,'password');
                if (Hash::check($data['oldpassword'], $hashedPassword)) 
                {
                    unset($data['oldpassword']);
                }else
                {
                    $messags['message'] = "The old password you entered does not match our records, Please try again.";
                    $messags['erro']= 202;  
                    echo json_encode($messags);
                     die; 
                }
            }
          if(!empty($data['newpassword']) && empty($data['conpassword']))
          {
                  $messags['message'] = "Confirm password is required.";
                  $messags['erro']= 202; 
          }
          else if(!empty($data['conpassword']) && empty($data['newpassword']))
          {
                $messags['message'] = "New password is required.";
                $messags['erro']= 202; 
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
                $messags['erro']= 202;   
            }
          }
              unset($data['_token']);
              $data['status']='1';
             // $data = array_filter($data);
             
              if(isset($data['dob']) && !empty($data['dob']))
              {
                  $data['dob'] = date('Y-m-d H:i:s',strtotime($data['dob']));

              }
              $userid =Session()->get('userid');
              $were= [['email','=',$data["email"]],['id','!=',$userid],['status','!=','2']];
              $exists= User::getUsermatch($were);
              if(count($exists) > 0)
              {
                  $messags['message'] = "Email already exist.";
                  $messags['erro']= 202;   
              }else
              {    if(!empty($data["phone"]))
                   {
                        $were= [['phone','=',$data["phone"]],['id','!=',$userid],['status','!=','2']];
                        $exists= User::getUsermatch($were);
                        if(count($exists) > 0)
                        {
                         $messags['message'] = "Phone number is already exist.";
                         $messags['erro']= 202;   
                        }else
                        {
                            if(empty($data['authentication']) || !$data['authentication'])
                            {
                                $data['authentication'] = '0';
                            }
                            if(User::updateUser($data,$userid))
                            {
                                 $messags['message'] = "Your profile has been updated sucessfully.";
                                 $messags['erro']= 101;    
                            }else
                            {
                                 $messags['message'] = "Error to update your profile.";
                                 $messags['erro']= 202;   
                            } 
                        }
                   }else
                   { 
                        if(empty($data['authentication']) || !$data['authentication'])
                            {
                                $data['authentication'] = '0';
                            }

                            if(User::updateUser($data,$userid))
                            {
                                 $messags['message'] = "Your profile has been updated sucessfully.";
                                 $messags['erro']= 101;    
                            }else
                            {
                                 $messags['message'] = "Error to update your profile.";
                                 $messags['erro']= 202;   
                            } 
                   }
              }
              
             
          
         
          
      }else
      {
        $messags['message'] = "Session has been expired.";
        $messags['erro']= 202;   
      }
    }else
    {
        return redirect('/login');
    }
     echo json_encode($messags);
     die;
      
  }
  
  
   public function uploadfile(Request $request)
   {
   
	   if(session()->exists('user'))
	     {
	       $messags = array();
		       if($request->isMethod('post'))
		          {
		           $data = $request->all();
			     if($request->file('file'))
			       {
			            $image = $request->file('file');
			            $imagename = time().'.'.$image->getClientOriginalExtension();
			            $destinationPath = public_path('/profile');
			            $image->move($destinationPath, $imagename);
			             $path1 = $imagename;
			             $userid =Session()->get('userid');
                          if(User::updateUser(array('profile'=>$path1),$userid))
                          {
    			             $messags['path'] = $path1;
    			             $messags['message'] = "Porfile Image uploaded Successfully.";
                            $messags['erro']= 101;
                          }else
                          {
                              $messags['message'] = "Error to upload the profile image.";
                             $messags['erro']= 202;
                          }
			          
			       }elseif($request->file('questionimg'))
			       {
			            $image = $request->file('questionimg');
			            $imagename = time().'.'.$image->getClientOriginalExtension();
			            $destinationPath = public_path('/questionimg');
			            $image->move($destinationPath, $imagename);
			             $path1 = $imagename;
                          if(!empty($path1))
                          {
    			             $messags['path'] = $path1;
                            $messags['erro']= 101;
                          }else
                          {
                             $messags['erro']= 202;
                          }
			          
			       }else
			       {
			          $messags['message'] = "Error to upload the profile image.";
                                 $messags['erro']= 202;
			       }
		         }else
		         {
		             $messags['message'] = "Error to upload the profile image.";
                     $messags['erro']= 202;
		         }
		         echo json_encode($messags);
                         die;
       }
   }
     
   
    public function questionlist(Request $request)
    {
         if(session()->exists('user'))
        {
            $data['title']='Questions List';
            $data['page']='questionlist';
            $id=Session()->get('userid');
            $were = [['user_id','=',$id],['status', '!=','2' ]];
            $data['questions'] = Question_answers::getbycondition($were);
            $gettags = [['type', '=', '2'],['status', '=', '1']]; 
            $gettags2 = [['type', '=', '3'],['status', '=', '1']]; 
            $data['subject']=course::getbycondition($gettags);
            $data['chapter']=course::getbycondition($gettags2);
            $data['courses']= course::getoption();
        return view('user.questionlist',$data);
        }else
        {
            return redirect('/login');
        }
    }
    
    public function addquestions(Request $request)
    { 
        if(session()->exists('user'))
        {
        $messags = array();
        if($request->isMethod('post'))
          {
              
                $data= $request->all();
               
                unset($data['_token']);
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
                $datas['user_id']=Session()->get('userid');
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
                        'question' => $data['question'][$key],
                        'type' => $data['type'][$key],
                        'optiona' => $data['optiona'][$key],
                        'optionb' => $data['optionb'][$key],
                        'optionc' => $data['optionc'][$key],
                        'optiond' => $data['optiond'][$key],
                        'answer' => $data['answer'][$key],
                        'question_id' => $id,
                         'qstatus'=>$status
                        ];
                 /*if($ap[0] !='1')
                {
                   $res=Hours::getdetailsuser(Session()->get('userid'));
                   if(!empty($res))
                   {
                       if($res->package_id == '1'){
                           if($res->current_question_count == '9')
                           {  
                               
                            $timestamp = strtotime($res->total_hours) + 60*60;
                            $time = date('H:i:s', $timestamp);

                               $update_question_count=array(
                                    'total_questions_uploaded'=>$res->total_questions_uploaded + '1',
                                    'current_question_count'=>'1',
                                    'total_hours'=>$time,
                                    );
                               
                           }else
                           {
                               $update_question_count=array(
                                    'total_questions_uploaded'=>$res->total_questions_uploaded + '1',
                                    'current_question_count'=>$res->current_question_count + '1',
                                    );
                               
                           }
                           
                       }else
                       {
                           $update_question_count=array(
                                    'total_questions_uploaded'=>$res->total_questions_uploaded + '1',
                                    );
                           
                       }
                       
                       
                       Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                       
                   }
                }*/
                   
                   
                    $ddis=  Question_answers::insertoption2($input);
                      Question_answers::updateoption($datas,$ddis);
                }//foreach end
                      
                           
                           
                      /// $res=Hours::getdetailsuser(Session()->get('userid'));
                           
                           
                       //   $update_question_count=array(
                              
                           //   'total_questions_uploaded'=>
                              
                              
                              
                           ///   );
                    
                     if($ap[0]=='0')
                        {
                            $getall =  Question_answers::getbycondition(array('user_id'=>Session()->get('userid')));
                            $getall2 =  Question_answers::getbycondition(array('user_id'=>Session()->get('userid'),'qstatus'=>'1'));
                            $update_question_count=array(
                            'total_questions_uploaded'=>count($getall),
                            );
                            Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                            $res=Hours::getdetailsuser(Session()->get('userid'));
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
                                            'total_hours'=>$time
                                            );
                                    }
                                } 
                                
                                }
                                Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                            
                            }
                          $messags['message'] = "Question has been added successfully.";
                          $messags['erro']= 101;
                        }else
                        {
                            $weres= [['id','!=','']];
                            $adminemail = Admin::getUsermatch($weres);
                            $were= [['id','=', Session()->get('userid')]];
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
                           // $htmls = 'To Approve the questions created by '.$r->name.', Please visit the following link given below:';
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
                          $messags['erro']= 101;
                        }
                    
                   
                }else
                {
                  $messags['message'] = "Error to add a question.";
                  $messags['erro']= 202; 
                }
               
          }else
          {
              $messags['message'] = "Error to add a question.";
              $messags['erro']= 202;
          }
        }else
        {
            $messags['message'] = "Session has been expired.";
             $messags['erro']= 202; 
             $messags['url']= url('/');
        }
        echo json_encode($messags);
                         die;
    }
    
    public function editquestion(Request $request,$id='')
    {
         $data = $request->all();
        
        if(isset($data['country']) && $data['country']!='')
        {
         $gettags = [['parent', '=', $data['country']],['status', '=', '1']]; 
         $text = 'Select State';
          $states=country::getbycondition($gettags);
             if ($request->ajax()) {
    		    $view = view('user.states', compact('states','text'))->render();
               return response()->json(['html'=>$view]);
    		}
        }
        
        if(isset($data['subject']) && $data['subject']!='')
        {
          $gettags = [['parent', '=', $data['subject']],['type', '=', '2'],['status', '=', '1']]; 
           $text = 'Select Subject';
          $states=course::getbycondition($gettags);
             if ($request->ajax()) {
    		    $view = view('user.states', compact('states','text'))->render();
               return response()->json(['html'=>$view]);
    		}  
        }
        
        if(isset($data['chapter']) && $data['chapter']!='')
        {
          $gettags = [['parent', '=', $data['chapter']],['type', '=', '3'],['status', '=', '1']]; 
           $text = 'Select Chapter';
          $states=course::getbycondition($gettags);
             if ($request->ajax()) {
    		    $view = view('user.states', compact('states','text'))->render();
               return response()->json(['html'=>$view]);
    		}  
        }
        $were = array('status'=>'1');
        $data['grades']= Grades::getbycondition($were);
        $data['years']= Years::getbycondition($were);
        $data['courses']= course::getoption();
        $this->middleware('auth');
        $data['user'] = Auth::user();
        $data['countries']= country::getoption();
        $users = Auth::user();
        $user_id=Session()->get('userid');
        $were = [['id','=',$id],['status', '!=','2' ],['user_id','=',$user_id]];
       // $data['questions'] = Pre_questiondetails::getoption($were);
      $data['answers']=Question_answers::getbycondition($were);
      $data['questions']=Question_answers::getbycondition($were);
        if(!empty($data['questions']) && count($data['questions']) > 0)
        {  
         $questions = Question_answers::getbycondition($were);
         if(!empty($questions[0]->country))
       { 
        $gettagss = [['parent', '=', $questions[0]->country],['status', '=', '1']];
        $data['states']=country::getbycondition($gettagss);
       }
          if(!empty($questions[0]->course))
          {
          $gettagssub = [['parent', '=', $questions[0]->course],['type', '=', '2'],['status', '=', '1']]; 
           $data['subjects']=course::getbycondition($gettagssub);
          }
        if(!empty($questions[0]->subject))
        {
        $gettagschap = [['parent', '=', $questions[0]->subject],['type', '=', '3'],['status', '=', '1']];
          $data['chapterss']=course::getbycondition($gettagschap);
        }
       }  
        $data['title']='Edit Questions';
        $data['page']='editquestion';
        //echo '<pre>'; print_r($data['states']); die; 
        if(!empty($data['user']) && $users->id !='' && isset($users->id))
        {
        return view('/user/editquestion',$data);
        }
        else if(session()->exists('user'))
        {
            return view('/user/editquestion',$data);
        }
        else
        {
            return redirect('/login');
        }
    }
    
    public function editquestions(Request $request)
    {
         
         if(session()->exists('user'))
        {
        $messags = array();
        if($request->isMethod('post'))
          {
                $data= $request->all();
                unset($data['_token']);
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
               //// $datas['is_admin']='0';
               // $datas['user_id']=Session()->get('userid');
                //$data['is_admin']='0';
               // $data['user_id']=Session()->get('userid');
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
               
                if(isset($data['id']))
                {    
                     $id = $data['id'];
                      unset($data['id']);
                } 
                 $datas['status']=$status; 
                 $datas['qstatus'] = $status;
                 /*if(Pre_questiondetails::updateoption($datas,$id))
                 {*/
                    if(!empty($data))
                    {  
                       
                       foreach($data['question'] as $key=>$ques)
                        {
                            $input = [
                            'type' => $data['type'][$key],
                            'question' => $data['question'][$key],
                            'optiona' => $data['optiona'][$key],
                            'optionb' => $data['optionb'][$key],
                            'optionc' => $data['optionc'][$key],
                            'optiond' => $data['optiond'][$key],
                            'answer' => $data['answer'][$key],
                            'question_id' => 0,
                            'qstatus'=>$status,
                            ];
                            
                           Question_answers::updateoption($input,$data['question_answer_id'][$key]); 
                           Question_answers::updateoption($datas,$data['question_answer_id'][$key]); 
                        }
                        
                        if($ap[0]=='0')
                        {
                          $messags['message'] = "Question has been updated successfully.";
                          $messags['erro']= 101;
                        }else
                        {
                            $weres= [['id','!=','']];
                            $adminemail = Admin::getUsermatch($weres);
                            $were= [['id','=', Session()->get('userid')]];
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
                            //$htmls = 'To Approve the questions updated by '.$r->name.', Please visit the following link given below:';
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
                          $messags['erro']= 101;
                        }
                       
                       
                       
                    }else
                    {
                      $messags['message'] = "Error to Update a questions.";
                      $messags['erro']= 202; 
                    }
                
               
          }else
          {
              $messags['message'] = "Error to edit a questions.";
              $messags['erro']= 202;
          }
        }else
        {
            $messags['message'] = "Session has been expired.";
             $messags['erro']= 202; 
             $messags['url']= url('/');
        }
        echo json_encode($messags);
                         die;
    }
    
     
    public function viewquestion($id='')
    {
        if(session()->exists('user'))
        {
        $user_id=Session()->get('userid');
        $were = array('status'=>'1');
        $data['grades']= Grades::getbycondition($were);
        $data['years']= Years::getbycondition($were);
        $data['courses']= course::getoption();
        $this->middleware('auth');
        $data['user'] = Auth::user();
        $data['countries']= country::getoption();
        $users = Auth::user();
        $prewere = [['id','=',$id],['status', '!=','2' ],['user_id','=',$user_id]];
       // $data['questions'] = Pre_questiondetails::getoption($prewere);
       /* $data['questions']=Question_answers::getbycondition($prewere);
        if(!empty($data['questions']) && count($data['questions']) > 0)
        {
             $questions =Question_answers::getbycondition($prewere);
       // $questions = Pre_questiondetails::getoption($prewere);
       if(!empty($questions[0]->country))
       {
        $gettagss = [['id', '=', $questions[0]->country],['status', '=', '1']];
        $data['countryexists']=country::getbycondition($gettagss);
        $gettagss2 = [['parent', '=', $questions[0]->country],['id', '=', $questions[0]->state],['status', '=', '1']];
        $data['stateexists']=country::getbycondition($gettagss2);
        
        $gettagss = [['parent', '=', $questions[0]->country],['status', '=', '1']];
        $data['states']=country::getbycondition($gettagss);
       }
       
       if(!empty($questions[0]->year))
       { $were = array('status'=>'1','id'=>$questions[0]->year);
          $data['yearsexists']= Years::getbycondition($were);  
       }else
       {
           $data['yearsexists']= '';
       }
       
       if(!empty($questions[0]->grade))
       { $were = array('status'=>'1','id'=>$questions[0]->grade);
          $data['gradeexists']= Grades::getbycondition($were);  
       }else
       {
           $data['gradeexists']= '';
       }
        
        if(!empty($questions[0]->course))
        {
         $gettagss2 = [['id', '=', $questions[0]->course],['status', '=', '1']];
        $data['courseexists']=course::getbycondition($gettagss2);
            if(count($data['courseexists']) > 0)
            {
                 $gettagssub22 = [['parent', '=', $questions[0]->course],['id', '=', $questions[0]->subject],['type', '=', '2'],['status', '=', '1']]; 
                 $data['subjectsexists']=course::getbycondition($gettagssub22);
                 if(count($data['subjectsexists']) > 0)
                 {
                    $gettagssub22 = [['parent', '=', $questions[0]->subject],['id', '=', $questions[0]->chapter],['type', '=', '3'],['status', '=', '1']]; 
                 $data['chapterexists']=course::getbycondition($gettagssub22);  
                 }
                
              $gettagssub = [['parent', '=', $questions[0]->course],['type', '=', '2'],['status', '=', '1']]; 
              $data['subjects']=course::getbycondition($gettagssub);
                 if(!empty($questions[0]->subject))
                {
                  $gettagschap = [['parent', '=', $questions[0]->subject],['type', '=', '3'],['status', '=', '1']];
                  $data['chapterss']=course::getbycondition($gettagschap);
                }
            }
        }
        
        $data['answers']=Question_answers::getbycondition(array('id'=>$id));
        } */
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
       // echo '<pre>'; print_r($data['questions']); die; 
            $data['title']= 'Questions View';
        $data['page']='viewquestion';
        
        //echo $id;
             return view('/user/viewquestions',$data);
        }else
        {
            return redirect('/login');
        }
    }
    
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
    
     public function delete($id1,$id2)
    {
        if(session()->exists('user'))
        {
          $data = [
               'status' => '2',
               'qstatus'=> '2',
            ];
         $this->updateData($id1,array('id'=>$id2), $data);   
          echo 1; die;
        }else
        {
           return false; 
        }
    }
public function stripe_update_plan(Request $request)
{
     $data = $request->all();
     
     if(session()->exists('user'))
        {
            $user_id=Session()->get('userid');
            $userid=Session()->get('userid');
            //cancel active subscription for stripe 
            $transactions = Transaction::getbycondition(array('user_id'=>$userid));
            $show = '0';
            foreach($transactions as $tr)
            { 
            if($tr->recurring=='1')
            { 
            if (strpos($tr->transaction_id, 'I-') !== false) {
            $show = '1';
            $id = $tr->transaction_id;
            }
            elseif (strpos($tr->transaction_id, 'sub') !== false) {
            $show = '2';
            $id = $tr->transaction_id;
            }
            }        
            }
            
            if($show=='1'){
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
            $user_id=Session()->get('userid');
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
            
            if($nvp['ACK']=='Success')
            {
            Transaction::updateoption2(array('recurring'=>'0'),array('transaction_id'=>$id,'user_id'=>$user_id));
            echo '1'; die;  
            } 
            }elseif($show=='2')
            {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $sub =  \Stripe\Subscription::retrieve($id);
            $sub->cancel();
            Transaction::updateoption2(array('recurring'=>'0'),array('transaction_id'=>$id,'user_id'=>$user_id));
            }
            //end of cancel stripe    
            //add new subscriptiobn stripe
            
            
                        if(!empty($data['stripeToken']))
                        {
                            
                              
                        $weres = [['friend_id','=',$user_id]];
                        $reffercheck =  Reffer::getbycondition($weres);
                        
                        if(count($reffercheck) > 0)
                        {
                        $packages = Subscription_content::getbycondition(array('id'=>$data['pid']));
                        $amounts=$packages[0]->referrel_amount;
                        if(!empty($amounts))
                        {  
                        $were3 =  array(
                        'friend_id'=>$user_id,
                        'uid'=>$reffercheck[0]->uid
                        );
                        $amountss =  $reffercheck[0]->amount;
                        $amountss +=$packages[0]->referrel_amount;
                        echo $amountss;
                        
                        Reffer::where('friend_id',$user_id)->where('uid',$reffercheck[0]->uid)->update(array('amount'=>$amountss));
                        
                        }
                        }      
                         
                        Stripe::setApiKey(env('STRIPE_SECRET'));
                        $token  =$data['stripeToken'];
                        $email  =$data['stripeEmail'];
                        
                        if($data['pid'] =='2')
                        {
                        $int='month';
                        }elseif($data['pid'] =='3')
                        {
                        $int='year'; 
                        }
                        $subscription_d = Subscription_content::where('id',$data['pid'])->first();
                        $plan = Plans::create(array( "product" => [ "name" => $subscription_d->title ],
                        "nickname" => $subscription_d->title ,
                        "interval" =>$int,
                        "interval_count" => 1, 
                        "currency" => "usd", 
                        "amount" => $data['amount_stripe'], ));
                        
                        $customer = Customer::create(array(
                        'email' => $email,
                        'source'  => $token
                        ));
                        
                        $subscription = \Stripe\Subscription::create(array(
                        "customer" => $customer->id,
                        "items" => array(
                        array(
                        "plan" => $plan->id,
                        ),
                        ),
                        ));
                        
                        
                        $date = '';
                            if($data['pid'] == '1'){
                            $start_date =date('Y-m-d');  
                            $date = strtotime($start_date);
                            $date = date('Y-m-d',strtotime("+7 day", $date));  
                            }
                            if($data['pid'] == '3')
                            {
                                $start_date =date('Y-m-d');  
                            $date = strtotime($start_date);
                            $date = date('Y-m-d',strtotime("+1 year", $date));   
                            }
                            if($data['pid'] == '2')
                            {
                                $start_date =date('Y-m-d');  
                                $date = strtotime($start_date);
                                $date = date('Y-m-d',strtotime("+1 month", $date));  
                            }
                        
                         $transaction_data=array(
                            'transaction_id'=>$subscription->id,
                            'user_id'=>$user_id,
                             'package_id'=>$data['pid'],
                             'status'=>'completed',
                             'currency'=>'usd',
                            'amount'=>$data['amount'],
                             'walletuse'=>$data['walletuse'],
                            'exp'=>$date,
                            'recurring'=>'1'
                
                             ); 
                             Transaction::insertUser($transaction_data);
                       
                             
                             
                             
        $lastpakcgae = Hours::getbycondition([['user_id','=',$user_id],['package_id','!=','1']]);
        $lastpakcgae2 = Hours::getbycondition([['user_id','=',$user_id],['package_id','=','1']]);
        if($data['pid'] == '1'){
        $date1 = date('Y-m-d',strtotime("+7 day", $date)); 
        $update_question_count=array(
                                    'total_hours'=>'00:10:00',
                                    'package_id'=>$data['pid'],
                                    'expiry'=>$date1,
                                    'current_question_count'=>'0',
                                    );
        Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
        
         
        }else if($data['pid'] == '3')
        {       
           if(count($lastpakcgae) > 0 )
           {  
               //$start_date =date('Y-m-d',strtotime($lastpakcgae[0]->expiry));  
                    $date = strtotime($lastpakcgae[0]->expiry);
                    $date = date('Y-m-d',strtotime("+1 year", $date));  
                     $hours_data=array(
                    'package_id'=>$data['pid'],
                    'total_hours'=>'00:00:00',
                    'expiry'=>$date,
                    'current_question_count'=>0,
                     );
              Hours::updateoption2($hours_data,array('user_id'=>Session()->get('userid')));
              
           }else
           {
               
               if(count($lastpakcgae2) > 0)
               {
                   $start_date =date('Y-m-d');  
                   $start_date = date('Y-m-d',strtotime("+7 day",$start_date));  
                    $date = strtotime($start_date);
                    $date = date('Y-m-d',strtotime("+1 year", $date));  
                     $hours_data=array(
                    'package_id'=>$data['pid'],
                    'total_hours'=>'00:00:00',
                    'expiry'=>$date,
                    'current_question_count'=>0,
                     );
              Hours::updateoption2($hours_data,array('user_id'=>Session()->get('userid')));
                 
               }else
               {
                    $start_date = strtotime(date('Y-m-d'));  
                    $date = strtotime($start_date);
                    $date = date('Y-m-d',strtotime("+1 year", $date));  
                     $hours_data=array(
                    'package_id'=>$data['pid'],
                    'total_hours'=>'00:00:00',
                    'expiry'=>$date,
                    'current_question_count'=>0,
                     );
              Hours::updateoption2($hours_data,array('user_id'=>Session()->get('userid')));
               
               }
           }
        }
        else
        {
            
                 
           if(count($lastpakcgae) > 0 )
           {
               $date = strtotime($lastpakcgae[0]->expiry);
               //$start_dates =date('Y-m-d',strtotime($lastpakcgae[0]->expiry));  
               $date1 = date('Y-m-d',strtotime("+1 month", $date)); 
                $update_question_count=array(
                                            'total_hours'=>'00:00:00',
                                            'package_id'=>$data['pid'],
                                            'expiry'=>$date1,
                                            'current_question_count'=>'0',
                                            );
                Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                
             
           }else
           {
               
               if(count($lastpakcgae2) > 0)
               {
                   $start_date =strtotime(date('Y-m-d'));
                   $start_date = date('Y-m-d',strtotime("+7 day",$start_date));  
                    $date = strtotime($start_date);
                    //$date = date('Y-m-d',strtotime("+1 year", $date));
                    //$date = strtotime($lastpakcgae[0]->expiry);
               //$start_dates =date('Y-m-d',strtotime($lastpakcgae[0]->expiry));  
               $date1 = date('Y-m-d',strtotime("+1 month", $date)); 
                $update_question_count=array(
                                            'total_hours'=>'00:00:00',
                                            'package_id'=>$data['pid'],
                                            'expiry'=>$date1,
                                            'current_question_count'=>'0',
                                            );
                Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                
               }else
               {
                $date1 = date('Y-m-d',strtotime("+1 month", $date)); 
                $update_question_count=array(
                                            'total_hours'=>'00:00:00',
                                            'package_id'=>$data['pid'],
                                            'expiry'=>$date1,
                                            'current_question_count'=>'0',
                                            );
                Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                
                
               }
           }
            }
            
            }
            
            
            
                return redirect(url('/home')); 
            
            }
  
}
  public function update_plan()
  {
     if(session()->exists('user'))
        {
        $user_id=Session()->get('userid');
        $userid=Session()->get('userid');
       
            /*//cancel active subscription for stripe 
            $transactions = Transaction::getbycondition(array('user_id'=>$userid));
            $show = '0';
            
            foreach($transactions as $tr)
            { 
            if($tr->recurring=='1')
            { 
            if (strpos($tr->transaction_id, 'I-') !== false) {
            $show = '1';
            $id = $tr->transaction_id;
            }
            elseif (strpos($tr->transaction_id, 'sub') !== false) {
            $show = '2';
            $id = $tr->transaction_id;
            }
            }        
            }
            if($show='2')
            {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $sub =  \Stripe\Subscription::retrieve($id);
            $sub->cancel();
            Transaction::updateoption2(array('recurring'=>'0'),array('transaction_id'=>$id,'user_id'=>$user_id));
            }*/
        //end of cancel stripe     
        
        $start_date =date('Y-m-d');  
        $date = strtotime($start_date);
        $lastpakcgae = Hours::getbycondition([['user_id','=',$user_id],['package_id','!=','1']]);
        $lastpakcgae2 = Hours::getbycondition([['user_id','=',$user_id],['package_id','=','1']]);
        if($_POST['package'] == '1'){
        $date1 = date('Y-m-d',strtotime("+7 day", $date)); 
        $update_question_count=array(
                                    'total_hours'=>'00:10:00',
                                    'package_id'=>$_POST['package'],
                                    'expiry'=>$date1,
                                    'current_question_count'=>'0',
                                    );
        Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
        
         $transaction_data=array(
                                'transaction_id'=>'0',
                                'user_id'=>$user_id,
                                'package_id'=>$_POST['package'],
                                'status'=>'completed',
                                'currency'=>"",
                                'amount'=>'0',
                                'walletuse'=>$_POST['walletuse'],
                                'exp'=>$date1
                                );
        Transaction::insertUser($transaction_data);
        }else if($_POST['package'] == '3')
        {       
           if(count($lastpakcgae) > 0 )
           {  
               //$start_date =date('Y-m-d',strtotime($lastpakcgae[0]->expiry));  
                    $date = strtotime($lastpakcgae[0]->expiry);
                    $date = date('Y-m-d',strtotime("+1 year", $date));  
                     $hours_data=array(
                    'package_id'=>$_POST['package'],
                    'total_hours'=>'00:00:00',
                    'expiry'=>$date,
                    'current_question_count'=>0,
                     );
              Hours::updateoption2($hours_data,array('user_id'=>Session()->get('userid')));
               $transaction_data=array(
                                    'transaction_id'=>$_POST['transaction_id'],
                                    'user_id'=>$user_id,
                                    'package_id'=>$_POST['package'],
                                    'status'=>$_POST['status'],
                                    'currency'=>$_POST['currency'],
                                    'amount'=>$_POST['amount'],
                                    'walletuse'=>$_POST['walletuse'],
                                    'exp'=>$date
                                    );
               Transaction::insertUser($transaction_data);
           }else
           {
               
               if(count($lastpakcgae2) > 0)
               {
                   $start_date =date('Y-m-d');  
                   $start_date = date('Y-m-d',strtotime("+7 day",$start_date));  
                    $date = strtotime($start_date);
                    $date = date('Y-m-d',strtotime("+1 year", $date));  
                     $hours_data=array(
                    'package_id'=>$_POST['package'],
                    'total_hours'=>'00:00:00',
                    'expiry'=>$date,
                    'current_question_count'=>0,
                     );
              Hours::updateoption2($hours_data,array('user_id'=>Session()->get('userid')));
               $transaction_data=array(
                                    'transaction_id'=>$_POST['transaction_id'],
                                    'user_id'=>$user_id,
                                    'package_id'=>$_POST['package'],
                                    'status'=>$_POST['status'],
                                    'currency'=>$_POST['currency'],
                                    'amount'=>$_POST['amount'],
                                    'walletuse'=>$_POST['walletuse'],
                                    'exp'=>$date
                                    );
               Transaction::insertUser($transaction_data);
                   
               }else
               {
                    $start_date = strtotime(date('Y-m-d'));  
                    $date = strtotime($start_date);
                    $date = date('Y-m-d',strtotime("+1 year", $date));  
                     $hours_data=array(
                    'package_id'=>$_POST['package'],
                    'total_hours'=>'00:00:00',
                    'expiry'=>$date,
                    'current_question_count'=>0,
                     );
              Hours::updateoption2($hours_data,array('user_id'=>Session()->get('userid')));
               $transaction_data=array(
                                    'transaction_id'=>$_POST['transaction_id'],
                                    'user_id'=>$user_id,
                                    'package_id'=>$_POST['package'],
                                    'status'=>$_POST['status'],
                                    'currency'=>$_POST['currency'],
                                    'amount'=>$_POST['amount'],
                                    'walletuse'=>$_POST['walletuse'],
                                    'exp'=>$date
                                    );
               Transaction::insertUser($transaction_data);
               }
           }
        }
        else
        {
            
                 
           if(count($lastpakcgae) > 0 )
           {      $date = strtotime($lastpakcgae[0]->expiry);
               //$start_dates =date('Y-m-d',strtotime($lastpakcgae[0]->expiry));  
               $date1 = date('Y-m-d',strtotime("+1 month", $date)); 
                $update_question_count=array(
                                            'total_hours'=>'00:00:00',
                                            'package_id'=>$_POST['package'],
                                            'expiry'=>$date1,
                                            'current_question_count'=>'0',
                                            );
                Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                
                 $transaction_data=array(
                                        'transaction_id'=>$_POST['transaction_id'],
                                        'user_id'=>$user_id,
                                        'package_id'=>$_POST['package'],
                                        'status'=>$_POST['status'],
                                        'currency'=>$_POST['currency'],
                                        'amount'=>$_POST['amount'],
                                        'walletuse'=>$_POST['walletuse'],
                                        'exp'=>$date1
                                        );
                Transaction::insertUser($transaction_data);
           }else
           {
               
               if(count($lastpakcgae2) > 0)
               {
                   $start_date =strtotime(date('Y-m-d'));
                   $start_date = date('Y-m-d',strtotime("+7 day",$start_date));  
                    $date = strtotime($start_date);
                    //$date = date('Y-m-d',strtotime("+1 year", $date));
                    //$date = strtotime($lastpakcgae[0]->expiry);
               //$start_dates =date('Y-m-d',strtotime($lastpakcgae[0]->expiry));  
               $date1 = date('Y-m-d',strtotime("+1 month", $date)); 
                $update_question_count=array(
                                            'total_hours'=>'00:00:00',
                                            'package_id'=>$_POST['package'],
                                            'expiry'=>$date1,
                                            'current_question_count'=>'0',
                                            );
                Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                
                 $transaction_data=array(
                                        'transaction_id'=>$_POST['transaction_id'],
                                        'user_id'=>$user_id,
                                        'package_id'=>$_POST['package'],
                                        'status'=>$_POST['status'],
                                        'currency'=>$_POST['currency'],
                                        'amount'=>$_POST['amount'],
                                        'walletuse'=>$_POST['walletuse'],
                                        'exp'=>$date1
                                        );
                Transaction::insertUser($transaction_data);
               }else
               {
                $date1 = date('Y-m-d',strtotime("+1 month", $date)); 
                $update_question_count=array(
                                            'total_hours'=>'00:00:00',
                                            'package_id'=>$_POST['package'],
                                            'expiry'=>$date1,
                                            'current_question_count'=>'0',
                                            );
                Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
                
                 $transaction_data=array(
                                        'transaction_id'=>$_POST['transaction_id'],
                                        'user_id'=>$user_id,
                                        'package_id'=>$_POST['package'],
                                        'status'=>$_POST['status'],
                                        'currency'=>$_POST['currency'],
                                        'amount'=>$_POST['amount'],
                                        'walletuse'=>$_POST['walletuse'],
                                        'exp'=>$date1
                                        );
                Transaction::insertUser($transaction_data);
               }
           }
        }
        
        $weres = [['friend_id','=',$user_id]];
        $reffercheck =  Reffer::getbycondition($weres);
        if(count($reffercheck) > 0)
        {
             $packages = Subscription_content::getbycondition(array('id'=>$_POST['package']));
             $amounts=$packages[0]->referrel_amount;
              if(!empty($amounts))
                {  
                    $were3 =  array(
                    'friend_id'=>$user_id,
                    'uid'=>$reffercheck[0]->uid
                    );
                   $amountss =  $reffercheck[0]->amount;
                    $amountss +=$packages[0]->referrel_amount;
                   Reffer::updateoption2(array('amount'=>$amountss),$were3);
                    
                }
        }
        $userid =Session()->get('userid');
        $data['uid'] = $userid;
        $weress= [['id','!=','']];
        $adminemail = Admin::getUsermatch($weress);
        $were= [['id','=', Session()->get('userid')]];
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
             $htmls = $r->name.' has been upgraded a plan, Please visit the following link given below:';
            $header = 'Upgarde a plan';
            $buttonhtml = 'Click here to visit';
            $pass_url  = url('admin/subscription_list'); 
            $path = url('resources/views/email.html');
            $subject = 'Upgarde a plan';
            $to_email=$adminemail[0];
            $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
             $arrays =[
            'w_from' => 'user',
            'from_id' => $r->id,
            'w_to' => 'admin',
            'to_id' => '1',
            'title' => $r->name.' has been upgraded a plan.',
            'description' => $r->name.' has been upgraded a plan.',
            'url' => 'admin/subscription_list',
            'tbl'=>'transaction',
            'status'=>'1'
            ];
            Notification::insertoption($arrays);
        }
        echo '200';
        }
        else{
        } 
  }
  public function update_hours()
  {
     if(session()->exists('user'))
        {
        $user_id=Session()->get('userid');
        $update_question_count=array(
                                    'total_hours'=>$_POST['newtime'],
                                    );
        Hours::updateoption2($update_question_count,array('user_id'=>Session()->get('userid')));
        echo '200';
        }
        else{
            
        }
      
  }
    public function hours_left()
    {
        if(session()->exists('user'))
        {
             $user_id=Session()->get('userid');
             $hours=Hours::getdetailsuser($user_id);
             if($hours->package_id == '1')
             {
                 echo $hours->total_hours;
             
        }
        else{
           echo 'paid';
           die;
           
        }
        }
        
    }
    public function getquestions(Request $request)
    { 
        if(session()->exists('user'))
        { 
            $data = $request->all(); 
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
                $attempt_test_fav = array();
                if(isset($data['attempt_test_fav']) && !empty($data['attempt_test_fav']))
                {
                    $attempt_test_fav = array(
                        'country'=>$data['country'],
                        'state'=>$data['state'],
                        'grade'=>$data['grade'],
                        'year'=>$data['year'],
                        'course'=>$data['course'],
                        'subject'=>$data['subject'],
                        'chapter'=>$data['chapter']
                        );
                    
                    unset($data['attempt_test_fav']);
                }
                ///$result = Pre_questiondetails::search($were);
               
                 $ids='';
                 $result2 = array();
               /* if(count($result) > 1)
                { */
                 // $result[]= Pre_questiondetails::search($were)->first(); 
                $result2 = DB::table('question_answers');
                //$result2 = $result2->join('question_answers', 'question_answers.question_id', '=', 'pre_questiondetails.id');
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
                
               // $result2 = $result2->where('question_answers.status','=','1');
                $result2 = $result2->where('question_answers.qstatus','=','1');
                //$result2 = $result2->where('question_answers.id','=','2883');
                $result2 = $result2->select('question_answers.*');
                $result2 = $result2->orderBy(DB::raw('RAND()'))->distinct('question_answers.id')->limit(10)->get();
               
                //$ids = $result2->id;
                /*}*/
                
                if((count($result2) > 0 && !empty($result2)))
                {   
                  if($ids=='')
                  {
                    $ids = $result2[0]->id;   
                  }
                  $answers = $result2;
                   
                    //$answers = Question_answers::getbycondition(array('question_id'=>$ids,'qstatus'=>'1'));
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
                        'user_id'=>Session()->get('userid'),
                        'test_id'=>'0',
                        'total_questions' => count($totals),
                        'attempt_answer' => '0',
                        'correct_answers' => '0',
                        'all_questions'=>implode(',',$all)
                        ];
                       $pre_que_id =  User_test::insertoption2($input);
                       User_test::updateoption($weres,$pre_que_id);
                       if(count($attempt_test_fav) > 0)
                       { 
                            DB::table('users')
                            ->where('id', Session()->get('userid'))
                            ->update(['attempt_test_fav' => json_encode($attempt_test_fav)]);
                           //User::updateUser(array('attempt_test_fav'=>json_encode($attempt_test_fav)),Session()->get('userid'));
                           
                       }else
                       {
                           DB::table('users')
                            ->where('id', Session()->get('userid'))
                            ->update(['attempt_test_fav' => '']);
                           //User::updateUser(array('attempt_test_fav'=>''),Session()->get('userid')); 
                       }
                       
                    }else
                    {
                     $answers=array(); 
                     $pre_que_id='';
                         if ($request->ajax()) {
                          return response()->json(['html'=>'']);
                        } 
                      die;
                    }
                    if ($request->ajax()) {
                    $view = view('user.attempt_questions', compact('answers','pre_que_id'))->render(); 
                    return response()->json(['html'=>$view], 200, ['Content-type'=> 'application/json; charset=utf-8'], JSON_UNESCAPED_UNICODE);
                    } 
                }else
                { 
                   if ($request->ajax()) {
                      return response()->json(['html'=>'']);
                    }  
                    die('---');
                }
           
        }else
        { 
           return false; 
        }
    }
    
     public static function convert_from_latin1_to_utf8_recursively($dat)
   {
      if (is_string($dat)) {
         return utf8_encode($dat);
      } elseif (is_array($dat)) {
         $ret = [];
         foreach ($dat as $i => $d) $ret[ $i ] = self::convert_from_latin1_to_utf8_recursively($d);

         return $ret;
      } elseif (is_object($dat)) {
         foreach ($dat as $i => $d) $dat->$i = self::convert_from_latin1_to_utf8_recursively($d);

         return $dat;
      } else {
         return $dat;
      }
   }
    
    public function addsugestion(Request $request)
    {
        if(session()->exists('user'))
        {
            $data = $request->all();
            if(isset($data['_token']))
            {
               unset($data['_token']);  
            }
            if(isset($data['suggested_answer']))
            {
               $thisid = $data['id'];
               unset($data['id']);
               if(User_test_answers::updateoption($data,$thisid))
               { 
                    $userid =Session()->get('userid');
                    $data['uid'] = $userid;
                    $weress= [['id','!=','']];
                    $adminemail = Admin::getUsermatch($weress);
                      $were= [['id','=', Session()->get('userid')]];
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
                 $messags['erro']= 101;   
               }else
               {
                   $messags['message'] = "Error to add your suggestion.";
                   $messags['erro']= 202; 
               }
            }else
            {
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
                 $data['user_id']=Session()->get('userid');
                $answer_id =  User_test_answers::insertoption2($data);
                if($answer_id!='')
                {
                    $messags['message'] = "Your Answer Submitted Successfully.";
                    $messags['erro']= 101; 
                    $messags['id']= $answer_id; 
                    
                }else
                {
                    $messags['message'] = "Error to submit your answer.";
                    $messags['erro']= 202; 
                }  
            }
           
        }else
        {
            $messags['message'] = "Session has been expired.";
             $messags['erro']= 202; 
        }
        
         echo json_encode($messags);
                         die;
        
    }
    
    public function attempttest(Request $request)
    {
       if(session()->exists('user'))
        {
        $data = $request->all();
        $data['pre_que_id'];
        $scores = User_test::getbycondition(array('id'=>$data['pre_que_id']));
        $realanswers =  Question_answers::gettotalresult($data['pre_que_id']);
            if ($request->ajax()) {
            $view = view('user.result_summary', compact('realanswers','scores'))->render();
            return response()->json(['html'=>$view]);
            } 
         
        }else
        {
            if ($request->ajax()) {
              return response()->json(['html'=>'session']);
            }  
        }
    }
    
    public function report(Request $request)
    {
        if(session()->exists('user'))
        {  
           $data = $request->all();
        
        if(isset($data['country']) && $data['country']!='')
        {
        $gettags = [['parent', '=', $data['country']],['status', '=', '1']]; 
        $text = 'Select State';
        $states=country::getbycondition($gettags);
        if ($request->ajax()) {
        $view = view('user.states', compact('states','text'))->render();
        return response()->json(['html'=>$view]);
        }
        }
        
        if(isset($data['subject']) && $data['subject']!='')
        {
        $gettags = [['parent', '=', $data['subject']],['type', '=', '2'],['status', '=', '1']]; 
        $text = 'Select Subject';
        $states=course::getbycondition($gettags);
        if ($request->ajax()) {
        $view = view('user.states', compact('states','text'))->render();
        return response()->json(['html'=>$view]);
        }  
        }
        
        if(isset($data['chapter']) && $data['chapter']!='')
        {
        $gettags = [['parent', '=', $data['chapter']],['type', '=', '3'],['status', '=', '1']]; 
        $text = 'Select Chapter';
        $states=course::getbycondition($gettags);
        if ($request->ajax()) {
            $view = view('user.states', compact('states','text'))->render();
            return response()->json(['html'=>$view]);
        }  
        }
            $were = array('status'=>'1');
            $data['grades']= Grades::getbycondition($were);
            $data['years']= Years::getbycondition($were);
            $data['courses']= course::getoption();
            $data['countries']= country::getoption();
            $data['user_id']=Session()->get('userid');
            $data['title']= 'Reports';
            $data['page']='report';
            $data['results']=User_test::getbyconditionpagination(array('user_id'=>$data['user_id']));
            
            
            $data['states'] = array();
            $data['subjects'] = array();
            $data['chapterss'] = array();
            $data['userdatas'] = User::getbycondition(array('id'=>$data['user_id']));
             if(!empty($data['userdatas'][0]->report_test_fav) && $data['userdatas'][0]->report_test_fav!=null)
              {
                  $alls = json_decode($data['userdatas'][0]->report_test_fav);
                  foreach($alls as $k=>$d)
                  {
                      if($k=='country')
                      {
                        $gettagss = [['parent', '=', $d],['status', '=', '1']];
                        $data['states']=country::getbycondition($gettagss);
                      }
                      if($k=='course')
                      {
                         $gettagssub = [['parent', '=', $d],['type', '=', '2'],['status', '=', '1']]; 
                         $data['subjects']=course::getbycondition($gettagssub);
                      }
                      if($k=='subject')
                      {
                         $gettagschap = [['parent', '=', $d],['type', '=', '3'],['status', '=', '1']];
                          $data['chapterss']=course::getbycondition($gettagschap);
                      }
                  }
              }
        
                
                if ($request->ajax()) {
                return view('user.search_report',$data);
                }
           return view('/user/report',$data);
        }else
        {
            return redirect('/login');  
        }
    }
    
    public function getsearch(Request $request)
    {
        if(session()->exists('user'))
        {
            $data = $request->all();
           $were = array();
           $attempt_test_fav = array();
           if(isset($data['report_test_fav']) && !empty($data['report_test_fav']))
                {
                    $attempt_test_fav = array(
                        'country'=>$data['country'],
                        'state'=>$data['state'],
                        'grade'=>$data['grade'],
                        'year'=>$data['year'],
                        'course'=>$data['course'],
                        'subject'=>$data['subject'],
                        'chapter'=>$data['chapter']
                        );
                    
                    unset($data['report_test_fav']);
                }
             //$were= array('status'=>'1');
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
                if(count($were) > 0)
                {   $data['user_id']=Session()->get('userid');
                
                
                $result2 = DB::table('user_test');
                $result2 = $result2->leftjoin('user_test_answers', 'user_test.id', '=', 'user_test_answers.test_id');
                $result2 = $result2->leftjoin('question_answers', 'question_answers.id', '=', 'user_test_answers.question_id');
                //$result2 = $result2->join('pre_questiondetails', 'pre_questiondetails.id', '=', 'question_answers.question_id');
                
                if(isset($data['country']) && $data['country']!='')
                {
                $result2 = $result2->where('user_test.country', $data['country']);
                }
                if(isset($data['state']) && $data['state']!='')
                {
                $result2 = $result2->where('user_test.state', $data['state']);
                }
                if(isset($data['course']) && $data['course']!='')
                {
                $result2 = $result2->where('user_test.course', $data['course']);
                }
                if(isset($data['grade']) && $data['grade']!='')
                {
                $result2 = $result2->where('user_test.grade', $data['grade']);
                }
                if(isset($data['year']) && $data['year']!='')
                {
                $result2 = $result2->where('user_test.year', $data['year']);
                }
                if(isset($data['subject']) && $data['subject']!='')
                {
                $result2 = $result2->where('user_test.subject', $data['subject']);
                }
                if(isset($data['chapter']) && $data['chapter']!='')
                {
                $result2 = $result2->where('user_test.chapter', $data['chapter']);
                }
                $result2 = $result2->where('user_test.user_id',$data['user_id']);
                //$result2 = $result2->where('user_test_answers.test_id',$id1);
                $results = $result2->distinct()->select('user_test.*');
                $results = $result2->orderBy('user_test.id', 'desc')->get();
            
                
                        if(count($attempt_test_fav) > 0)
                        {
                             DB::table('users')
                            ->where('id', Session()->get('userid'))
                            ->update(['report_test_fav' => json_encode($attempt_test_fav)]);
                            
                        }else
                        {
                            DB::table('users')
                            ->where('id', Session()->get('userid'))
                            ->update(['report_test_fav' => '']);
                        }
                        if(count($results) > 0 && $results[0]->id!='')
                        {
                           
                        }else
                        {
                           $results=array();
                        }
                }else
                {  
                    $were = array('status'=>'1');
                    $data['grades']= Grades::getbycondition($were);
                    $data['years']= Years::getbycondition($were);
                    $data['courses']= course::getoption();
                    $data['countries']= country::getoption();
                    $data['user_id']=Session()->get('userid');
                    
                    $results=User_test::getbyconditionpagination(array('user_id'=>$data['user_id']));
                }
                
                if(count($results) > 0)
                {  
                   
                    if ($request->ajax()) {
                    $view = view('user.search_report', compact('results'))->render();
                    return response()->json(['html'=>$view]);
                    } 
                }else
                {
                   if ($request->ajax()) {
                     $view = view('user.search_report', compact('results'))->render();
                      return response()->json(['html'=>$view]);
                    }  
                }
           
        }else
        {
           return false; 
        }
    }
    
    public function test_detail(Request $request,$id='')
    {
      if(session()->exists('user'))
        {     /*SET NAMES UTF8;*/
            $data = $request->all();
            $title = 'Test Detail';
           // $data['scores'] = User_test::getbycondition(array('id'=>$data['pre_que_id']));
           // $data['realanswers'] =  Question_answers::gettotalresultwithpagination2($id);
           $user_id=Session()->get('userid');
           $realanswers2 =  User_test::getbycondition(array('id'=>$id,'user_id'=>$user_id));
           

           $realanswers = array();

           if(count($realanswers2) > 0)
           {  $alls =  explode(',',$realanswers2[0]->all_questions);
           
               foreach($alls as $realanswer)
               {
                    $data = DB::table('question_answers');
                    //$data->leftJoin('user_test_answers', function ($data) use ($user_id) {
                    $data->join('user_test_answers', 'user_test_answers.question_id', '=', 'question_answers.id');
                   // ->where('user_test_answers.user_id', '=', $user_id);
                   // });
                   // $data->leftJoin('user_test', function ($data) use ($id) {
                    $data->join('user_test', 'user_test.id', '=', 'user_test_answers.test_id')
                    ->where('user_test.id',$id);
                   // });
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
                if ($request->ajax()) {
                return view('user.test_detail_presult',compact('realanswers','title'));
                }
           }else
           {
               $data['realanswers'] = array();
           }
                 $title= 'Detail';
            $page ='test_detail';
            return view('user.test_detail',compact('realanswers','title','page'));
        }
        else
        {
            return redirect('/login');
        }
    }
    
     public function referral(Request $request)
    {
      if(session()->exists('user'))
        {
            $data['title']= 'Referral';
            $data['page']='referral';
             $userid =Session()->get('userid');
            $were = array('uid'=>$userid);
            $data['reffered'] = Reffer::getbycondition($were);
            $data['code']= User::getbycondition(array('id'=>$were,'status'=>'1'));
            return view('user.referral',$data);
        }
        else
        {
            return redirect('/login');
        }
    }
    
    public function wallet(Request $request)
    {
      if(session()->exists('user'))
        {
            $data['title']= 'Wallet';
            $data['page']='wallet';
            $userid =Session()->get('userid');
            $were = array('uid'=>$userid);
            $data['applyrequests'] = Withdraw::getbycondition($were);
            $were = array('uid'=>$userid,'status'=>'2');
            $data['applyrequests2'] = Withdraw::getbycondition($were);
            $were2 = array('uid'=>$userid,'status'=>'1');
            $data['reffered'] = Reffer::getbycondition($were2);
            $data['transactions'] = Transaction::getbycondition(array('user_id'=>$userid));
            $data['walletamount']=0;
            $data['reffer_amount'] =0;
            
            
            foreach($data['reffered'] as $reffer)
            {
                if(!empty($reffer->amount))
                {
                    $data['walletamount'] += $reffer->amount;
                    $data['reffer_amount'] += $reffer->amount;
                }
            }
            
             foreach($data['transactions'] as $reffers)
            {
                if(!empty($reffers->walletuse))
                {
                 $data['walletamount'] -= $reffers->walletuse;
                }
            }
            
            $data['withdrwaamount']=0;
            foreach($data['applyrequests2'] as $reffers)
            {
                if(!empty($reffers->amount))
                {
                    $data['walletamount'] -= $reffers->amount;
                    $data['withdrwaamount'] +=$reffers->amount;
                }
            }
            
            if(count($data['reffered']) == 0 || $data['walletamount'] < 0)
            {
             $data['walletamount']=0;   
            }
           
            return view('user.wallet',$data);
        }
        else
        {
            return redirect('/login');
        }
    }
    
    public function applyamount(Request $request)
    {
         $messags= array();
         if(session()->exists('user'))
        {
            if($request->isMethod('post'))
            {
              $data = $request->all();
              if(isset($data['_token']))
              {
                  unset($data['_token']);
              }
               $userid =Session()->get('userid');
               $data['uid'] = $userid;
                $weress= [['id','!=','']];
                $adminemail = Admin::getUsermatch($weress);
               $were= [['id','=', Session()->get('userid')]];
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
                        $messags['erro']= 101; 
            }else
            {
                $messags['message'] = "Amount is required.";
                $messags['erro']= 202;  
            }
        }else
        {
            $messags['message'] = "Session has been expired.";
            $messags['erro']= 202; 
        }
        
         echo json_encode($messags);
                         die;
        
    }
    
     public function notification(Request $request)
    {    
       if(session()->exists('user'))
      {
           $userid =Session()->get('userid');
          $were = [['w_to','=','user'],['status','!=','2'],['to_id','=',$userid]];
        $data['notifications'] = Notification::getbycondition2($were);
        $data['title']='Notifications-Multiple Choice Online';
        $data['page']='notification';
         if ($request->ajax()) {
          return view('user.notification2',$data);
        }
        return view('/user/notification',$data);
      }
      else
      {
           return redirect('/login');
      } 
    }
    
     public function deletenotifications(Request $request)
    {
         $messags = array();
       if(session()->exists('user'))
      {
            if($request->isMethod('post'))
            {
                $data = $request->all(); 
             
                foreach($data['favorite'] as $d)
                {
                  Notification::updateoption(array('status'=>'2'),$d);
                }
                $messags['message'] = "Notification Deleted Successfully.";
                 $messags['erro']= 101;
            }else
            {
              $messags['message'] = "No notification selected.";
              $messags['erro']= 202;
            }
      }
      else
      {
            $messags['message'] = "Invalid request.";
            $messags['erro']= 202;   
      } 
        echo json_encode($messags);
            die;
    }
    
    public function adddropdowns(Request $request)
    {
        $messags = array();
       if(session()->exists('user'))
      {
            if($request->isMethod('post'))
            {
                $data = $request->all(); 
                if($data['type'] == 'country')
                {
                    $datacountry = country::getoptionmatch([['name','=',$data['name']],['status','!=','2'],['parent','=','0']]);

                    if(!empty($datacountry) && count($datacountry) > 0)
                    {
                        $messags['message'] = "Country already exists.";
                        $messags['erro']= 202;  
                    }else
                    {
                       $id = country::insertoption2(array('name'=>$data['name'],'status'=>'1','parent'=>'0'));
                        $messags['message'] = "Country has been added successfully.";
                        $messags['erro']= 101;
                        $messags['id']= $id;
                        $states = country::getoption();
                        $text = 'Select Country';
                        $view = view('user.states', compact('states','text'))->render();
                        $messags['id']= $id;
                        $messags['view']= $view;
                        $messags['type']= 'country';
                    }
                }else if($data['type'] == 'state')
                {
                  $datacountry = country::getoptionmatch([['name','=',$data['name']],['status','!=','2'],['parent','=',$data['country']]]);
                    if(!empty($datacountry) && count($datacountry) > 0)
                    {
                        $messags['message'] = "State already exists.";
                        $messags['erro']= 202;  
                    }else
                    {
                        $id = country::insertoption2(array('name'=>$data['name'],'status'=>'1','parent'=>$data['country']));
                        $messags['message'] = "State has been added successfully.";
                        $messags['erro']= 101;
                        $messags['id']= $id;
                        $gettags = [['parent', '=', $data['country']],['status', '=', '1']]; 
                        $text = 'Select State';
                        $states=country::getbycondition($gettags);
                        $view = view('user.states', compact('states','text'))->render();
                        $messags['view']= $view;
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
                        $messags['erro']= 202;  
                    }else
                    {
                        $id = Grades::insertoption2(array('name'=>$data['name'],'status'=>'1'));
                        $messags['message'] = "Grade has been added successfully.";
                        $messags['erro']= 101;
                        $messags['id']= $id;
                        $were = array('status'=>'1');
                        $text = 'Select Grade/Level';
                        $states=Grades::getbycondition($were);
                        $view = view('user.states', compact('states','text'))->render();
                        $messags['view']= $view;
                        $messags['type']= 'grade';
                    }  
                }
                else if($data['type'] == 'year')
                {
                  $datacountry = Years::getoptionmatch([['name','=',$data['name']],['status','!=','2']]);
                    if(!empty($datacountry) && count($datacountry) > 0)
                    {
                        $messags['message'] = "Year already exists.";
                        $messags['erro']= 202;  
                    }else
                    {
                        $id = Years::insertoption2(array('name'=>$data['name'],'status'=>'1'));
                        $messags['message'] = "Year has been added successfully.";
                        $messags['erro']= 101;
                        $messags['id']= $id;
                        $were = array('status'=>'1');
                         $text = 'Select Year';
                        $states= Years::getbycondition($were);
                        $view = view('user.states', compact('states','text'))->render();
                        $messags['view']= $view;
                        $messags['type']= 'year';
                    }  
                }
                else if($data['type'] == 'course')
                {
                  $datacountry = course::getoptionmatch([['name','=',$data['name']],['parent','=','0'],['type','=','1'],['status','!=','2']]);
                    if(!empty($datacountry) && count($datacountry) > 0)
                    {
                        $messags['message'] = "Course already exists.";
                        $messags['erro']= 202;  
                    }else
                    {
                       $id = course::insertoption2(array('name'=>$data['name'],'parent'=>'0','status'=>'1','type'=>'1'));
                        $messags['message'] = "Course has been added successfully.";
                        $messags['erro']= 101;
                        $messags['id']= $id;
                         $text = 'Select Course';
                        $states= course::getoption();
                        $view = view('user.states', compact('states','text'))->render();
                        $messags['view']= $view;
                        $messags['type']= 'course';
                    }  
                }
                else if($data['type'] == 'subject')
                {
                  $datacountry = course::getoptionmatch([['name','=',$data['name']],['parent','=',$data['course']],['type','=','2'],['status','!=','2']]);
                    if(!empty($datacountry) && count($datacountry) > 0)
                    {
                        $messags['message'] = "Subject already exists.";
                        $messags['erro']= 202;  
                    }else
                    {
                       $id =  course::insertoption2(array('name'=>$data['name'],'parent'=>$data['course'],'status'=>'1','type'=>'2'));
                        $messags['message'] = "Subject has been added successfully.";
                        $messags['erro']= 101;
                        $messags['id']= $id;
                        $gettags = [['parent', '=', $data['course']],['type', '=', '2'],['status', '=', '1']]; 
                        $text = 'Select Subject';
                        $states=course::getbycondition($gettags);
                        $view = view('user.states', compact('states','text'))->render();
                        $messags['view']= $view;
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
                        $messags['erro']= 202;  
                    }else
                    {
                       $id =  course::insertoption2(array('name'=>$data['name'],'parent'=>$data['subject1'],'status'=>'1','type'=>'3'));
                        $messags['message'] = "Chapter has been added successfully.";
                        $messags['erro']= 101;
                        $messags['id']= $id;
                         $gettags = [['parent', '=', $data['subject1']],['type', '=', '3'],['status', '=', '1']]; 
                        $text = 'Select Chapter';
                        $states=course::getbycondition($gettags);
                        $view = view('user.states', compact('states','text'))->render();
                        $messags['view']= $view;
                        $messags['type']= 'chapter';
                        $messags['parents']= $data['subject1'];
                    }  
                }
                
            }else
            {
              $messags['message'] = "Error to add ".lcfirst($data['id']);
              $messags['erro']= 202;
            }
      }
      else
      {
            $messags['message'] = "Invalid request.";
            $messags['erro']= 202;   
      } 
        echo json_encode($messags);
            die;
    }
    
    public function getview(Request $request)
    {
        $data = $request->all(); 
        if(isset($data['type']) && !empty($data['type']))
        {  $type = $data['type'];
            $id = $data['id'];
            if($data['type']=='country' || $data['type']=='course' || $data['type']=='grade' || $data['type']=='year')
            {  
                if ($request->ajax()) {
                $view = view('user.dropdown', compact('type','id'))->render();
                return response()->json(['html'=>$view]);
                }
            }else if($data['type']=='state')
            {  $countries = country::getoption();
               if ($request->ajax()) {
                $view = view('user.dropdown', compact('type','id','countries'))->render();
                return response()->json(['html'=>$view]);
                } 
            }
            else if($data['type']=='subject')
            {  $courses = course::getoption();
               if ($request->ajax()) {
                $view = view('user.dropdown', compact('type','id','courses'))->render();
                return response()->json(['html'=>$view]);
                } 
            }
            else if($data['type']=='chapter')
            {  $courses = course::getoption();
               if ($request->ajax()) {
                $view = view('user.dropdown', compact('type','id','courses'))->render();
                return response()->json(['html'=>$view]);
                } 
            }
        }
    }
    
    public function cancelpaypal($id='')
    {
        if($id!='')
        {
            $connections = Test::getall('users',array('id'=>Session()->get('userid'),'payout_item_id'=>$id));
            if(count($connections) > 0)
            {
                if(Options::getoptionmatch3('paypal_mode')=='0')
                {  
                    $url1 = 'https://api.sandbox.paypal.com/v1/oauth2/token';
                    $url2 = 'https://api.sandbox.paypal.com/v1/payments/payouts';
                    $client_id = Options::getoptionmatch3('paypal_client_id_sandbox');
                    $client_secrate = Options::getoptionmatch3('paypal_client_secrate_sandbox');
                }else
                {   
                    $url1 = 'https://api.paypal.com/v1/oauth2/token';
                    $url2 = 'https://api.paypal.com/v1/payments/payouts';
                    $client_id = Options::getoptionmatch3('paypal_client_id_live');
                    $client_secrate = Options::getoptionmatch3('paypal_client_secrate_live');
                }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.sandbox.paypal.com/v1/oauth2/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_USERPWD, 'AUy8QI7ABm9Mxtx7emYitH49UgKsRh2hEcDCsLOqY--cvXck9Wqqf_0zurTmgQzAoEhZNx28EM6E01hD:EIynqMz02rCAnum2ZRXrp-F12yNmZ3sP-L5MAZysP0raTx6C6waz5m7KRlJfmxeqekb4IzohWDL5i3rG');
            $headers = array(); 
            $headers[] = 'Accept: application/json';
            $headers[] = 'Accept-Language: en_US';
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $data = json_decode($result,true);
            if (curl_errno($ch)) {
           // echo 'Error:' . curl_error($ch);
             return redirect()->back()->with('error', 'Error:' . curl_error($ch));
            }
            curl_close($ch); 
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api.sandbox.paypal.com/v1/payments/payouts/'.$id.'?fields=batch_header');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $headers = array();
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Authorization: Bearer '.$data['access_token'];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $result = curl_exec($ch);
                $result2 = json_decode($result,true);
                if (curl_errno($ch)) {
                //echo 'Error:' . curl_error($ch);
                 return redirect()->back()->with('error', 'Error:' . curl_error($ch));
                }
                curl_close($ch);  
                echo '<pre>'; print_r($result2); echo '</pre>'; 
                die;
                echo '<pre>'; print_r($datak); echo '</pre>'; die;
            
                // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api.sandbox.paypal.com/v1/payments/payouts-item/'.$id.'/cancel');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                $headers = array();
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Authorization: Bearer '.$data['access_token'];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $result = curl_exec($ch);
                $results = json_decode($result,true);
                if (curl_errno($ch)) {
               // echo 'Error:' . curl_error($ch);
                return redirect()->back()->with('error', 'Error:' . curl_error($ch));
                }
                curl_close ($ch);
                echo '<pre>'; print_r($results); die; 
                if($results['status'] == 'succeeded') {
                    User::updateUser(array('payout_item_id'=>null),Session()->get('userid'));
                   //return redirect()->back()->with('success', 'Payment has been canceled payout.');  
                }else
                {
                // return redirect()->back()->with('error', 'error to cancel payout.');  
                }
            }else
            {
                // return redirect()->back()->with('error', 'Invalid Request.');  
            }
        }else
        {
            //return redirect()->back();
        }
    }
    
    public function cancelstripe($id='')
    {
       if($id!='')
       {
            $client_id1=''; $client_secrate1='';
            if(Options::getoptionmatch3('stripe_mode')=='0')
            {  
             $client_id1 = Options::getoptionmatch3('stripe_secrate_key_sandbox');
             $client_secrate1 = Options::getoptionmatch3('stripe_publish_key_sandbox');
            }else
            {
             $client_id1 = Options::getoptionmatch3('stripe_secrate_key_live');
             $client_secrate1 = Options::getoptionmatch3('stripe_publish_key_live');
            }
             $options = Options::getoption();
             $api_key = $client_secrate1;
            $curl = curl_init();
                curl_setopt_array($curl, [
                CURLOPT_URL => 'https://connect.stripe.com/oauth/deauthorize',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ["Authorization: Bearer $api_key"],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                'client_id' => 'ca_FRKhNv0IlTlsYCOpjsouZ7K5cleyfbl1',
                'stripe_user_id' => $id,
            ])
            ]);
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response,true);
            if(isset($response->error))
            {
              return redirect()->back()->with('error', $response->error);  
            }
            else{
              User::updateUser(array('bank_acc_id'=>null),Session()->get('userid'));
              return redirect()->back()->with('success', 'Account has been removed successfully.');  
            }
            return redirect()->back()->with('error', 'This application is not connected to stripe account '.$id.', or that account does not exist.');  
       }else
       {
           return redirect()->back()->with('error', 'Invalid Request.');  
       }
    }
    
}
