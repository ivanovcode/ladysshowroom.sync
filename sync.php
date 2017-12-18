<?php
error_reporting(0);
function logPush($data){
	$fp = fopen('sync.log', 'a');
	fwrite($fp, $data . PHP_EOL);
	fclose($fp);
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
function connect($db, $p) {
	$connect = mysqli_connect($p[$db]['host'], $p[$db]['user'], $p[$db]['password'], $p[$db]['dbname']) or require('install.php'); 
	mysqli_query($connect, "set names utf8");
	mysqli_query($connect, "SET sql_mode = ''");
	return $connect;
}
function key_compare_func($a, $b)
{
    if ($a === $b) {
        return 0;
    }
    return ($a > $b)? 1:-1;
}
function pj($connect){
	$rows = mysqli_query($connect, "
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
		ORDER BY articles.`value` ASC
		LIMIT 500
	");

	return convert($rows);	
}
function ls($connect){
	$rows = mysqli_query($connect, "
		SELECT
		products.id,
		products.article,
		GROUP_CONCAT(CONCAT_WS('::', sizes.size_id, IF(sizes.amount IS NULL, 0, sizes.amount)) SEPARATOR '||') as sizes
		FROM
		varieties AS products
		LEFT JOIN `size_variety` AS sizes ON sizes.variety_id = products.id
		WHERE products.article IN('IAMP0034L','IAMP0035M','IAMP0041L','IAMP0036P','IAMP0042M','IAMP0043P','IAMP0011P','IAMP0012M','IAMP0010B','IAMP0014P','IAMP0009L','IAMP0001L','IAMP0004B','IAMP0003B','IAMP0002B','IAMP0005L','IAMP0006B','IAMP0007P','0008','IAMP0033G','IAMP0057G','IAMP0016M','IAMP0015L','IAMP0058L','IAMP0059B','IAMP0060G','IAMP0017P','IAMP0023G','IAMP0018L','IAMP0019M','IAMP0020P','IAMP0021P','IAMP0013E','IAMP0044B','IAMP0046B','IAMP0046T','IAMP0047P','IAMP0048L','IAMP0049E','IAMP0050M','IAMP0051M','IAMP0052T','IAMP0053L','IAMP0054E','IAMP0055P','IAMP0061B','IAMP0062R','IAMP0037T','IAMP0038P','IAMP0039E','IAMP0040B','IAMP0056B','IAMP0025B','IAMP0027E','IAMP0029P','IAMP0032M','0009','123','IAMP0022T','IAMP0024P','IAMP0026P','IAMP0028B','IAMP0030M','IAMP0031T','IAMP0045P')
		AND sizes.size_id IS NOT NULL
		GROUP BY products.article
		ORDER BY products.article ASC
	");

	return convert($rows);	
}


$config = parse_ini_file('sync.ini', true);
$ls = connect('ladyshowroom', $config);
$pj = connect('iampijama', $config);
$p = pj($pj);
$l = ls($ls);
$result = array_diff_uassoc($p, $l, "key_compare_func");


logPush('#pijams'); 
logPush(print_r($p, true)); 
logPush('#ladyshowroom'); 
logPush(print_r($l, true)); 
logPush('#result'); 
logPush(print_r($result, true)); 


mysqli_close($ls);
mysqli_close($pj);
die();



/*mysqli_query($db, "TRUNCATE `admin.ladyshowroom`.`clients`");
mysqli_query($db, "TRUNCATE `admin.ladyshowroom`.`orders`");
mysqli_query($db, "TRUNCATE `admin.ladyshowroom`.`order_size_variety`");*/
//mysqli_query($db, "TRUNCATE `admin.ladyshowroom`.`size_variety`");

/*mysqli_query($showcase_db, "TRUNCATE `ladyshowroom`.`clients`");
mysqli_query($showcase_db, "TRUNCATE `ladyshowroom`.`orders`");
mysqli_query($showcase_db, "TRUNCATE `ladyshowroom`.`order_size_variety`");*/
//mysqli_query($showcase_db, "TRUNCATE `ladyshowroom`.`products_sizes`");

$rows = [];
mysqli_query($db, "UPDATE varieties SET price = cost");
$rows = mysqli_query($db, "
	SELECT 
	product.id,
	categories.id AS category_id,
	product.color_id,
	product.group_id,
	product.name,
	product.title,
	product.cost,
	product.visible,
	product.article,
	product.meta_description,
	product.sortPos,
	groups.visible AS group_visible
	FROM 
	`admin.ladyshowroom`.`varieties` as product 
	LEFT JOIN groups ON groups.id = product.group_id
	LEFT JOIN categories ON categories.id = groups.category_id
	WHERE groups.id IS NOT NULL AND groups.deleted_at IS NULL
");

mysqli_query($showcase_db, "TRUNCATE TABLE `ladyshowroom`.`varieties`");
$x=0;
foreach($rows as $row){
	$x++;
	$result = mysqli_query($showcase_db, "
	        INSERT IGNORE INTO `ladyshowroom`.`varieties` (
	                `id`, 
        	        `name`, 
	                `article`, 
	                `title`, 
        	        `cat_id`, 
	                `color_id`, 
	                `group_id`, 
	                `sizes_type_id`, 
	                `visible`, 
	                `meta_description`, 
	                `price`, 
	                `price_disc`, 
	                `description`, 
	                `deleted_at`, 
	                `created_at`, 
	                `updated_at`, 
	                `sortPos`
	        ) VALUES (
	                ".$row['id'].", 
	                '".$row['name']."', 
	                '".$row['article']."', 
	                '".$row['title']."', 
	                ".$row['category_id'].", 
	                ".$row['color_id'].", 
	                ".$row['group_id'].", 
	                1, 
	                ".$row['group_visible'].", 
	                '".$row['meta_description']."', 
	                ".$row['cost'].", 
	                ".$row['cost'].", 
	                '', 
	                NULL, 
        	        NULL, 
	                NULL, 
                	".strval(intval($row['sortPos'])>0 ? $row['sortPos'] : "0")."
        	);
	");
	echo $result;
}
echo strval($x).'/'.strval(count($rows));

$rows = [];
$rows = mysqli_query($db, "
	SELECT 
		sizes.id,
		sizes.size_id,
		sizes.variety_id,
		groups.showroom_id,
		sizes.amount
	FROM `size_variety` AS sizes
	LEFT JOIN `varieties` as product ON sizes.variety_id = product.id
	LEFT JOIN groups ON groups.id = (
	    SELECT 
	    groups.id
	    FROM groups
	    WHERE groups.id = product.group_id 
	    AND groups.deleted_at IS NULL
	    LIMIT 1
	)
");
mysqli_query($showcase_db, "TRUNCATE TABLE `products_sizes`");
foreach($rows as $row){
	mysqli_query($showcase_db, "
		INSERT INTO `products_sizes` (
			`id`, 
			`size_id`, 
			`product_id`, 
			`showroom_id`, 
			`amount`
		) VALUES (
			NULL, 
			".$row['size_id'].", 
			".$row['variety_id'].", 
			".$row['showroom_id'].", 
			".$row['amount']."
		);
	");
}

mysqli_query($db, "DROP TABLE `ladyshowroom`.`photos`;");
mysqli_query($db, "CREATE TABLE `ladyshowroom`.`photos` SELECT * FROM `admin.ladyshowroom`.`photos`;");
mysqli_query($db, "UPDATE `ladyshowroom`.`photos` SET `photable_type` = 'product_main' WHERE `photable_type` LIKE '%Variety%';");

//mysqli_query($showcase_db, "DROP TABLE `products_sizes`");
//mysqli_query($db, "RENAME TABLE `admin.ladyshowroom`.`products_sizes` TO `ladyshowroom`.`products_sizes`");

?>
