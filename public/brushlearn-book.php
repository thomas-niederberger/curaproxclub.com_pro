<?php
require_once __DIR__ . '/partials/config.php';
$pdo = getDbConnection();

// Use current user profile from config.php
$user = [
	'id' => $currentProfile['id'] ?? 0,
	'first_name' => $currentProfile['first_name'] ?? '',
	'last_name' => $currentProfile['last_name'] ?? '',
	'email' => $currentProfile['email'] ?? ''
];

// Check if user already has a booked booking and fetch details
$hasBookedBooking = false;
$bookedBooking = null;
if ($currentProfile) {
	$stmt = $pdo->prepare('
		SELECT b.*, l.city, l.state, l.is_virtual
		FROM ohc_booking b
		LEFT JOIN ohc_location l ON b.location_id = l.id
		WHERE b.profile_id = ? AND b.status = "booked" 
		ORDER BY b.created_at DESC 
		LIMIT 1
	');
	$stmt->execute([$currentProfile['id']]);
	$bookedBooking = $stmt->fetch(PDO::FETCH_ASSOC);
	$hasBookedBooking = $bookedBooking !== false;
}

// Fetch active locations from ohc_location table
$stmt = $pdo->prepare('SELECT id, city, state, is_virtual FROM ohc_location WHERE is_active = 1 ORDER BY state ASC, city ASC');
$stmt->execute();
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch professionals assigned to each location
$stmt = $pdo->prepare('
	SELECT op.location_id, p.id, p.first_name, p.last_name, p.email, p.cal_url
	FROM ohc_profile op
	JOIN profile p ON op.profile_id = p.id
	WHERE p.cal_url IS NOT NULL AND p.cal_url != ""
	ORDER BY p.first_name ASC, p.last_name ASC
');
$stmt->execute();
$professionalsByLocation = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $prof) {
	$locationId = $prof['location_id'];
	if (!isset($professionalsByLocation[$locationId])) {
		$professionalsByLocation[$locationId] = [];
	}
	$professionalsByLocation[$locationId][] = $prof;
}

// Fetch forms for virtual (id=1) and in-person (id=2) sessions
$stmt = $pdo->prepare('SELECT id, slug, title, questions FROM form WHERE id IN (1, 2) AND active = 1');
$stmt->execute();
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

$virtualForm = null;
$inPersonForm = null;

foreach ($forms as $form) {
	$form['questions'] = json_decode($form['questions'], true) ?: [];
	if ($form['id'] == 1) {
		$virtualForm = $form;
	} elseif ($form['id'] == 2) {
		$inPersonForm = $form;
	}
}

function getHubSpotUrlParams($contactId, $formId) {
	$token = $_ENV['hubspotTokenB2B'] ?? '';
	if (empty($token) || empty($contactId) || empty($formId)) return "error=missing_data";

	// 1. Fetch Form Definition
	$ch = curl_init("https://api.hubapi.com/marketing/v3/forms/{$formId}");
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token]
	]);
	$formRes = curl_exec($ch);
	$formDef = json_decode($formRes, true);
	curl_close($ch);

	if (!isset($formDef['fieldGroups'])) return "error=form_not_found";

	$contactFields = [];
	$companyFields = [];

	// 2. Sort fields into Contact vs Company buckets
	foreach ($formDef['fieldGroups'] as $group) {
		foreach ($group['fields'] as $field) {
			$name = $field['name'];
			if (str_starts_with($name, 'hs_') || str_starts_with($name, 'LEGAL_CONSENT')) continue;

			if (($field['objectTypeId'] ?? '') === '0-2') {
				$companyFields[] = $name;
			} else {
				$contactFields[] = $name;
			}
		}
	}

	// 3. Build Valid GraphQL Syntax
	$contactFieldsStr = implode("\n", array_unique($contactFields));
	$companyFieldsStr = !empty($companyFields) ? "associations { company_collection__primary { items { " . implode("\n", array_unique($companyFields)) . " } } }" : "";

	$query = "query GetContactData(\$id: String!) {
		CRM {
			contact(uniqueIdentifier: \"hs_object_id\", uniqueIdentifierValue: \$id) {
				{$contactFieldsStr}
				{$companyFieldsStr}
			}
		}
	}";

	// 4. Execute Query
	$ch = curl_init('https://api.hubapi.com/collector/graphql');
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
		CURLOPT_POSTFIELDS => json_encode(['query' => $query, 'variables' => ['id' => (string)$contactId]])
	]);
	$graphqlRes = curl_exec($ch);
	$result = json_decode($graphqlRes, true);
	curl_close($ch);
	if (isset($result['errors'])) {
		error_log("GraphQL Syntax Error: " . json_encode($result['errors']));
		return "error=syntax_check_logs";
	}

	$contact = $result['data']['CRM']['contact'] ?? null;
	if (!$contact) return "error=no_contact_data";

	// 5. Flatten results for the URL
	$params = [];
	$getVal = fn($v) => is_array($v) ? ($v['label'] ?? '') : $v;

	foreach ($contactFields as $f) {
		$params[$f] = $getVal($contact[$f] ?? '');
	}

	$company = ($contact['associations']['company_collection__primary']['items'] ?? [])[0] ?? null;
	if ($company) {
		foreach ($companyFields as $f) {
			$params[$f] = $getVal($company[$f] ?? '');
		}
	}
	
	return http_build_query(array_filter($params));
}

