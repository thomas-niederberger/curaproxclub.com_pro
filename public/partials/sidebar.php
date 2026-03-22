<?php
$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT * FROM page WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
$stmt->execute();
$allPages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current user role
$userRole = $currentProfile['role'] ?? null;
$isAdmin = ($userRole === 'admin');

// Filter pages based on role
$filteredPages = [];
foreach ($allPages as $page) {
	// Admin sees everything
	if ($isAdmin) {
		$filteredPages[] = $page;
	} else {
		// Other users see pages without required_role or matching their role
		if (empty($page['required_role']) || $page['required_role'] === $userRole) {
			$filteredPages[] = $page;
		}
	}
}

// Get current page URL
$currentPage = basename($_SERVER['PHP_SELF']);

// Organize pages by parent_id
$parentPages = [];
$childPages = [];
foreach ($filteredPages as $page) {
	if ($page['parent_id'] === null) {
		$parentPages[] = $page;
	} else {
		if (!isset($childPages[$page['parent_id']])) {
			$childPages[$page['parent_id']] = [];
		}
		$childPages[$page['parent_id']][] = $page;
	}
}
?>
<aside id="drawer-navigation" class="fixed z-10 top-0 left-0 w-full md:w-64 h-screen pt-14 md:pt-4 transition-transform -translate-x-full md:translate-x-0 bg-gray-800 dark:bg-gray-800" aria-label="Sidenav">
	<div class="hidden md:flex items-center justify-center w-full px-4 mb-6">
		<a href="/" class="block w-full">
			<img src="/assets/img/curaprox.svg" class="w-full h-auto" alt="CURAPROX" />
		</a>
	</div>
	<div class="overflow-y-auto pt-12 md:pt-4 px-3 h-full">
		<ul class="space-y-2">
			<?php foreach ($parentPages as $parent): ?>
				<?php $hasChildren = isset($childPages[$parent['id']]) && !empty($childPages[$parent['id']]); ?>
				<?php $showSeparator = $parent['sort_order'] > 100; ?>
				<li <?= $showSeparator ? 'class="pt-2 mt-2 border-t border-gray-600"' : '' ?>>
					<?php if ($hasChildren): ?>
					<?php 
					$hasActiveChild = false;
					foreach ($childPages[$parent['id']] as $child) {
						if (basename($child['url']) === $currentPage) {
							$hasActiveChild = true;
							break;
						}
					}
					?>
					<button type="button" class="flex items-center p-2 w-full text-base font-medium text-gray-400 rounded-lg transition duration-75 group hover:bg-gray-100/10 dark:text-gray-400 dark:hover:bg-gray-400/10" aria-controls="dropdown-<?= $parent['id'] ?>" data-collapse-toggle="dropdown-<?= $parent['id'] ?>">
						<i data-lucide="<?= htmlspecialchars($parent['icon'] ?? 'circle') ?>" class="w-4 h-4 stroke-[2px]"></i>
						<span class="flex-1 ml-3 text-left whitespace-nowrap"><?= htmlspecialchars($parent['name']) ?></span>
						<i data-lucide="chevron-down" class="w-4 h-4 stroke-[2px]"></i>
					</button>
					<ul id="dropdown-<?= $parent['id'] ?>" class="<?= $hasActiveChild ? '' : 'hidden' ?> py-2 space-y-2">
						<?php foreach ($childPages[$parent['id']] as $child): ?>
						<?php $isActive = basename($child['url']) === $currentPage; ?>
						<li>
							<a href="<?= htmlspecialchars($child['url']) ?>" class="flex items-center p-2 pl-9 w-full text-base font-medium rounded-lg transition duration-75 group <?= $isActive ? 'bg-gray-400/10 text-orange' : 'text-gray-400 hover:bg-gray-400/10' ?> dark:text-gray-400 dark:hover:bg-gray-400/10"><?= htmlspecialchars($child['name']) ?></a>
						</li>
						<?php endforeach; ?>
					</ul>
				<?php else: ?>
					<?php $isActive = basename($parent['url']) === $currentPage; ?>
					<a href="<?= htmlspecialchars($parent['url']) ?>" class="flex items-center p-2 text-base font-medium rounded-lg group <?= $isActive ? 'bg-gray-400/10 text-orange' : 'text-gray-400 hover:bg-gray-100/10 dark:hover:bg-gray-400/10' ?> dark:text-gray-400">
						<i data-lucide="<?= htmlspecialchars($parent['icon'] ?? 'circle') ?>" class="w-4 h-4 stroke-[2px]"></i>
						<span class="ml-3"><?= htmlspecialchars($parent['name']) ?></span>
					</a>
				<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</aside>