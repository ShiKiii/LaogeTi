define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'league/index' + location.search,
                    add_url: 'league/add',
                    edit_url: 'league/edit',
                    del_url: 'league/del',
                    multi_url: 'league/multi',
                    import_url: 'league/import',
                    table: 'dota_league',
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
                        {field: 'id', title: 'ID', visible: false},
                        {field: 'name', title: '联赛名称', operate: 'LIKE'},
                        {field: 'start_time', title: __('Start_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'end_time', title: __('End_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'fee', title: __('报名费'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'players_count', title: __('参赛人数'), operate:false},
                        {field: 'price', title: __('总奖金'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'notes', title: __('备注'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'status', title: "是否启用", operate: false, formatter: Table.api.formatter.toggle},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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
