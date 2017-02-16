<?php
$to = "vk.bkbiet@gmail.com";
$subject = "My subject";
$txt = "use this link  http://localhost/gcm_chat/v1/user/login?email=vk.bk@gmail.com";
$headers = "From: vicky@example.com" . "\r\n" .
"CC: somebodyelse@example.com";

mail($to,$subject,$txt,$headers);
?>