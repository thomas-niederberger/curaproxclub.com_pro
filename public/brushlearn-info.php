<?php
require_once __DIR__ . '/partials/config.php';
require_once __DIR__ . '/api/functions.php';
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

<div class="prose prose-gray max-w-none 
            prose-h1:text-gray-400 prose-h1:text-4xl prose-h1:md:text-5xl prose-h1:xl:text-6xl prose-h1:mb-6 prose-h1:font-normal
            prose-p:text-gray-400 prose-p:md:text-lg prose-p:leading-relaxed prose-p:mb-2 prose-p:mt-0
            prose-li:text-gray-400 prose-li:md:text-lg prose-li:leading-relaxed prose-li:mb-1 prose-li:mt-0 prose-ul:mb-2 prose-ul:mt-0
            prose-headings:text-gray-400 prose-headings:font-bold prose-headings:mb-4
			prose-strong:text-gray-400
            marker:text-gray-400 dark:prose-invert
			prose-a:no-underline prose-a:hover:no-underline
			mb-8 max-w-4xl w-full lg:w-5/8">
    <h1>Book a Brush & Learn</h1>
    <p>Taking good care of one's oral health has a direct impact on how patients feel—both physically and mentally. When patients are empowered through education, they are better equipped to maintain lifelong oral health. Every patient deserves access to the highest standard of education, regardless of age.</p>
    <p>Through our <strong>Curaprox Brush & Learn Educator Program</strong>, we provide hands-on learning experiences designed to elevate patient care and strengthen your team's confidence.</p>
    <p>During this session, your team will:</p>
    <ul>
        <li><strong>Master Curaprox's systems-based approach to dentistry</strong> and confidently choose the right products for every patient</li>
        <li><strong>Explore evidence-based solutions for biofilm-related conditions</strong>, including individualized mechanical biofilm control strategies</li>
        <li><strong>Level up interdental cleaning techniques</strong> and learn why they're essential for long-term oral health success</li>
        <li><strong>Boost patient engagement and compliance</strong> using practical education strategies that strengthen trust and improve outcomes</li>
        <li><strong>Walk away empowered</strong> with easy-to-teach techniques you can confidently share chairside to enhance both patient satisfaction and provider fulfillment</li>
    </ul>
    <p>This session provides your entire team with hands-on guidance and helps align your patient education approach.</p>
</div>

<nav aria-label="Progress" class="mx-auto py-4 mb-8 bg-gray-700 dark:bg-gray-700 p-6 rounded-lg">
	<ol role="list" class="space-y-4 md:flex md:space-y-0 md:space-x-8">
		
		<li class="md:flex-1">
			<a href="#" class="group flex flex-col border-l-4 border-orange py-2 pl-4 md:border-l-0 md:border-t-4 md:pb-0 md:pl-0 md:pt-4">
				<span class="text-xs font-semibold uppercase text-orange group-hover:text-orange/80">Step 1</span>
				<span class="text-sm font-medium dark:text-white">Location</span>
			</a>
		</li>
		<li class="md:flex-1">
			<a href="#" class="group flex flex-col border-l-4 border-gray-200 py-2 pl-4 hover:border-orange md:border-l-0 md:border-t-4 md:pb-0 md:pl-0 md:pt-4">
				<span class="text-xs font-semibold uppercase text-gray-400 group-hover:text-orange/80">Step 2</span>
				<span class="text-sm font-medium text-gray-400 group-hover:text-orange/80">Application</span>
			</a>
		</li>
		<li class="md:flex-1">
			<a href="#" class="group flex flex-col border-l-4 border-gray-200 py-2 pl-4 hover:border-orange md:border-l-0 md:border-t-4 md:pb-0 md:pl-0 md:pt-4">
				<span class="text-xs font-semibold uppercase text-gray-400 group-hover:text-orange/80">Step 3</span>
				<span class="text-sm font-medium text-gray-400 group-hover:text-orange/80">Questions</span>
			</a>
		</li>
		<li class="md:flex-1">
			<a href="#" class="group flex flex-col border-l-4 border-gray-200 py-2 pl-4 hover:border-orange md:border-l-0 md:border-t-4 md:pb-0 md:pl-0 md:pt-4">
				<span class="text-xs font-semibold uppercase text-gray-400 group-hover:text-orange/80">Step 4</span>
				<span class="text-sm font-medium text-gray-400 group-hover:text-orange/80">Confirmation</span>
			</a>
		</li>
	</ol>
</nav>

</div>
</main>

<?php include 'partials/footer.php'; ?>
</div>
</body>
</html>
