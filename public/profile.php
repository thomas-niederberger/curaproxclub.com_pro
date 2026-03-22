<?php
require_once __DIR__ . '/partials/config.php';

$pdo = getDbConnection();
$message = '';
$error = '';

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['avatar'];
    $maxSize = 800 * 1024;
    $allowedTypes = ['image/jpeg', 'image/png'];
    $allowedExts = ['jpg', 'jpeg', 'png'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes) || !in_array($ext, $allowedExts)) {
        $error = 'Only JPG and PNG files are allowed.';
    } elseif ($file['size'] > $maxSize) {
        $error = 'File size must be less than 800KB.';
    } else {
        // Ensure upload directory exists
        $uploadDir = __DIR__ . '/uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = $currentProfileId . '_' . time() . '.' . $ext;
        $uploadPath = $uploadDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $stmt = $pdo->prepare('SELECT avatar FROM profile WHERE id = ?');
            $stmt->execute([$currentProfileId]);
            $oldAvatar = $stmt->fetchColumn();
            if ($oldAvatar && file_exists($uploadDir . '/' . $oldAvatar)) {
                unlink($uploadDir . '/' . $oldAvatar);
            }

            $stmt = $pdo->prepare('UPDATE profile SET avatar = ? WHERE id = ?');
            $stmt->execute([$filename, $currentProfileId]);
            header('Location: profile.php?msg=avatar');
            exit;
        } else {
            $error = 'Failed to upload file.';
        }
    }
}

if (isset($_GET['msg'])) {
    $messages = [
        'avatar' => 'Avatar updated successfully!'
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

// Configure which contact fields to display
$displayContactFields = [
    'address',
    'address2',
    'city',
    'state',
    'zip',
    'country'
];

// Configure which company fields to display
$displayCompanyFields = [
    'name',
    'address',
    'address2',
    'city',
    'state',
    'zip',
    'country',
    'phone',
    'website'
];
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
				<?php if ($message): ?>
				<div class="mb-4 p-3 bg-green-500/20 border border-green-500/50 rounded-lg">
					<p class="text-green-400 text-sm"><?= htmlspecialchars($message) ?></p>
				</div>
				<?php endif; ?>
				<?php if ($error): ?>
				<div class="mb-4 p-3 bg-red-500/20 border border-red-500/50 rounded-lg">
					<p class="text-red-400 text-sm"><?= htmlspecialchars($error) ?></p>
				</div>
				<?php endif; ?>

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

		<!-- HubSpot Data -->
		<div class="lg:col-span-2 space-y-6">
			<?php if ($hubspotData): ?>
				<!-- Contact Information -->
				<div class="dark:bg-gray-700 bg-gray-700 rounded-lg p-6 ">
					<h3 class="text-xl font-bold text-gray-400 mb-6">Contact Information</h3>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
						<?php foreach ($displayContactFields as $fieldKey): ?>
							<?php if (isset($hubspotData['contact'][$fieldKey]) && $hubspotData['contact'][$fieldKey]): ?>
							<?php 
								$value = $hubspotData['contact'][$fieldKey];
								// Handle array values (like country field)
								$displayValue = is_array($value) ? ($value['label'] ?? $value['value'] ?? '') : $value;
							?>
							<div>
								<label class="block text-xs font-medium uppercase text-gray-400 mb-2"><?= ucfirst(str_replace('_', ' ', $fieldKey)) ?></label>
								<p class="text-gray-400"><?= htmlspecialchars($displayValue) ?></p>
							</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Company Information -->
				<?php if (!empty($hubspotData['company'])): ?>
				<div class="dark:bg-gray-700 bg-gray-700 rounded-lg p-6 ">
					<h3 class="text-xl font-bold text-gray-400 mb-6">Company Information</h3>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
						<?php foreach ($displayCompanyFields as $fieldKey): ?>
							<?php if (isset($hubspotData['company'][$fieldKey]) && $hubspotData['company'][$fieldKey]): ?>
							<?php 
								$value = $hubspotData['company'][$fieldKey];
								// Handle array values (like country field)
								$displayValue = is_array($value) ? ($value['label'] ?? $value['value'] ?? '') : $value;
							?>
							<div>
								<label class="block text-xs font-medium uppercase text-gray-400 mb-2"><?= ucfirst(str_replace('_', ' ', $fieldKey)) ?></label>
								<p class="text-gray-400"><?= htmlspecialchars($displayValue) ?></p>
							</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
			<?php else: ?>
				<div class="dark:bg-gray-700 bg-gray-700 rounded-lg p-6 ">
					<p class="text-gray-400">No data available for this profile.</p>
				</div>
			<?php endif; ?>
			<!-- Action Buttons -->
			<div class="mt-6 flex flex-wrap gap-3">
				<a href="profile-edit.php" class="inline-flex gap-2 items-center px-6 py-2 bg-gray-500 hover:bg-orange text-gray-400 font-medium rounded-full transition-colors hover:text-white">
					<i data-lucide="pencil" class="w-4 h-4 stroke-[2px]"></i> Edit
				</a>
				<button type="button" onclick="document.getElementById('avatar-upload-input').click()" class="cursor-pointer inline-flex gap-2 items-center px-6 py-2 bg-gray-500 hover:bg-orange text-gray-400 font-medium rounded-full transition-colors hover:text-white">
					<i data-lucide="image" class="w-4 h-4 stroke-[2px]"></i> Change Profile Picture
				</button>
				<form method="POST" enctype="multipart/form-data" id="avatar-upload-form" class="hidden">
					<input type="file" name="avatar" id="avatar-upload-input" accept=".jpg,.jpeg,.png" onchange="document.getElementById('avatar-upload-form').submit()">
				</form>
			</div>
		</div>
	</div>
</main>

<?php include 'partials/footer.php'; ?>
</div>
</body>
</html>
