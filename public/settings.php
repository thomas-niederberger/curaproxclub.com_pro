<?php
require_once __DIR__ . '/partials/config.php';
require_once __DIR__ . '/api/functions.php';

$pdo = getDbConnection();
$message = '';
$error = '';

// Handle theme update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_theme'])) {
    $theme = in_array($_POST['theme'], ['light', 'dark']) ? $_POST['theme'] : 'light';
    $stmt = $pdo->prepare('UPDATE profile SET theme = ? WHERE id = ?');
    $stmt->execute([$theme, $currentProfileId]);
    header('Location: settings.php?msg=theme');
    exit;
}

// Handle Cal.com update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['cal_url']) || isset($input['cal_token']) || isset($input['cal_webhook'])) {
        $stmt = $pdo->prepare('UPDATE profile SET cal_url = ?, cal_token = ?, cal_webhook = ? WHERE id = ?');
        $stmt->execute([
            $input['cal_url'] ?? '',
            $input['cal_token'] ?? '',
            $input['cal_webhook'] ?? '',
            $currentProfileId
        ]);
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

if (isset($_GET['msg'])) {
    $messages = [
        'theme' => 'Theme updated successfully!',
        'calcom' => 'Cal.com settings updated successfully!'
    ];
    $message = $messages[$_GET['msg']] ?? '';
}

// Fetch HubSpot B2B data if available
$hubspotData = null;
if (!empty($currentProfile['id_hubspot_b2b_contact'])) {
    $hubspotData = getHubSpotB2BData($currentProfile['id_hubspot_b2b_contact']);
}

// Use database profile data for name and email
$firstName = htmlspecialchars($currentProfile['first_name'] ?? '');
$lastName = htmlspecialchars($currentProfile['last_name'] ?? '');
$email = htmlspecialchars($currentProfile['email'] ?? '');

// Get additional fields from HubSpot if available
$jobTitle = '';
$phone = '';

if ($hubspotData && !empty($hubspotData['contact'])) {
    $jobTitle = htmlspecialchars($hubspotData['contact']['jobtitle'] ?? '');
    $phone = htmlspecialchars($hubspotData['contact']['phone'] ?? '');
}

$avatar = $currentProfile['avatar'] ?? '';
$avatarUrl = $avatar ? 'uploads/avatars/' . htmlspecialchars($avatar) : '';
$initials = $currentUserInitials;
$licenceNumber = $currentProfile['licence_number'];
$licenceVerified = $currentProfile['licence_verified'];
$theme = $currentProfile['theme'] ?? 'light';
$calUrl = htmlspecialchars($currentProfile['cal_url'] ?? '');
$calToken = htmlspecialchars($currentProfile['cal_token'] ?? '');
$calWebhook = htmlspecialchars($currentProfile['cal_webhook'] ?? '');
?>
<!DOCTYPE html>
<html class="h-full dark">
<?php include 'partials/meta.php'; ?>
<body class="antialiased bg-gray-50 dark:bg-gray-900 h-full">
<div class="max-w-[1600px] h-full bg-gray-200 dark:bg-gray-900 border-r border-gray-600 dark:border-gray-600">
<?php include 'partials/header.php'; ?>
<?php include 'partials/sidebar.php'; ?>

