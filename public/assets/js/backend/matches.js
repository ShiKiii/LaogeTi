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
            
            // 点击玩家名称显示最近战绩
            $(document).on('click', '.player_name', function(){
                var playerId = $(this).data('player-id');
                var offset = 0;
                var limit = 10;
                var loading = false;
                var hasMore = true;
            
                var html = '<div id="matchContainer" style="height:380px; overflow-y:auto; padding:10px;">' +
                           '<table class="table table-bordered" style="width:100%">' +
                           '<tr><th>英雄</th><th style="text-align:center;">胜负</th><th style="text-align:center;">结束时间</th></tr>' +
                           '</table></div>';
                
                layer.open({
                    type: 1,
                    title: '最近战绩',
                    area: ['auto','auto'], // 高度 auto，Layer 不再加滚动条
                    maxWidth: 600,                // 最大宽度，桌面端不会太宽
                    maxHeight: '80vh',            // 最大高度，防止移动端超出屏幕
                    content: html,
                    scrollbar: false
                });
            
                // 弹窗打开后再获取容器
                var $container = $('#matchContainer');
                var $table = $container.find('table');
            
                function loadMatches(){
                    if(loading || !hasMore) return;
                    loading = true;
            
                    $.ajax({
                        url: '/players/recentMatches',
                        data: {player_id: playerId, offset: offset, limit: limit},
                        success: function(res){
                            loading = false;
                            if(res.code == 1 && res.data.length){
                                res.data.forEach(function(match){
                                    var row = '<tr>';
                                    row += '<td><img src="'+match.hero_img+'" style="width:50px;height:28px;"></td>';
                                    row += '<td style="text-align:center">'+match.result+'</td>';
                                    row += '<td style="text-align:center">'+match.match_time+'</td>';
                                    row += '</tr>';
                                    $table.append(row);
                                });
                                offset += res.data.length;
            
                                if(res.data.length < limit){
                                    hasMore = false;
                                    $table.append('<tr><td colspan="3" style="text-align:center;color:#999;">没有更多记录</td></tr>');
                                }
                            } else {
                                hasMore = false;
                                $table.append('<tr><td colspan="3" style="text-align:center;color:#999;">没有更多记录</td></tr>');
                            }
                        }
                    });
                }
            
                // 滚动加载
                $container.on('scroll', function(){
                    if($container.scrollTop() + $container.innerHeight() >= $container[0].scrollHeight - 10){
                        loadMatches();
                    }
                });
            
                // 初次加载
                loadMatches();
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
