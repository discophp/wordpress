<?php
namespace Disco\addon\Wordpress\classes;
/**
 * This file holds the WordPress class.
*/


/**
 *
*/
class WordPress {

    //@var boolean Are we viewing a single post?
    private $singlePost = false;

    //@var string Full posts or feed? (post|feed)
    private $type = 'post';

    //@var string What is the path of the wordpress installation?
    public $path='';

    //@var array Wordpress settings.
    public $settings = Array(
        'posts_per_page'=>4,
        'inject_feed'=>true,
        'pagination_slug'=>'page',
        'tag_slug'=>'tag',
        'category_slug'=>'category',
        'date_format'=>'%W, %M %e %Y'
    );


    /**
     * In the constructor we set the posts_per_page and the path to wordpress directory from the wp_options table.
     *
     * @return void
    */
    public function __construct(){
        $row = \DB::query('
            SELECT 
            (SELECT option_value FROM wp_options WHERE option_name="posts_per_page") AS posts_per_page, 
            (SELECT option_value FROM wp_options WHERE option_name="home") AS path
        ')->fetch_assoc();
        foreach($row as $k=>$v){
            $this->settings[$k]=$v;
        }//foreach

        $p = explode('/',$this->settings['path']);
        $p = '/'.$p[count($p)-1];
        $p = rtrim($p,'/');
        $p = $p.'/';
        $this->path=$p;

    }//construct



    /**
     * Set the path that wordpress is supposed to serve files from, or read the path. 
     *
     *
     * @param string|null $path The path to the wordpress directory.
     *
     * @return string|null 
    */
    public function path($path=null) {
        if($path!=null){
            $this->path=$path;
        }//if
        else {
            return $this->path;
        }//el
    }//path



    /**
     * Create,Update,Read settings.
     *
     *
     * @param string|null $k The setting key.
     * @param sting|null $v The setting value. 
     *
     * @return string|null  
    */ 
    public function settings($k=null,$v=null){
        if($k==null){
            return $this->settings;
        }//if
        if($v==null){
            return $this->settings[$k];
        }//if
        $this->settings[$k]=$v; 
    }//settings



    /**
     * Generate the index
     *
     *
     * @return mixed 
    */
    public function index(){
        \WPm::prep('index');
        return $this->feed();
    }//index



    /**
     * Generate the results of a Search.
     *
     *
     * @param string $search The term being searched.
     *
     * @return string 
    */
    public function search($search){
        $search = '%'.$search.'%';
        \WPm::prep('search',Array($search,$search));
        $this->type='feed';
        return $this->feed();
    }//index



    /**
     * Generate a single post.
     *
     *
     * @param string $slug The posts URI. 
     *
     * @reutrn string 
    */
    public function post($slug){
        \WPm::prep('post',$slug);
        $this->singlePost=true;
        return $this->feed();
    }//post



    /**
     * Generate a List of the posts by ordered by Date.
     *
     *
     * @return string
    */
    public function listPosts(){
        $this->type='feed';
        \WPm::prep('list-posts');
        return $this->feed();
    }//listArticles



    /**
     * Generate the feed of posts related to a tag.
     *
     *
     * @param string $tag The tag.
     *
     * @return string 
    */
    public function tag($tag){
        $this->type='feed';
        \WPm::prep('tag',$tag);
        return $this->feed();
    }//tag



    /**
     * Generate the feed of posts related to a category.
     *
     *
     * @param string $category The category.
     *
     * @return string 
    */
    public function category($category){
        \WPm::prep('category',$category);
        $this->type='feed';
        return $this->feed();
    }//category



    /**
     * Generate the feed of posts authored by a particlar author.
     *
     *
     * @param string $author The author.
     *
     * @return string 
    */
    public function author($author){
        \WPm::prep('author',$author);
        $this->type='feed';
        $this->feed();
    }//tag




    /**
     * Return a list of just links of the most recent posts.
     *
     *
     * @return string  
    */
    public function recentPosts(){

        $posts = \WPm::get('recent-posts');

        $html='';
        while($row = $posts->fetch_assoc()){
            $row['path'] = $this->path;
            $html.= \Template::build('wordpress/post-list',$row);
        }//while

        return $html;

    }//if




    /**
     * Return top X number of tags.
     *
     *
     * @param integer $count The number of tags to return.
     *
     * @return string
    */
    public function topTags($count=5){

        $result = \WPm::get('top-terms',Array($count,'post_tag'));

        $html = '';
        while($row = $result->fetch_assoc()){
            $row['path'] = $this->path;
            $html.= \Template::build('wordpress/tag-list',$row);
        }//while

        return $html;

    }//topXTags



    /**
     * Return top X number of categories.
     *
     *
     * @param integer $count The number of categories to return.
    */
    public function topCategories($count=5){

        $result = \WPm::get('top-terms',Array('category',$count));

        $html = '';
        while($row = $result->fetch_assoc()){
            $row['path'] = $this->path;
            $html.= \Template::build('wordpress/category-list',$row);
        }//while

        return $html;


    }///topXCategories



    /**
     * Return the top X authors.
     *
     *
     * @param integer $count The number of authors to return.
     *
     * @return string 
    */
    public function topAuthors($count=5){

        $result = \WPm::get('top-authors',$count);

        $html = '';
        while($row = $result->fetch_assoc()){
            $row['path'] = $this->path;

            $html.= \Template::build('wordpress/author-list',$row);
        }//while

        return $html;

    }//topAuthors




    /**
     * Generate the requested feed (accounting for pagination).
     *
     *
     * @return string|void 
    */ 
    private function feed(){
        $s=0;

        if(\Data::get('current-page')>=1){
            $s = \Data::get('current-page');
            $s = ($s-1)*$this->settings['posts_per_page'];
        }//if
        else if(\Data::get('page')===0){
            $send = rtrim('0',$_SERVER['REQUEST_URI']);
            $send.='1';
            header('Location: '.$send);
            exit;
        }//elif

        $limit = " LIMIT {$s},{$this->settings['posts_per_page']}";

        $posts = \WPm::data($limit);

        $feed = '';
        $iter = 0;
        while($iter<$posts->num_rows){

            $tags=Array();
            $cats=Array();

            do {

                $posts->data_seek($iter);
                $row  = $posts->fetch_assoc();

                if($row['name']!=''){
                    $tags[] = Array('slug'=>$row['slug'],'name'=>$row['name']);
                }//if
                if($row['category']!=''){
                    $cats[] = Array('slug'=>$row['category_slug'],'name'=>$row['category']);
                }//if

                $posts->data_seek($iter+1);
                $nRow = $posts->fetch_assoc();

                $iter++;

            } while($row['post_id']==$nRow['post_id']);

            $row['tags'] = $tags;
            $row['cats'] = $cats;

            if(count($row['tags'])!=0){
                $row['tags'] = $this->formatTerms($row['tags']);
            }//if
            else {
                $row['tags']='';
            }//
            if(count($row['cats'])!=0){
                $row['cats'] = $this->formatCats($row['cats']);
            }//if
            else {
                $row['cats']='';
            }//el

            $row['path'] = $this->path;

            if($this->type=='feed'){
                $feed.= \Template::build('wordpress/feed',$row);
            }//if
            else {
                $feed.= \Template::build('wordpress/post',$row);
            }//el

        }//while

        if(!$this->singlePost){
            $feed.= $this->printPagination();
        }//if

        if($this->settings['inject_feed']){
            \View::html($feed);
        }//if
        else {
            return $feed;
        }//el

    }//feed



    /**
     * Format the template that holds the tags of a post.
     *
     *
     * @param array $terms The list of terms.
     *
     * @return string 
    */
    private function formatTerms($terms){

        $html = '';

        foreach($terms as $term){
            $term['path'] = $this->path;
            $html.= \Template::build('wordpress/tag',$term);
        }//foreach

        return \Template::build('wordpress/tag-container',Array('tags'=>$html));

    }//formatTerms



    /**
     * Format the template that holds the categories of a post.
     *
     *
     * @param array $terms The list of terms.
     *
     * @return string 
    */
    private function formatCats($terms){

        $html = '';

        foreach($terms as $term){
            $term['path'] = $this->path;
            $html.= \Template::build('wordpress/category',$term);
        }//foreach

        return \Template::build('wordpress/category-container',Array('cats'=>$html));

    }//formatTerms



    /**
     * Generate the pagination markup relative to the feed being requested.
     *
     *
     * @return string 
    */ 
    private function printPagination(){

        $total = \WPm::totalCount();

        $html='';
        $numPages = round($total/$this->settings['posts_per_page'],0);

        if($numPages==0){
           return ''; 
        }

        $iter=1;
        while($iter<=$numPages){

            $slug = $_SERVER['REQUEST_URI'];
            $slug = explode('/',$slug);
            $tail = $this->settings['pagination_slug'].'/'.$iter;
            if(\Data::get('current-page')===false){
                //$slug[count($slug)-1] = rtrim('/',$slug[count($slug)-1]);
                if($slug[count($slug)-1]==""){
                    $slug[count($slug)-1] = $tail;
                }//if
                else {
                    $slug[] = $tail;
                }//
            }//if
            else {
                $slug[count($slug)-1] = $iter;
            }//el

            $slug = implode('/',$slug);

            $classes = '';

            if(\Data::get('current-page')!==false && $iter==\Data::get('current-page')){
                $classes = 'current';
            }//if
            else if(\Data::get('current-page')===false && $iter==1){
                $classes = 'current';
            }//elif

            $html.= \Template::build('wordpress/pagination-list',
                Array('classes'=>$classes,'page'=>$iter,'slug'=>$slug)
            );

            $iter++;

        }//while

        $slug = $_SERVER['REQUEST_URI'];
        $slug = explode('/',$slug);
        $nFslug = $this->settings['pagination_slug'].'/1';
        if(\Data::get('current-page')===false){
            $slug[] = $nFslug;
        }//if
        else {
            $slug[count($slug)-1] = '1';
        }//el
        $fslug = implode('/',$slug);

        $slug[count($slug)-1] = $this->settings['pagination_slug'].'/'.$numPages;
        $lslug = implode('/',$slug);

        $data = Array(
            'pages'=>$html,
            'first_arrow_classes'=>'',
            'first_arrow_slug'=>$fslug,
            'last_arrow_classes'=>'',
            'last_arrow_slug'=>$lslug
        );

        if(\Data::get('current-page')===false){
            $data['first_arrow_classes']='unavailable';
            $data['first_arrow_slug']='';
        }//if
        else if($numPages == \Data::get('current-page')){
            $data['last_arrow_classes']='unavailable';
            $data['last_arrow_slug']='';
        }//elif

        if($numPages==1){
            $data['last_arrow_classes']='unavailable';
            $data['last_arrow_slug']='';
        }//el

        return \Template::build('wordpress/pagination-container',$data);

    }//printPagination



    /**
     * Print page breadcrumbs.
     *
     *
     * @param array $data The crumbs.
     *
     * @return string  
    */
    public function printBreadCrumbs($data){

        $html = '';
        foreach($data as $k=>$v){
            $html.= \Template::build('wordpress/breadcrumb',
                Array(
                    'path'=>$this->path,
                    'slug'=>$v,
                    'name'=>$k
                )
            );

        }//foreach

        $crumbs = \Template::build('wordpress/breadcrumb-container',Array('crumbs'=>$html,'path'=>$this->path));

        if($this->settings['inject_feed']){
            \View::html($crumbs);
        }//if
        else {
            return $crumbs;
        }//el

    }//printBreadCrumbs

}//class
?>
