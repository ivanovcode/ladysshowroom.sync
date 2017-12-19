<?php
error_reporting(0);
function GetArticlesPijamaStr($db) { 
	$result = mysqli_query($db, "
		SELECT 
		CONCAT(\"'\",GROUP_CONCAT(articles.`value` SEPARATOR \"','\"),\"'\") as articles
		FROM 
		`modx_site_content` AS products
		LEFT JOIN `modx_site_tmplvar_contentvalues` AS articles ON articles.`contentid` = products.id AND articles.tmplvarid = 15
		WHERE `parent` IN (11,12,13,14)
		AND products.id NOT IN(91,36,93,100)
		ORDER BY articles.`value` ASC
		LIMIT 500
	");
	$articles = mysqli_fetch_array($result)[0];
	if (empty($articles)) die('articles are empty');
	return $articles;
}
function GetSizesPijama($db, $article=''){ 
	$rows = mysqli_query($db, "
		SELECT 
		products.id,
		articles.`value` AS article,
		sizes.`value` AS sizes	
		FROM 
		`modx_site_content` AS products
		LEFT JOIN `modx_site_tmplvar_contentvalues` AS sizes ON sizes.`contentid` = products.id AND sizes.tmplvarid = 27
		LEFT JOIN `modx_site_tmplvar_contentvalues` AS articles ON articles.`contentid` = products.id AND articles.tmplvarid = 15
		WHERE `parent` IN (11,12,13,14)
		AND products.id NOT IN(91,36,93,100)
		".strval(!empty($article) ? "AND articles.`value` IN(".$article.")" : "")."			
		ORDER BY articles.`value` ASC
		LIMIT 500
	");
	if(!$rows) logio('PJ: no records', 'error', true);
	return $rows;	
}
function GetSizesLadysTmp($db, $article=''){ 
	$rows = mysqli_query($db, "
		SELECT 
		`iampijama.sync`.id,
		`iampijama.sync`.article,
		`iampijama.sync`.sizes	
		FROM 
		`iampijama.sync`
		WHERE `iampijama.sync`.id > 0
		".strval(!empty($article) ? "AND `iampijama.sync`.article IN(".$article.")" : "")."
	");
	if(!$rows) logio('LSTMP: no records', 'error');
	return $rows;	
}
function GetSizesLadys($db, $articles=''){ 
	$rows = mysqli_query($db, "
		SELECT
		products.id,
		products.article,
		GROUP_CONCAT(CONCAT_WS('::', sizes.`name`, IF(variety.amount IS NULL, 0, variety.amount)) SEPARATOR \"||\") as sizes		
		FROM
		varieties AS products
		LEFT JOIN `size_variety` AS variety ON variety.variety_id = products.id
		LEFT JOIN `sizes` AS sizes ON sizes.id = variety.size_id
		WHERE products.id > 0 
		".strval(!empty($articles) ? "AND products.article IN(".$articles.")" : "")."	
		AND variety.size_id IS NOT NULL
		GROUP BY products.article
		ORDER BY products.article ASC
	");
	if(!$rows) logio('LS: no records', 'error', true);
	return $rows;	
}
function CloseAllDb(){ 
	mysqli_close($db_iampijama);
	mysqli_close($db_ladyshowroom);
}
function compare($a, $b, $c){
	$items = $c;
	foreach($a as $article => $sizes){
		foreach($sizes as $size => $amount){
			if ($amount == $b[$article][$size]) continue;
			if ($amount > $b[$article][$size]) $items[$article][$size] = intval($items[$article][$size])!=0 ? (intval($items[$article][$size]) - ($amount - $b[$article][$size])) : 0;
			if ($amount < $b[$article][$size]) logio('Недопустимое значение в базе PJ количество товара Арт.: '.$article.' больше чем в базе LS. Кто-то редактирует количество напрямую в PJ', 'error');
			if(empty($b[$article][$size])) {
				logio('Отсутствует размер '.$size.' товара Арт.: '.$article.' в базе PJ. Кто-то редактирует товары удаляет размеры напрямую в PJ. ', 'error');
			}
		}
		if(!isset($b[$article])) logio('Отсутствует товар Арт.: '.$article.' в базе PJ. Кто-то редактирует товары удаляет их напрямую в PJ', 'error');
	}
	return $items;
}
function pushSizesLadysTmp($db, $a) { 
	if ($a) {		
		mysqli_query($db, "TRUNCATE `iampijama.sync`");
		$rows = [];
		foreach($a as $article => $sizes){
			mysqli_query($db, "INSERT IGNORE INTO `admin.ladyshowroom`.`iampijama.sync` (`id`, `article`, `sizes`) VALUES (NULL, '".$article."', '".deconvert($sizes)."');");	
		}
	}
}
function pushSizesLadys($db, $a) {
	if ($a) {		
		$rows = [];
		foreach($a as $article => $sizes){
			foreach($sizes as $name => $amount){
				if(!empty($name) && strval($name)!="0") {
					mysqli_query($db, "UPDATE `size_variety` INNER JOIN `sizes` ON `size_variety`.`size_id` = `sizes`.`id` INNER JOIN `varieties` ON `size_variety`.`variety_id` = `varieties`.`id` SET `size_variety`.`amount` = '".$amount."' WHERE `sizes`.`name` = '".$name."' AND `varieties`.`article` = '".$article."'");
				}
			}
			
		}
	}
}
function pushSizesPijama($db, $a) { 
	if ($a) {		
		$rows = [];
		foreach($a as $article => $sizes){
			$result = mysqli_query($db, "
				SELECT 
				products.id
				FROM 
				`modx_site_content` AS products
				LEFT JOIN `modx_site_tmplvar_contentvalues` AS articles ON articles.`contentid` = products.id AND articles.tmplvarid = 15
				WHERE articles.`value` = '".$article."'
			");
			$id = mysqli_fetch_array($result)[0];
			if(intval($id)) {
				mysqli_query($db, "UPDATE `modx_site_tmplvar_contentvalues` SET `value` = '".deconvert($sizes)."' WHERE `modx_site_tmplvar_contentvalues`.`tmplvarid` = 27 AND `modx_site_tmplvar_contentvalues`.`contentid` = ".$id);
			}			
	
		}
	}
}
function key_compare_func($a, $b) {
    if ($a === $b) {
        return 0;
    }  
	return ($a > $b)? 1:-1;
}
function convert($rows) { 
	if ($rows) {
		$arr = [];
		foreach($rows as $row){
			$arr[$row['article']] = [];
			$sizes = explode("||", $row['sizes']);
			foreach($sizes as $size){
				$size = explode("::", $size);
				$arr[$row['article']][$size[0]] = $size[1];
			}
			unset($sizes);
		}
		return $arr;
	} else {
		return 0;
	}
}
function deconvert($items) { 
	$row = implode('||', array_map(
		function ($v, $k) { return sprintf("%s::%s", $k, $v); },
		$items,
		array_keys($items)
	));
	return $row;
}
function connect($db, $p) { 
	$connect = mysqli_connect($p[$db]['host'], $p[$db]['user'], $p[$db]['password'], $p[$db]['dbname']) or logio('no connection to the database', 'error');
	mysqli_query($connect, "set names utf8");
	mysqli_query($connect, "SET sql_mode = ''");
	return $connect;
}
function logio($data, $name, $die=false, $clear=false, $msg=''){ 
	if ($clear) unlink($name.'.log');
	$fp = fopen($name.'.log', 'a');
	fwrite($fp, date("d.m.y").' '.date("H:i:s").' | '.$data . PHP_EOL);
	fclose($fp);
	if ($die) die($msg);
}
function sync($debug=false){ 
	$config = parse_ini_file('sync.ini', true); 
	$db_iampijama =  connect('iampijama', $config); 
	$db_ladyshowroom =  connect('ladyshowroom', $config); 
	if($debug) { $articles = "'IAMP0060G'"; } else { $articles = GetArticlesPijamaStr($db_iampijama); }	
	$result = compare(convert(GetSizesLadysTmp($db_ladyshowroom, $articles)), convert(GetSizesPijama($db_iampijama, $articles)), convert(GetSizesLadys($db_ladyshowroom, $articles))); 
	pushSizesLadysTmp($db_ladyshowroom, $result);
	pushSizesLadys($db_ladyshowroom, $result);
	pushSizesPijama($db_iampijama, $result);
	CloseAllDb();
}
sync(true);
?>