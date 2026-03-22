<?php
require_once __DIR__ . '/partials/config.php';

$pdo = getDbConnection();

// Fetch all profiles
$stmt = $pdo->query('SELECT id, first_name, last_name, email FROM profile ORDER BY last_name ASC, first_name ASC');
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all locations
$stmt = $pdo->query('SELECT id, city, state, is_virtual FROM ohc_location WHERE is_active = 1 ORDER BY state ASC, city ASC');
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all assignments
$stmt = $pdo->query('
	SELECT op.*, p.first_name, p.last_name, p.email, l.city, l.state, l.is_virtual
	FROM ohc_profile op
	JOIN profile p ON op.profile_id = p.id
	JOIN ohc_location l ON op.location_id = l.id
	ORDER BY p.last_name ASC, p.first_name ASC
');
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
	<button type="button" class="inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors btn-add-assignment">
		<i data-lucide="plus" class="w-4 h-4 stroke-[2px]"></i> Add Assignment
	</button>
</section>

<div class="bg-gray-700 dark:bg-gray-700 rounded-lg overflow-hidden">
	<table class="w-full">
		<thead class="bg-gray-600">
			<tr>
				<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">Profile</th>
				<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">Email</th>
				<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">Location</th>
				<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">Type</th>
				<th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-400">Actions</th>
			</tr>
		</thead>
		<tbody class="divide-y divide-gray-600">
			<?php if (empty($assignments)): ?>
			<tr>
				<td colspan="5" class="px-4 py-8 text-center text-gray-400">No assignments found. Add your first assignment to get started.</td>
			</tr>
			<?php else: ?>
				<?php foreach ($assignments as $assignment): ?>
				<tr class="hover:bg-gray-600">
					<td class="px-4 py-3 text-gray-400">
						<?= htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']) ?>
					</td>
					<td class="px-4 py-3 text-gray-400 text-sm"><?= htmlspecialchars($assignment['email']) ?></td>
					<td class="px-4 py-3 text-gray-400">
						<?= htmlspecialchars($assignment['city']) ?>, <?= htmlspecialchars($assignment['state']) ?>
					</td>
					<td class="px-4 py-3">
						<?php if ($assignment['is_virtual']): ?>
							<span class="px-2 py-1 text-xs bg-blue-500/20 text-blue-400 rounded-full">Virtual</span>
						<?php else: ?>
							<span class="px-2 py-1 text-xs bg-purple-500/20 text-purple-400 rounded-full">In-Person</span>
						<?php endif; ?>
					</td>
					<td class="px-4 py-3 text-right">
						<div class="flex items-center justify-end gap-2">
							<button type="button" class="p-2 hover:bg-gray-500 rounded-lg btn-edit-assignment" 
								data-profile-id="<?= $assignment['profile_id'] ?>"
								data-location-id="<?= $assignment['location_id'] ?>"
								data-profile-name="<?= htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']) ?>">
								<i data-lucide="pencil" class="w-4 h-4 text-gray-400"></i>
							</button>
							<button type="button" class="p-2 hover:bg-gray-500 rounded-lg btn-delete-assignment" 
								data-profile-id="<?= $assignment['profile_id'] ?>"
								data-location-id="<?= $assignment['location_id'] ?>">
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

<!-- Edit/Add Assignment Modal -->
<div id="editAssignmentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
	<div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
		<div class="flex items-center justify-between mb-4">
			<h3 id="editAssignmentModalLabel" class="text-xl font-bold text-gray-400">Edit Assignment</h3>
			<button type="button" class="p-2 hover:bg-gray-700 rounded-lg modal-close">
				<i data-lucide="x" class="w-5 h-5 text-gray-400"></i>
			</button>
		</div>
		<form id="editAssignmentForm" class="space-y-4">
			<input type="hidden" id="originalProfileId">
			<input type="hidden" id="originalLocationId">
			<div>
				<label for="assignmentProfile" class="block text-sm font-medium text-gray-400 mb-2">Profile</label>
				<select id="assignmentProfile" class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none">
					<option value="">Select a profile...</option>
					<?php foreach ($profiles as $profile): ?>
					<option value="<?= $profile['id'] ?>">
						<?= htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) ?> (<?= htmlspecialchars($profile['email']) ?>)
					</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label for="assignmentLocation" class="block text-sm font-medium text-gray-400 mb-2">Location</label>
				<select id="assignmentLocation" class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none">
					<option value="">Select a location...</option>
					<?php foreach ($locations as $location): ?>
					<option value="<?= $location['id'] ?>">
						<?= htmlspecialchars($location['city']) ?>, <?= htmlspecialchars($location['state']) ?>
						<?= $location['is_virtual'] ? '(Virtual)' : '(In-Person)' ?>
					</option>
					<?php endforeach; ?>
				</select>
			</div>
		</form>
		<div class="flex gap-2 mt-6">
			<button type="button" class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-500 text-gray-400 font-medium rounded-lg transition-colors modal-close">Cancel</button>
			<button type="button" id="saveAssignmentBtn" class="flex-1 px-4 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-lg transition-colors">Save Changes</button>
		</div>
	</div>
