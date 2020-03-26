<? HttpHeader::setHeader('Content-Type: text/html; charset=UTF-8'); ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">	
    <title><?= APP() -> config("app.name") . " " . (@ APP() -> head("title") ? ' - ' . APP() -> head("title") : '') ?></title>
  </head>
  <body>
  	<?php include($yield); ?>
  </body>
</html>