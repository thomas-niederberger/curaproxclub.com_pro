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

<section class="antialiased">
	<div class="mx-auto grid max-w-screen-xl lg:grid-cols-12 lg:gap-12 lg:pb-16 xl:gap-0">
	  <div class="justify-self-start md:col-span-7 md:text-start">
		<h1 class="mb-4 text-4xl text-gray-400 dark:text-gray-400 md:max-w-2xl md:text-5xl xl:text-6xl">Verify your Dental Professional status</h1>
		<p class="mb-3 text-gray-400 dark:text-gray-400 md:mb-12 md:text-lg lg:mb-3 lg:text-xl">To enjoy a <strong>permanent 10% discount</strong>, please verify your status as a licensed dental practitioner. To maintain the integrity of our professional pricing, we reserve the right to revoke professional status and terminate accounts at our sole discretion if our criteria are no longer met.</p>
		<p class="mb-6 text-gray-400 dark:text-gray-400 md:mb-12 md:text-lg lg:mb-6 lg:text-xl">Verified dental practitioners can use code <strong>PRO10</strong> at any time to receive <strong>10% off</strong> our entire range. These rates match our professional shop pricing, though additional savings are available for bulk inventory purchases.</p>
		<?php if ($isVerified): ?>
			<a href="https://curaprox.us/discount/PRO10" target="_blank" class="inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors"><i data-lucide="external-link" class="w-4 h-4 stroke-[2px]"></i> Go to Curaprox Shop</a>
		<?php else: ?>
			<button id="verify-btn" class="inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"><i data-lucide="store" class="w-4 h-4 stroke-[2px]"></i> <span id="btn-text">Verify and Shop</span></button>
		<?php endif; ?>
	  </div>
	  <div class="hidden lg:col-span-5 lg:flex">
		<img src="/assets/img/illustration-dentist-1.png" class="object-contain" alt="Dentist illustration" />
	  </div>
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
