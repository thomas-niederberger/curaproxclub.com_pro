<?php
require_once __DIR__ . '/partials/config.php';

$pdo = getDbConnection();

// Fetch all pages
$stmt = $pdo->query('SELECT * FROM page ORDER BY sort_order ASC, id ASC');
$allPages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize pages hierarchically: parents first, then their children
$pages = [];
$childrenByParent = [];

// Group children by parent_id
foreach ($allPages as $page) {
	if ($page['parent_id']) {
		if (!isset($childrenByParent[$page['parent_id']])) {
			$childrenByParent[$page['parent_id']] = [];
		}
		$childrenByParent[$page['parent_id']][] = $page;
	}
}

// Build hierarchical list: parent followed by its children
foreach ($allPages as $page) {
	if (!$page['parent_id']) {
		// Add parent page
		$pages[] = $page;
		
		// Add its children immediately after
		if (isset($childrenByParent[$page['id']])) {
			foreach ($childrenByParent[$page['id']] as $child) {
				$pages[] = $child;
			}
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

<section class="w-full flex justify-between items-end gap-6">
	<div class="<?= $theme->getHeaderClasses() ?> flex-1">
		<h1><?= htmlspecialchars($pageHeader) ?></h1>
	</div>
	<div class="mb-8">
		<button type="button" class="<?= $theme->getButtonClasses('btn-add-page') ?>">
			<i data-lucide="plus" class="w-4 h-4 stroke-[2px]"></i> 
			<span class="hidden sm:inline">Add Page</span>
		</button>
	</div>
</section>

<div class="bg-gray-700 dark:bg-gray-700 rounded-lg overflow-hidden">
	<table class="w-full">
		<thead class="bg-gray-600">
			<tr>
				<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400" style="width: 40px;"></th>
				<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400"></th>
				<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">Name</th>
				<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">Header</th>
				<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">URL</th>
				<th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-400">Status</th>
				<th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-400">Actions</th>
			</tr>
		</thead>
		<tbody class="divide-y divide-gray-600" id="pageTableBody">
			<?php if (empty($pages)): ?>
			<tr>
				<td colspan="7" class="px-4 py-8 text-center text-gray-400">No pages found. Add your first page to get started.</td>
			</tr>
			<?php else: ?>
				<?php foreach ($pages as $page): ?>
				<tr class="hover:bg-gray-600 cursor-move page-row" 
					draggable="true" 
					data-id="<?= $page['id'] ?>"
					data-parent-id="<?= $page['parent_id'] ?? '' ?>">
					<td class="px-4 py-3 text-gray-400">
						<i data-lucide="grip-vertical" class="w-4 h-4 text-gray-400 drag-handle"></i>
					</td>
					<td class="py-3">
						<i data-lucide="<?= htmlspecialchars($page['icon'] ?? 'circle') ?>" class="w-4 h-4 text-orange"></i>
					</td>
					<td class="px-4 py-3 text-gray-400 font-medium">
						<div class="flex items-center gap-2">
							<?php if ($page['parent_id']): ?>
								<i data-lucide="corner-down-right" class="w-4 h-4 text-gray-400"></i>
							<?php endif; ?>
							<?= htmlspecialchars($page['name']) ?>
						</div>
					</td>
					<td class="px-4 py-3 text-gray-400 text-sm"><?= htmlspecialchars($page['header']) ?></td>
					<td class="px-4 py-3 text-gray-400 text-sm"><?= htmlspecialchars($page['url']) ?></td>
					<td class="px-4 py-3 text-center">
						<?php if ($page['is_active']): ?>
							<span class="px-2 py-1 text-xs bg-green-500/20 text-green-400 rounded-full">Active</span>
						<?php else: ?>
							<span class="px-2 py-1 text-xs bg-red-500/20 text-red-400 rounded-full">Inactive</span>
						<?php endif; ?>
					</td>
					<td class="px-4 py-3 text-right">
						<div class="flex items-center justify-end gap-2">
							<button type="button" class="cursor-pointer p-2 hover:bg-gray-500 rounded-lg btn-edit-page" 
								data-id="<?= $page['id'] ?>"
								data-name="<?= htmlspecialchars($page['name']) ?>"
								data-header="<?= htmlspecialchars($page['header']) ?>"
								data-description-short="<?= htmlspecialchars($page['description_short'] ?? '') ?>"
								data-description="<?= htmlspecialchars($page['description'] ?? '') ?>"
								data-icon="<?= htmlspecialchars($page['icon'] ?? '') ?>"
								data-is-active="<?= $page['is_active'] ? '1' : '0' ?>">
								<i data-lucide="pencil" class="w-4 h-4 text-gray-400"></i>
							</button>
							<button type="button" class="cursor-pointer p-2 hover:bg-gray-500 rounded-lg btn-delete-page" 
								data-id="<?= $page['id'] ?>"
								data-name="<?= htmlspecialchars($page['name']) ?>">
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

<!-- Loading Overlay -->
<div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-70 z-50 flex items-center justify-center">
	<div class="bg-gray-700 rounded-lg p-6 flex flex-col items-center gap-4">
		<div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange"></div>
		<p class="text-gray-400 font-medium">Reordering pages...</p>
	</div>
</div>

<!-- Edit/Add Page Modal -->
<div id="editPageModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
	<div class="bg-gray-500 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
		<div class="flex items-center justify-between mb-4">
			<h3 id="editPageModalLabel" class="text-xl font-bold text-gray-400">Edit Page</h3>
			<button type="button" class="p-2 hover:bg-gray-700 rounded-lg modal-close">
				<i data-lucide="x" class="w-5 h-5 text-gray-400"></i>
			</button>
		</div>
		<form id="editPageForm" class="space-y-4">
			<input type="hidden" id="pageId">
			
			<div>
				<label for="pageName" class="block text-sm font-medium text-gray-400 mb-2">Name (Navigation) *</label>
				<input type="text" id="pageName" required class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none">
			</div>
			
			<div>
				<label for="pageHeader" class="block text-sm font-medium text-gray-400 mb-2">Header *</label>
				<input type="text" id="pageHeader" required class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none">
			</div>
			
			<div>
				<label for="pageDescriptionShort" class="block text-sm font-medium text-gray-400 mb-2">Short Description (255 Characters max)</label>
				<textarea id="pageDescriptionShort" maxlength="255" class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none"></textarea>
			</div>
			
			<div>
				<label for="pageDescription" class="block text-sm font-medium text-gray-400 mb-2">
					Description (Markdown)
					<a href="https://markdownlivepreview.com/" target="_blank" class="text-orange hover:underline ml-2">
						<i data-lucide="external-link" class="w-3 h-3 inline"></i> Markdown Guide
					</a>
				</label>
				<textarea id="pageDescription" rows="6" class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none font-mono text-sm"></textarea>
			</div>
			
			<div>
				<label for="pageIcon" class="block text-sm font-medium text-gray-400 mb-2">
					Icon
					<a href="https://lucide.dev/icons/" target="_blank" class="text-orange hover:underline ml-2">
						<i data-lucide="external-link" class="w-3 h-3 inline"></i> Browse Icons
					</a>
				</label>
				<input type="text" id="pageIcon" placeholder="e.g., home, user, settings" class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none">
			</div>
			
			<div>
				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" id="pageIsActive" class="w-4 h-4 rounded border-gray-600 bg-gray-600 text-orange focus:ring-2 focus:ring-orange">
					<span class="text-sm font-medium text-gray-400">Active (visible in navigation)</span>
				</label>
			</div>
		</form>
		<div class="flex gap-2 mt-6">
			<button type="button" class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-500 text-gray-400 font-medium rounded-lg transition-colors modal-close">Cancel</button>
			<button type="button" id="savePageBtn" class="flex-1 px-4 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-lg transition-colors">Save Changes</button>
		</div>
	</div>
</div>

</div>
</main>

<?php include 'partials/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	const editModal = document.getElementById('editPageModal');
	const modalTitle = document.getElementById('editPageModalLabel');
	const savePageBtn = document.getElementById('savePageBtn');
	
	// Modal open/close functionality
	function openModal(button) {
		const pageId = button?.getAttribute('data-id') || '';
		const isEditMode = !!pageId;
		
		document.getElementById('pageId').value = pageId;
		document.getElementById('pageName').value = button?.getAttribute('data-name') || '';
		document.getElementById('pageHeader').value = button?.getAttribute('data-header') || '';
		document.getElementById('pageDescriptionShort').value = button?.getAttribute('data-description-short') || '';
		document.getElementById('pageDescription').value = button?.getAttribute('data-description') || '';
		document.getElementById('pageIcon').value = button?.getAttribute('data-icon') || '';
		document.getElementById('pageIsActive').checked = button?.getAttribute('data-is-active') === '1';
		
		modalTitle.textContent = isEditMode ? 'Edit Page' : 'Add Page';
		savePageBtn.textContent = isEditMode ? 'Save Changes' : 'Add Page';
		
		editModal.classList.remove('hidden');
		lucide.createIcons();
	}
	
	function closeModal() {
		editModal.classList.add('hidden');
	}
	
	// Open modal on edit/add button click
	document.querySelectorAll('.btn-edit-page, .btn-add-page').forEach(btn => {
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
	savePageBtn.addEventListener('click', function() {
		const pageId = document.getElementById('pageId').value;
		const name = document.getElementById('pageName').value.trim();
		const header = document.getElementById('pageHeader').value.trim();
		const descriptionShort = document.getElementById('pageDescriptionShort').value.trim();
		const description = document.getElementById('pageDescription').value.trim();
		const icon = document.getElementById('pageIcon').value.trim();
		const isActive = document.getElementById('pageIsActive').checked ? 1 : 0;
		const isEditMode = !!pageId;

		if (!name) {
			alert('Please enter a page name.');
			return;
		}
		
		if (!header) {
			alert('Please enter a page header.');
			return;
		}

		const endpoint = isEditMode ? 'api/page_update.php' : 'api/page_add.php';
		const payload = {
			name: name,
			header: header,
			description_short: descriptionShort,
			description: description,
			icon: icon,
			is_active: isActive
		};
		
		if (isEditMode) {
			payload.id = pageId;
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
				alert((isEditMode ? 'Error updating page: ' : 'Error adding page: ') + (data.error || 'Unknown error'));
			}
		})
		.catch(error => {
			alert('Error: ' + error.message);
		});
	});
	
	// Delete page
	document.querySelectorAll('.btn-delete-page').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.stopPropagation();
			const pageName = this.getAttribute('data-name');
			if (!confirm(`Are you sure you want to delete the page "${pageName}"?`)) return;
			
			const pageId = this.getAttribute('data-id');
			
			fetch('api/page_delete.php', {
				method: 'POST',
				headers: {'Content-Type': 'application/json'},
				body: JSON.stringify({ id: pageId })
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					location.reload();
				} else {
					alert('Error deleting page: ' + (data.error || 'Unknown error'));
				}
			})
			.catch(error => {
				alert('Error: ' + error.message);
			});
		});
	});
	
	// Drag and Drop functionality
	let draggedRow = null;
	const loadingOverlay = document.getElementById('loadingOverlay');
	
	const pageRows = document.querySelectorAll('.page-row');
	
	pageRows.forEach(row => {
		row.addEventListener('dragstart', function(e) {
			draggedRow = this;
			this.style.opacity = '0.5';
			e.dataTransfer.effectAllowed = 'move';
			e.dataTransfer.setData('text/html', this.innerHTML);
		});
		
		row.addEventListener('dragend', function(e) {
			this.style.opacity = '1';
			
			// Remove all drag-over classes
			pageRows.forEach(r => {
				r.classList.remove('border-t-2', 'border-orange');
			});
		});
		
		row.addEventListener('dragover', function(e) {
			if (e.preventDefault) {
				e.preventDefault();
			}
			e.dataTransfer.dropEffect = 'move';
			
			// Remove previous highlights
			pageRows.forEach(r => {
				r.classList.remove('border-t-2', 'border-orange');
			});
			
			// Add highlight to current row
			if (draggedRow !== this) {
				this.classList.add('border-t-2', 'border-orange');
			}
			
			return false;
		});
		
		row.addEventListener('dragleave', function(e) {
			this.classList.remove('border-t-2', 'border-orange');
		});
		
		row.addEventListener('drop', function(e) {
			if (e.stopPropagation) {
				e.stopPropagation();
			}
			
			if (draggedRow !== this) {
				const draggedId = draggedRow.getAttribute('data-id');
				const targetId = this.getAttribute('data-id');
				
				// Show loading overlay
				loadingOverlay.classList.remove('hidden');
				
				// Send reorder request
				fetch('api/page_reorder_drag.php', {
					method: 'POST',
					headers: {'Content-Type': 'application/json'},
					body: JSON.stringify({
						dragged_id: draggedId,
						target_id: targetId
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						location.reload();
					} else {
						loadingOverlay.classList.add('hidden');
						alert('Error reordering pages: ' + (data.error || 'Unknown error'));
					}
				})
				.catch(error => {
					loadingOverlay.classList.add('hidden');
					alert('Error: ' + error.message);
				});
			}
			
			return false;
		});
	});
	
	lucide.createIcons();
});
</script>
</div>
</body>
</html>
