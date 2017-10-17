<!DOCTYPE html>
<html>
<head>
	<?php echo $this->Html->charset() ?>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $this->fetch('title');?></title>
	<?php
	echo $this->Html->meta('icon');

	echo $this->fetch('meta');
	echo $this->fetch('css');
	echo $this->fetch('script');
	?>
</head>
<body>
<?php
echo $this->fetch('content');
?>
</body>
</html>
