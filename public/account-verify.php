<?php
$error = null;

if (!isset($_GET['token'])) {
$error = 'Invalid login link.';
} else {
require_once __DIR__ . '/../config/config.php';

$pdo = getDbConnection();
$token = $_GET['token'];

$stmt = $pdo->prepare('SELECT lt.*, p.id as profile_id, p.first_name, p.last_name, p.email, p.login_last
						FROM profile_token lt 
						JOIN profile p ON lt.profile_id = p.id 
						WHERE lt.token = ? AND lt.used = 0 AND lt.expires_at > NOW()');
$stmt->execute([$token]);
$loginToken = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$loginToken) {
	$error = 'This login link is invalid or has expired. Please request a new one.';
} else {
	session_start();
	
	$stmt = $pdo->prepare('UPDATE profile_token SET used = 1 WHERE id = ?');
	$stmt->execute([$loginToken['id']]);
	
	$sessionToken = bin2hex(random_bytes(32));
	$sessionExpires = gmdate('Y-m-d H:i:s', strtotime('+30 days'));
	
	$stmt = $pdo->prepare('UPDATE profile SET session_token = ?, session_expires = ?, login_last = CURRENT_TIMESTAMP WHERE id = ?');
	$stmt->execute([$sessionToken, $sessionExpires, $loginToken['profile_id']]);
	
	$_SESSION['profile_id'] = $loginToken['profile_id'];
	$_SESSION['session_token'] = $sessionToken;
	$_SESSION['email'] = $loginToken['email'];
	$_SESSION['first_name'] = $loginToken['first_name'];
	$_SESSION['last_name'] = $loginToken['last_name'];
	
	setcookie('session_token', $sessionToken, [
		'expires' => strtotime('+30 days'),
		'path' => '/',
		'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
		'httponly' => true,
		'samesite' => 'Lax'
	]);
	
	checkAndStoreExternalIds($pdo, $loginToken['profile_id'], $loginToken['email']);
	
	//$isFirstLogin = empty($loginToken['login_last']);
	//if ($isFirstLogin) {
		//header('Location: profile-edit.php');
	//} else {
		header('Location: index.php');
	//}
	exit;
}
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'partials/meta.php'; ?>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">

	<div class="w-full max-auto max-w-md bg-gray-800 border border-gray-700 p-8 rounded-2xl shadow-xl">
		<div class="flex flex-col items-center mb-8">
			<div class="flex items-center justify-center mb-4">
				<img src="/assets/img/curaprox.svg" class="h-10" alt="CURAPROX" />
			</div>
		</div>
		
		<?php if ($error): ?>
			<h1 class="text-xl text-white mb-6">The Portal <span class="font-bold">REGISTER</span></h1>
			<div class="mb-6 p-4 bg-red-900/50 border border-red-700 rounded-lg text-red-200 text-sm">
				<?= $error ?>
			</div>
			<a href="account-login.php" class="inline-block text-center w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-lg transition-colors">
				Request New Login Link
			</a>
		<?php else: ?>
			<div class="mb-6">
				<div class="inline-block animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-blue-500"></div>
			</div>
			<h1 class="text-xl text-white mb-6">The Portal <span class="font-bold">LOGIN</span></h1>
			<p class="text-gray-400">Please wait while we log you in.</p>
		<?php endif; ?>
	</div>

<?php include 'partials/footer.php'; ?>
</body>
</html>
