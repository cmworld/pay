
<form id="payform" name="payform" action="{{$api_url}}" method="POST">
    @foreach ($params as $key => $val)
        <input type="hidden" name="{{$key}}" value="{{$val}}"/>
    @endforeach
    <input type="submit" value="ok" style="display:none;">
</form>

<script>document.forms['payform'].submit();</script>