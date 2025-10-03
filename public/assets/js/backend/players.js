define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'players/index' + location.search,
                    multi_url: 'players/multi',
                    import_url: 'players/import',
                    table: 'dota_players',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                escape: false,
                columns: [
                    [
                        {field: 'player_name', title: __('Player_name'), operate: 'LIKE'},
                        {field: 'win_count', title: __('胜场'), operate:false, sortable: true},
                        {field: 'lose_count', title: __('败场'), operate:false, sortable: true},
                        {field: 'all_matches_count', title: __('总场次'), operate:false, sortable: true},
                        {field: 'win_rate', title: __('胜率'), operate:false, sortable: true,
                            formatter: function(value, row, index) {
                                return value + '%';
                            }
                        },
                        {field: 'most_played_heroes', title: __('常用英雄（场次/胜率）'), operate:false, formatter:function(value,row){
                            if(!value) return '';
                            return value.map(h=> h.hero_img+'('+h.play + ' / ' + h.win_rate+'%)').join(' ');
                        }},
                        {field: 'top_win_rate_heroes', title: __('胜率最高英雄'), operate:false, formatter:function(value,row){
                            if(!value) return '';
                            return value.map(h=> h.hero_img+'('+h.play + ' / ' + h.win_rate+'%)').join(' ');
                        }},
                        {field: 'bottom_win_rate_heroes', title: __('胜率最低英雄'), operate:false, formatter:function(value,row){
                            if(!value) return '';
                            return value.map(h=> h.hero_img+'('+h.play + ' / ' + h.win_rate+'%)').join(' ');
                        }},
                        {field: 'updatetime', title: __('昵称更新时间'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            
            $('#league_select').change(function(){
                var league_id = $(this).val();
                table.bootstrapTable('refresh', {query:{league_id: league_id}});
            });
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
