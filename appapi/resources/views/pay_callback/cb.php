<!doctype html>
<html>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
<head><title></title></head>
<script type="text/javascript">
<?=$callback?>(<?=($is_success ? 'true' : 'false')?>, "<?=$message?>", <?=($openid ? "\"{$openid}\"" : 'null')?>, <?=($order_no ? "\"{$order_no}\"" : 'null')?>, <?=($trade_order_no ? "\"{$trade_order_no}\"" : 'null')?>);
</script>

<body>
<?=$message?>
</body>
</html>