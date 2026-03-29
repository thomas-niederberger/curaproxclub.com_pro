<head>
	<script>
	// Apply theme from database (set by PHP in config.php)
	<?php if (isset($userTheme) && $userTheme === 'light'): ?>
	document.documentElement.classList.remove('dark');
	<?php else: ?>
	document.documentElement.classList.add('dark');
	<?php endif; ?>
	</script>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>CURAPROX USA - The Portal</title>
	<meta name="robots" content="noindex, nofollow" />
	<meta name="format-detection" content="telephone=no">
	<link rel="icon" type="image/png" href="/assets/img/favicon.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon.png">
	<link rel="stylesheet" href="/assets/css/tailwind.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
</head>