define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'matches/index' + location.search,
                    del_url: 'matches/del',
                    multi_url: 'matches/multi',
                    import_url: 'matches/import',
                    table: 'dota_matches',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'end_time',
                escape: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('编号'), visible:false},
                        {field: 'match_id', title: __('比赛编号')},
                        {field: 'winners', title: __('胜者'), operate:false},
                        {field: 'losers', title: __('败者'), operate:false},
                        {field: 'end_time', title: __('结束时间'), operate:'RANGE', sortable: true, addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime}
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
