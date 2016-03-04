<?php

$url = $_GET["src"];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER_OUT, 1);
curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$content = curl_exec($ch);
$info = curl_getinfo($ch);

header('Content-Type:' . $info['content_type']);

$imgRes = imagecreatefromstring($content);

imagejpeg($imgRes);

imagedestroy($imgRes);
