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

function getContentView($page, $page_size, $account) { 
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

  $indexedPosts = [];
  foreach ($posts as $post) {
    $body = $post['body'];

    if (!is_url($body)) continue;

    $post['thumb'] = getThumb($body);
    $indexedPosts[$body][] = $post;
  }

  return $indexedPosts;
}

function getContentViewPageCount($page_size, $account) {
  return  R::$f->begin()->
    select("COUNT DISTINCT hash")->from("post")->
    where(
      "( 
        ( deleted IS NULL OR deleted = 0 ) AND 
        ( account_id = :acc_id ) AND 
        ( body <> '' ) 
      ) ")->put($account['id'])->get("cell") / $page_size;
}

function getListView($page, $page_size, $account) {
  $posts = $account->withCondition('
    ( deleted IS NULL OR deleted = 0 ) 
    ORDER BY `when` DESC 
    LIMIT :page_size OFFSET :page_off;',
    [
      ":page_size" => $page_size,
      ":page_off"  => ($page - 1)*$page_size
    ]
  )->ownPostList;

  foreach ($posts as $i => $post) {
	  $posts[$i]['thumb'] = getThumb($post['body']);
  }
  return $posts;
}

function getListViewPageCount($page_size, $account) {
  return $account->
    withCondition('( deleted IS NULL OR deleted = 0 )')->
    countOwnPosts / $page_size;
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


# Get the page from the url
$page = (int) (@$_GET['page'] ?? 1);
$page = $page > 0 ? $page : 1;

$this->vars["page"] = $page;

$page_size = (int) (@$_GET['page_size'] ?? 50);
$page_size = $page_size >= 1 ? $page_size : 50;

$this->vars['page_size'] = $page_size;

$account = $this->getAccount();
$this->vars['account'] = $account;
 
switch ($_SESSION['view']['dashboard']) {
case 'calendar':
  $this->vars['view'] = 'posts-calendar.html';
  $posts = getListView($page, $page_size, $account);

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
  $posts = getContentView($page, $page_size, $account);
  $this->vars['posts'] = $posts;

  $this->vars['n_pages'] = getContentViewPageCount($page_size, $account); 
  break;
case 'list':
default:
  $this->vars['view'] = 'posts-list.html';
  $posts = getListView($page, $page_size, $account);
  $this->vars['n_pages'] = getListViewPageCount($page_size, $account); 
  $this->vars['posts'] = $posts;
  break;
}
