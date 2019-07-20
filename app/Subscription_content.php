<?php
namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Subscription_content extends Authenticatable
{
     use Notifiable;
// The authentication guard for admin
    protected $table = 'subscription_content';
     /**
      * The attributes that are mass assignable.
      *
      * @var array
      */
    protected $fillable = [
        'title','price','month', 'description','currency','referrel_amount','status'
    ];
     /**
      * The attributes that should be hidden for arrays.
      *
      * @var array
      */
   
    
    public static function getUserDetail($id = '')
       {
           return Subscription_content::where(array('id' => $id ))->get();
       }
       
       public static function getname($id = '')
       {
           $name= Subscription_content::where(array('id' => $id ))->first();
           if(!empty($name->title))
           {
             return $name->title;   
           }else
           {
             return '------';  
           }
           
       }
       
        public static function getoptionmatch3($col_mane)
           {
               $op=Subscription_content::where('id',$col_mane)->first();
               echo  $op->price;
           }
             public static function getoptionmatch7($col_mane)
           {
               $op=Subscription_content::where('id',$col_mane)->first();
               echo  $op->month;
           }
            public static function getoptionmatch6($col_mane)
           {
               $op=Subscription_content::where('id',$col_mane)->first();
               echo  $op->price * 100;
           }
               public static function getoptionmatch4($col_mane)
           {
               $op=Subscription_content::where('id',$col_mane)->first();
               echo  $op->title;
           }
            public static function getoptionmatch5($col_mane)
           {
               $op=Subscription_content::where('id',$col_mane)->first();
               echo  $op->description;
           }
           
        public static function getuser()
        {
         return Subscription_content::all();
        }
        
        public static function getUsermatch($condition)
        {
         return Subscription_content::where($condition)->pluck('referrel_amount');
        }
        
        public static function getUsermatchdb($condition)
        {
         return Subscription_content::where($condition)->pluck('fb_id');
        }
        
        public static function insertUser($condition='')
        {
         return Subscription_content::insert($condition);
        }
        
        public static function updateUser($condition='',$id='')
        {
            $updateoptions = Subscription_content::findOrFail($id);
            $updateoptions->update($condition);
            return back();
        }
        
        public static function getbycondition($conditiion = '')
        {
         return Subscription_content::where($conditiion)->get();
        }
        
        public static function getbycondition2($conditiion = '')
        {
         return Subscription_content::where($conditiion)->orderBy('id', 'desc')->paginate(15);
        }
        
        public static function getbycondition3($conditiion = '')
        {
         return Subscription_content::where($conditiion)->orderBy('id', 'desc')->limit(10)->get();
        }
}
