<?php

namespace app\admin\model;

use think\Model;


class Todolist extends Model
{

    

    

    // 表名
    protected $name = 'todolist';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['pending' => __('Pending'), 'in_progress' => __('In_progress'), 'completed' => __('Completed'), 'cancelled' => __('Cancelled')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }




}
