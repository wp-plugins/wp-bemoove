$(function() {

    // メッセージ   -   Message
    var fade_msg_box = $('.fade_msg_box');
    if (fade_msg_box && 0 < fade_msg_box.length) {
        setTimeout(function() { fade_msg_box.css({ opacity:"0", "z-index":"0" }); }, 1500)
    }

    // アップロード画面    -  Upload screen
    // 画像の品質を決める簡単オプション選択時の処理    -   Processing at the time of easy option selection to determine the quality of the image
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

    // 一覧画面    -  When you see a picture
    // 変換中の動画があったらAjax通信でポーリングしつつ情報を取得・設定する   -  To get or set the information while polling if there is video during the conversion in Ajax communication
    var movie_listitem_wrap = $('div.movie_listitem_wrap');
    if (movie_listitem_wrap && 0 < movie_listitem_wrap.length) {

        // input要素にクリック時にテキストを全選択できるように修正    -  Fixed to be able to select all the text when clicked on the input element
        $(document).on('click', 'div.movie_listitem_wrap .copy', function(){
            $(this).select();
        });

        movie_listitem_wrap.each(function() {
            // すでに取得済みの場合はスキップ   -  Already skipped if already acquired
            var wrap = $(this);

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
                        // 取得できない場合はちょっと待って再取得    -   Wait a minute to re- acquired if you can not get
                        setTimeout(function() { get_movie_listitem_with_ajax(); }, 1000);
                        return;
                    }
                    wrap.html(response);
                });
            };
            get_movie_listitem_with_ajax();
        });
    }
});

