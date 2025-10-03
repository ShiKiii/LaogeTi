<?php

namespace app\admin\model;

use think\Model;


class League extends Model
{

    

    

    // 表名
    protected $name = 'dota_league';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    
    protected $type = [
        'status' => 'integer',
    ];
    
    protected $column = [
        'status' => ['title' => '是否启用', 'type' => 'switch'],
    ];
    







}
