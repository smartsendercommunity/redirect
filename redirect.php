<?php

// v1   10.11.2021
// Powered by Smart Sender
// https://smartsender.com

ini_set('max_execution_time', '1700');
set_time_limit(1700);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: application/json');
header('Content-Type: application/json; charset=utf-8');


$ssToken = "";
$defaultUrl = "https://google.com";

$input = json_decode(file_get_contents("php://input"), true);

function send_bearer($url, $token, $type = "GET", $param = []){
		
$descriptor = curl_init($url);

 curl_setopt($descriptor, CURLOPT_POSTFIELDS, json_encode($param));
 curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($descriptor, CURLOPT_HTTPHEADER, array('User-Agent: M-Soft Integration', 'Content-Type: application/json', 'Authorization: Bearer '.$token)); 
 curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $type);

    $itog = curl_exec($descriptor);
    curl_close($descriptor);

   		 return $itog;
		
}
function gen_random_string($length=6) {
    $chars ="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";//length:36
    $final_rand='';
    for ($i=0;$i<$length; $i++) {
        $final_rand .= $chars[ rand(0,strlen($chars)-1)];
    }
    return $final_rand;
}
{
    $dir = dirname($_SERVER["PHP_SELF"]);
    $url = ((!empty($_SERVER["HTTPS"])) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . $dir;
    $url = explode("?", $url);
    $url = $url[0];
	if (substr($url, -1) != "/") {
		$url = $url."/";
	}
    if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/.htaccess")) {
        file_put_contents($_SERVER["DOCUMENT_ROOT"]."/.htaccess", "RewriteEngine On".PHP_EOL."RewriteBase /".PHP_EOL."RewriteRule ^([^\.]+)$ $1.php".PHP_EOL."RewriteRule .* - [e=HTTP_AUTHORIZATION:%{HTTP:Authorization}]");
    }
    if (!file_exists("redirectData")) {
        mkdir("redirectData");
    }
}

if ($input["url"] != NULL && $input["userId"] != NULL) {
    if ($input["tag"] != NULL) {
        $tagsData = json_decode(send_bearer("https://api.smartsender.com/v1/tags?page=1&limitation=20&term=".$input["tag"], $ssToken), true);
        if (is_array($tagsData["collection"]) === true) {
            foreach ($tagsData["collection"] as $tagsSS) {
                if ($tagsSS["name"] == $input["tag"]) {
                    $dataUrl["tag"] = $tagsSS["id"];
                    break;
                }
            }
        }
        if ($dataUrl["tag"] == NULL) {
            $result["state"] = false;
            $result["message"]["tag"] = "tag not found";
        }
    }
    if ($input["funnel"] != NULL) {
        $tagsData = json_decode(send_bearer("https://api.smartsender.com/v1/funnels?page=1&limitation=20&term=".$input["funnel"], $ssToken), true);
        if (is_array($funnelsData["collection"]) === true) {
            foreach ($funnelsData["collection"] as $funnelsSS) {
                if ($funnelsSS["name"] == $input["funnel"]) {
                    $dataUrl["funnel"] = $funnelsSS["serviceKey"];
                    break;
                }
            }
        }
        if ($dataUrl["funnel"] == NULL) {
            $result["state"] = false;
            $result["message"]["funnel"] = "funnel not found";
        }
    }
    if ($input["trigger"] != NULL) {
        $dataUrl["trigger"] = $input["trigger"];
    }
    $dataUrl["url"] = $input["url"];
    $dataUrl["userId"] = $input["userId"];
    for ($code = gen_random_string(); file_exists("redirectData/".$code.".json"); $code = gen_random_string()) {
        
    }
    if (file_put_contents("redirectData/".$code.".json", json_encode($dataUrl))) {
        $htaccess = file_get_contents($_SERVER["DOCUMENT_ROOT"]."/.htaccess");
        if (stripos($htaccess, "RewriteEngine On") !== false && stripos($htaccess, "RewriteRule ^([^\.]+)$ $1.php") !== false && stripos($htaccess, "RewriteBase /") !== false && !file_exists("redirect")) {
            $result["state"] = true;
            $result["message"]["url"] = $url."redirect?".$code;
        } else {
            $result["state"] = true;
            $result["message"]["url"] = $url."redirect.php?".$code;
        }
    } else {
        $result["state"] = false;
        $resilt["message"]["error"] = "write server error";
    }
    echo json_encode($result);
} else if ($_GET != NULL && stripos(getallheaders()["User-Agent"], "Gecko") !== false) {
    foreach ($_GET as $k => $v) {
        if (file_exists("redirectData/".$k.".json")) {
            $dataUrl = json_decode(file_get_contents("redirectData/".$k.".json"), true);
            break;
        }
    }
    if ($dataUrl == NULL) {
        header('Location: '.$defaultUrl);
    } else {
        if ($dataUrl["tag"] != NULL) {
            send_bearer("https://api.smartsender.com/v1/contacts/".$dataUrl["userId"]."/tags/".$dataUrl["tag"], $ssToken, "POST");
        }
        if ($dataUrl["funnel"] != NULL) {
            send_bearer("https://api.smartsender.com/v1/contacts/".$dataUrl["userId"]."/funnels/".$dataUrl["funnel"], $ssToken, "POST");
        }
        $userChat = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$dataUrl["userId"]."/chat", $ssToken), true);
        $operators = json_decode(send_bearer("https://api.smartsender.com/v1/operators?page=1&limitation=1", $ssToken), true);
        $send["text"] = "Пользователь перешел по ссылке:".PHP_EOL.$dataUrl["url"];
        send_bearer("https://api.smartsender.com/v1/chats/".$userChat["id"]."/forward/".$operators["collection"][0]["id"], $ssToken, "POST", $send);
        header("Location: ".$dataUrl["url"]);
    }
} else {
    header('Location: '.$defaultUrl);
}
