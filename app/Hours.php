<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Hours extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   protected $table = "users_hours";
    protected $fillable = [
        'total_questions_uploaded','user_id','package_id','total_hours','expiry','current_question_count','apporved_questions'
    ];
     public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   


			public static function getoption()
			{
			    return Hours::where([['status', '!=','2' ]])->orderBy('id', 'desc')->get();
			}
				public static function getdetailsuser($id)
			{
			    $data= Hours::where([['user_id', '=',$id ]])->orderBy('id', 'desc')->first();
			    return $data;
			}
				public static function getdetailsuserfield($id,$field)
			{
			    $data= Hours::where([['user_id', '=',$id ]])->orderBy('id', 'desc')->first();
			    echo $data->$field;
			}
				public static function getdetailsuserret($id,$field)
			{
			    $data= Hours::where([['user_id', '=',$id ]])->orderBy('id', 'desc')->first();
			     
			    if(empty($data)){
			      return '0'; 
			        
			    }else{
			     return $data->$field;
			    };
			}
            public static function getoptionDetail($id = '')
           {
               return Hours::where(array('id' => $id ))->get();
           }
           	public static function getoption2()
			{
			    return Hours::where([['status', '!=','2' ]])->orderBy('id', 'desc')->paginate(10);
			}
            public static function insertUser($condition='')
            {
           return Hours::insert($condition);
            }
           public static function getoptionmatch($condition)
           {
               return Hours::where($condition)->pluck('title');
           }

           public static function insertoption($condition='')
           {
                return Hours::insert($condition);
           }

            public static function updateoption($condition='',$id='')
           {
            $updateoptions = Hours::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }
           
           public static function updateoption2($condition='',$query='')
           {
            $updateoptions = Hours::where($query);
            $updateoptions->update($condition);
            return back();
           }

            public static function getbycondition($conditiion = '')
            { 
            return Hours::where($conditiion)->get();
            }
}
