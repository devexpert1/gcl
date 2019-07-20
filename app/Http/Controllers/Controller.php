<?php

namespace App\Http\Controllers;
use DB;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Notification;
use App\User;
use App\Hours;
use App\Question_answers;
use App\Transaction;
use App\Reffer;
use App\Subscription_content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Redirect;
use Session;
use App\Options;
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
  
     public function selectData($table, $where='', $pagination='',$orderby='',$take='')
    {
        $results = DB::table($table);

        if($where){
            $results = $results->where($where);
        }
		 if($orderby){
            $results = $results->orderby($orderby,'desc');
        }
        
        if($pagination){
            $results = $results->paginate($pagination);
        }
        else{
            $results = $results->get();
        }

        return $results;
    }
    
    
    
     public function insertData($table, $data)
    {
    $insert_id =  DB::table($table)
            ->insertGetId($data);
   return  $insert_id;
         
    }

    public function deleteData($table, $where)
    {
        DB::table($table)
            ->where($where)
            ->delete();
    }
 public function updateData($table, $where, $data)
    {
		DB::table($table)
            ->where($where)
            ->update($data);
    }
     public function updateData3($table, $id, $column, $value)
    {
        	DB::table($table)
            ->where('id', '=', $id)
            ->update(array($column=>$value));
             return 1;
    }
    public function updateData2($table, $id, $column, $value,$uid)
    {  $ddddd = $id;
		DB::table($table)
            ->where('id', '=', $id)
            ->update(array($column=>$value));
            if($table=='question_answers' && $column=='answer')
            { 
                DB::table('user_test_answers')
                ->where('id', '=', $uid)
                ->update(array('approved_yet'=>'1'));
                return 1; die; 
            }
            
            if($uid!='0' )
            {
                            $were= [['id','=', $uid]];
                            $user = User::getbycondition($were);
                            foreach($user as $u){
                            $r = $u;
                            }
                            if(count($user)!=0)
                            {
                                if($value=='1')
                                {
                                     $action = 'approved';
                                }else
                                {
                                     $action = 'rejected';
                                }
                                $id = $r->id; 
                                $name = $r->name;
                                $hash    = md5(uniqid(rand(), true));
                                $string  = $id."&".$hash;
                                 $iv = base64_encode($string);
                                $htmls = 'Admin '.$action.' your question, Please visit the following link given below:';
                                $header = 'Amount Withdraw Request';
                                $buttonhtml = 'Click here to visit';
                                $pass_url  = url('user/viewquestion/'.$ddddd); 
                                $path = url('resources/views/email.html');
                                $subject = "Amount Withdraw Request";
                                $to_email=$r->email;
                                $cur_year = date('Y');
                                $email_path    = file_get_contents($path);
                                $email_content = array('[name]','[pass_url]','[htmls]','[buttonhtml]','[header]','[cur_year]');
                                $replace  = array($name,$pass_url,$htmls,$buttonhtml,$header,$cur_year);
                                $message = str_replace($email_content,$replace,$email_path);
                                 $header = 'From: '.env("IMAP_HOSTNAME_TEST").'' . "\r\n";
                                $header .= "MIME-Version: 1.0\r\n";
                                $header .= "Content-type: text/html\r\n";
                                $retval = mail($to_email,$subject,$message,$header); 
                                 $arrays =[
                                'w_from' => 'admin',
                                'from_id' => '1',
                                'w_to' => 'user',
                                'to_id' => $r->id,
                                'title' => 'Admin '.$action.' your question',
                                'description' => '<b> Admin </b> '.$action.' your question',
                                'url' => 'user/viewquestion/'.$ddddd,
                                'tbl'=>'pre_questiondetails',
                                'status'=>'1'
                                ];
                                Notification::insertoption($arrays);
                                $vendors = User::where(array('id'=>$r->id))->first();
                                if($vendors->package_id == '1')
                                {
                                        if($action == 'approved')
                                        {
                                           $getall =  Question_answers::getbycondition(array('user_id'=>$r->id));
                                            $getall2 =  Question_answers::getbycondition(array('user_id'=>$r->id,'qstatus'=>'1'));
                                                $update_question_count=array(
                                                  'total_questions_uploaded'=>count($getall),
                                                );
                                             Hours::updateoption2($update_question_count,array('user_id'=>$r->id));
                                             $res=Hours::getdetailsuser($r->id);
                                               if(!empty($res))
                                               {
                                                   if($res->package_id == '1')
                                                   {
                            
                                                           if(count($getall2) > $res->apporved_questions || (count($getall2) == $res->apporved_questions && count($getall2) > 10))
                                                           {  
                                                            $count = count($getall2) - $res->apporved_questions;
                                                               if($count > 10  || $count == '10')
                                                               {
                                                                //$timestamp = strtotime($res->total_hours) + 60*60;
                                                                $timestamp = strtotime($res->total_hours."+ 10 minutes");
                                                                $time = date('H:i:s', $timestamp);
                                                                   $update_question_count=array(
                                                                        'current_question_count'=>'0',
                                                                        'apporved_questions'=>$res->apporved_questions + '10',
                                                                         'total_hours'=>$time,
                                                                        /*'total_hours'=>$time,*/
                                                                          
                                                                        );
                                                               }
                                                           } 
                                                         
                                                   }
                                                   Hours::updateoption2($update_question_count,array('user_id'=>$r->id));
                                                   
                                               }
                                        }
                                }
                               
                            }
            }
            return 1;
    }
  public function checkExistsAjaxUpdate($table, $id, $column, $value)
    {
        echo DB::table($table)
                ->where($column, $value)
                ->where('id', '!=', $id)
                ->where('status', '!=', '2')
                ->exists();
    }
    public function checkExists($table, $where)
    {
        return DB::table($table)
                ->where($where)
                 ->where('status','1')
                ->exists();
    }
     public function checkExistsAjax($table, $column, $value)
    {
        echo DB::table($table)
                ->where($column, $value)
                ->where('status','1')
                ->exists();
    }
 public function reset_password(Request $request, $uniqid)
    {

        $table = "users";
        $where = ["uniqid"=>$uniqid];
        $user  = $this->selectData($table, $where);

        if( $user->isEmpty() )
            die('This link has been expired');

        if ($request->isMethod('post')) {
            // echo '<pre>'; print_r($_POST); die; 

            $table = "users";

            $password = Hash::make( $request->input('password') );
            $data = ['password' => $password, "uniqid"=>null];
            $where = [
                        ['uniqid', '=', $uniqid ],
                     ];

            $this->updateData($table, $where, $data);

            echo 1; die;
        }

        return view('common.reset-password');
    }
    
    public function ipn(Request $request,$transaction_id = null)
  {
 $postdata = file_get_contents('php://input'); 
 $file = fopen(public_path("test.txt"),"w");
echo fwrite($file,$postdata);
fclose($file);
$datae = array('destinations'=>$postdata);
            $insert_id =  DB::table('days')
            ->insertGetId($datae);
$datas = json_decode($postdata, true);
/*$datass = array('pictures'=>$datas['resource']['id'],'updated_at'=>date("Y-m-d H:i:s", strtotime($datas['create_time'])));
            $insert_id =  DB::table('days')
            ->insertGetId($datass);
            $datass = array('pictures'=>$datas['resource']['plan']['curr_code'],'updated_at'=>date("Y-m-d H:i:s", strtotime($datas['create_time'])));
            $insert_id =  DB::table('days')
            ->insertGetId($datass);
            $datass = array('pictures'=>$datas['resource']['plan']['payment_definitions'][0]['amount']['value'].'-'.$datas['resource']['plan']['curr_code'],'updated_at'=>date("Y-m-d H:i:s", strtotime($datas['create_time'])));
            $insert_id =  DB::table('days')
            ->insertGetId($datass);*/
 
       $data['transaction_id'] = $datas['resource']['billing_agreement_id'];
       $alldata = Transaction::getbycondition(array('transaction_id'=>$data['transaction_id']));
       $lastpakcgae = Hours::getbycondition([['user_id','=',$alldata[0]->user_id],['package_id','!=','1']]);
       if(count($alldata) > 0)
       {
                if($alldata[0]->package_id == '2')
                {
                if(count($lastpakcgae) > 0 )
                 {  
                    $date = strtotime($lastpakcgae[0]->expiry);
                 }else
                 {
                    $start_date =strtotime(date('Y-m-d'));
                    $start_date = date('Y-m-d',strtotime("+7 day",$start_date));  
                    $date = strtotime($start_date);
                    //$start_date =date('Y-m-d'); 
                    //$date = strtotime($start_date);
                 }
                $date = date('Y-m-d',strtotime("+1 month", $date));  
                }
                
                if($alldata[0]->package_id == '3')
                {
                    if(count($lastpakcgae) > 0 )
                 {  
                    $date = strtotime($lastpakcgae[0]->expiry);
                 }else
                 {
                    $start_date =date('Y-m-d');  
                    $start_date = date('Y-m-d',strtotime("+7 day",$start_date));  
                    $date = strtotime($start_date);
                    //$start_date =date('Y-m-d'); 
                    //$date = strtotime($start_date);
                 }
                $date = date('Y-m-d',strtotime("+1 year", $date));   
                }
                $transaction_data=array(
                'transaction_id'=>$data['transaction_id'],
                'user_id'=>$alldata[0]->user_id,
                'package_id'=>$alldata[0]->package_id,
                'status'=>'completed',
                'currency'=>$alldata[0]->currency,
                'amount'=>$alldata[0]->amount,
                'exp'=>$date,
                'recurring'=>'1'
                );
                Transaction::insertUser($transaction_data);
                $hours_data=array(
                'package_id'=>$alldata[0]->package_id,
                'total_questions_uploaded'=>'0',
                'total_hours'=>'00:00:00',
                'expiry'=>$date,
                'current_question_count'=>0,
                );
                 Hours::updateoption2($hours_data,array('user_id'=>$alldata[0]->user_id));
                 $weres = [['friend_id','=',$alldata[0]->user_id]];
                $reffercheck =  Reffer::getbycondition($weres);
                if(count($reffercheck) > 0)
                {
                     $packages = Subscription_content::getbycondition(array('id'=>$alldata[0]->package_id));
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
            $data = array('start'=>'1');
            $insert_id =  DB::table('days')
            ->insertGetId($data);
            echo  $insert_id; die; 
       }
       
  }

  public function stripecoonect(Request $request)
  {
       $client_id1=''; $client_secrate1='';
         if(Options::getoptionmatch3('stripe_mode')=='0')
        {  
         $client_secrate1 = Options::getoptionmatch3('stripe_secrate_key_sandbox');
          $client_id1 = Options::getoptionmatch3('stripe_publish_key_sandbox');
        }else
        {
             $client_secrate1 = Options::getoptionmatch3('stripe_secrate_key_live');
             $client_id1 = Options::getoptionmatch3('stripe_publish_key_live');
        }
     // $postdata = file_get_contents('php://input'); 
     $data =  $request->all();
    //  echo '----------------<pre>'; print_r($request->all());
      if(!empty($data)){
	/*	$stripe_keys = json_decode($this->GetStripeSettings());
		if($stripe_keys->data->payment_type == 'live'){
			$stripe_secret = $stripe_keys->data->live_sk;
		}
		else{
			$stripe_secret = $stripe_keys->data->sandbox_sk;
		}*/
		$url = 'https://connect.stripe.com/oauth/token';
	 	$post = [
		    'client_secret' => $client_secrate1,
		    'code' => $data['code'],
		    'grant_type'   => 'authorization_code',
		];
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$response = curl_exec($ch);
		curl_close($ch);
		$response = json_decode($response);
		if(isset($response->error)){
			 return redirect('/myprofile')->with('error','success with error');
		}
		else{
			if(isset($response->stripe_user_id)){
				User::updateUser(array('bank_acc_id'=>$response->stripe_user_id,'gateway_type'=>'1'),Session()->get('userid'));
			
				 return redirect('/myprofile')->withSuccess( 'Your account connected with stripe successfully!!' );
			}
			else{
			   
			  return redirect('/myprofile')->with('error','success but stripe acc id empty');
			}
			
		}
	}
	else{
	   
	    return redirect('/myprofile')->with('error','request empty');
	}
      die('done'); 
  }
  
  public function payplapayout2(Request $request)
  {
      $id = time().uniqid();
      
$datam = '{
  "sender_batch_header": {
    "sender_batch_id": "'.$id.'",
    "email_subject": "You have a payout!",
    "email_message": "You have received a payout! Thanks for using our service!"
  },
  "items": [
    {
      "recipient_type": "EMAIL",
      "amount": {
        "value": "0.01",
        "currency": "AUD"
      },
      "note": "Thanks for your patronage!",
      "sender_item_id": "201403140001",
      "receiver": "29userdemo-facilitator@gmail.com"
    }
  ]
}';


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
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch); 

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.sandbox.paypal.com/v1/payments/payouts/15624020715d205d174aa3c?fields=batch_header');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer '.$data['access_token'];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);  
    echo '<pre>'; print_r($result); echo '</pre>'; 
    die;

    $datad1 = $this->curl_run($datam,'https://api.sandbox.paypal.com/v1/payments/payouts',$data['access_token']);
    if($datad1['data']==1)
    {
     echo 'Third Step is this ';print_r($datad1['value']);echo '';
    }else
    {
     return 'error';
    }
      echo '11111111'; die; 
  }
  
  public function curl_run($data,$url,$token)
    {
        
        
        
        
        
           $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/payments/payouts");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Authorization: Bearer ".$token));

$result = curl_exec($ch);


if(empty($result))die("Error: No response.");
else
{
    $json = json_decode($result);
    print_r($json);
}
        die;
        
       
        
        
        
        
        
        
        
        
        
        
        
        
        
        
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, 'https://api.sandbox.paypal.com/v1/payments/payouts');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, 1);
            
            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: Bearer '.$token;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            echo '<pre>'; print_r($result); die; 
            if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
            }
            curl_close($ch);

        
        
        
      /*  $aut = array('Authorization: Bearer '.$token, 'Content-Type: application/json');
            $ch = curl_init();		
            curl_setopt($ch, CURLOPT_URL, $url);		
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aut);	
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            if(!empty($data))
            {
              curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            $datad = json_decode(curl_exec($ch), true);echo '';print_r($datad);echo '';
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if($http_code == 200)
            {
                return array('data'=>1,'value'=>$datad);
            }
            else if($http_code == 404)
            {
            return array('data'=>-1);
            }
            else
            {
              return array('data'=>0);
            }*/
            //echo '$http_code '.$http_code3;
    }
	
    
    
    
}
