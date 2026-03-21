<?php
require_once __DIR__ . '/partials/config.php';

// Check if user has shopify_tag timestamp
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

<div class="prose prose-gray max-w-none 
			prose-h1:text-gray-400 prose-h1:text-4xl prose-h1:md:text-5xl prose-h1:xl:text-6xl prose-h1:mb-6 prose-h1:font-normal
			prose-p:text-gray-400 prose-p:md:text-lg prose-p:leading-relaxed prose-p:mb-2 prose-p:mt-0
			prose-li:text-gray-400 prose-li:md:text-lg prose-li:leading-relaxed prose-li:mb-1 prose-li:mt-0 prose-ul:mb-2 prose-ul:mt-0
			prose-headings:text-gray-400 prose-headings:font-bold prose-headings:mb-4
			prose-strong:text-gray-400
			marker:text-gray-400 dark:prose-invert
			prose-a:no-underline prose-a:hover:no-underline
			mb-8 max-w-4xl w-full lg:w-5/8">
	<h1>Dental Professional Status Verification</h1>
	<p>To enjoy a permanent <strong>10% discount</strong>, please verify your status as a licensed dental professional.</p>
	<p>To maintain the integrity of our professional pricing, we reserve the right to revoke professional status and deactivate accounts if eligibility criteria are no longer met.</p>
	<p>Once verified, dental professionals can use code <strong>PRO10</strong> at any time to receive <strong>10% off</strong> our entire product range. These savings align with our professional pricing, with additional discounts available for bulk purchases.</p>
	<?php if ($isVerified): ?>
		<a href="https://curaprox.us/discount/PRO10" target="_blank" class="mt-6 inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors"><i data-lucide="external-link" class="w-4 h-4 stroke-[2px]"></i> Go to Curaprox Shop</a>
	<?php else: ?>
		<button id="verify-btn" class="mt-6 inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"><i data-lucide="store" class="w-4 h-4 stroke-[2px]"></i> <span id="btn-text">Verify and Shop</span></button>
	<?php endif; ?>
</div>

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
