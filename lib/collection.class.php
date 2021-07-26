<?php
/**
 *      Title:          collection.class.php
 *      Author:         Joe Cohen
 *      Contact:        <deskofjoe@gmail.com>
 *      GitHub:         https://github.com/cojohen
 * 
 *      Purpose:        A collection of verses.
 *            
 */
require(__DIR__.'/verse.class.php');

class Collection {

    public $verses = array();
    public $omit_search_terms = array(" a ", " the ", " and ");
    public $search_result_size;
    private $book;
    private $chapter;

    public function __construct($ids = array()) {
        if (count($ids) > 0)
            $this->setByID($ids);
    }

    /**
     * @param int[] ids
     */
    public function setByID($ids) {
        foreach ($ids as $id) {
            $this->verses[] = new Verse($id);
        }
    }

    public function getBook() {
        return $this->book;
    }

    public function getChapter() {
        return $this->chapter;
    }
    /**
     * @param string book
     * @param int chapter
     *   ex. 'revelation' or 'genesis', 14
     */
    public function loadChapter($book = '', $chapter = 1) {
        if ($book AND $chapter) {
            $db = new db();
            // Validate $book is an actual book
            $q = "SELECT books.id, books.book FROM kjv.books WHERE (books.book='$book') LIMIT 1";
            
            if ($db->query($q)->numRows() > 0) { // Book valid
                $row = $db->fetchArray();
                $this->book = $row['book'];
                $this->chapter = $chapter;
            } else { return false;  }           // Not a valid book
            
            // Get verse ids for this book and chapter
            $q = "SELECT text.id AS id FROM kjv.text WHERE text.book=" . $row['id'] . " AND text.chapter=" . $chapter . " ORDER BY text.id LIMIT 180";
            $rows = $db->query($q)->fetchAll();
            
            foreach($rows as $row) { $ids[] = $row['id']; }
            
            $this->setByID($ids);
        }
    }
    /**
     * @param bool breaks
     * 
     * returns string list
     */
    public function toListElements($breaks = FALSE) {
        $list = '';
        foreach ($this->verses as $verse) {
            $list .= $verse->toListItem;
            $list .= ($breaks ? "\n" : '');
        }
        return $list;
    }
    public function toBibleText() {
        $page = '';
        foreach ($this->verses as $verse) {
            $page .= $verse->toPageText();
        }
        return $page;
    }
    /**
     * @param bool breaks
     * 
     * returns string JSON
     */
    public function toJSON($breaks = FALSE) {
        $JSON = '';
        
        if (count($this->verses) > 0) {
            $JSON .= '{ "collection" : [';
            foreach ($this->verses as $verse) {
                $JSON .= $verse->toJSON().',';
                $JSON .= ($breaks ? "\n" : '');
            }

            $JSON = substr($JSON, 0 , ($breaks ? -2 :-1));  // Removes trailing comma
            $JSON .= '], ';

            $JSON .='"collection-size" : ' . count($this->verses) . ',';
            $JSON .='"search-result-size" : ' . intval($this->search_result_size) . '';
            $JSON .=' }';
        }

        return $JSON;
    }
    /**
     * @param string phrase
     * 
     * returns int[] verseIDs
     */
    public function searchVerses($phrase = '') {
        $verseIDs = array();
        $db = new db();
        $quotedSearchTerms = array();
        $db_rows = array();

        // 1. Search literal phrases contained in double quotes
        if (preg_match_all('/"([^"]+)"/', $phrase, $quotedSearchTerms)) {
            foreach ($quotedSearchTerms[1] as $quote) {
                $sql = "SELECT text.id  FROM kjv.text WHERE (text.text COLLATE utf8mb4_general_ci LIKE '%$quote%') LIMIT 20";

                $db_rows = array_merge($db_rows, $db->query($sql)->fetchAll());
            }
        }
        // 2. Search natural language mode for all phrases
        if (strlen($phrase) > 0) {

            $search = str_replace($this->omit_search_terms, ' ', $phrase);   // omit needless search terms
            
            $sql = "SELECT DISTINCT text.id FROM kjv.text WHERE MATCH (`text`) AGAINST ('$search' IN NATURAL LANGUAGE MODE) ORDER BY MATCH (`text`) AGAINST ('$search' IN NATURAL LANGUAGE MODE) DESC LIMIT 20;";
            $db_rows = array_merge($db_rows, $db->query($sql)->fetchAll());

            // update results count
            $count_sql = "SELECT COUNT(text.id) AS `count` FROM kjv.text WHERE MATCH (`text`) AGAINST ('$search' IN NATURAL LANGUAGE MODE) LIMIT 1";
            $count_row = $db->query($count_sql)->fetchArray();
        }
        // 3. Push IDs into verseIDs[] as ints
        if (count($db_rows)) {
            foreach ($db_rows as $row) {
                if ( !in_array(intval($row["id"]), $verseIDs, true) ) {
                    $verseIDs[] = intval($row["id"]);
                }
            }
        }

        $this->setByID($verseIDs);
        // update result size
        $this->search_result_size = (count($this->verses) > intval($count_row['count']) 
                                        ? count($this->verses)
                                        : intval($count_row['count']) );

    }

}