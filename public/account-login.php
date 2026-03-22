<?php
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/partials/config.php';
    require_once __DIR__ . '/partials/account-emailtoken.php';
    
    $pdo = getDbConnection();
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare('SELECT id, first_name, last_name FROM profile WHERE email = ?');
        $stmt->execute([$email]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($profile) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = gmdate('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            $stmt = $pdo->prepare('INSERT INTO profile_token (profile_id, token, expires_at, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)');
            $stmt->execute([$profile['id'], $token, $expiresAt]);
            
            $magicLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/account-verify.php?token=' . $token;
            
            if (sendMagicLinkEmail($email, $profile['first_name'] . ' ' . $profile['last_name'], $magicLink)) {
                $success = 'Check your email! We\'ve sent you a secure login link that will expire in 15 minutes.';
            } else {
                $error = 'We couldn\'t send the login email. <br>Please try again or contact support@curaden.us.';
            }
        } else {
            $error = 'No account found with this email address.<br>Please <a href="account-register.php" class="text-blue-400 hover:underline">create an account</a> first.';
        }
    }
}

// Force dark theme for login page
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
		<?php endif; ?>
		
		<form method="POST" class="space-y-6">
			<h1 class="text-xl text-white">The Portal <span class="font-bold">LOGIN</span></h1>
			<div>
				<label class="block text-xs font-medium text-gray-400 uppercase mb-2">Email Address</label>
				<input type="email" name="email" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="">
			</div>
			<button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-lg transition-colors cursor-pointer">
				Send Login Link
			</button>
			<p class="text-center text-sm text-gray-400">
				Don't have an account? <a href="account-register.php" class="text-blue-400 hover:underline">Sign up here</a>
			</p>
		</form>
		
		<div class="mt-6 p-4 bg-gray-700/50 rounded-lg">
			<p class="text-xs text-gray-400">We'll send you a secure link to log in. No password needed!</p>
		</div>
	</div>

<?php include 'partials/footer.php'; ?>
</body>
</html> 