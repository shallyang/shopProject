<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use DB;

class HomeUserController extends Controller
{
    public function getPerson()
    {
        return view('homes/user_person');
    }
    public function getMymoney($id)
    {
        $res = DB::table('user_table')->where('userid',$id)->value('usermoney');
    	return view('homes/user_mymoney',['res'=>$res]);
    }
    //用户充值
    public function postMymoneyinsert(Request $request)
    {
        $res = $request->except('_token');
        // var_dump($res);die;
        $pro = DB::table('user_table')->where('userid',$res['id'])->value('usermoney');
        $newmoney = (int)$res['usermoney'] + $pro;
        $tem = DB::table('user_table')->where('userid',$res['id'])->update(['usermoney'=>$newmoney]);
        if($tem){
            return redirect('/user/mymoney')->with('info','充值成功!');
        }else{
            return back()->with('info','充值失败!');
        }
    }
    //订单管理页面
    public function getOrder()
    {
        $res = DB::table('order_table')->get();
        foreach($res as $k=>$v){
            $arr = explode(',',$v->goodsid);
            foreach($arr as $ks=>$vs){                 
                $img = DB::table('goods_pic_table')->where('goodsid',$vs)->value('picurl');
                $imgs[] = $img;                
            }            
            $pics[] = $imgs;
            $imgs = null;
        }
    	return view('homes/user_order',['res'=>$res,'pics'=>$pics]);
    }
    //订单处理
    public function getClose($id)
    {   
        $res = DB::table('order_table')->where('orderid',$id)->update(['passstatus'=>4]);
        if ($res) {
            return back()->with('info','订单关闭成功');
        } else {
            return back()->with('info','订单关闭失败,请重试');
        }
    }

    public function getDelete($id)
    {   
        // dd($id);
        $res = DB::table('order_table')->where('orderid','=',$id)->delete();

        if ($res) {
            return back()->with('info','订单删除成功');
        } else {
            return back()->with('info','订单删除失败,请重试');
        }
    }

    public function getGoship($id)
    {
        return view('homes.user_order',['id'=>$id]);
    }

    public function postGoship(Request $request)
    {
        //获取订单号和快递代码.
        $orderid = $request->orderid;
        $shippost = $request->shippost;

        $res = $request->except('_token','orderid','shippost');
        //将快递代码改成具体的快递公司
        switch($shippost)
        {
            case 0:
                $res['shippost'] = '顺丰快递';
                break;
            case 1:
                $res['shippost'] = '联邦快递';
                break;
            case 2:
                $res['shippost'] = '京东快递';
                break;
            case 3:
                $res['shippost'] = '邮政快递';
                break;    
        }
        $res['passstatus'] = 1;
        // dd($res);
        $res = DB::table('order_table')->where('orderid','=',$orderid)->update($res);

        if ($res) {
            return redirect('/user/order')->with('info','发货成功!');
        } else {
            return redirect('/user/order',['$id'=>$orderid])->with('info','发货失败,请重试!');
        }
    }

    public function getChangeget($id)
    {
        $res = DB::table('order_table')->where('orderid',$id)->select()->first();
        return view('homes/user_orderchange',['res'=>$res]);
    }

    public function postChangeget(Request $request)
    {
        //删除token 和订单号
        $res = $request->except('_token','orderid');
        //获取订单号
       // dd($res);
        $orderid = $request->orderid;
        //修改数据
        $pro = DB::table('order_table')->where('orderid',$orderid)->update($res);

        if ($pro) {
            return redirect('/user/order')->with('info','修改成功!');
        } else {
            return redirect('/user/order')->with('info','修改失败!');
        }

    }
    
    //结束

