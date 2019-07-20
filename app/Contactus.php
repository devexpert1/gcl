<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Contactus extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   protected $table = "contactus";
    protected $fillable = [
        'name','email','phone', 'subject','message'
    ];
     public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   


			public static function getuser()
			{
			    return Contactus::all();
			}
            public static function getUserDetail($id = '')
           {
               return Contactus::where(array('id' => $id ))->get();
           }

           public static function getUsermatch($condition)
           {
               return Contactus::where($condition)->pluck('email');
           }

           public static function insertUser($condition='')
           {
                return Contactus::insert($condition);
           }

            public static function updateUser($condition='',$id='')
           {
            $updateoptions = Contactus::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }

            public static function getbycondition($conditiion = '')
            {
            return Contactus::where($conditiion)->get();
            }
}
