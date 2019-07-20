<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Admin;
use App\course;
use Config;
use Auth;
use App\Question_answers;
use App\Faqs;
use App\Options;
use App\Testimonials;
use App\Test;
use App\User;
use App\Grades;
use App\Years;
use App\Transaction;
use App\Withdraw;
use App\Hours;
use Hash;
use App\User_test;
use App\Subscription_content;
use App\country;
use App\Subscribers;
use DB;
use App\Notification;
use App\Reffer;
use Carbon\Carbon;
use App\User_test_answers;
use Mail;
use App\Pre_questiondetails;
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
use Illuminate\Support\Facades\Redirect;
class AdminController extends Controller
{
   /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {    DB::statement("SET NAMES 'utf8'");
        header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        $this->middleware('auth:admin');
       if(Auth::user()){
       }else
       {
         return redirect('/admin/dashboard');
       }
        
    }
    
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    { 
    
    if(Auth::user()){
    $data['admin'] = Auth::user();
    $data['users'] = User::getrecentuser();
    $data['totalque']=Question_answers::countall();
    $data['totaluser']=User::getusercount();
    $data['totaltest']=Test::testcount();
     $data['title']='Dashboard-Multiple Choice Online';
     $data['page']='dashboard';
    return view('/admin/admin-home',$data);
        }else
        {
         return redirect('admin');   
        }
    }
     public function subscription(Request $request)
    { 
            if(Auth::user()){
        $data['subscription'] = Subscription_content::getuser();
         $data['admin'] = Auth::user();
          $data['title']='Subscription-Multiple Choice Online';
         $data['page']='subscription';
        return view('/admin/subscription',$data); 
    }else
        {
          
         return redirect('admin');  
        }
    }
   
    public function user_test_details(Request $request,$id,$id1,$id2)
    {
      $data['user_id']=$id;
      $data['test_id']=$id1;
      $data['admin'] = Auth::user();
      $realanswers2 =  User_test::getbycondition(array('id'=>$id1,'user_id'=>$id));
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
                    ->where('user_test.id',$id1);
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
               $title='Test Detail-Multiple Choice Online';
                if ($request->ajax()) {
                   return view('admin.test_detail_presult',compact('realanswers','title'));
                }
           }else
           {
               $data['realanswers'] = array();
           }
           
