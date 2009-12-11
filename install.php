<?php
if (file_exists("posts.php") || file_exists("settings.php"))
{
	?><span style="color:red;">Error, posts.php and settings.php already exist.
	To reinstall, first delete those 2 files.</span><?php
	exit;
}
if (!is_writable("posts.php") || !is_writable("settings.php") || 
	!is_writable(".htaccess") || !is_writable("posts.php"))
{
	?><span style="color:red;">Error, make sure this directory is writeable
	If you don't know what that means, try a Google search for 
	<a href="http://www.google.com/search?q=make+a+directory+writeable">make a 
	directory writeable</a> or <a href="http://www.google.com/search?q=file+persmissions">
	file permissions</a></span><?php
	exit;
}
if (!isset($_POST['password']) || !isset($_POST['title']))
{
?>
<form method="post">
Blog title<br>
<input type="text" name="title"><br><br>
Blog password<br>
<input type="password" name="password"><br>
Blog Description<br><br>
<input type="text" name="description" size="50"><br><br>
Google Analytics Code (optional)<br>
<textarea name="analytics"></textarea><br><br>
<input type="submit" value="Create My Blog!">
</form>
<?php
}
else {
$htaccess = <<<LONG
RewriteEngine on
RewriteCond %{HTTP_HOST} ^www\.(.*) [NC]
RewriteRule ^(.*) http://%1/$1 [R=301,L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^.*$ index.php?r=%{REQUEST_URI}&%{QUERY_STRING}
IndexIgnore *
LONG;
file_put_contents('.htaccess', $htaccess);
$posts = <<<'LONG'
<?php $posts= array (
  1259736228 => 
  array (
    'Title' => 'Hello World',
    'Essay' => 'Your first blog post!',
  ),
)?>
LONG;
file_put_contents('.htaccess', $posts);
$settings = <<<LONG
<?php
define("BLOG_PASSWORD","{$_POST['password']}");
define("BLOG_TITLE","{$_POST['title']}");
define("BLOG_DESCRIPTION","{$_POST['description']}");

function blog_analytics()
{
	?>
	{$_POST['analytics']}
	<?php
}
?>
LONG;
file_put_contents('settings.php', $posts);
$index = <<<'NOWDOC'
<?php
class Blog {
	public function __construct()
	{
		include("settings.php");
		include("posts.php");
		$this->posts = $posts;
		foreach ($posts as $key => $array) // Necessary for the pretty urls
		{
			$this->titles[$this->prettyUrl($array['Title'])] = $key;
		}
		$this->controller();
	}
	public function prettyUrl($title_string) // Turns a "$String' Like THIS" into a string_like_this
	{
		return strtolower(str_replace(" ","_",preg_replace('/[^a-z0-9 ]/i',"",$title_string)));
	}
	public function saveBlog()
	{
		if (isset($_POST['password']) && $_POST['password'] == BLOG_PASSWORD)
		{
			if (!isset($_GET['post'])) // create new post
			{
				$this->posts[time()] = array("Title" => $_POST['title'], "Essay" => $_POST['essay']);
			}
			elseif (isset($this->posts[$_GET['post']]) && isset($_POST['delete'])) // delete a post
			{
				unset($this->posts[$_GET['post']]);
			}
			elseif (isset($this->posts[$_GET['post']])) // edit a post
			{
				$this->posts[$_GET['post']] = array("Title" => $_POST['title'], "Essay" => $_POST['essay']);
			}
			krsort($this->posts); // Sort the posts in reverse chronological order
			file_put_contents("posts.php", "<?php \$posts= ".var_export($this->posts, true) . "?>");
		}
	}
	public function displayEditor ()
	{
		$invalid = (isset($_POST['password']) && $_POST['password'] != BLOG_PASSWORD ? ' <span style="color:red;">Invalid Password</span>' : "");
		$title_value = ""; $essay_value = ""; $delete_button = "";
		if (isset($_GET['post']) && isset($this->posts[$_GET['post']]))
		{
			$title_value = $this->posts[$_GET['post']]['Title'];
			$essay_value = $this->posts[$_GET['post']]['Essay'];
			$delete_button = "<input type=\"submit\" value=\"Delete\" name=\"delete\" onclick=\"return confirm('DELETE. Are you sure?');\">";
		}
		$content = <<<LONG
		<form method="post" action="">
		<table>
		<tr><td>Title</td><td><input type="text" name="title" size="25" value="$title_value"></td></tr>
		<tr><td>Content</td><td><textarea name="essay" rows="30" cols="80">$essay_value</textarea></td></tr>
		<tr><td>Password</td><td><input type="password" name="password">$invalid</td></tr>
		<tr><td></td><td><input type="submit" value="Save">$delete_button</td></tr></table>
		</form>
		Edit a Post:<br>
LONG;
		if (!is_writable("posts.php")) // can remove this once we do install.php
		{
			$content = "<span style=\"color:red;\">WARNING! posts.php not writeable</span>".$content;
		}
		foreach ($this->posts as $key => $array) // display links to edit posts
		{
			$content .= "<a href=\"write?post=".$key."\">{$array['Title']}</a><br>";
		}
		$this->displayPage("Editor","Edit your blog",$content);
	}
	public function controller() // There are 3 pages: Editor, Post, Homepage
	{
		if (isset($_GET['r']) && $_GET['r'] == "/write") // Editor
		{
			$this->saveBlog();
			$this->displayEditor();
		}
		elseif (isset($_GET['r']) && isset($this->titles[substr($_GET['r'],1)]) ) // Post
		{
			$post = $this->posts[$this->titles[substr($_GET['r'],1)]];
			$this->displayPage($post['Title'],substr($post['Essay'],0,100),
			"<h1>{$post['Title']}</h1><div>".nl2br($post['Essay'])."<br><br>Posted ".date("m/d/Y",$this->titles[substr($_GET['r'],1)])."</div>");
		}
		else { // Homepage
			$all_posts = ""; // Might want to limit it to most recent 5 or so posts.
			foreach ($this->posts as $key => $post)
			{
				$all_posts .= "<h1><a href=\"".$this->prettyUrl($post['Title'])."\">{$post['Title']}</a></h1><div>".nl2br($post['Essay'])."<br><br>Posted ".date("m/d/Y", $key )."</div><br><br>";
			}
			$this->displayPage(BLOG_TITLE, BLOG_DESCRIPTION,
			$all_posts); 
		}
	}
	public function displayPage($title, $description, $body)
	{
		?>
			<html>
			<head>
			<style type="text/css">
			body {font-family: arial; color: #222222; padding: 20px;}
			h1 {margin-top: 0px;}
			</style>
			<title><?php echo $title;?></title>
			<meta name="description" content="<?php echo $description;?>">
			</head>
			<body><table><tr>
			<td>
				<?php echo $body; ?>
			</td>
			<td valign="top" style="width:30%; text-align:right;">
				<a href="/" style="text-decoration:none;"><?php echo BLOG_TITLE;?></a><br><br>
				<?php 
					foreach ($this->posts as $post)
					{
						?><a href="/<?php echo $this->prettyUrl($post['Title']);?>">
						<?php echo $post['Title'];?></a><br><?php
					}
				?>
				<br><a href="/write" rel="nofollow">Admin</a><br>
			</td>
			</tr></table>
			<?php blog_analytics();?>
			</body>
			</html>
		<?php
	}
}
$blog = new Blog;
?>
NOWDOC;
file_put_contents('index.php', $index);
}
?>