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

<div class="prose prose-gray max-w-none 
			prose-h1:text-gray-400 prose-h1:text-4xl prose-h1:md:text-5xl prose-h1:xl:text-6xl prose-h1:mb-6 prose-h1:font-normal
			prose-p:text-gray-400 prose-p:md:text-lg prose-p:leading-relaxed prose-p:mb-2 prose-p:mt-0
			prose-li:text-gray-400 prose-li:md:text-lg prose-li:leading-relaxed prose-li:mb-1 prose-li:mt-0 prose-ul:mb-2 prose-ul:mt-0
			prose-headings:text-gray-400 prose-headings:font-bold prose-headings:mb-4
			prose-strong:text-gray-400
			marker:text-gray-400 dark:prose-invert
			prose-a:no-underline prose-a:hover:no-underline
			mb-8 max-w-4xl w-full lg:w-5/8">
	<h1>Professional Shop</h1>
	<p>This dedicated store is optimized exclusively for high-volume practice needs and bulk inventory. To ensure your clinic remains fully stocked, our professional pricing is applied primarily to case quantities—such as toothbrushes in "cubes" and toothpaste in bulk packs.</p>
	<p><strong>Pricing Note:</strong> For individual items, pricing on our Professional Shop is identical to our <a href="shop-consumer.php" class="text-orange hover:text-orange/80">Curaprox Shop</a> if you use the professional code <strong>PRO10</strong>.</p>
		<?php if ($isConfirmed): ?>
			<a href="https://curaden.us" target="_blank" class="mt-6 inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors"><i data-lucide="external-link" class="w-4 h-4 stroke-[2px]"></i> Go to Professional Shop</a>
		<?php elseif ($hasApplied): ?>
			<button disabled class="mt-6 inline-flex gap-2 items-center px-6 py-2 bg-gray-500 text-gray-400 font-medium rounded-full transition-colors hover:text-white"><i data-lucide="clock" class="w-4 h-4 stroke-[2px]"></i> Waiting for Approval</button>
		<?php else: ?>
			<a href="shop-professional-apply.php" class="mt-6 inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors"><i data-lucide="store" class="w-4 h-4 stroke-[2px]"></i> Create Account</a>
		<?php endif; ?>
</div>

</div>
</main>

<?php include 'partials/footer.php'; ?>
</div>
</body>
</html>
