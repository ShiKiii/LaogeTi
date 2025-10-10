<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

/**
 * TI冠军
 *
 * @icon fa fa-circle-o
 */
class Champion extends Backend
{

    /**
     * Champion模型对象
     * @var \app\admin\model\Champion
     */
    protected $model = null;
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Champion;

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
            $team1 = json_decode($v['team1'], true);
            $v->team1 = '';
            if(!empty($team1)){
                foreach ($team1 as $kk => $vv){
                    $player = Db::name('dota_players')->where('steamid', $kk)->find();
                    $player_name = $player['player_name'];
                    $player_id = $player['id'];
                    $v->team1 .= '<img class="hero_img" src="https://cdn.cloudflare.steamstatic.com/apps/dota2/images/heroes/'.$heroes[$vv]['en'].'_lg.png"><span class="label label-success player_name" data-player-id="'.$player_id.'">'.$player_name.'</span>';
                    
                }
            }
            
            $team2 = json_decode($v['team2'], true);
            $v->team2 = '';
            if(!empty($team2)){
                foreach ($team2 as $kk => $vv){
                    $player = Db::name('dota_players')->where('steamid', $kk)->find();
                    $player_name = $player['player_name'];
                    $player_id = $player['id'];
                    $v->team2 .= '<img class="hero_img" src="https://cdn.cloudflare.steamstatic.com/apps/dota2/images/heroes/'.$heroes[$vv]['en'].'_lg.png"><span class="label label-success player_name" data-player-id="'.$player_id.'">'.$player_name.'</span>';
                    
                }
            }
            
            $team3 = json_decode($v['team3'], true);
            $v->team3 = '';
            if(!empty($team3)){
                foreach ($team3 as $kk => $vv){
                    $player = Db::name('dota_players')->where('steamid', $kk)->find();
                    $player_name = $player['player_name'];
                    $player_id = $player['id'];
                    $v->team3 .= '<img class="hero_img" src="https://cdn.cloudflare.steamstatic.com/apps/dota2/images/heroes/'.$heroes[$vv]['en'].'_lg.png"><span class="label label-success player_name" data-player-id="'.$player_id.'">'.$player_name.'</span>';
                    
                }
            }
            
            $team4 = json_decode($v['team4'], true);
            $v->team4 = '';
            if(!empty($team4)){
                foreach ($team4 as $kk => $vv){
                    $player = Db::name('dota_players')->where('steamid', $kk)->find();
                    $player_name = $player['player_name'];
                    $player_id = $player['id'];
                    $v->team4 .= '<img class="hero_img" src="https://cdn.cloudflare.steamstatic.com/apps/dota2/images/heroes/'.$heroes[$vv]['en'].'_lg.png"><span class="label label-success player_name" data-player-id="'.$player_id.'">'.$player_name.'</span>';
                    
                }
            }
        }
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    /**
     * 添加
     *
     * @return string
     * @throws \think\Exception
     */
    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        
        //冠亚
        $params['team1'] = '{}';
        $params['team2'] = '{}';
        if(!empty($params['win_match_id'])){
            $team1 = [];
            $team2 = [];
            $game = Db::name('dota_player_heroes')->where('match_id',$params['win_match_id'])->select();
            foreach($game as $k => $v){
                if($v['is_winner'] == 1){
                    $team1[$v['steamid']] = $v['hero_id'];
                }else{
                    $team2[$v['steamid']] = $v['hero_id'];
                }
            }
            $params['team1'] = json_encode($team1);
            $params['team2'] = json_encode($team2);
        }
        
        //季殿
        $params['team3'] = '{}';
        $params['team4'] = '{}';
        if(!empty($params['lose_match_id'])){
            $team3 = [];
            $team4 = [];
            $game = Db::name('dota_player_heroes')->where('match_id',$params['lose_match_id'])->select();
            
            foreach($game as $k => $v){
                if($v['is_winner'] == 1){
                    $team3[$v['steamid']] = $v['hero_id'];
                }else{
                    $team4[$v['steamid']] = $v['hero_id'];
                }
            }
            $params['team3'] = json_encode($team3);
            $params['team4'] = json_encode($team4);
        }
        
        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
    }

    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        
        //冠亚
        $params['team1'] = '{}';
        $params['team2'] = '{}';
        if(!empty($params['win_match_id'])){
            $team1 = [];
            $team2 = [];
            $game = Db::name('dota_player_heroes')->where('match_id',$params['win_match_id'])->select();
            foreach($game as $k => $v){
                if($v['is_winner'] == 1){
                    $team1[$v['steamid']] = $v['hero_id'];
                }else{
                    $team2[$v['steamid']] = $v['hero_id'];
                }
            }
            $params['team1'] = json_encode($team1);
            $params['team2'] = json_encode($team2);
        }
        
        //季殿
        $params['team3'] = '{}';
        $params['team4'] = '{}';
        if(!empty($params['lose_match_id'])){
            $team3 = [];
            $team4 = [];
            $game = Db::name('dota_player_heroes')->where('match_id',$params['lose_match_id'])->select();
            
            foreach($game as $k => $v){
                if($v['is_winner'] == 1){
                    $team3[$v['steamid']] = $v['hero_id'];
                }else{
                    $team4[$v['steamid']] = $v['hero_id'];
                }
            }
            $params['team3'] = json_encode($team3);
            $params['team4'] = json_encode($team4);
        }
        
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }
    
    


}
