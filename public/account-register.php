<?php
$error = null;
$success = null;

$usStates = [
    'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
    'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia',
    'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
    'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
    'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri',
    'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
    'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
    'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
    'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
    'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/account-ratelimit.php';
    require_once __DIR__ . '/../config/account-emailtoken.php';
    
    // Rate limiting: 5 requests per 60 seconds
    if (!checkRateLimit('register', 5, 60)) {
        http_response_code(429);
        header('Retry-After: 60');
        $error = 'Too many requests. Please wait a moment and try again.';
    } else {
    
    $pdo = getDbConnection();
    
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $licenceNo = trim($_POST['licence_number'] ?? '');
    $licenceState = trim($_POST['licence_state'] ?? '');
    
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $error = 'First name, last name, and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM profile WHERE email = ?');
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists. Please <a href="account-login.php" class="text-blue-400 hover:underline">log in</a> instead.';
        } else {
            try {
                // Use NULL for empty licence fields
                $licenceNoValue = !empty($licenceNo) ? $licenceNo : null;
                $licenceStateValue = !empty($licenceState) ? $licenceState : null;
                
                $stmt = $pdo->prepare('INSERT INTO profile (first_name, last_name, email, licence_number, licence_state, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
                $stmt->execute([$firstName, $lastName, $email, $licenceNoValue, $licenceStateValue]);
                
                $profileId = $pdo->lastInsertId();
                
                $token = bin2hex(random_bytes(32));
                $expiresAt = gmdate('Y-m-d H:i:s', strtotime('+15 minutes'));
                
                $stmt = $pdo->prepare('INSERT INTO profile_token (profile_id, token, expires_at, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)');
                $stmt->execute([$profileId, $token, $expiresAt]);
                
                $magicLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/account-verify.php?token=' . $token;
                
                if (sendMagicLinkEmail($email, $firstName . ' ' . $lastName, $magicLink)) {
                    $success = '<strong>Registration successful!</strong><br>Please check your email for your login link.';
                } else {
                    $error = 'Registration successful, but we couldn\'t send the login email.<br>Please contact support@curaden.us.';
                }
            } catch (Exception $e) {
                error_log('Registration error: ' . $e->getMessage());
                $error = 'An error occurred. Please try again or contact support@curaden.us.';
            }
        }
    }
    }
}

// Force dark theme for registration page
$userTheme = 'dark';
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<?php include 'partials/meta.php'; ?>
<body class="bg-gray-900 dark:bg-gray-900 flex items-center justify-center min-h-screen">

	<div class="w-full max-auto max-w-md bg-gray-800 border border-gray-700 p-8 rounded-2xl shadow-xl">
		<div class="flex flex-col items-center mb-8">
			<div class="flex items-center justify-center mb-4">
				<img src="/assets/img/curaprox.svg" class="h-10" alt="CURAPROX" />
			</div>
		</div>
		
		<?php if ($error): ?>
			<div class="mb-6 p-4 bg-red-900/50 border border-red-700 rounded-lg text-red-200 text-sm">
				<?= $error ?>
			</div>
		<?php endif; ?>
		
		<?php if ($success): ?>
			<div class="mb-6 p-4 bg-green-900/50 border border-green-700 rounded-lg text-green-200 text-sm">
				<?= $success ?>
			</div>
			<div class="text-center">
				<a href="account-login.php" class="text-blue-400 hover:underline text-sm">Go to login</a>
			</div>
		<?php else: ?>
		
		<form method="POST" class="space-y-4">
			<h1 class="text-xl text-white mb-6">The Portal <span class="font-bold">REGISTER</span></h1>
			
			<div>
				<label class="block text-xs font-medium text-gray-400 uppercase mb-2">First Name <span class="text-red-500">*</span></label>
				<input type="text" name="first_name" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" placeholder="">
			</div>
			
			<div>
				<label class="block text-xs font-medium text-gray-400 uppercase mb-2">Last Name <span class="text-red-500">*</span></label>
				<input type="text" name="last_name" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" placeholder="">
			</div>
			
			<div>
				<label class="block text-xs font-medium text-gray-400 uppercase mb-2">Email Address <span class="text-red-500">*</span></label>
				<input type="email" name="email" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="">
			</div>
			
			<div>
				<label class="block text-xs font-medium text-gray-400 uppercase mb-2">Licence Number <span class="text-red-500">*</span></label>
				<input type="text" name="licence_number" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all" value="<?= htmlspecialchars($_POST['licence_number'] ?? '') ?>" placeholder="">
			</div>
			
			<div>
				<label class="block text-xs font-medium text-gray-400 uppercase mb-2">Licence State <span class="text-red-500">*</span></label>
				<select name="licence_state" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all">
					<option value="">Select state</option>
					<?php foreach ($usStates as $code => $name): ?>
						<option value="<?= $code ?>" <?= (($_POST['licence_state'] ?? '') === $code) ? 'selected' : '' ?>><?= $name ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			
			<button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-lg transition-colors mt-6">
				Create Account
			</button>
			
			<p class="text-center text-sm text-gray-400 mt-4">
				Already have an account? <a href="account-login.php" class="text-blue-400 hover:underline">Log in here</a>
			</p>
		</form>
		
		<?php endif; ?>
	</div>

<?php include 'partials/footer.php'; ?>
</body>
</html>
