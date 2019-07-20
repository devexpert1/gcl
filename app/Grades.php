<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Grades extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   protected $table = "grades";
    protected $fillable = [
        'name','status'
    ];
     public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   


			public static function getoption()
			{  $query = "CAST(name AS UNSIGNED), name ASC";
			    return Grades::orderByRaw($query)->all();
			}
            public static function getoptionDetail($id = '')
           {
               return Grades::where(array('id' => $id ))->get();
           }

           public static function getoptionmatch($condition)
           {
               return Grades::where($condition)->pluck('name');
           }
           
           public static function getoptionmatchall($condition)
           {
               $name= Grades::where($condition)->Where('status','1')->pluck('name');
               if(count($name) > 0)
               {
                   return $name[0]; 
               }else
               {
                   return '';
               }
           }
           
           public static function getoptionmatchall2($condition)
           {
               $name= Grades::where($condition)->Where('status','1')->pluck('name');
               if(count($name) > 0)
               {
                   return $name[0]; 
               }else
               {
                   return 'Not Specified';
               }
           }
           
           public static function like($condition)
           {
              $name = Grades::Where('name', 'like', '%' .$condition. '%')->Where('status','1')->pluck('id');
              if(count($name) > 0)
              {
                  return $name;
              }else
              {
                  return array();
              }
           }

           public static function insertoption($condition='')
           {
                return Grades::insert($condition);
           }
           public static function insertoption2($condition='')
           {
                $id= Grades::create($condition)->id;
                return $id;
           }

            public static function updateoption($condition='',$id='')
           {
            $updateoptions = Grades::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }
           
           public static function getdetailsuserret($condition='')
			{
			    
    			    $data= Grades::where($condition)->orderBy('id', 'desc')->first();
    			    if(!empty($data->id) && !empty($data->id))
    			    {
    			       return $data->id; 
    			    }else
    			    {
    			       return '0'; 
    			    }
			   
			    
			}
           
           public static function updateoption2($condition='',$query='')
           {
            $updateoptions = Grades::where($query);
            $updateoptions->update($condition);
            return back();
           }

            public static function getbycondition($conditiion = '')
            {  $query = "CAST(name AS UNSIGNED), name ASC";
            return Grades::where($conditiion)->orderByRaw($query)->get();
            }
}
