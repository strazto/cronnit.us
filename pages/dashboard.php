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
  $imgur_pattern = '#^http[s]?://i\.imgur\.com/([[:alnum:]]{7})\.(\w+)$#i';
  
  $extension_fix_pat = '`(?<=\.)(mp4|gifv)$`'; 


  $div_start = "<div style='max-height:256px;'><div style='position:relative; padding-bottom:147.96%;'>";
  $div_close = "</div></div>"; 

  $out = ''; 
  if (preg_match($imgur_pattern, $body, $matches)) {
    $out = $body;
    
    // Replace video urls with static .jpg previews
    $out = preg_replace($extension_fix_pat, 'jpg', $out);
    $out = "$div_start<img src='$out' style='position:absolute;top:0;left:0;max-height:256px;'>$div_close";
    return $out;
  } 

  $redgifs_pat = '`^http[s]?://redgifs\.com/watch/([[:alnum:]-]+)$`i';

  if (preg_match($redgifs_pat, $body, $matches)) {
    
    $data_id = $matches[1];
    $out = "$div_start<iframe src='https://redgifs.com/ifr/$data_id' frameborder='0' scrolling='no' width='100%' height='100%' style='position:absolute;top:0;left:0;max-height:256px;' allowfullscreen></iframe>$div_close</p>";
    return $out;
  }

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
