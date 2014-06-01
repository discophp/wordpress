<?php
namespace Disco\addon\Wordpress\model;

class WordPress {

    public $qs = Array(
        'search'=>' AND (p.post_title LIKE ? OR p.post_content LIKE ?) ORDER BY p.post_date DESC ',
        'list-posts'=>' ORDER BY p.post_date DESC '
    );
    
    private $workingQ;

    public function data($limit=''){
        return \DB::query('
            SELECT 
            p.*,
            DATE_FORMAT(p.post_date,"'.WP::settings['date_format'].'") AS format_date, 
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


    public function get($action,$vars=null,$opts=null){
        $this->workingQ = \DB::set($this->$qs[$action],$vars);
        return $this->data();
    }//get


    public function prep($action,$vars=null,$opts=null){
        $this->workingQ = \DB::set($this->$qs[$action],$vars);
    }//prep


    public function searchData($search,$limit=''){
        if(is_array($search)){
            $s = '(p.post_title LIKE ? OR p.post_content LIKE ?) OR ';
            foreach($search as $k=>$v){
                $search[$k] = \DB::set($s,Array('%'.$v.'%','%'.$v.'%'));
            }//foreach
            $search[count($search)-1] = rtrim($search[count($search)-1],'OR ');
            $this->workingCondition = ' AND ('.implode('',$search).') ORDER BY p.post_date DESC ';
        }//if
        else {
            $search = '%'.$search.'%';
            $this->workingCondition = ' AND (p.post_title LIKE ? OR p.post_content LIKE ?) ORDER BY p.post_date DESC ';
            $this->workingCondition = \DB::set($this->workingCondition,Array($search,$search));
        }//el

        return $this->data($limit);

    }//searchdata




}//WordPress


?>
