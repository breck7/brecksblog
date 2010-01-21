<?php
class Blog {
	var $version = "v0.821";
	var $format_single_post;
	public function __construct()
	{
		$this->install();
		$this->format_single_post = 'nl2br'; // default format func
		if (file_exists("markdown.php")) {
			include_once("markdown.php"); $this->format_single_post = 'Markdown';
		}
		include("data.php");
		$this->posts = $data['posts'];
		$this->password = $data['password'];
		$this->settings = $data['settings'];
		foreach ($this->settings as $key => $value)
		{
			define($key,$value);
		}
		foreach ($this->posts as $key => $array) // Necessary for the pretty urls
		{
			$this->titles[$this->prettyUrl($array['Title'])] = $key;
		}
		$this->controller();
	}
	public function error($message)
	{ return "<span style=\"color:red;\">$message</span>";}
	public function pw() // returns true if correct password
	{
	if (isset($_POST['password']) && (md5($_POST['password'] . "breckrand") == $this->password)){return true;}
	echo $this->error("Invalid Password");
	exit;}
	public function prettyUrl($title_string) // Turns a "$String' Like THIS" into a string_like_this
	{ return strtolower(str_replace(" ","_",preg_replace('/[^a-z0-9 ]/i',"",$title_string)));}
	public function saveBlog()
	{
		if (count($_POST) && $this->pw())
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
			$this->saveData();
		}
	}
	public function saveData()
	{
		$data = array("posts" => $this->posts, "settings" => $this->settings, "password" => $this->password);
		file_put_contents("data.php", "<?php \$data= ".var_export($data, true) . "?>");
	}
	public function displayEditor ()
	{
		$title_value = ""; $essay_value = ""; $delete_button = "";
		if (isset($_GET['post']) && isset($this->posts[$_GET['post']]))
		{
			$title_value = $this->posts[$_GET['post']]['Title'];
			$essay_value = $this->posts[$_GET['post']]['Essay'];
			$delete_button = "<input type=\"submit\" value=\"Delete\" name=\"delete\" onclick=\"return confirm('DELETE. Are you sure?');\">";
		}
		echo(is_writable("data.php") ? "" : $this->error("WARNING! data.php not writeable") );
		?><div style="font-family:Arial;"><table style="width:100%;" cellpadding="10px"><tr>
		<td width="62.5%" valign="top"><form method="post" action=""><table style="width:100%;">
		<tr><td>Title</td><td style="width:100%;"><input type="text" name="title" style="width:100%;" value="<?=$title_value?>"></td></tr>
		<tr><td>Content</td><td><textarea name="essay" rows="15" style="width:100%;"><?=$essay_value?></textarea></td></tr>
		<tr><td>Password</td><td><input type="password" name="password"></td></tr>
		<tr><td></td><td><input type="submit" value="Save"><?=$delete_button?></td></tr></table></form></td>
		<td style="color:#999999; background: #f9f9f9;">
		<a href="index.php" style="text-decoration:none;"><?=BLOG_TITLE?></a><br><br>
		<b>Edit</b><br>
		<?php foreach ($this->posts as $key => $array) // display links to edit posts
		{ echo "<a href=\"write?post=".$key."\">{$array['Title']}</a><br>";}
		?><br><br><br><b>Settings</b>
			<form method="post" action="editsettings">
				<?php foreach ($this->settings as $key => $value)
				{?><?php echo ucfirst(strtolower(str_replace("_"," ",$key)));?><br><textarea style="width:100%;" rows="4" name="<?php echo $key;?>"><?php echo $value;?></textarea><br><br><?php }?>
				Password <input type="password" name="password">
				<input type="submit" value="Save">
			</form><br><br><br><b>Upgrade</b>
			<br>brecksblog version: <?php echo $this->version;?><br> <form action="upgrade" method="post">Password<input type="password" name="password"><input type="submit" value="Upgrade"></form></td></tr></table></div><?php
	}
	public function controller() // There are 3 pages: Editor, Post, Homepage+json+rss
	{
		if (isset($_GET['r']))
		{
			$url = array_pop(explode("/",$_GET['r']));  // Get the Redirect Path
			if ($url == "write"){$this->saveBlog();$this->displayEditor();}
			elseif ($url == "upgrade" && $this->pw())
			{
				file_put_contents("index.php",file_get_contents("http://brecksblog.com/newest/index.php")) or die($this->error("File permission problem. Change the file permissions on this directory."));
				header('Location: write');exit;
			}
			elseif ($url == "editsettings" && $this->pw()){
				unset($_POST['password']);
				$this->settings = $_POST;
				$this->saveData();
				header("Location: index.php");exit;
			}
			elseif ($url == "json"){echo $_GET['callback'].json_encode($this->posts);}
			elseif ($url == "feed") { $this->displayFeed();}
			elseif (isset($this->titles[$url]) ) // Post
			{
				$post = $this->posts[$this->titles[$url]];
				$this->displayPage($post['Title'],substr($post['Essay'],0,100),
				"<h1>{$post['Title']}</h1><div>".call_user_func($this->format_single_post, $post['Essay'])."<br><br>Posted ".date("m/d/Y",$this->titles[$url])."</div>");
			}
		}
		else { // Homepage
			$all_posts = ""; // Might want to limit it to most recent 5 or so posts.
			foreach ($this->posts as $key => $post)
			{
				$all_posts .= "<h1><a href=\"".$this->prettyUrl($post['Title'])."\">{$post['Title']}</a></h1><div>".call_user_func($this->format_single_post, $post['Essay'])."<br><br>Posted ".date("m/d/Y", $key )."</div><br><br>";
			}
			$this->displayPage(BLOG_TITLE, BLOG_DESCRIPTION,
			$all_posts); 
		}
	}
	public function displayPage($title, $description, $body)
	{
		?>
			<html>
			<head><?=BLOG_HEADER?>
			<title><?=$title?></title>
			<meta name="description" content="<?php echo str_replace('"',"",$description);?>">
			</head>
			<body><table width="100%"><tr>
			<td valign="top"><?=$body?>
			</td>
			<td valign="top" style="width:30%;">
			<div id="sidebar">
				<a href="index.php" style="text-decoration:none;"><?=BLOG_TITLE?></a><br><br>
				<?php 
					foreach ($this->posts as $post)
					{
						?><a href="<?php echo $this->prettyUrl($post['Title']);?>">
						<?php echo $post['Title'];?></a><?php
					}
					echo BLOG_SIDEBAR; 
				?>
				<br><a href="feed">RSS</a>
				<br><a href="write" rel="nofollow">Admin</a>
				</div>
			</td>
			</tr></table>
			<?=BLOG_FOOTER?>
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
						<description><?php echo call_user_func($this->format_single_post, str_replace("&","&amp;",strip_tags($post['Essay'])));?></description>
						</item><?php
					}
				?>
			</channel>
			</rss>
		<?php
	}
	public function install()
	{
		if (file_exists("data.php") || file_exists(".htaccess"))
		{ return false; } // dont overwrite these things
		elseif(!isset($_POST['password'])) {
		file_put_contents("test_file_permissions","");
		echo (is_writable("test_file_permissions") ? "" : $this->error("WARNING! Directory not writeable. Install will fail."));
		?>
		<h2>Install brecksblog</h2>
		<form method="post">
		Choose a Password <input name="password" type="password">
		<input type="submit" value="Install!">
		</form>
		<?php
		exit;
		}
		else {
		file_put_contents(".htaccess","RewriteEngine on
RewriteCond %{HTTP_HOST} ^www\.(.*) [NC]
RewriteRule ^(.*) http://%1/$1 [R=301,L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^.*$ index.php?r=%{REQUEST_URI}&%{QUERY_STRING}
IndexIgnore *");
		$this->password = md5($_POST['password'] . "breckrand");
		$this->settings = array("BLOG_TITLE" => "My blog",
		"BLOG_DESCRIPTION"=>"A blog experiment.",
		"BLOG_URL"=> "http://".$_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],
		"BLOG_SIDEBAR"=>"",
		"BLOG_FOOTER"=>"",
		"BLOG_HEADER"=>"<style type=\"text/css\">
body {font-family: arial; color: #222222; padding: 20px;}
h1 {margin-top: 0px; border-bottom: 1px solid #999999; font-size:26px;}
h1 a{text-decoration:none; color: #0000AA;}
#sidebar {font-size:.8em;background:#F9F9F9;
margin-left: 40px;padding: 8px;}
#sidebar a{display: block; padding: 3px;
text-decoration:none; color:#0000AA;}
#sidebar a:hover {background: #f9f9aa;}
</style>");
		$this->posts = array(1259736228=>array('Title' => 'Hello World',
		'Essay' => 'Your first blog post!'));
		$this->saveData();
		}
	}
}
$blog = new Blog; ?>