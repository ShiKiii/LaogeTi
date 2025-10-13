<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

/**
 * 比赛信息管理
 *
 * @icon fa fa-circle-o
 */
class Banpick extends Backend
{

    /**
     * Matches模型对象
     * @var \app\admin\model\Banpick
     */
    protected $model = null;
    protected $noNeedRight = ['recentMatches'];

    public function _initialize()
    {
        parent::_initialize();

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
        // 页面加载时获取所有赛季
        $leagues = Db::name('dota_league')->where('status',1)->order('start_time desc')->select();
        
        // 联赛筛选
        $league_id = $this->request->get('league_id') ?? '';
        $start = null;
        $end   = null;
        
        if ($league_id) {
            $league = Db::name('dota_league')->where('id', $league_id)->find();
            if ($league) {
                $start = strtotime($league['start_time']);
                $end   = strtotime($league['end_time']);
            }
        }
            
        // 读取 heroes 配置（assume key=hero_id）
        $heroes = config('heroes');
    
        // 1) Pick 数 Top10（直接按 player_heroes 表计数）
        $pickTop = Db::name('dota_player_heroes')
            ->where($start && $end ? "end_time BETWEEN $start AND $end" : '')
            ->field('hero_id, COUNT(*) as pick_count')
            ->group('hero_id')
            ->order('pick_count DESC')
            ->limit(10)
            ->select();
    
        // 2) Ban 数 Top10（需要有 picks_bans 表，字段示例: hero_id, is_ban）
        $banTop = Db::name('dota_banpicks')
            ->alias('b')
            ->join('fa_dota_matches m', 'm.match_id = b.match_id')
            ->where($start && $end ? "m.end_time BETWEEN $start AND $end" : '')
            ->where('is_pick', 0)
            ->field('hero_id, COUNT(*) as ban_count')
            ->group('hero_id')
            ->order('ban_count DESC')
            ->limit(10)
            ->select();
    
        // 如果你没有单独的 ban 表，请见下面“没有 ban 表怎么办”的说明
    
        // 3) 胜率 Top10（带 play_count / win_count / win_rate），过滤出场次数过少的英雄（阈值可改）
        $min_play = 4;
        $winTop = Db::name('dota_player_heroes')
                ->where($start && $end ? "end_time BETWEEN $start AND $end" : '')
                ->field("
                    hero_id, 
                    COUNT(*) as play_count, 
                    SUM(CASE WHEN is_winner=1 THEN 1 ELSE 0 END) as win_count, 
                    ROUND(SUM(CASE WHEN is_winner=1 THEN 1 ELSE 0 END)/COUNT(*)*100,2) as win_rate
                ")
                ->group('hero_id')
                ->having("play_count >= {$min_play}")
                ->order('win_rate DESC, play_count DESC')
                ->limit(16)
                ->select();
    
        // 4) 胜率最低 Top10
        $lowWinTop = Db::name('dota_player_heroes')
                ->where($start && $end ? "end_time BETWEEN $start AND $end" : '')
                ->field("
                    hero_id,
                    COUNT(*) as play_count,
                    SUM(is_winner) as win_count,
                    ROUND(SUM(is_winner)/COUNT(*)*100,2) as win_rate
                ")
                ->group('hero_id')
                ->having("play_count >= {$min_play}")
                ->order('win_rate ASC, play_count DESC')
                ->limit(16)
                ->select();
    
        // 把英雄名字和图片拼上去，并保证字段名统一到前端
        $mapHero = function($row) use ($heroes) {
            $hid = $row['hero_id'];
            $h = isset($heroes[$hid]) ? $heroes[$hid] : ['en'=>'Unknown','img'=>'default.png'];
            return [
                'hero_id'    => $hid,
                'name'       => isset($h['cn']) && $h['cn'] ? $h['cn'] : (isset($h['en']) ? $h['en'] : 'Unknown'),
                'img'        => "https://cdn.cloudflare.steamstatic.com/apps/dota2/images/heroes/".($h['img'] ?? 'default.png'),
                // preserve original stats if present
                'pick_count' => isset($row['pick_count']) ? (int)$row['pick_count'] : 0,
                'ban_count'  => isset($row['ban_count']) ? (int)$row['ban_count'] : 0,
                'play_count' => isset($row['play_count']) ? (int)$row['play_count'] : 0,
                'win_count'  => isset($row['win_count']) ? (int)$row['win_count'] : 0,
                'win_rate'   => isset($row['win_rate']) ? (float)$row['win_rate'] : 0.0,
            ];
        };
    
        $pickTop = array_map($mapHero, $pickTop);
        $banTop  = array_map($mapHero, $banTop);
        $winTop  = array_map($mapHero, $winTop);
        $lowWinTop  = array_map($mapHero, $lowWinTop);
    
        // 为前端方便直接输出 ECharts 数据（JSON）
        $this->view->assign([
            'leagues' => $leagues,
            'pickTop' => $pickTop,
            'banTop'  => $banTop,
            'winTop'  => $winTop,
            'lowWinTop'  => $lowWinTop,
            // JSON 字符串给 JS 使用（在模板里用 PHP echo 输出）
            'pickNamesJson'  => json_encode(array_column($pickTop, 'name'), JSON_UNESCAPED_UNICODE),
            'pickCountsJson' => json_encode(array_column($pickTop, 'pick_count')),
            'pickImgsJson'   => json_encode(array_column($pickTop, 'img')),
    
            'banNamesJson'  => json_encode(array_column($banTop, 'name'), JSON_UNESCAPED_UNICODE),
            'banCountsJson' => json_encode(array_column($banTop, 'ban_count')),
            'banImgsJson'   => json_encode(array_column($banTop, 'img')),
    
            'winListJson'   => json_encode($winTop, JSON_UNESCAPED_UNICODE),
            'lowWinListJson'   => json_encode($lowWinTop, JSON_UNESCAPED_UNICODE),
            'min_play'      => $min_play,
            'league_id'    => $league_id
        ]);
    
        return $this->view->fetch(); // 渲染视图
    }
    
    

    /**
     * 获取英雄最近的驾驶员
     */
    public function recentMatches($hero_id = 0, $offset = 0, $limit = 10, $league_id = '')
    {
        if (!$hero_id) {
            $this->error('Invalid HeroId');
        }
        
        // 联赛筛选
        $start = null;
        $end   = null;
        
        if (!empty($league_id)) {
            $league = Db::name('dota_league')->where('id', $league_id)->find();
            if ($league) {
                $start = strtotime($league['start_time']);
                $end   = strtotime($league['end_time']);
            }
        }

        $matches = Db::name('dota_player_heroes')
            ->alias('ph')
            ->join('dota_players p', 'ph.steamid = p.steamid')
            ->field('ph.match_id, ph.hero_id, ph.is_winner, ph.end_time, p.player_name')
            ->where('ph.hero_id', $hero_id)
            ->where($start && $end ? "end_time BETWEEN $start AND $end" : '')
            ->order('end_time DESC')
            ->limit($offset, $limit)
            ->select();

        foreach ($matches as &$row) {
            $row['player_name'] = $row['player_name'];
            $row['result'] = $row['is_winner'] ? '✅' :'❌';
            $row['match_time'] = date('Y-m-d H:i', $row['end_time']);
        }

        $this->success('', null, $matches);
    }


}
