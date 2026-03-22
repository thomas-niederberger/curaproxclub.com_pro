<?php
require_once __DIR__ . '/partials/config.php';

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT id, first_name, last_name, email, licence_number, licence_state, licence_verified, id_hubspot_b2b_contact, id_hubspot_b2c_contact, id_shopify_b2c FROM profile ORDER BY created_at DESC');
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
</section>

<div class="dark:bg-gray-700 bg-gray-700 rounded-lg">
  <div class="overflow-x-auto">
	<table class="min-w-full divide-y divide-gray-600">
		<thead class="bg-gray-600">
			<tr>
				<th scope="col" class="rounded-tl-lg p-4 text-start text-sm font-medium text-gray-400 uppercase">Name</th>
				<th scope="col" class="p-4 text-start text-sm font-medium text-gray-400 uppercase">Email</th>
				<th scope="col" class="p-4 text-start text-sm font-medium text-gray-400 uppercase">Licence Number</th>
				<th scope="col" class="p-4 text-start text-sm font-medium text-gray-400 uppercase">State</th>
				<th scope="col" class="p-4 text-center text-sm font-medium text-gray-400 uppercase">Verified</th>
				<th scope="col" class="rounded-tr-lg p-4 text-end text-sm font-medium text-gray-400 uppercase">Actions</th>
			</tr>
		</thead>
	  <tbody class="divide-y divide-gray-600">
		<?php foreach ($users as $user): ?>
		<tr>
			<td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-400"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
			<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-400"><?= htmlspecialchars($user['email']) ?></td>
			<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-400"><?= htmlspecialchars($user['licence_number'] ?? '-') ?></td>
			<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-400"><?= htmlspecialchars($user['licence_state'] ?? '-') ?></td>
			<td class="px-4 py-4 whitespace-nowrap text-center text-sm">
				<?php if ($user['licence_verified']): ?>
				<div class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-500">
					<i data-lucide="check" class="w-4 h-4 text-white stroke-[3px]"></i>
				</div>
				<?php else: ?>
				<button type="button" data-user-id="<?= $user['id'] ?>" class="licence-toggle group relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-gray-600 focus:ring-offset-2 bg-red-500">
					<span class="sr-only">Approve licence</span>
					<span class="pointer-events-none relative inline-block size-5 rounded-full bg-white shadow ring-0 transition-transform duration-200 ease-in-out translate-x-0">
						<span class="absolute inset-0 flex items-center justify-center transition-opacity duration-200">
							<i data-lucide="x" class="w-3 h-3 text-red-500"></i>
						</span>
					</span>
				</button>
				<?php endif; ?>
			</td>
			<td class="px-4 py-4 whitespace-nowrap text-end text-sm font-medium">
				<div class="flex justify-end gap-2">
					<?php if ($user['id_hubspot_b2b_contact']): ?>
					<a href="https://app-eu1.hubspot.com/contacts/27229630/record/0-1/<?= $user['id_hubspot_b2c_contact'] ?>" target="_blank" class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-lg bg-gray-500 text-gray-400 hover:bg-orange hover:text-white transition-colors">B2B</a>
					<?php endif; ?>
					<?php if ($user['id_hubspot_b2c_contact']): ?>
					<a href="https://app-eu1.hubspot.com/contacts/26560911/record/0-1/<?= $user['id_hubspot_b2b_contact'] ?>" target="_blank" class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-lg bg-gray-500 text-gray-400 hover:bg-orange hover:text-white transition-colors">B2C</a>
					<?php endif; ?>
					<?php if ($user['id_shopify_b2c']): ?>
					<a href="https://curaproxclub.myshopify.com/admin/customers/<?= $user['id_shopify_b2c'] ?>" target="_blank" class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-lg bg-gray-500 text-gray-400 hover:bg-orange hover:text-white transition-colors">Shopify</a>
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<?php endforeach; ?>
	  </tbody>
	</table>
  </div>
</div>

</div>
</main>
<?php include 'partials/footer.php'; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const toggles = document.querySelectorAll('.licence-toggle');
	
	toggles.forEach(toggle => {
		toggle.addEventListener('click', async function() {
			const userId = this.getAttribute('data-user-id');
			const knob = this.querySelector('span.pointer-events-none');
			const iconSpan = knob ? knob.querySelector('span') : null;
			const icon = iconSpan ? iconSpan.querySelector('i') : null;
			
			try {
				const response = await fetch('/api/licence_approve.php', {
					method: 'POST',
					headers: {'Content-Type': 'application/json'},
					body: JSON.stringify({ user_id: userId })
				});
				
				const result = await response.json();
				
				if (result.success) {
					// Animate to approved state
					this.classList.remove('bg-red-500');
					this.classList.add('bg-green-500');
					if (knob) knob.classList.add('translate-x-5');
					if (icon) {
						icon.setAttribute('data-lucide', 'check');
						icon.classList.remove('text-red-500');
						icon.classList.add('text-green-500');
					}
					
					// Re-render icons
					lucide.createIcons();
					
					// After animation, replace with static checkmark
					setTimeout(() => {
						const td = this.closest('td');
						td.innerHTML = '<div class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-500"><i data-lucide="check" class="w-4 h-4 text-white stroke-[3px]"></i></div>';
						lucide.createIcons();
					}, 300);
				} else {
					alert('Failed to approve licence: ' + (result.error || 'Unknown error'));
				}
			} catch (error) {
				console.error('Error approving licence:', error);
				alert('Failed to approve licence. Please try again.');
			}
		});
	});
});
</script>
</body>
</html>
