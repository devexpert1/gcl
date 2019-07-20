<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Faqs extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   protected $table = "faqs";
    protected $fillable = [
        'questions','answer','status'
    ];
     public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   


			public static function getoption()
			{
			    return Faqs::all();
			}
			public static function getoption2()
			{
			    return Faqs::where([['status', '!=','2' ]])->orderBy('id', 'desc')->paginate(10);
			}
            public static function getoptionDetail($id = '')
           {
               return Faqs::where(array('id' => $id ))->get();
           }

           public static function getoptionmatch($condition)
           {
               return Faqs::where($condition)->pluck('questions');
           }

           public static function insertoption($condition='')
           {
                return Faqs::insert($condition);
           }

            public static function updateoption($condition='',$id='')
           {
            $updateoptions = Faqs::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }
           
           public static function updateoption2($condition='',$query='')
           {
            $updateoptions = Faqs::where($query);
            $updateoptions->update($condition);
            return back();
           }

            public static function getbycondition($conditiion = '')
            { 
            return Faqs::where($conditiion)->get();
            }
}
