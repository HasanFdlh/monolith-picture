<html>
<body>
<p>Hello,</p>

<p>Thank you for using our photobooth. Attached is your session package for session code <strong>{{ $session->session_code }}</strong>.</p>

@if(!empty($session->media) && count($session->media) > 0)
    <p>Included files:</p>
    <ul>
    @foreach($session->media as $m)
        <li>{{ $m->file_name }}</li>
    @endforeach
    </ul>
@endif

<p>Regards,<br/>Photobooth</p>
</body>
</html>
