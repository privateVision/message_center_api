<!doctype html>
<html lang="{{ config('app.locale') }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>安锋api在线测试平台</title>
        <link href="/css/app.css" type="text/css" rel="stylesheet" media="screen,projection"/>
        <script type="text/javascript" src="/js/app.js"></script>
    <body>
        <div class="flex-center position-ref full-height">
            <div class="container">
                <div class="row">
                    <div class="col-md-4" style="border:1px solid #d1d1d1; min-height:768px;">
                        <div class="row">
                            <div class="col-md-12" style="border:1px solid #d1d1d1;border-radius:20px;">
                                <h4>安锋api在线测试平台</h4>
                                <p>在线帮助文档查看地址<a href="http://pms.qcwan.com:8080/index.php?s=/3&page_id=22" target="_blank">在线文档</a></p>
                                <p>使用说明：请使用平台账户登录</p>
                                <div class="row">
                                    <div class="col-md-12">
                                        <p><div class="input-group input-group-md">
                                            <span class="input-group-addon">用户名</span>
                                            <input type="text" name="user" class="form-control" placeholder="请输入用户名" aria-describedby="sizing-addon1">
                                        </div></p>
                                        <p><div class="input-group input-group-md">
                                            <span class="input-group-addon">密码</span>
                                            <input type="password" name="pwd" class="form-control" placeholder="请输入密码" aria-describedby="sizing-addon1">
                                        </div></p>
                                        <p class="text-center">
                                            <button type="button" class="btn btn-primary" event-action-demo="login">登录</button>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12" style="border:1px solid #d1d1d1;border-radius:20px; margin-top:5px;">
                                <h4>接口相关测试</h4>
                                <div class="form-group">
                                    <label for="inputEmail3" class="col-sm-4 control-label">_token</label>
                                    <div class="col-sm-8">
                                        <input type="text" name="_token" class="form-control" placeholder="登录后自动生成">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="inputEmail3" class="col-sm-4 control-label">_appid</label>
                                    <div class="col-sm-8">
                                        <input type="text" name="_appid" value="2" class="form-control" placeholder="后台渠道appid">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="inputEmail3" class="col-sm-4 control-label">_appkey</label>
                                    <div class="col-sm-8">
                                        <input type="text" name="_appkey" value="ebe89a4c54f35e593d86455aab4343a8" class="form-control" placeholder="后台渠道appkey">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="inputEmail3" class="col-sm-4 control-label">接口名称</label>
                                    <div class="col-sm-8">
                                        <select class="form-control" name="url">
                                            @foreach ($urls as $vo)
                                            <option value="{{$vo['url']}}" data-controller="{{$vo['controller']}}">{{$vo['url']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-12">
                                        <p>请输入对应接口主要参数（注意:请使用键值对格式，多个逗号分隔）：</p>
                                        <textarea type="text" name="extend" class="form-control" placeholder="键值对格式接口参数" rows="4">key:value</textarea>
                                    </div>
                                </div>
                                <div class="form-group text-center">
                                    <div class="col-sm-12" style="margin:10px;">
                                        <button type="button" class="btn btn-primary" event-action-demo="save">确实</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12" style="border:1px solid #d1d1d1;border-radius:20px; margin-top:5px;">
                                <h4>请求数据如下：</h4>
                                <p node-type="controller" style="word-break:break-all; word-wrap:break-all;">控制器：<span></span></p>
                                <p node-type="request" style="word-break:break-all; word-wrap:break-all;">请求数据：<span></span></p>
                                <p node-type="sign" style="word-break:break-all; word-wrap:break-all;">请求签名：<span></span></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8" style="border:1px solid #d1d1d1;border-radius:20px;min-height:768px;">
                        <h4>接口返回数据如下：</h4>
                        <div id="responseShow" style="height: 600px; overflow-y:scroll;"></div>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            var demoClass = function(){
                var _token = '{{csrf_token()}}',
                    pubs = null;

                function Create(){
                    this.init();
                }
                Create.prototype = {
                    init : function() {
                        this.addEvents();
                    },
                    addEvents : function() {
                        var self = this;
                        $(document).on('click', '*[event-action-demo]', function(){
                            var action = $(this).attr('event-action-demo');
                            self[action]($(this));
                        });

                        $('select[name="url"]').on('change', function(){
                            var url = $(this).val();
                            var controller = $(this).find('option[value="'+url+'"]').attr('data-controller');
                            $('p[node-type="controller"] span').html(controller);
                        });
                    },
                    login : function(target) {
                        var dt = {};
                        dt._token = _token;
                        dt.user = $('input[name="user"]').val();
                        dt.pwd = $('input[name="pwd"]').val();
                        if(!dt.user || !dt.pwd){
                            alert('用户名或密码不能为空！');return ;
                        }
                        dt.appid = $('input[name="_appid"]').val();
                        dt.appkey = $('input[name="_appkey"]').val();
                        if(!dt.appid || !dt.appkey){
                            alert('_appid或_appkey不能为空！');return ;
                        }
                        if(!target.hasClass('disabled')){
                            target.addClass('disabled');
                            $.ajax({
                                "type":"POST",
                                "url" : '/index.php/login',
                                "data" : dt,
                                "dataType" : 'json',
                                "success" : function(res) {
                                    target.removeClass('disabled');
                                    if(res.status == 0) {
                                        pubs = res.data.pubs;
                                        $('input[name="_token"]').val(res.data.token);
                                    } else {
                                        alert(res.msg);
                                    }
                                },
                                "error" : function(){
                                    target.removeClass('disabled');
                                    alert('服务器异常');
                                }
                            })
                        }
                    },
                    save : function(target){
                        var dt = {}, self = this;
                        dt._token = _token;
                        dt.token = $('input[name="_token"]').val();
                        dt.appid = $('input[name="_appid"]').val();
                        dt.appkey = $('input[name="_appkey"]').val();
                        if(!dt.token){
                            alert('token不能为空！');return ;
                        }
                        if(!dt.appid || !dt.appkey){
                            alert('_appid或_appkey不能为空！');return ;
                        }
                        var extend = $('textarea[name="extend"]').val();
                        var json = {};
                        extend = $.trim(extend);
                        if(!!extend){
                           var orgs = extend.split(',');
                           for(var i=0; i<orgs.length; i++) {
                               if(!!orgs[i]){
                                   var org = $.trim(orgs[i]);
                                   var tmps = org.split(':');
                                   var k = $.trim(tmps[0]);
                                   var v = $.trim(tmps[1]);
                                   json[k] = v;
                               }
                           }
                        }
                        dt.pubs = pubs;
                        dt.extend = JSON.stringify(json);
                        //console.log(dt);return;

                        if(!target.hasClass('disabled')){
                            target.addClass('disabled');
                            $.ajax({
                                "type": "POST",
                                "url": '/index.php/sign',
                                "data": dt,
                                "dataType": 'json',
                                "anyse": false,
                                "cache":false,
                                "success": function (res) {
                                    console.log('sign',res);
                                    target.removeClass('disabled');
                                    if(res.status == 0){
                                        $('p[node-type="request"] span').html(res.data.query);
                                        $('p[node-type="sign"] span').html(res.data.data._sign);
                                        self.apiRequest(res.data.data);
                                    } else {
                                        alert(res.msg);
                                    }
                                },
                                "error": function(){
                                    target.removeClass('disabled');
                                    alert('服务器异常');
                                }
                            });
                        }
                    },
                    //请求接口
                    apiRequest : function(params){
                        var url = $('select[name="url"]').val();
                        $.ajax({
                            "type": "POST",
                            "url": '/index.php/'+url,
                            "data": params,
                            "dataType": 'json',
                            "anyse": false,
                            "cache":false,
                            "success": function (res) {
                                console.log('api,',res);
                                Process(res);
                            },
                            "error": function(){
                                alert('服务器异常');
                            }
                        });
                    }
                };
                new Create();
            }(window.jQuery);
        </script>
        <script>
            // we need tabs as spaces and not CSS magin-left
            // in order to ratain format when coping and pasing the code
            window.TAB = "  ";

            function IsArray(obj) {
                return obj &&
                    typeof obj === 'object' &&
                    typeof obj.length === 'number' &&
                    !(obj.propertyIsEnumerable('length'));
            }

            function Process(data){
                var json = data;//$("#responseShow").html();
                var html = "";
                try{
                    if(json == "") json = "\"\"";
                    //var obj = $.parseJSON(json);
                    obj = json;
                    html = ProcessObject(obj, 0, false, false, false);
                    $("#responseShow").html("<PRE class='CodeContainer'>"+html+"</PRE>");
                }catch(e){
                    alert("JSON is not well formated:\n"+e.message);
                }
            }
            function ProcessObject(obj, indent, addComma, isArray, isPropertyContent){
                var html = "";
                var comma = (addComma) ? "<span class='Comma'>,</span> " : "";
                var type = typeof obj;

                if(IsArray(obj)){
                    if(obj.length == 0){
                        html += GetRow(indent, "<span class='ArrayBrace'>[ ]</span>"+comma, isPropertyContent);
                    }else{
                        html += GetRow(indent, "<span class='ArrayBrace'>[</span>", isPropertyContent);
                        for(var i = 0; i < obj.length; i++){
                            html += ProcessObject(obj[i], indent + 1, i < (obj.length - 1), true, false);
                        }
                        html += GetRow(indent, "<span class='ArrayBrace'>]</span>"+comma);
                    }
                }else if(type == 'object' && obj == null){
                    html += FormatLiteral("null", "", comma, indent, isArray, "Null");
                }else if(type == 'object'){
                    var numProps = 0;
                    for(var prop in obj) numProps++;
                    if(numProps == 0){
                        html += GetRow(indent, "<span class='ObjectBrace'>{ }</span>"+comma, isPropertyContent);
                    }else{
                        html += GetRow(indent, "<span class='ObjectBrace'>{</span>", isPropertyContent);
                        var j = 0;
                        for(var prop in obj){
                            html += GetRow(indent + 1, "<span class='PropertyName'>"+prop+"</span>: "+ProcessObject(obj[prop], indent + 1, ++j < numProps, false, true));
                        }
                        html += GetRow(indent, "<span class='ObjectBrace'>}</span>"+comma);
                    }
                }else if(type == 'number'){
                    html += FormatLiteral(obj, "", comma, indent, isArray, "Number");
                }else if(type == 'boolean'){
                    html += FormatLiteral(obj, "", comma, indent, isArray, "Boolean");
                }else if(type == 'function'){
                    obj = FormatFunction(indent, obj);
                    html += FormatLiteral(obj, "", comma, indent, isArray, "Function");
                }else if(type == 'undefined'){
                    html += FormatLiteral("undefined", "", comma, indent, isArray, "Null");
                }else{
                    html += FormatLiteral(obj, "\"", comma, indent, isArray, "String");
                }
                return html;
            }
            function FormatLiteral(literal, quote, comma, indent, isArray, style){
                if(typeof literal == 'string')
                    literal = literal.split("<").join("&lt;").split(">").join("&gt;");
                var str = "<span class='"+style+"'>"+quote+literal+quote+comma+"</span>";
                if(isArray) str = GetRow(indent, str);
                return str;
            }
            function FormatFunction(indent, obj){
                var tabs = "";
                for(var i = 0; i < indent; i++) tabs += window.TAB;
                var funcStrArray = obj.toString().split("\n");
                var str = "";
                for(var i = 0; i < funcStrArray.length; i++){
                    str += ((i==0)?"":tabs) + funcStrArray[i] + "\n";
                }
                return str;
            }
            function GetRow(indent, data, isPropertyContent){
                var tabs = "";
                for(var i = 0; i < indent && !isPropertyContent; i++) tabs += window.TAB;
                if(data != null && data.length > 0 && data.charAt(data.length-1) != "\n")
                    data = data+"\n";
                return tabs+data;
            }
        </script>
    </body>
</html>