<main class="md:ml-64 h-auto pt-20">
	<div class="p-8 border-t border-gray-600 dark:border-gray-600 grid grid-cols-1 lg:grid-cols-3 gap-6">

		<!-- Profile Card -->
		<div class="lg:col-span-1">
			<div class="dark:bg-gray-700 bg-gray-700 rounded-lg p-6">

				<div class="flex items-center gap-4 mb-6">
					<div class="w-20 h-20 rounded-full overflow-hidden flex-shrink-0">
						<?php if ($avatar): ?>
						<img src="<?= $avatarUrl ?>" alt="<?= $firstName ?> <?= $lastName ?>" class="w-full h-full object-cover">
						<?php else: ?>
						<div class="w-full h-full bg-gray-600 flex items-center justify-center text-gray-400 text-xl font-bold">
							<?= $initials ?>
						</div>
						<?php endif; ?>
					</div>
					<div class="flex-1 min-w-0">
						<h2 class="text-lg font-bold text-gray-400 truncate"><?= $firstName ?> <?= $lastName ?></h2>
						<?php if ($jobTitle): ?>
						<p class="text-gray-400 text-base"><?= $jobTitle ?></p>
						<?php endif; ?>
					</div>
				</div>

				<div class="space-y-4">
					<div>
						<label class="block text-xs font-medium uppercase text-gray-400 mb-1">Email</label>
						<p class="text-gray-400"><?= $email ?></p>
					</div>
					<div>
						<label class="block text-xs font-medium uppercase text-gray-400 mb-1">Phone</label>
						<p class="text-gray-400"><?= $phone ?></p>
					</div>
					<div>
						<label class="block text-xs font-medium uppercase text-gray-400 mb-1">Licence Number</label>
						<div class="flex items-center gap-2">
							<p class="text-gray-400"><?= $licenceNumber ?></p>
							<?php if ($licenceVerified): ?>
							<div class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-500">
								<i data-lucide="check" class="w-4 h-4 text-white stroke-[3px]"></i>
							</div>
							<?php else: ?>
							<span class="inline-flex px-2 py-1 bg-red-500 text-white text-xs font-medium rounded-full">Not Verified</span>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Settings -->
		<div class="lg:col-span-2 space-y-6">
			<!-- Theme Settings -->
			<div class="dark:bg-gray-700 bg-gray-700 rounded-lg p-6">
				<h3 class="text-xl font-bold text-gray-400 mb-6">Theme Settings</h3>
				<?php if ($message && $_GET['msg'] === 'theme'): ?>
				<div class="mb-4 p-3 bg-green-500/20 border border-green-500/50 rounded-lg">
					<p class="text-green-400 text-sm"><?= htmlspecialchars($message) ?></p>
				</div>
				<?php endif; ?>
				<form method="POST">
					<input type="hidden" name="update_theme" value="1">
					<div class="mb-6">
						<label class="block text-sm font-medium text-gray-400 mb-2">Theme</label>
						<select name="theme" class="w-full px-4 py-2 bg-gray-500 dark:bg-gray-500 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange focus:border-transparent text-gray-400 ">
							<option value="light" <?= $theme === 'light' ? 'selected' : '' ?>>Light</option>
							<option value="dark" <?= $theme === 'dark' ? 'selected' : '' ?>>Dark</option>
						</select>
					</div>
					<button type="submit" class="cursor-pointer inline-flex gap-2 items-center px-6 py-2 bg-gray-500 hover:bg-orange text-gray-400 font-medium rounded-full transition-colors hover:text-white">
						<i data-lucide="save" class="w-4 h-4 stroke-[2px]"></i> Save Changes
					</button>
				</form>
			</div>

			<!-- Cal.com Settings -->
			<div class="dark:bg-gray-700 bg-gray-700 rounded-lg p-6">
				<h3 class="text-xl font-bold text-gray-400 mb-6">Cal.com Integration</h3>
				<?php if ($message && $_GET['msg'] === 'calcom'): ?>
				<div class="mb-4 p-3 bg-green-500/20 border border-green-500/50 rounded-lg">
					<p class="text-green-400 text-sm"><?= htmlspecialchars($message) ?></p>
				</div>
				<?php endif; ?>
				<form id="calcomForm">
					<div class="space-y-4 mb-6">
						<div>
							<label class="block text-sm font-medium text-gray-400 mb-2">URL</label>
							<input type="text" name="cal_url" value="<?= $calUrl ?>" class="w-full px-4 py-2 bg-gray-500 dark:bg-gray-500 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange focus:border-transparent text-gray-400 ">
						</div>
						<div>
							<label class="block text-sm font-medium text-gray-400 mb-2">Token</label>
							<input type="text" name="cal_token" value="<?= $calToken ?>" class="w-full px-4 py-2 bg-gray-500 dark:bg-gray-500 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange focus:border-transparent text-gray-400 ">
						</div>
						<div>
							<label class="block text-sm font-medium text-gray-400 mb-2">Webhook</label>
							<input type="text" name="cal_webhook" value="<?= $calWebhook ?>" class="w-full px-4 py-2 bg-gray-500 dark:bg-gray-500 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange focus:border-transparent text-gray-400 ">
						</div>
					</div>
					<button type="submit" class="cursor-pointer inline-flex gap-2 items-center px-6 py-2 bg-gray-500 hover:bg-orange text-gray-400 font-medium rounded-full transition-colors hover:text-white">
						<i data-lucide="save" class="w-4 h-4 stroke-[2px]"></i> Save Changes
					</button>
				</form>
			</div>
		</div>

	</div>
</main>

<?php include 'partials/footer.php'; ?>

<script>
document.getElementById('calcomForm').addEventListener('submit', async function(e) {
	e.preventDefault();
	const formData = new FormData(this);
	const data = {
		cal_url: formData.get('cal_url'),
		cal_token: formData.get('cal_token'),
		cal_webhook: formData.get('cal_webhook')
	};
	
	try {
		const response = await fetch('settings.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: JSON.stringify(data)
		});
		const result = await response.json();
		
		if (result.success) {
			window.location.href = 'settings.php?msg=calcom';
		} else {
			alert('Error: ' + result.error);
		}
	} catch (error) {
		alert('Error updating settings: ' + error.message);
	}
});
</script>

</div>
</body>
</html>
