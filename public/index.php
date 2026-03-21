<?php
require_once __DIR__ . '/partials/config.php';
require_once __DIR__ . '/api/functions.php';

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT * FROM page WHERE is_dashboard = 1 AND url != \'index.php\' ORDER BY sort_order ASC, id ASC');
$stmt->execute();
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

<section>
	<div class="space-y-8 md:grid md:grid-cols-2 lg:grid-cols-3 md:gap-8 md:space-y-0">
		<?php foreach ($pages as $page): ?>
		<div class="bg-gray-700 dark:bg-gray-700 p-4 rounded-lg">
			<div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-gray-500 lg:h-12 lg:w-12 dark:bg-gray-800">
				<i data-lucide="<?= htmlspecialchars($page['icon'] ?? 'circle') ?>" class="text-orange w-6 h-6 stroke-[2px]"></i>
			</div>
			<h3 class="mb-2 text-xl text-gray-400 dark:text-gray-400"><?= htmlspecialchars($page['name']) ?></h3>
			<p class="mb-4 text-gray-400 dark:text-gray-400"><?= htmlspecialchars($page['description'] ?? '') ?></p>
			<?php if ($page['is_active']): ?>
			<a href="<?= htmlspecialchars($page['url']) ?>" class="inline-flex items-center px-6 py-2 bg-gray-500 hover:bg-orange text-gray-400 font-medium rounded-full transition-colors hover:text-white">Learn More</a>
			<?php else: ?>
			<span class="inline-flex items-center px-6 py-2 bg-gray-300 text-gray-400 font-medium rounded-full">Coming Soon</span>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
	</div>
</section>

</div>
</main>

<?php include 'partials/footer.php'; ?>
</div>
</body>
</html>
