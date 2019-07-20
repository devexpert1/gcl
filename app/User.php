<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
     protected $table = "users";
    protected $fillable = [
       'package_id','name','lname','email', 'password','g_id','fb_id','phone','gateway_type','paypal_email',
       'country','dob','forget_pass','address','profile','state','refferal_code','company_name','status','otp','authentication','bank_acc_id','payout_item_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    
    
     public static function getname($id = '')
       {
           $name= User::where(array('id' => $id ))->first();
           if(empty($name->name))
           {
                echo '-------';
           }else
           {
                echo $name->name;
           }
          
       }
         public static function getusercount()
        {
         return User::where([['status','!=','2']])->count();
        }
        public static function getuser()
        {
         return User::all();
        }
         public static function getrecentuser()
        {
         return User::where([['status','!=','2']])->orderBy('id', 'desc')->paginate(10);;
        }
        public static function getUserDetail($id = '')
        {
         return User::where(array('id' => $id ))->get();
        }
        
        public static function getdetailsuserret($id,$email)
			{
			    if($id!='')
			    {
    			    $data= User::where([['id', '=',$id ]])->orderBy('id', 'desc')->first();
    			    if(!empty($data->name) && !empty($data->lname))
    			    {
    			       return $data->name.' '.$data->lname; 
    			    }else
    			    {
    			       return '------'; 
    			    }
			    }else
			    {
			        $data= User::where([['email', '=',$email ]])->orderBy('id', 'desc')->first();
    			    if(!empty($data->name) && !empty($data->lname))
    			    {
    			       return $data->name.' '.$data->lname; 
    			    }else
    			    {
    			       return '------'; 
    			    } 
			    }
			    
			}
			
			
        
        public static function getUsermatch($condition)
        {
         return User::where($condition)->pluck('email');
        }
        
        public static function getmacthemailphone($otp,$email)
        {
         return User::where('otp',$otp)->where('status','1')->where(function($q) use ($email) {
             $q->where('email', $email)
               ->orWhere('phone', $email);
         })->first();  
        }
        
        public static function getUsermatchdb($condition)
        {
         return User::where($condition)->pluck('fb_id');
        }
        
        public static function insertUser($condition='')
        {
         return User::insert($condition);
        }
        
        public static function updateUser($condition='',$id='')
        {   
            $updateoptions = User::findOrFail($id);
            $updateoptions->update($condition);
            return back();
        }
        
        public static function getbycondition($conditiion = '')
        {
         return User::where($conditiion)->get();
        }
            
        public static function getbycondition2($conditiion = '')
        {
         return User::where($conditiion)->orderBy('id', 'desc')->paginate(15);
        }
        
        public static function getbycondition3($conditiion = '')
        {
         return User::where($conditiion)->orderBy('id', 'desc')->limit(10)->get();
        }
        
        public static function getdetailsuserret2($conditiion='',$field='')
			{
			    $data= User::where($conditiion)->orderBy('id', 'desc')->first();
			    return $data->$field;
			}
			
			 public static function getfirst($conditiion='')
			{
			    $data= User::where($conditiion)->get()->toArray();
			    return $data;
			}
}
