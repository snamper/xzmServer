<?php
/**
 * 订单列表(买家端)
 * 接口参数: 8段 * appuid * 订单状态(0待付款，1待发货，2待处理(收货)，3已完成,4待评价) * 页码
 * author zq
 * date 2017-6-15
 */
include_once("../functions_mut.php");
include_once("../functions_mdb.php");

//验证参数个数
if(!(count($reqlist) == 11)){
    forExit($lock_array);
    toExit(9, $return_list);
}

$status = intval(trim($reqlist[9]));
if(!($status >= 0 && $status <= 4)){
    forExit($lock_array);
    toExit(27, $return_list);
}

$page = intval(trim($reqlist[10]));
if($page < 1){
    forExit($lock_array);
    toExit(28, $return_list);
}

//验证userid
$userid = trim($reqlist[8]);
if($userid < 1 || $userid > 4294967296){
    forExit($lock_array);
    toExit(10, $return_list);
}
$user_path = $j_path.'user/'.getSubPath($userid, 4, true);
if(!is_dir($user_path)){
    forExit($lock_array);
    toExit(11, $return_list);
}
if(is_file($user_path."lock")){
    forExit($lock_array);
    toExit(11, $return_list);
}
if(!file_put_contents($user_path."lock", " ", LOCK_EX)){
    forExit($lock_array);
    toExit(11, $return_list);
}
$lock_array[] = $user_path.'lock';

//连接db
$con = conDb();
if($con == ''){
    forExit($lock_array);
    toExit(300, $return_list);
}

//检查连接数
if(!checkDbCon($con)){
    forExit($lock_array, $con);
    toExit(301, $return_list);
}

//获取当前用户登录状态
$condition = "userid = $userid and deviceid = '".$deviceid."' and status = 1 and is_app=1";
$count = dbCount('zj_user_login', $con, $condition);
if($count != 1){
    forExit($lock_array, $con);
    toExit(12, $return_list);
}

//$s_url = 'http://192.168.1.118/zjxzm/';
//$page="1";
//$status="0";
//$userid="5";
//根据状态和用户获取商城流程订单列表
$limit = " limit ".(($page - 1)*10).",10";

$field = "id,orderid,money,addtime,kuaidih,wlname,paytime,fhtime,retime from zj_order";
$where = "appuid = $userid and status = $status";
$sql = "select $field where $where order by addtime desc ".$limit;
$order_list = dbLoad(dbQuery($sql, $con));
file_put_contents('./log.txt', date('Y-m-d H:i:s').' :--'.$status,0777);
//取数量,单价,商品名称
if(count($order_list) > 0){
    foreach($order_list as &$order_item){
        $order_item['addtime'] = date("Y-m-d H:i:s", $order_item['addtime']);
        $order_item['paytime'] = isset($order_item['paytime'])? Date("Y-m-d H:i:s",$order_item['paytime']):" ";
        $order_item['fhtime'] = isset($order_item['fhtime'])? Date("Y-m-d H:i:s",$order_item['fhtime']):" ";
        $order_item['retime'] = isset($order_item['retime'])? Date("Y-m-d H:i:s",$order_item['retime']):" ";
//     价格待改,价格需要返回谈好的价格,不一定是商品价格,现在是直接取的商品价格
        $sql = "select * from zj_order_goods where orderid = '".$order_item['orderid']."'";
        $goods_list = dbLoad(dbQuery($sql, $con),true);
        $order_item['amount'] = $goods_list['amount'];
        $order_item['money'] = $goods_list['money'];
        $order_item['total_money'] = $goods_list['money'] * $goods_list['amount'];
            $sql = "select name,img from zj_good where goodid= '".$goods_list['goodid']."'";
            $ginfo = dbLoad(dbQuery($sql, $con), true);
        if($ginfo['img']){
            $imgs=$ginfo['img'];
            $imgs=json_decode($imgs);
            $img = trim($imgs[0]);
            $order_item['img'] = $s_url.$img;
        }

        $order_item['name'] = $ginfo['name'];

        $order_item['status']=$status;
         $order_item['biaoshi']=0;
    }
}else{
    $order_list = array();
}

//求购流程的订单列表
//根据状态和用户获取订单列表
$field = "id,bjid,qgorderid,type,price,addtime,kuaidih,wlname,paytime,fhtime,retime from zj_qgorder";
$where = "appuid = $userid and status = $status";
$sql = "select $field where $where order by addtime desc ".$limit;
$order_value = dbLoad(dbQuery($sql, $con));

//商品信息(类型,单价,总价)
if(count($order_value) > 0){
    foreach($order_value as &$order_qitem){
        $order_qitem['addtime'] = date("Y-m-d H:i:s", $order_qitem['addtime']);
        $order_qitem['paytime'] = isset($order_qitem['paytime'])? Date("Y-m-d H:i:s",$order_qitem['paytime']):" ";
        $order_qitem['fhtime'] = isset($order_qitem['fhtime'])? Date("Y-m-d H:i:s",$order_qitem['fhtime']):" ";
        $order_qitem['retime'] = isset($order_qitem['retime'])? Date("Y-m-d H:i:s",$order_qitem['retime']):" ";

        // 算类型总价
        $price = explode(',',$order_qitem['price']);
        $total_money= array_sum($price);
        $order_qitem['qg_total_money']= $total_money;

         $sql="select c.jname,c.picture from zj_qgorder a left join zj_setmoney b on a.bjid=b.id left join zj_border c on b.bid=c.bid where a.qgorderid='$order_qitem[qgorderid]'";
        $goinfo = dbLoad(dbQuery($sql, $con),true);
        if($goinfo['picture']){
            $imgs=$goinfo['picture'];
            $imgs=json_decode($imgs);
            $img = trim($imgs[0]);
            $order_qitem['picture'] = $s_url.$img;
        }

        $order_qitem['jname'] = $goinfo['jname'];
        $order_qitem['status']=$status;
         $order_qitem['biaoshi']=1;
    }
}else{
    $order_value = array();
}

$data=[
    "shop"=>$order_list,
    "qiugou"=>$order_value,
];

forExit($lock_array, $con);
$return_list['data'] = json_encode($data);

toExit(0, $return_list, false);

?>
