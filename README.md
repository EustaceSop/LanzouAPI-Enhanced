# 藍奏雲直鏈解析

單檔 PHP 藍奏雲(蓝奏云)直鏈解析工具，內建網頁操作介面。支援**單檔分享**、**密碼分享**、**資料夾分享**，解析到真正的 CDN 位址(`webgetstore.com`)，不是中轉跳轉頁。

基於 [hanximeng/LanzouAPI](https://github.com/hanximeng/LanzouAPI) 修改，修正中轉頁問題並加入資料夾支援與除錯模式。

## 功能

- 單檔分享連結解析（桌面版 ajaxm 流程 + 手機版 downurl 流程雙重 fallback)
- 密碼保護的分享連結
- **資料夾分享**：自動列出資料夾內所有檔案並逐一解析直鏈
- 解析到**最終 CDN 直鏈**（自動跟隨中轉跳轉頁的 302，非 `developer2.lanrar.com` 跳轉頁）
- 網頁操作介面：輸入連結即解析，附下載 / 複製按鈕
- 除錯模式：顯示每一步 HTTP 請求與提取結果，方便排查
- API 模式：回傳 JSON 或 302 直接跳轉下載

## 展示
https://github.com/EustaceSop/LanzouAPI-Enhanced/blob/main/img.png?raw=true

## 部署

需要 PHP + cURL 擴展。把 `index.php` 丟到任何 PHP 網站目錄即可，無其他依賴。

本機測試：

```bash
php -S 127.0.0.1:8000
```

## 網頁介面

直接開啟 `index.php`，輸入分享連結與密碼（如有），點「解析」。

- 單檔：顯示檔名、大小、直鏈，可下載或複製
- 資料夾：顯示檔案清單，每個檔案各自有下載 / 複製按鈕
- 勾選「顯示除錯資訊」可查看完整解析過程

## API 使用

```
GET index.php?url=<分享連結>&pwd=<密碼>&type=<模式>&debug=1
```

| 參數 | 必填 | 說明 |
|---|---|---|
| `url` | 是 | 藍奏雲分享連結（任何 lanzou 系列網域） |
| `pwd` | 否 | 分享密碼 |
| `type` | 否 | `down` = 302 直接跳轉下載（僅單檔） |
| `debug` | 否 | `1` = 回應中附帶解析步驟 log |
| `n` | 否 | 自訂下載檔名 |

### 單檔回應

```json
{
    "code": 200,
    "msg": "解析成功",
    "name": "example.zip",
    "filesize": "37.6 M",
    "downUrl": "https://zip1.webgetstore.com/..."
}
```

### 資料夾回應

```json
{
    "code": 200,
    "msg": "解析成功(資料夾)",
    "folder": "資料夾名稱",
    "files": [
        {
            "name": "example.exe",
            "size": "13.3 M",
            "time": "12-28",
            "share": "https://www.lanzouf.com/ixxxxxxx",
            "downUrl": "https://exe1.webgetstore.com/..."
        }
    ]
}
```

### 錯誤回應

```json
{
    "code": 400,
    "msg": "錯誤訊息",
    "debug": ["GET https://... -> HTTP 200, 6786 bytes", "..."]
}
```

## 原理

1. **單檔**：桌面版頁面取 iframe → POST `ajaxm.php` 取中轉位址；失敗則切手機版頁面，由 `downurl` 進入 `/tp/` 頁提取 `vkjxld` + `hyggid` + `lanosso` 拼接中轉位址
2. **資料夾**：POST `filemoreajax.php` 取檔案列表，逐檔走單檔流程
3. **中轉 → 真實 CDN**：對中轉位址發帶桌面 UA、`down_ip=1` cookie、完整 Accept headers 的請求，取得 302 到 `*.webgetstore.com` 的最終直鏈

## 注意事項

- 直鏈有時效（`sg`/`e` 簽名參數），解析後請盡快下載，失效就重新解析
- 資料夾列表目前只取第一頁（50 個檔案）
- 藍奏頁面結構變動時解析可能失效，用除錯模式定位問題

## 致謝

- [hanximeng/LanzouAPI](https://github.com/hanximeng/LanzouAPI) — 原始 PHP 版本
- [xingmihai/Lanzou_straight_chain_analysis](https://github.com/xingmihai/Lanzou_straight_chain_analysis) — 手機版解析流程參考
