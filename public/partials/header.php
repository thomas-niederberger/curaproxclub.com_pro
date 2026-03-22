<?php
	$headerFullName     = $currentUserName ?: 'User';
	$headerEmailDisplay = htmlspecialchars($currentProfile['email'] ?? '');
	$headerInitials     = $currentUserInitials ?: '??';
	$headerAvatarUrl    = $currentUserAvatar;
?>
<nav class="p-4 z-20 fixed left-0 right-0 top-0 bg-gray-00 dark:bg-gray2-900 border-r border-gray-600 dark:border-gray-600 max-w-[1600px]">
	<div class="flex flex-wrap justify-between items-center">
		<div class="flex items-center">
			<button data-drawer-target="drawer-navigation" data-drawer-toggle="drawer-navigation" aria-controls="drawer-navigation" class="p-2 mr-2 text-gray-600 rounded-lg cursor-pointer md:hidden hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
				<i data-lucide="menu" class="w-4 h-4 stroke-[2px]"></i>
			</button>
			<a href="/" class="flex items-center md:hidden">
				<img src="/assets/img/curaprox.svg" class="h-10" alt="CURAPROX" />
			</a>
			<div class="hidden md:block md:ml-68">
				<a href="shop-consumer.php" class="inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors"><i data-lucide="store" class="w-4 h-4 stroke-[2px]"></i> Shop</a>
			</div>
		</div>
		<div class="flex items-center pr-0 md:pr-4">
			<div class="flex items-center pr-2 md:pr-4">
				<button type="button" id="theme-toggle" role="switch" class="group relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-gray-600 focus:ring-offset-2 bg-gray-800 dark:bg-gray-800">
					<span class="sr-only">Toggle dark mode</span>
					<span id="theme-toggle-knob" class="pointer-events-none relative inline-block size-5 rounded-full bg-white shadow ring-0 transition-transform duration-200 ease-in-out">
						<span id="toggle-icon-moon" class="absolute inset-0 flex items-center justify-center transition-opacity duration-200">
							<i data-lucide="moon" class="w-3 h-3 text-gray-800"></i>
						</span>
						<span id="toggle-icon-sun" class="absolute inset-0 flex items-center justify-center transition-opacity duration-200">
							<i data-lucide="sun" class="w-3 h-3 text-black-400"></i>
						</span>
					</span>
				</button>
			</div>
			<button type="button" class="cursor-pointer flex text-sm rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600" id="user-menu-button" aria-expanded="false" data-dropdown-offset-skidding="-60" data-dropdown-toggle="dropdown">
				<span class="sr-only">Open user menu</span>
				<?php if ($headerAvatarUrl): ?>
				<img class="w-12 h-12 rounded-full object-cover" src="<?= $headerAvatarUrl ?>" alt="<?= $headerFullName ?>" />
				<?php else: ?>
				<div class="w-12 h-12 rounded-full bg-gray-600 flex items-center justify-center text-gray-400 text-sm font-bold">
					<?= $headerInitials ?>
				</div>
				<?php endif; ?>
			</button>
			<div class="hidden z-50 my-4 w-52 text-base list-none bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600 rounded-xl" id="dropdown">
				<div class="py-3 px-4">
					<span class="block text-sm font-semibold text-gray-900 dark:text-gray-400"><?= $headerFullName ?></span>
					<span class="block text-sm text-gray-900 truncate dark:text-gray-400"><?= $headerEmailDisplay ?></span>
				</div>
				<ul class="py-1 text-gray-700 dark:text-gray-300" aria-labelledby="dropdown">
					<li>
						<a href="profile.php" class="flex items-center py-2 px-4 text-sm text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-gray-400" >
						<i data-lucide="user" class="w-4 h-4 stroke-[2px]"></i><span class="flex-1 ml-3 text-left whitespace-nowrap">Profile</span>
						</a>
					</li>
					<li>
						<a href="settings.php" class="flex items-center py-2 px-4 text-sm text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-gray-400" >
						<i data-lucide="settings" class="w-4 h-4 stroke-[2px]"></i><span class="flex-1 ml-3 text-left whitespace-nowrap">Settings</span>
						</a>
					</li>
				</ul>
				<ul class="py-1 text-gray-700 dark:text-gray-300" aria-labelledby="dropdown">
					<li>
						<a href="account-logout.php" class="flex items-center py-2 px-4 text-sm text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-gray-400 rounded-b-lg">
							<i data-lucide="log-out" class="w-4 h-4 stroke-[2px]"></i><span class="flex-1 ml-3 text-left whitespace-nowrap">Sign out</span>
						</a>
					</li>
				</ul>
			</div>
		</div>
	</div>
</nav>