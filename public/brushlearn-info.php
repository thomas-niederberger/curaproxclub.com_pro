<?php
require_once __DIR__ . '/../config/config.php';
$pdo = getDbConnection();
$booking = null;
$location = null;
$formResponse = null;
if ($currentProfile) {
	$stmt = $pdo->prepare('
		SELECT b.*, l.city, l.state, l.is_virtual, fr.answers, fr.form_id
		FROM ohc_booking b
		LEFT JOIN ohc_location l ON b.location_id = l.id
		LEFT JOIN form_response fr ON b.form_response_id = fr.id
		WHERE b.profile_id = ?
		AND b.status IN ("draft", "booked")
		ORDER BY b.created_at DESC
		LIMIT 1
	');
	$stmt->execute([$currentProfile['id']]);
	$booking = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if ($booking && $booking['answers']) {
		$booking['answers'] = json_decode($booking['answers'], true);
	}
}
$hasBooking = !empty($booking);
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
<section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
	<div class="lg:col-span-2">
		<div class="<?= $theme->getContentClasses() ?>">
			<?= $pageDescription ?>
		</div>
		<div>
			<?php if (!$hasBooking): ?>
				<a href="/brushlearn-book.php" class="mt-6 inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors"><i data-lucide="calendar" class="w-4 h-4 stroke-[2px]"></i> Book your Brush & Learn</a>
			<?php endif; ?>
		</div>
	</div>
	<div class="lg:col-span-1">
		<div class="bg-gray-700 dark:bg-gray-700 rounded-lg p-6 sticky top-24">
			<h3 class="mb-4 text-xl text-gray-400 dark:text-gray-400">Your Booking</h3>
			
			<?php if ($hasBooking): ?>
				<!-- Booking Details -->
				<div class="space-y-4">
					<?php if ($booking['status'] === 'draft'): ?>
						<!-- Draft Status - In Progress -->
						<div class="mb-4">
							<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-500/20 text-yellow-400">
								<i data-lucide="clock" class="w-3 h-3 mr-1"></i> Booking in progress
							</span>
						</div>
						<div class="p-4 bg-gray-600 rounded-lg">
							<p class="text-sm text-gray-400 mb-3">Your booking is not yet complete. Continue where you left off:</p>
							<a href="/brushlearn-book.php" class="w-full inline-flex items-center justify-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors">
								<i data-lucide="edit" class="w-4 h-4 stroke-[2px]"></i> Continue Booking
							</a>
						</div>
					<?php endif; ?>
					
					<?php if ($booking['status'] === 'booked' && $booking['city'] && $booking['state']): ?>
						<div>
							<label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Location</label>
							<p class="text-gray-400">
								<?= htmlspecialchars($booking['city']) ?>, <?= htmlspecialchars($booking['state']) ?>
								<?php if ($booking['is_virtual']): ?>
									<span class="text-sm text-gray-400">(Virtual)</span>
								<?php endif; ?>
							</p>
						</div>
					<?php endif; ?>
					
					<?php if ($booking['status'] === 'booked' && $booking['booking_date']): ?>
						<div>
							<label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Date & Time</label>
							<p class="text-gray-400">
								<?php
								$date = new DateTime($booking['booking_date']);
								$date->setTimezone(new DateTimeZone('America/Los_Angeles')); // Adjust to user's timezone
								echo $date->format('F j, Y \a\t g:i A T');
								?>
							</p>
						</div>
					<?php endif; ?>
					
					<?php if ($booking['status'] === 'booked'): ?>
						<div>
							<label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Status</label>
							<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400">
								<i data-lucide="check-circle" class="w-3 h-3 mr-1"></i> Confirmed
							</span>
						</div>
					<?php endif; ?>
				</div>

				<?php if ($booking['status'] === 'booked'): ?>
					<!-- Action Buttons for Confirmed Bookings -->
					<div class="mt-6 pt-6 border-t border-gray-600">
						<p class="text-xs text-gray-400 mb-3">Need to make changes?</p>
						<a href="mailto:support@curaden.us?subject=Brush%20%26%20Learn%20Booking%20Change&body=Booking%20ID:%20<?= urlencode($booking['cal_booking_id'] ?? $booking['id']) ?>" 
						   class="w-full inline-flex items-center justify-center px-4 gap-2 py-2 bg-gray-600 hover:bg-gray-500 text-gray-400 font-medium rounded-full transition-colors">
							<i data-lucide="mail" class="w-4 h-4 stroke-[2px]"></i> Contact Support
						</a>
					</div>
				<?php endif; ?>
			<?php else: ?>
				<!-- No Booking State -->
				<div class="flex items-center gap-4">
					<div class="w-16 h-16 flex-shrink-0 rounded-full bg-gray-600 flex items-center justify-center">
						<i data-lucide="calendar-x" class="w-8 h-8 text-gray-400"></i>
					</div>
					<div class="flex-1">
						<p class="text-gray-400">No booking available</p>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>

</div>
</main>
<?php include 'partials/footer.php'; ?>
</div>
</body>
</html>
