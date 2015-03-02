<?php
/**
 * Contains all the action that PSearch currently supports.
 *
 * @author Chadi Wehbe.
 */

include ('db.php');

// ** Control variables.

// Sets which table to use to generate the keywords.
$keywordsTable = 'products';
// Sets which column to use to generate the keywords.
$keywordsColumn = 'name';

// ** End of control variables section.

class psearch extends db {
	
	/**
	 * Creates the necessary tables for DSearch to operate.
	 * 
	 * @return boolean as success flag.
	 */
	function createDsearchTables(){
		// CREATE TABLE `tsearchdb`.`dbsearchkeywords` ( `id` INT NOT NULL AUTO_INCREMENT , `word` VARCHAR(20) NOT NULL , `sourcetable` VARCHAR(20) NOT NULL , `sourcecolumn` VARCHAR(20) NOT NULL , `occurence` INT NOT NULL , `isfirstword` TINYINT NOT NULL , `sentencevalue` INT NOT NULL , `calculatedweight` INT NOT NULL , PRIMARY KEY (`id`) , INDEX (`calculatedweight`) , FULLTEXT (`word`) ) ENGINE = MyISAM;
	}

	/**
	 * Generates a list of keywords based on the preset db column. The list
	 * needs to be updated on every change in that column.
	 *
	 * @return boolean as success flag.
	 */
	function generateKeywordsList() {
		global $keywordsTable;
		global $keywordsColumn;
		$db = new db;

		// Get a list of the items to generate the list from.
		$items = $db -> q('SELECT `' . $keywordsColumn . '` FROM `' . $keywordsTable . '`');

		// Parse the item names into an array of distinct values.
		$keywordsArr = array();
		foreach ($items as $item) {

			// Replace all special characters by spaces (allow dashes for composite words).
			$nameStr = preg_replace('/[^A-Za-z0-9\-]/', ' ', $item[$keywordsColumn]);

			// Create a temporary array of each word in the name.
			$tmpNameArr = explode(' ', $nameStr);

			// Loop through every word in the name.
			foreach ($tmpNameArr as $word) {
				// Skip the strings that are too short.
				if (strlen($word) >= $minCharCount) {
					$word = strtolower($word);

					// Add the string if it's not already added.
					if (!in_array($word, $keywordsArr)) {
						$keywordsArr[] = $word;
					}
				}
			}
		}

		// Create a CSV form of the list to save in the db.
		$keywordsCSV = implode(',', $keywordsArr);

		// Save to the db.
		if ($db -> q('INSERT INTO `productswordlist` (wordlist) VALUES (?)', array(&$keywordsCSV))) {
			return true;
		} else {
			return false;
		}
	}

}
?>
