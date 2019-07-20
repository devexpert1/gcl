<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;
class Test extends Authenticatable implements MustVerifyEmail
{
use Notifiable;
 protected $table = 'user_test';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id','test_id','total_questions','correct_answers','test_date'
    ];
    
      public static function testcount()
        {
         return Test::count();
        }
 public  static function gettotalque($id){
    
        $data = DB::table('question_answers')
            //->join('question_answers', 'question_answers.question_id', '=', 'pre_questiondetails.id')
             ->join('country', 'country.id', '=', 'question_answers.country')
          
             ->where('question_answers.user_id',$id)
              ->where('question_answers.qstatus', 1)->orWhere('question_answers.qstatus', 0)
            ->select('question_answers.*','country.name as cname')
            ->count();
            return $data;
}

 public  static function gettotalrecord($id){
    
        $data = DB::table('pre_questiondetails')
            ->join('question_answers', 'question_answers.question_id', '=', 'pre_questiondetails.id')
             ->join('country', 'country.id', '=', 'pre_questiondetails.country')
          
             ->where('pre_questiondetails.user_id',$id)
            ->select('question_answers.*', 'pre_questiondetails.*','country.name as cname')
            ->get();
            return $data;
}

public  static function gettotalrecordwithpagination($id){
    
        $data = DB::table('question_answers')
            //->join('question_answers', 'question_answers.question_id', '=', 'pre_questiondetails.id')
            // ->join('country', 'country.id', '=', 'question_answers.country')
          
             ->where('question_answers.user_id',$id)
              ->where('question_answers.qstatus', '!=',2)
            ->select('question_answers.*')
            ->orderBy('question_answers.id', 'desc')->paginate(10);
            return $data;
}

public  static function gettotaltest($id){
    
       $user = Test::where('user_id',$id)->count();
       return $user;
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
    
    public static function getall($tbl,$where)
    {
       return DB::table($tbl)->where($where)->get()->toArray();  
    }
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   
}
