<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class BlogController extends Controller
{
    public function blog(){
        $post = DB::table('posts')
        ->join('post_category','posts.category_id','post_category.id')
		->select('posts.*','post_category.category_name_en','post_category.category_name_in')
		->get();
        return view('pages.blog',compact('post'));
    }
    public function English(){
        Session::get('lang');
        Session()->forget('lang');
        Session::put('lang','english');
        return redirect()->back();
  
    }
  
    public function Vn(){
        Session::get('lang');
        Session()->forget('lang');
        Session::put('lang','VN');
        return redirect()->back();
        
    }
  
    public function BlogSingle($id){
        $posts = DB::table('posts')->where('id',$id)->get();
        return view('pages.blog_single',compact('posts'));
  
    }

}
