<?php

class SimpleRequirements {
	
	/**
	 * default css files
	 *
	 * @config()
	 * @var array
	 */
	private static $default_css = array();
	
	/**
	 * default javascript files
	 *
	 * @config
	 * @var array
	 */
	private static $default_javascript = array();
	
	/**
	 * default css folder
	 *
	 * @config()
	 * @var string
	 */
	private static $folder_name_css = 'css';
	
	/**
	 * default javascript folder
	 *
	 * @config()
	 * @var string
	 */
	private static $folder_name_javascript = 'js';

	/**
	 * minify css files on ?flush=all
	 * remove all whitespaces, tabs and comments from css files
	 *
	 * @return boolean
	 */
	private static $minify_css_on_flush = true;
	
	/**
	 * css wrapper for {@link addFiles()}
	 */
	public static function css($files, $outputName = '') {
		self::addFiles('css', $files, $outputName);
	}
	
	/**
	 * javascript wrapper for {@link addFiles()}
	 */
	public static function javascript($files, $outputName = '') {
		self::addFiles('js', $files, $outputName);
	}
	
	/**
	 * returns the url to the current theme
	 *
	 * @return string
	 */
	public static function current_theme_path() {
		return 'themes/'.SSViewer::current_theme();
	}
	
	/**
	 * returns the folder name by type (css/js)
	 * you can change the name with {@link $folder_name_css} and {@link $folder_name_javascript}
	 *
	 * @return string
	 */
	private static function folder_name($type) {
		$folderName = '';
		
		if ('css' == $type) {
			$folderName = self::config()->get('folder_name_css');
		}
		else if ('js' == $type) {
			$folderName = self::config()->get('folder_name_javascript');
		}
		
		return $folderName;
	}
	
	
	/**
	 * combines the requirements into a single file. 
	 * You can set the name of the combined file with the second argument. 
	 * Otherwise the new filename will be generated by using the source file names.
	 *
	 * filenames are relative to your 'theme/filetype' directory {@link folder_name()}
	 * If you want to add a file that doesn't exist in your theme directory just add trailing slash
	 * at the beginning of the path and it is relative to the root directory.
	 *
	 * No output name given. The generated name is: filename1-filename2.min.css
	 * SimpleRequirements::css(array(
	 *	'filename1.css',
	 *	'filename2.css'
	 * ));
	 *
	 * The files are combined in the file video.min.css
	 * SimpleRequirements::css(array(
	 *	'videoPage.css',
	 *	'videoPlayer.css'
	 * ), 'video');
	 * 
	 * {@link css()}
	 * {@link javascript()}
	 *
	 * @param string $type 
	 *	- 'css'
	 *	- 'javascript'
	 * @param array $files 
	 * @param string $outputName
	 */
	private static function addFiles($type, $files = '', $name = '') {
		
		$tmp = array();
		$request = Controller::curr()->getRequest();
		$folder_name = self::folder_name($type);
		
		if (!is_array($files) && $files = 'default') {
			if ($type == 'css') {
				$files = self::$default_css;
			}
			else if ($type == 'js') {
				$files = self::config()->get('default_javascript');
			}
		}
		
		// add theme path
		foreach ($files as $file) {
			$tmp[] = 0 !== strpos($file, '/') ? self::current_theme_path() . '/' . $folder_name . '/' . $file : $file;
		}
		
		// replace filenames
		$files = $tmp;
		
		// create name
		if ($name == '' || !is_string($name)) {
			$combine_fileNames = array();
			foreach ($files as $file) {
			
				$lastSlashPos = strrpos($file, '/') + 1;
				$combine_fileNames[] = substr( $file, $lastSlashPos, strrpos($file, '.') - $lastSlashPos );
			}
			
			$name = implode('-', $combine_fileNames);
		}
		
		if ($type == 'css') {
			foreach ($files as $file) {
			    Requirements::css($file);
			}
		}
		else if($type == 'js') {
			foreach ($files as $file) {
			    Requirements::javascript($file);
			}
		}
		
		Requirements::combine_files(
		    "$name.min.$type",
		    $files
		);
		
		Requirements::process_combined_files();
		
		// minify css files the quick way
		if (self::config()->get('minify_css_on_flush') && isset($request) && $request->getVar('flush') == 'all' && $type == 'css') {
		
			$file = Director::baseFolder() . '/' . Requirements::backend()->getCombinedFilesFolder() . '/' . "$name.min.$type";
			
			if (file_exists($file)) {
				$bf = file_get_contents($file);
				$bf = preg_replace("!/\*[^*]*\*+([^/][^*]*\*+)*/!", '', $bf);
				$bf = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $bf);
				$fh = fopen($file, 'w');
				fwrite($fh, $bf);
				fclose($fh);
			}
		}	
	}
}