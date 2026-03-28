<?php
require_once __DIR__ . '/../config/config.php';
$isVerified = isset($currentProfile['shopify_tag']) && $currentProfile['shopify_tag'] !== null;
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
		<?php if ($isVerified): ?>
			<a href="https://curaprox.us/discount/PRO10" target="_blank" class="inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors"><i data-lucide="external-link" class="w-4 h-4 stroke-[2px]"></i> Go to Curaprox Shop</a>
		<?php else: ?>
			<button id="verify-btn" class="inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"><i data-lucide="store" class="w-4 h-4 stroke-[2px]"></i> <span id="btn-text">Verify and Shop</span></button>
		<?php endif; ?>
	</div>
</section>

</div>
</main>
<?php include 'partials/footer.php'; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const verifyBtn = document.getElementById('verify-btn');
	const btnText = document.getElementById('btn-text');
	
	verifyBtn.addEventListener('click', async function() {
		// Disable button
		verifyBtn.disabled = true;
		btnText.textContent = 'Verifying...';
		
		try {
			const response = await fetch('/api/shopify_tag.php', {
				method: 'POST',
				headers: {'Content-Type': 'application/json'}
			});
			
			const result = await response.json();
			
			if (result.success) {
				btnText.textContent = 'Verified!';
				// Reload page to show the shop link
				setTimeout(() => {
					window.location.reload();
				}, 1000);
			} else {
				btnText.textContent = 'Verification Failed';
				alert('Failed to verify: ' + (result.error || 'Unknown error'));
				verifyBtn.disabled = false;
				btnText.textContent = 'Verify and Shop';
			}
		} catch (error) {
			console.error('Error verifying:', error);
			alert('Failed to verify. Please try again.');
			verifyBtn.disabled = false;
			btnText.textContent = 'Verify and Shop';
		}
	});
});
</script>
</body>
</html>
