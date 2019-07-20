<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;

class Withdraw extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   protected $table = "withdraw";
    protected $fillable = [
        'uid','amount','comment','status'
    ];
     public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   


			public static function getoption()
			{
			    return Withdraw::all();
			}
            public static function getoptionDetail($id = '')
           {
               return Withdraw::where(array('id' => $id ))->get();
           }

           public static function getoptionmatch($condition)
           {
               return Withdraw::where($condition)->pluck('uid');
           }
           
           public static function getoptionmatch2($condition)
           {
               return Withdraw::where($condition)->pluck('uid');
           }

           public static function insertoption($condition='')
           {
                return Withdraw::insert($condition);
           }
           
            public static function insertoption2($condition='')
           {
                $id= Withdraw::create($condition)->id;
                return $id;
           }

            public static function updateoption($condition='',$id='')
           {
            $updateoptions = Withdraw::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }
           
           public static function updateoption2($condition='',$query='')
           {
            $updateoptions = Withdraw::where($query);
            $updateoptions->update($condition);
            return back();
           }
           
            public static function getbyconditionpagination($conditiion = '')
            { 
            return Withdraw::where($conditiion)->orderBy('id', 'desc')->get();
            }
            public static function getbycondition($conditiion = '')
            { 
            return Withdraw::where($conditiion)->get();
            }
            
             public static function getbycondition22($conditiion = '')
            { 
            return Withdraw::where($conditiion)->orderBy('id', 'desc')->paginate(10)->toArray();
            }
            
              public static function getjoin()
            { 
                $users = DB::table('withdraw')
            ->join('reffer_friend', 'reffer_friend.uid', '=', 'withdraw.uid')
            ->join('users', 'users.id', '=', 'withdraw.uid')
            ->select('users.*','withdraw.amount as requestamount','withdraw.id as requestid', 'withdraw.comment as requestcomment','withdraw.status as requestatus')
            ->where('reffer_friend.status','=','1')
            ->distinct()
            ->orderBy('withdraw.id', 'desc')
            ->get();
             return $users;
             
            }
            
            public static function getjoin2($id='')
            { 
                $users = DB::table('withdraw')
                    ->join('reffer_friend', 'reffer_friend.uid', '=', 'withdraw.uid')
                    ->join('users', 'users.id', '=', 'withdraw.uid')
                    ->select('users.*', 'withdraw.amount as requestamount','withdraw.id as requestid', 'withdraw.comment as requestcomment','withdraw.status as requestatus')
                    ->where('reffer_friend.status','=','1')
                    ->where('withdraw.id','=',$id)
                    ->distinct()
                    ->get();
                 return $users;
            }
            
            public static function getdetailsuserret()
			{
			    $data= Withdraw::orderBy('id', 'desc')->first();
			    return $data->id;
			}
			
			public static function getdetailsuserret2($conditiion='',$field='')
			{
			    $data= Withdraw::where($conditiion)->orderBy('id', 'desc')->first();
			    return $data->$field;
			}
}
