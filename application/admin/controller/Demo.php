<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\facade\Request;
use think\Db;

class Demo extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('dota_replay');
    }

    /**
     * 查看
     */
    public function index()
    {
        return $this->view->fetch();
    }

    /**
     * 上传并解析 .dem
     */
    public function upload()
    {
        // 获取上传的文件对象
        $file = $this->request->file('demfile');
        
        if (!$file) {
            return $this->error('没有上传文件');
        }
        
        // 上传目录
        $uploadDir = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'dem' .DS;

        // 获取原文件名
        $originalName = $file->getInfo("name");
        
        // 文件名形如 8488416467.dem
        $originalName = $file->getInfo("name");
        $matchId = pathinfo($originalName, PATHINFO_FILENAME); // 去掉后缀，得到 match_id
    
        if (!is_numeric($matchId)) {
            return $this->error('上传的文件名不合法，必须是 比赛编号.dem 格式');
        }
    
        // === 提前检查数据库是否存在该 match_id ===
        $exists = Db::name('dota_matches')->where('match_id', $matchId)->find();
        if ($exists) {
            return $this->error("比赛已存在 (比赛编号: $matchId)");
        }

        // 保存文件，保持原名，不生成子目录
        $info = $file->move($uploadDir, $originalName, false);

        if (!$info) {
            return json(['error' => $file->getError()]);
        }

        // 完整文件路径
        $filePath = $uploadDir . $originalName;

        // === 调用 Go 程序解析 ===
        // 假设 go 编译后的可执行文件名叫 dem_parser
        // dem_parser 的作用是接收 dem 文件路径，输出 JSON 到 stdout
        $goBinary = ROOT_PATH . 'parse' . DS . 'dem_parser';  

        // 执行 go 程序
        $cmd = escapeshellcmd("$goBinary " . escapeshellarg($filePath));
        $output = shell_exec($cmd);

        if (!$output) {
            return $this->error('demo解析失败');
        }

        // 解析 JSON
        $jsonData = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->error('解析JSON出错');
        }
        
        //写入数据库
        $match_id = $jsonData['match_id'];
        $end_time = $jsonData['end_time'];
        $winner_team = $jsonData['game_winner'];
    
        // 1. 插入比赛信息
        Db::name('dota_matches')->insert([
            'match_id' => $match_id,
            'end_time' => $end_time,
            'game_winner' => $winner_team
        ]);
        
        
        // 生成 en => id 映射
        $heroes = config('heroes');
        $heroesByEn = [];
        foreach ($heroes as $id => $info) {
            $heroesByEn[$info['en']] = $id;
        }
        
        foreach ($jsonData['players'] as $p) {
            // 2. 插入玩家信息
            $exists = Db::name('dota_players')->where('steamid', $p['steamid'])->find();
            if (!$exists) {
                Db::name('dota_players')->insert([
                    'steamid' => $p['steamid'],
                    'player_name' => $p['player_name'],
                    'updatetime' => time()
                ]);
            }
            
            // 3. 插入玩家对战信息
            $hero_name = str_replace('npc_dota_hero_', '', $p['hero_name']);
            $hero_id = $heroesByEn[$hero_name] ?? null;
            Db::name('dota_player_heroes')->insert([
                'match_id' => $match_id,
                'steamid' => $p['steamid'],
                'hero_id' => $hero_id,
                'game_team' => $p['game_team'],
                'is_winner' => ($p['game_team'] == $winner_team) ? 1 : 0
            ]);
        }
        
        // 4. 插入 BanPick 数据
        foreach ($jsonData['picks_bans'] as $pb) {
            Db::name('dota_banpicks')->insert([
                'match_id' => $match_id,
                'is_pick' => $pb['is_pick'],
                'team' => $pb['team'],
                'hero_id' => $pb['hero_id']
            ]);
        }
        
        // 删除上传的文件
        @unlink($filePath);

        // 返回给前端
        return $this->success('解析成功');
    }
}
