<?php
$p = parse_ini_file('sync.ini', true);
$db = mysqli_connect($p['db']['host'], $p['db']['user'], $p['db']['password'], $p['db']['dbname']) or require('install.php'); 
$showcase_db = mysqli_connect($p['showcase']['host'], $p['showcase']['user'], $p['showcase']['password'], $p['showcase']['dbname']) or require('install.php');
mysqli_query($db, "set names utf8");
mysqli_query($db, "SET sql_mode = ''");
mysqli_query($showcase_db, "set names utf8");
mysqli_query($showcase_db, "SET sql_mode = ''");
$rows = mysqli_query($db, "
	SELECT 
	product.id,
	categories.id AS category_id,
	product.color_id,
	product.group_id,
	product.name,
	product.title,
	product.cost
	FROM 
	`varieties` as product 
	LEFT JOIN groups ON groups.id = product.group_id
	LEFT JOIN categories ON categories.id = groups.category_id
	WHERE product.visible = 1 
	AND product.group_id = 1468
");

mysqli_query($showcase_db, "TRUNCATE TABLE `varieties`");
$rows = [];
foreach($rows as $row){
	mysqli_query($showcase_db, "
	        INSERT INTO `varieties` (
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
	                NULL, 
	                '".$row['name']."', 
	                '".$row['title']."', 
	                '".$row['title']."', 
	                ".$row['category_id'].", 
	                ".$row['color_id'].", 
	                ".$row['group_id'].", 
	                1, 
	                1, 
	                'Test_product_meta_description', 
	                ".$row['cost'].", 
	                0, 
	                'Test_product_description', 
	                NULL, 
        	        NULL, 
	                NULL, 
                	1
        	);
	");
}

mysqli_query($showcase_db, "TRUNCATE TABLE `products_sizes`");
$rows = [];
$rows = mysqli_query($db, "
	SELECT 
		sizes.id,
		sizes.size_id,
		sizes.variety_id,
		sizes.amount
	FROM `size_variety` AS sizes
");
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
			".$row['product_id'].", 
			5, 
			".$row['amount']."
		);
	");
}

//mysqli_query($showcase_db, "DROP TABLE `products_sizes`");
//mysqli_query($db, "RENAME TABLE `admin.ladyshowroom`.`products_sizes` TO `ladyshowroom`.`products_sizes`");
mysqli_close($db);
mysqli_close($showcase_db);
?>
