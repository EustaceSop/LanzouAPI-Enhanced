<?php
/**
 * @package Lanzou
 * @author Filmy,hanximeng
 * @version 1.3.107
 * @Date 2026-06-01
 * @link https://hanximeng.com
 */
//屏蔽报错
error_reporting(0);
//无 url 参数时输出操作界面
if (empty($_GET['url'])) {
    header('Content-Type:text/html; charset=utf-8');
    echo <<<'HTML'
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>藍奏雲直鏈解析</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: "Microsoft JhengHei", Arial, sans-serif; background: #f0f2f5; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .box { background: #fff; width: 100%; max-width: 640px; padding: 32px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
    h1 { font-size: 22px; margin: 0 0 20px; }
    label { display: block; font-size: 14px; color: #555; margin: 14px 0 6px; }
    input[type=text], input[type=password] { width: 100%; padding: 10px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px; }
    input:focus { outline: none; border-color: #1677ff; }
    button { margin-top: 20px; width: 100%; padding: 12px; background: #1677ff; color: #fff; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; }
    button:disabled { background: #91caff; cursor: default; }
    .dbgcheck { display: flex; align-items: center; gap: 6px; margin-top: 14px; font-size: 13px; color: #888; }
    .dbgcheck input { width: auto; }
    .result { display: none; margin-top: 24px; border-top: 1px solid #eee; padding-top: 20px; }
    .row { margin: 8px 0; font-size: 14px; word-break: break-all; }
    .row b { color: #555; }
    .link { color: #1677ff; }
    .actions { margin-top: 14px; display: flex; gap: 10px; }
    .actions button, .actions a { flex: 1; margin: 0; padding: 10px; font-size: 14px; text-align: center; border-radius: 6px; text-decoration: none; }
    .actions a { background: #52c41a; color: #fff; display: inline-block; }
    .copy { background: #722ed1; }
    .error { display: none; margin-top: 24px; background: #fff2f0; border: 1px solid #ffccc7; color: #cf1322; padding: 12px; border-radius: 6px; font-size: 14px; }
    .flist { display: none; margin-top: 24px; border-top: 1px solid #eee; padding-top: 16px; }
    .flist h2 { font-size: 16px; margin: 0 0 12px; }
    .frow { display: flex; align-items: center; gap: 8px; padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
    .frow .fname { flex: 1; word-break: break-all; }
    .frow .fsize { color: #999; font-size: 12px; white-space: nowrap; }
    .frow a, .frow button { padding: 6px 10px; font-size: 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; color: #fff; white-space: nowrap; }
    .frow .fdl { background: #52c41a; width: auto; margin: 0; }
    .frow .fcp { background: #722ed1; width: auto; margin: 0; }
    .frow .fnone { color: #cf1322; font-size: 12px; }
    .debuglog { display: none; margin-top: 16px; background: #1e1e1e; color: #9cdcfe; padding: 12px; border-radius: 6px; font-size: 12px; font-family: Consolas, monospace; white-space: pre-wrap; word-break: break-all; max-height: 300px; overflow: auto; }
</style>
</head>
<body>
<div class="box">
    <h1>藍奏雲直鏈解析</h1>
    <label for="url">分享連結</label>
    <input type="text" id="url" placeholder="https://wwi.lanzoup.com/xxxxxx">
    <label for="pwd">提取密碼(沒有就留空)</label>
    <input type="text" id="pwd" placeholder="">
    <div class="dbgcheck"><input type="checkbox" id="dbg"> 顯示除錯資訊(解析失敗時看詳細步驟)</div>
    <button id="btn" onclick="parse()">解析</button>
    <div class="error" id="err"></div>
    <div class="result" id="result">
        <div class="row"><b>檔案名稱:</b> <span id="name"></span></div>
        <div class="row"><b>檔案大小:</b> <span id="size"></span></div>
        <div class="row"><b>直鏈:</b> <a class="link" id="durl" target="_blank"></a></div>
        <div class="actions">
            <a id="dl" target="_blank">直接下載</a>
            <button class="copy" onclick="copyLink()">複製直鏈</button>
        </div>
    </div>
    <div class="flist" id="flist">
        <h2 id="foldername"></h2>
        <div id="frows"></div>
    </div>
    <div class="debuglog" id="dbglog"></div>
</div>
<script>
function parse() {
    var url = document.getElementById('url').value.trim();
    var pwd = document.getElementById('pwd').value.trim();
    if (!url) { showErr('請輸入分享連結'); return; }
    var btn = document.getElementById('btn');
    btn.disabled = true;
    btn.textContent = '解析中...';
    document.getElementById('err').style.display = 'none';
    document.getElementById('result').style.display = 'none';
    document.getElementById('flist').style.display = 'none';
    document.getElementById('dbglog').style.display = 'none';
    var api = '?url=' + encodeURIComponent(url) + (pwd ? '&pwd=' + encodeURIComponent(pwd) : '');
    if (document.getElementById('dbg').checked) api += '&debug=1';
    fetch(api)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            btn.disabled = false;
            btn.textContent = '解析';
            if (d.debug) showDebug(d.debug);
            if (d.code !== 200) { showErr(d.msg || '解析失敗'); return; }
            if (d.files) { showFiles(d); return; }
            document.getElementById('name').textContent = d.name || '';
            document.getElementById('size').textContent = d.filesize || '';
            var a = document.getElementById('durl');
            a.href = d.downUrl;
            a.textContent = d.downUrl;
            document.getElementById('dl').href = d.downUrl;
            document.getElementById('result').style.display = 'block';
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = '解析';
            showErr('請求失敗,請稍後再試');
        });
}
function showErr(msg) {
    var e = document.getElementById('err');
    e.textContent = msg;
    e.style.display = 'block';
}
function showDebug(logs) {
    var d = document.getElementById('dbglog');
    d.textContent = logs.join("\n");
    d.style.display = 'block';
}
function showFiles(d) {
    document.getElementById('foldername').textContent = '資料夾:' + (d.folder || '') + '(' + d.files.length + ' 個檔案)';
    var box = document.getElementById('frows');
    box.innerHTML = '';
    d.files.forEach(function(f) {
        var row = document.createElement('div');
        row.className = 'frow';
        var html = '<span class="fname">' + esc(f.name) + '</span><span class="fsize">' + esc(f.size || '') + '</span>';
        if (f.downUrl) {
            html += '<a class="fdl" href="' + esc(f.downUrl) + '" target="_blank">下載</a>';
            html += '<button class="fcp" onclick="copyText(\'' + encodeURIComponent(f.downUrl) + '\')">複製</button>';
        } else {
            html += '<a class="fdl" href="' + esc(f.share) + '" target="_blank">分享頁</a>';
            html += '<span class="fnone">直鏈解析失敗</span>';
        }
        row.innerHTML = html;
        box.appendChild(row);
    });
    document.getElementById('flist').style.display = 'block';
}
function esc(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function copyText(enc) {
    doCopy(decodeURIComponent(enc));
}
function doCopy(t) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(t).then(function() { alert('已複製'); });
    } else {
        var i = document.createElement('input');
        i.value = t;
        document.body.appendChild(i);
        i.select();
        document.execCommand('copy');
        document.body.removeChild(i);
        alert('已複製');
    }
}
function copyLink() {
    doCopy(document.getElementById('durl').href);
}
</script>
</body>
</html>
HTML;
    die;
}
header('Access-Control-Allow-Origin:*');
header('Content-Type:application/json; charset=utf-8');

//除錯模式: &debug=1 時輸出每個步驟
$DEBUG = !empty($_GET['debug']);
$DBG = array();

function dbg($msg) {
    global $DEBUG, $DBG;
    if ($DEBUG) $DBG[] = $msg;
}

function fail_json($msg) {
    global $DEBUG, $DBG;
    $out = array('code' => 400, 'msg' => $msg);
    if ($DEBUG) $out['debug'] = $DBG;
    die(json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

//默认UA
$UserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36';
$url = isset($_GET['url']) ? $_GET['url'] : "";
$pwd = isset($_GET['pwd']) ? $_GET['pwd'] : "";
$type = isset($_GET['type']) ? $_GET['type'] : "";
$webpage = explode('?',$url)['1'];
//判断传入链接参数是否为空
if (empty($url)) {
    fail_json('请输入URL');
}
//一个简单的链接处理
$u = explode('.com/', $url);
if (!isset($u[1])) {
    fail_json('URL 格式無法識別,請確認是藍奏雲分享連結');
}
$url = 'https://www.lanzouf.com/'.$u[1];

//方法1: 桌面版 iframe/ajaxm 流程(原版,單檔)
$result = parse_desktop($url, $pwd, $webpage, $UserAgent);
//方法2: 手機版流程(參考 worker 版,單檔)
if ($result === null) {
    dbg('桌面版流程失敗,改用手機版流程重試');
    $result = parse_mobile($url);
}
//方法3: 資料夾分享(filemoreajax 列表 -> 逐檔解析)
if ($result === null) {
    dbg('單檔流程失敗,檢查是否為資料夾分享');
    $result = parse_folder($url, $pwd, $UserAgent);
}
if ($result === null) {
    fail_json('解析失敗,所有方法均未取得直鏈');
}

if (isset($result['files'])) {
    $out = array(
        'code' => 200,
        'msg' => '解析成功(資料夾)',
        'folder' => $result['folder'],
        'files' => $result['files']
    );
} else {
    $out = array(
        'code' => 200,
        'msg' => '解析成功',
        'name' => $result['name'],
        'filesize' => $result['filesize'],
        'downUrl' => $result['downUrl']
    );
}
if ($DEBUG) $out['debug'] = $DBG;
//判断是否是直接下载
if ($type != "down") {
    die(json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
} else {
    if (isset($result['files'])) {
        fail_json('資料夾連結不支援 type=down,請從 files 列表選擇檔案');
    }
    header("Location:".$result['downUrl']);
    die;
}

//桌面版解析流程(原版邏輯),失敗回傳 null
function parse_desktop($url, $pwd, $webpage, $UserAgent) {
    $cookie = "";
    $softInfo = MloocCurlGet($url, $UserAgent, "acw_sc__v2=".$cookie);
    //判断文件链接是否失效
    if (strstr($softInfo, "文件取消分享了") != false) {
        fail_json('文件取消分享了');
    }
    //取文件名称、大小
    preg_match('~style="font-size: 30px;text-align: center;padding: 56px 0px 20px 0px;">(.*?)</div>~', $softInfo, $softName);
    if(!isset($softName[1])) {
        preg_match('~<div class="n_box_3fn".*?>(.*?)</div>~', $softInfo, $softName);
    }
    preg_match('~<div class="n_filesize".*?>大小：(.*?)</div>~', $softInfo, $softFilesize);
    if(!isset($softFilesize[1])) {
        preg_match('~<span class="p7">文件大小：</span>(.*?)<br>~', $softInfo, $softFilesize);
    }
    if(!isset($softName[1])) {
        preg_match('~var filename = \'(.*?)\';~', $softInfo, $softName);
    }
    if(!isset($softName[1])) {
        preg_match('~div class="b"><span>(.*?)</span></div>~', $softInfo, $softName);
    }
    if(!isset($softName[1])) {
        dbg('桌面版: 無法提取檔名(不影響解析)');
    }
    if(!empty($webpage)){
        $softInfo = MloocCurlGet($url.$webpage);
    }
    //带密码的链接的处理
    if(strpos($softInfo, "function down_p(){") != false && empty($webpage)) {
        if(empty($pwd)) {
            fail_json('请输入分享密码');
        }
        preg_match_all("~'sign':'(.*?)',~", $softInfo, $segment);
        preg_match_all("~ajaxdata = '(.*?)'~", $softInfo, $signs);
        preg_match_all("/ajaxm\.php\?file=(\d+)/", $softInfo, $ajaxm);
        if(empty($segment[1][1])) { dbg('桌面版(密碼): 提取 sign 失敗'); return null; }
        if(empty($ajaxm[0][0])) { dbg('桌面版(密碼): 找不到 ajaxm.php 路徑'); return null; }
        $post_data = array(
            "action" => "downprocess",
            "sign" => $segment[1][1],
            "p" => $pwd,
            "kd" => 1
        );
        $softInfo = MloocCurlPost($post_data, "https://www.lanzouf.com/".$ajaxm[0][0], $url);
        $softName[1] = json_decode($softInfo, JSON_UNESCAPED_UNICODE)['inf'];
    } else {
        //不带密码的链接处理
        preg_match("~\n<iframe.*?name=\"[\s\S]*?\"\ssrc=\"\/(.*?)\"~", $softInfo, $link);
        //蓝奏云新版页面正则规则
        if(empty($link[1])) {
            preg_match("~<iframe.*?name=\"[\s\S]*?\"\ssrc=\"\/(.*?)\"~", $softInfo, $link);
        }
        if(empty($link[1])) {
            dbg('桌面版: 找不到 iframe 下載頁連結,頁面結構可能已改版');
            return null;
        }
        $ifurl = "https://www.lanzouf.com/" . $link[1];
        dbg('桌面版: iframe = '.$ifurl);
        if(!empty($webpage)){
            preg_match_all("~'sign':'(.*?)'~", $softInfo, $segment);
            preg_match_all("~ajaxdata = '(.*?)'~", $softInfo, $signs);
            preg_match_all("/ajaxm\.php\?file=(\d+)/", $softInfo, $ajaxm);
            $post_data = array(
                "action" => "downprocess",
                "websignkey" => "Em2R",
                "sign" => $segment[1][1],
                "websign" => 2,
                "kd" => 1,
                "ves" => 1
            );
        }else{
            $softInfo = MloocCurlGet($ifurl);
            preg_match_all("~wp_sign = '(.*?)'~", $softInfo, $segment);
            preg_match_all("~ajaxdata = '(.*?)'~", $softInfo, $signs);
            preg_match_all("/ajaxm\.php\?file=(\d+)/", $softInfo, $ajaxm);
            if(empty($segment[1][0])) { dbg('桌面版: 提取 wp_sign 失敗'); return null; }
            $post_data = array(
                "action" => "downprocess",
                "websignkey" => $signs[1][0],
                "signs" => $signs[1][0],
                "sign" => $segment[1][0],
                "websign" => '',
                "kd" => 1,
                "ves" => 1
            );
        }
        $ajaxmPath = $ajaxm[0][1] ?? $ajaxm[0][0] ?? '';
        if(empty($ajaxmPath)) {
            dbg('桌面版: 找不到 ajaxm.php 路徑');
            return null;
        }
        $softInfo = MloocCurlPost($post_data, "https://www.lanzouf.com/".$ajaxmPath, $ifurl, "", "acw_sc__v2=".$cookie);
    }
    $raw = $softInfo;
    $softInfo = json_decode($softInfo, true);
    if(!is_array($softInfo)) {
        dbg('桌面版: ajaxm 回應不是 JSON,前 200 字: '.mb_substr((string)$raw, 0, 200));
        return null;
    }
    //其他情况下的信息输出
    if ($softInfo['zt'] != 1) {
        if(!empty($pwd)) {
            fail_json($softInfo['inf']);
        }
        dbg('桌面版: ajaxm 回應錯誤: '.$softInfo['inf']);
        return null;
    }
    //拼接链接
    $downUrl1 = $softInfo['dom'] . '/file/' . $softInfo['url'];
    $softInfo = MloocCurlGet($downUrl1, $UserAgent, "acw_sc__v2=".$cookie);
    //解析最终直链地址
    $downUrl2 = MloocCurlHead($downUrl1, "https://developer.lanzoug.com", $UserAgent, "down_ip=1; expires=Sat, 16-Nov-2019 11:42:54 GMT; path=/; domain=.baidupan.com;acw_sc__v2=".$decrypted);
    //判断最终链接是否获取成功，如未成功则使用原链接
    if(strpos($downUrl2, "http") === false) {
        dbg('桌面版: 未取得重新導向直鏈,改用 /file/ 連結');
        $downUrl = $downUrl1;
    } else {
        //2025-03-17 新增后缀自定义功能
        if(!empty($_GET['n'])){
            preg_match_all("~(.*?)\?fn=(.*?)\\.~", $downUrl2, $rename);
            $downUrl = $rename['0']['0'].$_GET['n'];
        }else{
            $downUrl = $downUrl2;
        }
    }
    //2024-12-03 修复pid参数可能导致的服务器ip地址泄露
    $downUrl = preg_replace('/pid=(.*?.)&/', '', $downUrl);
    return array(
        'name' => isset($softName[1]) ? $softName[1] : "",
        'filesize' => isset($softFilesize[1]) ? $softFilesize[1] : "",
        'downUrl' => $downUrl
    );
}

//手機版解析流程(參考 worker 版: downurl -> vkjxld + hyggid),失敗回傳 null
function parse_mobile($url) {
    $ua = 'Mozilla/5.0 (Linux; Android 10; Pixel 4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36';
    $page1 = MloocCurlGet($url, $ua, '', 'https://www.lanzou.com/');
    if(!$page1) {
        dbg('手機版: 初始頁面請求失敗');
        return null;
    }
    if(strpos($page1, 'function down_p(){') !== false) {
        dbg('手機版: 此連結需要密碼,手機版流程不支援');
        return null;
    }
    if(!preg_match('/<a href="([^"]+)"[^>]*id="downurl"/i', $page1, $m)) {
        dbg('手機版: 找不到 downurl 連結,頁面結構可能已改版');
        return null;
    }
    $host = parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST);
    $downPage = $host.$m[1];
    dbg('手機版: downurl = '.$downPage);
    $page2 = MloocCurlGet($downPage, $ua, '', $url);
    if(!$page2) {
        dbg('手機版: 下載頁請求失敗');
        return null;
    }
    if(!preg_match('/(?:var\s+)?vkjxld\s*=\s*[\'"]([^\'"]+)/i', $page2, $m1)) {
        dbg('手機版: 提取 vkjxld 失敗');
        return null;
    }
    if(!preg_match('/(?:var\s+)?hyggid\s*=\s*[\'"]([^\'"]+)/i', $page2, $m2)) {
        dbg('手機版: 提取 hyggid 失敗');
        return null;
    }
    //新版頁面多一段 lanosso(可為空)
    preg_match('/(?:var\s+)?lanosso\s*=\s*[\'"]([^\'"]*)/i', $page2, $m3);
    $jump = $m1[1].$m2[1].(isset($m3[1]) ? $m3[1] : '');
    dbg('手機版: 拼接中轉連結成功,解析最終位址');
    //桌面 UA + down_ip cookie 才會回 302(手機 UA 只會拿到跳轉頁 HTML)
    $desktopUA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36';
    $final = MloocCurlHead($jump, "https://developer.lanzoug.com", $desktopUA, "down_ip=1; expires=Sat, 16-Nov-2019 11:42:54 GMT; path=/; domain=.baidupan.com;acw_sc__v2=");
    if(strpos($final, "http") === 0) {
        dbg('手機版: 取得最終 CDN 直鏈');
        $jump = $final;
    } else {
        dbg('手機版: 未取得 302,保留中轉連結(下載會經過跳轉頁)');
    }
    $jump = preg_replace('/pid=(.*?.)&/', '', $jump);
    return array(
        'name' => '',
        'filesize' => '',
        'downUrl' => $jump
    );
}

//資料夾分享解析:filemoreajax 列表 -> 每個檔案走單檔流程,失敗回傳 null
function parse_folder($url, $pwd, $UserAgent) {
    $page = MloocCurlGet($url, $UserAgent);
    if(!preg_match('/filemoreajax\.php\?file=(\d+)/', $page, $m)) {
        dbg('資料夾: 非資料夾分享頁(無 filemoreajax)');
        return null;
    }
    $fid = $m[1];
    preg_match("/'uid':'(.*?)'/", $page, $uid);
    preg_match("/'puid':'(.*?)'/", $page, $puid);
    preg_match("/ibf1fp\s*=\s*'(.*?)'/", $page, $t);
    preg_match("/_hddhi\s*=\s*'(.*?)'/", $page, $k);
    preg_match("/var\s+inbpjb\s*=\s*'(.*?)'/", $page, $fname);
    if(empty($uid[1]) || empty($puid[1]) || empty($t[1]) || empty($k[1])) {
        dbg('資料夾: 提取 uid/puid/t/k 失敗');
        return null;
    }
    dbg('資料夾: fid='.$fid.', 名稱='.(isset($fname[1]) ? $fname[1] : ''));
    $host = parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST);
    $post_data = array(
        'lx' => 2,
        'fid' => $fid,
        'uid' => $uid[1],
        'puid' => $puid[1],
        'pg' => 1,
        'rep' => '0',
        't' => $t[1],
        'k' => $k[1],
        'up' => 1,
        'ls' => 1,
        'pwd' => $pwd
    );
    $resp = MloocCurlPost($post_data, $host.'/filemoreajax.php?file='.$fid, $url, $UserAgent);
    $j = json_decode($resp, true);
    if(!is_array($j)) {
        dbg('資料夾: filemoreajax 回應不是 JSON,前 200 字: '.mb_substr((string)$resp, 0, 200));
        return null;
    }
    if($j['zt'] == 3) {
        fail_json(empty($pwd) ? '此資料夾需要密碼,請輸入分享密碼' : $j['info']);
    }
    if($j['zt'] == 2) {
        fail_json('資料夾內沒有檔案');
    }
    if($j['zt'] != 1) {
        dbg('資料夾: filemoreajax 回應錯誤: '.$j['info']);
        return null;
    }
    $files = array();
    foreach($j['text'] as $n) {
        // t==1 是推廣廣告,跳過
        if(!empty($n['t']) && $n['t'] == 1) continue;
        if(empty($n['id']) || $n['id'] == '-1') continue;
        $share = $host.'/'.$n['id'];
        dbg('資料夾: 解析檔案 '.$n['name_all'].' -> '.$share);
        $one = parse_desktop($share, '', '', $UserAgent);
        if($one === null) {
            $one = parse_mobile($share);
        }
        $files[] = array(
            'name' => strip_tags($n['name_all']),
            'size' => isset($n['size']) ? $n['size'] : '',
            'time' => isset($n['time']) ? $n['time'] : '',
            'share' => $share,
            'downUrl' => $one !== null ? $one['downUrl'] : ''
        );
    }
    if(empty($files)) {
        fail_json('資料夾內沒有可解析的檔案');
    }
    dbg('資料夾: 共 '.count($files).' 個檔案');
    return array(
        'folder' => isset($fname[1]) ? $fname[1] : '',
        'files' => $files
    );
}

//CURL函数
function MloocCurlGet($url = '', $UserAgent = '', $cookie = '', $referer = '') {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_COOKIE , $cookie);
    if ($UserAgent != "") {
        curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    }
    if ($referer != "") {
        curl_setopt($curl, CURLOPT_REFERER, $referer);
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.Rand_IP(), 'CLIENT-IP:'.Rand_IP()));
    #关闭SSL
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    #返回数据不直接显示
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($curl);
    dbg('GET '.$url.' -> HTTP '.curl_getinfo($curl, CURLINFO_HTTP_CODE).', '.strlen((string)$response).' bytes');
    curl_close($curl);
    return $response;
}
//POST函数
function MloocCurlPost($post_data = '', $url = '', $ifurl = '', $UserAgent = '',$cookie = '') {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_COOKIE , $cookie);
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    if ($ifurl != '') {
        curl_setopt($curl, CURLOPT_REFERER, $ifurl);
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.Rand_IP(), 'CLIENT-IP:'.Rand_IP()));
    #关闭SSL
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    #返回数据不直接显示
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    $response = curl_exec($curl);
    dbg('POST '.$url.' -> HTTP '.curl_getinfo($curl, CURLINFO_HTTP_CODE).', '.strlen((string)$response).' bytes');
    curl_close($curl);
    return $response;
}
//直链解析函数
function MloocCurlHead($url,$guise,$UserAgent,$cookie) {
    $headers = array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
        'Accept-Encoding: gzip, deflate',
        'Accept-Language: zh-CN,zh;q=0.9',
        'Cache-Control: no-cache',
        'Connection: keep-alive',
        'Pragma: no-cache',
        'Upgrade-Insecure-Requests: 1',
        'User-Agent: '.$UserAgent
    );
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER,$headers);
    curl_setopt($curl, CURLOPT_REFERER, $guise);
    curl_setopt($curl, CURLOPT_COOKIE , $cookie);
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    curl_setopt($curl, CURLOPT_NOBODY, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLINFO_HEADER_OUT, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    //超时设置，默认为10秒
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    $data = curl_exec($curl);
    $urlinfo = curl_getinfo($curl);
    dbg('HEAD '.$url.' -> redirect: '.$urlinfo["redirect_url"]);
    curl_close($curl);
    return $urlinfo["redirect_url"];
}
//随机IP函数
function Rand_IP() {
    $ip2id = round(rand(600000, 2550000) / 10000);
    $ip3id = round(rand(600000, 2550000) / 10000);
    $ip4id = round(rand(600000, 2550000) / 10000);
    $arr_1 = array("218","218","66","66","218","218","60","60","202","204","66","66","66","59","61","60","222","221","66","59","60","60","66","218","218","62","63","64","66","66","122","211");
    $randarr= mt_rand(0,count($arr_1)-1);
    $ip1id = $arr_1[$randarr];
    return $ip1id.".".$ip2id.".".$ip3id.".".$ip4id;
}
//cookie生成函数(现在好像又不验证了，怕忘记就先留着吧，介意的可以删除这个函数)
function acw_sc_v2_simple($arg1) {
    $posList = [15,35,29,24,33,16,1,38,10,9,19,31,40,27,22,23,25,13,6,11,39,18,20,8,14,21,32,26,2,30,7,4,17,5,3,28,34,37,12,36];
    $mask = '3000176000856006061501533003690027800375';
    $outPutList = array_fill(0, 40, '');
    for ($i = 0; $i < strlen($arg1); $i++) {
        $char = $arg1[$i];
        foreach ($posList as $j => $pos) {
            if ($pos == $i + 1) {
                $outPutList[$j] = $char;
            }
        }
    }
    $arg2 = implode('', $outPutList);
    $result = '';
    $length = min(strlen($arg2), strlen($mask));
    for ($i = 0; $i < $length; $i += 2) {
        $strHex = substr($arg2, $i, 2);
        $maskHex = substr($mask, $i, 2);
        $xorResult = dechex(hexdec($strHex) ^ hexdec($maskHex));
        $result .= str_pad($xorResult, 2, '0', STR_PAD_LEFT);
    }
    return $result;
}
?>
