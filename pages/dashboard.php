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


function getThumb($body) : string {
  $matches = array();
  $out = '';

  $imgur_pattern = '#^(http[s]?://i\.imgur\.com/)([[:alnum:]]{7})([[:alnum:]])?\.(\w+)$#i';
  if (preg_match($imgur_pattern, $body, $matches)) {
    $out = $body;
    // Convert to 160x160 thumbnail URL - handles both images and gifs.
    $out = preg_replace($imgur_pattern, '\1\2t.jpg', $out);
    $out = "<img class='img-thumbnail img-fluid' loading='lazy' src='$out'>";
    return $out;
  }

  $redgifs_pat = '#^http[s]?://(www\.)?redgifs\.com/watch/([[:alnum:]]+)(-[[:alnum:]-]+)?$#i';
  if (preg_match($redgifs_pat, $body, $matches)) {
    $data_id = $matches[2];
    $out = "<iframe class='img-thumbnail img-fluid' src='https://redgifs.com/ifr/$data_id?autoplay=0' loading='lazy' frameborder='0' scrolling='no' allowfullscreen></iframe>";
    return $out;
  }

  return $out;
}


# Populate view menu, handle view switching
$this->vars['view_list'] = [
  [
    'id'=>'list',
    'desc' => 'List Posts Chronologically',
    'template' => 'posts-list.html'
  ],
  [
    'id'=>'body',
    'desc' => 'Group Posts By Content',
    'template' => 'posts-body.hmtl'
  ],
  [
    'id'=>'calendar',
    'desc' => 'Calendar (WIP)',
    'template' => 'posts-calendar.html'
  ]
];


# If view is queried from url, tell the session to use that view.
# If not given, just use the session's previous view
if(!is_array($_SESSION['view'])) $_SESSION['view'] = [];
$view = @$_GET['view'];
if (isset($view)) $view = (string) $view;

$_SESSION['view']['dashboard'] = $view ?? $_SESSION['view']['dashboard'] ?? "list";



$account = $this->getAccount();
$this->vars['account'] = $account;
$posts = $account->withCondition(' ( deleted IS NULL OR deleted = 0 ) ORDER BY `when` DESC ')->ownPostList;


switch ($_SESSION['view']['dashboard']) {
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

  $indexedThumbs = [];
  $indexedPosts = [];
  foreach ($posts as $post) {
    $body = $post['body'];

    if (!is_url($body)) continue;

    $post['thumb'] = getThumb($body);
    $indexedPosts[$body][] = $post;
  }

  $this->vars['posts'] = $indexedPosts;

  break;
case 'list':
default:
  $this->vars['view'] = 'posts-list.html';

  foreach ($posts as $i => $post) {
	  $posts[$i]['thumb'] = getThumb($post['body']);

  }
  $this->vars['posts'] = $posts;
  break;
}
