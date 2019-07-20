<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User_test extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   protected $table = "user_test";
    protected $fillable = [
        'user_id','test_id','total_questions','attempt_answer','correct_answers','country','state',
        'course','grade','year','subject','chapter','status','all_questions'
    ];
     public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   


			public static function getoption()
			{
			    return User_test::all();
			}
            public static function getoptionDetail($id = '')
           {
               return User_test::where(array('id' => $id ))->get();
           }

           public static function getoptionmatch($condition)
           {
               return User_test::where($condition)->pluck('test_id');
           }
           
           public static function getoptionmatch2($condition)
           {
               return User_test::where($condition)->pluck('test_id');
           }

           public static function insertoption($condition='')
           {
                return User_test::insert($condition);
           }
           
            public static function insertoption2($condition='')
           {
                $id= User_test::create($condition)->id;
                return $id;
           }

            public static function updateoption($condition='',$id='')
           {
            $updateoptions = User_test::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }
           
           public static function updateoption2($condition='',$query='')
           {
            $updateoptions = User_test::where($query);
            $updateoptions->update($condition);
            return back();
           }
           
            public static function getbyconditionpagination($conditiion = '')
            { 
            return User_test::where($conditiion)->orderBy('id', 'desc')->get();
            }
            public static function getbyconditionpagination23($conditiion = '')
            { 
            return User_test::where($conditiion)->orderBy('id', 'desc')->get()->toArray();
            }
            
            public static function getbyconditionpagination233($conditiion = '')
            { 
            return User_test::where($conditiion)->orderBy('id', 'desc')->paginate(10)->toArray();
            }
            
            public static function getbyconditionpagination2($conditiion = '')
            { 
            return User_test::where($conditiion)->limit(10)->orderBy('id', 'desc')->get();
            }
            
            public static function getbyconditionpagination22($conditiion = '')
            { 
            return User_test::where($conditiion)->limit(10)->orderBy('id', 'desc')->get()->toArray();
            }
            
            public static function getbycondition($conditiion = '')
            { 
            return User_test::where($conditiion)->get();
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
