<?php
require_once __DIR__ . '/partials/config.php';

$pdo = getDbConnection();
$stmt = $pdo->query('SELECT * FROM ohc_location ORDER BY state ASC, city ASC');
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
		<h1><?= htmlspecialchars($pageHeader) ?>, <?= htmlspecialchars($currentProfile['first_name'] ?? '') ?>.</h1>
	</div>
	<button type="button" class="inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors btn-add-location">
		<i data-lucide="plus" class="w-4 h-4 stroke-[2px]"></i> Add Location
	</button>
</section>

<div class="bg-gray-700 dark:bg-gray-700 rounded-lg overflow-hidden">
	<table class="w-full">
		<thead class="bg-gray-600">
			<tr>
				<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">City</th>
				<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">State</th>
				<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">Type</th>
				<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">Status</th>
				<th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-400">Actions</th>
			</tr>
		</thead>
		<tbody class="divide-y divide-gray-600">
			<?php if (empty($locations)): ?>
			<tr>
				<td colspan="5" class="px-4 py-8 text-center text-gray-400">No locations found. Add your first location to get started.</td>
			</tr>
			<?php else: ?>
				<?php foreach ($locations as $location): ?>
				<tr class="hover:bg-gray-600">
					<td class="px-4 py-3 text-gray-400"><?= htmlspecialchars($location['city']) ?></td>
					<td class="px-4 py-3 text-gray-400"><?= htmlspecialchars($location['state']) ?></td>
					<td class="px-4 py-3">
						<?php if ($location['is_virtual']): ?>
							<span class="px-2 py-1 text-xs bg-blue-500/20 text-blue-400 rounded-full">Virtual</span>
						<?php else: ?>
							<span class="px-2 py-1 text-xs bg-purple-500/20 text-purple-400 rounded-full">In-Person</span>
						<?php endif; ?>
					</td>
					<td class="px-4 py-3">
						<?php if ($location['is_active']): ?>
							<span class="px-2 py-1 text-xs bg-green-500/20 text-green-400 rounded-full">Active</span>
						<?php else: ?>
							<span class="px-2 py-1 text-xs bg-gray-500/20 text-gray-400 rounded-full">Inactive</span>
						<?php endif; ?>
					</td>
					<td class="px-4 py-3 text-right">
						<div class="flex items-center justify-end gap-2">
							<button type="button" class="p-2 hover:bg-gray-500 rounded-lg btn-edit-location" 
								data-id="<?= $location['id'] ?>"
								data-city="<?= htmlspecialchars($location['city']) ?>"
								data-state="<?= htmlspecialchars($location['state']) ?>"
								data-virtual="<?= $location['is_virtual'] ? '1' : '0' ?>"
								data-active="<?= $location['is_active'] ? '1' : '0' ?>">
								<i data-lucide="pencil" class="w-4 h-4 text-gray-400"></i>
							</button>
							<button type="button" class="p-2 hover:bg-gray-500 rounded-lg btn-delete-location" data-id="<?= $location['id'] ?>">
								<i data-lucide="trash-2" class="w-4 h-4 text-gray-400"></i>
							</button>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<!-- Edit/Add Location Modal -->
<div id="editLocationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
	<div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
		<div class="flex items-center justify-between mb-4">
			<h3 id="editLocationModalLabel" class="text-xl font-bold text-gray-400">Edit Location</h3>
			<button type="button" class="p-2 hover:bg-gray-700 rounded-lg modal-close">
				<i data-lucide="x" class="w-5 h-5 text-gray-400"></i>
			</button>
		</div>
		<form id="editLocationForm" class="space-y-4">
			<input type="hidden" id="locationId">
			<div>
				<label for="locationCity" class="block text-sm font-medium text-gray-400 mb-2">City</label>
				<input type="text" id="locationCity" class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none" placeholder="Los Angeles">
			</div>
			<div>
				<label for="locationState" class="block text-sm font-medium text-gray-400 mb-2">State (2-letter code)</label>
				<input type="text" id="locationState" maxlength="2" class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none uppercase" placeholder="CA">
			</div>
			<div class="flex items-center gap-2">
				<input type="checkbox" id="locationVirtual" class="w-4 h-4 text-orange bg-gray-600 border-gray-600 rounded focus:ring-orange">
				<label for="locationVirtual" class="text-sm text-gray-400">Virtual Session</label>
			</div>
			<div class="flex items-center gap-2">
				<input type="checkbox" id="locationActive" class="w-4 h-4 text-orange bg-gray-600 border-gray-600 rounded focus:ring-orange">
				<label for="locationActive" class="text-sm text-gray-400">Active</label>
			</div>
		</form>
		<div class="flex gap-2 mt-6">
			<button type="button" class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-500 text-gray-400 font-medium rounded-lg transition-colors modal-close">Cancel</button>
			<button type="button" id="saveLocationBtn" class="flex-1 px-4 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-lg transition-colors">Save Changes</button>
		</div>
	</div>
