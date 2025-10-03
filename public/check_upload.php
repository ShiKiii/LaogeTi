<?php
header("Content-Type: text/plain; charset=utf-8");

echo "===== PHP 配置 =====\n";
echo "upload_max_filesize = " . ini_get("upload_max_filesize") . "\n";
echo "post_max_size       = " . ini_get("post_max_size") . "\n";
echo "max_execution_time  = " . ini_get("max_execution_time") . "\n";
echo "max_input_time      = " . ini_get("max_input_time") . "\n";
echo "memory_limit        = " . ini_get("memory_limit") . "\n";

echo "\n===== 服务器环境 =====\n";
if (function_exists('apache_get_modules')) {
    echo "运行在 Apache，可能受 LimitRequestBody 限制\n";
} else {
    echo "运行在 Nginx 或 PHP-FPM\n";
    echo "⚠️ 请检查 nginx.conf 里的 client_max_body_size 配置\n";
}

echo "\n===== FastAdmin 配置 =====\n";
$configFile = __DIR__ . "/application/extra/upload.php";
if (file_exists($configFile)) {
    $config = include $configFile;
    echo "FastAdmin 上传最大限制 maxsize = " . ($config['maxsize'] ?? '未设置') . "\n";
} else {
    echo "未找到 application/extra/upload.php 文件\n";
}
