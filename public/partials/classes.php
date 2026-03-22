<?php
/**
 * Theme and Style Management for Curaprox Portal
 */
class CuraproxTheme {
    public $brandColor;

    public function __construct($color = 'orange') {
        // This is where you'll eventually pull the user's preference from the DB
        $this->brandColor = $color; 
    }

    /**
     * Returns the massive Tailwind string for Page Headers
     */
    public function getHeaderClasses() {
        return "prose prose-gray max-w-none 
                prose-h1:text-gray-400 prose-h1:text-4xl prose-h1:md:text-5xl prose-h1:xl:text-6xl 
                prose-h1:mb-8 prose-h1:font-normal";
    }

    /**
     * Returns the full Prose configuration for Main Content
     */
    public function getContentClasses() {
        return "prose prose-gray max-w-none 
                prose-h1:text-gray-400 prose-h1:text-4xl prose-h1:md:text-5xl prose-h1:xl:text-6xl prose-h1:mb-6 prose-h1:font-normal
                prose-p:text-gray-400 prose-p:md:text-lg prose-p:leading-relaxed prose-p:mb-2 prose-p:mt-0
                prose-li:text-gray-400 prose-li:md:text-lg prose-li:leading-relaxed prose-li:mb-1 prose-li:mt-0 prose-ul:mb-2 prose-ul:mt-0
                prose-headings:text-gray-400 prose-headings:font-bold prose-headings:mb-4
                prose-strong:text-gray-400
                marker:text-gray-400 dark:prose-invert
                prose-a:no-underline prose-a:hover:no-underline prose-a:text-orange prose-a:hover:text-gray-400
                mb-8";
    }

    /**
     * Returns a dynamic button class based on brand color
     */
    public function getButtonClasses($extra = '') {
        $color = $this->brandColor;
        return "inline-flex items-center px-4 gap-2 py-2 bg-{$color} hover:bg-{$color}/80 
                text-white font-medium rounded-full transition-colors " . $extra;
    }
}