    //没添加的用户信息页面
    public function getInfo()
    {           
        $pro = DB::table('user_table')->where('userid',8)->get()[0];
        // var_dump($pro);die;
            if(!$pro->userinfostatu){
                return view('homes/user_info');
        }
        
    	return redirect('/user/userinfo');
    }
    // 添加过的用户页面
    public function getUserinfo()
    {

        $pro = DB::table('user_table')->where('userid',8)->get()[0];
        $provin = ['安徽','河北','河南','山西'];
         $ct = [
            ['亳州','合肥','蚌埠'],
            ['石家庄','保定','秦皇岛'],
            ['郑州','开封','荥阳'],
            ['运城','太原','大同']
            ];
            $pro->provincevalue = array_search($pro->province,$provin);
            $pro->cityvalue = array_search($pro->city,$ct[$pro->provincevalue]);
            // echo '<pre>';
            // var_dump($pro);die;
        return view('homes/user_upinfo',['pro'=>$pro]);
    }
    public function postInfoupdate(Request $request)
    {
        $this->validate($request, [

                'username' => 'required',
                'truename' => 'required',
                'usersex'=>'required',
                'userphone'=>'required|regex:/^1[345678]\d{9}$/',
                'useremail'=>'required|email',
                'userqq'=>'required|regex:/^\d{4,13}$/',
                'userhead'=>'required'

            ],[
                //自定义错误消息
                'username.required'=>'用户名不能为空',
                'truename.required'=>'真实姓名不能为空',
                'userphone.required'=>'用户手机号不能为空',
                'userphone.regex'=>'手机号格式不正确',
                'useremail.required'=>'用户邮箱不能为空',
                'useremail.email'=>'邮箱格式不正确',
                'userqq.required'=>'用户QQ不能为空',
                'userqq.regex'=>'QQ格式输入不正确',
                'userhead.required'=>'上传图片不能为空'
        ]);
        $res = $request->except('_token','userhead','province','city');
        $names = rand(111111,999999).time();
        if($request->hasFile('userhead')){             
             $suffix = $request->file('userhead')->getClientOriginalExtension();
            $request->file('userhead')->move('./upload/',$names.'.'.$suffix);   
            $res['userhead'] = '/upload/'.$names.'.'.$suffix;    
        }
         $pro = ['安徽','河北','河南','山西'];
         $ct = [
            ['亳州','合肥','蚌埠'],
            ['石家庄','保定','秦皇岛'],
            ['郑州','开封','荥阳'],
            ['运城','太原','大同']
            ];
        $res['province'] = $pro[$request->province];
        $res['city'] = $ct[$request->province][$request->city];  
        $pro = DB::table('user_table')->where('userid',8)->update($res);
        if($pro){
            return redirect('/user/order')->with('info','修改成功!');
        }else{
            return back()->with('info','修改失败!');
        }
    }
    //商品收藏
    public function getCollectgoods()
    {
        $res = DB::table('collect_table')->where('userid',8)->get();
        $arr = [];
        foreach($res as $k=>$v){
            // $pro = DB::select('select goodsname,goodsprice from goods_table where goodsid = ?',[$v->goodsid])[0];
            $pro = DB::table('goods_table')->where('goodsid',$v->goodsid)->get()[0];
            $pic = DB::table('goods_pic_table')->where('goodsid',$v->goodsid)->value('picurl');
            $pro->partid = $pic;
            $arr[] = $pro;
        }    
        
    	return view('homes/user_collectgoods',['arr'=>$arr]);
    }
    // 删除商品收藏

    public function getDelcollect($goodsid)
    {
        $res = DB::table('collect_table')->where('goodsid',$goodsid)->delete();
        if($res){
            return back()->with('删除成功!');
        }else{
            return back()->whti('删除失败!');
        }
    }

   //收货地址页面
    public function getAddress()
    {
        $res = DB::table('user_address')->get();
        // var_dump($res);
    	return view('homes/user_address',['res'=>$res]);
    }
    //添加收货地址
    public function postAddressinsert(Request $request)
    {

        $res = $request->except('_token');   
         $pro = ['安徽','河北','河南','山西'];
         $ct = [
            ['亳州','合肥','蚌埠'],
            ['石家庄','保定','秦皇岛'],
            ['郑州','开封','荥阳'],
            ['运城','太原','大同']
            ];
        if(empty($request->status)){
            $res['status'] = '0';
            $res['province'] = $pro[$request->province];
            $res['city'] = $ct[$request->province][$request->city];
            $tem = DB::table('user_address')->insert($res);

        }else{        
        // var_dump($res);die;
            $getstatus = DB::table('user_address')->get();
            foreach($getstatus as $k=>$v){
                if($v->status == '1'){
                    DB::table('user_address')->where('id',$v->id)->update(['status'=>'0']);
                }
            }
            $res['province'] = $pro[$request->province];
            $res['city'] = $ct[$request->province][$request->city];
            $tem = DB::table('user_address')->insert($res);
        }        
       
        if($tem){
            return back()->with('info','添加成功！');
        }else{
            return back()->with('info','添加失败!');
        }           
    }
    // 地址删除
    public function getDeladdress($id)
    {
       $res = DB::table('user_address')->where('id',$id)->delete();
       if($res){
            return redirect('/user/address')->with('info','删除成功');
       }else{
            return back()->with('info','删除失败');
       }
    }
    
        
}
