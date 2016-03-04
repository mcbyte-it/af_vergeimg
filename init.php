<?php

class Af_VergeImg extends Plugin {

	private $host;
	private $dbh;

	function about() {
		return array(0.9,
				"Resize TheVerge images, and disable the gif animations",
				"ramik");
	}

	/*
	function flags() {
		return array("needs_curl" => true);
	}*/

	function init($host) {
		$this->host = $host;
		$this->dbh = Db::get();

		//if (function_exists("curl_init")) {
			$host->add_hook($host::HOOK_ARTICLE_FILTER, $this);
		//}
		$host->add_hook($host::HOOK_PREFS_TAB, $this);
	}
	
	function hook_prefs_tab($args){
		
		if( $args != 'prefPrefs' ) return;

		echo '<div dojoType="dijit.layout.AccordionPane" title="The Verge image fixer">
					<p>'. __('Clicking the button below will apply the fixes to already downloaded and yet unread articles. Note that this can take a long time depending on the number of unread articles. This means that the script may timeout before it finishes applying the fixes. Right now, the only solution in this situation is to increase the time limit in <a href="https://www.google.com/search?q=php+set+time+limit">PHP configuration file</a> or in <a href="https://www.google.com/search?q=.htaccess+php_value+set_time_limit">.htaccess file</a>.') .'</p>
					<button type="button" onClick="return fix_theverge_apply(this)">'. __('Apply fixes to already downloaded articles') .'</button><img src="images/indicator_white.gif" id="fix_theverge_indicator" style="vertical-align: middle; visibility: hidden;"/>
				</div>';
				
		return;
	}
	
	function get_prefs_js() {
		return file_get_contents(__DIR__ . '/init.js');
	}
	
	function fix_theverge_apply(){
		
		$log = array();
		$count = 0;
		
		$result = $this->dbh->query('SELECT `id`, `link`, `content` FROM `ttrss_entries`, `ttrss_user_entries` WHERE `ref_id` = `id` AND `unread` = 1 AND `owner_uid` = ' . $_SESSION['uid']);
		if( $this->dbh->num_rows($result) > 0 ){
			while( $article = $this->dbh->fetch_assoc($result) ){
				$count++;
				
				$article = $this->make_fixes($article);
				//array_push($log, $article["log"]);
				
				$r = $this->dbh->query('UPDATE `ttrss_entries` SET `content` = "'. $this->dbh->escape_string($article['content'], false) .'" WHERE `id` = '.$article['id']);
						
			}
		}
		print json_encode(array(
			"total fixes" => $count
			//"log" => $log
			));
		
	}
	
	function make_fixes($article) {
		if (stripos($article["link"], "theverge.com") !== FALSE) {
			//$article["log"] = $article["log"] . "\nEntered to fix";

			$charset_hack = '<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			</head>';

			$doc = new DOMDocument();
			$doc->loadHTML($charset_hack . $article["content"]);

			$found = false;

			//$article["log"] = $article["log"] . "\nLoaded doc";
			
			if ($doc) {
				$xpath = new DOMXpath($doc);

				$images = $xpath->query('(//img[@src])');
				
				//$article["log"] = $article["log"] . "\ndid query";

				foreach ($images as $img) {
					$src = $img->getAttribute("src");

					if (stripos($src, ".gif") > 0) {
						$img->setAttribute("src", "plugins.local/af_vergeimg/img_stop_gif.php?src=".urlencode($src));
						$found = true;
						//$article["log"] = $article["log"] . "\nFound GIF";
					}
					if (stripos($src, ".jpg") > 0) {
						$img->setAttribute("style", "max-height: 320px;");
						$found = true;
						//$article["log"] = $article["log"] . "\nFound jpg";
					}
				}

				if ($found) {
					$doc->removeChild($doc->firstChild); //remove doctype
					$article["content"] = $doc->saveHTML();
					//$article["log"] = $article["log"] . "\nDone replace";
				}
			}
		}

		return $article;
	}
	
	function hook_article_filter($article) {
		return $article = $this->make_fixes($article);
	}

	function api_version() {
		return 2;
	}
}
?>
