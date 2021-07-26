<?php
/**
 *      Title:      Search
 *      Author:     Joe Cohen
 *      Contact:    <deskofjoe@gmail.com>
 *      GitHub:     https://github.com/cojohen
 * 
 *      Purpose:    Search the database of KJV verses 
 */
function showSearchPage() {
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Search KJV</title>
<?php 
        include 'includes/globals.php';
        include 'includes/styles.php';
        include 'includes/jquery.php';
        include 'includes/favicon.php';
?>
        <script defer type="text/javascript" src="<?=$_site_document_root;?>js/search.js"></script>
    </head>
    <body>
        <main id="main">
            <div id="logo">
                <h1 class="text-reflect">Search <b class="KJV">KJV</b></h1>
            </div>
            <input type="text" id="search-input" name="search">
            <input type="button" id="search-submit" name="submit" value="Search">
            <div id="search-results">
                <ul id="search-results-list"></ul>
            </div>
        </main>
    </body>
</html>
<?php
}
?>