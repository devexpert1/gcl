<?php

namespace App\Http\Controllers;
use Session;
use Config;
use DB;
use Mail;
use App\Admin;
use App\Options;
use App\Faqs;
use App\User;
use App\country;
use App\course;
use App\Hours;
use App\Years;
use App\User_test;
use App\Pre_questiondetails;
use App\Subscription_content;
use App\Grades;
use App\Transaction;
use App\Withdraw;
use App\Subscribers;
use App\Reffer;
use Redirect;
use Illuminate\Http\Request;
use Auth;


class HomeController extends Controller
{
  
   
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {    DB::statement("SET NAMES 'utf8'");
            $this->middleware(function ($request, $next){
             if(Session()->exists('user'))
            { 
           
              $userid =Session()->get('userid');
              $were= [['id','=',$userid],['status','=','1']];
              $exists= User::getUsermatch($were);
              if(count($exists) > 0)
              { 
                  $exists2= User::getbycondition($were);
                  if($exists2[0]->refferal_code =='')
                  {  User::updateUser(array('refferal_code'=>time().uniqid(rand())),$userid);
                      
                  }
                $user_id = session('user_id');
                return $next($request);
              }if(count($exists) == 0)
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
                  

        
   
       ///$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
            $data['user'] = Auth::user();
            $users = Auth::user();
            $data['page']='home';
        if(!empty($data['user']) && $users->id !='' && isset($users->id))
        {  
            $userdata = array(
            'id'=> $users->id ,
            'name' => $users->name ,
            'lname' => $users->lname ,
            'email' => $users->email ,
            );
            Session::put('user',$userdata);
            Session::put('userid', $users->id);
            Session::save(); 
            $data['title']='Home';
             $id=Session()->get('userid');
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
                 //array_push($data['questions'], $getids);
                 
             }
             $data['questions']=User_test::getbyconditionpagination2(array('user_id'=>$id));
             foreach($data['questions'] as $key=>$dd)
            {    
                  $data['questions'][$key]['score'] = $dd['correct_answers'].'/'.$dd['total_questions'];
            } 
            $data['totalquestion'] = $totalquestion; 
            $data['correctanswers'] = $correctanswers;
            $gettags = [['type', '=', '2'],['status', '=', '1']]; 
            $gettags2 = [['type', '=', '3'],['status', '=', '1']]; 
            $data['subject']=course::getbycondition($gettags);
            $data['chapter']=course::getbycondition($gettags2);
            $data['courses']= course::getoption();
          return view('/user/home',$data);
        }else if(session()->exists('user'))
        {
        $userid =Session()->get('userid');
         $data['title']='Home';
          $id=Session()->get('userid');
            $were = [['user_id','=',$id],['status', '!=','2' ]];
           // $data['questions'] = Pre_questiondetails::getoption($were);
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
                 //array_push($data['questions'], $getids);
                 
             }
             $data['questions']=User_test::getbyconditionpagination2(array('user_id'=>$id));
             foreach($data['questions'] as $key=>$dd)
            {    
                  $data['questions'][$key]['score'] = $dd['correct_answers'].'/'.$dd['total_questions'];
            } 
           
            $data['totalquestion'] = $totalquestion; 
            $data['correctanswers'] = $correctanswers;
            $gettags = [['type', '=', '2'],['status', '=', '1']]; 
            $gettags2 = [['type', '=', '3'],['status', '=', '1']]; 
            $data['subject']=course::getbycondition($gettags);
            $data['chapter']=course::getbycondition($gettags2);
            $data['courses']= course::getoption();
        $data['user'] = User::getbycondition(array('id'=>$userid));
         return view('/user/home',$data);   
        }else
        {
            return redirect('/');
        }
        
        
    }
  
  public function stripe_subscription_updated(Request $request)
  {
     $data = $request->all();
     if(!empty($data['data']['object']['id']))
     {
         $sid=$data['data']['object']['id'];
         $get=Transaction::where('transaction_id',$sid)->orderby('id','desc')->first();
         if(!empty($get))
         {
              if($get->package_id == '1'){
                $start_date =date('Y-m-d');  
                $date = strtotime($start_date);
                $date = date('Y-m-d',strtotime("+7 day", $date));  
                }
                if($get->package_id == '3')
                {
                    $start_date =date('Y-m-d');  
                $date = strtotime($start_date);
                $date = date('Y-m-d',strtotime("+1 year", $date));   
                }
                if($get->package_id == '2')
                {
                    $start_date =date('Y-m-d');  
                    $date = strtotime($start_date);
                    $date = date('Y-m-d',strtotime("+1 month", $date));  
                }
    $transaction_data=array(
                'transaction_id'=>$sid,
                'user_id'=>$get->user_id,
                'package_id'=>$get->package_id,
                'status'=>$get->status,
                'currency'=>$get->currency,
                'amount'=>$get->amount,
                'exp'=>$date,
                'recurring'=>'1'
                );
    Transaction::insertUser($transaction_data);
    
    $weres = [['friend_id','=',$get->user_id]];
            $reffercheck =  Reffer::getbycondition($weres);
            
            if(count($reffercheck) > 0)
            {
            $packages = Subscription_content::getbycondition(array('id'=>$get->package_id));
            $amounts=$packages[0]->referrel_amount;
            if(!empty($amounts))
            {  
            $were3 =  array(
            'friend_id'=>$get->user_id,
            'uid'=>$reffercheck[0]->uid
            );
            $amountss =  $reffercheck[0]->amount;
            $amountss +=$packages[0]->referrel_amount;
            echo $amountss;
            
            Reffer::where('friend_id',$get->user_id)->where('uid',$reffercheck[0]->uid)->update(array('amount'=>$amountss));
            
            }
            }
             
         }else
         {
             
         }
     }
     
      
  }
    public function setsession($id)
    {
        
        Session::put('pack_id',$id);
        Session::save(); 
        
    }
    
    public function registered($id)
    {
       
        if(session()->exists('user'))
        {
            return redirect('/home');
        }else
        {
            $this->middleware('csrf');
            Session()->put('pack_id',$id);
            Session::save(); 
            return view('/auth/register');
        }
       /* Session::put('pack_id',$id);
        Session::save(); 
         return redirect('/');*/
        
    }
    public function about()
    {
        $this->middleware('auth');
        $data['user'] = Auth::user();
         $data['options'] = Options::getoption();
          $data['title']='About';
        return view('about',$data);
    }
    public function question22(Request $request)
    {
         $data = $request->all();
         $v = 'data';
         if(isset($data['search']) && $data['search']='1')
         {
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
                  $result2 = $result2->where('question_answers.course', $data['subject']);
                }
                if(isset($data['chapter']) && $data['chapter']!='')
                {
                  $result2 = $result2->where('question_answers.subject', $data['chapter']);
                }
                if(isset($data['chapters']) && $data['chapters']!='')
                {
                  $result2 = $result2->where('question_answers.chapter', $data['chapters']);
                }
                $result2 = $result2->where('question_answers.qstatus','=','1');
                $result2 = $result2->select('question_answers.*');
                 $result2 = $result2->orderBy(DB::raw('RAND()'))->distinct('question_answers.id')->limit(10)->get();
               if(count($result2)=='0')
                {  
                   $v = 'nodata';
                    if(isset($data['searchs']) && $data['searchs']='1')
                    {
                     return response()->json(['nohtml'=>'No Questions found under']);  
                    }
               }
         }
        if(isset($data['country']) && $data['country']!='' &&  $data['main']=='country')
        {
         $gettags = [['parent', '=', $data['country']],['status', '=', '1']]; 
         $text = 'Select State';
          $states=country::getbycondition($gettags);
             if ($request->ajax()) {
    		    $view = view('user.states', compact('states','text'))->render();
    		    if($v == 'nodata')
    		    {
    		       return response()->json(['htmls'=>$view,'nohtml'=>'No Questions found under']); 
    		    }else
    		    {
    		      return response()->json(['htmls'=>$view]);  
    		    }
               
    		}
        }
        
        if(isset($data['subject']) && $data['subject']!='' && $data['main']=='course')
        {
          $gettags = [['parent', '=', $data['subject']],['type', '=', '2'],['status', '=', '1']]; 
           $text = 'Select Subject';
          $states=course::getbycondition($gettags);
             if ($request->ajax()) {
    		    $view = view('user.states', compact('states','text'))->render();
    		    if($v == 'nodata')
    		    {
    		       return response()->json(['htmlsb'=>$view,'nohtml'=>'No Questions found under']); 
    		    }else
    		    {
                   return response()->json(['htmlsb'=>$view]);
    		    }
    		}  
        }
        
        if(isset($data['chapter']) && $data['chapter']!='' && $data['main']=='subject')
        {
          $gettags = [['parent', '=', $data['chapter']],['type', '=', '3'],['status', '=', '1']]; 
           $text = 'Select Chapter';
          $states=course::getbycondition($gettags);
             if ($request->ajax()) {
    		    $view = view('user.states', compact('states','text'))->render();
    		    if($v == 'nodata')
    		    {
    		       return response()->json(['htmlc'=>$view,'nohtml'=>'No Questions found under']); 
    		    }else
    		    {
                  return response()->json(['htmlc'=>$view]);
    		    }
    		}  
        }  
        if(isset($data['chapters']) && $data['chapters']!='' && $data['main']=='chapter')
        {
             if ($request->ajax()) {

    		    if($v == 'nodata')
    		    {
    		       return response()->json(['nohtml'=>'No Questions found under']); 
    		    }
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
        
        $data['title']='Question';
        $data['page']='question';
        
        if(!empty($data['user']) && $users->id !='' && isset($users->id))
        {
        return view('/user/question',$data);
        }
        else if(session()->exists('user'))
        {
            return view('/user/question',$data);
        }
        else
        {
            return redirect('/');
        }
        
    }
    public function question(Request $request)
    { 
         $data = $request->all();
         $v = 'data';
         if(isset($data['search']) && $data['search']='1')
         {
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
                if(isset($data['chapter']) && $data['chapter']!='')
                {
                  $result2 = $result2->where('question_answers.chapter', $data['chapter']);
                }
                $result2 = $result2->where('question_answers.qstatus','=','1');
                $result2 = $result2->select('question_answers.*');
                 $result2 = $result2->orderBy(DB::raw('RAND()'))->distinct('question_answers.id')->limit(10)->get();
               if(count($result2)=='0')
                {  
                   $v = 'nodata';
                    if(isset($data['searchs']) && $data['searchs']='1')
                    {
                     return response()->json(['nohtml'=>'No Questions found under']);  
                    }
               }
         }
        if(isset($data['country']) && $data['country']!='')
        {
         $gettags = [['parent', '=', $data['country']],['status', '=', '1']]; 
         $text = 'Select State';
          $states=country::getbycondition($gettags);
             if ($request->ajax()) {
    		    $view = view('user.states', compact('states','text'))->render();
    		    if($v == 'nodata')
    		    {
    		       return response()->json(['html'=>$view,'nohtml'=>'No Questions found under']); 
    		    }else
    		    {
    		      return response()->json(['html'=>$view]);  
    		    }
               
    		}
        }
        
        if(isset($data['subject']) && $data['subject']!='')
        {
          $gettags = [['parent', '=', $data['subject']],['type', '=', '2'],['status', '=', '1']]; 
           $text = 'Select Subject';
          $states=course::getbycondition($gettags);
             if ($request->ajax()) {
    		    $view = view('user.states', compact('states','text'))->render();
    		    if($v == 'nodata')
    		    {
    		       return response()->json(['html'=>$view,'nohtml'=>'No Questions found under']); 
    		    }else
    		    {
                   return response()->json(['html'=>$view]);
    		    }
    		}  
        }
        
        if(isset($data['chapter']) && $data['chapter']!='')
        {
          $gettags = [['parent', '=', $data['chapter']],['type', '=', '3'],['status', '=', '1']]; 
           $text = 'Select Chapter';
          $states=course::getbycondition($gettags);
             if ($request->ajax()) {
    		    $view = view('user.states', compact('states','text'))->render();
    		    if($v == 'nodata')
    		    {
    		       return response()->json(['html'=>$view,'nohtml'=>'No Questions found under']); 
    		    }else
    		    {
                  return response()->json(['html'=>$view]);
    		    }
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
        
        $data['title']='Question';
        $data['page']='question';
        
        if(!empty($data['user']) && $users->id !='' && isset($users->id))
        {
        return view('/user/question',$data);
        }
        else if(session()->exists('user'))
        {
            return view('/user/question',$data);
        }
        else
        {
            return redirect('/');
        }
        
    }
    public function test()
    {
          $this->middleware('auth');
        $data['user'] = Auth::user();
        return view('/user/test-summary'); 
        
    }
    public function start_test()
    {
        
         $this->middleware('auth');
        $data['user'] = Auth::user();
        return view('/user/start_test'); 
    }
    public function question_details ()
    {
       $this->middleware('auth');
        $data['user'] = Auth::user();
        return view('/user/question_details'); 
        
    }
    public function subscription()
    {
        $this->middleware('auth');
        $data['user'] = Auth::user();
        $users = Auth::user();
        $data['title']='Subscription';
        $data['page']='subscription';
        if(!empty($data['user']) && $users->id !='' && isset($users->id))
        {  
            $userdata = array(
            'id'=> $users->id ,
            'name' => $users->name ,
            'lname' => $users->lname ,
            'email' => $users->email ,
            );
            Session::put('user',$userdata);
            Session::put('userid', $users->id);
            Session::save(); 
            $userid = $users->id;
            $userid =Session()->get('userid');
            $were = array('uid'=>$userid);
            $data['applyrequests'] = Withdraw::getbycondition($were);
            $were = array('uid'=>$userid,'status'=>'2');
            $data['applyrequests2'] = Withdraw::getbycondition($were);
            $were2 = array('uid'=>$userid,'status'=>'1');
            $data['reffered'] = Reffer::getbycondition($were2);
            $data['transactions'] =  Transaction::getbycondition(array('user_id'=>$userid));
            $data['walletamount']=0;
            if(!empty($data['reffered']))
            {
                foreach($data['reffered'] as $reffer)
                {
                    $data['walletamount'] += $reffer->amount;
                }
            }
            if(!empty($data['transactions']))
            {
                foreach($data['transactions'] as $reffers)
                {
                    if(!empty($reffers->walletuse))
                    {
                     $data['walletamount'] -= $reffers->walletuse;
                    }
                }
            }
            
            $data['withdrwaamount']=0;
            if(!empty($data['applyrequests2']))
            {
                foreach($data['applyrequests2'] as $reffers)
                {
                    if(!empty($reffers->amount))
                    {
                        $data['walletamount'] -= $reffers->amount;
                        $data['withdrwaamount'] +=$reffers->amount;
                    }
                }
            }
              $data['subscription'] = Subscription_content::getbycondition([['status','=','1'],['id','!=','1']]);
           return view('/user/subscription',$data);
        }else if(session()->exists('user'))
        {  $userid =Session()->get('userid');
             $were = array('uid'=>$userid);
            $data['applyrequests'] = Withdraw::getbycondition($were);
            $were = array('uid'=>$userid,'status'=>'2');
            $data['applyrequests2'] = Withdraw::getbycondition($were);
            $were2 = array('uid'=>$userid,'status'=>'1');
            $data['reffered'] = Reffer::getbycondition($were2);
            $data['transactions'] =  Transaction::getbycondition(array('user_id'=>$userid));
            $userid =Session()->get('userid');
            $were = array('uid'=>$userid);
            $data['applyrequests'] = Withdraw::getbycondition($were);
            $were = array('uid'=>$userid,'status'=>'2');
            $data['applyrequests2'] = Withdraw::getbycondition($were);
            $were2 = array('uid'=>$userid,'status'=>'1');
            $data['reffered'] = Reffer::getbycondition($were2);
            $data['walletamount']=0;
            if(!empty($data['reffered']))
            {
                foreach($data['reffered'] as $reffer)
                {
                    if(!empty($reffer->amount))
                    {
                     $data['walletamount'] += $reffer->amount;
                    }
                }
            }
            
            
            if(!empty($data['transactions']))
            {
                foreach($data['transactions'] as $reffers)
                {
                    if(!empty($reffers->walletuse))
                    {
                     $data['walletamount'] -= $reffers->walletuse;
                    }
                }
            }
            
            $data['withdrwaamount']=0;
            if(!empty($data['applyrequests2']))
            {
                foreach($data['applyrequests2'] as $reffers)
                {
                    if(!empty($reffers->amount))
                    {
                        $data['walletamount'] -= $reffers->amount;
                        $data['withdrwaamount'] +=$reffers->amount;
                    }
                }
            }
             $data['subscription'] = Subscription_content::getbycondition([['status','=','1'],['id','!=','1']]);
           $userid =Session()->get('userid');
           return view('/user/subscription',$data);
        }
        
        
        
    }
    public function attempt_test (Request $request)
    {
        $this->middleware('auth');
        $data['user'] = Auth::user();
        if(!empty($data['user']) || session()->exists('user'))
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
            $data['title']= 'Attempt Test';
            $data['page']='attempt_test';
            
            $data['user_id']=Session()->get('userid');
            $data['userdatas'] = User::getbycondition(array('id'=>$data['user_id']));
            $expiry = DB::table('users_hours')->where('user_id',$data['user_id'])->select('expiry')->first();
            
            $data['states'] = array();
            $data['subjects'] = array();
            $data['chapterss'] = array();
             if(!empty($data['userdatas'][0]->attempt_test_fav) && $data['userdatas'][0]->attempt_test_fav!=null)
              {
                  $alls = json_decode($data['userdatas'][0]->attempt_test_fav);
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
            
            if(Hours::getdetailsuserret($data['user_id'],'package_id') == '1' ){ 
            if(date('Y-m-d') < date('Y-m-d',strtotime($expiry->expiry))){
            if(Hours::getdetailsuserret($data['user_id'],'total_hours') == '00:00:00')
            {
                return redirect('/subscription')->with('error','Your membership has expired. Please renew to perform the requested action.');
            }else
            {
              return view('/user/attempt-test',$data);
            }
            }else
            {
                return redirect('/subscription')->with('error','Your membership has expired. Please renew to perform the requested action.'); 
            }
            }else
            { $dat3 =  Hours::getdetailsuserret($data['user_id'],'expiry');
                 
            $date1 = date('Y-m-d',strtotime($dat3));
            $date2 = date('Y-m-d');
            if($date2 < $date1)
            {
                return view('/user/attempt-test',$data); 
            }else
            {
               return redirect('/subscription')->with('error','Your membership has expired. Please renew to perform the requested action.'); 
            }
                
            }
        }else
        {
            return redirect('/');
        }
         
    
    }
     public function myprofile()
    {
        $this->middleware('auth');
        $data['user'] = Auth::user();
        $users = Auth::user();
        $data['title']='My Profile';
        $data['page']='myprofile';
        $data['countries'] = country::getoption();
        if(!empty($data['user']) && $users->id !='' && isset($users->id))
        {  
            $userdata = array(
            'id'=> $users->id ,
            'name' => $users->name ,
            'lname' => $users->lname ,
            'email' => $users->email ,
            );
            Session::put('user',$userdata);
            Session::put('userid', $users->id);
            Session::save(); 
            $userid = $users->id;
            $data['transactions'] = Transaction::getbycondition(array('user_id'=>$userid));

            $data['users'] = User::getbycondition(array('id'=>$userid));
           if(!empty($data['users'][0]['country']))
            {
                 $gettagss = [['parent', '=', $data['users'][0]['country']],['status', '=', '1']]; 
            }else
            {
                $gettagss = [['parent', '!=', 0],['status', '=', '1']]; 
            }
            
             $data['states']=country::getbycondition($gettagss);
            return view('/user/myprofile',$data);
          
        }else if(session()->exists('user'))
        {
        $userid =Session()->get('userid');
        $data['transactions'] = Transaction::getbycondition(array('user_id'=>$userid));
        $data['users'] = User::getbycondition(array('id'=>$userid));
         if(!empty($data['users'][0]['country']))
            {
                 $gettagss = [['parent', '=', $data['users'][0]['country']],['status', '=', '1']]; 
            }else
            {
                $gettagss = [['parent', '!=', 0],['status', '=', '1']]; 
            }
            
             $data['states']=country::getbycondition($gettagss);
         return view('/user/myprofile',$data);   
        }else
        {
            return redirect('/');
        }
        
        
    
    }
     public function contact()
    {
        $this->middleware('auth');
        $data['user'] = Auth::user();
        $data['options'] = Options::getoption();
        $data['title']= 'Contact Us';
        return view('contact',$data);
        
    
    }
     public function pricing()
    {
        $data['options'] = Options::getoption();
        $data['user'] = Auth::user();
        $data['subscription'] = Subscription_content::getuser();
        $data['title']= 'Pricing';
        $data['subscription'] = Subscription_content::getbycondition([['status','=','1']]);
        if(empty(Session()->get('userid')))
        {
             return view('pricing',$data);
        }else
        {
            return redirect('/subscription');
        }
       
    }
    public function faq(Request $request)
    {
    $data['user'] = Auth::user();
    $data['options'] = Options::getoption();
    $data['optionses'] = Faqs::getoption2();
     $data['title']= 'Faq';
    if ($request->ajax()) {
    return view('presult',$data);
    }
        return view('faq',$data);
        
    
    }
     public function referral()
    {
        $data['options'] = Options::getoption();
         $data['title']= 'Referral';
        if(session()->exists('user'))
        {
            $userid =Session()->get('userid');
            $were = array('uid'=>$userid);
            $data['reffered'] = Reffer::getbycondition($were);
            $data['code']= User::getbycondition(array('id'=>$were,'status'=>'1'));
        }
        return view('referral',$data);
        
    
    }
    
    public function contactus(Request $request)
    { 
        if($request->isMethod('post'))
          {
               $data= $request->all();
               unset($data['_token']);
              $admin = Admin::getUserDetail('1');
              if(!empty($admin[0]->name))
              {
                  $name = $admin[0]->name;
              }
              else
              {
                $name = 'Admin';  
              }
              if(!empty($admin[0]->email))
              {
                  $email = $admin[0]->email;
              }else
              {
                  $email='admin@gmail.com';
              }
                $variavle = Config::get('constants.Contactus_html');
                $variavles = explode(' ',$variavle);
                foreach($variavles as $key=> $variavle)
                {
                    if($variavle=='#name#')
                    {
                        $variavles[$key]=$data["name"];
                    }
                    if($variavle=='#email#')
                    {
                        $variavles[$key]=$data["email"];
                    }
                    if($variavle=='#phone#')
                    {
                        $variavles[$key]=$data["phone"];
                    }
                    if($variavle=='#message#')
                    {
                        $variavles[$key]=$data["message"];
                    }
                }
                
                $data = array_filter($data);
                $messags = array();
                $hash    = md5(uniqid(rand(), true));
              //  $htmls = 'User '.$data["name"].' Send a query there email is '.$data["email"].' phone no.'.$data["phone"].' and there message is '.$data["message"];
                $htmls = implode(' ',$variavles);
                $header = str_replace("#Subject#",$data["subject"],Config::get('constants.Contactus_header'));
                $buttonhtml = Config::get('constants.Contactus_btn_html');
                $pass_url  = url(''); 
                $path = url('resources/views/email.html');
                $email_path    = file_get_contents($path);
                $cur_year = date('Y');
                $email_content = array('[name]','[pass_url]','[htmls]','[buttonhtml]','[header]','[cur_year]');
                $replace  = array($name,$pass_url,$htmls,$buttonhtml,$header,$cur_year);
                $message = str_replace($email_content,$replace,$email_path);
                $subject = Config::get('constants.Contactus_subject');
                 $header = 'From: '.env("IMAP_HOSTNAME_TEST").'' . "\r\n";
                $header .= "MIME-Version: 1.0\r\n";
                $header .= "Content-type: text/html\r\n";
                $retval = mail($email,$subject,$message,$header); 
            if($retval)
            {
               $messags['message'] = "Thank you, your query has been submitted successfully.";
               $messags['erro']= 101; 
            }else
            {
                $messags['message'] = "Error to submit the data, try again later.";
                $messags['erro']= 202;
            }
            
          }else
          {
             $messags['message'] = "Error to submit the data, try again later.";
             $messags['erro']= 202; 
             return redirect('/contact');
          }
          echo json_encode($messags);
        die;
    }
    
    public function subscribe(Request $request)
    {
        if($request->isMethod('post'))
        {
            $data= $request->all();
            unset($data['_token']);
            $email = array('email'=>$data['email']);
            $exists = Subscribers::getoptionmatch($email);
            $messags= array();
            if(count($exists) > 0)
            {
                $messags['message'] = "Email already exist.";
                $messags['erro']= 202; 
            }else
            {
                if(Subscribers::insertoption($email))
                {
                   $messags['message'] = "Email subscribed has been successfully.";
                  $messags['erro']= 101;  
                }else
                {
                  $messags['message'] = "Error to submit email.";
                  $messags['erro']= 202;   
                }
            }
            
        
        }else
        {
         return Redirect::back();
        }  
        echo json_encode($messags);
        die; 
    }
    
    public function reffer_friend(Request $request)
    {
         if($request->isMethod('post'))
        {
            if(session()->exists('user'))
            {
                $data= $request->all();
                unset($data['_token']);
                $email = [['email','=',$data['email']],['status','!=','2']];
                $exists = User::getUsermatch($email);
                $messags= array();
                if(count($exists) > 0)
                {
                    $messags['message'] = "Email already exist.";
                    $messags['erro']= 202; 
                }else
                {     $id = Session()->get('userid');
                    $weres = [['friend_email','=',$data['email']],['uid','=',$id],['status','=','1']];
                     $reffercheck =  Reffer::getoptionmatch($weres);
                        if(count($reffercheck) > 0)
                        {
                            $messags['message'] = "Email already exist.";
                            $messags['erro']= 202; 
                        }else
                        {
                            $were= [['id','=', Session()->get('userid')]];
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
                                $header = Config::get('constants.Reffer_header'); 
                                $buttonhtml = Config::get('constants.Reffer_btn_html');
                                $pass_url  = url('getinvitation/'.$iv); 
                                $path = url('resources/views/email.html');
                                $subject = Config::get('constants.Reffer_subject');
                                $to_email=$data['email'];
                                if($this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email))
                                {        $amout = Subscription_content::getUsermatch(array('id'=>'3'));
                
                                    $weres2 = [['friend_email','=',$data['email']],['uid','=',Session()->get('userid')]];
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
                                            $messags['message'] = "Referral email has been sent successfully.";
                                            $messags['erro']= 101;  
                                        }else
                                        {
                                            $messags['message'] = "Error to send referral email.";
                                            $messags['erro']= 202;   
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
                                        $messags['message'] = "Referral email has been sent successfully.";
                                        $messags['erro']= 101;  
                                        }else
                                        {
                                        $messags['message'] = "Error to send referral email.";
                                        $messags['erro']= 202;   
                                        }  
                                    }
                                }
                             
                            }
                            
                        }
                }
            }else
            {
                $messags['message'] = "Session has been expierd.";
              $messags['erro']= 202; 
            }
            
        
        }else
        {
         return Redirect::back();
        }  
        echo json_encode($messags);
        die;
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
    
    public function getinvitations(Request $request,$id='')
    {
        if(session()->exists('user'))
        {
            return redirect('/home'); die; 
        }
        $this->middleware('auth');
         Auth::logout();
        Session::flush();
        $request->session()->forget('user');
        $request->session()->flush();
        $data['getdata'] = Reffer::getbycondition(array('token'=>$id));
        $data['package'] = Subscription_content::getbycondition(array('id'=>'3'));
        if(count($data['getdata']) > 0)
        {      //$this->middleware('csrf');
            $data['options'] = Options::getoption();
            $data['user'] = Auth::user();
            $data['subscription'] = Subscription_content::getuser();
            $data['title']= 'Pricing';
            $data['subscription'] = Subscription_content::getbycondition([['status','=','1']]);
            //Session()->put('pack_id',$data['package'][0]->id);
            //Session::save(); 
            //return view('/auth/register',$data);
            $data['tokenss'] = $id;
            return view('pricing',$data);
        }else
        { $this->middleware('auth');
         Auth::logout();
        Session::flush();
        $request->session()->forget('user');
        $request->session()->flush();
         return Redirect('/login')->with('error','Link has been expierd');
        }
    }
    
    public function getinvitations22(Request $request,$id='')
    {
        if(session()->exists('user'))
        {
            return redirect('/home'); die; 
        }
        $this->middleware('auth');
         Auth::logout();
        Session::flush();
        $request->session()->forget('user');
        $request->session()->flush();
        $data['getdata'] = User::getbycondition(array('refferal_code'=>$id,'status'=>'1'));
        $data['package'] = Subscription_content::getbycondition(array('id'=>'3'));
        if(count($data['getdata']) > 0)
        {      //$this->middleware('csrf');
            $data['options'] = Options::getoption();
            $data['user'] = Auth::user();
            $data['subscription'] = Subscription_content::getuser();
            $data['title']= 'Pricing';
            $data['subscription'] = Subscription_content::getbycondition([['status','=','1']]);
            //Session()->put('pack_id',$data['package'][0]->id);
            //Session::save(); 
            //return view('/auth/register',$data);
            $data['tokenss2'] = $id;
            return view('pricing',$data);
        }else
        { $this->middleware('auth');
         Auth::logout();
        Session::flush();
        $request->session()->forget('user');
        $request->session()->flush();
         return Redirect('/login')->with('error',"Link has been expierd or user doesn't exists");
        }
    }
    
    public function showRegistrationForm()
    {
        if(session()->exists('user'))
        {
            return redirect('/home'); die; 
        }
           $this->middleware('csrf');
            Session()->put('pack_id','');
            Session::save(); 
            return view('/auth/register');
    }
    
    public function getuserregister(Request $request,$sbid='',$id='')
    {  
        if(session()->exists('user'))
        {
            return redirect('/home'); die; 
        }
        $data['getdata'] = Reffer::getbycondition(array('token'=>$id));
        $data['packagess'] = Subscription_content::getbycondition(array('id'=>$sbid));
        if(count($data['getdata']) > 0)
        {      $this->middleware('csrf');
            Session()->put('pack_id',$sbid);
            Session::save(); 
            return view('/auth/register',$data);
        }else
        { $this->middleware('auth');
        Auth::logout();
        Session::flush();
        $request->session()->forget('user');
        $request->session()->flush();
         return Redirect('/login')->with('error','Link has been expierd');
        }
    }
    
    public function getuserregister2(Request $request,$sbid='',$id='')
    { 
        if(session()->exists('user'))
        {
            return redirect('/home'); die; 
        }
        $data['getdatas'] = User::getbycondition(array('refferal_code'=>$id,'status'=>'1'));
        $data['packagess'] = Subscription_content::getbycondition(array('id'=>$sbid));
        if(count($data['getdatas']) > 0)
        {      $this->middleware('csrf');
            Session()->put('pack_id',$sbid);
            Session::save(); 
            $data['refercode'] = $id;
            return view('/auth/register',$data);
        }else
        { $this->middleware('auth');
        Auth::logout();
        Session::flush();
        $request->session()->forget('user');
        $request->session()->flush();
         return Redirect('/login')->with('error',"Link has been expierd or user doesn't exists");
        }
    }
    
    
}
