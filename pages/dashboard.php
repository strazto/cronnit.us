<?php

use \RedBeanPHP\R as R;

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
  $this->vars['view'] = 'posts-body.html';

  # I can't figure out any better way to do this from the RedBeanPHP docs, though
  # I am sure there is one.
  $distinct_urls = R::getAll("SELECT DISTINCT url FROM posts WHERE author-id = $account->id ORDER BY `when` DESC");
  
  $indexedPosts = [];
  foreach($distinct_urls as $url) {
    $posts_with_url = $posts->withCondition(' url = ? ', [$url])->ownPostList;

    $indexedPosts[$url] = $posts_with_url;
  }

  $this->vars['posts'] = $indexedPosts;

  break;
case 'list':
default:
  $this->vars['view'] = 'posts-list.html';
  $this->vars['posts'] = $posts;
  break;
}
