<?php
require_once __DIR__ . '/partials/config.php';
?>

<!DOCTYPE html>
<html class="h-full">
<?php include 'partials/meta.php'; ?>
<body class="antialiased bg-gray-50 dark:bg-gray-900 h-full">
<div class="max-w-[1600px] h-full bg-gray-200 dark:bg-gray-900 border-r border-gray-600 dark:border-gray-600">
<?php include 'partials/header.php'; ?>
<?php include 'partials/sidebar.php'; ?>
<main class="md:ml-64 h-auto pt-20">
<div class="p-8 border-t border-gray-600 dark:border-gray-600">

<section class="max-w-4xl w-full lg:w-6/8">
	<div class="<?= $theme->getHeaderClasses() ?>">
		<h1><?= htmlspecialchars($pageHeader) ?></h1>
	</div>
	<div class="<?= $theme->getContentClasses() ?>">
		<?= $pageDescription ?>
	</div>
</section>
	
</div>
</main>
<?php include 'partials/footer.php'; ?>
</div>
</body>
</html>
