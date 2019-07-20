<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Subscribers extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   protected $table = "subscribers";
    protected $fillable = [
        'email'
    ];
     public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   


			public static function getoption()
			{
			    return Subscribers::all();
			}
            public static function getoptionDetail($id = '')
           {
               return Subscribers::where(array('id' => $id ))->get();
           }

           public static function getoptionmatch($condition)
           {
               return Subscribers::where($condition)->pluck('email');
           }

           public static function insertoption($condition='')
           {
                return Subscribers::insert($condition);
           }

            public static function updateoption($condition='',$id='')
           {
            $updateoptions = Subscribers::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }
           
           public static function updateoption2($condition='',$query='')
           {
            $updateoptions = Subscribers::where($query);
            $updateoptions->update($condition);
            return back();
           }

            public static function getbycondition($conditiion = '')
            { 
              return Subscribers::where($conditiion)->get();
            }
}
