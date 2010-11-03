<?php
class Blog {
    
	public $version = "v0.847";
	
	public function __construct()
	{	// set default settings
		$this->settings = array("BLOG_TITLE" => "My blog",
		"BLOG_DESCRIPTION"=>"A blog experiment.",
		"BLOG_URL"=> "http://".$_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],
		"BLOG_NAVIGATION_HEADER"=>"",
		"BLOG_NAVIGATION_FOOTER"=>"",
		"BLOG_FOOTER"=>"",
		"BLOG_CSS"=>"body {font-family: arial; color: #222222; padding: 20px;}
h1 {margin-top: 0px; border-bottom: 1px solid #999999; font-size:26px;}
h1 a{text-decoration:none; color: #0000AA;}
#content {float:left; width:70%;margin-right:10px;}
#navigation {font-size:.8em;background:#F9F9F9; float:left; width:25%;padding: 8px;}
#navigation a{display: block; padding: 3px; text-decoration:none; color:#0000AA;}
#navigation a:hover {background: #f9f9aa;}",
		"BLOG_HEADER"=>"",
		"BLOG_HEAD_SCRIPTS" => "",
		"POST_FOOTER" => "");
		$this->install();
		include("data.php");
		$this->posts = $data['posts'];
		$this->password = $data['password'];
		foreach ($data['settings'] as $key => $value)
		{
			$this->settings[$key] = $value;
		}
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
	{	echo "<span style=\"color:red;\">$message</span>";exit;}
	
	public function success($message)
	{	echo "<span style=\"color:green;\">$message</span>";}
	
	public function pw() // returns true if correct password
	{	if (isset($_POST['password']) && (md5($_POST['password'] . "breckrand") == $this->password)){return true;} $this->error("Invalid Password");}
	
	public function prettyUrl($title_string) // cleans a string
	{	return strtolower(str_replace(" ","_",preg_replace('/[^a-z0-9 ]/i',"",$title_string)));}
	
	public function saveBlog()
	{
	    if (count($_POST) && $this->pw())
		{
			if (!isset($_GET['post'])) // create new post
			{	$time = time();
				if (strlen($_POST['title']) < 1){$this->error("Title can't be blank");}
				$this->posts[$time] = array("Title" => $_POST['title'], "Essay" => $_POST['essay']);
				$this->success("<a href=\"".$this->prettyUrl($_POST['title'])."\">Post created!</a> | <a href=\"write?post={$time}\">Edit it</a>");
			}
			elseif (isset($this->posts[$_GET['post']]) && isset($_POST['delete'])) // delete a post
			{
				unset($this->posts[$_GET['post']]);$this->saveData();
				$this->success("Post deleted. <a href=\"write\">Back</a>");exit;
			}
			elseif (isset($this->posts[$_GET['post']])) // edit a post
			{
				$this->posts[$_GET['post']] = array("Title" => $_POST['title'], "Essay" => $_POST['essay']);
				$this->success("<a href=\"".$this->prettyUrl($_POST['title'])."\">Post updated!</a>");
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
	
	public function format_post($post)
	{
		if (file_exists("markdown.php") && !preg_match('/^<nomarkdown>/',$post))
		{
			include_once("markdown.php"); 
			return Markdown($post);
		}
		return nl2br($post);
	}

	public function displayEditor ()
	{
	    $title_value = ""; $essay_value = ""; $delete_button = "";
		if (isset($_GET['post']) && isset($this->posts[$_GET['post']]))
		{
			$title_value = $this->posts[$_GET['post']]['Title'];
			$essay_value = $this->posts[$_GET['post']]['Essay'];
			$delete_button = "<input type=\"submit\" value=\"Delete\" name=\"delete\" onclick=\"return confirm('DELETE. Are you sure?');\"><br><br><a href=\"write\">Create new post</a>";
		}
		(is_writable("data.php") ? "" : $this->error("WARNING! data.php not writeable") );
		?><div style="font-family:Arial;"><table style="width:100%;" cellpadding="10px"><tr>
		<td width="62.5%" valign="top"><form method="post" action=""><table style="width:100%;">
		<tr><td>Title</td><td style="width:100%;"><input type="text" name="title" style="width:100%;" value="<?php echo htmlentities($title_value)?>"></td></tr>
		<tr><td>Content</td><td><textarea name="essay" rows="15" style="width:100%;"><?php echo $essay_value?></textarea></td></tr>
		<tr><td>Password</td><td><input type="password" name="password"></td></tr>
		<tr><td></td><td><input type="submit" value="Save"><?php echo $delete_button?></td></tr></table></form></td>
		<td style="color:#999999; background: #f9f9f9;">
		<a href="index.php" style="text-decoration:none;"><?php echo BLOG_TITLE?></a><br><br>
		<b>Edit</b><br>
		<?php foreach ($this->posts as $key => $array) // display links to edit posts
		{ echo "<a href=\"write?post=".$key."\">{$array['Title']}</a><br>";}
		?><br><br><br><b>Upload File</b>
		<form action="upload" method="post" enctype="multipart/form-data"><input type="file" name="file"><br>Password<br><input type="password" name="password">
		<input type="submit" value="Upload"></form>
		<br><br><br><b>Settings</b>
			<form method="post" action="editsettings">
				<?php foreach ($this->settings as $key => $value)
				{?><?php echo ucfirst(strtolower(str_replace("_"," ",$key)));?><br><textarea style="width:100%;" rows="7" name="<?php echo $key;?>"><?php echo $value;?></textarea><br><br><?php }?>
				Password<br><input type="password" name="password">
				<input type="submit" value="Save">
			</form><br><br><br><b>Upgrade</b>
			<br>brecksblog version: <?php echo $this->version;?><br> <form action="upgrade" method="post">Password<br><input type="password" name="password"><input type="submit" value="Upgrade"></form></td></tr></table></div><?php
	}
	
	public function controller()
	{
	    if (isset($_GET['r']))
	    {
			$url = array_pop(explode("/",$_GET['r']));  // Get the Redirect Path
			if ($url == "write"){$this->saveBlog();$this->displayEditor();}
			elseif ($url == "upgrade" && $this->pw())
			{
				file_put_contents("index.php",file_get_contents("http://brecksblog.com/newest/index.php")) or $this->error("File permission problem. Change the file permissions on this directory.");
				$this->success("Blog updated! <a href=\"write\">Admin</a>");exit;
			}
			elseif ($url == "upload" && $this->pw())
			{
				if (!preg_match('/(gif|jpeg|jpg|png|mov|avi|xls|doc|pdf|txt|html|htm|css|js)/i',end(explode('.', $_FILES["file"]["name"]))))
				{
					$this->error("You can't upload that type of file."); exit;
				}
				move_uploaded_file($_FILES["file"]["tmp_name"],$_FILES["file"]["name"]);
				$this->success("File <a target=\"_blank\" href=\"{$_FILES["file"]["name"]}\">saved</a> as {$_FILES["file"]["name"]}");
				$this->displayEditor();
			}
			elseif ($url == "editsettings" && $this->pw())
			{
				unset($_POST['password']);
				$this->settings = $_POST;
				$this->saveData();
				$this->success("Settings saved.");
				$this->displayEditor();
			}
			elseif ($url == "json")
			{
			    echo $_GET['callback'].json_encode($this->posts);
			}
			elseif ($url == "feed")
			{ 
			    $this->displayFeed();
			}
			elseif (isset($this->titles[$url]) ) // Post
			{
				$post = $this->posts[$this->titles[$url]];
				$this->displayPage($post['Title'],substr($post['Essay'],0,100),
				"<h1>{$post['Title']}</h1><div>".$this->format_post($post['Essay'])."<br><br><div class=\"dateposted\">Posted ".date("m/d/Y",$this->titles[$url])."</div>". POST_FOOTER ."</div>");
			}
			else
			{
				?>Oops! File not found. <a href="index.php">Back to blog</a>.<?php
			}
		}
		else // Display Homepage
		{
			$all_posts = ""; // Might want to limit it to most recent 5 or so posts.
			foreach ($this->posts as $key => $post)
			{
				$all_posts .= "<h1><a href=\"".$this->prettyUrl($post['Title'])."\">{$post['Title']}</a></h1><div>".$this->format_post(substr(strip_tags($post['Essay']),0,150))."<a href=\"".$this->prettyUrl($post['Title'])."\">...continue to full essay.</a><br><br><div class=\"dateposted\">Posted ".date("m/d/Y", $key )."</div></div><br><br>";
			}
			$this->displayPage(BLOG_TITLE, BLOG_DESCRIPTION,
			$all_posts); 
		}
	}
	
	public function displayPage($title, $description, $body)
	{
  ?><!doctype html>
  <head><?php echo BLOG_HEAD_SCRIPTS?>
    <style type="text/css"><?php echo BLOG_CSS?></style>
    <title><?php echo $title?></title>
	<meta name="description" content="<?php echo str_replace('"',"",$description);?>">
  </head>
  <body>
    <div id="header">
      <?php echo BLOG_HEADER?>
    </div>
	<div id="content">
	  <?php echo $body?>
	</div>
	<div id="navigation">
	  <?php echo BLOG_NAVIGATION_HEADER?>
	  <?php foreach ($this->posts as $post) { 
	        echo '      <a href=\"'.$this->prettyUrl($post['Title']).'">' . $post['Title'] . '</a>';
        }?>
      <br><a href="feed">RSS</a>
      <a href="write" rel="nofollow">Admin</a>
      <?php echo BLOG_NAVIGATION_FOOTER?>
	</div>
    <div id="footer">
      <?php echo BLOG_FOOTER?>
    </div>
  </body>
</html><?php 
	}
	
	public function displayFeed()
	{	header('Content-Type: text/xml');
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
						<description><?php echo $this->format_post(str_replace("&","&amp;",strip_tags($post['Essay'])));?></description>
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
		    return false; // dont overwrite these things
		elseif (!isset($_POST['password']) || strlen($_POST['password']) < 1 ) 
		{
			file_put_contents("test_file_permissions","1") or $this->error("WARNING! Directory not writeable. Change the file permissions before installing."); unlink("test_file_permissions",""); ?>
			<h2>Install brecksblog</h2>
			<form method="post">Choose a <b>strong</b> password <input name="password" type="password"><input type="submit" value="Install!"></form>
			<?php exit;
		}
		else // Run the install
		{
		    file_put_contents(".htaccess","RewriteEngine on
RewriteCond %{HTTP_HOST} ^www\.(.*) [NC]
RewriteRule ^(.*) http://%1/$1 [R=301,L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^.*$ index.php?r=%{REQUEST_URI}&%{QUERY_STRING}
IndexIgnore *");
		    $this->password = md5($_POST['password'] . "breckrand");
		    $this->posts = array( 1259736228=>array('Title' => 'Hello World',
		        'Essay' => 'Your first blog post!'));
		    $this->saveData();
		}
	}
}
$blog = new Blog;