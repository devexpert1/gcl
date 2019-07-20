<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Notification extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   protected $table = "notification";
    protected $fillable = [
        'w_from','from_id','w_to','to_id','title','description','url','tbl','status'
    ];
     public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   


			public static function getoption()
			{
			    return Notification::all();
			}
            public static function getoptionDetail($id = '')
           {
               return Notification::where(array('id' => $id ))->get();
           }

           public static function getoptionmatch($condition)
           {
               return Notification::where($condition)->pluck('id');
           }

           public static function insertoption($condition='')
           {
                return Notification::insert($condition);
           }

            public static function updateoption($condition='',$id='')
           {
            $updateoptions = Notification::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }
           
           public static function updateoption2($condition='',$query='')
           {
            $updateoptions = Notification::where($query);
            $updateoptions->update($condition);
            return back();
           }

            public static function getbycondition($conditiion = '')
            { 
            return Notification::where($conditiion)->get();
            }
            
             public static function getbycondition2($conditiion = '')
            { 
            return Notification::where($conditiion)->orderBy('status', 'desc')->orderBy('id', 'desc')->paginate(10);
            }
            
            public static function getbycondition234($conditiion = '')
            { 
            return Notification::where($conditiion)->orderBy('status', 'desc')->orderBy('id', 'desc')->paginate(10)->toArray();
            }
            
             public static function getbycondition23($conditiion = '')
            { 
            return Notification::where($conditiion)->orderBy('id', 'desc')->limit(5)->get();
            }
}
