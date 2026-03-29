<?php
require_once __DIR__ . '/../config/config.php';

$pdo = getDbConnection();

$bookings = $pdo->query('
	SELECT
		b.id,
		b.status,
		b.is_virtual,
		b.booking_date,
		b.cal_booking_id,
		b.hubspot_meeting_id,
		b.notes,
		b.created_at,
		b.updated_at,
		p.first_name,
		p.last_name,
		p.email,
		p.id_hubspot_b2b_contact,
		p.id_hubspot_b2b_company,
		l.city,
		l.state,
		l.is_virtual AS location_is_virtual
	FROM ohc_booking b
	LEFT JOIN profile p ON b.profile_id = p.id
	LEFT JOIN ohc_location l ON b.location_id = l.id
	ORDER BY b.created_at DESC
')->fetchAll(PDO::FETCH_ASSOC);

$totalBookings = count($bookings);
$totalBooked   = count(array_filter($bookings, fn($b) => $b['status'] === 'booked'));
$totalDraft    = count(array_filter($bookings, fn($b) => $b['status'] === 'draft'));
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

<section class="w-full flex justify-between items-end gap-6 mb-8">
	<div class="<?= $theme->getHeaderClasses() ?> flex-1">
		<h1><?= htmlspecialchars($pageHeader) ?></h1>
	</div>
</section>

<div class="grid grid-cols-3 gap-4 mb-8">
	<div class="bg-gray-700 rounded-lg p-5">
		<p class="text-xs font-semibold uppercase text-gray-400 mb-1">Total</p>
		<p class="text-3xl font-bold text-white"><?= $totalBookings ?></p>
	</div>
	<div class="bg-gray-700 rounded-lg p-5">
		<p class="text-xs font-semibold uppercase text-gray-400 mb-1">Booked</p>
		<p class="text-3xl font-bold text-green-400"><?= $totalBooked ?></p>
	</div>
	<div class="bg-gray-700 rounded-lg p-5">
		<p class="text-xs font-semibold uppercase text-gray-400 mb-1">Draft</p>
		<p class="text-3xl font-bold text-yellow-400"><?= $totalDraft ?></p>
	</div>
</div>

<div class="bg-gray-700 dark:bg-gray-700 rounded-lg overflow-hidden">
	<div class="overflow-x-auto">
		<table class="w-full">
			<thead class="bg-gray-600">
				<tr>
					<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">Profile</th>
					<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">Location</th>
					<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">Type</th>
					<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">Status</th>
					<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">Booking Date</th>
					<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">Cal Booking</th>
					<th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-400">HubSpot</th>
				</tr>
			</thead>
			<tbody class="divide-y divide-gray-600">
				<?php if (empty($bookings)): ?>
				<tr>
					<td colspan="11" class="px-4 py-10 text-center text-gray-400">No bookings found.</td>
				</tr>
				<?php else: ?>
					<?php foreach ($bookings as $b): ?>
					<tr class="hover:bg-gray-600/50 transition-colors">
						<td class="px-4 py-3 text-sm text-gray-400">
							<p><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></p>
							<p class="text-xs"><?= htmlspecialchars($b['email'] ?? '') ?></p>
						</td>
						<td class="px-4 py-3 text-sm text-gray-400">
							<?php if ($b['city']): ?>
								<?= htmlspecialchars($b['city']) ?>, <?= htmlspecialchars($b['state']) ?>
							<?php else: ?>
								<span class="text-gray-600">—</span>
							<?php endif; ?>
						</td>
						<td class="px-4 py-3">
							<?php if ($b['is_virtual']): ?>
								<span class="px-2 py-1 text-xs bg-blue-500/20 text-blue-400 rounded-full">Virtual</span>
							<?php else: ?>
								<span class="px-2 py-1 text-xs bg-purple-500/20 text-purple-400 rounded-full">In-Person</span>
							<?php endif; ?>
						</td>
						<td class="px-4 py-3">
							<?php if ($b['status'] === 'booked'): ?>
								<span class="px-2 py-1 text-xs bg-green-500/20 text-green-400 rounded-full font-medium">Booked</span>
							<?php else: ?>
								<span class="px-2 py-1 text-xs bg-yellow-500/20 text-yellow-400 rounded-full font-medium">Draft</span>
							<?php endif; ?>
						</td>
						<td class="px-4 py-3 text-sm text-gray-400">
							<?= $b['booking_date'] ? htmlspecialchars(date('M j, Y g:i A', strtotime($b['booking_date']))) : '<span class="text-gray-600">—</span>' ?>
						</td>
						<td class="px-4 py-3 text-sm text-gray-400 font-mono">
							<?php if ($b['cal_booking_id']): ?>
								<span class="text-xs bg-gray-600 px-2 py-1 rounded" title="<?= htmlspecialchars($b['cal_booking_id']) ?>">
									<a href="https://cal.com/curaprox/ohc-booking?booking=<?= htmlspecialchars($b['cal_booking_id']) ?>" target="_blank"><?= htmlspecialchars(substr($b['cal_booking_id'], 0, 12)) ?>…</a>
								</span>
							<?php else: ?>
								<span class="text-gray-600">—</span>
							<?php endif; ?>
						</td>
						<td class="px-4 py-3">
							<div class="flex gap-2">
								<?php if ($b['hubspot_meeting_id']): ?>
								<a href="https://app-eu1.hubspot.com/contacts/27229630/record/0-1/<?= htmlspecialchars($b['id_hubspot_b2b_contact']) ?>/view/1?engagement=<?= htmlspecialchars($b['hubspot_meeting_id']) ?>&type=MEETING" target="_blank" class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-lg bg-gray-500 text-gray-400 hover:bg-orange hover:text-white transition-colors">Meeting</a>
								<?php endif; ?>
								<?php if ($b['id_hubspot_b2b_contact']): ?>
								<a href="https://app-eu1.hubspot.com/contacts/27229630/record/0-1/<?= htmlspecialchars($b['id_hubspot_b2b_contact']) ?>" target="_blank" class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-lg bg-gray-500 text-gray-400 hover:bg-orange hover:text-white transition-colors">Contact</a>
								<?php endif; ?>
								<?php if ($b['id_hubspot_b2b_company']): ?>
								<a href="https://app-eu1.hubspot.com/contacts/27229630/record/0-2/<?= htmlspecialchars($b['id_hubspot_b2b_company']) ?>" target="_blank" class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-lg bg-gray-500 text-gray-400 hover:bg-orange hover:text-white transition-colors">Company</a>
								<?php endif; ?>
								<?php if (!$b['id_hubspot_b2b_contact'] && !$b['id_hubspot_b2b_company']): ?>
								<span class="text-gray-600">—</span>
								<?php endif; ?>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

</div>
</main>
</div>
<?php include 'partials/footer.php'; ?>
</body>
</html>
