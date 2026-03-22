<?php
require_once __DIR__ . '/partials/config.php';
$hasApplied = isset($currentProfile['professionalshop_tag']) && $currentProfile['professionalshop_tag'] !== null;
$isConfirmed = isset($currentProfile['professionalshop_confirmed']) && $currentProfile['professionalshop_confirmed'] !== null;
$hubspotData = null;
$erpId = null;

// Check HubSpot data if applied but not yet confirmed
if ($hasApplied && !$isConfirmed && !empty($currentProfile['id_hubspot_b2b_contact'])) {
	$hubspotData = getHubSpotB2BData($currentProfile['id_hubspot_b2b_contact']);
	
	if ($hubspotData && isset($hubspotData['contact']['id_erp']) && !empty($hubspotData['contact']['id_erp'])) {
		$erpId = $hubspotData['contact']['id_erp'];
		
		// Update professionalshop_confirmed timestamp
		try {
			$pdo = getDbConnection();
			$stmt = $pdo->prepare('UPDATE profile SET professionalshop_confirmed = NOW() WHERE id = ?');
			$stmt->execute([$currentProfile['id']]);
			$isConfirmed = true;
		} catch (Exception $e) {
			error_log('Failed to update professionalshop_confirmed: ' . $e->getMessage());
		}
	}
}
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

<section class="max-w-4xl w-full lg:w-5/8">
	<div class="<?= $theme->getHeaderClasses() ?>">
		<h1><?= htmlspecialchars($pageHeader) ?></h1>
	</div>
	<div class="<?= $theme->getContentClasses() ?>">
		<?= $pageDescription ?>
	</div>
	<div>
	<?php if ($isConfirmed): ?>
			<a href="https://curaden.us" target="_blank" class="inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors"><i data-lucide="external-link" class="w-4 h-4 stroke-[2px]"></i> Go to Professional Shop</a>
		<?php elseif ($hasApplied): ?>
			<button disabled class="inline-flex gap-2 items-center px-6 py-2 bg-gray-500 text-gray-400 font-medium rounded-full transition-colors hover:text-white"><i data-lucide="clock" class="w-4 h-4 stroke-[2px]"></i> Waiting for Approval</button>
		<?php else: ?>
			<a href="shop-professional-apply.php" class="inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors"><i data-lucide="store" class="w-4 h-4 stroke-[2px]"></i> Create Account</a>
		<?php endif; ?>
	</div>
</section>

</div>
</main>
<?php include 'partials/footer.php'; ?>
</div>

</body>
</html>
