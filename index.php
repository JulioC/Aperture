<?php
require 'Aperture.class.php';
?>

<html>
<head>
<title>Aperture Reader</title>
</head>
<body>
<?php
$act = strtoupper($_GET['act']);
if(empty($act))
	$act = 'APERTURE';

if($act == 'EXIT')
	echo 'Aplication finished. Click <a href="?">here</a> to restart';
else
{
	$ap = new Aperture('DATA/'.$act);

	$ap->parse();
	$ap->draw();

	//if(!file_exists('IMG/'.$act.'.PNG'))
		$ap->savePNG('IMG/'.$act.'.PNG');
	echo '<img src="IMG/'.$act.'.PNG" />';

	$menu = $ap->getMenu();
	echo '<p>Menu</p><ul>';
	foreach($menu as $item)
		echo '<li><a href="?act='.$item['action'].'">'.$item['label'].'</a></li>';
}
?>
</body>
</html>
