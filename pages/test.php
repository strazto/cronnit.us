<?php

use \RedBeanPHP\R as R;

# Get the page from the url
$page = (int) (@$_GET['page'] ?? 1);
$page = $page > 0 ? $page : 1;

$page_size = (int) (@$_GET['page_size'] ?? 50);
$page_size = $page_size >= 1 ? $page_size : 50;

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

$posts = $account->withCondition("
  (
    ( deleted IS NULL OR deleted = 0 ) AND 
    ( hash IN (".R::genSlots($hashes).") )
   )  ORDER BY `when` DESC;",
  $hashes
)->ownPostList;


$this->vars['posts'] = $posts;
$this->vars['hashes'] = $hashes;
$this->vars['page_size'] = $page_size;
$this->vars['page_num']  = $page;

