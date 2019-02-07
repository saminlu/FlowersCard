<?php
/**
 * Created by PhpStorm.
 * User: Simon
 * Date: 2019/1/31
 * Time: 17:10
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class OrderController extends Controller
{
    public $user_number = 50; // 允许进入队列的人数

    public function index()
    {
        return view('order');
    }

    /**
     * 秒杀入口
     *
     * @return \Illuminate\Http\Response
     */
    public function spike(Request $request)
    {
        /*
         * 1、将用户id放进user:1的列表list
         * 2、将库存数放进goods:1的列表list
         * 3、将抢购结果放进order:1的集合set
         */
        if (empty(Auth::user())) {
            return view('welcome');
        }
        $user_id = Auth::user()->id;
        $goods_id = isset($request->goods_id) ? (int)$request->goods_id : 0; // 商品id

        if (!empty(Redis::llen('goods_name'))) {
//            echo '已经设置了库存了';
            return view('order');
        }

        // 初始化
        Redis::command('del', ['user_number', 'success']);
        $goods = DB::table('goods')->where('id', $goods_id)->select('stock')->first();
        // 将商品存入redis链表中
        for ($i = 1; $i <= $goods->stock; $i++) {
            // lpush从链表头部添加元素
            Redis::lpush('goods_name', $i);
        }

        // 设置过期时间
        $this->setTime();

        // 返回链表 goods_name 的长度
//        echo '商品存入队列成功，数量：' . Redis::llen('goods_name');
        return view('order');
    }

    /*
     * 执行队列，处理秒杀订单
     */
    public function run(Request $request)
    {
        if (empty(Auth::user())) {
            return view('welcome');
        }
        $goods_id = isset($request->goods_id) ? (int)$request->goods_id : 0; // 商品id
        $goods_num = (int)Redis::get('goods_name:' . $goods_id);
        $user_id = Auth::user()->id;
        $tips = "抢活动已结束";

        // 如果人数超过50，直接提示被抢完
        if (Redis::llen('user_number') > $this->user_number) {
            $tips = '很遗憾，被抢完了';
            return view('flashResult')->with("tips", $tips);
        }

        // 获取抢购结果,假设里面存了uid
        $result = Redis::lrange('success', 0, 20);
        // 如果有一个用户只能抢一次，可以加上下面判断
        if (in_array($user_id, $result)) {
            $tips = '你已经抢过了';
            return view('flashResult')->with("tips", $tips);
        }

        // 将用户加入队列中
        Redis::lpush('user_number', $user_id);

        // 从链表的头部删除一个元素，返回删除的元素,因为pop操作是原子性，即使很多用户同时到达，也是依次执行
        $count = Redis::lpop('goods_name');
        if (!$count) {
            $tips = '很抱歉，你手慢被抢完了';
            return view('flashResult')->with("tips", $tips);
        }

        $tips = '抢到的人为：' . $user_id . '，商品ID为：' . $count;
        Redis::lpush('success', $tips);
        $tips .= '恭喜你，抢到了';
        // 通过事务处理商品库存和生成订单

        $rs = DB::transaction(function () use ($goods_id, $user_id, $goods_num) {
            // 修改商品库存
            DB::table('goods')->where('id', $goods_id)->decrement('stock');
            // 生成新的订单
            DB::table('order')->insert(
                [
                    'order_number' => $this->build_order_no(),
                    'user_id' => $user_id,
                    'goods_id' => $goods_id,
                    'goods_number' => 1,
                    'status' => 1,
                    'create_time' => time(),
                    'update_time' => time(),
                ]
            );
            // 将该用户id放入order:1的集合set
            Redis::sadd('order_num:' . $goods_id, $user_id);
        });
        if ($rs !== false) {
            $tips = "抢花花卡成功";
        }
        return view('flashResult')->with("tips", $tips);
    }

    public function setTime()
    {
        // 设置 goods_name 过期时间，相当于活动时间
        Redis::expire('goods_name', 180);
    }

    /*
     * 生成唯一订单号
     */
    protected function build_order_no()
    {
        return date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }
}