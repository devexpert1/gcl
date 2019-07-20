<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;
class Question_answers extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   protected $table = "question_answers";
    protected $fillable = [
        'question_id','question','optiona','optionb','optionc','optiond','answer','qstatus', 
        'user_id','is_admin','country','state','course','grade','year','subject','chapter','status','type'
    ];
     public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   
            public static function deleteque($id)
            {
       $del=Question_answers::where('id',$id)->delete();
       
            }
			public static function countall()
			{
			    return Question_answers::where([['status','!=','2']])->count();
			}
			
			public static function countall2($id)
			{
			    return Question_answers::where([['user_id','=',$id],['status','!=','2']])->count();
			}


			public static function getoption()
			{
			    return Question_answers::all();
			}
            public static function getoptionDetail($id = '')
           {
               return Question_answers::where(array('id' => $id ))->get();
           }

           public static function getoptionmatch($condition)
           {
               return Question_answers::where($condition)->pluck('user_id');
           }

           public static function insertoption($condition='')
           {
                return Question_answers::insert($condition);
           }

            public static function updateoption($condition='',$id='')
           {
            $updateoptions = Question_answers::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }
           
            public static function insertoption2($condition='')
           {
                $id= Question_answers::create($condition)->id;
                return $id;
           }

           
           public static function updateoption2($condition='',$query='')
           {
            $updateoptions = Question_answers::where($query);
            $updateoptions->update($condition);
            return back();
           }

            public static function getbycondition($conditiion = '')
            { 
            return Question_answers::where($conditiion)->orderBy('id', 'desc')->get();
            }
            
             public static function getbyconditionalt($conditiion = '')
            { 
            return Question_answers::where($conditiion)->orderBy('id', 'desc')->get()->toArray();
            }
            
            public static function getbyconditionaltp($conditiion = '')
            { 
            return Question_answers::where($conditiion)->orderBy('id', 'desc')->paginate(10)->toArray();
            }
            
            public static function getbyconditionall($conditiion = '')
            { 
            return Question_answers::where($conditiion)->orderBy('id', 'desc')->get()->toArray();
            }
            
             public static function getbyconditioncount($id = '')
            { 
                 $were = [['user_id','=',$id],['status', '!=','2' ]];
            return Question_answers::where($were)->count();
            }
            
            public static function getbycondition2($conditiion = '')
            { 
            return Question_answers::where($conditiion)->orderBy('id', 'desc')->paginate(10);
            }
            
             public static function getwherein($conditiion = '')
            { 
            return Question_answers::whereIn('id', [$conditiion])->get();
            }
            
            public static function getoptionDetailtest($id = '')
           {
               return Question_answers::where(array('question_id' => $id ))->paginate(10);
           }
            
            public  static function gettotalresult($id){
    
            $data = DB::table('user_test_answers');
           $data =  $data->join('question_answers', 'user_test_answers.question_id', '=', 'question_answers.id');
             $data =  $data->where('user_test_answers.test_id',$id);
           $data =  $data->select('question_answers.*', 'user_test_answers.*','user_test_answers.answer as myanswer','question_answers.answer as realanswer');
           $data =  $data->get();
            return $data;
          }
          
           public  static function gettotalresultwithpagination($id){
    
            $data = DB::table('user_test_answers');
           $data =  $data->join('question_answers', 'user_test_answers.question_id', '=', 'question_answers.id');
             $data =  $data->where('user_test_answers.test_id',$id);
           $data =  $data->select('question_answers.*', 'user_test_answers.*','user_test_answers.answer as myanswer','question_answers.answer as realanswer');
           $data =  $data->paginate(10);
            return $data;
          }
          
          public  static function gettotalresultwithpagination2($id){
           $user_id=Session()->get('userid');
            $data = DB::table('user_test_answers');
           $data =  $data->join('question_answers', 'user_test_answers.question_id', '=', 'question_answers.id');
             $data =  $data->where('user_test_answers.test_id',$id);
             $data =  $data->where('user_test_answers.user_id',$user_id);
           $data =  $data->select('question_answers.*', 'user_test_answers.*','user_test_answers.answer as myanswer','question_answers.answer as realanswer');
           $data =  $data->paginate(10);
            return $data;
          }
          
           public  static function gettotalresultwithpagination3($id){
           $user_id=Session()->get('userid');
            $data = DB::table('user_test_answers');
           $data =  $data->join('question_answers', 'user_test_answers.question_id', '=', 'question_answers.id');
             $data =  $data->where('user_test_answers.test_id',$id);
             $data =  $data->where('user_test_answers.user_id',$user_id);
           $data =  $data->select('question_answers.*', 'user_test_answers.*','user_test_answers.answer as myanswer','question_answers.answer as realanswer');
           $data =  $data->paginate(10);
            return $data;
          }
}
