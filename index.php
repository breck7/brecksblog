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
		if (isset($_GET['r']))
		{
			$url = array_pop(explode("/",$_GET['r']));  // Get the Redirect Path
			if ($url == "write") // Editor
			{
				$this->saveBlog();
				$this->displayEditor();
			}
			elseif (isset($this->titles[$url]) ) // Post
			{
				$post = $this->posts[$this->titles[$url]];
				$this->displayPage($post['Title'],substr($post['Essay'],0,100),
				"<h1>{$post['Title']}</h1><div>".nl2br($post['Essay'])."<br><br>Posted ".date("m/d/Y",$this->titles[$url])."</div>");
			}
			elseif ($url == "feed") // RSS Feed
			{
				$this->displayFeed();
			}
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
h1 {margin-top: 0px; border-bottom: 1px solid #999999; font-size:26px;}
                        h1 a{text-decoration:none; color: #0000AA;}
			/* theme inspired by http://crypto.stanford.edu/~blynn/c/index.html */
			#sidebar {font-size:.8em;background:#F9F9F9;
			margin-left: 40px;padding: 8px;}
			#sidebar a{display: block; padding: 3px;
			text-decoration:none; color:#0000AA;}
			#sidebar a:hover {background: #f9f9aa;}
			</style>
			<title><?php echo $title;?></title>
			<meta name="description" content="<?php echo str_replace('"',"",$description);?>">
			</head>
			<body><table><tr>
			<td>
				<?php echo $body; ?>
			</td>
			<td valign="top" style="width:30%;">
			<div id="sidebar">
				<a href="index.php" style="text-decoration:none;"><?php echo BLOG_TITLE;?></a><br><br>
				<?php 
					foreach ($this->posts as $post)
					{
						?><a href="<?php echo $this->prettyUrl($post['Title']);?>">
						<?php echo $post['Title'];?></a><?php
					}
					sidebar();
				?>
				<br><a href="write" rel="nofollow">Admin</a></div>
			</td>
			</tr></table>
			<?php blog_analytics();?>
			</body>
			</html>
		<?php
	}
	public function displayFeed()
	{
		header('Content-Type: text/xml');
		?><?php echo '<?xml version="1.0" encoding="ISO-8859-1" ?>';?>
			<rss version="0.91">
			<channel>
			<title><?php echo BLOG_TITLE;?></title>
			<link><?php echo BLOG_URL;?></link>
			<description><?php echo BLOG_DESCRIPTION;?></description>
			<language>en-us</language>
				<?php 
					foreach ($this->posts as $post)
					{
						?><item>
						<title><?php echo $post['Title'];?></title>
						<link><?php echo BLOG_URL . $this->prettyUrl($post['Title']);?></link>
						</item><?php
					}
				?>
			</channel>
			</rss>
		<?php
	}
}
$blog = new Blog;
?>
