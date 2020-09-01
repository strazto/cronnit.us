<?php

use \RedBeanPHP\R as R;


function is_url($uri) {
  return preg_match(
      '/^(http|https):'.
      '\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}'.
      '((:[0-9]{1,5})?\\/.*)?$/i',
      $uri
  );
}

function getThumb($body) : array {
  $matches = array();
  $out = [
    'src' => 'unknown',
    'slug' => $body
  ];
  
  # Domain
  $imgur_pattern  = '#^(http[s]?://i\.imgur\.com/)';
  # Slug
  $imgur_pattern .= '(?<slug>[[:alnum:]]{7})';
  # Ext
  $imgur_pattern .= '(?<ext>[[:alnum:]])?\.(\w+)$#i';


  if (preg_match($imgur_pattern, $body, $matches)) {
    $out['src']  = 'imgur';
    $out['slug'] = $matches['slug'];
    return $out;
  }
  
  # Prefix
  $gfypat  = '#^http[s]?://(?>www\.)?';
  # src
  $gfypat .= '(?<src>redgifs|gfycat)';
  $gfypat .= '\.com/(?>watch/)?';
  # Slug
  $gfypat .= '(?<slug>[[:alnum:]]+)';
  # Tags
  $gfypat .= '(-[[:alnum:]-]+)?$#i'; 
  
  if (preg_match($gfypat, $body, $matches)) {
    $out['src'] = $matches['src'];
    $out['slug'] = $matches['slug'];
    
    return $out;
  }
  # TODO: Youtube:
  # https://stackoverflow.com/a/43001028/9238801

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

# Handle pagination
$page_size = 1;
$this->vars['page_size'] = $page_size;
$pagenum =  (int) (@$_GET['pagenum'] ?? 1);
$this->vars['pagenum'] = $pagenum;


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

  $indexedPosts = [];
  foreach ($posts as $post) {
    $body = $post['body'];

    if (!is_url($body)) continue;

    $post['thumb'] = getThumb($body);
    $indexedPosts[$body][] = $post;
  }
  
  // Break these up into pages
  $pages = [];
  $i = 0;
  $current_page = 1;
  foreach ($indexedPosts as $body => $instance) {
    $pages[$current_page][$body] = $instance;
    $i += 1;

    $i = $i % $page_size;
    if ($i == 0) $current_page += 1;
  }
  
  $this->vars['n_pages'] = $current_page;
  $this->vars['pages'] = $pages;

  break;
case 'list':
default:
  $this->vars['view'] = 'posts-list.html';

  // Break posts up into pages
  $pages = [];
  $i = 0;
  $current_page = 1;

  foreach ($posts as $post) {
	  $post['thumb'] = getThumb($post['body']);
    
    $pages[$current_page][$i] = $post;
    $i += 1;

    $i = $i % $page_size;
    if ($i == 0) $current_page += 1;
  }
  
  $this->vars['n_pages'] = $current_page;
  $this->vars['pages'] = $pages;
  break;
}
