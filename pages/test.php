<?php

use \RedBeanPHP\R as R;

$ln = "===========================================";


# Get the page from the url
$page = (int) (@$_GET['page'] ?? 1);
$page = $page > 0 ? $page : 1;

$page_size = (int) (@$_GET['page_size'] ?? 50);
$page_size = $page_size > 1 ? $page_size : 50;

$account = $this->getAccount();
# https://stackoverflow.com/questions/5921550/limit-offset-by-distinct-column
$hashes = R::getCol(
  "SELECT DISTINCT hash FROM post 
   WHERE ( 
    ( deleted IS NULL OR deleted = 0 ) AND 
    ( account_id = :acc_id ) AND
    ( body <> '' ) ) 
    ORDER BY `when` DESC 
    LIMIT :page_size OFFSET :page_off;
  ",
  [
    ":acc_id" => $account['id'],  
    ":page_size" => $page_size,
    ":page_off"  => ($page - 1)*$page_size
  ]
);

$bindings = $hashes;
$bindings[] = $account['id'];

$posts = R::getAll("
  SELECT * FROM `post` WHERE
  (
    ( deleted IS NULL OR deleted = 0 ) AND 
    ( hash IN (".R::genSlots($hashes).") ) AND
    ( account_id = ? )
   )  ORDER BY `when` DESC;",
  $bindings
);
#$posts = R::exportAll($posts);

#$posts = $account->withCondition("( HEX(hash) IN ( ".R::genSlots($hashes)." ) )",
#  $hashes
#)->ownPostList;

#$posts = R::find("post", "( HEX(hash) IN ( ".R::genSlots($hashes)." ) )",
#  $hashes
#);
# 'SELECT DISTINCT HEX(hash) FROM post  WHERE ( ( deleted IS NULL OR deleted = 0 ) AND ( account_id = 1 ) AND ( body <> '' ) ) ORDER BY `when` DESC LIMIT 2 OFFSET 6;

error_log($ln."3");
error_log(json_encode($posts), JSON_PRETTY_PRINT);



$this->vars['posts'] = $posts;
$this->vars['hashes'] = $hashes;
$this->vars['page_size'] = $page_size;
$this->vars['page_num']  = $page;

foreach (['posts', 'hashes', 'page_size', 'page_num'] as $key) {
  error_log(json_encode($this->vars[$key]), JSON_PRETTY_PRINT);
}
