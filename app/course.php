<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class course extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;
 protected $table = 'courses';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name','parent','type'
    ];
    public $timestamps = false;
 public  static function getname($id){
    
       $user = course::where('id',$id)->first();
       echo $user->name;
}public static function getparent($id)
{
     $user = course::where('id',$id)->first();
       return $user->name;
}
public static function getparentpar($id)
{
     $user = course::where('id',$id)->first();
       return $user->parent;
}
public  static function getsupername($id){
    
    $user = course::where('id',$id)->first();
    $user1 = course::where('id',$user->parent)->first();
    echo $user1->name;
}
public  static function getsuperid($id){
    
    $user = course::where('id',$id)->first();
    $user1 = course::where('id',$user->parent)->first();
    return $user1->id;
}


  public function parent_admin(){
        return $this->hasOne('App\country', 'id', 'parent')
                    ->withTrashed(); // get inactive parent as well
    }
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
     
     public static function getoption()
			{   $query = "CAST(name AS UNSIGNED), name ASC";
			    return course::where([['status', '=','1' ],['parent', '=','0' ]])->orderByRaw($query)->get();
			}
				public static function getoptionstates()
			{  $query = "CAST(name AS UNSIGNED), name ASC";
			    return course::where([['status', '=','1' ],['parent', '!=','0' ]])->orderByRaw($query)->get();
			}
            public static function getoptionDetail($id = '')
           {
               return course::where(array('id' => $id ))->get();
           }
           	public static function getoption2()
			{   $query = "CAST(name AS UNSIGNED), name ASC";
			    return course::where([['status', '!=','2' ]])->orderByRaw($query)->paginate(10);
			}

           public static function getoptionmatch($condition)
           {
               return course::where($condition)->pluck('name');
           }
           
           public static function getoptionmatchall($condition)
           {
               $name= course::where($condition)->Where('status','1')->pluck('name');
               if(count($name) > 0)
               {
                   return $name[0]; 
               }else
               {
                   return array();
               }
           }
           
           public static function getoptionmatchall5555($condition)
           {
               $name= course::where($condition)->Where('status','1')->pluck('name');
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
               $name= course::where($condition)->Where('status','1')->pluck('name');
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
              $name= course::Where('name', 'like', '%' .$condition. '%')->Where('status','1')->pluck('id');
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
                return course::insert($condition);
           }

            public static function updateoption($condition='',$id='')
           {
            $updateoptions = course::findOrFail($id);
            $updateoptions->update($condition);
            return back();
           }
           
           public static function updateoption2($condition='',$query='')
           {
            $updateoptions = course::where($query);
            $updateoptions->update($condition);
            return back();
           }
          
          public static function insertoption2($condition='')
           {
                $id= course::create($condition)->id;
                return $id;
           }
           
            public static function getbycondition($conditiion = '')
            {  $query = "CAST(name AS UNSIGNED), name ASC";
            return course::where($conditiion)->orderByRaw($query)->get();
            }
            
            public static function getdetailsuserret($condition='')
			{
			    
    			    $data= course::where($condition)->orderBy('id', 'desc')->first();
    			    if(!empty($data->id) && !empty($data->id))
    			    {
    			       return $data->id; 
    			    }else
    			    {
    			       return '0'; 
    			    }
			   
			    
			}
   
}
