<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Players extends Backend
{

    /**
     * Players模型对象
     * @var \app\admin\model\Players
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Players;
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
            // 页面加载时获取所有赛季
            $leagues = Db::name('dota_league')->where('status',1)->order('start_time desc')->select();
            $this->view->assign('leagues', $leagues);
        
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        
        // 联赛筛选
        $league_id = $this->request->get('league_id');
        $start = null;
        $end   = null;
        
        if ($league_id) {
            $league = Db::name('dota_league')->where('id', $league_id)->find();
            if ($league) {
                $start = strtotime($league['start_time']);
                $end   = strtotime($league['end_time']);
            }
        }
        
        $list = $this->model
                    ->alias('p')
                    ->join('fa_dota_player_heroes ph', 'ph.steamid = p.steamid')
                    ->join('fa_dota_matches m', 'm.match_id = ph.match_id')
                    ->where($where)
                    ->where($start && $end ? "m.end_time BETWEEN $start AND $end" : '')
                    ->field("
                        p.*,
                        SUM(ph.is_winner = 1) as win_count,
                        SUM(ph.is_winner = 0) as lose_count,
                        COUNT(ph.id) as all_matches_count,
                        ROUND(SUM(ph.is_winner = 1)/COUNT(ph.id)*100,2) as win_rate
                    ")
                    ->group('p.steamid')
                    ->having('COUNT(ph.id) > 0') // 用聚合函数替代别名
                    ->order($sort, $order)
                    ->paginate($limit);
            
        $heroes = config('heroes');
        foreach ($list as $k => $v) {
            // 按赛季筛选玩家比赛
            $player_heroes_query = Db::name('dota_player_heroes')->where('steamid', $v->steamid);
        
            if ($start && $end) {
                $player_heroes_query = $player_heroes_query
                    ->alias('ph')
                    ->join('fa_dota_matches m', 'ph.match_id = m.match_id')
                    ->where("m.end_time BETWEEN {$start} AND {$end}");
            }
        
            $player_heroes_data = $player_heroes_query->select();
            
            $hero_stats = []; // hero_id => ['play'=>x,'win'=>y]

            foreach ($player_heroes_data as $ph) {
                $hid = $ph['hero_id'];
                if (!isset($hero_stats[$hid])) $hero_stats[$hid] = ['play'=>0,'win'=>0];
        
                $hero_stats[$hid]['play']++;
                if ($ph['is_winner'] == 1) {
                    $hero_stats[$hid]['win']++;
                }
                
                // 构建英雄统计数组
                $hero_summary = [];
                foreach ($hero_stats as $hid => $stat) {
                    $hero = $heroes[$hid];
                    $hero_summary[] = [
                        'hero_img' => "<img src='https://cdn.cloudflare.steamstatic.com/apps/dota2/images/heroes/". $hero['img']. "' style='width:41px;height:23px;border-radius:3px;'>",
                        'play' => $stat['play'],
                        'win_rate' => $stat['play'] ? round($stat['win'] / $stat['play'] * 100, 2) : 0
                    ];
                }
                
                // 最常使用英雄前三名（按 play 排序）
                usort($hero_summary, function($a, $b){ return $b['play'] <=> $a['play']; });
                $v->most_played_heroes = array_slice($hero_summary, 0, 3);
            
                // 胜率最高英雄前三名（按 win_rate 排序）
                usort($hero_summary, function($a, $b){ return $b['win_rate'] <=> $a['win_rate']; });
                $v->top_win_rate_heroes = array_slice($hero_summary, 0, 3);
            
                // 胜率最低英雄前三名
                $v->bottom_win_rate_heroes = array_slice($hero_summary, -3);
            }
        }
        
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }
    
    

    /**
     * 获取选手最近 20 场战绩
     */
    public function recentMatches($player_id = 0, $offset = 0, $limit = 10)
    {
        if (!$player_id) {
            $this->error('Invalid STEAMID');
        }
        
        $steamid = Db::name('dota_players')->where('id', $player_id)->find()['steamid'];
        
        $heros = Config('heroes');

        $matches = Db::name('dota_player_heroes')
            ->alias('ph')
            ->join('dota_matches m', 'ph.match_id = m.match_id')
            ->field('ph.match_id, ph.hero_id, ph.is_winner, m.end_time')
            ->where('ph.steamid', $steamid)
            ->order('m.end_time DESC')
            ->limit($offset, $limit)
            ->select();

        foreach ($matches as &$row) {
            $row['hero_img'] = "https://cdn.cloudflare.steamstatic.com/apps/dota2/images/heroes/" . $heros[$row['hero_id']]['img'];
            $row['result'] = $row['is_winner'] ? '✅' :'❌';
            $row['match_time'] = date('Y-m-d H:i', $row['end_time']);
        }

        $this->success('', null, $matches);
    }


}
