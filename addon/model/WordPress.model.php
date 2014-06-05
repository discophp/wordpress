<?php
namespace Disco\addon\Wordpress\model;
/**
 * This file holds the Disco\addon\Wordpress\model\WordPress class.
*/


/**
 * The is the Wordpress Model, it provides access to the data empowering wordpress.
*/
class WordPress {

    /**
     * @var array The query snippets used to generate the core WP data.
     */
    public $qs = Array(
        'index'=>' ORDER BY p.post_date DESC ',
        'search'=>' AND (p.post_title LIKE ? OR p.post_content LIKE ?) ORDER BY p.post_date DESC ',
        'list-posts'=>' ORDER BY p.post_date DESC ',
        'post'=>' AND p.post_name=?',
        'tag'=>'
                AND p.ID IN ( 
                    SELECT wptr.object_id 
                    FROM wp_term_relationships AS wptr
                    INNER JOIN wp_term_taxonomy AS wptt ON wptt.term_taxonomy_id=wptr.term_taxonomy_id AND wptt.taxonomy="post_tag"
                    INNER JOIN wp_terms AS t ON t.term_id=wptt.term_id
                    WHERE t.slug=?
                ) 
                ORDER BY p.post_date DESC',
        'category'=>'
                AND p.ID IN ( 
                    SELECT wptr.object_id 
                    FROM wp_term_relationships AS wptr
                    INNER JOIN wp_term_taxonomy AS wptt ON wptt.term_taxonomy_id=wptr.term_taxonomy_id AND wptt.taxonomy="category"
                    INNER JOIN wp_terms AS t ON t.term_id=wptt.term_id
                    WHERE t.slug=?
                ) 
                ORDER BY p.post_date DESC',
        'author'=>'
                AND p.post_author = ( SELECT wpu.ID FROM wp_users AS wpu WHERE wpu.user_nicename=? )
                ORDER BY p.post_date DESC',
        'recent-posts'=>'
            SELECT 
            post_title,
            post_name
            FROM wp_posts 
            WHERE post_status="publish" AND post_type="post"
            ORDER BY post_date DESC
            LIMIT 5
            ',
        'top-terms'=>'
            SELECT 
            t.name,
            t.slug,
            wptt.count
            FROM wp_term_taxonomy AS wptt
            INNER JOIN wp_terms AS t ON t.term_id=wptt.term_id
            WHERE wptt.taxonomy=?
            ORDER BY wptt.count DESC
            LIMIT ? 
            ',
        'top-authors'=>'
            SELECT 
            u.user_nicename,
            u.display_name,
            (SELECT COUNT(*) FROM wp_posts AS wp WHERE wp.post_author=u.ID) AS count 
            FROM wp_users AS u
            ORDER BY count
            LIMIT ?
        '


    );
    

    /**
     * @var string The snippet in $qs are we working with.
    */
    private $workingQ='';



    /**
     * Get the \mysqli_result requested by $this->workingQ instated by either $this->get() | $this->prep().
     *
     *
     * @param string $limit The limit to apply to the $this->workingQ.
     *
     * @return \mysqli_result
    */
    public function data($limit=''){
        $sel = substr(ltrim($this->workingQ),0,6);
        if($sel=='SELECT'){
            return \DB::query($this->workingQ);
        }//if

        return \DB::query('
            SELECT 
            p.*,
            DATE_FORMAT(p.post_date,"'.\WP::settings('date_format').'") AS format_date, 
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
                '.$this->workingQ.$limit.' 

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
     * Get the result of a specified $action with bound $vars.
     * Possible $action options:
     *  - index : Primary wordpress feed of articles listed by date. 
     *  - search : Search for regex matches of a search term in the database
     *  - list-posts : Return the most recent posts as a feed.
     *  - post : A single post identified by the slug.
     *  - tag : Articles sorted by date that used a particular tag.
     *  - category : Articles sorted by date that used a particular category.
     *  - author : Articles written by a particular author.
     *  - recent-posts : List of recent posts.
     *  - top-terms : Top X terms either 'category' or 'post_tag'.
     *  - top-authors : top X authors.
     *
     *
     * @param string $action The action to take from $this->qs.
     * @param null|string|array $vars The variables to bind into the query.
     * @param null|string|array $opts Reserved for expansion.
     *
     * @return \mysqli_result 
    */
    public function get($action,$vars=null,$opts=null){
        $this->prep($action,$vars,$opts);
        return $this->data();
    }//get



    /**
     * Prepare the query specified by $action and $vars and store it into the $this->workingQ.
     *
     *
     * @param string $action The action to take from $this->qs.
     * @param null|string|array $vars The variables to bind into the query.
     * @param null|string|array $opts Reserved for expansion.
     *
     * @return void 
    */
    public function prep($action,$vars=null,$opts=null){
        $this->workingQ = \DB::set($this->qs[$action],$vars);
    }//prep



    /**
     * Return the total number of posts that match the $workingQ.
     *
     *
     * @return numeric
    */
    public function totalCount(){

        $row = \DB::query('
            SELECT COUNT(*) as total
            FROM wp_posts AS p
            INNER JOIN wp_users AS u ON u.ID=p.post_author
            WHERE p.post_status="publish" AND p.post_type="post" '.$this->workingQ
            )->fetch_assoc();

        return $row['total'];

    }//totalCount



    /**
     * Perform a regex search on the database of any number of search terms.
     *
     *
     *  @param string|array $search The search terms to use to search the DB. 
     *  @param string       $limit  The number of results to return.
     *
     *  @return \mysqli_result
     */
    public function searchData($search,$limit=''){
        if(is_array($search)){
            $s = '(p.post_title LIKE ? OR p.post_content LIKE ?) OR ';
            foreach($search as $k=>$v){
                $search[$k] = \DB::set($s,Array('%'.$v.'%','%'.$v.'%'));
            }//foreach
            $search[count($search)-1] = rtrim($search[count($search)-1],'OR ');
            $this->workingQ = ' AND ('.implode('',$search).') ORDER BY p.post_date DESC ';
        }//if
        else {
            $search = '%'.$search.'%';
            $this->workingQ = ' AND (p.post_title LIKE ? OR p.post_content LIKE ?) ORDER BY p.post_date DESC ';
            $this->workingQ = \DB::set($this->workingQ,Array($search,$search));
        }//el

        return $this->data($limit);

    }//searchdata

}//WordPress

?>
