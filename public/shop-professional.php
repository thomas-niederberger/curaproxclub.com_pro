<?php
require_once __DIR__ . '/partials/config.php';
require_once __DIR__ . '/api/functions.php';

// Check professional shop status
$hasApplied = isset($currentProfile['professionalshop_tag']) && $currentProfile['professionalshop_tag'] !== null;
$isConfirmed = isset($currentProfile['professionalshop_confirmed']) && $currentProfile['professionalshop_confirmed'] !== null;
$hubspotData = null;
$erpId = null;

// Only check HubSpot data if applied but not yet confirmed
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

<section class="antialiased">
	<div class="mx-auto grid max-w-screen-xl lg:grid-cols-12 lg:gap-12 lg:pb-16 xl:gap-0">
	  <div class="justify-self-start md:col-span-7 md:text-start">
		<h1 class="mb-4 text-4xl text-gray-400 dark:text-gray-400 md:max-w-2xl md:text-5xl xl:text-6xl">Professional Shop</h1>
		<p class="mb-3 text-gray-400 dark:text-gray-400 md:mb-12 md:text-lg lg:mb-3 lg:text-xl">This dedicated store is optimized exclusively for high-volume practice needs and bulk inventory. To ensure your clinic remains fully stocked, our professional pricing is applied primarily to case quantities—such as toothbrushes in "cubes" and toothpaste in bulk packs.</p>
		<p class="mb-6 text-gray-400 dark:text-gray-400 md:mb-12 md:text-lg lg:mb-6 lg:text-xl"><strong>Pricing Note:</strong> For individual items, pricing on our Professional Shop is identical to our <a href="shop-consumer.php" class="text-orange hover:underline">Curaprox Shop</a> if you use the professional code PRO10.</p>
		<?php if ($isConfirmed): ?>
			<a href="https://curaden.us" target="_blank" class="inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors"><i data-lucide="external-link" class="w-4 h-4 stroke-[2px]"></i> Go to Professional Shop</a>
		<?php elseif ($hasApplied): ?>
			<button disabled class="inline-flex gap-2 items-center px-6 py-2 bg-gray-500 text-gray-400 font-medium rounded-full transition-colors hover:text-white"><i data-lucide="clock" class="w-4 h-4 stroke-[2px]"></i> Waiting for Approval</button>
		<?php else: ?>
			<a href="shop-professional-apply.php" class="inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors"><i data-lucide="store" class="w-4 h-4 stroke-[2px]"></i> Create Account</a>
		<?php endif; ?>
	  </div>
	  <div class="hidden lg:col-span-5 lg:flex">
		<img src="/assets/img/illustration-dentist-2.png" class="object-contain" alt="Dentist illustration" />
	  </div>
	</div>
</section>

</div>
</main>

<?php include 'partials/footer.php'; ?>
</div>
</body>
</html>
