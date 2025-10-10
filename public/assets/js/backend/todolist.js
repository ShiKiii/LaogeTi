define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'todolist/index' + location.search,
                    add_url: 'todolist/add',
                    edit_url: 'todolist/edit',
                    del_url: 'todolist/del',
                    table: 'todolist',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), visible: false},
                        {field: 'title', title: __('Title'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'description', title: __('任务描述'), operate: 'LIKE', class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'priority', title: __('优先级'),
                            searchList: {"0":__('普通'),"1":__('重要'),"2":__('紧急')},
                            formatter: function (value) {
                                let color = {
                                    0: 'grey',
                                    1: 'orange',
                                    2: 'red'
                                }[value] || 'grey';
                                let text = {
                                    0: '普通',
                                    1: '重要',
                                    2: '紧急'
                                }[value] || '未知';
                                return `<span class="text-${color}">${text}</span>`;
                            }
                        },
                        {field: 'status', title: __('Status'),
                            searchList: {
                                "pending": __('待处理'),
                                "in_progress": __('进行中'),
                                "completed": __('已完成'),
                                "cancelled": __('已取消')
                            },
                            formatter: function (value) {
                                let map = {
                                    'pending': {text: '待处理', color: 'secondary'},
                                    'in_progress': {text: '进行中', color: 'info'},
                                    'completed': {text: '已完成', color: 'success'},
                                    'cancelled': {text: '已取消', color: 'danger'}
                                };
                                let item = map[value] || {text: '未知', color: 'dark'};
                                return `<span class="badge badge-${item.color}">${item.text}</span>`;
                            }
                        },
                        {field: 'completed_at', title: __('Completed_at'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'created_at', title: __('Created_at'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
