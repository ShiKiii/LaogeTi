<?php

namespace app\admin\model;

use think\Model;


class Matches extends Model
{

    

    

    // 表名
    protected $name = 'dota_matches';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'end_time_text'
    ];
    

    



    public function getEndTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['end_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setEndTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
