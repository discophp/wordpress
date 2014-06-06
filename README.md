<h1>Wordpress Addon</h1>
==============

<p>Sick of Wordpress's bloated code but love their administration panel? Your in the right place.</p>

<h2>How to use</h2>

<p>First install wordpress via <a href='http://codex.wordpress.org/Installing_WordPress'>their instructions</a></p>

<p>Then remove the index.php file from the installation directory, you can move the installation directory to any
where now to hide it from public view and mask the login without affecting the frontend.</p>

<p>Prep your application by registering the Wordpress Facades with the Disco container:</p>

<p>Make the WP Facade</p>
'''php
   Disco::make('WP',function(){
        return new Disco\addon\Wordpress\classes\WordPress;
   });
'''

<p>Create a Router Filter for the wordpress directory</p>

'''php
    Router::filter(WP::path().'/{*}')->to('WordPress');
'''

<p>Thats it! Wordpress is set up!</p>

<h3>Override any of the templates used</h3>
<p>You can override the templates used by the Wordpress Addon by just creating a folder under your template folder
called wordpress/ and naming the template you wish to override by the same name as the one used by the Addon</p>

<p>List of Templates</p>
<ul>
    <li><a
    href='http://github.com/discophp/wordpress/blob/master/addon/template/wordpress/author-list.template.html'>author-list</a></li>
    <li><a href='http://github.com/discophp/wordpress/blob/master/addon/template/wordpress/breadcrumb-container.template.html'>breadcrumb-container</a></li>
    <li><a href='http://github.com/discophp/wordpress/blob/master/addon/template/wordpress/breadcrumb.template.html'>breadcrumb</a></li>
    <li><a href='http://github.com/discophp/wordpress/blob/master/addon/template/wordpress/category-container.template.html'>category-container</a></li>
    <li><a href='http://github.com/discophp/wordpress/blob/master/addon/template/wordpress/category-list.template.html'>category-list</a></li>
    <li><a href='http://github.com/discophp/wordpress/blob/master/addon/template/wordpress/category.template.html'>category</a></li>
    <li><a href='http://github.com/discophp/wordpress/blob/master/addon/template/wordpress/feed.template.html'>feed</a></li>
    <li><a href='http://github.com/discophp/wordpress/blob/master/addon/template/wordpress/pagination-container.template.html'>pagination-container</a></li>
    <li><a href='http://github.com/discophp/wordpress/blob/master/addon/template/wordpress/pagination-list.template.html'>pagination-list</a></li>
    <li><a href='http://github.com/discophp/wordpress/blob/master/addon/template/wordpress/post-list.template.html'>post-list</a></li>
    <li><a href='http://github.com/discophp/wordpress/blob/master/addon/template/wordpress/post.template.html'>post</a></li>
    <li><a href='http://github.com/discophp/wordpress/blob/master/addon/template/wordpress/tag-container.template.html'>tag-container</a></li>
    <li><a href='http://github.com/discophp/wordpress/blob/master/addon/template/wordpress/tag-list.template.html'>tag-list</a></li>
    <li><a href='http://github.com/discophp/wordpress/blob/master/addon/template/wordpress/tag.template.html'>tag</a></li>
</ul>

<h4>Work with strictly the Data</h4>

<p>Using the method:</p>

'''php
    $data = WP::get([option],[vars]);
'''

<p>You can receive <a href='http://www.php.net//manual/en/class.mysqli-result.php'>mysqli_result objects</a> back</p>

<ul>
    <li>index : Primary wordpress feed of articles listed by date.</li>
    <li>search : Search for regex matches of a search term in the database.</li>
    <li>list-posts : Return the most recent posts as a feed.</li>
    <li>post : A single post identified by the slug.</li>
    <li>tag : Articles sorted by date that used a particular tag.</li>
    <li>category : Articles sorted by date that used a particular category.</li>
    <li>author : Articles written by a particular author</li>.
    <li>recent-posts : List of recent posts</li>.
    <li>top-terms : Top X terms either 'category' or 'post_tag'.</li>
    <li>top-authors : top X authors.</li>
</ul>

