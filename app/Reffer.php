<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Reffer extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   protected $table = "reffer_friend";
    protected $fillable = [
        'uid','friend_email','status','amount','token','friend_id'
    ];
     public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   


			public static function getoption()
			{
			    return Reffer::all();
			}
            public static function getoptionDetail($id = '')
           {
               return Reffer::where(array('id' => $id ))->get();
           }

           public static function getoptionmatch($condition)
           {
               return Reffer::where($condition)->pluck('friend_email');
           }

           public static function insertoption($condition='')
           {
                return Reffer::insert($condition);
           }

            public static function updateoption($condition='',$id='')
           {
            $updateoptions = Reffer::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }
           
           public static function updateoption2($condition='',$query='')
           {
            $updateoptions = Reffer::where($query);
            $updateoptions->update($condition);
            return back();
           }

            public static function getbycondition($conditiion = '')
            { 
              return Reffer::where($conditiion)->orderBy('id', 'desc')->get();
            }
            
            public static function getbycondition22($conditiion = '')
            { 
              return Reffer::where($conditiion)->orderBy('id', 'desc')->paginate(10)->toArray();
            }
}
