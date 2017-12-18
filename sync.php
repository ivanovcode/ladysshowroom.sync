<?php
$p = parse_ini_file('sync.ini', true);
$ladyshowroom = mysqli_connect($p['ladyshowroom']['host'], $p['ladyshowroom']['user'], $p['ladyshowroom']['password'], $p['ladyshowroom']['dbname']) or require('install.php'); 
$iampijama = mysqli_connect($p['iampijama']['host'], $p['iampijama']['user'], $p['iampijama']['password'], $p['iampijama']['dbname']) or require('install.php');
mysqli_query($ladyshowroom, "set names utf8");
mysqli_query($ladyshowroom, "SET sql_mode = ''");
mysqli_query($iampijama, "set names utf8");
mysqli_query($iampijama, "SET sql_mode = ''");

$rows = mysqli_query($iampijama, "
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
print_r(explode("||", $rows[1]['sizes']));



foreach($rows as $row){

}

mysqli_close($ladyshowroom);
mysqli_close($iampijama);
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
