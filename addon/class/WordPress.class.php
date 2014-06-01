<?php


class WordPress {

    //condition used to query to generate feeds
    private $workingCondition='';


    //are we viewing a single post?
    private $singlePost = false;


    //what is the path of the wordpress installation?
    public $path='';


    //templates we are going to use to generate markup
    public $templates;


    //override templates
    public $userTemplates = Array();


    //settings
    public $settings = Array(
        'posts_per_page'=>4,
        'inject_feed'=>true,
        'pagination_slug'=>'page',
        'tag_slug'=>'tag',
        'category_slug'=>'category',
        'date_format'=>'%W, %M %e %Y'
    );


    /**
     *      In the constructor we set the posts_per_page, and the path from the wp_options table
     *
     *      @return void
    */
    public function __construct(){
        //$this->templates = require('../app/wordpress-templates.php');
        $row = DB::query('
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
     *      Set the path that wordpress is supposed to serve files from, or read the path. 
     *
     *
     *      @param $path the path
     *      @return mixed 
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
     *      Create,Update,Read settings
     *
     *
     *      @param $k the setting key
     *      @param $v the setting value 
     *      @return mixed
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
     *      Set a user template on the fly
     *
     *
     *      @param $k the template key
     *      @param $v the template value 
     *      @return void
    */
    public function template($k,$v){
        $this->userTemplates[$k]=$v;
    }//template



    /**
     *      Return the template we are supposed to be using to generate whatever markup.
     *      If a user has set a template then use theirs rather than the default.
     *
     *
     *      @param $t the template key
     *      @return string the template
    */
    private function useTemplate($t){
        if(isset($this->userTemplates[$t])){
            return $this->userTemplates[$t];
        }//if
        else {
            return $this->templates[$t];
        }//el

    }//useTemplate



    /**
     *      Unset a dynamic user template
     *
     *
     *      @param $k the template key
     *      @return void
    */
    public function clearTemplate($k){
        unset($this->userTemplates[$k]);
    }//clearTemplate







    /**
     *      Generate the index
     *
     *
     *      @return mixed 
    */
    public function index(){
        $this->workingCondition = ' ORDER BY p.post_date DESC ';
        return $this->feed();
    }//index



    /**
     *      Generate the results of a Search 
     *
     *
     *      @param $search the term being searched
     *      @return mixed 
    */
    public function search($search){
        $search = '%'.$search.'%';
        $this->workingCondition = ' AND (p.post_title LIKE ? OR p.post_content LIKE ?) ORDER BY p.post_date DESC ';
        $this->workingCondition = DB::set($this->workingCondition,Array($search,$search));
        $this->templates['post'] = $this->templates['feed'];
        return $this->feed();
    }//index

    public function searchData($search,$limit=''){
        if(is_array($search)){
            $s = '(p.post_title LIKE ? OR p.post_content LIKE ?) OR ';
            foreach($search as $k=>$v){
                $search[$k] = DB::set($s,Array('%'.$v.'%','%'.$v.'%'));
            }//foreach
            $search[count($search)-1] = rtrim($search[count($search)-1],'OR ');
            $this->workingCondition = ' AND ('.implode('',$search).') ORDER BY p.post_date DESC ';
        }//if
        else {
            $search = '%'.$search.'%';
            $this->workingCondition = ' AND (p.post_title LIKE ? OR p.post_content LIKE ?) ORDER BY p.post_date DESC ';
            $this->workingCondition = DB::set($this->workingCondition,Array($search,$search));
        }//el

        return $this->data($limit);

    }//searchdata



    /**
     * Generate a single post.
     *
     *
     * @param $slug the posts url
     * @reutrn mixed 
    */
    public function post($slug){
        $this->workingCondition = DB::set(' AND p.post_name=?',$slug);
        $this->singlePost=true;
        return $this->feed();
    }//post


    /**
     * Generate a single posts data.
     *
     *
     * @param $slug the posts url
     * @reutrn mixed 
    */
    public function postData($slug){
        $this->workingCondition = DB::set(' AND p.post_name=?',$slug);
        return $this->data();
    }//post



    /**
     *      Generate a List of the posts by Date
     *
     *
    */
    public function listPosts(){
        $this->templates['post'] = $this->templates['feed'];
        $this->workingCondition = ' ORDER BY p.post_date DESC ';
        return $this->feed();
    }//listArticles



    /**
     *      Generate the feed of posts related to a tag
     *
     *
     *      @param $tag the tag 
     *      @return mixed
    */
    public function tag($tag){
        $where = '
            AND p.ID IN ( 
                SELECT wptr.object_id 
                FROM wp_term_relationships AS wptr
                INNER JOIN wp_term_taxonomy AS wptt ON wptt.term_taxonomy_id=wptr.term_taxonomy_id AND wptt.taxonomy="post_tag"
                INNER JOIN wp_terms AS t ON t.term_id=wptt.term_id
                WHERE t.slug=?
            ) 
            ORDER BY p.post_date DESC
            ';

        $this->workingCondition = DB::set($where,$tag);
        $this->templates['post'] = $this->templates['feed'];
        return $this->feed();
    }//tag



    /**
     *      Generate the feed of posts related to a category
     *
     *
     *      @param $category the category
     *      @return mixed
    */
    public function category($category){
        $where = '
            AND p.ID IN ( 
                SELECT wptr.object_id 
                FROM wp_term_relationships AS wptr
                INNER JOIN wp_term_taxonomy AS wptt ON wptt.term_taxonomy_id=wptr.term_taxonomy_id AND wptt.taxonomy="category"
                INNER JOIN wp_terms AS t ON t.term_id=wptt.term_id
                WHERE t.slug=?
            ) 
            ORDER BY p.post_date DESC
            ';

        $this->workingCondition = DB::set($where,$category);
        $this->templates['post'] = $this->templates['feed'];
        return $this->feed();
    }//category



    /**
     *      Generate the feed of posts authored by a particlar author
     *
     *
     *      @param $author the author
     *      @return mixed
    */
    public function author($author){
        $where = '
            AND p.post_author = ( SELECT wpu.ID FROM wp_users AS wpu WHERE wpu.user_nicename=? )
            ORDER BY p.post_date DESC 
            ';


        $this->workingCondition = DB::set($where,$author);
        $this->templates['post'] = $this->templates['feed'];
        $this->feed();
    }//tag




    /**
     *      Return a list of just links of the most recent posts
     *
     *
     *      @return $html string the markup
    */
    public function recentPosts(){

        $posts = DB::query('
            SELECT 
            post_title,
            post_name
            FROM wp_posts 
            WHERE post_status="publish" AND post_type="post"
            ORDER BY post_date DESC
            LIMIT 5
        ');

        $html='';
        while($row = $posts->fetch_assoc()){
            $row['path'] = $this->path;
            $html.=Template::build('wordpress/post-list',$row);
        }//while

        return $html;

    }//if




    /**
     *      Return top X number of tags
     *
     *
     *      @param $count the number of tags to return
    */
    public function topTags($count=5){


        $result = $this->topTerms($count,"post_tag");

        $html = '';
        while($row = $result->fetch_assoc()){
            $row['path'] = $this->path;
            $html.= Template::build('wordpress/tag-list',$row);
        }//while

        return $html;

    }//topXTags



    /**
     *      Return top X number of categories 
     *
     *
     *      @param $count the number of categories to return
    */
    public function topCategories($count=5){

        $result = $this->topTerms($count,"category");

        $html = '';
        while($row = $result->fetch_assoc()){
            $row['path'] = $this->path;
            $html.=Template::build('wordpress/category-list',$row);
        }//while

        return $html;


    }///topXCategories




    /**
     *      Get the Top X requested tags or categories (term)
     *
     *
     *      @param $count numeric the number to return
     *      @param $type string the type of term
     *      @return object
    */
    private function topTerms($count,$type){
        return DB::query('
            SELECT 
            t.name,
            t.slug,
            wptt.count
            FROM wp_term_taxonomy AS wptt
            INNER JOIN wp_terms AS t ON t.term_id=wptt.term_id
            WHERE wptt.taxonomy=?
            ORDER BY wptt.count DESC
            LIMIT ? 
        ',Array($type,$count));

    }//topXTerms



    /**
     *      Return the top X authors
     *
     *
     *      @param $count numeric the number of authors to return
     *      @return string $html the markup
    */
    public function topAuthors($count=5){

        $result = DB::query('
            SELECT 
            u.user_nicename,
            u.display_name,
            (SELECT COUNT(*) FROM wp_posts AS wp WHERE wp.post_author=u.ID) AS count 
            FROM wp_users AS u
            ORDER BY count
            LIMIT ?
            ',$count);

        $html = '';
        while($row = $result->fetch_assoc()){
            $row['path'] = $this->path;

            $html.= Template::build('wordpress/author-list',$row);
        }//while

        return $html;

    }//topAuthors



    private function data($limit=''){
        return DB::query('
            SELECT 
            p.*,
            DATE_FORMAT(p.post_date,"'.$this->settings['date_format'].'") AS format_date, 
            t.name,
            t.slug,
            c.name AS category,
            c.slug AS category_slug
            FROM (
                SELECT 
                p.ID AS post_id,
                p.post_date,
                p.post_content,
                p.post_excerpt,
                p.post_title,
                p.post_name,
                u.display_name,
                u.user_nicename
                FROM wp_posts AS p
                INNER JOIN wp_users AS u ON u.ID=p.post_author
                WHERE p.post_status="publish" AND p.post_type="post"
                '.$this->workingCondition.$limit.' 

            ) AS p
            LEFT JOIN wp_term_relationships AS wptr ON wptr.object_id=p.post_id
            LEFT JOIN wp_term_taxonomy AS wptt ON wptt.term_taxonomy_id=wptr.term_taxonomy_id
            LEFT JOIN wp_terms AS t ON t.term_id=wptt.term_id
            LEFT JOIN wp_term_taxonomy AS wptt_category ON wptt_category.term_taxonomy_id=wptr.term_taxonomy_id AND wptt_category.taxonomy="category"
            LEFT JOIN wp_terms AS c ON c.term_id=wptt_category.term_id
            ORDER BY p.post_date DESC
        ');

    }//data




    /**
     *      Generate the requested feed (accounting for pagination) based on the set $matchingCondition
     *
     *
     *      @return mixed
    */ 
    private function feed(){
        $s=0;

        if(Data::get('current-page')>=1){

            $s = Data::get('current-page');

            $s = ($s-1)*$this->settings['posts_per_page'];

        }//if
        else if(Data::get('page')===0){
            $send = rtrim('0',$_SERVER['REQUEST_URI']);
            $send.='1';
            header('Location: '.$send);
        }//elif

        $limit = " LIMIT {$s},{$this->settings['posts_per_page']}";

        $posts = $this->data($limit);

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

            $feed.= Template::build('wordpress/post',$row);

        }//while


        if(!$this->singlePost){
            $feed.= $this->printPagination();
        }//if


        if($this->settings['inject_feed']){
            View::html($feed);
        }//if
        else {
            return $feed;
        }//el

    }//feed



    /**
     *      Format the template that holds the tags of a post
     *
     *
     *      @param $terms array the list of terms
     *      @return string 
    */
    private function formatTerms($terms){

        $html = '';

        foreach($terms as $term){
            $term['path'] = $this->path;
            $html.= Template::build('wordpress/tag',$term);
        }//foreach

        return Template::build('wordpress/tag-container',Array('tags'=>$html));

    }//formatTerms



    /**
     *      Format the template that holds the categories of a post
     *
     *
     *      @param $terms array the list of terms
     *      @return string 
    */
    private function formatCats($terms){

        $html = '';

        foreach($terms as $term){
            $term['path'] = $this->path;
            $html.= Template::build('wordpress/category',$term);
        }//foreach

        return Template::build('wordpress/category-container',Array('cats'=>$html));

    }//formatTerms




    /**
     *      Return the total number of posts that match the $workingCondition
     *
     *
     *      @return numeric
    */
    private function totalCount(){

        $row = DB::query('
            SELECT COUNT(*) as total
            FROM wp_posts AS p
            INNER JOIN wp_users AS u ON u.ID=p.post_author
            WHERE p.post_status="publish" AND p.post_type="post" '.$this->workingCondition
            )->fetch_assoc();

        return $row['total'];

    }//totalCount



    /**
     *      Generate the pagination markup relative to the feed being requested
     *
     *
     *      @return string the markup
    */ 
    private function printPagination(){

        $total = $this->totalCount();

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
            if(Data::get('current-page')===false){
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

            if(Data::get('current-page')!==false && $iter==Data::get('current-page')){
                $classes = 'current';
            }//if
            else if(Data::get('current-page')===false && $iter==1){
                $classes = 'current';
            }//elif

            $html.= Template::build('wordpress/pagination-list',
                Array('classes'=>$classes,'page'=>$iter,'slug'=>$slug)
            );

            $iter++;

        }//while

        $slug = $_SERVER['REQUEST_URI'];
        $slug = explode('/',$slug);
        $nFslug = $this->settings['pagination_slug'].'/1';
        if(Data::get('current-page')===false){
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

        if(Data::get('current-page')===false){
            $data['first_arrow_classes']='unavailable';
            $data['first_arrow_slug']='';
        }//if
        else if($numPages == Data::get('current-page')){
            $data['last_arrow_classes']='unavailable';
            $data['last_arrow_slug']='';
        }//elif

        if($numPages==1){
            $data['last_arrow_classes']='unavailable';
            $data['last_arrow_slug']='';
        }//el

        return Template::build('wordpress/pagination-container',$data);

    }//printPagination





    /**
     *      Print page breadcrumbs
     *
     *
     *      @param $data array the crumbs
     *      @return t
    */
    public function printBreadCrumbs($data){

        $html = '';
        foreach($data as $k=>$v){
            $html.= Template::build('wordpress/breadcrumb',
                Array(
                    'path'=>$this->path,
                    'slug'=>$v,
                    'name'=>$k
                )
            );

        }//foreach

        $crumbs = Template::build('wordpress/breadcrumb-container',Array('crumbs'=>$html,'path'=>$this->path));

        if($this->settings['inject_feed']){
            View::html($crumbs);
        }//if
        else {
            return $crumbs;
        }//el

    }//printBreadCrumbs

}//class



?>
