<?php
require_once __DIR__ . '/../config/config.php';

// Handle AJAX request to update profile timestamp
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
	header('Content-Type: application/json');
	
	if (!isset($_SESSION['profile_id'])) {
		http_response_code(401);
		echo json_encode(['success' => false, 'error' => 'Unauthorized']);
		exit;
	}
	
	try {
		$pdo = getDbConnection();
		$stmt = $pdo->prepare('UPDATE profile SET professionalshop_tag = NOW() WHERE id = ?');
		$stmt->execute([$_SESSION['profile_id']]);
		
		echo json_encode([
			'success' => true,
			'message' => 'Professional Shop timestamp updated',
			'profile_id' => $_SESSION['profile_id']
		]);
	} catch (Exception $e) {
		error_log('shop-professional-apply error: ' . $e->getMessage());
		http_response_code(500);
		echo json_encode(['success' => false, 'error' => 'An internal error occurred']);
	}
	exit;
}

$formId = 'c3cc6ce5-5f00-4ff6-9811-b15b8ae37f2d';
$hasHubSpotContact = !empty($currentProfile['id_hubspot_b2b_contact']);
?>

<!DOCTYPE html>
<html class="h-full bg-gray-900">
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
	</section>
	<script src="https://js-eu1.hsforms.net/forms/embed/developer/27229630.js" defer></script>
    <div class="hs-form-html"
         data-region="eu1"
         data-form-id="<?= $formId ?>"
         data-portal-id="27229630">
    </div>

    <script>
    (function () {
        <?php if ($hasHubSpotContact): ?>
        // Fetch prefill data asynchronously so the page renders immediately
        fetch('/api/hubspot_prefill_get.php?form_id=<?= urlencode($formId) ?>')
            .then(r => r.json())
            .then(res => {
                if (!res.success || !res.query_string) return;

                // Apply the prefill params to the URL without a page reload
                if (!window.location.search) {
                    const newUrl = window.location.protocol + '//' + window.location.host +
                                   window.location.pathname + '?' + res.query_string;
                    window.history.replaceState({ path: newUrl }, '', newUrl);
                }
            })
            .catch(() => { /* prefill is best-effort; form still works without it */ });

        // Prefill radio/checkbox inputs once the HubSpot form is ready
        window.addEventListener('message', event => {
            if (event.data.type === 'hsFormCallback' && event.data.eventName === 'onFormReady') {
                const urlParams = new URLSearchParams(window.location.search);
                const form = document.querySelector('.hs-form-html form');
                if (!form) return;
                urlParams.forEach((value, key) => {
                    form.querySelectorAll(
                        `input[name="${key}"][type="radio"], input[name$="/${key}"][type="radio"],` +
                        `input[name="${key}"][type="checkbox"], input[name$="/${key}"][type="checkbox"]`
                    ).forEach(input => {
                        if (input.value === value) {
                            input.checked = true;
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                });
            }
        });
        <?php endif; ?>
    })();
    </script>

    <script>
        window.addEventListener("hs-form-event:on-submission:success", async event => {
            try {
                const response = await fetch('shop-professional-apply.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    console.log('Profile timestamp updated successfully');
                    
                    // Create and show "Back to Dashboard" button
                    const backButton = document.createElement('a');
                    backButton.href = '/index.php';
                    backButton.className = 'inline-flex items-center px-6 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors mt-4';
                    backButton.innerHTML = '<i data-lucide="arrow-left" class="w-4 h-4 stroke-[2px]"></i> Back to Dashboard';
                    
                    const formContainer = document.querySelector('.hs-form-html');
                    formContainer.appendChild(backButton);
                    
                    // Initialize lucide icon
                    if (window.lucide) {
                        lucide.createIcons();
                    }
                } else {
                    console.error('Failed to update profile timestamp:', result.error);
                }
            } catch (error) {
                console.error('Error updating profile timestamp:', error);
            }
            
            // Sync HubSpot contact and company IDs (also checks B2C and Shopify)
            try {
                const syncResponse = await fetch('/api/sync_external_ids.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        profile_id: <?= $currentProfile['id'] ?? 0 ?>,
                        email: '<?= htmlspecialchars($currentProfile['email'] ?? '') ?>'
                    })
                });
                const syncResult = await syncResponse.json();
                if (syncResult.success) {
                    console.log('External IDs synced:', syncResult);
                } else {
                    console.error('Failed to sync external IDs:', syncResult.error);
                }
            } catch (error) {
                console.error('Error syncing external IDs:', error);
            }
        });
    </script>
</div>
</main>

<?php include 'partials/footer.php'; ?>
</div>
</body>
</html>