<?php


Router::get(WP::path(),function(){
    WP::index();
});


Router::get(WP::path().'list',function(){

    $c = Array();
    $c['All Articles Listing']='list';
    WP::printBreadCrumbs($c);

    WP::listPosts();

});

Router::get(WP::path().'list/page/{currentPage}',function($currentPage) {

    $c = Array();
    $c['All Articles Listing']='list';
    $c['Page '.$currentPage]='list/page/'.$currentPage;
    WP::printBreadCrumbs($c);

    Data::get()->set('current-page',$currentPage);
    Data::get()->set('page',2);

    WP::listPosts();

})->where('currentPage','integer');


Router::get(WP::path().'search/{search}',function($search){

    $c = Array();
    $c['Search']='search/';
    $c[$search]='search/'.$search;
    WP::printBreadCrumbs($c);

    WP::search($search);

    View::script('wp.$search.val("'.$search.'");');
})->where('search','alpha_numeric');


Router::get(WP::path().'search/{search}/page/{currentPage}',function($search,$currentPage){

    $c = Array();
    $c['Search']='search/';
    $c[$search]='search/'.$search;
    $c['Page '.$currentPage]='search/'.$search.'/page/'.$currentPage;
    WP::printBreadCrumbs($c);

    Data::get()->set('current-page',$currentPage);
    //Data::get()->set('page',true);

    WP::search($search);

    View::script('wp.$search.val("'.$search.'");');

})->where(Array('search'=>'alpha_numeric','currentPage'=>'integer'));



Router::get(WP::path().'category/',function(){

    $c = Array();
    $c['Category']='category/';
    WP::printBreadCrumbs($c);


    WP::template('category-list','<a class="wp-category-list" href="{{$path}}category/{{$slug}}">{{$name}} - {{$count}}</a>');
    $categories = WP::topCategories(1000);
    View::html("<div class='clearfix'>{$categories}</div>");

    WP::clearTemplate('category-list');
});


Router::get(WP::path().'category/{category}',function($category){

    $c = Array();
    $c['Category']='category/';
    $c[$category]='category/'.$category;
    WP::printBreadCrumbs($c);

    WP::category($category);

})->where('category','alpha_numeric');





Router::get(WP::path().'author/',function(){

    $c = Array();
    $c['Author']='author/';
    WP::printBreadCrumbs($c);


    WP::template('author-list','<a class="wp-author-list" href="{{$path}}author/{{$user_nicename}}">{{$display_name}}</a>');
    $authors = WP::topAuthors(10);
    View::html("<div class='clearfix'>{$authors}</div>");

    WP::clearTemplate('author-list');
});






Router::get(WP::path().'tag/{tag}/page/{currentPage}',function($tag,$currentPage) {

    $c = Array();
    $c['Tag']='tag/';
    $c[$tag]='tag/'.$tag;
    WP::printBreadCrumbs($c);

    Data::get()->set('current-page',$currentPage);

    WP::tag($tag);

})->where(Array('tag'=>'alpha_numeric','currentPage'=>'integer'));



Router::get(WP::path().'tag/{tag}',function($tag) {

    $c = Array();
    $c['Tag']='tag/';
    $c[$tag]='tag/'.$tag;
    WP::printBreadCrumbs($c);


    WP::tag($tag);

})->where('tag','alpha_numeric');




Router::get(WP::path().'author/{author}',function($author) {

    $c = Array();
    $c['Author']='author/';
    $c[$author]='author/'.$author;
    WP::printBreadCrumbs($c);


    WP::author($author);

})->where('author','alpha_numeric');


Router::get(WP::path().'author/{author}/page/{currentPage}',function($author,$currentPage) {

    $c = Array();
    $c['Author']='author/';
    $c[$author]='author/'.$author;
    WP::printBreadCrumbs($c);

    Data::get()->set('current-page',$currentPage);

    WP::author($author);

})->where(Array('author'=>'alpha_numeric','currentPage'=>'integer'));



Router::get(WP::path().'tag/',function(){

    $c = Array();
    $c['Tag']='tag/';
    WP::printBreadCrumbs($c);

    WP::template('tag-list','<a class="wp-tag-list" href="{{$path}}tag/{{$slug}}">{{$name}} - {{$count}}</a>');
    $tags = WP::topTags(1000);
    View::html("<div class='clearfix'>{$tags}</div>");

    WP::clearTemplate('tag-list');
});



Router::get(WP::path().'{slug}',function($slug) {

    WP::printBreadCrumbs(Array(str_replace('-',' ',$slug)=>$slug));

    WP::post($slug);

})->where('slug','alpha_numeric');


Router::get(WP::path().'page/{currentPage}',function($currentPage) {

    WP::printBreadCrumbs(Array('Page '.$currentPage=>'page/'.$currentPage));

    Data::get()->set('current-page',$currentPage);

    WP::index();

})->where('currentPage','integer');


?>
