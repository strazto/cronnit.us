<?php

use \RedBeanPHP\R as R;

function is_url($uri){
  $out = preg_match(
      '/^(http|https):'.
      '\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}'.
      '((:[0-9]{1,5})?\\/.*)?$/i',
      $uri
  );

  return $out;
}


$account = $this->getAccount();
$this->vars['account'] = $account;
$posts = $account->withCondition(' ( deleted IS NULL OR deleted = 0 ) ORDER BY `when` DESC ')->ownPostList;

switch (@$_GET['view']) {
case 'calendar':
  $this->vars['view'] = 'posts-calendar.html';
  $this->vars['time'] = @$_GET['time'];
  $indexedPosts = [];

  foreach ($posts as $post) {
    $year = date('Y', $post->when_original);
    $month = date('n', $post->when_original);
    $day = date('j', $post->when_original);
    $indexedPosts[$year][$month][$day][] = $post;
  }

  $this->vars['posts'] = $indexedPosts;

  break;
case 'body':
  $f = "json_encode";

  $this->vars['view'] = 'posts-body.html';

  # I can't figure out any better way to do this from the RedBeanPHP docs, though
  # I am sure there is one.
  $distinct_urls = R::getAll(
    "SELECT DISTINCT `body` FROM `post` WHERE account_id = ? ORDER BY `when` DESC",
    [$account->id]
  ); 
  

  $indexedPosts = [];
  foreach ($distinct_urls as $url) {
    $body = $url['body'];

    if (!is_url($body)) continue;
     
    
    $posts_with_url = $account->
      withCondition(' 
        ( ( deleted IS NULL OR deleted = 0 ) AND `body` = ? ) 
        ORDER BY `when` DESC 
      ', [$body])->ownPostList;

    $indexedPosts[$body] = $posts_with_url;
  }

  error_log("{$f($indexedPosts)}");

  $this->vars['posts'] = $indexedPosts;

  break;
case 'list':
default:
  $this->vars['view'] = 'posts-list.html';
  $this->vars['posts'] = $posts;
  break;
}
