<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

/**
 * 比赛信息管理
 *
 * @icon fa fa-circle-o
 */
class Matches extends Backend
{

    /**
     * Matches模型对象
     * @var \app\admin\model\Matches
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Matches;

    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        
        $list = $this->model
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
            
        $heroes = config('heroes');
        foreach ($list as $k => $v) {
            $player_heroes_data = Db::name('dota_player_heroes')->where('match_id', $v->match_id)->select();
            $v->winners = "";
            $v->losers = "";
            foreach ($player_heroes_data as $kk => $vv){
                $player_name = Db::name('dota_players')->where('steamid', $vv['steamid'])->find()['player_name'];
                
                if($vv['is_winner']== 1){
                    $v->winners .= '<img src="https://cdn.cloudflare.steamstatic.com/apps/dota2/images/heroes/'.$heroes[$vv['hero_id']]['en'].'_lg.png" style="width:41px;height:23px;border-radius:3px;"><span class="label label-success" style="margin-right:5px">'.$player_name.'</span>';
                }else{
                    $v->losers .= '<img src="https://cdn.cloudflare.steamstatic.com/apps/dota2/images/heroes/'.$heroes[$vv['hero_id']]['en'].'_lg.png" style="width:41px;height:23px;border-radius:3px;"><span class="label label-danger" style="margin-right:5px">'.$player_name.'</span>';
                }
            }
        }
        
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }


}
