<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Options extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   protected $table = "options";
    protected $fillable = [
        'coulmn_name','coulmn_value'
    ];
     public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   


			public static function getoption()
			{
			    return Options::all();
			}
            public static function getoptionDetail($id = '')
           {
               return Options::where(array('id' => $id ))->get();
           }

           public static function getoptionmatch($condition)
           {
               return Options::where($condition)->pluck('coulmn_name');
           }
              public static function getoptionmatch1($col_mane)
           {
               $op=Options::where('coulmn_name',$col_mane)->first();
               echo $op->coulmn_value;
           }
             public static function getoptionmatch3($col_mane)
           {
               $op=Options::where('coulmn_name',$col_mane)->first();
               return $op->coulmn_value;
           }
           
           
           
       public  static function column($column){
    	return \DB::table("options")->first()->$column;
    }
    
    public static function setting($column) {
        return self::column($column);
    }
    
           public static function getoptionmatch2($condition)
           {
               return Options::where($condition)->pluck('coulmn_value');
           }

           public static function insertoption($condition='')
           {
                return Options::insert($condition);
           }

            public static function updateoption($condition='',$id='')
           {
            $updateoptions = Options::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }
           
           public static function updateoption2($condition='',$query='')
           {
            $updateoptions = Options::where($query);
            $updateoptions->update($condition);
            return back();
           }

            public static function getbycondition($conditiion = '')
            { 
            return Options::where($conditiion)->get();
            }
}
