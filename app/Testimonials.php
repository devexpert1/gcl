<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Testimonials extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   protected $table = "testimonials";
    protected $fillable = [
        'title','name','image','description','status'
    ];
     public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   


			public static function getoption()
			{
			    return Testimonials::where([['status', '!=','2' ]])->orderBy('id', 'desc')->get();
			}
            public static function getoptionDetail($id = '')
           {
               return Testimonials::where(array('id' => $id ))->get();
           }
           	public static function getoption2()
			{
			    return Testimonials::where([['status', '!=','2' ]])->orderBy('id', 'desc')->paginate(10);
			}

           public static function getoptionmatch($condition)
           {
               return Testimonials::where($condition)->pluck('title');
           }

           public static function insertoption($condition='')
           {
                return Testimonials::insert($condition);
           }

            public static function updateoption($condition='',$id='')
           {
            $updateoptions = Testimonials::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }
           
           public static function updateoption2($condition='',$query='')
           {
            $updateoptions = Testimonials::where($query);
            $updateoptions->update($condition);
            return back();
           }

            public static function getbycondition($conditiion = '')
            { 
            return Testimonials::where($conditiion)->get();
            }
}
