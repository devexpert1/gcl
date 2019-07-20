<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Pre_questiondetails extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   protected $table = "pre_questiondetails";
    protected $fillable = [
        'user_id','is_admin','country','state','course','grade','year','subject','chapter','status'
    ];
     public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   


			public static function getoption($where)
			{
			    return Pre_questiondetails::where($where)->orderBy('id', 'desc')->get();
			}
            public static function getoptionDetail($id = '')
           {
               return Pre_questiondetails::where(array('id' => $id ))->get();
           }

           public static function getoptionmatch($condition)
           {
               return Pre_questiondetails::where($condition)->pluck('user_id');
           }

           public static function insertoption($condition='')
           {
                return Pre_questiondetails::insert($condition);
           }

            public static function updateoption($condition='',$id='')
           {
            $updateoptions = Pre_questiondetails::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }
           
            public static function insertoption2($condition='')
           {
                $id= Pre_questiondetails::create($condition)->id;
                return $id;
           }

           public static function getdetailsuserfield22($id,$field)
			{
			    $data= Pre_questiondetails::where([['user_id', '=',$id ],['status', '!=','2' ]])->orderBy('id', 'desc')->get();
			    echo count($data);
			}
           public static function updateoption2($condition='',$query='')
           {
            $updateoptions = Pre_questiondetails::where($query);
            $updateoptions->update($condition);
            return back();
           }

            public static function getbycondition($conditiion = '')
            { 
            return Pre_questiondetails::where($conditiion)->get();
            }
            
            public static function search($conditiion = '')
            { 
            return Pre_questiondetails::where($conditiion)->get();
            }
}
