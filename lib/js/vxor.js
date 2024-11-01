$(function() {

    function ajax(opt){
        /* opt = {
                u: url, -- vxor_url
                m: method, -- get
                d: data, -- null
                dt: dataType, -- html
                b: beforeSend,
                e: error,
                s: success
                fn: 'function'
        }; */

        url = opt.u ? opt.u : vxor_url + '&do=ajax';
        if(opt.fn){
            url += '&action='+opt.fn;
        }
        type = opt.m ? opt.m : 'GET';
        data = opt.d ? opt.d : null;
        dataType = opt.dt ? opt.dt : 'html';
        beforeSend = opt.b ? opt.b : null;
        error = opt.e ? opt.e : function(){
            alert(lang.commonError);
            document.body.style.cursor = 'auto';
        };
        success = opt.s ? opt.s : function(d){
            alert(d)
        };

        $.ajax( {
            url : url,
            type : type,
            data : data,
            dataType : dataType,
            beforeSend : beforeSend,
            error : error,
            success : success
        });
    }

    /*******************************************/

    function actions(id){
        var form = $('#vxorform');
        var url = vxor_url;
        var func = id;
        var data = form.serialize();
        var error = function(request){
            if(request.responseText){
                alert(request.responseText);
            }else{
                alert('lang.commonError');
            }
        };
        var success = function(result){
            $('head').empty();
            $('body').empty().append(result);
        };
        ajax({
            u:url,
            m:'POST',
            d:data,
            e:error,
            s:success,
            fn:func
        });
    }
    $('#config, #extend, #convert, #clean').click(function(){
        var id = $(this).attr('id');
        actions(id);
        return false;
    });

    function ext_post_add() {
        function add(){
            vxor_ext_post_id +=1;
            ext_post_text = '<tr id="ext_post_'+ vxor_ext_post_id +'"><th scope="row"><label for="ext_post_old_'+ vxor_ext_post_id +'">'+ vxor_ext_post_title + ' ' + vxor_ext_post_id +'</label></th>';
            ext_post_text += '<td><input type="text" id="ext_post_old_'+ vxor_ext_post_id +'" name="ext_post_old[]" /></td>';
            ext_post_text += '<td><input type="text" id="ext_post_new_'+ vxor_ext_post_id +'" name="ext_post_new[]" /></td></tr>';
            $('#extend-table').append(ext_post_text);
        }

        $('#ext_post_add').click(function() {
            add();
            return false;
        });
   }
    ext_post_add();

    function ext_post_del() {
        function del(){
            $('#ext_post_'+ vxor_ext_post_id ).remove();
            if(vxor_ext_post_id > 0){
                vxor_ext_post_id--;
            }
        }

        $('#ext_post_del').click(function() {
            del();
            return false;
        });
    }
    ext_post_del();

    function convert() {
        var step = $('#vxor_next_step').attr('value');
        if (!step)
            return;
        if( step == 'finish'){
            actions(step);
            return;
        }

        var url = vxor_url + '&action=convert&step='+step;
        var error = function(request){
            if(request.responseText){
                alert(request.responseText);
            }else{
                alert('lang.commonError');
            }
        };
        var success = function(result){
            $('head').empty();
            $('body').empty().append(result);
        };
        ajax({
            u:url,
            m:'POST',
            e:error,
            s:success
        });
    }
    convert();
});
