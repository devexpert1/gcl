<?php
namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
     use Notifiable;
// The authentication guard for admin
    protected $guard = 'admins';
     /**
      * The attributes that are mass assignable.
      *
      * @var array
      */
    protected $fillable = [
        'name','lname','email', 'password','profile','phone'
    ];
     /**
      * The attributes that should be hidden for arrays.
      *
      * @var array
      */
    protected $hidden = [
        'password', 'remember_token',
    ];
    
    public static function getUserDetail($id = '')
       {
           return Admin::where(array('id' => $id ))->get();
       }
       
       public static function getname($id = '')
       {
           $name= Admin::where(array('id' => $id ))->first();
           echo $name->name;
       }
       
        public static function getuser()
        {
         return Admin::all();
        }
        
        public static function getUsermatch($condition)
        {
         return Admin::where($condition)->pluck('email');
        }
        
        public static function getUsermatchdb($condition)
        {
         return Admin::where($condition)->pluck('fb_id');
        }
        
        public static function insertUser($condition='')
        {
         return Admin::insert($condition);
        }
        
        public static function updateUser($condition='',$id='')
        {
            $updateoptions = Admin::findOrFail($id);
            $updateoptions->update($condition);
            return back();
        }
        
        public static function getbycondition($conditiion = '')
        {
         return Admin::where($conditiion)->get();
        }
        
        public static function getbycondition2($conditiion = '')
        {
         return Admin::where($conditiion)->orderBy('id', 'desc')->paginate(15);
        }
        
        public static function getbycondition3($conditiion = '')
        {
         return Admin::where($conditiion)->orderBy('id', 'desc')->limit(10)->get();
        }
        
        public static function getdetailsuserret2($conditiion='',$field='')
		{
		    $data= Admin::where($conditiion)->orderBy('id', 'desc')->first();
		    return $data->$field;
		}
}