</div>

</div>
</main>

<?php include 'partials/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	const editModal = document.getElementById('editAssignmentModal');
	const modalTitle = document.getElementById('editAssignmentModalLabel');
	const saveAssignmentBtn = document.getElementById('saveAssignmentBtn');
	
	// Modal open/close functionality
	function openModal(button) {
		const profileId = button?.getAttribute('data-profile-id') || '';
		const locationId = button?.getAttribute('data-location-id') || '';
		const isEditMode = !!profileId && !!locationId;
		
		document.getElementById('originalProfileId').value = profileId;
		document.getElementById('originalLocationId').value = locationId;
		document.getElementById('assignmentProfile').value = profileId;
		document.getElementById('assignmentLocation').value = locationId;
		
		// Disable profile selection in edit mode
		document.getElementById('assignmentProfile').disabled = isEditMode;
		
		modalTitle.textContent = isEditMode ? 'Edit Assignment' : 'Add Assignment';
		saveAssignmentBtn.textContent = isEditMode ? 'Save Changes' : 'Add Assignment';
		
		editModal.classList.remove('hidden');
	}
	
	function closeModal() {
		editModal.classList.add('hidden');
		document.getElementById('assignmentProfile').disabled = false;
	}
	
	// Open modal on edit/add button click
	document.querySelectorAll('.btn-edit-assignment, .btn-add-assignment').forEach(btn => {
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
	
	// Save changes
	saveAssignmentBtn.addEventListener('click', function() {
		const originalProfileId = document.getElementById('originalProfileId').value;
		const originalLocationId = document.getElementById('originalLocationId').value;
		const profileId = document.getElementById('assignmentProfile').value;
		const locationId = document.getElementById('assignmentLocation').value;
		const isEditMode = !!originalProfileId && !!originalLocationId;

		if (!profileId) {
			alert('Please select a profile.');
			return;
		}
		
		if (!locationId) {
			alert('Please select a location.');
			return;
		}

		const endpoint = isEditMode ? 'api/ohc_assignment_update.php' : 'api/ohc_assignment_add.php';
		const payload = {
			profile_id: profileId,
			location_id: locationId
		};
		if (isEditMode) {
			payload.original_profile_id = originalProfileId;
			payload.original_location_id = originalLocationId;
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
				alert((isEditMode ? 'Error updating assignment: ' : 'Error adding assignment: ') + (data.error || 'Unknown error'));
			}
		})
		.catch(error => {
			alert('Error: ' + error.message);
		});
	});
	
	// Delete assignment
	document.querySelectorAll('.btn-delete-assignment').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.stopPropagation();
			if (!confirm('Are you sure you want to delete this assignment?')) return;
			
			const profileId = this.getAttribute('data-profile-id');
			const locationId = this.getAttribute('data-location-id');
			
			fetch('api/ohc_assignment_delete.php', {
				method: 'POST',
				headers: {'Content-Type': 'application/json'},
				body: JSON.stringify({
					profile_id: profileId,
					location_id: locationId
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					location.reload();
				} else {
					alert('Error deleting assignment: ' + (data.error || 'Unknown error'));
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
