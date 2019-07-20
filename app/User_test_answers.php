<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;

class User_test_answers extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   protected $table = "user_test_answers";
    protected $fillable = [
        'test_id','question_id','answer','user_id','suggested_answer','comment','user_test_answers'
    ];
     public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   


			public static function getoption()
			{
			    return User_test_answers::all();
			}
            public static function getoptionDetail($id = '')
           {
               return User_test_answers::where(array('id' => $id ))->get();
           }

           public static function getoptionmatch($condition)
           {
               return User_test_answers::where($condition)->pluck('coulmn_name');
           }
           
           public static function getoptionmatch2($condition)
           {
               return User_test_answers::where($condition)->pluck('coulmn_value');
           }

           public static function insertoption($condition='')
           {
                return User_test_answers::insert($condition);
           }

            public static function updateoption($condition='',$id='')
           {
            $updateoptions = User_test_answers::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }
           
            public static function insertoption2($condition='')
           {
                $id= User_test_answers::create($condition)->id;
                return $id;
           }

           
           public static function updateoption2($condition='',$query='')
           {
            $updateoptions = User_test_answers::where($query);
            $updateoptions->update($condition);
            return back();
           }

            public static function getbycondition($conditiion = '')
            { 
            return User_test_answers::where($conditiion)->get();
            }
            
            public static function getbycount($conditiion = '')
            { 
            $result =  User_test_answers::where($conditiion)->get();
            return count($result);
            }
            
            public static function user_test_answers_get($id='')
            { 
                $data = DB::table('user_test_answers');
                $data =  $data->join('question_answers', 'user_test_answers.question_id', '=', 'question_answers.id');
                $data =  $data->where('user_test_answers.suggested_answer','!=',NULL);
                
               $data = $data->join('users','users.id', '=', 'user_test_answers.user_id');
                $data = $data->where('users.status', '!=', '2');
                /* $data->Join('users', function ($data) {
            $data->on('users.id', '=', 'user_test_answers.user_id')
                 ->where('users.status', '!=', '2');
        });*/
              
                if($id !='')
                {
                   $data =  $data->where('user_test_answers.id','=',$id);
                }
                
                $data =  $data->select('question_answers.*', 'user_test_answers.*','user_test_answers.answer as myanswer','question_answers.answer as realanswer','user_test_answers.id as tid','users.name as uname','users.lname as lname');
                $data =  $data->orderBy('user_test_answers.id', 'desc')->get();
                return $data;
            }
}