</div>

</div>
</main>

<?php include 'partials/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	const editModal = document.getElementById('editLocationModal');
	const modalTitle = document.getElementById('editLocationModalLabel');
	const saveLocationBtn = document.getElementById('saveLocationBtn');
	
	// Modal open/close functionality
	function openModal(button) {
		const locationId = button?.getAttribute('data-id') || '';
		const city = button?.getAttribute('data-city') || '';
		const state = button?.getAttribute('data-state') || '';
		const isVirtual = button?.getAttribute('data-virtual') === '1';
		const isActive = button?.getAttribute('data-active') === '1';
		const isEditMode = !!locationId;
		
		document.getElementById('locationId').value = locationId;
		document.getElementById('locationCity').value = city;
		document.getElementById('locationState').value = state;
		document.getElementById('locationVirtual').checked = isVirtual;
		document.getElementById('locationActive').checked = isEditMode ? isActive : true;
		modalTitle.textContent = isEditMode ? 'Edit Location' : 'Add Location';
		saveLocationBtn.textContent = isEditMode ? 'Save Changes' : 'Add Location';
		
		editModal.classList.remove('hidden');
	}
	
	function closeModal() {
		editModal.classList.add('hidden');
	}
	
	// Open modal on edit/add button click
	document.querySelectorAll('.btn-edit-location, .btn-add-location').forEach(btn => {
		btn.addEventListener('click', function() {
			openModal(this);
		});
	});
	
	// Close modal buttons
	document.querySelectorAll('.modal-close').forEach(btn => {
		btn.addEventListener('click', closeModal);
	});
	
	// Close modal on backdrop click
	editModal.addEventListener('click', function(e) {
		if (e.target === editModal) {
			closeModal();
		}
	});
	
	// Auto-uppercase state input
	document.getElementById('locationState').addEventListener('input', function(e) {
		this.value = this.value.toUpperCase();
	});
	
	// Save changes
	saveLocationBtn.addEventListener('click', function() {
		const locationId = document.getElementById('locationId').value;
		const city = document.getElementById('locationCity').value.trim();
		const state = document.getElementById('locationState').value.trim().toUpperCase();
		const isVirtual = document.getElementById('locationVirtual').checked;
		const isActive = document.getElementById('locationActive').checked;
		const isEditMode = !!locationId;

		if (!city) {
			alert('City is required.');
			return;
		}
		
		if (!state || state.length !== 2) {
			alert('State must be a 2-letter code (e.g., CA).');
			return;
		}

		const endpoint = isEditMode ? 'api/ohc_location_update.php' : 'api/ohc_location_add.php';
		const payload = {
			city: city,
			state: state,
			is_virtual: isVirtual ? 1 : 0,
			is_active: isActive ? 1 : 0
		};
		if (isEditMode) {
			payload.id = locationId;
		}
		
		fetch(endpoint, {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify(payload)
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				location.reload();
			} else {
				alert((isEditMode ? 'Error updating location: ' : 'Error adding location: ') + (data.error || 'Unknown error'));
			}
		})
		.catch(error => {
			alert('Error: ' + error.message);
		});
	});
	
	// Delete location
	document.querySelectorAll('.btn-delete-location').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.stopPropagation();
			if (!confirm('Are you sure you want to delete this location? This will also remove all associated bookings.')) return;
			
			const locationId = this.getAttribute('data-id');
			
			fetch('api/ohc_location_delete.php', {
				method: 'POST',
				headers: {'Content-Type': 'application/json'},
				body: JSON.stringify({ id: locationId })
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					location.reload();
				} else {
					alert('Error deleting location: ' + (data.error || 'Unknown error'));
				}
			})
			.catch(error => {
				alert('Error: ' + error.message);
			});
		});
	});
	
	lucide.createIcons();
});
</script>
</div>
</body>
</html>