    /*  $data['questions_det'] = $this->selectData("question_answers",array('question_id'=>$id1));
      $data['questions'] = $this->selectData("user_test_answers",array('user_id'=>$id,'test_id'=>$id1));
      
       $data['questions11'] = DB::table('user_test_answers')
            ->join('question_answers', 'question_answers.id', '=', 'user_test_answers.question_id')
            ->where('user_test_answers.user_id',$id)
            ->where('user_test_answers.test_id',$id1)
            ->select('user_test_answers.*','question_answers.question','question_answers.optiona','question_answers.optionb',
            'question_answers.optionc','question_answers.optiond','question_answers.answer as oranswer')
            ->paginate(10);
            if(count($data['questions']) == 0)
            { 
                $data['answers'] = Question_answers::getoptionDetailtest($id2);
            }else
            {
                $data['answers'] = array(); 
            }*/
            /*echo '<pre>'; print_r($data); die; */
        /* if ($request->ajax()) {
           return view('admin.test_detail_presult',compact('realanswers','title'));
        }*/
     // $data['title']='Test Detail-Multiple Choice Online';
   //  $data['page']='users';
   $title='Test Detail-Multiple Choice Online';
   $page = 'users';
    return view('/admin/user_test_details',compact('realanswers','title','page')); 
    
    }
    public function users()
    {
        $data['admin'] = Auth::user();
        $data['title']='Users-Multiple Choice Online';
         $data['page']='users';
        $data['users'] = $this->selectData("users",[['status','!=','2']],'','id');
      
        return view('/admin/users',$data); 
    }
    
    public function total_questions(Request $request,$id='')
    {
        $data['admin'] = Auth::user();
        $admin = Auth::user();
       if(!empty($admin) > 0)
        {
        $were = array('status'=>'1');
        $data['grades']= Grades::getbycondition($were);
        $data['years']= Years::getbycondition($were);
        $data['courses']= course::getoption();
        $data['countries']= country::getoption();
        //$data['answers']=Test::gettotalrecordwithpagination($id);
        
         $were = [['user_id','=',$id],['status', '!=','2' ]];
         $data['answers'] = Question_answers::getbycondition2($were);
        $gettagss = [['status', '=', '1']]; 
        $data['states']=country::getbycondition($gettagss);
        $gettagssub = [['type', '=', '2'],['status', '=', '1']]; 
        $data['subjects']=course::getbycondition($gettagssub);
        $gettagschap = [['type', '=', '3'],['status', '=', '1']];
        $data['chapterss']=course::getbycondition($gettagschap);
            $data['title']= 'Questions View-Multiple Choice Online';
            $data['page']='users';
            if ($request->ajax()) {
              return view('admin.presult_viewquestions',$data);
            }
             return view('/admin/viewquestions',$data);
        }else
        { 
            return redirect('/admin');
        }
    }
     public function editquestion($id,$id1)
    {
         $data = DB::table('question_answers')
           // ->join('question_answers', 'question_answers.question_id', '=', 'pre_questiondetails.id')
             ->join('country', 'country.id', '=', 'question_answers.country')
           // ->where('pre_questiondetails.id',$id1)
             ->where('question_answers.id',$id)
            ->select('question_answers.*','country.name as cname')
            ->first();
            if(! is_array($data))
            {
              $data = DB::table('question_answers')
           // ->join('question_answers', 'question_answers.question_id', '=', 'pre_questiondetails.id')
             //->join('country', 'country.id', '=', 'pre_questiondetails.country')
            ->where('question_answers.id',$id1)
           //  ->where('question_answers.id',$id)
            ->select('question_answers.*')
            ->first();  
            }
           
        //$country= $this->selectData("country",[['parent','=','0'],['status','!=','2']]);
         $country=country::getbycondition([['parent','=','0'],['status','!=','2']]);
         //$state= $this->selectData("country",array('parent'=>$data->country));
         $state=country::getbycondition([['parent','=',$data->country],['status','=','1']]);
        // $course= $this->selectData("courses",array('type'=>'1'));
         $were = array('status'=>'1');
        $grade= Grades::getbycondition($were);
        $year= Years::getbycondition($were);
        $course = course::getoption();
         //$grade= $this->selectData("grades");
         //$year= $this->selectData("years");
        //$subject= $this->selectData("courses",array('parent'=>$data->course));
         $gettagssub = [['parent', '=', $data->course],['type', '=', '2'],['status', '=', '1']]; 
         $subject=course::getbycondition($gettagssub);
         $gettagschap = [['parent', '=', $data->subject],['type', '=', '3'],['status', '=', '1']];
         $chap=course::getbycondition($gettagschap);
         //$chap= $this->selectData("courses",array('parent'=>$data->subject));
         echo '<div class="row">  
            <span id="success_msg"></span>
            <div class="tab-inn ">
            <p class="fontsize-p">Choose Your Priorities</p>
            <div class="col-md-12">
            <div id="demo">
            <div class="row">     
            <div class="col-md-6">
            <div class="form-group lgbtn">
            <input type="hidden" value="'.$id1.'" name="pre_questiondetails_id">
            <select  class="form-control cat_country js-example-basic-single" id="Country" name="country">
            <option value="">Country</option>';
                              foreach($country as $c){?>
                                     <option value="<?=$c->id?>" <?php if(!empty($data->country)) { if($data->country == $c->id){ echo 'selected'; } } ?>><?=$c->name?></option>
                                   <?php }
                                echo '</select>
                            </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group lgbtn">
                          <input type="hidden" name="id" value="'.$data->question_id.'">
                           <select  class="form-control state_country js-example-basic-single" id="State" name="state">
                           <option value="">State</option>';
                                   foreach($state as $c){?>
                                     <option value="<?=$c->id?>" <?php if($data->state == $c->id){ echo 'selected'; }?>><?=$c->name?></option>
                                   <?php }
                                echo '</select>
                    </div>
                </div>
                <div class="col-md-6">
        <div class="form-group lgbtn">
        <select  class="form-control js-example-basic-single" id="Grade" name="grade"><option value="" >Grade</option>';
                                   foreach($grade as $c){?>
                                     <option value="<?=$c->id?>" <?php if($data->grade == $c->id){ echo 'selected'; }?>><?=$c->name?></option>
                                   <?php }
                                echo '</select>
    </div>
</div>
<div class="col-md-6">
  <div class="form-group lgbtn">

   <select class="form-control js-example-basic-single" id="Year" name="year">
												<option value="">Year</option>';
                                    foreach($year as $c){?>
                                     <option value="<?=$c->id?>" <?php if($data->year == $c->id){ echo 'selected'; }?>><?=$c->name?></option>
                                   <?php }
                                echo '</select>
</div>
</div>
                <div class="col-md-6">
                  <div class="form-group lgbtn">

                   <select  class="form-control category js-example-basic-single" id="Course" name="course"> <option value="">Course</option>';
                                   foreach($course as $c){?>
                                     <option value="<?=$c->id?>" <?php if($data->course == $c->id){ echo 'selected'; }?>><?=$c->name?></option>
                                   <?php }
                                echo '</select>
            </div>
        </div>
        
<div class="col-md-6">
  <div class="form-group lgbtn">

   <select  class="form-control subcat js-example-basic-single" id="Subject" name="subject">
												<option value="">Subject</option>';
                                   foreach($subject as $c){?>
                                     <option value="<?=$c->id?>" <?php if($data->subject == $c->id){ echo 'selected'; }?>><?=$c->name?></option>
                                   <?php }
                                echo '</select>
</div>
</div>
<div class="col-md-6">
<div class="form-group lgbtn">
<select  class="form-control subchapter js-example-basic-single" id="Chapter" name="chapter">
<option value="">Chapter</option>';
foreach($chap as $c){?>
<option value="<?=$c->id?>" <?php if($data->chapter == $c->id){ echo 'selected'; }?>><?=$c->name?></option>
<?php }
echo '</select>
</div>
</div>                                          
</div>
<div class="row">
<div class="form-group col-md-12 ">
<select name="type"  data-text1="Save Changed" data-type="'.$data->type.'" data-value="'.$data->question.'" data-text="updatebtns" onchange="typechanged(this,2);" class="form-control type" required="">
<option value="">Select Type</option> ';
?>
<option value="0" <?php if($data->type == '0'){ echo 'selected'; }?>><?php echo 'Image'; ?></option>
<option value="1" <?php if($data->type == '1'){ echo 'selected'; }?>><?php echo 'Text'; ?></option>
<?php

 echo '</select></div>
</div>'; ?>
   
<?php echo '<div class="row">
<input type="hidden" id="id" name="id" value="'.$id.'">
<div class="form-group col-md-12 questionaddedhere2"> 
<p class="fontsize-p">Question</p>';

if($data->type == 1){ 
echo '<textarea  class="form-control" rows="3" placeholder="Add Question" name="question">'.$data->question.'</textarea>';
}else
{
    echo ' <div class="edit-img-que"><input type="file" data-text1="Save Changed" style="display:none;" data-text="updatebtns"; onchange="uploadimgs(this,2);" class="form-control img2" > 
     <input type="hidden" name="question" value="'.$data->question.'" class="quesclass2"> 
     <img src="'.url('public/questionimg/'.$data->question).'" class="imgsss showimage2" >
     <a onclick="removeimg(2,this)" class="showimage2 showanc" href="javascript:void(0);"><i class="fa fa-trash"></i></a></div>';
}
echo '</div>

</div> 
<div class="row">
<div class="form-group col-lg-6">
<label for="Optionaa">Option A</label>
<input class="form-control ';
  if($data->answer == '1'){ echo '';}else{}
  echo'" id="Optionaa" value="'.$data->optiona.'"  type="text" name="optiona">
</div>
<div class="form-group col-lg-6">
  <label for="Optionbb">Option B</label>
  <input class="form-control ';
  if($data->answer == '2'){ echo '';}else{}
  echo'" id="Optionbb" value="'.$data->optionb.'" type="text" name="optionb">
</div>
<div class="form-group col-lg-6">
  <label for="Optioncc">Option C</label>
  <input class="form-control ';
  if($data->answer == '3'){ echo '';}else{}
  echo'" id="Optioncc" value="'.$data->optionc.'" type="text" name="optionc">
</div>
<div class="form-group col-lg-6">
  <label for="Optiondd">Option D</label>
  <input class="form-control ';
  if($data->answer == '4'){ echo '';}else{}
  echo'" id="Optiondd" value="'.$data->optiond.'" type="text" name="optiond">
</div>                                            
</div>
<div class="row">
<div class="form-group col-lg-12 questioninput-size">
<p class="fontsize-p pl-0 ml-0">Correct Answer</p>
<div class="form-check-inline ">
 <input  type="radio" class="form-check-input" id="Option11"  value="1"  name="answer" 
 ';  if($data->answer == '1'){ echo 'checked';}else{} echo'>
 <label class="form-check-label" for="Option11">Option A</label>
</div>
<div class="form-check-inline padding">
 <input  type="radio" class="form-check-input " id="Option22"  value="2"  name="answer" 
 ';  if($data->answer == '2'){ echo 'checked';}else{} echo'>
 <label class="form-check-label" for="Option22">Option B</label>
</div>
<div class="form-check-inline padding">
 <input type="radio" class="form-check-input "  value="3"  id="Option33" name="answer"
 ';  if($data->answer == '3'){ echo 'checked';}else{} echo'>
 <label class="form-check-label" for="Option33">Option C</label>
</div>
<div class="form-check-inline padding">
 <input  type="radio" class="form-check-input"  value="4" id="Option44" name="answer"
 ';  if($data->answer == '4'){ echo 'checked';}else{} echo'>
 <label class="form-check-label" for="Option44">Option D</label>
</div>  
</div>
</div>
</div>                  
<div class="modal-footer">
<button type="button" class="btn btn-secondary waves-effect text-left" data-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-success waves-effect updatebtns text-left">Save Changes</button>                                               
</div>
</div>
</div> 
</div>
<script>
$("select.js-example-basic-single").each(function () {
                $(this).select2({
                    dropdownParent: $(this).parent(),
                     width: "100%",
                });
            });
</script>


';
    }
    public function getdeta($id,$id1)
    {
        
        $data = DB::table('question_answers')
        //->join('question_answers', 'question_answers.question_id', '=', 'pre_questiondetails.id')
        //->where('pre_questiondetails.id',$id1)
        ->where('question_answers.id',$id)
        ->select('question_answers.*')
        ->first();
        $name = ''; $lname=''; $email='';
        if($data->is_admin != '' || $data->is_admin != '0')
        {
           $name =  Admin::getdetailsuserret2(array('id'=>$data->is_admin),'name');
            $lname =  Admin::getdetailsuserret2(array('id'=>$data->is_admin),'lname');
            $email =  Admin::getdetailsuserret2(array('id'=>$data->is_admin),'email');
        }else
        {
           $users= $this->selectData("users",array('id'=>$data->user_id));
           $name = $users[0]->name;
           $lname =$users[0]->lname;
           $email = $users[0]->email;
        }
        
         $country= $this->selectData("country",array('id'=>$data->country));
         $state= $this->selectData("country",array('id'=>$data->state));
         $course= $this->selectData("courses",array('id'=>$data->course));
         $grade= $this->selectData("grades",array('id'=>$data->grade));
         $year= $this->selectData("years",array('id'=>$data->year));
         $subject= $this->selectData("courses",array('id'=>$data->subject));
         $chap= $this->selectData("courses",array('id'=>$data->chapter));
         echo '<div class="row">
                    <div class="tab-inn ">
                       <!--p class="fontsize-p">Your Priorities</p--->
                         <div class="col-md-12">
                          <div id="demo">
                          <div class="row">     
                                 <div class="col-md-6">
                                  <div class="form-group lgbtn">
                                   User Name: '.$name.' '.$lname.'
                            </div>
                        </div>';
                        if(!empty($email))
                        {
                         echo '<div class="col-md-6">
                                  <div class="form-group lgbtn">
                                   User Email : '.$email.' 
                            </div>
                        </div> ';
                        }
                        echo '
                              <!---div class="row">     
                                 <div class="col-md-6">
                                  <div class="form-group lgbtn">
                                   <select disabled="" class="form-control" id="Country">';
                                   if(count($country)== '1'){
                                       echo '<option>'.$country[0]->name.'</option>';
                                   }else
                                   {
                                       echo '<option>Country</option>';
                                   }
                                   echo'
                                   
                                </select>
                            </div>
                        </div--->
                        <!--div class="col-md-6">
                          <div class="form-group lgbtn">
                           <select disabled="" class="form-control" id="State">';
                                   if(count($state)== '1'){
                                       echo '<option>'.$state[0]->name.'</option>';
                                   }else
                                   {
                                       echo '<option>State</option>';
                                   }
                                   echo'
                                   
                                </select>
                </div>
                </div--->
                <!--div class="col-md-6">
                  <div class="form-group lgbtn">
                   <select disabled="" class="form-control" id="Course">';
                                   if(count($course)== '1'){
                                  
                                       echo '<option>'.$course[0]->name.'</option>';
                                   }else
                                   {
                                       echo '<option>Country</option>';
                                   }
                                   echo'
                                   
                                </select>
            </div>
        </div---->
        <!--div class="col-md-6">
        <div class="form-group lgbtn">
        <select disabled="" class="form-control" id="Grade">';
                                   if(count($grade)== '1'){
                                       echo '<option>'.$grade[0]->name.'</option>';
                                   }else
                                   {
                                       echo '<option>Grade</option>';
                                   }
                                   echo'
                                   
                                </select>
         </div>
            </div--->
                <!--div class="col-md-6">
                 <div class="form-group lgbtn">

                        <select disabled="" class="form-control" id="Year">';
                                   if(count($year)== '1'){
                                       echo '<option>'.$year[0]->name.'</option>';
                                   }else
                                   {
                                       echo '<option>Year</option>';
                                   }
                                   echo'
                                   
                                </select>
                    </div>
                </div--->
                <!--div class="col-md-6">
             <div class="form-group lgbtn">
             <select disabled="" class="form-control" id="Subject">';
                                   if(count($subject)== '1'){
                                       echo '<option>'.$subject[0]->name.'</option>';
                                   }else
                                   {
                                       echo '<option>Subject</option>';
                                   }
                                   echo'
                                   
            </select>
            </div>
            </div--->
            <!---div class="col-md-6">
            <div class="form-group lgbtn">
            <select disabled="" class="form-control" id="Chapter">';
                                   if(count($chap)== '1'){
                                       echo '<option>'.$chap[0]->name.'</option>';
                                   }else
                                   {
                                       echo '<option>Chapter</option>';
                                   }
                                   echo'
                                   
                                </select>
            </div>
            </div-->                                          
            </div>
<div class="row"><p class="fontsize-p">Your Question</p>
<div class="form-group col-md-12">';
 if($data->type == '1'){
echo  '<textarea disabled="" class="form-control" rows="3" placeholder="Add Question">'.$data->question.'</textarea>';
}else
{
    echo '<img src="'.url('public/questionimg/'.$data->question).'" style="heaight:100px;width:100px;">';
}
echo '</div>
</div>  
<div class="row">
 <div class="form-group col-lg-6">
  <label for="Optiona">Option A</label>
  <input class="form-control ';
  if($data->answer == '1'){ echo 'correctans';}else{}
  echo'" id="Optiona" value="'.$data->optiona.'" type="text" disabled="">
</div>
<div class="form-group col-lg-6">
  <label for="Optionb">Option B</label>
  <input class="form-control ';
  if($data->answer == '2'){ echo 'correctans';}else{}
  echo'" id="Optionb" value="'.$data->optionb.'" type="text" disabled="">
</div>
<div class="form-group col-lg-6">
  <label for="Optionc">Option C</label>
  <input class="form-control ';
  if($data->answer == '3'){ echo 'correctans';}else{}
  echo'" id="Optionc" value="'.$data->optionc.'" type="text" disabled="">
</div>
<div class="form-group col-lg-6">
  <label for="Optiond">Option D</label>
  <input class="form-control ';
  if($data->answer == '4'){ echo 'correctans';}else{}
  echo'" id="Optiond" value="'.$data->optiond.'" type="text" disabled="">
</div>                                            
</div>
<div class="row">
<div class="form-group col-lg-12 questioninput-size">
<p class="fontsize-p pl-0 ml-0">Correct Answer</p>';
if($data->answer == '1'){ 


echo '<div class="form-check-inline ">
 <input disabled="" type="radio" class="form-check-input disabled" id="Option11" name="materialExampleRadios" 
 ';  if($data->answer == '1'){ echo 'checked';}else{} echo'>
 <label class="form-check-label" for="Option11">Option A</label>
</div>';
}
if($data->answer == '2'){

echo '<div class="form-check-inline ">
 <input disabled="" type="radio" class="form-check-input disabled" id="Option22" name="materialExampleRadios" 
 ';  if($data->answer == '2'){ echo 'checked';}else{} echo'>
 <label class="form-check-label" for="Option22">Option B</label>
</div>';
}
if($data->answer == '3'){
    echo'
<div class="form-check-inline ">
 <input disabled="" type="radio" class="form-check-input disabled" id="Option33" name="materialExampleRadios"
 ';  if($data->answer == '3'){ echo 'checked';}else{} echo'>
 <label class="form-check-label" for="Option33">Option C</label>
</div>';
}
if($data->answer == '4'){
    echo'

<div class="form-check-inline ">
 <input  type="radio" class="form-check-input disabled" id="Option44" name="materialExampleRadios"
 ';  if($data->answer == '4'){ echo 'checked';}else{} echo'>
 <label class="form-check-label" for="Option44">Option D</label>
</div>  ';
}
echo'
</div>
</div>


</div>                  
                    
</div>
</div> 
</div>';
        
    }
    public function questions(Request $request)
    { 
        $data = $request->all();
    $data['admin'] = Auth::user();
    $data['country'] = country::getoption();
      $join = DB::table('question_answers');
              $join->leftJoin('users', function ($join) {
            $join->on('users.id', '=', 'question_answers.user_id')
                 ->where('users.status', '!=', '2');
        });
            $join->leftJoin('admins', 'admins.id', '=', 'question_answers.is_admin');
            $join->where('question_answers.qstatus','!=','2');
             $join->where(function ($models) use ($data) {
            if(isset($data['search']) && !empty($data['search']))
            {
                $id = country::like($data['search']);
                $id2 = course::like($data['search']);
                $id3 = Years::like($data['search']);
                $id4 = Grades::like($data['search']);
                if(count($id) > 0)
                {  
                $models->where('question_answers.question', 'like', '%' .$data['search']. '%')->orWhere('users.name', 'like', '%' .$data['search']. '%')->orWhere('question_answers.country', 'like', '%' .$id[0]. '%')->orWhere('question_answers.state', 'like', '%' .$id[0]. '%')->where('question_answers.qstatus','!=','2')->where('question_answers.status','!=','2'); 
                }
                if(count($id2) > 0)
                {   
                    $models->where('question_answers.question', 'like', '%' .$data['search']. '%')->orWhere('users.name', 'like', '%' .$data['search']. '%')->orWhere('question_answers.course', 'like', '%' .$id2[0]. '%')->orWhere('question_answers.subject', 'like', '%' .$id2[0]. '%')->where('question_answers.qstatus','!=','2')->where('question_answers.status','!=','2'); 
                }
                if(count($id3) > 0)
                {  
                    $models->where('question_answers.question', 'like', '%' .$data['search']. '%')->orWhere('users.name', 'like', '%' .$data['search']. '%')->orWhere('question_answers.year', 'like', '%' .$id3[0]. '%')->where('question_answers.qstatus','!=','2')->where('question_answers.status','!=','2'); 
                }
                if(count($id4) > 0)
                {   
                    $models->where('question_answers.question', 'like', '%' .$data['search']. '%')->orWhere('users.name', 'like', '%' .$data['search']. '%')->orWhere('question_answers.grade', 'like', '%' .$id4[0]. '%')->where('question_answers.qstatus','!=','2')->where('question_answers.status','!=','2'); 
                }
               if(count($id) == 0 && count($id2) == 0 && count($id3) == 0 && count($id4) == 0)
                {   
                     $models->where('question_answers.question', 'like', '%' .$data['search']. '%')->orWhere('users.name', 'like', '%' .$data['search']. '%')->orWhere('admins.name', 'like', '%' .$data['search']. '%');
                }
           
            }
            });
            $join->select('question_answers.id as id','question_answers.question','question_answers.type','question_answers.optiona',
            'question_answers.optionb','question_answers.optionc','question_answers.optiond',
            'question_answers.answer','question_answers.id as testid', 'question_answers.country','question_answers.state','question_answers.course',
            'question_answers.grade','question_answers.year', 'question_answers.subject','question_answers.chapter',
            'question_answers.is_admin','question_answers.user_id','question_answers.qstatus','question_answers.created_at','question_answers.qstatus','question_answers.status');
            $join->orderBy('question_answers.id', 'desc');
            $data['questions'] =      $join->paginate(15);
   
            $were = array('status'=>'1');
            $data['q_details'] = $this->selectData("question_answers");
            $data['grades']= Grades::getbycondition($were);
            $data['years']= Years::getbycondition($were);
            $data['course'] = course::getoption();
            $data['title']='Questions-Multiple Choice Online';
            $data['page']='questions';
            if ($request->ajax()) {
            return view('admin.question2',$data);
            }
    return view('/admin/questions',$data); 
    }
    public function total_test($id='')
    {
         $data['admin'] = Auth::user();
        $data['test'] = $this->selectData("user_test",array('user_id'=>$id),'','id');
        $data['title']='Total Test-Multiple Choice Online';
         $data['page']='users';
        return view('/admin/total_test',$data); 
    }
    public function user_details($id='')
    {
         $data['admin'] = Auth::user();
         $data['user'] = $this->selectData("users",array('id'=>$id));
        $data['user'][0]->id;
        $data['transactions'] = DB::table('transaction')
            //->join('users_hours', 'transaction.package_id', '=', 'users_hours.package_id')
            ->join('users_hours', 'transaction.user_id', '=', 'users_hours.user_id')
            ->join('subscription_content', 'transaction.package_id', '=', 'subscription_content.id')
            ->where('transaction.user_id',$data['user'][0]->id)
            ->select('transaction.transaction_id','transaction.amount','users_hours.expiry',
            'subscription_content.title')
            ->get();
            $data['transactions'] = Transaction::getbycondition(array('user_id'=>$data['user'][0]->id));
         $data['title']='User Detail-Multiple Choice Online';
         $data['page']='users';
        return view('/admin/user_details',$data); 
    }
    
     public function edituser($id='')
    {
         $data['admin'] = Auth::user();
         $data['users'] = $this->selectData("users",array('id'=>$id));
         $data['title']='User Detail-Multiple Choice Online';
         $data['page']='users';
         //$data['countries'] = $this->selectData("country",array('parent'=>'0','status'=>'1'));
         $data['countries']= country::getoption();
          if(!empty($data['users'][0]->country))
            {
                 $gettagss = [['parent', '=', $data['users'][0]->country],['status', '=', '1']]; 
            }else
            {
                $gettagss = [['parent', '!=', 0],['status', '=', '1']]; 
            }
            
             $data['states']=country::getbycondition($gettagss);
        return view('/admin/edituser',$data); 
    }
     public function reports($id='')
    {
         $data['admin'] = Auth::user();
         $data['title']='Reports-Multiple Choice Online';
         $data['page']='reports';
         if($id !='')
         {
             $data['scroll']='1';
            return view('/admin/reports',$data);    
         }else
         { $data['scroll']='0';
           return view('/admin/reports',$data);    
         }
       
    }
    public function getreports()
    {
       $were1=[['status','=','1']];
      //   $MonthNumbers = User::getbycondition($were1); 
         
            $users = User::select('id', 'created_at')
            ->where('status','1')
            ->get()
            ->groupBy(function($date) {
            //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
            return Carbon::parse($date->created_at)->format('m'); // grouping by months
            });
            
            $usermcount = [];
            $userArr = [];
            
            foreach ($users as $key => $value) {
            $usermcount[(int)$key] = count($value);
            }
            
            for($i = 1; $i <= 12; $i++){
            if(!empty($usermcount[$i])){
            $userArr[$i] = $usermcount[$i];    
            }else{
            $userArr[$i] = 0;    
            }
            }
            
            
            $users2 = User::select('id', 'created_at')
            ->where('status','0')
            ->get()
            ->groupBy(function($date) {
            //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
            return Carbon::parse($date->created_at)->format('m'); // grouping by months
            });
            
            $usermcount2 = [];
            $userArr2 = [];
            
            foreach ($users2 as $key => $value) {
            $usermcount2[(int)$key] = count($value);
            }
            
            for($i = 1; $i <= 12; $i++){
            if(!empty($usermcount2[$i])){
            $userArr2[$i] = $usermcount2[$i];    
            }else{
            $userArr2[$i] = 0;    
            }
            }
            
             $users3 = User::select('id', 'created_at')
            ->where('status','2')
            ->get()
            ->groupBy(function($date) {
            //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
            return Carbon::parse($date->created_at)->format('m'); // grouping by months
            });
            
            $usermcount3 = [];
            $userArr3 = [];
            
            foreach ($users3 as $key => $value) {
            $usermcount3[(int)$key] = count($value);
            }
            
            for($i = 1; $i <= 12; $i++){
            if(!empty($usermcount3[$i])){
            $userArr3[$i] = $usermcount3[$i];    
            }else{
            $userArr3[$i] = 0;    
            }
            }
            $array1 = implode(', ',$userArr);
            $array2 = implode(', ',$userArr2);
            $array3 = implode(', ',$userArr3);
            echo json_encode(array('result1'=>$userArr,'result2'=>$userArr2,'result3'=>$userArr3));
         die; 
    }
    
    public function agetquestiondata()
    {
        $were1=[['status','=','1']];
        
        $users = DB::table('pre_questiondetails')
            ->join('question_answers', 'question_answers.question_id', '=', 'pre_questiondetails.id')
             ->join('country', 'country.id', '=', 'pre_questiondetails.country')
             ->where('pre_questiondetails.is_admin','0')
             ->where('pre_questiondetails.status','!=','2')
            ->select('pre_questiondetails.created_at')
            ->get();
            
     $admin = DB::table('pre_questiondetails')
            ->join('question_answers', 'question_answers.question_id', '=', 'pre_questiondetails.id')
             ->join('country', 'country.id', '=', 'pre_questiondetails.country')
             ->where('pre_questiondetails.is_admin','1')
             ->where('pre_questiondetails.status','!=','2')
            ->select('pre_questiondetails.created_at')
            ->get();
          
            
            $usermcount = [];
            $usermcount3 =[];
            $userArr = [];
            
            $a='1';  $b='1';  $c='1';  $d='1';  $e='1';  $f='1';  $g='1';  $h='1';  $i='1';  $j='1'; $k='1'; $l='1';
            foreach ($users as $key => $value) {
              
              if(Carbon::parse($value->created_at)->format('m')=='1')
              {   
                  $usermcount[1]= $a++;
              }else if(Carbon::parse($value->created_at)->format('m')=='2')
              { 
                  $usermcount[2]= $b++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='3')
              {  
                $usermcount[3]= $c++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='4')
              {
                  
                 $usermcount[4]= $d++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='5')
              {  
                 $usermcount[5]= $e++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='6')
              {  
                 $usermcount[6]= $f++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='7')
              { 
                  $usermcount[7]= $g++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='8')
              {
                 $usermcount[8]= $h++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='9')
              { 
                  $usermcount[9]= $i++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='10')
              { 
                 $usermcount[10]= $j++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='11')
              {  
                  $usermcount[11]= $k++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='12')
              {  
                  $usermcount[12]= $l++;
              }
            }
            
            for($i = 1; $i <= 12; $i++){
            if(!empty($usermcount[$i])){
            $userArr[$i] = $usermcount[$i];    
            }else{
            $userArr[$i] = 0;    
            }
            }
            
            
            
            $adminmcount = [];
            $adminmcount3 =[];
            $adminArr = [];
            
            $a='1';  $b='1';  $c='1';  $d='1';  $e='1';  $f='1';  $g='1';  $h='1';  $i='1';  $j='1'; $k='1'; $l='1';
            foreach ($admin as $key => $value) {
              
              if(Carbon::parse($value->created_at)->format('m')=='1')
              {   
                  $adminmcount[1]= $a++;
              }else if(Carbon::parse($value->created_at)->format('m')=='2')
              { 
                  $adminmcount[2]= $b++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='3')
              {  
                $adminmcount[3]= $c++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='4')
              {
                  
                 $adminmcount[4]= $d++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='5')
              {  
                 $adminmcount[5]= $e++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='6')
              {  
                 $adminmcount[6]= $f++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='7')
              { 
                  $adminmcount[7]= $g++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='8')
              {
                 $adminmcount[8]= $h++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='9')
              { 
                  $adminmcount[9]= $i++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='10')
              { 
                 $adminmcount[10]= $j++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='11')
              {  
                  $adminmcount[11]= $k++;
              }
              else if(Carbon::parse($value->created_at)->format('m')=='12')
              {  
                  $adminmcount[12]= $l++;
              }
            }
            
            for($i = 1; $i <= 12; $i++){
            if(!empty($adminmcount[$i])){
            $adminArr[$i] = $adminmcount[$i];    
            }else{
            $adminArr[$i] = 0;    
            }
            }
          echo json_encode(array('result12'=>$userArr,'result23'=>$adminArr));
         die; 
    }
    
    public function getattempttest()
    {
        $were1=[['status','=','1']];
           $users = User_test::select('id', 'test_date')
            ->get()
            ->groupBy(function($date) {
            return Carbon::parse($date->test_date)->format('m'); // grouping by months
            });
            
            $usermcount = [];
            $userArr = [];
            
            foreach ($users as $key => $value) {
            $usermcount[(int)$key] = count($value);
            }
            
            for($i = 1; $i <= 12; $i++){
            if(!empty($usermcount[$i])){
            $userArr[$i] = $usermcount[$i];    
            }else{
            $userArr[$i] = 0;    
            }
            }
    echo json_encode(array('usertest'=>$userArr));
         die; 
            
    }
    
    
    public function getCreatedAtAttribute($value)
    {
        $date = Carbon::parse($value);
        return $date->format('m');
    }
    
    public function country()
    {
        $data['admin'] = Auth::user();
        $data['country'] = Country::getoption();
        $data['title']='Country-Multiple Choice Online';
        $data['page']='country';
        return view('/admin/country',$data);  
        
    }
     public function state_province()
    {
        $data['admin'] = Auth::user();
        $data['title']='State/Province-Multiple Choice Online';
        $data['page']='state_province';
        $data['state'] = country::getoptionstates();
       
        return view('/admin/state_province',$data);  
        
    }
    
    public function addfilter_country($id='')
    {
        if(empty($id)){
        $data['admin'] = Auth::user();
        $data['title']='Add Country-Multiple Choice Online';
        $data['page']='country';
        return view('/admin/addfilter_country',$data);  
        }else
        {
        $data['title']='Edit Country-Multiple Choice Online';
        $data['page']='country';
        $data['c_details'] = $this->selectData("country",array('id'=>$id));
        $data['admin'] = Auth::user();
        return view('/admin/addfilter_country',$data);  
        }
    }
    
    public function substate($id='')
    {
       if($id !='')
       {
        //$childs = $this->selectData("country",array('parent'=>$id,'status'=>'1'));
        $childs = country::getbycondition(array('parent'=>$id,'status'=>'1'));
        if(count($childs) == '0')
        {
           
             echo "<option value=''>No data found</option>";
        }else
        {
            echo "<option value=''>State/Province</option>";
            foreach($childs as $c)
            {
            echo "<option value=$c->id>$c->name</option>";
            }
        }
       }else
       {
         echo "<option value=''>No data found</option>";  
       }
    }
    public function subchapter($id='')
    {
         //$childs = $this->selectData("courses",array('parent'=>$id,'status'=>'1'));
         if($id !='')
         {
          $childs=course::getbycondition(array('parent'=>$id,'status'=>'1'));
         if(count($childs) == '0')
        {
             echo "<option value=''>No data found</option>";
        }else
        {
            echo "<option value=''>Chapter</option>";
            foreach($childs as $c)
            {
            echo "<option value=$c->id>$c->name</option>";
            }
        }
      }else
      {
         echo "<option value=''>No data found</option>";  
      }
 
        
    }
    public function add_question(Request $request)
    {
      $table='question_answers';
      $data = [
     'user_id' =>  '0' ,
     'is_admin' => Auth::user()->id,
     'country' => ( $request->input('country')!='') ? $request->input('country') :'0',
     'state' => ( $request->input('state')!='') ? $request->input('state') :'0',
     'course' => ( $request->input('course')!='') ? $request->input('course') :'0',
     'grade' => ( $request->input('grade')!='') ? $request->input('grade') :'0',
     'year' => ( $request->input('year')!='') ? $request->input('year') :'0',
     'subject' => ( $request->input('subject')!='') ? $request->input('subject') :'0',
     'chapter' => ( $request->input('chapter')!='') ? $request->input('chapter') :'0',
     'status'=>'1',
     'type' => ( $request->input('type')!='') ? $request->input('type') :'1',
     'question' => $request->input('question'),
     'optiona' => ( $request->input('optiona')!='') ? $request->input('optiona') :'0',
     'optionb' => $request->input('optionb'),
     'optionc' => $request->input('optionc'),
     'optiond' => $request->input('optiond'),
     'answer' => ( $request->input('answer')!='') ? $request->input('answer') :'0',
     'qstatus'=>'1'  
            
        ];
       // $data = array_filter($data);
        
    if($request->input('id') != '')
    {
     /*$table1='question_answers';
      $data1 = [
     
     'question' => $request->input('question'),
     'optiona' => ( $request->input('optiona')!='') ? $request->input('optiona') :'0',
     'optionb' => $request->input('optionb'),
     'optionc' => $request->input('optionc'),
     'optiond' => $request->input('optiond'),
     'answer' => ( $request->input('answer')!='') ? $request->input('answer') :'0',
    'qstatus'=>'1'        
        ];
          $this->updateData($table1,array('id'=>$request->input('id')), $data1);   
          if($request->input('pre_questiondetails_id'))
          { */  unset($data['user_id']);
            unset($data['is_admin']);
            $this->updateData($table,array('id'=>$request->input('id')), $data); 
         /* }*/
    }else{
      $this->insertData($table, $data);
   
     /* $table1='question_answers';
      $data1 = [
     'question_id' =>$id,
     'question' => $request->input('question'),
     'optiona' => ( $request->input('optiona')!='') ? $request->input('optiona') :'0',
     'optionb' => $request->input('optionb'),
     'optionc' => $request->input('optionc'),
     'optiond' => $request->input('optiond'),
     'answer' => ( $request->input('answer')!='') ? $request->input('answer') :'0',
      'qstatus'=>'1'        
        ];
   $this->insertData($table1, $data1);*/
    }
      echo '1';die;
      
    }
    public function subcategory($id='')
    {
        if($id != '')
        {
        //$childs = $this->selectData("courses",array('parent'=>$id));
         $childs=course::getbycondition(array('parent'=>$id,'status'=>'1'));
         if(count($childs) == '0')
        {
             echo "<option value=''>No data found</option>";
        }else
        {
            echo "<option value=''>Subject </option>";
            foreach($childs as $c)
            {
            echo "<option value=$c->id>$c->name</option>";
            }
        }
      }else
      {
        echo "<option value=''>No data found</option>";  
      }

    }
    
    public function delete1($id1,$id2)
    {
        if($id1 == 'question_answers')
        {
           $data = [
           'status' => '2',
           'qstatus'=> '2',
          ]; 
          $this->updateData($id1,array('id'=>$id2), $data);   
           echo '1'; die; 
        }
        if(!empty($id1) && !empty($id2))
        {
           $delete=Question_answers::deleteque($id2);
           echo '1';
        }
        
    }
    
    public function delete112($id1,$id2)
    {
      $data = [
           'status' => '2',
            
        ];
     $this->updateData($id1,array('id'=>$id2), $data);   
     
     if($id1="courses"){
         $query = "CAST(name AS UNSIGNED), name ASC";
        $data['chapter'] = course::where('status','1')->where('parent','!=','0')->where('type','=','3')->orderByRaw($query)->get();
        foreach($data['chapter'] as $c)
        {
            
            $parid = course::getparentpar($c->parent);
             $par = course::getparent($c->parent);
              $pars = course::getparent($parid);
            $base=url('admin/addfilter_chapter/'.$c->id);
             echo "<tr>
             <td>$pars</td>
             <td>$par</td>
             <td>$c->name</td>
             <td><a href='$base' class='btn-info mr-1'><i class='mdi mdi-pencil'>
             </i></a>
             <a href='javascript:void(0);' data-url-param1='courses'
                data-url-param2='$c->id' class='btn-danger delete_request112'   data-toggle='modal' data-target='#delete_country112'>
                <i class='mdi mdi-delete'></i></a></td>
             </tr>";
        }  
     }
    }
     public function delete111($id1,$id2)
    {
      $data = [
           'status' => '2',
            
        ];
     $this->updateData($id1,array('id'=>$id2), $data);   
     if($id1 =="country")
     {
        $data['country'] = country::getoptionstates();
        foreach($data['country'] as $c)
        {
             $par = country::getparent($c->parent);
            $base=url('admin/addfilter_state/'.$c->id);
             echo "<tr>
             <td>$par</td>
             <td>$c->name</td>
             <td><a href='$base' class='btn-info mr-1'><i class='mdi mdi-pencil'>
             </i></a>
             <a href='javascript:void(0);' data-url-param1='country'
                data-url-param2='$c->id' class='btn-danger delete_request111'   data-toggle='modal' data-target='#delete_country111'>
                <i class='mdi mdi-delete'></i></a></td>
             </tr>";
        }
     }
     elseif($id1="courses"){
        $data['subject'] = course::where('status','1')->where('parent','!=','0')->where('type','=','2')->get();
        foreach($data['subject'] as $c)
        {
             $par = course::getparent($c->parent);
            $base=url('admin/addfilter_subject/'.$c->id);
             echo "<tr>
             <td>$par</td>
             <td>$c->name</td>
             <td><a href='$base' class='btn-info mr-1'><i class='mdi mdi-pencil'>
             </i></a>
             <a href='javascript:void(0);' data-url-param1='courses'
                data-url-param2='$c->id' class='btn-danger delete_request111'   data-toggle='modal' data-target='#delete_country111'>
                <i class='mdi mdi-delete'></i></a></td>
             </tr>";
        }  
     }
    }
    public function delete($id1,$id2)
    { 
      $data = [
           'status' => '2',
            
        ];
     $this->updateData($id1,array('id'=>$id2), $data);   
     
     if($id1 =="country")
     {
        $data['country'] = Country::getoption();
        foreach($data['country'] as $c)
        {
            $base=url('admin/addfilter_country/'.$c->id);
             echo "<tr><td>$c->name</td>
             <td><a href='$base' class='btn-info mr-1'><i class='mdi mdi-pencil'>
             </i></a>
             <a href='javascript:void(0);' data-url-param1='country'
                data-url-param2='$c->id' class='btn-danger delete_request'   data-toggle='modal' data-target='#delete_country'>
                <i class='mdi mdi-delete'></i></a></td>
             </tr>";
        }
     }elseif($id1 =="users")
     {
         $datas2 = $this->selectData("notification",array('from_id'=>$id2));
         if(count($datas2) > 0)
        {
          $this->updateData('notification',array('from_id'=>$id2), $data);
        }
        $datas1 = $this->selectData("withdraw",array('uid'=>$id2,'status'=>'0'));
        if(count($datas1) > 0)
        {
          $this->updateData('withdraw',array('uid'=>$id2,'status'=>'0'), array('status'=>'1'));
        }
        $datas = $this->selectData("question_answers",array('user_id'=>$id2));
        if(count($datas) > 0)
        {
            $this->updateData('question_answers',array('user_id'=>$id2), array('qstatus'=>'2','status'=>'2'));
          // $this->updateData('pre_questiondetails',array('user_id'=>$id2), $data);
           /*foreach($datas as $datask)
           {
               $this->updateData('question_answers',array('user_id'=>$id2), $data);*/
              // DB::table('question_answers')->where('question_id', '=', $datask->id)->delete();
          /* }*/
        }
     }elseif($id1 =="courses")
     {
          $data['course'] = course::getoption();
         foreach($data['course'] as $c)
        {
            $base=url('admin/addfilter_course/'.$c->id);
             echo "<tr><td>$c->name</td>
             <td><a href='$base' class='btn-info mr-1'><i class='mdi mdi-pencil'>
             </i></a>
             <a href='javascript:void(0);' data-url-param1='courses'
                data-url-param2='$c->id' class='btn-danger delete_request'   data-toggle='modal' data-target='#delete_country'>
                <i class='mdi mdi-delete'></i></a></td>
             </tr>";
        }
     }elseif($id1 =="grades")
     {
          //$data['grades'] = $this->selectData("grades",array('status'=>'1'));
          $data['grades'] = Grades::getbycondition(array('status'=>'1'));
         foreach($data['grades'] as $c)
        {
            $base=url('admin/addfilter_grade_level/'.$c->id);
             echo "<tr><td>$c->name</td>
             <td><a href='$base' class='btn-info mr-1'><i class='mdi mdi-pencil'>
             </i></a>
             <a href='javascript:void(0);' data-url-param1='grades'
                data-url-param2='$c->id' class='btn-danger delete_request'   data-toggle='modal' data-target='#delete_country'>
                <i class='mdi mdi-delete'></i></a></td>
             </tr>";
        }
     }elseif($id1 =="years")
     {
          //$data['year'] = $this->selectData("years",array('status'=>'1'));
          $data['year'] = Years::getbycondition(array('status'=>'1'));
         foreach($data['year'] as $c)
        {
            $base=url('admin/addfilter_year/'.$c->id);
             echo "<tr><td>$c->name</td>
             <td><a href='$base' class='btn-info mr-1'><i class='mdi mdi-pencil'>
             </i></a>
             <a href='javascript:void(0);' data-url-param1='years'
                data-url-param2='$c->id' class='btn-danger delete_request'   data-toggle='modal' data-target='#delete_country'>
                <i class='mdi mdi-delete'></i></a></td>
             </tr>";
        }
     }elseif($id1 =="faqs" || $id1 =="testimonials")
     {
         return 1;
     }
     
    }
    
     public function addfilter_chapter_store(Request $request)
    {
        
       
        
    $table='courses';
       $data = [
           'name' => $request->input('name'),
           'parent' => $request->input('parent'),
           'type'=>'3',
            
        ];
        
    if($request->input('id') != '')
    {
    $this->updateData($table,array('id'=>$request->input('id')), $data);   
    }else{
    $this->insertData($table, $data);
    }
    echo 1; die;
            
    }
    
      public function addfilter_subject_store(Request $request)
    {
    $table='courses';
       $data = [
           'name' => $request->input('name'),
           'parent' => $request->input('parent'),
           'type'=>'2',
            
        ];
        
    if($request->input('id') != '')
    {
    $this->updateData($table,array('id'=>$request->input('id')), $data);   
    }else{
    $this->insertData($table, $data);
    }
    echo 1; die;
            
    }
    
    public function addfilter_state_store(Request $request)
    {
    $table='country';
       $data = [
           'name' => $request->input('name'),
           'parent' => $request->input('parent'),
            
        ];
        
    if($request->input('id') != '')
    {
    $this->updateData($table,array('id'=>$request->input('id')), $data);   
    }else{
    $this->insertData($table, $data);
    }
    echo 1; die;
            
    }
     public function addfilter_year_store(Request $request)
    {
    $table='years';
       $data = [
           'name' => $request->input('name'),
          
        ];
        
    if($request->input('id') != '')
    {
    $this->updateData($table,array('id'=>$request->input('id')), $data);   
    }else{
    $this->insertData($table, $data);
    }
    echo 1; die;
            
    }
    
    
     public function addfilter_grade_store(Request $request)
    {
    $table='grades';
       $data = [
           'name' => $request->input('name'),
            
        ];
        
    if($request->input('id') != '')
    {
    $this->updateData($table,array('id'=>$request->input('id')), $data);   
    }else{
    $this->insertData($table, $data);
    }
    echo 1; die;
            
    }
    public function addfilter_country_store(Request $request)
    {
    $table='country';
       $data = [
           'name' => $request->input('name'),
            
        ];
        
    if($request->input('id') != '')
    {
    $this->updateData($table,array('id'=>$request->input('id')), $data);   
    }else{
    $this->insertData($table, $data);
    }
    echo 1; die;
            
    }
    public function addfilter_state($id='')
    { 
        
        if(empty($id)){
        $data['admin'] = Auth::user();
         $data['title']='Add State/Province-Multiple Choice Online';
        $data['page']='state_province';
        //$data['allcountries'] = $this->selectData("country",array('parent'=>'0','status'=>'1'));
        $data['allcountries'] = country::getoption();
        return view('/admin/addfilter_state',$data);  
        }else
        {
         $data['title']='Edit State/Province-Multiple Choice Online';
         $data['page']='state_province';
         //$data['allcountries'] = $this->selectData("country",array('parent'=>'0','status'=>'1'));
         $data['allcountries'] = country::getoption();
        $data['c_details'] = $this->selectData("country",array('id'=>$id));
        $data['admin'] = Auth::user();
        return view('/admin/addfilter_state',$data);  
        }
    }
    public function addfilter_course($id='')
    {

        
         if(empty($id)){
        $data['admin'] = Auth::user();
        $data['title']='Add Course-Multiple Choice Online';
        $data['page']='course';
        return view('/admin/addfilter_course',$data);  
        }else
        {
        $data['title']='Edit Course-Multiple Choice Online';
        $data['page']='course';
        $data['c_details'] = $this->selectData("courses",array('id'=>$id));
        $data['admin'] = Auth::user();
        return view('/admin/addfilter_course',$data);  
        }
      
    }
      public function addfilter_course_store(Request $request)
    {
    $table='courses';
       $data = [
           'name' => $request->input('name'),
        ];
        
    if($request->input('id') != '')
    {
    $this->updateData($table,array('id'=>$request->input('id')), $data);   
    }else{
    $this->insertData($table, $data);
    }
    echo 1; die;
            
    }
      public function addfilter_grade_level($id='')
    {
         if(empty($id)){
        $data['admin'] = Auth::user();
         $data['title']='Add Grade/Level-Multiple Choice Online';
         $data['page']='grade_level';
        return view('/admin/addfilter_grade_level',$data);  
        }else
        {
            $data['title']='Edit Grade/Level-Multiple Choice Online';
         $data['page']='grade_level';
        $data['c_details'] = $this->selectData("grades",array('id'=>$id));
        $data['admin'] = Auth::user();
        return view('/admin/addfilter_grade_level',$data);  
        }
        
    }
    
      public function addfilter_year($id='')
    {
        if(empty($id)){
        $data['admin'] = Auth::user();
        $data['title']='Add Year-Multiple Choice Online';
         $data['page']='year';
        return view('/admin/addfilter_year',$data);  
        }else
        {
         $data['title']='Edit Year-Multiple Choice Online';
         $data['page']='year';
        $data['c_details'] = $this->selectData("years",array('id'=>$id));
        $data['admin'] = Auth::user();
        return view('/admin/addfilter_year',$data);  
        }
        
    }
    public function addfilter_subject($id='')
    {
        
        if(empty($id)){
        $data['admin'] = Auth::user();
       // $data['allcourses'] = $this->selectData("courses",array('parent'=>'0','status'=>'1','type'=>'1'));
        $data['allcourses'] = course::getbycondition(array('parent'=>'0','status'=>'1','type'=>'1'));
        $data['title']='Add Subject-Multiple Choice Online';
         $data['page']='subject';
        return view('/admin/addfilter_subject',$data);  
        }else
        {
        $data['c_details'] = $this->selectData("courses",array('id'=>$id));
         //$data['allcourses'] = $this->selectData("courses",array('parent'=>'0','status'=>'1','type'=>'1'));
         $data['allcourses'] = course::getbycondition(array('parent'=>'0','status'=>'1','type'=>'1'));
         $data['title']='Edit Subject-Multiple Choice Online';
         $data['page']='subject';
        $data['admin'] = Auth::user();
        return view('/admin/addfilter_subject',$data);  
        }
        
        
    }
       public function addfilter_chapter($id='')
    {
          if(empty($id)){
        $data['admin'] = Auth::user();
         //$data['allsubjects'] = $this->selectData("courses",array('status'=>'1','type'=>'2'));
         $data['allsubjects'] = course::getbycondition(array('status'=>'1','type'=>'2'));
       // $data['allcourses'] = $this->selectData("courses",array('parent'=>'0','status'=>'1','type'=>'1'));
        $data['allcourses'] = course::getbycondition(array('parent'=>'0','status'=>'1','type'=>'1'));
         $data['title']='Add Chapter-Multiple Choice Online';
         $data['page']='chapter';
        return view('/admin/addfilter_chapter',$data);  
        }else
        {
        $data['c_details'] = $this->selectData("courses",array('id'=>$id));
        //$data['allcourses'] = $this->selectData("courses",array('parent'=>'0','status'=>'1','type'=>'1'));
         $data['allcourses'] = course::getbycondition(array('parent'=>'0','status'=>'1','type'=>'1'));
         //$data['allsubjects'] = $this->selectData("courses",array('status'=>'1','type'=>'2'));
         $data['allsubjects'] = course::getbycondition(array('status'=>'1','type'=>'2'));
        $data['admin'] = Auth::user();
         $data['title']='Edit Chapter-Multiple Choice Online';
         $data['page']='chapter';
        return view('/admin/addfilter_chapter',$data);  
        }
        
    
        
    }
     public function course()
    {
        $data['admin'] = Auth::user();
        $data['course'] = course::getoption();
        $data['title']='Course-Multiple Choice Online';
        $data['page']='course';
        return view('/admin/course',$data);  
        
    }
     public function grade_level()
    {
        $data['admin'] = Auth::user();
         $data['title']='Grade/Level-Multiple Choice Online';
        $data['page']='grade_level';
        $were = array('status'=>'1');
        $data['grades']= Grades::getbycondition($were);
       // $data['grades'] = $this->selectData("grades",array('status'=>'1'),'','id');
        return view('/admin/grade_level',$data);  
        
    }
     public function year()
    {
       // $data['year'] = $this->selectData("years",array('status'=>'1'),'','id');
        $data['year'] = Years::getbycondition(array('status'=>'1'));
        $data['admin'] = Auth::user();
         $data['title']='Year-Multiple Choice Online';
         $data['page']='year';
        return view('/admin/year',$data);  
        
    }
     public function subject()
    {
        $data['admin'] = Auth::user();
        $data['title']='Subject-Multiple Choice Online';
         $data['page']='subject';
        $data['subject'] = course::where('status','1')->where('parent','!=','0')->where('type','=','2')->orderBy('name', 'asc')->get();
        return view('/admin/subject',$data);  
        
    }
      public function chapter()
    {
        $data['admin'] = Auth::user();
        $data['chapter'] = course::where('status','1')->where('parent','!=','0')->where('type','=','3')->orderBy('name', 'asc')->get();
         $data['title']='Chapter-Multiple Choice Online';
         $data['page']='chapter';
        return view('/admin/chapter',$data);  
        
    }
    public function profile()
    {
         $data['admin'] = Auth::user();
        return view('/admin/profile',$data);  
        
    }
    
    public function contactusers()
    {
         if(Auth::user()){
           $data['options'] = Options::getoption();
            $data['title']='Contact Us-Multiple Choice Online';
           $data['page']='contactus';
           return view('/admin/contactusers',$data);
         }else
         {
            return Redirect::back();
         }
         
    }
    
    public function faqs()
    {
      if(Auth::user()){
           $data['options'] = Options::getoption();
            $data['title']='Faq-Multiple Choice Online';
            $data['page']='faqs';
           return view('/admin/faqs',$data);
         }else
         {
            return Redirect::back();
         }  
    }
    
    public function addoptions(Request $request)
    {
       if(Auth::user()){
            $data= $request->all();
            $page= $data['pages'];
             unset($data['_token']);
             unset($data['pages']);
            
             foreach($data as $key => $d)
             {
                 $query= array('coulmn_name'=>$key);
                 $query2= array('coulmn_value'=>$d);
                 $result = Options::getbycondition($query);
                
                 if(count($result) > 0 )
                 { 
                   if(Options::updateoption2($query2,$query))
                   {
                     $messags['message'] = $page." has been updated successfully.";
                     $messags['erro']= 101;   
                   }else
                   {
                       $messags['message'] =  "Error To Update ".$page.".";
                       $messags['erro']= 202; 
                   }
                   
                 }else
                 {
                      if(Options::insertoption($query))
                       {
                            if(Options::updateoption2($query2,$query))
                            {
                                $messags['message'] = $page." has been updated successfully.";
                                $messags['erro']= 101;
                            }else
                            {
                                $messags['message'] =  "Error To Update ".$page.".";
                                $messags['erro']= 202;
                            }  
                       }else
                       {
                           $messags['message'] = "Error To Update ".$page.".";
                           $messags['erro']= 202; 
                       } 
                 }
                 
             }
         }else
         {
            return Redirect::back();
         }  
         echo json_encode($messags);
        die;
    }
    
  public function uploadfile(Request $request)
   {
	   if(Auth::user())
	     {
	       $messags = array();
		       if($request->isMethod('post'))
		          {
		            $data = $request->all();
    			     if($request->file('file'))
    			       {
    			            $image = $request->file('file');
    			            $imagename = time().'.'.$image->getClientOriginalExtension();
    			            $destinationPath = public_path('/pages');
    			            $image->move($destinationPath, $imagename);
                            $path1 = $imagename;
                            $messags['path'] = $path1;
                            $messags['erro']= 101;
    			          
    			       }else if($request->file('questionimg'))
    			       {
    			            $image = $request->file('questionimg');
    			            $imagename = time().'.'.$image->getClientOriginalExtension();
    			            $destinationPath = public_path('/questionimg');
    			            $image->move($destinationPath, $imagename);
                            $path1 = $imagename;
                            $messags['path'] = $path1;
                            $messags['erro']= 101;
    			          
    			       }else
    			       {
    			           
                            $messags['message'] = "Error to upload the image.";
                            $messags['erro']= 202;
    			       }
		         }else
		         {
                        $messags['message'] = "Error to upload the image.";
                        $messags['erro']= 202;
		         }
		         echo json_encode($messags);
                         die;
       }
   }
   
   public function testemonialfile(Request $request)
   {
	   if(Auth::user())
	     {
	       $messags = array();
		       if($request->isMethod('post'))
		          {
		            $data = $request->all();
    			     if($request->file('file'))
    			       {
    			            $image = $request->file('file');
    			            $imagename = time().'.'.$image->getClientOriginalExtension();
    			            $destinationPath = public_path('/testimonials');
    			            $image->move($destinationPath, $imagename);
                            $path1 = $imagename;
                            $messags['path'] = $path1;
                            $messags['erro']= 101;
    			          
    			       }else
    			       {
                            $messags['message'] = "Error to upload the company logo.";
                            $messags['erro']= 202;
    			       }
		         }else
		         {
                        $messags['message'] = "Error to upload the company logo.";
                        $messags['erro']= 202;
		         }
		         echo json_encode($messags);
                         die;
       }
   }
   
   public function faq_list(Request $request)
   {
       if(Auth::user()){
           $data['options'] = Faqs::getoption2();
           $data['title']='Faq List-Multiple Choice Online';
            $data['page']='faq_list';
            if ($request->ajax()) {
            return view('admin.presult',$data);
            }
           
           return view('/admin/faq_list',$data);
         }else
         {
            return Redirect::back();
         }
   }
   
   public function add_faq(Request $request,$id='')
   { 
       if(Auth::user()){
                $messags = array();
		       if($request->isMethod('post'))
		          {
		             $data = $request->all();
		             unset($data['_token']);
		             if(!empty($data['id']))
		             {   $id = $data['id'];
		                  unset($data['id']);
		                 $data = array_filter($data);
		                 if(Faqs::updateoption($data,$id))
		                 {
		                    $messags['message'] = "Faq has been updated successfully.";
                             $messags['erro']= 101; 
		                 }else
		                 {
		                   $messags['message'] = "Error to update a faq.";
                           $messags['erro']= 202;  
		                 }
		             }else
		             {
		                 $data = array_filter($data);
		                 if(Faqs::insertoption($data))
		                 {
		                    $messags['message'] = "Faq has been added successfully.";
                             $messags['erro']= 101; 
		                 }else
		                 {
		                   $messags['message'] = "Error to add a faq.";
                           $messags['erro']= 202;  
		                 }
		             }
		          }else
		          {
		              if(!empty($id))
		              { 
		                   $datas['title']='Edit Faq-Multiple Choice Online';
                           $datas['page']='faq_list';
		                  $datas['optioss']=Faqs::getbycondition(array('id'=>$id));
		                return view('/admin/faq_questions',$datas);  
		              }else
		              {
		                  $data['title']='Add Faq-Multiple Choice Online';
                           $data['page']='faq_list';
		                return view('/admin/faq_questions',$data);    
		              }
		          }
		          
           
         }else
         {
            return Redirect::back();
         }
         echo json_encode($messags);
                         die;
   }
   
   public function about()
   {
        if(Auth::user()){
         $data['options'] = Options::getoption();
         $data['title']='About Us-Multiple Choice Online';
         $data['page']='about';
        return view('/admin/about_content',$data);
        }else
        {
        return Redirect::back();
        }
   }
   
   public function home1()
   {
     
      if(Auth::user()){
         $data['options'] = Options::getoption();
         $data['title']='Home-Multiple Choice Online';
         $data['page']='home1';
        return view('/admin/home',$data);
        }else
        {
        return Redirect::back();
        } 
   }
   
   public function testimonials(Request $request)
   {
       if(Auth::user()){
           $data['testimonials'] = Testimonials::getoption2();
            $data['title']='Testimonials-Multiple Choice Online';
            $data['page']='testimonials';
            if ($request->ajax()) {
            return view('admin.testimonial2',$data);
            }
           
           return view('/admin/testimonials',$data);
         }else
         {
            return Redirect::back();
         }
   }
   
   public function add_testimonial(Request $request,$id='')
   {
      if(Auth::user()){
           $messags = array();
		       if($request->isMethod('post'))
		          {
		             $data = $request->all();
		             unset($data['_token']);
		             if(!empty($data['id']))
		             {   $id = $data['id'];
		                  unset($data['id']);
		                 $status = $data['status'];
		                 $data = array_filter($data);
		                 $data['status'] = $status;
		                 if(Testimonials::updateoption($data,$id))
		                 {
		                    $messags['message'] = "Testimonial has been updated successfully.";
                             $messags['erro']= 101; 
		                 }else
		                 {
		                   $messags['message'] = "Error to update a testimonial.";
                           $messags['erro']= 202;  
		                 }
		             }else
		             {
		                 $data = array_filter($data);
		                 if(Testimonials::insertoption($data))
		                 {
		                    $messags['message'] = "Testimonial has been added successfully.";
                             $messags['erro']= 101; 
		                 }else
		                 {
		                   $messags['message'] = "Error to add a testimonial.";
                           $messags['erro']= 202;  
		                 }
		             }
		             
		              echo json_encode($messags);
                         die;
		          }else
		          {
		              if(!empty($id))
		              { 
		                  $datas['title']='Edit Testimonials-Multiple Choice Online';
                         $datas['page']='testimonials';
		                  $datas['testimonials']=Testimonials::getbycondition(array('id'=>$id));
		                return view('/admin/add_testimonial',$datas);  
		              }else
		              {
		                  $datas['title']='Add Testimonials-Multiple Choice Online';
                          $datas['page']='testimonials';
		                return view('/admin/add_testimonial',$datas);    
		              }
		          }
           
           return view('/admin/add_testimonial',$data);
         }else
         {
            return Redirect::back();
         } 
         
   }
   
   public function referral()
   {
      if(Auth::user()){
         $data['options'] = Options::getoption();
            $data['title']='Referral-Multiple Choice Online';
            $data['page']='referral';
        return view('/admin/referral',$data);
        }else
        {
        return Redirect::back();
        }  
   }
   
   public function settings(Request $request)
   {
       if(Auth::user()){
         $data['options'] = Options::getoption();
          $data['title']='Settings-Multiple Choice Online';
         $data['page']='settings';
        return view('/admin/settings',$data);
        }else
        {
        return Redirect::back();
        } 
   }
   
   public function uploadprofile(Request $request)
   {
   
	   if(Auth::user())
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
			             $admin = Auth::user();
			             $userid =$admin->id;
                          if(Admin::updateUser(array('profile'=>$path1),$userid))
                          {
    			             $messags['path'] = $path1;
    			             $messags['message'] = "Porfile Images uploaded Successfully.";
                            $messags['erro']= 101;
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
		         }else
		         {
		             $messags['message'] = "Error to upload the profile image.";
                     $messags['erro']= 202;
		         }
		         echo json_encode($messags);
                         die;
       }
   }
   
   public function editprofile(Request $request)
   {
        $messags = array();
    if($request->isMethod('post'))
    {
        $data = $request->all();
      if(Auth::user())
      {
              unset($data['_token']);
              $data = array_filter($data);
              $admin = Auth::user();
			  $userid =$admin->id;
              $were= [['email','=',$data["email"]],['id','!=',$userid]];
              $exists= Admin::getUsermatch($were);
              if(count($exists) > 0)
              {
                  $messags['message'] = "Email already exists.";
                  $messags['erro']= 202;   
              }else
              {
                    if(Admin::updateUser($data,$userid))
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
   
   public function updatepassword(Request $request)
   {
         $messags = array();
    if($request->isMethod('post'))
    {
        $data = $request->all();
      if(Auth::user())
      {
          if(!empty($data['oldpassword']))
          {
              
                    if(!Hash::check($data['oldpassword'], Auth::user()->password)){
                       $messags['message'] = "The current password you entered does not match our records, Please try again.";
                        $messags['erro']= 202; 
                        echo json_encode($messags);
                        die;
                    }
          }else
          {
             $messags['message'] = "Current password is required.";
             $messags['erro']= 202; 
             echo json_encode($messags);
              die;
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
                unset($data['_token']);
                $data = array_filter($data);
                $admin = Auth::user();
                $userid =$admin->id;
                    if(Admin::updateUser($data,$userid))
                    {
                    $messags['message'] = "Your password has been updated sucessfully.";
                    $messags['erro']= 101;    
                    }else
                    {
                    $messags['message'] = "Error to update your password.";
                    $messags['erro']= 202;   
                    } 
                    
            }else
            {
              $messags['message'] = "Please enter confirm password same as new password.";
                $messags['erro']= 202;   
            }
          }else
          {
             
                  $messags['message'] = "Error to update password.";
                  $messags['erro']= 202;   
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
   
   
   public function usersubscription($id='')
   {
       if(Auth::user() && $id!='')
      {
        $data['subscription'] = Subscription_content::getbycondition(array('id'=>$id));
         $data['title']='Subscription Content-Multiple Choice Online';
         $data['page']='subscription';
        return view('/admin/usersubscriptionedit',$data);
      }
      else
      {
           return redirect('/admin');
      }
   }
   
   public function editsubcription(Request $request)
   {
        $messags = array();
    if($request->isMethod('post'))
    {
        $data = $request->all();
      if(Auth::user())
      {
          unset($data['_token']);
          $id=$data['id'];
          unset($data['_token']);
       
          if(Subscription_content::updateUser($data,$id))
          {
              $messags['message'] = "Subscrition content has been updated Successfully.";
             $messags['erro']= 101;
          }else
          {
            $messags['message'] = "Error to update subscrition content.";
            $messags['erro']= 202;  
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
   
   public function subescribedusers()
   {
    if(Auth::user())
      {
        $data['subescribedusers'] = Subscribers::getoption();
         $data['title']='Newsletter Users-Multiple Choice Online';
         $data['page']='subescribedusers';
        return view('/admin/subescribedusers',$data);
      }
      else
      {
           return redirect('/admin');
      }
   }
   
   public function deletesusbscribe(Request $request,$id='',$id2='')
   {
      if(Auth::user())
      {
       DB::table($id)->where('id', '=', $id2)->delete();
       $data['subescribedusers'] = Subscribers::getoption();
       
       foreach($data['subescribedusers'] as $sub)
       {
          echo "<tr><td>$sub->email</td><td><a href='javascript:void(0);' data-url-param1='subscribers' data-url-param2='$sub->id' class=' btn-danger deleteses' data-toggle='modal' data-target='#deleteked'>
                 <i class='mdi mdi-delete'></i></a> </td></tr>";
       }
       }
      
      else
      {
           return redirect('/admin');
      } 
   }
   
   public function deletesugesstions(Request $request,$tbl='',$id='')
   {
      if(Auth::user())
      {
        if(User_test_answers::updateoption(array('suggested_answer'=>null,'comment'=>null),$id))
        {
            echo '1'; die; 
        }else
        {
          echo '0'; die;   
        }
       
       }
      
      else
      {
           return redirect('/admin');
      } 
   }
   
   public function footer()
   {
       if(Auth::user())
      {
        $data['options'] = Options::getoption();
        $data['title']='Footer Content-Multiple Choice Online';
        $data['page']='footer';
        return view('/admin/footer_content',$data);
      }
      else
      {
           return redirect('/admin');
      } 
   }
    
  public function pricing()
  {
    if(Auth::user())
      {
        $data['options'] = Options::getoption();
        $data['title']='Pricing Content-Multiple Choice Online';
        $data['page']='pricing';
        return view('/admin/pricing_content',$data);
      }
      else
      {
           return redirect('/admin');
      }   
  }
  
   public function withdraw()
  {
    if(Auth::user())
      {
        $data['title']='Withdraw Request-Multiple Choice Online';
        $data['page']='withdraw';
        $data['applyrequests'] = Withdraw::getjoin();
        foreach($data['applyrequests'] as $key=>$applyreq)
        {
            $were = array('uid'=>$applyreq->id,'status'=>'2');
            $applyrequests = Withdraw::getbycondition($were);
            $were2 = array('uid'=>$applyreq->id,'status'=>'1');
            $reffered = Reffer::getbycondition($were2);
            $transactions =  Transaction::getbycondition(array('user_id'=>$applyreq->id));
            $walletamount=0;

            foreach($reffered as $reffer)
            {
                if($reffer->amount != '')
                {
                   $walletamount += $reffer->amount; 
                }
            
            }
            foreach($transactions as $reffer)
            {
                if(!empty($reffer->walletuse))
                {
            $walletamount -= $reffer->walletuse;
                }
            }
            $withdrwaamount=0;
            foreach($applyrequests as $reffers)
            {
                if(!empty($reffers->amount))
                {
                 $walletamount -= $reffers->amount;
                 $withdrwaamount +=$reffers->amount;
                }
            }
            $data['applyrequests'][$key]->walletamount=$walletamount;
            $data['applyrequests'][$key]->withdrwaamount=$withdrwaamount;
        }

        return view('/admin/withdraw',$data);
      }
      else
      {
           return redirect('/admin');
      }   
  }
  
  public function request_detail(Request $request,$id='')
  {
      if(Auth::user())
      {
            $data['title']='Withdraw Request-Multiple Choice Online';
            $data['page']='withdraw';
            $data['user'] = Withdraw::getjoin2($id);
            $were = array('uid'=>$data['user'][0]->id,'status'=>'2');
            $applyrequests = Withdraw::getbycondition($were);
            $were2 = array('uid'=>$data['user'][0]->id,'status'=>'1');
            $reffered = Reffer::getbycondition($were2);
            $transactions =  Transaction::getbycondition(array('user_id'=>$data['user'][0]->id));
            $data['walletamount']=0;
            foreach($reffered as $reffer)
            {
                if($reffer->amount != '')
                {
                 $data['walletamount'] += $reffer->amount;
                }
            }
            
            foreach($transactions as $reffers)
            {
                if(!empty($reffer->walletuse))
                {
                 $data['walletamount'] -= $reffers->walletuse;
                }
            }
            
            $data['withdrwaamount']=0;
            foreach($applyrequests as $reffers)
            {
                if(!empty($reffer->amount))
                {
                $data['walletamount'] -= $reffers->amount;
                $data['withdrwaamount'] +=$reffers->amount;
                }
            }
            //echo '<pre>'; print_r($data); die; 
        return view('/admin/withdrawdetail',$data);
      }
      else
      {
           return redirect('/admin');
      } 
  }
  
  public function requestaction(Request $request)
      {
          $messags = array();
          if(Auth::user())
          {
             
                if($request->isMethod('post'))
                {
                    $data = $request->all(); 
                   if(isset($data['id']) && isset($data['status']))
                   {
                       $were = array('id'=>$data['id'],'status'=>'0');
                       $checkdata = Withdraw::getbycondition($were);
                       if(count($checkdata) > 0)
                       {
                           $update = array('status'=>$data['status']);
                           if(Withdraw::updateoption($update,$data['id']))
                           {
                               
                            $uid= Withdraw::getdetailsuserret2(array('id'=>$data['id']),'uid');
                            $amount= Withdraw::getdetailsuserret2(array('id'=>$data['id']),'amount');
                            $were= [['id','=', $uid]];
                            $user = User::getbycondition($were);
                            foreach($user as $u){
                            $r = $u;
                            }
                            if(count($user)!=0)
                            {
                                if($data['status']=='2')
                                {
                                     $action = 'approve';
                                }else
                                {
                                     $action = 'decline';
                                }
                                    $variavle = Config::get('constants.Requestaction_html');
                                    $variavles = explode(' ',$variavle);
                                    foreach($variavles as $key=> $variavle)
                                    {
                                        if($variavle=='#action#')
                                        {
                                         $variavles[$key]=$action;
                                        }
                                        if($variavle=='#amount#')
                                        {
                                         $variavles[$key]=$amount;
                                        }
                                    }
                                    $variavle2 = Config::get('constants.Requestaction_notification_description');
                                    $variavles2 = explode(' ',$variavle2);
                                    foreach($variavles2 as $key2=> $variavlej)
                                    {
                                        if($variavlej=='#action#')
                                        {
                                         $variavles2[$key2]=$action;
                                        }
                                        if($variavlej=='#amount#')
                                        {
                                         $variavles2[$key2]=$amount;
                                        }
                                    }
                                $id = $r->id; 
                                $name = $r->name;
                                $hash    = md5(uniqid(rand(), true));
                                $string  = $id."&".$hash;
                                 $iv = base64_encode($string);
                               // $htmls = 'Admin '.$action.' your request to withdraw amount of $'.$amount.', Please visit the following link given below:';
                                $htmls = implode(' ',$variavles);
                                $header = Config::get('constants.Requestaction_header');
                                $buttonhtml = Config::get('constants.Requestaction_btn_html');
                                $pass_url  = url('user/wallet/'); 
                                $path = url('resources/views/email.html');
                                $subject = Config::get('constants.Requestaction_subject');
                                $to_email=$r->email;
                                $this->createnotification($id,$name,$htmls,$header,$buttonhtml,$pass_url,$path,$subject,$to_email);
                                 $arrays =[
                                'w_from' => 'admin',
                                'from_id' => '1',
                                'w_to' => 'user',
                                'to_id' => $r->id,
                                'title' => Config::get('constants.Requestaction_notification_title'),
                                'description' => implode(' ',$variavles2),
                                'url' => 'user/wallet/',
                                'tbl'=>'withdraw',
                                'status'=>'1'
                                ];
                                Notification::insertoption($arrays);
                            }
                                
                               if($data['status']=='2')
                               {
                                 $messags['message'] = "Request withdrawal approve successfully.";
                                 $messags['erro']= 101;  
                               }else
                               {
                                  $messags['message'] = "Request withdrawal decline successfully.";
                                 $messags['erro']= 101; 
                               }
                                  
                           }else
                           {
                                $messags['message'] = "Error to approve request.";
                                $messags['erro']= 202;   
                           }
                       
                       }else
                       {
                        $messags['message'] = "Invalid request.";
                        $messags['erro']= 202;   
                       }
                   }
                }else
                {
                  return redirect('/admin');  
                }
          }
          else
          {
            $messags['message'] = "Session has been expierd.";
            $messags['erro']= 202;   
          } 
            echo json_encode($messags);
            die;
      
  }
  
  public function loginregister()
  {
    if(Auth::user())
      {
        $data['options'] = Options::getoption();
        $data['title']='Login/Register Content-Multiple Choice Online';
        $data['page']='loginregister';
        return view('/admin/loginregister',$data);
      }
      else
      {
           return redirect('/admin');
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
    
    public function notification(Request $request)
    {
       if(Auth::user())
      {
          $were = [['w_to','=','admin'],['status','!=','2']];
        $data['notifications'] = Notification::getbycondition2($were);
       // echo '<pre>'; print_r($data['notifications']); die; 
        $data['title']='Notifications-Multiple Choice Online';
        $data['page']='notification';
         if ($request->ajax()) {
          return view('admin.notification2',$data);
        }
        return view('/admin/notification',$data);
      }
      else
      {
           return redirect('/admin');
      } 
    }
    
    public function deletenotifications(Request $request)
    {
         $messags = array();
       if(Auth::user())
      {
            if($request->isMethod('post'))
            {
                $data = $request->all(); 
             
                foreach($data['favorite'] as $d)
                {
                  Notification::updateoption(array('status'=>'2'),$d);
                }
                $messags['message'] = "Notification has been deleted Successfully.";
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
    
     public function deleteall(Request $request)
    {
         $messags = array();
       if(Auth::user())
      {
            if($request->isMethod('post'))
            {
                $data = $request->all(); 
                $tbl = $data['tbl'];
                $data['favorite'] = explode(',',$data['favorite']);
                foreach($data['favorite'] as $d)
                {
                    if($tbl=='question_answers')
                    {
                      DB::table($tbl)
                    ->where('id', $d)
                    ->update(['status' => '2','qstatus'=> '2']);  
                    }else
                    {
                    DB::table($tbl)
                    ->where('id', $d)
                    ->update(['status' => '2']);
                    }
                }
                $messags['message'] = "Record has been deleted Successfully.";
                 $messags['erro']= 101;
            }else
            {
              $messags['message'] = "No data selected.";
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
    
      public function uploadprofiless(Request $request)
   {
	   if(Auth::user())
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
			            
                          if($path1 !='')
                          {
    			             $messags['path'] = $path1;
    			             $messags['message'] = "Porfile Image uploaded Successfully.";
                            $messags['erro']= 101;
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
		         }else
		         {
		             $messags['message'] = "Error to upload the profile image.";
                     $messags['erro']= 202;
		         }
       }
   
       echo json_encode($messags);
                         die;
   }
   
   public function userprofile(Request $request)
   {
       $messags = array();
    if($request->isMethod('post'))
    {
        $data = $request->all();
      if(Auth::user())
      {
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
          if(isset($data['dob']) && !empty($data['dob']))
              {
                  $data['dob'] = date('Y-m-d H:i:s',strtotime($data['dob']));

              }
              unset($data['_token']);
              $data['status']='1';
              //$data = array_filter($data);
              if(isset($data['id']))
              {
                    $userid = $data['id'];
                    unset($data['id']);
                  $were= [['email','=',$data["email"]],['id','!=',$userid],['status','!=','2']];
                  $exists= User::getUsermatch($were);
                  if(count($exists) > 0)
                  {
                      $messags['message'] = "Email already exist.";
                      $messags['erro']= 202;   
                  }else
                  {   
                      if(!empty($data["phone"]))
                      {
                      $were= [['phone','=',$data["phone"]],['id','!=',$userid],['status','!=','2']];
                      $exists= User::getUsermatch($were);
                      if(count($exists) > 0)
                      {
                          $messags['message'] = "Phone number is already exist.";
                          $messags['erro']= 202;   
                      }else
                      {
                        if(User::updateUser($data,$userid))
                        {
                         $messags['message'] = "User profile has been updated sucessfully.";
                         $messags['erro']= 101;    
                        }else
                        {
                         $messags['message'] = "Error to update your profile.";
                         $messags['erro']= 202;   
                        } 
                      }
                      }else
                      {
                         if(User::updateUser($data,$userid))
                        {
                         $messags['message'] = "User profile has been updated sucessfully.";
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
                $were= [['email','=',$data["email"]],['status','!=','2']];
                  $exists= User::getUsermatch($were);
                  if(count($exists) > 0)
                  {
                      $messags['message'] = "Email already exist.";
                      $messags['erro']= 202;   
                  }else
                  {    
                      if(!empty($data["phone"]))
                      {
                      $were= [['phone','=',$data["phone"]],['status','!=','2']];
                      $exists= User::getUsermatch($were);
                      if(count($exists) > 0)
                      {
                          $messags['message'] = "Phone number is already exist.";
                          $messags['erro']= 202;   
                      }else
                      { 
                          $data['package_id']='1';
                            if(User::insertUser($data))
                            {
                               $userdatas = User::getbycondition($were);
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
                                   
                                    
                                    $transaction_data=array(
                                    'transaction_id'=>'0',
                                    'user_id'=>$users->id,
                                     'package_id'=>'1',
                                     'status'=>'completed',
                                     'currency'=>'USD',
                                    'amount'=>'0',
                        
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
                                    }
                             $messags['message'] = "User has been added successfully.";
                             $messags['erro']= 101;    
                            }else
                            {
                             $messags['message'] = "Error to add user.";
                             $messags['erro']= 202;   
                            } 
                      }
                      }else
                      {
                         $data['package_id']='1';
                            if(User::insertUser($data))
                            {
                               $userdatas = User::getbycondition($were);
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
                                   
                                    
                                    $transaction_data=array(
                                    'transaction_id'=>'0',
                                    'user_id'=>$users->id,
                                     'package_id'=>'1',
                                     'status'=>'completed',
                                     'currency'=>'USD',
                                    'amount'=>'0',
                        
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
                                    }
                             $messags['message'] = "User has been added successfully.";
                             $messags['erro']= 101;    
                            }else
                            {
                             $messags['message'] = "Error to add user.";
                             $messags['erro']= 202;   
                            }  
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
        return redirect('/admin');
    }
     echo json_encode($messags);
     die;
   }
   
   public function subscription_list()
   {
       if(Auth::user())
       {
       $data['admin'] = Auth::user();
         
           $data['transactions'] = DB::table('transaction')
            ->join('users', 'transaction.user_id', '=', 'users.id')
            ->join('users_hours', 'transaction.user_id', '=', 'users_hours.user_id')
            ->join('subscription_content', 'transaction.package_id', '=', 'subscription_content.id')
            ->select('transaction.id','transaction.transaction_id','transaction.amount','users_hours.expiry',
            'subscription_content.title','users.name','users.email','users.lname','transaction.exp','transaction.created','transaction.package_id')
            ->where('transaction.package_id','!=','1')
             ->orderBy('transaction.id', 'desc')
            ->get(); 
         $data['title']='User Subscription List-Multiple Choice Online';
         $data['page']='subscription_list';
        return view('/admin/subscription_list',$data); 
       }else
       {
            return redirect('/admin');
       }
   }
   
   public function adduser()
   {
      if(Auth::user())
       {
         
         $data['title']='Add user-Multiple Choice Online';
         $data['page']='users';
         //$data['countries'] = $this->selectData("country",array('parent'=>'0','status'=>'1'));
         $data['countries']= country::getoption();
        return view('/admin/edituser',$data); 
       }else
       {
            return redirect('/admin');
       } 
   }
   
   public function suggested_answers()
   {
      if(Auth::user())
       {
         
         $data['title']='Suggested Answers-Multiple Choice Online';
         $data['page']='suggested_answers';
         $data['suggested_answers'] = User_test_answers::user_test_answers_get();
        return view('/admin/suggested_answer',$data); 
       }else
       {
            return redirect('/admin');
       } 
   }
   
   public function suggestion_detail($id)
   {
      if(Auth::user())
       {
         
         $data['title']='Suggested Answers Detail-Multiple Choice Online';
         $data['page']='suggested_answers';
         $data['suggested_answers'] = User_test_answers::user_test_answers_get($id);
        return view('/admin/suggestion_detail',$data); 
       }else
       {
            return redirect('/admin');
       } 
   }
   
   public function upload_csv()
   {
    if(Auth::user())
       {
         
         $data['title']='Upload Question-Multiple Choice Online';
         $data['page']='questions';
        return view('/admin/upload_csv',$data); 
       }else
       {
            return redirect('/admin');
       }    
   }
   
   public function uploadcsv(Request $request)
   {
       $messags = array();
    if($request->isMethod('post'))
    {
        $data = $request->all();
      if(Auth::user())
      {
         if(isset($_FILES) && $_FILES["csv"]["size"] > 0){
	  
			$filename=$_FILES["csv"]["tmp_name"];	
			
				if($_FILES["csv"]["size"] > 0)
				{
				$file = fopen($filename, "r");
				
					while (($line = fgetcsv($file)) !== FALSE) {
					$csv[] = $line;
					}
				
				$success = 0;
				$error='0';
				//echo 'totla record is '.count($csv);
				//echo '<pre>'; print_r($csv); json_encode($csv); die; 
                               $datas= array();
                               $datas2= array();
                               $datas3= array();
                               $i = '1';
				foreach($csv as $key=>$getData){
	                        if($key > 0 )
	                        {
    	                        foreach($getData as $ky =>$gets)
    	                        {
    	                             if($ky== '0')
    	                             {   if($gets!='All')
        	                             {
        	                                 $getcountry = country::getoptionmatch([['name','=',$gets],['parent','=','0'],['status','!=','2']]);
        	                                 if(count($getcountry) > 0)
        	                                 {   $getcountry2 = country::getoptionmatch([['name','=',$gets],['parent','=','0'],['status','=','0']]);
            	                                 if(count($getcountry2) > 0)
            	                                 { $getcountry2 = country::updateoption2([['name','=',$gets],['parent','=','0'],['status','=','0']],array('status'=>'1'));
            	                                    $countryid = country::getbycondition(array('name'=>$gets,'parent'=>'0','status'=>'1'));
            	                                     $datas['country'] = $countryid[0]->id;

            	                                 }else
            	                                 {
            	                                     $countryid = country::getbycondition(array('name'=>$gets,'parent'=>'0','status'=>'1'));
            	                                     $datas['country'] = $countryid[0]->id;
            	                                 }
        	                                 }else
        	                                 {  country::insertoption(array('name'=>$gets,'parent'=>'0','status'=>'1'));
        	                                    $datas['country'] = country::getdetailsuserret(array('name'=>$gets,'status'=>'1','parent'=>'0'));
        	                                 }
        	                             }else
        	                             {
        	                                 $datas['country'] = '0';
        	                             }
    	                             }
    	                             
    	                             if($ky== '1')
    	                             {   if($gets!='All' && $datas['country']!='0')
        	                             {
        	                                 $getcountry = country::getoptionmatch([['name','=',$gets],['parent','=',$datas['country']],['status','!=','2']]);
        	                                 if(count($getcountry) > 0)
        	                                 {   $getcountry2 = country::getoptionmatch([['name','=',$gets],['parent','=',$datas['country']],['status','=','0']]);
            	                                 if(count($getcountry2) > 0)
            	                                 { $getcountry2 = country::updateoption2([['name','=',$gets],['parent','=',$datas['country']],['status','=','0']],array('status'=>'1'));
            	                                    $stateid = country::getbycondition(array('name'=>$gets,'parent'=>$datas['country'],'status'=>'1'));
            	                                     $datas['state'] = $stateid[0]->id;
            	                                 }else
            	                                 {
            	                                     $stateid = country::getbycondition(array('name'=>$gets,'parent'=>$datas['country'],'status'=>'1'));
            	                                     $datas['state'] = $stateid[0]->id;
            	                                 }
        	                                 }else
        	                                 {  country::insertoption(array('name'=>$gets,'parent'=>$datas['country'],'status'=>'1'));
        	                                    $datas['state'] = country::getdetailsuserret(array('name'=>$gets,'status'=>'1','parent'=>$datas['country']));
        	                                 }
        	                             }else
        	                             {
        	                                 $datas['state'] = '0';
        	                             }
    	                             }
    	                             
    	                             if($ky== '2')
    	                             {   if($gets!='All')
        	                             {
        	                                 $coursei = course::getoptionmatch([['name','=',$gets],['parent','=','0'],['status','!=','2'],['type','=','1']]);
        	                                 if(count($coursei) > 0)
        	                                 {   $coursei2 = course::getoptionmatch([['name','=',$gets],['parent','=','0'],['status','=','0']]);
            	                                 if(count($coursei2) > 0)
            	                                 { $coursei2 = course::updateoption2([['name','=',$gets],['parent','=','0'],['status','=','0']],array('status'=>'1'));
            	                                    $courseid = course::getbycondition(array('name'=>$gets,'parent'=>'0','type'=>'1','status'=>'1'));
        	                                        $datas['course'] = $courseid[0]->id;
            	                                 }else
            	                                 {
        	                                      $courseid = course::getbycondition(array('name'=>$gets,'parent'=>'0','type'=>'1','status'=>'1'));
        	                                      $datas['course'] = $courseid[0]->id;
            	                                 }
        	                                 }else
        	                                 {  course::insertoption(array('name'=>$gets,'parent'=>'0','status'=>'1','type'=>'1'));
        	                                    $datas['course'] = course::getdetailsuserret(array('name'=>$gets,'status'=>'1','parent'=>'0','type'=>'1'));
        	                                 }
        	                             }else
        	                             {
        	                                 $datas['course'] = '0';
        	                             }
    	                             }
    	                             
    	                             if($ky== '3')
    	                             {   if($gets!='All')
        	                             {
        	                                 $coursei = Grades::getoptionmatch([['name','=',$gets],['status','!=','2']]);
        	                                 if(count($coursei) > 0)
        	                                 {
        	                                     $coursei2 = Grades::getoptionmatch([['name','=',$gets],['status','=','0']]);
            	                                 if(count($coursei2) > 0)
            	                                 { $coursei2 = Grades::updateoption2([['name','=',$gets],['status','=','0']],array('status'=>'1'));
            	                                    $courseid = Grades::getbycondition(array('name'=>$gets,'status'=>'1'));
            	                                     $datas['grade'] = $courseid[0]->id;
            	                                 }else
            	                                 {
            	                                     $courseid = Grades::getbycondition(array('name'=>$gets,'status'=>'1'));
            	                                     $datas['grade'] = $courseid[0]->id;
            	                                 }
        	                                 }else
        	                                 {  Grades::insertoption(array('name'=>$gets,'status'=>'1'));
        	                                    $datas['grade'] = Grades::getdetailsuserret(array('name'=>$gets,'status'=>'1'));
        	                                 }
        	                             }else
        	                             {
        	                                 $datas['grade'] = '0';
        	                             }
    	                             }
    	                             
    	                              if($ky== '4')
    	                             {   if($gets!='All')
        	                             {
        	                                 $coursei = Years::getoptionmatch([['name','=',$gets],['status','!=','2']]);
        	                                 if(count($coursei) > 0)
        	                                 {
        	                                     $coursei2 = Years::getoptionmatch([['name','=',$gets],['status','=','0']]);
            	                                 if(count($coursei2) > 0)
            	                                 { $coursei2 = Years::updateoption2([['name','=',$gets],['status','=','0']],array('status'=>'1'));
            	                                    $courseid = Years::getbycondition(array('name'=>$gets,'status'=>'1'));
            	                                     $datas['year'] = $courseid[0]->id;
            	                                 }else
            	                                 {
            	                                     $courseid = Years::getbycondition(array('name'=>$gets,'status'=>'1'));
            	                                     $datas['year'] = $courseid[0]->id;
            	                                 }
        	                                 }else
        	                                 {  Years::insertoption(array('name'=>$gets,'status'=>'1'));
        	                                    $datas['year'] = Years::getdetailsuserret(array('name'=>$gets,'status'=>'1'));
        	                                 }
        	                             }else
        	                             {
        	                                 $datas['year'] = '0';
        	                             }
    	                             }
    	                             
    	                             if($ky== '5')
    	                             {   if($gets!='All' && $datas['course']!='0')
        	                             {
        	                                 $coursei = course::getoptionmatch([['name','=',$gets],['parent','=',$datas['course']],['status','!=','2'],['type','=','2']]);
        	                                 if(count($coursei) > 0)
        	                                 {   $coursei2 = course::getoptionmatch([['name','=',$gets],['parent','=',$datas['course']],['status','=','0']]);
            	                                 if(count($coursei2) > 0)
            	                                 { $coursei2 = course::updateoption2([['name','=',$gets],['parent','=',$datas['course']],['status','=','0']],array('status'=>'1'));
            	                                    $courseid = course::getbycondition(array('name'=>$gets,'type'=>'2','parent'=>$datas['course'],'status'=>'1'));
        	                                        $datas['subject'] = $courseid[0]->id;
            	                                 }else
            	                                 {
        	                                      $courseid = course::getbycondition(array('name'=>$gets,'parent'=>$datas['course'],'type'=>'2','status'=>'1'));
        	                                      $datas['subject'] = $courseid[0]->id;
            	                                 }
        	                                 }else
        	                                 {  course::insertoption(array('name'=>$gets,'status'=>'1','type'=>'2','parent'=>$datas['course']));
        	                                    $datas['subject'] = course::getdetailsuserret(array('name'=>$gets,'status'=>'1','type'=>'2','parent'=>$datas['course']));
        	                                 }
        	                             }else
        	                             {
        	                                 $datas['subject'] = '0';
        	                             }
    	                             }
    	                             
    	                             if($ky== '6')
    	                             {   if($gets!='All' && $datas['subject']!='0')
        	                             {
        	                                 $coursei = course::getoptionmatch([['name','=',$gets],['parent','=',$datas['subject']],['status','!=','2'],['type','=','3']]);
        	                                 if(count($coursei) > 0)
        	                                 {   $coursei2 = course::getoptionmatch([['name','=',$gets],['parent','=',$datas['subject']],['status','=','0']]);
            	                                 if(count($coursei2) > 0)
            	                                 { $coursei2 = course::updateoption2([['name','=',$gets],['parent','=',$datas['subject']],['status','=','0']],array('status'=>'1'));
            	                                    $courseid = course::getbycondition(array('name'=>$gets,'type'=>'3','parent'=>$datas['subject'],'status'=>'1'));
        	                                        $datas['chapter'] = $courseid[0]->id;
            	                                 }else
            	                                 {
        	                                      $courseid = course::getbycondition(array('name'=>$gets,'type'=>'3','parent'=>$datas['subject'],'status'=>'1'));
        	                                      $datas['chapter'] = $courseid[0]->id;
            	                                 }
        	                                 }else
        	                                 {  course::insertoption(array('name'=>$gets,'status'=>'1','type'=>'3','parent'=>$datas['subject']));
        	                                    $datas['chapter'] = course::getdetailsuserret(array('name'=>$gets,'status'=>'1','type'=>'3','parent'=>$datas['subject']));
        	                                 }
        	                             }else
        	                             {
        	                                 $datas['chapter'] = '0';
        	                             }
    	                             }
    	                             
    	                            
    	                             $datas['user_id'] = '0';
    	                             $datas['is_admin'] =  Auth::user()->id;
    	                             $datas['status'] =  '1';
    	                             if($ky== '7')
    	                             {  
                                        if(!empty($gets))
                                        {
                                        $datas2['question'] = str_replace("—","-",$gets);
                                        }else
                                        {
                                        $datas2['question'] = 'Null';  
                                        }
    	                             }
    	                             if($ky== '8')
    	                             {   if(!empty($gets))
    	                                {
    	                                 $datas2['optiona'] = str_replace("—","-",$gets);
    	                                }else
    	                                {
    	                                    $datas2['optiona'] = 'Null';
    	                                }
    	                             }
    	                             if($ky== '9')
    	                             {  
    	                                 if(!empty($gets))
    	                                 {
    	                                 $datas2['optionb'] = str_replace("—","-",$gets);
    	                                 }else
    	                                 {
    	                                     $datas2['optionb'] = 'Null';
    	                                 }
    	                             }
    	                             if($ky== '10')
    	                             {
    	                                 if(!empty($gets))
    	                                 {
    	                                 $datas2['optionc'] = str_replace("—","-",$gets);
    	                                 }else
    	                                 {
    	                                     $datas2['optionc'] = 'Null';
    	                                 }
    	                             }
    	                             if($ky== '11')
    	                             {
    	                                 if(!empty($gets))
    	                                 {
    	                                 $datas2['optiond'] = str_replace("—","-",$gets);
    	                                 }else
    	                                 {
    	                                     $datas2['optiond'] = 'Null';
    	                                 }
    	                             }
    	                             if($ky== '12')
    	                             {   $ans= '1';
    	                                 if($gets == 'A')
    	                                 {
    	                                     $ans= '1';
    	                                 }
    	                                 if($gets == 'B')
    	                                 {
    	                                     $ans= '2';
    	                                 }
    	                                 if($gets == 'C')
    	                                 {
    	                                     $ans= '3';
    	                                 }
    	                                 if($gets == 'D')
    	                                 {
    	                                     $ans= '4';
    	                                 }
    	                                 
    	                                 $datas2['answer'] = $ans;
    	                             }
    	                           
    	                        } 
    	                        $id = Pre_questiondetails::insertoption2($datas);
                                if($id !='')
                                {
                                 $datas2['question_id']= $id;
                                 $datas2['qstatus']='1';
                                 $id2 = Question_answers::insertoption2($datas2);
                                  Question_answers::updateoption($datas,$id2);
                                }
	                        }
				} 
				}
			} 	
			return redirect('admin/questions')->with('success', 'Question has been uploaded successfully.');
      } 
    }else
    {
       return redirect('/admin');
    }
   }
   
   public function pay_user(Request $request,$id='')
   {
        $data['title']='Pay User-Multiple Choice Online';
        $data['page']='withdraw';
        $data['user'] = Withdraw::getjoin2($id);
        $data['request_id'] = $id; 
      return view('/admin/paytouser',$data);
   }
   
   public function payusers(Request $request)
   {
       $data = $request->all();
       $uid = $data['uid'];
       $messags = array();
       $request_id = $data['request_id'];
        $email = [['id','=',$data['uid']]];
        $client_id='';
        $client_secrate ='';
        $url1='';
        if(Options::getoptionmatch3('paypal_mode')=='0')
        {  
         $url1 = 'https://api.sandbox.paypal.com/v1/oauth2/token';
         $url2 = 'https://api.sandbox.paypal.com/v1/payments/payouts';
         $client_id = Options::getoptionmatch3('paypal_client_id_sandbox');
         $client_secrate = Options::getoptionmatch3('paypal_client_secrate_sandbox');
        }else
        {   $url1 = 'https://api.paypal.com/v1/oauth2/token';
             $url2 = 'https://api.paypal.com/v1/payments/payouts';
            $client_id = Options::getoptionmatch3('paypal_client_id_live');
             $client_secrate = Options::getoptionmatch3('paypal_client_secrate_live');
        }
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
        $exists = User::getbycondition($email);
        if($data['gateway_type'] == $exists[0]->gateway_type)
        {
            /* getway 1 for stripe */
            if($exists[0]->gateway_type=='1' && $exists[0]->bank_acc_id !='')
            {
                 \Stripe\Stripe::setApiKey($client_secrate1);
                $token= \Stripe\Token::create([
                    'card' => [
                    'number' => $data['ccNumber'],
                    'exp_month' => $data['exp_month'],
                    'exp_year' => $data['exp_year'],
                    'cvc' => $data['cvv'],
                ],
                ]);
                if (!empty($token['id'])) {
                            $payout = \Stripe\Charge::create([
                            'amount' => $data['moeny']*100,
                            'currency' => 'AUD',
                            "source" => $token['id'],
                           /* "application_fee_amount" => 200,*/
                            ]
                            , ['stripe_account' => $exists[0]->bank_acc_id]
                            );
                        if($payout['status'] == 'succeeded') {
                            Withdraw::updateoption(array('status'=>'1'),$request_id);
                           $messags['message'] = 'Payment has been sent successfully!!.';
                           $messags['erro']= 101; 
                        }else
                        {
                            $messags['message'] = 'Error: No Reponse.';
                           $messags['erro']= 202; 
                        }
                }
            }else if($exists[0]->gateway_type=='0' && $exists[0]->paypal_email !='')
           {  
                $id = time().uniqid();
                $id2 = uniqid().time();
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
                    "value": "'.$data['moeny'].'",
                    "currency": "AUD"
                  },
                  "note": "Thanks for your patronage!",
                  "sender_item_id": "'.$id2.'",
                  "receiver": "'.$exists[0]->paypal_email.'"
                }
                ]
                }';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_USERPWD, $client_id.':'.$client_secrate);
                $headers = array(); 
                $headers[] = 'Accept: application/json';
                $headers[] = 'Accept-Language: en_US';
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $result = curl_exec($ch);
                $data = json_decode($result,true);
                if (curl_errno($ch)) {
                  // echo 'Error:' . curl_error($ch);
                  $messags['message'] = 'Error:' . curl_error($ch);
                  $messags['erro']= 202;  
                }
                curl_close($ch); 
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url2);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $datam);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POST, 1);
                $headers = array();
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Authorization: Bearer '.$data['access_token'];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $result = curl_exec($ch);
                 $datak = json_decode($result,true);
                if (curl_errno($ch)) {
                 // echo 'Error:' . curl_error($ch);
                    $messags['message'] = 'Error:' . curl_error($ch);
                    $messags['erro']= 202;  
                }
                curl_close($ch);
        if(empty($datak)){
        $messags['message'] = "No response.";
        $messags['erro']= 202;  
        }
        else
        {
            User::updateUser(array('payout_item_id'=>$datak['batch_header']['payout_batch_id']),$uid);
             Withdraw::updateoption(array('status'=>'1'),$request_id);
            $messags['message'] = "Payment sent successfully!!.";
            $messags['erro']= 101;   
        }
       }else
            {
                $messags['message'] = "Invalid Request";
                $messags['erro']= 202;   
            }
        }else
        {
           $messags['message'] = "Invalid Request";
           $messags['erro']= 202;    
        }
        
    echo json_encode($messags);
    die;
   }
}
