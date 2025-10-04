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
                $player = Db::name('dota_players')->where('steamid', $vv['steamid'])->find();
                $player_name = $player['player_name'];
                $player_id = $player['id'];
                
                if($vv['is_winner']== 1){
                    $v->winners .= '<img class="hero_img" src="https://cdn.cloudflare.steamstatic.com/apps/dota2/images/heroes/'.$heroes[$vv['hero_id']]['en'].'_lg.png"><span class="label label-success player_name" data-player-id="'.$player_id.'">'.$player_name.'</span>';
                }else{
                    $v->losers .= '<img class="hero_img" src="https://cdn.cloudflare.steamstatic.com/apps/dota2/images/heroes/'.$heroes[$vv['hero_id']]['en'].'_lg.png"><span class="label label-danger player_name" data-player-id="'.$player_id.'">'.$player_name.'</span>';
                }
            }
        }
        
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
    
        // 转成数组
        $ids = is_array($ids) ? $ids : explode(',', $ids);
    
        // 先查出对应的 match_id 列表
        $matchIds = $this->model->whereIn('id', $ids)->column('match_id');
    
        if (empty($matchIds)) {
            $this->error(__('No Results were found'));
        }
    
        // 启动事务，保证一致性
        Db::startTrans();
        try {
            // 删除主表
            $this->model->whereIn('id', $ids)->delete();
    
            // 删除子表
            Db::name('dota_banpicks')->whereIn('match_id', $matchIds)->delete();
            Db::name('dota_player_heroes')->whereIn('match_id', $matchIds)->delete();
    
            Db::commit();
            $this->success();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }


}
