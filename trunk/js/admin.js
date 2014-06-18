$(function() {

    // アップロード画面
    // 画像の品質を決める簡単オプション選択時の処理
    var file_upload_spec_list = $('#file_upload_spec_list');
    if (file_upload_spec_list && 0 < file_upload_spec_list.length) {
        var option_def = {
            "l" : {
                "r" : 15,
                "b" : 256,
                "ar" : 16000,
                "ab" : 32
            },
            "m" : {
                "r" : 15,
                "b" : 256,
                "ar" : 16000,
                "ab" : 32
            },
            "h" : {
                "r" : 15,
                "b" : 256,
                "ar" : 16000,
                "ab" : 32
            }
        };

         file_upload_spec_list.change(function() {
         var selVal = this.options[this.selectedIndex].value;
            var targetOptName = "option_def." + selVal;
            var options = eval(targetOptName);
            for (var o in options) {
                var targetInput = document.getElementsByName(o)[0];
                targetInput.value = options[o];
            }
         });
    }

    // 一覧画面
    // 変換中の動画があったらAjax通信でポーリングしつつ情報を取得・設定する
    var movie_listitem_wrap = $('div.movie_listitem_wrap');
    if (movie_listitem_wrap && 0 < movie_listitem_wrap.length) {
        movie_listitem_wrap.each(function() {
            // すでに取得済みの場合はスキップ
            var wrap = $(this);

            // input要素にクリック時にテキストを全選択できるように修正
            wrap.find('.copy').on('click', function(){
                $(this).select();
            })

            if (wrap.find('input.flag').val() != 1) return;

            var get_movie_listitem_with_ajax = function() {
                var tag_name = wrap.find('input.tag_name').val();
                $.ajax({
                    type: "POST",
                    url: "admin-ajax.php",
                    cache: false,
                    data: { tag_name: tag_name, action: "get_bemoove_movie_listitem_Info" }
                }).done(function(response) {
                    if (response == 'error') {
                        // 取得できない場合はちょっと待って再取得
                        setTimeout(function() { get_movie_listitem_with_ajax(); }, 1000);
                        return;
                    }
                    wrap.html(response);
                });
            };
            get_movie_listitem_with_ajax();
        });
    }

    // 詳細画面
    // サムネイル画像URLの編集
    var link_thumbnail_edit = $('#link_thumbnail_edit');
    if (link_thumbnail_edit && link_thumbnail_edit.length) {
        link_thumbnail_edit.on('click', function() {
            var url = prompt('変更したいサムネイルのURLを入力してください');
            if (!url) return false;

            var dt = new Date();

            this.href = this.href + encodeURIComponent(url) + '&dummy=' + dt.getTime();
            return true;
        });
    }
});

