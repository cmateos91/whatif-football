<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatIF Football</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/public/css/app.css">
</head>
<body class="bg-light">

    {include file="_navbar.tpl"}

    <div class="container">
        {include file=$content_template}
    </div>

    <script src="/public/vendor/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/public/js/app.js"></script>
    {if isset($extra_js)}<script src="{$extra_js}"></script>{/if}
</body>
</html>
