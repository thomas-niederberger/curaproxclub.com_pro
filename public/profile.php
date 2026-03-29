<?php
require_once __DIR__ . '/../config/config.php';

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

// Use database profile data for name and email
$firstName = htmlspecialchars($currentProfile['first_name'] ?? '');
$lastName  = htmlspecialchars($currentProfile['last_name'] ?? '');
$email     = htmlspecialchars($currentProfile['email'] ?? '');
$hasHubSpotContact = !empty($currentProfile['id_hubspot_b2b_contact']);

$avatar = $currentProfile['avatar'] ?? '';
$avatarUrl = $avatar ? 'uploads/avatars/' . htmlspecialchars($avatar) : '';
$initials = $currentUserInitials;
$licenceNumber = $currentProfile['licence_number'];
$licenceVerified = $currentProfile['licence_verified'];

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
						<p id="hs-jobtitle" class="text-gray-400 text-base hidden"></p>
					</div>
				</div>

				<div class="space-y-4">
					<div>
						<label class="block text-xs font-medium uppercase text-gray-400 mb-1">Email</label>
						<p class="text-gray-400"><?= $email ?></p>
					</div>
					<div>
						<label class="block text-xs font-medium uppercase text-gray-400 mb-1">Phone</label>
						<p id="hs-phone" class="text-gray-400">
							<span class="animate-pulse inline-block h-4 w-28 bg-gray-600 rounded align-middle"></span>
						</p>
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

		<!-- HubSpot Data (loaded asynchronously) -->
		<div class="lg:col-span-2 space-y-6">
			<?php if ($hasHubSpotContact): ?>

			<!-- Contact Information skeleton -->
			<div id="hs-contact-section" class="dark:bg-gray-700 bg-gray-700 rounded-lg p-6">
				<h3 class="text-xl font-bold text-gray-400 mb-6">Contact Information</h3>
				<div id="hs-contact-content" class="grid grid-cols-1 md:grid-cols-2 gap-6">
					<?php for ($i = 0; $i < 4; $i++): ?>
					<div class="animate-pulse">
						<div class="h-3 bg-gray-600 rounded w-20 mb-2"></div>
						<div class="h-4 bg-gray-600 rounded w-3/4"></div>
					</div>
					<?php endfor; ?>
				</div>
			</div>

			<!-- Company Information (hidden until data arrives) -->
			<div id="hs-company-section" class="dark:bg-gray-700 bg-gray-700 rounded-lg p-6 hidden">
				<h3 class="text-xl font-bold text-gray-400 mb-6">Company Information</h3>
				<div id="hs-company-content" class="grid grid-cols-1 md:grid-cols-2 gap-6"></div>
			</div>

			<?php else: ?>
			<div class="dark:bg-gray-700 bg-gray-700 rounded-lg p-6">
				<p class="text-gray-400">No HubSpot data available for this profile.</p>
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

<?php if ($hasHubSpotContact): ?>
<script>
(function () {
    const CONTACT_FIELDS = ['address', 'address2', 'city', 'state', 'zip', 'country'];
    const COMPANY_FIELDS = ['name', 'address', 'address2', 'city', 'state', 'zip', 'country', 'phone', 'website'];

    function fieldLabel(key) {
        return key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
    }

    function buildFields(obj, keys) {
        return keys
            .filter(k => obj[k])
            .map(k => `<div>
                <label class="block text-xs font-medium uppercase text-gray-400 mb-2">${fieldLabel(k)}</label>
                <p class="text-gray-400">${obj[k]}</p>
            </div>`)
            .join('');
    }

    fetch('/api/hubspot_data_get.php')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const contact = res.data.contact || {};
            const company = res.data.company || {};

            // Update profile card
            const jobtitleEl = document.getElementById('hs-jobtitle');
            if (jobtitleEl && contact.jobtitle) {
                jobtitleEl.textContent = contact.jobtitle;
                jobtitleEl.classList.remove('hidden');
            }
            const phoneEl = document.getElementById('hs-phone');
            if (phoneEl) phoneEl.textContent = contact.phone || '—';

            // Populate Contact Information panel
            const contactContent = document.getElementById('hs-contact-content');
            if (contactContent) {
                const html = buildFields(contact, CONTACT_FIELDS);
                contactContent.innerHTML = html || '<p class="text-gray-400 col-span-2">No contact details on record.</p>';
            }

            // Populate Company Information panel (show only if there is data)
            const companySection = document.getElementById('hs-company-section');
            const companyContent = document.getElementById('hs-company-content');
            if (companySection && companyContent) {
                const html = buildFields(company, COMPANY_FIELDS);
                if (html) {
                    companyContent.innerHTML = html;
                    companySection.classList.remove('hidden');
                }
            }
        })
        .catch(() => {
            const contactContent = document.getElementById('hs-contact-content');
            if (contactContent) contactContent.innerHTML = '<p class="text-gray-400 col-span-2">Could not load profile data.</p>';
            const phoneEl = document.getElementById('hs-phone');
            if (phoneEl) phoneEl.textContent = '—';
        });
})();
</script>
<?php endif; ?>

</div>
</body>
</html>
