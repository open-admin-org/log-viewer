<div class="row">
    <div class="col-md-10">
        <div class="card card-primary">
            <div class="card-header with-border">
                <button type="button" class="btn btn-primary btn-sm log-refresh"><i class="icon-refresh"></i> {{ trans('admin.refresh') }}</button>
                <button type="button" class="btn btn-light btn-sm log-live"><i id="live-indicator" class="icon-play"></i> </button>
                <div class="float-end">
                    <div class="btn-group">
                        @if ($prevUrl)
                        <a href="{{ $prevUrl }}" class="btn btn-light btn-sm"><i class="icon-chevron-left"></i></a>
                        @endif
                        @if ($nextUrl)
                        <a href="{{ $nextUrl }}" class="btn btn-light btn-sm"><i class="icon-chevron-right"></i></a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body no-padding">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Level</th>
                                <th>Env</th>
                                <th>Time</th>
                                <th>Message</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>

                        @foreach($logs as $index => $log)
                            <tr>
                                <td><span class="badge bg-{{\OpenAdmin\Admin\LogViewer\LogViewer::$levelColors[$log['level']]}}">{{ $log['level'] }}</span></td>
                                <td><strong>{{ $log['env'] }}</strong></td>
                                <td style="width:150px;">{{ $log['time'] }}</td>
                                <td><code style="word-break: break-all;">{{ $log['info'] }}</code></td>
                                <td>
                                    @if(!empty($log['trace']))
                                    <a class="btn btn-primary btn-xs" style="position:absolute;right:20px;" data-bs-toggle="collapse" data-bs-target="#trace-{{$index}}"><i class="icon-info"></i>&nbsp;Exception&nbsp;</a>
                                    @endif
                                </td>
                            </tr>
                            @if (!empty($log['trace']))
                            <tr>
                                <td colspan="5" class="p-0 border-0"><div class="collapse" id="trace-{{$index}}" style="white-space: pre-wrap;background: #333;color: #fff; padding: 10px;">{{ $log['trace'] }}</div></td>
                            </tr>
                            @endif
                        @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-2">

        <div class="card card-solid">
            <div class="card-header with-border">
                <h3 class="card-title">Files</h3>
            </div>
            <div class="card-body no-padding">
                <ul class="nav nav-pills flex-column">
                    @foreach($logFiles as $logFile)
                        <li @if($logFile == $fileName)class="active"@endif>
                            <a href="{{ route('log-viewer-file', ['file' => str_replace($bypass_protected_urls_find,$bypass_protected_urls_replace,$logFile)]) }}"><i class="icon-{{ ($logFile == $fileName) ? 'folder-open' : 'folder' }}"></i>{{ $logFile }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="card card-solid mt-4">
            <div class="card-header with-border">
                <h3 class="card-title">Info</h3>
            </div>
            <div class="card-body no-padding">
                <ul class="nav nav-pills nav-stacked">
                    <li class="margin: 10px;">
                        <a>Size: {{ $size }}</a>
                    </li>
                    <li class="margin: 10px;">
                        <a>Updated at: {{ date('Y-m-d H:i:s', filectime($filePath)) }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<script data-exec-on-popstate>

    (function () {

        document.querySelector('.log-refresh').addEventListener('click', function() {
            admin.ajax.reload();
        });

        var pos = {{ $end }};

        function changePos(offset){
            pos = offset;
        }

        function fetch() {
            var url = '{{ $tailPath }}';
            var data = {offset : pos}
            admin.ajax.get(url,data,function(response){
                var logs = response.data.logs;
                for (var i in logs) {
                    $('table > tbody > tr:first').before(logs[i]);
                }
                changePos(data.pos);
            });
        }

        document.querySelector('.log-live').addEventListener("click",function(event) {

            if (typeof(window.logViewerState) == 'undefined' || window.logViewerState == 'pause'){
                document.querySelector("#live-indicator").classList.add("icon-pause")
                document.querySelector("#live-indicator").classList.remove("icon-play");

                window.logViewerState = "play";
                setTimeout(function(){
                    document.addEventListener("click",clickDisableLive);
                },10);
            }else{
                document.querySelector("#live-indicator").classList.remove("icon-pause")
                document.querySelector("#live-indicator").classList.add("icon-play");
                window.logViewerState = "pause";
                disableLive();
            }

            window.refreshTimeout = setTimeout(function() {
                admin.ajax.reload();
            }, 5000);
        });

        function clickDisableLive(event) {

            if (event.target.getAttribute("id") != "live-indicator"){
                disableLive();
            }
        }

        function disableLive(){
            window.logViewerState = "pause";
            document.removeEventListener("click",clickDisableLive);
            document.querySelector("#live-indicator").classList.add("icon-play")
            document.querySelector("#live-indicator").classList.remove("icon-pause");
            clearTimeout(window.refreshTimeout );
        }

        if(window.logViewerState == "play"){
            document.querySelector("#live-indicator").classList.remove("icon-play")
            document.querySelector("#live-indicator").classList.add("icon-pause");

            window.refreshTimeout = setTimeout(function() {
                admin.ajax.reload();
            }, 5000);
        }
    })();

</script>