$formId = 'eae6b326-2d0c-4534-b652-69dd49011c1f';
$prefillQuery = "";

if (!empty($currentProfile['id_hubspot_b2b_contact'])) {
	// Cache prefill query in session to avoid repeated API calls
	$cacheKey = 'hubspot_prefill_' . $currentProfile['id_hubspot_b2b_contact'];
	if (!isset($_SESSION[$cacheKey]) || empty($_SESSION[$cacheKey])) {
		$_SESSION[$cacheKey] = getHubSpotUrlParams($currentProfile['id_hubspot_b2b_contact'], $formId);
	}
	$prefillQuery = $_SESSION[$cacheKey];
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

<section>
	<div class="<?= $theme->getHeaderClasses() ?>">
		<h1><?= htmlspecialchars($pageHeader) ?></h1>
	</div>
</section>

<?php if (!$hasBookedBooking): ?>
<div class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-2">
	<div class="bg-gray-700 dark:bg-gray-700 rounded-lg p-4 border border-gray-700">
		<span class="block text-xs font-semibold uppercase text-orange mb-1">Step 1</span>
		<span class="block text-sm font-bold text-orange">Profile</span>
	</div>
	<div class="bg-gray-700 dark:bg-gray-700 rounded-lg p-4 border border-gray-700">
		<span class="block text-xs font-semibold uppercase text-gray-400 mb-1">Step 2</span>
		<span class="block text-sm font-bold text-gray-400">Location</span>
	</div>
	<div class="bg-gray-700 dark:bg-gray-700 rounded-lg p-4 border border-gray-700">
		<span class="block text-xs font-semibold uppercase text-gray-400 mb-1">Step 3</span>
		<span class="block text-sm font-bold text-gray-400">Questions</span>
	</div>
	<div class="bg-gray-700 dark:bg-gray-700 rounded-lg p-4 border border-gray-700">
		<span class="block text-xs font-semibold uppercase text-gray-400 mb-1">Step 4</span>
		<span class="block text-sm font-bold text-gray-400">Date & Time</span>
	</div>
	<div class="bg-gray-700 dark:bg-gray-700 rounded-lg p-4 border border-gray-700">
		<span class="block text-xs font-semibold uppercase text-gray-400 mb-1">Step 5</span>
		<span class="block text-sm font-bold text-gray-400">Confirmation</span>
	</div>
</div>

<!-- Step 1: Profile Information -->
<div id="step-profile" class="bg-gray-700 dark:bg-gray-700 rounded-lg p-6 mb-2">
	<h3 class="mb-2 text-xl text-gray-400 dark:text-gray-400">Your Profile Information</h3>
	<p class="mb-4 text-gray-400 dark:text-gray-400">Please complete or update your profile information to continue with your booking.</p>
	
	<script>
	(function() {
		const query = "<?= $prefillQuery ?>";
		if (query && !window.location.search) {
			const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?' + query;
			window.history.replaceState({ path: newUrl }, '', newUrl);
		}

		window.addEventListener('message', event => {
			if (event.data.type === 'hsFormCallback' && event.data.eventName === 'onFormReady') {
				const urlParams = new URLSearchParams(window.location.search);
				const form = document.querySelector('.hs-form-html form');
				if (form) {
					urlParams.forEach((value, key) => {
						const inputs = form.querySelectorAll(`input[name="${key}"][type="radio"], input[name$="/${key}"][type="radio"], input[name="${key}"][type="checkbox"], input[name$="/${key}"][type="checkbox"]`);
						inputs.forEach(input => {
							if (input.value === value) {
								input.checked = true;
								input.dispatchEvent(new Event('change', { bubbles: true }));
								console.log(`✅ Prefilled Radio/Checkbox: ${key} = ${value}`);
							}
						});
					});
				}
			}
		});
	})();
	</script>
	
	<script src="https://js-eu1.hsforms.net/forms/embed/developer/27229630.js" defer></script>
	<div class="hs-form-html" 
		 data-region="eu1" 
		 data-form-id="eae6b326-2d0c-4534-b652-69dd49011c1f" 
		 data-portal-id="27229630">
	</div>
</div>

<!-- Step 2: Location Selection -->
<div id="step-location" class="bg-gray-700 dark:bg-gray-700 rounded-lg p-6 mb-2 hidden">
	<h3 class="mb-2 text-xl text-gray-400 dark:text-gray-400">Choose your Location</h3>
	<p class="mb-4 text-gray-400 dark:text-gray-400">We're currently offering this training in select locations. Check back soon for updates on when we'll be expanding to more areas.</p>
	<div class="mb-6">
		<select id="location-select" name="location_id" 
			class="w-full pl-4 pr-10 py-2 bg-gray-600 dark:bg-gray-600 border border-gray-600 rounded-lg 
				focus:ring-2 focus:ring-orange text-gray-400 dark:text-gray-400 appearance-none cursor-pointer outline-none"
			style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2220%22 height=%2220%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22white%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><path d=%22m6 9 6 6 6-6%22/></svg>'); 
				background-repeat: no-repeat; 
				background-position: right 1.25rem center; 
				background-size: 1.2em;">
			<option value="">Select a location</option>
			<?php foreach ($locations as $location): ?>
				<option value="<?= htmlspecialchars($location['id']) ?>">
					<?= htmlspecialchars($location['city']) ?>, <?= htmlspecialchars($location['state']) ?>
					<?= $location['is_virtual'] ? ' (Virtual)' : '' ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
	<button type="button" id="btn-location-next" class="cursor-pointer inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors" disabled>
		<i data-lucide="arrow-right" class="w-4 h-4 stroke-[2px]"></i> Next
	</button>
</div>

<!-- Step 3: Virtual Form Questions -->
<div id="step-questions-virtual" class="bg-gray-700 dark:bg-gray-700 rounded-lg p-6 mb-2 hidden">
	<h3 class="mb-2 text-xl text-gray-400 dark:text-gray-400">Answer the questions</h3>
	<p class="mb-4 text-gray-400 dark:text-gray-400">Please answer the following questions to help us prepare for your virtual Brush & Learn session.</p>
	
	<?php if ($virtualForm && !empty($virtualForm['questions'])): ?>
		<div class="space-y-4 mb-6">
			<?php foreach ($virtualForm['questions'] as $question): ?>
				<div>
					<label class="block text-sm font-medium text-gray-400 mb-2">
						<?= htmlspecialchars($question['label']) ?>
						<?php if ($question['required']): ?>
							<span class="text-orange">*</span>
						<?php endif; ?>
					</label>
					<?php if ($question['type'] === 'textarea'): ?>
						<textarea 
							name="question_<?= htmlspecialchars($question['id']) ?>" 
							rows="3" 
							class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none" 
							<?= $question['required'] ? 'required' : '' ?>
						></textarea>
					<?php else: ?>
						<input 
							type="text" 
							name="question_<?= htmlspecialchars($question['id']) ?>" 
							class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none" 
							<?= $question['required'] ? 'required' : '' ?>
						>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	
	<button type="button" id="btn-questions-virtual-back" class="cursor-pointer mr-2 inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors">
		<i data-lucide="arrow-left" class="w-4 h-4 stroke-[2px]"></i> Back
	</button>
	<button type="button" id="btn-questions-virtual-next" class="cursor-pointer inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors">
		<i data-lucide="arrow-right" class="w-4 h-4 stroke-[2px]"></i> Next
	</button>
</div>

<!-- Step 3: In-Person Form Questions -->
<div id="step-questions-inperson" class="bg-gray-700 dark:bg-gray-700 rounded-lg p-6 mb-2 hidden">
	<h3 class="mb-2 text-xl text-gray-400 dark:text-gray-400">Answer the questions</h3>
	<p class="mb-4 text-gray-400 dark:text-gray-400">Please answer the following questions to help us prepare for your in-person Brush & Learn session.</p>
	
	<?php if ($inPersonForm && !empty($inPersonForm['questions'])): ?>
		<div class="space-y-4 mb-6">
			<?php foreach ($inPersonForm['questions'] as $question): ?>
				<div>
					<label class="block text-sm font-medium text-gray-400 mb-2">
						<?= htmlspecialchars($question['label']) ?>
						<?php if ($question['required']): ?>
							<span class="text-orange">*</span>
						<?php endif; ?>
					</label>
					<?php if ($question['type'] === 'textarea'): ?>
						<textarea 
							name="question_<?= htmlspecialchars($question['id']) ?>" 
							rows="3" 
							class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none" 
							<?= $question['required'] ? 'required' : '' ?>
						></textarea>
					<?php else: ?>
						<input 
							type="text" 
							name="question_<?= htmlspecialchars($question['id']) ?>" 
							class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none" 
							<?= $question['required'] ? 'required' : '' ?>
						>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	
	<button type="button" id="btn-questions-inperson-back" class="cursor-pointer mr-2 inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors">
		<i data-lucide="arrow-left" class="w-4 h-4 stroke-[2px]"></i> Back
	</button>
	<button type="button" id="btn-questions-inperson-next" class="cursor-pointer inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors">
		<i data-lucide="arrow-right" class="w-4 h-4 stroke-[2px]"></i> Next
	</button>
</div>

<!-- Step 4: Date & Time Selection -->
<div id="step-datetime" class="bg-gray-700 dark:bg-gray-700 rounded-lg p-6 mb-2 hidden">
	<h3 class="mb-2 text-xl text-gray-400 dark:text-gray-400">Choose your date and time</h3>
	<p class="mb-4 text-gray-400 dark:text-gray-400">Select a date and time that works best for you and confirm your session.</p>
	
	<!-- Professional Selection (shown when multiple professionals available) -->
	<div id="professional-selection" class="mb-6 hidden">
		<label class="block text-sm font-medium text-gray-400 mb-2">
			Select your preferred professional
			<span class="text-orange">*</span>
		</label>
		<select id="professional-select" name="professional_id" 
			class="w-full pl-4 pr-10 py-2 bg-gray-600 dark:bg-gray-600 border border-gray-600 rounded-lg 
				focus:ring-2 focus:ring-orange text-gray-400 dark:text-gray-400 appearance-none cursor-pointer outline-none"
			style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2220%22 height=%2220%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22white%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><path d=%22m6 9 6 6 6-6%22/></svg>'); 
				background-repeat: no-repeat; 
				background-position: right 1.25rem center; 
				background-size: 1.2em;">
			<option value="">Select a professional</option>
		</select>
	</div>
	
	<!-- Cal inline embed code begins -->
	<div style="width:100%;height:auto;min-height:600px;overflow:scroll" id="my-cal-inline-brush-learn"></div>

	<script type="text/javascript">
	(function (C, A, L) { 
		let p = function (a, ar) { a.q.push(ar); }; 
		let d = C.document; 
		C.Cal = C.Cal || function () { 
			let cal = C.Cal; 
			let ar = arguments; 
			if (!cal.loaded) { 
				cal.ns = {}; 
				cal.q = cal.q || []; 
				d.head.appendChild(d.createElement("script")).src = A; 
				cal.loaded = true; 
			} 
			if (ar[0] === L) { 
				const api = function () { p(api, arguments); }; 
				const namespace = ar[1]; 
				api.q = api.q || []; 
				if(typeof namespace === "string"){
					cal.ns[namespace] = cal.ns[namespace] || api;
					p(cal.ns[namespace], ar);
					p(cal, ["initNamespace", namespace]);
				} else p(cal, ar); 
				return;
			} 
			p(cal, ar); 
		}; 
	})(window, "https://app.cal.com/embed/embed.js", "init");

	// 1. Initialize the Namespace
	Cal("init", "brush-learn", {origin:"https://app.cal.com"});

	// 2. Configure the Inline Calendar (calLink will be set dynamically by JavaScript)
	// The calendar will be initialized when a professional is selected

	// 3. UI Styling (Curaprox Orange)
	Cal.ns["brush-learn"]("ui", {
		"theme": "dark", 
		"cssVarsPerTheme": {
			"light": {"cal-brand": "#ff8200"},
			"dark": {"cal-brand": "#ff8200"}
		},
		"hideEventTypeDetails": false,
		"layout": "month_view"
	});

	// 4. The "Success" Callback Logic
	Cal.ns["brush-learn"]("on", {
		action: "bookingSuccessful",
		callback: async (event) => {
			console.log("OHC Booking Confirmed - Full Event:", event);
			console.log("Event Detail:", event.detail);
			
			// Store Cal.com booking data globally
			window.calBookingData = event.detail;
			
			// Extract booking data from Cal.com event - try multiple paths
			const eventData = event.detail.data || event.detail;
			
			// Cal.com sends the UID in different places depending on version
			let calBookingUid = null;
			if (eventData.uid) calBookingUid = eventData.uid;
			else if (eventData.bookingUid) calBookingUid = eventData.bookingUid;
			else if (eventData.booking && eventData.booking.uid) calBookingUid = eventData.booking.uid;
			else if (eventData.booking && eventData.booking.id) calBookingUid = eventData.booking.id;
			else if (eventData.id) calBookingUid = eventData.id;
			
			// Get the booking date/time
			const calBookingDate = eventData.startTime || eventData.date || eventData.bookingStartTime || 
			                       (eventData.booking && eventData.booking.startTime);
			
			// Extract any notes from Cal.com booking
			const calNotes = (eventData.booking && eventData.booking.responses && eventData.booking.responses.notes) ||
			                 eventData.notes || eventData.description || 
			                 (eventData.booking && eventData.booking.notes) || '';
			
			console.log('Extracted Cal.com booking UID:', calBookingUid);
			console.log('Extracted Cal.com booking date:', calBookingDate);
			console.log('Extracted Cal.com notes:', calNotes);
			
			// Save booking confirmation to database and create HubSpot meeting
			try {
				const response = await fetch('/api/brushlearn-book_confirm.php', {
					method: 'POST',
					headers: {'Content-Type': 'application/json'},
					body: JSON.stringify({
						booking_id: window.bookingId,
						profile_id: <?= $user['id'] ?>,
						cal_booking_id: calBookingUid,
						booking_date: calBookingDate,
						contact_id: window.hubspotContactId || null,
						company_id: window.hubspotCompanyId || null,
						cal_notes: calNotes
					})
				});
				
				const result = await response.json();
				console.log('Booking confirmation response:', result);
				
				if (result.hubspot_meeting_id) {
					console.log('✅ HubSpot meeting created:', result.hubspot_meeting_id);
				} else {
					console.warn('⚠️ HubSpot meeting NOT created:', result.hubspot_meeting_debug);
				}
				
				if (result.success) {
					console.log('Booking confirmed in database');
					
					// Navigate directly to Step 5 (Confirmation)
					setTimeout(() => {
						const stepDatetime = document.getElementById('step-datetime');
						const stepConfirmation = document.getElementById('step-confirmation');
						
						stepDatetime.classList.add('hidden');
						stepConfirmation.classList.remove('hidden');
						
						// Update step indicator
						const stepIndicators = {
							profile: document.querySelector('.grid.grid-cols-1.md\\:grid-cols-5 > div:nth-child(1)'),
							location: document.querySelector('.grid.grid-cols-1.md\\:grid-cols-5 > div:nth-child(2)'),
							questions: document.querySelector('.grid.grid-cols-1.md\\:grid-cols-5 > div:nth-child(3)'),
							datetime: document.querySelector('.grid.grid-cols-1.md\\:grid-cols-5 > div:nth-child(4)'),
							confirmation: document.querySelector('.grid.grid-cols-1.md\\:grid-cols-5 > div:nth-child(5)')
						};
						
						Object.keys(stepIndicators).forEach(key => {
							const indicator = stepIndicators[key];
							const label = indicator.querySelector('span:first-child');
							const text = indicator.querySelector('span:last-child');
							
							if (key === 'confirmation') {
								label.classList.remove('text-gray-400');
								label.classList.add('text-orange');
								text.classList.remove('text-gray-400');
								text.classList.add('text-orange');
								indicator.classList.remove('border-gray-700');
								indicator.classList.add('border-orange');
							} else {
								label.classList.remove('text-orange');
								label.classList.add('text-gray-400');
								text.classList.remove('text-orange');
								text.classList.add('text-gray-400');
								indicator.classList.remove('border-orange');
								indicator.classList.add('border-gray-700');
							}
						});
						
						lucide.createIcons();
					}, 1500);
				} else {
					console.error('Failed to confirm booking:', result.error);
				}
			} catch (error) {
				console.error('Error confirming booking:', error);
			}
		}
	});
	</script>
	<!-- Cal inline embed code ends -->
	<button type="button" id="btn-datetime-back" class="cursor-pointer inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors">
		<i data-lucide="arrow-left" class="w-4 h-4 stroke-[2px]"></i> Back
	</button>
</div>

<?php endif; // End of !$hasBookedBooking ?>

<!-- Step 5: Confirmation -->
<div id="step-confirmation" class="bg-gray-700 dark:bg-gray-700 rounded-lg p-6 mb-2 <?= $hasBookedBooking ? '' : 'hidden' ?>">
	<?php if ($hasBookedBooking && $bookedBooking): ?>
		<!-- Show booking details for returning users -->
		<div class="py-6">
			<div class="text-center mb-8">
				<div class="w-20 h-20 mx-auto mb-4 rounded-full bg-green-500/20 flex items-center justify-center">
					<i data-lucide="check-circle" class="w-12 h-12 text-green-400"></i>
				</div>
				<h3 class="text-2xl font-bold text-gray-400 mb-2">Your Booking is Confirmed</h3>
			</div>
			
			<!-- Booking Details -->
			<div class="max-w-md mx-auto space-y-4 mb-8">
				<?php if ($bookedBooking['city'] && $bookedBooking['state']): ?>
					<div class="p-4 bg-gray-600 rounded-lg">
						<label class="block text-xs font-semibold uppercase text-gray-400 mb-2">Location</label>
						<p class="text-lg text-gray-400">
							<?= htmlspecialchars($bookedBooking['city']) ?>, <?= htmlspecialchars($bookedBooking['state']) ?>
							<?php if ($bookedBooking['is_virtual']): ?>
								<span class="text-sm text-gray-400 ml-2">(Virtual)</span>
							<?php endif; ?>
						</p>
					</div>
				<?php endif; ?>
				
				<?php if ($bookedBooking['booking_date']): ?>
					<div class="p-4 bg-gray-600 rounded-lg">
						<label class="block text-xs font-semibold uppercase text-gray-400 mb-2">Date & Time</label>
						<p class="text-lg text-gray-400">
							<?php
							$date = new DateTime($bookedBooking['booking_date']);
							$date->setTimezone(new DateTimeZone('America/Los_Angeles'));
							echo $date->format('F j, Y');
							?>
							at
							<?php echo $date->format('g:i A T'); ?>
						</p>
					</div>
				<?php endif; ?>
				
				<div class="p-4 bg-gray-600 rounded-lg">
					<label class="block text-xs font-semibold uppercase text-gray-400 mb-2">Status</label>
					<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500/20 text-green-400">
						<i data-lucide="check-circle" class="w-4 h-4 mr-2"></i> Confirmed
					</span>
				</div>
			</div>
			
			<div class="text-center">
				<p class="text-sm text-gray-400 mb-6">
					For any changes or questions, please reach out to 
					<a href="mailto:support@curaden.us" class="text-orange hover:underline">support@curaden.us</a>
				</p>
				<a href="/brushlearn-info.php" class="cursor-pointer inline-flex items-center px-6 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors">
					<i data-lucide="arrow-left" class="w-4 h-4 stroke-[2px]"></i> Back to Brush & Learn Info
				</a>
			</div>
		</div>
	<?php else: ?>
		<!-- Show thank you message for new bookings -->
		<div class="text-center py-8">
			<div class="w-20 h-20 mx-auto mb-6 rounded-full bg-orange/20 flex items-center justify-center">
				<i data-lucide="check-circle" class="w-12 h-12 text-orange"></i>
			</div>
			<h3 class="mb-4 text-2xl font-bold text-gray-400 dark:text-gray-400">Thank You!</h3>
			<p class="mb-2 text-lg text-gray-400 dark:text-gray-400">Your Brush & Learn session has been booked successfully.</p>
			<p class="mb-6 text-gray-400 dark:text-gray-400">You will receive a confirmation email shortly with all the details.</p>
			<p class="mb-8 text-sm text-gray-400 dark:text-gray-400">
				For any changes or questions, please reach out to 
				<a href="mailto:support@curaden.us" class="text-orange hover:underline">support@curaden.us</a>
			</p>
			<a href="/brushlearn-info.php" class="cursor-pointer inline-flex items-center px-6 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors">
				<i data-lucide="check" class="w-4 h-4 stroke-[2px]"></i> View My Booking
			</a>
		</div>
	<?php endif; ?>
</div>

</div>
</main>

<?php include 'partials/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Elements
	const stepProfile = document.getElementById('step-profile');
	const locationSelect = document.getElementById('location-select');
	const stepLocation = document.getElementById('step-location');
	const stepQuestionsVirtual = document.getElementById('step-questions-virtual');
	const stepQuestionsInperson = document.getElementById('step-questions-inperson');
	const stepDatetime = document.getElementById('step-datetime');
	const stepConfirmation = document.getElementById('step-confirmation');
	
	// Buttons
	const btnLocationNext = document.getElementById('btn-location-next');
	const btnQuestionsVirtualBack = document.getElementById('btn-questions-virtual-back');
	const btnQuestionsVirtualNext = document.getElementById('btn-questions-virtual-next');
	const btnQuestionsInpersonBack = document.getElementById('btn-questions-inperson-back');
	const btnQuestionsInpersonNext = document.getElementById('btn-questions-inperson-next');
	const btnDatetimeBack = document.getElementById('btn-datetime-back');
	
	// User and location data
	const currentUser = <?= json_encode($user) ?>;
	const locations = <?= json_encode($locations) ?>;
	const professionalsByLocation = <?= json_encode($professionalsByLocation) ?>;
	let selectedLocation = null;
	let isVirtual = false;
	
	// Professional selection
	const professionalSelection = document.getElementById('professional-selection');
	const professionalSelect = document.getElementById('professional-select');
	let selectedProfessional = null;
	
	// Booking state
	let bookingId = null;
	let formId = null;
	let calBookingData = null;
	
	// Expose bookingId to window for Cal.com callback access
	window.bookingId = null;
	
	// Load existing booking data on page load
	async function loadExistingBooking() {
		try {
			const response = await fetch('/api/brushlearn-book_get.php?profile_id=' + currentUser.id);
			const data = await response.json();
			
			if (data.success && data.booking) {
				const booking = data.booking;
				bookingId = booking.id;
				window.bookingId = booking.id;
				
				// Populate location if exists
				if (booking.location_id) {
					locationSelect.value = booking.location_id;
					selectedLocation = locations.find(loc => loc.id == booking.location_id);
					isVirtual = booking.is_virtual == 1;
					
					// Enable next button
					btnLocationNext.disabled = false;
					btnLocationNext.classList.remove('opacity-50', 'cursor-not-allowed');
				}
				
				// Populate question answers if they exist
				if (booking.answers && booking.form_id) {
					formId = booking.form_id;
					const stepElement = booking.form_id == 1 ? stepQuestionsVirtual : stepQuestionsInperson;
					
					Object.keys(booking.answers).forEach(questionId => {
						const input = stepElement.querySelector(`[name="question_${questionId}"]`);
						if (input) {
							input.value = booking.answers[questionId];
						}
					});
					
					// Update button state after populating
					const nextButton = booking.form_id == 1 ? btnQuestionsVirtualNext : btnQuestionsInpersonNext;
					updateNextButtonState(stepElement, nextButton);
				}
				
				console.log('Loaded existing booking:', bookingId);
			}
		} catch (error) {
			console.error('Error loading existing booking:', error);
		}
	}
	
	// Step progress indicators
	const stepIndicators = {
		profile: document.querySelector('.grid.grid-cols-1.md\\:grid-cols-5 > div:nth-child(1)'),
		location: document.querySelector('.grid.grid-cols-1.md\\:grid-cols-5 > div:nth-child(2)'),
		questions: document.querySelector('.grid.grid-cols-1.md\\:grid-cols-5 > div:nth-child(3)'),
		datetime: document.querySelector('.grid.grid-cols-1.md\\:grid-cols-5 > div:nth-child(4)'),
		confirmation: document.querySelector('.grid.grid-cols-1.md\\:grid-cols-5 > div:nth-child(5)')
	};
	
	function updateStepIndicator(step) {
		Object.keys(stepIndicators).forEach(key => {
			const indicator = stepIndicators[key];
			const label = indicator.querySelector('span:first-child');
			const text = indicator.querySelector('span:last-child');
			
			if (key === step) {
				// Active step - orange text and border
				label.classList.remove('text-gray-400');
				label.classList.add('text-orange');
				text.classList.remove('text-gray-400');
				text.classList.add('text-orange');
				indicator.classList.remove('border-gray-700');
				indicator.classList.add('border-orange');
			} else {
				// Inactive step - gray text and border
				label.classList.remove('text-orange');
				label.classList.add('text-gray-400');
				text.classList.remove('text-orange');
				text.classList.add('text-gray-400');
				indicator.classList.remove('border-orange');
				indicator.classList.add('border-gray-700');
			}
		});
	}
	
	// Enable/disable location next button and create/update booking
	locationSelect.addEventListener('change', async function() {
		const selectedLocationId = parseInt(this.value);
		
		if (selectedLocationId) {
			btnLocationNext.disabled = false;
			btnLocationNext.classList.remove('opacity-50', 'cursor-not-allowed');
			selectedLocation = locations.find(loc => loc.id === selectedLocationId);
			isVirtual = selectedLocation && selectedLocation.is_virtual == 1;
			
			// Create or update booking in database
			try {
				const response = await fetch('/api/brushlearn-book_create.php', {
					method: 'POST',
					headers: {'Content-Type': 'application/json'},
					body: JSON.stringify({
						profile_id: currentUser.id,
						location_id: selectedLocationId,
						is_virtual: isVirtual ? 1 : 0,
						booking_id: bookingId
					})
				});
				
				const data = await response.json();
				if (data.success) {
					bookingId = data.booking_id;
					window.bookingId = data.booking_id; // Expose to Cal.com callback
					console.log('Booking created/updated:', bookingId);
				} else {
					console.error('Failed to create booking:', data.error);
				}
			} catch (error) {
				console.error('Error creating booking:', error);
			}
		} else {
			btnLocationNext.disabled = true;
			btnLocationNext.classList.add('opacity-50', 'cursor-not-allowed');
			selectedLocation = null;
		}
	});
	
	// HubSpot form submission listener - automatically navigate to Step 2
	window.addEventListener("hs-form-event:on-submission:success", async event => {
		console.log('HubSpot form submitted successfully');
		
		// Update profile timestamp
		try {
			const response = await fetch('profile-edit.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-Requested-With': 'XMLHttpRequest'
				}
			});
			const result = await response.json();
			if (result.success) {
				console.log('Profile timestamp updated successfully');
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
					profile_id: currentUser.id,
					email: currentUser.email
				})
			});
			const syncResult = await syncResponse.json();
			if (syncResult.success) {
				console.log('External IDs synced:', syncResult);
				// Store IDs globally for later use
				window.hubspotContactId = syncResult.contact_id;
				window.hubspotCompanyId = syncResult.company_id;
			} else {
				console.error('Failed to sync external IDs:', syncResult.error);
			}
		} catch (error) {
			console.error('Error syncing external IDs:', error);
		}
		
		// Automatically navigate to Step 2 (Location)
		setTimeout(() => {
			stepProfile.classList.add('hidden');
			stepLocation.classList.remove('hidden');
			updateStepIndicator('location');
			lucide.createIcons();
		}, 100);
	});
	
	// Step 2 -> Step 3 (Location -> Questions)
	btnLocationNext.addEventListener('click', async function() {
		if (!selectedLocation) return;
		
		stepLocation.classList.add('hidden');
		
		if (isVirtual) {
			stepQuestionsVirtual.classList.remove('hidden');
		} else {
			stepQuestionsInperson.classList.remove('hidden');
		}
		
		updateStepIndicator('questions');
		lucide.createIcons();
	});
	
	// Step 3 -> Step 2 (Questions -> Location)
	btnQuestionsVirtualBack.addEventListener('click', function() {
		stepQuestionsVirtual.classList.add('hidden');
		stepLocation.classList.remove('hidden');
		updateStepIndicator('location');
		lucide.createIcons();
	});
	
	btnQuestionsInpersonBack.addEventListener('click', function() {
		stepQuestionsInperson.classList.add('hidden');
		stepLocation.classList.remove('hidden');
		updateStepIndicator('location');
		lucide.createIcons();
	});
	
	// Validate required questions
	function validateRequiredQuestions(stepElement) {
		const requiredInputs = stepElement.querySelectorAll('input[required], textarea[required]');
		let allFilled = true;
		
		requiredInputs.forEach(input => {
			if (!input.value.trim()) {
				allFilled = false;
				// Add visual feedback
				input.classList.add('border-red-500');
			} else {
				input.classList.remove('border-red-500');
			}
		});
		
		if (!allFilled) {
			// Show error message
			let errorMsg = stepElement.querySelector('.validation-error');
			if (!errorMsg) {
				errorMsg = document.createElement('p');
				errorMsg.className = 'validation-error text-red-500 text-sm mt-2';
				errorMsg.textContent = 'Please fill in all required fields before continuing.';
				const buttonContainer = stepElement.querySelector('button').parentElement;
				buttonContainer.insertBefore(errorMsg, buttonContainer.firstChild);
			}
		} else {
			// Remove error message if exists
			const errorMsg = stepElement.querySelector('.validation-error');
			if (errorMsg) {
				errorMsg.remove();
			}
		}
		
		return allFilled;
	}
	
	// Check if all required fields are filled and update button state
	function updateNextButtonState(stepElement, nextButton) {
		const requiredInputs = stepElement.querySelectorAll('input[required], textarea[required]');
		let allFilled = true;
		
		requiredInputs.forEach(input => {
			if (!input.value.trim()) {
				allFilled = false;
			}
		});
		
		if (allFilled) {
			nextButton.disabled = false;
			nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
		} else {
			nextButton.disabled = true;
			nextButton.classList.add('opacity-50', 'cursor-not-allowed');
		}
	}
	
	// Add input listeners for real-time validation on virtual questions
	const virtualRequiredInputs = stepQuestionsVirtual.querySelectorAll('input[required], textarea[required]');
	virtualRequiredInputs.forEach(input => {
		input.addEventListener('input', function() {
			updateNextButtonState(stepQuestionsVirtual, btnQuestionsVirtualNext);
		});
	});
	
	// Add input listeners for real-time validation on in-person questions
	const inpersonRequiredInputs = stepQuestionsInperson.querySelectorAll('input[required], textarea[required]');
	inpersonRequiredInputs.forEach(input => {
		input.addEventListener('input', function() {
			updateNextButtonState(stepQuestionsInperson, btnQuestionsInpersonNext);
		});
	});
	
	// Initialize button states
	updateNextButtonState(stepQuestionsVirtual, btnQuestionsVirtualNext);
	updateNextButtonState(stepQuestionsInperson, btnQuestionsInpersonNext);
	
	// Collect question answers from form
	function collectAnswers(stepElement) {
		const answers = {};
		const inputs = stepElement.querySelectorAll('input[name^="question_"], textarea[name^="question_"]');
		
		inputs.forEach(input => {
			const questionId = input.name.replace('question_', '');
			answers[questionId] = input.value;
		});
		
		return answers;
	}
	
	// Save questions to database
	async function saveQuestions(stepElement, formIdValue) {
		const answers = collectAnswers(stepElement);
		
		try {
			const response = await fetch('/api/brushlearn-book_save_questions.php', {
				method: 'POST',
				headers: {'Content-Type': 'application/json'},
				body: JSON.stringify({
					booking_id: bookingId,
					profile_id: currentUser.id,
					form_id: formIdValue,
					answers: answers
				})
			});
			
			const data = await response.json();
			if (data.success) {
				console.log('Questions saved:', data.form_response_id);
				return true;
			} else {
				console.error('Failed to save questions:', data.error);
				return false;
			}
		} catch (error) {
			console.error('Error saving questions:', error);
			return false;
		}
	}
	
	// Populate professional dropdown based on selected location
	function populateProfessionals(locationId) {
		const professionals = professionalsByLocation[locationId] || [];
		
		// Clear existing options except the first one
		professionalSelect.innerHTML = '<option value="">Select a professional</option>';
		
		if (professionals.length === 0) {
			professionalSelection.classList.add('hidden');
			selectedProfessional = null;
			return;
		}
		
		if (professionals.length === 1) {
			// Only one professional - auto-select and hide dropdown
			selectedProfessional = professionals[0];
			professionalSelection.classList.add('hidden');
			updateCalEmbed(selectedProfessional.cal_url);
		} else {
			// Multiple professionals - show dropdown
			professionals.forEach(prof => {
				const option = document.createElement('option');
				option.value = prof.id;
				option.textContent = `${prof.first_name} ${prof.last_name}`;
				option.dataset.calUrl = prof.cal_url;
				professionalSelect.appendChild(option);
			});
			professionalSelection.classList.remove('hidden');
		}
	}
	
	// Update Cal.com embed with selected professional's calendar
	function updateCalEmbed(calUrl) {
		if (!calUrl) return;
		
		// Extract the Cal.com username/link from the full URL
		// Format: https://cal.com/username/event-type or just username/event-type
		let calLink = calUrl;
		if (calUrl.includes('cal.com/')) {
			calLink = calUrl.split('cal.com/')[1];
		}
		
		// Re-initialize Cal.com with new link
		if (window.Cal && window.Cal.ns && window.Cal.ns["brush-learn"]) {
			Cal.ns["brush-learn"]("inline", {
				elementOrSelector:"#my-cal-inline-brush-learn",
				config: {
					"layout":"month_view",
					"useSlotsViewOnSmallScreen":"true",
					"name": currentUser.first_name + ' ' + currentUser.last_name,
					"email": currentUser.email
				},
				calLink: calLink,
			});
		}
	}
	
	// Professional selection change handler
	professionalSelect.addEventListener('change', function() {
		const selectedOption = this.options[this.selectedIndex];
		if (selectedOption.value) {
			const profId = parseInt(selectedOption.value);
			const locationId = selectedLocation ? selectedLocation.id : null;
			const professionals = professionalsByLocation[locationId] || [];
			selectedProfessional = professionals.find(p => p.id === profId);
			
			if (selectedProfessional && selectedProfessional.cal_url) {
				updateCalEmbed(selectedProfessional.cal_url);
			}
		}
	});
	
	// Step 2 -> Step 3 (Questions -> Date & Time)
	btnQuestionsVirtualNext.addEventListener('click', async function() {
		if (!validateRequiredQuestions(stepQuestionsVirtual)) {
			return;
		}
		
		// Save virtual form questions (form_id = 1)
		formId = 1;
		const saved = await saveQuestions(stepQuestionsVirtual, formId);
		
		if (!saved) {
			alert('Failed to save your answers. Please try again.');
			return;
		}
		
		stepQuestionsVirtual.classList.add('hidden');
		stepDatetime.classList.remove('hidden');
		
		// Populate professionals for selected location
		if (selectedLocation) {
			populateProfessionals(selectedLocation.id);
		}
		
		updateStepIndicator('datetime');
		lucide.createIcons();
	});
	
	btnQuestionsInpersonNext.addEventListener('click', async function() {
		if (!validateRequiredQuestions(stepQuestionsInperson)) {
			return;
		}
		
		// Save in-person form questions (form_id = 2)
		formId = 2;
		const saved = await saveQuestions(stepQuestionsInperson, formId);
		
		if (!saved) {
			alert('Failed to save your answers. Please try again.');
			return;
		}
		
		stepQuestionsInperson.classList.add('hidden');
		stepDatetime.classList.remove('hidden');
		
		// Populate professionals for selected location
		if (selectedLocation) {
			populateProfessionals(selectedLocation.id);
		}
		
		updateStepIndicator('datetime');
		lucide.createIcons();
	});
	
	// Step 4 -> Step 3 (Date & Time -> Questions)
	btnDatetimeBack.addEventListener('click', function() {
		stepDatetime.classList.add('hidden');
		
		if (isVirtual) {
			stepQuestionsVirtual.classList.remove('hidden');
		} else {
			stepQuestionsInperson.classList.remove('hidden');
		}
		
		updateStepIndicator('questions');
		lucide.createIcons();
	});
	
	
	// Initialize with profile step active
	updateStepIndicator('profile');
	
	// Disable location next button initially
	btnLocationNext.disabled = true;
	btnLocationNext.classList.add('opacity-50', 'cursor-not-allowed');
	
	// Load existing booking data if available
	loadExistingBooking();
});
</script>
</div>
</body>
</html>
