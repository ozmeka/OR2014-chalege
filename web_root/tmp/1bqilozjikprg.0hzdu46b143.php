<html>
<head>
<title>Fill My List</title>
</head>
<body>
<?php foreach (($templates?:array()) as $template__): ?>
	<?php echo $this->render($template__,$this->mime,get_defined_vars()); ?>
<?php endforeach; ?>
</body>
</html>
